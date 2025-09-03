<?php
namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Uid\Uuid;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class ResetPasswordController extends AbstractController
{
    #[Route('/reset-password', name: 'app_reset_password_request', methods: ['GET', 'POST'])]
    public function request(Request $request, EntityManagerInterface $em, MailerInterface $mailer): Response
    {
        $form = $this->createFormBuilder()
            ->add('email', EmailType::class, ['label' => 'Votre email'])
            ->add('submit', SubmitType::class, ['label' => 'Envoyer le lien de réinitialisation'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user = $em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
            if ($user) {
                $token = Uuid::v4()->toRfc4122();
                $user->setResetToken($token);
                $user->setResetTokenExpires((new \DateTime())->modify('+1 hour'));
                $em->flush();
                $email = (new Email())
                    ->from('no-reply@bucket-list.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->text('Pour réinitialiser votre mot de passe, cliquez sur ce lien : ' . $this->generateUrl('app_reset_password_reset', ['token' => $token], 0));
                $mailer->send($email);
                $this->addFlash('success', 'Un email de réinitialisation a été envoyé.');
            } else {
                $this->addFlash('error', 'Aucun utilisateur trouvé avec cet email.');
            }
        }
        return $this->render('reset_password/request.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/reset-password/{token}', name: 'app_reset_password_reset', methods: ['GET', 'POST'])]
    public function reset(string $token, Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $hasher): Response
    {
        $user = $em->getRepository(User::class)->findOneBy(['resetToken' => $token]);
        if (!$user || $user->getResetTokenExpires() < new \DateTime()) {
            $this->addFlash('error', 'Lien de réinitialisation invalide ou expiré.');
            return $this->redirectToRoute('app_reset_password_request');
        }
        $form = $this->createFormBuilder()
            ->add('plainPassword', PasswordType::class, ['label' => 'Nouveau mot de passe'])
            ->add('submit', SubmitType::class, ['label' => 'Réinitialiser'])
            ->getForm();
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();
            $user->setPassword($hasher->hashPassword($user, $data['plainPassword']));
            $user->setResetToken(null);
            $user->setResetTokenExpires(null);
            $em->flush();
            $this->addFlash('success', 'Mot de passe réinitialisé. Vous pouvez vous connecter.');
            return $this->redirectToRoute('app_login');
        }
        return $this->render('reset_password/reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}


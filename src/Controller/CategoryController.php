<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class CategoryController extends AbstractController
{
    #[Route('/category', name: 'app_category')]
    public function index(\App\Repository\CategoryRepository $categoryRepository): Response
    {
        $categories = $categoryRepository->findAll();
        return $this->render('category/index.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/categorie/admin/new', name: 'app_category_admin_new', methods: ['GET', 'POST'])]
    public function adminNew(\Symfony\Component\HttpFoundation\Request $request, \Doctrine\ORM\EntityManagerInterface $em): Response
    {
        $category = new \App\Entity\Category();
        $form = $this->createForm(\App\Form\CategoryType::class, $category);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Catégorie créée avec succès.');
            return $this->redirectToRoute('app_main');
        }

        return $this->render('category/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/categorie/admin/delete/{id}', name: 'app_category_admin_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function adminDelete(int $id, \Symfony\Component\HttpFoundation\Request $request, \Doctrine\ORM\EntityManagerInterface $em, \App\Repository\CategoryRepository $categoryRepository): Response
    {
        $category = $categoryRepository->find($id);
        if (!$category) {
            throw $this->createNotFoundException('Catégorie introuvable');
        }
        if (!$this->isGranted('ROLE_ADMIN')) {
            throw $this->createAccessDeniedException('Seul un administrateur peut supprimer une catégorie.');
        }
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_category_' . $category->getId(), $token)) {
            $this->addFlash('error', 'Jeton CSRF invalide.');
            return $this->redirectToRoute('app_category');
        }
        $em->remove($category);
        $em->flush();
        $this->addFlash('success', 'Catégorie supprimée.');
        return $this->redirectToRoute('app_category');
    }
}

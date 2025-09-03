<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Utilisateur test
        $user = new User();
        $user->setEmail('test@test.com');
        $user->setPseudo('test');
        $user->setRoles(['ROLE_USER']);
        $user->setIsAdmin(false);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'test1234'));
        $manager->persist($user);

        // Utilisateur admin
        $admin = new User();
        $admin->setEmail('admin@admin.com');
        $admin->setPseudo('admin');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setIsAdmin(true);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin1234'));
        $manager->persist($admin);

        $manager->flush();
    }
}

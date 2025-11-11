<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class UserTestController extends AbstractController
{
    #[Route('/test-create-admin', name: 'test_create_admin')]
    public function createAdmin(EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = new User();
        $user->setEmail('adminn@example.com');
        $user->setFirstName('Adminn');
        $user->setLastName('Userr');
        $user->setRoles([User::ROLE_ADMIN]);

        // Mot de passe en clair
        $plainPassword = 'AdminPass1234!';

        // Hashage du mot de passe
        $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashedPassword);

        $em->persist($user);
        $em->flush();

        return new Response('Admin créé avec succès. Email: admin@example.com, Mot de passe: AdminPass1234!');
    }
}
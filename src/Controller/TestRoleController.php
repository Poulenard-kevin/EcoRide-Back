<?php
// src/Controller/TestRoleController.php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestRoleController extends AbstractController
{
    #[Route('/test/role/{id<\d+>}', name: 'test_role')]
    public function testRole(int $id, EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find($id);
        if (!$user) {
            return new Response('Utilisateur non trouvé', 404);
        }

        $roles = [];
        if ($user->isAdmin()) {
            $roles[] = 'Admin';
        }
        if ($user->isEmployee()) {
            $roles[] = 'Employé';
        }
        if ($user->isUser()) {
            $roles[] = 'Utilisateur';
        }
        if ($user->isVisitor()) {
            $roles[] = 'Visiteur';
        }

        return new Response('Rôles de l\'Utilisateur : ' . implode(', ', $roles));
    }
}
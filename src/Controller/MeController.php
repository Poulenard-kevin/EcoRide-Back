<?php
// src/Controller/MeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class MeController extends AbstractController
{
    /**
     * @Route("/api/me", name="api_me", methods={"GET"})
     */
    public function __invoke(Security $security): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return $this->json(['message' => 'Not authenticated'], 401);
        }

        // Retourne uniquement les champs nécessaires — pas de sérialisation Doctrine/ORM
        return $this->json([
            'id' => $user->getId(),
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
        ]);
    }
}
<?php
// src/Controller/Api/UserMeController.php
namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/api/users/me", name="api_user_me", methods={"GET"}, priority=100)
 */
class UserMeController extends AbstractController
{
    public function __invoke(Security $security, SerializerInterface $serializer): JsonResponse
    {
        $user = $security->getUser();
        if (!$user) {
            return new JsonResponse(['message' => 'Authentification requise.'], 401);
        }

        $json = $serializer->serialize($user, 'json', ['groups' => ['user:read']]);

        return new JsonResponse($json, 200, [], true);
    }
}
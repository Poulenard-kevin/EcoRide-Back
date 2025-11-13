<?php
// src/Controller/Api/LoginCheckController.php
namespace App\Controller\Api;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class LoginCheckController extends AbstractController
{
    #[Route('/api/login_check', name: 'api_login_check', methods: ['POST'])]
    public function __invoke(
        Request $request,
        UserRepository $users,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;

        if (!$email || !$password) {
            return new JsonResponse(['message' => 'Email and password required'], 400);
        }

        $user = $users->findOneBy(['email' => $email]);
        if (!$user) {
            return new JsonResponse(['message' => 'Invalid credentials'], 401);
        }

        if (!$passwordHasher->isPasswordValid($user, $password)) {
            return new JsonResponse(['message' => 'Invalid credentials'], 401);
        }

        // Générer un apiToken si absent
        if (empty($user->getApiToken())) {
            $token = bin2hex(random_bytes(32));
            $user->setApiToken($token);
            $em->persist($user);
            $em->flush();
        }

        return new JsonResponse(['token' => $user->getApiToken()], 200);
    }
}
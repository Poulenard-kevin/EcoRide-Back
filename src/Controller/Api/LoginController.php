<?php

namespace App\Controller\Api;

use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginController extends AbstractController
{
    public function __construct(
        private UserRepository $users,
        private UserPasswordHasherInterface $hasher,
        private EntityManagerInterface $em,
        private ?LoggerInterface $logger = null
    ) {}

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function login(Request $request): JsonResponse
    {
        // 1) Récupérer les données du client (JSON ou form-data ou query)
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            // si ce n'est pas du JSON, récupérer les données POST (form-data) ou query
            $data = $request->request->all();
        }

        $email = trim((string)($data['email'] ?? $data['username'] ?? $request->query->get('email', '')));
        $password = $data['password'] ?? $request->request->get('password') ?? $request->query->get('password');

        if ($this->logger) {
            $this->logger->debug('Login attempt', ['email' => $email ? substr($email, 0, 50) : null]);
        }

        if (empty($email) || empty($password)) {
            return $this->json(['message' => 'Missing credentials'], 400);
        }

        // 2) Chercher l'utilisateur
        $user = $this->users->findOneBy(['email' => $email]);
        if (!$user) {
            // Ne pas divulguer si l'email est absent ; message générique
            return $this->json(['message' => 'Invalid credentials'], 401);
        }

        // 3) Vérifier le mot de passe
        if (!$this->hasher->isPasswordValid($user, $password)) {
            return $this->json(['message' => 'Invalid credentials'], 401);
        }

        // 4) Générer ou récupérer apiToken
        $token = $user->getApiToken();
        if (empty($token)) {
            $token = bin2hex(random_bytes(32));
            $user->setApiToken($token);

            // Persister uniquement si on a modifié l'utilisateur
            $this->em->persist($user);
            $this->em->flush();
        }

        // 5) Réponse standardisée pour le front
        $response = [
            'id' => $user->getId(),
            'firstName' => $user->getFirstName(),
            'lastName' => $user->getLastName(),
            'email' => $user->getEmail(),
            'apiToken' => $token,
            'roles' => $user->getRoles(),
        ];

        return $this->json($response, 200);
    }
}
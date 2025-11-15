<?php
// src/Controller/Api/DebugAuthController.php
namespace App\Controller\Api;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

class DebugAuthController
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * @Route("/api/debug-auth", name="api_debug_auth", methods={"GET"})
     */
    public function __invoke(): JsonResponse
    {
        $user = $this->security->getUser();
        $roles = null;
        $userId = null;
        $userClass = null;

        if ($user) {
            $roles = method_exists($user, 'getRoles') ? $user->getRoles() : null;
            $userId = method_exists($user, 'getId') ? $user->getId() : null;
            $userClass = get_class($user);
        }

        return new JsonResponse([
            'ok' => true,
            'authenticatedUser' => (bool) $user,
            'userId' => $userId,
            'userClass' => $userClass,
            'roles' => $roles,
        ], $user ? 200 : 200, ['Content-Type' => 'application/json']);
    }
}
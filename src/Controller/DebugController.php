<?php
// src/Controller/DebugController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Annotation\Route;

class DebugController extends AbstractController
{
    #[Route('/_debug/session', name: 'debug_session', methods: ['GET'])]
    public function session(Request $request, LoggerInterface $logger): JsonResponse
    {
        $user = $this->getUser();
        $session = $request->getSession();

        $data = [
            'user' => $user ? [
                'id' => method_exists($user, 'getId') ? $user->getId() : null,
                'identifier' => method_exists($user, 'getUserIdentifier') ? $user->getUserIdentifier() : (method_exists($user, 'getEmail') ? $user->getEmail() : null),
                'class' => get_class($user),
            ] : null,
            'session' => $session ? $session->all() : null,
            'session_id' => $session ? $session->getId() : null,
            'cookies' => $request->cookies->all(),
            'cookie_header' => $request->headers->get('cookie'),
        ];

        $logger->info('Debug session endpoint', $data);

        return new JsonResponse($data);
    }
}

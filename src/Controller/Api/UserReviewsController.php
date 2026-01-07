<?php

namespace App\Controller\Api;

use App\Repository\ReviewRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class UserReviewsController extends AbstractController
{
    private ReviewRepository $reviewRepository;
    private UserRepository $userRepository;

    public function __construct(ReviewRepository $reviewRepository, UserRepository $userRepository)
    {
        $this->reviewRepository = $reviewRepository;
        $this->userRepository = $userRepository;
    }

    /**
     * Retourne les avis validés ciblant l'utilisateur {id}.
     * L'operation ApiPlatform doit définir "read"=false pour que cette méthode soit appelée.
     */
    public function __invoke(Request $request, ?int $id = null): JsonResponse
    {
        // id peut venir des attributs de route
        $id = $id ?? $request->attributes->get('id');
        if (!$id) {
            throw new NotFoundHttpException('Missing user id');
        }

        $user = $this->userRepository->find($id);
        if (!$user) {
            throw new NotFoundHttpException(sprintf('Utilisateur %d introuvable', $id));
        }

        // Récupère les avis validés ciblant cet utilisateur, triés par date descendante
        $reviews = $this->reviewRepository->findBy(
            ['target' => $user, 'validated' => true],
            ['date' => 'DESC']
        );

        // Sérialisation via les groupes (review:read). Ajoute user:read si tu veux inclure infos auteur/cible.
        return $this->json($reviews, 200, [], ['groups' => ['review:read', 'user:read']]);
    }
}
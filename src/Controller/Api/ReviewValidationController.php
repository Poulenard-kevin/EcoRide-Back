<?php
//src/Controller/Api/ReviewValidationController.php

namespace App\Controller\Api;

use App\Entity\Review;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Contrôleur API pour la validation et le refus des avis.
 *
 * @Route("/api/reviews", name="api_reviews_")
 */
class ReviewValidationController extends AbstractController
{
    /**
     * Validate a review (accessible to employees and admins).
     *
     * @Route("/{id}/validate", name="validate", methods={"POST"})
     */
    public function validateReview(int $id, EntityManagerInterface $em, ReviewRepository $repo): JsonResponse
    {
        // Récupérer l'avis en base
        $review = $repo->find($id);
        if (!$review) {
            // Si l'avis n'existe pas, retourner 404
            return $this->json(['error' => 'Avis non trouvé'], 404);
        }

        // Vérifier que l'utilisateur a le rôle employé ou admin
        if (!$this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            // Accès refusé si l'utilisateur n'a pas les droits requis
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // Marquer l'avis comme validé — utiliser les constantes de l'entité si disponible
        if ($review instanceof Review) {
            $review->setStatus(Review::STATUS_APPROVED);
            $review->setValidated(true);
        } else {
            // fallback - si l'entité diffère
            $review->setStatus('APPROVED');
            if (method_exists($review, 'setValidated')) {
                $review->setValidated(true);
            }
        }

        // Persister la modification
        $em->flush();

        // Réponse de succès
        return $this->json(['ok' => true]);
    }

    /**
     * Refuse a review (accessible to employees and admins).
     *
     * @Route("/{id}/refuse", name="refuse", methods={"POST"})
     */
    public function refuseReview(int $id, EntityManagerInterface $em, ReviewRepository $repo): JsonResponse
    {
        // Récupérer l'avis en base
        $review = $repo->find($id);
        if (!$review) {
            // Si l'avis n'existe pas, retourner 404
            return $this->json(['error' => 'Avis non trouvé'], 404);
        }

        // Vérifier que l'utilisateur a le rôle employé ou admin
        if (!$this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            // Accès refusé si l'utilisateur n'a pas les droits requis
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // Marquer l'avis comme refusé — utiliser les constantes de l'entité si disponible
        if ($review instanceof Review) {
            $review->setStatus(Review::STATUS_REJECTED);
            $review->setValidated(false);
        } else {
            // fallback - si l'entité diffère
            $review->setStatus('REJECTED');
            if (method_exists($review, 'setValidated')) {
                $review->setValidated(false);
            }
        }

        // Persister la modification
        $em->flush();

        // Réponse de succès
        return $this->json(['ok' => true]);
    }
}
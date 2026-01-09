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
        $review = $repo->find($id);
        if (!$review) {
            return $this->json(['error' => 'Avis non trouvé'], 404);
        }

        if (!$this->isGranted('ROLE_EMPLOYEE') && !$this->isGranted('ROLE_ADMIN')) {
            return $this->json(['error' => 'Accès refusé'], 403);
        }

        // 1. Marquer l'avis comme validé
        $review->setStatus(Review::STATUS_APPROVED);
        $review->setValidated(true);
        
        // On flush une première fois pour que l'avis soit compté dans le calcul SQL
        $em->flush();

        // 2. Recalculer la moyenne
        $target = $review->getTarget();
        if ($target) {
            // On appelle la méthode du repository qu'on a créée
            $avg = $repo->getAverageRatingForUser($target);
            
            // On arrondit à l'unité (ex: 3.1 -> 3)
            $newAverage = ($avg === null) ? 5.0 : (float) round($avg);
            
            // On force la mise à jour de l'utilisateur
            $target->setAverageRating($newAverage);
            
            // On persiste explicitement l'utilisateur pour être sûr
            $em->persist($target);
            $em->flush();
            
            return $this->json([
                'ok' => true, 
                'message' => 'Avis validé et moyenne mise à jour',
                'new_rating' => $newAverage
            ]);
        }

        return $this->json(['ok' => true, 'message' => 'Avis validé mais aucun utilisateur cible trouvé']);
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
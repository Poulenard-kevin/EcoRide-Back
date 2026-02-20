<?php
// src/EventListener/ReviewChangedListener.php

namespace App\EventListener;

use App\Entity\Review;
use Doctrine\ORM\Event\LifecycleEventArgs;
use App\Repository\ReviewRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class ReviewChangedListener
{
    private ReviewRepository $reviewRepository;
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(ReviewRepository $reviewRepository, EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->reviewRepository = $reviewRepository;
        $this->em = $em;
        $this->logger = $logger;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleChange($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleChange($args);
    }

    public function postRemove(LifecycleEventArgs $args): void
    {
        $this->handleChange($args);
    }

    private function handleChange(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Review) {
            return;
        }

        $targetUser = $entity->getTarget();
        if (!$targetUser) {
            return;
        }

        try {
            $avg = $this->reviewRepository->getAverageRatingForUser($targetUser);
            // On arrondit à 1 décimale pour plus de précision ou entier selon ton choix
            $newAverage = ($avg === null) ? 5.0 : (float) round($avg, 1);

            $current = $targetUser->getAverageRating();
            
            // On ne met à jour que si la note a vraiment changé
            if ($current === null || abs((float)$current - $newAverage) > 0.1) {
                $targetUser->setAverageRating($newAverage);
                
                // On utilise une requête DQL directe pour éviter de déclencher 
                // d'autres évènements qui pourraient causer une boucle infinie
                $this->em->createQuery('UPDATE App\Entity\User u SET u.averageRating = :avg WHERE u.id = :id')
                         ->setParameter('avg', $newAverage)
                         ->setParameter('id', $targetUser->getId())
                         ->execute();
            }
        } catch (\Throwable $e) {
            $this->logger->error('Erreur lors du recalcul de la moyenne des avis', [
                'exception' => $e->getMessage(),
                'reviewId' => $entity->getId() ?? 'new',
                'targetUserId' => $targetUser->getId(),
            ]);
        }
    }
}
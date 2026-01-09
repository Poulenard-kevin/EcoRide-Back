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
        $newAverage = ($avg === null) ? 5.0 : (float) round($avg);

        $target->setAverageRating($newAverage);

        $current = $targetUser->getAverageRating();
        if ($current === null || abs($current - $newAverage) > 0.0001) {
            $targetUser->setAverageRating($newAverage);
            $this->em->persist($targetUser);

            // flush ici provoque un flush imbriqué si on est déjà dans une transaction.
            // Pour la plupart des petites applications c'est acceptable.
            $this->em->flush();
        }
    } catch (\Throwable $e) {
        $this->logger->error('Erreur lors du recalcul de la moyenne des avis', [
            'exception' => $e,
            'reviewId' => $entity->getId() ?? null,
            'targetUserId' => $targetUser->getId() ?? null,
        ]);
    }
}
}
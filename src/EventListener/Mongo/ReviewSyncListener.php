<?php

namespace App\EventListener\Mongo;

use App\Document\ReviewMongo;
use App\Entity\Review;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ORM\Event\LifecycleEventArgs;

class ReviewSyncListener
{
    private DocumentManager $documentManager;

    public function __construct(DocumentManager $documentManager)
    {
        $this->documentManager = $documentManager;
    }

    public function postPersist(LifecycleEventArgs $args): void
    {
        $this->handleReview($args);
    }

    public function postUpdate(LifecycleEventArgs $args): void
    {
        $this->handleReview($args);
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Review) {
            return;
        }

        $reviewMongo = $this->documentManager->getRepository(ReviewMongo::class)->findOneBy([
            'userId' => $entity->getAuthor()?->getId(),
            'comment' => $entity->getComment(),
            'note' => $entity->getRating(),
        ]);

        if ($reviewMongo) {
            $this->documentManager->remove($reviewMongo);
            $this->documentManager->flush();
        }
    }

    private function handleReview(LifecycleEventArgs $args): void
    {
        $entity = $args->getObject();

        if (!$entity instanceof Review) {
            return;
        }

        $reviewMongo = $this->documentManager->getRepository(ReviewMongo::class)->findOneBy([
            'userId' => $entity->getAuthor()?->getId(),
            'comment' => $entity->getComment(),
            'note' => $entity->getRating(),
        ]);

        if (!$reviewMongo) {
            $reviewMongo = new ReviewMongo();
        }

        $reviewMongo->setComment($entity->getComment() ?? '');
        $reviewMongo->setNote($entity->getRating() ?? 0);
        $reviewMongo->setUserId($entity->getAuthor()?->getId() ?? 0);
        $reviewMongo->setCreatedAt($entity->getDate() ?? new \DateTime());

        $this->documentManager->persist($reviewMongo);
        $this->documentManager->flush();
    }
}
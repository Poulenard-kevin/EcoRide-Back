<?php
// src/Controller/TestReviewTargetV2Controller.php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Carpool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestReviewTargetV2Controller extends AbstractController
{
    #[Route('/test/review-target-v2', name: 'test_review_target_v2')]
    public function test(EntityManagerInterface $em): Response
    {
        $author = $em->getRepository(User::class)->find(1);
        $target = $em->getRepository(User::class)->find(2);
        $carpool = $em->getRepository(Carpool::class)->find(1);

        if (!$author || !$target || !$carpool) {
            return new Response('Données manquantes', 404);
        }

        $review = new Review();
        $review->setAuthor($author);
        $review->setTarget($target);
        $review->setCarpool($carpool);
        $review->setRating(4);
        $review->setComment('Avis test.');
        $review->setDate(new \DateTime());

        $em->persist($review);
        $em->flush();

        return new Response('Avis créé avec ID : ' . $review->getId());
    }
}
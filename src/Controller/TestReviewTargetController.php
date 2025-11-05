<?php
// src/Controller/TestReviewTargetController.php

namespace App\Controller;

use App\Entity\Review;
use App\Entity\User;
use App\Entity\Carpool;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestReviewTargetController extends AbstractController
{
    #[Route('/test/review-target', name: 'test_review_target')]
    public function test(EntityManagerInterface $em): Response
    {
        $author = $em->getRepository(User::class)->find(1);
        if (!$author) {
            return new Response('Auteur non trouvé', 404);
        }

        $target = $em->getRepository(User::class)->find(1);
        if (!$target) {
            return new Response('Cible non trouvée', 404);
        }

        $carpool = $em->getRepository(Carpool::class)->find(1);
        if (!$carpool) {
            return new Response('Covoiturage non trouvé', 404);
        }

        $review = new Review();
        $review->setAuthor($author);
        $review->setTarget($target);
        $review->setCarpool($carpool);
        $review->setRating(4);
        $review->setComment('Avis test avec auteur, cible et covoiturage.');
        $review->setDate(new \DateTime());

        $em->persist($review);
        $em->flush();

        return new Response('Avis créé avec ID : ' . $review->getId());
    }
}
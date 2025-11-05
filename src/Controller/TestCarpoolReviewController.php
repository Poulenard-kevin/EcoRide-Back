<?php
// src/Controller/TestCarpoolReviewController.php

namespace App\Controller;

use App\Entity\Carpool;
use App\Entity\Review;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestCarpoolReviewController extends AbstractController
{
    #[Route('/test/carpool-review', name: 'test_carpool_review')]
    public function test(EntityManagerInterface $em): Response
    {
        $carpool = $em->getRepository(Carpool::class)->find(1);
        if (!$carpool) {
            return new Response('Covoiturage non trouvé', 404);
        }

        $review = new Review();
        $review->setCarpool($carpool);
        $review->setComment('Avis test sur ce covoiturage.');
        $review->setRating(4);
        $review->setDate(new \DateTime());
        $review->setAuthor($carpool->getDriver()); // ou un utilisateur valide

        $em->persist($review);
        $em->flush();

        return new Response('Avis créé et lié au covoiturage avec ID : ' . $review->getId());
    }
}
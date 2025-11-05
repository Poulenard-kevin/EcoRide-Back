<?php

namespace App\Controller;

use App\Entity\User; // Renommage de l'entité Utilisateur en User
use App\Entity\Review; // Renommage de l'entité Avis en Review
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestUserReviewController extends AbstractController // Renommage du contrôleur
{
    #[Route('/test/user-review', name: 'test_user_review')] // Mise à jour de la route
    public function test(EntityManagerInterface $em): Response
    {
        $user = $em->getRepository(User::class)->find(1); // Utilisation de User
        if (!$user) {
            return new Response('Utilisateur non trouvé', 404); // Message en français
        }

        $review = new Review(); // Utilisation de Review
        $review->setAuthor($user); // Renommage de la méthode
        $review->setComment('Super covoiturage, très agréable !'); // Renommage de la méthode
        $review->setRating(5); // Renommage de la méthode
        $review->setDate(new \DateTime());

        $em->persist($review);
        $em->flush();

        return new Response('Avis créé et lié à l\'utilisateur avec ID : ' . $review->getId()); // Message en français
    }
}
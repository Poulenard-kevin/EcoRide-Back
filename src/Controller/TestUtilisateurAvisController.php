<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Avis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestUtilisateurAvisController extends AbstractController
{
    #[Route('/test/utilisateur-avis', name: 'test_utilisateur_avis')]
    public function test(EntityManagerInterface $em): Response
    {
        $utilisateur = $em->getRepository(Utilisateur::class)->find(1);
        if (!$utilisateur) {
            return new Response('Utilisateur non trouvé', 404);
        }

        $avis = new Avis();
        $avis->setAuteur($utilisateur);
        $avis->setCommentaire('Super covoiturage, très agréable !');
        $avis->setNote(5);
        $avis->setDate(new \DateTime());

        $em->persist($avis);
        $em->flush();

        return new Response('Avis créé et lié à l\'utilisateur avec ID : ' . $avis->getId());
    }
}
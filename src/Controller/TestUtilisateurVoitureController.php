<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use App\Entity\Voiture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestUtilisateurVoitureController extends AbstractController
{
    #[Route('/test/utilisateur-voiture', name: 'test_utilisateur_voiture')]
    public function test(EntityManagerInterface $em): Response
    {
        $utilisateur = $em->getRepository(Utilisateur::class)->find(1);
        if (!$utilisateur) {
            return new Response('Utilisateur non trouvé', 404);
        }

        $voiture = new Voiture();
        $voiture->setMarque('Toyota');
        $voiture->setModele('Corolla');
        $voiture->setCouleur('Bleu');
        $voiture->setEnergie('Essence');
        $voiture->setImmatriculation('AB-123-CD');
        $voiture->setNbPlaces(5);
        $voiture->setProprietaire($utilisateur);

        $em->persist($voiture);
        $em->flush();

        return new Response('Voiture créée et liée à l\'utilisateur avec ID : ' . $voiture->getId());
    }
}
<?php
// src/Controller/TestCreateCovoiturageController.php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Utilisateur;
use App\Entity\Voiture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestCreateCovoiturageController extends AbstractController
{
    #[Route('/test/create-covoiturage', name: 'test_create_covoiturage')]
    public function create(EntityManagerInterface $em): Response
    {
        // Récupérer un utilisateur existant pour le chauffeur
        $chauffeur = $em->getRepository(Utilisateur::class)->find(1);
        if (!$chauffeur) {
            return new Response('Chauffeur non trouvé', 404);
        }

        // Récupérer une voiture existante
        $voiture = $em->getRepository(Voiture::class)->find(4);
        if (!$voiture) {
            return new Response('Voiture non trouvée', 404);
        }

        $covoiturage = new Covoiturage();
        $covoiturage->setChauffeur($chauffeur);
        $covoiturage->setVoiture($voiture);
        $covoiturage->setDateDepart(new \DateTime('tomorrow'));
        $covoiturage->setHeureDepart(new \DateTime('10:00'));
        $covoiturage->setLieuDepart('Paris');
        $covoiturage->setDateArrivee(new \DateTime('tomorrow'));
        $covoiturage->setHeureArrivee(new \DateTime('12:00'));
        $covoiturage->setLieuArrivee('Lyon');
        $covoiturage->setPrixParPlace(20.0);
        $covoiturage->setNbPlacesTotal(4);
        $covoiturage->setNbPlacesDispo(4);

        $em->persist($covoiturage);
        $em->flush();

        return new Response('Covoiturage créé avec ID : ' . $covoiturage->getId());
    }
}
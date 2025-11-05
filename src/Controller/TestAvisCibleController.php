<?php
// src/Controller/TestAvisCibleController.php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use App\Entity\Covoiturage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestAvisCibleController extends AbstractController
{
    #[Route('/test/avis-cible', name: 'test_avis_cible')]
    public function test(EntityManagerInterface $em): Response
    {
        $auteur = $em->getRepository(Utilisateur::class)->find(1);
        if (!$auteur) {
            return new Response('Auteur non trouvé', 404);
        }

        $cible = $em->getRepository(Utilisateur::class)->find(1);
        if (!$cible) {
            return new Response('Cible non trouvée', 404);
        }

        $covoiturage = $em->getRepository(Covoiturage::class)->find(1);
        if (!$covoiturage) {
            return new Response('Covoiturage non trouvé', 404);
        }

        $avis = new Avis();
        $avis->setAuteur($auteur);
        $avis->setCible($cible);
        $avis->setCovoiturage($covoiturage);
        $avis->setNote(4);
        $avis->setCommentaire('Avis test avec auteur, cible et covoiturage.');
        $avis->setDate(new \DateTime());

        $em->persist($avis);
        $em->flush();

        return new Response('Avis créé avec ID : ' . $avis->getId());
    }
}
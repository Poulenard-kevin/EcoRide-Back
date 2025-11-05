<?php
// src/Controller/TestAvisCibleV2Controller.php

namespace App\Controller;

use App\Entity\Avis;
use App\Entity\Utilisateur;
use App\Entity\Covoiturage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestAvisCibleV2Controller extends AbstractController
{
    #[Route('/test/avis-cible-v2', name: 'test_avis_cible_v2')]
    public function test(EntityManagerInterface $em): Response
    {
        $auteur = $em->getRepository(Utilisateur::class)->find(1);
        $cible = $em->getRepository(Utilisateur::class)->find(2);
        $covoiturage = $em->getRepository(Covoiturage::class)->find(1);

        if (!$auteur || !$cible || !$covoiturage) {
            return new Response('Données manquantes', 404);
        }

        $avis = new Avis();
        $avis->setAuteur($auteur);
        $avis->setCible($cible);
        $avis->setCovoiturage($covoiturage);
        $avis->setNote(4);
        $avis->setCommentaire('Avis test.');
        $avis->setDate(new \DateTime());

        $em->persist($avis);
        $em->flush();

        return new Response('Avis créé avec ID : ' . $avis->getId());
    }
}
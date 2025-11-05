<?php
// src/Controller/TestCovoiturageAvisController.php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Avis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestCovoiturageAvisController extends AbstractController
{
    #[Route('/test/covoiturage-avis', name: 'test_covoiturage_avis')]
    public function test(EntityManagerInterface $em): Response
    {
        $covoiturage = $em->getRepository(Covoiturage::class)->find(1);
        if (!$covoiturage) {
            return new Response('Covoiturage non trouvé', 404);
        }

        $avis = new Avis();
        $avis->setCovoiturage($covoiturage);
        $avis->setCommentaire('Avis test sur ce covoiturage.');
        $avis->setNote(4);
        $avis->setDate(new \DateTime());
        $avis->setAuteur($covoiturage->getChauffeur()); // ou un utilisateur valide

        $em->persist($avis);
        $em->flush();

        return new Response('Avis créé et lié au covoiturage avec ID : ' . $avis->getId());
    }
}
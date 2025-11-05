<?php

namespace App\Controller;

use App\Entity\Covoiturage;
use App\Entity\Voiture;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestCovoiturageVoitureController extends AbstractController
{
    #[Route('/test/covoiturage-voiture', name: 'test_covoiturage_voiture')]
    public function test(EntityManagerInterface $em): Response
    {
        $voiture = $em->getRepository(Voiture::class)->find(1);
        if (!$voiture) {
            return new Response('Voiture non trouvée', 404);
        }

        $covoiturage = new Covoiturage();
        $covoiturage->setDateDepart(new \DateTime('tomorrow'));
        $covoiturage->setHeureDepart(new \DateTime('10:00'));
        $covoiturage->setDateArrivee(new \DateTime('tomorrow'));
        $covoiturage->setHeureArrivee(new \DateTime('12:00'));
        $covoiturage->setLieuDepart('Paris');
        $covoiturage->setLieuArrivee('Lyon');
        $covoiturage->setPrixParPlace(20.0);
        $covoiturage->setNbPlacesTotal(4);
        $covoiturage->setNbPlacesDispo(4);
        $covoiturage->setChauffeur($voiture->getProprietaire());
        $covoiturage->setVoiture($voiture);

        $em->persist($covoiturage);
        $em->flush();

        return new Response('Covoiturage créé et lié à la voiture avec ID : ' . $covoiturage->getId());
    }
}
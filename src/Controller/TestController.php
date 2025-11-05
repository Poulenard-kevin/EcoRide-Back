<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestController extends AbstractController
{
    #[Route('/test/covoiturages/{id}', name: 'test_covoiturages')]
    public function testCovoiturages(int $id, ManagerRegistry $doctrine): Response
    {
        $utilisateur = $doctrine->getRepository(Utilisateur::class)->find($id);

        if (!$utilisateur) {
            return new Response("Utilisateur non trouvé");
        }

        $covoiturages = $utilisateur->getCovoituragesProposes();

        $result = "Covoiturages proposés par {$utilisateur->getNom()} :<br>";

        foreach ($covoiturages as $covoiturage) {
            $result .= "- Covoiturage ID: " . $covoiturage->getId() . "<br>";
        }

        return new Response($result);
    }
}
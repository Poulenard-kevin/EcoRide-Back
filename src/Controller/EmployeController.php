<?php
// src/Controller/EmployeController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EmployeController extends AbstractController
{
    #[Route('/employe', name: 'employe_dashboard')]
    public function index(): Response
    {
        $this->denyAccessUnlessGranted('ROLE_EMPLOYE');

        return $this->render('employe/index.html.twig', [
            'message' => 'Bienvenue dans l’espace employé',
        ]);
    }
}
<?php

namespace App\Controller\Api;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConfigController extends AbstractController
{
    #[Route('/config.js', name: 'app_config_js', methods: ['GET'])]
    public function index(): Response
    {
        // Récupère la valeur depuis .env (FRONT_API_BASE)
        $apiBase = $_ENV['FRONT_API_BASE'] ?? 'http://127.0.0.1:8000';

        // Génère le JS à retourner
        $content = sprintf("window.__API_BASE = '%s';", addslashes($apiBase));

        return new Response($content, 200, [
            'Content-Type' => 'application/javascript; charset=UTF-8',
            // Optionnel : éviter la mise en cache si tu changes souvent la valeur
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
        ]);
    }
}
<?php
// src/Controller/TestEnvController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class TestEnvController extends AbstractController
{
    #[Route('/test-env-full', name: 'test_env_full')]
    public function index(): Response
    {
        $keys = ['MONGODB_URL', 'MONGODB_USER', 'MONGODB_PASSWORD', 'MONGODB_DB'];
        $out = [];
        foreach ($keys as $k) {
            $out[$k] = [
                'getenv' => getenv($k),
                '_ENV' => $_ENV[$k] ?? null,
                '_SERVER' => $_SERVER[$k] ?? null,
            ];
        }

        // also include any env entries that start with MONGODB_
        foreach ($_ENV as $k => $v) {
            if (stripos($k, 'MONGODB_') === 0 && !isset($out[$k])) {
                $out[$k] = ['getenv' => getenv($k), '_ENV' => $v, '_SERVER' => $_SERVER[$k] ?? null];
            }
        }

        return new Response(json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES), 200, ['Content-Type' => 'application/json']);
    }
}
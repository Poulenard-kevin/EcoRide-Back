<?php

use App\Kernel;

// 1. On coupe l'affichage des erreurs au niveau PHP
error_reporting(E_ALL & ~E_DEPRECATED & ~E_USER_DEPRECATED);
ini_set('display_errors', '0');

// 2. On démarre un tampon de sortie pour capturer les éventuels messages parasites
ob_start();

require_once dirname(__DIR__).'/vendor/autoload_runtime.php';

return function (array $context) {
    // On s'assure que les variables d'env sont là
    putenv('MONGODB_URL=mongodb://127.0.0.1:27017');
    
    return new Kernel($context['APP_ENV'], (bool) $context['APP_DEBUG']);
};

// 3. Juste avant l'envoi, on pourrait nettoyer, mais Symfony gère normalement la fin.
// Si le problème persiste, on videra le tampon manuellement dans le Kernel.
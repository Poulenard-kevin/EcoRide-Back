<?php

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

require dirname(__DIR__).'/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$container = $kernel->getContainer();
/** @var EntityManagerInterface $em */
$em = $container->get('doctrine.orm.entity_manager');

// 💡 Change ici avec l'email de l'utilisateur pour lequel tu veux créer le token :
$user = $em->getRepository(User::class)->findOneBy(['email' => 'admin@ecoride.com']);

if (!$user) {
    echo "❌ Utilisateur non trouvé.\n";
    exit(1);
}

$user->setApiToken(bin2hex(random_bytes(32)));
$em->flush();

echo "✅ Nouveau token API généré : ".$user->getApiToken()."\n";
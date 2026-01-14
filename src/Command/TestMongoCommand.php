<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use MongoDB\Client;

#[AsCommand(
    name: 'app:test-mongo',
    description: 'Teste la connexion à MongoDB',
)]
class TestMongoCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $mongoUri = $_ENV['MONGODB_URL'] ?? $_ENV['MONGODB_URI'] ?? 'mongodb://127.0.0.1:27017';

        try {
            $io->info("Tentative de connexion à : $mongoUri");

            $client = new Client($mongoUri);
            $client->listDatabases();

            $io->success('Connexion à MongoDB réussie !');

            $collection = $client->selectCollection($_ENV['MONGODB_DB'] ?? 'sf_EcoRide_mongo', 'test_connection');
            $collection->insertOne(['date' => new \DateTime(), 'message' => 'Hello EcoRide!']);

            $io->writeln('Un document de test a été inséré avec succès.');

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur de connexion : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
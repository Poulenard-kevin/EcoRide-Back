<?php

namespace App\Service;

use MongoDB\Client;
use MongoDB\Collection;

class MongoService
{
    private Client $client;
    private string $dbName;

    public function __construct(\MongoDB\Client $client, string $dbName)
    {
        $this->client = $client;
        $this->dbName = $dbName;
    }

    /**
     * Récupère une collection MongoDB par son nom
     */
    public function getCollection(string $name): Collection
    {
        return $this->client->selectCollection($this->dbName, $name);
    }
}
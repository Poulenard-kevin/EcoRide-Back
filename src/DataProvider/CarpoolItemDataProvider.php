<?php

namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\ItemDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Carpool;
use App\Repository\CarpoolRepository;

final class CarpoolItemDataProvider implements ItemDataProviderInterface, RestrictedDataProviderInterface
{
    private CarpoolRepository $carpoolRepository;

    public function __construct(CarpoolRepository $carpoolRepository)
    {
        $this->carpoolRepository = $carpoolRepository;
    }

    /**
     * @param string $resourceClass
     * @param mixed $id
     * @param string|null $operationName
     * @param array $context
     * @return Carpool|null
     */
    public function getItem(string $resourceClass, $id, ?string $operationName = null, array $context = [])
    {
        if ($resourceClass !== Carpool::class) {
            return null;
        }

        // Utilise la méthode personnalisée pour charger les relations
        return $this->carpoolRepository->findWithRelations((int) $id);
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        return $resourceClass === Carpool::class;
    }
}
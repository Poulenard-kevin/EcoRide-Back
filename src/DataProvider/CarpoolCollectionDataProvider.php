<?php
// src/DataProvider/CarpoolCollectionDataProvider.php
namespace App\DataProvider;

use ApiPlatform\Core\DataProvider\CollectionDataProviderInterface;
use ApiPlatform\Core\DataProvider\RestrictedDataProviderInterface;
use App\Entity\Carpool;
use App\Repository\CarpoolRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;

final class CarpoolCollectionDataProvider implements CollectionDataProviderInterface, RestrictedDataProviderInterface
{
    private CarpoolRepository $carpoolRepository;

    public function __construct(CarpoolRepository $carpoolRepository)
    {
        $this->carpoolRepository = $carpoolRepository;
    }

    public function getCollection(string $resourceClass, ?string $operationName = null, array $context = [])
    {
        if ($resourceClass !== Carpool::class) {
            return null;
        }

        // Si la pagination d'API Platform est activée, lire page / items_per_page dans $context
        $page = $context['filters']['page'] ?? ($context['uri_variables']['page'] ?? ($context['pagination']['page'] ?? 1));
        $itemsPerPage = $context['filters']['itemsPerPage'] ?? ($context['pagination']['items_per_page'] ?? 30);

        // Construire QB via repo (méthode dédiée)
        $qb = $this->carpoolRepository->createQueryBuilder('c')
            ->leftJoin('c.car', 'car')->addSelect('car')
            ->leftJoin('c.driver', 'driver')->addSelect('driver')
            ->orderBy('c.id', 'DESC') // adapter si nécessaire
            ->setFirstResult(($page - 1) * $itemsPerPage)
            ->setMaxResults($itemsPerPage);

        $paginator = new Paginator($qb->getQuery(), true);

        return $paginator; // Paginator est iterable -> API Platform gère count / pagination headers
    }

    public function supports(string $resourceClass, ?string $operationName = null, array $context = []): bool
    {
        // Optionnel : restreindre à une opération spécifique (ex: 'get' collection)
        return $resourceClass === Carpool::class;
    }
}
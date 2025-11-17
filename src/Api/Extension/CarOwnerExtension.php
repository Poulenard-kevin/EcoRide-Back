<?php
namespace App\Api\Extension;

use ApiPlatform\Core\Bridge\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use Doctrine\ORM\QueryBuilder;
use Symfony\Component\Security\Core\Security;
use App\Entity\Car;

final class CarOwnerExtension implements QueryCollectionExtensionInterface
{
    private Security $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    /**
     * Filtre la collection Car pour n'exposer que les voitures
     * appartenant à l'utilisateur courant (sauf ROLE_ADMIN).
     *
     * @param QueryBuilder $qb
     * @param QueryNameGeneratorInterface $queryNameGenerator
     * @param string $resourceClass
     * @param string|null $operationName
     */
    public function applyToCollection(
        QueryBuilder $qb,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        string $operationName = null
    ): void {
        if ($resourceClass !== Car::class) {
            return;
        }

        $user = $this->security->getUser();
        $rootAlias = $qb->getRootAliases()[0];

        // Si pas connecté -> renvoyer vide (clause impossible)
        if (!$user) {
            $qb->andWhere(sprintf('%s.id IS NULL', $rootAlias));
            return;
        }

        // Admin voit tout
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return;
        }

        // Filtrer par owner = current user (comparaison par id pour compatibilité)
        $paramName = $queryNameGenerator->generateParameterName('current_user');
        $qb->andWhere(sprintf('%s.owner = :%s', $rootAlias, $paramName));
        $qb->setParameter($paramName, $user->getId());
    }
}
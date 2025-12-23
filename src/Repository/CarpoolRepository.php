<?php

namespace App\Repository;

use App\Entity\Carpool;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Carpool>
 *
 * @method Carpool|null find($id, $lockMode = null, $lockVersion = null)
 * @method Carpool|null findOneBy(array $criteria, array $orderBy = null)
 * @method Carpool[]    findAll()
 * @method Carpool[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CarpoolRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Carpool::class);
    }

    /**
     * Récupère un covoiturage avec ses relations car et driver chargées.
     */
    public function findAllWithRelations(): array
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.car', 'car')
            ->addSelect('car')
            ->leftJoin('c.driver', 'driver')
            ->addSelect('driver')
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupère un covoiturage par son id avec ses relations car et driver chargées.
     */
    public function findWithRelations(int $id): ?Carpool
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.car', 'car')
            ->addSelect('car')
            ->leftJoin('c.driver', 'driver')
            ->addSelect('driver')
            ->where('c.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function createQueryBuilderWithRelations(): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('c')
            ->leftJoin('c.car', 'car')->addSelect('car')
            ->leftJoin('c.driver', 'driver')->addSelect('driver');
    }
}
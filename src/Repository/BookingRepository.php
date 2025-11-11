<?php

namespace App\Repository;

use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Booking>
 *
 * @method Booking|null find($id, $lockMode = null, $lockVersion = null)
 * @method Booking|null findOneBy(array $criteria, array $orderBy = null)
 * @method Booking[]    findAll()
 * @method Booking[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

//    /**
//     * @return Reservation[] Returns an array of Reservation objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('r.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Reservation
//    {
//        return $this->createQueryBuilder('r')
//            ->andWhere('r.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    /**
     * Calcule la somme des places réservées pour un covoiturage selon des statuts donnés.
     *
     * @param \App\Entity\Carpool $carpool Le covoiturage concerné
     * @param string[] $statuses Liste des statuts à inclure dans le calcul
     * @return int Nombre total de places réservées
     */
    public function sumReservedSeatsByCarpoolAndStatuses(\App\Entity\Carpool $carpool, array $statuses = []): int
    {
        $qb = $this->createQueryBuilder('b')
            ->select('COALESCE(SUM(b.reservedSeats), 0)')
            ->where('b.carpool = :carpool')
            ->setParameter('carpool', $carpool);

        if (!empty($statuses)) {
            $qb->andWhere('b.status IN (:statuses)')
            ->setParameter('statuses', $statuses);
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

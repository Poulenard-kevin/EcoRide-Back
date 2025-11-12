<?php

namespace App\Repository;

use App\Entity\Review;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Review>
 *
 * @method Review|null find($id, $lockMode = null, $lockVersion = null)
 * @method Review|null findOneBy(array $criteria, array $orderBy = null)
 * @method Review[]    findAll()
 * @method Review[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ReviewRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Review::class);
    }


//    /**
//     * @return Avis[] Returns an array of Avis objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('a.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Avis
//    {
//        return $this->createQueryBuilder('a')
//            ->andWhere('a.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findApprovedByTarget(User $target, int $limit = null): array
    {
        $qb = $this->createQueryBuilder('r')
            ->andWhere('r.target = :target')
            ->andWhere('r.status = :approved')
            ->setParameter('target', $target)
            ->setParameter('approved', Review::STATUS_APPROVED)
            ->orderBy('r.date', 'DESC');

        if ($limit) $qb->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    public function findApprovedByAuthor(User $author, int $limit = null): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.author = :author')
            ->andWhere('r.status = :approved')
            ->setParameter('author', $author)
            ->setParameter('approved', Review::STATUS_APPROVED)
            ->orderBy('r.date', 'DESC')
            ->setMaxResults($limit ?? 999)
            ->getQuery()
            ->getResult();
    }

    public function findPendingForAuthor(User $author): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.author = :author')
            ->andWhere('r.status = :pending')
            ->setParameter('author', $author)
            ->setParameter('pending', Review::STATUS_PENDING)
            ->orderBy('r.date', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function findPendingAll(): array // utile pour admin
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.status = :pending')
            ->setParameter('pending', Review::STATUS_PENDING)
            ->orderBy('r.date', 'ASC')
            ->getQuery()
            ->getResult();
    }
}

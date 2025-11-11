<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }


//    /**
//     * @return User[] Returns an array of User objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('u.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?User
//    {
//        return $this->createQueryBuilder('u')
//            ->andWhere('u.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }

    public function findByRole(string $role): array
    {
        $qb = $this->createQueryBuilder('u');

        // Si ton DB supporte JSON_CONTAINS (MySQL) et roles est un JSON
        // $qb->where("JSON_CONTAINS(u.roles, :role) = 1")->setParameter('role', json_encode($role));

        // Variante plus portable : LIKE (fonctionne si roles contient le rôle sous forme de string)
        $qb->where('u.roles LIKE :role')
        ->setParameter('role', '%"'.$role.'"%')
        ->orderBy('u.email', 'ASC');

        return $qb->getQuery()->getResult();
    }

    public function countAdmins(bool $onlyActive = true): int
    {
        $qb = $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.roles LIKE :role')
            ->setParameter('role', '%"ROLE_ADMIN"%');

        if ($onlyActive) {
            $qb->andWhere('u.isActive = true');
        }

        return (int) $qb->getQuery()->getSingleScalarResult();
    }
}

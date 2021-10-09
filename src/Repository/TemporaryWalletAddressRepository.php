<?php

namespace App\Repository;

use App\Entity\TemporaryWalletAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TemporaryWalletAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemporaryWalletAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemporaryWalletAddress[]    findAll()
 * @method TemporaryWalletAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemporaryWalletAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryWalletAddress::class);
    }

    // /**
    //  * @return TemporaryWalletAddress[] Returns an array of TemporaryWalletAddress objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TemporaryWalletAddress
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

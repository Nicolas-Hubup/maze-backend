<?php

namespace App\Repository;

use App\Entity\WalletAddress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method WalletAddress|null find($id, $lockMode = null, $lockVersion = null)
 * @method WalletAddress|null findOneBy(array $criteria, array $orderBy = null)
 * @method WalletAddress[]    findAll()
 * @method WalletAddress[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WalletAddressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletAddress::class);
    }

    // /**
    //  * @return WalletAddress[] Returns an array of WalletAddress objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('w.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?WalletAddress
    {
        return $this->createQueryBuilder('w')
            ->andWhere('w.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

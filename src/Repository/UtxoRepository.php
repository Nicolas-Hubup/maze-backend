<?php

namespace App\Repository;

use App\Entity\Utxo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Utxo|null find($id, $lockMode = null, $lockVersion = null)
 * @method Utxo|null findOneBy(array $criteria, array $orderBy = null)
 * @method Utxo[]    findAll()
 * @method Utxo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UtxoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utxo::class);
    }

    // /**
    //  * @return Utxo[] Returns an array of Utxo objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('u.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Utxo
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}

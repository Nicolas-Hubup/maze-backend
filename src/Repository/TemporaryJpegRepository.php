<?php

namespace App\Repository;

use App\Entity\TemporaryJpeg;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TemporaryJpeg|null find($id, $lockMode = null, $lockVersion = null)
 * @method TemporaryJpeg|null findOneBy(array $criteria, array $orderBy = null)
 * @method TemporaryJpeg[]    findAll()
 * @method TemporaryJpeg[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TemporaryJpegRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TemporaryJpeg::class);
    }

    // /**
    //  * @return TemporaryJpeg[] Returns an array of TemporaryJpeg objects
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
    public function findOneBySomeField($value): ?TemporaryJpeg
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

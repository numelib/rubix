<?php

namespace App\Repository;

use App\Entity\Structure;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Structure>
 */
class StructureRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Structure::class);
    }

    public function findEmails() : array
    {
        return $this->createQueryBuilder('s')
            ->select('s.email')
            ->getQuery()
            ->getResult();
    }

    public function findAddressCodes() : array
    {
        return $this->createQueryBuilder('s')
            ->select('s.address_code')
            ->andWhere('s.address_code IS NOT NULL')
            ->andWhere('s.address_code != 0')
            ->orderBy('s.address_code', 'ASC')
            ->groupBy('s.address_code')
            ->getQuery()
            ->getSingleColumnResult();
    }

    public function findAddressCities() : array
    {
        return $this->createQueryBuilder('s')
            ->select('s.address_city')
            ->andWhere('s.address_city IS NOT NULL')
            ->orderBy('s.address_city', 'ASC')
            ->groupBy('s.address_city')
            ->getQuery()
            ->getSingleColumnResult();
    }

    //    /**
    //     * @return Structure[] Returns an array of Structure objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Structure
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

<?php

namespace App\Repository;

use App\Entity\ContactDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContactDetail>
 */
class ContactDetailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContactDetail::class);
    }

    public function findStructureFunctions() : array
    {
        $result = $this->createQueryBuilder('cd')
                ->select('cd.structure_function')
                ->andWhere('cd.structure_function IS NOT NULL')
                ->groupBy('cd.structure_function')
                ->distinct(true)
                ->getQuery()
                ->getSingleColumnResult()
           ;

        return $result;
    }

    //    /**
    //     * @return ContactDetail[] Returns an array of ContactDetail objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?ContactDetail
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

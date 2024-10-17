<?php

namespace App\Repository;

use App\Entity\Contact;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Contact>
 */
class ContactRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Contact::class);
    }

    public function findEmails() : array
    {
        return $this->createQueryBuilder('c')
            ->select('c.personnal_email, cd.email')
            ->leftJoin('c.contact_details','cd')
            ->getQuery()
            ->getResult();
    }

    public function findStructuresAddressCodes() : array
    {
        $result = $this->createQueryBuilder('contact')
                ->select('structure.address_code')
                ->andWhere('structure.address_code IS NOT NULL')
                ->andWhere('structure.address_code != 0')
                ->groupBy('structure.address_code')
                ->innerJoin('contact.contact_details', 'contact_details')
                ->innerJoin('contact_details.structure', 'structure')
                ->getQuery()
                ->getSingleColumnResult()
           ;

        return $result;
    }

    public function findStructuresAddressCity() : array
    {
        $result = $this->createQueryBuilder('contact')
                ->select('structure.address_city')
                ->andWhere('structure.address_city IS NOT NULL')
                ->groupBy('structure.address_city')
                ->innerJoin('contact.contact_details', 'contact_details')
                ->innerJoin('contact_details.structure', 'structure')
                ->getQuery()
                ->getSingleColumnResult()
           ;

        return $result;
    }

    public function findNotLinkedToFestivalProgramWithContactDetails() : array
    {
        $result = $this->createQueryBuilder('contact')
            ->select('contact', 'contact_details')
            ->leftJoin('contact.contact_details', 'contact_details')
            ->where('contact.festival_program IS NULL')
            ->setMaxResults(100)
            ->getQuery()
            ->getResult()
        ;

        return $result;
    }

    //    /**
    //     * @return Contact[] Returns an array of Contact objects
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

    //    public function findOneBySomeField($value): ?Contact
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}

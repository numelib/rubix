<?php
namespace App\Controller\Admin\Filter;

use App\Entity\ContactDetail;
use App\Repository\ContactDetailRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;

class HasStructureFunctionFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if(null !== $filterDataDto->getValue()){
            $queryBuilder->leftJoin('entity.contact_details', 'cd');
            $queryBuilder->andWhere('cd.structure_function = :structure_function');
            $queryBuilder->setParameter('structure_function', $filterDataDto->getValue());
            ;
        }
    }
}
?>
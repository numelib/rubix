<?php
namespace App\Controller\Admin\Filter;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;

class IsReceivingFestivalProgramFilter implements FilterInterface
{
    use FilterTrait;

    private string $alias;

    public static function new(string $propertyName, string $formTypeFqcn, array $formTypeOptions = [], $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setFormType($formTypeFqcn)
            ->setFormTypeOptions($formTypeOptions)
            ->setLabel($label)
        ;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if($filterDataDto->getValue() === null) return;

        if($filterDataDto->getValue() === true) {
            $queryBuilder
                ->leftJoin('entity.postProgram', 'postProgram')
                ->andWhere('postProgram IS NOT NULL');
        }

        if($filterDataDto->getValue() === false) {
            $queryBuilder
                ->leftJoin('entity.postProgram', 'postProgram')
                ->andWhere('postProgram IS NULL');
        }
           
    }
}
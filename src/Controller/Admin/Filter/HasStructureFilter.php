<?php
namespace App\Controller\Admin\Filter;

use App\Entity\Contact;
use App\Entity\Structure;
use App\Repository\StructureRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\EntityFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;

class HasStructureFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(EntityFilterType::class)
            ->setFormTypeOption('value_type_options.class', Structure::class)
            ->setFormTypeOption('value_type_options.query_builder', function (StructureRepository $structureRepository): QueryBuilder {
                return $structureRepository->createQueryBuilder('structure')
                    ->leftJoin('structure.contact_receiving_festival_program', 'contact')
                    ->addSelect('contact');
            })
            ;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if(null !== $filterDataDto->getValue()){
            $queryBuilder
                ->leftJoin('entity.contact_details', 'contact_detail')
                ->leftJoin('contact_detail.structure', 'contact_structure')
                ->andWhere('contact_structure = :structure')
                ->setParameter('structure', $filterDataDto->getValue())
            ;
        }
    }
}
?>
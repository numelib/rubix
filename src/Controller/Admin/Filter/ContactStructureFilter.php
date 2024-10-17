<?php
namespace App\Controller\Admin\Filter;

use App\Entity\Structure;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ContactStructureFilter implements FilterInterface
{
    use FilterTrait;

    private string $alias;

    public static function new(string $propertyName, $label = null): self
    {
        $alias = self::extractAliasFromPropertyName($propertyName);
        $structureFqcn = Structure::class;
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        if(!$propertyAccessor->isReadable(new $structureFqcn(), $alias)) {
            throw new Exception('Argument #1 is invalid : cannot get a way to read property ' .  $alias . ' on instances of ' . $structureFqcn . ' Entity.');
        };

        $formType = (str_starts_with($alias, 'is_')) ? BooleanFilterType::class : ChoiceFilterType::class;

        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setFormType($formType)
            ->setLabel($label)
        ;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if(null !== $filterDataDto->getValue()){
            $property = $filterDataDto->getProperty();
            $alias = self::extractAliasFromPropertyName($property);
            $andWhere = 'structure.' . $alias . ' = :value';

            $queryBuilder->leftJoin('entity.contact_details', 'contact_details');
            $queryBuilder->leftJoin('contact_details.structure', 'structure');
            $queryBuilder->andWhere($andWhere);
            $queryBuilder->setParameter('value', $filterDataDto->getValue());
            ;
        }
    }

    public static function extractAliasFromPropertyName(string $propertyName) : string
    {
        $propertyNameParts = explode(':', $propertyName);
        return end($propertyNameParts);
    }

    
}
?>
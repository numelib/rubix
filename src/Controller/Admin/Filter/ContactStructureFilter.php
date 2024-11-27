<?php
namespace App\Controller\Admin\Filter;

use App\Entity\Structure;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use Exception;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ContactStructureFilter implements FilterInterface
{
    use FilterTrait;

    private string $alias;

    public static function new(string $propertyName, string $formTypeFqcn, array $formTypeOptions = [], $label = null): self
    {
        $alias = self::extractAliasFromPropertyName($propertyName);
        $structureFqcn = Structure::class;
        $propertyAccessor = PropertyAccess::createPropertyAccessor();
        if(!$propertyAccessor->isReadable(new $structureFqcn(), $alias)) {
            throw new Exception('Argument #1 is invalid : cannot get a way to read property ' .  $alias . ' on instances of ' . $structureFqcn . ' Entity.');
        };

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

        $property = $filterDataDto->getProperty();
        $alias = self::extractAliasFromPropertyName($property);

        if(is_array($filterDataDto->getValue())) {
            $andWhere = 'structure.' . $alias . ' IN';
            $placeholders = [];
            foreach($filterDataDto->getValue() as $key => $value)
            {
                $placeholders[] = ':value' . $key;
                $queryBuilder->setParameter('value' . $key, $value);
            }
            $andWhere .= '(' . implode(', ', $placeholders) . ')';
        } else {
            $andWhere = 'structure.' . $alias . ' = :value';
    
            $queryBuilder->setParameter('value', $filterDataDto->getValue());
        }

        $queryBuilder->andWhere($andWhere);
    }

    public static function extractAliasFromPropertyName(string $propertyName) : string
    {
        $propertyNameParts = explode(':', $propertyName);
        return end($propertyNameParts);
    }
}
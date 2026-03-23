<?php
namespace App\Controller\Admin\Filter;

use App\Entity\Structure;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Filter\FilterTrait;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class ProgramPostingsRecipientFilter implements FilterInterface
{
    use FilterTrait;

    public static function new(string $propertyName, $label = null): self
    {
        return (new self())
            ->setFilterFqcn(__CLASS__)
            ->setProperty($propertyName)
            ->setLabel($label)
            ->setFormType(ChoiceType::class)
            ->setFormTypeOption('choices', 
                [
                    'ProgramPostingsRecipient.structure' => 'structure',
                    'ProgramPostingsRecipient.contacts' => 'contacts'
                ]
            )
            ;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        if(null === $filterDataDto->getValue()) return;

            $queryBuilder
                ->andWhere('entity.programSent = 1')
                ->leftJoin('entity.programPostings', 'pp')
            ;

            if($filterDataDto->getValue() == 'structure')
            {
                $queryBuilder->andWhere('pp.contact IS NULL');
            }elseif($filterDataDto->getValue() == 'contacts')
            {
                $queryBuilder->andWhere('pp.contact IS NOT NULL');
            }

            
        
    }
}
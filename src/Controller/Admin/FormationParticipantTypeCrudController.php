<?php

namespace App\Controller\Admin;

use App\Entity\FormationParticipantType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormationParticipantTypeCrudController extends AbstractCrudController
{
    public function __construct(private TranslatorInterface $translator)
    {
    }
    
    public static function getEntityFqcn(): string
    {
        return FormationParticipantType::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Formation')
            ->setEntityLabelInPlural('Formations')
            ->renderContentMaximized()
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(4);

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', $this->translator->trans('name')),
        ];
    }
}

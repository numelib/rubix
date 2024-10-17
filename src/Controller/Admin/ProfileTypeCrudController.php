<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

use App\Entity\ProfileType;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProfileTypeCrudController extends AbstractCrudController
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    public static function getEntityFqcn(): string
    {
        return ProfileType::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Profil de Contacts')
            ->setEntityLabelInPlural('Profils de Contacts')
            ->renderContentMaximized()
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(4);

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('name', $this->translator->trans('name'))
        ];
    }
}

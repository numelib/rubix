<?php

namespace App\Controller\Admin;

use App\Entity\PracticalGuide;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;

class PracticalGuideCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
    ){ }

    public static function getEntityFqcn(): string
    {
        return PracticalGuide::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE);

        $practicalGuides = $this->entityManager->getRepository(PracticalGuide::class)->findAll();
        if(count($practicalGuides) > 0) $actions->disable(Action::NEW);

        return $actions;
    }

    public function configureCrud(Crud $crud) : Crud
    {
        $crud
            ->setEntityLabelInSingular('Guide pratique')
            ->setEntityLabelInPlural('Guide pratique')
            ->renderContentMaximized()
            ->showEntityActionsInlined();

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            TextField::new('title', $this->translator->trans('title'))
                ->setTemplatePath('admin/fields/detail_link.html.twig'),
            TextEditorField::new('content', $this->translator->trans('content'))
                ->setTemplatePath('admin/fields/field_raw.html.twig'),
        ];
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\PracticalGuide;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class PracticalGuideCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ){ }

    public static function getEntityFqcn(): string
    {
        return PracticalGuide::class;
    }

    /*    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_INDEX, Action::NEW, Action::DELETE)
            ->remove(Crud::PAGE_DETAIL, Action::DELETE)
            ->remove(Crud::PAGE_INDEX, Action::BATCH_DELETE)
            ->remove(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE,
                static fn (Action $action) => $action->setLabel("Enregistrer")
            )
        ;
    }  */

    public function configureActions(Actions $actions): Actions
    {
        $actions
            ->disable(Action::DELETE, Action::NEW, Action::INDEX)
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
            TextEditorField::new('content', $this->translator->trans('content'))
                ->onlyOnForms()
                //->setTrixEditorConfig()
                ->setLabel(false),

            TextField::new('content', $this->translator->trans('content'))
                ->setTemplatePath('admin/fields/field_raw.html.twig')
                ->hideOnForm()
                ->setLabel(false),
        ];
    }

    /** Check if Practical Guide exists **/
    public function index(AdminContext $context)
    {
        $practicalGuides = $this->entityManager->getRepository(PracticalGuide::class)->findAll();
        if(count($practicalGuides) == 0){
            $guide = new PracticalGuide;
            $guide->setContent('To be completed');
            $this->entityManager->persist($guide);
            $this->entityManager->flush();
        }else{
            $guide = $practicalGuides[0];
        }

        $url = $this->adminUrlGenerator
                    ->setController(PracticalGuideCrudController::class)
                    ->setAction(Crud::PAGE_DETAIL)
                    ->setEntityId($guide->getId())
                    ->generateUrl();

        return $this->redirect($url);
    }
}

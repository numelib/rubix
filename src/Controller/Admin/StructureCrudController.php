<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\Structure;
use App\Entity\ProgramPosting;
use Doctrine\ORM\QueryBuilder;
use App\Repository\ContactRepository;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use App\Entity\StructureTypeSpecialization;
use App\Service\EntitySpreadsheetGenerator;
use App\Form\Admin\StructurePhoneNumberType;
use App\Form\Admin\ProgramPostingFromStructureType;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use App\Controller\Admin\Filter\IsReceivingFestivalProgramFilter;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

class StructureCrudController extends AbstractCrudController
{
    public function __construct(
        private EntitySpreadsheetGenerator $entitySpreadsheetGenerator,
        private TranslatorInterface $translator,
        private AdminUrlGenerator $adminUrlGenerator,
        private EntityManagerInterface $entityManager,
    ){}
    
    public static function getEntityFqcn(): string
    {
        return Structure::class;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->addBatchAction(Action::new('xlsExport', 'Export XLS')
                ->linkToCrudAction('exportAsXls')
                ->addCssClass('btn btn-primary')
                ->setIcon('fa-solid fa-file-excel'))
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action) => $action->setLabel('Créer une Structure'))
            ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        return $queryBuilder;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $addressCodes = $this->entityManager->getRepository(Structure::class)->findAddressCodes();
        $addressCities = $this->entityManager->getRepository(Structure::class)->findAddressCities();
        $adressCodesChoices = array_combine($addressCodes, $addressCodes);
        $adressCitiesChoices = array_combine($addressCities, $addressCities);

        $filters
            ->add('name')
            ->add('structureType')
            ->add(EntityFilter::new('structure_type_specializations'))
            ->add('disciplines');

        $filters
            ->add(ChoiceFilter::new('address_code')->setChoices(empty($adressCodesChoices) ? ["Aucun" => 0] : $adressCodesChoices)->setFormTypeOption('value_type_options.multiple', true))
            ->add(ChoiceFilter::new('address_city')->setChoices(empty($adressCitiesChoices) ? ["Aucun" => 0] : $adressCitiesChoices)->setFormTypeOption('value_type_options.multiple', true))
            ->add('programSent', $this->translator->trans('is_receiving_festival_program'))
        ;

        $filters
            ->add('near_parcs')
            ->add('newsletter_types')
        ;

        $filters
            ->add('is_festival_partner')
            ->add('is_company_programmed_in_festival')
            ->add('is_workshop_partner');

        return $filters;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Structure')
            ->setEntityLabelInPlural('Structures')
            ->setFormThemes(['admin/form/contacts_list.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(4);
        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance();
        $structureRepository = $this->entityManager->getRepository(Structure::class);

        return [
            FormField::addTab('STRUCTURE'),
            FormField::addColumn(6),
            FormField::addFieldset('Général'),
            TextField::new('name', $this->translator->trans('name'))
                ->hideOnDetail()
                ->setTemplatePath('admin/fields/detail_link.html.twig'),
            TextField::new('name', $this->translator->trans('name'))
                ->onlyOnDetail(),
            UrlField::new('website', $this->translator->trans('website'))
                ->hideOnIndex(),
            AssociationField::new('structureType', $this->translator->trans('structure_type'))
                ->setQueryBuilder(fn (QueryBuilder $queryBuilder) => $queryBuilder->addCriteria(Criteria::create()->orderBy(['name' => 'ASC'])))
                ->hideOnIndex(),
            AssociationField::new('structure_type_specializations', $this->translator->trans('structure_type_specializations'))
                ->setFormTypeOptions([
                    'choices' => $entity && $entity->getStructureTypeSpecializations() ? $entity->getStructureTypeSpecializations() : []
                ])
                ->setTemplatePath('admin/fields/association_field.html.twig')
                ->hideOnIndex(),
            AssociationField::new('disciplines', $this->translator->trans('disciplines'))
                ->setQueryBuilder(fn (QueryBuilder $queryBuilder) => $queryBuilder->addCriteria(Criteria::create()->orderBy(['name' => 'ASC'])))
                ->formatValue(fn($value, Structure $structure) => implode(', ', $value->toArray()))
                ->hideOnIndex(),
            TextEditorField::new('structure_notes', $this->translator->trans('structure_notes'))
                ->onlyOnForms(),
            TextField::new('structure_notes', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addFieldset('Festival'),
            BooleanField::new('is_festival_organizer', $this->translator->trans('is_festival_organizer'))
                ->hideOnIndex(),
            TextEditorField::new('festival_informations', $this->translator->trans('festival_informations'))
                ->onlyOnForms(),
            TextField::new('festival_informations', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addColumn(6),
            FormField::addFieldset('Coordonnées'),
            EmailField::new('email', $this->translator->trans('email')),
            CollectionField::new('phone_numbers', $this->translator->trans('structure_phone_number'))
                ->setEntryType(StructurePhoneNumberType::class)
                ->allowDelete(true)
                ->setEntryIsComplex()
                ->renderExpanded()
                ->hideOnIndex(),
            FormField::addFieldset('Adresse')
                ->hideOnIndex(),
            TextField::new('address_street', $this->translator->trans('address_street'))
                ->hideOnDetail()
                ->hideOnIndex(),
            TextField::new('address_adition', $this->translator->trans('address_adition'))
                ->hideOnDetail()
                ->hideOnIndex(),
            IntegerField::new('address_code', $this->translator->trans('address_code'))
                ->hideOnDetail()
                ->hideOnIndex(),
            TextField::new('address_city', $this->translator->trans('address_city'))
                ->hideOnDetail()
                ->hideOnIndex(),
            CountryField::new('address_country', $this->translator->trans('address_country'))
                ->hideOnDetail()
                ->hideOnIndex()
                ->setEmptyData('FR'),
            Field::new('formatted_address', $this->translator->trans('Address'))
                ->hideOnForm()
                ->setFormTypeOptions([
                    'mapped' => false,
                ]),

            FormField::addTab('CONTACTS'),
            ChoiceField::new('contact_details', $this->translator->trans('contacts'))
                ->onlyOnForms()
                ->setChoices(
                    static fn (?Structure $structure): array => ($structure) ? $structure->getContacts()->toArray() : []
                )
                ->setFormTypeOptions([
                    'choice_label' => function (?Contact $contact): string {
                        return $contact ? $contact->__toString() : '';
                    },
                    'attr' => ['readonly' => ''],
                    'block_name' => 'contacts_list',
                    'mapped' => false,
                ]),

            Field::new('contacts', false)
                ->setTemplatePath('admin/fields/contacts.html.twig')
                ->onlyOnDetail(),

            CollectionField::new('contacts', 'Contacts')
                ->onlyOnIndex(),
                
            FormField::addTab('COMMUNICATION'),
            FormField::addColumn(6),
            FormField::addFieldset('Général'),
            AssociationField::new('near_parcs', $this->translator->trans('near_parcs'))
                ->setTemplatePath('admin/fields/association_field.html.twig')
                ->renderAsNativeWidget()
                ->setFormTypeOptions([
                    'expanded' => true,
                    'multiple' => true,
                ])
                ->hideOnIndex(),
            TextEditorField::new('communication_notes', $this->translator->trans('communication_notes'))
                ->onlyOnForms(),
            TextField::new('communication_notes', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addColumn(6),
            FormField::addFieldset('Envoi newsletters'),
            ChoiceField::new('newsletter_email', $this->translator->trans('newsletter_email'))
                ->setChoices(static fn (?Structure $structure): array => ($structure !== null) ? [$structure->getEmail() => $structure->getEmail()] : [])
                ->hideOnIndex(),
            AssociationField::new('newsletter_types', $this->translator->trans('newsletter_types'))
                ->renderAsNativeWidget()
                ->setFormTypeOption('expanded', true)
                ->formatValue(fn($value, Structure $structure) => implode(', ', $value->toArray()))
                ->hideOnIndex(),
            
            FormField::addTab($this->translator->trans('post_program'))
                ->hideOnIndex()
                ->hideWhenCreating()
            ,

            BooleanField::new('programSent', 'Cette structure reçoit le programme du festival')->onlyWhenUpdating(),
            BooleanField::new('programSent', 'Reçoit le programme du festival')->renderAsSwitch(false)->hideOnForm(),

            CollectionField::new('programPostings', "Si oui, adresser le programme à :")
                ->useEntryCrudForm(ProgramPostingFromStructureCrudController::class)
                ->setRequired(false)
                ->onlyWhenUpdating()
                ->setFormTypeOptions([
                    'entry_options' => [
                        'constraints' => [
                            new UniqueEntity('contact', $this->translator->trans('This contact already receive the festival program')),
                        ],
                    ],
                ])
                ->setHelp("Laisser vide pour envoyer le programme à la structure sans l'adresser à une personne en particulier.")
                , 
            
            CollectionField::new('programPostings', false)
                ->setTemplatePath('admin/fields/contact_program_posting.html.twig')
                ->onlyOnDetail()
            ,

            FormField::addTab('RELATION A L\'ASSOCIATION'),
            BooleanField::new('is_festival_partner', $this->translator->trans('is_festival_partner'))
                ->hideOnIndex(),
            BooleanField::new('is_company_programmed_in_festival', $this->translator->trans('is_company_programmed_in_festival'))
                ->hideOnIndex(),
            BooleanField::new('is_workshop_partner', $this->translator->trans('is_workshop_partner'))
                ->hideOnIndex(),
            TextEditorField::new('organization_notes', $this->translator->trans('organization_notes'))
                ->onlyOnForms(),
            TextField::new('organization_notes', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addTab('AUTRES')
                ->onlyOnDetail(),
            DateField::new('created_at', $this->translator->trans('created_at'))
                ->onlyOnDetail(),
            DateField::new('updated_at', $this->translator->trans('updated_at'))
                ->onlyOnDetail(),
        ];
    }

    public function createNewFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {        
        $builder = parent::createNewFormBuilder($entityDto, $formOptions, $context);
        $this->extendForms($builder, $entityDto);

        $builder->get('newsletter_email')->resetViewTransformers();

        return $builder;
    }

    public function createEditFormBuilder(EntityDto $entityDto, KeyValueStore $formOptions, AdminContext $context): FormBuilderInterface
    {        
        $builder = parent::createEditFormBuilder($entityDto, $formOptions, $context);
        $this->extendForms($builder, $entityDto);

        $builder->get('newsletter_email')->resetViewTransformers();

        return $builder;
    }

    private function extendForms(FormBuilderInterface $builder, EntityDto $entityDto): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) use ($entityDto): void {
                $structure_type = $event->getData()->getStructureType();
                
                $specializations = ($structure_type) ? $structure_type->getStructureTypeSpecializations()->toArray() : [];

                usort($specializations, function($current, $next) {
                    return strnatcmp(strtolower($current->getName()), strtolower($next->getName()));
                });

                $options = $event->getForm()->get('structure_type_specializations')->getConfig()->getOptions();
                $options['choices'] = $specializations; // Fetch your choices
                $options['class'] = StructureTypeSpecialization::class;
                $event->getForm()?->add('structure_type_specializations', EntityType::class, $options);
            }
        );

        $builder->get('structureType')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $structure_type = $event->getForm()->getData();
               
                $specializations = ($structure_type) ? $structure_type->getStructureTypeSpecializations()->toArray() : [];

                usort($specializations, function($current, $next) {
                    return strnatcmp(strtolower($current->getName()), strtolower($next->getName()));
                });

                $options = $event->getForm()->getParent()?->get('structure_type_specializations')->getConfig()->getOptions() ?? [];
                $options['choices'] = $specializations;
                $options['class'] = StructureTypeSpecialization::class;
                $event->getForm()->getParent()?->add('structure_type_specializations', EntityType::class, $options);
            }
        );
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->storeProgramPosting($entityInstance);

        parent::persistEntity($entityManager, $entityInstance);
    }
    
    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $this->storeProgramPosting($entityInstance);

        parent::updateEntity($entityManager, $entityInstance);
    }

    public function storeProgramPosting($entity)
    {
        if ($entity instanceof Structure) {
            // Handle program posting logic here, creating a ProgramPosting record
            if ($entity->getProgramSent()) {      
                foreach($entity->getProgramPostings() as $pp){
                    $pp->setAddressType('professional');
                }
            }else{
                foreach($entity->getProgramPostings() as $pp){
                    $entity->removeProgramPosting($pp);
                }
            }
        }
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];

        if($submitButtonName === Action::SAVE_AND_RETURN) {
            $url =$this->container->get(AdminUrlGenerator::class)
                ->setDashboard(DashboardController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl();

            return $this->redirect($url);
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

    public function exportAsXls(BatchActionDto $batchActionDto) : Response
    {
        $className = $batchActionDto->getEntityFqcn();
        $entityManager = $this->container->get('doctrine')->getManagerForClass($className);

        $fields =  [
            'name',
            'email',
            'phone_numbers',
            'is_festival_organizer',
            'is_company_programmed_in_festival',
            'is_workshop_partner',
            'address_street',
            'address_adition',
            'address_code',
            'address_country',
            'address_city',
            'near_parcs',
            'structure_type',
            'structure_type_specializations',
            'festival_informations',
            'post_program_address',
            'is_festival_partner',
            'newsletter_email',
            'newsletter_types',
        ];

        $entities = array_map(function($id) use ($className, $entityManager) {
            return $entityManager->find($className, $id);
        }, $batchActionDto->getEntityIds());

        $spreadsheet = $this->entitySpreadsheetGenerator
            ->setWorksheetTitle('Structures')
            ->getSpreadsheet($entities, $fields);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");

        ob_start();
        $writer->save('php://output');

        return new Response(
            ob_get_clean(),
            200,
            array(
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="Export - Structures.xls"',
            )
        );
    }
}

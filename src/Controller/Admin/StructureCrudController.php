<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\IsReceivingFestivalProgramFilter;
use App\Entity\Contact;
use App\Entity\ProgramPosting;
use App\Entity\Structure;
use App\Entity\StructureTypeSpecialization;
use App\Form\Admin\ProgramPostingFromStructureType;
use App\Form\Admin\StructurePhoneNumberType;
use App\Repository\ContactRepository;
use App\Service\EntitySpreadsheetGenerator;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGeneratorInterface;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\Collection;
use Symfony\Component\Validator\Constraints\Unique;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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
        $exportXlsBtn = Action::new('exportAllAsXls', 'Export whole list as Xlsx', 'fa-regular fa-file-excel')
            ->asSuccessAction()
            ->linkToCrudAction('exportAllAsXls')
            ->createAsGlobalAction()
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $exportXlsBtn)
            ->addBatchAction(Action::new('xlsExport', 'Export selected items as Xlsx')
                ->linkToCrudAction('exportAsXls')
                ->asPrimaryAction()
                ->setIcon('fa-solid fa-file-excel'))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
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
            ->add(BooleanFilter::new('programSent', 'is_receiving_festival_program'))
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
            ->setPaginatorRangeSize(4)
            ->askConfirmationOnBatchActions(false)
            ->setSearchFields(['name', 'email', 'structure_notes', 'organization_notes', 'address_street', 'address_city', 'festival_informations', 'contact_details.contact.firstname', 'contact_details.contact.lastname']);

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var \App\Entity\Structure|null */
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
                //->setRequired(true)
                ->setFormTypeOption('attr', ['required' => true])
                ->setFormTypeOption('label_attr', ['class' => 'required'])
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
                ->hideOnIndex()
                ->setColumns(7),
            TextField::new('address_adition', $this->translator->trans('address_adition'))
                ->hideOnDetail()
                ->hideOnIndex()
                ->setColumns(5),
            TextField::new('address_code', $this->translator->trans('address_code'))
                ->hideOnDetail()
                ->hideOnIndex()
                ->setColumns(2),
            TextField::new('address_city', $this->translator->trans('address_city'))
                ->hideOnDetail()
                ->hideOnIndex()
                ->setColumns(7),
            CountryField::new('address_country', $this->translator->trans('address_country'))
                ->hideOnDetail()
                ->hideOnIndex()
                ->setEmptyData('FR')
                ->setColumns(3),
            Field::new('formatted_address', false)
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
                //->hideWhenCreating()
            ,

            BooleanField::new('programSent', 'Cette structure reçoit le programme du festival')
                ->setFormTypeOptions([
                    'constraints' => [
                        new Callback(function(mixed $value, ExecutionContextInterface $context, mixed $payload) use ($entity) {
                            $isAddressComplete = $entity->getAddressCity() && $entity->getAddressCode() && $entity->getAddressCountry() && $entity->getAddressStreet();
                            
                            if($entity && $value === true && !$isAddressComplete) {
                                $context
                                    ->buildViolation($this->translator->trans('Structure address is incomplete'))
                                    ->addViolation();
                            }
                        })
                    ],
                ])
                //->onlyWhenUpdating()
                ,
            BooleanField::new('programSent', 'Reçoit le programme du festival')->renderAsSwitch(false)->hideOnForm(),

            CollectionField::new('programPostings', "Si oui, adresser le programme à :")
                ->useEntryCrudForm(ProgramPostingFromStructureCrudController::class)
                ->setRequired(false)
                ->onlyWhenUpdating()
                ->setEntryIsComplex()
                ->setFormTypeOptions([
                    'entry_options' => [
                        'constraints' => [
                            new UniqueEntity('contact', $this->translator->trans('This contact already receive the festival program')),
                        ],
                    ],
                ])
                ->setHelp("Laisser vide pour envoyer le programme à la structure sans l'adresser à une personne en particulier.")
                ->addFormTheme('themes/post_program_from_structure.html.twig')
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
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'] ?? null;

        if($submitButtonName === Action::SAVE_AND_RETURN) {

            $url = $this->container->get(AdminUrlGeneratorInterface::class)
                    ->setAction(Action::DETAIL)
                    ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                    ->generateUrl();

            return $this->redirect($url);
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

    public function exportAllAsXls(AdminContext $context)
    {
        $sort_fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $sort_fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $sort_fields, $filters);
        $entities = $queryBuilder->getQuery()->getResult();

        return $this->generateXlsExport($entities);
    }

    public function exportAsXls(BatchActionDto $batchActionDto) : Response
    {
        $className = $batchActionDto->getEntityFqcn();
        $entityManager = $this->container->get('doctrine')->getManagerForClass($className);

        $entities = array_map(function($id) use ($className, $entityManager) {
            return $entityManager->find($className, $id);
        }, $batchActionDto->getEntityIds());

        return $this->generateXlsExport($entities);

    }

    public function generateXlsExport($entities)
    {
        $fields = [
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
            'program_sent',
            'post_program_contacts',
            'post_program_contacts_structuresFunctions',
            'is_festival_partner',
            'newsletter_email',
            'newsletter_types',
        ];

        $spreadsheet = $this->entitySpreadsheetGenerator
            ->setWorksheetTitle('Structures')
            ->getSpreadsheet($entities, $fields);

        $writer = new Xlsx($spreadsheet);

        // Nettoyage des tampons de sortie
        if (ob_get_level()) {
            ob_end_clean();
        }

        $response = new StreamedResponse(
            function () use ($writer) {
                $writer->save('php://output');
            }
        );

        // Définition des en-têtes
        $response->headers->set('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            "Export_Structures_" . date('d-m-Y') . ".xlsx"
        ));

        // Désactive la mise en cache pour éviter les problèmes de corruption
        $response->headers->set('Cache-Control', '');
        $response->headers->set('Pragma', 'public');

        // Libération des ressources
        $response->sendHeaders();
        $writer->save('php://output');
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet, $writer);

        return $response;
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\Structure;
use App\Entity\Discipline;
use App\Entity\PostProgram;
use App\Entity\ContactDetail;
use App\Entity\ProgramPosting;
use Doctrine\ORM\QueryBuilder;
use App\Form\Admin\ContactDetailType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Collections\Criteria;
use App\Form\ProgramPostingFromContactType;
use App\Service\EntitySpreadsheetGenerator;
use Symfony\Component\HttpFoundation\Response;
use Doctrine\Common\Collections\ArrayCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use App\Controller\Admin\Filter\HasStructureFilter;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Validator\Constraints\Callback;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Contracts\Translation\TranslatorInterface;
use App\Controller\Admin\Filter\ContactStructureFilter;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Factory\FilterFactory;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use App\Controller\Admin\Filter\HasStructureFunctionFilter;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use App\Controller\Admin\Filter\IsReceivingFestivalProgramFilter;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\BooleanFilterType;
use App\Controller\Admin\Filter\ContactIsReceivingFestivalProgramFilter;

class ContactCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private EntitySpreadsheetGenerator $entitySpreadsheetGenerator,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {}
    
    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Contact')
            ->setEntityLabelInPlural('Contacts')
            ->renderContentMaximized()
            ->setFormThemes(['admin/form/contact_profile_type.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(4)
            ->setDefaultSort(['lastname' => 'ASC'])
            ->setSearchFields(['firstname', 'lastname', 'contact_details.structure.name']);

        return $crud;
    }

    /*public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $queryBuilder
             ->leftJoin('entity.contact_details', 'contact_details')
             ->leftJoin('contact_details.structure', 'structure')
        ;
        
        return $queryBuilder;
    }*/

    public function configureActions(Actions $actions): Actions
    {
        $createAndInspectActionName = 'createAndInspect';

        $exportXlsBtn = Action::new('exportAllAsXls', 'Export XLS', 'fa-regular fa-file-excel')
            ->addCssClass('btn-success text-white')
            ->linkToCrudAction('exportAllAsXls')
            ->createAsGlobalAction()
        ;

        $exportPdfBtn = Action::new('exportAllAsPdf', 'Export PDF', 'fa-solid fa-file-pdf')
            ->addCssClass('btn-success text-white')
            ->linkToCrudAction('exportAllAsPdf')
            ->createAsGlobalAction()
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_NEW, Action::new($createAndInspectActionName)
                ->setCssClass('action-'.Action::SAVE_AND_RETURN)
                ->addCssClass('btn btn-primary action-save')
                ->displayAsButton()
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $createAndInspectActionName])
                ->linkToCrudAction(Action::NEW)
                ->setLabel($this->translator->trans('Create and inspect'))
            )
            ->add(Crud::PAGE_INDEX, $exportXlsBtn)
            ->add(Crud::PAGE_INDEX, $exportPdfBtn)
            ->addBatchAction(Action::new('xlsExport', 'Export XLS')
                ->linkToCrudAction('exportAsXls')
                ->addCssClass('btn btn-primary')
                ->setIcon('fa-solid fa-file-excel'))
            ->addBatchAction(Action::new('pdfExport', 'Export PDF')
                ->linkToCrudAction('exportAsPdf')
                ->addCssClass('btn btn-primary')
                ->setIcon('fa-solid fa-file-pdf'))
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_CONTINUE)
            ->reorder(Crud::PAGE_NEW, [Action::INDEX, $createAndInspectActionName])
            ->update(Crud::PAGE_INDEX, Action::NEW, fn(Action $action) => $action->setLabel('Créer un Contact'))
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        $filters
            ->add('lastname')
            ->add('profile_types')
            ->add('disciplines')
            ->add('is_workshop_artist')
            ->add('formationParticipantTypes')
            ->add('is_formation_speaker')
            ->add(HasStructureFilter::new('structure'))
        ;

        $filters->add(
        HasStructureFunctionFilter::new('structure_function', $this->translator->trans('structure_function'))
            ->setFormType(ChoiceFilterType::class)
            ->setFormTypeOption('value_type_options.choices', $this->entityManager->getRepository(ContactDetail::class)->findStructureFunctions())
            ->setFormTypeOption('value_type_options.choice_label', fn($choice, string $key, mixed $value): string => $value)
        );

        $filters->add(
        ContactStructureFilter::new('structure:address_code', ChoiceFilterType::class, ['value_type_options.multiple' => true], $this->translator->trans('structure_address_code'))
            ->setFormTypeOption('value_type_options.choices', $this->entityManager->getRepository(Contact::class)->findStructuresAddressCodes())
            ->setFormTypeOption('value_type_options.choice_label', fn($choice, string $key, mixed $value): string => $value)
        );

        $filters->add(
        ContactStructureFilter::new('structure:address_city', ChoiceFilterType::class, ['value_type_options.multiple' => true], $this->translator->trans('structure_address_city'))
            ->setFormTypeOption('value_type_options.choices', $this->entityManager->getRepository(Contact::class)->findStructuresAddressCity())
            ->setFormTypeOption('value_type_options.choice_label', fn($choice, string $key, mixed $value): string => $value)
        );

        $filters->add(BooleanFilter::new('programSent', 'is_receiving_festival_program'));

        $filters
            ->add('newsletter_types')
            ->add('is_festival_participant')
            ->add('is_board_of_directors_member')
            ->add('is_organization_participant');

        return $filters;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var \App\Entity\Contact|null */
        $entity = $this->getContext()->getEntity()->getInstance();

        return [
            FormField::addTab('PROFESSIONNEL'),
            FormField::addColumn(6),
            FormField::addFieldset('Général'),
            ChoiceField::new('civility', $this->translator->trans('civility'))
                ->setRequired(true)
                ->setChoices([
                    'Monsieur' => 'M',
                    'Madame' => 'F',
                ])
                //->renderExpanded()
                ,
            TextField::new('firstname', $this->translator->trans('firstname'))
                ->setRequired(true)
                ->hideOnDetail()
                ->setTemplatePath('admin/fields/detail_link.html.twig'),
            TextField::new('firstname', $this->translator->trans('firstname'))
                ->onlyOnDetail(),
            TextField::new('lastname', $this->translator->trans('lastname'))
                ->setRequired(true)
                ->hideOnDetail()
                ->setTemplatePath('admin/fields/detail_link.html.twig'),
            TextField::new('lastname', $this->translator->trans('lastname'))
                ->onlyOnDetail(),
            UrlField::new('website', $this->translator->trans('website'))
                ->hideOnIndex(),
            AssociationField::new('profile_types', $this->translator->trans('profile_types'))
                ->setRequired(true)
                ->setFormTypeOptions([
                    'attr' => [
                        'class' => 'profile_types',
                    ],
                ])
                ->setQueryBuilder(fn (QueryBuilder $queryBuilder) => $queryBuilder->addCriteria(Criteria::create()->orderBy(['name' => 'ASC'])))
                ->setTemplatePath('admin/fields/association_field.html.twig')
                ->hideOnIndex(),

            AssociationField::new('disciplines', $this->translator->trans('disciplines'))
                ->setFormTypeOptions([
                    'attr' => ['class' => 'discipline_type'],
                    'choices' => $entity && $entity->getDisciplines() ? $entity->getDisciplines() : []
                ])
                ->setTemplatePath('admin/fields/association_field.html.twig')
                ->hideOnIndex(),
            BooleanField::new('is_workshop_artist', $this->translator->trans('is_workshop_artist'))
                ->hideOnIndex(),
            AssociationField::new('formationParticipantTypes', $this->translator->trans('formationParticipantTypes'))
                ->setFormTypeOption('by_reference', false)
                ->setQueryBuilder(fn (QueryBuilder $queryBuilder) => $queryBuilder->addCriteria(Criteria::create()->orderBy(['name' => 'ASC'])))
                ->setTemplatePath('admin/fields/association_field.html.twig')
                ->hideOnIndex(),
            BooleanField::new('is_formation_speaker', $this->translator->trans('is_formation_speaker'))
                ->hideOnIndex(),
            TextEditorField::new('professional_notes', $this->translator->trans('professional_notes'))
                ->onlyOnForms(),
            TextField::new('professional_notes', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addColumn(6), 
            FormField::addFieldset('Coordonnées'),
            CollectionField::new('contact_details', $this->translator->trans('contact_details'))
                ->setEntryType(ContactDetailType::class)
                ->setLabel(false)
                ->allowDelete(true)
                ->setEntryIsComplex()
                ->renderExpanded()
                ->hideOnIndex()
                ->addFormTheme('themes/contact_details_collection.html.twig')
                ->setTemplatePath('admin/fields/contact_details.html.twig')
                ,
            Field::new('structures_functions', $this->translator->trans('structure_functions'))
                ->formatValue(fn(ArrayCollection $structuresFunctions) => implode(', ', $structuresFunctions->toArray()))
                ->onlyOnIndex(),
            /*Field::new('formatted_structures', $this->translator->trans('structures'))
                ->formatValue(function(ArrayCollection $structures)  {
                    $anchor = '<a href="">';
                    $structures = array_map(function(Structure $structure) {
                        $url = $this->adminUrlGenerator
                            ->setController(StructureCrudController::class)
                            ->setEntityId($structure->getId())
                            ->setAction(Action::DETAIL);
                        return '<a href="' . $url . '">' . $structure . '</a>';
                    }, $structures->toArray());
                    return implode(', ', $structures);
                })
                ->onlyOnIndex(),*/

            AssociationField::new('contact_details', $this->translator->trans('structures'))
                    ->setTemplatePath('admin/fields/contact_structures.html.twig')
                    ->setTextAlign('left')
                    //->setSortable(true) -> TODO: sort by Structure name
                    ->setQueryBuilder(function ($queryBuilder) {
                        // Tri personalisé ici
                        return $queryBuilder->orderBy('structure.name', 'ASC');
                    })
                    ->onlyOnIndex()
            ,

            FormField::addTab('PERSONNEL'),
            FormField::addColumn(6),
            FormField::addFieldset('Général'),
            EmailField::new('personal_email', $this->translator->trans('personal_email'))
                ->hideOnIndex(),
            TelephoneField::new('personal_phone_number', $this->translator->trans('personal_phone_number'))
                ->setFormType(PhoneNumberType::class)
                ->setFormTypeOptions([
                    'widget' => PhoneNumberType::WIDGET_COUNTRY_CHOICE,
                    'preferred_country_choices' => ['FR', 'US'],
                    'number_options' => [
                        'label' => $this->translator->trans('phone_number')
                    ],
                    'country_options' => [
                        'label' => $this->translator->trans('country'),
                    ],
                ])
                ->hideOnIndex(),
            TextEditorField::new('personal_notes', $this->translator->trans('personal_notes'))
                ->onlyOnForms(),
            TextField::new('personal_notes', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addColumn(6),
            FormField::addFieldset('Adresse'),
            TextField::new('address_street', $this->translator->trans('address_street'))
                ->onlyOnForms(),
            TextField::new('address_adition', $this->translator->trans('address_adition'))
                ->onlyOnForms(),
            IntegerField::new('address_code', $this->translator->trans('address_code'))
                ->onlyOnForms(),
            TextField::new('address_city', $this->translator->trans('address_city'))
                ->onlyOnForms(),
            CountryField::new('address_country', $this->translator->trans('address_country'))
                ->setEmptyData('FR')
                ->onlyOnForms(),
            TextField::new('formatted_address', $this->translator->trans('Address'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addTab('COMMUNICATION'),

            FormField::addColumn(6),
            FormField::addFieldset('Général'),
            TextEditorField::new('communication_notes', $this->translator->trans('communication_notes'))
                ->onlyOnForms(),
            TextField::new('communication_notes', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addColumn(6),
            FormField::addFieldset('Envoi newsletters'),
            ChoiceField::new('newsletter_email', $this->translator->trans('newsletter_email'))
                ->setChoices(fn(?Contact $contact) => ($contact?->getNewsletterEmail() !== null) ? [$contact?->getNewsletterEmail() => $contact?->getNewsletterEmail()] : [])
                ->hideOnIndex(),
            AssociationField::new('newsletter_types', $this->translator->trans('newsletter_types'))
                ->renderAsNativeWidget()
                ->formatValue(fn($value, Contact $contact) => implode(', ', $value->toArray()))
                ->setFormTypeOption('expanded', true)
                ->hideOnIndex(),
            
            FormField::addTab($this->translator->trans('post_program'))
                ->hideOnIndex(),
                FormField::addColumn(12),
                
            BooleanField::new('programSent', 'Ce contact reçoit le programme du festival')
                ->setFormTypeOptions([
                    'constraints' => [
                        new Callback(function(mixed $value, ExecutionContextInterface $context, mixed $payload) use ($entity) {
                            $isAddressComplete = $entity->getAddressCity() && $entity->getAddressCode() && $entity->getAddressCountry() && $entity->getAddressStreet();
                            
                            if($entity && $value === true && $entity->getProgramPosting()?->getAddressType() === 'personal' && !$isAddressComplete) {
                                $context
                                    ->buildViolation($this->translator->trans('Contact address is incomplete'))
                                    ->addViolation();
                            }
                        })
                    ],
                ])
                ->onlyWhenUpdating(),
            BooleanField::new('programSent', 'Reçoit le programme du festival')->renderAsSwitch(false)->hideOnForm(),
            
            AssociationField::new('programPosting', false) // New field to select professional structures
                ->renderAsEmbeddedForm(ProgramPostingFromContactCrudController::class)
                ->setFormTypeOption('attr', ['class' => 'program_posting_fieldset'])
                ->formatValue(fn(?ProgramPosting $value) => $value?->getSendThrough())
                ->setRequired(false)
                ->hideOnIndex()
                ,  

            FormField::addTab('RELATION A L\'ASSOCIATION'),
            BooleanField::new('is_festival_participant', $this->translator->trans('is_festival_participant'))
                ->hideOnIndex(),
            BooleanField::new('is_board_of_directors_member', $this->translator->trans('is_board_of_directors_member'))
                ->hideOnIndex(),
            BooleanField::new('is_organization_participant', $this->translator->trans('is_organization_participant'))
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
                $profile_types = $event->getData()->getProfileTypes();
                
                $disciplines = [];

                foreach($profile_types as $profile_type)
                {
                    foreach($profile_type->getDisciplines() as $discipline)
                    {
                        if($discipline) $disciplines[] = $discipline;
                    }
                }

                usort($disciplines, function($current, $next) {
                    return strnatcmp(strtolower($current->getName()), strtolower($next->getName()));
                });

                $options = $event->getForm()->get('disciplines')->getConfig()->getOptions();
                $options['choices'] = $disciplines; // Fetch your choices
                $options['class'] = Discipline::class;
                $event->getForm()?->add('disciplines', EntityType::class, $options);
            }
        );

        $builder->get('profile_types')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $profile_types = $event->getForm()->getData();
                $disciplines = [];
                foreach($profile_types as $profile_type)
                {
                    foreach($profile_type->getDisciplines() as $d)
                    {
                        $disciplines[] = $d;
                    }
                }

                usort($disciplines, function($current, $next) {
                    return strnatcmp(strtolower($current->getName()), strtolower($next->getName()));
                });

                $options = $event->getForm()->getParent()?->get('disciplines')->getConfig()->getOptions() ?? [];
                $options['choices'] = $disciplines;
                $options['class'] = Discipline::class;
                $event->getForm()->getParent()?->add('disciplines', EntityType::class, $options);
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
        if ($entity instanceof Contact) {
            // Handle program posting logic here, creating a ProgramPosting record
            if ($entity->getProgramSent()) {
                $programPosting = $entity->getProgramPosting();
                $programPosting->setContact($entity);
                
                if ($programPosting->getAddressType() === 'personal') {
                    $programPosting->setStructure(null);
                }
                
            }else{
                $entity->setProgramPosting(null);
            }
        }
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
        $fields =  [
            'civility',
            'firstname',
            'lastname',
            'structure',
            'structure_function',
            'professional_email',
            'professionnal_phone_numbers',
            'profile_types',
            'disciplines',
            'is_workshop_artist',
            'formationParticipantTypes',
            'is_formation_speaker',
            'personal_email',
            'personal_phone_number',
            'address_street',
            'address_adition',
            'address_code',
            'address_city',
            'address_country',
            'newsletter_email',
            'newsletter_types',
            'program_sent',
            'post_program_address',
            'is_festival_participant',
            'is_board_of_directors_member',
            'is_organization_participant',
        ];

        $replacements = [
            [
                'value' => 'woman',
                'defaultsTo' => 'Femme',
            ],
            [
                'value' => 'man',
                'defaultsTo' => 'Homme',
            ],
            [
                'value' => true,
                'defaultsTo' => 'Oui',
            ],
            [
                'value' => false,
                'defaultsTo' => 'Non',
            ],
            [
                'value' => null,
                'defaultsTo' => 'Aucun(e)',
            ],
        ];

        $spreadsheet = $this->entitySpreadsheetGenerator
            ->setValueReplacements($replacements)
            ->setWorksheetTitle('Contacts')
            ->getContactsSpreadsheet($entities, $fields);

        $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, "Xlsx");

        ob_start();
        $writer->save('php://output');

        return new Response(
            ob_get_clean(),
            200,
            array(
                'Content-Type' => 'application/vnd.ms-excel',
                'Content-Disposition' => 'attachment; filename="Export - Contacts.xls"',
            )
        );
    }

    public function exportAllAsPdf(AdminContext $context, DompdfWrapperInterface $domPdfWrapper)
    {
        $sort_fields = FieldCollection::new($this->configureFields(Crud::PAGE_INDEX));
        $filters = $this->container->get(FilterFactory::class)->create($context->getCrud()->getFiltersConfig(), $sort_fields, $context->getEntity());
        $queryBuilder = $this->createIndexQueryBuilder($context->getSearch(), $context->getEntity(), $sort_fields, $filters);
        $entities = $queryBuilder->getQuery()->getResult();

        $html = $this->renderView('admin/views/contacts_pdf.html.twig', ['contacts' => $entities]);

        $response = $domPdfWrapper->getStreamResponse($html, "document.pdf");
        $response->send();
    }

    public function exportAsPdf(BatchActionDto $batchActionDto, DompdfWrapperInterface $domPdfWrapper) : void
    {
        $className = $batchActionDto->getEntityFqcn();
        $entityManager = $this->container->get('doctrine')->getManagerForClass($className);
        $contacts = array_map(function($id) use ($className, $entityManager) {
            return $entityManager->find($className, $id);
        }, $batchActionDto->getEntityIds());

        $html = $this->renderView('admin/views/contacts_pdf.html.twig', ['contacts' => $contacts]);

        $response = $domPdfWrapper->getStreamResponse($html, "document.pdf");
        $response->send();
    }

    protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    {
        $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];

        if($submitButtonName === Action::SAVE_AND_RETURN && $context->getCrud()->getCurrentPage() === Action::EDIT) {
            $url =$this->container->get(AdminUrlGenerator::class)
                ->setDashboard(DashboardController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl();

            return $this->redirect($url);
        }

        if($submitButtonName === 'createAndInspect') {
            $url =$this->container->get(AdminUrlGenerator::class)
                ->setDashboard(DashboardController::class)
                ->setAction(Action::DETAIL)
                ->setEntityId($context->getEntity()->getPrimaryKeyValue())
                ->generateUrl();

            return $this->redirect($url);
        }

        return parent::getRedirectResponseAfterSave($context, $action);
    }

    // protected function getRedirectResponseAfterSave(AdminContext $context, string $action): RedirectResponse
    // {
    //     $submitButtonName = $context->getRequest()->request->all()['ea']['newForm']['btn'];

    //     $url = match ($submitButtonName) {
    //         Action::SAVE_AND_CONTINUE => $this->container->get(AdminUrlGenerator::class)
    //             ->setAction(Action::EDIT)
    //             ->setEntityId($context->getEntity()->getPrimaryKeyValue())
    //             ->generateUrl(),
    //         Action::SAVE_AND_RETURN => $context->getReferrer()
    //             ?? $this->container->get(AdminUrlGenerator::class)->setAction(Action::INDEX)->generateUrl(),
    //         Action::SAVE_AND_ADD_ANOTHER => $this->container->get(AdminUrlGenerator::class)->setAction(Action::NEW)->generateUrl(),
    //         default => $this->generateUrl($context->getDashboardRouteName()),
    //     };

    //     return $this->redirect($url);
    // }
}

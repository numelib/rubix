<?php

namespace App\Controller\Admin;

use App\Controller\Admin\Filter\ContactDetailFilter;
use App\Controller\Admin\Filter\ContactStructureFilter;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormBuilderInterface;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


use App\Entity\Contact;
use App\Entity\ContactDetail;
use App\Entity\Discipline;
use App\Entity\DisciplineType;
use App\Entity\Structure;
use App\Form\Admin\ContactDetailType;
use App\Form\Admin\ContactNewsletterType;
use App\Service\EntitySpreadsheetGenerator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Dto\BatchActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Contracts\Translation\TranslatorInterface;
use Nucleos\DompdfBundle\Wrapper\DompdfWrapperInterface;

use App\Controller\Admin\Filter\HasStructureFilter;
use App\Controller\Admin\Filter\HasStructureFunctionFilter;
use App\Entity\FestivalProgram;
use App\Service\MailjetAPI;
use EasyCorp\Bundle\EasyAdminBundle\Form\Filter\Type\ChoiceFilterType;
use Symfony\Component\HttpFoundation\RedirectResponse;

class ContactCrudController extends AbstractCrudController
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TranslatorInterface $translator,
        private EntitySpreadsheetGenerator $entitySpreadsheetGenerator,
        private MailjetAPI $mailjetAPI,
    ) {}
    
    public static function getEntityFqcn(): string
    {
        return Contact::class;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        $queryBuilder
            ->leftJoin('entity.structure_sending_festival_program', 'structure_sending_festival_program')
            ->leftJoin('entity.contact_details', 'contact_details')
            ->leftJoin('contact_details.structure', 'structure')
            ->addSelect('contact_details', 'structure', 'structure_sending_festival_program')
        ;
        
        return $queryBuilder;
    }

    public function configureActions(Actions $actions): Actions
    {
        $createAndInspectActionName = 'createAndInspect';
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_NEW, Action::new($createAndInspectActionName, $this->translator->trans('Create and inspect'))
                ->setCssClass('action-'.Action::SAVE_AND_RETURN)
                ->addCssClass('btn btn-primary action-save')
                ->displayAsButton()
                ->setHtmlAttributes(['type' => 'submit', 'name' => 'ea[newForm][btn]', 'value' => $createAndInspectActionName])
                ->linkToCrudAction(Action::NEW)
            )
            ->addBatchAction(Action::new('xlsExport', 'Export XLS')
                ->linkToCrudAction('exportAsXls')
                ->addCssClass('btn btn-primary')
                ->setIcon('fa-solid fa-file-excel'))
            ->addBatchAction(Action::new('pdfExport', 'Export PDF')
                ->linkToCrudAction('exportAsPdf')
                ->addCssClass('btn btn-primary')
                ->setIcon('fa-solid fa-file-pdf'))
            ->reorder(Crud::PAGE_NEW, [Action::INDEX, Action::SAVE_AND_CONTINUE, Action::SAVE_AND_ADD_ANOTHER, $createAndInspectActionName, Action::SAVE_AND_CONTINUE])
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
        ContactStructureFilter::new('structure:address_code', $this->translator->trans('structure_address_code'))
            ->setFormTypeOption('value_type_options.choices', $this->entityManager->getRepository(Contact::class)->findStructuresAddressCodes())
            ->setFormTypeOption('value_type_options.choice_label', fn($choice, string $key, mixed $value): string => $value)
        );

        $filters->add(
        ContactStructureFilter::new('structure:address_city', $this->translator->trans('structure_address_city'))
            ->setFormTypeOption('value_type_options.choices', $this->entityManager->getRepository(Contact::class)->findStructuresAddressCity())
            ->setFormTypeOption('value_type_options.choice_label', fn($choice, string $key, mixed $value): string => $value)
        );

        $filters
            ->add('is_receiving_festival_program')
            ->add('newsletter_types');

        $filters->add(
            ContactStructureFilter::new('structure:is_festival_partner', $this->translator->trans('structure_is_festival_partner'))
        );

        $filters->add(
            ContactStructureFilter::new('structure:is_company_programmed_in_festival', $this->translator->trans('structure_is_company_programmed_in_festival'))
        );

        $filters->add(
            ContactStructureFilter::new('structure:is_workshop_partner', $this->translator->trans('structure_is_workshop_partner'))
        );

        return $filters;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Contact')
            ->setEntityLabelInPlural('Contacts')
            ->renderContentMaximized()
            ->setFormThemes(['admin/form/contact_profile_type.html.twig', '@EasyAdmin/crud/form_theme.html.twig'])
            ->setPaginatorPageSize(20)
            ->setPaginatorRangeSize(4);

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance();

        $formatFestivalProgramReceiptAddress = fn(Structure $structure) => $structure->__toString() . ' - ' . $structure->getFormattedAddress(oneline : true);

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
                ->setTemplatePath('admin/fields/contact_details.html.twig'),
            Field::new('structures_functions', $this->translator->trans('structure_functions'))
                ->formatValue(fn(ArrayCollection $structuresFunctions) => implode(', ', $structuresFunctions->toArray()))
                ->onlyOnIndex(),
            Field::new('structures', $this->translator->trans('structures'))
                ->formatValue(fn(ArrayCollection $structures) => implode(', ', $structures->toArray()))
                ->onlyOnIndex(),
            FormField::addTab('PERSONNEL'),
            FormField::addColumn(6),
            FormField::addFieldset('Général'),
            EmailField::new('personnal_email', $this->translator->trans('personnal_email'))
                ->hideOnIndex(),
            TelephoneField::new('personnal_phone_number', $this->translator->trans('personnal_phone_number'))
                ->hideOnIndex(),
            TextEditorField::new('personnal_notes', $this->translator->trans('personnal_notes'))
                ->onlyOnForms(),
            TextField::new('personnal_notes', $this->translator->trans('professional_notes'))
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
            BooleanField::new('is_receiving_festival_program', $this->translator->trans('is_receiving_festival_program'))
                ->onlyOnForms(),
            TextEditorField::new('communication_notes', $this->translator->trans('communication_notes'))
                ->onlyOnForms(),
            TextField::new('communication_notes', $this->translator->trans('professional_notes'))
                ->renderAsHtml()
                ->onlyOnDetail(),

            FormField::addColumn(6),
            FormField::addFieldset('Envoi newsletters'),
            ChoiceField::new('newsletter_email', $this->translator->trans('newsletter_email'))
                ->setChoices(
                    function (?Contact $contact) 
                    {
                        if(is_null($contact)) return [];
                    
                        $personnalEmail = $contact->getPersonnalEmail();
                        $professionalEmails = $contact->getContactDetails()->map(fn(ContactDetail $contactDetail) => $contactDetail->getEmail());
            
                        $choices = [];
                        if($personnalEmail) $choices['Personnel'][] = $personnalEmail;
                        if($professionalEmails) {
                            foreach($professionalEmails as $index => $professionalEmail)
                            {
                                $choices['Professionnel'][] = $professionalEmail;
                            }
                        }
    
                        return $choices;
                    }

                )
                ->setFormTypeOptions([
                    'choice_label' => fn($newsletterEmail, $key) => $newsletterEmail,
                ])
                ->formatValue(fn($value, Contact $contact) => $value)
                ->hideOnIndex(),
            AssociationField::new('newsletter_types', $this->translator->trans('newsletter_types'))
                ->renderAsNativeWidget()
                ->formatValue(fn($value, Contact $contact) => implode(', ', $value->toArray()))
                ->setFormTypeOption('expanded', true)
                ->hideOnIndex(),
            

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

    public function exportAsXls(BatchActionDto $batchActionDto) : Response
    {
        $className = $batchActionDto->getEntityFqcn();
        $entityManager = $this->container->get('doctrine')->getManagerForClass($className);

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
            'personnal_email',
            'personnal_phone_number',
            'address_street',
            'address_adition',
            'address_code',
            'address_city',
            'address_country',
            'newsletter_email',
            'newsletter_types',
            'is_receiving_festival_program',
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

        $entities = array_map(function($id) use ($className, $entityManager) {
            return $entityManager->find($className, $id);
        }, $batchActionDto->getEntityIds());

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

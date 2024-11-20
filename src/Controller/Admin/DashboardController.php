<?php

namespace App\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use EasyAdminFriends\EasyAdminDashboardBundle\Controller\DefaultController as EasyAdminDashboard;

use App\Entity\Contact;
use App\Entity\ContactDetail;
use App\Entity\ContactDetailPhoneNumber;
use App\Entity\ContactNewsletter;
use App\Entity\FormationParticipantType;
use App\Entity\Newsletter;
use App\Entity\PracticalGuide;
use App\Entity\Structure;
use App\Entity\StructureNewsletter;
use App\Entity\ProfileType;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Contracts\Translation\TranslatorInterface;

class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EasyAdminDashboard $easyAdminDashboard,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly TranslatorInterface $translator
    ){}

    public function index(): Response
    {
        return $this->render('@EasyAdminDashboard/Default/index.html.twig', array(
            'dashboard' => $this->easyAdminDashboard->generateDashboardValues(),
            'layout_template_path' => $this->easyAdminDashboard->getLayoutTemplate()
        ));
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->overrideTemplate('layout', 'admin/advanced_layout.html.twig')
            ->hideNullValues();
    }

    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            // parameters usually defined in Symfony login forms
            'error' => $error,
            'last_username' => $lastUsername,
            'form_title' => (isset($_ENV['APP_NAME'], $_ENV['BUSINESS_NAME'])) ? $this->translator->trans('Base de données ' . $_ENV['APP_NAME'] . ' <br>' . $_ENV['BUSINESS_NAME']) : $this->translator->trans('Application'),
            'page_title' => $_ENV['APP_NAME'] ?? null,
            'csrf_token_intention' => 'authenticate',
            'target_path' => $this->generateUrl('admin'),
            'username_label' => 'Nom d\'utilisateur',
            'password_label' => 'Mot de passe',
            'sign_in_label' => 'Connexion',
            'username_parameter' => '_username',
            'password_parameter' => '_password',
        ]);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            //->addJsFile("https://code.jquery.com/jquery-3.6.0.js")
            //->addJsFile("https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js")
            //->addJsFile('assets/js/admin.js')
            //->addCssFile('assets/css/admin.css')
        ;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('<img src="/assets/img/logo.svg"><span class="d-block mt-3 fw-bold">' . strtoupper($_ENV['APP_NAME'] ?? 'CRM') . '<span>')
            ->renderContentMaximized();
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
        yield MenuItem::linkToCrud('Guide pratique', 'fa fa-address-book', PracticalGuide::class);
        yield MenuItem::section('Contacts');
        yield MenuItem::linkToCrud('contacts', 'fa fa-address-book', Contact::class);
        yield MenuItem::subMenu('Parameters', 'fa fa-cog')
            ->setSubItems([
                MenuItem::linkToCrud('Profils contacts', 'fa-solid fa-user-tag', ProfileType::class),
                MenuItem::linkToCrud('Formations', 'fa-solid fa-graduation-cap' , FormationParticipantType::class)
        ]);

        yield MenuItem::section('Structures');
        yield MenuItem::linkToCrud('Structures', 'fa fa-industry' , Structure::class);
    }

    public function configureActions(): Actions
    {
        return Actions::new()
            ->addBatchAction(Action::BATCH_DELETE)

            ->add(Crud::PAGE_INDEX, Action::NEW)
            ->add(Crud::PAGE_INDEX, Action::EDIT)
            ->add(Crud::PAGE_INDEX, Action::DELETE)
            
            // ->add(Crud::PAGE_INDEX, Action::DETAIL)

            ->add(Crud::PAGE_DETAIL, Action::EDIT)
            ->add(Crud::PAGE_DETAIL, Action::DELETE)
            ->add(Crud::PAGE_DETAIL, Action::INDEX)

            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_EDIT, Action::INDEX)

            ->add(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
            ->add(Crud::PAGE_NEW, Action::SAVE_AND_CONTINUE)
            ->add(Crud::PAGE_NEW, Action::INDEX)


            ->update(Crud::PAGE_EDIT, Action::INDEX, function (Action $action) {
                return $action->setIcon('fa-solid fa-arrow-left');
            })
            ->update(Crud::PAGE_DETAIL, Action::INDEX, function (Action $action) {
                return $action->setIcon('fa-solid fa-arrow-left');
            })
            ->update(Crud::PAGE_NEW, Action::INDEX, function (Action $action) {
                return $action->setIcon('fa-solid fa-arrow-left');
            })
        ;
    }
}
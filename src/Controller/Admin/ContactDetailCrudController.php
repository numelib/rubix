<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\ContactDetail;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
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
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class ContactDetailCrudController extends AbstractCrudController
{        
    public static function getEntityFqcn(): string
    {
        return ContactDetail::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Coordonnées')
            ->setEntityLabelInPlural('Coordonnéess')
            ->renderContentMaximized();

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('structure', 'Structure'),
            AssociationField::new('contact', 'Contact'),
            EmailField::new('email', 'Email'),
            AssociationField::new('contactDetailPhoneNumbers', 'Numéros de téléphone'),
            TextField::new('structure_function', 'Fonction au sein de la structure'),
        ];
    }
}

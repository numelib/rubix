<?php

namespace App\Controller\Admin;

use App\Entity\ContactDetailPhoneNumber;
use App\Entity\Structure;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use Symfony\Component\Intl\Countries;

class ContactDetailPhoneNumberCrudController extends AbstractCrudController
{
    private EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }
    
    public static function getEntityFqcn(): string
    {
        return ContactDetailPhoneNumber::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $crud
            ->setEntityLabelInSingular('Numéros de téléphone Contacts')
            ->setEntityLabelInPlural('Numéros de téléphone Contacts')
            ->renderContentMaximized();

        return $crud;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            AssociationField::new('contact_detail', 'Contact'),
            ChoiceField::new('code', 'Code')
                ->setChoices([
                    '+33' => '33',
                ]),
            TelephoneField::new('phone_number', 'Numéro de téléphone'),
        ];
    }
}

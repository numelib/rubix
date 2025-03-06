<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\ProgramPosting;
use App\Entity\Structure;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Contracts\Translation\TranslatorInterface;

class ProgramPostingFromContactCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager
    ){}
    
    public static function getEntityFqcn(): string
    {
        return ProgramPosting::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity()->getInstance();

        if(!($entity instanceof Contact)) return [];

        if($entity->getId() !== null) {
            $choices = $this->entityManager->getRepository(Structure::class)->findByContact($entity);
        } else {
            $choices = $this->entityManager->getRepository(Structure::class)->findAll();
        }

        $choices = array_combine($choices, $choices);

        return [
            ChoiceField::new('addressType', "Si oui, indiquer à quelle adresse ce contact reçoit le programme :")
                ->setChoices([
                    $this->translator->trans('personal address') => 'personal',
                    $this->translator->trans('professional address') => 'professional',
                ])
                ->renderExpanded()
                //->setHelp('Select the address type for sending the program (personal or professional).')
                ->setRequired(true),
            ChoiceField::new('structure', "Si adresse professionnelle, indiquer dans quelle structure :")
                ->setFormTypeOptions([
                    'placeholder' => $this->translator->trans('none'),
                ])
                ->setRequired(false)
                ->setChoices($choices)
        ];
    }
}

<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\PostProgram;
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

class PostProgramFromContactCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager
    ){}
    
    public static function getEntityFqcn(): string
    {
        return PostProgram::class;
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
            BooleanField::new('is_sent', $this->translator->trans('is_sent'))
                ->setFormTypeOptions([
                    'data' => $entity->getPostProgram() !== null
                ]),
            ChoiceField::new('structure')
                ->setFormTypeOptions([
                    'placeholder' => $this->translator->trans('none'),
                    'help' => 'Si la structure est déjà destinaire du programme, alors cette dernière ne pourra pas être sélectionnée',
                    'choice_attr' => function(?Structure $structure) {
                        $disabled = ($structure?->getPostProgram() !== null);
                        
                        return $disabled ? ['disabled' => 'disabled'] : [];
                    },
                ])
                ->setRequired(false)
                ->setChoices($choices)
        ];
    }
}

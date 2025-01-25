<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\PostProgram;
use App\Entity\Structure;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Contracts\Translation\TranslatorInterface;

class PostProgramFromStructureCrudController extends AbstractCrudController
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

        if(!($entity instanceof Structure)) return [];

        if($entity->getId() !== null) {
            $choices = $this->entityManager->getRepository(Contact::class)->findByStructure($entity);
        } else {
            $choices = $this->entityManager->getRepository(Contact::class)->findAllLeftJoined();
        }

        $choices = array_combine($choices, $choices);

        return [
            BooleanField::new('is_sent', $this->translator->trans('is_sent'))
                ->setFormTypeOptions([
                    'data' => $entity->getPostProgram() !== null
                ]),
            ChoiceField::new('contact')
                ->setFormTypeOptions([
                    'placeholder' => $this->translator->trans('none'),
                    'help' => 'Si le contact est déjà destinaire du programme, alors ce dernier ne pourra pas être sélectionné',
                    'choice_attr' => function(?Contact $contact) {
                        $disabled = ($contact?->getPostProgram() !== null);
                        
                        return $disabled ? ['disabled' => 'disabled'] : [];
                    },
                ])
                ->setRequired(false)
                ->setChoices($choices)
        ];
    }
}

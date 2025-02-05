<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\Structure;
use App\Entity\ProgramPosting;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;

class ProgramPostingFromStructureCrudController extends AbstractCrudController
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

        if(!($entity instanceof Structure)) return [];

        /*if($entity->getId() !== null) {
            $choices = $this->entityManager->getRepository(Contact::class)->findByStructure($entity);
        } else {
            $choices = $this->entityManager->getRepository(Contact::class)->findAllLeftJoined();
        }

        $contacts = [];
        foreach($choices as $c){
            $contacts[$c->__toString()] = $c->getId();
        }*/

        return [
            AssociationField::new('contact')
                ->setFormTypeOptions([
                    'placeholder' => $this->translator->trans('none'),
                    'help' => 'Indiquer le contact à qui adresser le programme',
                    'choices' => $entity->getContacts()
                ])
                ->setRequired(false)
                /*->setQueryBuilder(
                    fn (QueryBuilder $queryBuilder) => $queryBuilder->getEntityManager()->getRepository(Contact::class)->findAllLeftJoined()
                )*/

        ];
    }
}

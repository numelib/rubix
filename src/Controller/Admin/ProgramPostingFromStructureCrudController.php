<?php

namespace App\Controller\Admin;

use App\Entity\Contact;
use App\Entity\Structure;
use App\Entity\ProgramPosting;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use Symfony\Contracts\Translation\TranslatorInterface;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;

class ProgramPostingFromStructureCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ){}
    
    public static function getEntityFqcn(): string
    {
        return ProgramPosting::class;
    }

    public function configureFields(string $pageName): iterable
    {
        $entity = $this->getContext()->getEntity();
        $request = $this->getContext()->getRequest();
        $parentId = $request->query->get('entityId');

        $entity = $this->container->get('doctrine')->getRepository(Structure::class)->find($parentId);

        if(!($entity instanceof Structure)){
            return [];
        }

        return [
            AssociationField::new('contact')
                ->setFormTypeOptions([
                    'placeholder' => $this->translator->trans('none'),
                    'help' => 'Indiquer le contact à qui adresser le programme',
                    'choices' => $entity->getContacts()
                ])
                ->setRequired(false)

        ];
    }
}

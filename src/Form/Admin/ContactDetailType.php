<?php

namespace App\Form\Admin;

use App\Entity\ContactDetail;
use App\Entity\Structure;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactDetailType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly EntityManagerInterface $entityManager
    ){}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', TextType::class, [
                'attr' => ['required' => false],
                'label' => $this->translator->trans('professional_email')
            ])
            ->add('structure_function', TextType::class, [
                'attr' => ['required' => true],
                'label' => $this->translator->trans('structure_function')
            ])
            // ->add('structure', EntityType::class, [
            //     'class' => Structure::class,
            //     'query_builder' => function (EntityRepository $er): QueryBuilder {
            //         return $er->createQueryBuilder('structure')
            //             ->addSelect('structure')
            //             ->orderBy('structure.name', 'ASC');
            //     },
            //     'choice_label' => fn(Structure $structure) => ($structure->getAddressCity() !== null && !empty($structure->getAddressCity())) ? $structure . ' - ' . $structure->getAddressCity() : $structure,
            //     'required' => false,
            //     'attr' => ['required' => false],
            //     'placeholder' => 'Aucun(e)'
            // ])
            ->add('structure', ChoiceType::class, [
                'choices' => $this->entityManager->getRepository(Structure::class)->findAll(),
                'choice_label' => fn(Structure $structure) => ($structure->getAddressCity() !== null && !empty($structure->getAddressCity())) ? $structure . ' - ' . $structure->getAddressCity() : $structure,
                'required' => false,
                'attr' => ['required' => false],
                'placeholder' => 'Aucun(e)'
            ])
            ->add('contactDetailPhoneNumbers', CollectionType::class, [
                'entry_type' => ContactDetailPhoneNumberType::class,
                'allow_add' => true,
                'allow_delete' => true,
                'by_reference' => false,
                'label' => $this->translator->trans('contactDetailPhoneNumbers'),
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactDetail::class,
        ]);
    }
}
<?php

namespace App\Form\Admin;

use App\Entity\ContactDetail;
use App\Entity\Structure;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactDetailType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
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
            //             ->select('structure', 'contacts_receiving_festival_program', 'postProgram', 'contact_details', 'contact', 'postContact', 'postStructure', 'contactPostProgramStructure', 'contactPostProgram')
            //             // Avoid the Doctrine "n+1" problem
            //             ->join('structure.contacts_receiving_festival_program', 'contacts_receiving_festival_program')
            //             ->join('structure.postProgram', 'postProgram')
            //             ->join('postProgram.contact', 'postContact')
            //             ->join('postProgram.structure', 'postStructure')
            //             ->join('structure.contact_details', 'contact_details')
            //             ->join('contact_details.contact', 'contact')
            //             ->join('contact.postProgram', 'contactPostProgram')
            //             ->join('contactPostProgram.structure', 'contactPostProgramStructure')
            //             ->orderBy('structure.name', 'ASC');
            //     },
            //     'choice_label' => fn(Structure $structure) => ($structure->getAddressCity() !== null && !empty($structure->getAddressCity())) ? $structure . ' - ' . $structure->getAddressCity() : $structure,
            //     'required' => false,
            //     'attr' => ['required' => false],
            //     'placeholder' => 'Aucun(e)'
            // ])
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
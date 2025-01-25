<?php

namespace App\Form\Admin;

use App\Entity\StructurePhoneNumber;
use Misd\PhoneNumberBundle\Form\Type\PhoneNumberType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class StructurePhoneNumberType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ){}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('value', PhoneNumberType::class, [
                'label' => false,
                'widget' => PhoneNumberType::WIDGET_COUNTRY_CHOICE,
                'preferred_country_choices' => ['FR', 'US'],
                'number_options' => [
                    'label' => $this->translator->trans('phone_number')
                ],
                'country_options' => [
                    'label' => $this->translator->trans('country'),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StructurePhoneNumber::class,
        ]);
    }
}
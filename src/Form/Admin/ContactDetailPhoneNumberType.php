<?php

namespace App\Form\Admin;

use App\Entity\ContactDetailPhoneNumber;
use App\Transformer\DefaultValueTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactDetailPhoneNumberType extends AbstractType
{
    private TranslatorInterface $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('phone_number', TelType::class, ['label' => $this->translator->trans('phone_number')])
            ->add('code', NumberType::class, [
                'html5' => true,
                'required' => false,
                'empty_data' => 33,
                'label' => $this->translator->trans('code')
            ])
            ->get('code')
            ->addModelTransformer(new CallbackTransformer(
                fn($code) : int => ($code === null) ? 33 : $code,
                fn($code) : int => ($code === null) ? 33 : $code
            ))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactDetailPhoneNumber::class,
        ]);
    }
}
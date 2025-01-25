<?php

namespace App\Form\Admin;

use App\Entity\Contact;
use App\Entity\Structure;
use App\Entity\StructurePhoneNumber;
use App\Entity\PostProgram;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class PostProgramFromContactType extends AbstractType
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ){}
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('structure', EntityType::class, [
                'class' => Structure::class
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PostProgram::class,
        ]);
    }
}
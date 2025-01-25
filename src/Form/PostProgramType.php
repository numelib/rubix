<?php

namespace App\Form;

use App\Entity\Contact;
use App\Entity\PostProgram;
use App\Entity\Structure;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PostProgramType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('structure', EntityType::class, [
                'class' => Structure::class,
                'choice_label' => 'id',
            ])
            ->add('contact', EntityType::class, [
                'class' => Contact::class,
                'choice_label' => 'id',
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

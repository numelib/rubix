<?php

namespace App\Form\Admin;

use App\Entity\NewsletterType;
use App\Entity\StructureNewsletter;
use App\Service\MailjetAPI;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class StructureNewsletterType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private MailjetAPI $mailjetApi)
    {
    }

    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $emails = $this->mailjetApi->getNewslettersEmails();
        $options = [
            'choices' => $emails,
            'label' => $this->translator->trans('contact_email'),
            'attr' => ['data-ea-widget' => 'ea-autocomplete']
           
        ];

        if(empty($emails)) {
            $options['disabled'] = true;
            $options['help'] = 'La récupération des emails via Mailjet a échoué. Réessayez plus tard.';
        }

        $builder
            ->add('structure_email', ChoiceType::class, $options)
            ->add('newsletter_types', EntityType::class, [
                'label' => $this->translator->trans('newsletter_types'),
                'multiple' => true,
                'expanded' => true,
                'class' => NewsletterType::class,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => StructureNewsletter::class,
        ]);
    }
}
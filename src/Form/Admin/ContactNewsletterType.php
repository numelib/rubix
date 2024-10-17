<?php

namespace App\Form\Admin;

use App\Entity\ContactNewsletter;
use App\Entity\NewsletterType;
use App\Service\MailjetAPI;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\HttpClient\CachingHttpClient;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpKernel\HttpCache\Store;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ContactNewsletterType extends AbstractType
{
    public function __construct(private TranslatorInterface $translator, private MailjetAPI $mailjetApi)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $emails = $this->mailjetApi->getNewslettersEmails();

        $options = [
            'choices' => $emails ?? [],
            'label' => $this->translator->trans('contact_email'),
            'attr' => ['data-ea-widget' => 'ea-autocomplete']
           
        ];

        if(empty($emails)) {
            $options['disabled'] = true;
            $options['help'] = 'La récupération des emails via Mailjet a échoué. Réessayez plus tard.';
        }

        $builder
            ->add('contact_email', ChoiceType::class, $options)
            ->add('newsletter_types', EntityType::class, [
                'class' => NewsletterType::class,
                'multiple' => true,
                'expanded' => true,
                'label' => $this->translator->trans('newsletter_types')
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ContactNewsletter::class,
        ]);
    }
}
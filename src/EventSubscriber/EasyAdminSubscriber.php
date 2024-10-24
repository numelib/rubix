<?php

namespace App\EventSubscriber;

use App\Dto\MailjetContactDto;
use App\Entity\Contact;
use App\Enums\NewsletterType;
use App\Service\MailjetAPI;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\AbstractLifecycleEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityDeletedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class EasyAdminSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MailjetAPI $mailjetAPI,
        private readonly EntityManagerInterface $entityManager,
    ){}

    public function addMailjetContact(AbstractLifecycleEvent $event) : void
    {
        if(!$this->mailjetAPI->areCredentialsDefined() || !$this->mailjetAPI->areContactListsIdsDefined()) return;

        $entity = $event->getEntityInstance();

        if(!($entity instanceof Contact)) return;

        $newsletterEmail = $entity->getNewsletterEmail();
        $newsletterTypes = $entity->getNewsletterTypes();

        $hasNewsletterEmail = ($newsletterEmail !== null);
        $hasNewsletterTypes = ($newsletterTypes->count() > 0);

        if(!$hasNewsletterEmail || !$hasNewsletterTypes) return;

        $isContactRegistered = $this->mailjetAPI->isContactRegistered($newsletterEmail);

        if($isContactRegistered) return;

        foreach($newsletterTypes as $newsletterType)
        {
            $contactListId = NewsletterType::from($newsletterType->__toString())->contactListId();
            $parameters = ['name' => $entity->getLastname(), 'firstname' => $entity->getFirstname()];
            $mailjetContactDto = new MailjetContactDto($newsletterEmail, parameters : $parameters);

            if($contactListId !== null) {
                $this->mailjetAPI->registerContactInList($mailjetContactDto, $contactListId);
            }
        }
    }

    public function updateMailjetContact(AbstractLifecycleEvent $event) : void
    {
        if(!$this->mailjetAPI->areCredentialsDefined() || !$this->mailjetAPI->areContactListsIdsDefined()) return;

        $entity = $event->getEntityInstance();

        if(!($entity instanceof Contact)) return;
        /* Je passe directement par la BDD car l'entityManager me renvoie le nouveau contact modifié et non la version en BDD */
        $oldNewsletterEmail = $this->entityManager->getConnection()->executeQuery('SELECT newsletter_email FROM contact WHERE id = :id', ['id' => $entity->getId()])->fetchOne();

        $newsletterEmail = $entity->getNewsletterEmail();
        $newsletterTypes = $entity->getNewsletterTypes();

        $hasNewsletterEmail = ($newsletterEmail !== null);
        $hasNewsletterTypes = ($newsletterTypes->count() > 0);

        $isContactRegistered = ($oldNewsletterEmail === null) ? false : $this->mailjetAPI->isContactRegistered($oldNewsletterEmail);

        if((!$hasNewsletterEmail || !$hasNewsletterTypes) && $isContactRegistered) {
            $this->mailjetAPI->removeContactByEmail($oldNewsletterEmail);
            return;
        };        

        if($isContactRegistered) {
            $mailjetContactDto = $this->mailjetAPI->getContactByEmail($oldNewsletterEmail);

            // Si l'email de reception de newsletter à changé, le changer sur Mailjet
            $this->mailjetAPI->removeContactById($mailjetContactDto->getId());

            $mailjetContactDto = new MailjetContactDto($newsletterEmail, $mailjetContactDto->getId(), $mailjetContactDto->getName(), $mailjetContactDto->getParameters());
            foreach($newsletterTypes as $newsletterType)
            {
                $contactListId = NewsletterType::from($newsletterType->__toString())->contactListId();
                if($contactListId !== null) {
                    $this->mailjetAPI->registerContactInList($mailjetContactDto, $contactListId);
                }
            }
        } else {
            $this->addMailjetContact($event);
        }
    }

    public function deleteMailjetContact(BeforeEntityDeletedEvent $event) : void
    {
        if(!$this->mailjetAPI->areCredentialsDefined() || !$this->mailjetAPI->areContactListsIdsDefined()) return;

        $entity = $event->getEntityInstance();

        if(!($entity instanceof Contact)) return;

        $newsletterEmail = $entity->getNewsletterEmail();
        $newsletterTypes = $entity->getNewsletterTypes();

        $hasNewsletterEmail = ($newsletterEmail !== null);
        $hasNewsletterTypes = ($newsletterTypes->count() > 0);

        if(!$hasNewsletterEmail || !$hasNewsletterTypes) return;

        $isContactRegistered = $this->mailjetAPI->isContactRegistered($newsletterEmail);

        if(!$isContactRegistered) return;

        $this->mailjetAPI->removeContactByEmail($newsletterEmail);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => 'addMailjetContact',
            BeforeEntityUpdatedEvent::class => 'updateMailjetContact',
            BeforeEntityDeletedEvent::class => 'deleteMailjetContact',
        ];
    }
}

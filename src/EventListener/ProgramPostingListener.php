<?php

namespace App\EventListener;

use App\Entity\ProgramPosting;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;

class ProgramPostingListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    public function postPersist(ProgramPosting $entity, PostPersistEventArgs $args): void
    {
        if(!$entity instanceof ProgramPosting)
        {
            return;
        }

        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($entity);

        $this->updateStructurePosting($args, $changeset);

    }

    public function postUpdate(ProgramPosting $entity, PostUpdateEventArgs $args): void
    {
        if(!$entity instanceof ProgramPosting)
        {
            return;
        }

        $uow = $this->entityManager->getUnitOfWork();
        $uow->computeChangeSets();
        $changeset = $uow->getEntityChangeSet($entity);

        $this->updateStructurePosting($args, $changeset);

    }

    public function updateStructurePosting($args, array $changeset)
    {        
        $entityManager = $args->getObjectManager();

        /** @var \App\Entity\ProgramPosting */
        $programPosting = $args->getObject();

        /** @var \App\Entity\Structure */
        $structure = $programPosting->getStructure();

        /** @var \App\Entity\Contact */
        $contact = $programPosting->getContact();

        /* 
         * - Si un program_posting est rajouté/modifié depuis la structure
         * - Si un contact rajoute la structure a son program_posting
         */
        if($structure) {
            $programPosting->setAddressType('professional');
            $structure->setProgramSent(true);
            $entityManager->persist($structure);
            $entityManager->persist($programPosting);
            $entityManager->flush();
        }

        /* 
         * - Si un contact est ajouté dans un program_posting depuis la structure
         * - Si un program_posting est rajouté depuis un contact
         */
        if($contact) {
            $contact->setProgramSent(true);
            $entityManager->persist($contact);
            $entityManager->flush();
        } 
        
        /* 
         * - Si un contact est retiré d'un program_posting depuis la structure
         * - Si un program_posting avec un contact est supprimé depuis la structure 
         */
        if(!$contact && isset($changeset['contact']) && $changeset['contact'][0]) {
            $changeset['contact'][0]->setProgramSent(false);
            $entityManager->persist($changeset['contact'][0]);
            $entityManager->flush();
        }
    }
}

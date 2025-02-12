<?php

namespace App\EventListener;

use App\Entity\Structure;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostRemoveEventArgs;

class StructureListener
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    public function postRemove(Structure $entity, PostRemoveEventArgs $args): void
    {
        if(!$entity instanceof Structure)
        {
            return;
        }

        $this->updateStructurePosting($args);
    }

    public function updateStructurePosting($args)
    {        
        $entityManager = $args->getObjectManager();

        /** @var \App\Entity\Structure */
        $structure = $args->getObject();

        foreach($structure?->getProgramPostings() as $programPosting) 
        {
            $programPosting->getContact()?->setProgramSent(false);
            $this->entityManager->remove($programPosting);
            $this->entityManager->flush();
        }
    }
}

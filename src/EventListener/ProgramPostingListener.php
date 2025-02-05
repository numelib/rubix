<?php

namespace App\EventListener;

use App\Entity\ProgramPosting;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Annotation\Route;

class ProgramPostingListener
{
    public function postPersist(ProgramPosting $entity, PostPersistEventArgs $args): void
    {
        if(!$entity instanceof ProgramPosting)
        {
            return;
        }

        $this->updateStructurePosting($args);

    }

    public function postUpdate(ProgramPosting $entity, PostUpdateEventArgs $args): void
    {
        if(!$entity instanceof ProgramPosting)
        {
            return;
        }

        $this->updateStructurePosting($args);

    }

    /*public function preRemove(ProgramPosting $entity, PreRemoveEventArgs $args): void
    {

        if(!$entity instanceof ProgramPosting)
        {
            return;
        }
        
        //depuis la fiche contact : au cas où la structure serait retirée du contact
        if(!$entity->getStructure())
        {
            dd($entity);
        }

    }*/

    public function updateStructurePosting($args)
    {
        $entityManager = $args->getObjectManager();
        $entity = $args->getObject();
        $structure = $entity->getStructure();
        //$contact = $entity->getContact();

        if($entity->getStructure()){
            $structure->setProgramSent(true);
            $entityManager->persist($structure);
            $entityManager->flush();
        }else{
            //on passe le contact en personal
            $entity->setAddressType('personal');
            $entityManager->persist($entity);
            $entityManager->flush();
        }
        
    }

}

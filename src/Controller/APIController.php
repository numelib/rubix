<?php

namespace App\Controller;

use App\Entity\Contact;
use App\Entity\Structure;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;

class APIController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ){}

    #[Route('/api/festival-program/contact', name: 'app_api_contact', methods : ['GET'])]
    public function contact(#[MapQueryParameter()] int $contactId): JsonResponse
    {
        $contactRepository = $this->entityManager->getRepository(Contact::class);
        $contact = $contactRepository->find($contactId);

        if($contact instanceof Contact) {
            $structure = $contact->getStructureSendingFestivalProgram();
            $isStructureSendingFestivalProgramToContact = ($structure !== null && $structure->isReceivingFestivalProgram() === true);

            return $this->json(['structure' => ($isStructureSendingFestivalProgramToContact) ? $structure->getName() : null]);
        }

        throw $this->createNotFoundException('The contact with $id : ' . $contactId . ' does not exists');
    }

    #[Route('/api/festival-program/structure', name: 'app_api_structure', methods : ['GET'])]
    public function structure(#[MapQueryParameter] int $contactId): JsonResponse
    {
        $contactRepository = $this->entityManager->getRepository(Contact::class);
        $contact = $contactRepository->find($contactId);

        if($contact instanceof Contact) {
            $isContactReceivingFestivalProgram = $contact->isReceivingFestivalProgram();

            return $this->json(['contact' => ($isContactReceivingFestivalProgram === true) ? $contact?->getFirstname() . ' ' . $contact?->getLastname() : null]);
        }

        throw $this->createNotFoundException('The contact with $id : ' . $contactId . ' does not exists');
    }
}

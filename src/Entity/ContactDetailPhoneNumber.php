<?php

namespace App\Entity;

use App\Repository\ContactDetailPhoneNumberRepository;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

#[ORM\Entity(repositoryClass: ContactDetailPhoneNumberRepository::class)]
class ContactDetailPhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactDetailPhoneNumbers')]
    private ?ContactDetail $contact_detail = null;

    #[ORM\Column(type: 'phone_number', nullable: true)]
    private ?PhoneNumber $phone_number = null;

    public function __toString()
    {
        return $this->phone_number;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContactDetail(): ?ContactDetail
    {
        return $this->contact_detail;
    }

    public function setContactDetail(?ContactDetail $contact_detail): static
    {
        $this->contact_detail = $contact_detail;

        return $this;
    }

    public function getPhoneNumber(): ?PhoneNumber
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?PhoneNumber $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }
}

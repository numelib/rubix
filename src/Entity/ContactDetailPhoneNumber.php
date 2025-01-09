<?php

namespace App\Entity;

use App\Repository\ContactDetailPhoneNumberRepository;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

#[ORM\Entity(repositoryClass: ContactDetailPhoneNumberRepository::class)]
class ContactDetailPhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactDetailPhoneNumbers')]
    private ?ContactDetail $contact_detail = null;

    #[AssertPhoneNumber()]
    #[ORM\Column(type: 'phone_number', nullable: true)]
    private ?PhoneNumber $value = null;

    public function __toString()
    {
        return '+' . $this->value->getCountryCode() .  $this->value->getNationalNumber();
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

    public function getValue(): ?PhoneNumber
    {
        return $this->value;
    }

    public function setValue(?PhoneNumber $value): static
    {
        $this->value = $value;

        return $this;
    }
}

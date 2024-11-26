<?php

namespace App\Entity;

use App\Repository\ContactDetailPhoneNumberRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactDetailPhoneNumberRepository::class)]
class ContactDetailPhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'contactDetailPhoneNumbers')]
    private ?ContactDetail $contact_detail = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $phone_number = null;

    #[ORM\Column(nullable: true)]
    private ?int $code = 33;

    public function __toString()
    {
        return '+' . $this->code . ' ' . $this->phone_number;
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

    public function getPhoneNumber(): ?string
    {
        return $this->phone_number;
    }

    public function setPhoneNumber(?string $phone_number): static
    {
        $this->phone_number = $phone_number;

        return $this;
    }

    public function getCode(): int
    {
        return $this->code ?? 33;
    }

    public function setCode(?int $code): static
    {
        $this->code = $code ?? 33;

        return $this;
    }
}

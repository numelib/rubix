<?php

namespace App\Entity;

use App\Repository\StructurePhoneNumberRepository;
use Doctrine\ORM\Mapping as ORM;
use libphonenumber\PhoneNumber;

#[ORM\Entity(repositoryClass: StructurePhoneNumberRepository::class)]
class StructurePhoneNumber
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'phone_number', nullable: true)]
    private ?PhoneNumber $value = null;

    #[ORM\ManyToOne(inversedBy: 'phone_numbers')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Structure $structure = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getStructure(): ?Structure
    {
        return $this->structure;
    }

    public function setStructure(?Structure $structure): static
    {
        $this->structure = $structure;

        return $this;
    }
}

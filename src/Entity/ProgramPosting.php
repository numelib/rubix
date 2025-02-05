<?php

namespace App\Entity;

use App\Repository\ProgramPostingRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProgramPostingRepository::class)]
class ProgramPosting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Contact::class, inversedBy: 'programPosting')]
    private ?Contact $contact = null;

    #[ORM\ManyToOne(targetEntity: Structure::class, inversedBy: 'programPostings', cascade: ['remove'])]
    private ?Structure $structure = null;

    #[ORM\Column(type: 'string')]
    private string $addressType; // 'personal' or 'professional'

    public function getId(): ?int
    {
        return $this->id;
    }

    public function __toString()
    {
        return $this->getContact();
    }

    public function getAddressType(): ?string
    {
        return $this->addressType;
    }

    public function setAddressType(string $addressType): static
    {
        $this->addressType = $addressType;

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

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): static
    {
        $this->contact = $contact;

        return $this;
    }

}

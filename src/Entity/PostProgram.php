<?php

namespace App\Entity;

use App\Repository\PostProgramRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PostProgramRepository::class)]
class PostProgram
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Structure::class, inversedBy: 'postProgram')]
    private $structure = null;

    #[ORM\OneToOne(targetEntity: Contact::class, inversedBy: 'postProgram')]
    private $contact = null;

    #[ORM\Column]
    private ?bool $is_sent = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function __toString()
    {
        return $this->getAddress();
    }

    public function getAddress()
    {
        $address = 'Aucun(e)';
        $structure = $this->getStructure();
        $contact = $this->getContact();

        if($contact !== null) {
            $address = $contact->getFormattedAddress(oneline : true);
        }

        if($structure !== null) {
            $address = $structure->getFormattedAddress(oneline : true);
        }

        if($contact !== null && $structure !== null) {
            $address = $contact . ' - ' . $structure->getFormattedAddress(oneline : true);
        }

        return $address;
    }

    public function getSimpleAddress()
    {
        $address = 'Aucun(e)';
        $structure = $this->getStructure();
        $contact = $this->getContact();

        if($contact !== null || $contact !== null && $structure !== null) {
            $address = $contact->getFormattedAddress(oneline : true);
        }

        if($structure !== null) {
            $address = $structure->getFormattedAddress(oneline : true);
        }

        return $address;
    }

    public function getIsSent(): ?bool
    {
        return $this->is_sent;
    }

    public function setIsSent(bool $is_sent): static
    {
        $this->is_sent = $is_sent;

        return $this;
    }
}

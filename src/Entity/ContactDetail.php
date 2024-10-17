<?php

namespace App\Entity;

use App\Repository\ContactDetailRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContactDetailRepository::class)]
class ContactDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable : true)]
    private ?string $structure_function = null;

    #[ORM\ManyToOne(inversedBy: 'contact_details', cascade : ['persist'])]
    private ?Structure $structure = null;

    #[ORM\ManyToOne(inversedBy: 'contact_details')]
    private ?Contact $contact = null;

    /**
     * @var Collection<int, ContactDetailPhoneNumber>
     */
    #[ORM\OneToMany(targetEntity: ContactDetailPhoneNumber::class, mappedBy: 'contact_detail', cascade : ['persist', 'remove'])]
    private Collection $contactDetailPhoneNumbers;

    public function __construct()
    {
        $this->contactDetailPhoneNumbers = new ArrayCollection();
    }

    public function __toString()
    {
        $string = $this->email;
        if($this->email !== null && $this->structure_function !== null) $string .= ', ';
        $string .= $this->structure_function;

        return $string;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getStructureFunction(): ?string
    {
        return $this->structure_function;
    }

    public function setStructureFunction(string $structure_function): static
    {
        $this->structure_function = $structure_function;

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

    /**
     * @return Collection<int, ContactDetailPhoneNumber>
     */
    public function getContactDetailPhoneNumbers(): Collection
    {
        return $this->contactDetailPhoneNumbers;
    }

    public function addContactDetailPhoneNumber(ContactDetailPhoneNumber $contactDetailPhoneNumber): static
    {
        if (!$this->contactDetailPhoneNumbers->contains($contactDetailPhoneNumber)) {
            $this->contactDetailPhoneNumbers->add($contactDetailPhoneNumber);
            $contactDetailPhoneNumber->setContactDetail($this);
        }

        return $this;
    }

    public function removeContactDetailPhoneNumber(ContactDetailPhoneNumber $contactDetailPhoneNumber): static
    {
        if ($this->contactDetailPhoneNumbers->removeElement($contactDetailPhoneNumber)) {
            // set the owning side to null (unless already changed)
            if ($contactDetailPhoneNumber->getContactDetail() === $this) {
                $contactDetailPhoneNumber->setContactDetail(null);
            }
        }

        return $this;
    }
}

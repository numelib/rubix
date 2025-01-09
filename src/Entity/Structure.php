<?php

namespace App\Entity;

use App\Repository\StructureRepository;
use libphonenumber\PhoneNumber;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;

#[ORM\Entity(repositoryClass: StructureRepository::class)]
class Structure
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column]
    private ?bool $is_festival_organizer = null;

    #[ORM\Column(length: 500, type: "text", nullable: true)]
    private ?string $structure_notes = null;

    #[ORM\Column]
    private ?bool $is_company_programmed_in_festival = null;

    #[ORM\Column]
    private ?bool $is_workshop_partner = null;

    #[ORM\Column(length: 500, type: "text", nullable: true)]
    private ?string $organization_notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_street = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_adition = null;

    #[ORM\Column(nullable: true)]
    private ?string $address_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_country = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_city = null;

    /**
     * @var Collection<int, ContactDetail>
     */
    #[ORM\OneToMany(targetEntity: ContactDetail::class, mappedBy: 'structure', cascade : ['remove'])]
    private Collection $contact_details;

    /**
     * @var Collection<int, Parc>
     */
    #[ORM\ManyToMany(targetEntity: Parc::class, inversedBy: 'structures')]
    private Collection $near_parcs;

    /**
     * @var Collection<int, StructureTypeSpecialization>
     */
    #[ORM\ManyToMany(targetEntity: StructureTypeSpecialization::class, inversedBy: 'structures', cascade : ['persist'])]
    private Collection $structure_type_specializations;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $festival_informations = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $festival_program_receipt_email = null;

    #[ORM\Column(length: 500, type: "text", nullable: true)]
    private ?string $communication_notes = null;

    #[ORM\Column]
    private ?bool $is_festival_partner = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updated_at = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $created_at = null;

    #[ORM\ManyToOne()]
    private ?StructureType $structureType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $newsletter_email = null;

    /**
     * @var Collection<int, NewsletterType>
     */
    #[ORM\ManyToMany(targetEntity: NewsletterType::class)]
    private Collection $newsletter_types;

    /**
     * @var Collection<int, Discipline>
     */
    #[ORM\ManyToMany(targetEntity: Discipline::class)]
    private Collection $disciplines;

    #[ORM\Column]
    private ?bool $is_receiving_festival_program = null;

    /**
     * @var Collection<int, Contact>
     */
    #[ORM\OneToMany(targetEntity: Contact::class, mappedBy: 'structure_sending_festival_program')]
    private Collection $contacts_receiving_festival_program;

    /**
     * @var Collection<int, StructurePhoneNumber>
     */
    #[ORM\OneToMany(targetEntity: StructurePhoneNumber::class, mappedBy: 'structure', orphanRemoval: true, cascade: ['persist'])]
    private Collection $phone_numbers;

    public function __construct()
    {
        $this->contact_details = new ArrayCollection();
        $this->near_parcs = new ArrayCollection();
        $this->structure_type_specializations = new ArrayCollection();
        $this->newsletter_types = new ArrayCollection();
        $this->disciplines = new ArrayCollection();
        $this->contacts_receiving_festival_program = new ArrayCollection();
        $this->phone_numbers = new ArrayCollection();
    }

    public function __toString()
    {
        return ($this->name) ? $this->name : '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
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

    public function getWebsite(): ?string
    {
        return $this->website;
    }

    public function setWebsite(?string $website): static
    {
        $this->website = $website;

        return $this;
    }

    public function isFestivalOrganizer(): ?bool
    {
        return $this->is_festival_organizer;
    }

    public function setIsFestivalOrganizer(bool $is_festival_organizer): static
    {
        $this->is_festival_organizer = $is_festival_organizer;

        return $this;
    }

    public function getStructureNotes(): ?string
    {
        return $this->structure_notes;
    }

    public function setStructureNotes(?string $structure_notes): static
    {
        $this->structure_notes = $structure_notes;

        return $this;
    }

    public function isCompanyProgrammedInFestival(): ?bool
    {
        return $this->is_company_programmed_in_festival;
    }

    public function setIsCompanyProgrammedInFestival(bool $is_company_programmed_in_festival): static
    {
        $this->is_company_programmed_in_festival = $is_company_programmed_in_festival;

        return $this;
    }

    public function isWorkshopPartner(): ?bool
    {
        return $this->is_workshop_partner;
    }

    public function setIsWorkshopPartner(bool $is_workshop_partner): static
    {
        $this->is_workshop_partner = $is_workshop_partner;

        return $this;
    }

    public function getOrganizationNotes(): ?string
    {
        return $this->organization_notes;
    }

    public function setOrganizationNotes(?string $organization_notes): static
    {
        $this->organization_notes = $organization_notes;

        return $this;
    }

    public function getAddressStreet(): ?string
    {
        return $this->address_street;
    }

    public function setAddressStreet(?string $address_street): static
    {
        $this->address_street = $address_street;

        return $this;
    }

    public function getAddressAdition(): ?string
    {
        return $this->address_adition;
    }

    public function setAddressAdition(?string $address_adition): static
    {
        $this->address_adition = $address_adition;

        return $this;
    }

    public function getAddressCode(): ?string
    {
        return $this->address_code;
    }

    public function setAddressCode(?string $address_code): static
    {
        $this->address_code = $address_code;

        return $this;
    }

    public function getAddressCountry(): ?string
    {
        return $this->address_country;
    }

    public function setAddressCountry(?string $address_country): static
    {
        $this->address_country = $address_country;

        return $this;
    }

    public function getAddressCity(): ?string
    {
        return $this->address_city;
    }

    public function setAddressCity(?string $address_city): static
    {
        $this->address_city = $address_city;

        return $this;
    }

    /**
     * @return Collection<int, ContactDetail>
     */
    public function getContactDetails(): Collection
    {
        return $this->contact_details;
    }

    public function addContactDetail(ContactDetail $contactDetail): static
    {
        if (!$this->contact_details->contains($contactDetail)) {
            $this->contact_details->add($contactDetail);
            $contactDetail->setStructure($this);
        }

        return $this;
    }

    public function removeContactDetail(ContactDetail $contactDetail): static
    {
        if ($this->contact_details->removeElement($contactDetail)) {
            // set the owning side to null (unless already changed)
            if ($contactDetail->getStructure() === $this) {
                $contactDetail->setStructure(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Parc>
     */
    public function getNearParcs(): Collection
    {
        return $this->near_parcs;
    }

    public function addNearParc(Parc $nearParc): static
    {
        if (!$this->near_parcs->contains($nearParc)) {
            $this->near_parcs->add($nearParc);
        }

        return $this;
    }

    public function removeNearParc(Parc $nearParc): static
    {
        $this->near_parcs->removeElement($nearParc);

        return $this;
    }

    /**
     * @return Collection<int, StructureTypeSpecialization>
     */
    public function getStructureTypeSpecializations(): Collection
    {
        return $this->structure_type_specializations;
    }

    public function addStructureTypeSpecialization(StructureTypeSpecialization $structureTypeSpecilization): static
    {
        if (!$this->structure_type_specializations->contains($structureTypeSpecilization)) {
            $this->structure_type_specializations->add($structureTypeSpecilization);
        }

        return $this;
    }

    public function removeStructureTypeSpecialization(StructureTypeSpecialization $structureTypeSpecilization): static
    {
        $this->structure_type_specializations->removeElement($structureTypeSpecilization);

        return $this;
    }

    public function getFestivalInformations(): ?string
    {
        return $this->festival_informations;
    }

    public function setFestivalInformations(?string $festival_informations): static
    {
        $this->festival_informations = $festival_informations;

        return $this;
    }

    public function getFestivalProgramReceiptEmail(): ?string
    {
        return $this->festival_program_receipt_email;
    }

    public function setFestivalProgramReceiptEmail(?string $festival_program_receipt_email): static
    {
        $this->festival_program_receipt_email = $festival_program_receipt_email;

        return $this;
    }

    public function getCommunicationNotes(): ?string
    {
        return $this->communication_notes;
    }

    public function setCommunicationNotes(?string $communication_notes): static
    {
        $this->communication_notes = $communication_notes;

        return $this;
    }

    public function isFestivalPartner(): ?bool
    {
        return $this->is_festival_partner;
    }

    public function setIsFestivalPartner(bool $is_festival_partner): static
    {
        $this->is_festival_partner = $is_festival_partner;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(\DateTimeImmutable $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->created_at = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->created_at;
    }

    public function setCreatedAt(\DateTimeImmutable $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    public function isReceivingFestivalProgram(): ?bool
    {
        return $this->is_receiving_festival_program;
    }

    public function setIsReceivingFestivalProgram(bool $is_receiving_festival_program): static
    {
        $this->is_receiving_festival_program = $is_receiving_festival_program;

        return $this;
    }

    public function setFestivalPartner(bool $is_festival_partner): static
    {
        $this->is_festival_partner = $is_festival_partner;

        return $this;
    }

    public function getStructureType(): ?StructureType
    {
        return $this->structureType;
    }

    public function setStructureType(?StructureType $structureType): static
    {
        $this->structureType = $structureType;

        return $this;
    }

    public function getFormattedAddress(bool $oneline = false): string
    {
        $separator = ($oneline) ? ' ' : '<br>';

        $addressLines = [
            $this->getAddressAdition(),
            $this->getAddressStreet(),
            $this->getAddressCode() . ' ' . $this->getAddressCity(),
            (is_string($this->getAddressCountry()) && Countries::exists($this->getAddressCountry())) ? Countries::getName($this->getAddressCountry()) : $this->getAddressCountry(),
        ];

        $addressLines = array_filter($addressLines, fn(?string $line) => !empty(str_replace(' ', '', $line)));

        return implode($separator, $addressLines);
    }

    public function getContacts()
    {
        $contacts = array_unique($this->getContactDetails()
            ->map(fn(ContactDetail $contactDetail) => $contactDetail->getContact())
            ->filter(fn(?Contact $contact) => !is_null($contact))
            ->toArray(), SORT_REGULAR);

        return new ArrayCollection($contacts);
    }

    public function getNewsletterEmail(): ?string
    {
        return $this->newsletter_email;
    }

    public function setNewsletterEmail(?string $newsletter_email): static
    {
        $this->newsletter_email = $newsletter_email;

        return $this;
    }

    /**
     * @return Collection<int, NewsletterType>
     */
    public function getNewsletterTypes(): Collection
    {
        return $this->newsletter_types;
    }

    public function addNewsletterType(NewsletterType $newsletterType): static
    {
        if (!$this->newsletter_types->contains($newsletterType)) {
            $this->newsletter_types->add($newsletterType);
        }

        return $this;
    }

    public function removeNewsletterType(NewsletterType $newsletterType): static
    {
        $this->newsletter_types->removeElement($newsletterType);

        return $this;
    }

    /**
     * @return Collection<int, Discipline>
     */
    public function getDisciplines(): Collection
    {
        return $this->disciplines;
    }

    public function addDiscipline(Discipline $discipline): static
    {
        if (!$this->disciplines->contains($discipline)) {
            $this->disciplines->add($discipline);
        }

        return $this;
    }

    public function removeDiscipline(Discipline $discipline): static
    {
        $this->disciplines->removeElement($discipline);

        return $this;
    }

    /**
     * @return Collection<int, Contact>
     */
    public function getContactsReceivingFestivalProgram(): Collection
    {
        return $this->contacts_receiving_festival_program;
    }

    public function addContactsReceivingFestivalProgram(Contact $contactsReceivingFestivalProgram): static
    {
        if (!$this->contacts_receiving_festival_program->contains($contactsReceivingFestivalProgram)) {
            $this->contacts_receiving_festival_program->add($contactsReceivingFestivalProgram);
            $contactsReceivingFestivalProgram->setStructureSendingFestivalProgram($this);
        }

        return $this;
    }

    public function removeContactsReceivingFestivalProgram(Contact $contactsReceivingFestivalProgram): static
    {
        if ($this->contacts_receiving_festival_program->removeElement($contactsReceivingFestivalProgram)) {
            // set the owning side to null (unless already changed)
            if ($contactsReceivingFestivalProgram->getStructureSendingFestivalProgram() === $this) {
                $contactsReceivingFestivalProgram->setStructureSendingFestivalProgram(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, StructurePhoneNumber>
     */
    public function getPhoneNumbers(): Collection
    {
        return $this->phone_numbers;
    }

    public function addPhoneNumber(StructurePhoneNumber $phoneNumber): static
    {
        if (!$this->phone_numbers->contains($phoneNumber)) {
            $this->phone_numbers->add($phoneNumber);
            $phoneNumber->setStructure($this);
        }

        return $this;
    }

    public function removePhoneNumber(StructurePhoneNumber $phoneNumber): static
    {
        if ($this->phone_numbers->removeElement($phoneNumber)) {
            // set the owning side to null (unless already changed)
            if ($phoneNumber->getStructure() === $this) {
                $phoneNumber->setStructure(null);
            }
        }

        return $this;
    }
}

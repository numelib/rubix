<?php

namespace App\Entity;

use App\Repository\ContactRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use libphonenumber\PhoneNumber;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use Misd\PhoneNumberBundle\Validator\Constraints\PhoneNumber as AssertPhoneNumber;

use App\Validator as ProgramPostingAssert;

#[ORM\Entity(repositoryClass: ContactRepository::class)]
#[ORM\HasLifecycleCallbacks]
#[ProgramPostingAssert\ContactProgramPosting]
class Contact
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $civility = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $website = null;

    #[ORM\Column]
    private ?bool $is_workshop_artist = null;

    #[ORM\Column]
    private ?bool $is_formation_speaker = null;

    #[ORM\Column(length: 500, type: "text", nullable: true)]
    private ?string $personal_notes = null;

    #[ORM\Column(length: 500, type: "text", nullable: true)]
    private ?string $professional_notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $personal_email = null;

    #[AssertPhoneNumber()]
    #[ORM\Column(type: 'phone_number', nullable: true)]
    private ?PhoneNumber $personal_phone_number = null;

    #[ORM\Column(length: 500, type: "text", nullable: true)]
    private ?string $communication_notes = null;

    #[ORM\Column]
    private ?bool $is_festival_participant = null;

    #[ORM\Column]
    private ?bool $is_board_of_directors_member = null;

    #[ORM\Column]
    private ?bool $is_organization_participant = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $organization_notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_street = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_adition = null;

    #[ORM\Column(nullable: true)]
    private ?int $address_code = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_city = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $address_country = null;

    #[ORM\Column(type: 'datetime', nullable: 'true')]
    private $created_at = null;

    #[ORM\Column(type: 'datetime', nullable: 'true')]
    private $updated_at = null;

    /**
     * @var Collection<int, Option>
     */
    #[ORM\JoinTable(name: 'contact_profile_types')]
    #[ORM\ManyToMany(targetEntity: ProfileType::class, cascade: ['persist'])]
    private Collection $profile_types;

    /**
     * @var Collection<int, Option>
     */
    #[ORM\JoinTable(name: 'contact_disciplines')]
    #[ORM\ManyToMany(targetEntity: Discipline::class, cascade: ['persist'])]
    private Collection $disciplines;

    /**
     * @var Collection<int, FormationParticipantType>
     */
    #[ORM\ManyToMany(targetEntity: FormationParticipantType::class, mappedBy: 'contact', cascade: ['persist'])]
    private Collection $formationParticipantTypes;

    /**
     * @var Collection<int, ContactDetail>
     */
    #[ORM\OneToMany(targetEntity: ContactDetail::class, mappedBy: 'contact', cascade: ['persist', 'remove'])]
    private Collection $contact_details;

    #[ORM\OneToOne(targetEntity: ProgramPosting::class, mappedBy: 'contact', cascade: ['persist', 'remove'])]
    private ?ProgramPosting $programPosting = null;

    #[ORM\Column(type: 'boolean')]
    private bool $programSent = false; // Default value

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $newsletter_email = null;

    /**
     * @var Collection<int, NewsletterType>
     */
    #[ORM\ManyToMany(targetEntity: NewsletterType::class, inversedBy: 'contacts')]
    private Collection $newsletter_types;

    public function __construct()
    {
        $this->formationParticipantTypes = new ArrayCollection();
        $this->contact_details = new ArrayCollection();
        $this->profile_types = new ArrayCollection();
        $this->disciplines = new ArrayCollection();
        $this->newsletter_types = new ArrayCollection();
    }

    public function __toString()
    {
        $contact_details = $this->contact_details->toArray();
        $contact_functions = '';
        if(count($contact_details) > 0) {
            $contact_functions .= '(';
            $last_index = array_key_last($contact_details);
            foreach($contact_details as $index => $contact_detail)
            {
                $contact_functions .= $contact_detail->getStructureFunction();
                if ($index !== $last_index) $contact_functions .= ' / ';
            }
            $contact_functions .= ')';

            return $this->firstname . ' ' . $this->lastname . ' ' .  $contact_functions;
        } else {
            return $this->firstname . ' ' . $this->lastname . ' (fonction inconnue)';
        }

        return '';
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getCivility(): ?string
    {
        return $this->civility;
    }

    public function setCivility(string $civility): static
    {
        $this->civility = $civility;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(string $lastname): static
    {
        $this->lastname = $lastname;

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

    public function isWorkshopArtist(): ?bool
    {
        return $this->is_workshop_artist;
    }

    public function setIsWorkshopArtist(bool $is_workshop_artist): static
    {
        $this->is_workshop_artist = $is_workshop_artist;

        return $this;
    }

    public function isFormationSpeaker(): ?bool
    {
        return $this->is_formation_speaker;
    }

    public function setIsFormationSpeaker(bool $is_formation_speaker): static
    {
        $this->is_formation_speaker = $is_formation_speaker;

        return $this;
    }

    public function getpersonalNotes(): ?string
    {
        return $this->personal_notes;
    }

    public function setpersonalNotes(?string $personal_notes): static
    {
        $this->personal_notes = $personal_notes;

        return $this;
    }

    public function getProfessionalNotes(): ?string
    {
        return $this->professional_notes;
    }

    public function setProfessionalNotes(?string $professional_notes): static
    {
        $this->professional_notes = $professional_notes;

        return $this;
    }

    public function getpersonalEmail(): ?string
    {
        return $this->personal_email;
    }

    public function setpersonalEmail(?string $personal_email): static
    {
        $this->personal_email = $personal_email;

        return $this;
    }

    public function getpersonalPhoneNumber(): ?PhoneNumber
    {
        return $this->personal_phone_number;
    }

    public function setpersonalPhoneNumber(?PhoneNumber $personal_phone_number): static
    {
        $this->personal_phone_number = $personal_phone_number;

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

    public function isFestivalParticipant(): ?bool
    {
        return $this->is_festival_participant;
    }

    public function setIsFestivalParticipant(bool $is_festival_participant): static
    {
        $this->is_festival_participant = $is_festival_participant;

        return $this;
    }

    public function isBoardOfDirectorsMember(): ?bool
    {
        return $this->is_board_of_directors_member;
    }

    public function setIsBoardOfDirectorsMember(bool $is_board_of_directors_member): static
    {
        $this->is_board_of_directors_member = $is_board_of_directors_member;

        return $this;
    }

    public function isOrganizationParticipant(): ?bool
    {
        return $this->is_organization_participant;
    }

    public function setIsOrganizationParticipant(bool $is_organization_participant): static
    {
        $this->is_organization_participant = $is_organization_participant;

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

    public function getAddressCode(): ?int
    {
        return $this->address_code;
    }

    public function setAddressCode(?int $address_code): static
    {
        $this->address_code = $address_code;

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

    public function getAddressCountry(): ?string
    {
        return $this->address_country;
    }

    public function setAddressCountry(?string $address_country): static
    {
        $this->address_country = $address_country;

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


    /**
     * @return Collection<int, FormationParticipantType>
     */
    public function getFormationParticipantTypes(): Collection
    {
        return $this->formationParticipantTypes;
    }

    public function addFormationParticipantType(FormationParticipantType $formationParticipantType): static
    {
        if (!$this->formationParticipantTypes->contains($formationParticipantType)) {
            $this->formationParticipantTypes->add($formationParticipantType);
            $formationParticipantType->addContact($this);
        }

        return $this;
    }

    public function removeFormationParticipantType(FormationParticipantType $formationParticipantType): static
    {
        if ($this->formationParticipantTypes->removeElement($formationParticipantType)) {
            $formationParticipantType->removeContact($this);
        }

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
            $contactDetail->setContact($this);
        }

        return $this;
    }

    public function removeContactDetail(ContactDetail $contactDetail): static
    {
        if ($this->contact_details->removeElement($contactDetail)) {
            // set the owning side to null (unless already changed)
            if ($contactDetail->getContact() === $this) {
                $contactDetail->setContact(null);
            }
        }

        return $this;
    }

    public function getContactDetailsString() : string
    {
        $contact_details = $this->contact_details->toArray();
        return implode(', ', $contact_details);
    }

    public function setWorkshopArtist(bool $is_workshop_artist): static
    {
        $this->is_workshop_artist = $is_workshop_artist;

        return $this;
    }

    public function setFormationSpeaker(bool $is_formation_speaker): static
    {
        $this->is_formation_speaker = $is_formation_speaker;

        return $this;
    }

    public function setFestivalParticipant(bool $is_festival_participant): static
    {
        $this->is_festival_participant = $is_festival_participant;

        return $this;
    }

    public function setBoardOfDirectorsMember(bool $is_board_of_directors_member): static
    {
        $this->is_board_of_directors_member = $is_board_of_directors_member;

        return $this;
    }

    public function setOrganizationParticipant(bool $is_organization_participant): static
    {
        $this->is_organization_participant = $is_organization_participant;

        return $this;
    }

    /**
     * @return Collection<int, ProfileType>
     */
    public function getProfileTypes(): Collection
    {
        return $this->profile_types;
    }

    public function addProfileType(ProfileType $profileType): static
    {
        if (!$this->profile_types->contains($profileType)) {
            $this->profile_types->add($profileType);
        }

        return $this;
    }

    public function removeProfileType(ProfileType $profileType): static
    {
        $this->profile_types->removeElement($profileType);

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

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->created_at = new \DateTimeImmutable();
        $this->setUpdatedAtValue();
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->created_at;
    }

    public function setCreatedAt(?\DateTimeInterface $created_at): static
    {
        $this->created_at = $created_at;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updated_at;
    }

    public function setUpdatedAt(?\DateTimeInterface $updated_at): static
    {
        $this->updated_at = $updated_at;

        return $this;
    }

    public function getStructuresFunctions() : ArrayCollection
    {
        $structuresFunctions = array_unique($this->getContactDetails()
            ->map(fn(ContactDetail $contactDetail) => $contactDetail->getStructureFunction())
            ->filter(fn(?string $structureFunction) => !is_null($structureFunction))
            ->toArray(), SORT_REGULAR);

        return new ArrayCollection($structuresFunctions);
    }

    public function getFormattedStructures()  : ArrayCollection
    {
        $structures = array_unique($this->getContactDetails()
            ->map(fn(ContactDetail $contactDetail) => $contactDetail->getStructure())
            ->filter(fn(?Structure $structure) => !is_null($structure))
            ->toArray(), SORT_REGULAR);

        return new ArrayCollection($structures);
    }

    public function getStructures()
    {
        $structures = [];
        foreach($this->getContactDetails() as $cd)
        {
            if($cd->getStructure()){
                $structures[] = $cd->getStructure();
            }
        }

        return $structures;
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

    public function validate(ExecutionContextInterface $context, mixed $payload): void
    {
        if($this->getNewsletterEmail() !== null && $this->getNewsletterTypes()->count() === 0) {
            $context->buildViolation('Un ou plusieurs types de newsletters doivent être définis si une newsletter est envoyée.')
                ->atPath('newsletter_types')
                ->addViolation();
        }

        if($this->getNewsletterEmail() === null && $this->getNewsletterTypes()->count() > 0) {
            $context->buildViolation('Un email d\'envoi de newsletter doit être défini si une newsletter est envoyée.')
                ->atPath('newsletter_email')
                ->addViolation();
        }
    }

    // Getter and Setter for programSent
    public function getProgramSent(): bool
    {
        return $this->programSent;
    }

    public function setProgramSent(bool $programSent): self
    {
        $this->programSent = $programSent;
        return $this;
    }

    public function isProgramSent(): ?bool
    {
        return $this->programSent;
    }

    public function getProgramPosting(): ?ProgramPosting
    {
        return $this->programPosting;
    }

    public function setProgramPosting(?ProgramPosting $programPosting): static
    {
        // unset the owning side of the relation if necessary
        if ($programPosting === null && $this->programPosting !== null) {
            $this->programPosting->setContact(null);
        }

        // set the owning side of the relation if necessary
        if ($programPosting !== null && $programPosting->getContact() !== $this) {
            $programPosting->setContact($this);
        }

        $this->programPosting = $programPosting;

        return $this;
    }

    public function getProgramPostingAddress(): ?string
    {
        $address = null;

        if($this->getProgramSent() && $this->getProgramPosting()){
            if($this->getProgramPosting()->getAddressType() === 'personal'){
                $address = str_replace('<br>', PHP_EOL, $this->getFormattedAddress());
            }else{
                $address = PHP_EOL.str_replace('<br>', PHP_EOL, $this->getProgramPosting()->getStructure()->__toString().'<br>'.$this->getProgramPosting()->getStructure()->getFormattedAddress());
            }
        }

        return $address;
    }

}

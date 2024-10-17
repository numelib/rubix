<?php

namespace App\Entity;

use App\Repository\StructureTypeRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StructureTypeRepository::class)]
class StructureType
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    /**
     * @var Collection<int, StructureTypeSpecialization>
     */
    #[ORM\OneToMany(targetEntity: StructureTypeSpecialization::class, mappedBy: 'structure_type')]
    private Collection $structureTypeSpecializations;

    public function __construct()
    {
        $this->structureTypeSpecializations = new ArrayCollection();
    }

    public function __toString()
    {
        return $this->name;
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

    /**
     * @return Collection<int, StructureTypeSpecialization>
     */
    public function getStructureTypeSpecializations(): Collection
    {
        return $this->structureTypeSpecializations;
    }

    public function addStructureTypeSpecialization(StructureTypeSpecialization $structureTypeSpecialization): static
    {
        if (!$this->structureTypeSpecializations->contains($structureTypeSpecialization)) {
            $this->structureTypeSpecializations->add($structureTypeSpecialization);
            $structureTypeSpecialization->setStructureType($this);
        }

        return $this;
    }

    public function removeStructureTypeSpecialization(StructureTypeSpecialization $structureTypeSpecialization): static
    {
        if ($this->structureTypeSpecializations->removeElement($structureTypeSpecialization)) {
            // set the owning side to null (unless already changed)
            if ($structureTypeSpecialization->getStructureType() === $this) {
                $structureTypeSpecialization->setStructureType(null);
            }
        }

        return $this;
    }
}

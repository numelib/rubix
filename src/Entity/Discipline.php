<?php

namespace App\Entity;

use App\Repository\DisciplineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DisciplineRepository::class)]
class Discipline
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(targetEntity: ProfileType::class, inversedBy: 'disciplines')]
    #[ORM\JoinColumn(nullable: false)]
    private $profile_type;

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

    public function __toString()
    {
        return $this->getName();
    }

    public function getProfileType(): ?ProfileType
    {
        return $this->profile_type;
    }

    public function setProfileType(?ProfileType $profile_type): static
    {
        $this->profile_type = $profile_type;

        return $this;
    }
}

<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private ?string $name = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isCore = false;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    /**
     * @var Collection<int, Collaborator>
     */
    #[ORM\ManyToMany(targetEntity: Collaborator::class, mappedBy: 'skills')]
    private Collection $collaborators;

    public function __construct()
    {
        $this->collaborators = new ArrayCollection();
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    public function isCore(): bool
    {
        return $this->isCore;
    }

    public function setIsCore(bool $isCore): static
    {
        $this->isCore = $isCore;
        return $this;
    }

    /**
     * @return Collection<int, Collaborator>
     */
    public function getCollaborators(): Collection
    {
        return $this->collaborators;
    }
}

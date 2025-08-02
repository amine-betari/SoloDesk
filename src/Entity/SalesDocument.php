<?php

namespace App\Entity;

use App\Repository\SalesDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalesDocumentRepository::class)]
class SalesDocument
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private ?string $type = null;

    #[ORM\Column(length: 255)]
    private ?string $reference = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\ManyToOne(inversedBy: 'salesDocuments')]
    private ?Project $project = null;

    #[ORM\ManyToOne(inversedBy: 'salesDocuments')]
    private ?Estimate $estimate = null;

    /**
     * @var Collection<int, SalesDocumentItem>
     */
    #[ORM\OneToMany(targetEntity: SalesDocumentItem::class, mappedBy: 'salesDocument', cascade: ['persist'], orphanRemoval: true)]
    private Collection $salesDocumentItems;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->salesDocumentItems = new ArrayCollection();
       // $this->reference = $this->projectNumber ?? $this->generateProjectNumber();

    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getReference(): ?string
    {
        return $this->reference;
    }

    public function setReference(string $reference): static
    {
        $this->reference = $reference;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): static
    {
        $this->modifiedAt = $modifiedAt;

        return $this;
    }

    public function getProject(): ?Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getEstimate(): ?Estimate
    {
        return $this->estimate;
    }

    public function setEstimate(?Estimate $estimate): static
    {
        $this->estimate = $estimate;

        return $this;
    }

    public function getClient(): ?Client
    {
        if ($this->project !== null) {
            return $this->project->getClient();
        }
        if ($this->estimate !== null) {
            return $this->estimate->getClient();
        }
        return null;
    }

    /**
     * @return Collection<int, SalesDocumentItem>
     */
    public function getSalesDocumentItems(): Collection
    {
        return $this->salesDocumentItems;
    }

    public function addSalesDocumentItem(SalesDocumentItem $salesDocumentItem): static
    {
        if (!$this->salesDocumentItems->contains($salesDocumentItem)) {
            $this->salesDocumentItems->add($salesDocumentItem);
            $salesDocumentItem->setSalesDocument($this);
        }

        return $this;
    }

    public function removeSalesDocumentItem(SalesDocumentItem $salesDocumentItem): static
    {
        if ($this->salesDocumentItems->removeElement($salesDocumentItem)) {
            // set the owning side to null (unless already changed)
            if ($salesDocumentItem->getSalesDocument() === $this) {
                $salesDocumentItem->setSalesDocument(null);
            }
        }

        return $this;
    }

    /**
     * Calcule le total HT du document en sommant toutes les lignes.
     */
    public function getTotal(): float
    {
        $total = 0.0;

        foreach ($this->salesDocumentItems as $item) {
            $total += $item->getQuantity() * $item->getUnitPrice();
        }

        return $total;
    }
}

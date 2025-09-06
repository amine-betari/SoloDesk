<?php

namespace App\Entity;

use App\Repository\SalesDocumentRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Constants\InvoiceStatus;

#[ORM\Entity(repositoryClass: SalesDocumentRepository::class)]
class SalesDocument
{

    public const TYPE_ESTIMATE = 'estimate';
    public const TYPE_INVOICE = 'invoice';


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

    #[ORM\ManyToOne(inversedBy: 'salesDocuments')]
    private ?Client $client = null;

    #[ORM\OneToMany(mappedBy: 'salesDocument', targetEntity: Payment::class)]
    private Collection $payments;


    #[ORM\Column(length: 20)]
    private string $status = 'draft'; // Valeur par défaut

    #[ORM\Column(type: "text", nullable: true)]
    private ?string $notes = null;

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
        return $this->client;
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
            $total += (float)$item->getQuantity() * (float)$item->getUnitPrice();
        }

        return $total;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): static
    {
        // On peut éventuellement vérifier que le status est valide
        $validStatuses = ['draft', 'sent', 'partially_paid', 'paid', 'cancelled'];
        if (!in_array($status, $validStatuses, true) && $this->getType() !== 'estimate') {
            throw new \InvalidArgumentException("Statut invalide pour le SalesDocument : $status");
        }

        $this->status = $status;
        return $this;
    }

    public function setClient(?Client $client): self
    {
        $this->client = $client;
        return $this;
    }

    #[Assert\Callback]
    public function validateContext(ExecutionContextInterface $context): void
    {
        if (!$this->client && !$this->project && !$this->estimate) {
            $context->buildViolation('Veuillez choisir un Client ou lier un Projet/Devis.')
                ->atPath('client')->addViolation();
        }

        if ($this->project && $this->client && $this->project->getClient() !== $this->client) {
            $context->buildViolation('Le client ne correspond pas à celui du projet.')
                ->atPath('client')->addViolation();
        }

        if ($this->estimate && $this->client && $this->estimate->getClient() !== $this->client) {
            $context->buildViolation('Le client ne correspond pas à celui du devis.')
                ->atPath('client')->addViolation();
        }
    }


    public function getResolvedClient(): ?Client
    {
        return $this->client
            ?? $this->estimate?->getClient()
            ?? $this->project?->getClient();
    }

    public function getResolvedCurrency(string $fallback = 'EUR'): string
    {
        return $this->estimate?->getCurrency()
            ?? $this->project?->getCurrency()
            ?? $this->getResolvedClient()?->getCurrency()
            ?? $fallback;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    // src/Entity/SalesDocument.php

    public function updateStatusBasedOnPayments(): void
    {
        $totalPaid = 0;

        foreach ($this->payments as $payment) {
            $totalPaid += $payment->getAmount();
        }

        if ($totalPaid >= $this->getTotal()) {
            $this->setStatus(InvoiceStatus::PAID);
        } elseif ($totalPaid > 0) {
            $this->setStatus(InvoiceStatus::PARTIALLY_PAID);
        } else {
            $this->setStatus(InvoiceStatus::SENT);
        }
    }


    public function __toString(): string
    {
        return (string) $this->reference; // retourne le champ que tu veux afficher
    }

}

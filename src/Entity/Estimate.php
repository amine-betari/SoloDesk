<?php

namespace App\Entity;

use App\EventListener\EstimateListener;
use App\Repository\EstimateRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;


#[ORM\Entity(repositoryClass: EstimateRepository::class)]
#[ORM\EntityListeners([EstimateListener::class])]
#[UniqueEntity('estimateNumber')]
class Estimate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'string', length: 255)]
    private ?string $name = null;

    #[ORM\ManyToOne(inversedBy: 'estimates')]
    private ?Client $client = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $estimateNumber = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\Column(length: 255)]
    private ?string $status = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $vatRate = null;

    #[ORM\OneToOne(inversedBy: 'estimate', cascade: ['persist'])]
    private ?Project $project = null;

    #[ORM\Column(type: 'string', length: 3)]
    private $currency;

    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'estimate')]
    private Collection $documents;

    /**
     * @var Collection<int, SalesDocument>
     */
    #[ORM\OneToMany(targetEntity: SalesDocument::class, mappedBy: 'estimate')]
    private Collection $salesDocuments;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->startDate = new \DateTimeImmutable(); // date du jour par défaut
        $this->endDate = (new \DateTimeImmutable())->modify('+30 days'); // validité 30j par défaut

        $this->estimateNumber = $this->estimateNumber ?? $this->generateReference();
        $this->documents = new ArrayCollection();
        $this->salesDocuments = new ArrayCollection();

    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }


    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getEstimateNumber(): ?string
    {
        return $this->estimateNumber;
    }

    public function setEstimateNumber(string $estimateNumber): static
    {
        $this->estimateNumber = $estimateNumber;

        return $this;
    }

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;

        return $this;
    }

    private function generateReference(): string
    {
        // Exemple simple : EST + année + un id unique (à adapter)
        return 'EST-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
    }

    public function getVatRate(): ?string
    {
        return $this->vatRate;
    }

    public function setVatRate(?string $vatRate): static
    {
        $this->vatRate = $vatRate;

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

    /**
     * @return Collection<int, Document>
     */
    public function getDocuments(): Collection
    {
        return $this->documents;
    }

    public function addDocument(Document $document): static
    {
        if (!$this->documents->contains($document)) {
            $this->documents->add($document);
            $document->setEstimate($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getEstimate() === $this) {
                $document->setEstimate(null);
            }
        }

        return $this;
    }

    public function getCurrency(): string
    {
        return $this->currency;
    }

    public function setCurrency(string $currency): void
    {
        $this->currency = $currency;
    } // ex: 'MAD', 'EUR', 'USD'


    public function getModifiedAt(): ?\DateTimeImmutable
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?\DateTimeImmutable $modifiedAt): static
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(?\DateTimeImmutable $startDate): static
    {
        $this->startDate = $startDate;
        return $this;
    }

    public function getEndDate(): ?\DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(?\DateTimeImmutable $endDate): static
    {
        $this->endDate = $endDate;
        return $this;
    }

    /**
     * @return Collection<int, SalesDocument>
     */
    public function getSalesDocuments(): Collection
    {
        return $this->salesDocuments;
    }

    public function addSalesDocument(SalesDocument $salesDocument): static
    {
        if (!$this->salesDocuments->contains($salesDocument)) {
            $this->salesDocuments->add($salesDocument);
            $salesDocument->setEstimate($this);
        }

        return $this;
    }

    public function removeSalesDocument(SalesDocument $salesDocument): static
    {
        if ($this->salesDocuments->removeElement($salesDocument)) {
            // set the owning side to null (unless already changed)
            if ($salesDocument->getEstimate() === $this) {
                $salesDocument->setEstimate(null);
            }
        }

        return $this;
    }
}

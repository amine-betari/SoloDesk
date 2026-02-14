<?php

namespace App\Entity;

use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\Entity\Company;


#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[UniqueEntity('projectNumber')]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $name = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $type = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToOne(inversedBy: 'projects')]
    private ?Client $client = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    #[ORM\Column(length: 255, unique: true)]
    private ?string $projectNumber = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $modifiedAt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 4, scale: 2, nullable: true)]
    private ?string $vatRate = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $amount = null;

    #[ORM\OneToOne(mappedBy: 'project', cascade: ['persist'])]
    private ?Estimate $estimate = null;

    #[ORM\Column(type: 'boolean')]
    private bool $isRecurring = false;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $recurringAmount = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $recurringPeriod = null;

    #[ORM\Column(type: 'string', length: 3)]
    private $currency;


    /**
     * @var Collection<int, Document>
     */
    #[ORM\OneToMany(targetEntity: Document::class, mappedBy: 'project')]
    private Collection $documents;

    /**
     * @var Collection<int, SalesDocument>
     */
    #[ORM\OneToMany(targetEntity: SalesDocument::class, mappedBy: 'project')]
    private Collection $salesDocuments;


    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();

        $this->projectNumber = $this->projectNumber ?? $this->generateProjectNumber();
        $this->documents = new ArrayCollection();
        $this->salesDocuments = new ArrayCollection();

    }

    private function generateProjectNumber(): string
    {
        return 'PRJ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
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

    public function getStartDate(): ?\DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): static
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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(?string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;
        if ($client) {
            $this->company = $client->getCompany();
        }

        return $this;
    }

    public function getProjectNumber(): ?string
    {
        return $this->projectNumber;
    }

    public function setProjectNumber(string $projectNumber): static
    {
        $this->projectNumber = $projectNumber;

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

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
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

    public function getAmount(): ?string
    {
        return $this->amount;
    }

    public function setAmount(?string $amount): static
    {
        $this->amount = $amount;

        return $this;
    }

    public function getEstimate(): ?Estimate
    {
        return $this->estimate;
    }

    public function setEstimate(?Estimate $estimate): static
    {
        // unset the owning side of the relation if necessary
        if ($estimate === null && $this->estimate !== null) {
            $this->estimate->setProject(null);
        }

        // set the owning side of the relation if necessary
        if ($estimate !== null && $estimate->getProject() !== $this) {
            $estimate->setProject($this);
        }

        $this->estimate = $estimate;

        return $this;
    }

    public function isRecurring(): bool
    {
        return $this->isRecurring;
    }

    public function setIsRecurring(bool $isRecurring): static
    {
        $this->isRecurring = $isRecurring;
        return $this;
    }

    public function getRecurringAmount(): ?string
    {
        return $this->recurringAmount;
    }

    public function setRecurringAmount(?string $recurringAmount): static
    {
        $this->recurringAmount = $recurringAmount;
        return $this;
    }

    public function getRecurringPeriod(): ?string
    {
        return $this->recurringPeriod;
    }

    public function setRecurringPeriod(?string $recurringPeriod): static
    {
        $this->recurringPeriod = $recurringPeriod;
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
            $document->setProject($this);
        }

        return $this;
    }

    public function removeDocument(Document $document): static
    {
        if ($this->documents->removeElement($document)) {
            // set the owning side to null (unless already changed)
            if ($document->getProject() === $this) {
                $document->setProject(null);
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

    public function getCompany(): ?Company
    {
        return $this->company;
    }

    public function setCompany(?Company $company): static
    {
        $this->company = $company;
        return $this;
    }

    /**
     * @throws \Exception
     */
    public function getCalculatedAmount(): ?float
    {
        if (!$this->isRecurring) {
            return $this->amount;
        }

        $start = $this->startDate instanceof \DateTimeImmutable
            ? $this->startDate
            : new \DateTimeImmutable($this->startDate);

        $end = $this->endDate instanceof \DateTimeImmutable
            ? $this->endDate
            : new \DateTimeImmutable($this->endDate ?? 'now');

        $diff = $end->diff($start);
        $months = $diff->y * 12 + $diff->m;

        $periods = 1;
        switch ($this->recurringPeriod) {
            case 'monthly':
                $periods = $months + 1;
                break;
            case 'quarterly':
                $periods = (int)($months / 3) + 1;
                break;
            case 'yearly':
                $periods = $diff->y + 1;
                break;
        }

        return ($this->recurringAmount ?? 0) * $periods;
    }

    public function getPayments(): Collection
    {
        return $this->payments;
    }

    public function addPayment(Payment $payment): static
    {
        if (!$this->payments->contains($payment)) {
            $this->payments->add($payment);
            $payment->setProject($this);
        }

        return $this;
    }

    public function removePayment(Payment $payment): static
    {
        if ($this->payments->removeElement($payment)) {
            if ($payment->getProject() === $this) {
                $payment->setProject(null);
            }
        }

        return $this;
    }

    public function getTotalPaid(): float
    {
        $total = 0.0;
        foreach ($this->getPayments() as $payment) {
            $total += (float) $payment->getAmount();
        }
        return $total;
    }

    public function getRemainingAmount(): float
    {
        return (float) $this->amount - $this->getTotalPaid();
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
            $salesDocument->setProject($this);
        }

        return $this;
    }

    public function removeSalesDocument(SalesDocument $salesDocument): static
    {
        if ($this->salesDocuments->removeElement($salesDocument)) {
            // set the owning side to null (unless already changed)
            if ($salesDocument->getProject() === $this) {
                $salesDocument->setProject(null);
            }
        }

        return $this;
    }
    public function __toString(): string
    {
        return (string) $this->name; // retourne le champ que tu veux afficher
    }

    #[Assert\Callback]
    public function validateDates(ExecutionContextInterface $context): void
    {
        if ($this->startDate !== null && $this->endDate !== null && $this->endDate < $this->startDate) {
            $context->buildViolation('La date de fin ne peut pas être antérieure à la date de début.')
                ->atPath('endDate')
                ->addViolation();
        }
    }
}

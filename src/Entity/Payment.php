<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\UX\Autocomplete\AsEntityAutocompleteField;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;
use App\EventListener\PaymentListener;
use App\Entity\Company;

#[ORM\Entity(repositoryClass: PaymentRepository::class)]
#[ORM\EntityListeners([PaymentListener::class])]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $date;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $amount; // Montant HT

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $method = null; // virement, chèque, espèces, etc.

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $label = null; // ex : "Acompte LOT 1", "Solde", etc.

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $invoiceReference = null; // ex: "FAC-2024-0034"

    #[ORM\ManyToOne(targetEntity: SalesDocument::class, inversedBy: 'payments')]
    #[ORM\JoinColumn(nullable: true)]
    private ?SalesDocument $salesDocument = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Company $company = null;

    // --- Getters & Setters ---
    #[Assert\Callback]
    public function validateContext(ExecutionContextInterface $context): void
    {
       // Si aucun projet n'est choisi, alors la facture est obligatoire
        if (!$this->salesDocument) {
            $context->buildViolation('Veuillez sélectionner une facture')
                ->atPath('salesDocument') // ça pointe sur le champ facture
                ->addViolation();
        }
    }

    public function getSalesDocument(): ?SalesDocument
    {
        return $this->salesDocument;
    }

    public function setSalesDocument(?SalesDocument $salesDocument): static
    {
        $this->salesDocument = $salesDocument;
        if ($salesDocument) {
            $this->company = $salesDocument->getCompany();
        }
        return $this;
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getDate(): \DateTimeImmutable
    {
        return $this->date;
    }

    public function setDate(\DateTimeImmutable $date): static
    {
        $this->date = $date;
        return $this;
    }

    public function getAmount(): string
    {
        return $this->amount;
    }

    public function setAmount(string $amount): static
    {
        $this->amount = $amount;
        return $this;
    }

    public function getMethod(): ?string
    {
        return $this->method;
    }

    public function setMethod(?string $method): static
    {
        $this->method = $method;
        return $this;
    }

    public function getLabel(): ?string
    {
        return $this->label;
    }

    public function setLabel(?string $label): static
    {
        $this->label = $label;
        return $this;
    }

   /* public function getProject(): ?Project
    {
        return $this->project;
    }*/

   /* public function setProject(?Project $project): static
    {
        $this->project = $project;
        return $this;
    }*/

    public function getInvoiceReference(): ?string
    {
        return $this->invoiceReference;
    }

    public function setInvoiceReference(?string $invoiceReference): static
    {
        $this->invoiceReference = $invoiceReference;
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

}

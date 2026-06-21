<?php

declare(strict_types=1);

namespace App\Service;

use App\Constants\InvoiceStatus;
use App\Entity\Estimate;
use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use App\Repository\SalesDocumentRepository;

final class SalesDocumentInvoiceProgress
{
    public function __construct(private readonly SalesDocumentRepository $salesDocumentRepository)
    {
    }

    /**
     * @return array{estimate_ht: float, invoiced_ht: float, remaining_ht: float, over_invoiced_ht: float, estimate_ttc: float, invoiced_ttc: float, remaining_ttc: float, over_invoiced_ttc: float, invoices_count: int}
     */
    public function getProgress(SalesDocument $commercialEstimate): array
    {
        $estimateTotalHT = $commercialEstimate->getTotalHT();
        $estimateTotalTTC = $commercialEstimate->getTotalTTC();
        $invoices = $this->findInvoicesForCommercialEstimate($commercialEstimate);
        $invoicedTotalHT = $this->getInvoicedTotalHT($commercialEstimate);
        $invoicedTotalTTC = $this->getInvoicedTotalTTC($commercialEstimate);

        return [
            'estimate_ht' => $estimateTotalHT,
            'invoiced_ht' => $invoicedTotalHT,
            'remaining_ht' => max(0.0, $estimateTotalHT - $invoicedTotalHT),
            'over_invoiced_ht' => max(0.0, $invoicedTotalHT - $estimateTotalHT),
            'estimate_ttc' => $estimateTotalTTC,
            'invoiced_ttc' => $invoicedTotalTTC,
            'remaining_ttc' => max(0.0, $estimateTotalTTC - $invoicedTotalTTC),
            'over_invoiced_ttc' => max(0.0, $invoicedTotalTTC - $estimateTotalTTC),
            'invoices_count' => count(array_filter($invoices, static fn (SalesDocument $document): bool => $document->getStatus() !== InvoiceStatus::CANCELLED)),
        ];
    }

    /**
     * @return array{estimate_amount_ht: float, commercial_estimate_ht: float, invoiced_ht: float, remaining_ht: float, over_invoiced_ht: float, status: string}
     */
    public function getEstimateProgress(Estimate $estimate): array
    {
        $estimateAmount = (float) $estimate->getAmount();
        $commercialEstimateTotal = 0.0;
        $invoicedTotal = 0.0;

        foreach ($estimate->getSalesDocuments() as $document) {
            if ($document->isEstimate()) {
                $commercialEstimateTotal += $document->getTotalHT();
            }

            if ($document->isInvoice() && $document->getStatus() !== InvoiceStatus::CANCELLED) {
                $invoicedTotal += $document->getTotalHT();
            }
        }

        $billingTarget = $commercialEstimateTotal > 0.0 ? $commercialEstimateTotal : $estimateAmount;
        $remaining = max(0.0, $billingTarget - $invoicedTotal);
        $overInvoiced = max(0.0, $invoicedTotal - $billingTarget);

        return [
            'estimate_amount_ht' => $estimateAmount,
            'commercial_estimate_ht' => $commercialEstimateTotal,
            'invoiced_ht' => $invoicedTotal,
            'remaining_ht' => $remaining,
            'over_invoiced_ht' => $overInvoiced,
            'status' => $this->getEstimateBillingStatus($billingTarget, $invoicedTotal),
        ];
    }

    /**
     * @return SalesDocumentItem[]
     */
    public function createInvoiceItems(SalesDocument $commercialEstimate): array
    {
        $progress = $this->getProgress($commercialEstimate);

        if ($progress['invoiced_ht'] > 0.0 && $progress['remaining_ht'] < $progress['estimate_ht']) {
            return [
                $this->createItem(
                    'Solde restant du devis '.$commercialEstimate->getReference(),
                    '1.000',
                    $this->formatDecimal($progress['remaining_ht']),
                    $this->formatDecimal($progress['remaining_ht'])
                ),
            ];
        }

        $items = [];
        foreach ($commercialEstimate->getSalesDocumentItems() as $item) {
            $quantity = (float) $item->getQuantity();
            $unitPrice = (float) $item->getUnitPrice();
            $lineTotal = $quantity * $unitPrice;

            $items[] = $this->createItem(
                $item->getDescription(),
                $this->formatDecimal($quantity, 3),
                $this->formatDecimal($unitPrice),
                $this->formatDecimal($lineTotal)
            );
        }

        return $items;
    }

    private function getInvoicedTotalHT(SalesDocument $commercialEstimate): float
    {
        $estimate = $commercialEstimate->getEstimate();
        if ($estimate === null) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->findInvoicesForCommercialEstimate($commercialEstimate) as $document) {
            if ($document->isInvoice() && $document->getStatus() !== InvoiceStatus::CANCELLED) {
                $total += $document->getTotalHT();
            }
        }

        return $total;
    }

    private function getInvoicedTotalTTC(SalesDocument $commercialEstimate): float
    {
        $estimate = $commercialEstimate->getEstimate();
        if ($estimate === null) {
            return 0.0;
        }

        $total = 0.0;
        foreach ($this->findInvoicesForCommercialEstimate($commercialEstimate) as $document) {
            if ($document->isInvoice() && $document->getStatus() !== InvoiceStatus::CANCELLED) {
                $total += $document->getTotalTTC();
            }
        }

        return $total;
    }

    private function createItem(?string $description, string $quantity, string $unitPrice, string $lineTotal): SalesDocumentItem
    {
        return (new SalesDocumentItem())
            ->setDescription($description)
            ->setQuantity($quantity)
            ->setUnitPrice($unitPrice)
            ->setLineTotal($lineTotal);
    }

    private function formatDecimal(float $amount, int $decimals = 2): string
    {
        return number_format($amount, $decimals, '.', '');
    }

    private function getEstimateBillingStatus(float $billingTarget, float $invoicedTotal): string
    {
        if ($billingTarget > 0.0 && $invoicedTotal > $billingTarget) {
            return 'over_invoiced';
        }

        if ($invoicedTotal <= 0.0) {
            return 'not_invoiced';
        }

        if ($billingTarget > 0.0 && $invoicedTotal >= $billingTarget) {
            return 'fully_invoiced';
        }

        return 'partially_invoiced';
    }

    /**
     * @return SalesDocument[]
     */
    private function findInvoicesForCommercialEstimate(SalesDocument $commercialEstimate): array
    {
        $estimate = $commercialEstimate->getEstimate();
        if ($estimate === null) {
            return [];
        }

        return $this->salesDocumentRepository->findBy([
            'estimate' => $estimate,
            'type' => SalesDocument::TYPE_INVOICE,
        ]);
    }
}

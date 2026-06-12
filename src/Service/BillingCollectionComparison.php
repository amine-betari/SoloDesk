<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Payment;
use App\Entity\SalesDocument;

final class BillingCollectionComparison
{
    /**
     * @param SalesDocument[] $invoices
     * @param Payment[]       $payments
     *
     * @return array{
     *     labels: string[],
     *     billed: array<string, float[]>,
     *     collected: array<string, float[]>
     * }
     */
    public function compare(
        array $invoices,
        array $payments,
        \DateTimeImmutable $startMonth,
        int $monthCount
    ): array {
        $months = [];
        for ($offset = 0; $offset < $monthCount; ++$offset) {
            $month = $startMonth->modify(sprintf('+%d months', $offset));
            $months[$month->format('Y-m')] = $month->format('m/Y');
        }

        $billedTotals = [];
        foreach ($invoices as $invoice) {
            $invoiceDate = $invoice->getInvoiceDate();
            if (!$invoiceDate) {
                continue;
            }

            $monthKey = $invoiceDate->format('Y-m');
            if (!isset($months[$monthKey])) {
                continue;
            }

            $currency = $invoice->getResolvedCurrency();
            $billedTotals[$currency][$monthKey] = ($billedTotals[$currency][$monthKey] ?? 0.0)
                + $invoice->getTotalTTC();
        }

        $collectedTotals = [];
        foreach ($payments as $payment) {
            $salesDocument = $payment->getSalesDocument();
            if (!$salesDocument) {
                continue;
            }

            $monthKey = $payment->getDate()->format('Y-m');
            if (!isset($months[$monthKey])) {
                continue;
            }

            $currency = $salesDocument->getResolvedCurrency();
            $collectedTotals[$currency][$monthKey] = ($collectedTotals[$currency][$monthKey] ?? 0.0)
                + (float) $payment->getAmount();
        }

        $currencies = array_unique(array_merge(array_keys($billedTotals), array_keys($collectedTotals)));
        sort($currencies);

        $billed = [];
        $collected = [];
        foreach ($currencies as $currency) {
            foreach (array_keys($months) as $monthKey) {
                $billed[$currency][] = $billedTotals[$currency][$monthKey] ?? 0.0;
                $collected[$currency][] = $collectedTotals[$currency][$monthKey] ?? 0.0;
            }
        }

        return [
            'labels' => array_values($months),
            'billed' => $billed,
            'collected' => $collected,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;

final class SalesDocumentDuplicator
{
    public function duplicateEstimate(SalesDocument $source, ?\DateTimeImmutable $now = null): SalesDocument
    {
        if (!$source->isEstimate()) {
            throw new \InvalidArgumentException('Only estimate sales documents can be duplicated.');
        }

        $now = $now ?? new \DateTimeImmutable('now');

        $duplicate = new SalesDocument();
        $duplicate->setType($source->getType() ?? SalesDocument::TYPE_ESTIMATE);
        $duplicate->setCompany($source->getCompany());

        // Business rules validated with the user:
        // - status = draft
        // - invoiceDate = today
        // - reference is suggested and can be edited later
        $duplicate->setStatus('draft');
        $duplicate->setCreatedAt($now);
        $duplicate->setInvoiceDate($now->setTime(0, 0));
        $duplicate->setModifiedAt(null);
        $duplicate->setReference($this->suggestReference($source, $now));

        // Preserve relationships (client/project/estimate) and content.
        $duplicate->setProject($source->getProject());
        $duplicate->setEstimate($source->getEstimate());
        $duplicate->setClient($source->getResolvedClient());
        $duplicate->setNotes($source->getNotes());
        $duplicate->setTaxApplied($source->isTaxApplied());
        $duplicate->setVatRate($source->getVatRate());

        foreach ($source->getSalesDocumentItems() as $item) {
            $newItem = new SalesDocumentItem();
            $newItem->setDescription($item->getDescription());
            $newItem->setUnitPrice($item->getUnitPrice());
            $newItem->setQuantity($item->getQuantity());
            $newItem->setLineTotal($item->getLineTotal());

            $duplicate->addSalesDocumentItem($newItem);
        }

        return $duplicate;
    }

    private function suggestReference(SalesDocument $source, \DateTimeImmutable $now): string
    {
        $base = trim((string) $source->getReference());
        $base = $base !== '' ? $base : 'EST';

        // Keep it short-ish and unique enough for UI purposes.
        return sprintf('DUP-%s-%s', $base, $now->format('Ymd-His'));
    }
}


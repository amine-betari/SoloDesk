<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Estimate;
use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use App\Service\SalesDocumentDuplicator;
use PHPUnit\Framework\TestCase;

final class SalesDocumentDuplicatorTest extends TestCase
{
    public function testDuplicateEstimateCopiesFieldsAndItemsAndResetsBusinessFields(): void
    {
        $company = (new Company())->setName('ACME');

        $client = (new Client())
            ->setName('Client 1')
            ->setCompany($company);

        $estimate = (new Estimate())
            ->setName('Estimate 1')
            ->setClient($client)
            ->setEstimateNumber('EST-2026-ABC123')
            ->setStatus('accepted');
        $estimate->setCurrency('EUR');

        $source = new SalesDocument();
        $source->setType(SalesDocument::TYPE_ESTIMATE);
        $source->setEstimate($estimate);
        $source->setReference('EST-2026-ABC123');
        $source->setStatus('sent');
        $source->setNotes('Some notes');
        $source->setTaxApplied(true);
        $source->setVatRate(20.0);

        $item1 = (new SalesDocumentItem())
            ->setDescription('Item 1')
            ->setUnitPrice('10.00')
            ->setQuantity('2.000')
            ->setLineTotal('20.00');
        $source->addSalesDocumentItem($item1);

        $item2 = (new SalesDocumentItem())
            ->setDescription('Item 2')
            ->setUnitPrice('5.50')
            ->setQuantity('1.000')
            ->setLineTotal('5.50');
        $source->addSalesDocumentItem($item2);

        $now = new \DateTimeImmutable('2026-04-15 10:11:12');
        $duplicator = new SalesDocumentDuplicator();

        $duplicate = $duplicator->duplicateEstimate($source, $now);

        self::assertNotSame($source, $duplicate);
        self::assertTrue($duplicate->isEstimate());
        self::assertSame('draft', $duplicate->getStatus());
        self::assertSame('Some notes', $duplicate->getNotes());
        self::assertTrue($duplicate->isTaxApplied());
        self::assertSame(20.0, $duplicate->getVatRate());

        self::assertSame($estimate, $duplicate->getEstimate());
        self::assertSame($client, $duplicate->getResolvedClient());

        self::assertSame('2026-04-15 00:00:00', $duplicate->getInvoiceDate()?->format('Y-m-d H:i:s'));
        self::assertStringStartsWith('DUP-EST-2026-ABC123-', (string) $duplicate->getReference());

        self::assertCount(2, $duplicate->getSalesDocumentItems());
        $newItems = $duplicate->getSalesDocumentItems()->toArray();

        self::assertNotSame($item1, $newItems[0]);
        self::assertSame('Item 1', $newItems[0]->getDescription());
        self::assertSame('10.00', $newItems[0]->getUnitPrice());
        self::assertSame('2.000', $newItems[0]->getQuantity());
        self::assertSame('20.00', $newItems[0]->getLineTotal());

        self::assertNotSame($item2, $newItems[1]);
        self::assertSame('Item 2', $newItems[1]->getDescription());
        self::assertSame('5.50', $newItems[1]->getUnitPrice());
        self::assertSame('1.000', $newItems[1]->getQuantity());
        self::assertSame('5.50', $newItems[1]->getLineTotal());
    }
}


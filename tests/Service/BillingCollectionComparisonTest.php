<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Payment;
use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use App\Service\BillingCollectionComparison;
use PHPUnit\Framework\TestCase;

final class BillingCollectionComparisonTest extends TestCase
{
    public function testCompareSeparatesCurrenciesAndUsesActualInvoiceAndPaymentMonths(): void
    {
        $company = (new Company())->setName('SoloDesk');
        $madClient = (new Client())->setName('Client MAD')->setCompany($company);
        $madClient->setCurrency('MAD');
        $eurClient = (new Client())->setName('Client EUR')->setCompany($company);
        $eurClient->setCurrency('EUR');

        $madInvoice = $this->createInvoice($madClient, '2026-04-10', '1000.00');
        $eurInvoice = $this->createInvoice($eurClient, '2026-05-05', '500.00');

        $madPayment = (new Payment())
            ->setSalesDocument($madInvoice)
            ->setDate(new \DateTimeImmutable('2026-05-20'))
            ->setAmount('600.00');
        $eurPayment = (new Payment())
            ->setSalesDocument($eurInvoice)
            ->setDate(new \DateTimeImmutable('2026-06-02'))
            ->setAmount('500.00');

        $comparison = (new BillingCollectionComparison())->compare(
            [$madInvoice, $eurInvoice],
            [$madPayment, $eurPayment],
            new \DateTimeImmutable('2026-04-01'),
            3
        );

        self::assertSame(['04/2026', '05/2026', '06/2026'], $comparison['labels']);
        self::assertSame([0.0, 500.0, 0.0], $comparison['billed']['EUR']);
        self::assertSame([1000.0, 0.0, 0.0], $comparison['billed']['MAD']);
        self::assertSame([0.0, 0.0, 500.0], $comparison['collected']['EUR']);
        self::assertSame([0.0, 600.0, 0.0], $comparison['collected']['MAD']);
    }

    private function createInvoice(Client $client, string $date, string $amount): SalesDocument
    {
        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setClient($client)
            ->setReference('INV-'.$date)
            ->setInvoiceDate(new \DateTimeImmutable($date));

        $item = (new SalesDocumentItem())
            ->setDescription('Service')
            ->setQuantity('1.000')
            ->setUnitPrice($amount)
            ->setLineTotal($amount);
        $invoice->addSalesDocumentItem($item);

        return $invoice;
    }
}

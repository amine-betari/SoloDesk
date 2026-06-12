<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Constants\InvoiceStatus;
use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Payment;
use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Validation;

final class PaymentBusinessRulesTest extends TestCase
{
    public function testPaymentAmountMustBePositive(): void
    {
        $payment = (new Payment())
            ->setSalesDocument($this->createInvoice('100.00'))
            ->setAmount('0.00');

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($payment);

        self::assertCount(1, $violations);
        self::assertSame('amount', $violations[0]->getPropertyPath());
    }

    public function testPaymentCannotExceedRemainingBalance(): void
    {
        $invoice = $this->createInvoice('100.00');
        $invoice->getPayments()->add(
            (new Payment())
                ->setSalesDocument($invoice)
                ->setAmount('60.00')
        );
        $payment = (new Payment())
            ->setSalesDocument($invoice)
            ->setAmount('50.00');

        $violations = Validation::createValidatorBuilder()
            ->enableAttributeMapping()
            ->getValidator()
            ->validate($payment);

        self::assertCount(1, $violations);
        self::assertSame('amount', $violations[0]->getPropertyPath());
    }

    public function testCancelledInvoiceStatusIsPreservedWhenPaymentsChange(): void
    {
        $invoice = $this->createInvoice('100.00');
        $invoice->setStatus(InvoiceStatus::CANCELLED);
        $invoice->getPayments()->add(
            (new Payment())
                ->setSalesDocument($invoice)
                ->setAmount('100.00')
        );

        $invoice->updateStatusBasedOnPayments();

        self::assertSame(InvoiceStatus::CANCELLED, $invoice->getStatus());
    }

    private function createInvoice(string $amount): SalesDocument
    {
        $company = (new Company())->setName('SoloDesk');
        $client = (new Client())->setName('Client')->setCompany($company);
        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setClient($client)
            ->setReference('INV-TEST');

        $invoice->addSalesDocumentItem(
            (new SalesDocumentItem())
                ->setDescription('Service')
                ->setQuantity('1.000')
                ->setUnitPrice($amount)
                ->setLineTotal($amount)
        );

        return $invoice;
    }
}

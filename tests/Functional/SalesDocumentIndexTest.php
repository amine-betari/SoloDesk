<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Constants\InvoiceStatus;
use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Estimate;
use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class SalesDocumentIndexTest extends WebTestCase
{
    public function testAcceptedCommercialEstimateShowsLinkedInvoiceSummary(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Sales Document Index Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $estimate = (new Estimate())
            ->setName('Sales document index estimate '.bin2hex(random_bytes(4)))
            ->setClient($client)
            ->setEstimateNumber('EST-SALES-INDEX-'.bin2hex(random_bytes(3)))
            ->setAmount('82800.00')
            ->setStatus('accepted');
        $estimate->setCurrency('MAD');

        $commercialEstimate = (new SalesDocument())
            ->setType(SalesDocument::TYPE_ESTIMATE)
            ->setReference('EST-SALES-DOC-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus('accepted')
            ->setInvoiceDate(new \DateTimeImmutable('2026-06-20'));
        $commercialEstimate->addSalesDocumentItem($this->createSalesDocumentItem('Commercial estimate total', '82800.00'));

        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference('INV-SALES-DOC-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus(InvoiceStatus::SENT)
            ->setInvoiceDate(new \DateTimeImmutable('2026-06-19'));
        $invoice->addSalesDocumentItem($this->createSalesDocumentItem('Invoice line', '33120.00'));

        $entityManager->persist($client);
        $entityManager->persist($estimate);
        $entityManager->persist($commercialEstimate);
        $entityManager->persist($invoice);
        $entityManager->flush();
        $entityManager->clear();

        $browser->request('GET', '/sales/document');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Factures liées : 1');
        self::assertSelectorTextContains('table', 'Partiellement facturé');
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())->setName('Sales Document Index Company '.bin2hex(random_bytes(4)));
        $user = (new User())
            ->setEmail('sales-document-index-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('unused')
            ->setCompany($company);

        $entityManager->persist($company);
        $entityManager->persist($user);
        $entityManager->flush();
        $browser->loginUser($user);

        return $browser;
    }

    private function createSalesDocumentItem(string $description, string $amount): SalesDocumentItem
    {
        return (new SalesDocumentItem())
            ->setDescription($description)
            ->setQuantity('1.000')
            ->setUnitPrice($amount)
            ->setLineTotal($amount);
    }
}

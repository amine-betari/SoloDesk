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

final class SalesDocumentShowTest extends WebTestCase
{
    public function testAcceptedCommercialEstimateKeepsInvoiceCreationActionWhenInvoiceAlreadyExists(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Invoice Split Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $estimate = (new Estimate())
            ->setName('Accepted estimate '.bin2hex(random_bytes(4)))
            ->setClient($client)
            ->setEstimateNumber('EST-SPLIT-'.bin2hex(random_bytes(3)))
            ->setAmount('82800.00')
            ->setStatus('accepted');
        $estimate->setCurrency('MAD');

        $commercialEstimate = (new SalesDocument())
            ->setType(SalesDocument::TYPE_ESTIMATE)
            ->setReference('EST-COM-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus('sent');
        $commercialEstimate->addSalesDocumentItem($this->createSalesDocumentItem('Commercial line'));

        $existingInvoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference('INV-EXISTING-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus(InvoiceStatus::SENT);
        $existingInvoice->addSalesDocumentItem($this->createSalesDocumentItem('Existing invoice line'));

        $entityManager->persist($client);
        $entityManager->persist($estimate);
        $entityManager->persist($commercialEstimate);
        $entityManager->persist($existingInvoice);
        $entityManager->flush();

        $browser->request('GET', '/sales/document/'.$commercialEstimate->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(\sprintf('a[href="/sales/document/sales-document/from-estimate/%d"]', $commercialEstimate->getId()));
        self::assertSelectorExists(\sprintf('a[href="/estimate/%d"]', $estimate->getId()));
        self::assertSelectorTextContains('body', 'Créer une facture depuis ce devis');
        self::assertSelectorTextContains('body', 'Reste à facturer');
    }

    public function testCreatingAnotherInvoiceFromCommercialEstimateUsesRemainingAmount(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Remaining Invoice Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $estimate = (new Estimate())
            ->setName('Remaining estimate '.bin2hex(random_bytes(4)))
            ->setClient($client)
            ->setEstimateNumber('EST-REMAINING-'.bin2hex(random_bytes(3)))
            ->setAmount('82800.00')
            ->setStatus('accepted');
        $estimate->setCurrency('MAD');

        $commercialEstimate = (new SalesDocument())
            ->setType(SalesDocument::TYPE_ESTIMATE)
            ->setReference('EST-COM-REMAINING-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus('sent');
        $commercialEstimate->addSalesDocumentItem(
            (new SalesDocumentItem())
                ->setDescription('Commercial estimate total')
                ->setQuantity('1.000')
                ->setUnitPrice('82800.00')
                ->setLineTotal('82800.00')
        );

        $existingInvoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference('INV-ACOMPTE-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus(InvoiceStatus::SENT);
        $existingInvoice->addSalesDocumentItem(
            (new SalesDocumentItem())
                ->setDescription('Acompte')
                ->setQuantity('1.000')
                ->setUnitPrice('33120.00')
                ->setLineTotal('33120.00')
        );

        $entityManager->persist($client);
        $entityManager->persist($estimate);
        $entityManager->persist($commercialEstimate);
        $entityManager->persist($existingInvoice);
        $entityManager->flush();

        $browser->request('GET', '/sales/document/sales-document/from-estimate/'.$commercialEstimate->getId());

        self::assertResponseRedirects();
        $location = $browser->getResponse()->headers->get('Location');
        self::assertIsString($location);
        self::assertMatchesRegularExpression('#/sales/document/(\d+)#', $location);
        preg_match('#/sales/document/(\d+)#', $location, $matches);

        $createdInvoice = $entityManager->getRepository(SalesDocument::class)->find((int) $matches[1]);
        self::assertInstanceOf(SalesDocument::class, $createdInvoice);
        self::assertTrue($createdInvoice->isInvoice());
        self::assertSame(49680.0, $createdInvoice->getTotalHT());
        self::assertCount(1, $createdInvoice->getSalesDocumentItems());
        self::assertStringContainsString('Solde restant', (string) $createdInvoice->getSalesDocumentItems()->first()->getDescription());
    }

    public function testCommercialEstimateShowsOverInvoicedWarning(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Overrun Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $estimate = (new Estimate())
            ->setName('Overrun estimate '.bin2hex(random_bytes(4)))
            ->setClient($client)
            ->setEstimateNumber('EST-OVERRUN-'.bin2hex(random_bytes(3)))
            ->setAmount('100.00')
            ->setStatus('accepted');
        $estimate->setCurrency('MAD');

        $commercialEstimate = (new SalesDocument())
            ->setType(SalesDocument::TYPE_ESTIMATE)
            ->setReference('EST-COM-OVERRUN-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus('sent');
        $commercialEstimate->addSalesDocumentItem(
            (new SalesDocumentItem())
                ->setDescription('Commercial estimate total')
                ->setQuantity('1.000')
                ->setUnitPrice('100.00')
                ->setLineTotal('100.00')
        );

        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference('INV-OVERRUN-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus(InvoiceStatus::SENT);
        $invoice->addSalesDocumentItem(
            (new SalesDocumentItem())
                ->setDescription('Overrun invoice')
                ->setQuantity('1.000')
                ->setUnitPrice('120.00')
                ->setLineTotal('120.00')
        );

        $entityManager->persist($client);
        $entityManager->persist($estimate);
        $entityManager->persist($commercialEstimate);
        $entityManager->persist($invoice);
        $entityManager->flush();

        $browser->request('GET', '/sales/document/'.$commercialEstimate->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Attention : les factures dépassent le devis de');
    }

    public function testInvoiceShowsLinkedPreEstimateContext(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Invoice Context Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $estimate = (new Estimate())
            ->setName('Invoice context estimate '.bin2hex(random_bytes(4)))
            ->setClient($client)
            ->setEstimateNumber('EST-CONTEXT-'.bin2hex(random_bytes(3)))
            ->setAmount('100.00')
            ->setStatus('accepted');
        $estimate->setCurrency('MAD');

        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference('INV-CONTEXT-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus(InvoiceStatus::SENT);
        $invoice->addSalesDocumentItem($this->createSalesDocumentItem('Invoice context line'));

        $entityManager->persist($client);
        $entityManager->persist($estimate);
        $entityManager->persist($invoice);
        $entityManager->flush();

        $browser->request('GET', '/sales/document/'.$invoice->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Pré-estimation liée');
        self::assertSelectorTextContains('body', 'Cette facture est regroupée avec les autres documents de cette pré-estimation.');
        self::assertSelectorExists(\sprintf('a[href="/estimate/%d"]', $estimate->getId()));
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())->setName('Sales Document Company '.bin2hex(random_bytes(4)));
        $user = (new User())
            ->setEmail('sales-document-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('unused')
            ->setCompany($company);

        $entityManager->persist($company);
        $entityManager->persist($user);
        $entityManager->flush();
        $browser->loginUser($user);

        return $browser;
    }

    private function createSalesDocumentItem(string $description): SalesDocumentItem
    {
        return (new SalesDocumentItem())
            ->setDescription($description)
            ->setQuantity('1.000')
            ->setUnitPrice('100.00')
            ->setLineTotal('100.00');
    }
}

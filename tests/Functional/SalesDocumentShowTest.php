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

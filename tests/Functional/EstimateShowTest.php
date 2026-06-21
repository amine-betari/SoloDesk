<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Estimate;
use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EstimateShowTest extends WebTestCase
{
    public function testAcceptedEstimateShowsQuickInvoiceActionForLinkedCommercialEstimate(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Estimate Show Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $estimate = (new Estimate())
            ->setName('Estimate show '.bin2hex(random_bytes(4)))
            ->setClient($client)
            ->setEstimateNumber('EST-SHOW-'.bin2hex(random_bytes(3)))
            ->setAmount('42200.00')
            ->setStatus('accepted');
        $estimate->setCurrency('MAD');

        $commercialEstimate = (new SalesDocument())
            ->setType(SalesDocument::TYPE_ESTIMATE)
            ->setReference('EST-SHOW-DOC-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus('accepted');
        $commercialEstimate->addSalesDocumentItem($this->createSalesDocumentItem('Commercial estimate line', '42200.00'));

        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference('INV-SHOW-DOC-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus('sent');
        $invoice->addSalesDocumentItem($this->createSalesDocumentItem('Invoice line', '20000.00'));

        $entityManager->persist($client);
        $entityManager->persist($estimate);
        $entityManager->persist($commercialEstimate);
        $entityManager->persist($invoice);
        $entityManager->flush();
        $entityManager->clear();

        $browser->request('GET', '/estimate/'.$estimate->getId());

        self::assertResponseIsSuccessful();
        self::assertSelectorExists(\sprintf('a[href="/sales/document/sales-document/from-estimate/%d"]', $commercialEstimate->getId()));
        self::assertSelectorTextContains('body', 'Créer facture');
        self::assertSelectorTextContains('body', 'Suivi de facturation');
        self::assertSelectorTextContains('body', 'Partiellement facturé');
        self::assertSelectorTextContains('body', 'Déjà facturé');
        self::assertSelectorTextContains('body', 'Reste à facturer');
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())->setName('Estimate Show Company '.bin2hex(random_bytes(4)));
        $user = (new User())
            ->setEmail('estimate-show-'.bin2hex(random_bytes(6)).'@example.com')
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

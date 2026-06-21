<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Constants\InvoiceStatus;
use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Estimate;
use App\Entity\SalesDocument;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class EstimateIndexTest extends WebTestCase
{
    public function testEstimateIndexShowsLinkedSalesDocumentCounts(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Estimate Index Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $estimate = (new Estimate())
            ->setName('Estimate with linked documents '.bin2hex(random_bytes(4)))
            ->setClient($client)
            ->setEstimateNumber('EST-INDEX-'.bin2hex(random_bytes(3)))
            ->setAmount('42200.00')
            ->setStatus('accepted');
        $estimate->setCurrency('MAD');

        $commercialEstimate = (new SalesDocument())
            ->setType(SalesDocument::TYPE_ESTIMATE)
            ->setReference('EST-DOC-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus('accepted');

        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference('INV-DOC-'.bin2hex(random_bytes(3)))
            ->setEstimate($estimate)
            ->setStatus(InvoiceStatus::SENT);

        $entityManager->persist($client);
        $entityManager->persist($estimate);
        $entityManager->persist($commercialEstimate);
        $entityManager->persist($invoice);
        $entityManager->flush();
        $entityManager->clear();

        $browser->request('GET', '/estimate');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', 'Devis : 1');
        self::assertSelectorTextContains('table', 'Factures : 1');
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())->setName('Estimate Index Company '.bin2hex(random_bytes(4)));
        $user = (new User())
            ->setEmail('estimate-index-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('unused')
            ->setCompany($company);

        $entityManager->persist($company);
        $entityManager->persist($user);
        $entityManager->flush();
        $browser->loginUser($user);

        return $browser;
    }
}

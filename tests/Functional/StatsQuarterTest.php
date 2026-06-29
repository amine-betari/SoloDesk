<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Constants\InvoiceStatus;
use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Payment;
use App\Entity\SalesDocument;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class StatsQuarterTest extends WebTestCase
{
    public function testQuarterStatsSummaryIsCollapsedAndShowsBestYearFromRevenueCalculation(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $client = (new Client())
            ->setName('Stats Quarter Client '.bin2hex(random_bytes(4)))
            ->setCompany($company);
        $client->setCurrency('MAD');

        $invoice2025 = $this->createPaidInvoice($company, $client, 'STATS-Q-2025-'.bin2hex(random_bytes(3)));
        $invoice2026 = $this->createPaidInvoice($company, $client, 'STATS-Q-2026-'.bin2hex(random_bytes(3)));

        $payment2025 = (new Payment())
            ->setSalesDocument($invoice2025)
            ->setDate(new \DateTimeImmutable('2025-06-15'))
            ->setAmount('100.00')
            ->setMethod('transfer');
        $payment2026 = (new Payment())
            ->setSalesDocument($invoice2026)
            ->setDate(new \DateTimeImmutable('2026-06-15'))
            ->setAmount('250.00')
            ->setMethod('transfer');

        $entityManager->persist($client);
        $entityManager->persist($invoice2025);
        $entityManager->persist($invoice2026);
        $entityManager->persist($payment2025);
        $entityManager->persist($payment2026);
        $entityManager->flush();

        $crawler = $browser->request('GET', '/stats/ca/trimestre?annee=2026');

        self::assertResponseIsSuccessful();
        self::assertCount(1, $crawler->filter('details > summary'));
        self::assertSelectorTextContains('summary', 'CA total');
        self::assertSelectorTextContains('summary', 'Gain total');
        self::assertSelectorTextContains('summary', 'CA 2026');
        self::assertSelectorTextContains('summary', 'dont gain avant impôt');
        self::assertSelectorTextContains('summary', 'Externes');
        self::assertSelectorTextContains('summary', 'Prestations');
        self::assertSelectorTextNotContains('summary', 'Charges suivies');
        self::assertSelectorTextNotContains('summary', 'Résultat estimé après charges');
        self::assertSelectorTextNotContains('summary', 'Meilleure année');
        self::assertSelectorTextContains('details', 'Meilleure année');
        self::assertSelectorTextContains('details', 'MAD : 2026');
        self::assertSelectorTextContains('details', 'Gain estimé avant impôt');
        self::assertSelectorTextContains('details', 'CA encaissé par année');
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())
            ->setName('Stats Quarter Company '.bin2hex(random_bytes(4)))
            ->setLegalForm('AE');
        $user = (new User())
            ->setEmail('stats-quarter-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('unused')
            ->setCompany($company);

        $entityManager->persist($company);
        $entityManager->persist($user);
        $entityManager->flush();
        $browser->loginUser($user);

        return $browser;
    }

    private function createPaidInvoice(Company $company, Client $client, string $reference): SalesDocument
    {
        return (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference($reference)
            ->setCompany($company)
            ->setClient($client)
            ->setStatus(InvoiceStatus::PAID);
    }
}

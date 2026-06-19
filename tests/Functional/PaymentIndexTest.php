<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Constants\InvoiceStatus;
use App\Entity\Client;
use App\Entity\Company;
use App\Entity\Payment;
use App\Entity\SalesDocument;
use App\Entity\SalesDocumentItem;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class PaymentIndexTest extends WebTestCase
{
    public function testPaymentIndexShowsAndFiltersByClient(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $selectedClient = $this->createClientWithPayment(
            $entityManager,
            $company,
            'Payment Client Selected '.bin2hex(random_bytes(4)),
            'INV-FILTER-A-'.bin2hex(random_bytes(3)),
            'Selected payment'
        );
        $otherClient = $this->createClientWithPayment(
            $entityManager,
            $company,
            'Payment Client Other '.bin2hex(random_bytes(4)),
            'INV-FILTER-B-'.bin2hex(random_bytes(3)),
            'Other payment'
        );

        $crawler = $browser->request('GET', '/payment');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', $selectedClient->getName());
        self::assertSelectorTextContains('table', $otherClient->getName());

        $csrfToken = $crawler->filter('form[name="payment_filter_form"] input[name="payment_filter_form[_token]"]')->attr('value');
        self::assertNotNull($csrfToken);

        $browser->request('POST', '/payment', [
            'payment_filter_form' => [
                'salesDocument' => '',
                'client' => (string) $selectedClient->getId(),
                'date' => '',
                'search' => '',
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', $selectedClient->getName());
        self::assertSelectorTextNotContains('table', $otherClient->getName());
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())->setName('Payment Filter Company '.bin2hex(random_bytes(4)));
        $user = (new User())
            ->setEmail('payment-filter-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('unused')
            ->setCompany($company);

        $entityManager->persist($company);
        $entityManager->persist($user);
        $entityManager->flush();
        $browser->loginUser($user);

        return $browser;
    }

    private function createClientWithPayment(
        EntityManagerInterface $entityManager,
        Company $company,
        string $clientName,
        string $invoiceReference,
        string $paymentLabel
    ): Client {
        $client = (new Client())
            ->setName($clientName)
            ->setCompany($company);
        $client->setCurrency('EUR');

        $invoice = (new SalesDocument())
            ->setType(SalesDocument::TYPE_INVOICE)
            ->setReference($invoiceReference)
            ->setClient($client)
            ->setStatus(InvoiceStatus::PAID);

        $invoice->addSalesDocumentItem(
            (new SalesDocumentItem())
                ->setDescription('Service')
                ->setQuantity('1.000')
                ->setUnitPrice('100.00')
                ->setLineTotal('100.00')
        );

        $payment = (new Payment())
            ->setSalesDocument($invoice)
            ->setDate(new \DateTimeImmutable('2026-06-19'))
            ->setAmount('100.00')
            ->setMethod('transfer')
            ->setLabel($paymentLabel);

        $entityManager->persist($client);
        $entityManager->persist($invoice);
        $entityManager->persist($payment);
        $entityManager->flush();

        return $client;
    }
}

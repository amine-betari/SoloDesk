<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Company;
use App\Entity\Expense;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class ExpenseFlowTest extends WebTestCase
{
    public function testExpenseCanBeCreatedAndAppearsInList(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $crawler = $browser->request('GET', '/expenses/new');
        $csrfToken = $crawler->filter('form#expense_form input[name="expense_form[_token]"]')->attr('value');
        $label = 'Expense VPS '.bin2hex(random_bytes(4));

        self::assertResponseIsSuccessful();
        self::assertNotNull($csrfToken);

        $browser->request('POST', '/expenses/new', [
            'expense_form' => [
                'spentAt' => '2026-06-22',
                'label' => $label,
                'amount' => '120.50',
                'currency' => 'MAD',
                'category' => Expense::CATEGORY_HOSTING,
                'supplier' => 'VPS Provider',
                'notes' => 'Tracked hosting cost',
                '_token' => $csrfToken,
            ],
        ]);

        self::assertResponseRedirects('/expenses');
        $expense = $entityManager->getRepository(Expense::class)->findOneBy(['label' => $label]);
        self::assertInstanceOf(Expense::class, $expense);
        self::assertSame('MAD', $expense->getCurrency());
        self::assertSame(Expense::CATEGORY_HOSTING, $expense->getCategory());

        $browser->followRedirect();

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('table', $label);
        self::assertSelectorTextContains('table', 'VPS Provider');
    }

    public function testExpenseIndexIsScopedByCompany(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $visibleLabel = 'Visible expense '.bin2hex(random_bytes(4));
        $hiddenLabel = 'Hidden expense '.bin2hex(random_bytes(4));
        $otherCompany = (new Company())->setName('Other Expense Company '.bin2hex(random_bytes(4)));

        $visibleExpense = (new Expense())
            ->setCompany($company)
            ->setSpentAt(new \DateTimeImmutable('2026-06-22'))
            ->setLabel($visibleLabel)
            ->setAmount('50.00')
            ->setCurrency('MAD')
            ->setCategory(Expense::CATEGORY_BANK);
        $hiddenExpense = (new Expense())
            ->setCompany($otherCompany)
            ->setSpentAt(new \DateTimeImmutable('2026-06-22'))
            ->setLabel($hiddenLabel)
            ->setAmount('50.00')
            ->setCurrency('MAD')
            ->setCategory(Expense::CATEGORY_BANK);

        $entityManager->persist($otherCompany);
        $entityManager->persist($visibleExpense);
        $entityManager->persist($hiddenExpense);
        $entityManager->flush();

        $browser->request('GET', '/expenses');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', $visibleLabel);
        self::assertSelectorTextNotContains('body', $hiddenLabel);
    }

    public function testExpenseCanBeDuplicatedIntoPrefilledCreationForm(): void
    {
        $browser = $this->createAuthenticatedBrowser();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $user = self::getContainer()->get('security.token_storage')->getToken()?->getUser();
        self::assertInstanceOf(User::class, $user);
        $company = $user->getCompany();
        self::assertInstanceOf(Company::class, $company);

        $sourceExpense = (new Expense())
            ->setCompany($company)
            ->setSpentAt(new \DateTimeImmutable('2026-06-22'))
            ->setLabel('Duplicated expense '.bin2hex(random_bytes(4)))
            ->setAmount('89.90')
            ->setCurrency('EUR')
            ->setCategory(Expense::CATEGORY_SUBSCRIPTION)
            ->setSupplier('Duplicated Supplier')
            ->setNotes('Duplicated notes');

        $entityManager->persist($sourceExpense);
        $entityManager->flush();
        $sourceId = $sourceExpense->getId();
        self::assertIsInt($sourceId);

        $expenseCountBefore = $entityManager->getRepository(Expense::class)->count(['company' => $company]);
        $crawler = $browser->request('GET', '/expenses/new?duplicate='.$sourceId);

        self::assertResponseIsSuccessful();
        self::assertSame($sourceExpense->getLabel(), $crawler->filter('#expense_form_label')->attr('value'));
        self::assertSame('89.90', $crawler->filter('#expense_form_amount')->attr('value'));
        self::assertSame('EUR', $crawler->filter('#expense_form_currency option[selected]')->attr('value'));
        self::assertSame(Expense::CATEGORY_SUBSCRIPTION, $crawler->filter('#expense_form_category option[selected]')->attr('value'));
        self::assertSame('Duplicated Supplier', $crawler->filter('#expense_form_supplier')->attr('value'));
        self::assertStringContainsString('Duplicated notes', $crawler->filter('#expense_form_notes')->text());
        self::assertSame($expenseCountBefore, $entityManager->getRepository(Expense::class)->count(['company' => $company]));
    }

    private function createAuthenticatedBrowser(): KernelBrowser
    {
        $browser = static::createClient();
        $entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $company = (new Company())->setName('Expense Company '.bin2hex(random_bytes(4)));
        $user = (new User())
            ->setEmail('expense-'.bin2hex(random_bytes(6)).'@example.com')
            ->setPassword('unused')
            ->setCompany($company);

        $entityManager->persist($company);
        $entityManager->persist($user);
        $entityManager->flush();
        $browser->loginUser($user);

        return $browser;
    }
}

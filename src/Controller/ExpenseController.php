<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Company;
use App\Entity\Expense;
use App\Form\ExpenseForm;
use App\Repository\ExpenseRepository;
use App\Repository\PaginationService;
use App\Repository\PaymentRepository;
use App\Repository\PrestationRepository;
use App\Service\CompanySettings;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/expenses')]
final class ExpenseController extends AbstractController
{
    #[Route(name: 'app_expense_index', methods: ['GET'])]
    public function index(
        ExpenseRepository $expenseRepository,
        PaymentRepository $paymentRepository,
        PrestationRepository $prestationRepository,
        CompanySettings $settings,
        Request $request,
        PaginationService $paginator
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 30;
        $q = trim((string) $request->query->get('q', ''));
        $category = trim((string) $request->query->get('category', ''));
        $dateFromRaw = trim((string) $request->query->get('dateFrom', ''));
        $dateToRaw = trim((string) $request->query->get('dateTo', ''));
        $dateFrom = $this->parseDate($dateFromRaw);
        $dateTo = $this->parseDate($dateToRaw);

        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }
        $activityStartDate = $settings->getDate($company, CompanySettings::KEY_ACTIVITY_START_DATE, new \DateTimeImmutable('2017-01-01'));
        $firstAvailableYear = (int) $activityStartDate->format('Y');
        $currentYear = (int) (new \DateTimeImmutable())->format('Y');
        $availableSummaryYears = range($firstAvailableYear, $currentYear);
        $selectedSummaryYear = $request->query->getInt('summaryYear', $currentYear);
        if (!\in_array($selectedSummaryYear, $availableSummaryYears, true)) {
            $selectedSummaryYear = $currentYear;
        }

        $currentYearSummary = $this->buildYearSummary(
            $company,
            $expenseRepository,
            $paymentRepository,
            $prestationRepository,
            $activityStartDate,
            $selectedSummaryYear
        );

        $qb = $expenseRepository->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->setParameter('company', $company)
            ->orderBy('e.spentAt', 'DESC')
            ->addOrderBy('e.id', 'DESC');

        if ($q !== '') {
            $qb->andWhere('e.label LIKE :q OR e.supplier LIKE :q OR e.notes LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if (\in_array($category, Expense::CATEGORIES, true)) {
            $qb->andWhere('e.category = :category')
                ->setParameter('category', $category);
        } else {
            $category = '';
        }

        if ($dateFrom instanceof \DateTimeImmutable) {
            $qb->andWhere('e.spentAt >= :dateFrom')
                ->setParameter('dateFrom', $dateFrom);
        } else {
            $dateFromRaw = '';
        }

        if ($dateTo instanceof \DateTimeImmutable) {
            $qb->andWhere('e.spentAt <= :dateTo')
                ->setParameter('dateTo', $dateTo);
        } else {
            $dateToRaw = '';
        }

        $filteredTotals = [];
        $totalRows = (clone $qb)
            ->select('e.currency AS currency, SUM(e.amount) AS total')
            ->resetDQLPart('orderBy')
            ->groupBy('e.currency')
            ->getQuery()
            ->getArrayResult();
        foreach ($totalRows as $row) {
            $filteredTotals[(string) $row['currency']] = (float) $row['total'];
        }

        $pagination = $paginator->paginate($qb, $page, $limit);
        $displayedTotals = [];
        foreach ($pagination['items'] as $expense) {
            \assert($expense instanceof Expense);
            $displayedTotals[$expense->getCurrency()] = ($displayedTotals[$expense->getCurrency()] ?? 0.0) + (float) $expense->getAmount();
        }

        return $this->render('expense/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
            'category' => $category,
            'dateFrom' => $dateFromRaw,
            'dateTo' => $dateToRaw,
            'categories' => Expense::CATEGORIES,
            'filteredTotals' => $filteredTotals,
            'displayedTotals' => $displayedTotals,
            'currentYearSummary' => $currentYearSummary,
            'availableSummaryYears' => $availableSummaryYears,
            'selectedSummaryYear' => $selectedSummaryYear,
        ]);
    }

    #[Route('/new', name: 'app_expense_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        ExpenseRepository $expenseRepository
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $expense = (new Expense())->setCompany($company);
        $duplicateId = $request->query->getInt('duplicate');
        if ($duplicateId > 0) {
            $sourceExpense = $expenseRepository->find($duplicateId);
            if (!$sourceExpense instanceof Expense) {
                throw $this->createNotFoundException('Charge introuvable.');
            }

            $this->denyAccessUnlessCompanyOwns($sourceExpense);
            $expense = $this->createExpenseDraftFrom($sourceExpense);
        }

        $form = $this->createForm(ExpenseForm::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($expense);
            $entityManager->flush();

            return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('expense/new.html.twig', [
            'expense' => $expense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_expense_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Expense $expense): Response
    {
        $this->denyAccessUnlessCompanyOwns($expense);

        return $this->render('expense/show.html.twig', [
            'expense' => $expense,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_expense_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCompanyOwns($expense);

        $form = $this->createForm(ExpenseForm::class, $expense);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $expense->setUpdatedAt(new \DateTimeImmutable());
            $entityManager->flush();

            return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('expense/edit.html.twig', [
            'expense' => $expense,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/generate-monthly', name: 'app_expense_generate_monthly', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function generateMonthly(
        Request $request,
        Expense $expense,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response {
        $this->denyAccessUnlessCompanyOwns($expense);

        if (!$this->isCsrfTokenValid('generate_monthly'.$expense->getId(), $request->getPayload()->getString('_token'))) {
            $this->addFlash('error', $translator->trans('expense.generate_invalid_token'));

            return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
        }

        $months = $request->getPayload()->getInt('months', 12);
        if (!\in_array($months, [3, 6, 12], true)) {
            $this->addFlash('error', $translator->trans('expense.generate_invalid_months'));

            return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
        }

        $createdCount = 0;
        for ($monthOffset = 1; $monthOffset <= $months; ++$monthOffset) {
            $spentAt = $this->getClampedMonthlyDate($expense->getSpentAt(), $monthOffset);
            if ($this->monthlyExpenseExists($expense, $spentAt, $entityManager)) {
                continue;
            }

            $entityManager->persist($this->createMonthlyExpenseFrom($expense, $spentAt));
            ++$createdCount;
        }

        $entityManager->flush();
        $this->addFlash('success', $translator->trans('expense.generate_success', ['%count%' => $createdCount]));

        return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}', name: 'app_expense_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Expense $expense, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessCompanyOwns($expense);

        if ($this->isCsrfTokenValid('delete'.$expense->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($expense);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_expense_index', [], Response::HTTP_SEE_OTHER);
    }

    private function denyAccessUnlessCompanyOwns(Expense $expense): void
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $expense->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
    }

    private function parseDate(string $date): ?\DateTimeImmutable
    {
        if ($date === '') {
            return null;
        }

        $parsed = \DateTimeImmutable::createFromFormat('!Y-m-d', $date);

        return $parsed ?: null;
    }

    private function createExpenseDraftFrom(Expense $sourceExpense): Expense
    {
        return (new Expense())
            ->setCompany($sourceExpense->getCompany())
            ->setSpentAt($sourceExpense->getSpentAt())
            ->setLabel((string) $sourceExpense->getLabel())
            ->setAmount($sourceExpense->getAmount())
            ->setCurrency($sourceExpense->getCurrency())
            ->setCategory($sourceExpense->getCategory())
            ->setSupplier($sourceExpense->getSupplier())
            ->setNotes($sourceExpense->getNotes());
    }

    private function createMonthlyExpenseFrom(Expense $sourceExpense, \DateTimeImmutable $spentAt): Expense
    {
        return (new Expense())
            ->setCompany($sourceExpense->getCompany())
            ->setSpentAt($spentAt)
            ->setLabel((string) $sourceExpense->getLabel())
            ->setAmount($sourceExpense->getAmount())
            ->setCurrency($sourceExpense->getCurrency())
            ->setCategory($sourceExpense->getCategory())
            ->setSupplier($sourceExpense->getSupplier())
            ->setNotes($sourceExpense->getNotes());
    }

    private function getClampedMonthlyDate(\DateTimeImmutable $sourceDate, int $monthOffset): \DateTimeImmutable
    {
        $targetMonth = $sourceDate
            ->modify('first day of this month')
            ->modify(sprintf('+%d months', $monthOffset));
        $day = min((int) $sourceDate->format('d'), (int) $targetMonth->format('t'));

        return $targetMonth
            ->setDate((int) $targetMonth->format('Y'), (int) $targetMonth->format('m'), $day)
            ->setTime(
                (int) $sourceDate->format('H'),
                (int) $sourceDate->format('i'),
                (int) $sourceDate->format('s')
            );
    }

    private function monthlyExpenseExists(
        Expense $sourceExpense,
        \DateTimeImmutable $spentAt,
        EntityManagerInterface $entityManager
    ): bool {
        return $entityManager->getRepository(Expense::class)->findOneBy([
            'company' => $sourceExpense->getCompany(),
            'label' => $sourceExpense->getLabel(),
            'supplier' => $sourceExpense->getSupplier(),
            'amount' => $sourceExpense->getAmount(),
            'currency' => $sourceExpense->getCurrency(),
            'spentAt' => $spentAt,
        ]) instanceof Expense;
    }

    /**
     * @return array{
     *     year: int,
     *     ca: array<string, float>,
     *     gainBeforeCharges: array<string, float>,
     *     expenses: array<string, float>,
     *     gainAfterCharges: array<string, float>
     * }
     */
    private function buildYearSummary(
        Company $company,
        ExpenseRepository $expenseRepository,
        PaymentRepository $paymentRepository,
        PrestationRepository $prestationRepository,
        \DateTimeImmutable $activityStartDate,
        int $year
    ): array {
        $yearStart = new \DateTimeImmutable($year.'-01-01 00:00:00');
        $yearEnd = new \DateTimeImmutable($year.'-12-31 23:59:59');
        $effectiveStart = $activityStartDate > $yearStart ? $activityStartDate : $yearStart;

        $caByCurrency = [];
        $externalInvoicesByCurrency = [];
        foreach ($paymentRepository->findPaymentsForReports($company) as $payment) {
            $paymentDate = $payment->getDate();
            if ($paymentDate < $effectiveStart || $paymentDate > $yearEnd) {
                continue;
            }

            $salesDocument = $payment->getSalesDocument();
            $project = $salesDocument?->getProject();
            $client = $salesDocument?->getClient() ?? $project?->getClient();
            if (!$client) {
                continue;
            }

            $currency = $client->getCurrency() ?? $project?->getCurrency() ?? 'EUR';
            $amount = (float) $payment->getAmount();
            $caByCurrency[$currency] = ($caByCurrency[$currency] ?? 0.0) + $amount;
            if ($salesDocument?->isExternalInvoice() === true) {
                $externalInvoicesByCurrency[$currency] = ($externalInvoicesByCurrency[$currency] ?? 0.0) + $amount;
            }
        }

        $prestationsByCurrency = [];
        $prestations = $prestationRepository->createQueryBuilder('p')
            ->innerJoin('p.salesDocument', 'sd')
            ->andWhere('p.company = :company')
            ->andWhere('sd.company = :company')
            ->andWhere('COALESCE(sd.invoiceDate, sd.createdAt) >= :start')
            ->andWhere('COALESCE(sd.invoiceDate, sd.createdAt) <= :end')
            ->setParameter('company', $company)
            ->setParameter('start', $effectiveStart)
            ->setParameter('end', $yearEnd)
            ->getQuery()
            ->getResult();

        foreach ($prestations as $prestation) {
            $salesDocument = $prestation->getSalesDocument();
            if (!$salesDocument) {
                continue;
            }

            $currency = $salesDocument->getResolvedCurrency();
            $prestationsByCurrency[$currency] = ($prestationsByCurrency[$currency] ?? 0.0) + $prestation->getTotal();
        }

        $expensesByCurrency = [];
        $expenseRows = $expenseRepository->createQueryBuilder('e')
            ->select('e.currency AS currency, SUM(e.amount) AS total')
            ->andWhere('e.company = :company')
            ->andWhere('e.spentAt >= :start')
            ->andWhere('e.spentAt <= :end')
            ->setParameter('company', $company)
            ->setParameter('start', $effectiveStart)
            ->setParameter('end', $yearEnd)
            ->groupBy('e.currency')
            ->getQuery()
            ->getArrayResult();
        foreach ($expenseRows as $row) {
            $expensesByCurrency[(string) $row['currency']] = (float) $row['total'];
        }

        $currencies = array_unique(array_merge(
            array_keys($caByCurrency),
            array_keys($externalInvoicesByCurrency),
            array_keys($prestationsByCurrency),
            array_keys($expensesByCurrency)
        ));

        $gainBeforeChargesByCurrency = [];
        $gainAfterChargesByCurrency = [];
        foreach ($currencies as $currency) {
            $gainBeforeCharges = ($caByCurrency[$currency] ?? 0.0)
                - ($externalInvoicesByCurrency[$currency] ?? 0.0)
                - ($prestationsByCurrency[$currency] ?? 0.0);
            $gainBeforeChargesByCurrency[$currency] = $gainBeforeCharges;
            $gainAfterChargesByCurrency[$currency] = $gainBeforeCharges - ($expensesByCurrency[$currency] ?? 0.0);
        }

        return [
            'year' => $year,
            'ca' => $caByCurrency,
            'gainBeforeCharges' => $gainBeforeChargesByCurrency,
            'expenses' => $expensesByCurrency,
            'gainAfterCharges' => $gainAfterChargesByCurrency,
        ];
    }
}

<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Expense;
use App\Form\ExpenseForm;
use App\Repository\ExpenseRepository;
use App\Repository\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/expenses')]
final class ExpenseController extends AbstractController
{
    #[Route(name: 'app_expense_index', methods: ['GET'])]
    public function index(
        ExpenseRepository $expenseRepository,
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
}

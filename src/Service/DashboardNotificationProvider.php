<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Company;
use App\Entity\Project;
use App\Entity\SalesDocument;
use App\Repository\ProjectRepository;
use App\Repository\SalesDocumentRepository;

final readonly class DashboardNotificationProvider
{
    public const UPCOMING_DAYS = 7;
    public const UNANSWERED_ESTIMATE_DAYS = 14;

    public function __construct(
        private SalesDocumentRepository $salesDocumentRepository,
        private ProjectRepository $projectRepository
    ) {
    }

    /**
     * @return array{
     *     invoices: array<int, array{document: SalesDocument, dueDate: \DateTimeImmutable}>,
     *     estimates: SalesDocument[],
     *     projects: Project[],
     *     total: int
     * }
     */
    public function getNotifications(
        Company $company,
        int $paymentTermDays,
        ?\DateTimeImmutable $now = null
    ): array {
        $today = ($now ?? new \DateTimeImmutable())->setTime(0, 0);
        $rangeEnd = $today->modify(sprintf('+%d days', self::UPCOMING_DAYS + 1));
        $invoiceStartDate = $today->modify(sprintf('-%d days', $paymentTermDays));
        $invoiceEndDate = $rangeEnd->modify(sprintf('-%d days', $paymentTermDays));

        $invoices = [];
        foreach ($this->salesDocumentRepository->findInvoicesIssuedBetweenWithOutstandingBalance(
            $company,
            $invoiceStartDate,
            $invoiceEndDate
        ) as $invoice) {
            if ($invoice->getBalanceDue() <= 0.0 || !$invoice->getInvoiceDate()) {
                continue;
            }

            $invoices[] = [
                'document' => $invoice,
                'dueDate' => $invoice->getInvoiceDate()->modify(sprintf('+%d days', $paymentTermDays)),
            ];
        }

        $estimates = $this->salesDocumentRepository->findSentEstimatesIssuedBefore(
            $company,
            $today->modify(sprintf('-%d days', self::UNANSWERED_ESTIMATE_DAYS))
        );
        $projects = $this->projectRepository->findEndingBetween($company, $today, $rangeEnd);

        return [
            'invoices' => $invoices,
            'estimates' => $estimates,
            'projects' => $projects,
            'total' => count($invoices) + count($estimates) + count($projects),
        ];
    }
}

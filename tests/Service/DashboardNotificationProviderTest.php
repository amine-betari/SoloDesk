<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Company;
use App\Entity\Project;
use App\Entity\SalesDocument;
use App\Repository\ProjectRepository;
use App\Repository\SalesDocumentRepository;
use App\Service\DashboardNotificationProvider;
use PHPUnit\Framework\TestCase;

final class DashboardNotificationProviderTest extends TestCase
{
    public function testGetNotificationsUsesExpectedDateRangesAndExcludesSettledInvoices(): void
    {
        $company = new Company();
        $now = new \DateTimeImmutable('2026-06-12 15:30:00');

        $outstandingInvoice = $this->createMock(SalesDocument::class);
        $outstandingInvoice->method('getBalanceDue')->willReturn(250.0);
        $outstandingInvoice->method('getInvoiceDate')->willReturn(new \DateTimeImmutable('2026-05-20'));

        $settledInvoice = $this->createMock(SalesDocument::class);
        $settledInvoice->method('getBalanceDue')->willReturn(0.0);
        $settledInvoice->method('getInvoiceDate')->willReturn(new \DateTimeImmutable('2026-05-21'));

        $estimate = $this->createMock(SalesDocument::class);
        $project = $this->createMock(Project::class);

        $salesDocumentRepository = $this->createMock(SalesDocumentRepository::class);
        $salesDocumentRepository
            ->expects(self::once())
            ->method('findInvoicesIssuedBetweenWithOutstandingBalance')
            ->with(
                $company,
                self::callback(static fn (\DateTimeInterface $date): bool => $date->format('Y-m-d H:i:s') === '2026-05-13 00:00:00'),
                self::callback(static fn (\DateTimeInterface $date): bool => $date->format('Y-m-d H:i:s') === '2026-05-21 00:00:00')
            )
            ->willReturn([$outstandingInvoice, $settledInvoice]);
        $salesDocumentRepository
            ->expects(self::once())
            ->method('findSentEstimatesIssuedBefore')
            ->with(
                $company,
                self::callback(static fn (\DateTimeInterface $date): bool => $date->format('Y-m-d H:i:s') === '2026-05-29 00:00:00')
            )
            ->willReturn([$estimate]);

        $projectRepository = $this->createMock(ProjectRepository::class);
        $projectRepository
            ->expects(self::once())
            ->method('findEndingBetween')
            ->with(
                $company,
                self::callback(static fn (\DateTimeInterface $date): bool => $date->format('Y-m-d H:i:s') === '2026-06-12 00:00:00'),
                self::callback(static fn (\DateTimeInterface $date): bool => $date->format('Y-m-d H:i:s') === '2026-06-20 00:00:00')
            )
            ->willReturn([$project]);

        $notifications = (new DashboardNotificationProvider(
            $salesDocumentRepository,
            $projectRepository
        ))->getNotifications($company, 30, $now);

        self::assertSame(3, $notifications['total']);
        self::assertCount(1, $notifications['invoices']);
        self::assertSame($outstandingInvoice, $notifications['invoices'][0]['document']);
        self::assertSame('2026-06-19', $notifications['invoices'][0]['dueDate']->format('Y-m-d'));
        self::assertSame([$estimate], $notifications['estimates']);
        self::assertSame([$project], $notifications['projects']);
    }
}

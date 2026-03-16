<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\SalesDocument;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/exports')]
final class ExportController extends AbstractController
{
    #[Route('/invoices.csv', name: 'app_exports_invoices', methods: ['GET'])]
    public function exportInvoices(EntityManagerInterface $entityManager): BinaryFileResponse
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $exportsDir = $this->resolveExportsDir();
        $filename = 'invoices.csv';
        $filepath = $exportsDir . DIRECTORY_SEPARATOR . $filename;

        $handle = fopen($filepath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Impossible de créer le fichier d\'export des factures.');
        }

        fputcsv($handle, [
            'id',
            'reference',
            'type',
            'invoice_date',
            'created_at',
            'total_ht',
            'total_ttc',
            'currency',
            'external',
        ]);

        $salesDocuments = $entityManager->getRepository(SalesDocument::class)
            ->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->andWhere('s.type IN (:types)')
            ->setParameter('company', $company)
            ->setParameter('types', [SalesDocument::TYPE_INVOICE, SalesDocument::TYPE_PROJECT])
            ->orderBy('s.invoiceDate', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($salesDocuments as $salesDocument) {
            fputcsv($handle, [
                $salesDocument->getId(),
                $salesDocument->getReference(),
                $salesDocument->getType(),
                $salesDocument->getInvoiceDate()?->format('Y-m-d'),
                $salesDocument->getCreatedAt()?->format('Y-m-d'),
                number_format($salesDocument->getTotalHT(), 2, '.', ''),
                number_format($salesDocument->getTotalTTC(), 2, '.', ''),
                $salesDocument->getResolvedCurrency(),
                $salesDocument->isExternalInvoice() ? '1' : '0',
            ]);
        }

        fclose($handle);

        return $this->buildDownloadResponse($filepath, $filename);
    }

    #[Route('/payments.csv', name: 'app_exports_payments', methods: ['GET'])]
    public function exportPayments(EntityManagerInterface $entityManager): BinaryFileResponse
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $exportsDir = $this->resolveExportsDir();
        $filename = 'payments.csv';
        $filepath = $exportsDir . DIRECTORY_SEPARATOR . $filename;

        $handle = fopen($filepath, 'wb');
        if ($handle === false) {
            throw new \RuntimeException('Impossible de créer le fichier d\'export des paiements.');
        }

        fputcsv($handle, [
            'id',
            'date',
            'amount',
            'method',
            'label',
            'invoice_reference',
            'sales_document_id',
            'sales_document_reference',
            'currency',
        ]);

        $payments = $entityManager->getRepository(Payment::class)
            ->createQueryBuilder('p')
            ->leftJoin('p.salesDocument', 's')
            ->andWhere('p.company = :company')
            ->setParameter('company', $company)
            ->orderBy('p.date', 'DESC')
            ->getQuery()
            ->getResult();

        foreach ($payments as $payment) {
            $salesDocument = $payment->getSalesDocument();
            $currency = $salesDocument?->getResolvedCurrency() ?? 'EUR';

            fputcsv($handle, [
                $payment->getId(),
                $payment->getDate()?->format('Y-m-d'),
                number_format((float) $payment->getAmount(), 2, '.', ''),
                $payment->getMethod(),
                $payment->getLabel(),
                $payment->getInvoiceReference(),
                $salesDocument?->getId(),
                $salesDocument?->getReference(),
                $currency,
            ]);
        }

        fclose($handle);

        return $this->buildDownloadResponse($filepath, $filename);
    }

    private function resolveExportsDir(): string
    {
        $exportsDir = $_ENV['EXPORTS_DIR'] ?? '/home/amine/exports';
        if (!is_dir($exportsDir)) {
            if (!mkdir($exportsDir, 0775, true) && !is_dir($exportsDir)) {
                throw new \RuntimeException('Impossible de créer le dossier d\'exports.');
            }
        }

        return rtrim($exportsDir, DIRECTORY_SEPARATOR);
    }

    private function buildDownloadResponse(string $filepath, string $filename): BinaryFileResponse
    {
        $response = new BinaryFileResponse($filepath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $filename);
        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');

        return $response;
    }
}

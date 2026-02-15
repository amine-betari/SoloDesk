<?php

namespace App\Controller;

use App\Entity\Estimate;
use App\Entity\Project;
use App\Entity\SalesDocument;
use App\Form\SalesDocumentForm;
use App\Repository\PaginationService;
use App\Helper\ToolsHelper;
use App\Repository\SalesDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;

use App\Form\Search\SalesDocumentFilterForm;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Services\FilterService;
use PhpOffice\PhpWord\TemplateProcessor;
use App\Entity\DocumentTemplate;

#[Route('/sales/document')]
final class SalesDocumentController extends AbstractController
{
    public function __construct(
        protected ToolsHelper $toolsHelper,
        protected EntityManagerInterface $entityManager
    ) {

    }
    #[Route(name: 'app_sales_document_index')]
    public function index(
        SalesDocumentRepository $salesDocumentRepository,
        Request $request,
        PaginationService $paginator,
        FilterService $filterService,
        \App\Service\CompanySettings $settings
    ): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $session = $request->getSession();

        // Search Form
        $filterForm = $this->createForm(SalesDocumentFilterForm::class);
        // Search Form

        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $activityStartDate = $settings->getDate($company, \App\Service\CompanySettings::KEY_ACTIVITY_START_DATE, new \DateTimeImmutable('2017-01-01'));

        $qb = $salesDocumentRepository->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->andWhere('COALESCE(s.invoiceDate, s.createdAt) >= :start')
            ->setParameter('company', $company)
            ->setParameter('start', $activityStartDate)
            ->orderBy('s.invoiceDate', 'DESC');

        // Handle Generic
        $filterForm = $filterService->handle(
            $request,
            $qb,
            $filterForm,
            $session,
            'document_sales_filter',
            'app_sales_document_index'
        );

        // Redirection si reset
        if ($filterForm->isSubmitted() && $filterForm->get('reset')->isClicked()) {
            return $this->redirectToRoute('app_sales_document_index');
        }

        $pagination = $paginator->paginate($qb, $page, $limit);

        return $this->render('sales_document/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(), // on envoie le form à la vu
        ]);
    }

    #[Route('/new', name: 'app_sales_document_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $salesDocument = new SalesDocument();
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $salesDocument->setCompany($company);
        }

        $form = $this->createForm(SalesDocumentForm::class, $salesDocument);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            if ($salesDocument->getVatRate() > 0) {
                $salesDocument->setTaxApplied(true);
            } else {
                $salesDocument->setTaxApplied(false);
            }

            $entityManager->persist($salesDocument);
            $entityManager->flush();

            return $this->redirectToRoute('app_sales_document_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sales_document/new.html.twig', [
            'sales_document' => $salesDocument,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sales_document_show', methods: ['GET'])]
    public function show(SalesDocument $salesDocument): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $salesDocument->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        return $this->render('sales_document/show.html.twig', [
            'sales_document' => $salesDocument,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sales_document_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SalesDocument $salesDocument, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $salesDocument->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        $form = $this->createForm(SalesDocumentForm::class, $salesDocument);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la date de modification
            $salesDocument->setModifiedAt(new \DateTimeImmutable());
            if ($salesDocument->getVatRate() > 0) {
                $salesDocument->setTaxApplied(true);
            } else {
                $salesDocument->setTaxApplied(false);
            }

            $entityManager->flush();

            return $this->redirectToRoute('app_sales_document_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sales_document/edit.html.twig', [
            'sales_document' => $salesDocument,
            'salesDocument' => $salesDocument,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sales_document_delete', methods: ['POST'])]
    public function delete(Request $request, SalesDocument $salesDocument, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $salesDocument->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        if (!$salesDocument->getPayments()->isEmpty()) {
            $this->addFlash('error', 'Impossible de supprimer : des paiements existent.');
            return $this->redirectToRoute('app_sales_document_show', ['id' => $salesDocument->getId()]);
        }

        if ($this->isCsrfTokenValid('delete'.$salesDocument->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($salesDocument);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_sales_document_index', [], Response::HTTP_SEE_OTHER);
    }


    #[Route('/sales-document/new', name: 'app_sales_document_new')]
    public function generateQuote(Request $request, EntityManagerInterface $em): Response
    {
        $salesDocument = new SalesDocument();

        $estimateId = $request->query->get('estimateId');
        $projectId = $request->query->get('projectId');

        if ($estimateId) {
            $estimate = $em->getRepository(Estimate::class)->find($estimateId);
            if ($estimate) {
                $salesDocument->setEstimate($estimate);
                $salesDocument->setType("estimate");
                $salesDocument->setReference($estimate->getEstimateNumber());
                $this->applyVatFromRate($salesDocument, $estimate->getVatRate());
                $salesDocument->setStatus($estimate->getStatus());
                $salesDocument->setInvoiceDate($estimate->getStartDate());

            }
        } elseif ($projectId) {
            $project = $em->getRepository(Project::class)->find($projectId);
            if ($project) {
                $salesDocument->setProject($project);
                $salesDocument->setType("invoice");
                $salesDocument->setReference($project->getProjectNumber());
                $this->applyVatFromRate($salesDocument, $project->getVatRate());
            }
        } else {
            $salesDocument->setType("invoice");
            $salesDocument->setReference('INV-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3))));
            // Display les deux champs dans le formulaire (TVA ou non and Taux TVA) car tu dépend plus de projets mais il s'agit d'une facture directe
            $this->applyVatFromRate($salesDocument, 0.0);
            $company = $this->getUser()?->getCompany();
            if ($company) {
                $salesDocument->setCompany($company);
            }
        }

        $form = $this->createForm(SalesDocumentForm::class, $salesDocument);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($salesDocument);
            $em->flush();

            return $this->redirectToRoute('app_sales_document_show', [
                'id' => $salesDocument->getId(),
            ]);
        }

        return $this->render('sales_document/new.html.twig', [
            'form' => $form,
            'salesDocument' => $salesDocument
        ]);
    }


    #[Route('/sales-document/{id}/word', name: 'app_sales_document_generate_word')]
    public function generateWord(SalesDocument $salesDocument): Response
    {
        $templatePath = $this->getWordTemplatePath($salesDocument);
        if ($templatePath) {
            $template = new TemplateProcessor($templatePath);
            $data = $this->buildTemplateData($salesDocument);

            $logoRelativePath = $data['scalar']['company_logo'] ?? '';
            $logoAbsolutePath = null;
            if ($logoRelativePath) {
                $publicDir = $this->getParameter('kernel.project_dir') . '/public';
                $candidate = $publicDir . '/' . ltrim($logoRelativePath, '/');
                if (is_file($candidate)) {
                    $logoAbsolutePath = $candidate;
                }
            }

            foreach ($data['scalar'] as $key => $value) {
                if ($key === 'company_logo') {
                    if ($logoAbsolutePath) {
                        $template->setImageValue('company_logo', [
                            'path' => $logoAbsolutePath,
                            'width' => 140,
                            'height' => 60,
                            'ratio' => true,
                        ]);
                    } else {
                        $template->setValue('company_logo', '');
                    }
                    continue;
                }
                $template->setValue($key, $value);
            }

            $items = $data['items'];
            if (count($items) > 0) {
                $template->cloneRow('item_description', count($items));
                foreach ($items as $i => $item) {
                    $index = $i + 1;
                    $template->setValue("item_description#{$index}", $item['description']);
                    $template->setValue("item_qty#{$index}", $item['quantity']);
                    $template->setValue("item_unit_price#{$index}", $item['unit_price']);
                    $template->setValue("item_total#{$index}", $item['total']);
                }
            }

            $safeReference = $this->sanitizeFilename((string) $salesDocument->getReference());
            $fileName = ($salesDocument->isEstimate() ? 'devis-' : 'facture-') . $safeReference . '.docx';
            $tempFile = tempnam(sys_get_temp_dir(), 'docx_');
            $template->saveAs($tempFile);

            return $this->file($tempFile, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
        }

        // fallback simple si aucun modèle Word
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $currency = $salesDocument->getEstimate()?->getCurrency()
            ?? $salesDocument->getProject()?->getCurrency()
            ?? 'EUR';

        $client = $salesDocument->getEstimate()?->getClient()
            ?? $salesDocument->getProject()?->getClient();


        $section->addTitle("Devis : " . $salesDocument->getReference(), 1);
        $section->addText( "Date : " . $salesDocument->getCreatedAt()->format('d/m/Y'));
        $section->addText( "Client : " . ($client ? $client->getName() : ''));

        $section->addTextBreak(1);
        $section->addText("Lignes d'article :", ['bold' => true]);

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        $table->addRow();
        $table->addCell(3000)->addText("Article");
        $table->addCell(2000)->addText("Quantité");
        $table->addCell(2000)->addText("Prix Unitaire");
        $table->addCell(2000)->addText("Total");

        foreach ($salesDocument->getSalesDocumentItems() as $item) {
            $table->addRow();
            $table->addCell(3000)->addText($item->getDescription());
            $table->addCell(2000)->addText($item->getQuantity());
            $table->addCell(2000)->addText($this->toolsHelper->formatCurrency($item->getUnitPrice(), $currency));
            $total = $item->getQuantity() * $item->getUnitPrice();
            $table->addCell(2000)->addText($this->toolsHelper->formatCurrency($total, $currency));
        }

        $section->addTextBreak(1);
        $section->addText("Total : " . number_format($salesDocument->getTotalTTC(), 2, ',', ' ') . ' €', ['bold' => true]);

        $safeReference = $this->sanitizeFilename((string) $salesDocument->getReference());
        $fileName = ($salesDocument->isEstimate() ? 'devis-' : 'facture-') . $safeReference . '.docx';

        $tempFile = tempnam(sys_get_temp_dir(), $fileName);
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return $this->file($tempFile, $fileName, ResponseHeaderBag::DISPOSITION_INLINE);
    }

    #[Route('/sales-document/{id}/excel', name: 'app_sales_document_generate_excel')]
    public function generateExcel(SalesDocument $salesDocument): Response
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle("Devis");

        $currency = $salesDocument->getEstimate()?->getCurrency()
            ?? $salesDocument->getProject()?->getCurrency()
            ?? 'EUR';

        $client = $salesDocument->getEstimate()?->getClient()
            ?? $salesDocument->getProject()?->getClient();

        $sheet->setCellValue('A1', 'Article');
        $sheet->setCellValue('B1', 'Quantité');
        $sheet->setCellValue('C1', 'Prix Unitaire (€)');
        $sheet->setCellValue('D1', 'Total (€)');

        $row = 2;
        foreach ($salesDocument->getSalesDocumentItems() as $item) {
            $sheet->setCellValue("A$row", $item->getDescription());
            $sheet->setCellValue("B$row", $item->getQuantity());
            $sheet->setCellValue("C$row", $this->toolsHelper->formatCurrency($item->getUnitPrice(), $currency));
            $sheet->setCellValue("D$row", $this->toolsHelper->formatCurrency($item->getQuantity() * $item->getUnitPrice(), $currency));
            $row++;
        }

        $sheet->setCellValue("C$row", "TOTAL");
        $sheet->setCellValue("D$row", $salesDocument->getTotalTTC());

        $writer = new Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'excel');
        $writer->save($tempFile);

        return new BinaryFileResponse($tempFile, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="devis_' . $salesDocument->getReference() . '.xlsx"',
        ]);
    }

    #[Route('/sales-document/{id}/pdf', name: 'app_sales_document_generate_pdf')]
    public function generatePdf(SalesDocument $salesDocument): Response
    {
        // 1. Configure Dompdf
        $pdfOptions = new Options();
        $pdfOptions->set('defaultFont', 'Helvetica');

        $dompdf = new Dompdf($pdfOptions);

        // 2. Rendu HTML via Twig
        $html = $this->renderView('sales_document/pdf.html.twig', [
            'salesDocument' => $salesDocument,
        ]);

        // 3. Charger le HTML
        $dompdf->loadHtml($html);

        // 4. Format A4 et orientation portrait
        $dompdf->setPaper('A4', 'portrait');

        // 5. Générer le PDF
        $dompdf->render();

        // 6. Envoyer en téléchargement
        return new Response(
            $dompdf->output(),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="document-commercial-' . $salesDocument->getId() . '.pdf"',
            ]
        );
    }


    #[Route('/invoice/new', name: 'app_invoice_new')]
    public function createInvoice(Request $request, EntityManagerInterface $em): Response
    {
        $invoice = $this->createInvoiceBase();
        $invoice->setReference('INV-' . date('Ymd-His'));

        $form = $this->createForm(SalesDocumentForm::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($invoice);
            $em->flush();

            return $this->redirectToRoute('app_sales_document_index', [], Response::HTTP_SEE_OTHER);

        }

        return $this->render('sales_document/new.html.twig', [
            'form' => $form,
            'salesDocument' => $invoice,
        ]);
    }

    #[Route('/sales-document/from-estimate/{salesDocument}', name: 'app_sales_document_from_estimate')]
    public function createFromEstimate(
        SalesDocument $salesDocument,
        EntityManagerInterface $em
    ): Response {

        // Vérifier que le document est bien un devis
        if (!$salesDocument->isEstimate()) {
            throw $this->createNotFoundException('Ce document n’est pas un devis valide.');
        }
        // Nouveau SalesDocument
        $estimateRelated = $em->getRepository(Estimate::class)->find($salesDocument->getEstimate()?->getId());
       // dd($salesDocument);
        $invoice = $this->createInvoiceBase();
        $invoice->setInvoiceDate($salesDocument->getInvoiceDate());
        $invoice->setReference('INV-' . date('Ymd-His'));
        $invoice->setClient($salesDocument->getResolvedClient());
        $invoice->setEstimate($estimateRelated);
        $invoice->setStatus('draft');
        if ($estimateRelated) {
            $this->applyVatFromRate($invoice, $estimateRelated->getVatRate());
        }

        // Copier les items de l'estimate
        foreach ($salesDocument->getSalesDocumentItems() as $item) {
            $newItem = clone $item; // clone permet de copier les propriétés
            $newItem->setSalesDocument($invoice);
            $em->persist($newItem);
        }

        $em->persist($invoice);
        $em->flush();

        return $this->redirectToRoute('app_sales_document_show', ['id' => $invoice->getId()]);
    }

    private function createInvoiceBase(): SalesDocument
    {
        $invoice = new SalesDocument();
        $invoice->setType(SalesDocument::TYPE_INVOICE);
        $invoice->setCreatedAt(new \DateTimeImmutable());
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $invoice->setCompany($company);
        }

        return $invoice;
    }

    private function applyVatFromRate(SalesDocument $salesDocument, ?float $rate): void
    {
        $rate = (float) ($rate ?? 0.0);
        $salesDocument->setVatRate($rate);
        $salesDocument->setTaxApplied($rate > 0);
    }

    private function getWordTemplatePath(SalesDocument $salesDocument): ?string
    {
        $company = $salesDocument->getCompany();
        if (!$company) {
            return null;
        }

        $type = $salesDocument->isEstimate() ? DocumentTemplate::TYPE_ESTIMATE : DocumentTemplate::TYPE_INVOICE;
        $repo = $this->entityManager->getRepository(DocumentTemplate::class);

        $template = $repo->findOneBy([
            'company' => $company,
            'type' => $type,
            'format' => DocumentTemplate::FORMAT_WORD,
            'isDefault' => true,
        ]);

        if (!$template) {
            $template = $repo->findOneBy(
                [
                    'company' => $company,
                    'type' => $type,
                    'format' => DocumentTemplate::FORMAT_WORD,
                ],
                ['createdAt' => 'DESC']
            );
        }

        if (!$template) {
            return null;
        }

        $relativePath = $template->getFilePath();
        $fullPath = rtrim((string) $this->getParameter('kernel.project_dir'), '/') . '/public/' . ltrim($relativePath, '/');

        return file_exists($fullPath) ? $fullPath : null;
    }

    private function buildTemplateData(SalesDocument $salesDocument): array
    {
        $company = $salesDocument->getCompany();
        $client = $salesDocument->getResolvedClient();

        $currency = $salesDocument->getResolvedCurrency('EUR');
        $formatMoney = fn (?string $amount) => $this->toolsHelper->formatCurrency($amount ?? 0, $currency);

        $items = [];
        foreach ($salesDocument->getSalesDocumentItems() as $item) {
            $qty = $item->getQuantity() ?? 0;
            $unit = $item->getUnitPrice() ?? 0;
            $total = (float) $qty * (float) $unit;
            $items[] = [
                'description' => $item->getDescription() ?? '',
                'quantity' => (string) $qty,
                'unit_price' => $formatMoney((string) $unit),
                'total' => $formatMoney((string) $total),
            ];
        }

        return [
            'scalar' => [
                'company_name' => $company?->getName() ?? '',
                'company_ice' => $company?->getIce() ?? '',
                'company_if' => $company?->getFiscalId() ?? '',
                'company_tp' => $company?->getTaxProfessional() ?? '',
                'company_address' => $company?->getAddress() ?? '',
                'company_city' => $company?->getCity() ?? '',
                'company_country' => $company?->getCountry() ?? '',
                'company_phone' => $company?->getPhone() ?? '',
                'company_email' => $company?->getEmail() ?? '',
                'company_logo' => $company?->getLogoPath() ?? '',
                'reference' => $salesDocument->getReference() ?? '',
                'type' => $salesDocument->getType() ?? '',
                'date' => ($salesDocument->getInvoiceDate() ?? $salesDocument->getCreatedAt())?->format('d/m/Y') ?? '',
                'client_name' => $client?->getName() ?? '',
                'client_email' => $client?->getEmail() ?? '',
                'client_phone' => $client?->getPhone() ?? '',
                'client_address' => $client?->getAddress() ?? '',
                'client_country' => $client?->getCountry() ?? '',
                'total_ht' => $formatMoney((string) $salesDocument->getTotalHT()),
                'total_ttc' => $formatMoney((string) $salesDocument->getTotalTTC()),
                'vat_rate' => (string) $salesDocument->getVatRate(),
            ],
            'items' => $items,
        ];
    }

    private function sanitizeFilename(string $name): string
    {
        $name = str_replace(['/', '\\'], '-', $name);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '-', $name) ?? $name;
        $name = trim($name, '-');
        return $name !== '' ? $name : 'document';
    }
}

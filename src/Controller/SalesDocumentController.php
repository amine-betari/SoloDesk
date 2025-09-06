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

#[Route('/sales/document')]
final class SalesDocumentController extends AbstractController
{
    public function __construct(
        protected ToolsHelper $toolsHelper,
    ) {

    }
    #[Route(name: 'app_sales_document_index')]
    public function index(
        SalesDocumentRepository $salesDocumentRepository,
        Request $request,
        PaginationService $paginator,
        FilterService $filterService
    ): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $session = $request->getSession();

        // Search Form
        $filterForm = $this->createForm(SalesDocumentFilterForm::class);
        // Search Form

        $qb = $salesDocumentRepository->createQueryBuilder('s')->orderBy('s.createdAt', 'ASC');

        // Handle Generic
        $filterForm = $filterService->handle(
            $request,
            $qb,
            $filterForm,
            $session,
            'document_sales_filter',
            'app_sales_document_index'
        );

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
        $form = $this->createForm(SalesDocumentForm::class, $salesDocument);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
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
        return $this->render('sales_document/show.html.twig', [
            'sales_document' => $salesDocument,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_sales_document_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, SalesDocument $salesDocument, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(SalesDocumentForm::class, $salesDocument);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Mettre à jour la date de modification
            $salesDocument->setModifiedAt(new \DateTimeImmutable());

            $entityManager->flush();

            return $this->redirectToRoute('app_sales_document_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('sales_document/edit.html.twig', [
            'sales_document' => $salesDocument,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_sales_document_delete', methods: ['POST'])]
    public function delete(Request $request, SalesDocument $salesDocument, EntityManagerInterface $entityManager): Response
    {
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
            }
        } elseif ($projectId) {
            $project = $em->getRepository(Project::class)->find($projectId);
            if ($project) {
                $salesDocument->setProject($project);
                $salesDocument->setType("project");
                $salesDocument->setReference($project->getProjectNumber());
            }
        } else {
            $salesDocument->setType("invoice");
        }

        $form = $this->createForm(SalesDocumentForm::class, $salesDocument);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
           // dd($salesDocument);
            $em->persist($salesDocument);
            $em->flush();

            return $this->redirectToRoute('app_sales_document_show', [
                'id' => $salesDocument->getId(),
            ]);
        }

        return $this->render('sales_document/new.html.twig', [
            'form' => $form,
        ]);
    }


    #[Route('/sales-document/{id}/word', name: 'app_sales_document_generate_word')]
    public function generateWord(SalesDocument $salesDocument): Response
    {
        // à refaire
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
        $section->addText("Total : " . number_format($salesDocument->getTotal(), 2, ',', ' ') . ' €', ['bold' => true]);

        $fileName = 'devis-' . $salesDocument->getReference() . '.docx';

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
        $sheet->setCellValue("D$row", $salesDocument->getTotal());

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
        $invoice = new SalesDocument();
        $invoice->setType('invoice');
        $invoice->setCreatedAt(new \DateTimeImmutable());
        $invoice->setReference('INV-' . date('Ymd-His')); // à remplacer par un générateur propre

        $form = $this->createForm(SalesDocumentForm::class, $invoice);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($invoice);
            $em->flush();

            return $this->redirectToRoute('app_sales_document_index', [], Response::HTTP_SEE_OTHER);

        }

        return $this->render('sales_document/new.html.twig', [
            'form' => $form,
        ]);
    }



}

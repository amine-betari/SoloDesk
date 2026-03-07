<?php

// src/Controller/DocumentTemplateController.php
namespace App\Controller;

use App\Entity\DocumentTemplate;
use App\Entity\SalesDocument;
use App\Form\DocumentTemplateType;
use Doctrine\ORM\EntityManagerInterface as EM;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;

#[Route('/templates')]
class DocumentTemplateController extends AbstractController
{
    #[Route('/', name: 'app_templates_index')]
    public function index(EM $em): Response
    {
        $templates = $em->getRepository(DocumentTemplate::class)->findBy([], ['createdAt' => 'DESC']);

        return $this->render('templates/index.html.twig', [
            'templates' => $templates,
        ]);
    }

    #[Route('/upload', name: 'app_templates_upload')]
    public function upload(Request $request, EM $em): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise liée.');
        }

        $template = new DocumentTemplate();
        $template->setCompany($company);

        $form = $this->createForm(DocumentTemplateType::class, $template);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $file */
            $file = $form->get('file')->getData();

            $dir = $this->getParameter('templates_directory');
            @mkdir($dir, 0775, true);

            $ext = $file->guessExtension() ?: 'bin';
            $filename = uniqid('tpl_').'.'.$ext;
            $file->move($dir, $filename);

            $template->setFilePath('uploads/templates/'.$filename);

            if ($template->isDefault()) {
                // on enlève l’ancien default pour ce couple (type+format)
                $em->createQuery('UPDATE App\Entity\DocumentTemplate t SET t.isDefault = false WHERE t.type = :type AND t.format = :format AND t.company = :company')
                    ->setParameters(['type' => $template->getType(), 'format' => $template->getFormat(), 'company' => $company])
                    ->execute();
            }

            $em->persist($template);
            $em->flush();

            $this->addFlash('success', 'Modèle enregistré.');
            return $this->redirectToRoute('app_templates_index');
        }

        return $this->render('templates/upload.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/sample/word/invoice', name: 'app_templates_sample_word_invoice')]
    public function downloadSampleWordInvoice(): Response
    {
        return $this->downloadSampleWord('Facture', 'modele-facture.docx');
    }

    #[Route('/sample/word/estimate', name: 'app_templates_sample_word_estimate')]
    public function downloadSampleWordEstimate(): Response
    {
        return $this->downloadSampleWord('Devis', 'modele-devis.docx');
    }

    #[Route('/preview', name: 'app_templates_preview')]
    public function preview(EM $em): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise liée.');
        }

        $salesDocument = $em->getRepository(SalesDocument::class)
            ->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->setParameter('company', $company)
            ->orderBy('s.invoiceDate', 'DESC')
            ->addOrderBy('s.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$salesDocument) {
            $this->addFlash('warning', 'Aucune facture/devis disponible pour aperçu.');
            return $this->redirectToRoute('app_templates_index');
        }

        $logoDataUri = null;
        if ($company->getLogoPath()) {
            $logoFile = $this->getParameter('kernel.project_dir') . '/public/' . $company->getLogoPath();
            if (is_file($logoFile)) {
                $mime = @mime_content_type($logoFile) ?: 'image/png';
                $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($logoFile));
            }
        }

        return $this->render('sales_document/pdf.html.twig', [
            'salesDocument' => $salesDocument,
            'logoDataUri' => $logoDataUri,
        ]);
    }

    #[Route('/install-pro', name: 'app_templates_install_pro', methods: ['POST'])]
    public function installProTemplates(Request $request, EM $em): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise liée.');
        }

        if (!$this->isCsrfTokenValid('install_pro_templates', $request->getPayload()->getString('_token'))) {
            throw $this->createAccessDeniedException('Jeton CSRF invalide.');
        }

        $templatesDir = $this->getParameter('templates_directory');
        @mkdir($templatesDir, 0775, true);

        $this->createProTemplate($em, $company, DocumentTemplate::TYPE_INVOICE, 'Modèle Pro Facture', $templatesDir);
        $this->createProTemplate($em, $company, DocumentTemplate::TYPE_ESTIMATE, 'Modèle Pro Devis', $templatesDir);

        $this->addFlash('success', 'Modèles pro installés et définis par défaut.');
        return $this->redirectToRoute('app_templates_index');
    }

    private function downloadSampleWord(string $label, string $filename): Response
    {
        $tempFile = $this->buildSampleWordFile($label);

        return $this->file($tempFile, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
    }

    private function createProTemplate(EM $em, $company, string $type, string $name, string $templatesDir): void
    {
        $label = $type === DocumentTemplate::TYPE_ESTIMATE ? 'Devis' : 'Facture';
        $tempFile = $this->buildSampleWordFile($label);

        $filename = uniqid('tpl_', true) . '.docx';
        $targetPath = rtrim($templatesDir, '/') . '/' . $filename;
        @copy($tempFile, $targetPath);

        $em->createQuery('UPDATE App\Entity\DocumentTemplate t SET t.isDefault = false WHERE t.type = :type AND t.format = :format AND t.company = :company')
            ->setParameters(['type' => $type, 'format' => DocumentTemplate::FORMAT_WORD, 'company' => $company])
            ->execute();

        $template = new DocumentTemplate();
        $template->setCompany($company);
        $template->setName($name);
        $template->setType($type);
        $template->setFormat(DocumentTemplate::FORMAT_WORD);
        $template->setFilePath('uploads/templates/' . $filename);
        $template->setIsDefault(true);

        $em->persist($template);
        $em->flush();
    }

    private function buildSampleWordFile(string $label): string
    {
        $phpWord = new PhpWord();
        $phpWord->setDefaultFontName('Calibri');
        $phpWord->setDefaultFontSize(11);
        $section = $phpWord->addSection();

        $titleStyle = ['size' => 20, 'bold' => true, 'color' => 'FFFFFF'];
        $mutedStyle = ['color' => '6B7280', 'size' => 9];
        $labelStyle = ['bold' => true, 'size' => 10, 'color' => '374151'];
        $sectionTitleStyle = ['bold' => true, 'size' => 12, 'color' => '111827'];

        // Top band
        $bandTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 100]);
        $bandTable->addRow();
        $bandLeft = $bandTable->addCell(7000, ['bgColor' => '0F172A', 'valign' => 'center']);
        $bandRight = $bandTable->addCell(3000, ['bgColor' => '0F172A', 'valign' => 'center']);
        $bandLeft->addText(' ', ['size' => 4, 'color' => 'FFFFFF']);
        $bandLeft->addText($label . ' ${reference}', $titleStyle);
        $bandLeft->addText('Date: ${date}', ['color' => 'E2E8F0', 'size' => 9]);
        $bandRight->addText(' ', ['size' => 4, 'color' => 'FFFFFF']);
        $bandRight->addText('${company_logo}', ['color' => 'FFFFFF']);

        $section->addTextBreak(1);

        $headerTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
        $headerTable->addRow();
        $left = $headerTable->addCell(6000);
        $right = $headerTable->addCell(4000);

        $left->addText('${company_name}', ['bold' => true, 'size' => 12]);
        $left->addText('${company_address}', $mutedStyle);
        $left->addText('${company_city} ${company_country}', $mutedStyle);
        $left->addText('${company_email} | ${company_phone}', $mutedStyle);
        $left->addText('ICE: ${company_ice}  IF: ${company_if}  TP: ${company_tp}  RC: ${company_rc}', $mutedStyle);

        $right->addText('Facturé à', $sectionTitleStyle);
        $right->addText('${client_name}');
        $right->addText('${client_email}');
        $right->addText('${client_phone}');
        $right->addText('${client_address}');
        $right->addText('${client_country}');

        $section->addTextBreak(1);

        $infoTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
        $infoTable->addRow();
        $billTo = $infoTable->addCell(5000);
        $docInfo = $infoTable->addCell(5000);

        $billTo->addText('Informations document', $sectionTitleStyle);
        $billTo->addText('Référence: ${reference}');
        $billTo->addText('Date: ${date}');
        $billTo->addText('TVA: ${vat_rate}');

        $docInfo->addText('Contact entreprise', $sectionTitleStyle);
        $docInfo->addText('${company_email}');
        $docInfo->addText('${company_phone}');
        $docInfo->addText('${company_city} ${company_country}');

        $section->addTextBreak(1);

        $tableStyle = [
            'borderSize' => 6,
            'borderColor' => 'D1D5DB',
            'cellMargin' => 60,
        ];
        $table = $section->addTable($tableStyle);
        $table->addRow();
        $headerCellStyle = ['bgColor' => 'F3F4F6', 'valign' => 'center'];
        $table->addCell(5200, $headerCellStyle)->addText('Description', $labelStyle);
        $table->addCell(1200, $headerCellStyle)->addText('Qté', $labelStyle);
        $table->addCell(1600, $headerCellStyle)->addText('Prix Unitaire', $labelStyle);
        $table->addCell(1600, $headerCellStyle)->addText('Total', $labelStyle);

        $table->addRow();
        $table->addCell(5200)->addText('${item_description}');
        $table->addCell(1200)->addText('${item_qty}');
        $table->addCell(1600)->addText('${item_unit_price}');
        $table->addCell(1600)->addText('${item_total}');

        $section->addTextBreak(1);
        $totalsTable = $section->addTable(['borderSize' => 0, 'cellMargin' => 80]);
        $totalsTable->addRow();
        $totalsTable->addCell(6000)->addText(' ');
        $totalsCell = $totalsTable->addCell(4000);
        $totalsCell->addText('Total HT : ${total_ht}', $labelStyle);
        $totalsCell->addText('TVA : ${vat_rate}', $labelStyle);
        $totalsCell->addText('Total TTC : ${total_ttc}', ['bold' => true, 'size' => 12, 'color' => '111827']);

        $section->addTextBreak(1);
        $section->addTextBreak(1);
        $section->addText('Notes / Conditions', $sectionTitleStyle);
        $section->addText('${notes}');

        $section->addTextBreak(1);
        $section->addText('Merci pour votre confiance.', $mutedStyle);
        $section->addText('ICE: ${company_ice}  IF: ${company_if}  TP: ${company_tp}  RC: ${company_rc}', $mutedStyle);

        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return $tempFile;
    }

    #[Route('/{id}/default', name: 'app_templates_set_default')]
    public function setDefault(DocumentTemplate $template, EM $em): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise liée.');
        }

        $em->createQuery('UPDATE App\Entity\DocumentTemplate t SET t.isDefault = false WHERE t.type = :type AND t.format = :format AND t.company = :company')
            ->setParameters(['type' => $template->getType(), 'format' => $template->getFormat(), 'company' => $company])
            ->execute();

        $template->setIsDefault(true);
        $template->setUpdatedAt(new \DateTimeImmutable());
        $em->flush();

        $this->addFlash('success', 'Défini comme modèle par défaut.');
        return $this->redirectToRoute('app_templates_index');
    }

    #[Route('/{id}', name: 'app_templates_delete', methods: ['POST'])]
    public function delete(DocumentTemplate $template, Request $request, EM $em): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $template->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete_template'.$template->getId(), $request->getPayload()->getString('_token'))) {
            $em->remove($template);
            $em->flush();
            $this->addFlash('success', 'Modèle supprimé.');
        }

        return $this->redirectToRoute('app_templates_index');
    }
}

<?php

// src/Controller/DocumentTemplateController.php
namespace App\Controller;

use App\Entity\DocumentTemplate;
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

    private function downloadSampleWord(string $label, string $filename): Response
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        $section->addTitle($label . ' ${reference}', 1);
        $section->addText('Entreprise : ${company_name}');
        $section->addText('Client : ${client_name}');
        $section->addText('Email : ${client_email}');
        $section->addText('Téléphone : ${client_phone}');
        $section->addText('Adresse : ${client_address}');
        $section->addText('Pays : ${client_country}');
        $section->addText('Date : ${date}');

        $section->addTextBreak(1);
        $section->addText('Lignes :', ['bold' => true]);

        $table = $section->addTable(['borderSize' => 6, 'borderColor' => '999999']);
        $table->addRow();
        $table->addCell(5000)->addText('Description');
        $table->addCell(1500)->addText('Quantité');
        $table->addCell(2000)->addText('Prix Unitaire');
        $table->addCell(2000)->addText('Total');

        $table->addRow();
        $table->addCell(5000)->addText('${item_description}');
        $table->addCell(1500)->addText('${item_qty}');
        $table->addCell(2000)->addText('${item_unit_price}');
        $table->addCell(2000)->addText('${item_total}');

        $section->addTextBreak(1);
        $section->addText('Total HT : ${total_ht}');
        $section->addText('TVA : ${vat_rate}');
        $section->addText('Total TTC : ${total_ttc}', ['bold' => true]);

        $tempFile = tempnam(sys_get_temp_dir(), 'tpl_');
        IOFactory::createWriter($phpWord, 'Word2007')->save($tempFile);

        return $this->file($tempFile, $filename, ResponseHeaderBag::DISPOSITION_ATTACHMENT);
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

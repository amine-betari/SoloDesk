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

            $dir = $this->getParameter('documents_directory');
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
}

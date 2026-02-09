<?php

namespace App\Controller;

use App\Entity\Company;
use App\Form\CompanyForm;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/company')]
final class CompanyController extends AbstractController
{
    #[Route('', name: 'app_company_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $company = $user?->getCompany();

        if (!$company) {
            $company = new Company();
            $company->setName('Entreprise');
            $entityManager->persist($company);
            $user?->setCompany($company);
        }

        $form = $this->createForm(CompanyForm::class, $company);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile|null $logoFile */
            $logoFile = $form->get('logoFile')->getData();
            if ($logoFile) {
                $logosDir = $this->getParameter('company_logos_directory');
                @mkdir($logosDir, 0775, true);

                $originalFilename = pathinfo($logoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = preg_replace('/[^A-Za-z0-9_-]/', '-', $originalFilename) ?? 'logo';
                $newFilename = $safeFilename.'-'.uniqid().'.'.$logoFile->guessExtension();

                $logoFile->move($logosDir, $newFilename);
                $company->setLogoPath('uploads/company/'.$newFilename);
            }

            $entityManager->flush();
            $this->addFlash('success', 'Entreprise mise à jour.');
            return $this->redirectToRoute('app_company_edit');
        }

        return $this->render('company/edit.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}

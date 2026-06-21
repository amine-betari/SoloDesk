<?php

namespace App\Controller;

use App\Constants\EstimateStatuses;
use App\Form\Search\ProjectFilterForm;
use App\Entity\Estimate;
use App\Repository\PaginationService;
use App\Services\DocumentManager;
use App\Form\EstimateForm;
use App\Services\FilterService;
use App\Repository\EstimateRepository;
use App\Service\SalesDocumentInvoiceProgress;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/estimate')]
final class EstimateController extends AbstractController
{
    #[Route(name: 'app_estimate_index')]
    public function index(
        EstimateRepository $estimateRepository,
        Request $request,
        PaginationService $paginator,
        FilterService $filterService,
        \App\Service\CompanySettings $settings,
        SalesDocumentInvoiceProgress $invoiceProgress
    ): Response
    {

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $session = $request->getSession();

        // Search Form
        $filterForm = $this->createForm(ProjectFilterForm::class);
        // QueryBuilder
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $activityStartDate = $settings->getDate($company, \App\Service\CompanySettings::KEY_ACTIVITY_START_DATE, new \DateTimeImmutable('2017-01-01'));

        $qb = $estimateRepository->createQueryBuilder('e')
            ->andWhere('e.company = :company')
            ->andWhere('COALESCE(e.startDate, e.createdAt) >= :start')
            ->setParameter('company', $company)
            ->setParameter('start', $activityStartDate)
            ->orderBy('e.startDate', 'DESC');

        // Handle Generic
        $filterForm = $filterService->handle(
            $request,
            $qb,
            $filterForm,
            $session,
            'estimate_filter',
            'app_estimate_index'
        );

        // Redirection si reset
        if ($filterForm->isSubmitted() && $filterForm->get('reset')->isClicked()) {
            return $this->redirectToRoute('app_estimate_index');
        }

        $statusCounts = array_fill_keys(array_keys(EstimateStatuses::CHOICES), 0);
        $statusRows = (clone $qb)
            ->select('e.status AS status, COUNT(e.id) AS total')
            ->resetDQLPart('orderBy')
            ->groupBy('e.status')
            ->getQuery()
            ->getArrayResult();
        foreach ($statusRows as $row) {
            $statusCounts[$row['status']] = (int) $row['total'];
        }

        $qb->addSelect('salesDocuments')
            ->leftJoin('e.salesDocuments', 'salesDocuments');

        $pagination = $paginator->paginate($qb, $page, $limit);
        $displayedTotals = [];
        $billingProgressByEstimateId = [];
        foreach ($pagination['items'] as $estimate) {
            $currency = $estimate->getCurrency();
            $displayedTotals[$currency] = ($displayedTotals[$currency] ?? 0.0) + (float) $estimate->getAmount();
            $billingProgressByEstimateId[$estimate->getId()] = $invoiceProgress->getEstimateProgress($estimate);
        }

        return $this->render('estimate/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(), // on envoie le form à la vu
            'statusCounts' => $statusCounts,
            'displayedTotals' => $displayedTotals,
            'billingProgressByEstimateId' => $billingProgressByEstimateId,
        ]);
    }

    #[Route('/new', name: 'app_estimate_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        DocumentManager $documentManager
    ): Response
    {
        $estimate = new Estimate();
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $estimate->setCompany($company);
        }

        $form = $this->createForm(EstimateForm::class, $estimate);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Manage Clients
            $client = $estimate->getClient();
            if ($client) {
                // Copier la devise du client dans l'estimate
                $estimate->setCurrency($client->getCurrency());
            }
            // Manage Clients

            // Manage Documents
            $files = $request->files->get('estimate_form')['documents'] ?? [];
            $documentManager->uploadDocuments($files, $estimate, $slugger);
            // Manage Documents

            $estimate = $form->getData();

            if ($form->get('noVat')->getData()) {
                $estimate->setVatRate(0.00);
            }
            $entityManager->persist($estimate);
            $entityManager->flush();

            return $this->redirectToRoute('app_estimate_show', [
                'id' => $estimate->getId(),
                'created' => 1,
            ], Response::HTTP_SEE_OTHER);
        }

        return $this->render('estimate/new.html.twig', [
            'estimate' => $estimate,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_estimate_show', methods: ['GET'])]
    public function show(Estimate $estimate, SalesDocumentInvoiceProgress $invoiceProgress): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $estimate->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        return $this->render('estimate/show.html.twig', [
            'estimate' => $estimate,
            'billing_progress' => $invoiceProgress->getEstimateProgress($estimate),
        ]);
    }

    #[Route('/{id}/edit', name: 'app_estimate_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Estimate $estimate,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        DocumentManager $documentManager
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $estimate->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        $form = $this->createForm(EstimateForm::class, $estimate);

        // 1. **Important** : Préremplir la checkbox 'noVat' selon la valeur dans $project
        $form->get('noVat')->setData($estimate->getVatRate() == 0);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Manage Clients
            $client = $estimate->getClient();
            if ($client) {
                // Copier la devise du client dans l'estimate
                $estimate->setCurrency($client->getCurrency());
            }
            // Manage Clients

            // Manage Documents
            $documentsToRemove = $request->request->all('documents_to_remove');
            if (!is_array($documentsToRemove)) {
                $documentsToRemove = [];
            }
            $documentManager->removeDocuments($documentsToRemove, $estimate);
            $files = $request->files->get('estimate_form')['documents'] ?? [];
            $documentManager->uploadDocuments($files, $estimate, $slugger);
            // Manage Documents

            // Si pas de TVA coché alors le taux est zero
            if ($form->get('noVat')->getData()) {
                $estimate->setVatRate(0.00);
            }

            // Mettre à jour la date de modification
            $estimate->setModifiedAt(new \DateTimeImmutable());

            $entityManager->flush();

            return $this->redirectToRoute('app_estimate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('estimate/edit.html.twig', [
            'estimate' => $estimate,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_estimate_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Estimate $estimate,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $estimate->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        if ($this->isCsrfTokenValid('delete'.$estimate->getId(), $request->getPayload()->getString('_token'))) {
            if (!$estimate->canBeDeleted()) {
                $this->addFlash('warning', $translator->trans('estimate.delete_linked_error'));

                return $this->redirectToRoute('app_estimate_show', ['id' => $estimate->getId()], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($estimate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_estimate_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/convert-to-project', name: 'app_estimate_convert_to_project', methods: ['GET'])]
    public function convertToProject(Estimate $estimate): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $estimate->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($estimate->getProject()) {
            $this->addFlash('warning', 'Ce devis est déjà associé à un projet.');

            return $this->redirectToRoute('app_estimate_show', ['id' => $estimate->getId()]);
        }

        return $this->redirectToRoute('app_project_new', ['estimate' => $estimate->getId()]);
    }

}

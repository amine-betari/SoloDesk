<?php

namespace App\Controller;

use App\Entity\Project;
use App\Constants\ProjectStatuses;
use App\Form\ProjectForm;
use App\Form\Search\ProjectFilterForm;
use App\Repository\ProjectRepository;
use App\Repository\EstimateRepository;
use App\Repository\SalesDocumentRepository;
use App\Repository\PaginationService;
use App\Services\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use App\Services\FilterService;
use App\Service\CompanySettings;
use App\Service\ProjectFromEstimateFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/project')]
final class ProjectController extends AbstractController
{
    #[Route(name: 'app_project_index')]
    public function index(
        ProjectRepository $projectRepository,
        Request $request,
        PaginationService $paginator,
        FilterService $filterService,
        CompanySettings $settings
    ): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $session = $request->getSession();

        // Search Form
        $filterForm = $this->createForm(ProjectFilterForm::class);
        // Search Form
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $activityStartDate = $settings->getDate($company, CompanySettings::KEY_ACTIVITY_START_DATE, new \DateTimeImmutable('2017-01-01'));

        $qb = $projectRepository->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('COALESCE(p.startDate, p.createdAt) >= :start')
            ->setParameter('company', $company)
            ->setParameter('start', $activityStartDate)
            ->orderBy('p.name', 'ASC');

        // Handle Generic
        $filterForm = $filterService->handle(
            $request,
            $qb,
            $filterForm,
            $session,
            'project_filter',
            'app_project_index'
        );

        // Redirection si reset
        if ($filterForm->isSubmitted() && $filterForm->get('reset')->isClicked()) {
            return $this->redirectToRoute('app_project_index');
        }

        $statusCounts = array_fill_keys(array_keys(ProjectStatuses::CHOICES), 0);
        $statusRows = (clone $qb)
            ->select('p.status AS status, COUNT(p.id) AS total')
            ->resetDQLPart('orderBy')
            ->groupBy('p.status')
            ->getQuery()
            ->getArrayResult();
        foreach ($statusRows as $row) {
            $statusCounts[$row['status']] = (int) $row['total'];
        }

        $pagination = $paginator->paginate($qb, $page, $limit);
        $displayedTotals = [];
        foreach ($pagination['items'] as $project) {
            $currency = $project->getCurrency();
            $amount = $project->isRecurring() ? $project->getCalculatedAmount() : (float) $project->getAmount();
            $displayedTotals[$currency] = ($displayedTotals[$currency] ?? 0.0) + (float) $amount;
        }

        return $this->render('project/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(), // on envoie le form à la vu
            'statusCounts' => $statusCounts,
            'displayedTotals' => $displayedTotals,
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        DocumentManager $documentManager,
        EstimateRepository $estimateRepository,
        ProjectFromEstimateFactory $projectFromEstimateFactory
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $sourceEstimate = null;
        $estimateId = $request->query->getInt('estimate');
        if ($estimateId > 0) {
            $sourceEstimate = $estimateRepository->findOneBy([
                'id' => $estimateId,
                'company' => $company,
            ]);
            if (!$sourceEstimate) {
                throw $this->createNotFoundException('Pré-estimation introuvable.');
            }
            if ($sourceEstimate->getProject()) {
                $this->addFlash('warning', 'Ce devis est déjà associé à un projet.');

                return $this->redirectToRoute('app_estimate_show', ['id' => $sourceEstimate->getId()]);
            }
        }

        $project = $sourceEstimate
            ? $projectFromEstimateFactory->create($sourceEstimate)
            : (new Project())->setCompany($company);

        $form = $this->createForm(ProjectForm::class, $project, [
            'action' => $this->generateUrl('app_project_new', $sourceEstimate ? ['estimate' => $sourceEstimate->getId()] : []),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Manage Clients
            $client = $project->getClient();
            if ($client) {
                // Copier la devise du client dans l'estimate
                $project->setCurrency($client->getCurrency());
            }
            // Manage Clients

            // Manage Documents
            $files = $request->files->get('project_form')['documents'] ?? [];
            $documentManager->uploadDocuments($files, $project, $slugger);
            // Manage Documents

            $project = $form->getData();

            // Pas de TVA
            if ($form->get('noVat')->getData()) {
                $project->setVatRate(0.00);
            }
            // Project Reccurent
            if (!$project->isRecurring()) {
                // Reset les champs récurrents si non récurrent
                $project->setRecurringAmount(null);
                $project->setRecurringPeriod(null);
            }

            if ($sourceEstimate) {
                $sourceEstimate->setProject($project);
            }

            $entityManager->persist($project);
            $entityManager->flush();

            if ($sourceEstimate) {
                $this->addFlash('success', 'Projet créé et lié à la pré-estimation.');

                return $this->redirectToRoute('app_project_show', ['id' => $project->getId()], Response::HTTP_SEE_OTHER);
            }

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
            'sourceEstimate' => $sourceEstimate,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(
        Project $project,
        SalesDocumentRepository $salesDocumentRepository,
        CompanySettings $settings
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $project->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $overdueDays = $settings->getInt($company, CompanySettings::KEY_OVERDUE_DAYS, 45);
        $overdueBefore = (new \DateTimeImmutable('now'))->modify(sprintf('-%d days', $overdueDays));
        $overdueInvoices = $salesDocumentRepository->findOverdueInvoices($company, $overdueBefore, $project);

        return $this->render('project/show.html.twig', [
            'project' => $project,
            'overdueInvoices' => $overdueInvoices,
            'overdueDays' => $overdueDays,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_project_edit', methods: ['GET', 'POST'])]
    public function edit(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        DocumentManager $documentManager
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $project->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        $form = $this->createForm(ProjectForm::class, $project);

        // 3. **Important** : Préremplir la checkbox 'noVat' selon la valeur dans $project
        $form->get('noVat')->setData($project->getVatRate() == 0);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Manage Clients
            $client = $project->getClient();
            if ($client) {
                // Copier la devise du client dans l'estimate
                $project->setCurrency($client->getCurrency());
            }
            // Manage Clients

            // Manage Documents
            $documentsToRemove = $request->request->all('documents_to_remove');
            if (!is_array($documentsToRemove)) {
                $documentsToRemove = [];
            }
            $documentManager->removeDocuments($documentsToRemove, $project);
            $files = $request->files->get('project_form')['documents'] ?? [];
            $documentManager->uploadDocuments($files, $project, $slugger);
            // Manage Documents

            if ($form->get('noVat')->getData()) {
                $project->setVatRate(0.00);
            }

            // Project Reccurent
            if (!$project->isRecurring()) {
                // Reset les champs récurrents si non récurrent
                $project->setRecurringAmount(null);
                $project->setRecurringPeriod(null);
            }

            // Mettre à jour la date de modification
            $project->setModifiedAt(new \DateTimeImmutable());

            $entityManager->flush();

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/edit.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_delete', methods: ['POST'])]
    public function delete(
        Request $request,
        Project $project,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $project->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->getPayload()->getString('_token'))) {
            if (!$project->canBeDeleted()) {
                $this->addFlash('warning', $translator->trans('project.delete_linked_error'));

                return $this->redirectToRoute('app_project_show', ['id' => $project->getId()], Response::HTTP_SEE_OTHER);
            }

            if ($project->getEstimate()) {
                $project->getEstimate()->setProject(null);
            }

            $entityManager->remove($project);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
    }
}

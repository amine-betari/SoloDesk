<?php

namespace App\Controller;

use App\Constants\ProjectStatuses;
use App\Constants\ProjectTypes;
use App\Entity\Document;
use App\Form\Search\ProjectFilterForm;
use App\Entity\Estimate;
use App\Repository\PaginationService;
use App\Entity\Project;
use App\Services\DocumentManager;
use App\Form\EstimateForm;
use App\Services\FilterService;
use App\Repository\EstimateRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/estimate')]
final class EstimateController extends AbstractController
{
    #[Route(name: 'app_estimate_index')]
    public function index(
        EstimateRepository $estimateRepository,
        Request $request,
        PaginationService $paginator,
        FilterService $filterService
    ): Response
    {

        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $session = $request->getSession();

        // Search Form
        $filterForm = $this->createForm(ProjectFilterForm::class);
       // $filterForm->handleRequest($request);
        // QueryBuilder
        $qb = $estimateRepository->createQueryBuilder('e');

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

        $pagination = $paginator->paginate($qb, $page, $limit);

        return $this->render('estimate/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(), // on envoie le form à la vu
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

            return $this->redirectToRoute('app_estimate_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('estimate/new.html.twig', [
            'estimate' => $estimate,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_estimate_show', methods: ['GET'])]
    public function show(Estimate $estimate): Response
    {
        return $this->render('estimate/show.html.twig', [
            'estimate' => $estimate,
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
    public function delete(Request $request, Estimate $estimate, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$estimate->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($estimate);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_estimate_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/estimate/{id}/convert-to-project', name: 'app_estimate_convert_to_project')]
    public function convertToProject(Estimate $estimate, EntityManagerInterface $em): Response
    {
        // 1. Vérifier s'il a déjà un projet
        if ($estimate->getProject()) {
            $this->addFlash('warning', 'Ce devis est déjà associé à un projet.');
            return $this->redirectToRoute('app_estimate_show', ['id' => $estimate->getId()]);
        }

        // 2. Créer le projet à partir des infos du devis
        $project = new Project();
        $project->setName($estimate->getEstimateNumber()); // ou autre champ
        $project->setStartDate(new \DateTimeImmutable());
        $project->setStatus(ProjectStatuses::IN_PROGRESS);
        $project->setClient($estimate->getClient());
        $project->setType(ProjectTypes::AUTRE);
        $project->setDescription($estimate->getDescription());
        $project->setAmount($estimate->getAmount());
        $project->setVatRate($estimate->getVatRate());
        $project->setCurrency($estimate->getClient()->getCurrency());
        //$project->setHasVat($estimate->isHasVat());

        // 3. Lier les deux
        $estimate->setProject($project);

        // 4. Persister
        $em->persist($project);
        $em->flush();

        $this->addFlash('success', 'Projet créé avec succès depuis le devis.');

        return $this->redirectToRoute('app_project_edit', ['id' => $project->getId()]);
    }

}

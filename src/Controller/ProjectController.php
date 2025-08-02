<?php

namespace App\Controller;

use App\Entity\Project;
use App\Form\ProjectForm;
use App\Repository\ProjectRepository;
use App\Services\DocumentManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/project')]
final class ProjectController extends AbstractController
{
    #[Route(name: 'app_project_index', methods: ['GET'])]
    public function index(ProjectRepository $projectRepository): Response
    {
        return $this->render('project/index.html.twig', [
            'projects' => $projectRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_project_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        DocumentManager $documentManager
    ): Response
    {
        $project = new Project();
        $form = $this->createForm(ProjectForm::class, $project);
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

            $entityManager->persist($project);
            $entityManager->flush();

            return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('project/new.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_project_show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        return $this->render('project/show.html.twig', [
            'project' => $project,
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
    public function delete(Request $request, Project $project, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($project);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_project_index', [], Response::HTTP_SEE_OTHER);
    }
}

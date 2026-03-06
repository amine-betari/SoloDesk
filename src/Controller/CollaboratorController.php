<?php

namespace App\Controller;

use App\Entity\Collaborator;
use App\Form\CollaboratorForm;
use App\Repository\CollaboratorRepository;
use App\Repository\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/collaborateurs')]
final class CollaboratorController extends AbstractController
{
    #[Route(name: 'app_collaborator_index', methods: ['GET'])]
    public function index(
        CollaboratorRepository $collaboratorRepository,
        Request $request,
        PaginationService $paginator
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $q = trim((string) $request->query->get('q', ''));

        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $qb = $collaboratorRepository->createQueryBuilder('c')
            ->leftJoin('c.skills', 's')
            ->andWhere('c.company = :company')
            ->setParameter('company', $company)
            ->orderBy('c.name', 'ASC');

        if ($q !== '') {
            $qb->andWhere('c.name LIKE :q OR c.role LIKE :q OR s.name LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        $pagination = $paginator->paginate($qb->distinct(), $page, $limit);

        return $this->render('collaborator/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
        ]);
    }

    #[Route('/new', name: 'app_collaborator_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $collaborator = new Collaborator();
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $collaborator->setCompany($company);
        }

        $form = $this->createForm(CollaboratorForm::class, $collaborator, [
            'company' => $company,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($collaborator);
            $entityManager->flush();

            return $this->redirectToRoute('app_collaborator_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collaborator/new.html.twig', [
            'collaborator' => $collaborator,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collaborator_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Collaborator $collaborator): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $collaborator->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('collaborator/show.html.twig', [
            'collaborator' => $collaborator,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_collaborator_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Collaborator $collaborator, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $collaborator->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $form = $this->createForm(CollaboratorForm::class, $collaborator, [
            'company' => $company,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_collaborator_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collaborator/edit.html.twig', [
            'collaborator' => $collaborator,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_collaborator_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Collaborator $collaborator, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $collaborator->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete'.$collaborator->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($collaborator);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_collaborator_index', [], Response::HTTP_SEE_OTHER);
    }
}

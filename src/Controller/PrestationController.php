<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Prestation;
use App\Form\PrestationForm;
use App\Repository\CollaboratorRepository;
use App\Repository\PaginationService;
use App\Repository\PrestationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/prestations')]
final class PrestationController extends AbstractController
{
    #[Route(name: 'app_prestation_index', methods: ['GET'])]
    public function index(
        PrestationRepository $prestationRepository,
        CollaboratorRepository $collaboratorRepository,
        Request $request,
        PaginationService $paginator
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $q = trim((string) $request->query->get('q', ''));
        $status = trim((string) $request->query->get('status', ''));
        $collaboratorId = $request->query->getInt('collaborator', 0);

        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $qb = $prestationRepository->createQueryBuilder('p')
            ->leftJoin('p.collaborator', 'c')
            ->leftJoin('p.salesDocument', 'sd')
            ->andWhere('p.company = :company')
            ->setParameter('company', $company)
            ->orderBy('p.performedAt', 'DESC');

        if ($q !== '') {
            $qb->andWhere('p.label LIKE :q OR c.name LIKE :q OR sd.reference LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        if ($status !== '') {
            $qb->andWhere('p.status = :status')
                ->setParameter('status', $status);
        }

        if ($collaboratorId > 0) {
            $qb->andWhere('c.id = :collaboratorId')
                ->setParameter('collaboratorId', $collaboratorId);
        }

        $pagination = $paginator->paginate($qb->distinct(), $page, $limit);

        $collaborators = $collaboratorRepository->createQueryBuilder('c')
            ->andWhere('c.company = :company')
            ->setParameter('company', $company)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $this->render('prestation/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
            'status' => $status,
            'collaboratorId' => $collaboratorId,
            'collaborators' => $collaborators,
        ]);
    }

    #[Route('/new', name: 'app_prestation_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CollaboratorRepository $collaboratorRepository
    ): Response
    {
        $prestation = new Prestation();
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $prestation->setCompany($company);
        }

        $collaboratorId = $request->query->getInt('collaborator', 0);
        $collaboratorLocked = false;
        if ($collaboratorId > 0 && $company) {
            $collaborator = $collaboratorRepository->createQueryBuilder('c')
                ->andWhere('c.id = :id')
                ->andWhere('c.company = :company')
                ->setParameter('id', $collaboratorId)
                ->setParameter('company', $company)
                ->getQuery()
                ->getOneOrNullResult();

            if ($collaborator) {
                $prestation->setCollaborator($collaborator);
                $collaboratorLocked = true;
            }
        }

        $form = $this->createForm(PrestationForm::class, $prestation, [
            'company' => $company,
            'collaborator_locked' => $collaboratorLocked,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($company && !$prestation->getCompany()) {
                $prestation->setCompany($company);
            }

            $entityManager->persist($prestation);
            $entityManager->flush();

            return $this->redirectToRoute('app_prestation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('prestation/new.html.twig', [
            'prestation' => $prestation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_prestation_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(Prestation $prestation): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $prestation->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        return $this->render('prestation/show.html.twig', [
            'prestation' => $prestation,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_prestation_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Prestation $prestation, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $prestation->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $form = $this->createForm(PrestationForm::class, $prestation, [
            'company' => $company,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_prestation_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('prestation/edit.html.twig', [
            'prestation' => $prestation,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_prestation_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(Request $request, Prestation $prestation, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $prestation->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete'.$prestation->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($prestation);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_prestation_index', [], Response::HTTP_SEE_OTHER);
    }
}

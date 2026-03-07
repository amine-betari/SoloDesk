<?php

namespace App\Controller;

use App\Entity\Collaborator;
use App\Form\CollaboratorForm;
use App\Repository\CollaboratorRepository;
use App\Repository\PaginationService;
use App\Repository\SkillRepository;
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
        SkillRepository $skillRepository,
        EntityManagerInterface $entityManager,
        Request $request,
        PaginationService $paginator
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $q = trim((string) $request->query->get('q', ''));
        $type = trim((string) $request->query->get('type', ''));
        $skillId = $request->query->getInt('skill', 0);
        $sort = trim((string) $request->query->get('sort', 'name_asc'));

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

        if ($type !== '') {
            $qb->andWhere('c.type = :type')
                ->setParameter('type', $type);
        }

        if ($skillId > 0) {
            $qb->andWhere('s.id = :skillId')
                ->setParameter('skillId', $skillId);
        }

        switch ($sort) {
            case 'name_desc':
                $qb->orderBy('c.name', 'DESC');
                break;
            case 'role_asc':
                $qb->orderBy('c.role', 'ASC');
                break;
            case 'role_desc':
                $qb->orderBy('c.role', 'DESC');
                break;
            case 'cost_asc':
                $qb->orderBy('c.monthlyCost', 'ASC');
                break;
            case 'cost_desc':
                $qb->orderBy('c.monthlyCost', 'DESC');
                break;
            default:
                $qb->orderBy('c.name', 'ASC');
        }

        $pagination = $paginator->paginate($qb->distinct(), $page, $limit);

        $skills = $skillRepository->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->setParameter('company', $company)
            ->orderBy('s.isCore', 'DESC')
            ->addOrderBy('s.name', 'ASC')
            ->getQuery()
            ->getResult();

        $conn = $entityManager->getConnection();
        $countsByType = [
            'salarie' => 0,
            'freelance' => 0,
            'collaborateur' => 0,
        ];
        $typeRows = $conn->fetchAllAssociative(
            'SELECT type, COUNT(*) AS total FROM collaborator WHERE company_id = :companyId GROUP BY type',
            ['companyId' => $company->getId()]
        );
        foreach ($typeRows as $row) {
            $countsByType[$row['type']] = (int) $row['total'];
        }

        $topSkills = $conn->fetchAllAssociative(
            'SELECT s.name AS name, COUNT(cs.collaborator_id) AS total
             FROM collaborator_skill cs
             INNER JOIN skill s ON s.id = cs.skill_id
             WHERE s.company_id = :companyId
             GROUP BY s.id
             ORDER BY total DESC, s.name ASC
             LIMIT 8',
            ['companyId' => $company->getId()]
        );

        return $this->render('collaborator/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
            'type' => $type,
            'skillId' => $skillId,
            'sort' => $sort,
            'skills' => $skills,
            'countsByType' => $countsByType,
            'topSkills' => $topSkills,
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

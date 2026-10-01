<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Collaborator;
use App\Form\CollaboratorForm;
use App\Repository\CollaboratorRepository;
use App\Repository\PaginationService;
use App\Repository\SkillRepository;
use App\Service\CollaboratorCvStorage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
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
        $limit = 30;
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
            'SELECT s.id AS id, s.name AS name, COUNT(cs.collaborator_id) AS total
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
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        CollaboratorCvStorage $cvStorage,
    ): Response
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
            /** @var UploadedFile|null $cvFile */
            $cvFile = $form->get('cvFile')->getData();
            if ($cvFile !== null) {
                $originalName = $cvStorage->originalName($cvFile);
                $collaborator
                    ->setCvFilename($cvStorage->store($cvFile))
                    ->setCvOriginalName($originalName);
            }

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
    public function edit(
        Request $request,
        Collaborator $collaborator,
        EntityManagerInterface $entityManager,
        CollaboratorCvStorage $cvStorage,
    ): Response
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
            $previousFilename = $collaborator->getCvFilename();
            /** @var UploadedFile|null $cvFile */
            $cvFile = $form->get('cvFile')->getData();
            $removeCv = true === $form->get('removeCv')->getData();

            if ($cvFile !== null) {
                $originalName = $cvStorage->originalName($cvFile);
                $collaborator
                    ->setCvFilename($cvStorage->store($cvFile))
                    ->setCvOriginalName($originalName);
            } elseif ($removeCv) {
                $collaborator
                    ->setCvFilename(null)
                    ->setCvOriginalName(null);
            }

            $entityManager->flush();

            if (($cvFile !== null || $removeCv) && $previousFilename !== $collaborator->getCvFilename()) {
                $cvStorage->remove($previousFilename);
            }

            return $this->redirectToRoute('app_collaborator_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('collaborator/edit.html.twig', [
            'collaborator' => $collaborator,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/cv', name: 'app_collaborator_cv_download', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function downloadCv(
        Request $request,
        Collaborator $collaborator,
        CollaboratorCvStorage $cvStorage,
    ): BinaryFileResponse
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $collaborator->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $filename = $collaborator->getCvFilename();
        if ($filename === null || !is_file($cvStorage->path($filename))) {
            throw $this->createNotFoundException('CV introuvable.');
        }

        $response = new BinaryFileResponse($cvStorage->path($filename));
        $response->setContentDisposition(
            $request->query->getBoolean('inline')
                ? ResponseHeaderBag::DISPOSITION_INLINE
                : ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $collaborator->getCvOriginalName() ?? 'cv.pdf',
        );
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->setPrivate();

        return $response;
    }

    #[Route('/{id}', name: 'app_collaborator_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Collaborator $collaborator,
        EntityManagerInterface $entityManager,
        CollaboratorCvStorage $cvStorage,
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $collaborator->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete'.$collaborator->getId(), $request->getPayload()->getString('_token'))) {
            $cvFilename = $collaborator->getCvFilename();
            $entityManager->remove($collaborator);
            $entityManager->flush();
            $cvStorage->remove($cvFilename);
        }

        return $this->redirectToRoute('app_collaborator_index', [], Response::HTTP_SEE_OTHER);
    }
}

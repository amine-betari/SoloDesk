<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Form\SkillForm;
use App\Repository\PaginationService;
use App\Repository\SkillRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\Translation\TranslatorInterface;

#[Route('/skills')]
final class SkillController extends AbstractController
{
    #[Route(name: 'app_skill_index', methods: ['GET'])]
    public function index(
        SkillRepository $skillRepository,
        Request $request,
        PaginationService $paginator
    ): Response {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 30;
        $q = trim((string) $request->query->get('q', ''));
        $filter = trim((string) $request->query->get('filter', 'all'));
        $allowedFilters = ['all', 'core', 'non_core', 'used', 'unused'];
        if (!in_array($filter, $allowedFilters, true)) {
            $filter = 'all';
        }

        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $qb = $skillRepository->createQueryBuilder('s')
            ->andWhere('s.company = :company')
            ->setParameter('company', $company)
            ->orderBy('s.name', 'ASC');

        if ($q !== '') {
            $qb->andWhere('s.name LIKE :q')
                ->setParameter('q', '%' . $q . '%');
        }

        switch ($filter) {
            case 'core':
                $qb->andWhere('s.isCore = true');
                break;
            case 'non_core':
                $qb->andWhere('s.isCore = false');
                break;
            case 'used':
                $qb->andWhere('EXISTS (
                    SELECT usedCollaborator.id
                    FROM App\Entity\Collaborator usedCollaborator
                    JOIN usedCollaborator.skills usedSkill
                    WHERE usedSkill = s
                )');
                break;
            case 'unused':
                $qb->andWhere('NOT EXISTS (
                    SELECT usedCollaborator.id
                    FROM App\Entity\Collaborator usedCollaborator
                    JOIN usedCollaborator.skills usedSkill
                    WHERE usedSkill = s
                )');
                break;
        }

        $pagination = $paginator->paginate($qb, $page, $limit);
        $usageCounts = [];
        foreach ($pagination['items'] as $skill) {
            $usageCounts[$skill->getId()] = $skill->getCollaborators()->count();
        }

        return $this->render('skill/index.html.twig', [
            'pagination' => $pagination,
            'q' => $q,
            'filter' => $filter,
            'usageCounts' => $usageCounts,
        ]);
    }

    #[Route('/new', name: 'app_skill_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $skill = new Skill();
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $skill->setCompany($company);
        }

        $form = $this->createForm(SkillForm::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($skill);
            $entityManager->flush();

            return $this->redirectToRoute('app_skill_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('skill/new.html.twig', [
            'skill' => $skill,
            'form' => $form,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_skill_edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(Request $request, Skill $skill, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $skill->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        $form = $this->createForm(SkillForm::class, $skill);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_skill_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('skill/edit.html.twig', [
            'skill' => $skill,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_skill_delete', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function delete(
        Request $request,
        Skill $skill,
        EntityManagerInterface $entityManager,
        TranslatorInterface $translator
    ): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $skill->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }

        if ($this->isCsrfTokenValid('delete'.$skill->getId(), $request->getPayload()->getString('_token'))) {
            if (!$skill->getCollaborators()->isEmpty()) {
                $this->addFlash('error', $translator->trans('skill.delete_used_error'));

                return $this->redirectToRoute('app_skill_index', [], Response::HTTP_SEE_OTHER);
            }

            $entityManager->remove($skill);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_skill_index', [], Response::HTTP_SEE_OTHER);
    }
}

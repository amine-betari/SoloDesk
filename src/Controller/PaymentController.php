<?php

namespace App\Controller;

use App\Entity\Payment;
use App\Entity\Project;
use App\Form\PaymentForm;
use App\Repository\PaginationService;
use App\Repository\PaymentRepository;
use App\Repository\ProjectRepository;
use App\Repository\SalesDocumentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Services\FilterService;
use App\Form\Search\PaymentFilterForm;

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    #[Route(name: 'app_payment_index')]
    public function index(
        PaymentRepository $paymentRepository,
        Request $request,
        PaginationService $paginator,
        FilterService $filterService,
        \App\Service\CompanySettings $settings
    ): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;
        $session = $request->getSession();

        // Search Form
        $filterForm = $this->createForm(PaymentFilterForm::class);
        // Search Form
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $activityStartDate = $settings->getDate($company, \App\Service\CompanySettings::KEY_ACTIVITY_START_DATE, new \DateTimeImmutable('2017-01-01'));

        $qb = $paymentRepository->createQueryBuilder('p')
            ->andWhere('p.company = :company')
            ->andWhere('p.date >= :start')
            ->setParameter('company', $company)
            ->setParameter('start', $activityStartDate)
            ->orderBy('p.date', 'DESC');

        // Handle Generic
        $filterForm = $filterService->handle(
            $request,
            $qb,
            $filterForm,
            $session,
            'payment_filter',
            'app_payment_index'
        );

        $pagination = $paginator->paginate($qb, $page, $limit);

        return $this->render('payment/index.html.twig', [
            'pagination' => $pagination,
            'filterForm' => $filterForm->createView(), // on envoie le form à la vu
        ]);
    }

    #[Route('/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ?int $projectId = null,
        EntityManagerInterface $entityManager): Response
    {
        $payment = new Payment();
        $payment->setDate(new \DateTimeImmutable());
        $company = $this->getUser()?->getCompany();
        if ($company) {
            $payment->setCompany($company);
        }

        $form = $this->createForm(PaymentForm::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($payment->getSalesDocument()) {
                $payment->setCompany($payment->getSalesDocument()->getCompany());
            }
            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('app_payment_show',
                ['id' => $payment->getId()]
            );
        }

        return $this->render('payment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_payment_show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $payment->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_payment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $payment->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        $form = $this->createForm(PaymentForm::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('payment/edit.html.twig', [
            'payment' => $payment,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_payment_delete', methods: ['POST'])]
    public function delete(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company || $payment->getCompany()?->getId() !== $company->getId()) {
            throw $this->createAccessDeniedException('Accès refusé.');
        }
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
    }

}

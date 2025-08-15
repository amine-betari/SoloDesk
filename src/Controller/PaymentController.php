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

#[Route('/payment')]
final class PaymentController extends AbstractController
{
    #[Route(name: 'app_payment_index', methods: ['GET'])]
    public function index(
        PaymentRepository $paymentRepository,
        Request $request,
        PaginationService $paginator
    ): Response
    {
        $page = max(1, $request->query->getInt('page', 1));
        $limit = 10;

        $qb = $paymentRepository->createQueryBuilder('p')
            ->orderBy('p.date', 'ASC');

        $pagination = $paginator->paginate($qb, $page, $limit);

        return $this->render('payment/index.html.twig', [
            'pagination' => $pagination
        ]);
    }

    #[Route('/new', name: 'app_payment_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        ?int $projectId = null,
        EntityManagerInterface $entityManager): Response
    {
        $payment = new Payment();

        // Récupérer l'ID du projet depuis l'URL (ex: /payment/new?project=5)
        $projectId = $request->query->get('project');
        if ($projectId) {
            $project = $entityManager->getRepository(Project::class)->find($projectId);
            if ($project) {
                $payment->setProject($project);
                $payment->setInitialProject($project); // verrouillage
            }
        }

        $form = $this->createForm(PaymentForm::class, $payment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payment);
            $entityManager->flush();

            return $this->redirectToRoute('app_project_show', ['id' => $payment->getProject()->getId()]);
        }

        return $this->render('payment/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/{id}', name: 'app_payment_show', methods: ['GET'])]
    public function show(Payment $payment): Response
    {
        return $this->render('payment/show.html.twig', [
            'payment' => $payment,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_payment_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Payment $payment, EntityManagerInterface $entityManager): Response
    {
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
        if ($this->isCsrfTokenValid('delete'.$payment->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($payment);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_payment_index', [], Response::HTTP_SEE_OTHER);
    }

}

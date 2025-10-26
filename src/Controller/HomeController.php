<?php
namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\EstimateRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProjectRepository;
use App\Repository\SalesDocumentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class HomeController extends AbstractController
{
    #[Route('/', name: 'home')]
    public function index(
        ClientRepository $clientRepository,
        ProjectRepository $projectRepository,
        EstimateRepository $estimateRepository,
        PaymentRepository $paymentRepository,
        SalesDocumentRepository $salesDocumentRepository,
        ChartBuilderInterface $chartBuilder
    ): Response {
        $clients   = $clientRepository->findAll();
        $projects  = $projectRepository->findAll();

        $estimates      = $salesDocumentRepository->findBy(['type' => 'estimate']);
        $invoicesManual = $salesDocumentRepository->count(['type' => 'invoice']);
        $invoices       = $salesDocumentRepository->findBy(['type' => 'invoice']);

        $totalClients   = $clientRepository->count([]);
        $totalProjects  = $projectRepository->count([]);
        $totalEstimates = $estimateRepository->count([]);

        $totalInvoices  = $invoicesManual;




        $data = $clientRepository->countClientsGroupedByYear(
            new \DateTime('2017-01-01'),
            new \DateTime('2025-12-31')
        );

        $clientData = $chartBuilder->createChart(Chart::TYPE_BAR);
        $clientData->setData([
            'labels' => array_keys($data),
            'datasets' => [[
                'label' => 'Nouveaux clients par année',
                'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                'borderColor' => 'rgb(54, 162, 235)',
                'data' => array_values($data),
            ]],
        ]);

        $clientData->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,    // incréments de 1 (valeurs entières)
                        'precision' => 0,   // pas de décimales
                    ],
                ],
            ],
        ]);


        $projectData = $projectRepository->countProjectsActiveByYear(
            new \DateTime('2017-01-01'),
            new \DateTime('2025-12-31')
        );
        $projectChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $projectChart->setData([
            'labels' => array_keys($projectData),
            'datasets' => [[
                'label' => 'Projets par année (par startDate)',
                'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                'borderColor' => 'rgb(75, 192, 192)',
                'data' => array_values($projectData),
            ]],
        ]);
        $projectChart->setOptions([
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'stepSize' => 1,    // incréments de 1 (valeurs entières)
                        'precision' => 0,   // pas de décimales
                    ],
                ],
            ],
        ]);





        // Graph réellement paies
        $payments = $paymentRepository->findPaymentsForReports();
        $revenuesFromPayments = [];

        foreach ($payments as $payment) {
            $salesDocument = $payment->getSalesDocument();
            $project       = $payment->getSalesDocument()->getProject();

            // On tente de récupérer le client depuis la facture, sinon depuis le projet
            $client = $salesDocument?->getClient() ?? $project?->getClient();
            if (!$client) continue; // on skip si aucun client n'existe

            $devise = $client?->getCurrency() ?? $project?->getCurrency() ?? 'EUR'; // fallback devise si besoin
            $key = $client->getName() . ' (' . $devise . ')';

            $revenuesFromPayments[$key] = ($revenuesFromPayments[$key] ?? 0) + $payment->getAmount();
        }


        // Préparer les labels et données pour le chart
        $clientsPayments = array_keys($revenuesFromPayments);
        $revenuesPayments = array_values($revenuesFromPayments);

        $paymentsChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $paymentsChart->setData([
            'labels' => $clientsPayments,
            'datasets' => [[
                'label' => 'Revenus réels par paiements',
                'backgroundColor' => ['#4ade80', '#60a5fa', '#facc15', '#f87171', '#f472b6', '#a78bfa'],
                'data' => $revenuesPayments,
            ]],
        ]);
        $paymentsChart->setOptions([
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ]);


        // EstimateStats Graph
        $estimateStats = [];
        foreach ($estimates as $e) {
            $status = $e->getStatus(); // ex: 'accepted', 'rejected', 'draft', 'sent'
            // Traduction
            switch ($status) {
                case 'draft':    $label = 'Brouillon'; break;
                case 'sent':     $label = 'Envoyé'; break;
                case 'accepted': $label = 'Accepté'; break;
                case 'rejected': $label = 'Refusé'; break;
                default:         $label = $status; break;
            }

            // $estimateStats[$e->getStatus()] = ($estimateStats[$e->getStatus()] ?? 0) + 1;
            $estimateStats[$label] = ($estimateStats[$label] ?? 0) + 1;

        }
        $estimateChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $estimateChart->setData([
            'labels' => array_keys($estimateStats),
            'datasets' => [[
                'label' => 'Devis',
                'backgroundColor' => ['#facc15','#60a5fa','#4ade80','#f87171'],
                'data' => array_values($estimateStats),
            ]],
        ]);


        $invoicesByYear = [];
        foreach ($invoices as $invoice) {
            $year = $invoice->getInvoiceDate() ? $invoice->getInvoiceDate()->format('Y') : 'N/A';
            if (!isset($invoicesByYear[$year])) {
                $invoicesByYear[$year] = 0;
            }
            $invoicesByYear[$year]++;
        }

        // Crée le chart
        ksort($invoicesByYear); // Trie par année croissante

        $invoiceChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $invoiceChart->setData([
            'labels' => array_keys($invoicesByYear),
            'datasets' => [[
                'label' => 'Nombre de factures par année',
                'backgroundColor' => 'rgba(255, 159, 64, 0.5)',
                'borderColor' => 'rgb(255, 159, 64)',
                'data' => array_values($invoicesByYear),
            ]],
        ]);
        $invoiceChart->setOptions([
            'scales' => [
                'y' => ['beginAtZero' => true, 'ticks' => ['precision' => 0]],
            ],
        ]);


        return $this->render('home/index.html.twig', [
         //   'revenuesGlobalChart' => $revenuesGlobalChart,
            'clientDataChart' => $clientData,
            'projectDataChart' => $projectChart,
            'paymentsChart' => $paymentsChart,
            'clients' => $clients,
            'projects' => $projects,
            'estimates' => $estimates,
            'totalClients' => $totalClients,
            'totalProjects' => $totalProjects,
            'totalEstimates' => $totalEstimates,
            'totalInvoices' => $totalInvoices,
            'estimateChart' => $estimateChart,
            'invoiceChart' => $invoiceChart,
        ]);
    }


    #[Route('/stats/ca/trimestre', name: 'stats_ca_trimestre')]
    public function caParTrimestre(PaymentRepository $paymentRepository, ChartBuilderInterface $chartBuilder, Request $request): Response
    {
        $anneeSelectionnee = $request->query->get('annee', date('Y')); // année par défaut
        $anneeDebut = 2017;
        $anneesDisponibles = range($anneeDebut, date('Y'));

        $payments = $paymentRepository->findPaymentsForReports(); // ✅ paiement réel
        $revenusParTrimestreEtClient = [];

        $trimestrePeriodes = [
            'T1' => 'Janv - Mars',
            'T2' => 'Avril - Juin',
            'T3' => 'Juil - Sept',
            'T4' => 'Oct - Déc',
        ];

        foreach ($payments as $payment) {
            $salesDocument = $payment->getSalesDocument();
            $project = $salesDocument?->getProject();

            // Récupère le client via facture ou projet
            $client = $salesDocument?->getClient() ?? $project?->getClient();
            if (!$client) continue;

            $devise = $client->getCurrency() ?? $project?->getCurrency() ?? 'EUR';
            $key = $client->getName() . ' (' . $devise . ')';

            $paymentDate = $payment->getDate();
            if (!$paymentDate) continue;

            $year = $paymentDate->format('Y');
            if ($year != $anneeSelectionnee) continue;

            $quarterNumber = (int) ceil($paymentDate->format('m') / 3);
            $quarter = 'T' . $quarterNumber;
            $periode = sprintf('%s-%s (%s)', $year, $quarter, $trimestrePeriodes[$quarter]);

            if (!isset($revenusParTrimestreEtClient[$periode])) {
                $revenusParTrimestreEtClient[$periode] = [];
            }
            if (!isset($revenusParTrimestreEtClient[$periode][$key])) {
                $revenusParTrimestreEtClient[$periode][$key] = 0;
            }

            $revenusParTrimestreEtClient[$periode][$key] += $payment->getAmount();
        }

        // Crée les charts
        $charts = [];
        foreach ($revenusParTrimestreEtClient as $periode => $clients) {
            $chart = $chartBuilder->createChart(Chart::TYPE_PIE);
            $chart->setData([
                'labels' => array_keys($clients),
                'datasets' => [[
                    'label' => 'CA ' . $periode,
                    'backgroundColor' => ['#4ade80', '#60a5fa', '#facc15', '#f87171'],
                    'data' => array_values($clients),
                ]],
            ]);
            $chart->setOptions(['plugins' => ['legend' => ['position' => 'right']]]);
            $charts[$periode] = $chart;
        }

        return $this->render('stats/trimestriels.html.twig', [
            'charts' => $charts,
            'annees' => $anneesDisponibles,
            'anneeSelectionnee' => $anneeSelectionnee,
        ]);
    }
}

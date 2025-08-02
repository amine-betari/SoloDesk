<?php
namespace App\Controller;

use App\Repository\ClientRepository;
use App\Repository\EstimateRepository;
use App\Repository\PaymentRepository;
use App\Repository\ProjectRepository;
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
        ChartBuilderInterface $chartBuilder
    ): Response {
        $clients   = $clientRepository->findAll();
        $projects  = $projectRepository->findAll();
        $estimates = $estimateRepository->findAll();

        $totalClients   = $clientRepository->count([]);
        $totalProjects  = $projectRepository->count([]);
        $totalEstimates = $estimateRepository->count([]);


        $data = $clientRepository->countClientsGroupedByYear(
        //    new \DateTime('2019-01-01'),
        //    new \DateTime('2023-12-31')
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
        //    new \DateTime('2017-01-01'),
        //    new \DateTime('2025-12-31')
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



        $projectFinishedData = $projectRepository->findFinishedProjects();

        $revenuesPerClient = [];

        foreach ($projectFinishedData as $project) {
            $client = $project->getClient();
            if (!$client) {
                continue;
            }

            $clientName = $client->getName();
            $devise = $project->getCurrency();
            $amount = $project->getCalculatedAmount();

            // Clé unique : Client + Devise
            $key = $clientName . ' (' . $devise . ')';

            if (!isset($revenuesPerClient[$key])) {
                $revenuesPerClient[$key] = 0;
            }

            $revenuesPerClient[$key] += $amount;
        }

        $clientsGlobal = array_keys($revenuesPerClient);
        $revenuesGlobal = array_values($revenuesPerClient);

        $revenuesGlobalChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $revenuesGlobalChart->setData([
            'labels' => $clientsGlobal,
            'datasets' => [[
                'label' => 'Revenus par client',
                'backgroundColor' => ['#4ade80', '#60a5fa', '#facc15', '#f87171'],
                'data' => $revenuesGlobal,
            ]],
        ]);

        $revenuesGlobalChart->setOptions([
            'plugins' => [
                'legend' => [
                    'position' => 'right',
                ],
            ],
        ]);

        // Graph réellement paies
        $payments = $paymentRepository->findPaymentsForFinishedProjects();
        $revenuesFromPayments = [];
        foreach ($payments as $payment) {
            $project = $payment->getProject();
            $client = $project->getClient();
            if (!$client) {
                continue;
            }
            $clientName = $client->getName();
            $devise = $project->getCurrency();
            $amount = (float) $payment->getAmount();

            $key = $clientName . ' (' . $devise . ')';

            if (!isset($revenuesFromPayments[$key])) {
                $revenuesFromPayments[$key] = 0;
            }
            $revenuesFromPayments[$key] += $amount;
        }

        $clientsPayments = array_keys($revenuesFromPayments);
        $revenuesPayments = array_values($revenuesFromPayments);

        $paymentsChart = $chartBuilder->createChart(Chart::TYPE_PIE);
        $paymentsChart->setData([
            'labels' => $clientsPayments,
            'datasets' => [[
                'label' => 'Revenus réels par paiements',
                'backgroundColor' => ['#4ade80', '#60a5fa', '#facc15', '#f87171'],
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

        return $this->render('home/index.html.twig', [
          //  'charts' => $charts,
            'revenuesGlobalChart' => $revenuesGlobalChart,
            'clientDataChart' => $clientData,
            'projectDataChart' => $projectChart,
            'paymentsChart' => $paymentsChart,
            'clients' => $clients,
            'projects' => $projects,
            'estimates' => $estimates,
            'totalClients' => $totalClients,
            'totalProjects' => $totalProjects,
            'totalEstimates' => $totalEstimates,
        ]);
    }


    #[Route('/stats/clients/trimestriels', name: 'stats_clients_trimestriels')]
    public function revenusParTrimestre(
        ProjectRepository $projectRepository,
        ChartBuilderInterface $chartBuilder,
        Request $request
    ): Response {
        $annee = $request->query->get('annee', date('Y'));

        $projects = $projectRepository->findFinishedProjects();
        $revenusParTrimestreEtClient = [];
        $anneesDisponibles = [];

        // 👇 Tableau pour rendre les trimestres lisibles
        $trimestrePeriodes = [
            'T1' => 'Janv - Mars',
            'T2' => 'Avril - Juin',
            'T3' => 'Juil - Sept',
            'T4' => 'Oct - Déc',
        ];

        foreach ($projects as $project) {
            $client = $project->getClient();
            if (!$client) {
                continue;
            }

            $clientName = $client->getName();
            $devise = $project->getCurrency();
            $amount = $project->getCalculatedAmount();

            $createdAt = $project->getStartDate();
            $year = $createdAt->format('Y');

            if (!in_array($year, $anneesDisponibles)) {
                $anneesDisponibles[] = $year;
            }

            if ($year !== $annee) {
                continue;
            }

            $quarterNumber = (int) ceil($createdAt->format('m') / 3);
            $quarter = 'T' . $quarterNumber;

            // 🟢 Ajoute une description lisible dans la clé "période"
            $periode = sprintf('%s-%s (%s)', $year, $quarter, $trimestrePeriodes[$quarter]);

            $key = $clientName . ' (' . $devise . ')';

            if (!isset($revenusParTrimestreEtClient[$periode])) {
                $revenusParTrimestreEtClient[$periode] = [];
            }

            if (!isset($revenusParTrimestreEtClient[$periode][$key])) {
                $revenusParTrimestreEtClient[$periode][$key] = 0;
            }

            $revenusParTrimestreEtClient[$periode][$key] += $amount;
        }

        sort($anneesDisponibles);

        // 🔵 Création des charts
        $charts = [];
        foreach ($revenusParTrimestreEtClient as $periode => $clients) {
            $labels = array_keys($clients);
            $data = array_values($clients);

            $chart = $chartBuilder->createChart(Chart::TYPE_PIE);
            $chart->setData([
                'labels' => $labels,
                'datasets' => [[
                    'label' => 'Revenus ' . $periode,
                    'backgroundColor' => ['#4ade80', '#60a5fa', '#facc15', '#f87171'],
                    'data' => $data,
                ]],
            ]);

            $chart->setOptions([
                'plugins' => [
                    'legend' => [
                        'position' => 'right',
                    ],
                ],
            ]);

            $charts[$periode] = $chart;
        }

        // 🔵 Trie les périodes dans l’ordre chrono
        uksort($charts, function ($a, $b) {
            // Extraire année et trimestre (ex: 2025-T1 (Janv - Mars)) → 2025, 1
            [$partA] = explode(' ', $a); // "2025-T1"
            [$partB] = explode(' ', $b);

            [$yearA, $trimA] = explode('-T', $partA);
            [$yearB, $trimB] = explode('-T', $partB);

            $timestampA = strtotime($yearA . '-' . ((($trimA - 1) * 3) + 1) . '-01');
            $timestampB = strtotime($yearB . '-' . ((($trimB - 1) * 3) + 1) . '-01');

            return $timestampA <=> $timestampB;
        });

        return $this->render('stats/trimestriels.html.twig', [
            'charts' => $charts,
            'annees' => $anneesDisponibles,
            'anneeSelectionnee' => $annee,
        ]);
    }




}

<?php

namespace App\Controller;

use App\Form\SettingsForm;
use App\Service\CompanySettings;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/settings')]
class SettingsController extends AbstractController
{
    #[Route('', name: 'app_settings', methods: ['GET', 'POST'])]
    public function index(Request $request, CompanySettings $settings): Response
    {
        $company = $this->getUser()?->getCompany();
        if (!$company) {
            throw $this->createAccessDeniedException('Aucune entreprise associée à cet utilisateur.');
        }

        $defaultRate = 0.01;
        $defaultStart = new \DateTimeImmutable('2017-01-01');

        $data = [
            'taxImpotRate' => $settings->getFloat($company, CompanySettings::KEY_TAX_IMPOT_RATE, $defaultRate) * 100,
            'activityStartDate' => $settings->getDate($company, CompanySettings::KEY_ACTIVITY_START_DATE, $defaultStart),
        ];

        $form = $this->createForm(SettingsForm::class, $data);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payload = $form->getData();
            $ratePercent = (float) ($payload['taxImpotRate'] ?? 0);
            $rateDecimal = $ratePercent / 100;
            $startDate = $payload['activityStartDate'] ?? $defaultStart;

            $settings->setFloat($company, CompanySettings::KEY_TAX_IMPOT_RATE, $rateDecimal);
            $settings->setDate($company, CompanySettings::KEY_ACTIVITY_START_DATE, $startDate);

            $this->addFlash('success', 'Paramètres enregistrés.');
            return $this->redirectToRoute('app_settings');
        }

        return $this->render('settings/index.html.twig', [
            'form' => $form,
        ]);
    }
}

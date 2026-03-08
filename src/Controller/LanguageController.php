<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

final class LanguageController extends AbstractController
{
    #[Route('/lang/{_locale}', name: 'app_lang_switch', requirements: ['_locale' => 'fr|en|ar'])]
    public function switch(Request $request, string $_locale): Response
    {
        $request->getSession()->set('_locale', $_locale);

        $referer = $request->headers->get('referer');
        return $this->redirect($referer ?: $this->generateUrl('home'));
    }
}

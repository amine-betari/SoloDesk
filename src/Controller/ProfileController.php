<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Form\ChangePasswordFormType;
use Symfony\Component\Form\FormError;


class ProfileController extends AbstractController
{
    #[Route('/profile', name: 'app_profile')]
    public function profile(
        Request $request,
        UserPasswordHasherInterface $passwordHasher,
        EntityManagerInterface $em

    ): Response {
        $user = $this->getUser();

        $form = $this->createForm(ChangePasswordFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // Vérifier que l'ancien mot de passe est correct
            if ($passwordHasher->isPasswordValid($user, $data['oldPassword'])) {
                $newHashedPassword = $passwordHasher->hashPassword($user, $data['newPassword']);
                $user->setPassword($newHashedPassword);

                $em->flush();

                $this->addFlash('success', 'Mot de passe mis à jour avec succès !');
                return $this->redirectToRoute('app_profile');
            } else {
                $form->get('oldPassword')->addError(new FormError('Mot de passe actuel incorrect.'));
            }
        }

        return $this->render('profile/index.html.twig', [
            'changePasswordForm' => $form->createView(),
        ]);
    }
}

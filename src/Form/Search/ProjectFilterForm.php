<?php

namespace App\Form\Search;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\Client;
use App\Form\AutoComplete\ClientAutocompleteField;
use Symfony\Component\HttpFoundation\RequestStack;

class ProjectFilterForm extends AbstractType
{
    public function __construct(
        protected RequestStack $requestStack,
        protected EntityManagerInterface $entityManager,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $searchParams = $this->requestStack->getSession()->get('project_filter', []);
        $client       = $searchParams['client'] ?? null;

        if ($client instanceof Client) {
            // Forcer Doctrine à travailler avec une entité managée
            $client = $this->entityManager->getReference(Client::class, $client->getId());
        }
        $builder
            ->add('client', ClientAutocompleteField::class, [
                'required' => false,
                'placeholder' => 'Tous les clients',
                'data' => $client,
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
            ]);
        $builder->add('search', SubmitType::class, ['label' => 'Filtrer']);
        $builder->add('reset', SubmitType::class, ['label' => 'Reset']);

    }
}

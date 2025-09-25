<?php

namespace App\Form;

use App\Constants\ProjectStatuses;
use App\Constants\ProjectTypes;
use App\Entity\Client;
use App\Entity\Project;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\AutoComplete\ClientAutocompleteField;

class ProjectForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('projectNumber', TextType::class, [
                'required' => true,
                'label' => 'Référence',
                'disabled' => $options['data'] && $options['data']->getId() === null, // désactivé en création
            ])
            ->add('startDate')
            ->add('endDate')
            ->add('status', ChoiceType::class, [
                    'choices' => ProjectStatuses::CHOICES,
                    'label' => 'Statut du projet',
                ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type de projet',
                'choices' => ProjectTypes::TYPES,
                'placeholder' => 'Sélectionner un type',
            ])
            ->add('startDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de premier contact',
                'html5' => true, // active le datepicker natif
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'Date de premier contact',
                'html5' => true, // active le datepicker natif
            ])
            ->add('amount', /*MoneyType::class, [
                'label' => 'Montant HT',
                'currency' => 'EUR',
                'required' => true,
                'attr' => ['class' => 'w-full text-black'],
            ]*/)
            ->add('vatRate', NumberType::class, [
                'label' => 'TVA (%)',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => ['class' => 'w-full text-black', 'step' => '0.01'],
            ])
            ->add('noVat', CheckboxType::class, [
                'mapped' => false,
                'label' => 'Pas de TVA',
                'required' => false,
            ])
            ->add('client', ClientAutocompleteField::class, [
                  'required' => true,
                  'choice_label' => 'name',
              ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'Description',
                'attr' => [
                    'class' => 'mt-1 w-full rounded-md border-gray-300 text-black',
                ],
            ])
            ->add('isRecurring', CheckboxType::class, [
                'label' => 'Projet récurrent',
                'required' => false,
            ])
            ->add('recurringAmount', TextType::class, [
                'label' => 'Montant récurrent',
                'required' => false,
                'row_attr' => ['class' => 'hidden'], // caché par défaut
            ])
            ->add('recurringPeriod', ChoiceType::class, [
                'label' => 'Périodicité',
                'required' => false,
                'choices' => [
                    'Mensuel' => 'monthly',
                    'Trimestriel' => 'quarterly',
                    'Annuel' => 'yearly',
                ],
                'placeholder' => 'Choisir une période',
                'row_attr' => ['class' => 'hidden'], // caché par défaut
            ])
            ->add('documents', CollectionType::class, [
                'entry_type' => FileType::class,
                'entry_options' => [
                    'label' => false,
                    'mapped' => false, // car on va instancier les objets Document à la main
                    'required' => false,
                    'attr' => ['class' => 'block w-full p-2 border rounded-md text-white']
                ],
                'allow_add' => true,
                'by_reference' => false,
                'mapped' => false, // important ici aussi !
                'label' => 'Documents',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}

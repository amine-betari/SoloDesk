<?php

namespace App\Form;

use App\Constants\EstimateStatuses;
use App\Entity\Client;
use App\Entity\Estimate;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EstimateForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de l’estimate',
                'required' => true,
            ])
            ->add('amount')
            ->add('estimateNumber', TextType::class, [
                'required' => true,
                'label' => 'Référence',
                'disabled' => $options['data'] && $options['data']->getId() === null, // désactivé en création
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date de début',
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'Date de fin',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => EstimateStatuses::CHOICES,
                'label' => 'Statut du devis'
            ])
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
            ->add('description')
            ->add('client', ClientAutocompleteField::class, [
                'required' => true,
                'choice_label' => 'name',
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
            'data_class' => Estimate::class,
        ]);
    }
}

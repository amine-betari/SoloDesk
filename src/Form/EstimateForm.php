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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\AutoComplete\ClientAutocompleteField;

class EstimateForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'estimate.name',
                'required' => true,
            ])
            ->add('amount')
            ->add('estimateNumber', TextType::class, [
                'required' => true,
                'label' => 'estimate.reference',
                'disabled' => $options['data'] && $options['data']->getId() === null, // désactivé en création
            ])
            ->add('startDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'estimate.start_date',
            ])
            ->add('endDate', DateType::class, [
                'widget' => 'single_text',
                'required' => false,
                'label' => 'estimate.end_date',
            ])
            ->add('status', ChoiceType::class, [
                'choices' => EstimateStatuses::CHOICES,
                'label' => 'estimate.status'
            ])
            ->add('vatRate', NumberType::class, [
                'label' => 'estimate.vat_rate',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => ['class' => 'w-full text-black', 'step' => '0.01'],
            ])
            ->add('noVat', CheckboxType::class, [
                'mapped' => false,
                'label' => 'estimate.no_vat',
                'required' => false,
            ])
            ->add('description', TextareaType::class, [
                'label' => 'estimate.description',
                'required' => false,
            ])
            ->add('client', ClientAutocompleteField::class, [
                'required' => true,
                'choice_label' => 'name',
                'label' => 'estimate.client',
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
                'label' => 'estimate.documents',
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

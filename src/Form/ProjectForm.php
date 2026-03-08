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
use App\Form\AutoComplete\InvoiceAutocompleteField;
use Doctrine\ORM\EntityRepository;

class ProjectForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $project = $options['data'] ?? null;

        $builder
            ->add('name', TextType::class, [
                'label' => 'project.name',
            ])
            ->add('projectNumber', TextType::class, [
                'required' => true,
                'label' => 'project.reference',
                'disabled' => $options['data'] && $options['data']->getId() === null, // désactivé en création
            ])
            ->add('status', ChoiceType::class, [
                    'choices' => ProjectStatuses::CHOICES,
                    'label' => 'project.status',
                ])
            ->add('type', ChoiceType::class, [
                'label' => 'project.type',
                'choices' => ProjectTypes::TYPES,
                'placeholder' => 'project.type_placeholder',
            ])
            ->add('typeDescription', TextType::class, [
                'label' => 'project.type_description',
                'required' => false,
            ])
            ->add('startDate', DateType::class, [
                'required' => true,
                'widget' => 'single_text',
                'label' => 'project.start_date',
                'html5' => true, // active le datepicker natif
            ])
            ->add('endDate', DateType::class, [
                'required' => false,
                'widget' => 'single_text',
                'label' => 'project.end_date',
                'html5' => true, // active le datepicker natif
            ])
            ->add('amount', /*MoneyType::class, [
                'label' => 'Montant HT',
                'currency' => 'EUR',
                'required' => true,
                'attr' => ['class' => 'w-full text-black'],
            ]*/)
            ->add('vatRate', NumberType::class, [
                'label' => 'project.vat_rate',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => ['class' => 'w-full text-black', 'step' => '0.01'],
            ])
            ->add('noVat', CheckboxType::class, [
                'mapped' => false,
                'label' => 'project.no_vat',
                'required' => false,
            ])
            ->add('client', ClientAutocompleteField::class, [
                  'required' => true,
                  'choice_label' => 'name',
                  'label' => 'project.client',
              ])
            ->add('description', TextareaType::class, [
                'required' => false,
                'label' => 'project.description',
                'attr' => [
                    'class' => 'mt-1 w-full rounded-md border-gray-300 text-black',
                ],
            ])
            ->add('isRecurring', CheckboxType::class, [
                'label' => 'project.recurring',
                'required' => false,
            ])
            ->add('recurringAmount', TextType::class, [
                'label' => 'project.recurring_amount',
                'required' => false,
                'row_attr' => ['class' => 'hidden'], // caché par défaut
            ])
            ->add('recurringPeriod', ChoiceType::class, [
                'label' => 'project.recurring_period',
                'required' => false,
                'choices' => [
                    'project.period_monthly' => 'monthly',
                    'project.period_quarterly' => 'quarterly',
                    'project.period_yearly' => 'yearly',
                ],
                'placeholder' => 'project.period_placeholder',
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
                'label' => 'project.documents',
            ])
        ;

        if ($project instanceof Project && $project->getId() !== null) {
            $builder->add('salesDocuments', InvoiceAutocompleteField::class, [
                'required' => false,
                'multiple' => true,
                'by_reference' => false,
                'label' => 'project.linked_invoices',
                'query_builder' => function (EntityRepository $er) use ($project) {
                    return $er->createQueryBuilder('s')
                        ->where('s.type = :type1 OR s.type = :type2')
                        ->andWhere('s.project IS NULL OR s.project = :project')
                        ->setParameter('type1', 'invoice')
                        ->setParameter('type2', 'project')
                        ->setParameter('project', $project)
                        ->orderBy('s.reference', 'ASC');
                },
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Project::class,
        ]);
    }
}

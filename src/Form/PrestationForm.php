<?php

declare(strict_types=1);

namespace App\Form;

use App\Entity\Collaborator;
use App\Entity\Company;
use App\Entity\Prestation;
use App\Form\AutoComplete\InvoiceAutocompleteField;
use App\Repository\CollaboratorRepository;
use Doctrine\ORM\EntityRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PrestationForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('label', TextType::class, [
                'label' => 'prestation.label',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un libellé']),
                ],
            ])
            ->add('collaborator', EntityType::class, [
                'class' => Collaborator::class,
                'choice_label' => 'name',
                'label' => 'prestation.collaborator',
                'disabled' => $options['collaborator_locked'],
                'query_builder' => function (CollaboratorRepository $repo) use ($options) {
                    $qb = $repo->createQueryBuilder('c')
                        ->orderBy('c.name', 'ASC');

                    if ($options['company'] instanceof Company) {
                        $qb->andWhere('c.company = :company')
                            ->setParameter('company', $options['company']);
                    }

                    return $qb;
                },
                'attr' => [
                    'class' => 'mt-1 w-full rounded-md border-gray-300 bg-gray-50 text-gray-900',
                ],
            ])
            ->add('salesDocument', InvoiceAutocompleteField::class, [
                'label' => 'prestation.sales_document',
                'required' => false,
                'query_builder' => function (EntityRepository $repo) use ($options) {
                    $qb = $repo->createQueryBuilder('s')
                        ->andWhere('s.type IN (:types)')
                        ->setParameter('types', ['invoice', 'project'])
                        ->orderBy('s.reference', 'ASC');

                    if ($options['company'] instanceof Company) {
                        $qb->andWhere('s.company = :company')
                            ->setParameter('company', $options['company']);
                    }

                    return $qb;
                },
            ])
            ->add('quantity', NumberType::class, [
                'label' => 'prestation.quantity',
                'required' => false,
                'scale' => 3,
                'html5' => true,
                'attr' => ['step' => '0.001'],
            ])
            ->add('unitPrice', NumberType::class, [
                'label' => 'prestation.unit_price',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'prestation.status',
                'choices' => [
                    'prestation.status_draft' => Prestation::STATUS_DRAFT,
                    'prestation.status_invoiced' => Prestation::STATUS_INVOICED,
                    'prestation.status_paid' => Prestation::STATUS_PAID,
                ],
            ])
            ->add('performedAt', DateType::class, [
                'label' => 'prestation.performed_at',
                'widget' => 'single_text',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'prestation.notes',
                'required' => false,
                'attr' => [
                    'rows' => 4,
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prestation::class,
            'company' => null,
            'collaborator_locked' => false,
        ]);

        $resolver->setAllowedTypes('company', ['null', Company::class]);
        $resolver->setAllowedTypes('collaborator_locked', ['bool']);
    }
}

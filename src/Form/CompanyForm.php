<?php

namespace App\Form;

use App\Entity\Company;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CompanyForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Nom de l’entreprise',
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer le nom de l’entreprise']),
                new Length([
                    'min' => 2,
                    'minMessage' => 'Le nom doit faire au moins {{ limit }} caractères',
                    'max' => 255,
                ]),
            ],
        ])
        ->add('ice', TextType::class, [
            'required' => false,
            'label' => 'ICE',
        ])
        ->add('fiscalId', TextType::class, [
            'required' => false,
            'label' => 'Identifiant fiscal (IF)',
        ])
        ->add('taxProfessional', TextType::class, [
            'required' => false,
            'label' => 'Taxe professionnelle',
        ])
        ->add('address', TextareaType::class, [
            'required' => false,
            'label' => 'Adresse',
            'attr' => ['rows' => 3],
        ])
        ->add('city', TextType::class, [
            'required' => false,
            'label' => 'Ville',
        ])
        ->add('country', TextType::class, [
            'required' => false,
            'label' => 'Pays',
        ])
        ->add('phone', TextType::class, [
            'required' => false,
            'label' => 'Téléphone',
        ])
        ->add('email', EmailType::class, [
            'required' => false,
            'label' => 'Email',
        ])
        ->add('logoFile', FileType::class, [
            'mapped' => false,
            'required' => false,
            'label' => 'Logo',
            'constraints' => [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'image/png',
                        'image/jpeg',
                        'image/svg+xml',
                        'image/webp',
                    ],
                    'mimeTypesMessage' => 'Logo non supporté (png/jpg/svg/webp).',
                ])
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Company::class,
        ]);
    }
}

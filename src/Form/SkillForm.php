<?php

namespace App\Form;

use App\Entity\Skill;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;

class SkillForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label' => 'Compétence',
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer une compétence']),
                new Length([
                    'min' => 2,
                    'minMessage' => 'Le nom doit faire au moins {{ limit }} caractères',
                    'max' => 120,
                ]),
            ],
        ])
        ->add('isCore', CheckboxType::class, [
            'label' => 'Compétence clé',
            'required' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Skill::class,
        ]);
    }
}

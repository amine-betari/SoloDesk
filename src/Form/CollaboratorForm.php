<?php

namespace App\Form;

use App\Entity\Collaborator;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Skill;
use App\Repository\SkillRepository;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class CollaboratorForm extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(['message' => 'Veuillez entrer un nom']),
                ],
            ])
            ->add('role', TextType::class, [
                'label' => 'Rôle',
                'required' => false,
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'Type',
                'choices' => [
                    'Salarié' => 'salarie',
                    'Freelance' => 'freelance',
                    'Collaborateur' => 'collaborateur',
                ],
            ])
            ->add('monthlyCost', NumberType::class, [
                'label' => 'Coût mensuel',
                'required' => false,
                'scale' => 2,
                'html5' => true,
                'attr' => ['step' => '0.01'],
            ])
            ->add('skills', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'label' => 'Compétences',
                'required' => false,
                'multiple' => true,
                'expanded' => false,
                'query_builder' => function (SkillRepository $repo) use ($options) {
                    return $repo->createQueryBuilder('s')
                        ->andWhere('s.company = :company')
                        ->setParameter('company', $options['company'])
                        ->orderBy('s.name', 'ASC');
                },
                'attr' => [
                    'class' => 'mt-1 w-full rounded-md border-gray-300 bg-gray-50 text-gray-900',
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Notes / Description',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                    'class' => 'w-full rounded-md border-gray-300 text-black d-none',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Collaborator::class,
            'company' => null,
        ]);
        $resolver->setAllowedTypes('company', ['null', \App\Entity\Company::class]);
    }
}

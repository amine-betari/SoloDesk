<?php

// src/Form/DocumentTemplateType.php
namespace App\Form;

use App\Entity\DocumentTemplate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, FileType, TextType, CheckboxType};
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class DocumentTemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $b, array $options): void
    {
        $b
            ->add('name', TextType::class)
            ->add('type', ChoiceType::class, [
                'choices' => [
                    'Facture' => DocumentTemplate::TYPE_INVOICE,
                    'Devis'   => DocumentTemplate::TYPE_ESTIMATE,
                ],
            ])
            ->add('format', ChoiceType::class, [
                'choices' => [
                    'Word (.docx)'  => DocumentTemplate::FORMAT_WORD,
                    'Excel (.xlsx)' => DocumentTemplate::FORMAT_EXCEL,
                    'PDF (HTML->PDF)' => DocumentTemplate::FORMAT_PDF,
                ],
            ])
            ->add('file', FileType::class, [
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => [
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/pdf',
                        ],
                        'mimeTypesMessage' => 'Fichier non supporté (docx/xlsx/pdf).',
                    ])
                ],
            ])
            ->add('isDefault', CheckboxType::class, [
                'required' => false,
                'label' => 'Définir comme modèle par défaut',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DocumentTemplate::class]);
    }
}

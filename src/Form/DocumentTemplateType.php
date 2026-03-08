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
            ->add('name', TextType::class, [
                'label' => 'templates.form.name',
            ])
            ->add('type', ChoiceType::class, [
                'label' => 'templates.form.type',
                'choices' => [
                    'templates.type_invoice' => DocumentTemplate::TYPE_INVOICE,
                    'templates.type_estimate' => DocumentTemplate::TYPE_ESTIMATE,
                ],
            ])
            ->add('format', ChoiceType::class, [
                'label' => 'templates.form.format',
                'choices' => [
                    'templates.format_word'  => DocumentTemplate::FORMAT_WORD,
                    'templates.format_excel' => DocumentTemplate::FORMAT_EXCEL,
                    'templates.format_pdf' => DocumentTemplate::FORMAT_PDF,
                ],
            ])
            ->add('file', FileType::class, [
                'label' => 'templates.form.file',
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
                'label' => 'templates.form.is_default',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => DocumentTemplate::class]);
    }
}

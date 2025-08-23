<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Symfony\Contracts\Translation\TranslatorInterface;

class AppExtension extends AbstractExtension
{
    public function __construct(private TranslatorInterface $translator) {}

    public function getFilters(): array
    {
        return [
            new TwigFilter('document_type_label', [$this, 'documentTypeLabel']),
        ];
    }

    public function documentTypeLabel(string $type): string
    {
        return match($type) {
            'estimate' => $this->translator->trans('Devis'),
            'invoice'  => $this->translator->trans('Facture'),
            'project'  => $this->translator->trans('Facture tirée du projet'),
            default    => $type,
        };
    }
}

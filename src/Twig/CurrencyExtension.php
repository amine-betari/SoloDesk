<?php

namespace App\Twig;

use NumberFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class CurrencyExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('format_currency', [$this, 'formatCurrency']),
        ];
    }

    /**
     * Formate un montant avec une devise selon une locale (fr_FR par défaut).
     *
     * @param float|int $amount
     * @param string $currency ISO 4217 currency code (ex: EUR, USD, MAD)
     * @param string|null $locale Locale pour la mise en forme (ex: fr_FR)
     *
     * @return string
     */
    public function formatCurrency($amount, string $currency): string
    {
        if (!is_numeric($amount)) {
            return '';
        }

        // Formate le nombre avec 2 décimales et une virgule comme séparateur décimal
        $formattedAmount = number_format($amount, 2, ',', ' ');

        // Ajoute la devise à la fin (espace insécable pour éviter de couper)
        return $formattedAmount . ' ' . $currency;
    }
}

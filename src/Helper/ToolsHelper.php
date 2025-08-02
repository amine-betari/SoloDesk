<?php

namespace App\Helper;

class ToolsHelper
{

    public function __construct()
    {

    }

    public function formatCurrency($amount, string $currency): string
    {
        if (!is_numeric($amount)) {
            return '';
        }

        $formattedAmount = number_format($amount, 2, ',', ' ');

        return $formattedAmount . ' ' . $currency;
    }

}

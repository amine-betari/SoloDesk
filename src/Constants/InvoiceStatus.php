<?php

namespace App\Constants;

class InvoiceStatus
{

    public const DRAFT = 'draft';
    public const SENT = 'sent';
    public const PARTIALLY_PAID = 'partially_paid';
    public const PAID = 'paid';
    public const CANCELLED = 'cancelled';

    public const CHOICES = [
        'Brouillon' => self::DRAFT,
        'Envoyé' => self::SENT,
        'Partiellement payé' => self::PARTIALLY_PAID,
        'Payé' => self::PAID,
        'Annulé' => self::CANCELLED,
    ];
}

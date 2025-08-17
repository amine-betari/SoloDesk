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
        self::DRAFT => self::DRAFT,
        self::SENT => self::SENT,
        self::PARTIALLY_PAID => self::PARTIALLY_PAID,
        self::PAID => self::PAID,
        self::CANCELLED => self::CANCELLED,
    ];
}

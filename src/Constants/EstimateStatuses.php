<?php

namespace App\Constants;

class EstimateStatuses
{
    public const DRAFT = 'draft';
    public const SENT = 'sent';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    public const EXPIRED = 'expired';

    public const CHOICES = [
        'Brouillon' => self::DRAFT,
        'Envoyé' => self::SENT,
        'Accepté' => self::ACCEPTED,
        'Refusé' => self::REJECTED,
        'Expiré' => self::EXPIRED,
    ];
}

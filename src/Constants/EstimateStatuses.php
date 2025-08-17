<?php

namespace App\Constants;

use Symfony\Contracts\Translation\TranslatorInterface;


class EstimateStatuses
{
    public const DRAFT = 'draft';
    public const SENT = 'sent';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    public const EXPIRED = 'expired';

    public const CHOICES = [
        self::DRAFT => self::DRAFT,
        self::SENT => self::SENT,
        self::ACCEPTED => self::ACCEPTED,
        self::REJECTED  => self::REJECTED,
        self::EXPIRED => self::EXPIRED,
    ];


}

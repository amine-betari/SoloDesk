<?php

namespace App\Constants;

class ProjectStatuses
{
    public const STAND_BY = 'stand_by';
    public const IN_PROGRESS = 'in_progress';
    public const LATE = 'late';
    public const COMPLETED = 'completed';
    public const CANCELLED = 'cancelled';

    public const CHOICES = [
        self::STAND_BY => self::STAND_BY,
        self::IN_PROGRESS => self::IN_PROGRESS,
        self::LATE => self::LATE,
        self::COMPLETED => self::COMPLETED,
        self::CANCELLED => self::CANCELLED,
    ];
}

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
        'En attente' => self::STAND_BY,
        'En cours' => self::IN_PROGRESS,
        'En retard' => self::LATE,
        'Terminé' => self::COMPLETED,
        'Annulé' => self::CANCELLED,
    ];
}

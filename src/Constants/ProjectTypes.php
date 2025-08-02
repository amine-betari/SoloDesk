<?php

namespace App\Constants;

class ProjectTypes
{
    // Types de projets
    public const REGIE = 'regie';
    public const FORFAIT = 'forfait';
    public const FORMATION = 'formation';
    public const HEBERGEMENT = 'hebergement';
    public const TMA = 'tma';
    public const AUDIT = 'audit';
    public const CONSULTING = 'consulting';
    public const AUTRE = 'other';

    // Tableau associatif clé-label (utile pour les formulaires)
    public const TYPES = [
        'Régie' => self::REGIE,
        'Forfait' => self::FORFAIT,
        'Formation' => self::FORMATION,
        'Hébergement' => self::HEBERGEMENT,
        'TMA' => self::TMA,
        'Audit' => self::AUDIT,
        'Consulting' => self::CONSULTING,
        'Autre' => self::AUTRE,
    ];

    // Exemple d’autres constantes (pour demain)
    // public const STATUSES = [
    //     'Actif' => 'active',
    //     'Inactif' => 'inactive',
    // ];
}

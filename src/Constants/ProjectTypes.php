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
        self::REGIE => self::REGIE,
        self::FORFAIT => self::FORFAIT,
        self::FORMATION => self::FORMATION,
        self::HEBERGEMENT => self::HEBERGEMENT,
        self::TMA => self::TMA,
        self::AUDIT => self::AUDIT,
        self::CONSULTING => self::CONSULTING,
        self::AUTRE => self::AUTRE,
    ];

    // Exemple d’autres constantes (pour demain)
    // public const STATUSES = [
    //     'Actif' => 'active',
    //     'Inactif' => 'inactive',
    // ];
}

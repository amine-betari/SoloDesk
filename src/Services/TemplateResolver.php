<?php

// src/Service/TemplateResolver.php
namespace App\Services;

use App\Entity\DocumentTemplate;
use Doctrine\ORM\EntityManagerInterface;

final class TemplateResolver
{
    public function __construct(private EntityManagerInterface $em) {}

    public function resolve(?string $type, ?string $format): ?DocumentTemplate
    {
        if (!$type || !$format) return null;

        return $this->em->getRepository(DocumentTemplate::class)->findOneBy(
            ['type' => $type, 'format' => $format, 'isDefault' => true]
        );
    }
}

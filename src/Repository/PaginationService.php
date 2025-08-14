<?php

// src/Repository/PaginationService.php
namespace App\Repository;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;

class PaginationService
{
    public function paginate(QueryBuilder $qb, int $page = 1, int $limit = 10): array
    {
        $paginator = new Paginator($qb);
        $paginator->getQuery()
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit);

        return [
            'items' => iterator_to_array($paginator),
            'total' => count($paginator),
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil(count($paginator) / $limit)
        ];
    }
}

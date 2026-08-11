<?php

declare(strict_types=1);

namespace App\Support;

/**
 * Pagination uniforme (Phase 2).
 *
 * Centralise la lecture des paramètres de page/limite et la construction
 * du résultat paginé, pour remplacer les blocs répétés dans les services.
 */
final class PaginationService
{
    /**
     * Normalise les paramètres de pagination depuis GET.
     *
     * @param array<string,mixed> $query
     * @return array{page:int,per_page:int,offset:int}
     */
    public function fromQuery(array $query, int $defaultPerPage = 20, array $allowedPerPage = [10, 20, 50, 100]): array
    {
        $page = max(1, (int) ($query['page'] ?? 1));
        $perPage = (int) ($query['per_page'] ?? $query['limit'] ?? $defaultPerPage);
        if ($allowedPerPage !== [] && !in_array($perPage, $allowedPerPage, true)) {
            $perPage = $defaultPerPage;
        }
        $perPage = max(1, min(100, $perPage));

        return [
            'page' => $page,
            'per_page' => $perPage,
            'offset' => ($page - 1) * $perPage,
        ];
    }

    /**
     * Construit le résultat paginé standard.
     *
     * @param array<int,array<string,mixed>> $data
     * @return array{data:array<int,array<string,mixed>>,total:int,page:int,per_page:int,last_page:int}
     */
    public function result(array $data, int $total, int $page, int $perPage): array
    {
        return [
            'data' => $data,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'last_page' => max(1, (int) ceil($total / max(1, $perPage))),
        ];
    }
}

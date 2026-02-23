<?php

if (!function_exists('cm_paginate')) {
    /**
     * Build pagination payload for components/views.
     *
     * @return array<string, mixed>
     */
    function cm_paginate(int $total, int $perPage, int $currentPage, int $window = 2): array
    {
        $total = max(0, $total);
        $perPage = max(1, $perPage);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $currentPage = min($lastPage, max(1, $currentPage));
        $offset = ($currentPage - 1) * $perPage;

        $pages = [];
        if ($lastPage <= 7) {
            for ($p = 1; $p <= $lastPage; $p++) {
                $pages[] = $p;
            }
        } else {
            $pages[] = 1;
            $left = max(2, $currentPage - $window);
            $right = min($lastPage - 1, $currentPage + $window);

            if ($left > 2) {
                $pages[] = -1;
            }

            for ($p = $left; $p <= $right; $p++) {
                $pages[] = $p;
            }

            if ($right < $lastPage - 1) {
                $pages[] = -1;
            }

            $pages[] = $lastPage;
        }

        return [
            'total' => $total,
            'per_page' => $perPage,
            'current' => $currentPage,
            'last' => $lastPage,
            'offset' => $offset,
            'has_prev' => $currentPage > 1,
            'has_next' => $currentPage < $lastPage,
            'pages' => $pages,
        ];
    }
}

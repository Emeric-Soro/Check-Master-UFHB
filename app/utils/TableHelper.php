<?php

/**
 * TableHelper - declarative helpers for table definitions.
 */
class TableHelper
{
    /**
     * @param int $total
     * @param int $page
     * @param int $perPage
     * @return array<string, int|bool>
     */
    public function paginationData(int $total, int $page, int $perPage): array
    {
        $perPage = max(1, $perPage);
        $totalPages = max(1, (int) ceil(max(0, $total) / $perPage));
        $page = max(1, min($page, $totalPages));
        $start = $total > 0 ? (($page - 1) * $perPage) + 1 : 0;
        $end = min($page * $perPage, max(0, $total));

        return [
            'totalPages' => $totalPages,
            'currentPage' => $page,
            'start' => $start,
            'end' => $end,
            'total' => max(0, $total),
            'hasPrev' => $page > 1,
            'hasNext' => $page < $totalPages,
        ];
    }
}

if (!function_exists('cm_column')) {
    /**
     * Define a data-table column.
     *
     * @param string $key
     * @param string $label
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    function cm_column(string $key, string $label, array $options = []): array
    {
        return array_merge([
            'key' => $key,
            'label' => $label,
            'sortable' => false,
            'width' => null,
            'align' => 'left',
            'type' => 'text',
            'format' => null,
        ], $options);
    }
}

if (!function_exists('cm_action_column')) {
    /**
     * Define the standard action column.
     *
     * @param array<int, array<string, mixed>> $actions
     * @return array<string, mixed>
     */
    function cm_action_column(array $actions = []): array
    {
        if (empty($actions)) {
            $actions = [
                ['label' => 'Modifier', 'icon' => 'fa-pen', 'action' => 'edit', 'class' => 'cm-btn-action is-edit'],
                ['label' => 'Supprimer', 'icon' => 'fa-trash', 'action' => 'delete', 'class' => 'cm-btn-action is-delete'],
            ];
        }

        return cm_column('_actions', 'Actions', [
            'type' => 'actions',
            'align' => 'center',
            'width' => '140px',
            'actions' => $actions,
        ]);
    }
}

if (!function_exists('cm_table_cell')) {
    /**
     * @param mixed $value
     */
    function cm_table_cell($value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cm_table_sort_url')) {
    function cm_table_sort_url(string $field, string $direction = 'asc'): string
    {
        $params = $_GET;
        $params['sort'] = $field;
        $params['dir'] = strtolower($direction) === 'desc' ? 'desc' : 'asc';
        return '?' . http_build_query($params);
    }
}

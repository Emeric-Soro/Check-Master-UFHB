<?php

namespace CheckMaster\Support;

/**
 * TableHelper - Registre des colonnes et utilitaires de tableau.
 */
class TableHelper
{
    /** @var array<string, array<int, array<string, mixed>>> */
    private array $configs = [
        'etudiant' => [
            ['key' => 'num_carte_etud', 'label' => 'Matricule', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'prenom_etu', 'label' => 'Prénoms', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'email_etu', 'label' => 'Email', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'telephone_etu', 'label' => 'Téléphone', 'sortable' => false, 'renderer' => 'text'],
            ['key' => 'lib_niv_etude', 'label' => 'Niveau', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'libelle_genre', 'label' => 'Genre', 'sortable' => true, 'renderer' => 'text'],
        ],
        'enseignant' => [
            ['key' => 'id_ens', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_ens', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'prenom_ens', 'label' => 'Prénoms', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'email_ens', 'label' => 'Email', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'telephone_ens', 'label' => 'Téléphone', 'sortable' => false, 'renderer' => 'text'],
            ['key' => 'lib_grade', 'label' => 'Grade', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'lib_fonction', 'label' => 'Fonction', 'sortable' => true, 'renderer' => 'text'],
        ],
        'utilisateur' => [
            ['key' => 'id_utilisateur', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'login', 'label' => 'Identifiant', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'libelle_GU', 'label' => 'Groupe', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'actif', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'utilisateur_status'],
        ],
        'candidature' => [
            ['key' => 'id_candidature', 'label' => 'ID', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'nom_etu', 'label' => 'Nom', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'prenom_etu', 'label' => 'Prénoms', 'sortable' => true, 'renderer' => 'text'],
            ['key' => 'date_candidature', 'label' => 'Date', 'sortable' => true, 'renderer' => 'date'],
            ['key' => 'statut_candidature', 'label' => 'Statut', 'sortable' => true, 'renderer' => 'badge', 'badge_entity' => 'candidature_status'],
        ]
    ];

    /**
     * Get column config for data-table.
     *
     * @return array<int, array<string, mixed>>
     */
    public function columnsConfig(string $entity): array
    {
        return $this->configs[$entity] ?? [];
    }

    /**
     * Returns pagination metadata.
     *
     * @return array<string, mixed>
     */
    public function paginationData(int $total, int $page, int $perPage): array
    {
        $totalPages = (int) ceil($total / max(1, $perPage));
        $start = ($page - 1) * $perPage + 1;
        $end = min($page * $perPage, $total);

        return [
            'totalPages'  => $totalPages,
            'currentPage' => $page,
            'start'       => $start,
            'end'         => $end,
            'total'       => $total,
            'hasPrev'     => $page > 1,
            'hasNext'     => $page < $totalPages,
        ];
    }

    /**
     * Returns edit, view, delete action configs for a given entity.
     *
     * @return array<string, array<string, mixed>>
     */
    public function actionsConfig(string $entity, string $baseUrl): array
    {
        return [
            'view'   => ['url' => $baseUrl . '/view/',   'icon' => 'fa-eye',   'label' => 'Voir'],
            'edit'   => ['url' => $baseUrl . '/edit/',   'icon' => 'fa-edit',  'label' => 'Modifier'],
            'delete' => ['url' => $baseUrl . '/delete/', 'icon' => 'fa-trash', 'label' => 'Supprimer', 'confirm' => true],
        ];
    }
}

// ---------------------------------------------------------------------------
// Backward-compatible procedural helpers
// ---------------------------------------------------------------------------

if (!function_exists('cm_table_cell')) {
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

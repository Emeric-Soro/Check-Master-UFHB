<?php
$auditLog = is_array($GLOBALS['auditLog'] ?? null) ? $GLOBALS['auditLog'] : [];
$actions = is_array($GLOBALS['actions'] ?? null) ? $GLOBALS['actions'] : [];
$currentPage = max(1, (int) ($GLOBALS['page'] ?? 1));
$perPage = max(1, (int) ($GLOBALS['perPage'] ?? 50));
$totalPages = max(1, (int) ($GLOBALS['totalPages'] ?? 1));
$totalLogs = max(0, (int) ($GLOBALS['totalLogs'] ?? count($auditLog)));

$filters = [
    'date_debut' => (string) ($_GET['date_debut'] ?? ''),
    'date_fin' => (string) ($_GET['date_fin'] ?? ''),
    'action' => (string) ($_GET['action'] ?? ''),
    'table' => (string) ($_GET['table'] ?? ''),
    'statut' => (string) ($_GET['statut'] ?? ''),
    'utilisateur' => (string) ($_GET['utilisateur'] ?? ''),
    'search' => (string) ($_GET['search'] ?? ''),
];

$actionOptions = [];
foreach ($actions as $action) {
    $lib = (string) ($action->lib_action ?? $action['lib_action'] ?? '');
    if ($lib !== '') {
        $actionOptions[$lib] = $lib;
    }
}

$tableOptions = [];
$statutOptions = [];
foreach ($auditLog as $log) {
    $table = trim((string) ($log['nom_table'] ?? ''));
    $statut = trim((string) ($log['statut_action'] ?? ''));
    if ($table !== '') {
        $tableOptions[$table] = $table;
    }
    if ($statut !== '') {
        $statutOptions[$statut] = $statut;
    }
}
ksort($tableOptions);
ksort($statutOptions);

$rows = [];
foreach ($auditLog as $log) {
    $dateCreation = (string) ($log['date_creation'] ?? '');
    $dateText = $dateCreation !== '' ? date('d/m/Y H:i:s', strtotime($dateCreation)) : '-';
    $statut = (string) ($log['statut_action'] ?? '');
    $badgeType = 'info';
    if (strcasecmp($statut, 'Succès') === 0 || strcasecmp($statut, 'Succes') === 0) {
        $badgeType = 'success';
    } elseif (strcasecmp($statut, 'Erreur') === 0 || strcasecmp($statut, 'Echec') === 0) {
        $badgeType = 'danger';
    } elseif (strcasecmp($statut, 'Avertissement') === 0) {
        $badgeType = 'warning';
    }

    $rows[] = [
        'id' => (string) ($log['id'] ?? ''),
        'date_creation' => $dateText,
        'action' => (string) ($log['action'] ?? '-'),
        'nom_table' => (string) ($log['nom_table'] ?? '-'),
        'utilisateur' => (string) ($log['nom_utilisateur'] ?? $log['login_utilisateur'] ?? '-'),
        'statut_action' => ['label' => $statut === '' ? '-' : $statut, 'type' => $badgeType],
    ];
}

$pagination = cm_paginate($totalLogs, $perPage, $currentPage);
$pagination['last'] = $totalPages;

$queryForPager = array_filter([
    'page' => 'piste_audit',
    'date_debut' => $filters['date_debut'],
    'date_fin' => $filters['date_fin'],
    'action' => $filters['action'],
    'table' => $filters['table'],
    'statut' => $filters['statut'],
    'utilisateur' => $filters['utilisateur'],
    'search' => $filters['search'],
], static function ($value) {
    return $value !== '';
});
$pagerBase = '?' . http_build_query($queryForPager);
$exportFilters = $filters;
unset($exportFilters['action']);
$exportUrl = '?page=piste_audit&action=export&' . http_build_query(array_filter($exportFilters, static function ($v) {
    return $v !== '';
}));
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if (!empty($_GET['success']) && $_GET['success'] === 'cleanup'): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => 'Nettoyage termine: ' . (int) ($_GET['deleted'] ?? 0) . ' ligne(s) supprimee(s).']); ?>
    <?php endif; ?>
    <?php if (!empty($_GET['success']) && $_GET['success'] === 'log_deleted'): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => 'Log supprime avec succes.']); ?>
    <?php endif; ?>
    <?php if (!empty($_GET['error'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => 'Une erreur est survenue: ' . htmlspecialchars((string) $_GET['error'], ENT_QUOTES, 'UTF-8')]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php ob_start(); ?>
        <form id="cmAuditFiltersForm" method="GET" data-cm-ajax-form="true">
            <input type="hidden" name="page" value="piste_audit">
            <div class="cm-grid-4">
                <?php cm_component('form/input-date', [
                    'name' => 'date_debut',
                    'label' => 'Date debut',
                    'value' => $filters['date_debut'],
                ]); ?>
                <?php cm_component('form/input-date', [
                    'name' => 'date_fin',
                    'label' => 'Date fin',
                    'value' => $filters['date_fin'],
                ]); ?>
                <?php cm_component('form/select', [
                    'name' => 'action',
                    'label' => 'Action',
                    'options' => array_merge(['' => '-- Toutes --'], $actionOptions),
                    'selected' => $filters['action'],
                ]); ?>
                <?php cm_component('form/input-text', [
                    'name' => 'utilisateur',
                    'label' => 'Utilisateur',
                    'value' => $filters['utilisateur'],
                    'placeholder' => 'Nom ou login...',
                ]); ?>
            </div>
            <div class="cm-grid-3">
                <?php cm_component('form/select', [
                    'name' => 'table',
                    'label' => 'Table',
                    'options' => array_merge(['' => '-- Toutes --'], $tableOptions),
                    'selected' => $filters['table'],
                ]); ?>
                <?php cm_component('form/select', [
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => array_merge(['' => '-- Tous --'], $statutOptions),
                    'selected' => $filters['statut'],
                ]); ?>
                <?php cm_component('form/input-text', [
                    'name' => 'search',
                    'label' => 'Recherche globale',
                    'value' => $filters['search'],
                    'placeholder' => 'Action, table, utilisateur...',
                ]); ?>
            </div>
            <?php
            cm_component('crud/form-actions', [
                'actions' => [
                    [
                        'tag' => 'a',
                        'href' => '?page=piste_audit',
                        'label' => 'Réinitialiser',
                        'icon' => 'fa-rotate-left',
                        'class' => 'cm-btn is-light',
                    ],
                    [
                        'tag' => 'button',
                        'type' => 'submit',
                        'label' => 'Appliquer',
                        'icon' => 'fa-search',
                        'class' => 'cm-btn is-success',
                    ],
                ],
            ]);
            ?>
        </form>
        <?php
        cm_component('crud/form-pole', [
            'title' => '',
            'icon' => 'fa-shield-alt',
            'content' => (string) ob_get_clean(),
        ]);
        ?>

        <?php cm_toolbar([
            'screen' => 'piste_audit',
            'id_prefix' => 'audit',
            'search_value' => $filters['search'],
            'limit' => $perPage,
            'show_actions' => false,
            'custom_actions' => array_values(array_filter([
                [
                    'tag' => 'a',
                    'href' => $exportUrl,
                    'label' => 'Exporter CSV',
                    'class' => 'cm-btn is-info is-sm',
                ],
                (function_exists('canDelete') ? canDelete() : true) ? [
                    'tag' => 'button',
                    'type' => 'submit',
                    'label' => 'Nettoyer',
                    'class' => 'cm-btn is-danger is-sm',
                    'attrs' => [
                        'form' => 'cmAuditCleanupForm',
                    ],
                ] : null,
            ])),
        ]); ?>
        <?php if (function_exists('canDelete') ? canDelete() : true): ?>
            <form id="cmAuditCleanupForm" method="POST" action="?page=piste_audit&action=cleanup" class="cm-hidden" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="days" value="30">
            </form>
        <?php endif; ?>

        <div class="cm-pole-inferieur">
            <?php
            cm_component('crud/data-table', [
                'id' => 'cmAuditTable',
                'columns' => [
                    cm_column('date_creation', 'Date'),
                    cm_column('action', 'Action'),
                    cm_column('nom_table', 'Table'),
                    cm_column('utilisateur', 'Utilisateur'),
                    cm_column('statut_action', 'Statut', ['type' => 'badge', 'align' => 'center']),
                ],
                'rows' => $rows,
                'row_key' => 'id',
                'selectable' => false,
                'actions' => (function_exists('canDelete') ? canDelete() : true) ? [[
                    'tag' => 'button',
                    'type' => 'button',
                    'label' => 'Supprimer',
                    'icon' => 'fa-trash',
                    'class' => 'cm-btn-action is-delete',
                ]] : [],
                'empty_title' => 'Aucun log',
                'empty_message' => 'Aucune ligne trouvee pour ces filtres.',
            ]);
            ?>

            <?php cm_component('crud/pagination', [
                'pagination' => $pagination,
                'base_url' => $pagerBase,
                'param_name' => 'page_num',
            ]); ?>
        </div>
    </div>
</section>

<?php if (function_exists('canDelete') ? canDelete() : true): ?>
<form id="cmAuditDeleteForm" method="POST" action="?page=piste_audit&action=delete_log" class="cm-hidden" data-cm-ajax-form="true">
    <?php cm_component('form/csrf-token'); ?>
    <input type="hidden" name="log_id" id="cmAuditDeleteId" value="">
</form>
<script>
(function () {
    const table = document.getElementById('cmAuditTable');
    const deleteForm = document.getElementById('cmAuditDeleteForm');
    const deleteInput = document.getElementById('cmAuditDeleteId');
    if (!table || !deleteForm || !deleteInput) {
        return;
    }

    table.addEventListener('click', function (event) {
        const deleteButton = event.target.closest('.cm-btn-action.is-delete');
        if (!deleteButton) {
            return;
        }
        const id = deleteButton.getAttribute('data-row-id') || '';
        if (id === '') {
            return;
        }
        if (!window.confirm('Supprimer ce log d\\'audit ?')) {
            return;
        }
        deleteInput.value = id;
        if (typeof deleteForm.requestSubmit === 'function') {
            deleteForm.requestSubmit();
        } else {
            deleteForm.submit();
        }
    });
})();
</script>
<?php endif; ?>

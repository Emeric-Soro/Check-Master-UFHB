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
    $lib = trim((string) ($action->lib_action ?? $action['lib_action'] ?? $action ?? ''));
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

$tableFilterOptions = ['' => '-- Toutes --'];
foreach ($tableOptions as $tableValue) {
    $tableFilterOptions[$tableValue] = function_exists('cm_audit_humanize_context')
        ? cm_audit_humanize_context(['nom_table' => $tableValue])
        : $tableValue;
}

$rows = [];
foreach ($auditLog as $log) {
    $dateCreation = (string) ($log['date_creation'] ?? '');
    $dateText = $dateCreation !== '' ? date('d/m/Y H:i:s', strtotime($dateCreation)) : '-';
    $statut = (string) ($log['statut_action'] ?? '');
    $nomUtilisateur = trim((string) ($log['nom_utilisateur'] ?? ''));
    $loginUtilisateur = trim((string) ($log['login_utilisateur'] ?? ''));
    $idUtilisateur = (int) ($log['id_utilisateur'] ?? 0);
    $utilisateurLabel = $nomUtilisateur !== '' ? $nomUtilisateur : ($loginUtilisateur !== '' ? $loginUtilisateur : '-');
    if ($idUtilisateur > 0) {
        $utilisateurLabel .= ' (#' . $idUtilisateur . ')';
    }

    $badgeType = 'info';
    if (strcasecmp($statut, 'Succès') === 0 || strcasecmp($statut, 'Succes') === 0) {
        $badgeType = 'success';
    } elseif (strcasecmp($statut, 'Erreur') === 0 || strcasecmp($statut, 'Echec') === 0) {
        $badgeType = 'danger';
    } elseif (strcasecmp($statut, 'Avertissement') === 0) {
        $badgeType = 'warning';
    }

    $rows[] = [
        'id' => (string) ($log['id_piste'] ?? ''),
        'date_creation' => $dateText,
        'action' => function_exists('cm_audit_humanize_action')
            ? cm_audit_humanize_action($log)
            : (string) ($log['action'] ?? '-'),
        'contexte' => function_exists('cm_audit_humanize_context')
            ? cm_audit_humanize_context($log)
            : (string) ($log['nom_table'] ?? '-'),
        'utilisateur' => $utilisateurLabel,
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
        <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmAuditFiltersForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
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
                    'label' => 'Contexte',
                    'options' => $tableFilterOptions,
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
                    'placeholder' => 'Action, contexte, utilisateur...',
                ]); ?>
            </div>
            <div class="cm-grid-1">
                <?php cm_component('form/input-number', [
                    'name' => 'days',
                    'label' => 'Nettoyer logs plus vieux que (jours)',
                    'value' => '30',
                    'min' => '1',
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
                        'class' => 'cm-btn is-primary',
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
            'limit_options' => [5, 10, 25, 50, 100],
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
                    'type' => 'button',
                    'label' => 'Nettoyer',
                    'class' => 'cm-btn is-danger is-sm',
                    'attrs' => [
                        'onclick' => "if(confirm('Confirmer le nettoyage des logs ?')) document.getElementById('cmAuditCleanupForm').submit();"
                    ],
                ] : null,
            ])),
        ]); ?>
        <?php if (function_exists('canDelete') ? canDelete() : true): ?>
            <form id="cmAuditCleanupForm" method="POST" action="?page=piste_audit&action=cleanup" class="cm-hidden" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <script>
                    document.getElementById('cmAuditCleanupForm').addEventListener('submit', function(e) {
                        const daysInput = document.querySelector('input[name="days"]');
                        if (daysInput) {
                            const hiddenDays = document.createElement('input');
                            hiddenDays.type = 'hidden';
                            hiddenDays.name = 'days';
                            hiddenDays.value = daysInput.value;
                            this.appendChild(hiddenDays);
                        }
                    });
                </script>
            </form>
        <?php endif; ?>

        <div class="cm-pole-inferieur">
            <?php
            cm_component('crud/data-table', [
                'id' => 'cmAuditTable',
                'columns' => [
                    cm_column('date_creation', 'Date &amp; Heure'),
                    cm_column('action', 'Action effectuée'),
                    cm_column('contexte', 'Contexte'),
                    cm_column('utilisateur', 'Utilisateur'),
                    cm_column('statut_action', 'Statut', ['type' => 'badge', 'align' => 'center']),
                ],
                'rows' => $rows,
                'row_key' => 'id',
                'selectable' => false,
                'actions' => [[
                    'tag' => 'button',
                    'type' => 'button',
                    'label' => 'Voir détail',
                    'icon' => 'fa-eye',
                    'class' => 'cm-btn-action is-info js-audit-detail',
                ]],
                'empty_title' => 'Aucun log',
                'empty_message' => 'Aucune ligne trouvée pour ces filtres.',
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

    table.addEventListener('click', async function (event) {
        const detailButton = event.target.closest('.js-audit-detail');
        const deleteButton = event.target.closest('.cm-btn-action.is-delete');
        if (detailButton) {
            const id = detailButton.getAttribute('data-row-id') || '';
            if (id !== '') {
                window.location.href = '?page=piste_audit&action=voir&id=' + encodeURIComponent(id);
            }
            return;
        }
        if (!deleteButton) {
            return;
        }
        const id = deleteButton.getAttribute('data-row-id') || '';
        if (id === '') {
            return;
        }
        const confirmed = await window.CM.confirm({
            title: 'Suppression',
            message: 'Supprimer ce log d\\'audit ?',
            type: 'danger',
            confirmText: 'Supprimer',
        });
        if (!confirmed) {
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

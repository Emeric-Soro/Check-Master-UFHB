<?php
/**
 * P2.14 — Historique Modifications par Entité
 * Slug: historique_modifications | Permission: piste_audit
 *
 * Affiche l'historique des modifications groupé par entité avec timeline,
 * filtres avancés (entité, action, date) et export CSV.
 */

// @todo REFACTOR: Vue auto-contenue avec service + logique d'export CSV (couplage vue ↔ métier).
// Déplacer la logique dans layout.php (case 'historique_modifications') et/ou un contrôleur dédié.
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/Services/HistoriqueModificationsService.php';

use CheckMaster\Services\HistoriqueModificationsService;

$service = new HistoriqueModificationsService(Database::getConnection());

// Pagination
$page = isset($_GET['page_num']) ? max(1, (int) $_GET['page_num']) : 1;
$perPage = isset($_GET['limit']) ? (int) $_GET['limit'] : 20;
if (!in_array($perPage, [10, 20, 50, 100], true)) $perPage = 20;
$offset = ($page - 1) * $perPage;

// Filtres
$filters = $service->extractFilters($_GET);

// Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (!\canView('piste_audit')) {
        http_response_code(403);
        exit;
    }
    $service->exportLogsCSV($filters);
    exit;
}

// Données
$logs = $service->getFilteredLogs($filters, $offset, $perPage);
$totalLogs = $service->getTotalFilteredLogs($filters);
$totalPages = max(1, (int) ceil($totalLogs / $perPage));
$groupedByEntity = $service->getLogsByEntity($filters, $offset, $perPage);

$actions = $service->getAllActions();
$entities = $service->getAllEntities();

$rows = [];
foreach ($logs as $log) {
    $statut = $log['statut_action'] ?? '';
    $badgeType = 'info';
    if (strcasecmp($statut, 'Succès') === 0 || strcasecmp($statut, 'Succes') === 0) $badgeType = 'success';
    elseif (strcasecmp($statut, 'Erreur') === 0 || strcasecmp($statut, 'Echec') === 0) $badgeType = 'danger';
    elseif (strcasecmp($statut, 'Avertissement') === 0) $badgeType = 'warning';

    $rows[] = [
        'id'          => $log['id_piste'] ?? '',
        'date'        => $log['date_creation'] ? date('d/m/Y H:i', strtotime($log['date_creation'])) : '-',
        'entite'      => $log['contexte'] ?? $log['nom_table'] ?? '-',
        'id_entite'   => $log['id_entite'] ?? '-',
        'action'      => $log['action'] ?? '-',
        'utilisateur' => $log['nom_utilisateur'] ?? $log['login_utilisateur'] ?? '-',
        'statut'      => ['label' => $statut ?: '-', 'type' => $badgeType],
    ];
}

// Pagination
$pagination = \cm_paginate($totalLogs, $perPage, $page);
$pagination['last'] = $totalPages;

$queryForPager = array_filter(['page' => 'historique_modifications',
    'date_debut' => $filters['date_debut'], 'date_fin' => $filters['date_fin'],
    'action' => $filters['action'], 'entite' => $filters['entite'],
    'utilisateur' => $filters['utilisateur'], 'statut' => $filters['statut'],
    'limit' => $perPage,
], fn($v) => $v !== '');
$pagerBase = '?' . http_build_query($queryForPager);
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Historique des modifications',
            'icon'  => 'fa-history',
            'content' => '<p class="cm-text-muted">Consultez l\'historique des modifications par entité, action ou période.</p>',
        ]); ?>

        <!-- Filtres -->
        <form method="GET" class="cm-mb-md">
            <input type="hidden" name="page" value="historique_modifications">
            <div class="cm-grid-4">
                <?php cm_component('form/input-date', ['name' => 'date_debut', 'label' => 'Date début', 'value' => $filters['date_debut']]); ?>
                <?php cm_component('form/input-date', ['name' => 'date_fin', 'label' => 'Date fin', 'value' => $filters['date_fin']]); ?>
                <?php cm_component('form/select', [
                    'name' => 'action',
                    'label' => 'Action',
                    'options' => array_merge(['' => '-- Toutes --'], array_combine($actions, $actions)),
                    'selected' => $filters['action'],
                ]); ?>
                <?php cm_component('form/select', [
                    'name' => 'entite',
                    'label' => 'Entité',
                    'options' => array_merge(['' => '-- Toutes --'], array_combine($entities, $entities)),
                    'selected' => $filters['entite'],
                ]); ?>
            </div>
            <div class="cm-grid-3">
                <?php cm_component('form/input-text', ['name' => 'utilisateur', 'label' => 'Utilisateur', 'value' => $filters['utilisateur'], 'placeholder' => 'Nom ou login...']); ?>
                <?php cm_component('form/select', [
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => ['' => '-- Tous --', 'Succès' => 'Succès', 'Erreur' => 'Erreur', 'Avertissement' => 'Avertissement'],
                    'selected' => $filters['statut'],
                ]); ?>
                <div class="cm-flex cm-flex-end cm-items-end cm-mt-sm">
                    <button type="submit" class="cm-btn is-primary"><i class="fas fa-search"></i> Appliquer</button>
                    <a href="?page=historique_modifications" class="cm-btn is-light cm-ml-sm"><i class="fas fa-rotate-left"></i> Réinitialiser</a>
                </div>
            </div>
        </form>

        <!-- Toolbar -->
        <div class="cm-toolbar cm-mb-md">
            <div class="cm-toolbar-left">
                <span class="cm-text-muted"><?= $totalLogs ?> modification(s)</span>
            </div>
            <div class="cm-toolbar-right">
                <a href="?page=historique_modifications&export=csv&<?= htmlspecialchars(http_build_query(array_filter($filters, fn($v) => $v !== '')), ENT_QUOTES, 'UTF-8') ?>"
                   class="cm-btn is-info is-sm">
                    <i class="fas fa-download"></i> Exporter CSV
                </a>
            </div>
        </div>

        <!-- Timeline par entité -->
        <?php if (!empty($groupedByEntity)): ?>
            <?php foreach ($groupedByEntity as $group): ?>
            <div class="cm-card cm-mb-md">
                <div class="cm-card__header">
                    <h3 class="cm-card__title">
                        <i class="fas fa-database"></i>
                        <?= htmlspecialchars($group['entite'] ?: 'Autre', ENT_QUOTES, 'UTF-8') ?>
                        <span class="cm-badge is-info is-sm"><?= count($group['items']) ?></span>
                    </h3>
                </div>
                <div class="cm-card__body">
                    <div class="cm-timeline">
                        <?php foreach (array_slice($group['items'], 0, 10) as $item): ?>
                        <div class="cm-timeline__item">
                            <div class="cm-timeline__dot is-<?= strcasecmp($item['statut_action'] ?? '', 'Succès') === 0 ? 'success' : (strcasecmp($item['statut_action'] ?? '', 'Erreur') === 0 ? 'danger' : 'info') ?>"></div>
                            <div class="cm-timeline__content">
                                <div class="cm-flex cm-justify-between">
                                    <strong><?= htmlspecialchars($item['action'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                                    <span class="cm-text-xs cm-text-muted"><?= $item['date_creation'] ? date('d/m/Y H:i', strtotime($item['date_creation'])) : '-' ?></span>
                                </div>
                                <div class="cm-text-sm cm-text-muted">
                                    ID: <?= htmlspecialchars($item['id_entite'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                    — <?= htmlspecialchars($item['nom_utilisateur'] ?? $item['login_utilisateur'] ?? '-', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Tableau détaillé -->
        <div class="cm-pole-inferieur">
            <?php
            cm_component('crud/data-table', [
                'id'        => 'cmHistoriqueTable',
                'columns'   => [
                    cm_column('date',       'Date & Heure'),
                    cm_column('entite',     'Entité'),
                    cm_column('id_entite',  'ID', ['align' => 'center']),
                    cm_column('action',     'Action'),
                    cm_column('utilisateur','Utilisateur'),
                    cm_column('statut',     'Statut', ['type' => 'badge', 'align' => 'center']),
                ],
                'rows'          => $rows,
                'row_key'       => 'id',
                'empty_title'   => 'Aucune modification',
                'empty_message' => 'Aucune modification trouvée pour ces critères.',
            ]);
            ?>

            <?php cm_component('crud/pagination', [
                'pagination' => $pagination,
                'base_url'   => $pagerBase,
                'param_name' => 'page_num',
            ]); ?>
        </div>
    </div>
</section>

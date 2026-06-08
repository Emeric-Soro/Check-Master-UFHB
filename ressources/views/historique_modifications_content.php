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

$currentPage = $_GET['page'] ?? 'historique_modifications';
$currentAction = $_GET['action'] ?? '';
$currentTab = $_GET['tab'] ?? '';

$queryForPager = ['page' => $currentPage];
if ($currentAction !== '') $queryForPager['action'] = $currentAction;
if ($currentTab !== '') $queryForPager['tab'] = $currentTab;

$queryForPager = array_merge($queryForPager, array_filter([
    'date_debut' => $filters['date_debut'],
    'date_fin' => $filters['date_fin'],
    'action' => $filters['action'],
    'entite' => $filters['entite'],
    'utilisateur' => $filters['utilisateur'],
    'statut' => $filters['statut'],
    'limit' => $perPage,
], fn($v) => $v !== ''));

$pagerBase = '?' . http_build_query($queryForPager);
?>
<section class="cm-screen-scrollable">
    <style>
        /* Modern Filter Panel styling (integrated/borderless) */
        .audit-filter-panel {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 10px 0 20px 0;
            box-shadow: none;
        }
        
        /* Custom View Toggle Styles */
        .cm-btn-group button.is-active-toggle {
            background-color: #3b82f6 !important;
            color: #ffffff !important;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2), 0 2px 4px -1px rgba(59, 130, 246, 0.1) !important;
        }

        /* Premium Seamless Grid and Timeline Design */
        .timeline-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
            gap: 28px;
        }
        .timeline-card {
            background: transparent;
            border: none;
            border-radius: 0;
            box-shadow: none;
            display: flex;
            flex-direction: column;
        }
        .timeline-card__header {
            background: transparent;
            border-bottom: 1px solid #e2e8f0;
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 14px;
        }
        .timeline-card__title {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            text-transform: capitalize;
        }
        .timeline-card__body {
            padding: 0;
            max-height: 380px;
            overflow-y: auto;
            flex-grow: 1;
        }

        /* Custom Audit Timeline (seamless) */
        .audit-timeline {
            position: relative;
            padding-left: 20px;
            border-left: 1px dashed #cbd5e1;
            margin-left: 8px;
        }
        .audit-timeline__item {
            position: relative;
            margin-bottom: 18px;
        }
        .audit-timeline__item:last-child {
            margin-bottom: 0;
        }
        .audit-timeline__dot {
            position: absolute;
            left: -25px;
            top: 4px;
            width: 9px;
            height: 9px;
            border-radius: 50%;
            background: #64748b;
            border: 2px solid #ffffff;
            box-shadow: 0 0 0 2px #e2e8f0;
            transition: all 0.2s ease;
        }
        .audit-timeline__item:hover .audit-timeline__dot {
            transform: scale(1.3);
        }
        .audit-timeline__dot.is-success {
            background: #10b981;
            box-shadow: 0 0 0 2px #d1fae5;
        }
        .audit-timeline__dot.is-danger {
            background: #ef4444;
            box-shadow: 0 0 0 2px #fee2e2;
        }
        .audit-timeline__dot.is-warning {
            background: #f59e0b;
            box-shadow: 0 0 0 2px #fef3c7;
        }
        .audit-timeline__content {
            background: transparent;
            border-radius: 0;
            padding: 2px 0;
            border: none;
        }
    </style>

    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Historique des modifications',
            'icon'  => 'fa-history',
            'content' => '<p class="cm-text-muted">Consultez et filtrez la piste d\'audit des modifications d\'entités du système.</p>',
        ]); ?>

        <!-- Filtres -->
        <form method="GET" class="audit-filter-panel cm-mb-md">
            <input type="hidden" name="page" value="<?= htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8') ?>">
            <?php if ($currentAction !== ''): ?>
                <input type="hidden" name="action" value="<?= htmlspecialchars($currentAction, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>
            <?php if ($currentTab !== ''): ?>
                <input type="hidden" name="tab" value="<?= htmlspecialchars($currentTab, ENT_QUOTES, 'UTF-8') ?>">
            <?php endif; ?>

            <div class="cm-grid-3 cm-mb-sm">
                <?php cm_component('form/input-date', ['name' => 'date_debut', 'label' => 'Date début', 'value' => $filters['date_debut']]); ?>
                <?php cm_component('form/input-date', ['name' => 'date_fin', 'label' => 'Date fin', 'value' => $filters['date_fin']]); ?>
                <?php cm_component('form/input-text', ['name' => 'utilisateur', 'label' => 'Utilisateur', 'value' => $filters['utilisateur'], 'placeholder' => 'Nom ou login...']); ?>
            </div>
            <div class="cm-grid-4">
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
                <?php cm_component('form/select', [
                    'name' => 'statut',
                    'label' => 'Statut',
                    'options' => ['' => '-- Tous --', 'Succès' => 'Succès', 'Erreur' => 'Erreur', 'Avertissement' => 'Avertissement'],
                    'selected' => $filters['statut'],
                ]); ?>
                <div class="cm-flex cm-flex-end cm-items-end" style="height:100%; padding-bottom: 4px;">
                    <button type="submit" class="cm-btn is-primary" style="font-weight:600;"><i class="fas fa-search"></i> Filtrer</button>
                    <a href="?page=<?= htmlspecialchars($currentPage, ENT_QUOTES, 'UTF-8') ?><?= $currentAction !== '' ? '&action=' . htmlspecialchars($currentAction, ENT_QUOTES, 'UTF-8') : '' ?><?= $currentTab !== '' ? '&tab=' . htmlspecialchars($currentTab, ENT_QUOTES, 'UTF-8') : '' ?>" 
                       class="cm-btn is-light cm-ml-sm" style="font-weight:600;"><i class="fas fa-rotate-left"></i> Réinitialiser</a>
                </div>
            </div>
        </form>

        <!-- Toolbar de contrôle des vues -->
        <div class="cm-flex cm-justify-between cm-items-center cm-mb-md cm-flex-wrap" style="gap:1rem;">
            <!-- Commutateur de vue -->
            <div class="cm-btn-group" style="display:inline-flex; border-radius:8px; overflow:hidden; border:1px solid #e2e8f0; background:#f1f5f9; padding:2px;">
                <button type="button" id="btn-view-timeline" onclick="switchView('timeline')" class="cm-btn is-sm" style="border:none; margin:0; border-radius:6px; font-weight:600; transition:all 0.2s; background:transparent; color:#475569; padding:6px 16px; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <i class="fas fa-stream"></i> Vue Timeline
                </button>
                <button type="button" id="btn-view-table" onclick="switchView('table')" class="cm-btn is-sm" style="border:none; margin:0; border-radius:6px; font-weight:600; transition:all 0.2s; background:transparent; color:#475569; padding:6px 16px; display:inline-flex; align-items:center; gap:6px; cursor:pointer;">
                    <i class="fas fa-table"></i> Tableau Détaillé
                </button>
            </div>
            
            <!-- Informations & Action -->
            <div class="cm-flex cm-items-center" style="gap:1rem;">
                <span class="cm-badge" style="font-weight:600; padding:6px 12px; background:#f1f5f9; color:#475569; border-radius:20px; font-size:0.85rem; border:1px solid #e2e8f0;">
                    <i class="fas fa-info-circle cm-mr-xs" style="color:#3b82f6;"></i> <?= $totalLogs ?> modification(s)
                </span>
                <a href="<?= $pagerBase ?>&export=csv" class="cm-btn is-info is-sm" style="display:inline-flex; align-items:center; border-radius:8px; font-weight:600; gap:6px;">
                    <i class="fas fa-download"></i> Exporter CSV
                </a>
            </div>
        </div>

        <!-- 1. Vue Timeline par entité -->
        <div id="section-timeline" class="timeline-grid cm-mb-md">
            <?php if (empty($groupedByEntity)): ?>
                <div class="cm-card" style="grid-column: 1 / -1; padding: 40px; text-align: center; color: #64748b; border-radius:12px;">
                    <i class="fas fa-history fa-2x cm-mb-sm" style="opacity: 0.3;"></i>
                    <p style="margin:0; font-weight:500;">Aucune modification groupée disponible pour ces filtres.</p>
                </div>
            <?php else: ?>
                <?php foreach ($groupedByEntity as $group): ?>
                <div class="timeline-card">
                    <div class="timeline-card__header">
                        <h3 class="timeline-card__title">
                            <i class="fas fa-database" style="color: #3b82f6; font-size:1rem;"></i>
                            <?= htmlspecialchars($group['entite'] ?: 'Autre', ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <span class="cm-badge is-info is-sm" style="border-radius:12px; font-weight:600; padding:2px 8px; font-size:0.75rem; background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe;">
                            <?= count($group['items']) ?> action(s)
                        </span>
                    </div>
                    <div class="timeline-card__body">
                        <div class="audit-timeline">
                            <?php foreach (array_slice($group['items'], 0, 10) as $item): ?>
                            <?php
                            $itemStatut = $item['statut_action'] ?? '';
                            $dotClass = 'info';
                            if (strcasecmp($itemStatut, 'Succès') === 0 || strcasecmp($itemStatut, 'Succes') === 0) $dotClass = 'success';
                            elseif (strcasecmp($itemStatut, 'Erreur') === 0 || strcasecmp($itemStatut, 'Echec') === 0) $dotClass = 'danger';
                            elseif (strcasecmp($itemStatut, 'Avertissement') === 0) $dotClass = 'warning';
                            ?>
                            <div class="audit-timeline__item">
                                <div class="audit-timeline__dot is-<?= $dotClass ?>"></div>
                                <div class="audit-timeline__content">
                                    <div class="cm-flex cm-justify-between cm-items-start cm-mb-xs" style="gap:8px;">
                                        <strong style="color: #0f172a; font-size: 0.85rem; font-weight:600;"><?= htmlspecialchars($item['action'] ?? '-', ENT_QUOTES, 'UTF-8') ?></strong>
                                        <span class="cm-text-xs cm-text-muted" style="font-weight: 500; white-space:nowrap;"><?= $item['date_creation'] ? date('d/m H:i', strtotime($item['date_creation'])) : '-' ?></span>
                                    </div>
                                    <div class="cm-text-xs cm-text-muted" style="line-height:1.4;">
                                        ID: <span style="font-family: monospace; font-weight: 600; color: #475569;"><?= htmlspecialchars($item['id_entite'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                        — <span style="color: #475569;"><?= htmlspecialchars($item['nom_utilisateur'] ?? $item['login_utilisateur'] ?? '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- 2. Vue Tableau détaillé -->
        <div id="section-table" class="cm-pole-inferieur" style="display:none;">
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

<script>
    function switchView(viewName) {
        const timelineSection = document.getElementById('section-timeline');
        const tableSection = document.getElementById('section-table');
        const btnTimeline = document.getElementById('btn-view-timeline');
        const btnTable = document.getElementById('btn-view-table');
        
        if (!timelineSection || !tableSection || !btnTimeline || !btnTable) return;

        if (viewName === 'timeline') {
            timelineSection.style.display = 'grid';
            tableSection.style.display = 'none';
            btnTimeline.classList.add('is-active-toggle');
            btnTable.classList.remove('is-active-toggle');
            localStorage.setItem('cm_modifs_view', 'timeline');
        } else {
            timelineSection.style.display = 'none';
            tableSection.style.display = 'block';
            btnTimeline.classList.remove('is-active-toggle');
            btnTable.classList.add('is-active-toggle');
            localStorage.setItem('cm_modifs_view', 'table');
        }
    }

    // Activer la vue sauvegardée ou par défaut au chargement
    document.addEventListener('DOMContentLoaded', () => {
        const activeView = localStorage.getItem('cm_modifs_view') || 'timeline';
        switchView(activeView);
    });
</script>

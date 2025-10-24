<?php
require_once __DIR__ . '/../../app/utils/permissions.php';

function getActionColor($action) {
    switch ($action) {
        case 'Création':
            return 'bg-green-100 text-green-800';
        case 'Modification':
            return 'bg-blue-100 text-blue-800';
        case 'Suppression':
            return 'bg-blue-100 text-blue-800';
        case 'Connexion':
            return 'bg-green-100 text-green-800';
        case 'Déconnexion':
            return 'bg-blue-100 text-blue-800';
        case 'Validation':
            return 'bg-green-100 text-green-800';
        case 'Rejet':
            return 'bg-blue-100 text-blue-800';
        case 'Sauvegarde':
            return 'bg-blue-100 text-blue-800';
        case 'Restauration':
            return 'bg-blue-100 text-blue-800';
        case 'Impression':
            return 'bg-blue-100 text-blue-800';
        case 'Exportation':
            return 'bg-blue-100 text-blue-800';
        default:
            return 'bg-gray-100 text-gray-800';
    }
}
$auditLog = $GLOBALS['auditLog'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Piste D'Audit</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/fr.js"></script>
    <style>
        :root{
            --ufhb-blue: #0F4C75;
            --ufhb-blue-light: #3282B8;
            --ufhb-green: #10b981;
            --muted: #64748B;
            --bg: #F7FAFC;
            --card-shadow: rgba(15,76,117,0.06);
        }

        /* Reset / base */
        html,body { height:100%; margin:0; padding:0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial; background: var(--bg); color:#1f2937; -webkit-font-smoothing:antialiased; -moz-osx-font-smoothing:grayscale; }
        .container { max-width: 1160px; margin: 0 auto; padding: 24px; }

        /* Header */
        .header-row { display:flex; align-items:center; justify-content:space-between; gap:16px; margin-bottom:24px; }
        .header-left { display:flex; align-items:center; gap:16px; }
        .header-badge { background: var(--ufhb-blue); color: #fff; padding:10px; border-radius:8px; display:flex; align-items:center; justify-content:center; width:44px; height:44px; }
        .page-title { font-size:20px; font-weight:600; color:#0f1720; margin:0; }

        /* Card */
        .card { background:#fff; border-radius:12px; box-shadow:0 8px 24px var(--card-shadow); overflow:hidden; }

        /* Filter area */
        .filters { padding:16px; display:flex; flex-direction:column; gap:12px; background: #fbfdff; border-bottom:1px solid rgba(15,76,117,0.04); }
        .filters-row { display:flex; flex-wrap:wrap; gap:12px; align-items:end; }
        .filter-group { display:flex; flex-direction:column; gap:6px; min-width:160px; }
        .filter-input { border:1px solid rgba(15,76,117,0.08); border-radius:8px; padding:8px 10px; font-size:14px; background:#fff; color:#0f1720; }

        /* Buttons layout */
        .filter-actions { margin-left:auto; display:flex; gap:10px; align-items:center; }
        .btn { border:0; cursor:pointer; padding:10px 14px; border-radius:8px; font-size:14px; font-weight:600; display:inline-flex; align-items:center; gap:8px; }
        .btn:focus { outline:3px solid rgba(15,76,117,0.12); }
        .btn-green { background:var(--ufhb-green); color:#fff; }
        .btn-blue { background:var(--ufhb-blue); color:#fff; }
        .btn-muted { background:#f3f4f6; color:#374151; border:1px solid rgba(15,76,117,0.04); }

        /* Table area */
        .table-wrap { overflow-x:auto; padding:16px; }
        .table { width:100%; border-collapse:collapse; min-width:800px; }
        .table thead th { text-align:left; font-size:12px; text-transform:uppercase; color:var(--muted); padding:12px 10px; border-bottom:1px solid rgba(15,76,117,0.04); }
        .table tbody td { padding:12px 10px; border-bottom:1px solid rgba(15,76,117,0.04); vertical-align:middle; font-size:14px; color:#111827; }
        .row-empty { text-align:center; padding:48px 0; color:#6b7280; }

        /* Badges */
        .badge { display:inline-flex; align-items:center; gap:6px; padding:6px 8px; border-radius:9999px; font-size:12px; font-weight:700; }
        .badge-blue { background:rgba(15,76,117,0.08); color:var(--ufhb-blue); }
        .badge-green { background:rgba(16,185,129,0.08); color:var(--ufhb-green); }

        /* Actions row */
        .actions-row { display:flex; gap:8px; align-items:center; }

        /* Pagination */
        .pagination { display:flex; gap:8px; align-items:center; padding:12px 16px; background: #fbfdff; border-top:1px solid rgba(15,76,117,0.04); justify-content:space-between; }
        .page-info { color:#374151; font-size:14px; }

        /* Modals */
        .modal-backdrop { position:fixed; inset:0; background:rgba(0,0,0,0.45); display:flex; align-items:center; justify-content:center; z-index:60; }
        .modal { background:#fff; border-radius:10px; padding:20px; width:100%; max-width:520px; box-shadow:0 12px 36px rgba(2,6,23,0.2); }

        /* Small helpers */
        .small { font-size:13px; color:#6b7280; }


        /* Responsive */
        @media (max-width:880px){
            .filters-row { flex-direction:column; align-items:stretch; }
            .filter-actions { margin-left:0; justify-content:flex-end; width:100%; }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header-row">
        <div class="header-left">
            <div class="header-badge">
                <i class="fas fa-shield-alt" aria-hidden="true" style="font-size:18px;"></i>
            </div>
            <div>
                <h1 class="page-title">Piste d'Audit</h1>
                <div class="small">Historique des actions système</div>
            </div>
        </div>
        <div>
            <label class="small" style="display:block;margin-bottom:6px;color:#374151;">Date du jour</label>
            <input type="text" class="filter-input" readonly value="<?php echo date('d/m/Y'); ?>" style="width:140px;">
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cleanup'): ?>
        <div style="background:rgba(16,185,129,0.08); border-left:4px solid var(--ufhb-green); padding:12px; border-radius:8px; margin-bottom:14px; color:#065F46;">
            <strong>Succès !</strong>
            <div><?php echo $_GET['deleted'] ?? 0; ?> enregistrements d'audit ont été supprimés.</div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'log_deleted'): ?>
        <div style="background:rgba(16,185,129,0.08); border-left:4px solid var(--ufhb-green); padding:12px; border-radius:8px; margin-bottom:14px; color:#065F46;">
            <strong>Succès !</strong>
            <div>Le log d'audit a été supprimé avec succès.</div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div style="background:rgba(15,76,117,0.04); border-left:4px solid var(--ufhb-blue); padding:12px; border-radius:8px; margin-bottom:14px; color:var(--ufhb-blue);">
            <strong>Erreur !</strong>
            <div>
                <?php
                switch ($_GET['error']) {
                    case 'invalid_days':
                        echo 'Nombre de jours invalide pour le nettoyage.';
                        break;
                    case 'invalid_id':
                        echo 'ID de log invalide.';
                        break;
                    case 'log_not_found':
                        echo 'Log d\'audit introuvable.';
                        break;
                    case 'cleanup_failed':
                        echo 'Erreur lors du nettoyage des logs.';
                        break;
                    case 'delete_failed':
                        echo 'Erreur lors de la suppression du log.';
                        break;
                    case 'invalid_method':
                        echo 'Méthode de requête invalide.';
                        break;
                    default:
                        echo 'Une erreur s\'est produite.';
                }
                ?>
            </div>
        </div>
    <?php endif; ?>

    <div class="card" role="region" aria-labelledby="audit-title">
        <div style="padding:16px 20px; background: var(--ufhb-blue); color:#fff;">
            <h2 id="audit-title" style="margin:0; font-size:16px; font-weight:700;">
                <i class="fas fa-history" style="margin-right:8px;"></i>
                Piste d'Audit - Historique des Actions
            </h2>
        </div>

        <div class="filters">
            <form method="GET" action="?page=piste_audit" style="display:flex; flex-direction:column; gap:12px;">
                <input type="hidden" name="page" value="piste_audit">
                <div class="filters-row" role="group" aria-label="Filtres d'audit">
                    <div class="filter-group">
                        <label class="small">Date début</label>
                        <input type="text" name="date_debut" id="date_debut" value="<?php echo htmlspecialchars($_GET['date_debut'] ?? ''); ?>" placeholder="Date de début" class="filter-input">
                    </div>

                    <div class="filter-group">
                        <label class="small">Date fin</label>
                        <input type="text" name="date_fin" id="date_fin" value="<?php echo htmlspecialchars($_GET['date_fin'] ?? ''); ?>" placeholder="Date de fin" class="filter-input">
                    </div>

                    <div class="filter-group">
                        <label class="small">Action</label>
                        <select name="action" class="filter-input">
                            <option value="">Toutes les actions</option>
                            <option value="Création" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Création') ? 'selected' : ''; ?>>Création</option>
                            <option value="Modification" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Modification') ? 'selected' : ''; ?>>Modification</option>
                            <option value="Suppression" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Suppression') ? 'selected' : ''; ?>>Suppression</option>
                            <option value="Connexion" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Connexion') ? 'selected' : ''; ?>>Connexion</option>
                            <option value="Déconnexion" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Déconnexion') ? 'selected' : ''; ?>>Déconnexion</option>
                            <option value="Validation" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Validation') ? 'selected' : ''; ?>>Validation</option>
                            <option value="Rejet" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Rejet') ? 'selected' : ''; ?>>Rejet</option>
                            <option value="Sauvegarde" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Sauvegarde') ? 'selected' : ''; ?>>Sauvegarde</option>
                            <option value="Restauration" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Restauration') ? 'selected' : ''; ?>>Restauration</option>
                            <option value="Impression" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Impression') ? 'selected' : ''; ?>>Impression</option>
                            <option value="Exportation" <?php echo (isset($_GET['action']) && $_GET['action'] === 'Exportation') ? 'selected' : ''; ?>>Exportation</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="small">Table</label>
                        <select name="table" class="filter-input">
                            <option value="">Toutes les tables</option>
                            <option value="utilisateur" <?php echo (isset($_GET['table']) && $_GET['table'] === 'utilisateur') ? 'selected' : ''; ?>>Utilisateur</option>
                            <option value="etudiant" <?php echo (isset($_GET['table']) && $_GET['table'] === 'etudiant') ? 'selected' : ''; ?>>Étudiant</option>
                            <option value="enseignant" <?php echo (isset($_GET['table']) && $_GET['table'] === 'enseignant') ? 'selected' : ''; ?>>Enseignant</option>
                            <option value="rapport_etudiants" <?php echo (isset($_GET['table']) && $_GET['table'] === 'rapport_etudiants') ? 'selected' : ''; ?>>Rapport Étudiants</option>
                            <option value="candidature_soutenance" <?php echo (isset($_GET['table']) && $_GET['table'] === 'candidature_soutenance') ? 'selected' : ''; ?>>Candidature Soutenance</option>
                            <option value="versement" <?php echo (isset($_GET['table']) && $_GET['table'] === 'versement') ? 'selected' : ''; ?>>Versement</option>
                            <option value="base_de_donnees" <?php echo (isset($_GET['table']) && $_GET['table'] === 'base_de_donnees') ? 'selected' : ''; ?>>Base de Données</option>
                            <option value="sauvegarde" <?php echo (isset($_GET['table']) && $_GET['table'] === 'sauvegarde') ? 'selected' : ''; ?>>Sauvegarde</option>
                            <option value="pister" <?php echo (isset($_GET['table']) && $_GET['table'] === 'pister') ? 'selected' : ''; ?>>Piste d'Audit</option>
                        </select>
                    </div>

                    <div class="filter-group">
                        <label class="small">Statut</label>
                        <select name="statut" class="filter-input">
                            <option value="">Tous les statuts</option>
                            <option value="Succès" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'Succès') ? 'selected' : ''; ?>>Succès</option>
                            <option value="Erreur" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'Erreur') ? 'selected' : ''; ?>>Erreur</option>
                        </select>
                    </div>

                    <div class="filter-group" style="flex:1;">
                        <label class="small">Recherche</label>
                        <input type="text" name="search" id="search" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" placeholder="Rechercher..." class="filter-input" style="width:100%;">
                    </div>

                    <div class="filter-actions" role="group" aria-label="Actions filtres">
                        <button type="submit" class="btn btn-green" title="Filtrer">
                            <i class="fas fa-search" aria-hidden="true"></i>
                            <span>Filtrer</span>
                        </button>
                        <a href="?page=piste_audit" class="btn btn-blue" title="Réinitialiser">
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span>Réinitialiser</span>
                        </a>
                    </div>
                </div>
            </form>
        </div>

        <div class="flex justify-between items-center" style="padding:12px 16px;">
            <div class="small"><strong><?php echo count($auditLog); ?></strong> enregistrements trouvés</div>
            <div class="actions-row" style="margin-left:auto;">
                <a href="?page=piste_audit&action=export&<?php echo http_build_query(array_filter($_GET, function($key) { return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>" class="btn btn-blue" title="Exporter">
                    <i class="fas fa-file-export"></i>
                    <span>Exporter</span>
                </a>
                <button onclick="window.print()" class="btn btn-blue" title="Imprimer">
                    <i class="fas fa-print"></i>
                    <span>Imprimer</span>
                </button>
                <a href="?page=piste_audit" class="btn btn-green" title="Actualiser">
                    <i class="fas fa-sync-alt"></i>
                    <span>Actualiser</span>
                </a>
            </div>
        </div>

        <div class="table-wrap">
            <table class="table" role="table" aria-label="Logs d'audit">
                <thead>
                <tr>
                    <th>Date</th>
                    <th>Heure</th>
                    <th>Action</th>
                    <th>Statut</th>
                    <th>Table</th>
                    <th>Utilisateur</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php if (empty($auditLog)): ?>
                    <tr>
                        <td colspan="7" class="row-empty">
                            <div style="text-align:center;">
                                <i class="fas fa-search" style="font-size:36px;color:#cbd5e1;margin-bottom:12px;"></i>
                                <div>Aucun log d'audit trouvé pour les critères sélectionnés.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($auditLog as $log): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($log['date_creation'])); ?></td>
                            <td><?php echo date('H:i:s', strtotime($log['date_creation'])); ?></td>
                            <td><span class="badge <?php echo getActionColor($log['action']); ?>"><?php echo htmlspecialchars($log['action']); ?></span></td>
                            <td>
                                <?php if ($log['statut_action'] === 'Succès'): ?>
                                    <span class="badge badge-green"><?php echo htmlspecialchars($log['statut_action']); ?></span>
                                <?php else: ?>
                                    <span class="badge badge-blue"><?php echo htmlspecialchars($log['statut_action']); ?></span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($log['nom_table']); ?></td>
                            <td>
                                <div style="display:flex;flex-direction:column;">
                                    <div style="font-weight:600;"><?php echo htmlspecialchars($log['login_utilisateur'] ?? 'N/A'); ?></div>
                                    <div class="small"><?php echo htmlspecialchars($log['nom_utilisateur'] ?? 'N/A'); ?></div>
                                </div>
                            </td>
                            <td>
                                <button type="button" class="btn btn-muted open-delete-modal" data-log-id="<?php echo $log['id_piste']; ?>" title="Supprimer ce log" aria-label="Supprimer le log <?php echo $log['id_piste']; ?>">
                                    <i class="fas fa-trash-alt" style="color:#374151;"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="pagination" role="navigation" aria-label="Pagination">
                <div class="page-info">
                    Affichage de <strong><?php echo (($page - 1) * $perPage) + 1; ?></strong> à <strong><?php echo min($page * $perPage, $totalLogs); ?></strong> sur <strong><?php echo $totalLogs; ?></strong> enregistrements
                </div>
                <div style="display:flex; gap:8px; align-items:center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=piste_audit&page_num=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>" class="pagination-item"><i class="fas fa-chevron-left"></i></a>
                    <?php else: ?>
                        <span class="pagination-item" aria-hidden="true" style="opacity:0.5;"><i class="fas fa-chevron-left"></i></span>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="pagination-item" style="background:var(--ufhb-green); color:#fff;"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=piste_audit&page_num=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>" class="pagination-item"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=piste_audit&page_num=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function($key) { return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>" class="pagination-item"><i class="fas fa-chevron-right"></i></a>
                    <?php else: ?>
                        <span class="pagination-item" aria-hidden="true" style="opacity:0.5;"><i class="fas fa-chevron-right"></i></span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div style="margin-top:18px; background:#fff; border-radius:12px; box-shadow:0 8px 24px var(--card-shadow); overflow:hidden;">
        <div style="padding:12px 16px; background:#eee; color:#111; font-weight:700;">Nettoyage des Logs</div>
        <div style="padding:16px;">
            <form id="cleanupForm" method="POST" action="?page=piste_audit&action=cleanup" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">
                <div style="display:flex; flex-direction:column;">
                    <label class="small">Supprimer les logs de plus de</label>
                    <input type="number" name="days" min="1" max="365" value="30" required class="filter-input" style="width:140px;">
                </div>
                <div>
                    <span class="small" style="display:block; margin-bottom:6px;">&nbsp;</span>
                    <button type="button" id="openCleanupModalBtn" class="btn btn-blue"><i class="fas fa-trash-alt"></i> Nettoyer</button>
                </div>
                <div style="flex:1;">
                    <p class="small" style="margin:0;">Cette action supprimera définitivement tous les logs d'audit antérieurs à la période spécifiée.</p>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="modal-backdrop" style="display:none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="delete-title">
        <h2 id="delete-title" style="margin:0 0 12px 0; font-size:18px; color:#0f1720;"><i class="fas fa-exclamation-triangle" style="color:var(--ufhb-blue); margin-right:8px;"></i>Confirmation de suppression</h2>
        <p style="margin:0 0 16px 0; color:#374151;">Êtes-vous sûr de vouloir supprimer ce log d'audit ? Cette action est irréversible.</p>
        <form id="deleteLogForm" method="POST" action="?page=piste_audit&action=delete_log">
            <input type="hidden" name="log_id" id="deleteLogId" value="">
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" id="cancelDeleteBtn" class="btn btn-muted">Annuler</button>
                <button type="submit" class="btn btn-blue">Supprimer</button>
            </div>
        </form>
    </div>
</div>

<!-- Cleanup Modal -->
<div id="cleanupModal" class="modal-backdrop" style="display:none;">
    <div class="modal" role="dialog" aria-modal="true" aria-labelledby="cleanup-title">
        <h2 id="cleanup-title" style="margin:0 0 12px 0; font-size:18px; color:#0f1720;"><i class="fas fa-exclamation-triangle" style="color:var(--ufhb-blue); margin-right:8px;"></i>Confirmation du nettoyage</h2>
        <p style="margin:0 0 16px 0; color:#374151;">Êtes-vous sûr de vouloir supprimer tous les logs d'audit plus anciens que la période spécifiée ? Cette action est irréversible.</p>
        <div style="display:flex; justify-content:flex-end; gap:8px;">
            <button type="button" id="cancelCleanupBtn" class="btn btn-muted">Annuler</button>
            <button type="button" id="confirmCleanupBtn" class="btn btn-blue">Nettoyer</button>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        flatpickr("#date_debut", { locale: "fr", dateFormat: "Y-m-d", allowInput: true });
        flatpickr("#date_fin", { locale: "fr", dateFormat: "Y-m-d", allowInput: true });

        let searchTimeout;
        const searchEl = document.getElementById('search');
        if (searchEl) {
            searchEl.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function() { searchEl.form.submit(); }, 500);
            });
        }

        const deleteModal = document.getElementById('deleteModal');
        const deleteLogIdInput = document.getElementById('deleteLogId');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

        Array.from(document.getElementsByClassName('open-delete-modal')).forEach(function(btn){
            btn.addEventListener('click', function() {
                const logId = this.getAttribute('data-log-id');
                deleteLogIdInput.value = logId;
                deleteModal.style.display = 'flex';
            });
        });

        cancelDeleteBtn.addEventListener('click', function(e){
            e.preventDefault();
            deleteModal.style.display = 'none';
        });

        window.addEventListener('click', function(e){
            if (e.target === deleteModal) deleteModal.style.display = 'none';
        });

        const cleanupModal = document.getElementById('cleanupModal');
        const openCleanupModalBtn = document.getElementById('openCleanupModalBtn');
        const cancelCleanupBtn = document.getElementById('cancelCleanupBtn');
        const confirmCleanupBtn = document.getElementById('confirmCleanupBtn');
        const cleanupForm = document.getElementById('cleanupForm');

        openCleanupModalBtn.addEventListener('click', function(){ cleanupModal.style.display = 'flex'; });
        cancelCleanupBtn.addEventListener('click', function(){ cleanupModal.style.display = 'none'; });
        confirmCleanupBtn.addEventListener('click', function(){ cleanupModal.style.display = 'none'; cleanupForm.submit(); });

        window.addEventListener('click', function(e){ if (e.target === cleanupModal) cleanupModal.style.display = 'none'; });
    });
</script>
</body>
</html>
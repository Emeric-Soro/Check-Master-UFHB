<?php
function getActionColor($action)
{
    switch ($action) {
        case 'Création':
            return 'badge-success';
        case 'Modification':
            return 'badge-info';
        case 'Suppression':
            return 'badge-danger';
        case 'Connexion':
            return 'badge-success';
        case 'Déconnexion':
            return 'badge-warning';
        case 'Validation':
            return 'badge-success';
        case 'Rejet':
            return 'badge-danger';
        case 'Sauvegarde':
            return 'badge-info';
        case 'Restauration':
            return 'badge-info';
        case 'Impression':
            return 'badge-info';
        case 'Exportation':
            return 'badge-info';
        default:
            return 'badge-secondary';
    }
}
$auditLog = $GLOBALS['auditLog'];
?>

<div class="container">
    <div class="page-header">
        <div>
            <h1><i class="fas fa-shield-alt"></i> Piste d'Audit</h1>
            <p>Historique des actions système</p>
        </div>
        <div class="card-actions">
            <div style="text-align: right;">
                <label style="display:block;font-size:12px;color:#64748B;margin-bottom:4px;">Date du jour</label>
                <input type="text" class="input" readonly value="<?php echo date('d/m/Y'); ?>" style="width:140px;">
            </div>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'cleanup'): ?>
        <div class="alert alert-success">
            <strong>Succès !</strong>
            <div><?php echo $_GET['deleted'] ?? 0; ?> enregistrements d'audit ont été supprimés.</div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'log_deleted'): ?>
        <div class="alert alert-success">
            <strong>Succès !</strong>
            <div>Le log d'audit a été supprimé avec succès.</div>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger">
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

    <div class="card">
        <div class="card-header">
            <h3 class="card-title">
                <i class="fas fa-history"></i>
                Piste d'Audit - Historique des Actions
            </h3>
        </div>

        <div class="filter-bar">
            <form method="GET" action="?page=piste_audit">
                <input type="hidden" name="page" value="piste_audit">
                <div style="display:flex;flex-wrap:wrap;gap:12px;align-items:flex-end;">
                    <div class="form-group">
                        <label>Date début</label>
                        <input type="text" name="date_debut" id="date_debut"
                            value="<?php echo htmlspecialchars($_GET['date_debut'] ?? ''); ?>"
                            placeholder="Date de début" class="input">
                    </div>

                    <div class="form-group">
                        <label>Date fin</label>
                        <input type="text" name="date_fin" id="date_fin"
                            value="<?php echo htmlspecialchars($_GET['date_fin'] ?? ''); ?>"
                            placeholder="Date de fin" class="input">
                    </div>

                    <div class="form-group">
                        <label>Action</label>
                        <select name="action" class="select">
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

                    <div class="form-group">
                        <label>Table</label>
                        <select name="table" class="select">
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

                    <div class="form-group">
                        <label>Statut</label>
                        <select name="statut" class="select">
                            <option value="">Tous les statuts</option>
                            <option value="Succès" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'Succès') ? 'selected' : ''; ?>>Succès</option>
                            <option value="Erreur" <?php echo (isset($_GET['statut']) && $_GET['statut'] === 'Erreur') ? 'selected' : ''; ?>>Erreur</option>
                        </select>
                    </div>

                    <div class="form-group" style="flex:1;">
                        <label>Recherche</label>
                        <input type="text" name="search" id="search"
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"
                            placeholder="Rechercher..." class="input">
                    </div>

                    <div class="form-group" style="margin-left:auto;">
                        <label>&nbsp;</label>
                        <div style="display:flex;gap:8px;">
                            <button type="submit" class="btn btn-primary" title="Filtrer">
                                <i class="fas fa-search"></i>
                                <span>Filtrer</span>
                            </button>
                            <a href="?page=piste_audit" class="btn btn-secondary" title="Réinitialiser">
                                <i class="fas fa-times"></i>
                                <span>Réinitialiser</span>
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="card-body" style="padding:12px 16px;border-bottom:1px solid rgba(0,0,0,0.08);">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <div><strong><?php echo count($auditLog); ?></strong> enregistrements trouvés</div>
                <div class="action-buttons">
                    <a href="?page=piste_audit&action=export&<?php echo http_build_query(array_filter($_GET, function ($key) {
                        return $key !== 'page'; }, ARRAY_FILTER_USE_KEY)); ?>"
                        class="btn btn-primary" title="Exporter">
                        <i class="fas fa-file-export"></i>
                        <span>Exporter</span>
                    </a>
                    <button onclick="window.print()" class="btn btn-primary" title="Imprimer">
                        <i class="fas fa-print"></i>
                        <span>Imprimer</span>
                    </button>
                    <a href="?page=piste_audit" class="btn btn-success" title="Actualiser">
                        <i class="fas fa-sync-alt"></i>
                        <span>Actualiser</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="table-wrapper">
            <table>
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
                            <td colspan="7" style="text-align:center;padding:48px 0;color:#6b7280;">
                                <i class="fas fa-search" style="font-size:36px;color:#cbd5e1;display:block;margin-bottom:12px;"></i>
                                <div>Aucun log d'audit trouvé pour les critères sélectionnés.</div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($auditLog as $log): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($log['date_creation'])); ?></td>
                                <td><?php echo date('H:i:s', strtotime($log['date_creation'])); ?></td>
                                <td>
                                    <span class="badge <?php echo getActionColor($log['action']); ?>">
                                        <?php echo htmlspecialchars($log['action']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($log['statut_action'] === 'Succès'): ?>
                                        <span class="badge badge-success"><?php echo htmlspecialchars($log['statut_action']); ?></span>
                                    <?php else: ?>
                                        <span class="badge badge-danger"><?php echo htmlspecialchars($log['statut_action']); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo htmlspecialchars($log['nom_table']); ?></td>
                                <td>
                                    <div style="font-weight:600;">
                                        <?php echo htmlspecialchars($log['login_utilisateur'] ?? 'N/A'); ?>
                                    </div>
                                    <div style="font-size:12px;color:#6b7280;">
                                        <?php echo htmlspecialchars($log['nom_utilisateur'] ?? 'N/A'); ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if (canDelete()): ?>
                                    <button type="button" class="btn btn-secondary open-delete-modal"
                                        data-log-id="<?php echo $log['id_piste']; ?>" 
                                        title="Supprimer ce log"
                                        aria-label="Supprimer le log <?php echo $log['id_piste']; ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if (($totalPages ?? 1) > 1): ?>
            <div class="card-footer" style="display:flex;justify-content:space-between;align-items:center;padding:12px 16px;">
                <div style="color:#374151;font-size:14px;">
                    Affichage de <strong><?php echo (($page - 1) * $perPage) + 1; ?></strong> à
                    <strong><?php echo min($page * $perPage, $totalLogs); ?></strong> sur
                    <strong><?php echo $totalLogs; ?></strong> enregistrements
                </div>
                <div style="display:flex;gap:8px;align-items:center;">
                    <?php if ($page > 1): ?>
                        <a href="?page=piste_audit&page_num=<?php echo $page - 1; ?>&<?php echo http_build_query(array_filter($_GET, function ($key) {
                                 return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>"
                            class="btn btn-secondary btn-sm">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    <?php else: ?>
                        <span class="btn btn-secondary btn-sm" style="opacity:0.5;cursor:not-allowed;">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                    <?php endif; ?>

                    <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="btn btn-success btn-sm"><?php echo $i; ?></span>
                        <?php else: ?>
                            <a href="?page=piste_audit&page_num=<?php echo $i; ?>&<?php echo http_build_query(array_filter($_GET, function ($key) {
                                   return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>"
                                class="btn btn-secondary btn-sm"><?php echo $i; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <a href="?page=piste_audit&page_num=<?php echo $page + 1; ?>&<?php echo http_build_query(array_filter($_GET, function ($key) {
                                 return !in_array($key, ['page', 'page_num']); }, ARRAY_FILTER_USE_KEY)); ?>"
                            class="btn btn-secondary btn-sm">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    <?php else: ?>
                        <span class="btn btn-secondary btn-sm" style="opacity:0.5;cursor:not-allowed;">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="card" style="margin-top:20px;">
        <div class="card-header">
            <h3 class="card-title">Nettoyage des Logs</h3>
        </div>
        <div class="card-body">
            <?php if (canDelete()): ?>
            <form id="cleanupForm" method="POST" action="?page=piste_audit&action=cleanup" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap;">
                <div class="form-group">
                    <label>Supprimer les logs de plus de</label>
                    <input type="number" name="days" min="1" max="365" value="30" required class="input" style="width:140px;">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <button type="button" id="openCleanupModalBtn" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Nettoyer
                    </button>
                </div>
                <div style="flex:1;padding-top:20px;">
                    <p style="margin:0;font-size:13px;color:#6b7280;">
                        Cette action supprimera définitivement tous les logs d'audit antérieurs à la période spécifiée.
                    </p>
                </div>
            </form>
            <?php else: ?>
            <p style="margin:0;color:#6b7280;">Vous n'avez pas les permissions nécessaires pour nettoyer les logs.</p>
            <?php endif; ?>
        </div>
    </div>
</div>



<!-- Delete Modal -->
<div id="deleteModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>
                Confirmation de suppression
            </h3>
        </div>
        <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer ce log d'audit ? Cette action est irréversible.</p>
            <form id="deleteLogForm" method="POST" action="?page=piste_audit&action=delete_log">
                <input type="hidden" name="log_id" id="deleteLogId" value="">
                <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
                    <button type="button" id="cancelDeleteBtn" class="btn btn-secondary">Annuler</button>
                    <button type="submit" class="btn btn-danger">Supprimer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Cleanup Modal -->
<div id="cleanupModal" class="modal" style="display:none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3 style="margin:0;display:flex;align-items:center;gap:8px;">
                <i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i>
                Confirmation du nettoyage
            </h3>
        </div>
        <div class="modal-body">
            <p>Êtes-vous sûr de vouloir supprimer tous les logs d'audit plus anciens que la période spécifiée ? Cette action est irréversible.</p>
            <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px;">
                <button type="button" id="cancelCleanupBtn" class="btn btn-secondary">Annuler</button>
                <button type="button" id="confirmCleanupBtn" class="btn btn-danger">Nettoyer</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        flatpickr("#date_debut", { locale: "fr", dateFormat: "Y-m-d", allowInput: true });
        flatpickr("#date_fin", { locale: "fr", dateFormat: "Y-m-d", allowInput: true });

        let searchTimeout;
        const searchEl = document.getElementById('search');
        if (searchEl) {
            searchEl.addEventListener('input', function () {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(function () { searchEl.form.submit(); }, 500);
            });
        }

        const deleteModal = document.getElementById('deleteModal');
        const deleteLogIdInput = document.getElementById('deleteLogId');
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');

        Array.from(document.getElementsByClassName('open-delete-modal')).forEach(function (btn) {
            btn.addEventListener('click', function () {
                const logId = this.getAttribute('data-log-id');
                deleteLogIdInput.value = logId;
                deleteModal.style.display = 'flex';
            });
        });

        cancelDeleteBtn.addEventListener('click', function (e) {
            e.preventDefault();
            deleteModal.style.display = 'none';
        });

        window.addEventListener('click', function (e) {
            if (e.target === deleteModal) deleteModal.style.display = 'none';
        });

        const cleanupModal = document.getElementById('cleanupModal');
        const openCleanupModalBtn = document.getElementById('openCleanupModalBtn');
        const cancelCleanupBtn = document.getElementById('cancelCleanupBtn');
        const confirmCleanupBtn = document.getElementById('confirmCleanupBtn');
        const cleanupForm = document.getElementById('cleanupForm');

        openCleanupModalBtn.addEventListener('click', function () { cleanupModal.style.display = 'flex'; });
        cancelCleanupBtn.addEventListener('click', function () { cleanupModal.style.display = 'none'; });
        confirmCleanupBtn.addEventListener('click', function () { cleanupModal.style.display = 'none'; cleanupForm.submit(); });

        window.addEventListener('click', function (e) { if (e.target === cleanupModal) cleanupModal.style.display = 'none'; });
    });
</script>

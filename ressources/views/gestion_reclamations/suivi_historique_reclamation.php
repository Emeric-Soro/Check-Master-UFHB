<?php

$reclamations = is_array($reclamations ?? null) ? $reclamations : [];
$statistiques = is_array($statistiques ?? null) ? $statistiques : [];

$totalReclamations = (int) ($statistiques['total'] ?? count($reclamations));
$enAttente = (int) ($statistiques['en_attente'] ?? 0);
$resolues = (int) ($statistiques['resolue'] ?? 0);
$rejetees = (int) ($statistiques['rejetee'] ?? 0);

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);

$filterStatus = (string) ($_GET['status'] ?? 'all');
$filterType = (string) ($_GET['type'] ?? 'all');
$currentPage = max(1, (int) ($_GET['p'] ?? 1));
$totalPages = (int) ($totalPages ?? 1);
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                
                <p class="cm-etu-panel__subtitle">Consultez le statut et l'historique de toutes vos réclamations.</p>
            </div>
            <span class="cm-etu-count-badge"><?= $totalReclamations ?> réclamation<?= $totalReclamations > 1 ? 's' : '' ?></span>
        </header>

        <?php if (is_array($message)): ?>
            <?php cm_component('ui/alert-box', [
                    'type' => ($message['type'] ?? '') === 'success' ? 'success' : 'danger',
                    'message' => (string) ($message['text'] ?? ''),
            ]); ?>
        <?php endif; ?>

        <!-- Filtres -->
        <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
.cm-content-area form .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="GET" class="cm-etu-toolbar">
            <input type="hidden" name="page" value="gestion_reclamations">
            <input type="hidden" name="action" value="suivi_historique_reclamation">

            <label class="cm-etu-toolbar__field" for="filterRecStatus">
                <span>Statut</span>
                <select id="filterRecStatus" name="status" class="cm-form-control">
                    <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>Tous les statuts</option>
                    <option value="en_attente" <?= $filterStatus === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="resolue" <?= $filterStatus === 'resolue' ? 'selected' : '' ?>>Résolue</option>
                    <option value="rejetee" <?= $filterStatus === 'rejetee' ? 'selected' : '' ?>>Rejetée</option>
                </select>
            </label>

            <label class="cm-etu-toolbar__field" for="filterRecType">
                <span>Type</span>
                <select id="filterRecType" name="type" class="cm-form-control">
                    <option value="all" <?= $filterType === 'all' ? 'selected' : '' ?>>Tous les types</option>
                    <option value="academic" <?= $filterType === 'academic' ? 'selected' : '' ?>>Académique</option>
                    <option value="financial" <?= $filterType === 'financial' ? 'selected' : '' ?>>Financière</option>
                    <option value="administrative" <?= $filterType === 'administrative' ? 'selected' : '' ?>>Administrative</option>
                    <option value="technical" <?= $filterType === 'technical' ? 'selected' : '' ?>>Technique</option>
                </select>
            </label>

            <div class="cm-etu-toolbar__field" style="flex-direction: row; align-items: flex-end; gap: 0.35rem;">
                <button type="submit" class="cm-btn is-primary is-sm">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span>Filtrer</span>
                </button>
                <a href="?page=gestion_reclamations&action=suivi_historique_reclamation" class="cm-btn is-light is-sm">
                    <i class="fas fa-rotate-left" aria-hidden="true"></i>
                    <span>Réinitialiser</span>
                </a>
            </div>
        </form>

        <!-- Statistiques -->
        <div class="cm-etu-hub-grid cm-etu-hub-grid--4">
            <article class="cm-etu-hub-card cm-etu-hub-card--stat">
                <div class="cm-etu-hub-card__icon"><i class="fas fa-file-alt" aria-hidden="true"></i></div>

                <p class="cm-etu-stat-value"><?= $totalReclamations ?></p>
            </article>
            <article class="cm-etu-hub-card cm-etu-hub-card--stat">
                <div class="cm-etu-hub-card__icon" style="background: rgba(243, 156, 18, 0.14); color: #e67e22;"><i class="fas fa-clock" aria-hidden="true"></i></div>

                <p class="cm-etu-stat-value"><?= $enAttente ?></p>
            </article>
            <article class="cm-etu-hub-card cm-etu-hub-card--stat">
                <div class="cm-etu-hub-card__icon" style="background: rgba(39, 174, 96, 0.14); color: #27ae60;"><i class="fas fa-check-circle" aria-hidden="true"></i></div>

                <p class="cm-etu-stat-value"><?= $resolues ?></p>
            </article>
            <article class="cm-etu-hub-card cm-etu-hub-card--stat">
                <div class="cm-etu-hub-card__icon" style="background: rgba(231, 76, 60, 0.14); color: #e74c3c;"><i class="fas fa-times-circle" aria-hidden="true"></i></div>

                <p class="cm-etu-stat-value"><?= $rejetees ?></p>
            </article>
        </div>

        <!-- Liste des réclamations -->
        <div class="cm-etu-list-header">

            <?php if (!empty($reclamations)): ?>
                <?php cm_component('ui/badge', ['type' => 'info', 'text' => count($reclamations) . ' résultat' . (count($reclamations) > 1 ? 's' : '')]); ?>
            <?php endif; ?>
        </div>

        <?php if (empty($reclamations)): ?>
            <div class="cm-etu-empty">
                <i class="fas fa-inbox" aria-hidden="true"></i>
                <?php if ($filterStatus !== 'all' || $filterType !== 'all'): ?>
                    <p>Aucune réclamation ne correspond aux filtres sélectionnés.</p>
                <?php else: ?>
                    <p>Vous n'avez pas encore soumis de réclamation.</p>
                <?php endif; ?>
                <?php if (canCreate()): ?>
                    <a class="cm-btn is-primary" href="?page=gestion_reclamations&action=soumettre_reclamation">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span>Créer une réclamation</span>
                    </a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div class="cm-etu-report-list">
                <?php foreach ($reclamations as $rec): ?>
                    <?php
                    $recId = (int) ($rec['id_reclamation'] ?? 0);
                    $sujet = (string) ($rec['titre_reclamation'] ?? $rec['objet'] ?? 'Réclamation');
                    $typeRec = (string) ($rec['type_reclamation'] ?? '');
                    $dateRec = (string) ($rec['date_creation'] ?? $rec['date_reclamation'] ?? '');
                    $statutRec = (string) ($rec['statut_reclamation'] ?? $rec['statut'] ?? 'En attente');
                    $statutLower = strtolower($statutRec);

                    $badgeType = 'light';
                    $badgeText = $statutRec;
                    if (str_contains($statutLower, 'attente')) {
                        $badgeType = 'warning';
                        $badgeText = 'En attente';
                    } elseif (str_contains($statutLower, 'cours')) {
                        $badgeType = 'info';
                        $badgeText = 'En cours';
                    } elseif (str_contains($statutLower, 'solue') || str_contains($statutLower, 'résolu')) {
                        $badgeType = 'success';
                        $badgeText = 'Résolue';
                    } elseif (str_contains($statutLower, 'rejet')) {
                        $badgeType = 'danger';
                        $badgeText = 'Rejetée';
                    }
                    ?>
                    <article class="cm-etu-report-item" data-rec-id="<?= $recId ?>">
                        <div class="cm-etu-report-item__main">
                            <div class="cm-etu-report-item__head">
                                <span class="cm-etu-report-item__title">REC-<?= $recId ?> — <?= htmlspecialchars($sujet, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                            </div>
                            <?php if ($typeRec !== ''): ?>
                                <p class="cm-etu-report-item__meta"><strong>Type :</strong> <?= htmlspecialchars($typeRec, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if ($dateRec !== ''): ?>
                                <p class="cm-etu-report-item__meta">Soumise le <?= date('d/m/Y', strtotime($dateRec)) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="cm-etu-report-item__actions">
                            <button type="button" class="cm-btn is-info is-sm js-view-reclamation" data-rec-id="<?= $recId ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span>Voir détail</span>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="cm-etu-actions" style="justify-content: space-between;">
                    <span class="cm-etu-help">
                        Page <?= $currentPage ?> sur <?= $totalPages ?>
                    </span>
                    <div style="display: flex; gap: 0.35rem;">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=gestion_reclamations&action=suivi_historique_reclamation&p=<?= $currentPage - 1 ?><?= $filterStatus !== 'all' ? '&status=' . urlencode($filterStatus) : '' ?><?= $filterType !== 'all' ? '&type=' . urlencode($filterType) : '' ?>" class="cm-btn is-light is-sm">
                                <i class="fas fa-chevron-left" aria-hidden="true"></i>
                                <span>Précédent</span>
                            </a>
                        <?php endif; ?>
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=gestion_reclamations&action=suivi_historique_reclamation&p=<?= $currentPage + 1 ?><?= $filterStatus !== 'all' ? '&status=' . urlencode($filterStatus) : '' ?><?= $filterType !== 'all' ? '&type=' . urlencode($filterType) : '' ?>" class="cm-btn is-light is-sm">
                                <span>Suivant</span>
                                <i class="fas fa-chevron-right" aria-hidden="true"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </section>
</div>

<!-- Modal détails réclamation -->
<div id="recDetailModal" class="cm-legacy-panel cm-etu-modal" hidden>
    <div class="cm-etu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="recDetailTitle" style="width: min(680px, 100%); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center;">

            <button type="button" class="cm-btn is-light is-sm" id="closeRecDetail">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div id="recDetailContent">
            <p class="cm-etu-help">Chargement...</p>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('recDetailModal');
        var contentEl = document.getElementById('recDetailContent');
        var closeBtn = document.getElementById('closeRecDetail');

        if (!modal || !contentEl || !closeBtn) return;

        function openModal(recId) {
            contentEl.innerHTML = '<p style="text-align:center;padding:1rem;color:#6b7785;">Chargement des détails…</p>';
            modal.hidden = false;

            fetch('?page=gestion_reclamations&action=get_reclamation_details&id=' + encodeURIComponent(recId))
                .then(function (res) {
                    if (!res.ok) throw new Error('Erreur réseau');
                    return res.text();
                })
                .then(function (html) { contentEl.innerHTML = html; })
                .catch(function () {
                    contentEl.innerHTML = '<p style="text-align:center;padding:1rem;color:#e74c3c;"><i class="fas fa-triangle-exclamation"></i> Erreur lors du chargement.</p>';
                });
        }

        function closeModal() { modal.hidden = true; }

        document.querySelectorAll('.js-view-reclamation').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal(btn.getAttribute('data-rec-id'));
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    })();
</script>


<?php

$rapports = is_array($rapports ?? null) ? $rapports : [];
$statistiquesCompteRendu = is_array($statistiquesCompteRendu ?? null) ? $statistiquesCompteRendu : [];

$totalRapports = (int) ($statistiquesCompteRendu['total'] ?? count($rapports));
$semaine = (int) ($statistiquesCompteRendu['semaine'] ?? 0);
$mois = (int) ($statistiquesCompteRendu['mois'] ?? 0);

$filterStatut = (string) ($_GET['statut'] ?? '');
$filterSearch = (string) ($_GET['search'] ?? '');
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                
                <p class="cm-etu-panel__subtitle">Consultez les commentaires publiés par les évaluateurs sur vos rapports.</p>
            </div>
            <a href="?page=gestion_rapports" class="cm-btn is-light is-sm">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
        </header>

        <!-- Statistiques -->
        <?php if (!empty($statistiquesCompteRendu)): ?>
            <div class="cm-etu-hub-grid">
                <article class="cm-etu-hub-card cm-etu-hub-card--stat">
                    <div class="cm-etu-hub-card__icon"><i class="fas fa-file-alt" aria-hidden="true"></i></div>

                    <p class="cm-etu-stat-value"><?= $totalRapports ?></p>
                </article>
                <article class="cm-etu-hub-card cm-etu-hub-card--stat">
                    <div class="cm-etu-hub-card__icon" style="background: rgba(39, 174, 96, 0.14); color: #27ae60;"><i class="fas fa-check-circle" aria-hidden="true"></i></div>

                    <p class="cm-etu-stat-value"><?= $semaine ?></p>
                </article>
                <article class="cm-etu-hub-card cm-etu-hub-card--stat">
                    <div class="cm-etu-hub-card__icon" style="background: rgba(243, 156, 18, 0.14); color: #e67e22;"><i class="fas fa-calendar-alt" aria-hidden="true"></i></div>

                    <p class="cm-etu-stat-value"><?= $mois ?></p>
                </article>
            </div>
        <?php endif; ?>

        <!-- Filtres -->
        <form method="GET" class="cm-etu-toolbar">
            <input type="hidden" name="page" value="gestion_rapports">
            <input type="hidden" name="action" value="commentaire_rapport">

            <label class="cm-etu-toolbar__field" for="filterComStatut">
                <span>Statut</span>
                <select id="filterComStatut" name="statut" class="cm-form-control">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente" <?= $filterStatut === 'en_attente' ? 'selected' : '' ?>>En attente</option>
                    <option value="en_cours" <?= $filterStatut === 'en_cours' ? 'selected' : '' ?>>En cours</option>
                    <option value="valider" <?= $filterStatut === 'valider' ? 'selected' : '' ?>>Validé</option>
                    <option value="rejeter" <?= $filterStatut === 'rejeter' ? 'selected' : '' ?>>Rejeté</option>
                </select>
            </label>

            <label class="cm-etu-toolbar__field cm-etu-toolbar__field--grow" for="filterComSearch">
                <span>Recherche</span>
                <input id="filterComSearch" name="search" type="search" class="cm-form-control" value="<?= htmlspecialchars($filterSearch, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher un rapport...">
            </label>

            <div class="cm-etu-toolbar__field" style="flex-direction: row; align-items: flex-end; gap: 0.35rem;">
                <button type="submit" class="cm-btn is-primary is-sm">
                    <i class="fas fa-search" aria-hidden="true"></i>
                    <span>Filtrer</span>
                </button>
                <?php if ($filterStatut !== '' || $filterSearch !== ''): ?>
                    <a href="?page=gestion_rapports&action=commentaire_rapport" class="cm-btn is-light is-sm">
                        <i class="fas fa-xmark" aria-hidden="true"></i>
                        <span>Effacer</span>
                    </a>
                <?php endif; ?>
            </div>
        </form>

        <!-- Liste des rapports -->
        <?php if (!empty($rapports)): ?>
            <div class="cm-etu-report-list">
                <?php foreach ($rapports as $rapport): ?>
                    <?php
                    $rapportId = (int) ($rapport['id_rapport'] ?? 0);
                    $nomRapport = (string) ($rapport['nom_rapport'] ?? 'Rapport');
                    $themeRapport = (string) ($rapport['theme_rapport'] ?? '');
                    $dateRapport = (string) ($rapport['date_rapport'] ?? '');
                    $statutRapport = strtolower((string) ($rapport['statut_rapport'] ?? 'en_attente'));
                    $etudiant = '';
                    if (isset($rapport['nom_etu']) && isset($rapport['prenom_etu'])) {
                        $etudiant = trim($rapport['prenom_etu'] . ' ' . $rapport['nom_etu']);
                    }

                    $badgeType = 'light';
                    $badgeText = 'En attente';
                    if ($statutRapport === 'en_cours') {
                        $badgeType = 'info';
                        $badgeText = 'En cours';
                    } elseif ($statutRapport === 'valider') {
                        $badgeType = 'success';
                        $badgeText = 'Validé';
                    } elseif ($statutRapport === 'rejeter') {
                        $badgeType = 'danger';
                        $badgeText = 'Rejeté';
                    }
                    ?>
                    <article class="cm-etu-report-item">
                        <div class="cm-etu-report-item__main">
                            <div class="cm-etu-report-item__head">
                                <span class="cm-etu-report-item__title"><?= htmlspecialchars($nomRapport, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                            </div>
                            <?php if ($themeRapport !== ''): ?>
                                <p class="cm-etu-report-item__meta"><strong>Thème :</strong> <?= htmlspecialchars($themeRapport, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if ($dateRapport !== ''): ?>
                                <p class="cm-etu-report-item__meta">Soumis le <?= date('d/m/Y à H:i', strtotime($dateRapport)) ?></p>
                            <?php endif; ?>
                            <?php if ($etudiant !== ''): ?>
                                <p class="cm-etu-report-item__meta">Étudiant : <?= htmlspecialchars($etudiant, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="cm-etu-report-item__actions">
                            <button type="button" class="cm-btn is-info is-sm js-view-comments" data-rapport-id="<?= $rapportId ?>">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span>Voir détails</span>
                            </button>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cm-etu-empty">
                <i class="fas fa-file-alt" aria-hidden="true"></i>
                <p>Aucun rapport trouvé avec des commentaires.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<!-- Modal commentaires -->
<div id="commentsDetailModal" class="cm-etu-modal" hidden>
    <div class="cm-etu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="commentsDetailTitle" style="width: min(680px, 100%); max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center;">

            <button type="button" class="cm-btn is-light is-sm" id="closeCommentsModal">
                <i class="fas fa-xmark" aria-hidden="true"></i>
            </button>
        </div>
        <div id="commentsModalContent">
            <p class="cm-etu-help">Chargement...</p>
        </div>
    </div>
</div>

<script>
    (function () {
        var modal = document.getElementById('commentsDetailModal');
        var contentEl = document.getElementById('commentsModalContent');
        var closeBtn = document.getElementById('closeCommentsModal');

        if (!modal || !contentEl || !closeBtn) return;

        function openModal(rapportId) {
            contentEl.innerHTML = '<p style="text-align:center;padding:1rem;color:#6b7785;">Chargement des commentaires…</p>';
            modal.hidden = false;

            fetch('?page=gestion_rapports&action=get_commentaires&id=' + encodeURIComponent(rapportId))
                .then(function (res) {
                    if (!res.ok) throw new Error('Erreur réseau');
                    return res.text();
                })
                .then(function (html) { contentEl.innerHTML = html; })
                .catch(function () {
                    contentEl.innerHTML = '<p style="text-align:center;padding:1rem;color:#e74c3c;"><i class="fas fa-triangle-exclamation"></i> Erreur lors du chargement des commentaires.</p>';
                });
        }

        function closeModal() { modal.hidden = true; }

        document.querySelectorAll('.js-view-comments').forEach(function (btn) {
            btn.addEventListener('click', function () {
                openModal(btn.getAttribute('data-rapport-id'));
            });
        });

        closeBtn.addEventListener('click', closeModal);
        modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });
    })();
</script>
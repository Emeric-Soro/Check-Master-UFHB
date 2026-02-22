<?php
$infosDepot = is_array($GLOBALS['infosDepot'] ?? null) ? $GLOBALS['infosDepot'] : [];
$rapportsRecents = is_array($rapportsRecents ?? null) ? $rapportsRecents : [];
$totalRapports = isset($statistiquesRapports->total_rapports)
    ? (int) $statistiquesRapports->total_rapports
    : count($rapportsRecents);

$messageMap = [
    'depot_ok' => ['type' => 'success', 'text' => 'Le rapport a bien été déposé et votre candidature a été transmise.'],
    'depot_fail' => ['type' => 'danger', 'text' => 'Impossible de déposer le rapport. Vérifiez son état puis réessayez.'],
    'depot_en_cours' => ['type' => 'warning', 'text' => 'Vous avez déjà un rapport en cours d\'évaluation.'],
    'depot_quota' => ['type' => 'warning', 'text' => 'Le dépôt est bloqué: minimum 5 000 mots requis pour le rapport.'],
    'suppression_ok' => ['type' => 'success', 'text' => 'Le rapport a été supprimé avec succès.'],
];
$messageKey = (string) ($_GET['message'] ?? '');
$flash = $messageMap[$messageKey] ?? null;
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                
                <p class="cm-etu-panel__subtitle">Créez, suivez et déposez vos rapports de stage.</p>
            </div>
            <span class="cm-etu-count-badge"><?= (int) $totalRapports ?> rapport<?= (int) $totalRapports > 1 ? 's' : '' ?></span>
        </header>

        <?php if ($flash !== null): ?>
            <?php cm_component('ui/alert-box', ['type' => $flash['type'], 'message' => $flash['text']]); ?>
        <?php endif; ?>

        <div class="cm-etu-hub-grid">
            <article class="cm-etu-hub-card">
                <div class="cm-etu-hub-card__icon"><i class="fas fa-plus" aria-hidden="true"></i></div>

                <p class="cm-etu-hub-card__desc">Démarrez une nouvelle rédaction avec le modèle institutionnel.</p>
                <?php if (canCreate()): ?>
                    <a class="cm-btn is-primary is-sm" href="?page=gestion_rapports&action=creer_rapport">Commencer</a>
                <?php endif; ?>
            </article>

            <article class="cm-etu-hub-card">
                <div class="cm-etu-hub-card__icon"><i class="fas fa-chart-line" aria-hidden="true"></i></div>

                <p class="cm-etu-hub-card__desc">Consultez le statut détaillé de chaque rapport.</p>
                <a class="cm-btn is-info is-sm" href="?page=gestion_rapports&action=suivi_rapport">Consulter</a>
            </article>

            <article class="cm-etu-hub-card">
                <div class="cm-etu-hub-card__icon"><i class="fas fa-comments" aria-hidden="true"></i></div>

                <p class="cm-etu-hub-card__desc">Accédez aux commentaires publiés par les évaluateurs.</p>
                <a class="cm-btn is-light is-sm" href="?page=gestion_rapports&action=commentaire_rapport">Voir</a>
            </article>
        </div>

        <div class="cm-etu-list-header">

            <?php cm_component('ui/badge', ['type' => 'info', 'text' => $totalRapports . ' rapport' . ($totalRapports > 1 ? 's' : '')]); ?>
        </div>

        <?php if (!empty($rapportsRecents)): ?>
            <div class="cm-etu-report-list">
                <?php foreach ($rapportsRecents as $rapport): ?>
                    <?php
                    $rapportId = (int) ($rapport->id_rapport ?? 0);
                    $infoDepot = $infosDepot[$rapportId] ?? ['peutDeposer' => true, 'messageDepot' => '', 'dejaDepose' => false];
                    $peutDeposer = (bool) ($infoDepot['peutDeposer'] ?? false);
                    $dejaDepose = (bool) ($infoDepot['dejaDepose'] ?? false);
                    $messageDepot = (string) ($infoDepot['messageDepot'] ?? '');

                    $statutRapport = strtolower((string) ($rapport->statut_rapport ?? ''));
                    $badgeType = 'light';
                    $badgeText = 'Brouillon';
                    if ($statutRapport === 'en_cours') {
                        $badgeType = 'info';
                        $badgeText = 'En cours';
                    } elseif ($statutRapport === 'valider') {
                        $badgeType = 'success';
                        $badgeText = 'Validé';
                    } elseif ($statutRapport === 'rejeter') {
                        $badgeType = 'danger';
                        $badgeText = 'Rejeté';
                    } elseif ($dejaDepose) {
                        $badgeType = 'warning';
                        $badgeText = 'Déposé';
                    }
                    ?>
                    <article class="cm-etu-report-item" data-report-title="<?= htmlspecialchars((string) ($rapport->nom_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        <div class="cm-etu-report-item__main">
                            <div class="cm-etu-report-item__head">
                                <span class="cm-etu-report-item__title"><?= htmlspecialchars((string) ($rapport->nom_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                            </div>
                            <p class="cm-etu-report-item__meta"><strong>Thème:</strong> <?= htmlspecialchars((string) ($rapport->theme_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?></p>
                            <p class="cm-etu-report-item__meta">Créé le <?= date('d/m/Y à H:i', strtotime((string) ($rapport->date_rapport ?? 'now'))) ?></p>
                        </div>

                        <div class="cm-etu-report-item__actions">
                            <?php if ($peutDeposer): ?>
                                <form method="POST" action="?page=gestion_rapports" class="cm-etu-inline-form">
                                    <input type="hidden" name="action" value="deposer_rapport">
                                    <input type="hidden" name="id_rapport" value="<?= $rapportId ?>">
                                    <button type="submit" class="cm-btn is-success is-sm">
                                        <i class="fas fa-upload" aria-hidden="true"></i>
                                        <span>Déposer</span>
                                    </button>
                                </form>
                            <?php else: ?>
                                <button type="button" class="cm-btn is-light is-sm" disabled title="<?= htmlspecialchars($messageDepot, ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-lock" aria-hidden="true"></i>
                                    <span><?= htmlspecialchars($messageDepot !== '' ? $messageDepot : 'Dépôt indisponible', ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            <?php endif; ?>

                            <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-info is-sm">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span>Voir</span>
                            </a>

                            <?php if (!$dejaDepose && canDelete()): ?>
                                <button
                                    type="button"
                                    class="cm-btn is-danger is-sm js-delete-report"
                                    data-report-id="<?= $rapportId ?>"
                                    data-report-name="<?= htmlspecialchars((string) ($rapport->nom_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <i class="fas fa-trash" aria-hidden="true"></i>
                                    <span>Suppr</span>
                                </button>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cm-etu-empty">
                <i class="fas fa-file-circle-plus" aria-hidden="true"></i>
                <p>Aucun rapport disponible pour le moment.</p>
                <?php if (canCreate()): ?>
                    <a class="cm-btn is-primary" href="?page=gestion_rapports&action=creer_rapport">Créer mon premier rapport</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>

<div id="deleteReportModal" class="cm-etu-modal" hidden>
    <div class="cm-etu-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="deleteReportTitle">

        <p>Le rapport <strong id="deleteReportName"></strong> sera supprimé définitivement.</p>
        <form method="POST" action="?page=gestion_rapports" id="deleteReportForm" class="cm-etu-modal__actions">
            <input type="hidden" name="action" value="supprimer_rapport">
            <input type="hidden" id="deleteReportId" name="rapport_id" value="">
            <button type="button" class="cm-btn is-light" id="cancelDeleteReport">Annuler</button>
            <button type="submit" class="cm-btn is-danger">Supprimer</button>
        </form>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('deleteReportModal');
    const deleteIdField = document.getElementById('deleteReportId');
    const deleteName = document.getElementById('deleteReportName');
    const cancelDelete = document.getElementById('cancelDeleteReport');

    if (!modal || !deleteIdField || !deleteName || !cancelDelete) {
        return;
    }

    function openModal(reportId, reportName) {
        deleteIdField.value = String(reportId || '');
        deleteName.textContent = reportName || 'ce rapport';
        modal.hidden = false;
    }

    function closeModal() {
        modal.hidden = true;
        deleteIdField.value = '';
        deleteName.textContent = '';
    }

    document.querySelectorAll('.js-delete-report').forEach(function (button) {
        button.addEventListener('click', function () {
            openModal(button.getAttribute('data-report-id'), button.getAttribute('data-report-name'));
        });
    });

    cancelDelete.addEventListener('click', closeModal);

    modal.addEventListener('click', function (event) {
        if (event.target === modal) {
            closeModal();
        }
    });
})();
</script>

<?php
require_once __DIR__ . '/../../../app/config/database.php';
$pdo = Database::getConnection();
$rapports = is_array($rapports ?? null) ? $rapports : [];
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                <h2 class="cm-etu-panel__title"><i class="fas fa-list-check" aria-hidden="true"></i> Suivi du Rapport</h2>
                <p class="cm-etu-panel__subtitle">Visualisez l\'avancement de vos dépôts via une timeline détaillée.</p>
            </div>
        </header>

        <div class="cm-etu-toolbar">
            <label class="cm-etu-toolbar__field" for="filterStatus">
                <span>Statut</span>
                <select id="filterStatus" class="cm-form-control">
                    <option value="">Tous les statuts</option>
                    <option value="en_attente">En attente</option>
                    <option value="en_cours">En cours</option>
                    <option value="valider">Validé</option>
                    <option value="rejeter">Rejeté</option>
                </select>
            </label>
            <label class="cm-etu-toolbar__field cm-etu-toolbar__field--grow" for="filterSearch">
                <span>Recherche</span>
                <input id="filterSearch" type="search" class="cm-form-control" placeholder="Rechercher un rapport...">
            </label>
        </div>

        <?php if (empty($rapports)): ?>
            <div class="cm-etu-empty">
                <i class="fas fa-hourglass-start" aria-hidden="true"></i>
                <p>Aucun rapport à suivre pour le moment.</p>
            </div>
        <?php else: ?>
            <div class="cm-etu-timeline-list" id="timelineReports">
                <?php foreach ($rapports as $rapport): ?>
                    <?php
                    $rapportId = (int) ($rapport['id_rapport'] ?? 0);
                    $nomRapport = (string) ($rapport['nom_rapport'] ?? 'Rapport');
                    $themeRapport = (string) ($rapport['theme_rapport'] ?? '');
                    $dateRapport = (string) ($rapport['date_rapport'] ?? '');
                    $statutRapport = strtolower((string) ($rapport['statut_rapport'] ?? 'en_attente'));

                    $statusLabel = 'En attente';
                    $statusType = 'light';
                    if ($statutRapport === 'en_cours') {
                        $statusLabel = 'En cours';
                        $statusType = 'info';
                    } elseif ($statutRapport === 'valider') {
                        $statusLabel = 'Validé';
                        $statusType = 'success';
                    } elseif ($statutRapport === 'rejeter') {
                        $statusLabel = 'Rejeté';
                        $statusType = 'danger';
                    }

                    $stmt = $pdo->prepare('SELECT date_depot FROM deposer WHERE num_etu = ? AND id_rapport = ? ORDER BY date_depot DESC LIMIT 1');
                    $stmt->execute([$_SESSION['num_etu'], $rapportId]);
                    $dateDepot = $stmt->fetchColumn();
                    $estDepose = !empty($dateDepot);

                    $evaluation = null;
                    $decisions = is_array($rapport['decisions'] ?? null) ? $rapport['decisions'] : [];
                    foreach ($decisions as $decision) {
                        $niveau = strtolower((string) ($decision['lib_approb'] ?? ''));
                        if ($niveau === 'niveau 2' || $niveau === 'niveau_2') {
                            $evaluation = $decision;
                            break;
                        }
                    }
                    if ($evaluation === null && !empty($decisions)) {
                        $evaluation = $decisions[count($decisions) - 1];
                    }

                    $evaluationDecision = strtolower((string) ($evaluation['decision'] ?? ''));
                    $evaluationDone = $evaluation !== null;
                    $evaluationDate = $evaluationDone && !empty($evaluation['date_approv'])
                        ? date('d/m/Y - H:i', strtotime((string) $evaluation['date_approv']))
                        : 'En attente';
                    $evaluationComment = $evaluationDone
                        ? (string) ($evaluation['commentaire_approv'] ?? '')
                        : '';

                    $finalDone = in_array($statutRapport, ['valider', 'rejeter'], true);
                    $finalDate = $finalDone && $evaluationDone && !empty($evaluation['date_approv'])
                        ? date('d/m/Y - H:i', strtotime((string) $evaluation['date_approv']))
                        : 'En attente';
                    $finalDesc = '';
                    if ($statutRapport === 'valider') {
                        $finalDesc = 'Rapport validé par la commission.';
                    } elseif ($statutRapport === 'rejeter') {
                        $finalDesc = 'Rapport rejeté par la commission.';
                    }

                    $steps = [
                        [
                            'state' => 'done',
                            'label' => 'Enregistrement du rapport',
                            'date' => $dateRapport !== '' ? date('d/m/Y - H:i', strtotime($dateRapport)) : 'Date indisponible',
                            'icon' => 'fa-check',
                        ],
                        [
                            'state' => $estDepose ? 'done' : 'pending',
                            'label' => 'Soumission du rapport',
                            'date' => $estDepose ? date('d/m/Y - H:i', strtotime((string) $dateDepot)) : 'En attente',
                            'desc' => $estDepose ? '' : 'Le dépôt n\'a pas encore été effectué.',
                            'icon' => $estDepose ? 'fa-check' : 'fa-clock',
                        ],
                        [
                            'state' => $evaluationDone ? 'current' : 'pending',
                            'label' => 'Évaluation',
                            'date' => $evaluationDate,
                            'desc' => $evaluationComment,
                            'icon' => $evaluationDone ? ($evaluationDecision === 'desapprouve' ? 'fa-xmark' : 'fa-clipboard-check') : 'fa-clock',
                        ],
                        [
                            'state' => $finalDone ? 'done' : 'pending',
                            'label' => 'Décision finale de la commission',
                            'date' => $finalDate,
                            'desc' => $finalDesc,
                            'icon' => $finalDone ? ($statutRapport === 'valider' ? 'fa-check' : 'fa-xmark') : 'fa-clock',
                        ],
                    ];

                    $searchTokens = strtolower($nomRapport . ' ' . $themeRapport);
                    ?>
                    <article
                        class="cm-etu-timeline-card"
                        data-status="<?= htmlspecialchars($statutRapport, ENT_QUOTES, 'UTF-8') ?>"
                        data-search="<?= htmlspecialchars($searchTokens, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="cm-etu-timeline-card__head">
                            <div>
                                <h3><?= htmlspecialchars($nomRapport, ENT_QUOTES, 'UTF-8') ?></h3>
                                <p><?= htmlspecialchars($themeRapport, ENT_QUOTES, 'UTF-8') ?></p>
                            </div>
                            <?php cm_component('ui/badge', ['type' => $statusType, 'text' => $statusLabel]); ?>
                        </div>
                        <?php cm_component('timeline/timeline', ['steps' => $steps]); ?>
                    </article>
                <?php endforeach; ?>
            </div>

            <div id="timelineEmptyState" class="cm-etu-empty" hidden>
                <i class="fas fa-filter" aria-hidden="true"></i>
                <p>Aucun rapport ne correspond à vos critères de recherche.</p>
            </div>
        <?php endif; ?>
    </section>
</div>

<script>
(function () {
    const list = document.getElementById('timelineReports');
    const emptyState = document.getElementById('timelineEmptyState');
    const statusFilter = document.getElementById('filterStatus');
    const searchFilter = document.getElementById('filterSearch');

    if (!list || !statusFilter || !searchFilter || !emptyState) {
        return;
    }

    const cards = Array.from(list.querySelectorAll('.cm-etu-timeline-card'));

    function applyFilters() {
        const status = String(statusFilter.value || '').trim();
        const search = String(searchFilter.value || '').toLowerCase().trim();

        let visibleCount = 0;

        cards.forEach(function (card) {
            const matchesStatus = status === '' || card.dataset.status === status;
            const searchText = String(card.dataset.search || '');
            const matchesSearch = search === '' || searchText.includes(search);
            const visible = matchesStatus && matchesSearch;
            card.hidden = !visible;
            if (visible) {
                visibleCount += 1;
            }
        });

        emptyState.hidden = visibleCount > 0;
    }

    statusFilter.addEventListener('change', applyFilters);
    searchFilter.addEventListener('input', applyFilters);
})();
</script>

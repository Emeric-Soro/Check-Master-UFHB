<div class="cm-prd3-screen cm-workflow-flat">
    <!-- Selection du rapport -->
    <div class="cm-card">
        <div class="cm-card__header cm-flex-between">
            <h3 class="cm-card__title">
                <i class="fas fa-diagram-project cm-mr-sm"></i>Workflow de validation
            </h3>
            <div class="cm-flex cm-flex-gap-sm">
                <form method="GET" class="cm-form-inline">
                    <input type="hidden" name="page" value="workflow_validation">
                    <div class="cm-form-group cm-field--number">
                        <input type="number" name="id_rapport" class="cm-form-control is-sm"
                            placeholder="N° rapport..."
                            min="1"
                            value="<?= (int) ($_GET['id_rapport'] ?? ($workflow['id_rapport'] ?? 0)) ?>"
                            style="width:120px;">
                    </div>
                    <button type="submit" class="cm-btn is-sm is-primary">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <?php if (empty($_GET['id_rapport'])): ?>
                <a href="?page=processus_validation" class="cm-btn is-sm is-primary-accent">
                    <i class="fas fa-list cm-mr-sm"></i>Vue liste
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($workflow): ?>
        <?php $etapes = $workflow['etapes'] ?? []; ?>
        <?php $progression = (int) ($workflow['progression'] ?? 0); ?>
        <?php $totalEtapes = (int) ($workflow['total_etapes'] ?? 5); ?>
        <?php $pct = $totalEtapes > 0 ? (int) round(($progression / $totalEtapes) * 100) : 0; ?>

        <!-- Barre de progression globale -->
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title">
                    Rapport #<?= (int) ($workflow['id_rapport'] ?? 0) ?>
                    <span class="cm-text-muted cm-text-sm cm-ml-sm">
                        (<?= $progression ?>/<?= $totalEtapes ?> etapes — <?= $pct ?>%)
                    </span>
                </h3>
            </div>
            <div class="cm-card__body">
                <!-- Barre progression -->
                <div class="cm-workflow-progress-bar cm-mb-md">
                    <div class="cm-workflow-progress-track">
                        <div class="cm-workflow-progress-fill" style="width: <?= $pct ?>%;"></div>
                    </div>
                    <div class="cm-workflow-progress-labels">
                        <?php foreach ([25, 50, 75, 100] as $p): ?>
                        <span class="cm-workflow-progress-pct <?= $pct >= $p ? 'is-active' : '' ?>"><?= $p ?>%</span>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Etapes visuelles -->
                <div class="cm-workflow-steps">
                    <?php foreach ($etapes as $i => $etape): ?>
                        <?php
                        $statut = $etape['statut'] ?? 'en_attente';
                        $isTermine = $statut === 'termine';
                        $isEnCours = $statut === 'en_cours';
                        $isWaiting = $statut === 'en_attente';
                        $stepClass = $isTermine ? 'is-done' : ($isEnCours ? 'is-active' : '');
                        ?>
                        <div class="cm-workflow-step <?= $stepClass ?>">
                            <div class="cm-workflow-step__connector <?= $isTermine ? 'is-done' : '' ?>"></div>
                            <div class="cm-workflow-step__circle <?= $isTermine ? 'is-done' : ($isEnCours ? 'is-active' : '') ?>">
                                <?php if ($isTermine): ?>
                                    <i class="fas fa-check"></i>
                                <?php else: ?>
                                    <i class="fas <?= htmlspecialchars($etape['icone'] ?? 'fa-circle', ENT_QUOTES, 'UTF-8') ?>"></i>
                                <?php endif; ?>
                            </div>
                            <div class="cm-workflow-step__content">
                                <h4 class="cm-workflow-step__title">
                                    <?= htmlspecialchars($etape['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                </h4>
                                <?php if ($isTermine || $isEnCours): ?>
                                    <div class="cm-workflow-step__meta">
                                        <?php if ($etape['date']): ?>
                                            <span class="cm-workflow-step__date">
                                                <i class="fas fa-calendar-day cm-mr-xs"></i>
                                                <?= htmlspecialchars(date('d/m/Y H:i', strtotime((string) $etape['date'])), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                        <?php if ($etape['acteur']): ?>
                                            <span class="cm-workflow-step__actor">
                                                <i class="fas fa-user cm-mr-xs"></i>
                                                <?= htmlspecialchars($etape['acteur'], ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <?php if ($etape['detail']): ?>
                                        <p class="cm-workflow-step__detail"><?= htmlspecialchars($etape['detail'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                                <?php if ($isWaiting): ?>
                                    <p class="cm-workflow-step__detail cm-text-muted">En attente</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Lien vers processus validation -->
        <div class="cm-mt-md cm-text-right">
            <a href="?page=processus_validation&detail=<?= urlencode((string) ($workflow['id_rapport'] ?? '')) ?>"
               class="cm-btn is-primary-accent">
                <i class="fas fa-external-link-alt cm-mr-sm"></i>Voir details dans processus validation
            </a>
        </div>

    <?php else: ?>
        <!-- Liste des rapports recents si pas de workflow selectionne -->
        <?php if (isset($rapports) && !empty($rapports)): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-file-lines cm-mr-sm"></i>Rapports recents</h3>
            </div>
            <div class="cm-card__body">
                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">N°</th>
                                <th class="cm-data-table__th">Etudiant</th>
                                <th class="cm-data-table__th">Theme</th>
                                <th class="cm-data-table__th">Statut</th>
                                <th class="cm-data-table__th">Progression</th>
                                <th class="cm-data-table__th is-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapports as $r): ?>
                            <?php
                            $idRap = (int) ($r['id_rapport'] ?? 0);
                            $vote = $r['statut_vote'] ?? [];
                            $statut = strtolower((string) ($vote['statut'] ?? 'en_cours'));
                            $badgeType = 'info';
                            $statutLabel = 'En cours';
                            if ($statut === 'valide') { $badgeType = 'success'; $statutLabel = 'Valide'; }
                            elseif ($statut === 'rejete') { $badgeType = 'danger'; $statutLabel = 'Rejete'; }
                            ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="?page=workflow_validation&id_rapport=<?= $idRap ?>">
                                <td class="cm-data-table__td">#<?= $idRap ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(trim(($r['nom_etu'] ?? '') . ' ' . ($r['prenom_etu'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(mb_substr((string) ($r['theme_rapport'] ?? ''), 0, 50), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td">
                                    <?php cm_component('ui/badge', ['text' => $statutLabel, 'type' => $badgeType]); ?>
                                </td>
                                <td class="cm-data-table__td">
                                    <?php
                                    $totalVotes = (int) ($vote['total_votes'] ?? 0);
                                    echo $totalVotes . '/4 votes';
                                    ?>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <a href="?page=workflow_validation&id_rapport=<?= $idRap ?>"
                                       class="cm-btn-action is-view"
                                       title="Voir workflow">
                                        <i class="fas fa-diagram-project"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php else: ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__body cm-text-center">
                <p class="cm-text-muted">Saisissez un numero de rapport dans le champ ci-dessus pour visualiser son workflow de validation.</p>
            </div>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<style>
/* Page flat overrides */
.cm-workflow-flat {
    background: transparent;
}
.cm-workflow-flat .cm-card,
.cm-workflow-flat .cm-card__header,
.cm-workflow-flat .cm-card__body {
    background: transparent;
    border: 0;
    box-shadow: none;
}
.cm-workflow-flat .cm-card {
    padding: 0;
}
.cm-workflow-flat .cm-card__header,
.cm-workflow-flat .cm-card__body {
    padding-left: 0;
    padding-right: 0;
}
.cm-workflow-flat .cm-card__header {
    padding-top: 0;
    padding-bottom: 0;
}
.cm-workflow-flat .cm-card__body {
    padding-bottom: 0;
}
.cm-workflow-flat .cm-card + .cm-card {
    margin-top: 1rem;
}
.cm-workflow-flat .cm-form-inline {
    gap: 0.65rem;
}
.cm-workflow-flat .cm-form-group.cm-field--number {
    margin-bottom: 0;
}

/* Workflow progress bar */
.cm-workflow-progress-bar {
    padding: 0.5rem 0;
}
.cm-workflow-progress-track {
    height: 10px;
    background: transparent;
    border-radius: 0;
    overflow: visible;
    margin-bottom: 0.25rem;
}
.cm-workflow-progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #3b82f6, #10b981);
    border-radius: 999px;
    transition: width 0.5s ease;
}
.cm-workflow-progress-labels {
    display: flex;
    justify-content: space-between;
    padding: 0 0.25rem;
}
.cm-workflow-progress-pct {
    font-size: 0.72rem;
    color: #9ca3af;
}
.cm-workflow-progress-pct.is-active {
    color: #10b981;
    font-weight: 600;
}

/* Workflow steps vertical */
.cm-workflow-steps {
    position: relative;
    padding-left: 2.5rem;
}
.cm-workflow-step {
    position: relative;
    padding-bottom: 1.5rem;
}
.cm-workflow-step:last-child {
    padding-bottom: 0;
}
.cm-workflow-step__connector {
    position: absolute;
    left: 1.05rem;
    top: 2rem;
    bottom: 0;
    width: 2px;
    background: #d1d5db;
}
.cm-workflow-step__connector.is-done {
    background: #10b981;
}
.cm-workflow-step:last-child .cm-workflow-step__connector {
    display: none;
}
.cm-workflow-step__circle {
    position: absolute;
    left: -2.2rem;
    top: 0.25rem;
    width: 2.2rem;
    height: 2.2rem;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    background: rgba(148, 163, 184, 0.16);
    border: 0;
    color: #5b7286;
    z-index: 1;
    transition: all 0.3s ease;
}
.cm-workflow-step__circle.is-done {
    background: #10b981;
    color: #fff;
}
.cm-workflow-step__circle.is-active {
    background: #3b82f6;
    color: #fff;
    animation: cm-pulse 2s infinite;
}
@keyframes cm-pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.06); }
}
.cm-workflow-step__content {
    padding: 0;
    background: transparent;
    border-radius: 0;
    border: 0;
    transition: all 0.3s ease;
}
.cm-workflow-step.is-active .cm-workflow-step__content {
    background: transparent;
}
.cm-workflow-step.is-done .cm-workflow-step__content {
    background: transparent;
}
.cm-workflow-step__title {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0 0 0.2rem;
    color: #111827;
}
.cm-workflow-step__meta {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
    font-size: 0.78rem;
    color: #6b7280;
    margin-bottom: 0.15rem;
}
.cm-workflow-step__date i,
.cm-workflow-step__actor i {
    margin-right: 0.15rem;
}
.cm-workflow-step__detail {
    font-size: 0.8rem;
    color: #4b5563;
    margin: 0;
}
.cm-workflow-step.is-active .cm-workflow-step__detail {
    color: #1e40af;
}
</style>

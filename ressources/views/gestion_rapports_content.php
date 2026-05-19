<?php
$infosDepot = is_array($GLOBALS['infosDepot'] ?? null) ? $GLOBALS['infosDepot'] : [];
$rapportsRecents = is_array($GLOBALS['rapportsRecents'] ?? null) ? $GLOBALS['rapportsRecents'] : [];
$dernierRapportDepose = $GLOBALS['dernierRapportDepose'] ?? null;
$statistiquesRapports = $GLOBALS['statistiquesRapports'] ?? null;
$totalRapports = isset($statistiquesRapports->total_rapports)
        ? (int) $statistiquesRapports->total_rapports
        : count($rapportsRecents);

$formatDate = static function ($value, $withTime = false) {
    $raw = trim((string) $value);
    if ($raw === '' || $raw === '0000-00-00' || $raw === '0000-00-00 00:00:00') {
        return '—';
    }

    $timestamp = strtotime($raw);
    if ($timestamp === false) {
        return '—';
    }

    return $withTime ? date('d/m/Y H:i', $timestamp) : date('d/m/Y', $timestamp);
};

$rapportEnCours = $dernierRapportDepose;
if (!$rapportEnCours) {
    foreach ($rapportsRecents as $r) {
        $statut = strtolower((string) ($r->statut_rapport ?? ''));
        $id = (int) ($r->id_rapport ?? 0);
        $info = $infosDepot[$id] ?? [];
        if ($statut === 'en_cours' || !empty($info['dejaDepose'])) {
            $rapportEnCours = $r;
            break;
        }
    }
}

$peutCreerNouveau = true;
$dernierStatut = strtolower((string) ($rapportEnCours->statut_rapport ?? ''));
if ($rapportEnCours && $dernierStatut !== 'rejeter') {
    $peutCreerNouveau = false;
}

$draftCount = 0;
$submittedCount = 0;
$validatedCount = 0;
$rejectedCount = 0;

foreach ($rapportsRecents as $rapportItem) {
    $rapportIdStat = (int) ($rapportItem->id_rapport ?? 0);
    $statutRapportStat = strtolower((string) ($rapportItem->statut_rapport ?? ''));
    $dejaDeposeStat = (bool) (($infosDepot[$rapportIdStat]['dejaDepose'] ?? false));

    if ($statutRapportStat === 'valider') {
        $validatedCount++;
    } elseif ($statutRapportStat === 'rejeter') {
        $rejectedCount++;
    } elseif ($statutRapportStat === 'en_cours' || $dejaDeposeStat) {
        $submittedCount++;
    } else {
        $draftCount++;
    }
}
?>

<style>
    .cm-reports-page {
        width: min(1160px, 100%);
        margin: 0 auto;
        display: flex;
        flex-direction: column;
        gap: 1rem;
        padding: 0.4rem 0 1.5rem;
    }

    .cm-reports-topbar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.85rem;
        flex-wrap: wrap;
    }

    .cm-reports-title {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 1.05rem;
        font-weight: 700;
    }

    .cm-reports-subtitle {
        margin: 0.2rem 0 0;
        color: #5e7d95;
        font-size: 0.82rem;
    }

    .cm-reports-cta {
        display: flex;
        align-items: center;
        gap: 0.6rem;
        flex-wrap: wrap;
    }

    .cm-reports-section {
        border: 1px solid rgba(26, 82, 118, 0.12);
        border-radius: 18px;
        background: rgba(228, 240, 252, 0.3);
        padding: 1rem;
        backdrop-filter: blur(2px);
    }

    .cm-reports-section__head {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.8rem;
        flex-wrap: wrap;
        margin-bottom: 0.95rem;
    }

    .cm-reports-section__title {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 0.95rem;
        font-weight: 700;
    }

    .cm-reports-stats {
        display: grid;
        grid-template-columns: repeat(4, minmax(0, 1fr));
        gap: 0.8rem;
    }

    .cm-reports-stat {
        padding: 0.95rem 1rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.42);
        border: 1px solid rgba(26, 82, 118, 0.08);
    }

    .cm-reports-stat__label {
        margin: 0 0 0.3rem;
        color: #5d7a90;
        font-size: 0.74rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cm-reports-stat__value {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 1.55rem;
        font-weight: 700;
        line-height: 1;
        font-variant-numeric: tabular-nums;
    }

    .cm-reports-stat__meta {
        margin: 0.35rem 0 0;
        color: #6b879b;
        font-size: 0.78rem;
    }

    .cm-reports-focus {
        display: grid;
        grid-template-columns: minmax(0, 1.2fr) minmax(260px, 0.8fr);
        gap: 0.9rem;
        align-items: stretch;
    }

    .cm-reports-panel {
        padding: 1rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.42);
        border: 1px solid rgba(26, 82, 118, 0.08);
    }

    .cm-reports-panel__title {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 0.93rem;
        font-weight: 700;
    }

    .cm-reports-panel__sub {
        margin: 0.28rem 0 0;
        color: #658197;
        font-size: 0.79rem;
    }

    .cm-reports-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-height: 2rem;
        padding: 0.35rem 0.7rem;
        border-radius: 999px;
        border: 1px solid rgba(26, 82, 118, 0.1);
        background: rgba(255, 255, 255, 0.65);
        color: var(--cm-primary-dark);
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cm-reports-badge.is-success {
        color: #166534;
        background: rgba(240, 253, 244, 0.92);
        border-color: rgba(34, 197, 94, 0.14);
    }

    .cm-reports-badge.is-warning {
        color: #9a6700;
        background: rgba(255, 251, 235, 0.95);
        border-color: rgba(245, 158, 11, 0.14);
    }

    .cm-reports-badge.is-danger {
        color: #b42318;
        background: rgba(254, 242, 242, 0.95);
        border-color: rgba(239, 68, 68, 0.12);
    }

    .cm-reports-badge.is-info {
        color: #0f5f99;
        background: rgba(239, 248, 255, 0.95);
        border-color: rgba(47, 136, 200, 0.16);
    }

    .cm-reports-meta {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.75rem;
        margin-top: 0.95rem;
    }

    .cm-reports-meta__item {
        min-width: 0;
        padding: 0.8rem 0.9rem;
        border-radius: 14px;
        background: rgba(233, 243, 252, 0.55);
        border: 1px solid rgba(26, 82, 118, 0.06);
    }

    .cm-reports-meta__label {
        margin: 0 0 0.2rem;
        color: #5d7a90;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cm-reports-meta__value {
        margin: 0;
        color: var(--cm-text-main);
        font-size: 0.9rem;
        font-weight: 600;
        line-height: 1.38;
        word-break: break-word;
    }

    .cm-reports-steps {
        display: grid;
        gap: 0.7rem;
        align-content: start;
        height: 100%;
    }

    .cm-reports-step {
        display: grid;
        grid-template-columns: 2rem minmax(0, 1fr);
        gap: 0.65rem;
        align-items: start;
    }

    .cm-reports-step__dot {
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 700;
        background: rgba(208, 225, 240, 0.88);
        color: var(--cm-primary-dark);
        border: 1px solid rgba(26, 82, 118, 0.08);
    }

    .cm-reports-step.is-done .cm-reports-step__dot {
        background: rgba(214, 243, 223, 0.96);
        color: #166534;
        border-color: rgba(22, 163, 74, 0.12);
    }

    .cm-reports-step.is-current .cm-reports-step__dot {
        background: rgba(219, 236, 251, 0.96);
        color: #0f5f99;
        border-color: rgba(47, 136, 200, 0.16);
    }

    .cm-reports-step__title {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 0.83rem;
        font-weight: 700;
    }

    .cm-reports-step__meta {
        margin: 0.12rem 0 0;
        color: #658197;
        font-size: 0.77rem;
        line-height: 1.35;
    }

    .cm-reports-step__date {
        margin: 0.12rem 0 0;
        color: #48667f;
        font-size: 0.76rem;
        font-weight: 600;
    }

    .cm-reports-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.9rem;
    }

    .cm-report-card {
        display: flex;
        flex-direction: column;
        gap: 0.9rem;
        min-height: 100%;
        padding: 1rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.42);
        border: 1px solid rgba(26, 82, 118, 0.08);
        transition: border-color var(--cm-transition-fast), box-shadow var(--cm-transition-fast), transform var(--cm-transition-fast);
    }

    .cm-report-card:hover {
        transform: translateY(-1px);
        border-color: rgba(47, 136, 200, 0.18);
        box-shadow: 0 12px 24px rgba(17, 77, 120, 0.08);
    }

    .cm-report-card__head {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.75rem;
    }

    .cm-report-card__title {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 0.93rem;
        font-weight: 700;
        line-height: 1.35;
    }

    .cm-report-card__theme {
        padding: 0.85rem 0.9rem;
        border-radius: 14px;
        background: rgba(233, 243, 252, 0.55);
        border: 1px solid rgba(26, 82, 118, 0.06);
    }

    .cm-report-card__theme-label {
        margin: 0 0 0.2rem;
        color: #5d7a90;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }

    .cm-report-card__theme-value {
        margin: 0;
        color: var(--cm-text-main);
        font-size: 0.86rem;
        line-height: 1.42;
    }

    .cm-report-card__meta {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
        color: #66829a;
        font-size: 0.78rem;
    }

    .cm-report-card__meta span {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
    }

    .cm-report-card__actions {
        display: flex;
        align-items: center;
        justify-content: flex-end;
        gap: 0.55rem;
        flex-wrap: wrap;
        margin-top: auto;
        padding-top: 0.8rem;
        border-top: 1px solid rgba(26, 82, 118, 0.08);
    }

    .cm-btn {
        min-height: 42px !important;
        padding: 0.68rem 1rem !important;
        border-radius: 12px !important;
        font-weight: 700 !important;
        font-size: 0.88rem !important;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.45rem;
        border: 1px solid transparent;
        text-decoration: none;
        transition: background-color var(--cm-transition-fast), border-color var(--cm-transition-fast), box-shadow var(--cm-transition-fast);
    }

    .cm-btn:focus-visible {
        outline: none;
        box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.16);
    }

    .cm-btn.is-primary {
        background: #2f88c8 !important;
        color: #fff !important;
        border-color: #2f88c8 !important;
    }

    .cm-btn.is-primary:hover {
        background: #277bb8 !important;
    }

    .cm-btn.is-outline {
        background: rgba(255, 255, 255, 0.82) !important;
        color: var(--cm-primary-dark) !important;
        border-color: rgba(26, 82, 118, 0.14) !important;
    }

    .cm-btn.is-outline:hover {
        background: rgba(255, 255, 255, 0.96) !important;
        border-color: rgba(26, 82, 118, 0.22) !important;
    }

    .cm-btn.is-success {
        background: #1d9b67 !important;
        color: #fff !important;
        border-color: #1d9b67 !important;
    }

    .cm-btn.is-success:hover {
        background: #188759 !important;
    }

    .cm-report-card__form {
        margin: 0;
    }

    .cm-reports-empty {
        display: grid;
        place-items: center;
        text-align: center;
        min-height: 15rem;
        padding: 1.5rem;
        border-radius: 16px;
        background: rgba(255, 255, 255, 0.34);
        border: 1px dashed rgba(26, 82, 118, 0.14);
    }

    .cm-reports-empty__icon {
        width: 4rem;
        height: 4rem;
        border-radius: 1.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: rgba(220, 233, 245, 0.8);
        color: var(--cm-primary);
        font-size: 1.4rem;
        margin-bottom: 0.85rem;
    }

    .cm-reports-empty__title {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 1rem;
        font-weight: 700;
    }

    .cm-reports-empty__text {
        margin: 0.35rem 0 1rem;
        color: #69859b;
        font-size: 0.84rem;
    }

    @media (max-width: 980px) {
        .cm-reports-stats,
        .cm-reports-grid,
        .cm-reports-focus {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 640px) {
        .cm-reports-stats,
        .cm-reports-meta {
            grid-template-columns: 1fr;
        }

        .cm-reports-cta,
        .cm-report-card__actions {
            width: 100%;
        }

        .cm-reports-cta .cm-btn,
        .cm-report-card__actions .cm-btn,
        .cm-report-card__actions form,
        .cm-report-card__actions form .cm-btn {
            width: 100%;
        }
    }
</style>

<div class="cm-reports-page">
    <section class="cm-reports-topbar">
        <div class="cm-reports-cta">
            <?php if ($peutCreerNouveau && canCreate()): ?>
                <a href="?page=gestion_rapports&action=creer_rapport" class="cm-btn is-primary">
                    <i class="fas fa-plus"></i>
                    Nouveau rapport
                </a>
            <?php endif; ?>
        </div>
    </section>


    <?php if ($rapportEnCours): ?>
        <?php
        $rapportId = (int) ($rapportEnCours->id_rapport ?? 0);
        $infoDepot = $infosDepot[$rapportId] ?? [];
        $isDepose = !empty($infoDepot['dejaDepose']) || ($dernierRapportDepose !== null && $rapportId === (int) ($dernierRapportDepose->id_rapport ?? 0));

        $statutBadge = 'info';
        $statutText = 'En cours';
        if ($isDepose) {
            $statutBadge = 'warning';
            $statutText = 'Déposé';
        }
        if ($dernierStatut === 'rejeter') {
            $statutBadge = 'danger';
            $statutText = 'Rejeté';
        }
        if ($dernierStatut === 'valider') {
            $statutBadge = 'success';
            $statutText = 'Validé';
        }
        $decisionDisponible = in_array($dernierStatut, ['valider', 'rejeter'], true);
        $decisionLabel = $dernierStatut === 'valider'
            ? 'Décision favorable'
            : ($dernierStatut === 'rejeter' ? 'Décision défavorable' : 'À venir');
        $decisionDate = $decisionDisponible
            ? $formatDate((string) ($rapportEnCours->date_modification ?? $rapportEnCours->date_depot ?? ''), true)
            : 'En attente';

        $followSteps = [
                [
                        'title' => 'Rédaction',
                        'meta' => 'Rapport créé',
                        'date' => $formatDate((string) ($rapportEnCours->date_rapport ?? ''), true),
                        'class' => 'is-done',
                        'dot' => '✓',
                ],
                [
                        'title' => 'Dépôt',
                        'meta' => $isDepose ? 'Soumis à la commission' : 'En attente de dépôt',
                        'date' => $isDepose ? $formatDate((string) ($rapportEnCours->date_depot ?? ''), true) : '—',
                        'class' => $isDepose ? 'is-done' : '',
                        'dot' => $isDepose ? '✓' : '•',
                ],
                [
                        'title' => 'Commission',
                        'meta' => $decisionDisponible ? 'Décision rendue' : 'En attente de décision',
                        'date' => $decisionDate,
                        'class' => $decisionDisponible ? 'is-done' : ($isDepose ? 'is-current' : ''),
                        'dot' => $decisionDisponible ? '✓' : '•',
                ],
                [
                        'title' => 'Résultat',
                        'meta' => $decisionLabel,
                        'date' => $decisionDisponible ? 'Décision disponible' : '—',
                        'class' => $decisionDisponible ? 'is-done' : '',
                        'dot' => $decisionDisponible ? '✓' : '•',
                ],
        ];
        ?>
        <section class="cm-reports-section">
            <div class="cm-reports-section__head">
                <h2 class="cm-reports-section__title">Rapport actif</h2>
                <span class="cm-reports-badge is-<?= htmlspecialchars($statutBadge, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($statutText, ENT_QUOTES, 'UTF-8') ?></span>
            </div>

            <div class="cm-reports-focus">
                <article class="cm-reports-panel">
                    <h3 class="cm-reports-panel__title"><?= htmlspecialchars((string) ($rapportEnCours->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?></h3>
                    <p class="cm-reports-panel__sub"><?= htmlspecialchars((string) ($rapportEnCours->theme_rapport ?? 'Thème non spécifié'), ENT_QUOTES, 'UTF-8') ?></p>

                    <div class="cm-reports-meta">
                        <div class="cm-reports-meta__item">
                            <p class="cm-reports-meta__label">Création</p>
                            <p class="cm-reports-meta__value"><?= htmlspecialchars($formatDate((string) ($rapportEnCours->date_rapport ?? ''), true), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="cm-reports-meta__item">
                            <p class="cm-reports-meta__label">Dépôt</p>
                            <p class="cm-reports-meta__value"><?= htmlspecialchars($isDepose ? $formatDate((string) ($rapportEnCours->date_depot ?? ''), true) : '—', ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="cm-reports-meta__item">
                            <p class="cm-reports-meta__label">Statut</p>
                            <p class="cm-reports-meta__value"><?= htmlspecialchars($statutText, ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                        <div class="cm-reports-meta__item">
                            <p class="cm-reports-meta__label">Modification</p>
                            <p class="cm-reports-meta__value"><?= htmlspecialchars($formatDate((string) ($rapportEnCours->date_modification ?? ''), true), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>
                    </div>
                </article>

                <aside class="cm-reports-panel">
                    <div class="cm-reports-steps">
                        <?php foreach ($followSteps as $step): ?>
                            <article class="cm-reports-step <?= $step['class'] ?>">
                                <span class="cm-reports-step__dot"><?= $step['dot'] ?></span>
                                <div>
                                    <p class="cm-reports-step__title"><?= htmlspecialchars($step['title'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="cm-reports-step__meta"><?= htmlspecialchars($step['meta'], ENT_QUOTES, 'UTF-8') ?></p>
                                    <p class="cm-reports-step__date"><?= htmlspecialchars($step['date'], ENT_QUOTES, 'UTF-8') ?></p>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </aside>
            </div>

            <div class="cm-report-card__actions">
                <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                    <i class="fas fa-eye"></i>
                    Voir le rapport
                </a>
            </div>
        </section>
    <?php endif; ?>

    <section class="cm-reports-section">
        <div class="cm-reports-section__head">
            <h2 class="cm-reports-section__title">Tous les rapports</h2>
            <span class="cm-reports-badge"><?= $totalRapports ?> élément(s)</span>
        </div>

        <?php if (!empty($rapportsRecents)): ?>
            <div class="cm-reports-grid">
                <?php foreach ($rapportsRecents as $rapport): ?>
                    <?php
                    $rapportId = (int) ($rapport->id_rapport ?? 0);
                    $infoDepot = $infosDepot[$rapportId] ?? ['peutDeposer' => true, 'messageDepot' => '', 'dejaDepose' => false];
                    $peutDeposer = (bool) ($infoDepot['peutDeposer'] ?? false);
                    $dejaDepose = (bool) ($infoDepot['dejaDepose'] ?? false);

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
                    <article class="cm-report-card">
                        <div class="cm-report-card__head">
                            <h3 class="cm-report-card__title"><?= htmlspecialchars((string) ($rapport->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?></h3>
                            <span class="cm-reports-badge is-<?= htmlspecialchars($badgeType, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($badgeText, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="cm-report-card__theme">
                            <p class="cm-report-card__theme-label">Thème</p>
                            <p class="cm-report-card__theme-value"><?= htmlspecialchars((string) ($rapport->theme_rapport ?? 'Non spécifié'), ENT_QUOTES, 'UTF-8') ?></p>
                        </div>

                        <div class="cm-report-card__meta">
                            <span><i class="fas fa-calendar-alt"></i><?= htmlspecialchars($formatDate((string) ($rapport->date_rapport ?? '')), ENT_QUOTES, 'UTF-8') ?></span>
                            <span><i class="fas fa-file-alt"></i><?= htmlspecialchars($dejaDepose ? 'Déjà déposé' : 'Modifiable', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>

                        <div class="cm-report-card__actions">
                            <?php if ($peutDeposer): ?>
                                <form method="POST" action="?page=gestion_rapports" class="cm-report-card__form">
                                    <input type="hidden" name="action" value="deposer_rapport">
                                    <input type="hidden" name="id_rapport" value="<?= $rapportId ?>">
                                    <button type="submit" class="cm-btn is-success" title="Soumettre ce rapport">
                                        <i class="fas fa-paper-plane"></i>
                                        Déposer
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($statutRapport !== 'en_cours' && $statutRapport !== 'valider'): ?>
                                <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                                    <i class="fas fa-edit"></i>
                                    Modifier
                                </a>
                            <?php else: ?>
                                <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                                    <i class="fas fa-eye"></i>
                                    Voir
                                </a>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cm-reports-empty">
                <div>
                    <div class="cm-reports-empty__icon">
                        <i class="fas fa-file-circle-plus"></i>
                    </div>
                    <h3 class="cm-reports-empty__title">Aucun rapport</h3>
                    <p class="cm-reports-empty__text">Créez votre premier rapport de stage.</p>
                    <?php if (canCreate()): ?>
                        <a class="cm-btn is-primary" href="?page=gestion_rapports&action=creer_rapport">
                            <i class="fas fa-plus"></i>
                            Créer un rapport
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </section>
</div>

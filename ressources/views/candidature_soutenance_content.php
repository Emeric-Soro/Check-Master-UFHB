<?php
$stage_info = is_array($GLOBALS['stage_info'] ?? null) ? $GLOBALS['stage_info'] : [];
$entreprises = is_array($GLOBALS['entreprises'] ?? null) ? $GLOBALS['entreprises'] : [];
$maitresDeStageData = is_array($GLOBALS['maitres_de_stage'] ?? null) ? $GLOBALS['maitres_de_stage'] : [];
$successMessage = (string) ($_SESSION['success'] ?? '');
$errorMessage = (string) ($_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);

$dossier = is_array($GLOBALS['dossier_soutenance'] ?? null) ? $GLOBALS['dossier_soutenance'] : [];
$candidature = is_array($dossier['candidature'] ?? null) ? $dossier['candidature'] : null;
$rapport = is_array($dossier['rapport'] ?? null) ? $dossier['rapport'] : null;
$commission = is_array($dossier['commission'] ?? null) ? $dossier['commission'] : [];
$compteRendu = is_array($dossier['compte_rendu'] ?? null) ? $dossier['compte_rendu'] : null;
$soutenance = is_array($dossier['soutenance'] ?? null) ? $dossier['soutenance'] : null;
$pv = is_array($dossier['pv'] ?? null) ? $dossier['pv'] : ['disponible' => false, 'date' => null];
$steps = is_array($dossier['steps'] ?? null) ? $dossier['steps'] : [];
$trackingMode = !empty($dossier['tracking_mode']);

$entrepriseValue = (string) ($stage_info['nom_entreprise'] ?? '');
$dateDebutValue = (string) ($stage_info['date_debut_stage'] ?? '');
$dateFinValue = (string) ($stage_info['date_fin_stage'] ?? '');
$sujetValue = (string) ($stage_info['sujet_stage'] ?? '');
$encadrantNom = (string) ($stage_info['encadrant_nom'] ?? '');
$encadrantPrenom = (string) ($stage_info['encadrant_prenom'] ?? '');
$encadrantValue = trim($encadrantNom . ' ' . $encadrantPrenom);
if ($encadrantValue === '') {
    $encadrantValue = (string) ($stage_info['encadrant_entreprise'] ?? '');
}
$emailEncadrantValue = (string) ($stage_info['encadrant_email'] ?? '');
$telephoneEncadrantValue = (string) ($stage_info['encadrant_telephone'] ?? '');

$canEditStage = function_exists('canEdit') ? canEdit() : true;
$canCreateRapport = function_exists('canCreate') ? (canCreate('gestion_rapports') || canCreate()) : true;
$rapportUrl = $rapport && !empty($rapport['id_rapport'])
    ? '?page=gestion_rapports&action=creer_rapport&edit=' . urlencode((string) $rapport['id_rapport'])
    : '?page=gestion_rapports&action=creer_rapport';
$pvUrl = !empty($_SESSION['num_etu'])
    ? '?page=evaluation_soutenance&action=imprimer_pv&num_etu=' . urlencode((string) $_SESSION['num_etu'])
    : '#';

$formatDate = static function (?string $value, bool $withTime = false): string {
    if ($value === null || trim($value) === '') {
        return '—';
    }
    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '—';
    }
    return date($withTime ? 'd/m/Y H:i' : 'd/m/Y', $timestamp);
};

$statusBadge = static function (?string $status): array {
    $normalized = mb_strtolower(trim((string) $status));
    return match ($normalized) {
        'validée', 'validee', 'validé', 'valide', 'disponible', 'programmée', 'programmee' => ['success', (string) $status],
        'rejetée', 'rejetee', 'rejeté', 'rejete', 'à corriger' => ['danger', (string) $status],
        'en évaluation', 'en evaluation', 'en cours' => ['warning', (string) $status],
        default => ['info', (string) ($status !== null && $status !== '' ? $status : 'En attente')],
    };
};

$buildStateClass = static function (string $status): string {
    return match ($status) {
        'done' => 'is-done',
        'current' => 'is-current',
        default => 'is-pending',
    };
};
?>

<style>
    .cm-cand-page {
        display: grid;
        gap: 18px;
        padding-top: 8px;
    }

    .cm-cand-box {
        background: rgba(255, 255, 255, 0.12);
        border: 1px solid rgba(43, 92, 132, 0.14);
        border-radius: 18px;
        padding: 18px 20px;
    }

    .cm-cand-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 14px;
    }

    .cm-cand-head__title {
        margin: 0;
        color: #1d4d77;
        font-size: 1.1rem;
        font-weight: 700;
    }

    .cm-cand-topline {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
        margin-top: 14px;
    }

    .cm-cand-mini,
    .cm-cand-detail,
    .cm-cand-step,
    .cm-cand-form,
    .cm-cand-actions-panel {
        background: rgba(255, 255, 255, 0.18);
        border: 1px solid rgba(43, 92, 132, 0.12);
        border-radius: 16px;
    }

    .cm-cand-mini,
    .cm-cand-detail {
        padding: 14px 16px;
    }

    .cm-cand-label {
        display: block;
        margin-bottom: 7px;
        color: #1d4d77;
        font-size: 0.82rem;
        font-weight: 700;
    }

    .cm-cand-key {
        color: #607d98;
        font-size: 0.72rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }

    .cm-cand-value {
        margin-top: 6px;
        color: #173754;
        font-size: 0.94rem;
        font-weight: 600;
        line-height: 1.4;
    }

    .cm-cand-form {
        padding: 20px;
    }

    .cm-cand-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 18px 24px;
    }

    .cm-cand-full {
        grid-column: 1 / -1;
    }

    .cm-cand-required {
        color: #dc2626;
    }

    .cm-cand-input {
        width: 100%;
        padding: 12px 14px;
        border-radius: 12px;
        border: 1px solid rgba(43, 92, 132, 0.18);
        background: rgba(255, 255, 255, 0.88);
        color: #173754;
        font-size: 0.95rem;
        outline: none;
        transition: border-color 0.18s ease, box-shadow 0.18s ease;
    }

    .cm-cand-input:focus {
        border-color: #3c84c5;
        box-shadow: 0 0 0 3px rgba(60, 132, 197, 0.15);
    }

    .cm-cand-help {
        margin-top: 6px;
        color: #607d98;
        font-size: 0.78rem;
    }

    .cm-cand-buttons {
        display: flex;
        justify-content: space-between;
        gap: 10px;
        flex-wrap: wrap;
        margin-top: 18px;
        padding-top: 16px;
        border-top: 1px solid rgba(43, 92, 132, 0.1);
    }

    .cm-cand-btns-right,
    .cm-cand-links {
        display: flex;
        gap: 10px;
        flex-wrap: wrap;
    }

    .cm-cand-modal-overlay {
        position: fixed;
        inset: 0;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 24px;
        background: rgba(15, 23, 42, 0.48);
        z-index: var(--cm-z-modal-overlay, 300);
    }

    .cm-cand-modal-overlay.is-open {
        display: flex;
    }

    .cm-cand-modal {
        width: min(100%, 440px);
        background: #fff;
        border-radius: 18px;
        box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        overflow: hidden;
    }

    .cm-cand-modal__header {
        padding: 20px 24px 8px;
    }

    .cm-cand-modal__title {
        margin: 0;
        color: #0f172a;
        font-size: 1.125rem;
        font-weight: 700;
    }

    .cm-cand-modal__body {
        padding: 0 24px 16px;
        color: #334155;
        line-height: 1.6;
    }

    .cm-cand-modal__footer {
        display: flex;
        justify-content: flex-end;
        padding: 0 24px 24px;
    }

    .cm-cand-sr-only {
        position: absolute;
        width: 1px;
        height: 1px;
        padding: 0;
        margin: -1px;
        overflow: hidden;
        clip: rect(0, 0, 0, 0);
        white-space: nowrap;
        border: 0;
    }

    .cm-cand-timeline {
        position: relative;
        display: grid;
        grid-template-columns: repeat(6, minmax(0, 1fr));
        gap: 14px;
    }

    .cm-cand-timeline::before {
        content: "";
        position: absolute;
        top: 27px;
        left: 24px;
        right: 24px;
        height: 3px;
        background: rgba(60, 132, 197, 0.15);
    }

    .cm-cand-step {
        position: relative;
        min-height: 148px;
        padding: 20px 14px 16px;
    }

    .cm-cand-step::before {
        content: "";
        position: absolute;
        top: 18px;
        left: 18px;
        width: 18px;
        height: 18px;
        border-radius: 999px;
        border: 4px solid rgba(60, 132, 197, 0.22);
        background: rgba(255, 255, 255, 0.95);
        z-index: 1;
    }

    .cm-cand-step.is-done::before {
        background: #16a34a;
        border-color: #16a34a;
        box-shadow: 0 0 0 4px rgba(22, 163, 74, 0.14);
    }

    .cm-cand-step.is-current::before {
        background: #3c84c5;
        border-color: #3c84c5;
        box-shadow: 0 0 0 4px rgba(60, 132, 197, 0.14);
    }

    .cm-cand-step__title {
        margin: 28px 0 8px;
        color: #1d4d77;
        font-size: 0.98rem;
        font-weight: 700;
    }

    .cm-cand-step__state,
    .cm-cand-step__date {
        color: #607d98;
        font-size: 0.88rem;
        line-height: 1.35;
    }

    .cm-cand-details {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 12px;
    }

    .cm-cand-actions-panel {
        padding: 16px;
    }

    .cm-etu-autocomplete {
        position: relative;
    }

    .cm-etu-autocomplete__list {
        position: absolute;
        top: calc(100% + 6px);
        left: 0;
        right: 0;
        display: none;
        max-height: 260px;
        overflow: auto;
        z-index: 12;
        background: #fff;
        border: 1px solid rgba(43, 92, 132, 0.18);
        border-radius: 14px;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.12);
    }

    .cm-etu-autocomplete__list.is-open {
        display: block;
    }

    .cm-etu-autocomplete__item {
        width: 100%;
        padding: 12px 14px;
        border: 0;
        background: transparent;
        text-align: left;
        color: #173754;
        cursor: pointer;
        border-bottom: 1px solid rgba(43, 92, 132, 0.08);
    }

    .cm-etu-autocomplete__item:hover,
    .cm-etu-autocomplete__item.is-active {
        background: rgba(60, 132, 197, 0.08);
    }

    @media (max-width: 1180px) {
        .cm-cand-timeline,
        .cm-cand-details {
            grid-template-columns: repeat(3, minmax(0, 1fr));
        }
    }

    @media (max-width: 920px) {
        .cm-cand-topline,
        .cm-cand-grid,
        .cm-cand-details,
        .cm-cand-timeline {
            grid-template-columns: 1fr;
        }

        .cm-cand-timeline::before {
            display: none;
        }
    }
</style>

<div class="cm-cand-page">
    <section class="cm-cand-box">
        <div class="cm-cand-head">
            <h2 class="cm-cand-head__title">Ma candidature à la soutenance</h2>
                <?php if ($trackingMode && $candidature): ?>
                    <?php
                    $overallStatus = ($steps[1]['state_label'] ?? 'En attente');
                    if ($overallStatus === '—') {
                        $overallStatus = 'En attente';
                    }
                    [$badgeType, $badgeText] = $statusBadge($overallStatus);
                    ?>
                    <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                <?php endif; ?>
        </div>

        <div class="cm-cand-topline">
            <article class="cm-cand-mini">
                <div class="cm-cand-key">Entreprise</div>
                <div class="cm-cand-value"><?= htmlspecialchars($entrepriseValue !== '' ? $entrepriseValue : 'Non renseignée', ENT_QUOTES, 'UTF-8') ?></div>
            </article>
            <article class="cm-cand-mini">
                <div class="cm-cand-key">Période</div>
                <div class="cm-cand-value"><?= htmlspecialchars($formatDate($dateDebutValue) . ' au ' . $formatDate($dateFinValue), ENT_QUOTES, 'UTF-8') ?></div>
            </article>
            <article class="cm-cand-mini">
                <div class="cm-cand-key">Rapport</div>
                <div class="cm-cand-value"><?= htmlspecialchars((string) ($rapport['nom_rapport'] ?? 'Non déposé'), ENT_QUOTES, 'UTF-8') ?></div>
            </article>
        </div>
    </section>

    <?php if (!$trackingMode): ?>
        <section class="cm-cand-form">
            <form id="stageInfoForm" method="POST" action="?page=candidature_soutenance&action=info_stage" data-cm-ajax-form="true" novalidate>
                <div class="cm-cand-grid">
                    <div>
                        <label class="cm-cand-label" for="entreprise">Entreprise <span class="cm-cand-required">*</span></label>
                        <div class="cm-etu-autocomplete">
                            <input type="text" id="entreprise" name="entreprise" class="cm-cand-input" autocomplete="off" required maxlength="50"
                                   value="<?= htmlspecialchars($entrepriseValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Entreprise d'accueil">
                            <div id="entrepriseSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                        </div>
                    </div>

                    <div>
                        <label class="cm-cand-label" for="encadrant">Maître de stage <span class="cm-cand-required">*</span></label>
                        <div class="cm-etu-autocomplete">
                            <input type="text" id="encadrant" name="encadrant" class="cm-cand-input" autocomplete="off" required maxlength="70"
                                   value="<?= htmlspecialchars($encadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom et prénoms">
                            <div id="encadrantSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                        </div>
                    </div>

                    <div>
                        <label class="cm-cand-label" for="email_encadrant">E-mail <span class="cm-cand-required">*</span></label>
                        <input type="email" id="email_encadrant" name="email_encadrant" class="cm-cand-input" required maxlength="80"
                               value="<?= htmlspecialchars($emailEncadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="exemple@domaine.com">
                    </div>

                    <div>
                        <label class="cm-cand-label" for="telephone_encadrant">Téléphone <span class="cm-cand-required">*</span></label>
                        <input type="tel" id="telephone_encadrant" name="telephone_encadrant" class="cm-cand-input" required
                               value="<?= htmlspecialchars($telephoneEncadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="0700000000">
                    </div>

                    <div>
                        <label class="cm-cand-label" for="date_debut">Date début <span class="cm-cand-required">*</span></label>
                        <input type="date" id="date_debut" name="date_debut" class="cm-cand-input" required max="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($dateDebutValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div>
                        <label class="cm-cand-label" for="date_fin">Date fin <span class="cm-cand-required">*</span></label>
                        <input type="date" id="date_fin" name="date_fin" class="cm-cand-input" required max="<?= date('Y-m-d') ?>"
                               value="<?= htmlspecialchars($dateFinValue, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="cm-cand-full">
                        <label class="cm-cand-label" for="sujet">Thème de stage <span class="cm-cand-required">*</span></label>
                        <textarea id="sujet" name="sujet" class="cm-cand-input" required rows="4"
                                  placeholder="Titre exact du stage ou du rapport"><?= htmlspecialchars($sujetValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <p id="stageDateError" class="cm-cand-sr-only" aria-live="polite"></p>

                <div class="cm-cand-buttons">
                    <button type="button" class="cm-btn is-light" onclick="window.history.back();">Annuler</button>
                    <div class="cm-cand-btns-right">
                        <?php if ($canEditStage): ?>
                            <button type="reset" class="cm-btn is-secondary">Réinitialiser</button>
                            <button type="submit" name="btn_enregistrer" value="1" class="cm-btn is-primary">Enregistrer</button>
                        <?php endif; ?>
                        <?php if ($canCreateRapport): ?>
                            <a href="?page=gestion_rapports&action=creer_rapport" class="cm-btn is-primary-dark">Déposer le rapport</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </section>
        <div id="stageAlertModal" class="cm-cand-modal-overlay" hidden>
            <div class="cm-cand-modal" role="dialog" aria-modal="true" aria-labelledby="stageAlertTitle" aria-describedby="stageAlertMessage">
                <div class="cm-cand-modal__header">
                    <h3 id="stageAlertTitle" class="cm-cand-modal__title">Alerte</h3>
                </div>
                <div class="cm-cand-modal__body">
                    <p id="stageAlertMessage">La période de stage doit être de 3 à 6 mois.</p>
                </div>
                <div class="cm-cand-modal__footer">
                    <button type="button" id="stageAlertClose" class="cm-btn is-primary">Compris</button>
                </div>
            </div>
        </div>
    <?php else: ?>
        <section class="cm-cand-box">
            <div class="cm-cand-timeline">
                <?php foreach ($steps as $step): ?>
                    <article class="cm-cand-step <?= htmlspecialchars($buildStateClass((string) ($step['status'] ?? 'pending')), ENT_QUOTES, 'UTF-8') ?>">
                        <h3 class="cm-cand-step__title"><?= htmlspecialchars((string) ($step['label'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h3>
                        <div class="cm-cand-step__state"><?= htmlspecialchars((string) ($step['state_label'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></div>
                        <?php $stepDateStr = $formatDate($step['date'] ?? null); ?>
                        <?php if ($stepDateStr !== '—'): ?>
                            <div class="cm-cand-step__date"><?= htmlspecialchars($stepDateStr, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>

        <section class="cm-cand-details">
            <article class="cm-cand-detail">
                <div class="cm-cand-key">Candidature</div>
                <div class="cm-cand-value"><?= htmlspecialchars((string) ($candidature ? ($steps[0]['state_label'] ?? 'Enregistrée') : 'En attente'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="cm-cand-help"><?= htmlspecialchars($formatDate($candidature['date_candidature'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </article>
            <article class="cm-cand-detail">
                <div class="cm-cand-key">Validation</div>
                <div class="cm-cand-value"><?= htmlspecialchars((string) ($steps[1]['state_label'] ?? 'En attente'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="cm-cand-help"><?= htmlspecialchars($formatDate($candidature['date_traitement'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </article>
            <article class="cm-cand-detail">
                <div class="cm-cand-key">Commission</div>
                <div class="cm-cand-value"><?= htmlspecialchars((string) ($commission['label'] ?? 'En attente'), ENT_QUOTES, 'UTF-8') ?></div>
                <div class="cm-cand-help">Votes : <?= (int) ($commission['votes'] ?? 0) ?></div>
            </article>
            <article class="cm-cand-detail">
                <div class="cm-cand-key">Compte Rendu</div>
                <div class="cm-cand-value"><?= htmlspecialchars($compteRendu ? (string) ($compteRendu['nom_CR'] ?? 'Disponible') : 'En attente', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="cm-cand-help"><?= htmlspecialchars($formatDate($compteRendu['date_CR'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </article>
            <article class="cm-cand-detail">
                <div class="cm-cand-key">Soutenance</div>
                <div class="cm-cand-value"><?= htmlspecialchars($soutenance ? $formatDate((string) ($soutenance['date_soutenance'] ?? ''), true) : 'Non programmée', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="cm-cand-help"><?= htmlspecialchars((string) ($soutenance['lib_salle'] ?? '—') . (!empty($soutenance['lib_session']) ? ' • ' . (string) $soutenance['lib_session'] : ''), ENT_QUOTES, 'UTF-8') ?></div>
            </article>
            <article class="cm-cand-detail">
                <div class="cm-cand-key">PV</div>
                <div class="cm-cand-value"><?= !empty($pv['disponible']) ? 'Disponible' : 'En attente' ?></div>
                <div class="cm-cand-help"><?= htmlspecialchars($formatDate($pv['date'] ?? null), ENT_QUOTES, 'UTF-8') ?></div>
            </article>
        </section>

        <section class="cm-cand-actions-panel">
            <div class="cm-cand-links">
                <a href="<?= htmlspecialchars($rapportUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-secondary">Voir le rapport</a>
                <?php if ($compteRendu): ?>
                    <a href="?page=consultation_cr_etud" class="cm-btn is-info">Compte rendu</a>
                <?php endif; ?>
                <?php if (!empty($pv['disponible'])): ?>
                    <a href="<?= htmlspecialchars($pvUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-primary">Imprimer le PV</a>
                <?php endif; ?>
            </div>
        </section>
    <?php endif; ?>
</div>

<script>
    (function () {
        const entreprisesData = <?= json_encode(array_map(static function ($entreprise) {
            $nomLong = (string) ($entreprise->lib_long_entreprise ?? '');
            $nomCourt = (string) ($entreprise->lib_court_en ?? '');
            $nomAffiche = $nomCourt ? ($nomLong . ' (' . $nomCourt . ')') : $nomLong;
            return [
                'id' => (int) ($entreprise->id_entreprise ?? 0),
                'nom' => $nomAffiche
            ];
        }, $entreprises), JSON_UNESCAPED_UNICODE) ?>;

        const maitresDeStage = <?= json_encode(array_map(static function ($maitre) {
            $nomLong = (string) ($maitre->lib_long_entreprise ?? '');
            $nomCourt = (string) ($maitre->lib_court_en ?? '');
            $nomEntreprise = $nomCourt ? ($nomLong . ' (' . $nomCourt . ')') : $nomLong;
            return [
                'nom_complet' => trim((string) ($maitre->Nom ?? '') . ' ' . (string) ($maitre->prenom ?? '')),
                'email' => (string) ($maitre->email ?? ''),
                'telephone' => (string) ($maitre->telephone ?? ''),
                'id_entreprise' => (int) ($maitre->id_entreprise ?? 0),
                'entreprise' => $nomEntreprise
            ];
        }, $maitresDeStageData), JSON_UNESCAPED_UNICODE) ?>;

        const inputEntreprise = document.getElementById('entreprise');
        const suggestions = document.getElementById('entrepriseSuggestions');
        const inputEncadrant = document.getElementById('encadrant');
        const suggestionsEncadrant = document.getElementById('encadrantSuggestions');
        const inputEmailEncadrant = document.getElementById('email_encadrant');
        const inputTelephoneEncadrant = document.getElementById('telephone_encadrant');
        const dateDebut = document.getElementById('date_debut');
        const dateFin = document.getElementById('date_fin');
        const dateError = document.getElementById('stageDateError');
        const form = document.getElementById('stageInfoForm');
        const stageAlertModal = document.getElementById('stageAlertModal');
        const stageAlertTitle = document.getElementById('stageAlertTitle');
        const stageAlertMessage = document.getElementById('stageAlertMessage');
        const stageAlertClose = document.getElementById('stageAlertClose');
        const sujetInput = document.getElementById('sujet');
        const submitButton = form ? form.querySelector('button[type="submit"][name="btn_enregistrer"]') : null;
        const defaultSubmitLabel = submitButton ? submitButton.innerHTML : '';
        const successMessage = <?= json_encode($successMessage, JSON_UNESCAPED_UNICODE) ?>;
        const errorMessage = <?= json_encode($errorMessage, JSON_UNESCAPED_UNICODE) ?>;
        const debugBucket = window.__cmCandLogs = window.__cmCandLogs || [];

        function debugLog(label, payload) {
            const entry = {
                time: new Date().toISOString(),
                label: label,
                payload: payload || null
            };
            debugBucket.push(entry);
            if (window.console && typeof window.console.log === 'function') {
                console.log('[candidature_soutenance]', label, payload || null);
            }
        }

        window.addEventListener('error', function (event) {
            debugLog('window_error', {
                message: event.message || null,
                source: event.filename || null,
                line: event.lineno || null,
                column: event.colno || null
            });
        });

        window.addEventListener('unhandledrejection', function (event) {
            debugLog('window_unhandled_rejection', {
                reason: event && Object.prototype.hasOwnProperty.call(event, 'reason') ? event.reason : null
            });
        });

        if (!inputEntreprise || !suggestions || !inputEncadrant || !suggestionsEncadrant || !dateDebut || !dateFin || !dateError || !form || !stageAlertModal || !stageAlertTitle || !stageAlertMessage || !stageAlertClose || !sujetInput) {
            debugLog('bootstrap_missing_nodes', {
                hasInputEntreprise: !!inputEntreprise,
                hasSuggestions: !!suggestions,
                hasInputEncadrant: !!inputEncadrant,
                hasSuggestionsEncadrant: !!suggestionsEncadrant,
                hasDateDebut: !!dateDebut,
                hasDateFin: !!dateFin,
                hasDateError: !!dateError,
                hasForm: !!form,
                hasModal: !!stageAlertModal,
                hasModalTitle: !!stageAlertTitle,
                hasModalMessage: !!stageAlertMessage,
                hasModalClose: !!stageAlertClose,
                hasSujet: !!sujetInput
            });
            return;
        }

        debugLog('bootstrap_ready', {
            formAction: form.getAttribute('action'),
            formMethod: form.getAttribute('method'),
            ajaxEnabled: form.getAttribute('data-cm-ajax-form'),
            successMessage: successMessage,
            errorMessage: errorMessage,
            entrepriseValue: inputEntreprise.value,
            encadrantValue: inputEncadrant.value,
            dateDebutValue: dateDebut.value,
            dateFinValue: dateFin.value,
            sujetLength: String(sujetInput.value || '').length
        });

        let selectedEntrepriseId = null;
        let currentIndex = -1;
        let currentIndexEncadrant = -1;
        let lastValidationMessage = '';

        if (inputEntreprise.value.trim() !== '') {
            const currentEntreprise = entreprisesData.find(function (item) {
                return item.nom === inputEntreprise.value.trim();
            });
            if (currentEntreprise) {
                selectedEntrepriseId = currentEntreprise.id;
                debugLog('bootstrap_selected_entreprise', {
                    id: selectedEntrepriseId,
                    value: inputEntreprise.value.trim()
                });
            }
        }

        function hideSuggestions() {
            debugLog('hide_entreprise_suggestions');
            suggestions.classList.remove('is-open');
            suggestions.innerHTML = '';
            currentIndex = -1;
        }

        function hideSuggestionsEncadrant() {
            debugLog('hide_encadrant_suggestions');
            suggestionsEncadrant.classList.remove('is-open');
            suggestionsEncadrant.innerHTML = '';
            currentIndexEncadrant = -1;
        }

        function createEntrepriseItem(label, entrepriseData) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'cm-etu-autocomplete__item';
            item.textContent = label;
            item.addEventListener('click', function () {
                debugLog('entreprise_selected', {
                    label: label,
                    entrepriseId: entrepriseData ? entrepriseData.id : null
                });
                inputEntreprise.value = label;
                selectedEntrepriseId = entrepriseData ? entrepriseData.id : null;
                inputEncadrant.value = '';
                inputEmailEncadrant.value = '';
                inputTelephoneEncadrant.value = '';
                hideSuggestions();
            });
            return item;
        }

        function renderEntrepriseSuggestions(query) {
            const value = String(query || '').trim().toLowerCase();
            debugLog('render_entreprise_suggestions', {
                query: query,
                normalized: value
            });
            if (value === '') {
                hideSuggestions();
                return;
            }

            const matches = entreprisesData.filter(function (item) {
                return String(item.nom).toLowerCase().includes(value);
            });
            debugLog('render_entreprise_suggestions_matches', {
                count: matches.length
            });

            suggestions.innerHTML = '';
            matches.slice(0, 8).forEach(function (item) {
                suggestions.appendChild(createEntrepriseItem(item.nom, item));
            });

            if (!matches.length) {
                suggestions.appendChild(createEntrepriseItem(String(query).trim(), null));
            }

            suggestions.classList.add('is-open');
        }

        function updateEntrepriseSelection() {
            const items = suggestions.querySelectorAll('.cm-etu-autocomplete__item');
            debugLog('update_entreprise_selection', {
                currentIndex: currentIndex,
                itemCount: items.length
            });
            items.forEach(function (item, index) {
                item.classList.toggle('is-active', index === currentIndex);
            });
        }

        function createEncadrantItem(maitre, isCustom) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'cm-etu-autocomplete__item';

            if (isCustom) {
                item.textContent = maitre;
                item.addEventListener('click', function () {
                    debugLog('encadrant_selected_custom', {
                        value: maitre
                    });
                    inputEncadrant.value = maitre;
                    inputEmailEncadrant.value = '';
                    inputTelephoneEncadrant.value = '';
                    hideSuggestionsEncadrant();
                });
                return item;
            }

            item.innerHTML = '<strong>' + maitre.nom_complet + '</strong><br><small>' +
                (maitre.email || '—') + ' • ' + (maitre.telephone || '—') + '</small>';
            item.addEventListener('click', function () {
                debugLog('encadrant_selected_existing', {
                    nom: maitre.nom_complet,
                    email: maitre.email || '',
                    telephone: maitre.telephone || '',
                    entrepriseId: maitre.id_entreprise
                });
                inputEncadrant.value = maitre.nom_complet;
                inputEmailEncadrant.value = maitre.email || '';
                inputTelephoneEncadrant.value = maitre.telephone || '';
                hideSuggestionsEncadrant();
            });
            return item;
        }

        function renderEncadrantSuggestions(query) {
            const value = String(query || '').trim().toLowerCase();
            debugLog('render_encadrant_suggestions', {
                query: query,
                normalized: value,
                selectedEntrepriseId: selectedEntrepriseId
            });
            if (value === '') {
                hideSuggestionsEncadrant();
                return;
            }

            let source = maitresDeStage;
            if (selectedEntrepriseId !== null) {
                source = source.filter(function (item) {
                    return item.id_entreprise === selectedEntrepriseId;
                });
            }

            const matches = source.filter(function (item) {
                return String(item.nom_complet).toLowerCase().includes(value);
            });
            debugLog('render_encadrant_suggestions_matches', {
                sourceCount: source.length,
                count: matches.length
            });

            suggestionsEncadrant.innerHTML = '';
            matches.slice(0, 8).forEach(function (item) {
                suggestionsEncadrant.appendChild(createEncadrantItem(item, false));
            });

            if (!matches.length) {
                suggestionsEncadrant.appendChild(createEncadrantItem(String(query).trim(), true));
            }

            suggestionsEncadrant.classList.add('is-open');
        }

        function updateEncadrantSelection() {
            const items = suggestionsEncadrant.querySelectorAll('.cm-etu-autocomplete__item');
            debugLog('update_encadrant_selection', {
                currentIndexEncadrant: currentIndexEncadrant,
                itemCount: items.length
            });
            items.forEach(function (item, index) {
                item.classList.toggle('is-active', index === currentIndexEncadrant);
            });
        }

        function openStageAlert(title, message) {
            debugLog('open_stage_alert', {
                title: title,
                message: message
            });
            stageAlertTitle.textContent = title;
            stageAlertMessage.textContent = message;
            stageAlertModal.hidden = false;
            stageAlertModal.classList.add('is-open');
            document.body.classList.add('modal-open');
            stageAlertClose.focus();
        }

        function closeStageAlert() {
            debugLog('close_stage_alert');
            stageAlertModal.classList.remove('is-open');
            stageAlertModal.hidden = true;
            document.body.classList.remove('modal-open');
        }

        function setSubmitState(isSubmitting) {
            debugLog('set_submit_state', {
                isSubmitting: !!isSubmitting
            });
            if (!submitButton) {
                return;
            }
            submitButton.disabled = !!isSubmitting;
            submitButton.classList.toggle('is-disabled', !!isSubmitting);
            submitButton.innerHTML = isSubmitting ? 'Enregistrement...' : defaultSubmitLabel;
        }

        function setDateValidationMessage(message, showModal) {
            debugLog('set_date_validation_message', {
                message: message || '',
                showModal: !!showModal
            });
            dateError.textContent = message || '';
            if (showModal && message && message !== lastValidationMessage) {
                openStageAlert('Période de stage invalide', message);
            }
            lastValidationMessage = message || '';
            return !message;
        }

        function validateFields(showModal) {
            debugLog('validate_fields:start', {
                showModal: !!showModal
            });
            const checks = [
                { field: inputEntreprise, message: "Veuillez renseigner l'entreprise." },
                { field: inputEncadrant, message: 'Veuillez renseigner le maître de stage.' },
                { field: inputEmailEncadrant, message: "Veuillez renseigner l'e-mail du maître de stage." },
                { field: inputTelephoneEncadrant, message: 'Veuillez renseigner le téléphone du maître de stage.' },
                { field: dateDebut, message: 'Veuillez renseigner la date de début.' },
                { field: dateFin, message: 'Veuillez renseigner la date de fin.' },
                { field: sujetInput, message: 'Veuillez renseigner le thème de stage.' }
            ];

            for (let index = 0; index < checks.length; index += 1) {
                const check = checks[index];
                const value = String(check.field.value || '').trim();
                if (value === '') {
                    debugLog('validate_fields:missing_value', {
                        field: check.field.name || check.field.id || null,
                        message: check.message
                    });
                    if (showModal) {
                        openStageAlert('Champ requis', check.message);
                    }
                    check.field.focus();
                    return false;
                }
            }

            if (inputEmailEncadrant.validity.typeMismatch) {
                debugLog('validate_fields:invalid_email', {
                    value: inputEmailEncadrant.value
                });
                if (showModal) {
                    openStageAlert('E-mail invalide', "Veuillez saisir une adresse e-mail valide.");
                }
                inputEmailEncadrant.focus();
                return false;
            }

            debugLog('validate_fields:success');
            return true;
        }

        function validateDates(showModal) {
            debugLog('validate_dates:start', {
                showModal: !!showModal,
                dateDebut: dateDebut.value,
                dateFin: dateFin.value
            });
            if (!dateDebut.value || !dateFin.value) {
                return setDateValidationMessage('', false);
            }

            const debut = new Date(dateDebut.value + 'T00:00:00');
            const fin = new Date(dateFin.value + 'T00:00:00');
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            const finExclusive = new Date(fin.getTime());
            finExclusive.setDate(finExclusive.getDate() + 1);
            finExclusive.setHours(0, 0, 0, 0);

            function addCalendarMonths(baseDate, monthsToAdd) {
                const result = new Date(baseDate.getTime());
                const originalDay = result.getDate();
                result.setMonth(result.getMonth() + monthsToAdd);
                if (result.getDate() < originalDay) {
                    result.setDate(0);
                }
                result.setHours(0, 0, 0, 0);
                return result;
            }

            function formatDateFr(dateValue) {
                const day = String(dateValue.getDate()).padStart(2, '0');
                const month = String(dateValue.getMonth() + 1).padStart(2, '0');
                const year = dateValue.getFullYear();
                return day + '/' + month + '/' + year;
            }

            if (debut > today) {
                return setDateValidationMessage('La date de début ne peut pas être dans le futur.', showModal);
            }
            if (fin > today) {
                return setDateValidationMessage('La date de fin ne peut pas être dans le futur.', showModal);
            }
            if (fin <= debut) {
                return setDateValidationMessage('La date de fin doit être après la date de début.', showModal);
            }

            const minFinExclusive = addCalendarMonths(debut, 3);
            const months = ((finExclusive - debut) / (1000 * 60 * 60 * 24)) / 30.44;
            debugLog('validate_dates:computed', {
                months: months,
                debut: debut.toISOString(),
                fin: fin.toISOString(),
                finExclusive: finExclusive.toISOString(),
                minFinExclusive: minFinExclusive.toISOString()
            });
            if (finExclusive < minFinExclusive) {
                const minAllowedDate = new Date(minFinExclusive.getTime());
                minAllowedDate.setDate(minAllowedDate.getDate() - 1);
                return setDateValidationMessage(
                    'La periode de stage doit couvrir au moins 3 mois calendaires. Pour une date de debut au ' +
                    formatDateFr(debut) + ', la date de fin doit etre a partir du ' +
                    formatDateFr(minAllowedDate) + '.',
                    showModal
                );
            }

            return setDateValidationMessage('', false);
        }

        inputEntreprise.addEventListener('input', function () {
            debugLog('entreprise_input', {
                value: inputEntreprise.value
            });
            renderEntrepriseSuggestions(inputEntreprise.value);
        });

        inputEntreprise.addEventListener('keydown', function (event) {
            const items = suggestions.querySelectorAll('.cm-etu-autocomplete__item');
            if (!items.length) {
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                currentIndex = Math.min(currentIndex + 1, items.length - 1);
                updateEntrepriseSelection();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                currentIndex = Math.max(currentIndex - 1, 0);
                updateEntrepriseSelection();
            } else if (event.key === 'Enter' && currentIndex >= 0) {
                event.preventDefault();
                items[currentIndex].click();
            }
        });

        inputEncadrant.addEventListener('input', function () {
            debugLog('encadrant_input', {
                value: inputEncadrant.value
            });
            renderEncadrantSuggestions(inputEncadrant.value);
        });

        inputEncadrant.addEventListener('keydown', function (event) {
            const items = suggestionsEncadrant.querySelectorAll('.cm-etu-autocomplete__item');
            if (!items.length) {
                return;
            }
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                currentIndexEncadrant = Math.min(currentIndexEncadrant + 1, items.length - 1);
                updateEncadrantSelection();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                currentIndexEncadrant = Math.max(currentIndexEncadrant - 1, 0);
                updateEncadrantSelection();
            } else if (event.key === 'Enter' && currentIndexEncadrant >= 0) {
                event.preventDefault();
                items[currentIndexEncadrant].click();
            }
        });

        dateDebut.addEventListener('change', function () {
            debugLog('date_debut_change', {
                value: dateDebut.value
            });
            validateDates(true);
        });
        dateFin.addEventListener('change', function () {
            debugLog('date_fin_change', {
                value: dateFin.value
            });
            validateDates(true);
        });

        form.addEventListener('submit', function (event) {
            const formData = new FormData(form);
            const snapshot = {};
            formData.forEach(function (value, key) {
                snapshot[key] = value;
            });
            debugLog('form_submit', {
                action: form.getAttribute('action'),
                method: form.getAttribute('method'),
                submitter: event.submitter ? (event.submitter.name || event.submitter.textContent || null) : null,
                data: snapshot
            });

            const fieldsValid = validateFields(true);
            const datesValid = validateDates(true);
            debugLog('form_submit:validation_results', {
                fieldsValid: fieldsValid,
                datesValid: datesValid
            });

            if (!fieldsValid || !datesValid) {
                event.preventDefault();
                dateFin.scrollIntoView({behavior: 'smooth', block: 'center'});
                setSubmitState(false);
                return;
            }

            setSubmitState(true);
        });

        document.addEventListener('cm:ajax:form:error', function (event) {
            const detail = event && event.detail ? event.detail : null;
            if (!detail || detail.form !== form) {
                return;
            }

            debugLog('ajax_form_error', {
                payload: detail.payload || null
            });
            setSubmitState(false);

            const payload = detail.payload || null;
            if (payload && payload.message) {
                openStageAlert('Erreur', payload.message);
            }
        });

        document.addEventListener('cm:ajax:before-load', function (event) {
            debugLog('ajax_before_load', {
                detail: event && event.detail ? event.detail : null
            });
        });

        document.addEventListener('cm:ajax:after-load', function (event) {
            debugLog('ajax_after_load', {
                detail: event && event.detail ? event.detail : null
            });
        });

        stageAlertClose.addEventListener('click', closeStageAlert);
        stageAlertModal.addEventListener('click', function (event) {
            if (event.target === stageAlertModal) {
                closeStageAlert();
            }
        });

        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && stageAlertModal.classList.contains('is-open')) {
                closeStageAlert();
            }
        });

        document.addEventListener('click', function (event) {
            if (!suggestions.contains(event.target) && event.target !== inputEntreprise) {
                hideSuggestions();
            }
            if (!suggestionsEncadrant.contains(event.target) && event.target !== inputEncadrant) {
                hideSuggestionsEncadrant();
            }
        });

        if (errorMessage) {
            openStageAlert('Erreur', errorMessage);
        } else if (successMessage) {
            openStageAlert('Succès', successMessage);
        }
    })();
</script>

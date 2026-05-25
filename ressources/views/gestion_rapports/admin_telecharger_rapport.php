<?php
$etudiantsSansRapport = $GLOBALS['etudiantsSansRapport'] ?? [];
$rapportsAdmin = $GLOBALS['rapportsAdmin'] ?? [];
$typesAutorises = $GLOBALS['typesAutorises'] ?? 'pdf,doc,docx';
$tailleMax = $GLOBALS['tailleMax'] ?? 20971520;

$messageSuccess = $_SESSION['success'] ?? null;
$messageError = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$searchTerm = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$dateSysteme = date('Y-m-d\TH:i');
$basePage = (isset($_GET['page']) && $_GET['page'] === 'telecharger_rapport')
    ? 'telecharger_rapport'
    : 'gestion_rapports';
$csrfTokenValue = class_exists('\CheckMaster\Core\Csrf')
    ? \CheckMaster\Core\Csrf::token()
    : '';

$formatText = static function ($value, $fallback = '-') {
    $value = trim((string) $value);
    return $value !== '' ? $value : $fallback;
};

$formatPromotion = static function ($value) use ($formatText) {
    $value = \FormattingUtils::formatPromotion((string) $value);
    return $formatText($value);
};

$formatAcademicYear = static function ($value) use ($formatText) {
    $formatted = \FormattingUtils::formatPromotion((string) $value);
    return $formatText($formatted);
};

$resolveThemeValue = static function ($themeRapport, $sujetStage = '') {
    $themeRapport = trim((string) $themeRapport);
    if ($themeRapport !== '') {
        return $themeRapport;
    }

    return trim((string) $sujetStage);
};

$formatDate = static function ($value, $fallback = '-') {
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }
    return \FormattingUtils::formatDate($value, 'd/m/Y');
};

$formatDateTime = static function ($value, $fallback = '-') {
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }
    return \FormattingUtils::formatDateTime($value, 'd/m/Y H:i');
};

$normalizeDateTimeLocal = static function ($value, $fallback = '') {
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }

    $value = str_replace(' ', 'T', $value);
    return substr($value, 0, 16);
};

$formatStagePeriod = static function ($start, $end) use ($formatDate) {
    $start = trim((string) $start);
    $end = trim((string) $end);

    if ($start === '' && $end === '') {
        return '-';
    }
    if ($start !== '' && $end !== '') {
        return $formatDate($start) . ' au ' . $formatDate($end);
    }
    if ($start !== '') {
        return 'Depuis le ' . $formatDate($start);
    }
    return 'Jusqu au ' . $formatDate($end);
};

$formatFileSize = static function ($bytes) {
    $bytes = (int) $bytes;
    if ($bytes <= 0) {
        return '-';
    }
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 2, ',', ' ') . ' Mo';
    }
    return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
};

$getBadge = static function ($statut) {
    $statut = strtolower(trim((string) $statut));
    if ($statut === 'valider') {
        return ['class' => 'is-success', 'label' => 'Valide'];
    }
    if ($statut === 'rejeter') {
        return ['class' => 'is-danger', 'label' => 'Rejete'];
    }
    if ($statut === 'en_cours' || $statut === 'en_attente') {
        return ['class' => 'is-info', 'label' => 'En attente'];
    }
    return ['class' => 'is-light', 'label' => 'Brouillon'];
};

$prefill = [
    'num_etu' => trim((string) ($_GET['prefill_num_etu'] ?? '')),
    'nom' => trim((string) ($_GET['prefill_nom'] ?? '')),
    'num_carte' => trim((string) ($_GET['prefill_num_carte'] ?? '')),
    'num_ident' => trim((string) ($_GET['prefill_num_ident'] ?? '')),
    'email' => trim((string) ($_GET['prefill_email'] ?? '')),
    'promotion' => $formatPromotion($_GET['prefill_promotion'] ?? ''),
    'annee' => $formatAcademicYear($_GET['prefill_annee'] ?? ''),
    'candidature' => trim((string) ($_GET['prefill_candidature'] ?? '')),
    'entreprise' => trim((string) ($_GET['prefill_entreprise'] ?? '')),
    'sujet' => trim((string) ($_GET['prefill_sujet'] ?? '')),
    'maitre' => trim((string) ($_GET['prefill_maitre'] ?? '')),
    'periode' => trim((string) ($_GET['prefill_periode'] ?? '')),
    'nb_rapports' => trim((string) ($_GET['prefill_nb_rapports'] ?? '')),
    'date_operation' => $normalizeDateTimeLocal($_GET['prefill_date_operation'] ?? '', $dateSysteme),
    'existing_rapport' => isset($_GET['prefill_existing_rapport']) ? '1' : '0',
    'focus_upload' => isset($_GET['focus_upload']) ? '1' : '0',
];

$hasPrefill = $prefill['num_etu'] !== '';
if ($hasPrefill && $prefill['num_carte'] === '') {
    $prefill['num_carte'] = $prefill['num_etu'];
}

$initialImportLabel = 'Importer le rapport';
$initialImportHint = 'Choisissez d abord un etudiant.';
if ($hasPrefill) {
    $prefillNom = $prefill['nom'] !== '' ? $prefill['nom'] : 'cet etudiant';
    if ($prefill['existing_rapport'] === '1') {
        $initialImportLabel = 'Remplacer le rapport de ' . $prefillNom;
        $initialImportHint = 'Le prochain fichier remplacera le rapport deja associe a ' . $prefillNom . '.';
    } else {
        $initialImportLabel = 'Importer le rapport pour ' . $prefillNom;
        $initialImportHint = 'Le fichier sera rattache au dossier de ' . $prefillNom . '.';
    }
}
?>

<style>

    .cm-rapport-admin-form-note {
        margin: 0;
        color: #6d7c8b;
        line-height: 1.6;
    }

    .cm-rapport-admin-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem 1rem;
        align-items: start;
    }

    .cm-rapport-admin-form-grid .cm-form-group {
        max-width: none !important;
        width: 100% !important;
    }

    .cm-rapport-admin-form-grid .cm-form-control,
    .cm-rapport-admin-form-grid input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    .cm-rapport-admin-form-grid select,
    .cm-rapport-admin-form-grid textarea {
        width: 100% !important;
        max-width: none !important;
    }

    .cm-rapport-admin-span-2 {
        grid-column: 1 / -1;
    }

    .cm-rapport-admin-info {
        display: none;
        margin-top: 0.35rem;
        padding: 0.95rem;
        border: 1px solid var(--cm-border-color, #d8e2eb);
        border-radius: 0.9rem;
        background: var(--cm-app-bg, linear-gradient(180deg, #dff2ff 0%, #d6ecff 52%, #cfe6fb 100%));
    }

    .cm-rapport-admin-info__title {
        margin: 0 0 0.8rem 0;
        font-size: 1rem;
        color: #223046;
    }

    .cm-rapport-admin-info__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.75rem;
    }

    .cm-rapport-admin-info__item {
        padding: 0.7rem 0.8rem;
        border: 1px solid #e6edf4;
        border-radius: 0.75rem;
        background: var(--cm-content-bg, rgba(237, 246, 255, 0.84));
    }

    .cm-rapport-admin-info__label {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #7b8794;
    }

    .cm-rapport-admin-info__value {
        color: #223046;
        font-weight: 600;
        word-break: break-word;
    }

    .cm-rapport-admin-help {
        display: block;
        margin-top: 0.3rem;
        color: #758292;
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .cm-rapport-admin-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        align-items: center;
    }

    .cm-rapport-admin-table-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 0.85rem;
    }

    .cm-rapport-admin-table-head h2 {
        margin: 0 0 0.3rem 0;
        font-size: 1.12rem;
    }

    .cm-rapport-admin-table-head p {
        margin: 0;
        color: #5d6b7a;
    }

    .cm-rapport-admin-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: end;
    }

    .cm-rapport-admin-filter .cm-form-group {
        max-width: none !important;
        width: 100% !important;
        min-width: 240px;
        flex: 1 1 280px;
    }

    .cm-rapport-admin-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cm-rapport-admin-badge.is-success {
        background: #e9f8ef;
        color: #167c41;
    }

    .cm-rapport-admin-badge.is-danger {
        background: #fdecec;
        color: #b42318;
    }

    .cm-rapport-admin-badge.is-info {
        background: #eaf3ff;
        color: #175cd3;
    }

    .cm-rapport-admin-badge.is-light {
        background: #eef2f6;
        color: #516173;
    }

    .cm-rapport-admin-student-trigger {
        display: block;
        width: 100%;
        padding: 0;
        border: 0;
        background: transparent;
        text-align: left;
        color: inherit;
        cursor: pointer;
        text-decoration: none;
    }

    .cm-rapport-admin-student-trigger strong {
        color: #0f4c81;
        text-decoration: underline;
        text-decoration-color: rgba(15, 76, 129, 0.3);
        text-underline-offset: 0.15rem;
    }

    .cm-rapport-admin-student-trigger:hover strong,
    .cm-rapport-admin-student-trigger:focus-visible strong {
        text-decoration-color: rgba(15, 76, 129, 0.8);
    }

    .cm-rapport-admin-student-trigger:focus-visible {
        outline: 2px solid rgba(23, 92, 211, 0.35);
        outline-offset: 0.25rem;
        border-radius: 0.35rem;
    }

    .cm-rapport-admin-student-meta {
        display: block;
        margin-top: 0.18rem;
        color: #5d6b7a;
    }

    .cm-rapport-admin-student-theme {
        display: block;
        margin-top: 0.35rem;
        color: #223046;
        font-size: 0.84rem;
        line-height: 1.45;
    }

    .cm-rapport-admin-student-theme-label {
        font-weight: 700;
        color: #516173;
    }

    .cm-rapport-admin-table-note {
        display: block;
        margin-top: 0.35rem;
        color: #758292;
        font-size: 0.84rem;
    }

    .cm-rapport-admin-empty {
        padding: 1.4rem;
        text-align: center;
        color: #708090;
    }

    @media (max-width: 900px) {
        .cm-rapport-admin-form-grid {
            grid-template-columns: 1fr;
        }

        .cm-rapport-admin-span-2 {
            grid-column: auto;
        }
    }
</style>

<section
    class="cm-prd3-crud-screen cm-prd6-admin-screen cm-screen-scrollable cm-rapport-admin-shell"
    data-cm-rapport-admin-prefilled="<?= $hasPrefill ? '1' : '0' ?>"
    data-cm-rapport-admin-focus="<?= $prefill['focus_upload'] === '1' ? '1' : '0' ?>">
    <?php if ($messageSuccess): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>

    <?php if ($messageError): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageError]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <form id="admin_upload_rapport_form" method="POST" action="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="admin_upload_rapport">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenValue, ENT_QUOTES, 'UTF-8') ?>">

                <div class="cm-rapport-admin-form-grid">
                    <div class="cm-form-group cm-rapport-admin-span-2">
                        <label class="cm-form-label" for="num_etu_select">Etudiant <span class="cm-required-star">*</span></label>
                        <select id="num_etu_select" name="num_etu" class="cm-form-control" required>
                            <option value="" <?= $hasPrefill ? '' : 'selected' ?>>-- Selectionnez un etudiant --</option>
                            <?php $prefillFoundInOptions = false; ?>
                            <?php foreach ($etudiantsSansRapport as $e): ?>
                                <?php
                                $nomComplet = trim((string) (($e->nom_etu ?? '') . ' ' . ($e->prenom_etu ?? '')));
                                $matricule = (string) ($e->num_carte_etud ?? $e->num_ident_etud ?? '');
                                $identifiant = (string) ($e->num_ident_etud ?? '');
                                $isSelected = $hasPrefill && $prefill['num_etu'] === $matricule;
                                if ($isSelected) {
                                    $prefillFoundInOptions = true;
                                }
                                ?>
                                <option
                                    value="<?= htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8') ?>"
                                    <?= $isSelected ? 'selected' : '' ?>
                                    data-nom="<?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?>"
                                    data-num-carte="<?= htmlspecialchars((string) ($e->num_carte_etud ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-num-ident="<?= htmlspecialchars($identifiant, ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars((string) ($e->email_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-promotion="<?= htmlspecialchars($formatPromotion($e->promotion_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-annee="<?= htmlspecialchars($formatAcademicYear($e->id_annee_acad ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-candidature="<?= htmlspecialchars((string) ($e->statut_candidature ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-entreprise="<?= htmlspecialchars((string) ($e->entreprise_stage ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-sujet="<?= htmlspecialchars((string) ($e->sujet_stage ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-maitre="<?= htmlspecialchars((string) ($e->maitre_stage_nom ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-periode="<?= htmlspecialchars($formatStagePeriod($e->date_debut_stage ?? null, $e->date_fin_stage ?? null), ENT_QUOTES, 'UTF-8') ?>"
                                    data-nb-rapports="<?= htmlspecialchars((string) ($e->nb_rapports ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($nomComplet . ' (' . $matricule . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                            <?php if ($hasPrefill && !$prefillFoundInOptions): ?>
                                <option
                                    value="<?= htmlspecialchars($prefill['num_etu'], ENT_QUOTES, 'UTF-8') ?>"
                                    selected
                                    data-nom="<?= htmlspecialchars($prefill['nom'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-num-carte="<?= htmlspecialchars($prefill['num_carte'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-num-ident="<?= htmlspecialchars($prefill['num_ident'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars($prefill['email'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-promotion="<?= htmlspecialchars($formatPromotion($prefill['promotion']), ENT_QUOTES, 'UTF-8') ?>"
                                    data-annee="<?= htmlspecialchars($prefill['annee'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-candidature="<?= htmlspecialchars($prefill['candidature'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-entreprise="<?= htmlspecialchars($prefill['entreprise'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-sujet="<?= htmlspecialchars($prefill['sujet'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-maitre="<?= htmlspecialchars($prefill['maitre'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-periode="<?= htmlspecialchars($prefill['periode'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-nb-rapports="<?= htmlspecialchars($prefill['nb_rapports'], ENT_QUOTES, 'UTF-8') ?>"
                                    data-existing-rapport="<?= htmlspecialchars($prefill['existing_rapport'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(($prefill['nom'] !== '' ? $prefill['nom'] : 'Etudiant') . ' (' . $prefill['num_carte'] . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endif; ?>
                        </select>
                        <span class="cm-rapport-admin-help">Le rapport importe sera lie au dossier selectionne.</span>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="admin_theme_rapport">Theme du rapport</label>
                        <input type="text" id="admin_theme_rapport" name="theme_rapport" class="cm-form-control"
                            value="<?= htmlspecialchars($prefill['sujet'], ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex : Conception d'une application de suivi">
                        <span class="cm-rapport-admin-help">Vous pouvez reprendre le sujet de stage si besoin.</span>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="admin_rapport_fichier">Fichier du rapport <span class="cm-required-star">*</span></label>
                        <input type="file" id="admin_rapport_fichier" name="rapport_fichier" class="cm-form-control"
                            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            required>
                        <span class="cm-rapport-admin-help">
                            Formats autorises : <?= htmlspecialchars($typesAutorises, ENT_QUOTES, 'UTF-8') ?>.
                            Taille max : <?= htmlspecialchars($formatFileSize($tailleMax), ENT_QUOTES, 'UTF-8') ?>.
                        </span>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Date systeme</label>
                        <input type="datetime-local" class="cm-form-control"
                            value="<?= htmlspecialchars($dateSysteme, ENT_QUOTES, 'UTF-8') ?>" disabled>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="date_operation">Date d'operation</label>
                        <input type="datetime-local" id="date_operation" name="date_operation" class="cm-form-control"
                            value="<?= htmlspecialchars($prefill['date_operation'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="cm-rapport-admin-span-2">
                        <div id="etudiant_info" class="cm-rapport-admin-info"<?= $hasPrefill ? ' style="display:block;"' : '' ?>>
                            <h3 id="info_nom_header" class="cm-rapport-admin-info__title"><?= htmlspecialchars($hasPrefill && $prefill['nom'] !== '' ? $prefill['nom'] : 'Dossier selectionne', ENT_QUOTES, 'UTF-8') ?></h3>
                            <div class="cm-rapport-admin-info__grid">
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Matricule</span><span id="info_matricule" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['num_carte']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Identifiant</span><span id="info_identifiant" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['num_ident']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Promotion</span><span id="info_promotion" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['promotion']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Annee academique</span><span id="info_annee" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['annee']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Email</span><span id="info_email" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['email']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Candidature</span><span id="info_candidature" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['candidature']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Entreprise</span><span id="info_entreprise" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['entreprise']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Maitre de stage</span><span id="info_maitre_stage" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['maitre']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Periode de stage</span><span id="info_stage_periode" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['periode']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Sujet de stage</span><span id="info_sujet_stage" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['sujet']), ENT_QUOTES, 'UTF-8') ?></span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Rapports existants</span><span id="info_nb_rapports" class="cm-rapport-admin-info__value"><?= htmlspecialchars($formatText($prefill['nb_rapports']), ENT_QUOTES, 'UTF-8') ?></span></div>
                            </div>
                        </div>
                    </div>

                    <div class="cm-rapport-admin-span-2">
                        <div class="cm-form-buttons">
                            <button type="submit" class="cm-btn is-primary">
                                <i class="fas fa-upload" aria-hidden="true"></i>
                                <span id="admin_import_label"><?= htmlspecialchars($initialImportLabel, ENT_QUOTES, 'UTF-8') ?></span>
                            </button>
                            <span id="admin_import_hint" class="cm-rapport-admin-help"><?= htmlspecialchars($initialImportHint, ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="cm-barre-intermediaire">
            <div class="cm-rapport-admin-table-head">
                <div>
                    <h2>Tableau recapitulatif</h2>
                    <p>Historique des rapports deja importes par l'administration.</p>
                    <span class="cm-rapport-admin-table-note">Cliquez sur un etudiant pour le recharger dans le formulaire du haut.</span>
                </div>
                <div class="cm-rapport-admin-actions">
                    <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=admin_telecharger_rapport<?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?>"
                        class="cm-btn is-light is-sm" onclick="window.print(); return false;">
                        <i class="fas fa-print" aria-hidden="true"></i>
                        <span>Imprimer</span>
                    </a>
                    <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=export_rapports_csv<?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?>"
                        class="cm-btn is-light is-sm">
                        <i class="fas fa-file-csv" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </a>
                </div>
            </div>

            <form method="GET" action="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>" class="cm-rapport-admin-filter">
                <input type="hidden" name="page" value="<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="admin_telecharger_rapport">

                <div class="cm-form-group">
                    <label class="cm-form-label" for="search_rapport_admin">Recherche</label>
                    <input type="text" id="search_rapport_admin" name="search" class="cm-form-control"
                        value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nom, prenom, theme, rapport...">
                </div>

                <div class="cm-form-buttons">
                    <button type="submit" class="cm-btn is-primary is-sm">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>Filtrer</span>
                    </button>
                    <?php if ($searchTerm !== ''): ?>
                        <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=admin_telecharger_rapport"
                            class="cm-btn is-light is-sm">
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span>Reinitialiser</span>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <?php if (!empty($rapportsAdmin)): ?>
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th>Etudiant</th>
                                <th>Promotion</th>
                                <th>Date operation</th>
                                <th>Statut</th>
                                <th class="is-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapportsAdmin as $r): ?>
                                <?php
                                $badge = $getBadge($r->statut_rapport ?? '');
                                $nomComplet = trim((string) (($r->nom_etu ?? '') . ' ' . ($r->prenom_etu ?? '')));
                                $matricule = (string) ($r->num_carte_etud ?? $r->num_etu ?? '');
                                $numSelection = (string) ($r->num_etu ?? $matricule);
                                $dateOperationForm = $normalizeDateTimeLocal($r->date_operation ?? '', $dateSysteme);
                                $resolvedTheme = $resolveThemeValue($r->theme_rapport ?? '', $r->sujet_stage ?? '');
                                $prefillUrl = '?' . http_build_query([
                                    'page' => $basePage,
                                    'action' => 'admin_telecharger_rapport',
                                    'focus_upload' => '1',
                                    'prefill_existing_rapport' => '1',
                                    'prefill_num_etu' => $numSelection,
                                    'prefill_nom' => $nomComplet,
                                    'prefill_num_carte' => (string) ($r->num_carte_etud ?? ''),
                                    'prefill_num_ident' => (string) ($r->num_ident_etud ?? ''),
                                    'prefill_email' => (string) ($r->email_etu ?? ''),
                                    'prefill_promotion' => $formatPromotion($r->promotion_etu ?? ''),
                                    'prefill_annee' => $formatAcademicYear($r->id_annee_acad ?? ''),
                                    'prefill_candidature' => (string) ($r->statut_candidature ?? ''),
                                    'prefill_entreprise' => (string) ($r->entreprise_stage ?? ''),
                                    'prefill_sujet' => $resolvedTheme,
                                    'prefill_maitre' => (string) ($r->maitre_stage_nom ?? ''),
                                    'prefill_periode' => $formatStagePeriod($r->date_debut_stage ?? null, $r->date_fin_stage ?? null),
                                    'prefill_nb_rapports' => 'Rapport deja importe',
                                    'prefill_date_operation' => $dateOperationForm,
                                ]);
                                ?>
                                <tr>
                                    <td>
                                        <a
                                            href="<?= htmlspecialchars($prefillUrl, ENT_QUOTES, 'UTF-8') ?>"
                                            class="cm-rapport-admin-student-trigger js-rapport-student-trigger"
                                            data-num-etu="<?= htmlspecialchars($numSelection, ENT_QUOTES, 'UTF-8') ?>"
                                            data-nom="<?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?>"
                                            data-num-carte="<?= htmlspecialchars((string) ($r->num_carte_etud ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-num-ident="<?= htmlspecialchars((string) ($r->num_ident_etud ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-email="<?= htmlspecialchars((string) ($r->email_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-promotion="<?= htmlspecialchars($formatPromotion($r->promotion_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-annee="<?= htmlspecialchars($formatAcademicYear($r->id_annee_acad ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-candidature="<?= htmlspecialchars((string) ($r->statut_candidature ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-entreprise="<?= htmlspecialchars((string) ($r->entreprise_stage ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-sujet="<?= htmlspecialchars($resolvedTheme, ENT_QUOTES, 'UTF-8') ?>"
                                            data-maitre="<?= htmlspecialchars((string) ($r->maitre_stage_nom ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            data-periode="<?= htmlspecialchars($formatStagePeriod($r->date_debut_stage ?? null, $r->date_fin_stage ?? null), ENT_QUOTES, 'UTF-8') ?>"
                                            data-nb-rapports="<?= htmlspecialchars('Rapport deja importe', ENT_QUOTES, 'UTF-8') ?>"
                                            data-date-operation="<?= htmlspecialchars($dateOperationForm, ENT_QUOTES, 'UTF-8') ?>"
                                            data-existing-rapport="1">
                                            <strong><?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <small class="cm-rapport-admin-student-meta"><?= htmlspecialchars($formatText($matricule), ENT_QUOTES, 'UTF-8') ?></small>
                                            <span class="cm-rapport-admin-student-theme">
                                                <span class="cm-rapport-admin-student-theme-label">Theme :</span>
                                                <?= htmlspecialchars($formatText($resolvedTheme), ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </a>
                                    </td>
                                    <td><?= htmlspecialchars($formatPromotion($r->promotion_etu ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <input form="form_inline_<?= (int) ($r->id_rapport ?? 0) ?>" type="datetime-local" name="date_operation" value="<?= htmlspecialchars($dateOperationForm, ENT_QUOTES, 'UTF-8') ?>" class="cm-form-control is-sm" style="width: 100%;">
                                    </td>
                                    <td><span class="cm-rapport-admin-badge <?= htmlspecialchars($badge['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="is-right">
                                        <form id="form_inline_<?= (int) ($r->id_rapport ?? 0) ?>" method="POST" action="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>" style="display: inline-block;">
                                            <input type="hidden" name="action" value="update_rapport_inline">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenValue, ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id_rapport" value="<?= (int) ($r->id_rapport ?? 0) ?>">
                                            <input type="hidden" name="nom_rapport" value="<?= htmlspecialchars((string) ($r->nom_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="theme_rapport" value="<?= htmlspecialchars((string) ($r->theme_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                        </form>
                                        <div class="cm-rapport-admin-actions" style="justify-content: flex-end;">
                                            <?php if (!empty($r->id_rapport)): ?>
                                                <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=download_fichier_rapport&id=<?= (int) ($r->id_rapport ?? 0) ?>"
                                                    class="cm-btn is-light is-sm" title="Telecharger le PDF du rapport">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button form="form_inline_<?= (int) ($r->id_rapport ?? 0) ?>" type="submit" class="cm-btn is-primary is-sm" title="Enregistrer les modifications">
                                                <i class="fas fa-save" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="cm-rapport-admin-empty">Aucun rapport a afficher pour le moment.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        function initRapportAdmin(root) {
            if (!root || root.getAttribute('data-cm-rapport-admin-init') === '1') {
                return;
            }

            root.setAttribute('data-cm-rapport-admin-init', '1');

            var select = root.querySelector('#num_etu_select');
            var infoBox = root.querySelector('#etudiant_info');
            var uploadForm = root.querySelector('#admin_upload_rapport_form');
            var importLabel = root.querySelector('#admin_import_label');
            var importHint = root.querySelector('#admin_import_hint');
            var themeField = root.querySelector('#admin_theme_rapport');
            var fileField = root.querySelector('#admin_rapport_fichier');
            var dateOperationField = root.querySelector('#date_operation');
            var infoHeader = root.querySelector('#info_nom_header');

            function setText(id, value) {
                var node = root.querySelector('#' + id);
                if (!node) {
                    return;
                }

                node.textContent = value && String(value).trim() !== '' ? value : '-';
            }

            function updateStudentInfo() {
                if (!select || !infoBox || !importLabel || !importHint) {
                    return;
                }

                var option = select.options[select.selectedIndex];
                if (!option || !option.value) {
                    infoBox.style.display = 'none';
                    importLabel.textContent = 'Importer le rapport';
                    importHint.textContent = 'Choisissez d abord un etudiant.';
                    if (themeField) {
                        themeField.value = '';
                    }
                    return;
                }

                var nom = option.getAttribute('data-nom') || 'Etudiant selectionne';
                if (infoHeader) {
                    infoHeader.textContent = nom;
                }

                setText('info_matricule', option.getAttribute('data-num-carte'));
                setText('info_identifiant', option.getAttribute('data-num-ident'));
                setText('info_promotion', option.getAttribute('data-promotion'));
                setText('info_annee', option.getAttribute('data-annee'));
                setText('info_email', option.getAttribute('data-email'));
                setText('info_candidature', option.getAttribute('data-candidature'));
                setText('info_entreprise', option.getAttribute('data-entreprise'));
                setText('info_maitre_stage', option.getAttribute('data-maitre'));
                setText('info_stage_periode', option.getAttribute('data-periode'));
                setText('info_sujet_stage', option.getAttribute('data-sujet'));
                setText('info_nb_rapports', option.getAttribute('data-nb-rapports'));

                if (themeField) {
                    themeField.value = option.getAttribute('data-sujet') || '';
                }

                infoBox.style.display = 'block';

                if (option.getAttribute('data-existing-rapport') === '1') {
                    importLabel.textContent = 'Remplacer le rapport de ' + nom;
                    importHint.textContent = 'Le prochain fichier remplacera le rapport deja associe a ' + nom + '.';
                } else {
                    importLabel.textContent = 'Importer le rapport pour ' + nom;
                    importHint.textContent = 'Le fichier sera rattache au dossier de ' + nom + '.';
                }
            }

            function upsertOption(data) {
                if (!select || !data || !data.numEtu) {
                    return null;
                }

                var option = null;
                for (var index = 0; index < select.options.length; index += 1) {
                    if (select.options[index].value === data.numEtu) {
                        option = select.options[index];
                        break;
                    }
                }

                if (!option) {
                    option = document.createElement('option');
                    option.value = data.numEtu;
                    select.appendChild(option);
                }

                option.textContent = data.nom + ' (' + (data.numCarte || data.numEtu) + ')';
                option.selected = true;
                option.setAttribute('data-nom', data.nom || '');
                option.setAttribute('data-num-carte', data.numCarte || '');
                option.setAttribute('data-num-ident', data.numIdent || '');
                option.setAttribute('data-email', data.email || '');
                option.setAttribute('data-promotion', data.promotion || '');
                option.setAttribute('data-annee', data.annee || '');
                option.setAttribute('data-candidature', data.candidature || '');
                option.setAttribute('data-entreprise', data.entreprise || '');
                option.setAttribute('data-sujet', data.sujet || '');
                option.setAttribute('data-maitre', data.maitre || '');
                option.setAttribute('data-periode', data.periode || '');
                option.setAttribute('data-nb-rapports', data.nbRapports || '');
                option.setAttribute('data-existing-rapport', data.existingRapport ? '1' : '0');

                return option;
            }

            function focusForm(trigger) {
                if (themeField) {
                    themeField.value = trigger.getAttribute('data-sujet') || '';
                }

                if (dateOperationField) {
                    var clickedDate = trigger.getAttribute('data-date-operation') || '';
                    if (clickedDate !== '') {
                        dateOperationField.value = clickedDate;
                    }
                }

                updateStudentInfo();

                if (uploadForm) {
                    try {
                        uploadForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    } catch (error) {
                        uploadForm.scrollIntoView(true);
                    }
                }

                if (fileField) {
                    fileField.focus();
                }
            }

            if (select) {
                select.addEventListener('change', updateStudentInfo);
                updateStudentInfo();

                if (root.getAttribute('data-cm-rapport-admin-focus') === '1' && select.value) {
                    if (uploadForm) {
                        try {
                            uploadForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        } catch (error) {
                            uploadForm.scrollIntoView(true);
                        }
                    }

                    if (fileField) {
                        fileField.focus();
                    }
                }
            }

            root.addEventListener('click', function (event) {
                var target = event.target;
                if (!target || typeof target.closest !== 'function') {
                    return;
                }

                var trigger = target.closest('.js-rapport-student-trigger');
                if (!trigger || !root.contains(trigger)) {
                    return;
                }

                event.preventDefault();

                var option = upsertOption({
                    numEtu: trigger.getAttribute('data-num-etu') || '',
                    nom: trigger.getAttribute('data-nom') || 'Etudiant selectionne',
                    numCarte: trigger.getAttribute('data-num-carte') || '',
                    numIdent: trigger.getAttribute('data-num-ident') || '',
                    email: trigger.getAttribute('data-email') || '',
                    promotion: trigger.getAttribute('data-promotion') || '',
                    annee: trigger.getAttribute('data-annee') || '',
                    candidature: trigger.getAttribute('data-candidature') || '',
                    entreprise: trigger.getAttribute('data-entreprise') || '',
                    sujet: trigger.getAttribute('data-sujet') || '',
                    maitre: trigger.getAttribute('data-maitre') || '',
                    periode: trigger.getAttribute('data-periode') || '',
                    nbRapports: trigger.getAttribute('data-nb-rapports') || '',
                    existingRapport: true
                });

                if (!option || !select) {
                    return;
                }

                select.value = option.value;
                focusForm(trigger);
            });
        }

        function bootRapportAdmin() {
            var roots = document.querySelectorAll('.cm-rapport-admin-shell');
            for (var i = 0; i < roots.length; i += 1) {
                initRapportAdmin(roots[i]);
            }
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', bootRapportAdmin);
        }

        bootRapportAdmin();
    })();
</script>

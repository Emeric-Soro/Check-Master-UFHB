<?php
// permissions_helper déjà inclus par layout.php

$rapport = $GLOBALS['rapport'] ?? null;
$rapport = is_array($rapport) ? $rapport : [];
$isEditMode = $GLOBALS['isEditMode'] ?? false;
$contenuRapport = $GLOBALS['contenuRapport'] ?? '';
$contenuRapport = is_string($contenuRapport) ? $contenuRapport : '';
$erreurs = $GLOBALS['erreurs'] ?? [];
$erreurs = is_array($erreurs) ? $erreurs : [];
$rapportEstUpload = !empty($GLOBALS['rapportEstUpload']);
$rapportUploadChemin = (string) ($GLOBALS['rapportUploadChemin'] ?? '');

$isEditingExisting = $isEditMode && is_array($rapport) && !empty($rapport);
$isReadOnly = !empty($GLOBALS['rapportDejaDepose']) || $rapportEstUpload;

$numEtu = $_SESSION['num_etu'] ?? '';
$nomEtu = $_SESSION['nom_etu'] ?? '';
$prenomEtu = $_SESSION['prenom_etu'] ?? '';

if (($nomEtu === '' || $prenomEtu === '') && $numEtu !== '') {
    try {
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT nom_etu, prenom_etu FROM etudiants WHERE num_carte_etud = :num_etu LIMIT 1");
        $stmt->execute(['num_etu' => $numEtu]);
        $etudiantInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($etudiantInfo) {
            $nomEtu = $etudiantInfo['nom_etu'] ?? '';
            $prenomEtu = $etudiantInfo['prenom_etu'] ?? '';
            $_SESSION['nom_etu'] = $nomEtu;
            $_SESSION['prenom_etu'] = $prenomEtu;
        }
    } catch (Exception $e) {
        error_log('Erreur récupération nom étudiant : ' . $e->getMessage());
    }
}

$nomCompletEtu = trim($nomEtu . ' ' . $prenomEtu);
$stageInfo = $GLOBALS['stage_info'] ?? [];
$stageInfo = is_array($stageInfo) ? $stageInfo : [];

$rapportId = (string) ($rapport['id_rapport'] ?? '');
$nomRapportInitial = (string) ($rapport['nom_rapport'] ?? '');
$themeRapportInitial = (string) ($rapport['theme_rapport'] ?? '');
if ($themeRapportInitial === '' && isset($stageInfo['sujet_stage'])) {
    $themeRapportInitial = (string) $stageInfo['sujet_stage'];
}

$nomEntreprise = (string) ($stageInfo['nom_entreprise'] ?? '');
$encadrantNom = (string) ($stageInfo['nom_maitre_stage'] ?? ($stageInfo['encadrant_nom'] ?? ''));
$encadrantPrenom = (string) ($stageInfo['prenom_maitre_stage'] ?? ($stageInfo['encadrant_prenom'] ?? ''));
$maitreStage = trim($encadrantNom . ' ' . $encadrantPrenom);

$anneeAcademique = '';
$sessionYearCandidates = [
    $_SESSION['annee_academique'] ?? null,
    $_SESSION['annee_academique_libelle'] ?? null,
    $_SESSION['lib_annee_academique'] ?? null,
    $_SESSION['annee_active'] ?? null,
];
foreach ($sessionYearCandidates as $candidate) {
    if (is_string($candidate) && trim($candidate) !== '') {
        $anneeAcademique = trim($candidate);
        break;
    }
}
if ($anneeAcademique === '') {
    $year = (int) date('Y');
    $month = (int) date('n');
    $start = $month >= 9 ? $year : ($year - 1);
    $end = $start + 1;
    $anneeAcademique = $start . ' - ' . $end;
}

$documentName = $nomRapportInitial !== ''
    ? $nomRapportInitial
    : ($themeRapportInitial !== '' ? $themeRapportInitial : 'Rapport de stage');
$studentLabel = $nomCompletEtu !== '' ? $nomCompletEtu : 'Etudiant non renseigné';
$themeLabel = $themeRapportInitial !== '' ? $themeRapportInitial : 'Thème non renseigné';
$mentorLabel = $maitreStage !== '' ? $maitreStage : 'Maître de stage non renseigné';
$companyLabel = $nomEntreprise !== '' ? $nomEntreprise : '';
$statusLabel = $rapportEstUpload ? 'Téléversé' : ($isReadOnly ? 'Déposé' : ($isEditingExisting ? 'Brouillon' : 'Nouveau'));
$mentorWithCompany = $mentorLabel . ($companyLabel !== '' ? ' (' . $companyLabel . ')' : '');

if (!function_exists('cm_etu_escape')) {
    function cm_etu_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('cm_etu_image_data_uri')) {
    function cm_etu_image_data_uri(string $path): string
    {
        if (!is_file($path) || !is_readable($path)) {
            return '';
        }
        $binary = file_get_contents($path);
        if ($binary === false) {
            return '';
        }
        $mime = function_exists('mime_content_type') ? (string) mime_content_type($path) : 'image/png';
        if ($mime === '' || !str_starts_with($mime, 'image/')) {
            $mime = 'image/png';
        }
        return 'data:' . $mime . ';base64,' . base64_encode($binary);
    }
}

$logoBasePath = __DIR__ . '/../../../public/image/';
$logoUfhbData = cm_etu_image_data_uri($logoBasePath . 'logo_ufhb.png');
$logoCivData = cm_etu_image_data_uri($logoBasePath . 'logo_civ.png');
$logoCmData = cm_etu_image_data_uri($logoBasePath . 'logoCM.png');

$editorMeta = [
    'studentName' => $studentLabel,
    'studentNumber' => (string) $numEtu,
    'theme' => $themeRapportInitial,
    'mentor' => $mentorLabel,
    'company' => $companyLabel,
    'documentName' => $documentName,
    'academicYear' => $anneeAcademique,
    'ufhbLogo' => $logoUfhbData,
    'civLogo' => $logoCivData,
    'cmLogo' => $logoCmData,
];
$jsFlags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT;

// Garantir un encodage JSON valide même en présence de données corrompues
$editorMetaJson = json_encode($editorMeta, $jsFlags);
if ($editorMetaJson === false) {
    $editorMetaJson = json_encode(array_map(function ($v) {
        return is_string($v) ? @iconv('UTF-8', 'UTF-8//IGNORE', $v) : $v;
    }, $editorMeta), $jsFlags);
    if ($editorMetaJson === false) {
        $editorMetaJson = '{}';
    }
}
$contenuRapportJson = json_encode($contenuRapport, $jsFlags);
if ($contenuRapportJson === false) {
    $contenuRapportJson = '""';
}
?>
<style>
/* ── Focus Mode: 3-zone layout ── */
.fm-wrapper {
    display: flex;
    flex-direction: column;
    height: calc(100vh - 60px);
    background: #DFF2FF;
    overflow: hidden;
}

/* ── ZONE A: Header fixe ── */
.fm-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.65rem 1.25rem;
    background: linear-gradient(135deg, #0b3954 0%, #1a5276 100%);
    color: #fff;
    flex-shrink: 0;
    z-index: 10;
    box-shadow: 0 2px 12px rgba(11,57,84,0.25);
}
.fm-header__left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    min-width: 0;
}
.fm-back-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 36px;
    height: 36px;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.08);
    color: #fff;
    text-decoration: none;
    transition: background 0.2s;
    flex-shrink: 0;
}
.fm-back-btn:hover {
    background: rgba(255,255,255,0.18);
}
.fm-header__title {
    font-size: 1rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
    white-space: nowrap;
}
.fm-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.3rem;
    padding: 0.25rem 0.7rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.03em;
    text-transform: uppercase;
    white-space: nowrap;
}
.fm-badge.is-warning {
    background: rgba(243,156,18,0.2);
    color: #f9d423;
}
.fm-badge.is-success {
    background: rgba(39,174,96,0.2);
    color: #6fec97;
}
.fm-badge.is-info {
    background: rgba(52,152,219,0.2);
    color: #7ec8f8;
}
.fm-header__right {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    flex-shrink: 0;
}
.fm-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 1rem;
    border-radius: 10px;
    border: none;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: opacity 0.2s, transform 0.1s;
    white-space: nowrap;
}
.fm-btn:active {
    transform: scale(0.97);
}
.fm-btn:disabled {
    opacity: 0.55;
    cursor: not-allowed;
}
.fm-btn.is-save {
    background: #3498db;
    color: #fff;
}
.fm-btn.is-save:hover:not(:disabled) {
    background: #2980b9;
}
.fm-btn.is-deposit {
    background: #27ae60;
    color: #fff;
}
.fm-btn.is-deposit:hover:not(:disabled) {
    background: #219a52;
}

/* ── ZONE B: Corps central (scrollable) ── */
.fm-body {
    flex: 1 1 0;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0;
}

/* Info panel compact */
.fm-info-panel {
    display: flex;
    align-items: stretch;
    gap: 0;
    padding: 0.6rem 1.25rem;
    background: #fff;
    border-bottom: 1px solid rgba(26,82,118,0.1);
    flex-shrink: 0;
}
.fm-info-item {
    display: flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0 1rem;
    font-size: 0.82rem;
    color: #334155;
    line-height: 1.4;
    border-right: 1px solid rgba(26,82,118,0.1);
}
.fm-info-item:first-child {
    padding-left: 0;
}
.fm-info-item:last-child {
    border-right: none;
}
.fm-info-item i {
    color: #1a5276;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.fm-info-item .fm-info-label {
    color: #64748b;
    font-weight: 600;
    text-transform: uppercase;
    font-size: 0.7rem;
    letter-spacing: 0.04em;
    margin-right: 0.3rem;
}

/* Title input */
.fm-title-bar {
    display: flex;
    align-items: center;
    padding: 0.5rem 1.25rem;
    background: #f0f7ff;
    border-bottom: 1px solid rgba(26,82,118,0.1);
    flex-shrink: 0;
}
.fm-title-bar label {
    font-size: 0.72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #1a5276;
    margin-right: 0.75rem;
    white-space: nowrap;
}
.fm-title-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 1.05rem;
    font-weight: 600;
    color: #0f172a;
    outline: none;
    padding: 0.4rem 0;
    font-family: inherit;
}
.fm-title-input:focus {
    border-bottom: 2px solid #3498db;
}
.fm-title-input:read-only {
    color: #64748b;
    cursor: default;
}

/* Read-only banner */
.fm-readonly-banner {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    padding: 0.55rem 1.25rem;
    background: #fff8eb;
    border-bottom: 1px solid rgba(243,156,18,0.2);
    color: #8b5b08;
    font-size: 0.82rem;
    flex-shrink: 0;
}


/* Editor zone */
.fm-editor-zone {
    flex: 1 1 0;
    min-height: 0;
    display: flex;
    flex-direction: column;
}
.fm-editor-zone .jodit-container:not(.jodit_inline) {
    border: 0 !important;
    flex: 1 1 0;
    display: flex;
    flex-direction: column;
}
.fm-editor-zone .jodit-toolbar__box,
.fm-editor-zone .jodit-toolbar-editor-collection {
    background: #f6fbff !important;
    border-bottom: 1px solid rgba(26,82,118,0.12) !important;
}
.fm-editor-zone .jodit-workplace {
    flex: 1 1 0;
    background: linear-gradient(180deg, #f0f5fa 0%, #e8eff6 100%);
}
.fm-editor-zone .jodit-wysiwyg {
    min-height: 500px !important;
    padding: 1.4rem !important;
    background: transparent !important;
    font-family: 'Times New Roman', serif !important;
    font-size: 12pt !important;
    line-height: 1.65 !important;
    color: #111827 !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
}
.fm-editor-zone .jodit-wysiwyg > [data-cm-report-document="1"] {
    max-width: 210mm;
    width: 100%;
    margin: 0 auto;
}
.fm-editor-zone .jodit-status-bar {
    display: none !important;
}

/* ── ZONE C: Footer fixe ── */
.fm-footer {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.45rem 1.25rem;
    background: #fff;
    border-top: 1px solid rgba(26,82,118,0.12);
    flex-shrink: 0;
    z-index: 10;
    box-shadow: 0 -2px 8px rgba(11,57,84,0.06);
}
.fm-footer__left,
.fm-footer__right {
    font-size: 0.78rem;
    color: #64748b;
}
.fm-footer__center {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.fm-btn-preview {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.85rem;
    border-radius: 8px;
    border: 1px solid #3498db;
    background: transparent;
    color: #3498db;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.fm-btn-preview:hover {
    background: #3498db;
    color: #fff;
}
.fm-btn-download {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.85rem;
    border-radius: 8px;
    border: 1px solid #27ae60;
    background: transparent;
    color: #27ae60;
    font-size: 0.78rem;
    font-weight: 600;
    cursor: pointer;
    transition: background 0.2s, color 0.2s;
}
.fm-btn-download:hover {
    background: #27ae60;
    color: #fff;
}
.fm-footer__right .fm-save-status {
    color: #27ae60;
    font-weight: 500;
}

/* PDF loading overlay */
.fm-pdf-loading {
    position: fixed;
    inset: 0;
    display: none;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 1rem;
    background: rgba(10,23,34,0.74);
    z-index: 1100;
    color: #fff;
    text-align: center;
}
.fm-pdf-loading.is-visible {
    display: flex;
}
.fm-pdf-loading__spinner {
    width: 48px;
    height: 48px;
    border-radius: 999px;
    border: 3px solid rgba(255,255,255,0.2);
    border-top-color: #fff;
    animation: fm-spin 0.8s linear infinite;
}
@keyframes fm-spin {
    to { transform: rotate(360deg); }
}

/* Notification toasts */
#fmNotifications {
    position: fixed;
    top: 1rem;
    right: 1rem;
    z-index: 9999;
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
}

/* Responsive */
@media (max-width: 768px) {
    .fm-header {
        flex-wrap: wrap;
        padding: 0.5rem 0.75rem;
    }
    .fm-header__title {
        font-size: 0.85rem;
    }
    .fm-info-panel {
        flex-direction: column;
        gap: 0.3rem;
        padding: 0.5rem 0.75rem;
    }
    .fm-info-item {
        border-right: none;
        border-bottom: 1px solid rgba(26,82,118,0.06);
        padding: 0.3rem 0;
    }
    .fm-title-bar {
        flex-direction: column;
        align-items: flex-start;
        gap: 0.3rem;
        padding: 0.5rem 0.75rem;
    }
    .fm-footer {
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
        padding: 0.4rem 0.75rem;
    }
}
</style>

<!-- Hidden form for AJAX submissions -->
<style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#rapportForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form id="rapportForm" method="POST" action="?page=gestion_rapports" style="display:none;">
    <input type="hidden" name="action" value="save_rapport">
    <?php if ($isEditingExisting): ?>
        <input type="hidden" name="edit_id" value="<?= cm_etu_escape($rapportId) ?>">
    <?php endif; ?>
    <input type="hidden" id="nom_rapport_hidden" name="nom_rapport" value="<?= cm_etu_escape($documentName) ?>">
    <input type="hidden" id="theme_rapport_hidden" name="theme_rapport" value="<?= cm_etu_escape($themeLabel) ?>">
    <input type="hidden" id="contenu_rapport" name="contenu_rapport">
    <input type="hidden" id="payloadReportId" name="id_rapport" value="<?= cm_etu_escape($rapportId) ?>">
</form>

<div class="fm-wrapper">
    <!-- ═══ ZONE A: Header ═══ -->
    <header class="fm-header">
        <div class="fm-header__left">
            <a href="?page=gestion_rapports" class="fm-back-btn" title="Retour à la liste">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span class="fm-header__title"><?= $rapportEstUpload ? 'Consultation du rapport' : 'Rédaction du rapport' ?></span>
            <span class="fm-badge <?= $rapportEstUpload ? 'is-info' : ($isReadOnly ? 'is-success' : ($isEditingExisting ? 'is-warning' : 'is-info')) ?>">
                <i class="fas <?= $rapportEstUpload ? 'fa-cloud-upload-alt' : ($isReadOnly ? 'fa-lock' : ($isEditingExisting ? 'fa-pen' : 'fa-plus')) ?>"></i>
                <?= cm_etu_escape($statusLabel) ?>
            </span>
        </div>
        <div class="fm-header__right">
            <?php if (!$isReadOnly): ?>
                <?php if (!$rapportEstUpload): ?>
                    <button id="saveBtn" type="button" class="fm-btn is-save">
                        <i class="fas fa-save"></i> Enregistrer
                    </button>
                <?php endif; ?>
                <button id="deposerBtn" type="button" class="fm-btn is-deposit">
                    <i class="fas fa-paper-plane"></i> Déposer
                </button>
            <?php endif; ?>
        </div>
    </header>

    <!-- ═══ ZONE B: Corps central ═══ -->
    <div class="fm-body">
        <?php if ($rapportEstUpload): ?>
            <div class="fm-readonly-banner">
                <i class="fas fa-cloud-upload-alt"></i>
                <span>Rapport téléversé — le document original est affiché ci-dessous.</span>
            </div>
        <?php elseif ($isReadOnly): ?>
            <div class="fm-readonly-banner">
                <i class="fas fa-lock"></i>
                <span>Mode consultation — le rapport a été déposé et ne peut plus être modifié.</span>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['success'])): ?>
            <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $_SESSION['success']]); ?>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($erreurs)): ?>
            <?php foreach ($erreurs as $e): ?>
                <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $e]); ?>
            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Info panel compact -->
        <div class="fm-info-panel">
            <div class="fm-info-item">
                <i class="fas fa-user-graduate"></i>
                <span class="fm-info-label">Étudiant</span>
                <span><?= cm_etu_escape($studentLabel) ?> <?= $numEtu !== '' ? '(' . cm_etu_escape($numEtu) . ')' : '' ?></span>
            </div>
            <div class="fm-info-item">
                <i class="fas fa-user-tie"></i>
                <span class="fm-info-label">Maître</span>
                <span><?= cm_etu_escape($mentorWithCompany) ?></span>
            </div>
        </div>

        <!-- Titre officiel editable -->
        <div class="fm-title-bar">
            <label for="reportTitleInput">Titre officiel</label>
            <input type="text" id="reportTitleInput" class="fm-title-input"
                   placeholder="Saisissez le thème du rapport..."
                   value="<?= cm_etu_escape($themeLabel) ?>"
                   <?= ($isReadOnly || $rapportEstUpload) ? 'readonly' : '' ?>>
        </div>

        <?php if ($rapportEstUpload): ?>
            <!-- Visualiseur du fichier téléversé -->
            <div class="fm-editor-zone">
                <?php
                $uploadExt = strtolower(pathinfo($rapportUploadChemin, PATHINFO_EXTENSION));
                $downloadUrl = '?page=gestion_rapports&action=download_fichier_rapport&id=' . ($rapport['id_rapport'] ?? 0);
                if ($uploadExt === 'pdf'): ?>
                    <iframe src="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>"
                            style="width:100%;height:calc(100vh - 220px);border:none;border-radius:8px;" frameborder="0"
                            title="Aperçu du rapport PDF"></iframe>
                <?php else: ?>
                    <div style="text-align:center;padding:80px 20px;">
                        <i class="fas fa-file-word" style="font-size:3rem;color:#2b6cb0;margin-bottom:20px;display:block;"></i>
                        <p style="color:#4a5568;font-size:1.1rem;margin-bottom:8px;">
                            Document <?= htmlspecialchars(strtoupper($uploadExt), ENT_QUOTES, 'UTF-8') ?> téléversé
                        </p>
                        <p style="color:#718096;font-size:0.85rem;margin-bottom:24px;">
                            <?= htmlspecialchars($rapportUploadChemin, ENT_QUOTES, 'UTF-8') ?>
                        </p>
                        <a href="<?= htmlspecialchars($downloadUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-primary" style="display:inline-flex;">
                            <i class="fas fa-download"></i> Télécharger le document
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <!-- Editeur WYSIWYG -->
            <div class="fm-editor-zone">
                <textarea id="jodit-editor"></textarea>
            </div>
        <?php endif; ?>
    </div>

    <!-- ═══ ZONE C: Footer ═══ -->
    <footer class="fm-footer">
        <div class="fm-footer__left">
            <?php if (!$rapportEstUpload): ?>
                <span id="wordCount">0 mots</span>
            <?php endif; ?>
        </div>
        <div class="fm-footer__center">
            <?php if (!$rapportEstUpload): ?>
                <button id="previewPdfBtn" type="button" class="fm-btn-preview" title="Ouvrir l'aperçu PDF dans un nouvel onglet">
                    <i class="fas fa-eye"></i> Aperçu PDF
                </button>
                <button id="downloadPdfBtn" type="button" class="fm-btn-download" title="Télécharger le PDF officiel">
                    <i class="fas fa-file-pdf"></i> Télécharger
                </button>
            <?php else: ?>
                <?php
                $uploadExt = strtolower(pathinfo($rapportUploadChemin, PATHINFO_EXTENSION));
                $dlUrl = '?page=gestion_rapports&action=download_fichier_rapport&id=' . ($rapport['id_rapport'] ?? 0);
                ?>
                <a href="<?= htmlspecialchars($dlUrl, ENT_QUOTES, 'UTF-8') ?>" class="fm-btn-download" style="text-decoration:none;display:inline-flex;align-items:center;gap:6px;">
                    <i class="fas fa-download"></i> Télécharger le fichier
                </a>
            <?php endif; ?>
        </div>
        <div class="fm-footer__right">
            <span id="saveStatus" class="fm-save-status"><?= $rapportEstUpload ? 'Document téléversé' : ($isReadOnly ? 'Lecture seule' : 'Auto-save toutes les 60s') ?></span>
        </div>
    </footer>
</div>

<!-- PDF loading overlay -->
<div id="pdfLoading" class="fm-pdf-loading">
    <div class="fm-pdf-loading__spinner"></div>
    <p>Génération du PDF en cours…</p>
</div>

<!-- Notification container -->
<div id="fmNotifications"></div>

<script>
(function () {
function initRapportEditorPage() {
    var isReadOnly = <?= $isReadOnly ? 'true' : 'false' ?>;
    var isUploadedFile = <?= $rapportEstUpload ? 'true' : 'false' ?>;
    var editorMeta = <?= $editorMetaJson ?>;
    var rawContent = <?= $contenuRapportJson ?>;
    if (typeof editorMeta !== 'object' || editorMeta === null) editorMeta = {};
    if (typeof rawContent !== 'string') rawContent = '';

    var rapportForm = document.getElementById('rapportForm');
    var saveBtn = document.getElementById('saveBtn');
    var deposerBtn = document.getElementById('deposerBtn');
    var previewPdfBtn = document.getElementById('previewPdfBtn');
    var downloadPdfBtn = document.getElementById('downloadPdfBtn');
    var titleInput = document.getElementById('reportTitleInput');
    var wordCountEl = document.getElementById('wordCount');
    var saveStatusEl = document.getElementById('saveStatus');
    var pdfLoading = document.getElementById('pdfLoading');
    var reportEndpoint = window.location.pathname + '?page=gestion_rapports';

    // Pour les fichiers uploadés, on ne initialise pas l'éditeur Jodit
    if (isUploadedFile) {
        // Configurer uniquement le dépôt si le bouton existe
        if (rapportForm) {
            rapportForm.setAttribute('action', reportEndpoint);
        }
        if (deposerBtn) {
            deposerBtn.addEventListener('click', function (event) {
                event.preventDefault();
                if (deposerBtn.disabled) return;
                requestDepositConfirmation()
                    .then(function (confirmed) {
                        if (!confirmed) return null;
                        deposerBtn.disabled = true;
                        var reportId = getEditId();
                        if (!reportId) return null;
                        applyFormPayload('deposer_rapport');
                        syncReportId(reportId);
                        rapportForm.submit();
                        return null;
                    })
                    .catch(function (error) {
                        deposerBtn.disabled = false;
                        showNotification('error', error.message || 'Erreur lors du dépôt.');
                    });
            });
        }
        return;
    }
    var editorTextarea = document.getElementById('jodit-editor');
    var joditEditor = null;
    var fallbackEditorMode = false;

    if (window.__cmRapportEditorReady) return;
    window.__cmRapportEditorReady = true;

    if (rapportForm) {
        rapportForm.setAttribute('action', reportEndpoint);
    }

    console.log('[init] Elements found:', {
        rapportForm: !!rapportForm,
        saveBtn: !!saveBtn,
        deposerBtn: !!deposerBtn,
        previewPdfBtn: !!previewPdfBtn,
        downloadPdfBtn: !!downloadPdfBtn,
        titleInput: !!titleInput,
        isReadOnly: isReadOnly
    });

    var requiresMigrationSave = false;
    var saveInFlight = null;
    var lastSnapshot = '';
    var activePreviewUrl = null;

    /* ── Helpers ── */

    function escapeHtml(text) {
        var div = document.createElement('div');
        div.textContent = text == null ? '' : String(text);
        return div.innerHTML;
    }

    function nowLabel() {
        return new Date().toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
    }

    function buildDocumentName() {
        var v = String(titleInput.value || '').trim();
        if (v !== '') return v;
        var t = String(editorMeta.theme || '').trim();
        return t !== '' ? t : 'Rapport de stage';
    }

    function buildThemeValue() {
        var v = String(titleInput.value || '').trim();
        return v !== '' ? v : buildDocumentName();
    }

    function getCsrfToken() {
        if (rapportForm) {
            var tokenInput = rapportForm.querySelector('input[name="csrf_token"]');
            if (tokenInput && tokenInput.value) return tokenInput.value;
        }
        var anyToken = document.querySelector('input[name="csrf_token"]');
        return anyToken && anyToken.value ? anyToken.value : '';
    }

    function editorGetValue() {
        if (joditEditor) return joditEditor.value || '';
        return editorTextarea ? String(editorTextarea.value || '') : '';
    }

    function editorSetValue(value) {
        var html = String(value || '');
        if (joditEditor) {
            joditEditor.value = html;
            return;
        }
        if (editorTextarea) {
            editorTextarea.value = html;
        }
    }

    function editorOnChange(handler) {
        if (joditEditor && joditEditor.events && typeof joditEditor.events.on === 'function') {
            joditEditor.events.on('change', handler);
            return;
        }
        if (editorTextarea) {
            editorTextarea.addEventListener('input', handler);
        }
    }

    /* ── Cover page HTML builder ── */

    function buildCoverSectionHTML() {
        var meta = editorMeta || {};
        var theme = String(titleInput.value || meta.theme || 'Thème du rapport');
        var studentName = String(meta.studentName || 'Étudiant');
        var mentor = String(meta.mentor || 'Maître de stage');
        var company = String(meta.company || '');
        var academicYear = String(meta.academicYear || '');
        var studentNumber = String(meta.studentNumber || '');
        var companyLogos = '';

        if (meta.civLogo) {
            companyLogos += '<img src="' + meta.civLogo + '" alt="Armoiries" style="max-width:68px; width:68px; height:auto; display:inline-block; vertical-align:middle;">';
        }
        if (meta.cmLogo) {
            companyLogos += '<img src="' + meta.cmLogo + '" alt="Logo CM" style="max-width:64px; width:64px; height:auto; display:inline-block; vertical-align:middle; margin-left:12px;">';
        }

        return '' +
            '<section class="cm-report-cover-page" data-cm-report-cover="1" style="font-family:\'Times New Roman\', serif; width:210mm; min-height:297mm; padding:20mm 22mm 18mm; box-sizing:border-box; background:#ffffff; color:#111827;">' +
                '<table style="width:100%; border:none; margin-bottom:8mm; border-collapse:collapse;">' +
                    '<tr>' +
                        '<td style="width:50%; vertical-align:top; border:none; padding:0; font-size:10pt; line-height:1.45;">MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR<br>ET DE LA RECHERCHE SCIENTIFIQUE</td>' +
                        '<td style="width:50%; vertical-align:top; border:none; padding:0; font-size:10pt; line-height:1.45; text-align:right;">REPUBLIQUE DE COTE D\'IVOIRE<br>UNION - DISCIPLINE - TRAVAIL</td>' +
                    '</tr>' +
                '</table>' +
                '<table style="width:100%; border:none; margin:0 0 12mm; border-collapse:collapse;">' +
                    '<tr>' +
                        '<td style="width:50%; vertical-align:top; border:none; padding:0 10mm 0 0; text-align:center;">' +
                            (meta.ufhbLogo ? '<img src="' + meta.ufhbLogo + '" alt="Logo UFHB" style="max-width:72px; width:72px; height:auto; display:block; margin:0 auto 10px;">' : '') +
                            '<div style="font-size:11pt; font-weight:bold; color:#0f4666; line-height:1.5;">UNIVERSITE FELIX HOUPHOUET BOIGNY</div>' +
                            '<div style="font-size:10pt; line-height:1.55; margin-top:6px;">UFR MATHEMATIQUES ET INFORMATIQUE<br>FILIERES PROFESSIONNALISEES MIAGE-GI</div>' +
                        '</td>' +
                        '<td style="width:50%; vertical-align:top; border:none; padding:0 0 0 10mm; text-align:center;">' +
                            '<div style="margin-bottom:10px;">' + companyLogos + '</div>' +
                            (company ? '<div style="font-size:11pt; font-weight:bold; line-height:1.5; color:#0f172a;">' + escapeHtml(company.toUpperCase()) + '</div>' : '') +
                        '</td>' +
                    '</tr>' +
                '</table>' +
                '<div style="text-align:center; margin:0 0 10mm;">' +
                    '<p style="margin:0 0 6px; font-size:11pt;">RAPPORT DE STAGE POUR L\'OBTENTION DU</p>' +
                    '<p style="margin:0; font-size:13pt; font-weight:bold; font-style:italic; color:#0f4666;">Diplôme d\'Ingénieur de conception en informatique</p>' +
                    '<p style="margin:6px 0 0; font-size:10pt; font-style:italic;">Option Méthodes Informatiques Appliquées à la Gestion des Entreprises</p>' +
                '</div>' +
                '<div data-cm-theme-block="1" style="margin:0 auto 12mm; border-radius:18px; border:2px solid #0f4666; background:#f6fbff; padding:10mm 9mm; text-align:center;">' +
                    '<p style="margin:0 0 8px; font-size:11pt; font-weight:bold; text-transform:uppercase; letter-spacing:0.04em; color:#0f4666;">Thème</p>' +
                    '<p data-cm-theme-text="1" style="margin:0; font-size:14pt; font-weight:bold; line-height:1.6; text-transform:uppercase;">' + escapeHtml(theme.toUpperCase()) + '</p>' +
                '</div>' +
                '<table style="width:100%; border-collapse:collapse; margin-top:12mm;">' +
                    '<tr>' +
                        '<td style="width:50%; border:1.5px solid #111827; padding:12mm 8mm; vertical-align:top; text-align:center;">' +
                            '<p style="margin:0 0 8px; font-size:10.5pt; font-weight:bold;">SOUTENU PAR</p>' +
                            '<p style="margin:0; font-size:12pt; font-weight:bold; line-height:1.6;">' + escapeHtml(studentName.toUpperCase()) + '</p>' +
                            (studentNumber ? '<p style="margin:6px 0 0; font-size:10pt;">Matricule : ' + escapeHtml(studentNumber) + '</p>' : '') +
                        '</td>' +
                        '<td style="width:50%; border:1.5px solid #111827; padding:12mm 8mm; vertical-align:top; text-align:center;">' +
                            '<p style="margin:0 0 8px; font-size:10.5pt; font-weight:bold;">MAITRE DE STAGE</p>' +
                            '<p style="margin:0; font-size:12pt; font-weight:bold; line-height:1.6;">' + escapeHtml(mentor.toUpperCase()) + '</p>' +
                            (company ? '<p style="margin:6px 0 0; font-size:10pt;">' + escapeHtml(company) + '</p>' : '') +
                        '</td>' +
                    '</tr>' +
                '</table>' +
                '<div style="margin-top:14mm; text-align:center; font-size:10pt; color:#475569;">Année académique ' + escapeHtml(academicYear) + '</div>' +
            '</section>';
    }

    function getDefaultBodyContent() {
        return '' +
            '<h1 style="font-family:\'Times New Roman\', serif; font-size:15pt; text-align:center; margin:0 0 16px; color:#0f4666;">INTRODUCTION</h1>' +
            '<p style="margin:0 0 14px; text-align:justify; text-indent:50px;">Présentez le contexte général de votre stage…</p>' +
            '<h2 style="font-family:\'Times New Roman\', serif; font-size:13pt; margin:24px 0 12px; color:#0f4666;">I. PRÉSENTATION DU CADRE DE RÉFÉRENCE</h2>' +
            '<p style="margin:0 0 14px; text-align:justify; text-indent:50px;">Décrivez l\'entreprise, son organisation, ses activités…</p>' +
            '<h2 style="font-family:\'Times New Roman\', serif; font-size:13pt; margin:24px 0 12px; color:#0f4666;">II. PROBLÉMATIQUE ET OBJECTIFS</h2>' +
            '<p style="margin:0 0 14px; text-align:justify; text-indent:50px;">Expliquez la problématique métier ou technique…</p>' +
            '<h2 style="font-family:\'Times New Roman\', serif; font-size:13pt; margin:24px 0 12px; color:#0f4666;">III. DÉMARCHE ET RÉALISATIONS</h2>' +
            '<p style="margin:0 0 14px; text-align:justify; text-indent:50px;">Présentez la méthode de travail adoptée…</p>' +
            '<h2 style="font-family:\'Times New Roman\', serif; font-size:13pt; margin:24px 0 12px; color:#0f4666;">IV. BILAN ET PERSPECTIVES</h2>' +
            '<p style="margin:0 0 14px; text-align:justify; text-indent:50px;">Concluez en mettant en avant les acquis…</p>';
    }

    /* ── Document assembly ── */

    function extractLegacyBodyContent(rawHtml) {
        if (!rawHtml) return '';
        var temp = document.createElement('div');
        temp.innerHTML = rawHtml;
        var bodySection = temp.querySelector('[data-cm-report-body="1"]');
        if (bodySection) return bodySection.innerHTML.trim();
        var pageBreaks = temp.querySelectorAll('div[style*="page-break"], .cm-report-page-break, [data-cm-report-page-break="1"]');
        if (pageBreaks.length > 0) {
            var frags = [];
            var cur = pageBreaks[pageBreaks.length - 1].nextSibling;
            while (cur) {
                if (cur.nodeType === 1) frags.push(cur.outerHTML);
                else if (cur.nodeType === 3 && cur.textContent.trim() !== '') frags.push(cur.textContent);
                cur = cur.nextSibling;
            }
            var after = frags.join('').trim();
            if (after !== '') return after;
        }
        return temp.innerHTML.trim();
    }

    function buildDocumentHtml(bodyHtml) {
        return '' +
            '<div class="cm-report-editor-document" data-cm-report-document="1" style="background:#ffffff; color:#111827;">' +
                buildCoverSectionHTML() +
                '<div class="cm-report-page-break" data-cm-report-page-break="1" style="page-break-before:always; break-before:page; border-top:2px dashed #cbd5e1; margin:18px 0;"></div>' +
                '<section class="cm-report-body-page" data-cm-report-body="1" style="font-family:\'Times New Roman\', serif; width:210mm; min-height:297mm; padding:18mm 20mm 20mm; box-sizing:border-box; background:#ffffff; color:#111827;">' +
                    (bodyHtml && bodyHtml.trim() !== '' ? bodyHtml : getDefaultBodyContent()) +
                '</section>' +
            '</div>';
    }

    function isUnifiedDocumentHtml(html) {
        return String(html || '').indexOf('data-cm-report-document="1"') !== -1;
    }

    function normalizeDocumentHtml(html) {
        if (isUnifiedDocumentHtml(html)) return String(html || '');
        return buildDocumentHtml(extractLegacyBodyContent(String(html || '')) || getDefaultBodyContent());
    }

    /* ── Metrics ── */

    function extractPlainText(html) {
        var probe = document.createElement('div');
        probe.innerHTML = html;
        return String(probe.textContent || probe.innerText || '').replace(/\s+/g, ' ').trim();
    }

    function getBodyHtmlForMetrics(html) {
        var probe = document.createElement('div');
        probe.innerHTML = normalizeDocumentHtml(html || '');
        var bodySection = probe.querySelector('[data-cm-report-body="1"]');
        return bodySection ? bodySection.innerHTML : probe.innerHTML;
    }

    function updateWordCount() {
        var text = extractPlainText(getBodyHtmlForMetrics(editorGetValue()));
        var words = text === '' ? 0 : text.split(' ').filter(Boolean).length;
        wordCountEl.textContent = words.toLocaleString('fr-FR') + ' mots';
    }

    /* ── Title <-> Editor sync ── */

    function syncTitleToEditor() {
        if (isReadOnly) return;
        var html = editorGetValue();
        var placeholder = 'data-cm-theme-text="1"';
        if (html.indexOf(placeholder) !== -1) {
            var titleVal = (titleInput.value || 'Thème du rapport').toUpperCase();
            html = html.replace(/(<p[^>]*data-cm-theme-text="1"[^>]*>)([^<]*?)(<\/p>)/g, '$1' + escapeHtml(titleVal) + '$3');
            editorSetValue(html);
        }
    }

    function syncEditorToTitle() {
        // Not needed for now
    }

    /* ── Persistence ── */

    function snapshot() {
        return JSON.stringify({ html: normalizeDocumentHtml(editorGetValue()) });
    }

    function getEditId() {
        var editIdField = rapportForm.querySelector('input[name="edit_id"]');
        if (editIdField && editIdField.value) return editIdField.value;
        var payloadField = document.getElementById('payloadReportId');
        if (payloadField && payloadField.value) return payloadField.value;
        return new URL(window.location.href).searchParams.get('edit');
    }

    function syncReportId(reportId) {
        if (!reportId) return;
        var editIdField = rapportForm.querySelector('input[name="edit_id"]');
        if (!editIdField) {
            editIdField = document.createElement('input');
            editIdField.type = 'hidden';
            editIdField.name = 'edit_id';
            rapportForm.appendChild(editIdField);
        }
        editIdField.value = reportId;
        document.getElementById('payloadReportId').value = reportId;
        var currentUrl = new URL(window.location.href);
        currentUrl.searchParams.set('edit', reportId);
        window.history.replaceState({}, '', currentUrl.toString());
    }

    function applyFormPayload(action) {
        rapportForm.querySelector('input[name="action"]').value = action;
        document.getElementById('nom_rapport_hidden').value = buildDocumentName();
        document.getElementById('theme_rapport_hidden').value = buildThemeValue();
        document.getElementById('contenu_rapport').value = normalizeDocumentHtml(editorGetValue());
        var csrfInput = rapportForm.querySelector('input[name="csrf_token"]');
        if (!csrfInput) {
            var csrfToken = getCsrfToken();
            if (csrfToken) {
                csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = csrfToken;
                rapportForm.appendChild(csrfInput);
            }
        }
    }

    function persistDraft(options) {
        options = options || {};
        if (isReadOnly) return Promise.resolve(getEditId());
        if (saveInFlight) return saveInFlight;

        applyFormPayload('save_rapport');
        var formData = new FormData(rapportForm);
        if (!formData.get('csrf_token')) {
            var csrfToken = getCsrfToken();
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }
        }
        if (!formData.get('csrf_token')) {
            return Promise.reject(new Error('Session expirée. Veuillez recharger la page.'));
        }
        saveStatusEl.textContent = options.pendingMessage || 'Sauvegarde en cours…';
        if (saveBtn && options.disableButton !== false) saveBtn.disabled = true;

        console.log('[persistDraft] Sending data', {
            nom_rapport: document.getElementById('nom_rapport_hidden').value,
            theme: document.getElementById('theme_rapport_hidden').value,
            contentLength: document.getElementById('contenu_rapport').value.length
        });

        saveInFlight = fetch(reportEndpoint, {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function (r) { 
            console.log('[persistDraft] Response status:', r.status, r.headers.get('content-type'));
            if (!r.ok && r.status !== 200) {
                return r.text().then(function(text) {
                    console.error('[persistDraft] Error response:', text);
                    throw new Error('HTTP ' + r.status);
                });
            }
            return r.json(); 
        })
        .then(function (result) {
            console.log('[persistDraft] Success:', result);
            if (!result.success) throw new Error(result.message || 'Échec de la sauvegarde.');
            if (result.rapport_id) syncReportId(String(result.rapport_id));
            requiresMigrationSave = false;
            lastSnapshot = snapshot();
            saveStatusEl.textContent = (options.successPrefix || 'Sauvegardé à ') + nowLabel();
            if (!options.silentSuccess) showNotification('success', result.message || 'Rapport enregistré.');
            return getEditId();
        })
        .catch(function (error) {
            console.error('[persistDraft] Error:', error);
            saveStatusEl.textContent = options.failureMessage || ('Échec à ' + nowLabel());
            var errMsg = (error && typeof error.message === 'string') ? error.message : String(error || 'Erreur inconnue');
            if (!options.silentError) showNotification('error', errMsg);
            // Ne pas re-throw pour éviter les popups [object Object] du handler unhandledrejection global
        })
        .finally(function () {
            saveInFlight = null;
            if (saveBtn) saveBtn.disabled = false;
        });

        return saveInFlight;
    }

    function ensurePersistedForOutput() {
        var reportId = getEditId();
        var currentSnapshot = snapshot();
        if (!reportId || requiresMigrationSave || currentSnapshot !== lastSnapshot) {
            return persistDraft({
                pendingMessage: 'Préparation du document…',
                successPrefix: 'Sauvegardé à ',
                silentSuccess: true,
                failureMessage: 'Échec de la préparation à ' + nowLabel()
            });
        }
        return Promise.resolve(reportId);
    }

    /* ── PDF export ── */

    function downloadBlob(blob, filename) {
        var url = window.URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = String(filename || 'rapport.pdf').replace(/[\\/:*?"<>|]+/g, '_');
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        window.URL.revokeObjectURL(url);
    }

    function requestPdfBlob(reportId, shouldDownload) {
        var csrfToken = getCsrfToken();
        if (!csrfToken) {
            return Promise.reject(new Error('Session expirée. Veuillez recharger la page.'));
        }

        var formData = new FormData();
        formData.append('action', 'export_pdf');
        formData.append('edit_id', reportId);
        formData.append('csrf_token', csrfToken);
        if (shouldDownload) {
            formData.append('download', '1');
        }

        console.log('[requestPdfBlob] Requesting PDF for report', reportId, 'download=', !!shouldDownload);

        return fetch(reportEndpoint, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Cache-Control': 'no-cache'
            }
        })
        .then(function (response) {
            console.log('[requestPdfBlob] Response:', response.status, response.headers.get('content-type'));
            if (!response.ok) throw new Error('Erreur HTTP ' + response.status);
            var ct = response.headers.get('content-type') || '';
            if (ct.indexOf('application/pdf') !== -1) return response.blob();
            return response.text().then(function (text) {
                console.error('[requestPdfBlob] Non-PDF response:', text);
                var data = {};
                try { data = JSON.parse(text); } catch (e) {}
                throw new Error(data.message || 'Erreur lors de la génération du PDF.');
            });
        });
    }

    function exportPdf(reportId) {
        return requestPdfBlob(reportId, true)
        .then(function (blob) {
            console.log('[exportPdf] Blob size:', blob.size);
            if (!blob || blob.size === 0) throw new Error('Le PDF généré est vide.');
            downloadBlob(blob, buildDocumentName().replace(/\s+/g, '_') + '.pdf');
        });
    }

    function previewPdf(reportId, previewWindow) {
        return requestPdfBlob(reportId, false)
        .then(function (blob) {
            console.log('[previewPdf] Blob size:', blob.size);
            if (!blob || blob.size === 0) {
                throw new Error('Le PDF généré est vide.');
            }

            if (activePreviewUrl) {
                window.URL.revokeObjectURL(activePreviewUrl);
                activePreviewUrl = null;
            }

            activePreviewUrl = window.URL.createObjectURL(blob);

            if (previewWindow && !previewWindow.closed) {
                previewWindow.location.href = activePreviewUrl;
                return;
            }

            var fallbackWindow = window.open(activePreviewUrl, '_blank', 'noopener');
            if (!fallbackWindow) {
                throw new Error('Le navigateur a bloqué l’aperçu PDF.');
            }
        });
    }

    /* ── Deposit ── */

    function requestDepositConfirmation() {
        if (window.CM && typeof window.CM.confirm === 'function') {
            return window.CM.confirm({
                title: 'Confirmation de dépôt',
                message: 'Voulez-vous vraiment déposer ce rapport ? Cette action est irréversible.',
                type: 'warning',
                confirmText: 'Déposer'
            });
        }
        return Promise.resolve(window.confirm('Voulez-vous vraiment déposer ce rapport ? Cette action est irréversible.'));
    }

    /* ── Notifications ── */

    function showNotification(type, message) {
        var msgText = message;
        if (typeof message === 'object' && message !== null) {
            msgText = message.message || JSON.stringify(message);
        } else {
            msgText = String(message || '');
        }

        if (typeof window.cmToast === 'function') {
            window.cmToast(msgText, type);
            return;
        }
        var container = document.getElementById('fmNotifications');
        if (!container) return;
        var toast = document.createElement('div');
        toast.style.cssText = 'min-width:260px; max-width:360px; padding:0.8rem 1rem; border-radius:12px; background:#fff; border:1px solid rgba(26,82,118,0.12); box-shadow:0 12px 24px rgba(15,44,70,0.14); color:#0f172a; font-size:0.85rem;';
        toast.style.borderLeft = '4px solid ' + (type === 'success' ? '#27ae60' : '#e74c3c');
        toast.textContent = message;
        container.appendChild(toast);
        setTimeout(function () { toast.remove(); }, 4000);
    }

    function initJoditEditor() {
        joditEditor = window.Jodit.make('#jodit-editor', {
            height: 'auto',
            minHeight: 500,
            language: 'fr',
            readonly: isReadOnly,
            toolbarAdaptive: false,
            askBeforePasteHTML: false,
            askBeforePasteFromWord: false,
            defaultFontSize: '12pt',
            defaultFontName: 'Times New Roman',
            buttons: [
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'font', 'fontsize', 'paragraph', 'brush', '|',
                'align', 'ul', 'ol', 'indent', 'outdent', '|',
                'table', 'link', 'image', '|',
                'hr', 'undo', 'redo', '|',
                'fullsize'
            ],
            uploader: { insertImageAsBase64URI: true },
            placeholder: 'Rédigez votre rapport ici…'
        });
    }

    function activateFallbackEditor() {
        fallbackEditorMode = true;
        if (editorTextarea) {
            editorTextarea.style.width = '100%';
            editorTextarea.style.minHeight = '520px';
            editorTextarea.style.padding = '14px 16px';
            editorTextarea.style.fontFamily = 'Times New Roman, serif';
            editorTextarea.style.fontSize = '12pt';
            editorTextarea.style.border = '1px solid #d8e4f2';
            editorTextarea.style.borderRadius = '10px';
            editorTextarea.style.background = '#ffffff';
            editorTextarea.readOnly = !!isReadOnly;
        }
        showNotification('error', 'Éditeur enrichi indisponible. Mode texte activé.');
        console.error('[editor] Jodit indisponible, fallback textarea actif.');
    }

    function applyInitialContent() {
        if (rawContent && rawContent.trim() !== '') {
            if (isUnifiedDocumentHtml(rawContent)) {
                editorSetValue(rawContent);
            } else {
                requiresMigrationSave = true;
                editorSetValue(buildDocumentHtml(extractLegacyBodyContent(rawContent) || getDefaultBodyContent()));
            }
        } else {
            if (getEditId()) requiresMigrationSave = true;
            editorSetValue(buildDocumentHtml(getDefaultBodyContent()));
        }

        updateWordCount();
        lastSnapshot = snapshot();

        if (!isReadOnly && requiresMigrationSave) {
            saveStatusEl.textContent = 'Mise à niveau au prochain enregistrement.';
        }
    }

    function ensureJoditAssets() {
        if (typeof window.Jodit !== 'undefined' && typeof window.Jodit.make === 'function') {
            return Promise.resolve();
        }

        if (window.__cmJoditLoaderPromise) {
            return window.__cmJoditLoaderPromise;
        }

        window.__cmJoditLoaderPromise = new Promise(function (resolve, reject) {
            var existingCss = document.querySelector('link[data-cm-jodit-css="1"]');
            if (!existingCss) {
                var css = document.createElement('link');
                css.rel = 'stylesheet';
                css.href = 'https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.5/jodit.min.css';
                css.setAttribute('data-cm-jodit-css', '1');
                document.head.appendChild(css);
            }

            var existingScript = document.querySelector('script[data-cm-jodit-script="1"]');
            if (existingScript) {
                existingScript.addEventListener('load', resolve, { once: true });
                existingScript.addEventListener('error', reject, { once: true });
                return;
            }

            var script = document.createElement('script');
            script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.5/jodit.min.js';
            script.async = false;
            script.setAttribute('data-cm-jodit-script', '1');
            script.onload = function () { resolve(); };
            script.onerror = function () { reject(new Error('Impossible de charger l’éditeur Jodit.')); };
            document.head.appendChild(script);
        }).finally(function () {
            if (!(typeof window.Jodit !== 'undefined' && typeof window.Jodit.make === 'function')) {
                window.__cmJoditLoaderPromise = null;
            }
        });

        return window.__cmJoditLoaderPromise;
    }

    /* ── Initialize editor (Jodit with textarea fallback) ── */

    function finalizeEditorInitialization() {
        applyInitialContent();

        /* ── Event bindings ── */

        editorOnChange(function () {
            updateWordCount();
        });

        // Bidirectional title sync
        if (titleInput) {
            titleInput.addEventListener('input', function () {
                syncTitleToEditor();
            });
        }

        // Save button
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                persistDraft({ pendingMessage: 'Sauvegarde en cours…', successPrefix: 'Sauvegardé à ' })
                    .catch(function () {});
            });
        }

        // Download PDF button (footer)
        if (downloadPdfBtn) {
            downloadPdfBtn.addEventListener('click', function () {
                pdfLoading.classList.add('is-visible');
                downloadPdfBtn.disabled = true;
                ensurePersistedForOutput()
                    .then(function (reportId) {
                        if (!reportId) throw new Error('Veuillez enregistrer le rapport d\'abord.');
                        return exportPdf(reportId);
                    })
                    .then(function () {
                        showNotification('success', 'PDF téléchargé avec succès.');
                    })
                    .catch(function (error) {
                        showNotification('error', error.message || 'Erreur PDF.');
                    })
                    .finally(function () {
                        pdfLoading.classList.remove('is-visible');
                        downloadPdfBtn.disabled = false;
                    });
            });
        }

        // Preview PDF button (footer) — opens backend PDF in new tab
        if (previewPdfBtn) {
            previewPdfBtn.addEventListener('click', function () {
                console.log('[previewPdf] Button clicked');
                previewPdfBtn.disabled = true;
                var previewWindow = window.open('', '_blank');
                if (previewWindow && previewWindow.document) {
                    previewWindow.document.write('<!DOCTYPE html><title>Aperçu PDF</title><p style="font-family:Arial,sans-serif;padding:16px;">Generation du PDF...</p>');
                    previewWindow.document.close();
                }
                ensurePersistedForOutput()
                    .then(function (reportId) {
                        console.log('[previewPdf] Got reportId:', reportId);
                        if (!reportId) throw new Error('Veuillez enregistrer le rapport d\'abord.');
                        return previewPdf(reportId, previewWindow);
                    })
                    .catch(function (error) {
                        console.error('[previewPdf] Error:', error);
                        if (previewWindow && !previewWindow.closed) {
                            previewWindow.close();
                        }
                        showNotification('error', error.message || 'Erreur aperçu.');
                    })
                    .finally(function () {
                        previewPdfBtn.disabled = false;
                    });
            });
        }

        // Deposit button
        if (deposerBtn) {
            deposerBtn.addEventListener('click', function (event) {
                event.preventDefault();
                console.log('[deposit] Button clicked');
                if (deposerBtn.disabled) return;
                requestDepositConfirmation()
                    .then(function (confirmed) {
                        console.log('[deposit] Confirmed:', confirmed);
                        if (!confirmed) return null;
                        deposerBtn.disabled = true;
                        return ensurePersistedForOutput();
                    })
                    .then(function (reportId) {
                        console.log('[deposit] Got reportId:', reportId);
                        if (!reportId) return null;
                        applyFormPayload('deposer_rapport');
                        syncReportId(reportId);
                        console.log('[deposit] Submitting form');
                        rapportForm.submit();
                        return null;
                    })
                    .catch(function (error) {
                        console.error('[deposit] Error:', error);
                        deposerBtn.disabled = false;
                        showNotification('error', error.message || 'Erreur lors du dépôt.');
                    });
            });
        }

        // Auto-save every 60 seconds
        if (!isReadOnly) {
            window.setInterval(function () {
                var currentSnapshot = snapshot();
                if (requiresMigrationSave || currentSnapshot !== lastSnapshot) {
                    persistDraft({
                        pendingMessage: 'Auto-save…',
                        successPrefix: 'Auto-save à ',
                        silentSuccess: true,
                        silentError: true,
                        failureMessage: 'Échec auto-save à ' + nowLabel(),
                        disableButton: false
                    }).catch(function () {});
                }
            }, 60000);
        }
    }

    ensureJoditAssets()
        .then(function () {
            if (typeof window.Jodit !== 'undefined' && typeof window.Jodit.make === 'function') {
                initJoditEditor();
            } else {
                activateFallbackEditor();
            }
        })
        .catch(function (error) {
            console.error('[editor] Asset load error:', error);
            activateFallbackEditor();
        })
        .finally(function () {
            finalizeEditorInitialization();
        });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initRapportEditorPage, { once: true });
} else {
    initRapportEditorPage();
}
})();
</script>


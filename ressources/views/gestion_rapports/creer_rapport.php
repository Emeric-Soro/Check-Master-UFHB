<?php
// Inclure le helper de permissions
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';

// Determiner si c'est une edition ou creation
$isEditingExisting = isset($isEditMode) && $isEditMode && isset($rapport);
$isReadOnly = !empty($GLOBALS['rapportDejaDepose']);

// Recuperer les informations de l'etudiant depuis la session
$numEtu = $_SESSION['num_etu'] ?? '';
$nomEtu = $_SESSION['nom_etu'] ?? '';
$prenomEtu = $_SESSION['prenom_etu'] ?? '';
$nomCompletEtu = trim($nomEtu . ' ' . $prenomEtu);

// Infos stage
$stageInfo = is_array($stage_info ?? null) ? $stage_info : (is_array($GLOBALS['stage_info'] ?? null) ? $GLOBALS['stage_info'] : []);
$rapport   = is_array($rapport ?? null) ? $rapport : [];
$erreurs   = is_array($erreurs ?? null) ? $erreurs : [];
$contenuRapport = (string) ($contenuRapport ?? '');

$rapportId         = (string) ($rapport['id_rapport'] ?? '');
$nomRapportInitial = (string) ($rapport['nom_rapport'] ?? '');
$themeRapportInitial = (string) ($rapport['theme_rapport'] ?? '');
if ($themeRapportInitial === '' && isset($stageInfo['sujet_stage'])) {
    $themeRapportInitial = (string) $stageInfo['sujet_stage'];
}

$nomEntreprise       = (string) ($stageInfo['nom_entreprise'] ?? '');
$encadrantEntreprise = (string) ($stageInfo['encadrant_entreprise'] ?? '');

// Annee academique
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

// URLs des logos
$baseUrl = (isset($_SERVER["REQUEST_SCHEME"]) ? $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] : "") . '/checkmaster.ufrmi-ufhb-ci/public/image/';
$logoUfhb = $baseUrl . 'logo_ufhb.png';
$logoCiv = $baseUrl . 'logo_civ.png';

if (!function_exists('cm_etu_escape')) {
    function cm_etu_escape($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!-- Ressources necessaires pour l'editeur -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.5/jodit.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.5/jodit.min.js"></script>

<style>
    /* Tab styles (cm-etu) */
    .cm-etu-tabs {
        display: flex;
        gap: 0;
        border-bottom: 1px solid rgba(26, 82, 118, 0.12);
    }
    .cm-etu-tab-btn {
        padding: 0.75rem 1.5rem;
        border: none;
        background: #ebf3fa;
        cursor: pointer;
        font-weight: var(--cm-font-weight-semibold);
        font-size: 0.9rem;
        color: var(--cm-primary-dark);
        transition: all 0.2s;
        border-bottom: 3px solid transparent;
    }
    .cm-etu-tab-btn:first-child { border-radius: 10px 0 0 0; }
    .cm-etu-tab-btn:last-child  { border-radius: 0 10px 0 0; }
    .cm-etu-tab-btn.is-active {
        background: var(--cm-primary);
        color: #fff;
        border-bottom-color: var(--cm-primary-dark);
    }
    .cm-etu-tab-btn:hover:not(.is-active) { background: #dceaf7; }

    .cm-etu-tab-pane { display: none; }
    .cm-etu-tab-pane.is-active { display: block; }

    /* Cover preview (scaled) */
    .cm-etu-cover-preview-wrap {
        border: 1px solid rgba(26, 82, 118, 0.14);
        border-radius: 12px;
        background: #fff;
        padding: 12px;
        overflow: hidden;
        position: relative;
    }
    .cm-etu-cover-preview-scaler {
        transform-origin: top left;
        /* largeur et scale calcules dynamiquement en JS */
    }
    .cm-etu-cover-preview {
        width: 210mm; /* largeur A4 réelle */
        min-height: 100px;
        background: white;
    }

    /* Cover form grid */
    .cm-etu-cover-layout {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 1rem;
    }
    @media (max-width: 900px) {
        .cm-etu-cover-layout { grid-template-columns: 1fr; }
    }

    /* Preview Modal */
    .cm-etu-preview-modal {
        position: fixed;
        top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(10, 23, 34, 0.55);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: var(--cm-z-modal-overlay, 1000);
        opacity: 0; visibility: hidden;
        transition: all 0.3s ease;
    }
    .cm-etu-preview-modal.is-visible { opacity: 1; visibility: visible; }
    .cm-etu-preview-modal__dialog {
        background: #fff;
        border-radius: 14px;
        border: 1px solid rgba(26, 82, 118, 0.16);
        box-shadow: 0 18px 38px rgba(15, 44, 70, 0.22);
        padding: 1.25rem;
        max-width: 900px; width: 95%;
        max-height: 90vh; overflow-y: auto;
        transform: scale(0.9);
        transition: transform 0.3s ease;
    }
    .cm-etu-preview-modal.is-visible .cm-etu-preview-modal__dialog { transform: scale(1); }
    .cm-etu-preview-modal__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.75rem;
    }
    .cm-etu-preview-modal__header h2 {
        margin: 0;
        color: var(--cm-primary-dark);
        font-size: 1.1rem;
    }
    .cm-etu-preview-modal__close {
        background: none; border: none;
        color: var(--cm-text-muted);
        cursor: pointer; font-size: 1.2rem;
    }
    .cm-etu-preview-modal__close:hover { color: var(--cm-primary-dark); }

    /* PDF Loading overlay */
    .cm-etu-pdf-loading {
        position: fixed; top: 0; left: 0;
        width: 100%; height: 100%;
        background: rgba(0, 0, 0, 0.8);
        display: flex; flex-direction: column;
        align-items: center; justify-content: center;
        z-index: 2000;
        opacity: 0; visibility: hidden;
        transition: all 0.3s ease;
    }
    .cm-etu-pdf-loading.is-visible { opacity: 1; visibility: visible; }
    .cm-etu-pdf-loading__spinner {
        width: 56px; height: 56px;
        border: 4px solid rgba(255,255,255,0.2);
        border-top: 4px solid var(--cm-feedback-success, #10b981);
        border-radius: 50%;
        animation: cm-spin 1s linear infinite;
    }
    @keyframes cm-spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    .cm-etu-pdf-loading p { color: #fff; margin-top: 1rem; font-size: 1.1rem; }

    /* Section title */
    .cm-etu-section-title {
        margin: 0 0 0.65rem;
        color: var(--cm-primary-dark);
        font-size: 1.02rem;
        font-weight: var(--cm-font-weight-semibold);
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
    }
</style>

<div class="cm-etu-screen cm-etu-editor-screen">
    <section class="cm-etu-panel">
        <!-- Header -->
        <header class="cm-etu-editor-header">
            <div>
                <h2 class="cm-etu-panel__title">
                    <i class="fas fa-file-alt" aria-hidden="true"></i> Editeur de Rapport de Stage
                </h2>
                <p class="cm-etu-panel__subtitle">
                    <?php if ($isEditingExisting): ?>
                        Mode edition : <?= cm_etu_escape(isset($rapport['nom_rapport']) ? $rapport['nom_rapport'] : 'Rapport') ?>
                    <?php else: ?>
                        Approche hybride : Template fixe + Editeur Jodit
                    <?php endif; ?>
                </p>
            </div>
            <span class="cm-etu-count-badge">Annee A.: <?= cm_etu_escape($anneeAcademique) ?></span>
        </header>

        <!-- Top Actions -->
        <div class="cm-etu-actions" style="justify-content: flex-start;">
            <a href="?page=gestion_rapports" class="cm-btn is-light is-sm">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Retour</span>
            </a>
            <div style="flex:1;"></div>
            <?php if (!$isReadOnly): ?>
                <button id="saveBtn" type="button" class="cm-btn is-primary is-sm">
                    <i class="fas fa-floppy-disk" aria-hidden="true"></i>
                    <span>Enregistrer</span>
                </button>
            <?php endif; ?>
            <button id="previewBtn" type="button" class="cm-btn is-info is-sm">
                <i class="fas fa-eye" aria-hidden="true"></i>
                <span>Apercu</span>
            </button>
            <button id="exportBtn" type="button" class="cm-btn is-success is-sm">
                <i class="fas fa-file-pdf" aria-hidden="true"></i>
                <span>Exporter PDF</span>
            </button>
            <?php if (!$isReadOnly): ?>
                <button id="deposerBtn" type="button" class="cm-btn is-warning is-sm">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>Deposer</span>
                </button>
            <?php else: ?>
                <button disabled class="cm-btn is-light is-sm" title="Rapport deja depose" style="opacity:0.6; cursor:not-allowed;">
                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                    <span>Depose</span>
                </button>
            <?php endif; ?>
        </div>

        <!-- Alerts -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="cm-etu-note-box is-info">
                <i class="fas fa-check-circle" aria-hidden="true"></i>
                <p><strong>Succes :</strong> <?= cm_etu_escape($_SESSION['success']) ?></p>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (!empty($erreurs)): ?>
            <div class="cm-etu-validation-box">
                <strong>Erreurs de validation :</strong>
                <ul>
                    <?php foreach ($erreurs as $erreur): ?>
                        <li><?= cm_etu_escape($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Hidden Form -->
        <form id="rapportForm" method="POST" style="display: none;">
            <input type="hidden" name="action" value="save_rapport">
            <?php if ($isEditingExisting): ?>
                <input type="hidden" name="edit_id" value="<?= cm_etu_escape($rapportId) ?>">
            <?php endif; ?>
            <input type="hidden" id="nom_rapport_hidden" name="nom_rapport" value="<?= cm_etu_escape($nomRapportInitial) ?>">
            <input type="hidden" id="theme_rapport_hidden" name="theme_rapport" value="<?= cm_etu_escape($themeRapportInitial) ?>">
            <input type="hidden" id="contenu_rapport" name="contenu_rapport">
            <input type="hidden" id="cover_data" name="cover_data">
            <input type="hidden" id="payloadReportId" name="id_rapport" value="<?= cm_etu_escape($rapportId) ?>">
        </form>

        <!-- Tabs -->
        <div class="cm-etu-tabs">
            <button class="cm-etu-tab-btn is-active" data-tab="cover">
                <i class="fas fa-image" aria-hidden="true"></i> 1. Page de Couverture
            </button>
            <button class="cm-etu-tab-btn" data-tab="body">
                <i class="fas fa-pen" aria-hidden="true"></i> 2. Corps du Rapport
            </button>
        </div>

        <!-- Tab 1: Cover Page -->
        <div id="tab-cover" class="cm-etu-tab-pane is-active">
            <div class="cm-etu-cover-layout">
                <!-- Left: Form -->
                <div>
                    <h3 class="cm-etu-section-title"><i class="fas fa-pen-to-square" aria-hidden="true"></i> Informations de la Page de Couverture</h3>

                    <div id="coverPageFormContainer" class="cm-etu-form">
                        <!-- Nom du rapport -->
                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="nom_rapport">Nom du rapport <span class="cm-required-star">*</span></label>
                            <input type="text" id="nom_rapport" class="cm-etu-input"
                                   value="<?= cm_etu_escape($nomRapportInitial) ?>"
                                   placeholder="Ex: Rapport de stage - Developpement Web"
                                    <?= $isReadOnly ? 'readonly' : '' ?> required>
                        </div>

                        <!-- Diplome et Option -->
                        <div class="cm-etu-grid cm-etu-grid--2">
                            <div class="cm-etu-field">
                                <label class="cm-etu-label" for="diplome">Diplome <span class="cm-required-star">*</span></label>
                                <select id="diplome" class="cm-etu-select" <?= $isReadOnly ? 'disabled' : '' ?> required>
                                    <option value="Diplome d'Ingenieur de conception en informatique">Diplome d'Ingenieur de conception en informatique</option>
                                    <option value="Licence Professionnelle">Licence Professionnelle</option>
                                    <option value="Master Professionnel">Master Professionnel</option>
                                </select>
                            </div>
                            <div class="cm-etu-field">
                                <label class="cm-etu-label" for="option_specialite">Option/Specialite <span class="cm-required-star">*</span></label>
                                <select id="option_specialite" class="cm-etu-select" <?= $isReadOnly ? 'disabled' : '' ?> required>
                                    <option value="Option Methodes Informatiques Appliquees a la Gestion des Entreprises">MIAGE</option>
                                    <option value="Option Genie Logiciel">Genie Logiciel</option>
                                    <option value="Option Reseaux et Systemes">Reseaux et Systemes</option>
                                </select>
                            </div>
                        </div>

                        <!-- Etudiant -->
                        <div class="cm-etu-grid cm-etu-grid--2">
                            <div class="cm-etu-field">
                                <label class="cm-etu-label" for="civilite">Civilite</label>
                                <select id="civilite" class="cm-etu-select" <?= $isReadOnly ? 'disabled' : '' ?>>
                                    <option value="M.">M.</option>
                                    <option value="Mme">Mme</option>
                                    <option value="Mlle">Mlle</option>
                                </select>
                            </div>
                            <div class="cm-etu-field">
                                <label class="cm-etu-label" for="matricule">Matricule</label>
                                <input type="text" id="matricule" class="cm-etu-input" value="<?= cm_etu_escape($numEtu) ?>" readonly>
                            </div>
                        </div>

                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="nom_etudiant">Nom complet de l'etudiant <span class="cm-required-star">*</span></label>
                            <input type="text" id="nom_etudiant" class="cm-etu-input" value="<?= cm_etu_escape($nomCompletEtu) ?>"
                                    <?= $isReadOnly ? 'readonly' : '' ?> required>
                        </div>

                        <!-- Theme -->
                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="titre_theme">Titre du theme <span class="cm-required-star">*</span></label>
                            <input type="text" id="titre_theme" class="cm-etu-input"
                                   value="<?= cm_etu_escape($themeRapportInitial) ?>"
                                   placeholder="Ex: MISE EN PLACE D'UN MODULE D'INTEGRATION..."
                                    <?= $isReadOnly ? 'readonly' : '' ?> required>
                        </div>

                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="sous_titre">Sous-titre (optionnel)</label>
                            <input type="text" id="sous_titre" class="cm-etu-input"
                                   placeholder="Ex: CAS DE L'ENTREPRISE XYZ"
                                    <?= $isReadOnly ? 'readonly' : '' ?>>
                        </div>

                        <!-- Entreprise -->
                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="nom_entreprise">Entreprise d'accueil <span class="cm-required-star">*</span></label>
                            <input type="text" id="nom_entreprise" class="cm-etu-input"
                                   placeholder="Ex: KYRIA CONSULTANCY SERVICES"
                                   value="<?= cm_etu_escape($nomEntreprise) ?>"
                                    <?= $isReadOnly ? 'readonly' : '' ?> required>
                        </div>

                        <!-- Encadrement -->
                        <div class="cm-etu-grid cm-etu-grid--2">
                            <div class="cm-etu-field">
                                <label class="cm-etu-label" for="encadreur">Encadreur academique</label>
                                <input type="text" id="encadreur" class="cm-etu-input"
                                       placeholder="Nom de l'encadreur"
                                        <?= $isReadOnly ? 'readonly' : '' ?>>
                            </div>
                            <div class="cm-etu-field">
                                <label class="cm-etu-label" for="maitre_stage">Maitre de stage</label>
                                <input type="text" id="maitre_stage" class="cm-etu-input"
                                       placeholder="Nom du maitre de stage"
                                       value="<?= cm_etu_escape($encadrantEntreprise) ?>"
                                        <?= $isReadOnly ? 'readonly' : '' ?>>
                            </div>
                        </div>

                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="fonction_maitre">Fonction du maitre de stage</label>
                            <input type="text" id="fonction_maitre" class="cm-etu-input"
                                   placeholder="Ex: Consultant a KYRIA Consultancy Services"
                                    <?= $isReadOnly ? 'readonly' : '' ?>>
                        </div>

                        <?php if (!$isReadOnly): ?>
                            <button type="button" id="updatePreviewBtn" class="cm-btn is-success" style="width:100%; margin-top:0.5rem;">
                                <i class="fas fa-rotate" aria-hidden="true"></i>
                                Mettre a jour l'apercu
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Right: Preview -->
                <div>
                    <h3 class="cm-etu-section-title"><i class="fas fa-eye" aria-hidden="true"></i> Apercu de la Page de Couverture</h3>
                    <div class="cm-etu-cover-preview-wrap" id="coverPreviewWrap">
                        <div id="coverPreviewScaler" class="cm-etu-cover-preview-scaler">
                            <div id="coverPreview" class="cm-etu-cover-preview">
                                <!-- Contenu genere dynamiquement -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab 2: Report Body -->
        <div id="tab-body" class="cm-etu-tab-pane">
            <h3 class="cm-etu-section-title"><i class="fas fa-pen" aria-hidden="true"></i> Corps du Rapport (Editeur Jodit)</h3>
            <div class="cm-etu-editor-shell">
                <textarea id="jodit-editor"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="cm-etu-editor-footer">
            <p id="wordCounter" class="cm-etu-word-counter">0 mot(s)</p>
            <p id="autosaveStatus" class="cm-etu-autosave-status"><?= $isReadOnly ? 'Rapport deja depose : lecture seule.' : 'Auto-save toutes les 30 secondes.' ?></p>
        </div>
    </section>
</div>

<!-- Preview Modal -->
<div id="previewModal" class="cm-etu-preview-modal">
    <div class="cm-etu-preview-modal__dialog">
        <div class="cm-etu-preview-modal__header">
            <h2><i class="fas fa-eye" aria-hidden="true"></i> Apercu du Rapport Complet</h2>
            <button id="closePreviewModal" class="cm-etu-preview-modal__close">
                <i class="fas fa-xmark"></i>
            </button>
        </div>
        <div id="fullPreview" style="background: white; padding: 20px; border: 1px solid #ddd; max-height: 70vh; overflow-y: auto;"></div>
    </div>
</div>

<!-- PDF Loading Overlay -->
<div id="pdfLoading" class="cm-etu-pdf-loading">
    <div class="cm-etu-pdf-loading__spinner"></div>
    <p>Generation du PDF en cours...</p>
</div>

<!-- Notifications (toast) -->
<div id="notificationContainer" style="position:fixed; top:1rem; right:1rem; z-index:9999; display:flex; flex-direction:column; gap:0.5rem;"></div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var logoUfhb = '<?= $logoUfhb ?>';
        var logoCiv = '<?= $logoCiv ?>';
        var isReadOnly = <?= $isReadOnly ? 'true' : 'false' ?>;
        var isEditMode = <?= $isEditingExisting ? 'true' : 'false' ?>;
        var minWords = 5000;

        var tabBtns         = document.querySelectorAll('.cm-etu-tab-btn');
        var tabPanes        = document.querySelectorAll('.cm-etu-tab-pane');
        var updatePreviewBtn = document.getElementById('updatePreviewBtn');
        var coverPreview    = document.getElementById('coverPreview');
        var previewBtn      = document.getElementById('previewBtn');
        var previewModal    = document.getElementById('previewModal');
        var closePreviewModal = document.getElementById('closePreviewModal');
        var fullPreview     = document.getElementById('fullPreview');
        var pdfLoading      = document.getElementById('pdfLoading');
        var saveBtn         = document.getElementById('saveBtn');
        var exportBtn       = document.getElementById('exportBtn');
        var deposerBtn      = document.getElementById('deposerBtn');
        var rapportForm     = document.getElementById('rapportForm');
        var wordCounter     = document.getElementById('wordCounter');
        var autosaveStatus  = document.getElementById('autosaveStatus');

        var joditEditor = Jodit.make('#jodit-editor', {
            height: 500,
            language: 'fr',
            placeholder: 'Commencez a rediger le corps de votre rapport ici...',
            toolbarButtonSize: 'middle',
            readonly: isReadOnly,
            toolbarAdaptive: false,
            askBeforePasteHTML: false,
            askBeforePasteFromWord: false,
            buttons: [
                'source', '|',
                'bold', 'italic', 'underline', 'strikethrough', '|',
                'font', 'fontsize', 'brush', 'paragraph', '|',
                'ul', 'ol', 'indent', 'outdent', '|',
                'align', 'undo', 'redo', '|',
                'table', 'link', 'image', '|',
                'hr', 'copyformat', 'fullsize', 'print'
            ],
            uploader: { insertImageAsBase64URI: true },
            defaultFontSize: '12pt',
            defaultFontName: 'Times New Roman'
        });

        <?php if ($isEditingExisting && !empty($contenuRapport)): ?>
        joditEditor.value = <?= json_encode($contenuRapport) ?>;
        <?php else: ?>
        joditEditor.value = getInitialBodyContent();
        <?php endif; ?>

        // Tab switching
        tabBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tabId = btn.dataset.tab;
                tabBtns.forEach(function (b) { b.classList.remove('is-active'); });
                tabPanes.forEach(function (p) { p.classList.remove('is-active'); });
                btn.classList.add('is-active');
                document.getElementById('tab-' + tabId).classList.add('is-active');
            });
        });

        // Word counter
        function extractPlainText(html) {
            var probe = document.createElement('div');
            probe.innerHTML = html;
            return String(probe.textContent || probe.innerText || '').replace(/\s+/g, ' ').trim();
        }
        function getWordCount() {
            var text = extractPlainText(joditEditor.value || '');
            if (text === '') return 0;
            return text.split(' ').filter(Boolean).length;
        }
        function updateWordCounter() {
            var words = getWordCount();
            wordCounter.textContent = words.toLocaleString('fr-FR') + ' mot(s)';
        }
        joditEditor.events.on('change', updateWordCounter);
        updateWordCounter();

        // Cover Page HTML Generator
        function generateCoverPageHTML() {
            var data = getCoverData();
            var logoKyria = '<?= $baseUrl ?>logoCM.png';

            return '<div style="font-family: \'Times New Roman\', serif; width: 210mm; min-height: 297mm; padding: 20mm 25mm; box-sizing: border-box; background: white;">' +
                '<table style="width: 100%; border: none; margin-bottom: 5px;">' +
                '<tr>' +
                '<td style="width: 50%; text-align: left; font-size: 10pt; vertical-align: top; border: none; padding: 0;">' +
                'MINISTERE DE L\'ENSEIGNEMENT SUPERIEUR<br/>ET DE LA RECHERCHE SCIENTIFIQUE' +
                '</td>' +
                '<td style="width: 50%; text-align: right; font-size: 10pt; vertical-align: top; border: none; padding: 0;">' +
                'REPUBLIQUE DE COTE D\'IVOIRE<br/>UNION - DISCIPLINE - TRAVAIL' +
                '</td>' +
                '</tr>' +
                '</table>' +
                '<table style="width: 100%; border: none; margin: 15px 0 20px 0;">' +
                '<tr>' +
                '<td style="width: 50%; text-align: center; vertical-align: top; border: none; padding: 10px;">' +
                '<img src="' + logoUfhb + '" alt="Logo UFHB" style="width: 70px; height: auto;" onerror="this.style.display=\'none\'"/><br/><br/>' +
                '<span style="font-size: 11pt; font-weight: bold; color: #1a5276;">UNIVERSITE FELIX HOUPHOUET BOIGNY</span><br/><br/>' +
                '<span style="font-size: 10pt;">UFR MATHEMATIQUES ET INFORMATIQUE</span><br/>' +
                '<span style="font-size: 10pt;">FILIERES PROFESSIONNALISEES MIAGE-GI</span>' +
                '</td>' +
                '<td style="width: 50%; text-align: center; vertical-align: top; border: none; padding: 10px;">' +
                '<img src="' + logoCiv + '" alt="Armoiries CI" style="width: 65px; height: auto;" onerror="this.style.display=\'none\'"/>' +
                '<img src="' + logoKyria + '" alt="Logo Entreprise" style="width: 65px; height: auto; margin-left: 15px;" onerror="this.style.display=\'none\'"/><br/><br/>' +
                '<span style="font-size: 11pt; font-weight: bold;">' + escapeHtml(data.nomEntreprise).toUpperCase() + '</span>' +
                '</td>' +
                '</tr>' +
                '</table>' +
                '<div style="text-align: center; margin: 25px 0 15px 0;">' +
                '<p style="font-size: 11pt; margin: 0 0 8px 0;">Memoire de fin de cycle pour l\'obtention du :</p>' +
                '<p style="font-size: 12pt; font-weight: bold; font-style: italic; margin: 0 0 5px 0;">' + escapeHtml(data.diplome) + '</p>' +
                '<p style="font-size: 10pt; font-style: italic; margin: 0;">' + escapeHtml(data.optionSpecialite) + '</p>' +
                '</div>' +
                '<div style="text-align: center; margin: 25px 0;">' +
                '<p style="font-size: 11pt; font-weight: bold; margin: 0 0 12px 0;">Theme :</p>' +
                '<div style="background-color: #1B5E20; color: white; padding: 18px 25px; margin: 0 auto; width: 95%; text-align: center;">' +
                '<p style="font-size: 13pt; font-weight: bold; text-transform: uppercase; line-height: 1.5; margin: 0; text-align: center;">' +
                escapeHtml(data.titreTheme).toUpperCase() + (data.sousTitre ? ' :' : '') +
                '</p>' +
                (data.sousTitre ? '<p style="font-size: 12pt; font-weight: bold; text-transform: uppercase; margin: 8px 0 0 0; text-align: center;">' + escapeHtml(data.sousTitre).toUpperCase() + '</p>' : '') +
                '</div>' +
                '</div>' +
                '<div style="text-align: center; margin: 30px 0 25px 0;">' +
                '<p style="font-size: 11pt; margin: 0 0 10px 0;">PRESENTE PAR :</p>' +
                '<p style="font-size: 11pt; font-weight: bold; margin: 0;">' + escapeHtml(data.civilite) + ' ' + escapeHtml(data.nomEtudiant).toUpperCase() + '</p>' +
                '</div>' +
                '<table style="width: 100%; border-collapse: collapse; margin-top: 30px;">' +
                '<tr>' +
                '<td style="width: 50%; padding: 20px; border: 2px solid #000; text-align: center; vertical-align: top;">' +
                '<p style="font-size: 11pt; font-weight: bold; margin: 0 0 15px 0; text-align: center;">ENCADREUR</p>' +
                '<p style="font-size: 10pt; margin: 0; text-align: center;">' + (escapeHtml(data.encadreur) || '') + '</p>' +
                '</td>' +
                '<td style="width: 50%; padding: 20px; border: 2px solid #000; text-align: center; vertical-align: top;">' +
                '<p style="font-size: 11pt; font-weight: bold; margin: 0 0 15px 0; text-align: center;">MAITRE DE STAGE</p>' +
                '<p style="font-size: 11pt; font-weight: bold; margin: 0; text-align: center;">' + (escapeHtml(data.maitreStage) || '') + '</p>' +
                (data.fonctionMaitre ? '<p style="font-size: 9pt; font-style: italic; margin: 8px 0 0 0; text-align: center;">' + escapeHtml(data.fonctionMaitre) + '</p>' : '') +
                '</td>' +
                '</tr>' +
                '</table>' +
                '</div>';
        }

        function getCoverData() {
            return {
                diplome: document.getElementById('diplome').value,
                optionSpecialite: document.getElementById('option_specialite').value,
                civilite: document.getElementById('civilite').value,
                matricule: document.getElementById('matricule').value,
                nomEtudiant: document.getElementById('nom_etudiant').value,
                titreTheme: document.getElementById('titre_theme').value,
                sousTitre: document.getElementById('sous_titre').value,
                nomEntreprise: document.getElementById('nom_entreprise').value,
                encadreur: document.getElementById('encadreur').value,
                maitreStage: document.getElementById('maitre_stage').value,
                fonctionMaitre: document.getElementById('fonction_maitre').value
            };
        }

        function escapeHtml(text) {
            if (!text) return '';
            var div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function getInitialBodyContent() {
            return '<div style="font-family: \'Times New Roman\', serif; font-size: 11pt; line-height: 1.5;">' +
                '<p style="font-size: 12pt; font-weight: bold; margin: 0 0 20px 0;">' +
                '<span style="font-weight: bold;">I.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">PRESENTATION DU CADRE DE REFERENCE</span>' +
                '</p>' +
                '<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">' +
                'Decrivez ici la structure d\'accueil, son historique, ses activites principales et son organisation.' +
                '</p>' +
                '<p style="font-size: 12pt; font-weight: bold; margin: 25px 0 20px 0;">' +
                '<span style="font-weight: bold;">II.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">INTRODUCTION : GENERALITE &amp; PROBLEMATIQUE</span>' +
                '</p>' +
                '<p style="text-decoration: underline; font-weight: normal; margin: 0 0 15px 0;">Generalites</p>' +
                '<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">' +
                'Presentez le contexte general de votre projet de stage.' +
                '</p>' +
                '<p style="text-decoration: underline; font-weight: normal; margin: 20px 0 15px 0;">Problematique</p>' +
                '<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">' +
                'Decrivez la problematique a laquelle repond votre projet.' +
                '</p>' +
                '<p style="font-size: 12pt; font-weight: bold; margin: 25px 0 20px 0;">' +
                '<span style="font-weight: bold;">III.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">OBJECTIFS GENERAUX ET SPECIFIQUES</span>' +
                '</p>' +
                '<p style="margin: 0 0 10px 0;"><strong>1. Objectif general</strong></p>' +
                '<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">' +
                'Decrivez l\'objectif principal de votre projet.' +
                '</p>' +
                '<p style="margin: 0 0 10px 0;"><strong>2. Objectifs specifiques</strong></p>' +
                '<p style="margin: 0 0 8px 40px;">- <strong>Objectif specifique 1</strong> : Description</p>' +
                '<p style="margin: 0 0 8px 40px;">- <strong>Objectif specifique 2</strong> : Description</p>' +
                '<p style="margin: 0 0 20px 40px;">- <strong>Objectif specifique 3</strong> : Description</p>' +
                '<p style="font-size: 12pt; font-weight: bold; margin: 25px 0 20px 0;">' +
                '<span style="font-weight: bold;">IV.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">METHODOLOGIE</span>' +
                '</p>' +
                '<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">' +
                'Decrivez la methodologie adoptee pour mener a bien votre projet.' +
                '</p>' +
                '</div>';
        }

        // Mise a l'echelle dynamique de l'apercu
        function scaleCoverPreview() {
            var wrap = document.getElementById('coverPreviewWrap');
            var scaler = document.getElementById('coverPreviewScaler');
            var preview = document.getElementById('coverPreview');
            if (!wrap || !scaler || !preview) return;

            var wrapWidth = wrap.clientWidth - 24; // padding 12px x2
            var previewWidth = preview.scrollWidth;
            if (previewWidth <= 0) return;

            var scale = wrapWidth / previewWidth;
            scaler.style.transform = 'scale(' + scale + ')';
            scaler.style.transformOrigin = 'top left';
            scaler.style.width = previewWidth + 'px';
            // Forcer la hauteur du wrap pour eviter le collapse
            var previewHeight = preview.scrollHeight;
            wrap.style.height = Math.round(previewHeight * scale + 24) + 'px';
        }

        // Update preview
        function updatePreview() {
            coverPreview.innerHTML = generateCoverPageHTML();
            document.getElementById('nom_rapport_hidden').value = document.getElementById('nom_rapport').value;
            document.getElementById('theme_rapport_hidden').value = document.getElementById('titre_theme').value +
                (document.getElementById('sous_titre').value ? ' : ' + document.getElementById('sous_titre').value : '');
            document.getElementById('cover_data').value = JSON.stringify(getCoverData());
            // Laisser le navigateur rendre le contenu avant de calculer l'echelle
            requestAnimationFrame(function () {
                requestAnimationFrame(scaleCoverPreview);
            });
        }

        updatePreview();
        window.addEventListener('resize', scaleCoverPreview);

        if (updatePreviewBtn) {
            updatePreviewBtn.addEventListener('click', function () {
                updatePreview();
                showNotification('success', 'Apercu mis a jour !');
            });
        }

        document.querySelectorAll('#coverPageFormContainer input, #coverPageFormContainer select').forEach(function (el) {
            el.addEventListener('change', updatePreview);
            el.addEventListener('input', updatePreview);
        });

        // Preview full report
        previewBtn.addEventListener('click', function () {
            var coverHTML = generateCoverPageHTML();
            var bodyHTML = '<div style="font-family: \'Times New Roman\', serif; width: 210mm; padding: 15mm 20mm; box-sizing: border-box; background: white;">' + joditEditor.value + '</div>';
            fullPreview.innerHTML = coverHTML + '<div style="page-break-before: always;"></div>' + bodyHTML;
            previewModal.classList.add('is-visible');
        });

        closePreviewModal.addEventListener('click', function () { previewModal.classList.remove('is-visible'); });
        previewModal.addEventListener('click', function (e) { if (e.target === previewModal) previewModal.classList.remove('is-visible'); });

        function nowLabel() {
            var now = new Date();
            return now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit', second: '2-digit' });
        }

        function snapshot() {
            return JSON.stringify({
                nom: document.getElementById('nom_rapport').value.trim(),
                theme: document.getElementById('titre_theme').value.trim(),
                content: joditEditor.value
            });
        }
        var lastSnapshot = snapshot();

        // Save report
        if (saveBtn) {
            saveBtn.addEventListener('click', function () {
                var nomRapport = document.getElementById('nom_rapport').value.trim();
                var titreTheme = document.getElementById('titre_theme').value.trim();

                if (!nomRapport) { showNotification('error', 'Veuillez saisir le nom du rapport'); return; }
                if (!titreTheme) { showNotification('error', 'Veuillez saisir le titre du theme'); return; }

                var fullContent = generateCoverPageHTML() +
                    '<div style="page-break-before: always;"></div>' +
                    '<div style="font-family: Times New Roman, serif; padding: 15mm 20mm;">' + joditEditor.value + '</div>';

                document.getElementById('nom_rapport_hidden').value = nomRapport;
                document.getElementById('theme_rapport_hidden').value = titreTheme +
                    (document.getElementById('sous_titre').value ? ' : ' + document.getElementById('sous_titre').value : '');
                document.getElementById('contenu_rapport').value = fullContent;
                document.getElementById('cover_data').value = JSON.stringify(getCoverData());

                var formData = new FormData(rapportForm);
                saveBtn.disabled = true;

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                    .then(function (response) { return response.json(); })
                    .then(function (result) {
                        if (!result.success) {
                            showNotification('error', result.message || 'Echec de la sauvegarde.');
                            return;
                        }
                        if (result.rapport_id) {
                            var reportId = String(result.rapport_id);
                            var editIdField = rapportForm.querySelector('input[name="edit_id"]');
                            if (editIdField) editIdField.value = reportId;
                            document.getElementById('payloadReportId').value = reportId;
                            var currentUrl = new URL(window.location.href);
                            currentUrl.searchParams.set('edit', reportId);
                            window.history.replaceState({}, '', currentUrl.toString());
                        }
                        autosaveStatus.textContent = 'Sauvegarde a ' + nowLabel();
                        lastSnapshot = snapshot();
                        showNotification('success', result.message || 'Rapport enregistre avec succes.');
                    })
                    .catch(function () {
                        showNotification('error', 'Erreur reseau lors de la sauvegarde.');
                    })
                    .finally(function () {
                        saveBtn.disabled = false;
                    });
            });
        }

        // Export PDF
        exportBtn.addEventListener('click', function () {
            var nomRapport = document.getElementById('nom_rapport').value.trim() || 'Rapport_Stage';
            var fullContent = generateCoverPageHTML() +
                '<div style="page-break-before: always;"></div>' +
                '<div style="font-family: Times New Roman, serif; padding: 15mm 20mm;">' + joditEditor.value + '</div>';

            if (!joditEditor.value || joditEditor.value.trim() === '' || joditEditor.value === '<p><br></p>') {
                showNotification('warning', 'Le corps du rapport est vide. Ajoutez du contenu avant d\'exporter.');
            }

            pdfLoading.classList.add('is-visible');
            exportBtn.disabled = true;

            var formData = new FormData();
            formData.append('action', 'export_pdf');
            formData.append('contenu_rapport', fullContent);
            formData.append('nom_rapport', nomRapport);
            formData.append('theme_rapport', document.getElementById('titre_theme').value || 'Theme du rapport');

            fetch(window.location.href, {
                method: 'POST',
                body: formData,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (response) {
                    if (!response.ok) throw new Error('Erreur HTTP: ' + response.status);
                    var contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        return response.blob();
                    } else {
                        return response.text().then(function (text) {
                            try {
                                var data = JSON.parse(text);
                                throw new Error(data.message || 'Erreur lors de la generation du PDF');
                            } catch (e) {
                                throw new Error('Erreur serveur: ' + text.substring(0, 200));
                            }
                        });
                    }
                })
                .then(function (blob) {
                    if (blob.size === 0) throw new Error('Le PDF genere est vide');
                    var url = window.URL.createObjectURL(blob);
                    var link = document.createElement('a');
                    link.href = url;
                    link.download = nomRapport.replace(/\s+/g, '_') + '.pdf';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                    showNotification('success', 'Rapport exporte en PDF avec succes!');
                })
                .catch(function (error) {
                    console.error('Erreur export PDF:', error);
                    showNotification('error', error.message || 'Erreur lors de l\'export PDF');
                })
                .finally(function () {
                    pdfLoading.classList.remove('is-visible');
                    exportBtn.disabled = false;
                });
        });

        // Deposer button
        if (deposerBtn) {
            deposerBtn.addEventListener('click', function (e) {
                e.preventDefault();
                if (isReadOnly) {
                    showNotification('warning', 'Ce rapport a deja ete depose');
                    return;
                }

                if (!confirm('Etes-vous sur de vouloir deposer ce rapport ? Cette action est irreversible.')) {
                    return;
                }

                var fullContent = generateCoverPageHTML() +
                    '<div style="page-break-before: always;"></div>' +
                    '<div style="font-family: Times New Roman, serif; padding: 15mm 20mm;">' + joditEditor.value + '</div>';

                document.getElementById('nom_rapport_hidden').value = document.getElementById('nom_rapport').value;
                document.getElementById('theme_rapport_hidden').value = document.getElementById('titre_theme').value;
                document.getElementById('contenu_rapport').value = fullContent;
                rapportForm.querySelector('input[name="action"]').value = 'deposer_rapport';
                rapportForm.submit();
            });
        }

        // Autosave
        if (!isReadOnly) {
            setInterval(function () {
                var current = snapshot();
                if (current !== lastSnapshot) {
                    var nomRapport = document.getElementById('nom_rapport').value.trim();
                    var titreTheme = document.getElementById('titre_theme').value.trim();
                    if (!nomRapport || !titreTheme) return;

                    var fullContent = generateCoverPageHTML() +
                        '<div style="page-break-before: always;"></div>' +
                        '<div style="font-family: Times New Roman, serif; padding: 15mm 20mm;">' + joditEditor.value + '</div>';

                    document.getElementById('nom_rapport_hidden').value = nomRapport;
                    document.getElementById('theme_rapport_hidden').value = titreTheme;
                    document.getElementById('contenu_rapport').value = fullContent;
                    document.getElementById('cover_data').value = JSON.stringify(getCoverData());

                    var formData = new FormData(rapportForm);
                    formData.set('action', 'save_rapport');

                    fetch(window.location.href, {
                        method: 'POST',
                        body: formData,
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    })
                        .then(function (response) { return response.json(); })
                        .then(function (result) {
                            if (result.success) {
                                autosaveStatus.textContent = 'Sauvegarde automatiquement a ' + nowLabel();
                                lastSnapshot = snapshot();
                                if (result.rapport_id) {
                                    var reportId = String(result.rapport_id);
                                    var editIdField = rapportForm.querySelector('input[name="edit_id"]');
                                    if (editIdField) editIdField.value = reportId;
                                    document.getElementById('payloadReportId').value = reportId;
                                    var currentUrl = new URL(window.location.href);
                                    currentUrl.searchParams.set('edit', reportId);
                                    window.history.replaceState({}, '', currentUrl.toString());
                                }
                            }
                        })
                        .catch(function () { /* silently fail */ });
                }
            }, 30000);
        }

        // Notification function
        function showNotification(type, message, title) {
            if (typeof window.cmToast === 'function') {
                window.cmToast(message, type === 'error' ? 'danger' : type, 3500);
                return;
            }
            if (window.CM && window.CM.toast && typeof window.CM.toast.show === 'function') {
                window.CM.toast.show(message, type === 'error' ? 'danger' : type, 3500);
                return;
            }
            var container = document.getElementById('notificationContainer');
            var notification = document.createElement('div');
            notification.style.cssText = 'background:#fff; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.15); padding:14px 18px; min-width:300px; max-width:460px; display:flex; align-items:flex-start; gap:10px; transform:translateX(100%); opacity:0; transition:all 0.3s cubic-bezier(0.4,0,0.2,1);';

            var colors = {
                success: { border: '#10b981', bg: '#f0fdf4', icon: 'V' },
                error:   { border: '#ef4444', bg: '#fef2f2', icon: 'X' },
                info:    { border: '#3b82f6', bg: '#eff6ff', icon: 'i' },
                warning: { border: '#f59e0b', bg: '#fffbeb', icon: '!' }
            };
            var c = colors[type] || colors.info;
            notification.style.borderLeft = '4px solid ' + c.border;
            notification.style.background = c.bg;

            var displayTitle = title || type.charAt(0).toUpperCase() + type.slice(1);
            notification.innerHTML = '<div style="flex-shrink:0;font-size:18px;">' + c.icon + '</div>' +
                '<div style="flex:1;min-width:0;">' +
                '<div style="font-weight:600;font-size:14px;margin-bottom:3px;color:#1f2937;">' + displayTitle + '</div>' +
                '<div style="font-size:13px;color:#6b7280;line-height:1.4;">' + message + '</div>' +
                '</div>' +
                '<button style="flex-shrink:0;background:none;border:none;color:#9ca3af;cursor:pointer;font-size:16px;" onclick="this.parentElement.remove()">X</button>';

            container.appendChild(notification);
            setTimeout(function () { notification.style.transform = 'translateX(0)'; notification.style.opacity = '1'; }, 10);
            setTimeout(function () {
                notification.style.transform = 'translateX(100%)';
                notification.style.opacity = '0';
                setTimeout(function () { if (container.contains(notification)) container.removeChild(notification); }, 300);
            }, 4000);
        }
    });
</script>

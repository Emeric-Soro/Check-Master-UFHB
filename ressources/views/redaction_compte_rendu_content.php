<?php
$rapportsValides = is_array($GLOBALS['rapports_valides'] ?? null) ? $GLOBALS['rapports_valides'] : [];
$enseignantsRaw = is_array($GLOBALS['enseignants'] ?? null) ? $GLOBALS['enseignants'] : [];
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writableYearLabel = \AcademicYear::getWritableLabelFromSession();
$academicYearLabels = [];
foreach (\AcademicYear::fetchAll(Database::getConnection()) as $academicYear) {
    $academicYearLabels[(int) ($academicYear['id'] ?? 0)] = (string) ($academicYear['label'] ?? '');
}

$enseignantOptions = [];
foreach ($enseignantsRaw as $enseignant) {
    $id = trim((string) ($enseignant->id_enseignant ?? $enseignant['id_enseignant'] ?? ''));
    if ($id === '') {
        continue;
    }
    $nom = trim((string) ($enseignant->prenom_enseignant ?? $enseignant['prenom_enseignant'] ?? '') . ' ' .
        (string) ($enseignant->nom_enseignant ?? $enseignant['nom_enseignant'] ?? ''));
    $enseignantOptions[$id] = $nom !== '' ? $nom : ('Enseignant #' . $id);
}

$reportsById = [];
$reportSelectOptions = [];
foreach ($rapportsValides as $rapport) {
    $idRapport = (int) ($rapport['id_rapport'] ?? 0);
    if ($idRapport <= 0) {
        continue;
    }
    $numEtu = (string) ($rapport['num_etu'] ?? '');
    $prenom = (string) ($rapport['prenom_etu'] ?? '');
    $nom = (string) ($rapport['nom_etu'] ?? '');
    $theme = (string) ($rapport['theme_rapport'] ?? 'Rapport');
    $decision = strtolower((string) ($rapport['decision_validation'] ?? 'valider'));
    $studentName = trim($prenom . ' ' . $nom);
    if ($studentName === '') {
        $studentName = $numEtu !== '' ? $numEtu : 'Etudiant introuvable';
    }

    $reportsById[$idRapport] = [
        'id_rapport' => $idRapport,
        'num_etu' => $numEtu,
        'theme_rapport' => $theme,
        'student' => $studentName,
        'decision' => $decision,
        'promotion' => trim((string) ($rapport['promotion_etu'] ?? '')),
        'deja_lie_cr' => !empty($rapport['deja_lie_cr']),
    ];

    $reportOptionLabel = '#' . $idRapport . ' - ' . $theme . ' (' . $studentName . ')';
    if (!empty($rapport['deja_lie_cr'])) {
        $reportOptionLabel .= ' - CR existant';
    }
    if ($allYearsSelected) {
        $promotionLabel = trim((string) ($rapport['promotion_etu'] ?? ''));
        if ($promotionLabel === '' && !empty($rapport['id_annee_acad'])) {
            $promotionLabel = $academicYearLabels[(int) $rapport['id_annee_acad']] ?? '';
        }
        if ($promotionLabel !== '') {
            $reportOptionLabel .= ' - ' . $promotionLabel;
            $reportsById[$idRapport]['promotion'] = $promotionLabel;
        }
    }

    $reportSelectOptions[$idRapport] = $reportOptionLabel;
}

$generatedCrName = 'CR_' . date('Y-m-d');

if (!function_exists('cm_cr_build_logo_data_uri')) {
    /**
     * Retourne un logo encodé base64 (compatible navigateur + DOMPDF).
     *
     * @param array<int, string> $candidatePaths
     */
    function cm_cr_build_logo_data_uri(array $candidatePaths): string
    {
        foreach ($candidatePaths as $path) {
            if (!is_string($path) || $path === '' || !is_file($path) || !is_readable($path)) {
                continue;
            }
            $content = @file_get_contents($path);
            if ($content === false) {
                continue;
            }
            $mime = @mime_content_type($path);
            if (!is_string($mime) || $mime === '') {
                $mime = 'image/png';
            }
            return 'data:' . $mime . ';base64,' . base64_encode($content);
        }
        return '';
    }
}

$logoUfhbDataUri = cm_cr_build_logo_data_uri([
    __DIR__ . '/../../public/image/logo_ufhb.png',
    __DIR__ . '/../../public/image/ufhb-logo-sbg.png',
    __DIR__ . '/../../public/image/logo_civ.png',
]);
$logoMiDataUri = cm_cr_build_logo_data_uri([
    __DIR__ . '/../../public/image/logo_mi.png',
    __DIR__ . '/../../public/image/logo_mi_sbg.png',
    __DIR__ . '/../../public/images/logo_mathInfo_fond_blanc.png',
]);

$logoLeftHtml = $logoUfhbDataUri !== ''
    ? '<img src="' . htmlspecialchars($logoUfhbDataUri, ENT_QUOTES, 'UTF-8') . '" alt="Logo UFHB" style="max-height:70px;height:auto;">'
    : '';
$logoRightHtml = $logoMiDataUri !== ''
    ? '<img src="' . htmlspecialchars($logoMiDataUri, ENT_QUOTES, 'UTF-8') . '" alt="Logo UFR MI" style="max-height:70px;height:auto;">'
    : '';

$legacyTemplateHtml = <<<HTML
<table class="header-table" style="width:100%; border-collapse:collapse; margin-bottom:15px;">
    <tr>
        <td style="width:15%; text-align:left; vertical-align:middle;">%LOGO_LEFT%</td>
        <td style="width:70%; text-align:center; vertical-align:middle;">
            <div style="font-size:11pt; font-weight:bold; letter-spacing:0.5px;">Procès-Verbal de séance de validation de thèmes</div>
        </td>
        <td style="width:15%; text-align:right; vertical-align:middle;">%LOGO_RIGHT%</td>
    </tr>
</table>
<hr style="border: 1px solid #C4A000; margin: 10px 0;">
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; text-indent:1.5em;">
    Lieu de réunion : [], le [DATE] s'est tenue de 11 h 00 à 12 h 30 une séance de validation de thèmes de soutenance des étudiants en fin de cycle de la filière MIAGE-GI.
</p>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; text-indent:1.5em;">
    La réunion était animée par Prof KOUA Brou le responsable de ladite filière. Les membres de la commission de validation ont examiné [N] dossiers.
</p>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; text-indent:0;">
    L'ordre du jour debattu est le suivant :
</p>
<ul style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; margin-left:2em;">
    <li>Informations</li>
    <li>Validation de thèmes</li>
    <li>Divers</li>
</ul>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; font-weight:bold; margin-top:20px; margin-bottom:10px;">
    1. Informations
</p>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; text-indent:1.5em;">
    Le responsable de la filière a exposé sur l'intérêt des séances de validation. Il a donné des informations sur le choix des thèmes niveau ingénieur et la tenue mensuelle des séances de validation.
</p>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; text-indent:1.5em;">
    L'organisation des séances de validation permet de faire le point des encadrements, le contenu potentiel de thèmes, et le suivi des mémoires par des encadreurs pédagogiques.
</p>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; font-weight:bold; margin-top:20px; margin-bottom:10px;">
    2. Validation de thèmes
</p>
<div id="casDynamique"></div>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; font-weight:bold; margin-top:20px; margin-bottom:10px;">
    3. Divers
</p>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; text-indent:1.5em;">
    La commission a recommandé au Directeur de la filière d'améliorer le partenariat avec les entreprises.
</p>
<ul style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; margin-left:2em;">
    <li>Respecter toutes les rubriques du template de présentation de thème.</li>
    <li>Joindre un CV contenant une photo d'identité.</li>
    <li>Soutenir au plus tard à la session suivante.</li>
</ul>
<p style="font-family:'Times New Roman', Times, serif; font-size:12pt; line-height:1.5; text-indent:1.5em;">
    Les travaux de la commission ont pris fin à 12 h 30.
</p>
<div style="text-align:right; margin-top:30px; font-family:'Times New Roman', Times, serif; font-size:12pt; font-weight:bold;">
    La commission
</div>
HTML;
$legacyTemplateHtml = strtr($legacyTemplateHtml, [
    '%LOGO_LEFT%' => $logoLeftHtml,
    '%LOGO_RIGHT%' => $logoRightHtml,
]);
?>

<div class="cm-prd3-screen cm-prd3-crud-screen">
    <?php if ($allYearsSelected): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'info',
            'message' => "Affichage global sur toutes les années. La rédaction et l'enregistrement restent limités à l'année active {$writableYearLabel}.",
        ]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <div class="cm-pole-inferieur cm-cr-workspace">
            <div class="cm-grid-2 cm-cr-workspace-grid">
                <div class="cm-card cm-p-md cm-cr-workspace-panel">


                    <div class="cm-form-group">
                        <label class="cm-form-label" for="cmCrReportPicker">Ajouter un rapport</label>
                        <div style="display:flex; gap:0.5rem;">
                            <select id="cmCrReportPicker" class="cm-form-control cm-form-select" style="flex:1;">
                                <option value="">-- Sélectionner un rapport --</option>
                                <?php foreach ($reportSelectOptions as $id => $label): ?>
                                    <option value="<?php echo (int) $id; ?>"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="cm-btn is-info" id="cmCrAddReportBtn">
                                <i class="fas fa-plus" aria-hidden="true"></i>
                            </button>
                        </div>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Rapports selectionnes</label>
                        <div id="cmCrSelectedReports" style="display:grid; gap:0.5rem;"></div>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Infos rapport</label>
                        <div id="cmCrReportInfo" class="cm-card cm-p-sm">
                            <span class="cm-text-sm cm-text-muted">Aucun rapport selectionne.</span>
                        </div>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Affectation encadrants</label>
                        <div id="cmCrAssignments" style="display:grid; gap:0.65rem;"></div>
                    </div>

                    <button type="button" class="cm-btn is-info" id="cmCrLoadTemplateBtn">
                        <i class="fas fa-file-import" aria-hidden="true"></i>
                        Charger le modele
                    </button>
                </div>

                <div class="cm-card cm-p-md cm-cr-workspace-panel">


                    <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmCompteRenduForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}

#cmCompteRenduForm .cm-form-buttons.cm-cr-actions {
    width: 100%;
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(8.25rem, 1fr));
    gap: 0.55rem;
    justify-content: stretch;
    align-items: stretch;
}

#cmCompteRenduForm .cm-form-buttons.cm-cr-actions .cm-btn {
    width: 100%;
    min-width: 0;
    white-space: normal;
    line-height: 1.15;
    justify-content: center;
    text-align: center;
}

#cmCompteRenduForm .cm-form-buttons.cm-cr-actions .cm-btn i {
    flex: 0 0 auto;
}
</style>
<form id="cmCompteRenduForm" method="POST" action="?page=redaction_compte_rendu" data-cm-ajax-form="true">
                        <?php cm_component('form/csrf-token'); ?>
                        <input type="hidden" name="num_etu" id="cmCrNumEtu" value="">
                        <input type="hidden" name="cm_reports_payload" id="cmCrReportsPayload" value="">

                        <?php
                        cm_component('form/input-text', [
                            'name' => 'nom_CR',
                            'id' => 'cmCrNom',
                            'label' => 'Nom du CR (auto)',
                            'value' => $generatedCrName,
                            'readonly' => true,
                            'required' => true,
                        ]);

                        cm_component('editor/wysiwyg-editor', [
                            'name' => 'contenu_CR',
                            'id' => 'cmCrContenu',
                            'label' => '',
                            'height' => 'xl',
                            'placeholder' => 'Redigez le compte rendu...',
                            'value' => $legacyTemplateHtml,
                        ]);
                        ?>

                        <div class="cm-flex cm-flex-between cm-text-sm cm-text-muted cm-mb-sm">
                            <span id="cmCrAutoSaveLabel">Sauvegarde auto: inactive</span>
                            <span id="cmCrLastSaveLabel">Derniere sauvegarde: --:--</span>
                        </div>

                        <div class="cm-form-buttons cm-cr-actions">
                            <button class="cm-btn is-info" type="button" id="cmCrSaveDraftBtn">
                                <i class="fas fa-save" aria-hidden="true"></i>
                                Sauv. brouillon
                            </button>
                            <button class="cm-btn is-info" type="button" id="cmCrToggleAutoSaveBtn">
                                <i class="fas fa-clock" aria-hidden="true"></i>
                                Sauvegarde auto
                            </button>
                            <button class="cm-btn is-info" type="button" id="cmCrPreviewBtn">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                Aperçu
                            </button>
                            <button class="cm-btn is-light" type="button" id="cmCrPrintBtn" onclick="window.print()">
                                <i class="fas fa-print" aria-hidden="true"></i>
                                Imprimer
                            </button>
                            <button class="cm-btn is-primary" type="submit" id="cmCrSubmitBtn">
                                <i class="fas fa-check" aria-hidden="true"></i>
                                Enregistrer PDF
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const reports = <?php echo json_encode($reportsById, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const enseignantOptions = <?php echo json_encode($enseignantOptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const legacyTemplate = <?php echo json_encode($legacyTemplateHtml, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
    const storageKey = 'cm_cr_draft_v1';

    const form = document.getElementById('cmCompteRenduForm');
    const reportPicker = document.getElementById('cmCrReportPicker');
    const addBtn = document.getElementById('cmCrAddReportBtn');
    const selectedContainer = document.getElementById('cmCrSelectedReports');
    const reportInfo = document.getElementById('cmCrReportInfo');
    const assignmentsContainer = document.getElementById('cmCrAssignments');
    const hiddenNumEtu = document.getElementById('cmCrNumEtu');
    const hiddenPayload = document.getElementById('cmCrReportsPayload');
    const editorInput = document.getElementById('cmCrContenu');
    const nomInput = document.getElementById('cmCrNom');

    const saveDraftBtn = document.getElementById('cmCrSaveDraftBtn');
    const autoSaveBtn = document.getElementById('cmCrToggleAutoSaveBtn');
    const previewBtn = document.getElementById('cmCrPreviewBtn');
    const loadTemplateBtn = document.getElementById('cmCrLoadTemplateBtn');
    const draftCountEl = document.getElementById('cmCrDraftCount');
    const autoSaveLabel = document.getElementById('cmCrAutoSaveLabel');
    const lastSaveLabel = document.getElementById('cmCrLastSaveLabel');

    let selectedIds = [];
    let autoSaveTimer = null;
    let draftState = {};

    function buildCasesHtml() {
        let html = '';
        selectedIds.forEach(function (id, index) {
            const report = reports[id];
            if (!report) {
                return;
            }
            const encSelect = document.getElementById('cmCrEnc_' + id);
            const dirSelect = document.getElementById('cmCrDir_' + id);
            const encText = encSelect && encSelect.selectedOptions && encSelect.selectedOptions[0]
                ? encSelect.selectedOptions[0].text
                : '[Non attribué]';
            const dirText = dirSelect && dirSelect.selectedOptions && dirSelect.selectedOptions[0]
                ? dirSelect.selectedOptions[0].text
                : '[Non attribué]';

             html += '' +
                 '<div style="text-align:center; margin:15px 0;"><div style="border:1px solid #000; padding:8px 15px; display:inline-block;">Cas ' + (index + 1) + '</div></div>' +
                 '<div style="margin-bottom:15px; font-family:\\"Times New Roman\\", Times, serif; font-size:12pt; line-height:1.5;">' +
                '<p style="text-indent:0;"><strong>Etudiant :</strong> ' + String(report.student || '').replace(/[<>]/g, '') + '</p>' +
                '<p style="text-indent:0;"><strong>Thème :</strong> ' + String(report.theme_rapport || '').replace(/[<>]/g, '') + '</p>' +
                '<p style="text-indent:0; font-weight:bold;">Recommandations de la commission :</p>' +
                '<ul style="list-style-type:none; margin-left:1em;">' +
                '<li>- thème validé ;</li>' +
                '<li>- bien décrire le processus ;</li>' +
                '<li>- décrire exactement le contexte.</li>' +
                '</ul>' +
                '<p style="text-indent:0; margin-top:15px;"><strong>Directeur de mémoire :</strong> ' + String(dirText || '[Non attribué]').replace(/[<>]/g, '') + '</p>' +
                '<p style="text-indent:0;"><strong>Encadreur pédagogique :</strong> ' + String(encText || '[Non attribué]').replace(/[<>]/g, '') + '</p>' +
                '</div>';
        });
        return html;
    }

    function refreshTemplateCases() {
        const editorDiv = document.getElementById('cmCrContenu_editor');
        if (!editorDiv || !editorInput) {
            return;
        }
        const currentHtml = editorDiv.innerHTML || '';
        if (currentHtml.indexOf('id=\"casDynamique\"') === -1) {
            return;
        }
        const casesHtml = buildCasesHtml();
        const updated = currentHtml.replace(/<div id=\"casDynamique\">[\s\S]*?<\/div>/, '<div id=\"casDynamique\">' + casesHtml + '</div>');
        editorDiv.innerHTML = updated;
        editorInput.value = updated;
    }

    function formatOptionHtml(selectedValue) {
        let html = '<option value=\"\">-- Sélectionner --</option>';
        Object.keys(enseignantOptions).forEach(function (id) {
            const label = String(enseignantOptions[id] || '');
            const selected = String(selectedValue || '') === String(id) ? ' selected' : '';
            html += '<option value=\"' + id + '\"' + selected + '>' + label.replace(/[<>]/g, '') + '</option>';
        });
        return html;
    }

    function syncHiddenData() {
        const payload = [];
        const firstReport = selectedIds.length > 0 ? reports[selectedIds[0]] : null;
        hiddenNumEtu.value = firstReport ? (firstReport.num_etu || '') : '';

        selectedIds.forEach(function (id) {
            payload.push({
                id_rapport: id,
                num_etu: reports[id] ? reports[id].num_etu : '',
                encadrant: document.getElementById('cmCrEnc_' + id) ? document.getElementById('cmCrEnc_' + id).value : '',
                directeur: document.getElementById('cmCrDir_' + id) ? document.getElementById('cmCrDir_' + id).value : ''
            });
        });
        hiddenPayload.value = JSON.stringify(payload);
    }

    function renderInfoCard() {
        if (selectedIds.length === 0) {
            reportInfo.innerHTML = '<span class=\"cm-text-sm cm-text-muted\">Aucun rapport selectionne.</span>';
            return;
        }
        const r = reports[selectedIds[0]];
        const status = r && r.decision === 'valider' ? 'Validé' : 'Rejeté';
        reportInfo.innerHTML = '<div class=\"cm-text-sm\"><strong>Theme:</strong> ' + (r ? r.theme_rapport : '-') + '</div>' +
            '<div class=\"cm-text-sm\"><strong>Etudiant:</strong> ' + (r ? r.student : '-') + '</div>' +
            '<div class=\"cm-text-sm\"><strong>Statut:</strong> ' + status + '</div>';
    }

    function renderSelected() {
        selectedContainer.innerHTML = '';
        assignmentsContainer.innerHTML = '';

        selectedIds.forEach(function (id) {
            const report = reports[id];
            if (!report) {
                return;
            }

            const item = document.createElement('div');
            item.className = 'cm-card cm-p-sm';
            item.innerHTML = '<input type=\"hidden\" name=\"rapports[]\" value=\"' + id + '\">' +
                '<div style=\"display:flex;justify-content:space-between;align-items:center;gap:0.5rem;\">' +
                    '<div class=\"cm-text-sm\"><strong>#' + id + '</strong> - ' + String(report.theme_rapport || '').replace(/[<>]/g, '') +
                    '<br><span class=\"cm-text-muted\">' + String(report.student || '').replace(/[<>]/g, '') + '</span></div>' +
                    '<button type=\"button\" class=\"cm-btn-action is-delete\" data-remove-id=\"' + id + '\"><i class=\"fas fa-times\" aria-hidden=\"true\"></i></button>' +
                '</div>';
            selectedContainer.appendChild(item);

            const block = document.createElement('div');
            block.className = 'cm-card cm-p-sm';
            block.innerHTML = '<div class=\"cm-text-sm cm-text-semibold cm-mb-sm\">Rapport #' + id + '</div>' +
                '<div class=\"cm-grid-2\">' +
                    '<div class=\"cm-form-group\"><label class=\"cm-form-label\" for=\"cmCrEnc_' + id + '\">Encadrant pédagogique</label><select class=\"cm-form-control cm-form-select\" id=\"cmCrEnc_' + id + '\" name=\"encadrant_pedagogique[' + id + ']\">' + formatOptionHtml(draftState['enc_' + id] || '') + '</select></div>' +
                    '<div class=\"cm-form-group\"><label class=\"cm-form-label\" for=\"cmCrDir_' + id + '\">Directeur mémoire</label><select class=\"cm-form-control cm-form-select\" id=\"cmCrDir_' + id + '\" name=\"directeur_memoire[' + id + ']\">' + formatOptionHtml(draftState['dir_' + id] || '') + '</select></div>' +
                '</div>';
            assignmentsContainer.appendChild(block);
        });

        selectedContainer.querySelectorAll('[data-remove-id]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                const id = parseInt(btn.getAttribute('data-remove-id') || '0', 10);
                selectedIds = selectedIds.filter(function (x) { return x !== id; });
                renderSelected();
                syncHiddenData();
            });
        });

        assignmentsContainer.querySelectorAll('select').forEach(function (select) {
            select.addEventListener('change', function () {
                syncHiddenData();
                refreshTemplateCases();
            });
        });

        renderInfoCard();
        syncHiddenData();
        refreshTemplateCases();
    }

    function saveDraft(notify) {
        const payload = {
            nom: nomInput ? nomInput.value : '',
            contenu: editorInput ? editorInput.value : '',
            selectedIds: selectedIds,
            timestamp: Date.now()
        };

        selectedIds.forEach(function (id) {
            const enc = document.getElementById('cmCrEnc_' + id);
            const dir = document.getElementById('cmCrDir_' + id);
            payload['enc_' + id] = enc ? enc.value : '';
            payload['dir_' + id] = dir ? dir.value : '';
        });

        localStorage.setItem(storageKey, JSON.stringify(payload));
        localStorage.setItem(storageKey + '_count', '1');
        if (draftCountEl) {
            draftCountEl.textContent = '1';
        }
        const time = new Date(payload.timestamp);
        if (lastSaveLabel) {
            lastSaveLabel.textContent = 'Derniere sauvegarde: ' + time.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
        }
        if (notify) {
            window.alert('Brouillon sauvegarde localement.');
        }
    }

    function loadDraft() {
        const raw = localStorage.getItem(storageKey);
        if (!raw) {
            return;
        }
        try {
            draftState = JSON.parse(raw) || {};
            if (Array.isArray(draftState.selectedIds)) {
                selectedIds = draftState.selectedIds.map(function (id) { return parseInt(id, 10); }).filter(Boolean);
            }
            if (editorInput && draftState.contenu) {
                editorInput.value = draftState.contenu;
                const editorDiv = document.getElementById('cmCrContenu_editor');
                if (editorDiv) {
                    editorDiv.innerHTML = draftState.contenu;
                }
            }
            renderSelected();
            if (draftState.timestamp && lastSaveLabel) {
                const t = new Date(draftState.timestamp);
                lastSaveLabel.textContent = 'Derniere sauvegarde: ' + t.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            }
        } catch (e) {
            draftState = {};
        }
    }

    function loadTemplate() {
        const editorDiv = document.getElementById('cmCrContenu_editor');
        if (!editorDiv || !editorInput) {
            return;
        }
        const template = legacyTemplate;
        editorDiv.innerHTML = template;
        editorInput.value = template;
        refreshTemplateCases();
    }

    if (addBtn && reportPicker) {
        addBtn.addEventListener('click', function () {
            const id = parseInt(reportPicker.value || '0', 10);
            if (!id || !reports[id]) {
                return;
            }
            if (selectedIds.indexOf(id) !== -1) {
                return;
            }
            selectedIds.push(id);
            renderSelected();
        });
    }

    if (saveDraftBtn) {
        saveDraftBtn.addEventListener('click', function () {
            saveDraft(true);
        });
    }

    if (autoSaveBtn) {
        autoSaveBtn.addEventListener('click', function () {
            if (autoSaveTimer) {
                clearInterval(autoSaveTimer);
                autoSaveTimer = null;
                if (autoSaveLabel) autoSaveLabel.textContent = 'Sauvegarde auto: inactive';
                return;
            }
            autoSaveTimer = setInterval(function () {
                saveDraft(false);
            }, 20000);
            if (autoSaveLabel) autoSaveLabel.textContent = 'Sauvegarde auto: active (20s)';
        });
    }

    if (loadTemplateBtn) {
        loadTemplateBtn.addEventListener('click', loadTemplate);
    }

    if (previewBtn && form) {
        previewBtn.addEventListener('click', function () {
            const tokenInput = form.querySelector('input[name=\"csrf_token\"]');
            if (!tokenInput || !nomInput || !editorInput) {
                return;
            }
            if ((editorInput.value || '').trim() === '') {
                window.alert('Renseignez le contenu du compte rendu.');
                return;
            }

            const previewForm = document.createElement('form');
            previewForm.method = 'POST';
            previewForm.action = '?page=redaction_compte_rendu&action=export_pdf';
            previewForm.target = '_blank';
            previewForm.style.display = 'none';

            [['csrf_token', tokenInput.value], ['nom_CR', nomInput.value], ['contenu_CR', editorInput.value]].forEach(function (pair) {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = pair[0];
                input.value = pair[1];
                previewForm.appendChild(input);
            });
            document.body.appendChild(previewForm);
            previewForm.submit();
            previewForm.remove();
        });
    }

    if (form) {
        form.addEventListener('submit', function (event) {
            if (selectedIds.length === 0) {
                event.preventDefault();
                window.alert('Sélectionnez au moins un rapport.');
                return;
            }
            if ((editorInput.value || '').trim() === '') {
                event.preventDefault();
                window.alert('Le contenu du compte rendu est obligatoire.');
                return;
            }
            syncHiddenData();
        });
    }

    if (draftCountEl) {
        draftCountEl.textContent = localStorage.getItem(storageKey + '_count') || '0';
    }

    loadDraft();
    if ((editorInput.value || '').trim() === '') {
        loadTemplate();
    } else {
        refreshTemplateCases();
    }
    renderSelected();
})();
</script>

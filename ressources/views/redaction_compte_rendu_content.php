<?php
/**
 * CheckMaster Premium - Rédaction des Comptes Rendus
 * Vue refactorisée avec Premium Design System
 */

// ========== DONNÉES ET LOGIQUE PHP ==========

$rapports_valides = $GLOBALS['rapports_valides'] ?? [];
$enseignants = $GLOBALS['enseignants'] ?? [];

// Notifications
$notifType = '';
$notifMsg = '';
if (!empty($_SESSION['success'])) {
    $notifType = 'success';
    $notifMsg = $_SESSION['success'];
    unset($_SESSION['success']);
} elseif (!empty($_SESSION['error'])) {
    $notifType = 'error';
    $notifMsg = $_SESSION['error'];
    unset($_SESSION['error']);
}

// ========== CONSTRUCTION DU CONTENU ==========

ob_start();
?>

<div class="grid grid-2 gap-lg">
    <!-- ===== COLONNE GAUCHE ===== -->
    <div class="space-y-lg">
        
        <!-- Sélection des rapports -->
        <?php
        ob_start();
        ?>
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-file-alt text-primary"></i> Ajouter un rapport
            </label>
            <div class="flex gap-sm items-center">
                <select id="reportSelect" class="form-select flex-1">
                    <option value="">Sélectionner un rapport...</option>
                    <?php foreach ($rapports_valides as $rapport): ?>
                        <option value="<?= htmlspecialchars($rapport['id_rapport']) ?>">
                            <?= htmlspecialchars($rapport['theme_rapport']) ?> - <?= htmlspecialchars($rapport['prenom_etu'] . ' ' . $rapport['nom_etu']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?= renderButton('+', 'primary', true, 'onclick="addReport()" type="button"', 'sm', '', true) ?>
            </div>
        </div>

        <div id="selectedReports" class="space-y-sm">
            <h4 class="text-sm font-semibold text-foreground">Rapports sélectionnés :</h4>
            <div id="reportsList" class="space-y-sm">
                <!-- Les rapports seront ajoutés ici dynamiquement -->
            </div>
        </div>
        <?php
        $selectionCard = ob_get_clean();
        echo renderFullCard(
            'Sélection des rapports',
            $selectionCard,
            '',
            '',
            'Sélectionnez les rapports à inclure dans le compte rendu'
        );
        ?>

        <!-- Informations des rapports -->
        <?php
        ob_start();
        ?>
        <div id="reportDetails" class="space-y-md">
            <p class="text-muted text-sm">Aucun rapport sélectionné</p>
        </div>
        <?php
        $infoCard = ob_get_clean();
        echo renderFullCard(
            'Informations des rapports',
            $infoCard,
            '',
            '',
            'Détails des rapports sélectionnés',
            'fade-in'
        );
        ?>

        <!-- Attribution des encadrants -->
        <div id="attribution-enseignants" class="card fade-in">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        <i class="fas fa-user-tie text-primary"></i>
                        Attribution des encadrants
                    </h3>
                    <p class="card-description">Attribuez les encadrants et directeurs de mémoire</p>
                </div>
            </div>
            <div class="card-content">
                <div id="enseignants-par-rapport-js" class="space-y-md">
                    <!-- Les sélecteurs seront ajoutés ici dynamiquement -->
                </div>
            </div>
        </div>

        <!-- Résumé des évaluations -->
        <div id="evaluationsSummary" class="card fade-in" style="display:none;">
            <div class="card-header">
                <div>
                    <h3 class="card-title">
                        <i class="fas fa-users text-primary"></i>
                        Résumé des évaluations
                    </h3>
                </div>
            </div>
            <div class="card-content">
                <div id="evaluationsContent" class="space-y-sm">
                    <!-- Content will be loaded dynamically -->
                </div>
            </div>
        </div>

        <!-- Template -->
        <div class="card bg-warning-light border-warning">
            <div class="card-content text-center py-lg">
                <?= renderButton('Charger le modèle', 'warning', true, 'onclick="loadTemplate(\'validation_seance\')" type="button"', 'lg', 'fa-file-import') ?>
            </div>
        </div>
    </div>

    <!-- ===== COLONNE DROITE - ÉDITEUR ===== -->
    <div class="card fade-in">
        <!-- Header de l'éditeur -->
        <div class="card-header bg-muted-light">
            <div class="flex items-center justify-between w-full">
                <h3 class="card-title">
                    <i class="fas fa-edit text-warning"></i>
                    Éditeur de compte rendu
                </h3>
                <div class="flex items-center gap-sm">
                    <span class="text-sm text-muted">Dernière sauvegarde: </span>
                    <span id="lastSave" class="text-sm text-success">--:--</span>
                </div>
            </div>
        </div>

        <!-- Barre d'outils -->
        <div class="card-content border-b">
            <div class="flex items-center gap-xs flex-wrap">
                <button onclick="formatText('bold')" class="btn btn-ghost btn-sm" title="Gras" type="button">
                    <i class="fas fa-bold"></i>
                </button>
                <button onclick="formatText('italic')" class="btn btn-ghost btn-sm" title="Italique" type="button">
                    <i class="fas fa-italic"></i>
                </button>
                <button onclick="formatText('underline')" class="btn btn-ghost btn-sm" title="Souligné" type="button">
                    <i class="fas fa-underline"></i>
                </button>
                <div class="separator-vertical"></div>
                <button onclick="insertList('ul')" class="btn btn-ghost btn-sm" title="Liste à puces" type="button">
                    <i class="fas fa-list-ul"></i>
                </button>
                <button onclick="insertList('ol')" class="btn btn-ghost btn-sm" title="Liste numérotée" type="button">
                    <i class="fas fa-list-ol"></i>
                </button>
            </div>
        </div>

        <!-- Contenu de l'éditeur -->
        <div class="card-content">
            <div id="editorContent" class="editor-content border rounded p-lg" contenteditable="true" style="min-height: 500px; font-family: 'Times New Roman', serif;">
                <div class="text-center mb-8">
                    <h1 class="text-3xl font-bold mb-3">COMPTE RENDU D'ÉVALUATION</h1>
                    <h2 class="text-xl font-semibold mb-2">Commission de Validation des Rapports de Soutenance</h2>
                    <p class="text-lg">Université Félix Houphouët-Boigny</p>
                    <p>Institut de Formation et de Recherche en Informatique</p>
                    <p>Département MIAGE</p>
                </div>

                <div class="mb-8">
                    <h3 class="text-xl font-bold border-b-2 pb-3 mb-4">I. INFORMATIONS GÉNÉRALES</h3>
                    <div class="mb-4">
                        <p><strong>Nombre de rapports évalués :</strong><br><span>[À compléter]</span></p>
                        <p><strong>Date d'évaluation :</strong><br><span>[À compléter]</span></p>
                        <p><strong>Membres de la commission d'évaluation :</strong><br><span>[À compléter]</span></p>
                    </div>
                    <div class="bg-info-light p-4 rounded border-l-4 border-info">
                        <h4 class="font-semibold mb-2">Rapports évalués :</h4>
                        <div>[À compléter]</div>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xl font-bold border-b-2 pb-3 mb-4">II. PRÉSENTATION DES TRAVAUX</h3>
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold mb-2">2.1 Contexte général</h4>
                        <p class="italic">[Présentation du contexte général et des problématiques abordées dans les rapports...]</p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold mb-2">2.2 Objectifs et méthodologies</h4>
                        <p class="italic">[Description des objectifs poursuivis et des méthodologies adoptées dans les différents travaux...]</p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold mb-2">2.3 Résultats obtenus</h4>
                        <p class="italic">[Synthèse des principaux résultats obtenus dans l'ensemble des travaux...]</p>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xl font-bold border-b-2 pb-3 mb-4">III. ANALYSE ET ÉVALUATION</h3>
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold mb-2">3.1 Points forts identifiés</h4>
                        <ul class="ml-6">
                            <li>[À compléter]</li>
                            <li>[À compléter]</li>
                        </ul>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold mb-2">3.2 Points d'amélioration</h4>
                        <ul class="ml-6">
                            <li>[À compléter]</li>
                            <li>[À compléter]</li>
                        </ul>
                    </div>
                </div>

                <div class="mb-8">
                    <h3 class="text-xl font-bold border-b-2 pb-3 mb-4">IV. DÉCISIONS ET RECOMMANDATIONS</h3>
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold mb-2">4.1 Décisions prises</h4>
                        <p class="italic">[Décisions de la commission concernant les rapports...]</p>
                    </div>
                    <div class="mb-4">
                        <h4 class="text-lg font-semibold mb-2">4.2 Recommandations</h4>
                        <p class="italic">[Recommandations pour la suite...]</p>
                    </div>
                </div>

                <div class="mt-12">
                    <h3 class="text-xl font-bold border-b-2 pb-3 mb-4">V. SIGNATURES</h3>
                    <div class="grid grid-3 gap-md mt-8">
                        <div>
                            <p class="font-semibold">Le Président de la Commission</p>
                            <p class="mt-8 border-t border-foreground inline-block min-w-[200px]"></p>
                        </div>
                        <div>
                            <p class="font-semibold">Le Secrétaire</p>
                            <p class="mt-8 border-t border-foreground inline-block min-w-[200px]"></p>
                        </div>
                        <div>
                            <p class="font-semibold">Le Rapporteur</p>
                            <p class="mt-8 border-t border-foreground inline-block min-w-[200px]"></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Formulaire de soumission -->
<?php if (canCreate()): ?>
<form id="formCR" method="POST" action="?page=redaction_compte_rendu">
    <input type="hidden" name="num_etu" id="num_etu" value="">
    <input type="hidden" name="nom_CR" id="nom_CR" value="">
    <input type="hidden" name="contenu_CR" id="contenu_CR" value="">
    <div class="flex justify-end mt-lg">
        <?= renderButton('Aperçu', 'outline', true, 'onclick="showPreviewModal()" type="button"', '', 'fa-eye') ?>
        <?= renderButton('Enregistrer le compte rendu', 'success', true, 'onclick="submitCR()" type="button"', 'lg', 'fa-save') ?>
    </div>
</form>
<?php endif; ?>

<?php
$pageContent = ob_get_clean();
echo $pageContent;
?>

<!-- ========== MODALE APERÇU ========== -->
<?php
ob_start();
?>
<div id="previewContent" class="preview-content" style="font-family: 'Times New Roman', serif; line-height: 1.6;">
    <!-- Preview content will be inserted here -->
</div>
<?php
$previewContent = ob_get_clean();

$previewFooter = renderButton('Fermer', 'secondary', true, 'onclick="CM.Modal.hide(\'previewModal\')" type="button"');
$previewFooter .= ' ';
$previewFooter .= renderButton('Imprimer', 'primary', true, 'onclick="printReport()" type="button"', '', 'fa-print');
$previewFooter .= ' ';
$previewFooter .= renderButton('Exporter PDF', 'danger', true, 'onclick="exportToPDF()" type="button"', '', 'fa-file-pdf');

echo renderModal(
    'previewModal',
    'Aperçu du compte rendu',
    $previewContent,
    $previewFooter,
    'xl'
);
?>

<!-- Toast notification -->
<div id="toastNotif" style="display:none; position:fixed; top:30px; right:30px; z-index:9999; min-width:250px;" class="transition-opacity">
    <div id="toastContent" class="alert">
        <span id="toastIcon"></span>
        <span id="toastMsg"></span>
    </div>
</div>

<!-- ========== JAVASCRIPT ========== -->

<script>
// ========== VARIABLES GLOBALES ==========
let selectedReports = [];
const rapportsData = <?php echo json_encode($rapports_valides); ?>;
const enseignantsData = <?php echo json_encode($enseignants); ?>;
let autoSaveInterval = null;

// ========== GESTION DES RAPPORTS ==========

function getRapportById(id) {
    return rapportsData.find(r => r.id_rapport == id);
}

function addReport() {
    const select = document.getElementById('reportSelect');
    const id = select.value;
    
    if (!id) {
        CM.Toast.show('Veuillez sélectionner un rapport', 'warning');
        return;
    }
    
    if (selectedReports.find(r => r.id_rapport == id)) {
        CM.Toast.show('Ce rapport est déjà sélectionné', 'info');
        return;
    }
    
    const rapport = getRapportById(id);
    if (!rapport) return;
    
    selectedReports.push(rapport);
    
    // Ajouter à la liste visuelle
    const reportsList = document.getElementById('reportsList');
    const div = document.createElement('div');
    div.id = 'rapport-cas-' + id;
    div.className = 'flex items-center justify-between p-sm bg-muted-light rounded border';
    div.innerHTML = `
        <div class="flex-1">
            <p class="font-semibold text-sm">${rapport.theme_rapport}</p>
            <p class="text-xs text-muted">${rapport.prenom_etu} ${rapport.nom_etu}</p>
        </div>
        <button onclick="removeReport('${id}')" class="btn btn-ghost btn-sm text-danger" type="button">
            <i class="fas fa-times"></i>
        </button>
    `;
    reportsList.appendChild(div);
    
    // Réinitialiser le select
    select.value = '';
    
    updateAttributionEnseignants();
    showReportDetails();
    updateEditorWithReportData();
}

function removeReport(id) {
    selectedReports = selectedReports.filter(r => r.id_rapport != id);
    
    const div = document.getElementById('rapport-cas-' + id);
    if (div) div.remove();
    
    updateAttributionEnseignants();
    showReportDetails();
    updateEditorWithReportData();
}

// ========== AFFICHAGE DES DÉTAILS ==========

function showReportDetails() {
    const reportDetails = document.getElementById('reportDetails');
    const evaluationsSummary = document.getElementById('evaluationsSummary');
    
    if (selectedReports.length === 0) {
        reportDetails.innerHTML = '<p class="text-muted text-sm">Aucun rapport sélectionné</p>';
        evaluationsSummary.style.display = 'none';
        return;
    }
    
    let html = '';
    selectedReports.forEach((rapport, index) => {
        html += `
            <div class="border border-muted rounded p-md bg-muted-light">
                <div class="flex items-center justify-between mb-sm">
                    <h4 class="font-semibold">Rapport ${index + 1}</h4>
                    ${renderBadge('Validé', 'success')}
                </div>
                <div class="grid grid-2 gap-sm text-sm">
                    <div><span class="font-medium">Étudiant :</span> ${rapport.prenom_etu} ${rapport.nom_etu}</div>
                    <div><span class="font-medium">Thème :</span> ${rapport.theme_rapport}</div>
                </div>
            </div>
        `;
    });
    
    reportDetails.innerHTML = html;
    evaluationsSummary.style.display = 'block';
}

// ========== ATTRIBUTION DES ENSEIGNANTS ==========

function updateAttributionEnseignants() {
    const container = document.getElementById('enseignants-par-rapport-js');
    container.innerHTML = '';
    
    if (selectedReports.length === 0) {
        container.innerHTML = '<p class="text-muted text-sm">Aucun rapport sélectionné</p>';
        return;
    }
    
    selectedReports.forEach((rapport, idx) => {
        const div = document.createElement('div');
        div.className = 'p-md bg-muted-light rounded border';
        div.innerHTML = `
            <h4 class="font-semibold mb-sm text-sm">Rapport ${idx + 1}: ${rapport.theme_rapport}</h4>
            <div class="grid grid-2 gap-sm">
                <div class="form-group">
                    <label class="form-label text-sm">Encadrant pédagogique</label>
                    <select id="encadrant_${rapport.id_rapport}" class="form-select">
                        <option value="">Sélectionner</option>
                        ${enseignantsData.map(e => `<option value="${e.id_enseignant}">${e.nom_enseignant} ${e.prenom_enseignant}</option>`).join('')}
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label text-sm">Directeur de mémoire</label>
                    <select id="directeur_${rapport.id_rapport}" class="form-select">
                        <option value="">Sélectionner</option>
                        ${enseignantsData.map(e => `<option value="${e.id_enseignant}">${e.nom_enseignant} ${e.prenom_enseignant}</option>`).join('')}
                    </select>
                </div>
            </div>
        `;
        container.appendChild(div);
        
        // Listeners pour mise à jour dynamique
        div.querySelector(`#encadrant_${rapport.id_rapport}`).addEventListener('change', function() {
            rapport.encadrant_nom = this.options[this.selectedIndex].text;
            updateEditorWithReportData();
        });
        
        div.querySelector(`#directeur_${rapport.id_rapport}`).addEventListener('change', function() {
            rapport.directeur_nom = this.options[this.selectedIndex].text;
            updateEditorWithReportData();
        });
    });
}

// ========== ÉDITEUR ==========

function formatText(command) {
    document.execCommand(command, false, null);
}

function insertList(type) {
    const command = type === 'ul' ? 'insertUnorderedList' : 'insertOrderedList';
    document.execCommand(command, false, null);
}

function updateEditorWithReportData() {
    if (selectedReports.length === 0) return;
    
    let casHTML = '';
    selectedReports.forEach((rapport, idx) => {
        const encadrant = rapport.encadrant_nom || '<span class="text-muted">[Non attribué]</span>';
        const directeur = rapport.directeur_nom || '<span class="text-muted">[Non attribué]</span>';
        
        casHTML += `
            <div class="mb-4 p-3 bg-muted-light rounded">
                <h4 class="font-semibold">Cas ${idx + 1}</h4>
                <p><strong>Étudiant :</strong> ${rapport.prenom_etu} ${rapport.nom_etu}</p>
                <p><strong>Thème :</strong> ${rapport.theme_rapport}</p>
                <p><strong>Encadrant :</strong> ${encadrant}</p>
                <p><strong>Directeur :</strong> ${directeur}</p>
            </div>
        `;
    });
    
    // Mise à jour du contenu si le template est chargé
    const editor = document.getElementById('editorContent');
    const rapportsSection = editor.querySelector('.bg-info-light div');
    if (rapportsSection) {
        rapportsSection.innerHTML = casHTML;
    }
}

// ========== TEMPLATES ==========

function loadTemplate(type) {
    if (selectedReports.length === 0) {
        CM.Toast.show('Veuillez sélectionner au moins un rapport', 'warning');
        return;
    }
    
    const editor = document.getElementById('editorContent');
    
    if (type === 'validation_seance') {
        editor.innerHTML = `
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold mb-3">Procès-Verbal de séance de validation de thèmes</h1>
                <h2 class="text-xl font-semibold mb-2">Commission de Validation</h2>
                <p class="text-lg">Université Félix Houphouët-Boigny</p>
                <p>Date: ${new Date().toLocaleDateString('fr-FR')}</p>
            </div>
            
            <div class="mb-6">
                <h3 class="text-xl font-bold border-b-2 pb-2 mb-3">Rapports validés</h3>
                <div class="bg-info-light p-4 rounded">
                    <div id="rapportsContent"></div>
                </div>
            </div>
        `;
        updateEditorWithReportData();
    }
    
    CM.Toast.show('Modèle chargé avec succès', 'success');
}

// ========== SOUMISSION ==========

function submitCR() {
    if (selectedReports.length === 0) {
        CM.Toast.show('Veuillez sélectionner au moins un rapport', 'warning');
        return;
    }
    
    document.getElementById('contenu_CR').value = document.getElementById('editorContent').innerHTML;
    const selected = selectedReports[0] || {};
    document.getElementById('num_etu').value = selected.num_etu || '';
    document.getElementById('nom_CR').value = 'Compte rendu séance du ' + new Date().toLocaleDateString('fr-FR');
    
    // Ajouter les IDs des rapports
    const form = document.getElementById('formCR');
    document.querySelectorAll('input[name="rapports[]"]').forEach(e => e.remove());
    
    selectedReports.forEach(rapport => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'rapports[]';
        input.value = rapport.id_rapport;
        form.appendChild(input);
        
        // Encadrants et directeurs
        const encadrantSelect = document.getElementById(`encadrant_${rapport.id_rapport}`);
        const directeurSelect = document.getElementById(`directeur_${rapport.id_rapport}`);
        
        if (encadrantSelect && encadrantSelect.value) {
            const inputEnc = document.createElement('input');
            inputEnc.type = 'hidden';
            inputEnc.name = `encadrant_pedagogique[${rapport.id_rapport}]`;
            inputEnc.value = encadrantSelect.value;
            form.appendChild(inputEnc);
        }
        
        if (directeurSelect && directeurSelect.value) {
            const inputDir = document.createElement('input');
            inputDir.type = 'hidden';
            inputDir.name = `directeur_memoire[${rapport.id_rapport}]`;
            inputDir.value = directeurSelect.value;
            form.appendChild(inputDir);
        }
    });
    
    form.submit();
}

// ========== APERÇU ET EXPORT ==========

function showPreviewModal() {
    const previewContent = document.getElementById('previewContent');
    const editorContent = document.getElementById('editorContent').innerHTML;
    previewContent.innerHTML = editorContent;
    CM.Modal.show('previewModal');
}

function printReport() {
    const content = document.getElementById('previewContent').innerHTML;
    const printWindow = window.open('', '', 'width=800,height=600');
    printWindow.document.write(`
        <html>
        <head>
            <title>Compte Rendu</title>
            <style>
                body { font-family: 'Times New Roman', serif; line-height: 1.6; padding: 20px; }
                h1 { text-align: center; }
                @media print { body { margin: 0; } }
            </style>
        </head>
        <body>${content}</body>
        </html>
    `);
    printWindow.document.close();
    printWindow.print();
}

function exportToPDF() {
    CM.Toast.show('Export PDF en cours...', 'info');
    // Implémenter l'export PDF ici
}

// ========== AUTO-SAVE ==========

function startAutoSave() {
    autoSaveInterval = setInterval(() => {
        const now = new Date();
        document.getElementById('lastSave').textContent = 
            now.getHours().toString().padStart(2, '0') + ':' + 
            now.getMinutes().toString().padStart(2, '0');
    }, 60000); // Toutes les minutes
}

// ========== INITIALISATION ==========

window.addEventListener('DOMContentLoaded', function() {
    // Notifications
    const notifType = <?php echo json_encode($notifType); ?>;
    const notifMsg = <?php echo json_encode($notifMsg); ?>;
    
    if (notifType && notifMsg) {
        CM.Toast.show(notifMsg, notifType);
    }
    
    // Démarrer l'auto-save
    startAutoSave();
});
</script>

<style>
.editor-content {
    min-height: 400px;
    font-family: 'Times New Roman', serif;
}

.editor-content:focus {
    outline: none;
}

.fade-in {
    animation: fadeIn 0.3s ease-in;
}

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

@media print {
    body * {
        visibility: hidden;
    }
    #editorContent, #editorContent * {
        visibility: visible;
    }
    #editorContent {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
    }
}
</style>

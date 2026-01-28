<?php
$rapports_valides = $GLOBALS['rapports_valides'] ?? [];
$enseignants = $GLOBALS['enseignants'] ?? [];
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

// Encoder les logos en base64 pour DOMPDF
$logoUfhbPath = __DIR__ . '/../../public/image/logo_ufhb.png';
$logoMiPath = __DIR__ . '/../../public/image/logo_mi.png';

$logoUfhbBase64 = '';
$logoMiBase64 = '';

if (file_exists($logoUfhbPath) && is_readable($logoUfhbPath)) {
    $type = mime_content_type($logoUfhbPath) ?: 'image/png';
    $data = base64_encode(file_get_contents($logoUfhbPath));
    $logoUfhbBase64 = 'data:' . $type . ';base64,' . $data;
}

if (file_exists($logoMiPath) && is_readable($logoMiPath)) {
    $type = mime_content_type($logoMiPath) ?: 'image/png';
    $data = base64_encode(file_get_contents($logoMiPath));
    $logoMiBase64 = 'data:' . $type . ';base64,' . $data;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rédaction des Comptes Rendus</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .editor-toolbar {
            border-bottom: 1px solid #e5e7eb;
        }
        .editor-button {
            transition: all 0.2s ease;
        }
        .editor-button:hover {
            background-color: #f3f4f6;
            transform: scale(1.05);
        }
        .editor-content {
            min-height: 400px;
            font-family: 'Times New Roman', serif;
        }
        .template-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
        }
        .progress-step {
            transition: all 0.3s ease;
        }
        .progress-step.active {
            background-color: #f59e0b;
            color: white;
        }
        .progress-step.completed {
            background-color: #10b981;
            color: white;
        }
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
        }
        .preview-content {
            font-family: 'Times New Roman', serif;
            line-height: 1.6;
        }

        /* Styles pour masquer les éléments lors de l'impression */
        @media print {
            /* Masquer seulement les éléments d'interface */
            .editor-toolbar,
            .template-section,
            #toastNotif,
            button,
            select,
            input,
            .bg-gray-50,
            .bg-white,
            .shadow,
            .rounded-lg,
            .flex,
            .grid,
            .lg\\:col-span-2,
            .lg\\:grid-cols-3,
            .space-y-6,
            .space-y-3,
            .space-y-2,
            .space-y-4,
            .space-x-3,
            .space-x-2,
            .p-6,
            .px-6,
            .py-3,
            .py-4,
            .mb-8,
            .mb-4,
            .mt-4,
            .mt-12,
            .max-w-7xl,
            .mx-auto,
            .overflow-hidden,
            .border-t,
            .border-gray-200 {
                display: none !important;
            }

            /* Reset du body */
            body {
                margin: 0 !important;
                padding: 0 !important;
                background: white !important;
            }

            /* Formatage du contenu de l'éditeur */
            #editorContent {
                position: static !important;
                left: auto !important;
                top: auto !important;
                margin: 0 !important;
                padding: 20px !important;
                border: none !important;
                box-shadow: none !important;
                background: white !important;
                width: 100% !important;
                height: auto !important;
                min-height: auto !important;
                font-family: 'Times New Roman', serif !important;
                line-height: 1.6 !important;
                color: black !important;
            }
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Main content area -->
            <div class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto p-6">
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <!-- Left column - Report selection and info -->
                        <div class="space-y-6">
                            <!-- Report Selection -->
                            <div class="bg-white rounded-lg shadow p-6 fade-in">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                    <i class="fas fa-file-alt text-blue-600 mr-2"></i>
                                    Sélection des rapports
                                </h3>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Ajouter un rapport</label>
                                        <div class="flex items-center space-x-2">
                                            <select id="reportSelect" class="flex-1 p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" style="height:36px; width: 100px;">
                                            <option value="">Sélectionner un rapport...</option>
                                                <?php foreach (
                                                    $rapports_valides as $rapport): ?>
                                                    <option value="<?= htmlspecialchars($rapport['id_rapport']) ?>">
                                                        <?= htmlspecialchars($rapport['theme_rapport']) ?> - <?= htmlspecialchars($rapport['prenom_etu'] . ' ' . $rapport['nom_etu']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                        </select>
                                            <button onclick="addReport()"
                                                class="bg-blue-200 text-gray-700 rounded-full hover:bg-gray-300 focus:ring-2 focus:ring-blue-400 flex items-center justify-center"
                                                style="height:28px; width:28px; min-width:28px;">
                                                <i class="fas fa-plus" style="font-size:14px;"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Liste des rapports sélectionnés -->
                                    <div id="selectedReports" class="space-y-2">
                                        <h4 class="text-sm font-medium text-gray-700 mt-4">Rapports sélectionnés :</h4>
                                        <div id="reportsList" class="space-y-2">
                                            <!-- Les rapports seront ajoutés ici dynamiquement -->
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Report Info -->
                            <div id="reportInfo" class="bg-white rounded-lg shadow p-6 fade-in">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                    <i class="fas fa-info-circle text-green-600 mr-2"></i>
                                    Informations des rapports
                                </h3>
                                <div id="reportDetails" class="space-y-4">
                                    <!-- Content will be loaded dynamically -->
                                </div>
                            </div>

                            <!-- Attribution dynamique des encadrants/directeurs -->
                            <div id="attribution-enseignants" class="bg-white rounded-lg shadow p-6 fade-in mt-6 mb-8">
                                <h3 class="text-lg font-semibold text-gray-800 mb-2">
                                    <i class="fas fa-user-tie text-blue-600 mr-2"></i>
                                    Attribution des encadrants et directeurs de mémoire
                                </h3>
                                <div id="enseignants-par-rapport-js" class="flex flex-col gap-4">
                                    <!-- Les sélecteurs seront ajoutés ici dynamiquement -->
                                </div>
                            </div>

                            <!-- Evaluations Summary -->
                            <div id="evaluationsSummary" class="bg-white rounded-lg shadow p-6 fade-in hidden">
                                <h3 class="text-lg font-semibold text-gray-800 mb-4">
                                    <i class="fas fa-users text-purple-600 mr-2"></i>
                                    Résumé des évaluations
                                </h3>
                                <div id="evaluationsContent" class="space-y-3">
                                    <!-- Content will be loaded dynamically -->
                                </div>
                            </div>

                            <!-- Templates -->
                            <div class="template-section p-6 rounded-lg shadow mb-8 text-center">
                                <button onclick="loadTemplate('validation_seance')" class="px-6 py-3 bg-yellow-500 text-white rounded-lg shadow hover:bg-yellow-600 transition-colors font-semibold text-lg">
                                    <i class="fas fa-file-import mr-2"></i>Charger le modèle
                                    </button>
                            </div>
                        </div>

                        <!-- Right column - Editor -->
                        <div class="lg:col-span-2">
                            <div class="bg-white rounded-lg shadow overflow-hidden fade-in">
                                <!-- Editor Header -->
                                <div class="editor-toolbar px-6 py-3 bg-gray-50">
                                    <div class="flex items-center justify-between">
                                        <h3 class="text-lg font-semibold text-gray-800">
                                            <i class="fas fa-edit text-yellow-600 mr-2"></i>
                                            Éditeur de compte rendu
                                        </h3>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-sm text-gray-600">Dernière sauvegarde: </span>
                                            <span id="lastSave" class="text-sm text-green-600">--:--</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Toolbar -->
                                <div class="editor-toolbar px-6 py-3 bg-white">
                                    <div class="flex items-center space-x-2">
                                        <button onclick="formatText('bold')" class="editor-button p-2 rounded hover:bg-gray-100" title="Gras">
                                            <i class="fas fa-bold"></i>
                                        </button>
                                        <button onclick="formatText('italic')" class="editor-button p-2 rounded hover:bg-gray-100" title="Italique">
                                            <i class="fas fa-italic"></i>
                                        </button>
                                        <button onclick="formatText('underline')" class="editor-button p-2 rounded hover:bg-gray-100" title="Souligné">
                                            <i class="fas fa-underline"></i>
                                        </button>
                                        <div class="w-px h-6 bg-gray-300 mx-2"></div>
                                        <button onclick="insertList('ul')" class="editor-button p-2 rounded hover:bg-gray-100" title="Liste à puces">
                                            <i class="fas fa-list-ul"></i>
                                        </button>
                                        <button onclick="insertList('ol')" class="editor-button p-2 rounded hover:bg-gray-100" title="Liste numérotée">
                                            <i class="fas fa-list-ol"></i>
                                        </button>
                                        <div class="w-px h-6 bg-gray-300 mx-2"></div>
                                    </div>
                                </div>

                                <!-- Editor Content -->
                                <div class="p-6">
                                    <div id="editorContent" class="editor-content w-full p-4 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500" contenteditable="true" style="min-height: 500px;">
                                        <div class="text-center mb-8">
                                            <h1 class="text-3xl font-bold mb-3 text-gray-800">COMPTE RENDU D'ÉVALUATION</h1>
                                            <h2 class="text-xl font-semibold text-gray-700 mb-2">Commission de Validation des Rapports de Soutenance</h2>
                                            <p class="text-gray-600 text-lg">Université Félix Houphouët-Boigny</p>
                                            <p class="text-gray-600">Institut de Formation et de Recherche en Informatique</p>
                                            <p class="text-gray-600">Département MIAGE</p>
                                        </div>

                                        <div class="mb-8">
                                            <h3 class="text-xl font-bold border-b-2 border-gray-400 pb-3 mb-4 text-gray-800">I. INFORMATIONS GÉNÉRALES</h3>
                                            <div class="mb-4">
                                                <p><strong class="text-gray-700">Nombre de rapports évalués :</strong><br><span class="text-gray-600">[À compléter]</span></p>
                                                    <p><strong class="text-gray-700">Date d'évaluation :</strong><br><span class="text-gray-600">[À compléter]</span></p>
                                                <p><strong class="text-gray-700">Membres de la commission d'évaluation :</strong><br><span class="text-gray-600">[À compléter]</span></p>
                                                </div>

                                            <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                                                <h4 class="font-semibold text-gray-700 mb-2">Rapports évalués :</h4>
                                                <div class="text-gray-600">
                                                    [À compléter]
                                            </div>
                                            </div>
                                        </div>

                                        <div class="mb-8">
                                            <h3 class="text-xl font-bold border-b-2 border-gray-400 pb-3 mb-4 text-gray-800">II. PRÉSENTATION DES TRAVAUX</h3>
                                            <div class="mb-4">
                                                <h4 class="text-lg font-semibold mb-2 text-gray-700">2.1 Contexte général</h4>
                                                <p class="text-gray-600 italic">[Présentation du contexte général et des problématiques abordées dans les rapports...]</p>
                                            </div>
                                            <div class="mb-4">
                                                <h4 class="text-lg font-semibold mb-2 text-gray-700">2.2 Objectifs et méthodologies</h4>
                                                <p class="text-gray-600 italic">[Description des objectifs poursuivis et des méthodologies adoptées dans les différents travaux...]</p>
                                            </div>
                                            <div class="mb-4">
                                                <h4 class="text-lg font-semibold mb-2 text-gray-700">2.3 Résultats obtenus</h4>
                                                <p class="text-gray-600 italic">[Synthèse des principaux résultats obtenus dans l'ensemble des travaux...]</p>
                                            </div>
                                        </div>

                                        <div class="mb-8">
                                            <h3 class="text-xl font-bold border-b-2 border-gray-400 pb-3 mb-4 text-gray-800">III. ÉVALUATIONS PAR RAPPORT</h3>
                                            <p class="text-gray-600 italic mb-4">[Les évaluations détaillées de chaque rapport seront automatiquement insérées ici...]</p>

                                            <div class="bg-gray-50 p-4 rounded-lg border-l-4 border-blue-500">
                                                <h4 class="font-semibold text-gray-700 mb-2">Résumé global des votes :</h4>
                                                <p class="text-sm text-gray-600">Total des votes favorables : [X]/[Total]</p>
                                                <p class="text-sm text-gray-600">Total des votes défavorables : [X]/[Total]</p>
                                                <p class="text-sm text-gray-600">Taux de validation global : [X]%</p>
                                            </div>
                                        </div>

                                        <div class="mb-8">
                                            <h3 class="text-xl font-bold border-b-2 border-gray-400 pb-3 mb-4 text-gray-800">IV. ANALYSE ET SYNTHÈSE GLOBALES</h3>
                                            <div class="mb-4">
                                                <h4 class="text-lg font-semibold mb-2 text-gray-700">4.1 Points forts de l'ensemble des travaux</h4>
                                                <ul class="list-disc list-inside text-gray-600 ml-4">
                                                    <li>[Point fort global 1]</li>
                                                    <li>[Point fort global 2]</li>
                                                    <li>[Point fort global 3]</li>
                                                </ul>
                                            </div>
                                            <div class="mb-4">
                                                <h4 class="text-lg font-semibold mb-2 text-gray-700">4.2 Points à améliorer</h4>
                                                <ul class="list-disc list-inside text-gray-600 ml-4">
                                                    <li>[Point à améliorer global 1]</li>
                                                    <li>[Point à améliorer global 2]</li>
                                                    <li>[Point à améliorer global 3]</li>
                                                </ul>
                                            </div>
                                            <div class="mb-4">
                                                <h4 class="text-lg font-semibold mb-2 text-gray-700">4.3 Recommandations générales</h4>
                                                <p class="text-gray-600 italic">[Recommandations pour l'amélioration de l'ensemble des travaux...]</p>
                                            </div>
                                        </div>

                                        <div class="mb-8">
                                            <h3 class="text-xl font-bold border-b-2 border-gray-400 pb-3 mb-4 text-gray-800">V. DÉCISIONS DE LA COMMISSION</h3>
                                            <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                                                <p class="text-lg font-semibold text-gray-800 mb-2">Décisions finales par rapport :</p>
                                                <div class="text-gray-600">
                                                    [Les décisions finales pour chaque rapport seront ajoutées ici]
                                                </div>
                                            </div>
                                            <div class="mt-4">
                                                <h4 class="text-lg font-semibold mb-2 text-gray-700">Justification des décisions :</h4>
                                                <p class="text-gray-600 italic">[Justification détaillée des décisions prises par la commission pour chaque rapport...]</p>
                                            </div>
                                        </div>

                                        <div class="mt-12 text-center">
                                            <p class="text-gray-600 mb-4">Fait à Abidjan, le [DATE]</p>
                                            <div class="flex justify-center space-x-8">
                                                <div class="text-center">
                                                    <p class="font-semibold text-gray-700">Président de la Commission</p>
                                                    <p class="text-gray-600">[Signature]</p>
                                                </div>
                                                <div class="text-center">
                                                    <p class="font-semibold text-gray-700">Rapporteur</p>
                                                    <p class="text-gray-600">[Signature]</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <button onclick="saveAsDraft()" class="flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-md hover:bg-blue-700">
                                                <i class="fas fa-save mr-2"></i>Sauvegarder en brouillon
                                            </button>
                                            <button onclick="autoSave()" class="flex items-center px-3 py-2 bg-gray-600 text-white text-sm font-medium rounded-md hover:bg-gray-700">
                                                <i class="fas fa-clock mr-2"></i>Sauvegarde auto
                                            </button>
                                        </div>
                                        <div class="flex items-center space-x-3">
                                            <button onclick="printReport()" class="flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700">
                                                <i class="fas fa-print mr-2"></i>Imprimer
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity modal-overlay" onclick="closePreviewModal()"></div>

            <div class="inline-block w-full max-w-4xl p-0 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-eye text-green-600 mr-2"></i>
                        Aperçu du compte rendu
                    </h3>
                    <div class="flex items-center space-x-2">
                        <button onclick="printReport()" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-print mr-1"></i>Imprimer
                        </button>
                        <button onclick="exportToPDF()" class="px-3 py-1 text-sm bg-red-600 text-white rounded hover:bg-red-700">
                            <i class="fas fa-file-pdf mr-1"></i>PDF
                        </button>
                        <button onclick="closePreviewModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="px-8 py-6 max-h-96 overflow-y-auto">
                    <div id="previewContent" class="preview-content">
                        <!-- Preview content will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (canCreate()): ?>
    <form id="formCR" method="POST" action="?page=redaction_compte_rendu">
        <input type="hidden" name="num_etu" id="num_etu" value="">
        <input type="hidden" name="nom_CR" id="nom_CR" value="">
        <input type="hidden" name="contenu_CR" id="contenu_CR" value="">
        <div class="flex justify-end mt-6">
            <button type="button" onclick="submitCR()" class="px-6 py-3 bg-green-600 text-white rounded-lg shadow hover:bg-green-700 transition-colors font-semibold text-lg">
                <i class="fas fa-save mr-2"></i>Enregistrer le compte rendu
            </button>
        </div>
    </form>
    <?php endif; ?>

    <div id="toastNotif" style="display:none; position:fixed; top:30px; right:30px; z-index:9999; min-width:250px;" class="transition-opacity duration-500">
        <div id="toastContent" class="px-4 py-3 rounded shadow-lg flex items-center">
            <span id="toastIcon" class="mr-3"></span>
            <span id="toastMsg"></span>
        </div>
    </div>

    <script>
        // Variables globales
        let currentReportData = null;
        let autoSaveInterval = null;

        // Variables globales pour gérer les rapports
        let selectedReports = [];
        let currentReportIndex = 0;

        // Logos encodés en base64 pour DOMPDF
        const logoUfhbBase64 = '<?php echo $logoUfhbBase64; ?>';
        const logoMiBase64 = '<?php echo $logoMiBase64; ?>';

        // Tableau associatif des rapports pour accès rapide côté JS
        const rapportsData = <?php echo json_encode($rapports_valides); ?>;

        // Pour retrouver un rapport par son id
        function getRapportById(id) {
            return rapportsData.find(r => r.id_rapport == id);
        }

        // Ajouter un rapport à la liste
        function addReport() {
            const select = document.getElementById('reportSelect');
            const id = select.value;
            if (!id) return;
            if (selectedReports.find(r => r.id_rapport == id)) return;
            const rapport = getRapportById(id);
            if (!rapport) return;
            selectedReports.push(rapport);
            // Ajouter à la liste des rapports sélectionnés (affichage)
            const reportsList = document.getElementById('reportsList');
            const div = document.createElement('div');
            div.id = 'rapport-cas-' + id;
            div.className = 'p-2 bg-gray-100 rounded flex items-center justify-between';
            div.innerHTML = `<span><b>Thème :</b> ${rapport.theme_rapport} <br><b>Étudiant :</b> ${rapport.prenom_etu} ${rapport.nom_etu}</span>
                <button onclick="removeReport('${id}')" class="ml-2 text-red-500 hover:text-red-700"><i class='fas fa-times'></i></button>`;
            reportsList.appendChild(div);
            updateAttributionEnseignants();
            showReportDetails();
            updateEditorWithReportData();
        }

        // Supprimer un rapport de la liste
        function removeReport(id) {
            selectedReports = selectedReports.filter(r => r.id_rapport != id);
            const div = document.getElementById('rapport-cas-' + id);
            if (div) div.remove();
            updateAttributionEnseignants();
            showReportDetails();
            updateEditorWithReportData();
        }

        // Mettre à jour la liste des rapports
        function updateReportsList() {
            const reportsList = document.getElementById('reportsList');
            const selectedReportsDiv = document.getElementById('selectedReports');

            if (selectedReports.length === 0) {
                selectedReportsDiv.classList.add('hidden');
                return;
            }

            selectedReportsDiv.classList.remove('hidden');
            reportsList.innerHTML = '';

            selectedReports.forEach((report, index) => {
                const reportElement = document.createElement('div');
                reportElement.className = 'flex items-center justify-between p-3 bg-gray-50 rounded-md border border-gray-200';
                reportElement.innerHTML = `
                    <div class="flex items-center space-x-3">
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">${index + 1}</span>
                        <span class="text-sm text-gray-700">${report.text}</span>
                    </div>
                    <button onclick="removeReport('${report.id}')" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                `;
                reportsList.appendChild(reportElement);
            });
        }

        // Mettre à jour les informations des rapports
        function updateReportInfo() {
            const reportDetails = document.getElementById('reportDetails');
            const evaluationsSummary = document.getElementById('evaluationsSummary');

            if (selectedReports.length === 0) {
                reportDetails.innerHTML = '<p class="text-gray-500 text-sm">Aucun rapport sélectionné</p>';
                evaluationsSummary.classList.add('hidden');
                return;
            }

        // Données simulées des rapports
            const reportData = {
            '1': {
                    title: 'IA Diagnostic Médical',
                student: 'Marie Lambert',
                    supervisor: 'Dr. Martin Dubois',
                    date: '15 Janvier 2025',
                    duration: '45 minutes',
                    grade: '16/20',
                    status: 'Validé',
                evaluations: [
                        { evaluator: 'Dr. Kouassi', decision: 'Validé', comment: 'Excellent travail technique' },
                        { evaluator: 'Dr. Koné', decision: 'Validé', comment: 'Méthodologie solide' },
                        { evaluator: 'Pr. Assan', decision: 'Validé', comment: 'Présentation claire' },
                        { evaluator: 'Dr. Bamba', decision: 'Validé', comment: 'Résultats convaincants' }
                ]
            },
            '2': {
                    title: 'Blockchain Sécurité',
                student: 'Jean Dupont',
                    supervisor: 'Prof. Sophie Martin',
                    date: '12 Janvier 2025',
                    duration: '50 minutes',
                    grade: '14/20',
                    status: 'Validé',
                evaluations: [
                        { evaluator: 'Dr. Kouassi', decision: 'Validé', comment: 'Approche innovante' },
                        { evaluator: 'Dr. Koné', decision: 'Validé', comment: 'Bonne analyse' },
                        { evaluator: 'Pr. Assan', decision: 'Rejeté', comment: 'Manque de profondeur' },
                        { evaluator: 'Dr. Bamba', decision: 'Validé', comment: 'Méthodologie correcte' }
                    ]
                },
                '3': {
                    title: 'Réseaux Neurones',
                    student: 'Thomas Moreau',
                    supervisor: 'Dr. Pierre Rousseau',
                    date: '10 Janvier 2025',
                    duration: '40 minutes',
                    grade: '17/20',
                    status: 'Validé',
                    evaluations: [
                        { evaluator: 'Dr. Kouassi', decision: 'Validé', comment: 'Travail remarquable' },
                        { evaluator: 'Dr. Koné', decision: 'Validé', comment: 'Excellente présentation' },
                        { evaluator: 'Pr. Assan', decision: 'Validé', comment: 'Résultats exceptionnels' },
                        { evaluator: 'Dr. Bamba', decision: 'Validé', comment: 'Méthodologie exemplaire' }
                    ]
                },
                '4': {
                    title: 'Machine Learning',
                    student: 'Sophie Martin',
                    supervisor: 'Dr. Claire Dubois',
                    date: '8 Janvier 2025',
                    duration: '42 minutes',
                    grade: '15/20',
                    status: 'Validé',
                    evaluations: [
                        { evaluator: 'Dr. Kouassi', decision: 'Validé', comment: 'Bon travail' },
                        { evaluator: 'Dr. Koné', decision: 'Validé', comment: 'Approche méthodique' },
                        { evaluator: 'Pr. Assan', decision: 'Validé', comment: 'Résultats satisfaisants' },
                        { evaluator: 'Dr. Bamba', decision: 'Validé', comment: 'Présentation claire' }
                    ]
                },
                '5': {
                    title: 'Cybersécurité',
                    student: 'Pierre Dubois',
                    supervisor: 'Prof. Jean Martin',
                    date: '5 Janvier 2025',
                    duration: '38 minutes',
                    grade: '18/20',
                    status: 'Validé',
                    evaluations: [
                        { evaluator: 'Dr. Kouassi', decision: 'Validé', comment: 'Excellente analyse' },
                        { evaluator: 'Dr. Koné', decision: 'Validé', comment: 'Travail approfondi' },
                        { evaluator: 'Pr. Assan', decision: 'Validé', comment: 'Méthodologie rigoureuse' },
                        { evaluator: 'Dr. Bamba', decision: 'Validé', comment: 'Résultats exceptionnels' }
                    ]
                },
                '6': {
                    title: 'Intelligence Artificielle',
                    student: 'Claire Rousseau',
                    supervisor: 'Dr. Thomas Moreau',
                    date: '3 Janvier 2025',
                    duration: '47 minutes',
                    grade: '16/20',
                    status: 'Validé',
                    evaluations: [
                        { evaluator: 'Dr. Kouassi', decision: 'Validé', comment: 'Travail de qualité' },
                        { evaluator: 'Dr. Koné', decision: 'Validé', comment: 'Approche intéressante' },
                        { evaluator: 'Pr. Assan', decision: 'Validé', comment: 'Bonne présentation' },
                        { evaluator: 'Dr. Bamba', decision: 'Validé', comment: 'Résultats convaincants' }
                    ]
                }
            };

            let html = '';
            let allEvaluationsHTML = '';

            selectedReports.forEach((report, index) => {
                const data = reportData[report.id];
                if (data) {
                    html += `
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center justify-between mb-3">
                                <h4 class="font-semibold text-gray-800">Rapport ${index + 1} : ${data.title}</h4>
                                <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">${data.status}</span>
                            </div>
                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div><span class="font-medium text-gray-700">Étudiant :</span> <span class="text-gray-900">${data.student}</span></div>
                                <div><span class="font-medium text-gray-700">Encadrant :</span> <span class="text-gray-900">${data.supervisor}</span></div>
                                <div><span class="font-medium text-gray-700">Date :</span> <span class="text-gray-900">${data.date}</span></div>
                                <div><span class="font-medium text-gray-700">Durée :</span> <span class="text-gray-900">${data.duration}</span></div>
                                <div><span class="font-medium text-gray-700">Note :</span> <span class="text-gray-900 font-semibold">${data.grade}</span></div>
                            </div>
                    </div>
                `;

                    // Ajouter les évaluations pour ce rapport
                    allEvaluationsHTML += `
                        <div class="mb-4">
                            <h5 class="font-semibold text-gray-700 mb-2">Évaluations - ${data.title}</h5>
                    `;

                    data.evaluations.forEach(eval => {
                    const bgColor = eval.decision === 'Validé' ? 'bg-green-50 border-green-200' : 'bg-red-50 border-red-200';
                    const textColor = eval.decision === 'Validé' ? 'text-green-800' : 'text-red-800';

                        allEvaluationsHTML += `
                            <div class="p-3 border rounded-lg ${bgColor} mb-2">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium text-sm">${eval.evaluator}</span>
                                <span class="px-2 py-1 text-xs rounded-full ${bgColor} ${textColor}">
                                    ${eval.decision}
                                </span>
                            </div>
                            <p class="text-xs text-gray-600">${eval.comment}</p>
                        </div>
                    `;
                });

                    allEvaluationsHTML += `</div>`;
                }
            });

            reportDetails.innerHTML = html;
            document.getElementById('evaluationsContent').innerHTML = allEvaluationsHTML;
                evaluationsSummary.classList.remove('hidden');

            // Mettre à jour l'éditeur si le modèle de séance de validation est chargé
            const editor = document.getElementById('editorContent');
            if (editor.innerHTML.includes('Procès-Verbal de séance de validation de thèmes')) {
                updateEditorWithReportData();
            }
        }

        // Mise à jour de l'éditeur avec les données des rapports
        function updateEditorWithReportData() {
            // Génère dynamiquement les cas à partir des rapports sélectionnés
            let casHTML = '';
            selectedReports.forEach((rapport, idx) => {
                const encadrant = rapport.encadrant_nom || '[Non attribué]';
                const directeur = rapport.directeur_nom || '[Non attribué]';
                casHTML += `
                <!-- Cas ${idx + 1} -->
                <div class="cas-box-container">
                    <div class="cas-box">Cas ${idx + 1}</div>
                </div>
                <div class="cas-content">
                    <p style="text-indent:0;"><strong>Étudiant :</strong> M. ${rapport.nom_etu} ${rapport.prenom_etu}</p>
                    <p style="text-indent:0;"><strong>Thème :</strong> ${rapport.theme_rapport}</p>
                    <p style="text-indent:0;" class="recommandations-title">Recommandations de la commission :</p>
                    <ul style="list-style-type:none; margin-left:1em;">
                        <li>- thème valide ;</li>
                        <li>- bien décrire le processus de règlement de chèques ;</li>
                        <li>- décrire exactement le contexte.</li>
                    </ul>
                    <p style="text-indent:0; margin-top:15px;"><strong>Directeur de mémoire :</strong> ${directeur}</p>
                    <p style="text-indent:0;"><strong>Encadreur pédagogique :</strong> ${encadrant}</p>
                </div>
                `;
            });
            // Remplacer le contenu de #casDynamique dans l'éditeur
            const editor = document.getElementById('editorContent');
            let content = window.baseTemplate;
            content = content.replace('<div id="casDynamique"></div>', `<div id="casDynamique">${casHTML}</div>`);
            editor.innerHTML = content;
        }

        // Chargement des modèles
        function loadTemplate(templateType) {
            const editor = document.getElementById('editorContent');
            let template = `
                <style>
                @page {
                    size: A4;
                    margin: 2cm;
                }
                .editor-content { 
                    font-family: 'Times New Roman', Times, serif; 
                    font-size: 12pt;
                    line-height: 1.5;
                    color: #000;
                }
                .header-container {
                    display: table;
                    width: 100%;
                    margin-bottom: 15px;
                }
                .header-left, .header-center, .header-right {
                    display: table-cell;
                    vertical-align: middle;
                }
                .header-left {
                    width: 15%;
                    text-align: left;
                }
                .header-center {
                    width: 70%;
                    text-align: center;
                }
                .header-right {
                    width: 15%;
                    text-align: right;
                }
                .header-logo {
                    max-width: 70px;
                    height: auto;
                }
                .header-text {
                    font-size: 11pt;
                    font-weight: bold;
                    letter-spacing: 0.5px;
                }
                .header-subtext {
                    font-size: 10pt;
                    font-style: italic;
                }
                .title-box {
                    border: 2px solid #C4A000;
                    border-radius: 0;
                    padding: 8px 20px;
                    margin: 20px auto;
                    text-align: center;
                    display: inline-block;
                }
                .title-box-container {
                    text-align: center;
                    margin: 20px 0;
                }
                .title-main {
                    font-size: 14pt;
                    font-weight: bold;
                    text-decoration: underline;
                    margin: 0;
                }
                .title-date {
                    font-size: 12pt;
                    font-weight: bold;
                    margin: 5px 0 0 0;
                    text-decoration: underline;
                }
                .section-title-num {
                    font-size: 12pt;
                    font-weight: bold;
                    margin-top: 20px;
                    margin-bottom: 10px;
                }
                .editor-content p {
                    margin-bottom: 10px;
                    text-align: justify;
                    text-indent: 1.5em;
                }
                .editor-content p.no-indent {
                    text-indent: 0;
                }
                .editor-content ul {
                    margin-left: 2em;
                    margin-bottom: 10px;
                    list-style-type: disc;
                }
                .editor-content li {
                    margin-bottom: 3px;
                }
                .cas-box {
                    border: 1px solid #000;
                    padding: 8px 15px;
                    margin: 15px auto;
                    text-align: center;
                    display: inline-block;
                }
                .cas-box-container {
                    text-align: center;
                    margin: 15px 0;
                }
                .cas-content {
                    margin-bottom: 15px;
                }
                .cas-content p {
                    text-indent: 0;
                }
                .recommandations-title {
                    font-weight: bold;
                    margin-top: 10px;
                    margin-bottom: 5px;
                }
                .signature-section {
                    text-align: right;
                    margin-top: 30px;
                    font-weight: bold;
                }
                .page-number {
                    text-align: right;
                    font-size: 10pt;
                    margin-top: 40px;
                }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .italic { font-style: italic; }
                .bold { font-weight: bold; }
                .underline { text-decoration: underline; }
                </style>
                
                <!-- En-tête avec logos - Table pour compatibilité DOMPDF -->
                <table class="header-table" style="width:100%; border-collapse:collapse; margin-bottom:15px;">
                    <tr>
                        <td style="width:15%; text-align:left; vertical-align:middle;">
                            <img src="${logoUfhbBase64}" alt="Logo UFHB" style="max-height:70px; height:auto;">
                        </td>
                        <td style="width:70%; text-align:center; vertical-align:middle;">
                            <div style="font-size:11pt; font-weight:bold; letter-spacing:0.5px;">REPUBLIQUE DE COTE D'IVOIRE</div>
                            <div style="font-size:10pt; font-style:italic;">Ministère de l'Enseignement Supérieur<br>et de la Recherche Scientifique</div>
                        </td>
                        <td style="width:15%; text-align:right; vertical-align:middle;">
                            <img src="${logoMiBase64}" alt="Logo UFR MI" style="max-height:70px; height:auto;">
                        </td>
                    </tr>
                </table>

                <hr style="border: 1px solid #C4A000; margin: 10px 0;">

                <!-- Titre encadré -->
                <div style="text-align:center; margin:20px 0;">
                    <div style="border:2px solid #C4A000; padding:8px 20px; display:inline-block;">
                        <p style="margin:0; text-indent:0; font-size:14pt; font-weight:bold; text-decoration:underline;">Procès-Verbal de séance de validation de thèmes</p>
                        <p style="margin:5px 0 0 0; text-indent:0; font-size:12pt; font-weight:bold; text-decoration:underline;">[DATE]</p>
                    </div>
                </div>

                <!-- Corps du document -->
                <p>Dans le bureau du Prof KOUA Brou à l'UFR MI, le [DATE] s'est tenue de 11 h 00 à 12 h 30 une séance de validation de thèmes de soutenance des étudiants en fin de cycle de la filière MIAGE-GI.</p>
                
                <p>La réunion était animée par Prof KOUA Brou le responsable de ladite filière. Etaient présents Prof. KOUA Brou, Dr MAMADOU Diarra, M. WAH Médard et M. BROU Patrice. Les membres de la commission de validation ont examiné [N] dossiers.</p>
                
                <p class="no-indent">L'ordre du jour débattu est le suivant :</p>
                <ul>
                    <li>Informations</li>
                    <li>Validation de thèmes</li>
                    <li>Divers</li>
                </ul>

                <p class="section-title-num">1. Informations</p>
                <p>Le responsable de la filière a exposé sur l'intérêt des séances de validation. Il a donné des informations sur le choix des thèmes niveau ingénieur et la tenue mensuelle des séances de validation.</p>
                <p>L'organisation des séances de validation permet de faire le point des encadrements, le contenu potentiel de thèmes, et le suivi des mémoires par des encadreurs pédagogiques.</p>

                <p class="section-title-num">2. Validation de thèmes</p>
                <div id="casDynamique"></div>

                <p class="section-title-num">3. Divers</p>
                <p>La commission a recommandé au Directeur de la filière d'améliorer le partenariat avec les entreprises car elles le souhaitent compte tenu du rendement des stagiaires déjà reçus.</p>
                <p>Aussi, la commission a fait les recommandations suivantes aux étudiants :</p>
                <ul>
                    <li>Respecter toutes les rubriques du template de présentation de thème en possession de la chargée de communication ;</li>
                    <li>Joindre un CV contenant une photo d'identité ;</li>
                    <li>Soutenir au plus tard à la session suivante pour ne pas tomber sous le coup d'une pénalité.</li>
                </ul>
                
                <p>Les travaux de la commission ont pris fin à 12 h 30.</p>

                <div class="signature-section">
                    La commission
                </div>
                    `;
            editor.innerHTML = template;
            window.baseTemplate = template;
                updateEditorWithReportData();
        }

        // Fonctions de formatage de texte
        function formatText(command) {
            document.execCommand(command, false, null);
            document.getElementById('editorContent').focus();
        }

        function insertList(type) {
            const command = type === 'ul' ? 'insertUnorderedList' : 'insertOrderedList';
            document.execCommand(command, false, null);
            document.getElementById('editorContent').focus();
        }

        function insertSection(sectionType) {
            const editor = document.getElementById('editorContent');
            const selection = window.getSelection();
            const range = selection.getRangeAt(0);

            let sectionHTML = '';
            switch(sectionType) {
                case 'evaluation':
                    sectionHTML = `
                        <div class="mb-4 p-4 border-l-4 border-blue-500 bg-blue-50">
                            <h4 class="font-semibold text-blue-800 mb-2">Évaluation - [Nom de l'évaluateur]</h4>
                            <p><strong>Décision :</strong> [Validé/Rejeté]</p>
                            <p><strong>Commentaire :</strong> [Commentaire détaillé...]</p>
                        </div>
                    `;
                    break;
                case 'recommendation':
                    sectionHTML = `
                        <div class="mb-4 p-4 border-l-4 border-green-500 bg-green-50">
                            <h4 class="font-semibold text-green-800 mb-2">Recommandation</h4>
                            <p>[Votre recommandation...]</p>
                        </div>
                    `;
                    break;
            }

            const div = document.createElement('div');
            div.innerHTML = sectionHTML;
            range.insertNode(div.firstChild);
            editor.focus();
        }

        // Fonctions de sauvegarde
        function saveAsDraft() {
            const content = document.getElementById('editorContent').innerHTML;
            const reportId = document.getElementById('reportSelect').value;

            // Simuler la sauvegarde
            localStorage.setItem(`draft_${reportId}`, content);
            updateLastSaveTime();

            // Notification
            showNotification('Brouillon sauvegardé avec succès', 'success');
        }

        function autoSave() {
            if (autoSaveInterval) {
                clearInterval(autoSaveInterval);
                autoSaveInterval = null;
                document.querySelector('[onclick="autoSave()"]').innerHTML = '<i class="fas fa-clock mr-2"></i>Sauvegarde auto';
            } else {
                autoSaveInterval = setInterval(() => {
                    saveAsDraft();
                }, 30000); // Sauvegarde toutes les 30 secondes
                document.querySelector('[onclick="autoSave()"]').innerHTML = '<i class="fas fa-check mr-2"></i>Auto: ON';
            }
        }

        function updateLastSaveTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' });
            document.getElementById('lastSave').textContent = timeString;
        }

        // Aperçu du rapport
        function previewReport() {
            const content = document.getElementById('editorContent').innerHTML;
            document.getElementById('previewContent').innerHTML = content;
            document.getElementById('previewModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closePreviewModal() {
            document.getElementById('previewModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Fonctions d'export
        function printReport() {
            const content = document.getElementById('previewContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Compte Rendu d'Évaluation</title>
                    <style>
                        body { font-family: 'Times New Roman', serif; line-height: 1.6; margin: 40px; }
                        h2, h3, h4 { color: #333; }
                        .border-b { border-bottom: 1px solid #ccc; }
                        .mb-6 { margin-bottom: 24px; }
                        .mb-4 { margin-bottom: 16px; }
                        .mb-3 { margin-bottom: 12px; }
                        .mb-2 { margin-bottom: 8px; }
                        .pb-2 { padding-bottom: 8px; }
                        .p-4 { padding: 16px; }
                        .border-l-4 { border-left: 4px solid #3b82f6; }
                        .bg-blue-50 { background-color: #eff6ff; }
                        .bg-green-50 { background-color: #f0fdf4; }
                        .text-center { text-align: center; }
                        .font-bold { font-weight: bold; }
                        .font-semibold { font-weight: 600; }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        function exportToPDF() {
            // Simulation d'export PDF
            showNotification('Export PDF en cours...', 'info');
            setTimeout(() => {
                showNotification('Rapport exporté en PDF avec succès', 'success');
            }, 2000);
        }

        // Impression du rapport via DOMPDF
        function printReport() {
            const content = document.getElementById('editorContent').innerHTML;

            if (!content || content.trim() === '') {
                showNotification('Aucun contenu à imprimer', 'error');
                return;
            }

            // Créer le HTML complet avec les styles pour DOMPDF
            const fullHtml = `
                <!DOCTYPE html>
                <html>
                <head>
                    <meta charset="UTF-8">
                    <title>Procès-Verbal de séance de validation de thèmes</title>
                    <style>
                        @page {
                            size: A4;
                            margin: 2cm;
                        }
                        body {
                            font-family: 'Times New Roman', Times, serif;
                            font-size: 12pt;
                            line-height: 1.5;
                            margin: 0;
                            padding: 0;
                            color: #000;
                            background: white;
                        }
                        .header-container {
                            width: 100%;
                            margin-bottom: 15px;
                        }
                        .header-table {
                            width: 100%;
                            border-collapse: collapse;
                        }
                        .header-table td {
                            vertical-align: middle;
                        }
                        .header-logo {
                            max-width: 70px;
                            height: auto;
                        }
                        .header-text {
                            font-size: 11pt;
                            font-weight: bold;
                            letter-spacing: 0.5px;
                            text-align: center;
                        }
                        .header-subtext {
                            font-size: 10pt;
                            font-style: italic;
                            text-align: center;
                        }
                        hr {
                            border: 1px solid #C4A000;
                            margin: 10px 0;
                        }
                        .title-box {
                            border: 2px solid #C4A000;
                            padding: 8px 20px;
                            margin: 20px auto;
                            text-align: center;
                            display: inline-block;
                        }
                        .title-box-container {
                            text-align: center;
                            margin: 20px 0;
                        }
                        .title-main {
                            font-size: 14pt;
                            font-weight: bold;
                            text-decoration: underline;
                            margin: 0;
                        }
                        .title-date {
                            font-size: 12pt;
                            font-weight: bold;
                            margin: 5px 0 0 0;
                            text-decoration: underline;
                        }
                        .section-title-num {
                            font-size: 12pt;
                            font-weight: bold;
                            margin-top: 20px;
                            margin-bottom: 10px;
                        }
                        p {
                            margin-bottom: 10px;
                            text-align: justify;
                            text-indent: 1.5em;
                        }
                        p.no-indent {
                            text-indent: 0;
                        }
                        ul {
                            margin-left: 2em;
                            margin-bottom: 10px;
                        }
                        li {
                            margin-bottom: 3px;
                        }
                        .cas-box {
                            border: 1px solid #000;
                            padding: 8px 15px;
                            margin: 15px auto;
                            text-align: center;
                            display: inline-block;
                        }
                        .cas-box-container {
                            text-align: center;
                            margin: 15px 0;
                        }
                        .cas-content {
                            margin-bottom: 15px;
                        }
                        .cas-content p {
                            text-indent: 0;
                        }
                        .recommandations-title {
                            font-weight: bold;
                            margin-top: 10px;
                            margin-bottom: 5px;
                        }
                        .signature-section {
                            text-align: right;
                            margin-top: 30px;
                            font-weight: bold;
                        }
                        .text-center { text-align: center; }
                        .text-right { text-align: right; }
                        .italic { font-style: italic; }
                        .bold { font-weight: bold; }
                        .underline { text-decoration: underline; }
                    </style>
                </head>
                <body>
                    ${content}
                </body>
                </html>
            `;

            // Créer un formulaire pour envoyer au contrôleur DOMPDF
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '?page=redaction_compte_rendu&action=export_pdf';
            form.target = '_blank';

            // Ajouter le token CSRF (obligatoire pour les requêtes POST)
            const csrfToken = document.querySelector('input[name="csrf_token"]')?.value || '<?php echo \CheckMaster\Core\Csrf::token(); ?>';
            const inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden';
            inputCsrf.name = 'csrf_token';
            inputCsrf.value = csrfToken;
            form.appendChild(inputCsrf);

            const inputContenu = document.createElement('input');
            inputContenu.type = 'hidden';
            inputContenu.name = 'contenu_CR';
            inputContenu.value = fullHtml;
            form.appendChild(inputContenu);

            const inputNom = document.createElement('input');
            inputNom.type = 'hidden';
            inputNom.name = 'nom_CR';
            inputNom.value = 'Proces_Verbal_Validation_Themes_' + new Date().toISOString().slice(0,10);
            form.appendChild(inputNom);

            document.body.appendChild(form);
            form.submit();
            document.body.removeChild(form);

            showNotification('Génération du PDF en cours...', 'success');
        }

        // Mise à jour des étapes de progression
        function updateProgressSteps(currentStep) {
            const steps = document.querySelectorAll('.progress-step');
            steps.forEach((step, index) => {
                step.classList.remove('active', 'completed');
                if (index + 1 < currentStep) {
                    step.classList.add('completed');
                } else if (index + 1 === currentStep) {
                    step.classList.add('active');
                }
            });
        }

        // Système de notifications
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white text-sm font-medium z-50 ${
                type === 'success' ? 'bg-green-600' :
                type === 'error' ? 'bg-red-600' :
                type === 'info' ? 'bg-blue-600' : 'bg-gray-600'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'} mr-2"></i>
                    ${message}
                </div>
            `;

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {

            // Sauvegarde automatique lors de la saisie
            const editor = document.getElementById('editorContent');
            let saveTimeout;

            editor.addEventListener('input', function() {
                clearTimeout(saveTimeout);
                saveTimeout = setTimeout(() => {
                    if (autoSaveInterval) {
                        saveAsDraft();
                    }
                }, 5000); // Sauvegarde 5 secondes après la dernière modification
            });

            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                if (e.ctrlKey || e.metaKey) {
                    switch(e.key) {
                        case 's':
                            e.preventDefault();
                            saveAsDraft();
                            break;
                        case 'p':
                            e.preventDefault();
                            previewReport();
                            break;
                    }
                }

                if (e.key === 'Escape') {
                    closePreviewModal();
                }
            });
        });

        // Charger le modèle de séance de validation par défaut à l'ouverture de la page
        window.addEventListener('DOMContentLoaded', function() {
            loadTemplate('validation_seance');
        });

        // Affiche les infos détaillées de tous les rapports sélectionnés
        function showReportDetails() {
            const reportsList = document.getElementById('reportsList');
            const reportDetails = document.getElementById('reportDetails');
            reportDetails.innerHTML = '';
            const selected = Array.from(reportsList.children).map(div => div.id.replace('rapport-cas-', ''));
            if (selected.length === 0) {
                reportDetails.innerHTML = '<span class="text-gray-400">Aucun rapport sélectionné.</span>';
                return;
            }
            selected.forEach((id, idx) => {
                const rapport = getRapportById(id);
                if (rapport) {
                    reportDetails.innerHTML += `<div class='p-3 mb-2 bg-yellow-50 rounded border border-yellow-200'>
                        <b>Cas ${idx + 1}</b><br>
                        <b>Thème :</b> ${rapport.theme_rapport}<br>
                        <b>Étudiant :</b> ${rapport.prenom_etu} ${rapport.nom_etu}<br>
                        <b>Nom du rapport :</b> ${rapport.nom_rapport}
                    </div>`;
                }
            });
        }

        const enseignantsData = <?php echo json_encode($enseignants); ?>;

        // Ajoute dynamiquement les sélecteurs pour chaque rapport sélectionné
        function updateAttributionEnseignants() {
            const container = document.getElementById('enseignants-par-rapport-js');
            container.innerHTML = '';
            selectedReports.forEach((rapport, idx) => {
                const div = document.createElement('div');
                div.className = 'flex flex-col bg-blue-50 border border-blue-200 rounded-lg p-6 gap-4 mb-4';
                div.innerHTML = `
                    <div class="text-center mb-2">
                        <h4 class="text-lg font-semibold text-gray-800">Cas ${idx + 1}</h4>
                        <p class="text-gray-700">${rapport.theme_rapport}</p>
                        <p class="text-gray-500 text-sm">(${rapport.prenom_etu} ${rapport.nom_etu})</p>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div class="flex flex-col gap-2">
                            <label for="encadrant_${rapport.id_rapport}" class="font-medium text-gray-700 text-center">Encadrant pédagogique</label>
                            <select name="encadrant_pedagogique[${rapport.id_rapport}]" id="encadrant_${rapport.id_rapport}" class="p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-400 text-center">
                                <option value="">-- Choisir un encadrant --</option>
                                ${enseignantsData.map(ens => `<option value="${ens.id_enseignant}">${ens.prenom_enseignant} ${ens.nom_enseignant}</option>`).join('')}
                            </select>
                        </div>
                        <div class="flex flex-col gap-2">
                            <label for="directeur_${rapport.id_rapport}" class="font-medium text-gray-700 text-center">Directeur de mémoire</label>
                            <select name="directeur_memoire[${rapport.id_rapport}]" id="directeur_${rapport.id_rapport}" class="p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-400 text-center">
                                <option value="">-- Choisir un directeur --</option>
                                ${enseignantsData.map(ens => `<option value="${ens.id_enseignant}">${ens.prenom_enseignant} ${ens.nom_enseignant}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                `;
                container.appendChild(div);

                // Ajout des listeners pour mettre à jour l'éditeur dynamiquement
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

        function submitCR() {
            if (selectedReports.length === 0) {
                alert("Veuillez sélectionner au moins un rapport.");
                return;
            }
            document.getElementById('contenu_CR').value = document.getElementById('editorContent').innerHTML;
            let selected = selectedReports[0] || {};
            document.getElementById('num_etu').value = selected.num_etu || '';
            document.getElementById('nom_CR').value = 'Compte rendu séance du ' + (new Date()).toLocaleDateString('fr-FR');
            let rapportsIds = selectedReports.map(r => r.id_rapport);
            // Supprimer les anciens inputs rapports[]
            document.querySelectorAll('input[name="rapports[]"]').forEach(e => e.remove());
            // Ajouter un input caché pour chaque rapport sélectionné
            let form = document.getElementById('formCR');
            rapportsIds.forEach(id => {
                let input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'rapports[]';
                input.value = id;
                form.appendChild(input);
            });
            // Ajouter les encadrants/directeurs sélectionnés
            selectedReports.forEach(rapport => {
                if (rapport.encadrant_nom) {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `encadrant_pedagogique[${rapport.id_rapport}]`;
                    input.value = document.getElementById(`encadrant_${rapport.id_rapport}`).value;
                    form.appendChild(input);
                }
                if (rapport.directeur_nom) {
                    let input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `directeur_memoire[${rapport.id_rapport}]`;
                    input.value = document.getElementById(`directeur_${rapport.id_rapport}`).value;
                    form.appendChild(input);
                }
            });
            console.log("num_etu envoyé :", selected.num_etu);
            document.getElementById('formCR').submit();
        }

        window.addEventListener('DOMContentLoaded', function() {
            var notifType = <?php echo json_encode($notifType); ?>;
            var notifMsg = <?php echo json_encode($notifMsg); ?>;
            if (notifType && notifMsg) {
                var toast = document.getElementById('toastNotif');
                var toastContent = document.getElementById('toastContent');
                var toastIcon = document.getElementById('toastIcon');
                var toastMsg = document.getElementById('toastMsg');
                toastMsg.textContent = notifMsg;
                if (notifType === 'success') {
                    toastContent.className = 'bg-green-500 text-white px-4 py-3 rounded shadow-lg flex items-center';
                    toastIcon.innerHTML = '<i class="fas fa-check-circle"></i>';
                } else {
                    toastContent.className = 'bg-red-500 text-white px-4 py-3 rounded shadow-lg flex items-center';
                    toastIcon.innerHTML = '<i class="fas fa-times-circle"></i>';
                }
                toast.style.display = 'block';
                toast.style.opacity = 1;
                setTimeout(function() {
                    toast.style.opacity = 0;
                    setTimeout(function() { toast.style.display = 'none'; }, 500);
                }, 3000);
            }
        });
    </script>
</body>
</html>
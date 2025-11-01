<?php
require_once __DIR__ . '/../../../app/utils/permissions.php';
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Éditeur de Rapport de Stage</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tailwindcss/2.2.19/tailwind.min.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js"></script>
    <script src="https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/translations/fr.js"></script>
    <style>
    .loader {
        border-top-color: #3498db;
        animation: spinner 1.5s linear infinite;
    }

    @keyframes spinner {
        0% {
            transform: rotate(0deg);
        }

        100% {
            transform: rotate(360deg);
        }
    }

    /* CKEditor customizations */
    .ck-editor__editable {
        min-height: 600px;
    }

    .document-loaded {
        opacity: 1;
        transition: opacity 0.5s ease-in-out;
    }

    .document-loading {
        opacity: 0.6;
    }

    .notification {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        padding: 16px 20px;
        min-width: 320px;
        max-width: 480px;
        border-left: 4px solid;
        transform: translateX(100%);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .notification.hide {
        transform: translateX(100%);
        opacity: 0;
    }

    .notification.success {
        border-left-color: #10b981;
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    }

    .notification.error {
        border-left-color: #ef4444;
        background: linear-gradient(135deg, #fef2f2 0%, #fef2f2 100%);
    }

    .notification.info {
        border-left-color: #3b82f6;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    }

    .notification.warning {
        border-left-color: #f59e0b;
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    }

    .notification-icon {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-content {
        flex: 1;
        min-width: 0;
    }

    .notification-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
        color: #1f2937;
    }

    .notification-message {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.4;
        word-wrap: break-word;
    }

    .notification-close {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        opacity: 0.6;
    }

    .notification-close:hover {
        opacity: 1;
        background: rgba(0, 0, 0, 0.2);
    }

    .notification-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 0 0 12px 12px;
        transition: width linear;
    }

    .notification.success .notification-progress {
        background: #10b981;
    }

    .notification.error .notification-progress {
        background: #ef4444;
    }

    .notification.info .notification-progress {
        background: #3b82f6;
    }

    .notification.warning .notification-progress {
        background: #f59e0b;
    }
    </style>
</head>

<body class="bg-gray-100 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">
                    <?php if (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']): ?>
                    Consultation du Rapport de Stage
                    <?php else: ?>
                    Éditeur de Rapport de Stage
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600 mt-2">
                    <?php if (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']): ?>
                    Consultation en lecture seule - Rapport déjà déposé
                    <?php else: ?>
                    Créez et modifiez votre rapport facilement
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex space-x-3 mt-4 md:mt-0">
                <?php if (!isset($GLOBALS['rapportDejaDepose']) || !$GLOBALS['rapportDejaDepose']): ?>
                <!-- Boutons actifs seulement si le rapport n'est pas déposé -->
                <?php if (hasPermission('gestion_rapports', 'CREATE') || hasPermission('gestion_rapports', 'UPDATE')): ?>
                <button id="saveBtn"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                        </path>
                    </svg>
                    Enregistrer
                </button>
                <?php endif; ?>
                <button id="exportBtn"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Exporter
                </button>
                <button id="deposerBtn"
                    class="bg-yellow-500 hover:bg-yellow-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Déposer
                </button>
                <?php else: ?>
                <!-- Boutons désactivés si le rapport est déjà déposé -->
                <button disabled
                    class="bg-blue-400 text-white font-semibold py-2 px-4 rounded-lg flex items-center cursor-not-allowed"
                    title="Non disponible - Rapport déjà déposé">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4">
                        </path>
                    </svg>
                    Enregistrer
                </button>
                <button disabled id="exportBtn"
                    class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Exporter
                </button>
                <button disabled
                    class="bg-yellow-500 text-white font-semibold py-2 px-4 rounded-lg flex items-center cursor-not-allowed"
                    title="Non disponible - Rapport déjà déposé">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Déposer
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <?php if (isset($erreurs) && !empty($erreurs)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <strong>Erreurs de validation :</strong>
                <ul class="mt-2 list-disc list-inside">
                    <?php foreach ($erreurs as $erreur): ?>
                    <li><?= htmlspecialchars($erreur) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Formulaire principal -->
            <form id="rapportForm" method="POST" onsubmit="return false;">
                <input type="hidden" name="action" value="save_rapport">
                <?php if (isset($isEditMode) && $isEditMode && isset($rapport)): ?>
                <input type="hidden" name="edit_id" value="<?= $rapport['id_rapport'] ?>">
                <?php endif; ?>
                <!-- Toolbar -->
                <div class="p-6 border-b border-gray-200">
                    <h2 class="text-xl font-semibold text-gray-800 mb-4">Informations du rapport</h2>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="nom_rapport" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom du rapport <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nom_rapport" name="nom_rapport"
                                value="<?= isset($rapport) ? htmlspecialchars($rapport['nom_rapport']) : '' ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                placeholder="Ex: Rapport de stage - Développement Web"
                                <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : 'required' ?>>
                        </div>
                        <div>
                            <label for="theme_rapport" class="block text-sm font-medium text-gray-700 mb-2">
                                Thème du rapport <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="theme_rapport" name="theme_rapport"
                                value="<?= isset($rapport) ? htmlspecialchars($rapport['theme_rapport']) : '' ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                placeholder="Ex: Intégration d'un système CRM"
                                <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : 'required' ?>>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 border-b border-gray-200 p-4 flex flex-wrap justify-between items-center">
                    <div class="flex items-center space-x-4 mb-3 md:mb-0">
                        <?php if (!isset($GLOBALS['rapportDejaDepose']) || !$GLOBALS['rapportDejaDepose']): ?>
                        <button id="loadTemplateBtn"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Charger le Modèle
                        </button>
                        <?php else: ?>
                        <div
                            class="bg-gray-400 text-gray-600 font-medium py-2 px-4 rounded-lg flex items-center cursor-not-allowed">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"
                                xmlns="http://www.w3.org/2000/svg">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            Charger le Modèle
                        </div>
                        <?php endif; ?>
                        <span id="loadingIndicator" class="hidden">
                            <div class="loader ease-linear rounded-full border-4 border-t-4 border-gray-200 h-6 w-6">
                            </div>
                            <span class="ml-2 text-gray-600">Chargement...</span>
                        </span>
                    </div>
                    <!-- Font selectors hidden - CKEditor 5 requires custom plugins for dynamic font changes -->
                    <div class="flex items-center space-x-3" style="display: none;">
                        <div class="relative">
                            <select id="fontSelector"
                                class="bg-white border border-gray-300 text-gray-700 py-2 pl-3 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'disabled' : '' ?>>
                                <option value="Arial, sans-serif">Arial</option>
                                <option value="Times New Roman, serif">Times New Roman</option>
                                <option value="Calibri, sans-serif">Calibri</option>
                                <option value="Georgia, serif">Georgia</option>
                                <option value="Verdana, sans-serif">Verdana</option>
                            </select>
                        </div>
                        <div class="relative">
                            <select id="fontSize"
                                class="bg-white border border-gray-300 text-gray-700 py-2 pl-3 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'disabled' : '' ?>>
                                <option value="12pt">12pt</option>
                                <option value="14pt">14pt</option>
                                <option value="16pt">16pt</option>
                                <option value="18pt">18pt</option>
                                <option value="20pt">20pt</option>
                                <option value="24pt">24pt</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Editor Area -->
                <div id="editorContainer" class="p-6 document-loading">
                    <div id="documentStatusMessage" class="text-center py-8 text-gray-500">
                        Veuillez charger le modèle pour commencer l'édition
                    </div>
                    <textarea id="editor" name="contenu_rapport" class="hidden"></textarea>
                </div>
        </div>
        </form>

        <!-- Footer -->
        <div class="mt-6 text-center text-gray-600 text-sm">
            <p>© 2025 - Éditeur de Rapport de Stage | Développé pour faciliter la création de rapports professionnels
            </p>
        </div>
    </div>

    <!-- Notifications -->
    <div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-3">
        <!-- Les notifications seront ajoutées ici dynamiquement -->
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const loadTemplateBtn = document.getElementById('loadTemplateBtn');
        const saveBtn = document.getElementById('saveBtn');
        const exportBtn = document.getElementById('exportBtn');
        const deposerBtn = document.getElementById('deposerBtn');
        const editorContainer = document.getElementById('editorContainer');
        const documentStatusMessage = document.getElementById('documentStatusMessage');
        const loadingIndicator = document.getElementById('loadingIndicator');
        const fontSelector = document.getElementById('fontSelector');
        const fontSize = document.getElementById('fontSize');

        // Variable pour gérer l'état de lecture seule
        const isReadOnly =
            <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'true' : 'false' ?>;

        let editor;

        // Initialize CKEditor 5
        ClassicEditor
            .create(document.querySelector('#editor'), {
                toolbar: {
                    items: [
                        'heading', '|',
                        'bold', 'italic', 'underline', 'strikethrough', '|',
                        'bulletedList', 'numberedList', 'outdent', 'indent', '|',
                        'link', 'blockQuote', 'insertTable', '|',
                        'undo', 'redo'
                    ]
                },
                language: 'fr',
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                }
            })
            .then(newEditor => {
                editor = newEditor;
                
                // Gérer le mode lecture seule
                if (isReadOnly) {
                    editor.isReadOnly = true;
                }

                <?php if (isset($isEditMode) && $isEditMode && !empty($contenuRapport)): ?>
                // Mode édition : charger le contenu du rapport
                setTimeout(function() {
                    editor.setData(<?= json_encode($contenuRapport) ?>);
                    editorContainer.classList.remove('document-loading');
                    editorContainer.classList.add('document-loaded');
                    documentStatusMessage.classList.add('hidden');
                    if (isReadOnly) {
                        showNotification('info',
                            'Rapport chargé en mode consultation (lecture seule)'
                        );
                    } else {
                        showNotification('info',
                            'Rapport chargé en mode édition');
                    }
                }, 500);
                <?php else: ?>
                // Mode création : ne rien charger, laisser l'éditeur vide
                editor.setData('');
                editorContainer.classList.remove('document-loaded');
                editorContainer.classList.add('document-loading');
                documentStatusMessage.classList.remove('hidden');
                <?php endif; ?>
            })
            .catch(error => {
                console.error('Erreur lors de l\'initialisation de CKEditor 5 :', error);
            });


        // Load template button event
        loadTemplateBtn.addEventListener('click', function(e) {
            e.preventDefault(); // IMPORTANT : Empêcher la soumission du formulaire

            loadingIndicator.classList.remove('hidden');
            documentStatusMessage.textContent = 'Chargement du modèle en cours...';

            // Fetch template HTML from server
            fetch('?page=gestion_rapports&action=load_template_html')
                .then(response => response.json())
                .then(data => {
                    if (data.success && editor) {
                        editor.setData(data.html);
                        editorContainer.classList.remove('document-loading');
                        editorContainer.classList.add('document-loaded');
                        documentStatusMessage.classList.add('hidden');
                        showNotification('success', 'Modèle chargé avec succès!');
                    } else {
                        showNotification('error', data.message || 'Erreur de chargement du modèle.');
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('error', 'Erreur de communication avec le serveur.');
                })
                .finally(() => {
                    loadingIndicator.classList.add('hidden');
                });
        });

        // Font selector event (Note: CKEditor doesn't support dynamic font changes easily)
        fontSelector.addEventListener('change', function() {
            // Font changing in CKEditor requires custom plugin or manual selection
            // For now, we'll show a notification that this feature needs to be set before typing
            showNotification('info', 'Veuillez sélectionner la police avant de commencer à taper.');
        });

        // Font size selector event (Note: CKEditor doesn't support dynamic size changes easily)
        fontSize.addEventListener('change', function() {
            // Font size changing in CKEditor requires custom plugin or manual selection
            showNotification('info', 'Veuillez sélectionner la taille avant de commencer à taper.');
        });

        // Save button event
        saveBtn.addEventListener('click', function() {
            if (isReadOnly) {
                showNotification('warning',
                    'Ce rapport ne peut plus être modifié car il a déjà été déposé');
                return;
            }

            if (!editor) {
                showNotification('error', 'Éditeur non initialisé');
                return;
            }

            const content = editor.getData();
            const nomRapport = document.getElementById('nom_rapport').value.trim();
            const themeRapport = document.getElementById('theme_rapport').value.trim();

            if (!content || content.trim() === '') {
                showNotification('error', 'Le contenu du rapport est vide');
                return;
            }

            if (!nomRapport) {
                showNotification('error', 'Le nom du rapport est requis');
                return;
            }

            if (!themeRapport) {
                showNotification('error', 'Le thème du rapport est requis');
                return;
            }

            // Désactiver le bouton pendant la sauvegarde
            saveBtn.disabled = true;
            saveBtn.innerHTML =
                '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"></path></svg>Enregistrement...';

            // Créer FormData pour la sauvegarde
            const formData = new FormData();
            formData.append('action', 'save_rapport');
            formData.append('contenu_rapport', content);
            formData.append('nom_rapport', nomRapport);
            formData.append('theme_rapport', themeRapport);
            <?php if (isset($isEditMode) && $isEditMode && isset($rapport)): ?>
            formData.append('edit_id', '<?= $rapport['id_rapport'] ?>');
            <?php endif; ?>

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success) {
                        showNotification('success', data.message);
                        setTimeout(() => {
                            window.location.href = '?page=gestion_rapports';
                        }, 2000);
                    } else {
                        showNotification('error', data.message || 'Erreur lors de la sauvegarde');
                        if (data.errors) {
                            console.log('Erreurs de validation:', data.errors);
                        }
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    showNotification('error', 'Erreur lors de la sauvegarde');
                })
                .finally(() => {
                    // Restaurer le bouton
                    saveBtn.disabled = false;
                    saveBtn.innerHTML =
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>Enregistrer';
                });

            return false;
        });


        // Export button event
        exportBtn.addEventListener('click', function() {
            if (!editor) {
                showNotification('error', 'Éditeur non initialisé');
                return;
            }

            const content = editor.getData();
            const nomRapport = document.getElementById('nom_rapport').value.trim();
            const themeRapport = document.getElementById('theme_rapport').value.trim();

            if (!content || content.trim() === '') {
                showNotification('error', 'Le contenu du rapport est vide');
                return;
            }

            // Debug: afficher les données envoyées
            console.log('Export PDF - Données:', {
                nomRapport: nomRapport,
                themeRapport: themeRapport,
                contentLength: content.length
            });

            // Afficher indicateur de chargement
            exportBtn.disabled = true;
            exportBtn.innerHTML =
                '<svg class="w-5 h-5 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25"></circle><path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z" class="opacity-75"></path></svg>Génération PDF...';

            // Créer FormData pour l'export PDF
            const formData = new FormData();
            formData.append('action', 'export_pdf');
            formData.append('contenu_rapport', content);
            formData.append('nom_rapport', nomRapport || 'Rapport_de_Stage');
            formData.append('theme_rapport', themeRapport || 'Thème du rapport');

            fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);

                    if (!response.ok) {
                        throw new Error(`Erreur HTTP: ${response.status} ${response.statusText}`);
                    }

                    // Vérifier si la réponse est un PDF
                    const contentType = response.headers.get('content-type');
                    console.log('Content-Type:', contentType);

                    if (contentType && contentType.includes('application/pdf')) {
                        return response.blob();
                    } else {
                        // Si ce n'est pas un PDF, c'est probablement une erreur JSON
                        return response.text().then(text => {
                            console.log('Response text:', text);
                            try {
                                const data = JSON.parse(text);
                                throw new Error(data.message ||
                                    'Erreur lors de la génération du PDF');
                            } catch (e) {
                                if (e.message.includes('Erreur lors de la génération')) {
                                    throw e;
                                }
                                throw new Error('Réponse inattendue du serveur: ' + text);
                            }
                        });
                    }
                })
                .then(blob => {
                    console.log('PDF blob size:', blob.size);

                    if (blob.size === 0) {
                        throw new Error('Le PDF généré est vide');
                    }

                    // Créer le lien de téléchargement
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `${nomRapport || 'Rapport_de_Stage'}.pdf`;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);

                    showNotification('success', 'Rapport exporté en PDF avec succès!');
                })
                .catch(error => {
                    console.error('Erreur export PDF:', error);
                    showNotification('error', error.message || 'Erreur lors de l\'export PDF');
                })
                .finally(() => {
                    // Restaurer le bouton
                    exportBtn.disabled = false;
                    exportBtn.innerHTML =
                        '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>Exporter';
                });
        });

        // Utility function to show notifications
        function showNotification(type, message, title = null) {
            const notificationContainer = document.getElementById('notificationContainer');
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;

            // Définir les icônes selon le type
            let iconPath = '';
            let displayTitle = title || type.charAt(0).toUpperCase() + type.slice(1);

            switch (type) {
                case 'success':
                    iconPath = 'M5 13l4 4L19 7';
                    break;
                case 'error':
                    iconPath = 'M6 18L18 6M6 6l12 12';
                    break;
                case 'info':
                    iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                    break;
                case 'warning':
                    iconPath =
                        'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z';
                    break;
                default:
                    iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
            }

            notification.innerHTML = `
                <div class="notification-icon">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path>
                    </svg>
                </div>
                <div class="notification-content">
                    <div class="notification-title">${displayTitle}</div>
                    <div class="notification-message">${message}</div>
                </div>
                <button class="notification-close">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
                <div class="notification-progress"></div>
            `;

            const closeButton = notification.querySelector('.notification-close');
            const progressBar = notification.querySelector('.notification-progress');

            // Gestion de la fermeture manuelle
            closeButton.addEventListener('click', function() {
                hideNotification(notification);
            });

            // Animation de la barre de progression
            let progress = 100;
            const progressInterval = setInterval(() => {
                progress -= 1;
                progressBar.style.width = progress + '%';
                if (progress <= 0) {
                    clearInterval(progressInterval);
                }
            }, 30); // 3000ms / 100 = 30ms par étape

            notificationContainer.appendChild(notification);

            // Animation d'entrée
            setTimeout(() => {
                notification.classList.add('show');
            }, 10);

            // Auto-fermeture après 3 secondes
            setTimeout(() => {
                hideNotification(notification);
                clearInterval(progressInterval);
            }, 3000);

            function hideNotification(notification) {
                notification.classList.remove('show');
                notification.classList.add('hide');
                setTimeout(() => {
                    if (notificationContainer.contains(notification)) {
                        notificationContainer.removeChild(notification);
                    }
                }, 300);
            }
        }

        // Déposer button event
        if (deposerBtn) {
            deposerBtn.addEventListener('click', function(e) {
                if (isReadOnly) {
                    showNotification('warning',
                        'Ce rapport ne peut plus être déposé car il l\'est déjà');
                    return;
                }
                e.preventDefault();
                document.querySelector('input[name="action"]').value = 'deposer_rapport';
                document.getElementById('rapportForm').submit();
            });
        }
    });
    </script>

</body>

</html>
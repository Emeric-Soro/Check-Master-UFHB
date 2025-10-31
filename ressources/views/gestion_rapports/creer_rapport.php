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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/tinymce/6.4.2/tinymce.min.js"></script>
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

    /* TinyMCE customizations */
    .tox-tinymce {
        border-radius: 0.5rem !important;
        border: 1px solid #e2e8f0 !important;
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
                    <?php if (isset($rapportDejaDepose) && $rapportDejaDepose): ?>
                    Consultation du Rapport de Stage
                    <?php else: ?>
                    Éditeur de Rapport de Stage
                    <?php endif; ?>
                </h1>
                <p class="text-gray-600 mt-2">
                    <?php if (isset($rapportDejaDepose) && $rapportDejaDepose): ?>
                    Consultation en lecture seule - Rapport déjà déposé
                    <?php else: ?>
                    Créez et modifiez votre rapport facilement
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex space-x-3 mt-4 md:mt-0">
                <?php if (!isset($rapportDejaDepose) || !$rapportDejaDepose): ?>
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
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                placeholder="Ex: Rapport de stage - Développement Web"
                                <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'readonly' : 'required' ?>>
                        </div>
                        <div>
                            <label for="theme_rapport" class="block text-sm font-medium text-gray-700 mb-2">
                                Thème du rapport <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="theme_rapport" name="theme_rapport"
                                value="<?= isset($rapport) ? htmlspecialchars($rapport['theme_rapport']) : '' ?>"
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                placeholder="Ex: Intégration d'un système CRM"
                                <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'readonly' : 'required' ?>>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 border-b border-gray-200 p-4 flex flex-wrap justify-between items-center">
                    <div class="flex items-center space-x-4 mb-3 md:mb-0">
                        <?php if (!isset($rapportDejaDepose) || !$rapportDejaDepose): ?>
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
                    <div class="flex items-center space-x-3">
                        <div class="relative">
                            <select id="fontSelector"
                                class="bg-white border border-gray-300 text-gray-700 py-2 pl-3 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'disabled' : '' ?>>
                                <option value="Arial, sans-serif">Arial</option>
                                <option value="Times New Roman, serif">Times New Roman</option>
                                <option value="Calibri, sans-serif">Calibri</option>
                                <option value="Georgia, serif">Georgia</option>
                                <option value="Verdana, sans-serif">Verdana</option>
                            </select>
                        </div>
                        <div class="relative">
                            <select id="fontSize"
                                class="bg-white border border-gray-300 text-gray-700 py-2 pl-3 pr-8 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'bg-gray-100 cursor-not-allowed' : '' ?>"
                                <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'disabled' : '' ?>>
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
            <?= (isset($rapportDejaDepose) && $rapportDejaDepose) ? 'true' : 'false' ?>;

        let editor;

        // Initialize TinyMCE editor
        tinymce.init({
            selector: '#editor',
            height: 800,
            menubar: true,
            plugins: [
                'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
                'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
                'insertdatetime', 'media', 'table', 'help', 'wordcount'
            ],
            toolbar: 'undo redo | blocks | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
            content_style: 'body { font-family: Arial, sans-serif; font-size: 14px; }',
            readonly: isReadOnly,
            setup: function(ed) {
                editor = ed;

                ed.on('init', function() {
                    document.getElementById('editor').classList.remove('hidden');

                    <?php if (isset($isEditMode) && $isEditMode && !empty($contenuRapport)): ?>
                    // Mode édition : charger le contenu du rapport
                    setTimeout(function() {
                        editor.setContent(<?= json_encode($contenuRapport) ?>);
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
                    editor.setContent('');
                    editorContainer.classList.remove('document-loaded');
                    editorContainer.classList.add('document-loading');
                    documentStatusMessage.classList.remove('hidden');
                    <?php endif; ?>
                });
            }
        });

        // Template content - Modèle standardisé de rapport de stage
        const templateContent = `
        <div style="display: flex; justify-content: space-between " id="header_rapport">
            <div style="text-align: center; margin-bottom: 40px;" id="header_rapport1">

                <div style="margin: 16px 0;">
                    <p style="font-size: 14px; color: #666;">[LOGO DE L'UNIVERSITÉ]</p>
                </div>

                <h2 style="font-size: 16px; font-weight: bold; margin-bottom: 8px; color: #2d3748;">
                    [NOM DE L'UNIVERSITÉ]
                </h2>
                
                <h3 style="font-size: 14px; font-weight: normal; margin-bottom: 15px; color: #4a5568;">
                    [FACULTÉ/ÉCOLE] - [DÉPARTEMENT/SPÉCIALITÉ]
                </h3>
                
                <div style="margin: 16px 0; padding: 10px;">
                    <p style="font-size: 12px; font-weight: bold; margin: 2px 0; color: #2d3748;">RÉPUBLIQUE DE [PAYS]</p>
                    <p style="font-size: 12px; margin: 2px 0; color: #4a5568;">[DEVISE NATIONALE]</p>
                </div>

                
            </div>

            <div style="text-align: center; margin-bottom: 60px;" id="header_rapport2">
                <div style="margin: 16px 0;">
                    <p style="font-size: 12px; color: #666;">[LOGO ENTREPRISE D'ACCUEIL]</p>
                    <p style="font-size: 12px; color: #666;">[LOGO PARTENAIRE]</p>
                </div>
                <h2 style="font-size: 16px; font-weight: bold; margin-bottom: 15px; color: #2d3748;">
                    [NOM DE L'ENTREPRISE D'ACCUEIL]
                </h2>
            </div>    
                
        </div>

            <div id="theme_rapport" style="display: flex; flex-direction: column; align-items: center;">
               
            <p style="font-size: 14px; margin-bottom: 8px; color: #4a5568;">
                    Mémoire de fin de cycle pour l'obtention du :
                </p>
                <p style="font-size: 14px; font-weight: bold; margin-bottom: 5px; color: #2d3748;">
                    [DIPLÔME]
                </p>
                <p style="font-size: 13px; margin-bottom: 20px; color: #4a5568;">
                    [SPÉCIALITÉ/OPTION]
                </p>  
                <h2 style="font-size: 20px; font-weight: bold; margin-bottom: 10px; color: #2d3748;">Thème :</h2>
                <p style="font-size: 16px; font-weight: bold; margin-bottom: 5px; color: #2d3748; line-height: 1.4;">
                    [TITRE PRINCIPAL DU THÈME] :
                </p>
                <p style="font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #2d3748; line-height: 1.4;">
                    [SOUS-TITRE OU PRÉCISION]
                </p>
                
                <div style="margin: 30px 0; padding: 25px; border: 2px solid #e2e8f0; border-radius: 10px; background-color: #f7fafc;">
                    <p style="font-size: 13px; font-weight: bold; margin-bottom: 8px; color: #2d3748; text-decoration: underline;">
                        PRÉSENTÉ PAR :
                    </p>
                    <p style="font-size: 14px; font-weight: bold; color: #2d3748;">
                        [CIVILITÉ] [NOM COMPLET DE L'ÉTUDIANT]
                    </p>
                    <p style="font-size: 12px; margin-top: 5px; color: #4a5568;">
                        Matricule : [NUMÉRO D'ÉTUDIANT]
                    </p>
                </div>
            </div>
            
            <div id="body_rapport">

            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #1a365d; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                    PRÉSENTATION DU CADRE DE RÉFÉRENCE
                </h1>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    [NOM DE L'ENTREPRISE] est une société spécialisée dans [DOMAINE D'ACTIVITÉ], qui a développé des compétences complémentaires pour répondre aux besoins spécifiques de ses clients et à leur évolution.
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Créée en [DATE DE CRÉATION], [NOM DE L'ENTREPRISE] fournit [TYPE DE SERVICES] qui s'articulent autour d'une idée forte qui est de [OBJECTIF PRINCIPAL]. Elle s'appuie sur les connaissances professionnelles de ses [TYPE DE PROFESSIONNELS], qui peuvent pleinement appréhender [DOMAINE DE COMPÉTENCE], à savoir [LISTE DES COMPÉTENCES]. Elle accompagne surtout les entreprises présentes dans les domaines de [SECTEURS D'ACTIVITÉ].
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 20px; color: #2d3748;">
                    [VALEURS/PRINCIPES] représentent le point culminant des réflexions de l'entreprise. L'objectif de [NOM DE L'ENTREPRISE] est de fournir dans la durée, un service qui apporte une réelle valeur ajoutée aux entreprises. L'ensemble des méthodes de travail, des outils utilisés et des programmes de formation vont dans le sens de [APPROCHE] et assurent ainsi la pérennité de notre qualité de service.
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    [NOM DE L'ENTREPRISE] organise ses activités métiers autour de [NOMBRE] pôles d'expertises :
                </p>
                
                <ul style="margin-left: 20px; margin-bottom: 20px; color: #2d3748;">
                    <li style="margin-bottom: 8px; line-height: 1.5;"><strong>[POLE 1]</strong> : [DESCRIPTION DU POLE 1]</li>
                    <li style="margin-bottom: 8px; line-height: 1.5;"><strong>[POLE 2]</strong> : [DESCRIPTION DU POLE 2]</li>
                    <li style="margin-bottom: 8px; line-height: 1.5;"><strong>[POLE 3]</strong> : [DESCRIPTION DU POLE 3]</li>
                    <li style="margin-bottom: 8px; line-height: 1.5;"><strong>[POLE 4]</strong> : [DESCRIPTION DU POLE 4]</li>
                </ul>

                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Pour la « valeur ajoutée », [NOM DE L'ENTREPRISE] apporte à ses prestations :
                </p>
                
                <ul style="margin-left: 20px; margin-bottom: 20px; color: #2d3748;">
                    <li style="margin-bottom: 8px; line-height: 1.5;">[VALEUR AJOUTÉE 1]</li>
                    <li style="margin-bottom: 8px; line-height: 1.5;">[VALEUR AJOUTÉE 2]</li>
                    <li style="margin-bottom: 8px; line-height: 1.5;">[VALEUR AJOUTÉE 3]</li>
                </ul>
            </div>

            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #1a365d; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                    INTRODUCTION : GÉNÉRALITÉS & PROBLÉMATIQUE
                </h1>
                
                <h2 style="font-size: 15px; font-weight: bold; margin-bottom: 15px; color: #2d3748;">Généralités</h2>
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Dans le contexte actuel de [CONTEXTE GÉNÉRAL], les entreprises cherchent constamment à améliorer leurs processus de [DOMAINE] pour rester compétitives et répondre aux besoins de leurs clients. Le secteur [SECTEUR] n'échappe pas à cette réalité. Les [TYPE D'INSTITUTIONS], telles que [EXEMPLES], adoptent de plus en plus de solutions technologiques avancées pour gérer efficacement leurs [ACTIVITÉS]. Les systèmes de [TYPE DE SYSTÈMES] sont devenus des outils essentiels pour assurer la compétitivité et la pérennité des [INSTITUTIONS] sur les marchés globalisés.
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Sur le plan international, l'intégration des systèmes [SYSTÈME 1] avec d'autres systèmes de gestion, comme les [SYSTÈME 2], est devenue une nécessité pour garantir une vue unifiée et cohérente des [OPÉRATIONS]. Cette intégration permet non seulement de rationaliser les processus internes, mais aussi d'offrir une meilleure expérience client, en centralisant les informations et en facilitant l'accès à des données cruciales pour la prise de décision.
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Au niveau national, dans des pays comme [PAYS] où l'industrie des [SECTEUR] est en pleine expansion, les entreprises de [DOMAINE] commencent à investir dans ces technologies pour répondre aux besoins croissants du marché. Cette tendance se reflète en effet dans les stratégies adoptées par des sociétés telles que [NOM DE L'ENTREPRISE], qui cherchent à intégrer des modules sophistiqués pour relier leurs systèmes [SYSTÈME 1] à leurs [SYSTÈME 2].
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 20px; color: #2d3748;">
                    Dans ce contexte, notre mémoire se focalisera sur le thème suivant : [TITRE COMPLET DU PROJET].
                </p>
                
                <h2 style="font-size: 15px; font-weight: bold; margin-bottom: 15px; color: #2d3748;">Problématique</h2>
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Dans un environnement où l'efficacité et la réactivité sont des critères déterminants de succès, la mise en place d'une solution intégrée entre [SYSTÈME 1] et [SYSTÈME 2] se pose comme un défi majeur. [NOM DE L'ENTREPRISE] se trouve confrontée à la difficulté de gérer efficacement [PROBLÈME 1] tout en assurant [PROBLÈME 2]. L'absence d'une intégration harmonieuse entre [SYSTÈME 1] et [SYSTÈME 2] entraîne [CONSÉQUENCES NÉGATIVES].
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 20px; color: #2d3748;">
                    La problématique à laquelle répond notre projet est donc la suivante : <strong>[FORMULATION DE LA PROBLÉMATIQUE]</strong>
                </p>
            </div>

            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #1a365d; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                    OBJECTIFS GÉNÉRAUX ET SPÉCIFIQUES
                </h1>
                
                <h2 style="font-size: 15px; font-weight: bold; margin-bottom: 15px; color: #2d3748;">1. Objectif général</h2>
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 20px; color: #2d3748;">
                    L'objectif principal est de [OBJECTIF GÉNÉRAL], qui permettra de [BÉNÉFICES ATTENDUS], afin d'améliorer [RÉSULTATS ESPÉRÉS].
                </p>
                
                <h2 style="font-size: 15px; font-weight: bold; margin-bottom: 15px; color: #2d3748;">2. Objectifs spécifiques</h2>
                <ul style="margin-left: 20px; margin-bottom: 20px; color: #2d3748;">
                    <li style="margin-bottom: 10px; line-height: 1.5;"><strong>[OBJECTIF SPÉCIFIQUE 1]</strong> : [DESCRIPTION DÉTAILLÉE]</li>
                    <li style="margin-bottom: 10px; line-height: 1.5;"><strong>[OBJECTIF SPÉCIFIQUE 2]</strong> : [DESCRIPTION DÉTAILLÉE]</li>
                    <li style="margin-bottom: 10px; line-height: 1.5;"><strong>[OBJECTIF SPÉCIFIQUE 3]</strong> : [DESCRIPTION DÉTAILLÉE]</li>
                </ul>
            </div>

            <div style="margin-bottom: 40px;">
                <h1 style="font-size: 16px; font-weight: bold; margin-bottom: 20px; color: #1a365d; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;">
                    MÉTHODOLOGIE
                </h1>
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Suite à la problématique, le déroulement de notre projet comportera [NOMBRE] grandes parties.
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 10px; color: #2d3748;">
                    En Première Partie, <em>[TITRE DE LA PREMIÈRE PARTIE]</em>, dans laquelle nous présenterons [CONTENU DE LA PREMIÈRE PARTIE].
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 10px; color: #2d3748;">
                    En deuxième Partie, nous déroulerons [TITRE DE LA DEUXIÈME PARTIE], quitte à définir [CONTENU DE LA DEUXIÈME PARTIE].
                </p>
                
                <p style="text-align: justify; line-height: 1.6; margin-bottom: 15px; color: #2d3748;">
                    Enfin la Troisième Partie qui est [TITRE DE LA TROISIÈME PARTIE]. Dans cette partie nous déroulerons [CONTENU DE LA TROISIÈME PARTIE].
                </p>
            </div>
        </div>
            `;

        // Load template button event
        loadTemplateBtn.addEventListener('click', function(e) {
            e.preventDefault(); // IMPORTANT : Empêcher la soumission du formulaire

            loadingIndicator.classList.remove('hidden');
            documentStatusMessage.textContent = 'Chargement du modèle en cours...';

            setTimeout(function() {
                if (editor) {
                    editor.setContent(templateContent);
                    editorContainer.classList.remove('document-loading');
                    editorContainer.classList.add('document-loaded');
                    documentStatusMessage.classList.add('hidden');
                }
                loadingIndicator.classList.add('hidden');
                showNotification('success', 'Modèle chargé avec succès!');
            }, 1500);
        });

        // Font selector event
        fontSelector.addEventListener('change', function() {
            if (editor) {
                editor.execCommand('fontName', false, this.value);
            }
        });

        // Font size selector event
        fontSize.addEventListener('change', function() {
            if (editor) {
                editor.execCommand('fontSize', false, this.value);
            }
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

            const content = editor.getContent();
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

            const content = editor.getContent();
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
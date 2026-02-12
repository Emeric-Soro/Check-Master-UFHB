<?php
// Inclure le helper de permissions
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';

// Déterminer si c'est une édition ou création
$isEditingExisting = isset($isEditMode) && $isEditMode && isset($rapport);

// Récupérer les informations de l'étudiant depuis la session
$numEtu = $_SESSION['num_etu'] ?? '';
$nomEtu = $_SESSION['nom_etu'] ?? '';
$prenomEtu = $_SESSION['prenom_etu'] ?? '';
$nomCompletEtu = trim($nomEtu . ' ' . $prenomEtu);

// URLs des logos
$baseUrl = (isset($_SERVER["REQUEST_SCHEME"]) ? $_SERVER["REQUEST_SCHEME"] . "://" . $_SERVER["HTTP_HOST"] : "") . '/checkmaster.ufrmi-ufhb-ci/public/image/';
$logoUfhb = $baseUrl . 'logo_ufhb.png';
$logoCiv = $baseUrl . 'logo_civ.png';
?>
<!-- Ressources nécessaires pour l'éditeur -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.5/jodit.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jodit/3.24.5/jodit.min.js"></script>
<style>
    :root {
        --primary-green: #1B5E20;
        --light-bg: #DFF2FF;
    }

        body {
            background-color: var(--light-bg);
        }

        /* Notification styles */
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

        .notification.show { transform: translateX(0); opacity: 1; }
        .notification.hide { transform: translateX(100%); opacity: 0; }
        .notification.success { border-left-color: #10b981; background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%); }
        .notification.error { border-left-color: #ef4444; background: linear-gradient(135deg, #fef2f2 0%, #fef2f2 100%); }
        .notification.info { border-left-color: #3b82f6; background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%); }
        .notification.warning { border-left-color: #f59e0b; background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%); }

        .notification-icon { flex-shrink: 0; width: 24px; height: 24px; display: flex; align-items: center; justify-content: center; }
        .notification-content { flex: 1; min-width: 0; }
        .notification-title { font-weight: 600; font-size: 14px; margin-bottom: 4px; color: #1f2937; }
        .notification-message { font-size: 13px; color: #6b7280; line-height: 1.4; word-wrap: break-word; }
        .notification-close { flex-shrink: 0; width: 20px; height: 20px; cursor: pointer; color: #9ca3af; transition: color 0.2s; background: none; border: none; padding: 0; }
        .notification-close:hover { color: #6b7280; }
        .notification-progress { position: absolute; bottom: 0; left: 0; height: 3px; background-color: currentColor; opacity: 0.3; transition: width 0.03s linear; }

        /* Tab styles */
        .tab-btn {
            padding: 14px 28px;
            border: none;
            background: #e5e7eb;
            cursor: pointer;
            font-weight: 600;
            font-size: 15px;
            transition: all 0.2s;
        }
        .tab-btn.active {
            background: var(--primary-green);
            color: white;
        }
        .tab-btn:first-child { border-radius: 10px 0 0 10px; }
        .tab-btn:last-child { border-radius: 0 10px 10px 0; }
        .tab-btn:hover:not(.active) { background: #d1d5db; }

        .tab-content { display: none; }
        .tab-content.active { display: block; }

        /* Form styles */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: 0.875rem; font-weight: 600; color: #374151; margin-bottom: 6px; }
        .form-input {
            width: 100%;
            padding: 10px 14px;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            font-size: 0.9rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .form-input:focus {
            outline: none;
            border-color: var(--primary-green);
            box-shadow: 0 0 0 3px rgba(27, 94, 32, 0.1);
        }
        .form-input:disabled, .form-input[readonly] {
            background-color: #f3f4f6;
            cursor: not-allowed;
        }
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        @media (max-width: 768px) { .form-row { grid-template-columns: 1fr; } }

        /* Cover Preview */
        .cover-preview {
            background: white;
            border: 2px solid #e5e7eb;
            border-radius: 8px;
            padding: 15px;
            min-height: 400px;
            transform: scale(0.55);
            transform-origin: top left;
            width: 182%;
        }

        /* Jodit customization */
        .jodit-container { border-radius: 8px !important; overflow: hidden; }
        .jodit-toolbar { background: #f8fafc !important; }

        /* Preview Modal */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .modal-overlay.show { opacity: 1; visibility: visible; }
        .modal-content {
            background: white;
            border-radius: 16px;
            padding: 24px;
            max-width: 900px;
            width: 95%;
            max-height: 90vh;
            overflow-y: auto;
            transform: scale(0.9);
            transition: transform 0.3s ease;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
        }
        .modal-overlay.show .modal-content { transform: scale(1); }

        /* PDF Loading */
        .pdf-loading {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            z-index: 2000;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease;
        }
        .pdf-loading.show { opacity: 1; visibility: visible; }
        .pdf-loading .spinner {
            width: 60px;
            height: 60px;
            border: 5px solid #374151;
            border-top: 5px solid #10b981;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .pdf-loading p { color: white; margin-top: 20px; font-size: 1.2rem; }
    </style>

<div class="min-h-screen">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">📄 Éditeur de Rapport de Stage</h1>
                <p class="text-gray-600 mt-1">
                    <?php if ($isEditingExisting): ?>
                        Mode édition : <?= htmlspecialchars(isset($rapport) && isset($rapport['nom_rapport']) ? $rapport['nom_rapport'] : 'Rapport') ?>
                    <?php else: ?>
                        Approche hybride : Template fixe + Éditeur Jodit
                    <?php endif; ?>
                </p>
            </div>
            <div class="flex flex-wrap gap-3 mt-4 md:mt-0">
                <?php if (!isset($GLOBALS['rapportDejaDepose']) || !$GLOBALS['rapportDejaDepose']): ?>
                    <button id="saveBtn" class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                        </svg>
                        Enregistrer
                    </button>
                <?php endif; ?>
                <button id="previewBtn" class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Aperçu
                </button>
                <button id="exportBtn" class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Exporter PDF
                </button>
                <?php if (!isset($GLOBALS['rapportDejaDepose']) || !$GLOBALS['rapportDejaDepose']): ?>
                    <button id="deposerBtn" class="bg-yellow-500 hover:bg-yellow-600 text-white font-semibold py-2 px-4 rounded-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Déposer
                    </button>
                <?php else: ?>
                    <button disabled class="bg-gray-400 text-white font-semibold py-2 px-4 rounded-lg flex items-center cursor-not-allowed" title="Rapport déjà déposé">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Déposé ✓
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Main Content -->
        <div class="bg-white rounded-xl shadow-lg overflow-hidden">
            <?php if (isset($erreurs) && !empty($erreurs)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 m-4 rounded">
                    <strong>Erreurs de validation :</strong>
                    <ul class="mt-2 list-disc list-inside">
                        <?php foreach ($erreurs as $erreur): ?>
                            <li><?= htmlspecialchars($erreur) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <!-- Form hidden -->
            <form id="rapportForm" method="POST" style="display: none;">
                <input type="hidden" name="action" value="save_rapport">
                <?php if ($isEditingExisting): ?>
                    <input type="hidden" name="edit_id" value="<?= $rapport['id_rapport'] ?>">
                <?php endif; ?>
                <input type="hidden" id="nom_rapport_hidden" name="nom_rapport" value="<?= isset($rapport) ? htmlspecialchars($rapport['nom_rapport']) : '' ?>">
                <input type="hidden" id="theme_rapport_hidden" name="theme_rapport" value="<?= isset($rapport) ? htmlspecialchars($rapport['theme_rapport']) : '' ?>">
                <input type="hidden" id="contenu_rapport" name="contenu_rapport">
                <input type="hidden" id="cover_data" name="cover_data">
            </form>

            <!-- Tabs -->
            <div class="border-b border-gray-200 p-4">
                <div class="flex">
                    <button class="tab-btn active" data-tab="cover">1️⃣ Page de Couverture</button>
                    <button class="tab-btn" data-tab="body">2️⃣ Corps du Rapport</button>
                </div>
            </div>

            <!-- Tab 1: Cover Page Form -->
            <div id="tab-cover" class="tab-content active p-6">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Left: Form -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">📝 Informations de la Page de Couverture</h3>
                        
                        <div id="coverPageFormContainer">
                            <!-- Nom du rapport -->
                            <div class="form-group">
                                <label class="form-label">Nom du rapport *</label>
                                <input type="text" id="nom_rapport" class="form-input" 
                                    value="<?= isset($rapport) ? htmlspecialchars($rapport['nom_rapport']) : '' ?>" 
                                    placeholder="Ex: Rapport de stage - Développement Web"
                                    <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?> required>
                            </div>

                            <!-- Diplôme & Option -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Diplôme *</label>
                                    <select id="diplome" class="form-input" <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'disabled' : '' ?> required>
                                        <option value="Diplôme d'Ingénieur de conception en informatique">Diplôme d'Ingénieur de conception en informatique</option>
                                        <option value="Licence Professionnelle">Licence Professionnelle</option>
                                        <option value="Master Professionnel">Master Professionnel</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Option/Spécialité *</label>
                                    <select id="option_specialite" class="form-input" <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'disabled' : '' ?> required>
                                        <option value="Option Méthodes Informatiques Appliquées à la Gestion des Entreprises">MIAGE</option>
                                        <option value="Option Génie Logiciel">Génie Logiciel</option>
                                        <option value="Option Réseaux et Systèmes">Réseaux et Systèmes</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Étudiant -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Civilité</label>
                                    <select id="civilite" class="form-input" <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'disabled' : '' ?>>
                                        <option value="M.">M.</option>
                                        <option value="Mme">Mme</option>
                                        <option value="Mlle">Mlle</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Matricule</label>
                                    <input type="text" id="matricule" class="form-input" value="<?= htmlspecialchars($numEtu) ?>" readonly>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Nom complet de l'étudiant *</label>
                                <input type="text" id="nom_etudiant" class="form-input" value="<?= htmlspecialchars($nomCompletEtu) ?>" 
                                    <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?> required>
                            </div>

                            <!-- Thème -->
                            <div class="form-group">
                                <label class="form-label">Titre du thème *</label>
                                <input type="text" id="titre_theme" class="form-input" 
                                    value="<?= isset($rapport) ? htmlspecialchars($rapport['theme_rapport']) : '' ?>" 
                                    placeholder="Ex: MISE EN PLACE D'UN MODULE D'INTEGRATION..."
                                    <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?> required>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Sous-titre (optionnel)</label>
                                <input type="text" id="sous_titre" class="form-input" 
                                    placeholder="Ex: CAS DE L'ENTREPRISE XYZ"
                                    <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?>>
                            </div>

                            <!-- Entreprise -->
                            <div class="form-group">
                                <label class="form-label">Entreprise d'accueil *</label>
                                <input type="text" id="nom_entreprise" class="form-input" 
                                    placeholder="Ex: KYRIA CONSULTANCY SERVICES"
                                    value="<?= isset($GLOBALS['stage_info']['nom_entreprise']) ? htmlspecialchars($GLOBALS['stage_info']['nom_entreprise']) : '' ?>"
                                    <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?> required>
                            </div>

                            <!-- Encadrement -->
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Encadreur académique</label>
                                    <input type="text" id="encadreur" class="form-input" 
                                        placeholder="Nom de l'encadreur"
                                        <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?>>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Maître de stage</label>
                                    <input type="text" id="maitre_stage" class="form-input" 
                                        placeholder="Nom du maître de stage"
                                        value="<?= isset($GLOBALS['stage_info']['encadrant_entreprise']) ? htmlspecialchars($GLOBALS['stage_info']['encadrant_entreprise']) : '' ?>"
                                        <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?>>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Fonction du maître de stage</label>
                                <input type="text" id="fonction_maitre" class="form-input" 
                                    placeholder="Ex: Consultant à KYRIA Consultancy Services"
                                    <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'readonly' : '' ?>>
                            </div>

                            <?php if (!isset($GLOBALS['rapportDejaDepose']) || !$GLOBALS['rapportDejaDepose']): ?>
                                <button type="button" id="updatePreviewBtn" class="w-full mt-4 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-6 rounded-lg transition">
                                    🔄 Mettre à jour l'aperçu
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Right: Preview -->
                    <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">👁️ Aperçu de la Page de Couverture</h3>
                        <div id="coverPreview" class="cover-preview">
                            <!-- Le contenu sera généré dynamiquement -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Report Body Editor -->
            <div id="tab-body" class="tab-content p-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">📝 Corps du Rapport (Éditeur Jodit)</h3>
                <textarea id="jodit-editor"></textarea>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-6 text-center text-gray-600 text-sm">
            <p>© 2025 - Éditeur de Rapport de Stage | Développé pour faciliter la création de rapports professionnels</p>
        </div>
    </div>

    <!-- Notifications -->
    <div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-3"></div>

    <!-- Preview Modal -->
    <div id="previewModal" class="modal-overlay">
        <div class="modal-content">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">👁️ Aperçu du Rapport Complet</h2>
                <button id="closePreviewModal" class="text-gray-500 hover:text-gray-700">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            <div id="fullPreview" style="background: white; padding: 20px; border: 1px solid #ddd; max-height: 70vh; overflow-y: auto;"></div>
        </div>
    </div>

    <!-- PDF Loading Overlay -->
    <div id="pdfLoading" class="pdf-loading">
        <div class="spinner"></div>
        <p>Génération du PDF en cours...</p>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Configuration
            const logoUfhb = '<?= $logoUfhb ?>';
            const logoCiv = '<?= $logoCiv ?>';
            const isReadOnly = <?= (isset($GLOBALS['rapportDejaDepose']) && $GLOBALS['rapportDejaDepose']) ? 'true' : 'false' ?>;
            const isEditMode = <?= $isEditingExisting ? 'true' : 'false' ?>;

            // Elements
            const tabBtns = document.querySelectorAll('.tab-btn');
            const tabContents = document.querySelectorAll('.tab-content');
            const updatePreviewBtn = document.getElementById('updatePreviewBtn');
            const coverPreview = document.getElementById('coverPreview');
            const previewBtn = document.getElementById('previewBtn');
            const previewModal = document.getElementById('previewModal');
            const closePreviewModal = document.getElementById('closePreviewModal');
            const fullPreview = document.getElementById('fullPreview');
            const pdfLoading = document.getElementById('pdfLoading');
            const saveBtn = document.getElementById('saveBtn');
            const exportBtn = document.getElementById('exportBtn');
            const deposerBtn = document.getElementById('deposerBtn');
            const rapportForm = document.getElementById('rapportForm');

            // Initialize Jodit Editor
            const joditEditor = Jodit.make('#jodit-editor', {
                height: 500,
                language: 'fr',
                placeholder: 'Commencez à rédiger le corps de votre rapport ici...',
                toolbarButtonSize: 'middle',
                readonly: isReadOnly,
                buttons: [
                    'source', '|',
                    'bold', 'italic', 'underline', 'strikethrough', '|',
                    'font', 'fontsize', 'brush', 'paragraph', '|',
                    'ul', 'ol', 'indent', 'outdent', '|',
                    'align', 'undo', 'redo', '|',
                    'table', 'link', 'image', '|',
                    'hr', 'copyformat', 'fullsize', 'print'
                ],
                uploader: {
                    insertImageAsBase64URI: true
                },
                defaultFontSize: '12pt',
                defaultFontName: 'Times New Roman'
            });

            // Load existing content if in edit mode
            <?php if ($isEditingExisting && !empty($contenuRapport)): ?>
                joditEditor.value = <?= json_encode($contenuRapport) ?>;
            <?php else: ?>
                joditEditor.value = getInitialBodyContent();
            <?php endif; ?>

            // Tab switching
            tabBtns.forEach(btn => {
                btn.addEventListener('click', () => {
                    const tabId = btn.dataset.tab;
                    tabBtns.forEach(b => b.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    btn.classList.add('active');
                    document.getElementById('tab-' + tabId).classList.add('active');
                });
            });

            // Generate Cover Page HTML - Pixel Perfect Match
            function generateCoverPageHTML() {
                const data = getCoverData();
                const logoKyria = '<?= $baseUrl ?>logoCM.png'; // Logo entreprise
                
                return `
<div style="font-family: 'Times New Roman', serif; width: 210mm; min-height: 297mm; padding: 20mm 25mm; box-sizing: border-box; background: white;">
    <!-- En-têtes officiels -->
    <table style="width: 100%; border: none; margin-bottom: 5px;">
        <tr>
            <td style="width: 50%; text-align: left; font-size: 10pt; vertical-align: top; border: none; padding: 0;">
                MINISTERE DE L'ENSEIGNEMENT SUPERIEUR<br/>
                ET DE LA RECHERCHE SCIENTIFIQUE
            </td>
            <td style="width: 50%; text-align: right; font-size: 10pt; vertical-align: top; border: none; padding: 0;">
                REPUBLIQUE DE COTE D'IVOIRE<br/>
                UNION - DISCIPLINE - TRAVAIL
            </td>
        </tr>
    </table>

    <!-- Section Logos et Informations -->
    <table style="width: 100%; border: none; margin: 15px 0 20px 0;">
        <tr>
            <!-- Colonne gauche: UFHB -->
            <td style="width: 50%; text-align: center; vertical-align: top; border: none; padding: 10px;">
                <img src="${logoUfhb}" alt="Logo UFHB" style="width: 70px; height: auto;" onerror="this.style.display='none'"/><br/><br/>
                <span style="font-size: 11pt; font-weight: bold; color: #1a5276;">UNIVERSITE FELIX HOUPHOUET BOIGNY</span><br/><br/>
                <span style="font-size: 10pt;">UFR MATHEMATIQUES ET INFORMATIQUE</span><br/>
                <span style="font-size: 10pt;">FILIERES PROFESSIONNALISEES MIAGE-GI</span>
            </td>
            <!-- Colonne droite: Armoiries + Entreprise -->
            <td style="width: 50%; text-align: center; vertical-align: top; border: none; padding: 10px;">
                <img src="${logoCiv}" alt="Armoiries CI" style="width: 65px; height: auto;" onerror="this.style.display='none'"/>
                <img src="${logoKyria}" alt="Logo Entreprise" style="width: 65px; height: auto; margin-left: 15px;" onerror="this.style.display='none'"/><br/><br/>
                <span style="font-size: 11pt; font-weight: bold;">${escapeHtml(data.nomEntreprise).toUpperCase()}</span>
            </td>
        </tr>
    </table>

    <!-- Mémoire de fin de cycle -->
    <div style="text-align: center; margin: 25px 0 15px 0;">
        <p style="font-size: 11pt; margin: 0 0 8px 0;">Mémoire de fin de cycle pour l'obtention du :</p>
        <p style="font-size: 12pt; font-weight: bold; font-style: italic; margin: 0 0 5px 0;">${escapeHtml(data.diplome)}</p>
        <p style="font-size: 10pt; font-style: italic; margin: 0;">${escapeHtml(data.optionSpecialite)}</p>
    </div>

    <!-- Thème -->
    <div style="text-align: center; margin: 25px 0;">
        <p style="font-size: 11pt; font-weight: bold; margin: 0 0 12px 0;">Thème :</p>
        <div style="background-color: #1B5E20; color: white; padding: 18px 25px; margin: 0 auto; width: 95%; text-align: center;">
            <p style="font-size: 13pt; font-weight: bold; text-transform: uppercase; line-height: 1.5; margin: 0; text-align: center;">
                ${escapeHtml(data.titreTheme).toUpperCase()}${data.sousTitre ? ' :' : ''}
            </p>
            ${data.sousTitre ? `<p style="font-size: 12pt; font-weight: bold; text-transform: uppercase; margin: 8px 0 0 0; text-align: center;">${escapeHtml(data.sousTitre).toUpperCase()}</p>` : ''}
        </div>
    </div>

    <!-- Présenté par -->
    <div style="text-align: center; margin: 30px 0 25px 0;">
        <p style="font-size: 11pt; margin: 0 0 10px 0;">PRESENTE PAR :</p>
        <p style="font-size: 11pt; font-weight: bold; margin: 0;">${escapeHtml(data.civilite)} ${escapeHtml(data.nomEtudiant).toUpperCase()}</p>
    </div>

    <!-- Encadrement -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 30px;">
        <tr>
            <td style="width: 50%; padding: 20px; border: 2px solid #000; text-align: center; vertical-align: top;">
                <p style="font-size: 11pt; font-weight: bold; margin: 0 0 15px 0; text-align: center;">ENCADREUR</p>
                <p style="font-size: 10pt; margin: 0; text-align: center;">${escapeHtml(data.encadreur) || ''}</p>
            </td>
            <td style="width: 50%; padding: 20px; border: 2px solid #000; text-align: center; vertical-align: top;">
                <p style="font-size: 11pt; font-weight: bold; margin: 0 0 15px 0; text-align: center;">MAITRE DE STAGE</p>
                <p style="font-size: 11pt; font-weight: bold; margin: 0; text-align: center;">${escapeHtml(data.maitreStage) || ''}</p>
                ${data.fonctionMaitre ? `<p style="font-size: 9pt; font-style: italic; margin: 8px 0 0 0; text-align: center;">${escapeHtml(data.fonctionMaitre)}</p>` : ''}
            </td>
        </tr>
    </table>
</div>`;
            }

            // Get cover page data
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

            // Escape HTML
            function escapeHtml(text) {
                if (!text) return '';
                const div = document.createElement('div');
                div.textContent = text;
                return div.innerHTML;
            }

            // Initial body content - Pixel Perfect Match with Pages 2-4
            function getInitialBodyContent() {
                return `
<div style="font-family: 'Times New Roman', serif; font-size: 11pt; line-height: 1.5;">

<p style="font-size: 12pt; font-weight: bold; margin: 0 0 20px 0;">
    <span style="font-weight: bold;">I.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">PRESENTATION DU CADRE DE REFERENCE</span>
</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    KYRIA CONSULTANCY SERVICES (KYRIA-CS) est une société d'ingénierie financière spécialisée dans le conseil et la stratégie, qui a développé des compétences complémentaires pour répondre aux besoins spécifiques de ses clients et à leur évolution.
</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    Créée en Mai 2019, KYRIA-CS fournit un conseil et un accompagnement qui s'articulent autour d'une idée forte qui est de transformer une vision stratégique en actions et en processus. Elle s'appuie sur les connaissances professionnelles de ses ingénieurs, qui peuvent pleinement appréhender le système d'information de l'entreprise, à savoir la rédaction du cahier des charges, le développement logiciel et l'ingénierie. Elle accompagne surtout les entreprises présentent dans les domaines de la Finance, l'assurance, la mutualité, l'industrie et l'audit.
</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    KYRIA organise ses activités métiers autour de quatre pôles d'expertises :
</p>

<ul style="margin: 0 0 15px 40px; list-style-type: disc;">
    <li style="margin-bottom: 8px; text-align: justify;"><strong>La Stratégie</strong> : Stratégie opérationnelle, technologique ou commerciale</li>
    <li style="margin-bottom: 8px; text-align: justify;"><strong>Le Conseil</strong> : Transformation des entreprises et des administrations dans le contexte de la révolution numérique</li>
    <li style="margin-bottom: 8px; text-align: justify;"><strong>Le Numérique</strong> : Relation client, marketing numérique, big data, technologies mobiles, gestion de contenus, e-commerce</li>
    <li style="margin-bottom: 8px; text-align: justify;"><strong>La Technologie</strong> : Services technologiques, conseil, Logiciels sectoriels, recherche et développement</li>
</ul>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    Pour la « valeur ajoutée », KYRIA nous donnons à ses prestations :
</p>
<p style="margin: 0 0 5px 40px;">- Un accompagnement personnalisé</p>
<p style="margin: 0 0 5px 40px;">- Un conseil de proximité fourni par des professionnels expérimentés</p>
<p style="margin: 0 0 20px 40px;">- Une approche personnalisée de niveau international.</p>

<p style="font-size: 12pt; font-weight: bold; margin: 25px 0 20px 0;">
    <span style="font-weight: bold;">II.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">INTRODUCTION : GENERALITE &amp; PROBLEMATIQUE</span>
</p>

<p style="text-decoration: underline; font-weight: normal; margin: 0 0 15px 0;">Généralités</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    Dans le contexte actuel de la transformation numérique, les entreprises cherchent constamment à améliorer leurs processus de gestion pour rester compétitives et répondre aux besoins de leurs clients. Le secteur financier n'échappe pas à cette réalité. Les institutions financières, telles que les banques et les sociétés de gestion d'actifs, adoptent de plus en plus de solutions technologiques avancées pour gérer efficacement leurs relations avec les clients et les opérations d'investissement.
</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    Dans ce contexte, notre mémoire se focalisera sur le thème suivant : <strong>MISE EN PLACE D'UN MODULE D'INTEGRATION ENTRE ATLANTIS CRM ET ATLANTIS SGO : CAS DE LA BOA CAPITAL ASSET MANAGEMENT.</strong>
</p>

<p style="text-decoration: underline; font-weight: normal; margin: 20px 0 15px 0;">Problématique</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    Dans un environnement où l'efficacité et la réactivité sont des critères déterminants de succès, la mise en place d'une solution intégrée entre un système de gestion de la relation client (CRM) et un logiciel de gestion de placement collectif en valeurs mobilières (SGO) se pose comme un défi majeur.
</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    La problématique à laquelle répond notre projet est donc la suivante : <strong>Comment concevoir et implémenter un module d'intégration efficace entre Atlantis CRM et Atlantis SGO pour améliorer la gestion des relations client et des placements financiers ?</strong>
</p>

<p style="font-size: 12pt; font-weight: bold; margin: 25px 0 20px 0;">
    <span style="font-weight: bold;">III.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">OBJECTIFS GENERAUX ET SPECIFIQUES</span>
</p>

<p style="margin: 0 0 10px 0;"><strong>1. Objectif général</strong></p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    L'objectif principal est de concevoir et de mettre en œuvre un module d'intégration entre Atlantis CRM et Atlantis SGO, qui permettra de synchroniser les données client avec les informations de gestion des placements financiers, afin d'améliorer la qualité du service client et l'efficacité opérationnelle.
</p>

<p style="margin: 0 0 10px 0;"><strong>2. Objectifs spécifiques</strong></p>

<p style="margin: 0 0 8px 40px;">- <strong>Automatisation des flux de données</strong> : Développer des processus automatisés pour le transfert et la synchronisation des données entre Atlantis CRM et Atlantis SGO</p>
<p style="margin: 0 0 8px 40px;">- <strong>Amélioration de l'expérience utilisateur</strong> : Optimiser l'interface utilisateur pour permettre un accès facile et rapide aux informations intégrées, facilitant ainsi la prise de décision pour les gestionnaires de portefeuille et les équipes de service client.</p>
<p style="margin: 0 0 20px 40px;">- <strong>Renforcement de la sécurité et de l'intégrité des données</strong> : Mettre en place des mécanismes robustes pour assurer la protection des données sensibles échangées entre les deux systèmes et leur non redondance.</p>

<p style="font-size: 12pt; font-weight: bold; margin: 25px 0 20px 0;">
    <span style="font-weight: bold;">IV.</span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span style="font-weight: bold;">METHODOLOGIE</span>
</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    Suite à la problématique, le déroulement de notre projet comportera trois (3) grandes parties.
</p>

<p style="text-align: justify; margin: 0 0 10px 0; text-indent: 50px;">
    <strong>En Première Partie</strong>, <em>L'Approche Méthodologique</em>, dans laquelle nous présenterons la structure d'accueil, le cadre de référence ainsi que le projet.
</p>

<p style="text-align: justify; margin: 0 0 10px 0; text-indent: 50px;">
    <strong>En deuxième Partie</strong>, nous déroulerons la conception du système, quitte à définir la méthode d'étude, de modélisation et de réalisation du projet.
</p>

<p style="text-align: justify; margin: 0 0 15px 0; text-indent: 50px;">
    <strong>Enfin la Troisième Partie</strong> qui est la Réalisation de la solution. Dans cette partie nous déroulerons les différents outils utilisés, le système de gestion de base de données et scripts utilisés pour la réalisation de la solution cible.
</p>

</div>`;
            }

            // Update preview
            function updatePreview() {
                coverPreview.innerHTML = generateCoverPageHTML();
                // Update hidden fields
                document.getElementById('nom_rapport_hidden').value = document.getElementById('nom_rapport').value;
                document.getElementById('theme_rapport_hidden').value = document.getElementById('titre_theme').value + 
                    (document.getElementById('sous_titre').value ? ' : ' + document.getElementById('sous_titre').value : '');
                document.getElementById('cover_data').value = JSON.stringify(getCoverData());
            }

            // Initial preview
            updatePreview();

            // Update preview button
            if (updatePreviewBtn) {
                updatePreviewBtn.addEventListener('click', function() {
                    updatePreview();
                    showNotification('success', 'Aperçu mis à jour !');
                });
            }

            // Auto-update on form change
            document.querySelectorAll('#coverPageFormContainer input, #coverPageFormContainer select').forEach(el => {
                el.addEventListener('change', updatePreview);
                el.addEventListener('input', updatePreview);
            });

            // Preview full report
            previewBtn.addEventListener('click', () => {
                const coverHTML = generateCoverPageHTML();
                const bodyHTML = `<div style="font-family: 'Times New Roman', serif; width: 210mm; padding: 15mm 20mm; box-sizing: border-box; background: white;">${joditEditor.value}</div>`;
                fullPreview.innerHTML = coverHTML + '<div style="page-break-before: always;"></div>' + bodyHTML;
                previewModal.classList.add('show');
            });

            // Close preview modal
            closePreviewModal.addEventListener('click', () => previewModal.classList.remove('show'));
            previewModal.addEventListener('click', (e) => { if (e.target === previewModal) previewModal.classList.remove('show'); });

            // Save report
            if (saveBtn) {
                saveBtn.addEventListener('click', function() {
                    const nomRapport = document.getElementById('nom_rapport').value.trim();
                    const titreTheme = document.getElementById('titre_theme').value.trim();
                    
                    if (!nomRapport) {
                        showNotification('error', 'Veuillez saisir le nom du rapport');
                        return;
                    }
                    if (!titreTheme) {
                        showNotification('error', 'Veuillez saisir le titre du thème');
                        return;
                    }

                    // Prepare content: Cover + Body
                    const fullContent = generateCoverPageHTML() + 
                        '<div style="page-break-before: always;"></div>' +
                        '<div style="font-family: Times New Roman, serif; padding: 15mm 20mm;">' + joditEditor.value + '</div>';

                    // Update hidden fields
                    document.getElementById('nom_rapport_hidden').value = nomRapport;
                    document.getElementById('theme_rapport_hidden').value = titreTheme + 
                        (document.getElementById('sous_titre').value ? ' : ' + document.getElementById('sous_titre').value : '');
                    document.getElementById('contenu_rapport').value = fullContent;
                    document.getElementById('cover_data').value = JSON.stringify(getCoverData());
                    
                    // Submit form
                    rapportForm.querySelector('input[name="action"]').value = 'save_rapport';
                    rapportForm.submit();
                });
            }

            // Export PDF
            exportBtn.addEventListener('click', function() {
                const nomRapport = document.getElementById('nom_rapport').value.trim() || 'Rapport_Stage';
                const fullContent = generateCoverPageHTML() + 
                    '<div style="page-break-before: always;"></div>' +
                    '<div style="font-family: Times New Roman, serif; padding: 15mm 20mm;">' + joditEditor.value + '</div>';

                if (!joditEditor.value || joditEditor.value.trim() === '' || joditEditor.value === '<p><br></p>') {
                    showNotification('warning', 'Le corps du rapport est vide. Ajoutez du contenu avant d\'exporter.');
                }

                pdfLoading.classList.add('show');
                exportBtn.disabled = true;

                const formData = new FormData();
                formData.append('action', 'export_pdf');
                formData.append('contenu_rapport', fullContent);
                formData.append('nom_rapport', nomRapport);
                formData.append('theme_rapport', document.getElementById('titre_theme').value || 'Thème du rapport');

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(response => {
                    if (!response.ok) throw new Error(`Erreur HTTP: ${response.status}`);
                    const contentType = response.headers.get('content-type');
                    if (contentType && contentType.includes('application/pdf')) {
                        return response.blob();
                    } else {
                        return response.text().then(text => {
                            try {
                                const data = JSON.parse(text);
                                throw new Error(data.message || 'Erreur lors de la génération du PDF');
                            } catch (e) {
                                throw new Error('Erreur serveur: ' + text.substring(0, 200));
                            }
                        });
                    }
                })
                .then(blob => {
                    if (blob.size === 0) throw new Error('Le PDF généré est vide');
                    const url = window.URL.createObjectURL(blob);
                    const link = document.createElement('a');
                    link.href = url;
                    link.download = `${nomRapport.replace(/\s+/g, '_')}.pdf`;
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
                    pdfLoading.classList.remove('show');
                    exportBtn.disabled = false;
                });
            });

            // Déposer button
            if (deposerBtn) {
                deposerBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    if (isReadOnly) {
                        showNotification('warning', 'Ce rapport a déjà été déposé');
                        return;
                    }
                    
                    if (!confirm('Êtes-vous sûr de vouloir déposer ce rapport ? Cette action est irréversible.')) {
                        return;
                    }

                    const fullContent = generateCoverPageHTML() + 
                        '<div style="page-break-before: always;"></div>' +
                        '<div style="font-family: Times New Roman, serif; padding: 15mm 20mm;">' + joditEditor.value + '</div>';

                    document.getElementById('nom_rapport_hidden').value = document.getElementById('nom_rapport').value;
                    document.getElementById('theme_rapport_hidden').value = document.getElementById('titre_theme').value;
                    document.getElementById('contenu_rapport').value = fullContent;
                    rapportForm.querySelector('input[name="action"]').value = 'deposer_rapport';
                    rapportForm.submit();
                });
            }

            // Notification function
            function showNotification(type, message, title = null) {
                const container = document.getElementById('notificationContainer');
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;

                let iconPath = '';
                let displayTitle = title || type.charAt(0).toUpperCase() + type.slice(1);

                switch (type) {
                    case 'success': iconPath = 'M5 13l4 4L19 7'; break;
                    case 'error': iconPath = 'M6 18L18 6M6 6l12 12'; break;
                    case 'info': iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'; break;
                    case 'warning': iconPath = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z'; break;
                    default: iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
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
                `;

                notification.querySelector('.notification-close').addEventListener('click', () => hideNotification(notification));
                container.appendChild(notification);
                setTimeout(() => notification.classList.add('show'), 10);
                setTimeout(() => hideNotification(notification), 4000);

                function hideNotification(notif) {
                    notif.classList.remove('show');
                    notif.classList.add('hide');
                    setTimeout(() => { if (container.contains(notif)) container.removeChild(notif); }, 300);
                }
            }
        });
    </script>
</div>
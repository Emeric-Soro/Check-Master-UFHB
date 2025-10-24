<?php
require_once __DIR__ . '/../../../app/utils/permissions.php';

$templates = $GLOBALS['templates'] ?? [];
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Modèles de Documents</title>
    <style>
    /* Animations et transitions */
    .animate__animated {
        animation-duration: 0.3s;
    }

    .transition-all {
        transition-property: all;
        transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
        transition-duration: 200ms;
    }

    .form-input:focus {
        border-color: #22c55e;
        box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
        background-color: #f0fdf4;
    }

    .table-row:hover {
        background-color: #f0fdf4;
    }

    .btn-gradient-primary {
        background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
    }

    .btn-gradient-secondary {
        background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
    }

    .btn-gradient-warning {
        background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
    }

    .btn-gradient-danger {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .btn-hover {
        transition: all 0.3s ease;
    }

    .btn-hover:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    .info-box {
        background-color: #eff6ff;
        border-left: 4px solid #3b82f6;
        padding: 1rem;
        margin-bottom: 1.5rem;
        border-radius: 0.5rem;
    }
    </style>
</head>

<body class="bg-gray-50">

    <div class="container mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">
                <i class="fas fa-file-word text-blue-600 mr-2"></i>
                Gestion des Modèles de Documents
            </h1>
            <p class="text-gray-600">Téléversez et gérez les modèles Word (.docx) pour la génération de documents PDF</p>
        </div>

        <!-- Messages -->
        <?php if (!empty($messageSuccess)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-lg shadow-md animate__animated animate__fadeIn">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-xl mr-3"></i>
                    <p><?= htmlspecialchars($messageSuccess) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($messageErreur)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-lg shadow-md animate__animated animate__fadeIn">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle text-xl mr-3"></i>
                    <p><?= htmlspecialchars($messageErreur) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <!-- Information Box -->
        <div class="info-box mb-6">
            <h3 class="font-bold text-blue-800 mb-2">
                <i class="fas fa-info-circle mr-2"></i>
                Comment utiliser les modèles
            </h3>
            <ul class="list-disc list-inside text-sm text-blue-900 space-y-1">
                <li>Créez vos modèles dans Microsoft Word (.docx)</li>
                <li>Utilisez des placeholders comme <code class="bg-blue-200 px-1 rounded">${'{nom_etudiant}'}</code> pour les données dynamiques</li>
                <li>Pour les tableaux répétitifs, utilisez <code class="bg-blue-200 px-1 rounded">${'{critere}'}</code> dans les cellules</li>
                <li>Téléversez le modèle avec un nom unique (ex: pv_soutenance, releve_notes)</li>
            </ul>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Upload Form -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-upload text-green-600 mr-2"></i>
                        Téléverser un modèle
                    </h2>
                    
                    <form method="POST" enctype="multipart/form-data" class="space-y-4">
                        <div>
                            <label for="template_name" class="block text-sm font-medium text-gray-700 mb-2">
                                Nom du modèle *
                            </label>
                            <input 
                                type="text" 
                                id="template_name" 
                                name="template_name" 
                                required
                                placeholder="ex: pv_soutenance"
                                class="form-input w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                            >
                            <p class="text-xs text-gray-500 mt-1">Sans espaces, utilisez des underscores (_)</p>
                        </div>
                        
                        <div>
                            <label for="template_file" class="block text-sm font-medium text-gray-700 mb-2">
                                Fichier Word (.docx) *
                            </label>
                            <input 
                                type="file" 
                                id="template_file" 
                                name="template_file" 
                                accept=".docx"
                                required
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none"
                            >
                        </div>
                        
                        <button 
                            type="submit" 
                            name="btn_upload_template"
                            class="w-full btn-gradient-primary text-white px-6 py-3 rounded-lg font-semibold btn-hover"
                        >
                            <i class="fas fa-cloud-upload-alt mr-2"></i>
                            Téléverser
                        </button>
                    </form>
                    
                    <!-- Link to Placeholders Documentation -->
                    <div class="mt-6 pt-6 border-t border-gray-200">
                        <a 
                            href="?page=parametres_generaux&action=placeholders_documentation" 
                            class="text-blue-600 hover:text-blue-800 text-sm font-medium"
                        >
                            <i class="fas fa-book mr-2"></i>
                            Voir les placeholders disponibles
                        </a>
                    </div>
                </div>
            </div>

            <!-- Templates List -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">
                        <i class="fas fa-list text-blue-600 mr-2"></i>
                        Modèles disponibles (<?= count($templates) ?>)
                    </h2>
                    
                    <?php if (empty($templates)): ?>
                        <div class="text-center py-12 text-gray-500">
                            <i class="fas fa-folder-open text-6xl mb-4 opacity-50"></i>
                            <p class="text-lg">Aucun modèle disponible</p>
                            <p class="text-sm">Téléversez votre premier modèle pour commencer</p>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead>
                                    <tr class="bg-gray-50 border-b border-gray-200">
                                        <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">
                                            <i class="fas fa-file-word mr-2"></i>Nom du fichier
                                        </th>
                                        <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <?php foreach ($templates as $template): ?>
                                        <tr class="table-row">
                                            <td class="px-4 py-4">
                                                <div class="flex items-center">
                                                    <i class="fas fa-file-word text-blue-600 text-xl mr-3"></i>
                                                    <span class="font-medium text-gray-800"><?= htmlspecialchars($template) ?></span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-4">
                                                <div class="flex justify-center space-x-2">
                                                    <!-- Download Button -->
                                                    <a 
                                                        href="?page=parametres_generaux&action=modeles_documents&download_template=<?= urlencode($template) ?>" 
                                                        class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-2 rounded-lg text-sm font-medium btn-hover inline-flex items-center"
                                                        title="Télécharger"
                                                    >
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                    
                                                    <!-- Delete Button -->
                                                    <form method="POST" class="inline" onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer ce modèle ?');">
                                                        <input type="hidden" name="template_name" value="<?= htmlspecialchars($template) ?>">
                                                        <button 
                                                            type="submit" 
                                                            name="btn_delete_template"
                                                            class="bg-red-500 hover:bg-red-600 text-white px-3 py-2 rounded-lg text-sm font-medium btn-hover inline-flex items-center"
                                                            title="Supprimer"
                                                        >
                                                            <i class="fas fa-trash-alt"></i>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</body>
</html>

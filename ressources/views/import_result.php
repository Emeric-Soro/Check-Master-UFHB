<?php
$importSummary = $GLOBALS['importSummary'] ?? null;
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Résultat de l'Import</title>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-4xl">
        <div class="mb-6">
            <a href="?page=admin_historique" class="text-primary hover:text-primary-light mb-2 inline-block">
                <i class="fas fa-arrow-left mr-2"></i>Retour à l'historique
            </a>
            <h1 class="text-3xl font-bold text-gray-900">
                <i class="fas fa-file-import text-primary mr-3"></i>
                Résultat de l'Import
            </h1>
        </div>
        
        <!-- Summary -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-2xl font-semibold mb-4">Résumé</h2>
            <div class="grid grid-cols-2 gap-6">
                <div class="text-center p-4 bg-green-50 rounded-lg">
                    <div class="text-4xl font-bold text-green-600"><?php echo $importSummary['total_success'] ?? 0; ?></div>
                    <div class="text-gray-600 mt-2">Réussites</div>
                </div>
                <div class="text-center p-4 bg-red-50 rounded-lg">
                    <div class="text-4xl font-bold text-red-600"><?php echo $importSummary['total_errors'] ?? 0; ?></div>
                    <div class="text-gray-600 mt-2">Erreurs</div>
                </div>
            </div>
        </div>
        
        <!-- Success Messages -->
        <?php if (!empty($importSummary['successes'])): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-green-600">
                <i class="fas fa-check-circle mr-2"></i>
                Imports Réussis
            </h2>
            <div class="max-h-96 overflow-y-auto">
                <ul class="space-y-2">
                    <?php foreach ($importSummary['successes'] as $success): ?>
                    <li class="flex items-start">
                        <i class="fas fa-check text-green-500 mr-2 mt-1"></i>
                        <span class="text-gray-700"><?php echo htmlspecialchars($success); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Error Messages -->
        <?php if (!empty($importSummary['errors'])): ?>
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 text-red-600">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                Erreurs Rencontrées
            </h2>
            <div class="max-h-96 overflow-y-auto">
                <ul class="space-y-2">
                    <?php foreach ($importSummary['errors'] as $error): ?>
                    <li class="flex items-start">
                        <i class="fas fa-times text-red-500 mr-2 mt-1"></i>
                        <span class="text-gray-700"><?php echo htmlspecialchars($error); ?></span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Actions -->
        <div class="flex justify-center gap-4">
            <a href="?page=admin_historique" class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-light transition-colors">
                <i class="fas fa-list mr-2"></i>
                Voir l'historique
            </a>
            <a href="?page=admin_historique&action=import_form" class="bg-secondary text-white px-6 py-3 rounded-lg hover:bg-orange-600 transition-colors">
                <i class="fas fa-upload mr-2"></i>
                Nouvel import
            </a>
        </div>
    </div>
</body>
</html>

<?php
$importSummary = $GLOBALS['importSummary'] ?? ['total_success' => 0, 'total_errors' => 0, 'successes' => [], 'errors' => []];
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
$hasErrors = ($importSummary['total_errors'] ?? 0) > 0;
?>

<div class="bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8 font-sans">
    <div class="max-w-4xl mx-auto">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <?php if ($hasErrors): ?>
                <div class="inline-block bg-yellow-100 text-yellow-800 rounded-full px-4 py-2 mb-4">
                    <i class="fas fa-exclamation-triangle mr-2"></i>
                    Importation terminée avec des erreurs
                </div>
                <p class="text-gray-500">Certaines lignes n'ont pas pu être importées. Veuillez consulter les détails ci-dessous.</p>
            <?php else: ?>
                 <div class="inline-block bg-green-100 text-green-800 rounded-full px-4 py-2 mb-4">
                    <i class="fas fa-check-circle mr-2"></i>
                    Importation terminée avec succès
                </div>
                <p class="text-gray-500">Toutes les lignes ont été importées avec succès.</p>
            <?php endif; ?>
        </div>

        <!-- Summary -->
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 text-center">
                <div>
                    <div class="text-3xl font-bold text-blue-600"><?php echo count($importSummary['successes']) + count($importSummary['errors']); ?></div>
                    <div class="text-sm text-gray-500">Lignes Lues</div>
                </div>
                 <div>
                    <div class="text-3xl font-bold text-green-600"><?php echo $importSummary['total_success'] ?? 0; ?></div>
                    <div class="text-sm text-gray-500">Succès</div>
                </div>
                <div>
                    <div class="text-3xl font-bold text-red-600"><?php echo $importSummary['total_errors'] ?? 0; ?></div>
                    <div class="text-sm text-gray-500">Échecs</div>
                </div>
            </div>
            <div class="mt-4 bg-gray-50 rounded-lg p-3 text-sm text-gray-600">
                <p><strong>Fichier traité:</strong> <span><?php echo htmlspecialchars($importSummary['fileName'] ?? 'N/A'); ?></span></p>
                <p><strong>Date d'import:</strong> <span><?php echo date('d/m/Y H:i:s'); ?></span></p>
            </div>
        </div>

        <!-- Errors -->
        <?php if ($hasErrors): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6 mb-8">
            <div class="flex justify-between items-center mb-4 border-b pb-3">

                 <?php if (canView()): ?>
                 <button class="text-sm text-blue-600 hover:underline"><i class="fas fa-download mr-2"></i>Exporter les erreurs</button>
                 <?php endif; ?>
            </div>
           
            <div class="cm-table-wrapper max-h-60 pr-2">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50 sticky top-0">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ligne</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Erreur</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                         <?php foreach ($importSummary['errors'] as $index => $error): ?>
                        <tr>
                            <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500 font-mono"><?php echo htmlspecialchars($error['line'] ?? 'N/A'); ?></td>
                            <td class="px-4 py-3 text-sm text-red-700"><?php echo htmlspecialchars($error['message'] ?? 'Erreur inconnue'); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

         <!-- Successes -->
        <?php if (!empty($importSummary['successes'])): ?>
        <div class="bg-white rounded-2xl shadow-sm p-6">

            <div class="max-h-48 overflow-y-auto pr-2 text-sm text-gray-600 space-y-2">
                <?php foreach ($importSummary['successes'] as $success): ?>
                <div class="bg-green-50 p-2 rounded-md flex items-center">
                    <i class="fas fa-check-circle text-green-500 mr-3"></i>
                    <span><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
         <?php endif; ?>


        <!-- Actions -->
        <div class="mt-8 flex flex-wrap justify-center gap-4">
            <a href="?page=admin_historique" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-6 py-2.5 text-center">
                <i class="fas fa-list mr-2"></i>
                Voir les données importées
            </a>
            <button data-modal-target="import-modal" data-modal-toggle="import-modal" class="text-gray-900 bg-white border border-gray-300 hover:bg-gray-100 font-medium rounded-lg text-sm px-6 py-2.5 text-center">
                <i class="fas fa-upload mr-2"></i>
                Lancer un nouvel import
            </button>
        </div>
    </div>
</div>

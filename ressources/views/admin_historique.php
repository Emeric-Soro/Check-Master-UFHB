<?php
$currentTab = $GLOBALS['currentTab'] ?? 'students';
$students = $GLOBALS['students'] ?? [];
$juries = $GLOBALS['juries'] ?? [];
$academicYears = $GLOBALS['academicYears'] ?? [];
$filters = $GLOBALS['filters'] ?? [];
$totalPages = $GLOBALS['totalPages'] ?? 1;
$currentPage = $GLOBALS['currentPage'] ?? 1;
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Historique et Archivage</title>
    <style>
        .tab-button {
            padding: 0.75rem 1.5rem;
            border: none;
            background: transparent;
            cursor: pointer;
            font-weight: 500;
            color: #64748b;
            border-bottom: 3px solid transparent;
            transition: all 0.3s ease;
        }
        
        .tab-button.active {
            color: #0F4C75;
            border-bottom-color: #0F4C75;
        }
        
        .tab-button:hover {
            color: #0F4C75;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .filter-section {
            background: #f8fafc;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1.5rem;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .status-valider {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-rejeter {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .status-en_cours {
            background: #fef3c7;
            color: #92400e;
        }
        
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            max-width: 24rem;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            animation: slideIn 0.5s ease-out;
        }
        
        .notification.success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }
        
        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }
        
        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Notifications -->
    <?php if ($messageSuccess): ?>
        <div class="notification success">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <span><?php echo htmlspecialchars($messageSuccess); ?></span>
            </div>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.notification.success')?.remove();
            }, 5000);
        </script>
    <?php endif; ?>
    
    <?php if ($messageErreur): ?>
        <div class="notification error">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <span><?php echo htmlspecialchars($messageErreur); ?></span>
            </div>
        </div>
        <script>
            setTimeout(() => {
                document.querySelector('.notification.error')?.remove();
            }, 5000);
        </script>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">
                <i class="fas fa-archive text-primary mr-3"></i>
                Historique et Archivage
            </h1>
            <p class="text-gray-600">Consultation et gestion des archives des soutenances passées</p>
        </div>
        
        <!-- Import Section -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <h2 class="text-xl font-semibold mb-4 flex items-center">
                <i class="fas fa-upload mr-2 text-primary"></i>
                Importer des Archives
            </h2>
            <form action="?page=admin_historique&action=import" method="POST" enctype="multipart/form-data" class="flex items-center gap-4">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Fichier CSV (Format: ANNEE_ACAD, MATRICULE, NOM, PRENOMS, THEME, ...)
                    </label>
                    <input type="file" name="archive_file" accept=".csv,.xlsx,.xls" required
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none">
                </div>
                <button type="submit" class="px-6 py-2.5 bg-primary text-white rounded-lg hover:bg-primary-light transition-colors">
                    <i class="fas fa-cloud-upload-alt mr-2"></i>
                    Importer
                </button>
            </form>
            <p class="mt-2 text-sm text-gray-500">
                <i class="fas fa-info-circle mr-1"></i>
                Format attendu: CSV avec 20 colonnes (voir documentation pour détails)
            </p>
        </div>
        
        <!-- Tabs Navigation -->
        <div class="bg-white rounded-t-lg shadow-md">
            <div class="border-b border-gray-200">
                <div class="flex">
                    <button class="tab-button <?php echo $currentTab === 'students' ? 'active' : ''; ?>"
                            onclick="switchTab('students')">
                        <i class="fas fa-user-graduate mr-2"></i>
                        Historique des Étudiants
                    </button>
                    <button class="tab-button <?php echo $currentTab === 'jury' ? 'active' : ''; ?>"
                            onclick="switchTab('jury')">
                        <i class="fas fa-users mr-2"></i>
                        Historique des Jurys
                    </button>
                </div>
            </div>
            
            <!-- Tab Content: Students -->
            <div id="students-tab" class="tab-content <?php echo $currentTab === 'students' ? 'active' : ''; ?> p-6">
                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <input type="hidden" name="page" value="admin_historique">
                        <input type="hidden" name="tab" value="students">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Année Académique</label>
                            <select name="annee" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="">Toutes les années</option>
                                <?php foreach ($academicYears as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>"
                                            <?php echo ($filters['annee'] ?? '') === $year ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                            <select name="statut" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="">Tous les statuts</option>
                                <option value="valider" <?php echo ($filters['statut'] ?? '') === 'valider' ? 'selected' : ''; ?>>Validé</option>
                                <option value="rejeter" <?php echo ($filters['statut'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Rejeté</option>
                                <option value="en_cours" <?php echo ($filters['statut'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                            </select>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>"
                                   placeholder="Nom, prénom, matricule..."
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2">
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="w-full bg-primary text-white rounded-lg px-4 py-2 hover:bg-primary-light transition-colors">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Students Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matricule</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Prénoms</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thème</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entreprise</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>Aucun étudiant trouvé</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                    <tr class="hover:bg-gray-50 cursor-pointer"
                                        onclick="window.location.href='?page=admin_historique&action=view_student&num_etu=<?php echo $student['matricule']; ?>'">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($student['matricule']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($student['nom']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($student['prenoms']); ?>
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-900">
                                            <?php echo htmlspecialchars(substr($student['theme'] ?? '', 0, 50)) . (strlen($student['theme'] ?? '') > 50 ? '...' : ''); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($student['entreprise'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($student['annee_academique'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="status-badge status-<?php echo $student['statut'] ?? 'en_cours'; ?>">
                                                <?php 
                                                    $statutLabel = [
                                                        'valider' => 'Validé',
                                                        'rejeter' => 'Rejeté',
                                                        'en_cours' => 'En cours'
                                                    ];
                                                    echo $statutLabel[$student['statut'] ?? 'en_cours'] ?? 'N/A';
                                                ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <a href="?page=admin_historique&action=view_student&num_etu=<?php echo $student['matricule']; ?>"
                                               class="text-primary hover:text-primary-light">
                                                <i class="fas fa-eye mr-1"></i>Voir
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex justify-center">
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <a href="?page=admin_historique&tab=students&p=<?php echo $i; ?>&annee=<?php echo urlencode($filters['annee'] ?? ''); ?>&statut=<?php echo urlencode($filters['statut'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>"
                                   class="relative inline-flex items-center px-4 py-2 border <?php echo $i === $currentPage ? 'bg-primary text-white border-primary' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Tab Content: Jury -->
            <div id="jury-tab" class="tab-content <?php echo $currentTab === 'jury' ? 'active' : ''; ?> p-6">
                <!-- Filters -->
                <div class="filter-section">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <input type="hidden" name="page" value="admin_historique">
                        <input type="hidden" name="tab" value="jury">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Année Académique</label>
                            <select name="annee" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                                <option value="">Toutes les années</option>
                                <?php foreach ($academicYears as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>"
                                            <?php echo ($filters['annee'] ?? '') === $year ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="flex items-end col-span-2">
                            <button type="submit" class="w-full bg-primary text-white rounded-lg px-4 py-2 hover:bg-primary-light transition-colors">
                                <i class="fas fa-search mr-2"></i>Filtrer
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Jury Table -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Président</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Examinateur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Encadreur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Directeur</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php if (empty($juries)): ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-4 text-center text-gray-500">
                                        <i class="fas fa-inbox text-4xl mb-2"></i>
                                        <p>Aucun jury trouvé</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($juries as $jury): ?>
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($jury['date_soutenance'] ?? 'now'))); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($jury['etudiant'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($jury['president'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($jury['examinateur'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($jury['encadreur'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($jury['directeur'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            <?php echo htmlspecialchars($jury['annee_academique'] ?? 'N/A'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            // Update URL
            const url = new URL(window.location);
            url.searchParams.set('tab', tab);
            window.history.pushState({}, '', url);
            
            // Update tab buttons
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.closest('.tab-button').classList.add('active');
            
            // Update tab content
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            document.getElementById(tab + '-tab').classList.add('active');
        }
    </script>
</body>
</html>

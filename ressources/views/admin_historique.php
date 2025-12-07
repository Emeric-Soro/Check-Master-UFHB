<?php
$currentTab = $GLOBALS['currentTab'] ?? 'students';
$students = $GLOBALS['students'] ?? [];
$juries = $GLOBALS['juries'] ?? [];
$academicYears = $GLOBALS['academicYears'] ?? [];
$filters = $GLOBALS['filters'] ?? [];
$totalPages = $GLOBALS['totalPages'] ?? 1;
$currentPage = $GLOBALS['currentPage'] ?? 1;
$globalStats = $GLOBALS['globalStats'] ?? [];
$yearlyEvolution = $GLOBALS['yearlyEvolution'] ?? [];
$mentionsDistribution = $GLOBALS['mentionsDistribution'] ?? [];
$topEntreprises = $GLOBALS['topEntreprises'] ?? [];
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
?>

<div class="bg-gray-50 min-h-screen p-4 sm:p-6 lg:p-8">
    <div class="max-w-7xl mx-auto">

        <?php if ($messageSuccess): ?>
            <div id="success-notice" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded-lg mb-6 flex justify-between items-center">
                <span><?php echo htmlspecialchars($messageSuccess); ?></span>
                <button onclick="document.getElementById('success-notice').style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <?php if ($messageErreur): ?>
            <div id="error-notice" class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 flex justify-between items-center">
                <span><?php echo htmlspecialchars($messageErreur); ?></span>
                <button onclick="document.getElementById('error-notice').style.display='none'">&times;</button>
            </div>
        <?php endif; ?>

        <div class="bg-white shadow-sm rounded-2xl p-6 mb-8">
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-archive mr-3 text-blue-800"></i>
                Historique et Archivage
            </h1>
            <p class="text-gray-500 mt-1">Consultation et gestion des archives des soutenances passées.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
            <!-- Importer -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-transparent hover:border-blue-500 transition-all">
                <h2 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-upload mr-3 text-blue-500"></i>
                    Importer des Données
                </h2>
                <p class="text-gray-500 text-sm mt-2 mb-4">Importer un fichier Excel pour ajouter des archives.</p>
                <button data-modal-target="import-modal" data-modal-toggle="import-modal" class="text-white bg-blue-700 hover:bg-blue-800 font-medium rounded-lg text-sm px-5 py-2.5 text-center">
                    Lancer l'importation
                </button>
            </div>

            <!-- Exporter -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-transparent hover:border-gray-300 transition-all opacity-60">
                <h2 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-download mr-3 text-gray-400"></i>
                    Exporter les Archives
                </h2>
                <p class="text-gray-500 text-sm mt-2 mb-4">Télécharger les données au format Excel ou CSV.</p>
                <button class="text-white bg-gray-400 cursor-not-allowed font-medium rounded-lg text-sm px-5 py-2.5 text-center">Bientôt disponible</button>
            </div>

            <!-- Gérer les sauvegardes -->
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-transparent hover:border-gray-300 transition-all opacity-60">
                <h2 class="text-lg font-bold text-gray-800 flex items-center">
                    <i class="fas fa-database mr-3 text-gray-400"></i>
                    Gérer les Sauvegardes
                </h2>
                <p class="text-gray-500 text-sm mt-2 mb-4">Créer/Restaurer une sauvegarde de la base.</p>
                <button class="text-white bg-gray-400 cursor-not-allowed font-medium rounded-lg text-sm px-5 py-2.5 text-center">Bientôt disponible</button>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex gap-6 px-6" aria-label="Tabs">
                    <button onclick="switchTab('students')" class="tab-button <?php echo $currentTab === 'students' ? 'active' : ''; ?>">
                        <i class="fas fa-user-graduate mr-2"></i> Historique Étudiants
                    </button>
                    <button onclick="switchTab('jury')" class="tab-button <?php echo $currentTab === 'jury' ? 'active' : ''; ?>">
                        <i class="fas fa-users mr-2"></i> Historique Jurys
                    </button>
                     <button onclick="switchTab('stats')" class="tab-button <?php echo $currentTab === 'stats' ? 'active' : ''; ?>">
                        <i class="fas fa-chart-pie mr-2"></i> Statistiques Globales
                    </button>
                </nav>
            </div>

            <!-- Students Tab -->
            <div id="students-tab" class="tab-content <?php echo $currentTab === 'students' ? 'block' : 'hidden'; ?> p-6">
                <!-- Filters -->
                <div class="bg-gray-50 p-4 rounded-xl mb-6">
                    <form method="GET">
                        <input type="hidden" name="page" value="admin_historique">
                        <input type="hidden" name="tab" value="students">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                            <!-- Year Filter -->
                            <div>
                                <label for="annee_students" class="block text-sm font-medium text-gray-700">Année Académique</label>
                                <select name="annee" id="annee_students" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="">Toutes</option>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($filters['annee'] ?? '') === $year ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Status Filter -->
                            <div>
                                <label for="statut_students" class="block text-sm font-medium text-gray-700">Statut Soutenance</label>
                                <select name="statut" id="statut_students" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                     <option value="">Tous</option>
                                    <option value="valider" <?php echo ($filters['statut'] ?? '') === 'valider' ? 'selected' : ''; ?>>Admis</option>
                                    <option value="rejeter" <?php echo ($filters['statut'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Ajourné</option>
                                    <option value="en_cours" <?php echo ($filters['statut'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                                </select>
                            </div>
                            <!-- Search Filter -->
                            <div class="lg:col-span-2">
                                <label for="search_students" class="block text-sm font-medium text-gray-700">Recherche</label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <input type="text" name="search" id="search_students" class="focus:ring-blue-500 focus:border-blue-500 flex-1 block w-full rounded-none rounded-l-md sm:text-sm border-gray-300" placeholder="Nom, matricule, thème..." value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>">
                                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-l-0 border-gray-300 rounded-r-md bg-gray-50 text-gray-500 hover:bg-gray-100">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Students Table -->
                <div class="overflow-x-auto">
                    <div class="align-middle inline-block min-w-full">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">#</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Matricule</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thème</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Entreprise</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut</th>
                                    <th scope="col" class="relative px-6 py-3"></th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($students)): ?>
                                    <tr><td colspan="7" class="text-center py-12 text-gray-500">Aucun étudiant trouvé.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($students as $index => $student): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo ($currentPage - 1) * 20 + $index + 1; ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900"><?php echo htmlspecialchars($student['matricule']); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($student['nom'] . ' ' . $student['prenoms']); ?></td>
                                            <td class="px-6 py-4 max-w-xs truncate text-sm text-gray-500"><?php echo htmlspecialchars($student['theme'] ?? 'N/A'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($student['entreprise'] ?? 'N/A'); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $statusClass = 'bg-gray-100 text-gray-800';
                                                $statusText = 'En cours';
                                                if (($student['statut'] ?? 'en_cours') === 'valider') {
                                                    $statusClass = 'bg-green-100 text-green-800';
                                                    $statusText = 'Admis';
                                                } elseif (($student['statut'] ?? 'en_cours') === 'rejeter') {
                                                    $statusClass = 'bg-red-100 text-red-800';
                                                    $statusText = 'Ajourné';
                                                }
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                                    <?php echo $statusText; ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="?page=admin_historique&action=view_student&num_etu=<?php echo urlencode($student['matricule']); ?>" class="text-blue-600 hover:text-blue-900">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                 <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-between items-center">
                    <span class="text-sm text-gray-500">Page <?php echo $currentPage; ?> sur <?php echo $totalPages; ?></span>
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                         <a href="?page=admin_historique&tab=students&p=<?php echo max(1, $currentPage-1); ?>&<?php echo http_build_query($filters);?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Préc.</a>
                        <a href="?page=admin_historique&tab=students&p=<?php echo min($totalPages, $currentPage+1); ?>&<?php echo http_build_query($filters);?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">Suiv.</a>
                    </nav>
                </div>
                <?php endif; ?>
            </div>

            <!-- Jury Tab -->
            <div id="jury-tab" class="tab-content <?php echo $currentTab === 'jury' ? 'block' : 'hidden'; ?> p-6">
                <!-- Filters -->
                <div class="bg-gray-50 p-4 rounded-xl mb-6">
                    <form method="GET">
                        <input type="hidden" name="page" value="admin_historique">
                        <input type="hidden" name="tab" value="jury">
                        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                            <div>
                                <label for="annee_jury" class="block text-sm font-medium text-gray-700">Année Académique</label>
                                <select name="annee" id="annee_jury" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm rounded-md">
                                    <option value="">Toutes</option>
                                    <?php foreach ($academicYears as $year): ?>
                                        <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($filters['annee'] ?? '') === $year ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($year); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="enseignant_jury" class="block text-sm font-medium text-gray-700">Enseignant</label>
                                <input type="text" id="enseignant_jury" class="mt-1 focus:ring-blue-500 focus:border-blue-500 block w-full shadow-sm sm:text-sm border-gray-300 rounded-md" placeholder="Nom de l'enseignant...">
                            </div>
                            <div class="flex items-end">
                                <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-md inline-flex items-center justify-center">
                                    <i class="fas fa-search mr-2"></i>
                                    Rechercher
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Jury Table -->
                <div class="overflow-x-auto">
                    <div class="align-middle inline-block min-w-full">
                        <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Président</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Encadreur</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Examinateur</th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Directeur</th>
                                    <th scope="col" class="relative px-6 py-3"></th>
                                </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($juries)): ?>
                                    <tr><td colspan="8" class="text-center py-12 text-gray-500">Aucun jury trouvé pour les filtres sélectionnés.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($juries as $jury): ?>
                                        <tr class="hover:bg-gray-50">
                                            <?php
                                                $rawDate = $jury['date_soutenance'] ?? null;
                                                $hasValidDate = $rawDate && !str_starts_with($rawDate, '0000-00-00');
                                                $displayDate = $hasValidDate ? date('d/m/Y', strtotime($rawDate)) : 'N/A';
                                            ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800"><?php echo htmlspecialchars($displayDate); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                                <div><?php echo htmlspecialchars($jury['etudiant_nom'] ?? 'N/A'); ?></div>
                                                <div class="text-xs text-gray-500"><?php echo htmlspecialchars($jury['etudiant_matricule'] ?? 'N/A'); ?></div>
                                            </td>
                                            <?php
                                                $president = trim($jury['president'] ?? '') ?: 'N/A';
                                                $encadreur = trim($jury['encadreur'] ?? '') ?: 'N/A';
                                                $examinateur = trim($jury['examinateur'] ?? '') ?: 'N/A';
                                                $directeur = trim($jury['directeur'] ?? '') ?: 'N/A';
                                            ?>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($president); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($encadreur); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($examinateur); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500"><?php echo htmlspecialchars($directeur); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="#" class="text-blue-600 hover:text-blue-900"><i class="fas fa-eye"></i></a>
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

            <!-- Stats Tab -->
            <div id="stats-tab" class="tab-content <?php echo $currentTab === 'stats' ? 'block' : 'hidden'; ?> p-6 space-y-8">
                <!-- Overview Stats -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div class="bg-blue-50 p-6 rounded-2xl text-center">
                        <div class="text-4xl font-bold text-blue-800"><?php echo (int)($globalStats['total_students'] ?? 0); ?></div>
                        <div class="text-sm font-semibold text-blue-700 mt-1">Étudiants Total</div>
                    </div>
                    <div class="bg-green-50 p-6 rounded-2xl text-center">
                        <div class="text-4xl font-bold text-green-800"><?php echo (int)($globalStats['total_soutenances'] ?? 0); ?></div>
                        <div class="text-sm font-semibold text-green-700 mt-1">Soutenances Réalisées</div>
                    </div>
                    <div class="bg-indigo-50 p-6 rounded-2xl text-center">
                        <div class="text-4xl font-bold text-indigo-800"><?php echo (int)($globalStats['total_entreprises'] ?? 0); ?></div>
                        <div class="text-sm font-semibold text-indigo-700 mt-1">Entreprises Partenaires</div>
                    </div>
                     <div class="bg-orange-50 p-6 rounded-2xl text-center">
                        <div class="text-4xl font-bold text-orange-800"><?php echo (int)($globalStats['total_encadreurs'] ?? 0); ?></div>
                        <div class="text-sm font-semibold text-orange-700 mt-1">Enseignants Encadreurs</div>
                    </div>
                </div>

                <!-- Evolution Table -->
                <div>
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Évolution par Année Académique</h3>
                    <div class="shadow overflow-hidden border-b border-gray-200 sm:rounded-lg">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Année</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Inscrits</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Admis</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Taux (%)</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Moy. Note</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php if (empty($yearlyEvolution)): ?>
                                    <tr><td colspan="5" class="px-6 py-4 text-center text-gray-500">Aucune donnée disponible.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($yearlyEvolution as $index => $row): ?>
                                        <tr class="<?php echo $index % 2 === 1 ? 'bg-gray-50' : ''; ?>">
                                            <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($row['annee']); ?></td>
                                            <td class="px-6 py-4"><?php echo (int)$row['inscrits']; ?></td>
                                            <td class="px-6 py-4"><?php echo (int)$row['admis']; ?></td>
                                            <td class="px-6 py-4"><?php echo number_format($row['taux'], 1); ?>%</td>
                                            <td class="px-6 py-4"><?php echo $row['moyenne_note'] !== null ? number_format($row['moyenne_note'], 2) : '—'; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Charts -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                     <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Répartition des Mentions</h3>
                        <div class="bg-white p-6 rounded-2xl shadow-sm space-y-4">
                            <?php
                                $totalMentions = array_sum($mentionsDistribution);
                                $barColors = [
                                    'Tres bien' => 'bg-purple-600',
                                    'Bien' => 'bg-blue-600',
                                    'Assez bien' => 'bg-green-600',
                                    'Passable' => 'bg-yellow-500',
                                ];
                            ?>
                            <?php if ($totalMentions === 0): ?>
                                <p class="text-sm text-gray-500">Aucune évaluation disponible.</p>
                            <?php else: ?>
                                <?php foreach ($mentionsDistribution as $label => $count):
                                    $percent = $totalMentions > 0 ? round($count * 100 / $totalMentions, 1) : 0;
                                    $color = $barColors[$label] ?? 'bg-gray-400';
                                ?>
                                    <div class="space-y-1">
                                        <p class="text-sm font-medium text-gray-600"><?php echo htmlspecialchars($label); ?> (<?php echo $percent; ?>%)</p>
                                        <div class="w-full bg-gray-200 rounded-full h-2.5"><div class="<?php echo $color; ?> h-2.5 rounded-full" style="width: <?php echo $percent; ?>%"></div></div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                     <div>
                        <h3 class="text-lg font-bold text-gray-800 mb-4">Top 10 Entreprises</h3>
                        <div class="bg-white p-6 rounded-2xl shadow-sm">
                            <?php if (empty($topEntreprises)): ?>
                                <p class="text-sm text-gray-500">Aucune entreprise déclarée pour le moment.</p>
                            <?php else: ?>
                                <ul class="space-y-3">
                                    <?php foreach ($topEntreprises as $idx => $ent): ?>
                                        <li class="text-sm text-gray-600">
                                            <?php echo ($idx + 1) . '. ' . htmlspecialchars($ent['lib_entreprise']); ?>
                                            <span class="font-bold float-right"><?php echo (int)$ent['total']; ?></span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" tabindex="-1" aria-hidden="true" class="hidden overflow-y-auto overflow-x-hidden fixed top-0 right-0 left-0 z-50 justify-center items-center w-full md:inset-0 h-modal md:h-full">
    <div class="relative p-4 w-full max-w-2xl h-full md:h-auto">
        <!-- Modal content -->
        <div class="relative p-4 bg-white rounded-lg shadow sm:p-5">
            <!-- Modal header -->
            <div class="flex justify-between items-center pb-4 mb-4 rounded-t border-b sm:mb-5">
                <h3 class="text-lg font-semibold text-gray-900">Importer des archives</h3>
                <button type="button" class="text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm p-1.5 ml-auto inline-flex items-center" data-modal-toggle="import-modal">
                    <svg aria-hidden="true" class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path></svg>
                    <span class="sr-only">Fermer</span>
                </button>
            </div>
            <!-- Modal body -->
            <form action="?page=admin_historique&action=import" method="POST" enctype="multipart/form-data">
                <div class="space-y-4">
                    <div>
                        <a href="#" class="font-medium text-blue-600 hover:underline">Télécharger le modèle Excel</a>
                        <p class="text-sm text-gray-500">Le fichier doit contenir les colonnes: ANNEE_ACAD, MATRICULE, NOM, PRENOMS, THEME...</p>
                    </div>
                    <div>
                        <label for="archive_file" class="block mb-2 text-sm font-medium text-gray-900">Téléverser le fichier</label>
                        <input type="file" name="archive_file" id="archive_file" class="block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 cursor-pointer focus:outline-none" required>
                    </div>
                </div>
                <button type="submit" class="text-white inline-flex items-center bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center mt-6">
                    <i class="fas fa-upload mr-2"></i>
                    Confirmer l'importation
                </button>
            </form>
        </div>
    </div>
</div>


<style>
.tab-button {
    @apply border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm;
}
.tab-button.active {
    @apply border-blue-500 text-blue-600;
}
</style>

<script>
// Flowbite a besoin d'être initialisé pour les modales
document.addEventListener("DOMContentLoaded", () => {
    // Initialise les toggles de modale
    document.querySelectorAll('[data-modal-toggle]').forEach(function (toggle) {
        const modalId = toggle.getAttribute('data-modal-toggle');
        const modal = document.getElementById(modalId);
        if(modal) {
             const closeButtons = modal.querySelectorAll('[data-modal-toggle="'+modalId+'"]');
             toggle.addEventListener('click', function() {
                modal.classList.toggle('hidden');
                modal.classList.toggle('flex');
             });
             closeButtons.forEach(btn => {
                btn.addEventListener('click', function() {
                    modal.classList.toggle('hidden');
                    modal.classList.toggle('flex');
                });
             });
        }
    });
});

function switchTab(tab) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    url.searchParams.delete('p'); // reset pagination when switching tabs
    window.location.href = url.toString();
}
</script>


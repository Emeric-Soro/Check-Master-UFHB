<?php
// Récupérer les données du contrôleur
$rapportsVerifies = $GLOBALS['rapports_verifies'] ?? [];
$statistiques = $GLOBALS['statistiques'] ?? ['total' => 0, 'approuves' => 0, 'desapprouves' => 0];

// Filtres
$statutFilter = $_GET['statut'] ?? 'all';
$searchTerm = $_GET['search'] ?? '';

// Filtrer les rapports selon les critères
if ($statutFilter !== 'all') {
    $rapportsVerifies = array_filter($rapportsVerifies, function($rapport) use ($statutFilter) {
        return $rapport['statut_approbation'] === $statutFilter;
    });
}

if (!empty($searchTerm)) {
    $rapportsVerifies = array_filter($rapportsVerifies, function($rapport) use ($searchTerm) {
        return stripos($rapport['nom_etu'] . ' ' . $rapport['prenom_etu'], $searchTerm) !== false ||
               stripos($rapport['titre_rapport'], $searchTerm) !== false ||
               stripos($rapport['theme_rapport'], $searchTerm) !== false;
    });
}

// Pagination
$perPage = 15;
$totalRapports = count($rapportsVerifies);
$totalPages = ($totalRapports > 0) ? ceil($totalRapports / $perPage) : 1;
$p = isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ? (int)$_GET['p'] : 1;
if ($p > $totalPages) $p = $totalPages;
$startIndex = ($p - 1) * $perPage;
$rapportsPage = array_slice($rapportsVerifies, $startIndex, $perPage);
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des dossiers de candidature vérifiés</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
</head>

<body class="p-4 sm:p-6 md:p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:p-8 p-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Historique des Rapports Vérifiés <span
                class="text-4xl text-green-500 font-bold">MIAGE</span></h1>

        <!-- Statistiques -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-green-600"><?php echo $statistiques['total']; ?></div>
                <div class="text-sm text-green-700">Total vérifiés</div>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-blue-600"><?php echo $statistiques['approuves']; ?></div>
                <div class="text-sm text-blue-700">Approuvés</div>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
                <div class="text-2xl font-bold text-red-600"><?php echo $statistiques['desapprouves']; ?></div>
                <div class="text-sm text-red-700">Désapprouvés</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="get" class="mb-6 flex flex-wrap gap-4 items-center">
            <?php if (isset($_GET['page'])): ?>
            <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
            <?php endif; ?>
            <input type="text" name="search" placeholder="Rechercher par étudiant, titre ou thème..."
                class="outline-green-500 flex-1 min-w-[200px] p-3 pl-4 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm"
                value="<?= htmlspecialchars($searchTerm) ?>">
            <select name="statut"
                class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                <option value="all" <?php echo $statutFilter === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                <option value="approuve" <?php echo $statutFilter === 'approuve' ? 'selected' : ''; ?>>Approuvé</option>
                <option value="desapprouve" <?php echo $statutFilter === 'desapprouve' ? 'selected' : ''; ?>>Désapprouvé
                </option>
            </select>
            <button type="submit"
                class="p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Filtrer</button>
        </form>

        <!-- Tableau -->
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">
                            Étudiant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rapport</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Thème</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date d'envoi</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vérifié par</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut</th>
                        <th
                            class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-lg">
                            Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($rapportsPage)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 py-8">
                            Aucun rapport vérifié trouvé pour votre recherche.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($rapportsPage as $rapport): ?>
                    <tr class="table-row-hover">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 rounded-bl-lg">
                            <div class="flex items-center">
                                <i class="fa-solid fa-user text-green-500 mr-2"></i>
                                <?php echo htmlspecialchars($rapport['nom_etu'] . ' ' . $rapport['prenom_etu']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <div class="flex items-center">
                                <?php echo htmlspecialchars($rapport['titre_rapport']); ?>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo htmlspecialchars($rapport['theme_rapport']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo date('d/m/Y', strtotime($rapport['date_depot'])); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 hover:text-green-800">
                            <?php echo htmlspecialchars($rapport['nom_pers_admin'] . ' ' . $rapport['prenom_pers_admin']); ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $rapport['statut_approbation'] === 'approuve' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-700'; ?>">
                                <i
                                    class="<?php echo $rapport['statut_approbation'] === 'approuve' ? 'fa-solid fa-circle-check text-green-500' : 'fa-solid fa-circle-xmark text-red-400'; ?>"></i>
                                <?php echo $rapport['statut_approbation'] === 'approuve' ? 'Approuvé' : 'Désapprouvé'; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center text-sm rounded-br-lg">
                            <div class="flex items-center gap-2 justify-center">

                                <a href="?page=gestion_dossiers_candidatures&action=telecharger_pdf&id_rapport=<?php echo $rapport['id_rapport']; ?>"
                                    title="Télécharger le rapport en PDF"
                                    class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-lg text-sm flex items-center gap-1 shadow transition-colors">
                                    <i class="fa-solid fa-file-pdf"></i>
                                    PDF
                                </a>
                                <a href="?page=gestion_dossiers_candidatures&action=consulter_rapport&id_rapport=<?php echo $rapport['id_rapport']; ?>"
                                    target="_blank" title="Consulter le rapport"
                                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-lg text-sm flex items-center gap-1 shadow transition-colors">
                                    <i class="fa-solid fa-file-text"></i>
                                    Consulter
                                </a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-4 gap-2">
            <div class="text-gray-600 text-sm">
                <?php if ($totalRapports > 0): ?>
                Page <?= $p ?> sur <?= $totalPages ?> —
                Affichage de <span class="font-semibold"><?= $startIndex + 1 ?></span>
                à <span class="font-semibold"><?= min($startIndex + $perPage, $totalRapports) ?></span>
                sur <span class="font-semibold"><?= $totalRapports ?></span> rapports vérifiés
                <?php else: ?>
                Aucun rapport à afficher
                <?php endif; ?>
            </div>
            <?php if ($totalPages > 1) : ?>
            <div class="flex justify-center mt-2 md:mt-0">
                <nav class="inline-flex -space-x-px">
                    <?php
                    function buildPageUrl($p) {
                        $params = $_GET;
                        $params['p'] = $p;
                        if (isset($_GET['page'])) {
                            $params['page'] = $_GET['page'];
                        }
                        return '?' . http_build_query($params);
                    }
                    ?>
                    <a href="<?= $p > 1 ? buildPageUrl($p-1) : '#' ?>"
                        class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium <?= $p == 1 ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50' ?> rounded-l-md">&laquo;</a>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                    <a href="<?= buildPageUrl($i) ?>"
                        class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium <?= $i == $p ? 'bg-green-100 text-green-700 font-bold' : 'text-gray-700 hover:bg-gray-50' ?>"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="<?= $p < $totalPages ? buildPageUrl($p+1) : '#' ?>"
                        class="px-3 py-2 border border-gray-300 bg-white text-sm font-medium <?= $p == $totalPages ? 'text-gray-300 cursor-not-allowed' : 'text-gray-700 hover:bg-gray-50' ?> rounded-r-md">&raquo;</a>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modale résumé de candidature -->
    <div id="resumeModal" class="fixed inset-0 bg-black/40 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-2xl shadow-2xl p-8 w-full max-w-2xl relative border border-green-100">
            <button onclick="closeResumeModal()"
                class="absolute top-4 right-4 text-green-400 hover:text-green-700 text-xl transition-colors">
                <i class="fa-solid fa-xmark"></i>
            </button>
            <h3 class="text-xl font-bold text-green-700 mb-4 flex items-center gap-2">
                <i class="fa-solid fa-user-graduate text-green-600"></i> Détails du rapport
            </h3>
            <div id="modalContent" class="text-green-900 text-base">
                <!-- Contenu dynamique du résumé à insérer ici -->
            </div>
        </div>
    </div>

    <style>
    .table-row-hover:hover {
        background-color: #f0fdf4;
    }
    </style>

    <script>
    function openResumeModal(idRapport) {
        // Récupérer les détails du rapport via AJAX
        fetch(`?page=gestion_dossiers_candidatures&action=get_details_rapport&id_rapport=${idRapport}`)
            .then(response => response.json())
            .then(data => {
                const modalContent = document.getElementById('modalContent');
                modalContent.innerHTML = `
                        <p class="mb-2"><span class="font-semibold">Étudiant :</span> ${data.nom_etu} ${data.prenom_etu}</p>
                        <p class="mb-2"><span class="font-semibold">Numéro étudiant :</span> ${data.num_etu}</p>
                        <p class="mb-2"><span class="font-semibold">Rapport :</span> ${data.nom_rapport}</p>
                        <p class="mb-2"><span class="font-semibold">Thème :</span> ${data.theme_rapport}</p>
                        <p class="mb-2"><span class="font-semibold">Date de dépôt :</span> ${new Date(data.date_rapport).toLocaleDateString('fr-FR')}</p>
                        <p class="mb-2"><span class="font-semibold">Statut :</span> 
                            <span class="inline-block px-3 py-1 rounded-full text-xs font-bold ${data.statut_approbation === 'approuve' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">
                                ${data.statut_approbation === 'approuve' ? 'Approuvé' : 'Désapprouvé'}
                            </span>
                        </p>
                        <p class="mb-2"><span class="font-semibold">Vérifié par :</span> ${data.nom_pers_admin} ${data.prenom_pers_admin}</p>
                        <p class="mb-2"><span class="font-semibold">Date de vérification :</span> ${new Date(data.date_approbation).toLocaleDateString('fr-FR')}</p>
                        ${data.commentaire ? `<p class="mb-2"><span class="font-semibold">Commentaire :</span> <em>"${data.commentaire}"</em></p>` : ''}
                    `;
                document.getElementById('resumeModal').classList.remove('hidden');
            })
            .catch(error => {
                console.error('Erreur lors de la récupération des détails:', error);
                alert('Erreur lors de la récupération des détails du rapport');
            });
    }

    function closeResumeModal() {
        document.getElementById('resumeModal').classList.add('hidden');
    }

    // Fermer la modale en cliquant à l'extérieur
    document.getElementById('resumeModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeResumeModal();
        }
    });
    </script>
</body>

</html>
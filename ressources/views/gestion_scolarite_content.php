<?php
// Initialisation des variables avec des valeurs par défaut
$etudiantsInscrits = isset($GLOBALS['etudiantsInscrits']) ? $GLOBALS['etudiantsInscrits'] : [];
$listeAllEtudiant = $GLOBALS['listeAllEtudiant'];
$allVersement = $GLOBALS['listeVersement'];

// Configuration de la pagination
$items_par_page = 10; // Nombre d'éléments par page
$page_actuelle = isset($_GET['page_versements']) ? (int) $_GET['page_versements'] : 1;
$total_items = count($allVersement);
$total_pages = ceil($total_items / $items_par_page);
$page_actuelle = max(1, min($page_actuelle, $total_pages)); // S'assurer que la page est valide

// Calculer l'index de début et de fin pour la pagination
$debut = ($page_actuelle - 1) * $items_par_page;
$versements_pages = array_slice($allVersement, $debut, $items_par_page);

// Calcul des statistiques
$totalEtudiants = count($etudiantsInscrits);
$complete = 0;
$partial = 0;

foreach ($etudiantsInscrits as $etudiant) {
    $reste_a_payer = isset($etudiant['reste_a_payer']) ? floatval($etudiant['reste_a_payer']) : 0;

    if ($reste_a_payer <= 0) {
        $complete++;
    } else {
        $partial++;
    }
}

$pourcentageComplete = $totalEtudiants > 0 ? round(($complete / $totalEtudiants) * 100) : 0;
$pourcentagePartial = $totalEtudiants > 0 ? round(($partial / $totalEtudiants) * 100) : 0;
$pourcentagePending = count($listeAllEtudiant) > 0 ? round(($totalEtudiants / count($listeAllEtudiant)) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion Scolarité | Scolarité</title>

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Animation pour les suggestions */
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .suggestion-item {
            transition: all 0.2s ease;
        }

        .suggestion-item:hover {
            background-color: rgba(59, 130, 246, 0.1);
            transform: translateX(4px);
        }

        .hover-scale {
            transition: transform 0.3s ease;
        }

        .hover-scale:hover {
            transform: scale(1.03);
        }

        .sidebar-item.active {
            background-color: #e6f7ff;
            border-left: 4px solid #3b82f6;
            color: #3b82f6;
        }

        .sidebar-item.active i {
            color: #3b82f6;
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        /* Styles de tabs supprimés car les sections sont maintenant affichées simultanément */
    </style>
</head>

<body class="font-sans antialiased" style="background-color: #DFF2FF;">
    <!-- Système de notification -->
    <?php if (isset($GLOBALS['messageSuccess']) && !empty($GLOBALS['messageSuccess'])): ?>
        <div id="successNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 rounded shadow-lg flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto pl-3">
                    <i class="fas fa-times text-green-500 hover:text-green-700"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>

    <?php if (isset($GLOBALS['messageErreur']) && !empty($GLOBALS['messageErreur'])): ?>
        <div id="errorNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded shadow-lg flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?= htmlspecialchars($GLOBALS['messageErreur']) ?></p>
                </div>
                <button onclick="this.parentElement.parentElement.remove()" class="ml-auto pl-3">
                    <i class="fas fa-times text-red-500 hover:text-red-700"></i>
                </button>
            </div>
        </div>
    <?php endif; ?>
    <div class="flex h-screen overflow-hidden">
        <!-- Main content area -->
        <div class="flex-1 p-4 md:p-6 overflow-y-auto">
            <div class="max-w-7xl mx-auto">
                <!-- Header -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <?php if (isset($GLOBALS['anneeAcademiqueActive'])):
                        $annee = $GLOBALS['anneeAcademiqueActive'];
                        $anneeDebut = date('Y', strtotime($annee->date_deb));
                        $anneeFin = date('Y', strtotime($annee->date_fin));
                        ?>
                        <div class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow-md">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-calendar-alt"></i>
                                <div>
                                    <p class="text-xs font-medium opacity-90">Année académique</p>
                                    <p class="text-lg font-bold"><?php echo $anneeDebut . '-' . $anneeFin; ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Payment status cards -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                    <div class="bg-white p-4 rounded-lg shadow-sm hover-scale">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Paiements complets</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $complete; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $pourcentageComplete; ?>% des étudiants</p>
                            </div>
                            <div class="p-3 rounded-full bg-green-100 text-green-500">
                                <i class="fas fa-check-circle text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm hover-scale">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Paiements partiels</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $partial; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $pourcentagePartial; ?>% des étudiants</p>
                            </div>
                            <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                                <i class="fas fa-exclamation-circle text-xl"></i>
                            </div>
                        </div>
                    </div>

                    <div class="bg-white p-4 rounded-lg shadow-sm hover-scale">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm font-medium text-gray-500">Etudiants inscrits</p>
                                <p class="text-2xl font-bold text-gray-800"><?php echo $totalEtudiants; ?></p>
                                <p class="text-xs text-gray-500"><?php echo $pourcentagePending; ?>% des étudiants</p>
                            </div>
                            <div class="p-3 rounded-full bg-red-100 text-red-500">
                                <i class="fas fa-times-circle text-xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Forms with tabs -->
                <?php if (canCreate() || canEdit()): ?>
                    <!-- Formulaire unifié Inscription/Versement -->
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden border-2 border-gray-100 mb-6">
                        <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                            <h2 class="text-xl font-bold text-white flex items-center">
                                <i class="fas fa-money-bill-wave mr-3"></i>
                                Gestion des paiements de scolarité
                            </h2>
                        </div>

                        <div class="px-6 py-5 bg-white">
                            <div class="bg-white">
                                <div class="mb-4 p-3 bg-blue-50 border-l-4 border-blue-500 rounded">
                                    <p class="text-gray-700 text-sm">
                                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                                        <span class="font-semibold">Mode intelligent :</span> Sélectionnez un étudiant. Le
                                        formulaire s'adaptera automatiquement selon qu'il est déjà inscrit ou non.
                                    </p>
                                </div>

                                <form id="unifiedPaymentForm" method="POST"
                                    action="?page=gestion_scolarite&action=enregistrer_paiement">
                                    <input type="hidden" name="csrf_token"
                                        value="<?php echo htmlspecialchars(\CheckMaster\Core\Csrf::token()); ?>">
                                    <input type="hidden" id="isNewInscription" name="is_new_inscription" value="">

                                    <!-- Section: Sélection de l'étudiant -->
                                    <div class="mb-6">
                                        <h3
                                            class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-gray-200 flex items-center">
                                            <i class="fas fa-user-check text-blue-500 mr-2"></i>
                                            Sélection de l'étudiant
                                        </h3>
                                        <div class="grid grid-cols-1 gap-4">
                                            <div>
                                                <label for="unifiedEtudiantSearch"
                                                    class="block text-xs font-medium text-gray-700 mb-1.5">
                                                    <i class="fas fa-user text-blue-500 mr-1"></i>Rechercher un étudiant
                                                    <span class="text-red-500">*</span>
                                                </label>
                                                <div class="relative">
                                                    <div
                                                        class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                                        <i class="fas fa-search text-gray-400"></i>
                                                    </div>
                                                    <input type="text" id="unifiedEtudiantSearch" autocomplete="off"
                                                        placeholder="Tapez le nom, prénom ou numéro de l'étudiant..."
                                                        class="block w-full pl-10 pr-3 py-2 text-sm border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200">
                                                    <input type="hidden" id="unifiedEtudiantSelect" name="etudiant"
                                                        required>

                                                    <!-- Liste de suggestions -->
                                                    <div id="etudiantSuggestions"
                                                        style="display: none; position: absolute; z-index: 9999; width: 100%; margin-top: 4px; background-color: white; border: 2px solid #d1d5db; border-radius: 8px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1); max-height: 24rem; overflow-y: auto;">
                                                    </div>
                                                </div>
                                                <p class="text-xs text-gray-500 mt-1">
                                                    <i class="fas fa-search text-blue-500 mr-1"></i>
                                                    <span id="searchHint">Tapez pour rechercher parmi les étudiants inscrits
                                                        et non inscrits</span>
                                                </p>

                                                <!-- Script de test -->
                                                <script>
                                                    // Fonction pour formater les nombres (définie tôt)
                                                    function formatNumber(num) {
                                                        return parseFloat(num).toFixed(0).replace(/\B(?=(\d{3})+(?!\d))/g, " ");
                                                    }

                                                    // Fonction pour mettre à jour le hint de recherche
                                                    function updateSearchHint(count, query) {
                                                        const hint = document.getElementById('searchHint');
                                                        if (!hint) return;

                                                        if (query && count > 0) {
                                                            hint.innerHTML = `<span class="text-green-600 font-semibold">${count} étudiant(s) trouvé(s)</span>`;
                                                        } else if (query && count === 0) {
                                                            hint.innerHTML = `<span class="text-red-600">Aucun résultat</span>`;
                                                        } else {
                                                            hint.innerHTML = 'Tapez pour rechercher parmi les étudiants inscrits et non inscrits';
                                                        }
                                                    }

                                                    // Fonction displaySuggestions (définie avant test)
                                                    function displaySuggestions(etudiants) {
                                                        const suggestionsDiv = document.getElementById('etudiantSuggestions');
                                                        if (!suggestionsDiv) return;

                                                        if (etudiants.length === 0) {
                                                            suggestionsDiv.innerHTML = '<div class="px-4 py-3 text-sm text-gray-500 text-center"><i class="fas fa-search mr-2"></i>Aucun étudiant trouvé</div>';
                                                            suggestionsDiv.style.display = 'block';
                                                            return;
                                                        }

                                                        let html = '';
                                                        let currentCategorie = '';

                                                        etudiants.forEach((etudiant, index) => {
                                                            if (etudiant.categorie !== currentCategorie) {
                                                                currentCategorie = etudiant.categorie;
                                                                const icon = etudiant.inscrit ? '✅' : '📝';
                                                                html += `<div class="px-3 py-2 text-xs font-semibold text-gray-600 bg-gray-100 sticky top-0">${icon} ${currentCategorie}</div>`;
                                                            }

                                                            const bgClass = etudiant.inscrit ? 'hover:bg-blue-50' : 'hover:bg-green-50';
                                                            html += `<div class="suggestion-item px-4 py-2.5 cursor-pointer border-b border-gray-100 ${bgClass} transition-colors" data-index="${index}">`;
                                                            html += `<div class="flex items-center justify-between">`;
                                                            html += `<div class="flex-1">`;
                                                            html += `<div class="text-sm font-medium text-gray-900">${etudiant.label}</div>`;
                                                            html += `<div class="text-xs text-gray-500 mt-0.5"><i class="fas fa-id-card mr-1"></i>${etudiant.numero}</div>`;
                                                            html += `</div>`;

                                                            if (etudiant.inscrit && etudiant.resteAPayer !== undefined) {
                                                                const resteFormat = formatNumber(etudiant.resteAPayer);
                                                                const color = etudiant.resteAPayer > 0 ? 'text-orange-600' : 'text-green-600';
                                                                html += `<div class="text-xs ${color} font-semibold ml-2">Reste: ${resteFormat} FCFA</div>`;
                                                            }

                                                            html += `</div></div>`;
                                                        });

                                                        suggestionsDiv.innerHTML = html;
                                                        suggestionsDiv.style.display = 'block';
                                                        suggestionsDiv.style.animation = 'slideDown 0.3s ease-out';
                                                        console.log('Suggestions affichées:', etudiants.length);

                                                        // Attacher les événements de clic
                                                        suggestionsDiv.querySelectorAll('.suggestion-item').forEach((item, index) => {
                                                            item.addEventListener('click', function () {
                                                                // Fonction selectEtudiant sera définie plus bas dans le script principal
                                                                if (typeof selectEtudiant === 'function') {
                                                                    selectEtudiant(etudiants[index]);
                                                                } else {
                                                                    console.error('selectEtudiant non définie!');
                                                                }
                                                            });
                                                        });
                                                    }
                                                </script>

                                                <!-- Script PHP pour générer les données JSON -->
                                                <script>
                                                    <?php
                                                    // Récupérer les données des étudiants
                                                    $etudiantsNonInscrits = isset($GLOBALS['etudiantsNonInscrits']) ? $GLOBALS['etudiantsNonInscrits'] : [];
                                                    $etudiantsInscrits = isset($GLOBALS['etudiantsInscrits']) ? $GLOBALS['etudiantsInscrits'] : [];

                                                    error_log("=== DEBUG GESTION SCOLARITE ===");
                                                    error_log("etudiantsNonInscrits: " . count($etudiantsNonInscrits));
                                                    error_log("etudiantsInscrits: " . count($etudiantsInscrits));

                                                    $allEtudiants = [];

                                                    // Étudiants NON inscrits
                                                    if (!empty($etudiantsNonInscrits)) {
                                                        foreach ($etudiantsNonInscrits as $etudiant) {
                                                            $allEtudiants[] = [
                                                                'value' => $etudiant['num_etu'],
                                                                'label' => $etudiant['nom_etu'] . ' ' . $etudiant['prenom_etu'],
                                                                'numero' => $etudiant['num_etu'],
                                                                'inscrit' => false,
                                                                'categorie' => 'Non inscrit'
                                                            ];
                                                        }
                                                    }

                                                    // Étudiants INSCRITS
                                                    if (!empty($etudiantsInscrits)) {
                                                        foreach ($etudiantsInscrits as $etudiant) {
                                                            $reste = isset($etudiant['reste_a_payer']) ? floatval($etudiant['reste_a_payer']) : 0;
                                                            $montantPaye = isset($etudiant['montant_paye']) ? floatval($etudiant['montant_paye']) : 0;
                                                            $montantTotal = isset($etudiant['montant_scolarite']) ? floatval($etudiant['montant_scolarite']) : 0;

                                                            $allEtudiants[] = [
                                                                'value' => $etudiant['id_etudiant'],
                                                                'label' => $etudiant['nom'] . ' ' . $etudiant['prenom'],
                                                                'numero' => $etudiant['id_etudiant'],
                                                                'inscrit' => true,
                                                                'idEtudiant' => $etudiant['id_etudiant'],
                                                                'idNiveau' => $etudiant['id_niveau'],
                                                                'nomNiveau' => $etudiant['nom_niveau'],
                                                                'montantTotal' => $montantTotal,
                                                                'montantPaye' => $montantPaye,
                                                                'resteAPayer' => $reste,
                                                                'categorie' => 'Inscrit - ' . $etudiant['nom_niveau']
                                                            ];
                                                        }
                                                    }
                                                    ?>

                                                    const etudiantsData = <?php echo json_encode($allEtudiants, JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
                                                    console.log('✅ Étudiants chargés:', etudiantsData.length);
                                                    console.log('Données:', etudiantsData);
                                                </script>
                                            </div>
                                        </div>

                                        <!-- Section: Nouvelle inscription (visible uniquement si étudiant non inscrit) -->
                                        <div id="nouvelleInscriptionSection" class="mb-6" style="display: none;">
                                            <h3
                                                class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-green-200 flex items-center">
                                                <i class="fas fa-user-plus text-green-500 mr-2"></i>
                                                Informations d'inscription <span
                                                    class="ml-2 px-2 py-0.5 bg-green-100 text-green-700 text-xs rounded-full">Nouvelle
                                                    inscription</span>
                                            </h3>
                                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                                <div>
                                                    <label for="unifiedNiveau"
                                                        class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-graduation-cap text-blue-500 mr-1"></i>Niveau
                                                        d'étude
                                                        <span class="text-red-500">*</span>
                                                    </label>
                                                    <select id="unifiedNiveau" name="id_niveau"
                                                        onchange="updateMontantScolariteUnified()"
                                                        class="block w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                                                        <option value="">-- Sélectionner un niveau --</option>
                                                        <?php
                                                        $niveaux = isset($GLOBALS['niveaux']) ? $GLOBALS['niveaux'] : [];
                                                        foreach ($niveaux as $niveau): ?>
                                                            <option value="<?php echo $niveau['id_niv_etude']; ?>"
                                                                data-montant="<?php echo $niveau['montant_scolarite']; ?>">
                                                                <?php echo htmlspecialchars($niveau['lib_niv_etude']); ?> -
                                                                <?php echo number_format($niveau['montant_scolarite'], 0, ',', ' '); ?>
                                                                FCFA
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="unifiedAnnee"
                                                        class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-calendar-alt text-blue-500 mr-1"></i>Année
                                                        académique
                                                        <span class="text-red-500">*</span>
                                                    </label>
                                                    <select id="unifiedAnnee" name="id_annee_acad"
                                                        class="block w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                                                        <option value="">-- Sélectionner --</option>
                                                        <?php
                                                        $listeAnnees = isset($GLOBALS['listeAnnees']) ? $GLOBALS['listeAnnees'] : [];
                                                        foreach ($listeAnnees as $annee):
                                                            $anneeLabel = date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin));
                                                            ?>
                                                            <option value="<?php echo $annee->id_annee_acad; ?>">
                                                                <?php echo $anneeLabel; ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-calculator text-blue-500 mr-1"></i>Montant total
                                                        scolarité
                                                    </label>
                                                    <div class="relative">
                                                        <input type="text" id="unifiedMontantTotal" readonly
                                                            class="block w-full px-3 py-2 pr-16 text-sm border-2 border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-semibold"
                                                            placeholder="0">
                                                        <span
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-gray-500 font-medium">FCFA</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Section: Situation actuelle (visible uniquement si étudiant déjà inscrit) -->
                                        <div id="situationActuelleSection" class="mb-6" style="display: none;">
                                            <h3
                                                class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-blue-200 flex items-center">
                                                <i class="fas fa-chart-line text-blue-500 mr-2"></i>
                                                Situation actuelle <span
                                                    class="ml-2 px-2 py-0.5 bg-blue-100 text-blue-700 text-xs rounded-full">Étudiant
                                                    inscrit</span>
                                            </h3>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-graduation-cap text-blue-500 mr-1"></i>Niveau
                                                    </label>
                                                    <input type="text" id="unifiedNiveauInfo" readonly
                                                        class="block w-full px-3 py-2 text-sm border-2 border-gray-200 rounded-lg bg-gray-50 text-gray-600">
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-receipt text-blue-500 mr-1"></i>Montant total
                                                    </label>
                                                    <div class="relative">
                                                        <input type="text" id="unifiedMontantTotalInfo" readonly
                                                            class="block w-full px-3 py-2 pr-16 text-sm border-2 border-gray-200 rounded-lg bg-gray-50 text-gray-700 font-semibold">
                                                        <span
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-gray-500 font-medium">FCFA</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-check-circle text-green-500 mr-1"></i>Déjà payé
                                                    </label>
                                                    <div class="relative">
                                                        <input type="text" id="unifiedMontantPaye" readonly
                                                            class="block w-full px-3 py-2 pr-16 text-sm border-2 border-gray-200 rounded-lg bg-green-50 text-green-700 font-semibold">
                                                        <span
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-green-600 font-medium">FCFA</span>
                                                    </div>
                                                </div>

                                                <div>
                                                    <label class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-exclamation-circle text-orange-500 mr-1"></i>Reste
                                                        à
                                                        payer
                                                    </label>
                                                    <div class="relative">
                                                        <input type="text" id="unifiedResteAPayer" readonly
                                                            class="block w-full px-3 py-2 pr-16 text-sm border-2 border-gray-200 rounded-lg bg-orange-50 text-orange-700 font-semibold">
                                                        <span
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-orange-600 font-medium">FCFA</span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Section: Détails du versement (toujours visible) -->
                                        <div class="mb-6">
                                            <h3
                                                class="text-sm font-semibold text-gray-700 mb-3 pb-2 border-b border-purple-200 flex items-center">
                                                <i class="fas fa-money-bill-wave text-purple-500 mr-2"></i>
                                                Détails du versement
                                            </h3>
                                            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                <div>
                                                    <label for="unifiedMontantVersement"
                                                        class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-coins text-yellow-500 mr-1"></i>Montant du
                                                        versement
                                                        <span class="text-red-500">*</span>
                                                    </label>
                                                    <div class="relative">
                                                        <input type="number" id="unifiedMontantVersement"
                                                            name="montant_versement" required min="0" step="0.01"
                                                            class="block w-full px-3 py-2 pr-16 text-sm border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                                            placeholder="Entrez le montant">
                                                        <span
                                                            class="absolute inset-y-0 right-0 flex items-center pr-3 text-xs text-gray-500 font-medium">FCFA</span>
                                                    </div>
                                                    <p class="text-xs text-gray-500 mt-1" id="montantHelp"></p>
                                                </div>

                                                <div>
                                                    <label for="unifiedMethodePaiement"
                                                        class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-credit-card text-indigo-500 mr-1"></i>Méthode de
                                                        paiement <span class="text-red-500">*</span>
                                                    </label>
                                                    <select id="unifiedMethodePaiement" name="methode_paiement" required
                                                        class="block w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200">
                                                        <option value="">-- Sélectionner --</option>
                                                        <option value="Espèce">💵 Espèce</option>
                                                        <option value="Carte bancaire">💳 Carte bancaire</option>
                                                        <option value="Virement">🏦 Virement</option>
                                                        <option value="Chèque">📄 Chèque</option>
                                                    </select>
                                                </div>

                                                <div>
                                                    <label for="unifiedDateVersement"
                                                        class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-calendar-alt text-teal-500 mr-1"></i>Date du
                                                        versement
                                                        <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="date" id="unifiedDateVersement" name="date_versement"
                                                        required value="<?php echo date('Y-m-d'); ?>"
                                                        class="block w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200">
                                                </div>

                                                <div>
                                                    <label for="unifiedNumPiece"
                                                        class="block text-xs font-medium text-gray-700 mb-1.5">
                                                        <i class="fas fa-receipt text-purple-500 mr-1"></i>N° de pièce
                                                    </label>
                                                    <input type="text" id="unifiedNumPiece" name="num_piece"
                                                        class="block w-full px-3 py-2 text-sm border-2 border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all duration-200"
                                                        placeholder="N° chèque, reçu, etc.">
                                                    <p class="text-xs text-gray-500 mt-1">Optionnel</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="flex items-center justify-between pt-4 border-t border-gray-200">
                                            <button type="button" onclick="resetUnifiedForm()"
                                                class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 bg-white border-2 border-gray-300 rounded-lg hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 transition-all duration-200">
                                                <i class="fas fa-redo mr-2"></i>
                                                Réinitialiser
                                            </button>
                                            <button type="submit" id="unifiedSubmitButton"
                                                class="inline-flex items-center px-6 py-2 text-sm font-medium text-white bg-gradient-to-r from-purple-600 to-purple-700 rounded-lg hover:from-purple-700 hover:to-purple-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 shadow-lg transition-all duration-200 transform hover:scale-105">
                                                <i class="fas fa-save mr-2"></i>
                                                <span id="submitButtonText">Enregistrer le paiement</span>
                                            </button>
                                        </div>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Liste des versements -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden mt-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex justify-between items-center">
                            <h2 class="text-lg font-semibold text-gray-800">Liste des versements</h2>
                        </div>
                        <div class="mt-4 flex items-center justify-between space-x-4">
                            <div class="flex-1 max-w-md">
                                <input type="text" id="searchVersements" placeholder="Rechercher un versement..."
                                    class="w-full px-4 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex space-x-2">
                                <button type="button" onclick="exporterVersements()"
                                    class="px-3 py-2 bg-green-500 text-white rounded hover:bg-green-600 transition">
                                    <i class="fas fa-file-excel mr-1"></i> Exporter
                                </button>
                                <button type="button" onclick="imprimerListeVersements()"
                                    class="px-3 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition">
                                    <i class="fas fa-print mr-1"></i> Imprimer
                                </button>
                            </div>
                        </div>
                    </div>
                    <form id="versementsListForm" method="POST" action="?page=gestion_scolarite">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Étudiant</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Niveau</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            N° Versement</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Montant versé</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Date versement</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Méthode</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Solde</th>
                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    <?php if (!empty($versements_pages)): ?>
                                        <?php foreach ($versements_pages as $versement): ?>
                                            <tr class="versement-row">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="flex items-center">
                                                        <div
                                                            class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                                            <i class="fas fa-user text-gray-500"></i>
                                                        </div>
                                                        <div>
                                                            <p class="text-sm font-medium text-gray-800">
                                                                <?php echo htmlspecialchars($versement['nom_etu'] . ' ' . $versement['prenom_etu']); ?>
                                                            </p>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                    <?php echo htmlspecialchars($versement['lib_niv_etude'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <span
                                                        class="px-2 py-1 bg-blue-100 text-blue-800 rounded-full font-semibold">
                                                        <?php echo htmlspecialchars($versement['num_versement'] ?? 'N/A'); ?>
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                    <?php echo htmlspecialchars(number_format($versement['montant_verser'] ?? 0, 0, ',', ' ')); ?>
                                                    FCFA
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                    <?php echo htmlspecialchars(date('d/m/Y', strtotime($versement['date_versement'] ?? 'now'))); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                    <?php echo htmlspecialchars($versement['methode_paiement'] ?? 'N/A'); ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                                    <span
                                                        class="<?php echo ($versement['solde'] ?? 0) <= 0 ? 'text-green-600 font-semibold' : 'text-orange-600'; ?>">
                                                        <?php echo number_format($versement['solde'] ?? 0, 0, ',', ' '); ?> FCFA
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                                    <div class="flex items-center justify-center space-x-2">
                                                        <button
                                                            onclick="imprimerRecu(<?php echo $versement['id_inscription']; ?>)"
                                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2  transition-all duration-200"
                                                            title="Imprimer le reçu">
                                                            <i class="fas fa-print mr-1"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="8" class="px-6 py-4 text-center text-gray-500">
                                                Aucun versement trouvé.
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="bg-white px-4 py-3 flex items-center justify-between border-t border-gray-200 sm:px-6">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page_actuelle > 1): ?>
                                    <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle - 1; ?>"
                                        class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Précédent
                                    </a>
                                <?php endif; ?>
                                <?php if ($page_actuelle < $total_pages): ?>
                                    <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle + 1; ?>"
                                        class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                        Suivant
                                    </a>
                                <?php endif; ?>
                            </div>
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm text-gray-700">
                                        Affichage de <span class="font-medium"><?php echo $debut + 1; ?></span> à
                                        <span
                                            class="font-medium"><?php echo min($debut + $items_par_page, $total_items); ?></span>
                                        sur
                                        <span class="font-medium"><?php echo $total_items; ?></span> versements
                                    </p>
                                </div>
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px"
                                        aria-label="Pagination">
                                        <?php if ($page_actuelle > 1): ?>
                                            <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle - 1; ?>"
                                                class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Précédent</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php endif; ?>

                                        <?php
                                        $debut_pagination = max(1, $page_actuelle - 2);
                                        $fin_pagination = min($total_pages, $page_actuelle + 2);

                                        if ($debut_pagination > 1) {
                                            echo '<a href="?page=gestion_scolarite&page_versements=1" class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                            if ($debut_pagination > 2) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                        }

                                        for ($i = $debut_pagination; $i <= $fin_pagination; $i++) {
                                            $classes = $i === $page_actuelle
                                                ? 'relative inline-flex items-center px-4 py-2 border border-blue-500 bg-blue-50 text-sm font-medium text-blue-600'
                                                : 'relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50';
                                            echo "<a href=\"?page=gestion_scolarite&page_versements={$i}\" class=\"{$classes}\">{$i}</a>";
                                        }

                                        if ($fin_pagination < $total_pages) {
                                            if ($fin_pagination < $total_pages - 1) {
                                                echo '<span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">...</span>';
                                            }
                                            echo "<a href=\"?page=gestion_scolarite&page_versements={$total_pages}\" class=\"relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50\">{$total_pages}</a>";
                                        }
                                        ?>

                                        <?php if ($page_actuelle < $total_pages): ?>
                                            <a href="?page=gestion_scolarite&page_versements=<?php echo $page_actuelle + 1; ?>"
                                                class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                                <span class="sr-only">Suivant</span>
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales pour l'autocomplétion
        let selectedEtudiantData = null;
        let currentFocusIndex = -1;

        // Mettre à jour le focus lors de la navigation au clavier
        function updateFocus(items) {
            items.forEach((item, index) => {
                if (index === currentFocusIndex) {
                    item.classList.add('bg-blue-100');
                    item.scrollIntoView({ block: 'nearest' });
                } else {
                    item.classList.remove('bg-blue-100');
                }
            });
        }

        // Sélectionner un étudiant
        function selectEtudiant(etudiant) {
            selectedEtudiantData = etudiant;

            // Mettre à jour le champ de recherche
            const searchInput = document.getElementById('unifiedEtudiantSearch');
            searchInput.value = `${etudiant.label} (${etudiant.numero})`;

            // Mettre à jour le champ caché
            document.getElementById('unifiedEtudiantSelect').value = etudiant.value;

            // Masquer les suggestions
            document.getElementById('etudiantSuggestions').style.display = 'none';

            // Appeler la fonction de gestion
            handleEtudiantSelection(etudiant);
        }

        // Fonction pour gérer la sélection d'étudiant dans le formulaire unifié
        function handleEtudiantSelection(etudiantData) {
            if (!etudiantData) {
                resetUnifiedForm();
                return;
            }

            // Sections conditionnelles
            const nouvelleInscriptionSection = document.getElementById('nouvelleInscriptionSection');
            const situationActuelleSection = document.getElementById('situationActuelleSection');

            // Champs du formulaire
            const submitButton = document.getElementById('unifiedSubmitButton');
            const isNewInscriptionInput = document.getElementById('isNewInscription');

            const isInscrit = etudiantData.inscrit === true;

            if (isInscrit) {
                // Étudiant déjà inscrit - Afficher la situation actuelle
                nouvelleInscriptionSection.style.display = 'none';
                situationActuelleSection.style.display = 'block';
                isNewInscriptionInput.value = 'false';

                // Remplir les champs de situation actuelle
                document.getElementById('unifiedNiveauInfo').value = etudiantData.nomNiveau || 'Non défini';
                document.getElementById('unifiedMontantTotalInfo').value = formatNumber(etudiantData.montantTotal || 0);
                document.getElementById('unifiedMontantPaye').value = formatNumber(etudiantData.montantPaye || 0);
                document.getElementById('unifiedResteAPayer').value = formatNumber(etudiantData.resteAPayer || 0);

                // Vérifier si la scolarité est déjà soldée
                const resteAPayer = parseFloat(etudiantData.resteAPayer) || 0;
                if (resteAPayer <= 0) {
                    alert('⚠️ Cet étudiant a déjà soldé sa scolarité pour cette année.');
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                    submitButton.textContent = 'Scolarité soldée';
                } else {
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                    submitButton.textContent = 'Enregistrer le versement';
                }
            } else {
                // Nouvel étudiant - Afficher le formulaire d'inscription
                nouvelleInscriptionSection.style.display = 'block';
                situationActuelleSection.style.display = 'none';
                isNewInscriptionInput.value = 'true';

                submitButton.disabled = false;
                submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                submitButton.textContent = 'Créer l\'inscription';
            }
        }

        // Fonction pour mettre à jour le montant total selon le niveau sélectionné (pour nouvelle inscription)
        function updateMontantScolariteUnified() {
            const selectNiveau = document.getElementById('unifiedNiveauSelect');
            const montantTotalField = document.getElementById('unifiedMontantTotal');

            if (selectNiveau && selectNiveau.selectedIndex > 0) {
                const selectedOption = selectNiveau.options[selectNiveau.selectedIndex];
                const montant = selectedOption.dataset.montant;
                montantTotalField.value = formatNumber(montant);
            } else {
                montantTotalField.value = '';
            }
        }

        // Fonction pour réinitialiser le formulaire unifié
        function resetUnifiedForm() {
            document.getElementById('unifiedPaymentForm').reset();
            document.getElementById('nouvelleInscriptionSection').style.display = 'none';
            document.getElementById('situationActuelleSection').style.display = 'none';
            document.getElementById('unifiedSubmitButton').disabled = true;
            document.getElementById('unifiedSubmitButton').classList.add('opacity-50', 'cursor-not-allowed');
        }

        document.addEventListener('DOMContentLoaded', function () {
            console.log('DOM Content Loaded');

            const unifiedForm = document.getElementById('unifiedPaymentForm');
            const searchInput = document.getElementById('searchVersements');
            const searchEtudiant = document.getElementById('unifiedEtudiantSearch');
            const suggestionsDiv = document.getElementById('etudiantSuggestions');

            // === Autocomplétion des étudiants ===
            if (searchEtudiant && suggestionsDiv) {
                console.log('Système d\'autocomplétion initialisé');
                console.log('Nombre d\'étudiants:', etudiantsData ? etudiantsData.length : 0);

                // Écouter les frappes
                searchEtudiant.addEventListener('input', function () {
                    const query = this.value.trim().toLowerCase();
                    console.log('Recherche:', query);

                    if (query.length === 0) {
                        suggestionsDiv.style.display = 'none';
                        selectedEtudiantData = null;
                        document.getElementById('unifiedEtudiantSelect').value = '';
                        resetUnifiedForm();
                        updateSearchHint(0, '');
                        return;
                    }

                    // Au moins 2 caractères pour commencer la recherche
                    if (query.length < 2) {
                        updateSearchHint(0, '');
                        return;
                    }

                    // Vérifier si etudiantsData existe
                    if (typeof etudiantsData === 'undefined' || !etudiantsData) {
                        console.error('etudiantsData non défini!');
                        suggestionsDiv.innerHTML = '<div class="px-4 py-3 text-sm text-red-500 text-center"><i class="fas fa-exclamation-triangle mr-2"></i>Erreur: Données non chargées</div>';
                        suggestionsDiv.style.display = 'block';
                        return;
                    }

                    // Filtrer les étudiants
                    const filtered = etudiantsData.filter(etudiant => {
                        return etudiant.label.toLowerCase().includes(query) ||
                            etudiant.numero.toLowerCase().includes(query) ||
                            etudiant.categorie.toLowerCase().includes(query);
                    });

                    console.log('Résultats filtrés:', filtered.length);

                    // Afficher les suggestions
                    displaySuggestions(filtered);

                    // Mettre à jour le hint
                    updateSearchHint(filtered.length, query);
                });

                // Fermer les suggestions si on clique ailleurs
                document.addEventListener('click', function (e) {
                    if (!searchEtudiant.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                        suggestionsDiv.style.display = 'none';
                    }
                });

                // Navigation au clavier
                searchEtudiant.addEventListener('keydown', function (e) {
                    const items = suggestionsDiv.querySelectorAll('.suggestion-item');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        currentFocusIndex = Math.min(currentFocusIndex + 1, items.length - 1);
                        updateFocus(items);
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        currentFocusIndex = Math.max(currentFocusIndex - 1, 0);
                        updateFocus(items);
                    } else if (e.key === 'Enter') {
                        e.preventDefault();
                        if (currentFocusIndex >= 0 && items[currentFocusIndex]) {
                            items[currentFocusIndex].click();
                        }
                    } else if (e.key === 'Escape') {
                        suggestionsDiv.style.display = 'none';
                    }
                });
            }

            // Validation du formulaire unifié avant soumission
            if (unifiedForm) {
                unifiedForm.addEventListener('submit', function (e) {
                    const select = document.getElementById('unifiedEtudiantSelect');

                    if (!select.value || !selectedEtudiantData) {
                        e.preventDefault();
                        alert('⚠️ Veuillez sélectionner un étudiant.');
                        return;
                    }

                    const isNewInscription = document.getElementById('isNewInscription').value === 'true';
                    const montantVersement = parseFloat(document.getElementById('unifiedMontantVersement').value) || 0;
                    const methodeSection = document.getElementById('unifiedMethodePaiement');

                    // Validation du montant
                    if (montantVersement <= 0) {
                        e.preventDefault();
                        alert('❌ Le montant doit être supérieur à 0.');
                        return;
                    }

                    // Validation spécifique pour versement additionnel
                    if (!isNewInscription) {
                        const resteAPayer = parseFloat(selectedEtudiantData.resteAPayer) || 0;
                        if (montantVersement > resteAPayer) {
                            e.preventDefault();
                            alert('❌ Le montant ne peut pas dépasser le reste à payer (' + formatNumber(resteAPayer) + ' FCFA).');
                            return;
                        }
                    }

                    // Validation de la méthode de paiement
                    if (!methodeSection.value) {
                        e.preventDefault();
                        alert('⚠️ Veuillez sélectionner une méthode de paiement.');
                        return;
                    }

                    // Validation pour nouvelle inscription
                    if (isNewInscription) {
                        const niveau = document.getElementById('unifiedNiveauSelect').value;
                        const annee = document.getElementById('unifiedAnneeAcadSelect').value;

                        if (!niveau) {
                            e.preventDefault();
                            alert('⚠️ Veuillez sélectionner un niveau d\'étude.');
                            return;
                        }

                        if (!annee) {
                            e.preventDefault();
                            alert('⚠️ Veuillez sélectionner une année académique.');
                            return;
                        }
                    }

                    // Confirmation avant soumission
                    const messageType = isNewInscription ? 'inscription' : 'versement';
                    const confirmation = confirm('✅ Confirmer l\\'enregistrement ' + (isNewInscription ? 'de l\'' : 'du ') + messageType + ' de ' + formatNumber(montantVersement) + ' FCFA ?');
                if (!confirmation) {
                    e.preventDefault();
                }
            });
        }

        // Recherche de versements
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchTerm = this.value.toLowerCase();
                const rows = document.querySelectorAll('.versement-row');

                rows.forEach(row => {
                    const text = row.textContent.toLowerCase();
                    row.style.display = text.includes(searchTerm) ? '' : 'none';
                });
            });
        }
    });

        // Fonction pour imprimer le reçu
        // Supporte les appels rétrocompatibles : imprimerRecu(id) ou imprimerRecu(id, isVersement, idInscription)
        function imprimerRecu(id, isVersement, idInscription) {
            // Si appelé avec un seul argument, on le considère comme un id_versement
            if (typeof isVersement === 'undefined') {
                isVersement = true;
            }

            if (!id) {
                alert('ID manquant pour l\'impression du reçu');
                return;
            }

            if (isVersement) {
                // id est un id_versement
                window.open(`?page=gestion_scolarite&action=imprimer_recu&id=${id}`, '_blank');
                return;
            }

            // id est un id_inscription : si idInscription est fourni, on l'utilise, sinon on utilise id
            var inscriptionId = idInscription || id;
            // Ouvrir la page qui imprimera le dernier versement pour cette inscription (serveur fera le fallback)
            window.open(`?page=gestion_scolarite&action=imprimer_recu&id=${inscriptionId}`, '_blank');
        }

        // Fonction pour exporter les versements
        function exporterVersements() {
            const searchTerm = document.getElementById('searchVersements').value.toLowerCase();
            const rows = document.querySelectorAll('.versement-row');
            const selectedVersements = document.querySelectorAll('.versement-checkbox:checked');

            // Si aucun versement n'est sélectionné et qu'il y a une recherche, exporter les versements filtrés
            const versementsAExporter = selectedVersements.length > 0 ? selectedVersements :
                Array.from(rows).filter(row => {
                    const text = row.textContent.toLowerCase();
                    return searchTerm === '' || text.includes(searchTerm);
                });

            if (versementsAExporter.length === 0) {
                alert('Aucun versement à exporter.');
                return;
            }

            // Créer le contenu CSV
            let csvContent = "data:text/csv;charset=utf-8,";
            csvContent += "Étudiant,Montant,Date,Méthode,Type\n";

            versementsAExporter.forEach(row => {
                const cells = row.querySelectorAll('td:not(:first-child):not(:last-child)');
                const rowData = Array.from(cells).map(cell => `"${cell.textContent.trim()}"`).join(',');
                csvContent += rowData + '\n';
            });

            // Télécharger le fichier
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'versements.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Fonction pour imprimer la liste des versements
        function imprimerListeVersements() {
            const searchTerm = document.getElementById('searchVersements').value.toLowerCase();
            const rows = document.querySelectorAll('.versement-row');
            const selectedVersements = document.querySelectorAll('.versement-checkbox:checked');

            // Si aucun versement n'est sélectionné et qu'il y a une recherche, imprimer les versements filtrés
            const versementsAImprimer = selectedVersements.length > 0 ? selectedVersements :
                Array.from(rows).filter(row => {
                    const text = row.textContent.toLowerCase();
                    return searchTerm === '' || text.includes(searchTerm);
                });

            if (versementsAImprimer.length === 0) {
                alert('Aucun versement à imprimer.');
                return;
            }

            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
            <html>
            <head>
                <title>Liste des versements</title>
                <style>
                    body { font-family: Arial, sans-serif; }
                    table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                    th { background-color: #f5f5f5; }
                    h2 { text-align: center; margin: 20px 0; }
                    .info { text-align: center; margin: 10px 0; color: #666; }
                    @media print {
                        body { margin: 0; padding: 20px; }
                        table { page-break-inside: auto; }
                        tr { page-break-inside: avoid; page-break-after: auto; }
                    }
                </style>
            </head>
            <body>
                <h2>Liste des versements</h2>
                <div class="info">
                    ${searchTerm ? `Filtre de recherche : "${searchTerm}"` : 'Liste complète des versements'}<br>
                    Nombre de versements : ${versementsAImprimer.length}<br>
                    Date d'impression : ${new Date().toLocaleDateString()}
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Étudiant</th>
                            <th>Montant</th>
                            <th>Date</th>
                            <th>Méthode</th>
                            <th>Type</th>
                        </tr>
                    </thead>
                    <tbody>
        `);

            versementsAImprimer.forEach(row => {
                const cells = row.querySelectorAll('td:not(:first-child):not(:last-child)');
                printWindow.document.write('<tr>');
                cells.forEach(cell => {
                    printWindow.document.write(`<td>${cell.textContent.trim()}</td>`);
                });
                printWindow.document.write('</tr>');
            });

            printWindow.document.write(`
                    </tbody>
                </table>
            </body>
            </html>
        `);
            printWindow.document.close();
            printWindow.print();
            printWindow.focus();
            printWindow.close();
        }

        // Gérer les notifications
        const successNotification = document.getElementById('successNotification');
        const errorNotification = document.getElementById('errorNotification');

        function removeNotification(notification) {
            if (notification) {
                notification.classList.add('animate__fadeOut');
                setTimeout(() => notification.remove(), 500);
            }
        }

        if (successNotification) {
            setTimeout(() => removeNotification(successNotification), 5000);
        }

        if (errorNotification) {
            setTimeout(() => removeNotification(errorNotification), 5000);
        }
    </script>
</body>

</html>
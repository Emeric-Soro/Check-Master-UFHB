<?php
// Initialisation des variables avec des valeurs par défaut
$etudiantsInscrits = isset($GLOBALS['etudiantsInscrits']) ? $GLOBALS['etudiantsInscrits'] : [];
$listeAllEtudiant = $GLOBALS['listeAllEtudiant'];
$allVersement = $GLOBALS['listeVersement'];

// Configuration de la pagination
$items_par_page = 10; // Nombre d'éléments par page
$page_actuelle = isset($_GET['page_versements']) ? (int)$_GET['page_versements'] : 1;
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
    </style>
</head>

<body class="font-sans antialiased bg-gray-50">
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
                    <div>
                        <h1 class="text-2xl font-bold text-gray-800">Gestion des paiements</h1>
                        <p class="text-gray-600">Suivi des paiements de scolarité</p>
                    </div>

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

                <!-- Payment form -->
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-800">
                            <?php echo isset($GLOBALS['versementAModifier']) ? 'Mettre à jour un versement' : 'Enregistrer un versement'; ?>
                        </h2>
                    </div>
                    <div class="px-6 py-4">
                        <form id="versementsForm" method="POST"
                            action="?page=gestion_scolarite<?php echo isset($GLOBALS['versementAModifier']) ? '&action=mettre_a_jour_versement' : '&action=enregistrer_versement'; ?>">
                            <?php if (isset($GLOBALS['versementAModifier'])): ?>
                            <input type="hidden" name="id_versement"
                                value="<?php echo $GLOBALS['versementAModifier']['id_versement']; ?>">
                            <?php endif; ?>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label for="studentSelect"
                                        class="block text-sm font-medium text-gray-700 mb-1">Étudiant <span
                                            class="text-red-500">*</span></label>
                                    <select id="studentSelect" name="id_etudiant" required
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Sélectionner un étudiant</option>
                                        <?php foreach ($etudiantsInscrits as $etudiant): ?>
                                        <option value="<?php echo $etudiant['id_etudiant']; ?>"
                                            data-montant-total="<?php echo isset($GLOBALS['montantTotal']) ? $GLOBALS['montantTotal'] : (isset($etudiant['montant_scolarite']) ? $etudiant['montant_scolarite'] : 0); ?>"
                                            data-montant-paye="<?php echo isset($GLOBALS['montantPaye']) ? $GLOBALS['montantPaye'] : (isset($etudiant['montant_paye']) ? $etudiant['montant_paye'] : 0); ?>"
                                            data-reste-a-payer="<?php echo isset($GLOBALS['resteAPayer']) ? $GLOBALS['resteAPayer'] : (isset($etudiant['reste_a_payer']) ? $etudiant['reste_a_payer'] : 0); ?>"
                                            <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['id_inscription'] == $etudiant['id_inscription']) ? 'selected' : ''; ?>>
                                            <?php echo $etudiant['nom'] . ' ' . $etudiant['prenom']; ?> -
                                            <?php echo $etudiant['nom_niveau']; ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label for="paymentAmount"
                                        class="block text-sm font-medium text-gray-700 mb-1">Montant <span
                                            class="text-red-500">*</span></label>
                                    <div class="relative mt-1">
                                        <div
                                            class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500">FCFA</span>
                                        </div>
                                        <input type="number" id="paymentAmount" name="montant" required
                                            class="block w-full pl-16 pr-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                            placeholder="0"
                                            value="<?php echo isset($GLOBALS['versementAModifier']) ? $GLOBALS['versementAModifier']['montant'] : ''; ?>">
                                    </div>
                                </div>

                                <div>
                                    <label for="paymentMethod"
                                        class="block text-sm font-medium text-gray-700 mb-1">Méthode <span
                                            class="text-red-500">*</span></label>
                                    <select id="paymentMethod" name="methode_paiement" required
                                        class="mt-1 block w-full border border-gray-300 rounded-md shadow-sm py-2 px-3 focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Sélectionner une méthode de paiement</option>
                                        <option value="Espèce"
                                            <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Espèce') ? 'selected' : ''; ?>>
                                            Espèce</option>
                                        <option value="Carte bancaire"
                                            <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Carte bancaire') ? 'selected' : ''; ?>>
                                            Carte bancaire</option>
                                        <option value="Virement"
                                            <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Virement') ? 'selected' : ''; ?>>
                                            Virement</option>
                                        <option value="Chèque"
                                            <?php echo (isset($GLOBALS['versementAModifier']) && $GLOBALS['versementAModifier']['methode_paiement'] == 'Chèque') ? 'selected' : ''; ?>>
                                            Chèque</option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-end mt-4">
                                <button type="submit" id="submitButton"
                                    class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    <?php echo isset($GLOBALS['versementAModifier']) ? 'Mettre à jour' : 'Enregistrer'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

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
                    <form id="versementsForm" method="POST" action="?page=gestion_scolarite">
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>

                                        <th scope="col"
                                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                            Étudiant</th>
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
                                            Type versement</th>
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
                                                        <?php echo htmlspecialchars($versement['nom_etudiant'] . ' ' . $versement['prenom_etudiant']); ?>
                                                    </p>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                            <?php echo htmlspecialchars(number_format($versement['montant'] ?? 0, 0, ',', ' ')); ?>
                                            FCFA
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                            <?php echo htmlspecialchars(date('d/m/Y', strtotime($versement['date_versement'] ?? 'now'))); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                            <?php echo htmlspecialchars($versement['methode_paiement'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-800">
                                            <?php echo htmlspecialchars($versement['type_versement'] ?? 'N/A'); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                            <div class="flex items-center justify-center space-x-2">
                                                <?php if ($versement['type_versement'] === 'Tranche'): ?>
                                                <a href="?page=gestion_scolarite&action=mettre_a_jour_versement&id=<?php echo $versement['id_versement']; ?>"
                                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200">
                                                    <i class="fas fa-edit mr-1"></i>
                                                </a>
                                                <?php endif; ?>
                                                <button
                                                    onclick="imprimerRecu(<?php echo $versement['id_versement']; ?>)"
                                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2  transition-all duration-200">
                                                    <i class="fas fa-print mr-1"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php else: ?>
                                    <tr>
                                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">
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
    document.addEventListener('DOMContentLoaded', function() {
        const studentSelect = document.getElementById('studentSelect');
        const paymentAmount = document.getElementById('paymentAmount');
        const searchInput = document.getElementById('searchVersements');
        const submitButton = document.getElementById('submitButton');
        const paymentMethod = document.getElementById('paymentMethod');




        // Mettre à jour le montant maximum possible
        studentSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const resteAPayer = selectedOption.dataset.resteAPayer;
                if (resteAPayer == 0) {
                    paymentAmount.disabled = true;
                    paymentAmount.value = '';
                    paymentAmount.placeholder = 'Paiement déjà soldé';
                    submitButton.disabled = true;
                    submitButton.classList.add('opacity-50', 'cursor-not-allowed');
                } else {
                    paymentAmount.disabled = false;
                    paymentAmount.max = resteAPayer;
                    paymentAmount.placeholder = `Montant maximum: ${resteAPayer} FCFA`;
                    submitButton.disabled = false;
                    submitButton.classList.remove('opacity-50', 'cursor-not-allowed');
                }

            }
        });

        // Recherche d'étudiants
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const rows = document.querySelectorAll('.versement-row');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });

        // Validation du formulaire
        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            const amount = parseFloat(paymentAmount.value);
            const selectedOption = studentSelect.options[studentSelect.selectedIndex];
            const resteAPayer = parseFloat(selectedOption.dataset.resteAPayer);

            if (amount > resteAPayer) {
                e.preventDefault();
                alert('Le montant ne peut pas dépasser le reste à payer.');
            }

        });

    });



    // Fonction pour imprimer le reçu
    function imprimerRecu(idInscription) {
        if (!idInscription) {
            alert('ID de l\'inscription manquant');
            return;
        }
        window.open(`?page=gestion_scolarite&action=imprimer_recu&id=${idInscription}`, '_blank');
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
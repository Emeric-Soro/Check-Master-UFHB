<?php
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';

$listeEtudiants = $GLOBALS['listeEtudiants'] ?? [];
$etudiant_a_modifier = $GLOBALS['etudiant_a_modifier'] ?? null;

// Pagination
$currentPage = $GLOBALS['currentPage'] ?? 1;
$itemsPerPage = $GLOBALS['itemsPerPage'] ?? 10;
$totalItems = $GLOBALS['totalItems'] ?? 0;
$totalPages = $GLOBALS['totalPages'] ?? 0;
$startIndex = $GLOBALS['startIndex'] ?? 0;
$endIndex = $GLOBALS['endIndex'] ?? 0;
$currentPageItems = $GLOBALS['listeEtudiants'] ?? [];

// Récupérer tous les étudiants pour la recherche
$allEtudiants = $GLOBALS['allEtudiants'] ?? [];

// Récupérer la liste des niveaux d'étude
$listeNiveaux = $GLOBALS['listeNiveaux'] ?? [];

// Récupérer la liste des années académiques
$listeAnneesAcad = $GLOBALS['listeAnneesAcad'] ?? [];

// Déterminer l'année académique en cours
$anneeAcadEnCours = null;
$dateActuelle = date('Y-m-d');
foreach ($listeAnneesAcad as $annee) {
    if ($dateActuelle >= $annee->date_deb && $dateActuelle <= $annee->date_fin) {
        $anneeAcadEnCours = $annee->id_annee_acad;
        break;
    }
}
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des étudiants</title>
    <style>
        /* Styles pour les notifications */
        .notification {
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            max-width: 24rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
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

        /* Dégradé pour les boutons */
        .bg-gradient {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .bg-gradient:hover {
            background: linear-gradient(135deg, #16a34a 0%, #15803d 100%);
        }

        /* Ombres pour les cartes */
        .shadow-card {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
        }

        /* Style personnalisé pour la barre de défilement */
        .custom-scrollbar::-webkit-scrollbar {
            width: 8px;
        }

        .custom-scrollbar::-webkit-scrollbar-track {
            background: #e5e7eb;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #22c55e;
            border-radius: 4px;
        }

        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #16a34a;
        }
    </style>
</head>

<body style="background-color: #DFF2FF;">
    <div class="relative container mx-auto px-3 py-3">
        <!-- Système de notification -->
        <?php if (!empty($GLOBALS['messageSuccess'])): ?>
            <div id="successNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
                <div class="notification success">
                    <div class="flex items-center">
                        <i class="fas fa-check-circle mr-2"></i>
                        <p><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($GLOBALS['messageErreur'])): ?>
            <div id="errorNotification" class="fixed top-4 right-4 z-50 animate__animated animate__fadeIn">
                <div class="notification error">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle mr-2"></i>
                        <p><?= htmlspecialchars($GLOBALS['messageErreur']) ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Formulaire compact d'ajout/mise à jour étudiant -->
        <div class="bg-white rounded-lg shadow-card p-3 mb-3 border border-gray-200">
            <form id="userForm" class="space-y-2" method="post"
                action="?page=gestion_etudiants&action=ajouter_des_etudiants">
                <input type="hidden" name="csrf_token"
                    value="<?php echo htmlspecialchars(\CheckMaster\Core\Csrf::token()); ?>">
                <?php if ($etudiant_a_modifier): ?>
                    <input type="hidden" name="old_num_etu"
                        value="<?php echo htmlspecialchars($etudiant_a_modifier->num_carte_etud); ?>">
                <?php endif; ?>

                <!-- Année Académique -->
                <div class="pb-2 border-b border-gray-200 flex justify-end">
                    <div class="space-y-1 w-64">
                        <label for="id_annee_acad" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-calendar-alt text-green-500 mr-1"></i>Année Académique
                        </label>
                        <select name="id_annee_acad" id="id_annee_acad"
                            class="focus:outline-none w-full px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner une année</option>
                            <?php
                            foreach ($listeAnneesAcad as $annee):
                                $isSelected = false;
                                if ($etudiant_a_modifier) {
                                    $isSelected = ($etudiant_a_modifier->id_annee_acad == $annee->id_annee_acad);
                                } else {
                                    // Si pas en mode modification, sélectionner l'année en cours
                                    $isSelected = ($anneeAcadEnCours && $annee->id_annee_acad == $anneeAcadEnCours);
                                }
                                ?>
                                <option value="<?php echo htmlspecialchars($annee->id_annee_acad); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin))); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Ligne 1: N° Étudiant, Nom, Prénom-->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <div class="space-y-1">
                        <label for="num_ident_etud" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-fingerprint text-green-500 mr-1"></i>Identifiant MESRS
                        </label>
                        <input type="text" name="num_ident_etud" id="num_ident_etud" maxlength="25"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->identifiant_mesrs ?? '') : ''; ?>"
                            placeholder="Identifiant MESRS"
                            class="focus:outline-none w-full px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label for="num_etu" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-id-card text-green-500 mr-1"></i>N° Étudiant <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="text" name="num_etu" id="num_etu" required maxlength="25"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->num_carte_etud) : ''; ?>"
                            placeholder="Ex: 20230001"
                            class="focus:outline-none w-full px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label for="nom_etu" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-user text-green-500 mr-1"></i>Nom <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nom_etu" id="nom_etu" required maxlength="50"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->nom_etu) : ''; ?>"
                            placeholder="Nom"
                            class="focus:outline-none w-full px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label for="prenom_etu" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-user text-green-500 mr-1"></i>Prénom <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="prenom_etu" id="prenom_etu" required maxlength="100"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->prenom_etu) : ''; ?>"
                            placeholder="Prénom"
                            class="focus:outline-none w-full px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>

                </div>

                <!-- Ligne 2: Date de naissance, Genre, Email, Niveau -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <div class="space-y-1">
                        <label for="date_naiss_etu" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-calendar text-green-500 mr-1"></i>Date de Naissance <span
                                class="text-red-500">*</span>
                        </label>
                        <input type="date" name="date_naiss_etu" id="date_naiss_etu" required
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->date_naiss_etu) : ''; ?>"
                            class="focus:outline-none w-full px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>
                    <div class="space-y-1">
                        <label for="genre_etu" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-venus-mars text-green-500 mr-1"></i>Genre <span
                                class="text-red-500">*</span>
                        </label>
                        <select name="genre_etu" id="genre_etu" required
                            class="focus:outline-none w-full px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner</option>
                            <option value="1" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu == 1) ? 'selected' : ''; ?>>Masculin</option>
                            <option value="2" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu == 2) ? 'selected' : ''; ?>>Féminin</option>
                            <option value="3" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu == 3) ? 'selected' : ''; ?>>Neutre</option>
                        </select>
                    </div>
                </div>

                <!-- Ligne 3: Promotion -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                    <div class="space-y-1">
                        <label for="id_niveau" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-graduation-cap text-green-500 mr-1"></i>Niveau
                        </label>
                        <select name="id_niveau" id="id_niveau"
                            class="focus:outline-none w-64 px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner un niveau</option>
                            <?php foreach ($listeNiveaux as $niveau): ?>
                                <option value="<?php echo htmlspecialchars($niveau->id_niv_etude); ?>" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->id_niveau == $niveau->id_niv_etude) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($niveau->lib_niv_etude); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="promotion_etu" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-users text-green-500 mr-1"></i>Promotion <span
                                class="text-red-500">*</span>
                        </label>
                        <select name="promotion_etu" id="promotion_etu" required
                            class="focus:outline-none w-64 px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white transition-all duration-200">
                            <option value="">Sélectionner une promotion</option>
                            <?php
                            foreach ($listeAnneesAcad as $annee):
                                $promotionLabel = date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin));
                                $isSelected = false;
                                if ($etudiant_a_modifier) {
                                    $isSelected = ($etudiant_a_modifier->promotion_etu == $promotionLabel);
                                } else {
                                    // Si pas en mode modification, sélectionner l'année en cours
                                    $isSelected = ($anneeAcadEnCours && $annee->id_annee_acad == $anneeAcadEnCours);
                                }
                                ?>
                                <option value="<?php echo htmlspecialchars($promotionLabel); ?>" <?php echo $isSelected ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($promotionLabel); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="space-y-1">
                        <label for="email_etu" class="block text-xs font-semibold text-gray-700">
                            <i class="fas fa-envelope text-green-500 mr-1"></i>Email <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email_etu" id="email_etu" required maxlength="60"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->email_etu) : ''; ?>"
                            placeholder="email@example.com"
                            class="focus:outline-none w-75 px-3 py-1.5 border-2 border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200 text-sm">
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-2 pt-2 border-t border-gray-200 mt-2">
                    <?php if ($etudiant_a_modifier): ?>
                        <a href="?page=gestion_etudiants&action=ajouter_des_etudiants"
                            class="px-4 py-1.5 border border-gray-300 text-xs font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fas fa-times mr-1"></i>Annuler
                        </a>
                        <button type="submit" name="submit_modifier_etudiant"
                            class="px-4 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-1"></i>Modifier
                        </button>
                    <?php else: ?>
                        <button type="reset"
                            class="px-4 py-1.5 border border-gray-300 text-xs font-medium rounded-lg shadow-sm text-gray-700 bg-white hover:bg-gray-50 transition-all duration-200">
                            <i class="fas fa-redo mr-1"></i>Réinitialiser
                        </button>
                        <button type="submit" name="submit_add_etudiant"
                            class="px-4 py-1.5 border border-transparent text-xs font-medium rounded-lg shadow-sm text-white bg-gradient hover:shadow-lg transition-all duration-200">
                            <i class="fas fa-save mr-1"></i>Enregistrer
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Main Content -->
        <div class="bg-white shadow-card rounded-lg overflow-hidden border border-gray-200">
            <!-- Dashboard Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-800 px-4 py-3">
                <h2 class="text-lg font-bold text-gray-700">Liste des étudiants</h2>
            </div>

            <!-- Action Bar for Table -->
            <div
                class="px-4 py-2 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200 gap-2">
                <div class="flex gap-2 w-full sm:w-auto">
                    <div class="flex items-center gap-1">
                        <label for="limitSelect" class="text-xs text-gray-600 whitespace-nowrap">
                            <i class="fas fa-list-ol mr-1"></i>Afficher:
                        </label>
                        <select id="limitSelect" onchange="changeLimit(this.value)"
                            class="px-2 py-1.5 text-xs border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 bg-white">
                            <option value="2" <?php echo $itemsPerPage == 2 ? 'selected' : ''; ?>>2</option>
                            <option value="5" <?php echo $itemsPerPage == 5 ? 'selected' : ''; ?>>5</option>
                            <option value="10" <?php echo $itemsPerPage == 10 ? 'selected' : ''; ?>>10</option>
                            <option value="25" <?php echo $itemsPerPage == 25 ? 'selected' : ''; ?>>25</option>
                            <option value="50" <?php echo $itemsPerPage == 50 ? 'selected' : ''; ?>>50</option>
                            <option value="100" <?php echo $itemsPerPage == 100 ? 'selected' : ''; ?>>100</option>
                        </select>
                    </div>
                    <div class="relative flex-1 sm:flex-initial sm:w-64">
                        <input type="text" id="searchInput" placeholder="Rechercher un étudiant..."
                            class="w-full px-3 py-1.5 pl-8 text-sm border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-2 pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-xs"></i>
                        </span>
                    </div>
                </div>
                <div class="flex flex-wrap gap-1.5 justify-center sm:justify-end">
                    <?php if (canEdit()): ?>
                        <button onclick="selectAll()"
                            class="bg-indigo-500 hover:bg-indigo-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-opacity-50">
                            <i class="fas fa-check-square mr-1"></i>Sélectionner tout
                        </button>
                        <button onclick="deselectAll()"
                            class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-opacity-50">
                            <i class="fas fa-square mr-1"></i>Désélectionner tout
                        </button>
                        <button onclick="deleteSelected()" id="deleteSelectedBtn"
                            class="bg-red-500 hover:bg-red-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-opacity-50 opacity-50 cursor-not-allowed"
                            disabled>
                            <i class="fas fa-trash mr-1"></i>Supprimer (<span id="selectedCount">0</span>)
                        </button>
                    <?php endif; ?>
                    <button onclick="imprimerListe()"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-print mr-1"></i>Imprimer
                    </button>
                    <button onclick="exporterListe()"
                        class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-1 px-2.5 text-xs rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                        <i class="fas fa-file-export mr-1"></i>Exporter
                    </button>
                </div>
            </div>

            <!-- Users Table with Scroll -->
            <div class="overflow-y-scroll custom-scrollbar"
                style="max-height: 130px; scrollbar-width: thin; scrollbar-color: #22c55e #e5e7eb;">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50 sticky top-0 z-10">
                            <tr>
                                <?php if (canEdit()): ?>
                                    <th
                                        class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        <input type="checkbox" id="selectAllCheckbox" onclick="toggleSelectAll()"
                                            class="rounded text-green-600 focus:ring-green-500">
                                    </th>
                                <?php endif; ?>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    ID MESRS</th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    N° Etudiant</th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Nom</th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Prénom</th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Date Nais.</th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Genre</th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Email</th>
                                <th
                                    class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                    Promotion</th>
                                <?php if (canEdit()): ?>
                                    <th
                                        class="px-4 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                                        Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                            <?php if (empty($currentPageItems)): ?>
                                <tr>
                                    <td colspan="<?php echo canEdit() ? '10' : '8'; ?>"
                                        class="px-4 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-users text-gray-300 text-3xl mb-3"></i>
                                            <p class="text-sm">Aucun étudiant trouvé.</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($currentPageItems as $etudiant): ?>
                                    <tr class="hover:bg-gray-50">
                                        <?php if (canEdit()): ?>
                                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                                <input type="checkbox"
                                                    class="student-checkbox rounded text-green-600 focus:ring-green-500"
                                                    value="<?php echo htmlspecialchars($etudiant->num_carte_etud); ?>"
                                                    data-name="<?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>"
                                                    onchange="updateSelectedCount()">
                                            </td>
                                        <?php endif; ?>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->identifiant_mesrs ?? ''); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->num_carte_etud); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->nom_etu); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->prenom_etu); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->date_naiss_etu); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->libelle_genre ?? $etudiant->genre_etu); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->email_etu); ?>
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-center text-sm">
                                            <?php echo htmlspecialchars($etudiant->promotion_etu); ?>
                                        </td>
                                        <?php if (canEdit()): ?>
                                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&num_etu=<?php echo $etudiant->num_carte_etud; ?>"
                                                    class="text-blue-600 hover:text-blue-900 mr-2" title="Modifier">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button
                                                    onclick="deleteStudent('<?php echo htmlspecialchars($etudiant->num_carte_etud); ?>', '<?php echo htmlspecialchars(addslashes($etudiant->nom_etu . ' ' . $etudiant->prenom_etu)); ?>')"
                                                    class="text-red-600 hover:text-red-900" title="Supprimer">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="bg-white px-4 py-3 border-t border-gray-200">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-3">
                        <div class="text-xs text-gray-500">
                            Affichage de <?= $startIndex + 1 ?> à <?= min($startIndex + $itemsPerPage, $totalItems) ?> sur
                            <?= $totalItems ?> entrées
                        </div>
                        <div class="flex flex-wrap justify-center gap-1">
                            <?php
                            $limitParam = isset($_GET['limit']) ? '&limit=' . (int) $_GET['limit'] : '';
                            $searchParam = !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '';
                            ?>
                            <?php if ($currentPage > 1): ?>
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $currentPage - 1 ?><?= $searchParam . $limitParam ?>"
                                    class="px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    <i class="fas fa-chevron-left mr-1"></i>Précédent
                                </a>
                            <?php endif; ?>

                            <?php
                            $start = max(1, $currentPage - 2);
                            $end = min($totalPages, $currentPage + 2);

                            if ($start > 1) {
                                echo '<a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=1' . $searchParam . $limitParam . '" class="px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                if ($start > 2) {
                                    echo '<span class="px-2 py-1 text-gray-500 text-xs">...</span>';
                                }
                            }

                            for ($i = $start; $i <= $end; $i++):
                                ?>
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $i ?><?= $searchParam . $limitParam ?>"
                                    class="px-2 py-1 <?= $i === $currentPage ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-xs font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor;

                            if ($end < $totalPages) {
                                if ($end < $totalPages - 1) {
                                    echo '<span class="px-2 py-1 text-gray-500 text-xs">...</span>';
                                }
                                echo '<a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=' . $totalPages . $searchParam . $limitParam . '" class="px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                            }
                            ?>

                            <?php if ($currentPage < $totalPages): ?>
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $currentPage + 1 ?><?= $searchParam . $limitParam ?>"
                                    class="px-2 py-1 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 hover:bg-gray-50">
                                    Suivant<i class="fas fa-chevron-right ml-1"></i>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script>
        // Fonction pour changer la limite d'affichage
        function changeLimit(limit) {
            const currentUrl = new URL(window.location.href);
            currentUrl.searchParams.set('limit', limit);
            currentUrl.searchParams.set('p', '1'); // Retour à la première page
            window.location.href = currentUrl.toString();
        }

        // Initialisation au chargement de la page
        document.addEventListener('DOMContentLoaded', function () {
            const searchInput = document.getElementById('searchInput');
            const searchTerm = '<?= $GLOBALS['searchTerm'] ?? '' ?>';

            // Si un terme de recherche est présent dans l'URL, l'afficher dans le champ de recherche
            if (searchTerm) {
                searchInput.value = searchTerm;
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
        });

        // Search functionality
        const searchInput = document.getElementById('searchInput');

        searchInput.addEventListener('input', function () {
            const searchTerm = this.value.toLowerCase();
            const allEtudiants = <?= json_encode($allEtudiants) ?>;

            // Filtrer les étudiants
            const filteredEtudiants = allEtudiants.filter(etudiant => {
                const nom = etudiant.nom_etu.toLowerCase();
                const prenom = etudiant.prenom_etu.toLowerCase();
                const num = etudiant.num_carte_etud.toLowerCase();
                return nom.includes(searchTerm) || prenom.includes(searchTerm) || num.includes(searchTerm);
            });

            // Mettre à jour l'affichage
            if (filteredEtudiants.length === 0) {
                document.getElementById('usersTableBody').innerHTML = `
                    <tr>
                        <td colspan="<?php echo canEdit() ? '10' : '8'; ?>" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
                                <p>Aucun étudiant ne correspond à votre recherche.</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                const canEdit = <?php echo canEdit() ? 'true' : 'false'; ?>;
                filteredEtudiants.forEach(etudiant => {
                    html += `<tr class="hover:bg-gray-50">`;

                    if (canEdit) {
                        html += `
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <input type="checkbox" class="student-checkbox rounded text-green-600 focus:ring-green-500" 
                                       value="${etudiant.num_carte_etud}"
                                       data-name="${etudiant.nom_etu} ${etudiant.prenom_etu}"
                                       onchange="updateSelectedCount()">
                            </td>
                        `;
                    }

                    html += `
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.num_carte_etud}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.identifiant_mesrs || ''}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.nom_etu}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.prenom_etu}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.date_naiss_etu}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.libelle_genre || etudiant.genre_etu}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.email_etu}</td>
                            <td class="px-4 py-2 whitespace-nowrap text-center text-sm">${etudiant.promotion_etu}</td>
                    `;

                    if (canEdit) {
                        html += `
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&num_etu=${etudiant.num_carte_etud}"
                                    class="text-blue-600 hover:text-blue-900 mr-2" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button onclick="deleteStudent('${etudiant.num_carte_etud}', '${etudiant.nom_etu} ${etudiant.prenom_etu}')"
                                    class="text-red-600 hover:text-red-900" title="Supprimer">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        `;
                    }

                    html += `</tr>`;
                });
                document.getElementById('usersTableBody').innerHTML = html;
                updateSelectedCount();
            }
        });

        // Fonction pour exporter en CSV
        function exporterListe() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const allEtudiants = <?= json_encode($allEtudiants) ?>;

            // Filtrer les étudiants si une recherche est active
            let etudiantsToExport = allEtudiants;
            if (searchTerm) {
                etudiantsToExport = allEtudiants.filter(etudiant => {
                    const nom = etudiant.nom_etu.toLowerCase();
                    const prenom = etudiant.prenom_etu.toLowerCase();
                    const num = etudiant.num_carte_etud.toLowerCase();
                    return nom.includes(searchTerm) || prenom.includes(searchTerm) || num.includes(searchTerm);
                });
            }

            // Créer le contenu CSV
            let csvContent = "data:text/csv;charset=utf-8,\uFEFF";

            // Ajouter les en-têtes
            csvContent += "N° Etudiant,Identifiant MESRS,Nom,Prénom,Date Nais.,Genre,Email,Niveau,Promotion,Année Acad.\n";

            // Ajouter les données
            etudiantsToExport.forEach(etudiant => {
                // Formater l'année académique
                let anneeAcad = '';
                if (etudiant.date_deb && etudiant.date_fin) {
                    const debut = new Date(etudiant.date_deb).getFullYear();
                    const fin = new Date(etudiant.date_fin).getFullYear();
                    anneeAcad = `${debut}-${fin}`;
                }

                const row = [
                    etudiant.num_carte_etud,
                    etudiant.identifiant_mesrs || '',
                    etudiant.nom_etu,
                    etudiant.prenom_etu,
                    etudiant.date_naiss_etu,
                    etudiant.genre_etu,
                    etudiant.email_etu,
                    etudiant.lib_niv_etude || '',
                    etudiant.promotion_etu,
                    anneeAcad
                ].map(field => `"${field}"`).join(',');
                csvContent += row + '\n';
            });

            // Créer le lien de téléchargement
            const encodedUri = encodeURI(csvContent);
            const link = document.createElement('a');
            link.setAttribute('href', encodedUri);
            link.setAttribute('download', 'etudiants_' + new Date().toISOString().split('T')[0] + '.csv');
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        }

        // Fonction pour imprimer
        function imprimerListe() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const allEtudiants = <?= json_encode($allEtudiants) ?>;
            const printWindow = window.open('', '_blank');

            // Filtrer les étudiants si une recherche est active
            let etudiantsToPrint = allEtudiants;
            if (searchTerm) {
                etudiantsToPrint = allEtudiants.filter(etudiant => {
                    const nom = etudiant.nom_etu.toLowerCase();
                    const prenom = etudiant.prenom_etu.toLowerCase();
                    const num = etudiant.num_carte_etud.toLowerCase();
                    return nom.includes(searchTerm) || prenom.includes(searchTerm) || num.includes(searchTerm);
                });
            }

            // Créer le contenu HTML pour l'impression
            let html = `
            <html>
                <head>
                    <title>Liste des étudiants</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        h2 { text-align: center; margin-bottom: 20px; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; font-size: 11px; }
                        th, td { border: 1px solid #333; padding: 6px; text-align: center; }
                        th { background-color: #f0f0f0; font-weight: bold; }
                        @media print {
                            body { margin: 0; padding: 15px; }
                        }
                    </style>
                </head>
                <body>
                    <h2>LISTE DES ETUDIANTS</h2>
                    ${searchTerm ? `<p style="text-align: center;">Résultats de la recherche pour : "${searchTerm}"</p>` : ''}
                    <table>
                        <thead>
                            <tr>
                                <th>Identifiant MESRS</th>
                                <th>N° Etudiant</th>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Date Nais.</th>
                                <th>Genre</th>
                                <th>Email</th>
                                <th>Niveau</th>
                                <th>Promotion</th>
                                <th>Année Acad.</th>
                            </tr>
                        </thead>
                        <tbody>
        `;

            // Ajouter les données
            etudiantsToPrint.forEach(etudiant => {
                // Formater l'année académique
                let anneeAcad = '';
                if (etudiant.date_deb && etudiant.date_fin) {
                    const debut = new Date(etudiant.date_deb).getFullYear();
                    const fin = new Date(etudiant.date_fin).getFullYear();
                    anneeAcad = `${debut}-${fin}`;
                }

                html += `
                <tr>
                    <td>${etudiant.identifiant_mesrs || ''}</td>
                    <td>${etudiant.num_carte_etud}</td>
                    <td>${etudiant.nom_etu}</td>
                    <td>${etudiant.prenom_etu}</td>
                    <td>${etudiant.date_naiss_etu}</td>
                    <td>${etudiant.libelle_genre || etudiant.genre_etu}</td>
                    <td>${etudiant.email_etu}</td>
                    <td>${etudiant.lib_niv_etude || ''}</td>
                    <td>${etudiant.promotion_etu}</td>
                    <td>${anneeAcad}</td>
                </tr>
            `;
            });

            html += `
                        </tbody>
                    </table>
                    <script>
                        window.onload = function() {
                            window.print();
                        };
                    <\/script>
                </body>
            </html>
        `;

            printWindow.document.write(html);
            printWindow.document.close();
        }

        // Fonctions de gestion de sélection et suppression
        function updateSelectedCount() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            const count = checkboxes.length;
            const deleteBtn = document.getElementById('deleteSelectedBtn');
            const countSpan = document.getElementById('selectedCount');

            countSpan.textContent = count;

            if (count > 0) {
                deleteBtn.disabled = false;
                deleteBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                deleteBtn.classList.add('hover:bg-red-600');
            } else {
                deleteBtn.disabled = true;
                deleteBtn.classList.add('opacity-50', 'cursor-not-allowed');
                deleteBtn.classList.remove('hover:bg-red-600');
            }

            // Mettre à jour la checkbox "Sélectionner tout"
            const allCheckboxes = document.querySelectorAll('.student-checkbox');
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            if (selectAllCheckbox) {
                selectAllCheckbox.checked = allCheckboxes.length > 0 && count === allCheckboxes.length;
            }
        }

        function toggleSelectAll() {
            const selectAllCheckbox = document.getElementById('selectAllCheckbox');
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedCount();
        }

        function selectAll() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = true;
            });
            updateSelectedCount();
        }

        function deselectAll() {
            const checkboxes = document.querySelectorAll('.student-checkbox');
            checkboxes.forEach(checkbox => {
                checkbox.checked = false;
            });
            updateSelectedCount();
        }

        function deleteSelected() {
            const checkboxes = document.querySelectorAll('.student-checkbox:checked');
            if (checkboxes.length === 0) {
                alert('Veuillez sélectionner au moins un étudiant à supprimer.');
                return;
            }

            let names = [];
            checkboxes.forEach(cb => {
                names.push(cb.getAttribute('data-name'));
            });

            const confirmation = confirm(
                `Êtes-vous sûr de vouloir supprimer ${checkboxes.length} étudiant(s) ?\n\n` +
                names.join('\n')
            );

            if (confirmation) {
                // Créer un formulaire et le soumettre
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;

                checkboxes.forEach(cb => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'selected_ids[]';
                    input.value = cb.value;
                    form.appendChild(input);
                });

                document.body.appendChild(form);
                form.submit();
            }
        }

        function deleteStudent(numEtu, name) {
            const confirmation = confirm(
                `Êtes-vous sûr de vouloir supprimer l'étudiant :\n\n${name}\n(${numEtu})`
            );

            if (confirmation) {
                // Créer un formulaire et le soumettre
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = window.location.href;

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_ids[]';
                input.value = numEtu;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>

</html>
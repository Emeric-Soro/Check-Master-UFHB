<?php
// Utilisation des variables globales au lieu d'appeler directement la base de données
$etudiantsNonInscrits = isset($GLOBALS['etudiantsNonInscrits']) ? $GLOBALS['etudiantsNonInscrits'] : [];
$niveaux = isset($GLOBALS['niveaux']) ? $GLOBALS['niveaux'] : [];
$etudiantsInscrits = isset($GLOBALS['etudiantsInscrits']) ? $GLOBALS['etudiantsInscrits'] : [];
$listeAnnees = isset($GLOBALS['listeAnnees']) ? $GLOBALS['listeAnnees'] : [];
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription des étudiants</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#2c3e50',
                    secondary: '#34495e',
                    accent: '#3498db',
                    success: '#27ae60',
                    danger: '#e74c3c',
                    warning: '#f1c40f',
                }
            }
        }
    }
    </script>
    <style>
    /* Style pour les sections */
    .section-container {
        border: 1px solid #e2e8f0;
        border-radius: 0.5rem;
        background-color: #DFF2FF;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        transition: all 0.3s ease-in-out;
    }

    .section-container:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
    }

    /* Style pour les champs en lecture seule */
    .form-control[readonly] {
        background-color: #f1f5f9;
        border-color: #cbd5e1 !important;
    }

    /* Style pour les selects */
    select.form-control {
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 20 20'%3e%3cpath stroke='%2310b981' stroke-linecap='round' stroke-linejoin='round' stroke-width='1.5' d='M6 8l4 4 4-4'/%3e%3c/svg%3e");
        background-position: right 0.5rem center;
        background-repeat: no-repeat;
        background-size: 1.5em 1.5em;
    }

    /* Animation pour les icônes */
    .icon-animate {
        transition: all 0.3s ease-in-out;
    }

    .section-container:hover .icon-animate {
        transform: scale(1.1);
        color: #10b981;
    }

    /* Style pour les labels */
    .form-label {
        color: #d9d9d9;
        transition: all 0.3s ease-in-out;
    }

    .section-container:hover .form-label {
        color: #d9d9d9;
    }

    /* Animations pour les messages */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeOut {
        from {
            opacity: 1;
            transform: translateY(0);
        }

        to {
            opacity: 0;
            transform: translateY(-10px);
        }
    }

    .animate-fade-in {
        animation: fadeIn 0.5s ease-out forwards;
    }

    .animate-fade-out {
        animation: fadeOut 0.5s ease-out forwards;
    }

    /* Styles pour la pagination */
    .pagination {
        display: flex;
        justify-content: center;
        margin-top: 1rem;
    }

    .pagination button {
        margin: 0 0.25rem;
        padding: 0.5rem 1rem;
        border: 1px solid #e2e8f0;
        border-radius: 0.375rem;
        background-color: white;
        color: #4a5568;
        cursor: pointer;
        transition: all 0.2s;
    }

    .pagination button:hover {
        background-color: #DFF2FF;
        border-color: #cbd5e0;
    }

    .pagination button.active {
        background-color: #4299e1;
        color: white;
        border-color: #4299e1;
    }

    .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }

    /* Styles pour l'impression */
    @media print {
        .no-print {
            display: none !important;
        }

        .print-only {
            display: block !important;
        }

        body {
            background: white;
        }

        .container {
            width: 100%;
            max-width: none;
            padding: 0;
            margin: 0;
        }
    }
    </style>
</head>

<body style="background-color: #DFF2FF;">
    <div class="container mx-auto px-4 py-8">
        <div id="messageContainer"></div>

        <?php if (isset($GLOBALS['messageSuccess']) && !empty($GLOBALS['messageSuccess'])): ?>
        <div id="successMessage"
            class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4 animate-fade-in"
            role="alert">
            <span class="block sm:inline"><?php echo $GLOBALS['messageSuccess']; ?></span>
        </div>
        <?php unset($GLOBALS['messageSuccess']); ?>
        <?php endif; ?>

        <?php if (isset($GLOBALS['messageErreur']) && !empty($GLOBALS['messageErreur'])): ?>
        <div id="errorMessage"
            class="bg-green-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4 animate-fade-in"
            role="alert">
            <span class="block sm:inline"><?php echo $GLOBALS['messageErreur']; ?></span>
        </div>
        <?php unset($GLOBALS['messageErreur']); ?>
        <?php endif; ?>

        <!-- Formulaire d'inscription -->
        <div class="bg-white rounded-lg shadow-sm mb-8 print:hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h5 class="text-lg font-semibold text-green-600 flex items-center">
                    <i class="fas fa-user-plus mr-2"></i>Nouvelle inscription
                </h5>
            </div>
            <div class="p-6">
                <form id="inscriptionForm" method="POST" action="?page=gestion_etudiants&action=inscrire_des_etudiants">
                    <input type="hidden" name="modalAction"
                        value="<?php echo isset($GLOBALS['inscriptionAModifier']) ? 'modifier' : 'inscrire'; ?>">
                    <?php if (isset($GLOBALS['inscriptionAModifier'])): ?>
                    <input type="hidden" name="id_inscription"
                        value="<?php echo $GLOBALS['inscriptionAModifier']['id_inscription']; ?>">
                    <?php endif; ?>
                    <!-- Hidden montant_paye (somme des paiements à ce jour) -->
                    <input type="hidden" id="montant_paye" name="montant_paye" value="<?php echo isset($GLOBALS['inscriptionAModifier']) ? (floatval($GLOBALS['inscriptionAModifier']['montant_paye']) ?? 0) : 0; ?>">
                    <!-- Section Année académique -->
                    <div
                        class="mb-8 border border-gray-200 rounded-lg bg-gray-50 p-6 transition-all duration-300 ease-in-out hover:shadow-md">
                        <h6 class="text-sm font-semibold text-green-600 mb-4 flex items-center">
                            <i
                                class="fas fa-calendar-alt mr-2 transition-all duration-300 ease-in-out hover:scale-110 hover:text-green-500"></i>Année
                            académique
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label for="annee_academique"
                                    class="block text-sm font-medium text-gray-600 mb-1">Sélectionner l'année
                                    académique</label>
                                <select
                                    class="w-full h-10 border border-gray-300 rounded-md transition-all duration-300 ease-in-out outline-none focus:border-green-500 focus:shadow-sm hover:-translate-y-0.5"
                                    id="annee_academique" name="annee_academique" required>
                                    <option value="">Choisir une année académique...</option>
                                    <?php foreach ($listeAnnees as $annee): ?>
                                    <option value="<?php echo $annee->id_annee_acad; ?>"
                                        <?php echo (isset($GLOBALS['inscriptionAModifier']) && $GLOBALS['inscriptionAModifier']['id_annee_acad'] == $annee->id_annee_acad) ? 'selected' : ''; ?>>
                                        <?php echo date('Y', strtotime($annee->date_deb)) . ' - ' . date('Y', strtotime($annee->date_fin)); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <!-- Section Informations étudiant -->
                    <div
                        class="mb-8 border border-gray-200 rounded-lg bg-gray-50 p-6 transition-all duration-300 ease-in-out hover:shadow-md">
                        <h6 class="text-sm font-semibold text-green-600 mb-4 flex items-center">
                            <i
                                class="fas fa-user-graduate mr-2 transition-all duration-300 ease-in-out hover:scale-110 hover:text-green-500"></i>Informations
                            étudiant
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div class="col-span-2">
                                <label for="etudiant" class="block text-sm font-medium text-gray-600 mb-1">Sélectionner
                                    un étudiant</label>
                                <select
                                    class="w-full h-10 border border-gray-300 rounded-md transition-all duration-300 ease-in-out outline-none focus:border-green-500 focus:shadow-sm hover:-translate-y-0.5"
                                    id="etudiant" name="etudiant"
                                    <?php echo (isset($GLOBALS['inscriptionAModifier']) && isset($_GET['id'])) ? '' : 'required'; ?>>
                                    <option value="">Choisir un étudiant...</option>
                                    <?php foreach ($etudiantsNonInscrits as $etudiant): ?>
                                    <option value="<?php echo $etudiant['num_etu']; ?>"
                                        <?php echo (isset($GLOBALS['inscriptionAModifier']) && $GLOBALS['inscriptionAModifier']['id_etudiant'] == $etudiant['num_etu']) ? 'selected' : ''; ?>>
                                        <?php echo $etudiant['num_etu'] . ' - ' . $etudiant['nom_etu'] . ' ' . $etudiant['prenom_etu']; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label for="niveau" class="block text-sm font-medium text-gray-600 mb-1">Niveau
                                    d'études</label>
                                <select
                                    class="w-full h-10 border border-gray-300 rounded-md transition-all duration-300 ease-in-out outline-none focus:border-green-500 focus:shadow-sm hover:-translate-y-0.5"
                                    id="niveau" name="niveau" required>
                                    <option value="">Choisir un niveau...</option>
                                    <?php foreach ($niveaux as $niveau): ?>
                                    <option value="<?php echo $niveau['id_niv_etude']; ?>"
                                        data-montant-total="<?php echo $niveau['montant_scolarite']; ?>"
                                        data-montant-inscription="<?php echo $niveau['montant_inscription']; ?>"
                                        <?php echo (isset($GLOBALS['inscriptionAModifier']) && $GLOBALS['inscriptionAModifier']['id_niveau'] == $niveau['id_niv_etude']) ? 'selected' : ''; ?>>
                                        <?php echo $niveau['lib_niv_etude']; ?> -
                                        <?php echo number_format($niveau['montant_scolarite'], 2); ?> FCFA
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Section Détails étudiant -->
                    <div
                        class="mb-8 border border-gray-200 rounded-lg bg-gray-50 p-6 transition-all duration-300 ease-in-out hover:shadow-md">
                        <h6 class="text-sm font-semibold text-green-600 mb-4 flex items-center">
                            <i
                                class="fas fa-id-card mr-2 transition-all duration-300 ease-in-out hover:scale-110 hover:text-green-500"></i>Détails
                            étudiant
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Numéro étudiant</label>
                                <input type="text" required
                                    class="w-full h-10 border pl-3 border-gray-300 rounded-md bg-gray-100 transition-all duration-300 ease-in-out outline-none"
                                    id="num_etu" name="num_etu"
                                    value="<?php echo isset($GLOBALS['etudiantInfo']['num_etu']) ? htmlspecialchars($GLOBALS['etudiantInfo']['num_etu']) : ''; ?>"
                                    readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Nom</label>
                                <input type="text"
                                    class="w-full pl-3 h-10 border border-gray-300 rounded-md bg-gray-100 transition-all duration-300 ease-in-out outline-none"
                                    id="nom_etu" name="nom_etu" required
                                    value="<?php echo isset($GLOBALS['etudiantInfo']['nom_etu']) ? htmlspecialchars($GLOBALS['etudiantInfo']['nom_etu']) : ''; ?>"
                                    readonly>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-600 mb-1">Prénom</label>
                                <input type="text"
                                    class="w-full pl-3 h-10 border border-gray-300 rounded-md bg-gray-100 transition-all duration-300 ease-in-out outline-none"
                                    id="prenom_etu" name="prenom_etu" required
                                    value="<?php echo isset($GLOBALS['etudiantInfo']['prenom_etu']) ? htmlspecialchars($GLOBALS['etudiantInfo']['prenom_etu']) : ''; ?>"
                                    readonly>
                            </div>
                        </div>
                    </div>

                    <!-- Section Paiement -->
                    <div class="mb-8">
                        <h6 class="text-sm font-semibold text-green-600 mb-4 flex items-center">
                            <i
                                class="fas fa-money-bill-wave mr-2 transition-all duration-300 ease-in-out hover:scale-110 hover:text-green-500"></i>Détails
                            du paiement
                        </h6>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div
                                class="border border-gray-200 rounded-lg bg-gray-50 p-6 transition-all duration-300 ease-in-out hover:shadow-md">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Montant total de la
                                        scolarité</label>
                                    <input type="text"
                                        class="w-full pl-3 h-10 border border-gray-300 rounded-md bg-gray-100 transition-all duration-300 ease-in-out outline-none"
                                        id="montant_total" readonly
                                        value="<?php echo isset($GLOBALS['inscriptionAModifier']) ? number_format($GLOBALS['inscriptionAModifier']['montant_total'] ?? $GLOBALS['inscriptionAModifier']['montant_scolarite'] ?? 0, 0, ',', ' ') : ''; ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Premier
                                        versement</label>
                                    <input type="number"
                                        class="w-full pl-3 h-10 border border-gray-300 rounded-md transition-all duration-300 ease-in-out outline-none focus:border-green-500 focus:shadow-sm hover:-translate-y-0.5"
                                        id="premier_versement" name="premier_versement" required
                                        value="<?php echo isset($GLOBALS['inscriptionAModifier']) ? $GLOBALS['inscriptionAModifier']['montant_premier_versement'] : ''; ?>">
                                </div>
                            </div>
                            <div
                                class="border border-gray-200 rounded-lg bg-gray-50 p-6 transition-all duration-300 ease-in-out hover:shadow-md">
                                <div class="mb-4">
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Reste à payer</label>
                                    <input type="text"
                                        class="w-full pl-3 h-10 border border-gray-300 rounded-md bg-gray-100 transition-all duration-300 ease-in-out outline-none"
                                        id="reste_payer" name="reste_payer" readonly
                                        value="<?php echo isset($GLOBALS['inscriptionAModifier']) ? number_format($GLOBALS['inscriptionAModifier']['reste_a_payer'] ?? 0, 0, ',', ' ') : ''; ?>">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Nombre de
                                        tranches</label>
                                    <select
                                        class="w-full h-10 border border-gray-300 rounded-md transition-all duration-300 ease-in-out outline-none focus:border-green-500 focus:shadow-sm hover:-translate-y-0.5"
                                        id="nombre_tranches" name="nombre_tranches">
                                        <option value="1"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && $GLOBALS['inscriptionAModifier']['nombre_tranche'] == 1) ? 'selected' : ''; ?>>
                                            1 tranche</option>
                                        <option value="2"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && $GLOBALS['inscriptionAModifier']['nombre_tranche'] == 2) ? 'selected' : ''; ?>>
                                            2 tranches</option>
                                        <option value="3"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && $GLOBALS['inscriptionAModifier']['nombre_tranche'] == 3) ? 'selected' : ''; ?>>
                                            3 tranches</option>
                                        <option value="4"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && $GLOBALS['inscriptionAModifier']['nombre_tranche'] == 4) ? 'selected' : ''; ?>>
                                            4 tranches</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-600 mb-1">Méthode de
                                        paiement</label>
                                    <select
                                        class="w-full h-10 border border-gray-300 rounded-md transition-all duration-300 ease-in-out outline-none focus:border-green-500 focus:shadow-sm hover:-translate-y-0.5"
                                        id="methode_paiement" name="methode_paiement" required>
                                        <option value="">Sélectionner une méthode de paiement</option>
                                        <option value="Espèce"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && isset($GLOBALS['inscriptionAModifier']['methode_paiement']) && $GLOBALS['inscriptionAModifier']['methode_paiement'] == 'Espèce') ? 'selected' : ''; ?>>
                                            Espèce</option>
                                        <option value="Carte bancaire"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && isset($GLOBALS['inscriptionAModifier']['methode_paiement']) && $GLOBALS['inscriptionAModifier']['methode_paiement'] == 'Carte bancaire') ? 'selected' : ''; ?>>
                                            Carte bancaire</option>
                                        <option value="Virement"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && isset($GLOBALS['inscriptionAModifier']['methode_paiement']) && $GLOBALS['inscriptionAModifier']['methode_paiement'] == 'Virement') ? 'selected' : ''; ?>>
                                            Virement</option>
                                        <option value="Chèque"
                                            <?php echo (isset($GLOBALS['inscriptionAModifier']) && isset($GLOBALS['inscriptionAModifier']['methode_paiement']) && $GLOBALS['inscriptionAModifier']['methode_paiement'] == 'Chèque') ? 'selected' : ''; ?>>
                                            Chèque</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php if(isset($GLOBALS['inscriptionAModifier'])) : ?>
                    <div class="flex justify-between">
                        <button type="button" name="btn_annuler_insciption" id="btnAnnuler"
                            onclick="window.location.href='?page=gestion_etudiants&action=inscrire_des_etudiants'"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-500 hover:bg-red-500/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200">
                            <i class="fas fa-times mr-2"></i>Annuler
                        </button>

                        <button type="submit" name="btn_modifier_insciption" id="edit_inscription"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-500/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                            <i class="fas fa-save mr-2"></i>Modifier l'inscription
                        </button>
                    </div>

                    <?php else : ?>
                    <div class="flex justify-between">
                        <div>
                        </div>
                        <button type="submit" name="btn_add_insciption" id="add_inscription"
                            class="inline-flex items-center px-6 py-2.5 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-500 hover:bg-blue-500/90 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                            <i class="fas fa-save mr-2"></i>Enregistrer l'inscription
                        </button>
                    </div>
                    <?php endif ?>

                </form>
            </div>
        </div>

        <!-- Liste des étudiants inscrits -->
        <div class="bg-white rounded-lg shadow-sm">
            <div class="border-b border-gray-200 px-6 py-4">
                <div class="flex justify-between items-center">
                    <h5 class="text-lg font-semibold text-green-600 flex items-center">
                        <i class="fas fa-users mr-2"></i>Étudiants inscrits
                    </h5>
                </div>
            </div>
            <div class="p-6">
                <!-- Barre d'outils -->
                <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between no-print">
                    <!-- Barre de recherche -->
                    <div class="relative w-full sm:w-96">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400"></i>
                        </div>
                        <input type="text" id="searchInput"
                            class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500  focus:outline-green-500 focus:ring-2 focus:ring-green-500 focus:border-green-500 sm:text-sm"
                            placeholder="Rechercher un étudiant...">
                    </div>

                    <!-- Boutons d'action -->
                    <div class="flex flex-wrap gap-2">
                        <button onclick="exportToExcel()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-emerald-600 hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 transition-all duration-200">
                            <i class="fas fa-file-excel mr-2"></i>Excel
                        </button>

                        <button onclick="printTable()"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                            <i class="fas fa-print mr-2"></i>Imprimer
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto rounded-lg border border-gray-200">
                    <table id="inscriptionsTable" class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gradient-to-r from-green-500 to-green-600">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Numéro</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Nom</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Niveau</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Année académique</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Date d'inscription</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-white uppercase tracking-wider">
                                    Statut</th>
                                <th
                                    class="px-6 py-3 text-center text-xs font-medium text-white uppercase tracking-wider">
                                    Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($etudiantsInscrits as $inscrit): ?>
                            <tr class="hover:bg-gray-50 transition-colors duration-150">
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $inscrit['id_etudiant']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $inscrit['nom'] . ' ' . $inscrit['prenom']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo $inscrit['nom_niveau']; ?></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('Y', strtotime($inscrit['date_deb'])) . ' - ' . date('Y', strtotime($inscrit['date_fin'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo date('d/m/Y', strtotime($inscrit['date_inscription'])); ?></td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span
                                        class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $inscrit['statut_inscription'] === 'Validée' ? 'bg-emerald-100 text-emerald-800' : 'bg-amber-100 text-amber-800'; ?>">
                                        <?php echo $inscrit['statut_inscription']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-center">
                                    <div class="flex items-center justify-center space-x-2">
                                        <button onclick="modifierInscription(<?php echo $inscrit['id_inscription']; ?>)"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200">
                                            <i class="fas fa-edit mr-1"></i>
                                        </button>
                                        <button
                                            onclick="supprimerInscription(<?php echo $inscrit['id_inscription']; ?>)"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-red-500 focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200">
                                            <i class="fas fa-trash-alt mr-1"></i>
                                        </button>
                                        <?php
                                        // Déterminer l'identifiant à envoyer au script d'impression :
                                        // préférer l'id du PREMIER versement si disponible (reçu du premier versement),
                                        // sinon utiliser le last_versement_id, sinon l'id de l'inscription
                                        if (!empty($inscrit['first_versement_id'])) {
                                            $printId = $inscrit['first_versement_id'];
                                            $isVersement = true;
                                        } elseif (!empty($inscrit['last_versement_id'])) {
                                            $printId = $inscrit['last_versement_id'];
                                            $isVersement = true;
                                        } else {
                                            $printId = $inscrit['id_inscription'];
                                            $isVersement = false;
                                        }
                                        ?>
                                        <button onclick="imprimerRecu(<?php echo $printId; ?>, <?php echo $isVersement ? 'true' : 'false'; ?>, <?php echo $inscrit['id_inscription']; ?>)"
                                            class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-md text-green-500 focus:outline-none focus:ring-2 focus:ring-offset-2  transition-all duration-200">
                                            <i class="fas fa-print mr-1"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination mt-4 no-print">
                    <button id="prevPage" class="px-3 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                        <i class="fas fa-chevron-left"></i>
                    </button>
                    <div id="pageNumbers" class="flex space-x-2"></div>
                    <button id="nextPage" class="px-3 py-1 rounded-md bg-gray-100 text-gray-700 hover:bg-gray-200">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmation de suppression -->
    <div id="deleteModal" class="fixed inset-0 hidden overflow-y-auto h-full w-full top-16">
        <div class="relative top-16 mx-auto p-5 w-96 shadow-2xl rounded-md bg-white">
            <div class="mt-3 text-center">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h3 class="text-lg leading-6 font-medium text-gray-900 mt-4">Confirmer la suppression</h3>
                <div class="mt-2 px-7 py-3">
                    <p class="text-sm text-gray-500">
                        Êtes-vous sûr de vouloir supprimer cette inscription ? Cette action est irréversible.
                    </p>
                </div>
                <div class="flex justify-center space-x-4 mt-4">
                    <button id="cancelDelete"
                        class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500">
                        Annuler
                    </button>
                    <button id="confirmDelete"
                        class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                        Supprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // Variables globales pour la pagination
    const itemsPerPage = 10;
    let currentPage = 1;
    let filteredData = [];
    let allData = [];

    // Variables pour la modale de suppression
    let inscriptionToDelete = null;
    const deleteModal = document.getElementById('deleteModal');
    const cancelDelete = document.getElementById('cancelDelete');
    const confirmDelete = document.getElementById('confirmDelete');

    // Fonction d'export Excel
    window.exportToExcel = function() {
        const table = document.getElementById('inscriptionsTable');
        const searchInput = document.getElementById('searchInput');
        const dataToExport = searchInput.value.trim() === '' ? allData : filteredData;

        let csvContent = "data:text/csv;charset=utf-8,";

        // En-têtes (exclure la dernière colonne Actions)
        const headers = Array.from(table.querySelectorAll('thead th'))
            .slice(0, -1) // Exclure la dernière colonne
            .map(th => th.textContent.trim());
        csvContent += headers.join(",") + "\r\n";

        // Données (exclure la dernière colonne Actions)
        dataToExport.forEach(item => {
            const rowData = item.data
                .slice(0, -1) // Exclure la dernière colonne (Actions)
                .map(cell => `"${cell}"`)
                .join(",");
            csvContent += rowData + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "inscriptions.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    };

    // Fonction d'impression
    window.printTable = function() {
        const printWindow = window.open('', '_blank');
        const table = document.getElementById('inscriptionsTable');
        const searchInput = document.getElementById('searchInput');
        const dataToPrint = searchInput.value.trim() === '' ? allData : filteredData;

        // Créer une copie de la table sans la colonne Actions
        const tableClone = table.cloneNode(true);
        const headers = tableClone.querySelectorAll('thead th');
        const lastHeader = headers[headers.length - 1];
        lastHeader.parentNode.removeChild(lastHeader);

        // Supprimer toutes les lignes existantes
        const tbody = tableClone.querySelector('tbody');
        tbody.innerHTML = '';

        // Ajouter les lignes de données
        dataToPrint.forEach(item => {
            const newRow = document.createElement('tr');
            item.data.slice(0, -1).forEach(cellData => {
                const cell = document.createElement('td');
                cell.textContent = cellData;
                newRow.appendChild(cell);
            });
            tbody.appendChild(newRow);
        });

        printWindow.document.write(`
            <html>
                <head>
                    <title>Liste des inscriptions</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .no-print { display: none; }
                    </style>
                </head>
                <body>
                    <h2>Liste des inscriptions</h2>
                    ${tableClone.outerHTML}
                </body>
            </html>
        `);
        printWindow.document.close();
        printWindow.print();
        printWindow.focus();
        printWindow.close();
    };

    document.addEventListener('DOMContentLoaded', function() {
        const etudiantSelect = document.getElementById('etudiant');
        const niveauSelect = document.getElementById('niveau');
        const premierVersementInput = document.getElementById('premier_versement');
        const nombreTranchesSelect = document.getElementById('nombre_tranches');
        const montantTotalInput = document.getElementById('montant_total');
        const restePayerInput = document.getElementById('reste_payer');

        // Gestion de la sélection d'un étudiant
        etudiantSelect.addEventListener('change', function() {
            const numEtu = this.value;
            if (numEtu) {
                // Récupérer les informations de l'étudiant sélectionné directement depuis l'option
                const selectedOption = this.options[this.selectedIndex];
                const etudiantInfo = selectedOption.textContent.split(' - ');

                // Extraire le numéro, nom et prénom
                const numEtu = etudiantInfo[0];
                const nomPrenom = etudiantInfo[1].split(' ');
                const nom = nomPrenom[0];
                const prenom = nomPrenom[1];

                // Remplir les champs
                document.getElementById('num_etu').value = numEtu;
                document.getElementById('nom_etu').value = nom;
                document.getElementById('prenom_etu').value = prenom;
            } else {
                // Réinitialiser les champs si aucun étudiant n'est sélectionné
                document.getElementById('num_etu').value = '';
                document.getElementById('nom_etu').value = '';
                document.getElementById('prenom_etu').value = '';
            }
        });

        // Fonction pour formater les montants
        function formaterMontant(montant) {
            return new Intl.NumberFormat('fr-FR').format(montant);
        }

        // Fonction pour calculer le reste à payer
        // Priorité : utiliser montant_paye (somme des paiements effectués jusqu'à aujourd'hui)
        // Si montant_paye n'est pas disponible côté client, retomber sur le calcul à partir du premier versement
        function calculerResteAPayer() {
            const montantTotal = parseFloat(montantTotalInput.value.replace(/\s/g, '')) || 0;
            // essayer de récupérer montant_paye depuis :
            // 1) champ hidden #montant_paye (rempli côté serveur si édition)
            // 2) dataset de l'option etudiant sélectionnée
            let montantPayeDataset = 0;
            try {
                const hiddenMontantPaye = document.getElementById('montant_paye');
                if (hiddenMontantPaye && hiddenMontantPaye.value) {
                    montantPayeDataset = parseFloat(hiddenMontantPaye.value) || 0;
                } else {
                    const selectedEtudiant = document.getElementById('etudiant');
                    if (selectedEtudiant && selectedEtudiant.options[selectedEtudiant.selectedIndex]) {
                        montantPayeDataset = parseFloat(selectedEtudiant.options[selectedEtudiant.selectedIndex].dataset.montantPaye) || 0;
                    }
                }
            } catch (err) {
                montantPayeDataset = 0;
            }

            const premierVersement = parseFloat(premierVersementInput.value) || 0;

            let reste = 0;
            if (montantPayeDataset > 0) {
                // reste = montant total - montant déjà payé (sommes passées)
                reste = montantTotal - montantPayeDataset;
            } else {
                // fallback : reste = montant total - premier versement
                reste = montantTotal - premierVersement;
            }

            if (reste < 0) reste = 0;
            restePayerInput.value = formaterMontant(reste);
        }

        // Événement lors du changement de niveau
        niveauSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const montantTotal = selectedOption.getAttribute('data-montant-total');
            const montantInscription = selectedOption.getAttribute('data-montant-inscription');

            montantTotalInput.value = formaterMontant(montantTotal);
            premierVersementInput.value = montantInscription;
            calculerResteAPayer();
        });

        // Événements pour le calcul automatique
        premierVersementInput.addEventListener('input', calculerResteAPayer);
        nombreTranchesSelect.addEventListener('change', calculerResteAPayer);

        // Initialisation des calculs si un niveau est déjà sélectionné
        if (niveauSelect.value) {
            const event = new Event('change');
            niveauSelect.dispatchEvent(event);
        }

        // Validation du formulaire
        document.getElementById('inscriptionForm').addEventListener('submit', function(e) {
            const montantTotal = parseFloat(niveauSelect.options[niveauSelect.selectedIndex].dataset
                .montantTotal);
            const premierVersement = parseFloat(premierVersementInput.value);

            if (premierVersement > montantTotal) {
                e.preventDefault();
                alert("Le premier versement ne peut pas être supérieur au montant total.");
                return;
            }

            // Si la validation est passée, le formulaire sera soumis normalement
        });

        // Fonction pour initialiser les données
        function initializeData() {
            const table = document.getElementById('inscriptionsTable');
            const rows = Array.from(table.querySelectorAll('tbody tr'));
            allData = rows.map(row => ({
                element: row,
                data: Array.from(row.cells).map(cell => cell.textContent.trim())
            }));
            filteredData = [...allData];
            updateTable();
        }

        // Fonction pour mettre à jour la table
        function updateTable() {
            const start = (currentPage - 1) * itemsPerPage;
            const end = start + itemsPerPage;
            const paginatedData = filteredData.slice(start, end);

            // Cacher toutes les lignes
            allData.forEach(item => item.element.style.display = 'none');

            // Afficher les lignes de la page courante
            paginatedData.forEach(item => item.element.style.display = '');

            updatePagination();
        }

        // Fonction pour mettre à jour la pagination
        function updatePagination() {
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            const pageNumbers = document.getElementById('pageNumbers');
            pageNumbers.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const button = document.createElement('button');
                button.textContent = i;
                button.className =
                    `px-3 py-1 rounded-md ${i === currentPage ? 'bg-blue-500 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'}`;
                button.onclick = () => {
                    currentPage = i;
                    updateTable();
                };
                pageNumbers.appendChild(button);
            }

            document.getElementById('prevPage').disabled = currentPage === 1;
            document.getElementById('nextPage').disabled = currentPage === totalPages;
        }

        // Gestionnaires d'événements pour la pagination
        document.getElementById('prevPage').addEventListener('click', () => {
            if (currentPage > 1) {
                currentPage--;
                updateTable();
            }
        });

        document.getElementById('nextPage').addEventListener('click', () => {
            const totalPages = Math.ceil(filteredData.length / itemsPerPage);
            if (currentPage < totalPages) {
                currentPage++;
                updateTable();
            }
        });

        // Fonction de recherche
        document.getElementById('searchInput').addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            filteredData = allData.filter(item =>
                item.data.some(cell => cell.toLowerCase().includes(searchTerm))
            );
            currentPage = 1;
            updateTable();
        });


        // Fonction pour modifier une inscription
        window.modifierInscription = function(idInscription) {
            window.location.href =
                `?page=gestion_etudiants&action=inscrire_des_etudiants&modalAction=modifier&id=${idInscription}`;
        };


        // Gestion des messages
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');

        function showMessage(element) {
            if (element) {
                element.classList.remove('hidden');
                element.classList.add('animate-fade-in');
                setTimeout(() => {
                    element.classList.add('animate-fade-out');
                    setTimeout(() => {
                        element.classList.add('hidden');
                        element.classList.remove('animate-fade-in',
                            'animate-fade-out');
                    }, 500);
                }, 3000);
            }
        }

        // Afficher les messages avec un délai
        setTimeout(() => {
            showMessage(successMessage);
            showMessage(errorMessage);
        }, 500);

        // Initialiser les données
        initializeData();
    });

    // Fonction pour imprimer un reçu individuel
    // Signature compatible : imprimerRecu(id) or imprimerRecu(id, isVersement, idInscription)
    window.imprimerRecu = function(id, isVersement, idInscription) {
        // Si l'appel ne fournit qu'un seul argument, on l'interprète comme l'id à transmettre
        const targetId = (typeof id !== 'undefined') ? id : '';

        // Ouvrir la page d'impression. Le script serveur sait interpréter l'id
        // comme id_versement ou id_inscription selon le contexte.
        window.open(`?page=gestion_scolarite&action=imprimer_recu&id=${encodeURIComponent(targetId)}`, '_blank');
    };

    // Fonction pour supprimer une inscription
    window.supprimerInscription = function(idInscription) {
        inscriptionToDelete = idInscription;
        deleteModal.classList.remove('hidden');
    };

    // Fermer la modale lors du clic sur Annuler
    cancelDelete.addEventListener('click', function() {
        deleteModal.classList.add('hidden');
        inscriptionToDelete = null;
    });

    // Confirmer la suppression
    confirmDelete.addEventListener('click', function() {
        if (inscriptionToDelete) {
            window.location.href =
                `?page=gestion_etudiants&action=inscrire_des_etudiants&modalAction=supprimer&id=${inscriptionToDelete}`;
        }
    });

    // Fermer la modale en cliquant en dehors
    deleteModal.addEventListener('click', function(e) {
        if (e.target === deleteModal) {
            deleteModal.classList.add('hidden');
            inscriptionToDelete = null;
        }
    });
    </script>
</body>

</html>
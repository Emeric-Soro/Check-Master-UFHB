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
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des étudiants</title>
</head>

<body style="background-color: #DFF2FF;">
    <div class="relative container mx-auto px-4 py-8">
        <!-- Système de notification -->
        <?php if (!empty($GLOBALS['messageSuccess'])): ?>
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

        <?php if (!empty($GLOBALS['messageErreur'])): ?>
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

        <!-- Formulaire fixe d'ajout/mise à jour étudiant -->
        <div class="bg-white shadow-lg rounded-lg overflow-hidden border-2 border-gray-300 mb-6">
            <div class="bg-white px-6 py-3 border-b-2 border-gray-300">
                <h2 class="text-xl font-bold text-gray-800 text-center">
                    <?php echo $etudiant_a_modifier ? 'MISE A JOUR ETUDIANT' : 'AJOUTER UN ETUDIANT'; ?>
                </h2>
            </div>
            
            <form id="userForm" class="p-6" method="post" action="?page=gestion_etudiants&action=ajouter_des_etudiants">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\CheckMaster\Core\Csrf::token()); ?>">
                <input type="hidden" id="num_etu" name="num_etu" value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->num_carte_etud) : ''; ?>">
                
                <!-- Première ligne: Niveau, Promotion, Année A. -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="id_niveau" class="block text-sm font-medium text-gray-700 mb-1">Niveau</label>
                        <select name="id_niveau" id="id_niveau"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                            <option value="">▼ Sélectionner un niveau</option>
                            <?php foreach ($listeNiveaux as $niveau): ?>
                                    <option value="<?php echo htmlspecialchars($niveau->id_niv_etude); ?>" 
                                        <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->id_niveau == $niveau->id_niv_etude) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($niveau->lib_niv_etude); ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label for="promotion_etu" class="block text-sm font-medium text-gray-700 mb-1">Promotion</label>
                        <input type="text" name="promotion_etu" id="promotion_etu"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->promotion_etu) : ''; ?>"
                            placeholder="Ex: M1, M2"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="id_annee_acad" class="block text-sm font-medium text-gray-700 mb-1">Année Académique</label>
                        <select name="id_annee_acad" id="id_annee_acad"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                            <option value="">▼ Sélectionner une année</option>
                            <?php foreach ($listeAnneesAcad as $annee): ?>
                                    <option value="<?php echo htmlspecialchars($annee->id_annee_acad); ?>" 
                                        <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->id_annee_acad == $annee->id_annee_acad) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars(date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin))); ?>
                                    </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <!-- Deuxième ligne: N° Étudiant, Identifiant MESRS, Nom -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <?php if ($etudiant_a_modifier): ?>
                        <input type="hidden" name="old_num_etu" value="<?php echo htmlspecialchars($etudiant_a_modifier->num_carte_etud); ?>">
                    <?php endif; ?>
                    <div>
                        <label for="num_etu" class="block text-sm font-medium text-gray-700 mb-1">
                            N° Étudiant <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="num_etu" id="num_etu" required maxlength="25"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->num_carte_etud) : ''; ?>"
                            placeholder="Ex: 20230001"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="identifiant_mesrs" class="block text-sm font-medium text-gray-700 mb-1">Identifiant MESRS</label>
                        <input type="text" name="identifiant_mesrs" id="identifiant_mesrs" maxlength="15"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->identifiant_mesrs ?? '') : ''; ?>"
                            placeholder="Identifiant"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="nom_etu" class="block text-sm font-medium text-gray-700 mb-1">Nom <span class="text-red-500">*</span></label>
                        <input type="text" name="nom_etu" id="nom_etu" required maxlength="30"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->nom_etu) : ''; ?>"
                            placeholder="Nom de l'étudiant"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Troisième ligne: Prénom, Genre, Date de naissance -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div>
                        <label for="prenom_etu" class="block text-sm font-medium text-gray-700 mb-1">Prénom <span class="text-red-500">*</span></label>
                        <input type="text" name="prenom_etu" id="prenom_etu" required maxlength="30"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->prenom_etu) : ''; ?>"
                            placeholder="Prénom de l'étudiant"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="genre_etu" class="block text-sm font-medium text-gray-700 mb-1">Genre <span class="text-red-500">*</span></label>
                        <select name="genre_etu" id="genre_etu" required
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
                            <option value="">▼</option>
                            <option value="1" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu == 1) ? 'selected' : ''; ?>>Masculin</option>
                            <option value="2" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu == 2) ? 'selected' : ''; ?>>Féminin</option>
                            <option value="3" <?php echo ($etudiant_a_modifier && $etudiant_a_modifier->genre_etu == 3) ? 'selected' : ''; ?>>Neutre</option>
                        </select>
                    </div>
                    <div>
                        <label for="date_naiss_etu" class="block text-sm font-medium text-gray-700 mb-1">Date de Naissance <span class="text-red-500">*</span></label>
                        <input type="date" name="date_naiss_etu" id="date_naiss_etu" required
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->date_naiss_etu) : ''; ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Quatrième ligne: Email -->
                <div class="grid grid-cols-1 gap-4 mb-4">
                    <div>
                        <label for="email_etu" class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                        <input type="email" name="email_etu" id="email_etu" required maxlength="50"
                            value="<?php echo $etudiant_a_modifier ? htmlspecialchars($etudiant_a_modifier->email_etu) : ''; ?>"
                            placeholder="email@example.com"
                            class="w-full px-3 py-2 border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end gap-3">
                    <?php if ($etudiant_a_modifier): ?>
                            <a href="?page=gestion_etudiants&action=ajouter_des_etudiants"
                                class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-2 px-6 rounded focus:outline-none focus:ring-2 focus:ring-gray-500">
                                Annuler
                            </a>
                    <?php endif; ?>
                    <button type="submit" name="<?php echo $etudiant_a_modifier ? 'submit_modifier_etudiant' : 'submit_add_etudiant'; ?>"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-8 rounded focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <?php echo $etudiant_a_modifier ? 'METTRE A JOUR' : 'AJOUTER'; ?>
                    </button>
                </div>
            </form>
        </div>

        <!-- Main Content -->
        <div class="bg-white shadow-card rounded-lg overflow-hidden border border-gray-200 mb-8">
            <!-- Dashboard Header -->
            <div class="bg-gradient-to-r from-green-600 to-green-800 px-6 py-4">
                <h2 class="text-xl font-bold text-white">Liste des étudiants</h2>
            </div>

            <!-- Action Bar for Table -->
            <div class="px-6 py-4 flex flex-col sm:flex-row justify-between items-center border-b border-gray-200">
                <div class="relative w-full sm:w-1/2 lg:w-1/3 mb-4 sm:mb-0">
                    <input type="text" id="searchInput" placeholder="Rechercher un étudiant..."
                        class="w-full px-4 py-2 pl-10 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-green-500 transition-all duration-200">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                        <i class="fas fa-search text-gray-400"></i>
                    </span>
                </div>
                <div class="flex flex-wrap gap-2 justify-center sm:justify-end">
                    <button onclick="exporterListe()"
                        class="bg-orange-500 hover:bg-orange-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-orange-500 focus:ring-opacity-50">
                        <i class="fas fa-file-export mr-2"></i>Exporter
                    </button>
                    <button onclick="imprimerListe()"
                        class="bg-blue-500 hover:bg-blue-600 text-white font-medium py-2 px-4 rounded-lg shadow transition-all duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-opacity-50">
                        <i class="fas fa-print mr-2"></i>Imprimer
                    </button>
                </div>
            </div>

            <!-- Users Table -->
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">N° Etudiant</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Identifiant MESRS</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Nom</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Prénom</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Date Nais.</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Genre</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200" id="usersTableBody">
                        <?php if (empty($currentPageItems)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-users text-gray-300 text-4xl mb-4"></i>
                                            <p>Aucun étudiant trouvé.</p>
                                        </div>
                                    </td>
                                </tr>
                        <?php else: ?>
                                <?php foreach ($currentPageItems as $etudiant): ?>
                                        <tr class="hover:bg-gray-50">
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->num_carte_etud); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->identifiant_mesrs ?? ''); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->nom_etu); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->prenom_etu); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->date_naiss_etu); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->libelle_genre ?? $etudiant->genre_etu); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->email_etu); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center"><?php echo htmlspecialchars($etudiant->promotion_etu); ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                                <?php if (canEdit()): ?>
                                                        <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&num_etu=<?php echo $etudiant->num_carte_etud; ?>"
                                                            class="text-blue-600 hover:text-blue-900 mr-3" title="Modifier">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                    <div class="bg-white rounded-lg shadow-sm p-4 mt-6">
                        <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                            <div class="text-sm text-gray-500">
                                Affichage de <?= $startIndex + 1 ?> à <?= min($startIndex + $itemsPerPage, $totalItems) ?> sur <?= $totalItems ?> entrées
                            </div>
                            <div class="flex flex-wrap justify-center gap-2">
                                <?php if ($currentPage > 1): ?>
                                        <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $currentPage - 1 ?><?= !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '' ?>"
                                            class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
                                            <i class="fas fa-chevron-left mr-1"></i>Précédent
                                        </a>
                                <?php endif; ?>

                                <?php
                                $start = max(1, $currentPage - 2);
                                $end = min($totalPages, $currentPage + 2);

                                if ($start > 1) {
                                    echo '<a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=1' . (!empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '') . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">1</a>';
                                    if ($start > 2) {
                                        echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                    }
                                }

                                for ($i = $start; $i <= $end; $i++):
                                    $searchParam = !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '';
                                    ?>
                                        <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $i ?><?= $searchParam ?>"
                                            class="px-3 py-2 <?= $i === $currentPage ? 'bg-green-600 text-white' : 'bg-white text-gray-700 hover:bg-gray-50' ?> border border-gray-300 rounded-lg text-sm font-medium">
                                            <?= $i ?>
                                        </a>
                                <?php endfor;

                                if ($end < $totalPages) {
                                    if ($end < $totalPages - 1) {
                                        echo '<span class="px-3 py-2 text-gray-500">...</span>';
                                    }
                                    $searchParam = !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '';
                                    echo '<a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=' . $totalPages . $searchParam . '" class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">' . $totalPages . '</a>';
                                }
                                ?>

                                <?php if ($currentPage < $totalPages): ?>
                                        <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&p=<?= $currentPage + 1 ?><?= !empty($GLOBALS['searchTerm']) ? '&search=' . urlencode($GLOBALS['searchTerm']) : '' ?>"
                                            class="px-3 py-2 bg-white border border-gray-300 rounded-lg text-sm font-medium text-gray-700 hover:bg-gray-50">
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
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-search text-gray-300 text-4xl mb-4"></i>
                                <p>Aucun étudiant ne correspond à votre recherche.</p>
                            </div>
                        </td>
                    </tr>
                `;
            } else {
                let html = '';
                filteredEtudiants.forEach(etudiant => {
                    html += `
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.num_carte_etud}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.identifiant_mesrs || ''}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.nom_etu}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.prenom_etu}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.date_naiss_etu}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.libelle_genre || etudiant.genre_etu}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.email_etu}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">${etudiant.promotion_etu}</td>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <a href="?page=gestion_etudiants&action=ajouter_des_etudiants&num_etu=${etudiant.num_carte_etud}"
                                    class="text-blue-600 hover:text-blue-900 mr-3" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    `;
                });
                document.getElementById('usersTableBody').innerHTML = html;
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
                                <th>N° Etudiant</th>
                                <th>Identifiant MESRS</th>
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
                    <td>${etudiant.num_carte_etud}</td>
                    <td>${etudiant.identifiant_mesrs || ''}</td>
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
    </script>
</body>

</html>

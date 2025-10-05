<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
$pdo = Database::getConnection();
$etudiantModel = new Etudiant($pdo);
$niveauModel = new NiveauEtude($pdo);
$niveaux = $niveauModel->getAllNiveauxEtudes();

// Paramètres de pagination
$itemsPerPage = 10;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;

// Filtres
$niveauFiltre = isset($_GET['niveau']) ? $_GET['niveau'] : '';
$searchFiltre = isset($_GET['search']) ? $_GET['search'] : '';

// Récupération des étudiants avec filtres
$etudiants = $etudiantModel->getAllListeEtudiants();

// Application des filtres
if ($niveauFiltre) {
    $etudiants = array_filter($etudiants, function($e) use ($niveauFiltre) {
        return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
    });
}

if ($searchFiltre) {
    $etudiants = array_filter($etudiants, function($e) use ($searchFiltre) {
        $search = strtolower($searchFiltre);
        return strpos(strtolower($e->nom_etu ?? ''), $search) !== false ||
               strpos(strtolower($e->prenom_etu ?? ''), $search) !== false ||
               strpos(strtolower($e->email_etu ?? ''), $search) !== false ||
               strpos(strtolower($e->lib_niv_etude ?? ''), $search) !== false;
    });
}

// Pagination
$totalItems = count($etudiants);
$totalPages = ceil($totalItems / $itemsPerPage);
$etudiants = array_slice($etudiants, $offset, $itemsPerPage);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des dossiers académiques</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="bg-gradient-to-br from-blue-50 to-green-50 min-h-screen font-sans">
    <div class="max-w-5xl mx-auto py-10 ">
        <h1 class="text-3xl font-bold text-green-700 mb-8 flex items-center gap-3">
            <i class="fas fa-folder-open text-green-400"></i> Dossiers académiques des étudiants
        </h1>

        <!-- Messages de succès/erreur en haut -->
        <?php
        if (isset($_GET['success']) && $_GET['success'] === '1') {
            echo '<div id="successMessage" class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded-lg flex items-center gap-2 transition-opacity duration-500">
                    <i class="fas fa-check-circle"></i>
                    <span class="font-semibold">Dossier enregistré avec succès !</span>
                  </div>';
        } elseif (isset($_GET['success']) && $_GET['success'] === '0') {
            echo '<div id="errorMessage" class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg flex items-center gap-2 transition-opacity duration-500">
                    <i class="fas fa-exclamation-circle"></i>
                    <span class="font-semibold">Erreur lors de l\'enregistrement du dossier.</span>
                  </div>';
        }
        ?>

        <div class="mb-6 flex flex-col md:flex-row md:items-center gap-4">
            <form method="get" class="flex gap-4 w-full">
                <input type="hidden" name="page" value="dossiers_academiques">
                <input type="text" name="search" id="searchInput" style="outline: none;"
                    placeholder="Rechercher par nom, email, niveau..."
                    value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                    class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg shadow-sm focus:ring-green-500 focus:border-green-500">
                <select name="niveau" style="outline: none;"
                    class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500">
                    <option value="">Tous les niveaux</option>
                    <?php foreach($niveaux as $niv): ?>
                    <option value="<?= htmlspecialchars($niv->id_niv_etude) ?>"
                        <?= $niveauFiltre == $niv->id_niv_etude ? 'selected' : '' ?>>
                        <?= htmlspecialchars($niv->lib_niv_etude) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded-lg">Filtrer</button>
            </form>
        </div>
        <div class="bg-white rounded-xl shadow-lg overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-green-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-700 uppercase tracking-wider">Nom
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Prénom</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Email</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Niveau</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Promotion</th>
                        <th class="px-6 py-3 text-center text-xs font-bold text-green-700 uppercase tracking-wider">
                            Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody" class="bg-white divide-y divide-gray-200">
                    <?php 
                    // Vérifier s'il y a des résultats totaux (pas juste sur la page courante)
                    $allEtudiants = $etudiantModel->getAllListeEtudiants();
                    if ($niveauFiltre) {
                        $allEtudiants = array_filter($allEtudiants, function($e) use ($niveauFiltre) {
                            return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
                        });
                    }
                    if ($searchFiltre) {
                        $allEtudiants = array_filter($allEtudiants, function($e) use ($searchFiltre) {
                            $search = strtolower($searchFiltre);
                            return strpos(strtolower($e->nom_etu ?? ''), $search) !== false ||
                                   strpos(strtolower($e->prenom_etu ?? ''), $search) !== false ||
                                   strpos(strtolower($e->email_etu ?? ''), $search) !== false ||
                                   strpos(strtolower($e->lib_niv_etude ?? ''), $search) !== false;
                        });
                    }
                    
                    if (empty($allEtudiants)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center">
                            <div class="flex flex-col items-center gap-3 text-gray-500">
                                <i class="fas fa-search text-4xl text-gray-300"></i>
                                <div class="text-lg font-medium">Aucun étudiant trouvé</div>
                                <div class="text-sm">Aucun résultat ne correspond à vos critères de recherche</div>
                                <?php if (!empty($_GET['search']) || !empty($_GET['niveau'])): ?>
                                <a href="?page=dossiers_academiques"
                                    class="text-green-600 hover:text-green-700 font-medium">
                                    <i class="fas fa-times mr-1"></i>Effacer les filtres
                                </a>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach($etudiants as $etu): ?>
                    <tr class="hover:bg-green-50 transition">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($etu->nom_etu ?? ''); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo htmlspecialchars($etu->prenom_etu ?? ''); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo htmlspecialchars($etu->email_etu ?? ''); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?php echo htmlspecialchars($etu->lib_niv_etude ?? ''); ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700"><?php
                            if (isset($etu->date_deb, $etu->date_fin)) {
                                echo htmlspecialchars(date('Y', strtotime($etu->date_deb)) . '-' . date('Y', strtotime($etu->date_fin)));
                            }
                        ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <button
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg shadow hover:bg-green-700 transition open-dossier-modal"
                                data-num-etu="<?= htmlspecialchars($etu->num_etu) ?>"
                                data-nom="<?= htmlspecialchars($etu->nom_etu . ' ' . $etu->prenom_etu) ?>">
                                <i class="fas fa-eye mr-2"></i> Visualiser le dossier
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-6 flex items-center justify-between">
            <div class="text-sm text-gray-700">
                Affichage de <?= $offset + 1 ?> à <?= min($offset + $itemsPerPage, $totalItems) ?> sur
                <?= $totalItems ?> étudiants
            </div>
            <div class="flex items-center gap-2">
                <?php if ($currentPage > 1): ?>
                <a href="?page=dossiers_academiques&p=<?= $currentPage - 1 ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    <i class="fas fa-chevron-left mr-1"></i>Précédent
                </a>
                <?php endif; ?>

                <?php
                $startPage = max(1, $currentPage - 2);
                $endPage = min($totalPages, $currentPage + 2);
                
                if ($startPage > 1): ?>
                <a href="?page=dossiers_academiques&p=1<?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">1</a>
                <?php if ($startPage > 2): ?>
                <span class="px-3 py-2 text-sm text-gray-500">...</span>
                <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                <a href="?page=dossiers_academiques&p=<?= $i ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                    class="px-3 py-2 text-sm font-medium <?= $i == $currentPage ? 'text-white bg-green-600 border-green-600' : 'text-gray-500 bg-white border-gray-300 hover:bg-gray-50' ?> border rounded-md">
                    <?= $i ?>
                </a>
                <?php endfor; ?>

                <?php if ($endPage < $totalPages): ?>
                <?php if ($endPage < $totalPages - 1): ?>
                <span class="px-3 py-2 text-sm text-gray-500">...</span>
                <?php endif; ?>
                <a href="?page=dossiers_academiques&p=<?= $totalPages ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50"><?= $totalPages ?></a>
                <?php endif; ?>

                <?php if ($currentPage < $totalPages): ?>
                <a href="?page=dossiers_academiques&p=<?= $currentPage + 1 ?><?= !empty($searchFiltre) ? '&search=' . urlencode($searchFiltre) : '' ?><?= !empty($niveauFiltre) ? '&niveau=' . urlencode($niveauFiltre) : '' ?>"
                    class="px-3 py-2 text-sm font-medium text-gray-500 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                    Suivant<i class="fas fa-chevron-right ml-1"></i>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <div id="dossierModal" class="fixed inset-0 z-50 flex items-center justify-center bg-opacity-40 hidden">
        <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl p-8 relative overflow-y-auto max-h-[90vh]">
            <button id="closeModalBtn" class="absolute top-4 left-4  text-gray-500 flex items-center justify-center"><i
                    class="fas fa-times text-lg"></i></button>
            <h2 class="text-2xl font-bold mb-6 text-green-700 text-center">Dossier académique de <span
                    id="modalNom"></span></h2>

            <!-- Indicateur de chargement -->
            <div id="loadingIndicator" class="hidden flex items-center justify-center py-8">
                <div class="flex items-center gap-3 text-green-600">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-green-600"></div>
                    <span class="font-medium">Chargement des données...</span>
                </div>
            </div>

            <form id="dossierForm" method="POST" action="?page=dossiers_academiques&action=enregistrer_dossier">
                <input type="hidden" name="num_etu" id="modalNumEtu">
                <!-- Informations personnelles -->
                <div class="mb-6">
                    <h3 class="font-semibold text-green-600 mb-3 text-base flex items-center gap-2"><i
                            class="fas fa-user"></i> Informations personnelles</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700">Adresse</label>
                            <input type="text" name="adresse" id="modalAdresse"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                disabled>
                        </div>
                        <div>
                            <label class="block text-gray-700">Téléphone</label>
                            <input type="tel" name="telephone" id="modalTelephone"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                disabled>
                        </div>
                        <div>
                            <label class="block text-gray-700">Nationalité</label>
                            <input type="text" name="nationalite" id="modalNationalite"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                disabled>
                        </div>
                        <div>
                            <label class="block text-gray-700">Situation familiale</label>
                            <input type="text" name="situation_familiale" id="modalSituationFamiliale"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                disabled>
                        </div>
                    </div>
                </div>
                <hr class="my-4 border-gray-200">
                <!-- Informations académiques -->
                <div class="mb-6">
                    <h3 class="font-semibold text-blue-600 mb-3 text-base flex items-center gap-2"><i
                            class="fas fa-graduation-cap"></i> Informations académiques</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-gray-700">Dernier diplôme</label>
                            <input type="text" name="dernier_diplome" id="modalDernierDiplome"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                disabled>
                        </div>
                        <div>
                            <label class="block text-gray-700">Établissement d'origine</label>
                            <input type="text" name="etablissement_origine" id="modalEtablissementOrigine"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                disabled>
                        </div>
                        <div>
                            <label class="block text-gray-700">Année d'obtention du diplôme</label>
                            <input type="number" name="annee_obtention_diplome" id="modalAnneeObtentionDiplome"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                min="1900" max="2030" placeholder="Ex: 2023" disabled>
                        </div>
                        <div>
                            <label class="block text-gray-700">Mention du diplôme</label>
                            <input type="text" name="mention_diplome" id="modalMentionDiplome"
                                class="w-full border border-gray-300 rounded px-3 py-2 focus:border-green-500 focus:ring-green-500"
                                disabled>
                        </div>
                    </div>
                </div>
                <div class="flex gap-4 mt-6">
                    <button type="button" id="editBtn"
                        class="bg-blue-500 text-white p-2 rounded-lg hover:bg-blue-600">Modifier</button>
                    <button type="submit" id="saveBtn"
                        class="bg-green-600 text-white p-2 rounded-lg hover:bg-green-700">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
    <script>
    // Masquer automatiquement les messages de succès/erreur après 5 secondes
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.getElementById('successMessage');
        const errorMessage = document.getElementById('errorMessage');

        function hideMessage(element) {
            if (element) {
                element.style.opacity = '0';
                setTimeout(() => {
                    element.style.display = 'none';
                }, 500);
            }
        }

        // Masquer le message de succès après 5 secondes
        if (successMessage) {
            setTimeout(() => hideMessage(successMessage), 5000);
        }

        // Masquer le message d'erreur après 5 secondes
        if (errorMessage) {
            setTimeout(() => hideMessage(errorMessage), 5000);
        }
    });

    // Filtrage JS côté client (pour la démo)
    const searchInput = document.getElementById('searchInput');
    const tableBody = document.getElementById('studentTableBody');
    const rows = Array.from(tableBody.getElementsByTagName('tr'));
    searchInput.addEventListener('input', function() {
        const value = this.value.toLowerCase();
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(value) ? '' : 'none';
        });
    });
    document.querySelectorAll('.open-dossier-modal').forEach(btn => {
        btn.addEventListener('click', function() {
            const numEtu = this.dataset.numEtu;
            const nom = this.dataset.nom;

            // Affiche la modal immédiatement
            document.getElementById('dossierModal').classList.remove('hidden');
            document.getElementById('modalNom').textContent = nom;
            document.getElementById('modalNumEtu').value = numEtu;

            // Affiche l'indicateur de chargement
            document.getElementById('loadingIndicator').classList.remove('hidden');
            document.getElementById('dossierForm').classList.add('hidden');

            // Désactive les champs
            document.querySelectorAll('#dossierForm input, #dossierForm textarea').forEach(i => {
                if (i.type !== 'hidden' && i.type !== 'file') i.disabled = true;
            });
            document.getElementById('saveBtn').classList.add('hidden');
            document.getElementById('editBtn').classList.remove('hidden');
            // Cache les inputs file
            document.querySelectorAll('#dossierForm input[type=file]').forEach(i => i.style.display =
                'none');

            // Charge les infos via AJAX avec timeout
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 10000); // 10 secondes de timeout

            fetch('?page=dossiers_academiques&action=get_dossier&num_etu=' + encodeURIComponent(
                    numEtu), {
                    signal: controller.signal
                })
                .then(r => {
                    clearTimeout(timeoutId);
                    if (!r.ok) throw new Error('Erreur réseau');
                    return r.json();
                })
                .then(data => {
                    // Cache l'indicateur de chargement
                    document.getElementById('loadingIndicator').classList.add('hidden');
                    document.getElementById('dossierForm').classList.remove('hidden');

                    // Remplit les champs avec les données existantes
                    document.getElementById('modalAdresse').value = data.adresse || '';
                    document.getElementById('modalTelephone').value = data.telephone || '';
                    document.getElementById('modalNationalite').value = data.nationalite || '';
                    document.getElementById('modalSituationFamiliale').value = data
                        .situation_familiale || '';
                    document.getElementById('modalDernierDiplome').value = data.dernier_diplome ||
                        '';
                    document.getElementById('modalEtablissementOrigine').value = data
                        .etablissement_origine || '';
                    document.getElementById('modalAnneeObtentionDiplome').value = data
                        .annee_obtention_diplome || '';
                    document.getElementById('modalMentionDiplome').value = data.mention_diplome ||
                        '';
                })
                .catch(error => {
                    clearTimeout(timeoutId);
                    console.log('Aucun dossier existant pour cet étudiant ou erreur de chargement');

                    // Cache l'indicateur de chargement
                    document.getElementById('loadingIndicator').classList.add('hidden');
                    document.getElementById('dossierForm').classList.remove('hidden');

                    // Vide les champs si pas de dossier existant
                    document.getElementById('modalAdresse').value = '';
                    document.getElementById('modalTelephone').value = '';
                    document.getElementById('modalNationalite').value = '';
                    document.getElementById('modalSituationFamiliale').value = '';
                    document.getElementById('modalDernierDiplome').value = '';
                    document.getElementById('modalEtablissementOrigine').value = '';
                    document.getElementById('modalAnneeObtentionDiplome').value = '';
                    document.getElementById('modalMentionDiplome').value = '';
                });
        });

        document.getElementById('closeModalBtn').onclick = () => document.getElementById('dossierModal')
            .classList.add(
                'hidden');
        document.getElementById('editBtn').onclick = function() {
            document.querySelectorAll('#dossierForm input, #dossierForm textarea').forEach(i => {
                if (i.type !== 'hidden') i.disabled = false;
            });
            // Affiche les inputs file
            document.querySelectorAll('#dossierForm input[type=file]').forEach(i => i.style.display =
                'block');
            document.getElementById('saveBtn').classList.remove('hidden');
            this.classList.add('hidden');
        };
    });
    </script>
</body>

</html>
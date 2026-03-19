<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
$pdo = Database::getConnection();
$etudiantModel = new Etudiant($pdo);
$niveauModel = new NiveauEtude($pdo);
$niveaux = $niveauModel->getAllNiveauxEtudes();
$selectedYearId = \AcademicYear::getSelectedIdFromSession();
$selectedYearLabel = \AcademicYear::getSelectedLabelFromSession();
$activeYearLabel = \AcademicYear::getActiveLabelFromSession();
$writableYearId = \AcademicYear::getWritableIdFromSession();
$writableYearLabel = \AcademicYear::getWritableLabelFromSession();
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$isWritableYear = \AcademicYear::isWriteAllowedFromSession();
// Paramètres de pagination
$itemsPerPage = 10;
$currentPage = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($currentPage - 1) * $itemsPerPage;
// Filtres
$niveauFiltre = isset($_GET['niveau']) ? $_GET['niveau'] : '';
$searchFiltre = isset($_GET['search']) ? $_GET['search'] : '';
// Récupération des étudiants avec filtres
$etudiants = $etudiantModel->getAllListeEtudiants($selectedYearId);
// Application des filtres
if ($niveauFiltre) {
    $etudiants = array_filter($etudiants, function ($e) use ($niveauFiltre) {
        return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
    });
}
if ($searchFiltre) {
    $etudiants = array_filter($etudiants, function ($e) use ($searchFiltre) {
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
<style>
        .cm-dossier-filters {
            gap: 0.65rem;
        }

        .cm-dossier-filters .cm-form-control,
        .cm-dossier-grid .cm-form-control {
            min-height: 32px;
            padding: 0.3rem 0.55rem;
            font-size: 0.84rem;
        }

        .cm-dossier-filters .cm-form-control {
            max-width: 18rem;
        }

        .cm-dossier-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(min(100%, 12rem), 17rem));
            gap: 0.75rem;
            align-items: end;
        }

        .cm-dossier-grid > div {
            min-width: 0;
        }

        .cm-dossier-grid input[type="number"] {
            max-width: 10rem;
        }
    </style>
<div class="cm-prd3-screen min-h-screen font-sans" style="background: linear-gradient(135deg, #DFF2FF 0%, #C8E8FF 100%);">
    <div class="max-w-5xl mx-auto py-10 ">

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
        <?php if ($selectedYearLabel !== ''): ?>
            <div class="mb-6 p-4 rounded-xl border <?= ($allYearsSelected || $isWritableYear) ? 'bg-blue-50 border-blue-200 text-blue-800' : 'bg-amber-50 border-amber-200 text-amber-800' ?>">
                <div class="flex items-start gap-3">
                    <i class="fas <?= ($allYearsSelected || $isWritableYear) ? 'fa-calendar-check' : 'fa-clock-rotate-left' ?> mt-1"></i>
                    <div>
                        <div class="font-semibold">Année académique affichée : <?= htmlspecialchars($selectedYearLabel) ?></div>
                        <div class="text-sm">
                            <?= $allYearsSelected
                                ? 'Affichage multi-années actif. Les modifications restent possibles uniquement pour les étudiants rattachés à l année active ' . htmlspecialchars($writableYearLabel) . '.'
                                : ($isWritableYear
                                    ? 'Les dossiers académiques affichés et modifiables correspondent à l année active.'
                                    : 'Consultation historique uniquement. Les enregistrements restent réservés à l année active ' . htmlspecialchars($activeYearLabel) . '.') ?>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        <?php cm_toolbar([
            'screen' => 'dossiers_academiques',
            'id_prefix' => 'dossiers',
            'search_value' => $searchFiltre,
            'limit' => $itemsPerPage,
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>
        <div class="bg-white rounded-xl shadow-lg cm-table-wrapper">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-green-50">
                    <tr>
                        <th class="px-3 py-1.5 text-left text-xs font-bold text-green-700 uppercase tracking-wider">Nom
                        </th>
                        <th class="px-3 py-1.5 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Prénom</th>
                        <th class="px-3 py-1.5 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Email</th>
                        <th class="px-3 py-1.5 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Niveau</th>
                        <th class="px-3 py-1.5 text-left text-xs font-bold text-green-700 uppercase tracking-wider">
                            Promotion</th>
                        <th class="px-3 py-1.5 text-center text-xs font-bold text-green-700 uppercase tracking-wider">
                            Action</th>
                        <th class="px-3 py-1.5 text-center text-xs font-bold text-green-700 uppercase tracking-wider">
                            Action</th>
                    </tr>
                </thead>
                <tbody id="studentTableBody" class="bg-white divide-y divide-gray-200">
                    <?php
                    // Vérifier s'il y a des résultats totaux (pas juste sur la page courante)
                    $allEtudiants = $etudiantModel->getAllListeEtudiants($selectedYearId);
                    if ($niveauFiltre) {
                        $allEtudiants = array_filter($allEtudiants, function ($e) use ($niveauFiltre) {
                            return isset($e->id_niv_etude) && $e->id_niv_etude == $niveauFiltre;
                        });
                    }
                    if ($searchFiltre) {
                        $allEtudiants = array_filter($allEtudiants, function ($e) use ($searchFiltre) {
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
                        <?php foreach ($etudiants as $etu): ?>
                            <tr class="hover:bg-green-50 transition">
                                <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($etu->nom_etu ?? ''); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo htmlspecialchars($etu->prenom_etu ?? ''); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo htmlspecialchars($etu->email_etu ?? ''); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                    <?php echo htmlspecialchars($etu->lib_niv_etude ?? ''); ?>
                                </td>
                                <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700"><?php
                                if (isset($etu->date_deb, $etu->date_fin)) {
                                    echo htmlspecialchars(date('Y', strtotime($etu->date_deb)) . '-' . date('Y', strtotime($etu->date_fin)));
                                }
                                ?></td>
                                <td class="px-4 py-2 whitespace-nowrap text-center">
                                    <button onclick="toggleDossierForm('<?= htmlspecialchars($etu->num_carte_etud) ?>')" class="inline-flex items-center px-3 py-1.5 bg-green-600 text-white text-xs font-semibold rounded-lg shadow hover:bg-green-700 transition">
                                        <i class="fas fa-eye mr-2"></i> Visualiser
                                    </button>
                                </td>
                            </tr>
                            <!-- Formulaire inline pour afficher/modifier le dossier -->
                            <tr id="dossier-row-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="hidden">
                                <td colspan="6" class="p-4 bg-gray-50">
                                    <div class="dossier-inline-form bg-white rounded-lg p-4 border border-gray-200">
                                        <div class="flex justify-between items-center mb-4">
                                            <h4 class="font-semibold text-gray-800">Dossier de <?= htmlspecialchars($etu->nom_etu . ' ' . $etu->prenom_etu) ?></h4>
                                            <button onclick="toggleDossierForm('<?= htmlspecialchars($etu->num_carte_etud) ?>')" class="text-gray-500 hover:text-gray-700">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                        <form method="POST" action="?page=dossiers_academiques&action=enregistrer_dossier">
                                            <input type="hidden" name="num_etu" value="<?= htmlspecialchars($etu->num_carte_etud) ?>">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Adresse</label>
                                                    <input type="text" name="adresse" id="adresse-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" disabled>
                                                </div>
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Téléphone</label>
                                                    <input type="tel" name="telephone" id="telephone-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" disabled>
                                                </div>
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Nationalité</label>
                                                    <input type="text" name="nationalite" id="nationalite-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" disabled>
                                                </div>
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Situation familiale</label>
                                                    <input type="text" name="situation_familiale" id="situation-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" disabled>
                                                </div>
                                            </div>
                                            <hr class="my-4">
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Dernier diplôme</label>
                                                    <input type="text" name="dernier_diplome" id="diplome-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" disabled>
                                                </div>
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Établissement d'origine</label>
                                                    <input type="text" name="etablissement_origine" id="etablissement-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" disabled>
                                                </div>
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Année d'obtention</label>
                                                    <input type="number" name="annee_obtention_diplome" id="annee-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" min="1900" max="2030" disabled>
                                                </div>
                                                <div>
                                                    <label class="block text-sm text-gray-700 mb-1">Mention</label>
                                                    <input type="text" name="mention_diplome" id="mention-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm" disabled>
                                                </div>
                                            </div>
                                            <div class="flex gap-2">
                                                <?php if (canEdit() && $isWritableYear): ?>
                                                <button type="button" onclick="enableDossierEdit('<?= htmlspecialchars($etu->num_carte_etud) ?>')" id="edit-btn-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-sm">Modifier</button>
                                                <button type="submit" id="save-btn-<?= htmlspecialchars($etu->num_carte_etud) ?>" class="hidden px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 text-sm">Enregistrer</button>
                                                <?php endif; ?>
                                            </div>
                                        </form>
                                    </div>
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

<script>
function toggleDossierForm(numEtu) {
    const row = document.getElementById('dossier-row-' + numEtu);
    if (!row) return;

    if (row.classList.contains('hidden')) {
        row.classList.remove('hidden');
        // Charger les données du dossier
        fetch('?page=dossiers_academiques&action=get_dossier&num_etu=' + encodeURIComponent(numEtu))
            .then(r => r.json())
            .then(data => {
                document.getElementById('adresse-' + numEtu).value = data.adresse || '';
                document.getElementById('telephone-' + numEtu).value = data.telephone || '';
                document.getElementById('nationalite-' + numEtu).value = data.nationalite || '';
                document.getElementById('situation-' + numEtu).value = data.situation_familiale || '';
                document.getElementById('diplome-' + numEtu).value = data.dernier_diplome || '';
                document.getElementById('etablissement-' + numEtu).value = data.etablissement_origine || '';
                document.getElementById('annee-' + numEtu).value = data.annee_obtention_diplome || '';
                document.getElementById('mention-' + numEtu).value = data.mention_diplome || '';
            })
            .catch(() => {
                console.log('Aucun dossier existant pour cet étudiant');
            });
    } else {
        row.classList.add('hidden');
    }
}

function enableDossierEdit(numEtu) {
    const fields = ['adresse', 'telephone', 'nationalite', 'situation', 'diplome', 'etablissement', 'annee', 'mention'];
    fields.forEach(field => {
        const input = document.getElementById(field + '-' + numEtu);
        if (input) input.disabled = false;
    });
    document.getElementById('edit-btn-' + numEtu).classList.add('hidden');
    document.getElementById('save-btn-' + numEtu).classList.remove('hidden');
}
</script>

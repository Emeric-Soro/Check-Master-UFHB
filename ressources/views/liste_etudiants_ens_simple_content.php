<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/AnneeAcademique.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/Enseignant.php';
require_once __DIR__ . '/../../app/models/Ecue.php';
require_once __DIR__ . '/../../app/models/Ue.php';

$pdo = Database::getConnection();
$anneeAcademiqueModel = new AnneeAcademique($pdo);
$listeAnnees = $anneeAcademiqueModel->getAllAnneeAcademiques();
$niveauEtudeModel = new NiveauEtude($pdo);
$listeNiveaux = $niveauEtudeModel->getAllNiveauxEtudes();
$etudiantModel = new Etudiant($pdo);
$enseignantModel = new Enseignant($pdo);
$ecueModel = new Ecue($pdo);
$ueModel = new Ue($pdo);

// Récupération de l'ID de l'enseignant connecté
$enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);
$enseignantId = $enseignant->id_enseignant;
if (!$enseignantId) {
    die("Enseignant non trouvé ou non connecté.");
}

// Récupération des UE et ECUE pris en charge par l'enseignant
$uesEnseignant = $ueModel->getUesByEnseignant($enseignantId);
$ecuesEnseignant = $ecueModel->getEcuesByEnseignant($enseignantId);

// Construction de la liste des niveaux concernés (par UE ou ECUE)
$niveauIds = [];
foreach ($uesEnseignant as $ue) {
    if (!in_array($ue->id_niveau_etude, $niveauIds)) {
        $niveauIds[] = $ue->id_niveau_etude;
    }
}
foreach ($ecuesEnseignant as $ecue) {
    if (!in_array($ecue->id_niveau_etude, $niveauIds)) {
        $niveauIds[] = $ecue->id_niveau_etude;
    }
}

// Indexer les UE et ECUE pour les filtres
$uesEnseignantIndexed = [];
foreach ($uesEnseignant as $ue) {
    $uesEnseignantIndexed[$ue->id_ue] = $ue->lib_ue;
}
$ecuesEnseignantIndexed = [];
foreach ($ecuesEnseignant as $ecue) {
    $ecuesEnseignantIndexed[$ecue->id_ecue] = [
        'lib_ecue' => $ecue->lib_ecue,
        'lib_ue' => $ecue->lib_ue
    ];
}

// Récupération de tous les étudiants
$etudiants = $etudiantModel->getAllListeEtudiants();

// Récupération des filtres
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$promotion = isset($_GET['promotion']) ? $_GET['promotion'] : '';
$ue = isset($_GET['ue']) ? $_GET['ue'] : '';
$ecue = isset($_GET['ecue']) ? $_GET['ecue'] : '';

// Filtrage des étudiants : uniquement ceux des niveaux concernés
$filteredEtudiants = array_filter($etudiants, function($etudiant) use ($niveauIds, $search, $promotion, $ue, $ecue, $ecuesEnseignant, $uesEnseignant) {
    if (!in_array($etudiant->id_niv_etude, $niveauIds)) {
        return false;
    }
    $matchesSearch = $search === '' ||
        strpos(strtolower($etudiant->nom_etu), $search) !== false ||
        strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
        strpos(strtolower($etudiant->email_etu), $search) !== false;
    $matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
    // Filtrage par UE
    $matchesUe = $ue === '';
    if ($ue !== '') {
        foreach ($ecuesEnseignant as $ecueEnseignant) {
            if ($ecueEnseignant->id_ue == $ue && $etudiant->id_niv_etude == $ecueEnseignant->id_niveau_etude) {
                $matchesUe = true;
                break;
            }
        }
        foreach ($uesEnseignant as $ueEnseignant) {
            if ($ueEnseignant->id_ue == $ue && $etudiant->id_niv_etude == $ueEnseignant->id_niveau_etude) {
                $matchesUe = true;
                break;
            }
        }
    }
    // Filtrage par ECUE
    $matchesEcue = $ecue === '';
    if ($ecue !== '') {
        foreach ($ecuesEnseignant as $ecueEnseignant) {
            if ($ecueEnseignant->id_ecue == $ecue && $etudiant->id_niv_etude == $ecueEnseignant->id_niveau_etude) {
                $matchesEcue = true;
                break;
            }
        }
    }
    return $matchesSearch && $matchesPromotion && $matchesUe && $matchesEcue;
});

// Pagination
$perPage = 15;
$totalEtudiants = count($filteredEtudiants);
$totalPages = ($totalEtudiants > 0) ? ceil($totalEtudiants / $perPage) : 1;
$p = isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ? (int)$_GET['p'] : 1;
if ($p > $totalPages) $p = $totalPages;
$startIndex = ($p - 1) * $perPage;
$etudiantsPage = array_slice($filteredEtudiants, $startIndex, $perPage);
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liste des Étudiants -
        <?= htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant) ?></title>
</head>

<body class="p-4 sm:p-6 md:p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:p-8 p-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Liste des Étudiants -
            <span
                class="text-4xl text-green-500 font-bold"><?= htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant) ?></span>
        </h1>

        <form method="get" class="mb-6 flex flex-wrap gap-4 items-center">
            <?php if (isset($_GET['page'])): ?>
            <input type="hidden" name="page" value="<?= htmlspecialchars($_GET['page']) ?>">
            <?php endif; ?>

            <input type="text" name="search" placeholder="Rechercher par nom, prénom ou email..."
                class="outline-green-500 flex-1 min-w-[200px] p-3 pl-4 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm"
                value="<?= htmlspecialchars(isset($_GET['search']) ? $_GET['search'] : '') ?>">

            <select name="promotion"
                class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                <option value="">Toutes les Années Académiques</option>
                <?php foreach ($listeAnnees as $annee): ?>
                <option value="<?= htmlspecialchars($annee->id_annee_acad) ?>"
                    <?php if($promotion == $annee->id_annee_acad) echo 'selected'; ?>>
                    <?= htmlspecialchars(date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin))) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select name="ue"
                class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                <option value="">Toutes mes UE</option>
                <?php foreach ($uesEnseignantIndexed as $idUe => $libUe): ?>
                <option value="<?= htmlspecialchars($idUe) ?>" <?php if($ue == $idUe) echo 'selected'; ?>>
                    <?= htmlspecialchars($libUe) ?>
                </option>
                <?php endforeach; ?>
            </select>

            <select name="ecue"
                class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                <option value="">Tous mes ECUE</option>
                <?php foreach ($ecuesEnseignantIndexed as $idEcue => $ecueData): ?>
                <option value="<?= htmlspecialchars($idEcue) ?>" <?php if($ecue == $idEcue) echo 'selected'; ?>>
                    <?= htmlspecialchars($ecueData['lib_ecue']) ?>
                    (<?= htmlspecialchars($ecueData['lib_ue']) ?>)
                </option>
                <?php endforeach; ?>
            </select>

            <button type="submit"
                class="p-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Filtrer</button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tl-lg">
                            Nom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Prénom</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Promotion</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Niveau</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date
                            de Naissance</th>
                        <th
                            class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider rounded-tr-lg">
                            Statut</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($etudiantsPage)) : ?>
                    <tr>
                        <td colspan="7" class="text-center text-gray-500 py-8">Aucun étudiant trouvé pour votre
                            recherche.</td>
                    </tr>
                    <?php else : ?>
                    <?php foreach ($etudiantsPage as $etudiant) : ?>
                    <tr class="table-row-hover">
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 rounded-bl-lg">
                            <?= htmlspecialchars($etudiant->nom_etu ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?= htmlspecialchars($etudiant->prenom_etu ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-green-600 hover:text-green-800">
                            <?= htmlspecialchars($etudiant->email_etu ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?= (isset($etudiant->date_deb, $etudiant->date_fin) ? htmlspecialchars(date('Y', strtotime($etudiant->date_deb)) . '-' . date('Y', strtotime($etudiant->date_fin))) : '') ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?= htmlspecialchars($etudiant->lib_niv_etude ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            <?= htmlspecialchars($etudiant->date_naiss_etu ?? '') ?></td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <span
                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= (isset($etudiant->status) && $etudiant->status === 'Actif') ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= htmlspecialchars($etudiant->status ?? 'Actif') ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mt-4 gap-2">
            <div class="text-gray-600 text-sm">
                <?php if ($totalEtudiants > 0): ?>
                Page <?= $p ?> sur <?= $totalPages ?> —
                Affichage de <span class="font-semibold"><?= $startIndex + 1 ?></span>
                à <span class="font-semibold"><?= min($startIndex + $perPage, $totalEtudiants) ?></span>
                sur <span class="font-semibold"><?= $totalEtudiants ?></span> étudiants
                <?php else: ?>
                Aucun étudiant à afficher
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
</body>

</html>
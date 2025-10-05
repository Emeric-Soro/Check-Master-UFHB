<?php
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/AnneeAcademique.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/Enseignant.php';

$pdo = Database::getConnection();
$anneeAcademiqueModel = new AnneeAcademique($pdo);
$listeAnnees = $anneeAcademiqueModel->getAllAnneeAcademiques();
$niveauEtudeModel = new NiveauEtude($pdo);
$etudiantModel = new Etudiant($pdo);
$enseignantModel = new Enseignant($pdo);

// Récupération de l'enseignant connecté
$enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur']);
$enseignantId = $enseignant->id_enseignant;
if (!$enseignantId) {
    die("Enseignant non trouvé ou non connecté.");
}

// Récupérer les niveaux dont l'enseignant est responsable
$niveauxResponsable = array_filter($niveauEtudeModel->getAllNiveauxEtudes(), function($niv) use ($enseignantId) {
    return isset($niv->id_enseignant) && $niv->id_enseignant == $enseignantId;
});

// Récupérer tous les étudiants
$etudiants = $etudiantModel->getAllListeEtudiants();

// Récupération des filtres
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$promotion = isset($_GET['promotion']) ? $_GET['promotion'] : '';
$niveau = isset($_GET['niveau']) ? $_GET['niveau'] : '';

// Filtrage des étudiants : uniquement ceux des niveaux dont l'enseignant est responsable
$filteredEtudiants = array_filter($etudiants, function($etudiant) use ($search, $promotion, $niveau, $niveauxResponsable) {
    $matchesSearch = $search === '' ||
        strpos(strtolower($etudiant->nom_etu), $search) !== false ||
        strpos(strtolower($etudiant->prenom_etu), $search) !== false ||
        strpos(strtolower($etudiant->email_etu), $search) !== false;
    $matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
    $niveauIds = array_map(function($niv) { return $niv->id_niv_etude; }, $niveauxResponsable);
    $matchesNiveau = $niveau === '' ? in_array($etudiant->id_niv_etude, $niveauIds) : ($etudiant->id_niv_etude == $niveau);
    return $matchesSearch && $matchesPromotion && $matchesNiveau;
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
    <title>Liste des Étudiants - Responsable Niveau</title>
</head>

<body class="p-4 sm:p-6 md:p-8">
    <div class="max-w-6xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden md:p-8 p-6">
        <h1 class="text-3xl font-bold text-gray-900 mb-6 text-center">Liste des Étudiants - <span
                class="text-4xl text-green-500 font-bold">Responsable Niveau</span></h1>
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
            <select name="niveau"
                class="p-3 border border-gray-300 rounded-lg focus:ring-green-500 focus:border-green-500 text-gray-700 shadow-sm md:text-base text-sm">
                <option value="">Tous les Niveaux dont je suis responsable</option>
                <?php foreach ($niveauxResponsable as $niv): ?>
                <option value="<?= htmlspecialchars($niv->id_niv_etude) ?>"
                    <?php if($niveau == $niv->id_niv_etude) echo 'selected'; ?>>
                    <?= htmlspecialchars($niv->lib_niv_etude) ?>
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
<?php
/**
 * Page de liste des étudiants - Version CheckMaster Design System
 */
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/AnneeAcademique.php';
require_once __DIR__ . '/../../app/models/NiveauEtude.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';
require_once __DIR__ . '/../../app/models/Enseignant.php';
$pdo = Database::getConnection();
$anneeAcademiqueModel = new AnneeAcademique($pdo);
$niveauEtudeModel = new NiveauEtude($pdo);
$etudiantModel = new Etudiant($pdo);
$enseignantModel = new Enseignant($pdo);
// Vérification si l'utilisateur est administrateur
$estAdministrateur = isset($_SESSION['id_GU']) && $_SESSION['id_GU'] == 5;
// Récupération de l'enseignant connecté (sauf pour administrateur)
$enseignant = null;
$enseignantId = null;
if (!$estAdministrateur) {
    $enseignant = $enseignantModel->getEnseignantByLogin($_SESSION['login_utilisateur'] ?? '');
    if (!$enseignant) {
        echo cm_component('ui/empty-state', [
            'title' => '',
            'message' => 'Cette page est réservée aux enseignants et administrateurs.',
            'icon' => 'fa-lock'
        ]);
        return;
    }
    $enseignantId = $enseignant->id_enseignant;
}
// Déterminer le type d'utilisateur
if ($estAdministrateur) {
    $typeAffichage = 'administrateur';
    $titre = 'Liste des Étudiants';
    $sousTitre = 'Administrateur';
} else {
    $typeAffichage = 'enseignant';
    $titre = 'Liste des Étudiants';
    $sousTitre = 'Enseignant';
}
// Récupération des données
$listeAnnees = $anneeAcademiqueModel->getAllAnneeAcademiques();
$listeNiveaux = $niveauEtudeModel->getAllNiveauxEtudes();
$selectedAcademicYearId = \AcademicYear::getSelectedIdFromSession();
$etudiants = $etudiantModel->getAllListeEtudiants($selectedAcademicYearId);
// Récupération des filtres
$search = isset($_GET['search']) ? strtolower(trim($_GET['search'])) : '';
$promotion = isset($_GET['promotion']) ? $_GET['promotion'] : (($selectedAcademicYearId !== null && $selectedAcademicYearId > 0) ? (string) $selectedAcademicYearId : '');
$niveau = isset($_GET['niveau']) ? $_GET['niveau'] : '';
// Filtrage
$filteredEtudiants = array_filter($etudiants, function ($etudiant) use ($search, $promotion, $niveau) {
    $matchesSearch = $search === '' ||
        strpos(strtolower($etudiant->nom_etu ?? ''), $search) !== false ||
        strpos(strtolower($etudiant->prenom_etu ?? ''), $search) !== false ||
        strpos(strtolower($etudiant->email_etu ?? ''), $search) !== false ||
        strpos(strtolower($etudiant->num_carte_etud ?? ''), $search) !== false;
    $matchesPromotion = $promotion === '' || (isset($etudiant->id_annee_acad) && $etudiant->id_annee_acad == $promotion);
    $matchesNiveau = $niveau === '' || (isset($etudiant->id_niveau) && $etudiant->id_niveau == $niveau);
    return $matchesSearch && $matchesPromotion && $matchesNiveau;
});
// Pagination
$perPage = 10;
$totalEtudiants = count($filteredEtudiants);
$totalPages = ($totalEtudiants > 0) ? ceil($totalEtudiants / $perPage) : 1;
$p = isset($_GET['p']) && is_numeric($_GET['p']) && $_GET['p'] > 0 ? (int) $_GET['p'] : 1;
if ($p > $totalPages) $p = $totalPages;
$startIndex = ($p - 1) * $perPage;
$etudiantsPage = array_slice($filteredEtudiants, $startIndex, $perPage);
// Construction des options pour les selects
$anneeOptions = [];
foreach ($listeAnnees as $annee) {
    $libelle = date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin));
    $anneeOptions[$annee->id_annee_acad] = $libelle;
}
$niveauOptions = [];
foreach ($listeNiveaux as $niv) {
    $niveauOptions[$niv->id_niv_etude] = $niv->lib_niv_etude;
}
// Pagination data
$pagination = [
    'total' => $totalEtudiants,
    'current' => $p,
    'per_page' => $perPage,
    'last' => $totalPages,
    'offset' => $startIndex,
    'has_prev' => $p > 1,
    'has_next' => $p < $totalPages,
    'pages' => $totalPages > 0 ? range(1, $totalPages) : [1],
];
?>
<section class="cm-prd3-screen">
    <?php cm_toolbar([
        'screen' => 'liste_etudiants_resp',
        'id_prefix' => 'etudiants',
        'search_value' => $search,
        'limit' => $perPage,
        'can_delete' => canDelete(),
        'can_view' => canView(),
    ]); ?>
    <div class="cm-card">
        <div class="cm-table-responsive">
            <table class="cm-table">
                <thead>
                    <tr>
                        <th>ID MESRS</th>
                        <th>N° Carte Étudiant</th>
                        <th>Nom &amp; Prénom</th>
                        <th>Date Naissance</th>
                        <th>Genre</th>
                        <th>Email</th>
                        <th>Promotion</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($etudiantsPage)): ?>
                        <tr>
                            <td colspan="8">
                                <?= cm_component('ui/empty-state', [
                                    'title' => '',
                                    'message' => 'Aucun étudiant ne correspond aux critères sélectionnés.',
                                    'icon' => 'fa-users',
                                    'in_table' => true,
                                    'colspan' => 8
                                ]) ?>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($etudiantsPage as $etudiant):
                            $promoLib = '';
                            foreach ($listeAnnees as $annee) {
                                if ($annee->id_annee_acad == ($etudiant->id_annee_acad ?? 0)) {
                                    $promoLib = date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin));
                                    break;
                                }
                            }
                        ?>
                            <tr>
                                <td><?= htmlspecialchars($etudiant->num_ident_etud ?? '') ?></td>
                                <td><?= htmlspecialchars($etudiant->num_carte_etud ?? '') ?></td>
                                <td><?= htmlspecialchars(strtoupper($etudiant->nom_etu ?? '') . ' ' . ($etudiant->prenom_etu ?? '')) ?></td>
                                <td><?= htmlspecialchars($etudiant->date_nais_etu ?? '') ?></td>
                                <td><?= htmlspecialchars($etudiant->libelle_genre ?? '') ?></td>
                                <td><?= htmlspecialchars($etudiant->email_etu ?? '') ?></td>
                                <td><?= htmlspecialchars($promoLib) ?></td>
                                <td>
                                    <?php if (canEdit()): ?>
                                    <a href="?page=maj_etudiant&num_etu=<?= urlencode($etudiant->num_carte_etud ?? '') ?>"
                                       class="cm-btn-action is-edit" title="Modifier">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                    <?php endif; ?>
                                    <?php if (canDelete()): ?>
                                    <button type="button"
                                            class="cm-btn-action is-delete"
                                            data-delete-url="?page=maj_etudiant&action=supprimer&num_etu=<?= urlencode($etudiant->num_carte_etud ?? '') ?>"
                                            title="Supprimer">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <?php if ($pagination['last'] > 1): ?>
            <div class="cm-mt-md">
                <?= cm_component('crud/pagination', [
                    'pagination' => (object) $pagination,
                    'base_url' => '?page=liste_etudiants_ens&search=' . urlencode($search) . '&promotion=' . urlencode($promotion) . '&niveau=' . urlencode($niveau)
                ]) ?>
            </div>
        <?php endif; ?>
    </div>
</section>

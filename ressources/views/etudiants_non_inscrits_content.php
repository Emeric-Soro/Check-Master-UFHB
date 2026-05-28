<?php
/**
 * P2.6 — Étudiants non inscrits
 * Slug: etudiants_non_inscrits | Permission: gestion_scolarite
 */
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Scolarite.php';

$scolarite = new Scolarite(Database::getConnection());
$id_annee = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;
$isHubContext = (string) ($_GET['page'] ?? '') === 'suivi_scolarite';
$ficheBaseUrl = $isHubContext
    ? '?page=suivi_scolarite&tab=fiche_etudiant_complete'
    : '?page=fiche_etudiant_complete';

$etudiants = $scolarite->getEtudiantsNonInscrits($id_annee);

$rows = [];
foreach ($etudiants as $e) {
    $rows[] = [
        'num_etu'    => $e['num_etu'] ?? '',
        'nom_etu'    => $e['nom_etu'] ?? '',
        'prenom_etu' => $e['prenom_etu'] ?? '',
    ];
}
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Étudiants non inscrits',
            'icon'  => 'fa-user-slash',
            'content' => '<p class="cm-text-muted">Liste des étudiants qui n\'ont aucune inscription pour l\'année académique sélectionnée.</p>',
        ]); ?>

        <div class="cm-pole-inferieur">
            <?php
            cm_component('crud/data-table', [
                'id'        => 'cmEtudiantsNonInscritsTable',
                'clickable' => true,
                'row_link'  => $ficheBaseUrl . '&id={num_etu}',
                'columns'   => [
                    cm_column('num_etu',    'Matricule',    ['align' => 'center']),
                    cm_column('nom_etu',    'Nom'),
                    cm_column('prenom_etu', 'Prénom'),
                ],
                'rows'         => $rows,
                'row_key'      => 'num_etu',
                'empty_title'  => 'Tous les étudiants sont inscrits',
                'empty_message' => 'Aucun étudiant non inscrit trouvé pour cette année académique.',
            ]);
            ?>
        </div>
    </div>
</section>

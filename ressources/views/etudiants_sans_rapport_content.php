<?php
/**
 * P2.5 — Étudiants sans rapport
 * Slug: etudiants_sans_rapport | Permission: gestion_rapports
 */
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/RapportEtudiant.php';
require_once __DIR__ . '/../../app/Services/GestionRapportService.php';

$service = new GestionRapportService(Database::getConnection());
$id_annee = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;
$isHubContext = (string) ($_GET['page'] ?? '') === 'suivi_scolarite';
$ficheBaseUrl = $isHubContext
    ? '?page=suivi_scolarite&tab=fiche_etudiant_complete'
    : '?page=fiche_etudiant_complete';

$etudiants = $service->getEtudiantsSansRapport($id_annee);

$rows = [];
foreach ($etudiants as $e) {
    $rows[] = [
        'num_etu'       => $e->num_carte_etud ?? $e->num_ident_etud ?? $e->num_etu ?? '',
        'nom_etu'       => $e->nom_etu ?? '',
        'prenom_etu'    => $e->prenom_etu ?? '',
        'email_etu'     => $e->email_etu ?? '',
        'filiere'       => $e->lib_filiere ?? $e->lib_niv_etude ?? '',
    ];
}
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Étudiants sans rapport',
            'icon'  => 'fa-file-circle-exclamation',
            'content' => '<p class="cm-text-muted">Liste des étudiants n\'ayant pas encore déposé de rapport pour l\'année académique en cours.</p>',
        ]); ?>

        <?php if (!empty($_SESSION['success'])): ?>
            <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $_SESSION['success']]); ?>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>
        <?php if (!empty($_SESSION['error'])): ?>
            <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $_SESSION['error']]); ?>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <div class="cm-pole-inferieur">
            <?php
            cm_component('crud/data-table', [
                'id'        => 'cmEtudiantsSansRapportTable',
                'clickable' => true,
                'row_link'  => $ficheBaseUrl . '&id={num_etu}',
                'columns'   => [
                    cm_column('num_etu',    'Matricule',    ['align' => 'center']),
                    cm_column('nom_etu',    'Nom'),
                    cm_column('prenom_etu', 'Prénom'),
                    cm_column('email_etu',  'Email'),
                    cm_column('filiere',    'Filière / Niveau'),
                ],
                'rows'        => $rows,
                'row_key'     => 'num_etu',
                'empty_title' => 'Tous les étudiants ont un rapport',
                'empty_message' => 'Aucun étudiant sans rapport trouvé pour cette année académique.',
            ]);
            ?>
        </div>
    </div>
</section>

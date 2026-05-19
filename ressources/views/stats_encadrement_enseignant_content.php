<?php
/**
 * P2.9 — Stats Encadrement Enseignant
 * Slug: stats_encadrement_enseignant | Permission: dashboard_enseignant
 *
 * Affiche les statistiques d'encadrement pour l'enseignant connecté :
 * widgets (nb étudiants, taux réussite, note moyenne) + tableau détaillé.
 */

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/Services/StatsEncadrementEnseignantService.php';

use CheckMaster\Services\StatsEncadrementEnseignantService;

$service = new StatsEncadrementEnseignantService(Database::getConnection());
$id_annee = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;

$enseignantId = $service->getConnectedEnseignantId();
$stats = $enseignantId ? $service->getStats($enseignantId, $id_annee) : null;

$detailRows = [];
if ($stats && !empty($stats['detail_etudiants'])) {
    foreach ($stats['detail_etudiants'] as $d) {
        $detailRows[] = [
            'num_etu'      => $d['num_etu'] ?? '',
            'nom_etu'      => $d['nom_etu'] ?? '',
            'prenom_etu'   => $d['prenom_etu'] ?? '',
            'titre_rapport' => $d['titre_rapport'] ?? '',
            'role'         => $d['role'] ?? '',
            'statut'       => match (strtolower((string) ($d['valide'] ?? 'en_cours'))) {
                'valider' => 'Validé',
                'rejeter' => 'Rejeté',
                default => 'En cours',
            },
            'note'         => $d['note'] ?? '--',
        ];
    }
}
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Statistiques d\'encadrement',
            'icon'  => 'fa-chart-simple',
            'content' => '<p class="cm-text-muted">Synthèse des étudiants encadrés, taux de réussite et performances.</p>',
        ]); ?>

        <?php if (!$enseignantId): ?>
            <?php cm_component('ui/alert-box', ['type' => 'warning', 'message' => 'Aucun enseignant trouvé pour votre compte utilisateur.']); ?>
        <?php elseif (!$stats): ?>
            <?php cm_component('ui/empty-state', ['title' => 'Aucune donnée', 'message' => 'Impossible de charger les statistiques.']); ?>
        <?php else: ?>

        <!-- Widgets -->
        <div class="cm-grid-4 cm-mb-md">
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value"><?= $stats['total_etudiants'] ?></div>
                <div class="cm-stat-widget__label">Étudiants encadrés</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value"><?= $stats['total_valides'] ?></div>
                <div class="cm-stat-widget__label">Rapports validés</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value <?= $stats['taux_reussite'] >= 70 ? 'cm-text-success' : ($stats['taux_reussite'] >= 40 ? 'cm-text-warning' : 'cm-text-danger') ?>">
                    <?= $stats['taux_reussite'] ?>%
                </div>
                <div class="cm-stat-widget__label">Taux de réussite</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value"><?= $stats['note_moyenne'] !== null ? $stats['note_moyenne'] . '/20' : '--' ?></div>
                <div class="cm-stat-widget__label">Note moyenne</div>
            </div>
        </div>

        <!-- Répartition par rôle -->
        <?php if (!empty($stats['roles_repartition'])): ?>
        <div class="cm-card cm-mb-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title">Répartition par rôle</h3>
            </div>
            <div class="cm-card__body">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Rôle</th>
                            <th class="cm-data-table__th is-center">Nombre d'étudiants</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats['roles_repartition'] as $role => $count): ?>
                        <tr class="cm-data-table__row">
                            <td class="cm-data-table__td">
                                <?php
                                $roleLabel = $role === 'encadrant' ? 'Encadrant' : ($role === 'directeur' ? 'Directeur' : $role);
                                echo htmlspecialchars($roleLabel, ENT_QUOTES, 'UTF-8');
                                ?>
                            </td>
                            <td class="cm-data-table__td is-center"><?= $count ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Tableau détaillé -->
        <div class="cm-pole-inferieur">
            <h3 class="cm-text-semibold cm-mb-sm">Détail des étudiants encadrés</h3>
            <?php
            cm_component('crud/data-table', [
                'id'        => 'cmStatsEncadrementTable',
                'clickable' => true,
                'row_link'  => '?page=fiche_etudiant_complete&id={num_etu}',
                'columns'   => [
                    cm_column('num_etu',       'Matricule',  ['align' => 'center']),
                    cm_column('nom_etu',       'Nom'),
                    cm_column('prenom_etu',    'Prénom'),
                    cm_column('titre_rapport', 'Titre rapport'),
                    cm_column('role',          'Rôle',       ['align' => 'center']),
                    cm_column('statut',        'Statut',     ['align' => 'center']),
                    cm_column('note',          'Note',       ['align' => 'center']),
                ],
                'rows'          => $detailRows,
                'row_key'       => 'num_etu',
                'empty_title'   => 'Aucun étudiant encadré',
                'empty_message' => 'Vous n\'avez pas encore d\'étudiants encadrés.',
            ]);
            ?>
        </div>

        <?php endif; ?>
    </div>
</section>

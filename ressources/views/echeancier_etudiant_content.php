<?php
/**
 * P2.12 — Échéancier étudiant
 * Slug: echeancier_etudiant | Permission: gestion_scolarite
 *
 * Affiche un tableau détaillé des échéances avec timeline mensuelle,
 * widgets de statistiques et filtre par statut.
 */

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/Services/EcheancierEtudiantService.php';

use CheckMaster\Services\EcheancierEtudiantService;

$service = new EcheancierEtudiantService(Database::getConnection());
$id_annee = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;
$search = trim((string) ($_GET['search'] ?? ''));
$statut = trim((string) ($_GET['statut'] ?? ''));

// Données
$timeline = $service->getTimelineData($id_annee, $search, $statut);
$stats = $service->getStats($id_annee);
$allEcheances = $service->getAllEcheances($id_annee, $search, $statut);

// Mois en français
$moisFrancais = [
    'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
    'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
    'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
    'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre',
];

$rows = [];
foreach ($allEcheances as $e) {
    $statutE = $e['statut_echeance'] ?? 'en_attente';
    $badgeType = match (strtolower($statutE)) {
        'payée', 'paye', 'payee', 'validé', 'valide' => 'success',
        'en_retard', 'impayée', 'impaye' => 'danger',
        'partielle', 'partiel' => 'warning',
        default => 'info',
    };

    $rows[] = [
        'id_echeance'    => $e['id_echeance'] ?? '',
        'num_etu'        => $e['num_carte_etud'] ?? '',
        'etudiant'       => ($e['nom_etu'] ?? '') . ' ' . ($e['prenom_etu'] ?? ''),
        'niveau'         => $e['lib_niv_etude'] ?? '',
        'annee_label'    => $e['annee_label'] ?? '',
        'date_echeance'  => $e['date_echeance'] ?? '',
        'libelle'        => $e['libelle_echeance'] ?? 'Échéance',
        'montant'        => number_format((float) ($e['montant_echeance'] ?? 0), 0, ',', ' ') . ' FCFA',
        'montant_verse'  => number_format((float) ($e['montant_verser'] ?? 0), 0, ',', ' ') . ' FCFA',
        'solde'          => number_format((float) ($e['solde'] ?? 0), 0, ',', ' ') . ' FCFA',
        'statut'         => ['label' => $statutE, 'type' => $badgeType],
    ];
}
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Échéancier étudiant',
            'icon'  => 'fa-calendar-check',
            'content' => '<p class="cm-text-muted">Suivi des échéances de paiement par étudiant, avec timeline et statistiques.</p>',
        ]); ?>

        <!-- Stats widgets -->
        <div class="cm-grid-4 cm-mb-md">
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value"><?= $stats['total'] ?></div>
                <div class="cm-stat-widget__label">Total échéances</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value cm-text-success"><?= $stats['payees'] ?></div>
                <div class="cm-stat-widget__label">Payées</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value cm-text-danger"><?= $stats['impayees'] ?></div>
                <div class="cm-stat-widget__label">Impayées / Retard</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value"><?= $stats['taux_paiement'] ?>%</div>
                <div class="cm-stat-widget__label">Taux de paiement</div>
            </div>
        </div>

        <!-- Filtres -->
        <form method="GET" class="cm-grid-3 cm-mb-md">
            <input type="hidden" name="page" value="echeancier_etudiant">
            <?php cm_component('form/input-text', [
                'name' => 'search',
                'label' => 'Rechercher un étudiant',
                'value' => $search,
                'placeholder' => 'Nom, prénom ou matricule...',
            ]); ?>
            <?php cm_component('form/select', [
                'name' => 'statut',
                'label' => 'Statut',
                'options' => [
                    '' => 'Tous',
                    'en_attente' => 'En attente',
                    'payée' => 'Payée',
                    'paye' => 'Payé',
                    'en_retard' => 'En retard',
                    'impayée' => 'Impayée',
                    'partielle' => 'Partielle',
                ],
                'selected' => $statut,
            ]); ?>
            <div class="cm-flex cm-flex-end cm-items-end cm-mt-sm">
                <button type="submit" class="cm-btn is-primary"><i class="fas fa-filter"></i> Filtrer</button>
                <a href="?page=echeancier_etudiant" class="cm-btn is-light cm-ml-sm"><i class="fas fa-rotate-left"></i> Réinitialiser</a>
            </div>
        </form>

        <!-- Timeline -->
        <?php if (!empty($timeline)): ?>
            <div class="cm-card cm-mb-md">
                <div class="cm-card__header">
                    <h3 class="cm-card__title"><i class="fas fa-timeline"></i> Timeline des échéances</h3>
                </div>
                <div class="cm-card__body">
                    <?php foreach ($timeline as $monthGroup): ?>
                    <?php
                        $monthLabel = $monthGroup['month_label'];
                        foreach ($moisFrancais as $en => $fr) {
                            $monthLabel = str_replace($en, $fr, $monthLabel);
                        }
                    ?>
                    <div class="cm-mb-md">
                        <h4 class="cm-text-semibold cm-mb-sm cm-text-primary">
                            <i class="fas fa-calendar"></i> <?= htmlspecialchars(ucfirst($monthLabel), ENT_QUOTES, 'UTF-8') ?>
                        </h4>
                        <div class="cm-timeline">
                            <?php foreach ($monthGroup['items'] as $item): ?>
                            <div class="cm-timeline__item cm-clickable-row" data-href="?page=fiche_etudiant_complete&id=<?= urlencode((string) $item['num_etu']) ?>">
                                <div class="cm-timeline__dot is-<?= $item['badge_type'] ?>"></div>
                                <div class="cm-timeline__content">
                                    <div class="cm-flex cm-justify-between">
                                        <strong><?= htmlspecialchars($item['jour_semaine'] . ' ' . $item['jour'], ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php cm_component('ui/badge', ['text' => $item['statut'], 'type' => $item['badge_type']]); ?>
                                    </div>
                                    <div><?= htmlspecialchars($item['libelle'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="cm-text-sm cm-text-muted">
                                        <?= htmlspecialchars($item['etudiant'], ENT_QUOTES, 'UTF-8') ?>
                                        (<?= htmlspecialchars($item['niveau'], ENT_QUOTES, 'UTF-8') ?>)
                                        — <?= number_format($item['montant'], 0, ',', ' ') ?> FCFA
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <!-- Tableau détaillé -->
        <div class="cm-pole-inferieur">
            <?php
            cm_component('crud/data-table', [
                'id'        => 'cmEcheancierTable',
                'clickable' => true,
                'row_link'  => '?page=fiche_etudiant_complete&id={num_etu}',
                'columns'   => [
                    cm_column('date_echeance', 'Date',     ['align' => 'center']),
                    cm_column('etudiant',       'Étudiant'),
                    cm_column('niveau',         'Niveau'),
                    cm_column('annee_label',    'Année'),
                    cm_column('libelle',        'Libellé'),
                    cm_column('montant',        'Montant',  ['align' => 'right']),
                    cm_column('montant_verse',  'Versé',    ['align' => 'right']),
                    cm_column('solde',          'Solde',    ['align' => 'right']),
                    cm_column('statut',         'Statut',   ['type' => 'badge', 'align' => 'center']),
                ],
                'rows'          => $rows,
                'row_key'       => 'id_echeance',
                'empty_title'   => 'Aucune échéance',
                'empty_message' => 'Aucune échéance trouvée pour les critères sélectionnés.',
            ]);
            ?>
        </div>
    </div>
</section>

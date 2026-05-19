<?php
/**
 * P2.8 — Planning Jurys Enseignant
 * Slug: planning_jurys_enseignant | Permission: dashboard_enseignant
 *
 * Affiche un calendrier mensuel des jurys pour l'enseignant connecté
 * (ou pour un enseignant sélectionné, pour les admins).
 */

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/Services/PlanningJurysEnseignantService.php';

use CheckMaster\Services\PlanningJurysEnseignantService;

$service = new PlanningJurysEnseignantService(Database::getConnection());
$id_annee = !empty($_SESSION['selected_academic_year_id']) ? (int) $_SESSION['selected_academic_year_id'] : null;

// Enseignant cible
$enseignantId = $_GET['enseignant'] ?? null;
if (empty($enseignantId)) {
    $enseignantId = $service->getConnectedEnseignantId();
}

$isAdmin = isset($_SESSION['id_GU']) && in_array((int) $_SESSION['id_GU'], [5, 6, 8], true);

// Données
$jurys = $service->getJurysByEnseignant((string) $enseignantId, $id_annee);
$grouped = $service->groupByMonth($jurys);
$stats = $service->getStats((string) $enseignantId, $id_annee);

// Infos enseignant
$enseignantNom = '';
if ($enseignantId) {
    try {
        $stmt = Database::getConnection()->prepare("SELECT nom_enseignant, prenom_enseignant FROM enseignants WHERE id_enseignant = :id LIMIT 1");
        $stmt->execute([':id' => $enseignantId]);
        $ens = $stmt->fetch(PDO::FETCH_ASSOC);
        $enseignantNom = ($ens['prenom_enseignant'] ?? '') . ' ' . ($ens['nom_enseignant'] ?? '');
    } catch (Exception $e) {}
}

// Liste pour filtre admin
$enseignants = $isAdmin ? $service->getAllEnseignants() : [];

$moisFrancais = [
    'January' => 'Janvier', 'February' => 'Février', 'March' => 'Mars',
    'April' => 'Avril', 'May' => 'Mai', 'June' => 'Juin',
    'July' => 'Juillet', 'August' => 'Août', 'September' => 'Septembre',
    'October' => 'Octobre', 'November' => 'Novembre', 'December' => 'Décembre',
];
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Planning des Jurys',
            'icon'  => 'fa-calendar-alt',
            'content' => '<p class="cm-text-muted">Calendrier des sessions de jury pour ' .
                ($enseignantNom ?: 'l\'enseignant connecté') . '.</p>',
        ]); ?>

        <!-- Filtre admin -->
        <?php if ($isAdmin && !empty($enseignants)): ?>
        <form method="GET" class="cm-mb-md cm-flex cm-flex-gap-sm cm-flex-wrap">
            <input type="hidden" name="page" value="planning_jurys_enseignant">
            <select name="enseignant" class="cm-form-control cm-form-select" style="width:auto; min-width:250px;" onchange="this.form.submit()">
                <option value="">-- Choisir un enseignant --</option>
                <?php foreach ($enseignants as $ens): ?>
                <option value="<?= htmlspecialchars($ens['id_enseignant'], ENT_QUOTES, 'UTF-8') ?>"
                    <?= ($ens['id_enseignant'] === $enseignantId) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($ens['nom_enseignant'] . ' ' . $ens['prenom_enseignant'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </form>
        <?php endif; ?>

        <!-- Stats widgets -->
        <div class="cm-grid-3 cm-mb-md">
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value"><?= $stats['total_jurys'] ?></div>
                <div class="cm-stat-widget__label">Participation(s) jury</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value"><?= $stats['total_jours'] ?></div>
                <div class="cm-stat-widget__label">Jour(s) de soutenance</div>
            </div>
            <div class="cm-stat-widget">
                <div class="cm-stat-widget__value">
                    <?php if (!empty($stats['qualites'])): ?>
                        <?= implode(' / ', array_map(fn($q, $c) => "$q: $c", array_keys($stats['qualites']), $stats['qualites'])) ?>
                    <?php else: ?>
                        0
                    <?php endif; ?>
                </div>
                <div class="cm-stat-widget__label">Qualités</div>
            </div>
        </div>

        <!-- Calendrier mensuel -->
        <?php if (empty($grouped)): ?>
            <?php cm_component('ui/empty-state', [
                'title' => 'Aucun jury programmé',
                'message' => 'Aucune session de jury trouvée pour cette période.',
                'icon' => 'fa-calendar-day',
            ]); ?>
        <?php else: ?>
            <?php foreach ($grouped as $monthGroup): ?>
            <?php
                $monthLabel = $monthGroup['month_label'];
                // Traduire le mois en français
                foreach ($moisFrancais as $en => $fr) {
                    $monthLabel = str_replace($en, $fr, $monthLabel);
                }
            ?>
            <div class="cm-card cm-mb-lg">
                <div class="cm-card__header">
                    <h3 class="cm-card__title">
                        <i class="fas fa-calendar"></i>
                        <?= htmlspecialchars(ucfirst($monthLabel), ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                </div>
                <div class="cm-card__body">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Date</th>
                                <th class="cm-data-table__th">Jour</th>
                                <th class="cm-data-table__th">Horaire</th>
                                <th class="cm-data-table__th">Semaine</th>
                                <th class="cm-data-table__th">Étudiant</th>
                                <th class="cm-data-table__th">Salle</th>
                                <th class="cm-data-table__th">Qualité</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($monthGroup['items'] as $item): ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="?page=fiche_etudiant_complete&id=<?= htmlspecialchars(urlencode((string) $item['num_etud']), ENT_QUOTES, 'UTF-8') ?>">
                                <td class="cm-data-table__td">
                                    <?= date('d/m/Y', strtotime($item['date_soutenance'])) ?>
                                </td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($item['jour_semaine'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($item['heure_soutenance'] ?? '--:--', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td is-center">S<?= $item['week_number'] ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($item['etudiant'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($item['salle'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td">
                                    <?php
                                    cm_component('ui/badge', [
                                        'text' => $item['qualite_jury'],
                                        'type' => 'info',
                                    ]);
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

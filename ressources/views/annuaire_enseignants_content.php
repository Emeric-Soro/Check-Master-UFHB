<?php
/**
 * Annuaire des enseignants avec filtres
 * Tables: enseignants, grade, specialite, type_enseignant
 * Permission: repertoire_enseignant
 * Lignes cliquables → ?page=fiche_enseignante&view=fiche&id={id_enseignant}
 */
declare(strict_types=1);

require_once __DIR__ . '/../../app/utils/permissions_helper.php';

if (!canView('repertoire_enseignant')) {
    $_SESSION['error'] = "Acces refuse.";
    header('Location: ?page=dashboard');
    exit;
}

$enseignants = $GLOBALS['annuaire_enseignants'] ?? [];
$grades = $GLOBALS['annuaire_grades'] ?? [];
$specialites = $GLOBALS['annuaire_specialites'] ?? [];
$types = $GLOBALS['annuaire_types'] ?? [];
$pagination = $GLOBALS['annuaire_pagination'] ?? ['total' => 0, 'current' => 1, 'last' => 1];
$filtreGrade = $_GET['grade'] ?? '';
$filtreSpecialite = $_GET['specialite'] ?? '';
$filtreType = $_GET['type_enseignant'] ?? '';
$search = $_GET['search'] ?? '';
?>
<div class="cm-prd3-screen">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-title">
            <i class="fas fa-address-book cm-text-primary"></i>
            Annuaire des enseignants
        </h1>
        <p class="cm-page-subtitle">Repertoire complet avec filtres avances</p>
    </div>

    <div class="cm-crud-wrapper">
        <!-- Barre de filtres -->
        <div class="cm-pole-superieur">
            <form method="GET" class="cm-form">
                <input type="hidden" name="page" value="annuaire_enseignants">
                <div class="cm-grid-4 cm-mb-3">
                    <?php
                    cm_component('form/input-text', [
                        'name' => 'search',
                        'label' => 'Recherche',
                        'value' => $search,
                        'placeholder' => 'Nom, prenom, email...',
                    ]);

                    $gradeOptions = ['' => '-- Tous les grades --'];
                    foreach ($grades as $g) {
                        $gradeOptions[(int) ($g['id_grade'] ?? 0)] = (string) ($g['lib_grade'] ?? '-');
                    }
                    cm_component('form/select', [
                        'name' => 'grade',
                        'label' => 'Grade',
                        'options' => $gradeOptions,
                        'selected' => (string) $filtreGrade,
                    ]);

                    $specOptions = ['' => '-- Toutes les specialites --'];
                    foreach ($specialites as $s) {
                        $specOptions[(int) ($s['id_specialite'] ?? 0)] = (string) ($s['lib_specialite'] ?? '-');
                    }
                    cm_component('form/select', [
                        'name' => 'specialite',
                        'label' => 'Specialite',
                        'options' => $specOptions,
                        'selected' => (string) $filtreSpecialite,
                    ]);

                    $typeOptions = ['' => '-- Tous les types --'];
                    foreach ($types as $t) {
                        $typeOptions[(int) ($t['id_type_enseignant'] ?? 0)] = (string) ($t['lib_type_enseignant'] ?? '-');
                    }
                    cm_component('form/select', [
                        'name' => 'type_enseignant',
                        'label' => "Type d'enseignant",
                        'options' => $typeOptions,
                        'selected' => (string) $filtreType,
                    ]);
                    ?>
                </div>
                <div class="cm-flex cm-gap-2 cm-justify-end">
                    <a href="?page=annuaire_enseignants" class="cm-btn is-light is-sm">
                        <i class="fas fa-rotate-left"></i> Reinitialiser
                    </a>
                    <button type="submit" class="cm-btn is-primary is-sm">
                        <i class="fas fa-filter"></i> Filtrer
                    </button>
                    <a href="?page=annuaire_enseignants&export=csv&grade=<?php echo urlencode((string) $filtreGrade); ?>&specialite=<?php echo urlencode((string) $filtreSpecialite); ?>&type_enseignant=<?php echo urlencode((string) $filtreType); ?>&search=<?php echo urlencode((string) $search); ?>"
                       class="cm-btn is-info is-sm">
                        <i class="fas fa-file-export"></i> Export CSV
                    </a>
                </div>
            </form>
        </div>

        <!-- Resultats -->
        <div class="cm-pole-inferieur">
            <div class="cm-flex cm-justify-between cm-items-center cm-mb-3">
                <span class="cm-text-muted">
                    <?php echo (int) ($pagination['total'] ?? 0); ?> enseignant(s) trouve(s)
                </span>
            </div>

            <?php if (empty($enseignants)): ?>
                <?php cm_component('ui/empty-state', [
                    'title' => 'Aucun enseignant',
                    'message' => 'Aucun resultat avec les filtres selectionnes.',
                ]); ?>
            <?php else: ?>
                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">N°</th>
                                <th class="cm-data-table__th">Nom &amp; Prenom</th>
                                <th class="cm-data-table__th">Grade</th>
                                <th class="cm-data-table__th">Specialite</th>
                                <th class="cm-data-table__th">Type</th>
                                <th class="cm-data-table__th">Email</th>
                                <th class="cm-data-table__th is-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($enseignants as $i => $ens): 
                                $idEns = (string) ($ens['id_enseignant'] ?? '');
                            ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=fiche_enseignante&view=fiche&id=<?php echo urlencode($idEns); ?>">
                                    <td class="cm-data-table__td">
                                        <?php echo (int) ($pagination['offset'] ?? 0) + $i + 1; ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars(
                                            ((string) ($ens['prenom_enseignant'] ?? '')) . ' ' . ((string) ($ens['nom_enseignant'] ?? '')),
                                            ENT_QUOTES, 'UTF-8'
                                        ); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($ens['lib_grade'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($ens['lib_specialite'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php echo htmlspecialchars((string) ($ens['lib_type_enseignant'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <a href="mailto:<?php echo htmlspecialchars((string) ($ens['mail_enseignant'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"
                                           class="cm-link">
                                            <?php echo htmlspecialchars((string) ($ens['mail_enseignant'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                        </a>
                                    </td>
                                    <td class="cm-data-table__td is-center">
                                        <div class="cm-table-actions">
                                            <a href="?page=fiche_enseignante&view=fiche&id=<?php echo urlencode($idEns); ?>"
                                               class="cm-btn-action is-view" title="Voir la fiche">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <?php if ((int) ($pagination['last'] ?? 1) > 1): ?>
                    <div class="cm-flex cm-justify-center cm-mt-4 cm-gap-1">
                        <?php if ($pagination['has_prev'] ?? false): ?>
                            <a href="?page=annuaire_enseignants&p=<?php echo (int) ($pagination['current'] ?? 1) - 1; ?>&grade=<?php echo urlencode((string) $filtreGrade); ?>&specialite=<?php echo urlencode((string) $filtreSpecialite); ?>&type_enseignant=<?php echo urlencode((string) $filtreType); ?>&search=<?php echo urlencode((string) $search); ?>"
                               class="cm-btn is-light is-sm">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php endif; ?>
                        <?php foreach (range(1, (int) ($pagination['last'] ?? 1)) as $p): ?>
                            <a href="?page=annuaire_enseignants&p=<?php echo $p; ?>&grade=<?php echo urlencode((string) $filtreGrade); ?>&specialite=<?php echo urlencode((string) $filtreSpecialite); ?>&type_enseignant=<?php echo urlencode((string) $filtreType); ?>&search=<?php echo urlencode((string) $search); ?>"
                               class="cm-btn is-sm <?php echo $p === (int) ($pagination['current'] ?? 1) ? 'is-primary' : 'is-light'; ?>">
                                <?php echo $p; ?>
                            </a>
                        <?php endforeach; ?>
                        <?php if ($pagination['has_next'] ?? false): ?>
                            <a href="?page=annuaire_enseignants&p=<?php echo (int) ($pagination['current'] ?? 1) + 1; ?>&grade=<?php echo urlencode((string) $filtreGrade); ?>&specialite=<?php echo urlencode((string) $filtreSpecialite); ?>&type_enseignant=<?php echo urlencode((string) $filtreType); ?>&search=<?php echo urlencode((string) $search); ?>"
                               class="cm-btn is-light is-sm">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

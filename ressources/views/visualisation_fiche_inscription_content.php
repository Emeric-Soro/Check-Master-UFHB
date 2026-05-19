<?php
/**
 * Visualisation de la fiche d'inscription d'un etudiant
 * Permission: archives_etudiants
 */
$matricule = trim((string) ($_GET['num_etu'] ?? $_GET['matricule'] ?? ''));
$anneeId = (int) ($_GET['id_annee'] ?? 0);

$fichePath = '';
$etudiant = [];
$inscriptions = [];

if ($matricule !== '') {
    $db = Database::getConnection();

    // Recuperer les infos etudiant
    $stmt = $db->prepare("SELECT e.*, g.libelle_genre
                          FROM etudiants e
                          LEFT JOIN genre g ON e.id_genre = g.id_genre
                          WHERE e.num_carte_etud = ?");
    $stmt->execute([$matricule]);
    $etudiant = $stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    // Recuperer les fiches d'inscription
    $sql = "SELECT i.*, ne.lib_niv_etude,
                   CONCAT(YEAR(aa.date_deb), '-', YEAR(aa.date_fin)) AS annee_label,
                   aa.date_deb, aa.date_fin
            FROM inscriptions i
            LEFT JOIN niveau_etude ne ON i.id_niv_etude = ne.id_niv_etude
            LEFT JOIN annee_academique aa ON i.id_annee_acad = aa.id_annee_acad
            WHERE i.num_carte_etud = ?";
    if ($anneeId > 0) {
        $sql .= " AND i.id_annee_acad = ?";
    }
    $sql .= " ORDER BY i.date_inscription DESC";

    $params = [$matricule];
    if ($anneeId > 0) {
        $params[] = $anneeId;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $inscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Recuperer la fiche d'inscription a afficher
    if (!empty($inscriptions)) {
        $target = $inscriptions[0];
        if (!empty($target['fiche_inscription'])) {
            $fichePath = $target['fiche_inscription'];
        }
    }
}
?>
<div class="cm-prd3-screen">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-title">Visualisation fiche d'inscription</h1>
        <p class="cm-page-subtitle">Consultez les fiches d'inscription telechargees</p>
    </div>

    <!-- Formulaire de recherche -->
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <form method="GET" class="cm-form cm-grid-3">
                <input type="hidden" name="page" value="visualisation_fiche_inscription">
                <?php
                cm_component('form/input-text', [
                    'name' => 'num_etu',
                    'label' => 'Matricule etudiant',
                    'value' => $matricule,
                    'placeholder' => 'Ex: 2024-1234',
                    'required' => true,
                ]);
                cm_component('form/select', [
                    'name' => 'id_annee',
                    'label' => 'Annee academique',
                    'options' => ['' => '-- Toutes --'] + ($GLOBALS['globalAcademicYears'] ?? []),
                    'selected' => (string) $anneeId,
                ]);
                ?>
                <div class="cm-form-group cm-flex-end">
                    <button type="submit" class="cm-btn is-primary is-sm cm-mt-6">
                        <i class="fas fa-search"></i> Rechercher
                    </button>
                </div>
            </form>
        </div>

        <?php if ($matricule !== ''): ?>
            <!-- Infos etudiant -->
            <?php if (!empty($etudiant)): ?>
                <div class="cm-card cm-mb-4 cm-p-4">
                    <div class="cm-grid-3">
                        <div>
                            <strong>Matricule:</strong>
                            <?php echo htmlspecialchars((string) ($etudiant['num_carte_etud'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div>
                            <strong>Nom:</strong>
                            <?php echo htmlspecialchars((string) ($etudiant['nom_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <div>
                            <strong>Prenom:</strong>
                            <?php echo htmlspecialchars((string) ($etudiant['prenom_etu'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Liste des fiches d'inscription -->
            <?php if (empty($inscriptions)): ?>
                <?php cm_component('ui/empty-state', [
                    'title' => 'Aucune fiche d\'inscription',
                    'message' => 'Aucune inscription trouvee pour ce matricule.',
                ]); ?>
            <?php else: ?>
                <div class="cm-pole-inferieur">
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Annee</th>
                                    <th class="cm-data-table__th">Niveau</th>
                                    <th class="cm-data-table__th">Date inscription</th>
                                    <th class="cm-data-table__th">Fiche</th>
                                    <th class="cm-data-table__th is-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inscriptions as $ins): 
                                    $hasFiche = !empty($ins['fiche_inscription']);
                                ?>
                                    <tr class="cm-data-table__row<?php echo $hasFiche ? ' cm-clickable-row' : ''; ?>"
                                        <?php if ($hasFiche): ?>
                                        data-href="?page=visualisation_fiche_inscription&num_etu=<?php echo urlencode((string) $matricule); ?>&id_annee=<?php echo (int) ($ins['id_annee_acad'] ?? 0); ?>&view=fiche"
                                        <?php endif; ?>>
                                        <td class="cm-data-table__td">
                                            <?php echo htmlspecialchars((string) ($ins['annee_label'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php echo htmlspecialchars((string) ($ins['lib_niv_etude'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php echo !empty($ins['date_inscription']) ? date('d/m/Y', strtotime((string) $ins['date_inscription'])) : '-'; ?>
                                        </td>
                                        <td class="cm-data-table__td">
                                            <?php if ($hasFiche): ?>
                                                <i class="fas fa-file-pdf cm-text-danger"></i>
                                                <?php echo htmlspecialchars(basename((string) $ins['fiche_inscription']), ENT_QUOTES, 'UTF-8'); ?>
                                            <?php else: ?>
                                                <span class="cm-text-muted">Non disponible</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="cm-data-table__td is-center">
                                            <div class="cm-table-actions">
                                                <?php if ($hasFiche): ?>
                                                    <a href="<?php echo htmlspecialchars((string) $ins['fiche_inscription'], ENT_QUOTES, 'UTF-8'); ?>"
                                                       target="_blank"
                                                       class="cm-btn-action is-view"
                                                       title="Visualiser">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="<?php echo htmlspecialchars((string) $ins['fiche_inscription'], ENT_QUOTES, 'UTF-8'); ?>"
                                                       download
                                                       class="cm-btn-action is-download"
                                                       title="Telecharger">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="cm-text-muted">—</span>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

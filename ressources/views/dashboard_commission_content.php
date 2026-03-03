<?php
if (!is_array($stats ?? null)) {
    try {
        require_once __DIR__ . '/../../app/controllers/DashboardCommissionController.php';
        $fallbackController = new DashboardCommissionController();
        $stats = $fallbackController->getDashboardData();
    } catch (Throwable $e) {
        $stats = [];
    }
}
$dashboardData = is_array($stats ?? null) ? $stats : [];
$enAttente = (int) ($dashboardData['en_attente'] ?? 0);
$repartition = is_array($dashboardData['repartition_statuts'] ?? null) ? $dashboardData['repartition_statuts'] : [];
$activites = is_array($dashboardData['activites_recentes'] ?? null) ? $dashboardData['activites_recentes'] : [];
$rapportsDetails = is_array($dashboardData['rapports_details'] ?? null) ? $dashboardData['rapports_details'] : [];
$countByStatut = static function (array $rows, string $needle): int {
    $count = 0;
    foreach ($rows as $row) {
        if (strtolower((string) ($row['statut'] ?? '')) === strtolower($needle)) {
            $count += (int) ($row['nombre'] ?? 0);
        }
    }
    return $count;
};
$valides = $countByStatut($repartition, 'valider');
$rejetes = $countByStatut($repartition, 'rejeter');
$crRediges = count($rapportsDetails);
$totalRapports = max(1, $enAttente + $valides + $rejetes);
$pctValides = (int) round(($valides / $totalRapports) * 100);
$pctAttente = (int) round(($enAttente / $totalRapports) * 100);
$pctRejetes = (int) round(($rejetes / $totalRapports) * 100);
$activityLines = [];
foreach (array_slice($activites, 0, 5) as $activite) {
    $titre = trim((string) ($activite['titre'] ?? 'Rapport'));
    $etudiant = trim((string) ($activite['prenom_etudiant'] ?? '') . ' ' . (string) ($activite['nom_etudiant'] ?? ''));
    $date = !empty($activite['date_validation'])
        ? date('d/m/Y', strtotime((string) $activite['date_validation']))
        : '';
    $line = $titre;
    if ($etudiant !== '') {
        $line .= ' - ' . $etudiant;
    }
    if ($date !== '') {
        $line .= ' (' . $date . ')';
    }
    $activityLines[] = $line;
}
?>
<div class="cm-prd3-screen cm-prd3-crud-screen">
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="">
            </div>
            <div class="cm-grid-4">
                <div class="cm-card cm-p-md">
                    <div class="cm-text-sm cm-text-semibold cm-text-primary cm-mb-sm">
                        <i class="fas fa-clipboard-list" aria-hidden="true"></i>
                        EN ATTENTE
                    </div>
                    <div style="font-size:1.85rem;font-weight:700;line-height:1.1;"><?php echo $enAttente; ?></div>
                    <div class="cm-mt-md">
                        <?php if (canView()): ?>
                        <a class="cm-btn is-info is-sm" href="?page=reception_rapport_com">Voir</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cm-card cm-p-md">
                    <div class="cm-text-sm cm-text-semibold cm-text-primary cm-mb-sm">
                        <i class="fas fa-circle-check" aria-hidden="true"></i>
                        VALIDÉS
                    </div>
                    <div style="font-size:1.85rem;font-weight:700;line-height:1.1;"><?php echo $valides; ?></div>
                    <div class="cm-mt-md">
                        <?php if (canView()): ?>
                        <a class="cm-btn is-info is-sm" href="?page=processus_validation">Voir</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cm-card cm-p-md">
                    <div class="cm-text-sm cm-text-semibold cm-text-primary cm-mb-sm">
                        <i class="fas fa-circle-xmark" aria-hidden="true"></i>
                        REJETÉS
                    </div>
                    <div style="font-size:1.85rem;font-weight:700;line-height:1.1;"><?php echo $rejetes; ?></div>
                    <div class="cm-mt-md">
                        <?php if (canView()): ?>
                        <a class="cm-btn is-info is-sm" href="?page=processus_validation&status=rejete">Voir</a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="cm-card cm-p-md">
                    <div class="cm-text-sm cm-text-semibold cm-text-primary cm-mb-sm">
                        <i class="fas fa-file-signature" aria-hidden="true"></i>
                        CR RÉDIGÉS
                    </div>
                    <div style="font-size:1.85rem;font-weight:700;line-height:1.1;"><?php echo $crRediges; ?></div>
                    <div class="cm-mt-md">
                        <?php if (canCreate()): ?>
                        <a class="cm-btn is-info is-sm" href="?page=redaction_compte_rendu&cr_view=redaction">Rédiger</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="cm-grid-2">
                <div class="cm-card cm-p-md">

                    <?php
                    $progressRows = [
                        ['label' => 'Validés', 'count' => $valides, 'pct' => $pctValides, 'color' => '#1a5276'],
                        ['label' => 'En attente', 'count' => $enAttente, 'pct' => $pctAttente, 'color' => '#3498db'],
                        ['label' => 'Rejetés', 'count' => $rejetes, 'pct' => $pctRejetes, 'color' => '#e74c3c'],
                    ];
                    foreach ($progressRows as $row):
                    ?>
                        <div class="cm-mb-sm">
                            <div class="cm-flex-between cm-text-sm cm-mb-sm">
                                <span><?php echo htmlspecialchars($row['label'], ENT_QUOTES, 'UTF-8'); ?> (<?php echo (int) $row['count']; ?>)</span>
                                <strong><?php echo (int) $row['pct']; ?>%</strong>
                            </div>
                            <div style="height:8px;background:#cfe7f7;border-radius:999px;overflow:hidden;">
                                <span style="display:block;height:100%;width:<?php echo (int) $row['pct']; ?>%;background:<?php echo htmlspecialchars($row['color'], ENT_QUOTES, 'UTF-8'); ?>;"></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="cm-card cm-p-md">

                    <?php if (empty($activityLines)): ?>
                        <p class="cm-text-sm cm-text-muted cm-m-0">Aucune activité récente.</p>
                    <?php else: ?>
                        <ul class="cm-m-0" style="padding-left:1.1rem; display:grid; gap:0.45rem;">
                            <?php foreach ($activityLines as $line): ?>
                                <li class="cm-text-sm"><?php echo htmlspecialchars($line, ENT_QUOTES, 'UTF-8'); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
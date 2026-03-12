<?php
/**
 * Vue : Fiche Soutenance
 * $data['soutenance']   - détails de la soutenance
 * $data['jury']         - membres du jury
 * $data['evaluations']  - critères d'évaluation et notes
 */
$soutenance  = $data['soutenance'] ?? null;
$jury        = $data['jury'] ?? [];
$evaluations = $data['evaluations'] ?? [];
?>

<div class="cm-fiche-soutenance">

    <!-- En-tête -->
    <div class="cm-page-header cm-mb-5">
        <div>
            <h1 class="cm-page-title">
                <i class="fas fa-file-alt cm-mr-2"></i>Fiche Soutenance
            </h1>
            <?php if ($soutenance): ?>
                <p class="cm-page-subtitle">
                    N° <?= htmlspecialchars($soutenance['num_soutenance']) ?>
                    — <?= htmlspecialchars(date('d/m/Y', strtotime($soutenance['date_soutenance']))) ?>
                </p>
            <?php endif; ?>
        </div>
        <a href="?page=archives_soutenances" class="cm-btn cm-btn-outline cm-btn-sm">
            <i class="fas fa-arrow-left cm-mr-1"></i> Retour aux soutenances
        </a>
    </div>

    <?php if (!$soutenance): ?>
        <div class="cm-card">
            <div class="cm-card-body cm-text-center cm-py-5">
                <div class="cm-icon-box cm-icon-box-lg cm-icon-box-secondary cm-mx-auto cm-mb-3">
                    <i class="fas fa-exclamation-triangle fa-2x"></i>
                </div>
                <p class="cm-text-muted">Soutenance introuvable.</p>
            </div>
        </div>
    <?php else: ?>

        <div class="cm-grid-2 cm-gap-4 cm-mb-4">

            <!-- Infos soutenance -->
            <div class="cm-card">
                <div class="cm-card-header">
                    <h3 class="cm-card-title"><i class="fas fa-info-circle cm-mr-2"></i>Informations</h3>
                </div>
                <div class="cm-card-body">
                    <table class="cm-table">
                        <tbody>
                            <tr>
                                <th class="cm-text-muted">Étudiant</th>
                                <td>
                                    <a href="?page=fiche_etudiant_archive&matricule=<?= urlencode($soutenance['num_carte_etud']) ?>">
                                        <?= htmlspecialchars($soutenance['etudiant'] ?? '—') ?>
                                    </a>
                                    <br><small class="cm-text-muted"><?= htmlspecialchars($soutenance['num_carte_etud']) ?></small>
                                </td>
                            </tr>
                            <tr>
                                <th class="cm-text-muted">Promotion</th>
                                <td><?= htmlspecialchars($soutenance['promotion_etu'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th class="cm-text-muted">Domaine</th>
                                <td><?= htmlspecialchars($soutenance['lib_domaine'] ?? '—') ?></td>
                            </tr>
                            <tr>
                                <th class="cm-text-muted">Session</th>
                                <td><span class="cm-badge cm-badge-info"><?= htmlspecialchars($soutenance['lib_session'] ?? '—') ?></span></td>
                            </tr>
                            <tr>
                                <th class="cm-text-muted">Date</th>
                                <td><?= htmlspecialchars(date('d/m/Y', strtotime($soutenance['date_soutenance']))) ?></td>
                            </tr>
                            <tr>
                                <th class="cm-text-muted">Heure</th>
                                <td><?= htmlspecialchars(substr($soutenance['heure_soutenance'], 0, 5)) ?></td>
                            </tr>
                            <tr>
                                <th class="cm-text-muted">Salle</th>
                                <td><?= htmlspecialchars($soutenance['lib_salle'] ?? '—') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Thème -->
            <div class="cm-card">
                <div class="cm-card-header">
                    <h3 class="cm-card-title"><i class="fas fa-book cm-mr-2"></i>Thème de mémoire</h3>
                </div>
                <div class="cm-card-body">
                    <p class="cm-mb-4"><?= htmlspecialchars($soutenance['theme_soutenance'] ?? '—') ?></p>

                    <?php if (!empty($evaluations)): ?>
                        <h4 class="cm-font-semibold cm-mb-3">Évaluations</h4>
                        <?php
                        $noteTotal  = 0;
                        $baremeTotal = 0;
                        foreach ($evaluations as $ev):
                            $noteTotal  += (float)($ev['note'] ?? 0);
                            $baremeTotal += (float)($ev['bareme'] ?? 0);
                        ?>
                            <div class="cm-flex cm-justify-between cm-items-center cm-mb-2">
                                <span><?= htmlspecialchars($ev['lib_critere']) ?></span>
                                <span class="cm-badge cm-badge-primary">
                                    <?= htmlspecialchars($ev['note']) ?> / <?= htmlspecialchars($ev['bareme']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                        <?php if ($baremeTotal > 0): ?>
                            <hr>
                            <div class="cm-flex cm-justify-between cm-items-center cm-font-bold">
                                <span>Total</span>
                                <span class="cm-badge cm-badge-success cm-text-lg">
                                    <?= number_format($noteTotal, 2) ?> / <?= number_format($baremeTotal, 2) ?>
                                </span>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Jury -->
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title"><i class="fas fa-users cm-mr-2"></i>Membres du jury</h3>
            </div>
            <div class="cm-card-body cm-p-0">
                <?php if (empty($jury)): ?>
                    <div class="cm-text-center cm-py-4">
                        <p class="cm-text-muted">Aucun membre de jury enregistré.</p>
                    </div>
                <?php else: ?>
                    <div class="cm-table-responsive">
                        <table class="cm-table cm-table-striped">
                            <thead>
                                <tr>
                                    <th>Nom & Prénom</th>
                                    <th>Grade</th>
                                    <th>Rôle</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($jury as $j): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($j['nom_complet']) ?></strong></td>
                                        <td><?= htmlspecialchars($j['lib_grade'] ?? '—') ?></td>
                                        <td>
                                            <span class="cm-badge cm-badge-warning">
                                                <?= htmlspecialchars($j['lib_role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (!empty($j['mail_enseignant'])): ?>
                                                <a href="mailto:<?= htmlspecialchars($j['mail_enseignant']) ?>">
                                                    <?= htmlspecialchars($j['mail_enseignant']) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="cm-text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    <?php endif; ?>
</div>

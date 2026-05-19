<div class="cm-prd3-screen">
    <!-- En-tete : stats -->
    <div class="cm-grid-4">
        <?php
        $totalMembres = count($membres ?? []);
        $totalEvalues = count($rapportsEvalues ?? []);
        $totalAttente = count($rapportsAttente ?? []);
        $totalVotesValider = (int) ($statsVote['total_valider'] ?? 0);
        $totalVotesRejeter = (int) ($statsVote['total_rejeter'] ?? 0);
        $totalVotes = $totalVotesValider + $totalVotesRejeter;
        ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => (string) $totalMembres,
            'label' => 'Membres commission',
            'icon' => 'fa-users',
            'color' => 'primary',
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => (string) $totalEvalues,
            'label' => 'Rapports evalues',
            'icon' => 'fa-file-circle-check',
            'color' => 'success',
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => (string) $totalAttente,
            'label' => 'En attente',
            'icon' => 'fa-clock',
            'color' => 'warning',
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => (string) $totalVotes,
            'label' => 'Votes exprimes',
            'icon' => 'fa-check-double',
            'color' => 'info',
        ]); ?>
    </div>

    <!-- Membres commission -->
    <div class="cm-card cm-mt-md">
        <div class="cm-card__header">
            <h3 class="cm-card__title"><i class="fas fa-users-cog cm-mr-sm"></i>Membres de la commission</h3>
        </div>
        <div class="cm-card__body">
            <?php if (empty($membres)): ?>
                <?php cm_component('ui/empty-state', ['title' => 'Aucun membre', 'message' => 'Aucun membre de commission trouve.']); ?>
            <?php else: ?>
                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Enseignant</th>
                                <th class="cm-data-table__th">Email</th>
                                <th class="cm-data-table__th">Telephone</th>
                                <th class="cm-data-table__th">Statut compte</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($membres as $m): ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="?page=fiche_enseignante&view=fiche&id=<?= urlencode((string) ($m['id_enseignant'] ?? '')) ?>">
                                <td class="cm-data-table__td">
                                    <?= htmlspecialchars(trim(($m['nom_enseignant'] ?? '') . ' ' . ($m['prenom_enseignant'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($m['mail_enseignant'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($m['tel_enseignant'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td">
                                    <?php
                                    $statut = $m['statut_utilisateur'] ?? 'Inactif';
                                    $badgeType = $statut === 'Actif' ? 'success' : 'warning';
                                    cm_component('ui/badge', ['text' => $statut, 'type' => $badgeType]);
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Rapports evalues / en attente -->
    <div class="cm-grid-2 cm-mt-md">
        <!-- Rapports evalues -->
        <div class="cm-card">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-check-circle cm-mr-sm"></i>Rapports evalues</h3>
            </div>
            <div class="cm-card__body">
                <?php if (empty($rapportsEvalues)): ?>
                    <?php cm_component('ui/empty-state', ['title' => 'Aucun rapport', 'message' => 'Aucun rapport evalue.']); ?>
                <?php else: ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Etudiant</th>
                                    <th class="cm-data-table__th">Theme</th>
                                    <th class="cm-data-table__th">Votes (V/R)</th>
                                    <th class="cm-data-table__th">Decision</th>
                                    <th class="cm-data-table__th">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rapportsEvalues as $r): ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=processus_validation&detail=<?= urlencode((string) ($r['id_rapport'] ?? '')) ?>">
                                    <td class="cm-data-table__td"><?= htmlspecialchars(trim(($r['nom_etu'] ?? '') . ' ' . ($r['prenom_etu'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars(mb_substr((string) ($r['theme_rapport'] ?? ''), 0, 50), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= (int) ($r['votes_valider'] ?? 0) ?> / <?= (int) ($r['votes_rejeter'] ?? 0) ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?php
                                        $dec = $r['decision_finale'] ?? 'en_attente';
                                        $badgeType = 'info';
                                        $label = 'En attente';
                                        if ($dec === 'valider') { $badgeType = 'success'; $label = 'Valide'; }
                                        elseif ($dec === 'rejeter') { $badgeType = 'danger'; $label = 'Rejete'; }
                                        cm_component('ui/badge', ['text' => $label, 'type' => $badgeType]);
                                        ?>
                                    </td>
                                    <td class="cm-data-table__td">
                                        <?= !empty($r['date_validation']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $r['date_validation'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Rapports en attente -->
        <div class="cm-card">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-hourglass-half cm-mr-sm"></i>Rapports en attente</h3>
            </div>
            <div class="cm-card__body">
                <?php if (empty($rapportsAttente)): ?>
                    <?php cm_component('ui/empty-state', ['title' => 'Aucun rapport', 'message' => 'Aucun rapport en attente.']); ?>
                <?php else: ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Etudiant</th>
                                    <th class="cm-data-table__th">Theme</th>
                                    <th class="cm-data-table__th">Date depot</th>
                                    <th class="cm-data-table__th">Promotion</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rapportsAttente as $r): ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=evaluation_dossiers&detail=<?= urlencode((string) ($r['id_rapport'] ?? '')) ?>">
                                    <td class="cm-data-table__td"><?= htmlspecialchars(trim(($r['nom_etu'] ?? '') . ' ' . ($r['prenom_etu'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars(mb_substr((string) ($r['theme_rapport'] ?? ''), 0, 50), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?= !empty($r['date_redaction_rapport']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $r['date_redaction_rapport'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars($r['promotion_etu'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Decisions recentes et planning -->
    <div class="cm-grid-2 cm-mt-md">
        <!-- Decisions recentes -->
        <div class="cm-card">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-gavel cm-mr-sm"></i>Decisions recentes</h3>
            </div>
            <div class="cm-card__body">
                <?php if (empty($decisions)): ?>
                    <?php cm_component('ui/empty-state', ['title' => 'Aucune decision', 'message' => 'Aucune decision recente.']); ?>
                <?php else: ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Etudiant</th>
                                    <th class="cm-data-table__th">Decision</th>
                                    <th class="cm-data-table__th">Par</th>
                                    <th class="cm-data-table__th">Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($decisions as $d): ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=processus_validation&detail=<?= urlencode((string) ($d['id_rapport'] ?? '')) ?>">
                                    <td class="cm-data-table__td"><?= htmlspecialchars(trim(($d['nom_etu'] ?? '') . ' ' . ($d['prenom_etu'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?php
                                        $decVal = $d['decision_validation'] ?? '';
                                        $bt = $decVal === 'valider' ? 'success' : 'danger';
                                        $lb = $decVal === 'valider' ? 'Valide' : 'Rejete';
                                        cm_component('ui/badge', ['text' => $lb, 'type' => $bt]);
                                        ?>
                                    </td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars(trim(($d['nom_enseignant'] ?? '') . ' ' . ($d['prenom_enseignant'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= !empty($d['date_validation']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $d['date_validation'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Planning seances -->
        <div class="cm-card">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-calendar-days cm-mr-sm"></i>Planning seances</h3>
            </div>
            <div class="cm-card__body">
                <?php if (empty($planning)): ?>
                    <?php cm_component('ui/empty-state', ['title' => 'Aucune seance', 'message' => 'Aucune seance planifiee.']); ?>
                <?php else: ?>
                    <div class="cm-table-wrapper">
                        <table class="cm-data-table">
                            <thead>
                                <tr>
                                    <th class="cm-data-table__th">Etudiant</th>
                                    <th class="cm-data-table__th">Theme</th>
                                    <th class="cm-data-table__th">Date</th>
                                    <th class="cm-data-table__th">Salle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($planning as $p): ?>
                                <tr class="cm-data-table__row cm-clickable-row"
                                    data-href="?page=programmation_soutenance&detail=<?= urlencode((string) ($p['num_soutenance'] ?? '')) ?>">
                                    <td class="cm-data-table__td"><?= htmlspecialchars(trim(($p['nom_etu'] ?? '') . ' ' . ($p['prenom_etu'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars(mb_substr((string) ($p['theme_soutenance'] ?? ''), 0, 40), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="cm-data-table__td">
                                        <?php
                                        $date = $p['date_soutenance'] ?? '';
                                        $heure = $p['heure_soutenance'] ?? '';
                                        $afficher = '';
                                        if ($date) {
                                            $afficher .= date('d/m/Y', strtotime((string) $date));
                                        }
                                        if ($heure) {
                                            $afficher .= ' ' . substr((string) $heure, 0, 5);
                                        }
                                        echo htmlspecialchars($afficher ?: '-', ENT_QUOTES, 'UTF-8');
                                        ?>
                                    </td>
                                    <td class="cm-data-table__td"><?= htmlspecialchars($p['lib_salle'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

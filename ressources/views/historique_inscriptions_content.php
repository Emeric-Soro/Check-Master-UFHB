<?php
$isHubContext = ((string) ($_GET['page'] ?? '') === 'suivi_scolarite')
    || (((string) ($_GET['page'] ?? '') === 'parametres_generaux') && ((string) ($_GET['action'] ?? '') === 'suivi_scolarite'));
$historyBaseUrl = $isHubContext
    ? '?page=parametres_generaux&action=suivi_scolarite&tab=historique_inscriptions'
    : '?page=historique_inscriptions';
$ficheBaseUrl = $isHubContext
    ? '?page=parametres_generaux&action=suivi_scolarite&tab=fiche_etudiant_complete'
    : '?page=fiche_etudiant_complete';
?>
<div class="cm-prd3-screen">
    <?php if (!$etudiant): ?>
        <!-- Formulaire recherche etudiant -->
        <div class="cm-card">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-search cm-mr-sm"></i>Rechercher un etudiant</h3>
            </div>
            <div class="cm-card__body">
                <form method="GET" class="cm-form-inline cm-flex cm-flex-gap-sm">
                    <input type="hidden" name="page" value="<?= $isHubContext ? 'suivi_scolarite' : 'historique_inscriptions' ?>">
                    <?php if ($isHubContext): ?>
                        <input type="hidden" name="tab" value="historique_inscriptions">
                    <?php endif; ?>
                    <div class="cm-form-group cm-field--text" style="flex:1;">
                        <input type="text" name="num_etu" class="cm-form-control"
                            placeholder="Numero carte etudiant ou identifiant MESRS"
                            value="<?= htmlspecialchars($_GET['num_etu'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                            required>
                    </div>
                    <button type="submit" class="cm-btn is-primary">
                        <i class="fas fa-search cm-mr-sm"></i>Rechercher
                    </button>
                </form>
            </div>
        </div>
    <?php else: ?>
        <!-- En-tete etudiant -->
        <div class="cm-card">
            <div class="cm-card__header cm-flex-between">
                <h3 class="cm-card__title">
                    <i class="fas fa-user-graduate cm-mr-sm"></i>
                    <?= htmlspecialchars(trim(($etudiant['nom_etu'] ?? $etudiant->nom_etu ?? '') . ' ' . ($etudiant['prenom_etu'] ?? $etudiant->prenom_etu ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </h3>
                <div class="cm-flex cm-flex-gap-sm">
                    <form method="GET" class="cm-form-inline">
                        <input type="hidden" name="page" value="<?= $isHubContext ? 'suivi_scolarite' : 'historique_inscriptions' ?>">
                        <?php if ($isHubContext): ?>
                            <input type="hidden" name="tab" value="historique_inscriptions">
                        <?php endif; ?>
                        <div class="cm-form-group">
                            <input type="text" name="num_etu" class="cm-form-control is-sm"
                                placeholder="Autre numero..." style="width:180px;">
                        </div>
                        <button type="submit" class="cm-btn is-sm is-primary-accent">
                            <i class="fas fa-search"></i>
                        </button>
                    </form>
                    <a href="<?= htmlspecialchars($historyBaseUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-sm is-secondary">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
            </div>
            <div class="cm-card__body">
                <div class="cm-grid-4">
                    <div>
                        <span class="cm-text-muted cm-text-sm">Numero carte</span>
                        <p class="cm-text-semibold"><?= htmlspecialchars($etudiant['num_carte_etud'] ?? $etudiant->num_carte_etud ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span class="cm-text-muted cm-text-sm">Numero ident.</span>
                        <p class="cm-text-semibold"><?= htmlspecialchars($etudiant['num_ident_etud'] ?? $etudiant->num_ident_etud ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span class="cm-text-muted cm-text-sm">Email</span>
                        <p class="cm-text-semibold"><?= htmlspecialchars($etudiant['email_etu'] ?? $etudiant->email_etu ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                    <div>
                        <span class="cm-text-muted cm-text-sm">Promotion</span>
                        <p class="cm-text-semibold"><?= htmlspecialchars($etudiant['promotion_etu'] ?? $etudiant->promotion_etu ?? '-', ENT_QUOTES, 'UTF-8') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Timeline des inscriptions -->
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-timeline cm-mr-sm"></i>Parcours academique</h3>
            </div>
            <div class="cm-card__body">
                <?php if (empty($parcours)): ?>
                    <?php cm_component('ui/empty-state', ['title' => 'Aucune inscription', 'message' => "Aucune inscription trouvee pour cet etudiant."]); ?>
                <?php else: ?>
                    <div class="cm-timeline">
                        <?php foreach ($parcours as $annee): ?>
                            <?php
                            $totalFrais = (float) ($annee['frais_montant'] ?? 0);
                            $totalVerse = (float) ($annee['total_verse'] ?? 0);
                            $solde = (float) ($annee['solde'] ?? 0);
                            $nbVersements = count($annee['versements'] ?? []);
                            ?>
                            <div class="cm-timeline__item">
                                <div class="cm-timeline__marker <?= $solde <= 0 ? 'is-success' : 'is-warning' ?>"></div>
                                <div class="cm-timeline__content">
                                    <div class="cm-card is-compact">
                                        <div class="cm-card__header cm-flex-between">
                                            <h4 class="cm-card__title cm-text-base">
                                                <i class="fas fa-calendar-alt cm-mr-sm"></i>
                                                <?= htmlspecialchars($annee['annee_label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                                                <span class="cm-badge is-info cm-ml-sm"><?= htmlspecialchars($annee['niveau'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                            </h4>
                                            <span class="cm-text-sm cm-text-muted">
                                                <?= (int) $nbVersements ?> versement<?= $nbVersements > 1 ? 's' : '' ?>
                                            </span>
                                        </div>
                                        <div class="cm-card__body">
                                            <div class="cm-grid-4 cm-mb-sm">
                                                <div>
                                                    <span class="cm-text-muted cm-text-sm">Frais annuels</span>
                                                    <p class="cm-text-semibold"><?= number_format($totalFrais, 0, ',', ' ') ?> CFA</p>
                                                </div>
                                                <div>
                                                    <span class="cm-text-muted cm-text-sm">Total verse</span>
                                                    <p class="cm-text-semibold cm-text-success"><?= number_format($totalVerse, 0, ',', ' ') ?> CFA</p>
                                                </div>
                                                <div>
                                                    <span class="cm-text-muted cm-text-sm">Solde</span>
                                                    <p class="cm-text-semibold <?= $solde > 0 ? 'cm-text-danger' : 'cm-text-success' ?>">
                                                        <?= number_format($solde, 0, ',', ' ') ?> CFA
                                                    </p>
                                                </div>
                                                <div>
                                                    <span class="cm-text-muted cm-text-sm">Taux</span>
                                                    <?php
                                                    $taux = $totalFrais > 0 ? round(($totalVerse / $totalFrais) * 100) : 0;
                                                    $tauxBadge = $taux >= 100 ? 'success' : ($taux >= 50 ? 'warning' : 'danger');
                                                    cm_component('ui/badge', ['text' => $taux . '%', 'type' => $tauxBadge]);
                                                    ?>
                                                </div>
                                            </div>

                                            <?php if (!empty($annee['versements'])): ?>
                                                <div class="cm-table-wrapper">
                                                    <table class="cm-data-table is-sm">
                                                        <thead>
                                                            <tr>
                                                                <th class="cm-data-table__th">N° versement</th>
                                                                <th class="cm-data-table__th">Date</th>
                                                                <th class="cm-data-table__th">Montant</th>
                                                                <th class="cm-data-table__th">Mode</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php foreach ($annee['versements'] as $v): ?>
                                                            <tr class="cm-data-table__row">
                                                                <td class="cm-data-table__td">#<?= (int) ($v['num_versement'] ?? 0) ?></td>
                                                                <td class="cm-data-table__td">
                                                                    <?= !empty($v['date_versement']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $v['date_versement'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                                                </td>
                                                                <td class="cm-data-table__td cm-text-semibold"><?= number_format((float) ($v['montant'] ?? 0), 0, ',', ' ') ?> CFA</td>
                                                                <td class="cm-data-table__td"><?= htmlspecialchars($v['methode'] ?? '-', ENT_QUOTES, 'UTF-8') ?></td>
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
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Notes -->
        <?php if (!empty($notes)): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-chart-simple cm-mr-sm"></i>Notes</h3>
            </div>
            <div class="cm-card__body">
                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Annee</th>
                                <th class="cm-data-table__th">Moyenne M1</th>
                                <th class="cm-data-table__th">Moyenne M2</th>
                                <th class="cm-data-table__th">Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($notes as $n): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td">
                                    <?= !empty($n['date_deb']) ? htmlspecialchars(date('Y', strtotime((string) $n['date_deb'])) . '-' . date('Y', strtotime((string) $n['date_fin'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                </td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(number_format((float) ($n['moyenne_M1'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars(number_format((float) ($n['moyenne_M2'] ?? 0), 2), ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= !empty($n['date_modification']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $n['date_modification'])), ENT_QUOTES, 'UTF-8') : '-' ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lien fiche etudiante -->
        <div class="cm-mt-md cm-text-right">
            <a href="<?= htmlspecialchars($ficheBaseUrl . '&id=' . urlencode((string) ($etudiant['num_carte_etud'] ?? $etudiant->num_carte_etud ?? '')), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-btn is-primary-accent">
                <i class="fas fa-external-link-alt cm-mr-sm"></i>Fiche etudiante complete
            </a>
        </div>
    <?php endif; ?>
</div>

<style>
/* Timeline verticale */
.cm-timeline {
    position: relative;
    padding-left: 2rem;
}
.cm-timeline::before {
    content: '';
    position: absolute;
    left: 0.75rem;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #d1d5db;
}
.cm-timeline__item {
    position: relative;
    margin-bottom: 1rem;
}
.cm-timeline__marker {
    position: absolute;
    left: -1.45rem;
    top: 1rem;
    width: 14px;
    height: 14px;
    border-radius: 50%;
    border: 3px solid #3b82f6;
    background: #fff;
    z-index: 1;
}
.cm-timeline__marker.is-success {
    border-color: #10b981;
    background: #d1fae5;
}
.cm-timeline__marker.is-warning {
    border-color: #f59e0b;
    background: #fef3c7;
}
.cm-timeline__content {
    padding-left: 0.5rem;
}
.cm-card.is-compact .cm-card__header {
    padding: 0.5rem 0.75rem;
}
.cm-card.is-compact .cm-card__body {
    padding: 0 0.75rem 0.5rem;
}
</style>

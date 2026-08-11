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
$anneeAcademique = $dashboardData['annee_academique'] ?? null;
$enAttente = (int) ($dashboardData['en_attente'] ?? 0);
$rapportsRejetes = (int) ($dashboardData['rapports_rejetes'] ?? 0);
$repartition = is_array($dashboardData['repartition_statuts'] ?? null) ? $dashboardData['repartition_statuts'] : [];
$activites = is_array($dashboardData['activites_recentes'] ?? null) ? $dashboardData['activites_recentes'] : [];
$rapportsDetails = is_array($dashboardData['rapports_details'] ?? null) ? $dashboardData['rapports_details'] : [];
$observationMembers = is_array($dashboardData['observations_membres'] ?? null) ? $dashboardData['observations_membres'] : [];
$observationDetail = is_array($dashboardData['observation_detail'] ?? null) ? $dashboardData['observation_detail'] : [];

$countByStatut = static function (array $rows, string $needle): int {
    $count = 0;
    foreach ($rows as $row) {
        if (strtolower((string) ($row['statut'] ?? '')) === strtolower($needle)) {
            $count += (int) ($row['nombre'] ?? 0);
        }
    }
    return $count;
};

// Priorité : clé rapports_valides (basée sur statut_rapport) > repartition_statuts (basée sur table valider)
$valides = isset($dashboardData['rapports_valides'])
    ? (int) $dashboardData['rapports_valides']
    : $countByStatut($repartition, 'valider');
$rejetes = $rapportsRejetes;
$crRediges = count($rapportsDetails);


// Préparer les activités récentes pour le format attendu
$activityItems = [];
foreach (array_slice($activites, 0, 8) as $activite) {
    $titre = trim((string) ($activite['titre'] ?? 'Rapport'));
    $etudiant = trim((string) ($activite['prenom_etudiant'] ?? '') . ' ' . (string) ($activite['nom_etudiant'] ?? ''));
    $date = !empty($activite['date_validation'])
        ? date('d/m/Y', strtotime((string) $activite['date_validation']))
        : '';

    $text = $titre;
    if ($etudiant !== '') {
        $text .= ' - ' . $etudiant;
    }

    $statut = strtolower((string) ($activite['statut'] ?? ''));
    $type = 'info';
    $icon = 'fa-file-lines';

    if ($statut === 'valider') {
        $type = 'success';
        $icon = 'fa-circle-check';
    } elseif ($statut === 'rejeter') {
        $type = 'danger';
        $icon = 'fa-circle-xmark';
    }

    $activityItems[] = [
        'type' => $type,
        'icon' => $icon,
        'text' => $text,
        'time' => $date,
    ];
}

// Année académique pour l'affichage
$yearLabel = '';
if ($anneeAcademique && is_array($anneeAcademique)) {
    $dateDeb = date('Y', strtotime($anneeAcademique['date_deb']));
    $dateFin = date('Y', strtotime($anneeAcademique['date_fin']));
    $yearLabel = $dateDeb . '-' . $dateFin;
}
?>

<section class="cm-prd3-screen">

    <!-- Statistiques principales -->
    <div class="cm-grid-4">
        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($enAttente, 0, ',', ' '),
                'label' => 'En attente',
                'icon' => 'fa-clipboard-list',
                'color' => 'info',
                'url' => canView() ? '?page=reception_rapport_com' : ''
            ]); ?>
        </div>

        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($valides, 0, ',', ' '),
                'label' => 'Validés',
                'icon' => 'fa-circle-check',
                'color' => 'success',
                'url' => canView() ? '?page=processus_validation' : ''
            ]); ?>
        </div>

        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($rejetes, 0, ',', ' '),
                'label' => 'Rejetés',
                'icon' => 'fa-circle-xmark',
                'color' => 'danger',
                'url' => canView() ? '?page=processus_validation&status=rejete' : ''
            ]); ?>
        </div>

        <div>
            <?php cm_component('dashboard/stat-widget', [
                'value' => number_format($crRediges, 0, ',', ' '),
                'label' => 'CR rédigés',
                'icon' => 'fa-file-signature',
                'color' => 'primary',
                'url' => canCreate() ? '?page=redaction_compte_rendu&cr_view=redaction' : ''
            ]); ?>
        </div>
    </div>



    <!-- Activités récentes et actions rapides -->
    <div class="cm-grid-2 cm-mt-md">
        <?php cm_component('dashboard/activity-list', [
            'title' => 'Activités récentes',
            'items' => $activityItems
        ]); ?>

        <div class="cm-chart-container">
            <div class="cm-chart-container__header">
                <h3 class="cm-chart-container__title">Actions rapides</h3>
                <p class="cm-chart-container__subtitle">Navigation directe</p>
            </div>
            <div class="cm-chart-container__body">
                <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                    <?php if (canView()): ?>
                        <a class="cm-btn is-primary-accent" href="?page=reception_rapport_com" data-cm-ajax-link="true">
                            <i class="fas fa-inbox" aria-hidden="true"></i>
                            Réception rapports
                        </a>
                        <a class="cm-btn is-primary-deep" href="?page=processus_validation" data-cm-ajax-link="true">
                            <i class="fas fa-check-double" aria-hidden="true"></i>
                            Processus validation
                        </a>
                    <?php endif; ?>
                    <?php if (canCreate()): ?>
                        <a class="cm-btn is-primary-dark" href="?page=redaction_compte_rendu" data-cm-ajax-link="true">
                            <i class="fas fa-pen-to-square" aria-hidden="true"></i>
                            Rédaction CR
                        </a>
                    <?php endif; ?>
                    <?php if (canView()): ?>
                        <a class="cm-btn is-primary-sky" href="?page=programmation_soutenance" data-cm-ajax-link="true">
                            <i class="fas fa-calendar-days" aria-hidden="true"></i>
                            Soutenances
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($rapportsDetails)): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header cm-flex-between">
                <h3 class="cm-card__title"><i class="fas fa-file-lines cm-mr-sm"></i>Derniers rapports traités</h3>
                <?php if (canView()): ?>
                    <a href="?page=processus_validation" class="cm-btn cm-btn--primary cm-btn--sm" data-cm-ajax-link="true">
                        <i class="fas fa-external-link-alt cm-mr-sm"></i> Voir tout
                    </a>
                <?php endif; ?>
            </div>
            <div class="cm-card__body">
                <div style="overflow-x:auto">
                    <table class="cm-table">
                        <thead>
                            <tr>
                                <th>Étudiant</th>
                                <th>Thème / Titre</th>
                                <th>Validé par</th>
                                <th>Date</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapportsDetails as $r):
                                $statut = strtolower((string) ($r['statut'] ?? ''));
                                $badgeClass = $statut === 'valider' ? 'cm-badge--success' : ($statut === 'rejeter' ? 'cm-badge--danger' : 'cm-badge--info');
                                $label = $statut === 'valider' ? 'Validé' : ($statut === 'rejeter' ? 'Rejeté' : ucfirst($statut));
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars(trim(($r['nom_etudiant'] ?? '') . ' ' . ($r['prenom_etudiant'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><small><?= htmlspecialchars($r['titre'] ?? '', ENT_QUOTES, 'UTF-8') ?></small></td>
                                    <td><?= htmlspecialchars(trim(($r['nom_enseignant'] ?? '') . ' ' . ($r['prenom_enseignant'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td><?= !empty($r['date_validation']) ? htmlspecialchars(date('d/m/Y', strtotime((string) $r['date_validation'])), ENT_QUOTES, 'UTF-8') : '-' ?>
                                    </td>
                                    <td><span
                                            class="cm-badge <?= $badgeClass ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="cm-card cm-mt-md">
        <div class="cm-card__header cm-flex-between">
            <div><h3 class="cm-card__title"><i class="fas fa-comments cm-mr-sm"></i>Observations des membres</h3><p class="cm-card__subtitle">Cliquez sur un membre pour voir les rapports et observations qu’il a renseignés.</p></div>
        </div>
        <div class="cm-card__body">
            <?php if ($observationMembers === []): ?>
                <p class="cm-empty-state">Aucun membre reçu ou observateur enregistré pour le moment.</p>
            <?php else: ?>
                <div class="cm-flex cm-flex-wrap cm-flex-gap-sm">
                    <?php foreach ($observationMembers as $member): ?>
                        <?php $memberKey = (string) ($member['member_key'] ?? ''); ?>
                        <a class="cm-observation-member" href="?page=dashboard_commission&member=<?= urlencode($memberKey) ?>">
                            <strong><?= htmlspecialchars((string) ($member['membre'] ?? 'Membre'), ENT_QUOTES, 'UTF-8') ?></strong>
                            <span><?= (int) ($member['nb_recus'] ?? 0) ?> reçu(s), <?= (int) ($member['nb_observations'] ?? 0) ?> observation(s), <?= (int) ($member['nb_commentaires'] ?? 0) ?> commentaire(s)</span>
                            <?php if ((int) ($member['nb_observations'] ?? 0) === 0): ?><small>RAS — aucune observation renseignée</small><?php endif; ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($observationDetail !== []): ?>
        <div class="cm-card cm-mt-md">
            <div class="cm-card__header cm-flex-between"><h3 class="cm-card__title">Détail des observations</h3><a class="cm-btn is-light is-sm" href="?page=dashboard_commission">Fermer</a></div>
            <div class="cm-card__body">
                <div class="cm-table-wrapper">
                    <table class="cm-table"><thead><tr><th>Rapport</th><th>Étudiant</th><th>Décision</th><th>Observation</th><th>Date</th></tr></thead><tbody>
                    <?php foreach ($observationDetail as $observation): ?>
                        <tr>
                            <td><?= htmlspecialchars((string) ($observation['nom_rapport'] ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(trim((string) ($observation['nom_etu'] ?? '') . ' ' . (string) ($observation['prenom_etu'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string) ($observation['decision_evaluation'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(trim((string) ($observation['commentaire'] ?? '')) ?: 'RAS', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= !empty($observation['date_evaluation']) ? htmlspecialchars(date('d/m/Y H:i', strtotime((string) $observation['date_evaluation'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody></table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</section>

<style>
    .cm-observation-member { display: inline-flex; flex-direction: column; gap: .2rem; min-width: 13rem; padding: .75rem 1rem; border: 1px solid var(--cm-border-color, #d9e1e8); border-radius: .65rem; color: inherit; text-decoration: none; background: rgba(42,95,130,.03); }
    .cm-observation-member:hover { border-color: var(--cm-primary, #2a5f82); background: rgba(42,95,130,.08); }
    .cm-observation-member span { font-size: .78rem; color: var(--cm-text-muted, #64748b); }
</style>

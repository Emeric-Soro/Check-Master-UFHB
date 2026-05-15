<?php
$data = $GLOBALS['repertoire_data'] ?? [];
$teacherName = (string) ($data['teacher_name'] ?? 'Enseignant');
$tab = (string) ($data['tab'] ?? 'rapports');
$items = (array) ($data['data'] ?? []);
$pagination = (array) ($data['pagination'] ?? []);
$anneeOptions = (array) ($data['annee_options'] ?? []);
$sessionOptions = (array) ($data['session_options'] ?? []);
$filtreAnnee = $data['filtre_annee'] ?? null;
$filtreSession = $data['filtre_session'] ?? null;
$search = (string) ($data['search'] ?? '');
$error = (string) ($data['error'] ?? '');
$tabCounts = (array) ($data['tab_counts'] ?? ['rapports' => 0, 'comptes_rendus' => 0, 'memoires' => 0]);
function formatDate(?string $date): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    $ts = strtotime($date);
    return $ts !== false ? date('d/m/Y', $ts) : '—';
}
function truncate(string $text, int $max = 80): string {
    if (strlen($text) <= $max) return $text;
    return substr($text, 0, $max - 3) . '...';
}
$tabs = [
    ['id' => 'rapports', 'label' => 'Rapports (' . $tabCounts['rapports'] . ')'],
    ['id' => 'comptes_rendus', 'label' => 'Comptes-rendus (' . $tabCounts['comptes_rendus'] . ')'],
    ['id' => 'memoires', 'label' => 'Mémoires (' . $tabCounts['memoires'] . ')'],
];
$baseUrlParams = http_build_query([
    'page' => 'repertoire_enseignant',
    'tab' => $tab,
    'id_annee_acad' => $filtreAnnee ?? '',
    'id_session' => $filtreSession ?? '',
    'search' => $search,
]);
?>
<section class="cm-prd3-screen">
    <?php if ($error): ?>
        <div class="cm-alert cm-alert--danger cm-mb-md">
            <i class="fas fa-circle-exclamation cm-mr-sm"></i>
            <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php cm_toolbar([
        'screen' => 'repertoire_enseignant',
        'id_prefix' => 'repertoire',
        'search_value' => $search,
        'limit' => 10,
        'can_delete' => canDelete(),
        'can_view' => canView(),
    ]); ?>
    <?= cm_component('tabs/tab-nav', [
        'tabs' => $tabs,
        'active' => $tab,
    ]) ?>
    <?php if ($tab === 'rapports'): ?>
        <?php ob_start(); ?>
        <table class="cm-table">
            <thead>
                <tr>
                    <th>Nom &amp; Prénom Étudiant</th>
                    <th>N° Carte</th>
                    <th>Thème</th>
                    <th>Statut</th>
                    <th>Date dépôt</th>
                    <th>Année acad.</th>
                    <th>Période/Session</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8">
                            <?= cm_component('ui/empty-state', [
                                'title' => '',
                                'message' => 'Aucun rapport associe pour les critères sélectionnés.',
                                'icon' => 'fa-file-alt',
                                'in_table' => true,
                                'colspan' => 8
                            ]) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(strtoupper($item['nom_etu'] ?? '') . ' ' . ($item['prenom_etu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars($item['num_carte_etud'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(truncate($item['theme_rapport'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php $statut = $item['statut_rapport'] ?? 'inconnu'; ?>
                                <span class="cm-badge cm-badge--<?= $statut === 'valide' ? 'success' : ($statut === 'en_attente' ? 'warning' : 'info') ?>">
                                    <?= htmlspecialchars($statut, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td><?= formatDate($item['date_redaction_rapport'] ?? null) ?></td>
                            <td><?= htmlspecialchars($item['annee_academique'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($item['lib_session'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <button type="button"
                                   class="cm-btn cm-btn--sm cm-btn--ghost" title="Voir"
                                   onclick="CM.openDocViewer('rapport', '<?= htmlspecialchars((string) ($item['id_rapport'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars($item['theme_rapport'] ?? 'Rapport', ENT_QUOTES, 'UTF-8') ?>'})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="?page=docviewer&type=rapport&id=<?= urlencode((string) ($item['id_rapport'] ?? '')) ?>&action=download"
                                   class="cm-btn cm-btn--sm cm-btn--ghost" title="Télécharger">
                                    <i class="fas fa-download"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php 
        $tableContent = ob_get_clean();
        echo cm_component('tabs/tab-content', [
            'id' => 'rapports',
            'active' => $tab === 'rapports',
            'content' => $tableContent
        ]);
        ?>
    <?php elseif ($tab === 'comptes_rendus'): ?>
        <?php ob_start(); ?>
        <table class="cm-table">
            <thead>
                <tr>
                    <th>Nom CR</th>
                    <th>Rapports inclus</th>
                    <th>Rédigé par</th>
                    <th>Date rédaction</th>
                    <th>Statut</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="6">
                            <?= cm_component('ui/empty-state', [
                                'title' => '',
                                'message' => 'Aucun compte-rendu trouvé pour les critères sélectionnés.',
                                'icon' => 'fa-file-contract',
                                'in_table' => true,
                                'colspan' => 6
                            ]) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(truncate($item['nom_CR'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars($item['rapports_inclus'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(strtoupper($item['nom_etu'] ?? '') . ' ' . ($item['prenom_etu'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= formatDate($item['date_CR'] ?? null) ?></td>
                            <td>
                                <?php $statutCR = $item['statut_CR'] ?? ''; ?>
                                <span class="cm-badge cm-badge--<?= $statutCR === 'Publié' ? 'success' : 'warning' ?>">
                                    <?= htmlspecialchars($statutCR !== '' ? $statutCR : 'Brouillon', ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <button type="button"
                                   class="cm-btn cm-btn--sm cm-btn--ghost" title="Voir"
                                   onclick="CM.openDocViewer('compte_rendu', '<?= htmlspecialchars((string) ($item['id_CR'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars($item['nom_CR'] ?? 'Compte-rendu', ENT_QUOTES, 'UTF-8') ?>'})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="?page=docviewer&type=compte_rendu&id=<?= urlencode((string) ($item['id_CR'] ?? '')) ?>&action=download"
                                   class="cm-btn cm-btn--sm cm-btn--ghost" title="Télécharger PDF">
                                    <i class="fas fa-download"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php 
        $tableContent = ob_get_clean();
        echo cm_component('tabs/tab-content', [
            'id' => 'comptes_rendus',
            'active' => $tab === 'comptes_rendus',
            'content' => $tableContent
        ]);
        ?>
    <?php else: // memoires ?>
        <?php ob_start(); ?>
        <table class="cm-table">
            <thead>
                <tr>
                    <th>Nom &amp; Prénom</th>
                    <th>N° Carte</th>
                    <th>Thème</th>
                    <th>Date dépôt</th>
                    <th>Taille (Mo)</th>
                    <th>Année acad.</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="7">
                            <?= cm_component('ui/empty-state', [
                                'title' => '',
                                'message' => 'Aucun mémoire trouvé pour les critères sélectionnés.',
                                'icon' => 'fa-graduation-cap',
                                'in_table' => true,
                                'colspan' => 7
                            ]) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td>
                                <?= htmlspecialchars(strtoupper($item['nom_etu'] ?? '') . ' ' . ($item['prenom_etu'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><?= htmlspecialchars($item['num_carte_etud'] ?? '', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars(truncate($item['theme_soutenance'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= formatDate($item['date_depot_memoire'] ?? $item['date_soutenance'] ?? null) ?></td>
                            <td><?= htmlspecialchars(!empty($item['taille_fichier']) ? number_format((float) $item['taille_fichier'] / 1048576, 2) . ' Mo' : '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($item['annee_academique'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <button type="button"
                                   class="cm-btn cm-btn--sm cm-btn--ghost" title="Voir"
                                   onclick="CM.openDocViewer('pv_final', '<?= htmlspecialchars((string) ($item['num_soutenance'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars($item['theme_soutenance'] ?? 'PV final', ENT_QUOTES, 'UTF-8') ?>'})">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="?page=docviewer&type=pv_final&id=<?= urlencode((string) ($item['num_soutenance'] ?? '')) ?>&action=download"
                                   class="cm-btn cm-btn--sm cm-btn--ghost" title="Télécharger">
                                    <i class="fas fa-download"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php 
        $tableContent = ob_get_clean();
        echo cm_component('tabs/tab-content', [
            'id' => 'memoires',
            'active' => $tab === 'memoires',
            'content' => $tableContent
        ]);
        ?>
    <?php endif; ?>
    <?php if (!empty($pagination) && $pagination['total'] > 0): ?>
        <div class="cm-mt-md">
            <?= cm_component('crud/pagination', [
                'pagination' => (object) $pagination,
                'base_url' => '?page=repertoire_enseignant&tab=' . urlencode($tab) . 
                             '&id_annee_acad=' . urlencode((string)($filtreAnnee ?? '')) . 
                             '&id_session=' . urlencode((string)($filtreSession ?? '')) . 
                             '&search=' . urlencode($search)
            ]) ?>
        </div>
    <?php endif; ?>
</section>

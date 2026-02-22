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
$uploadsBase = __DIR__ . '/../../ressources/uploads/';
function formatDate(?string $date): string {
    if (empty($date) || $date === '0000-00-00') return '—';
    $ts = strtotime($date);
    return $ts !== false ? date('d/m/Y', $ts) : '—';
}
function truncate(string $text, int $max = 80): string {
    if (strlen($text) <= $max) return $text;
    return substr($text, 0, $max - 3) . '...';
}
function fileExistsSafe(?string $path, string $base): bool {
    if (empty($path)) return false;
    $full = realpath($base . ltrim($path, '/\\'));
    return $full !== false && strpos($full, realpath($base)) === 0 && file_exists($full);
}
$tabs = [
    ['id' => 'rapports', 'label' => 'Rapports (' . $tabCounts['rapports'] . ')'],
    ['id' => 'comptes_rendus', 'label' => 'Comptes-rendus (' . $tabCounts['comptes_rendus'] . ')'],
    ['id' => 'memoires', 'label' => 'Memoires (' . $tabCounts['memoires'] . ')'],
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
    <div class="cm-card cm-mb-md">
        <form method="GET" class="cm-grid-4" style="align-items: end;">
            <input type="hidden" name="page" value="repertoire_enseignant">
            <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_annee_acad" value="<?= htmlspecialchars((string) (\AcademicYear::getWritableIdFromSession() ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <?= cm_component('form/select', [
                'name' => 'id_session',
                'label' => 'Période',
                'options' => $sessionOptions,
                'selected' => (string)($filtreSession ?? ''),
                'placeholder' => 'Toutes les périodes'
            ]) ?>
            <div>
                <label class="cm-form__label" for="search">Recherche</label>
                <input type="text" id="search" name="search" class="cm-form__input" 
                       value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" 
                       placeholder="Nom, theme...">
            </div>
            <div class="cm-flex cm-flex-gap-sm">
                <button type="submit" class="cm-btn cm-btn--primary">
                    <i class="fas fa-filter cm-mr-sm"></i> Filtrer
                </button>
                <a href="?page=repertoire_enseignant&tab=<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>" 
                   class="cm-btn cm-btn--outline">
                    Réinitialiser
                </a>
            </div>
        </form>
    </div>
    <?= cm_component('tabs/tab-nav', [
        'tabs' => $tabs,
        'active' => $tab,
    ]) ?>
    <?php if ($tab === 'rapports'): ?>
        <?php ob_start(); ?>
        <table class="cm-table">
            <thead>
                <tr>
                    <th>Etudiant</th>
                    <th>N° Carte</th>
                    <th>Theme</th>
                    <th>Statut</th>
                    <th>Date</th>
                    <th>Annee</th>
                    <th>Periode</th>
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
                                <?php if (fileExistsSafe($item['chemin_fichier'] ?? null, $uploadsBase)): ?>
                                    <a href="?page=download&file=<?= urlencode($item['chemin_fichier']) ?>" 
                                       class="cm-btn cm-btn--sm cm-btn--ghost" title="Telecharger">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="cm-text-muted" title="Fichier non disponible">
                                        <i class="fas fa-ban"></i>
                                    </span>
                                <?php endif; ?>
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
                    <th>Etudiant</th>
                    <th>N° Carte</th>
                    <th>Titre CR</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="5">
                            <?= cm_component('ui/empty-state', [
                                'title' => '',
                                'message' => 'Aucun compte-rendu trouve pour les critères sélectionnés.',
                                'icon' => 'fa-file-contract',
                                'in_table' => true,
                                'colspan' => 5
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
                            <td><?= htmlspecialchars(truncate($item['nom_CR'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= formatDate($item['date_CR'] ?? null) ?></td>
                            <td>
                                <?php if (fileExistsSafe($item['chemin_fichier_pdf'] ?? null, $uploadsBase)): ?>
                                    <a href="?page=download&file=<?= urlencode($item['chemin_fichier_pdf']) ?>" 
                                       class="cm-btn cm-btn--sm cm-btn--ghost" title="Telecharger PDF">
                                        <i class="fas fa-download"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="cm-text-muted" title="Fichier non disponible">
                                        <i class="fas fa-ban"></i>
                                    </span>
                                <?php endif; ?>
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
                    <th>Etudiant</th>
                    <th>N° Carte</th>
                    <th>Theme</th>
                    <th>Date soutenance</th>
                    <th>Heure</th>
                    <th>Periode</th>
                    <th>Annee</th>
                    <th>Note</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($items)): ?>
                    <tr>
                        <td colspan="8">
                            <?= cm_component('ui/empty-state', [
                                'title' => '',
                                'message' => 'Aucune soutenance trouvee pour les critères sélectionnés.',
                                'icon' => 'fa-graduation-cap',
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
                            <td><?= htmlspecialchars(truncate($item['theme_soutenance'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= formatDate($item['date_soutenance'] ?? null) ?></td>
                            <td><?= !empty($item['heure_soutenance']) ? htmlspecialchars(substr($item['heure_soutenance'], 0, 5), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                            <td><?= htmlspecialchars($item['lib_session'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars($item['annee_academique'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php 
                                $note = (float) ($item['note_memoire'] ?? 0);
                                echo $note > 0 ? number_format($note, 2, ',', ' ') : '—';
                                ?>
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

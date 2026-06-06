<?php
/**
 * Archives Mémoires
 * Affiche la liste des mémoires déposés dans la base documents,
 * avec métadonnées (thème, étudiant), évaluations et statut.
 *
 * Données: $memoires (array), $memoiresCount (int)
 */
$memoires = is_array($memoires ?? null) ? $memoires : (is_array($GLOBALS['memoires'] ?? null) ? $GLOBALS['memoires'] : []);
$memoiresCount = (int) ($memoiresCount ?? $GLOBALS['memoiresCount'] ?? count($memoires));
$isHubContext = (string) ($_GET['page'] ?? '') === 'commissions_archives';
$baseUrl = $isHubContext ? '?page=commissions_archives&tab=archives_memoires' : '?page=archives_memoires';

$formatDate = static function (?string $value): string {
    if (!is_string($value) || trim($value) === '') return '—';
    $ts = strtotime($value);
    return $ts ? date('d/m/Y', $ts) : '—';
};

$truncate = static function (string $text, int $max = 80): string {
    $text = trim($text);
    if ($text === '') return '—';
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') <= $max ? $text : mb_substr($text, 0, $max - 3, 'UTF-8') . '...';
    }
    return strlen($text) <= $max ? $text : substr($text, 0, $max - 3) . '...';
};

$renderDecisionBadge = static function (string $decisions): string {
    $decisions = trim($decisions);
    if ($decisions === '') {
        return '<span class="cm-badge cm-badge--warning">En attente</span>';
    }
    $parts = array_map('trim', explode(',', $decisions));
    $allValider = true;
    $allRejeter = true;
    foreach ($parts as $d) {
        if ($d === 'valider') $allRejeter = false;
        elseif ($d === 'rejeter') $allValider = false;
        else { $allValider = false; $allRejeter = false; }
    }
    if ($allValider) return '<span class="cm-badge cm-badge--success">Validé</span>';
    if ($allRejeter) return '<span class="cm-badge cm-badge--danger">Rejeté</span>';
    return '<span class="cm-badge cm-badge--info">Mixte (' . count($parts) . ' avis)</span>';
};

$formatTaille = static function (?string $bytes): string {
    if ($bytes === null || $bytes === '' || (int) $bytes <= 0) return '—';
    $b = (int) $bytes;
    if ($b < 1024) return $b . ' o';
    if ($b < 1048576) return round($b / 1024, 1) . ' Ko';
    return round($b / 1048576, 2) . ' Mo';
};
?>
<div class="cm-archive-memoires">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-header__title">
            <i class="fas fa-graduation-cap cm-mr-sm"></i> Mémoires archivés
        </h1>
        <p class="cm-page-header__subtitle">
            <?= $memoiresCount ?> mémoire(s) trouvé(s)
        </p>
    </div>

    <?php if (empty($memoires)): ?>
        <?php cm_component('ui/empty-state', [
            'title' => 'Aucun mémoire',
            'message' => 'Aucun mémoire archivé trouvé pour la période sélectionnée.',
            'icon' => 'fa-graduation-cap',
        ]); ?>
    <?php else: ?>
        <div class="cm-table-wrapper">
            <table class="cm-data-table">
                <thead>
                    <tr>
                        <th class="cm-data-table__th">Étudiant</th>
                        <th class="cm-data-table__th">Thème</th>
                        <th class="cm-data-table__th">Date dépôt</th>
                        <th class="cm-data-table__th is-center">Taille</th>
                        <th class="cm-data-table__th is-center">Version</th>
                        <th class="cm-data-table__th is-center">Évaluations</th>
                        <th class="cm-data-table__th is-center">Statut</th>
                        <th class="cm-data-table__th is-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($memoires as $m): 
                        $idDoc = (int) ($m['id_document'] ?? 0);
                        $nomFichier = (string) ($m['nom_fichier'] ?? '');
                    ?>
                        <tr class="cm-data-table__row">
                            <td class="cm-data-table__td">
                                <strong><?= htmlspecialchars((string) ($m['etudiant_nom'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                                <br>
                                <span class="cm-text-muted" style="font-size:0.85em;">
                                    <?= htmlspecialchars((string) ($m['num_carte_etud'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td class="cm-data-table__td">
                                <?= htmlspecialchars($truncate((string) ($m['theme_memoire'] ?? $m['theme_soutenance'] ?? '—')), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="cm-data-table__td">
                                <?= htmlspecialchars($formatDate($m['date_depot'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="cm-data-table__td is-center">
                                <?= htmlspecialchars($formatTaille($m['taille_fichier'] ?? null), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td class="cm-data-table__td is-center">
                                v<?= (int) ($m['version'] ?? 1) ?>
                            </td>
                            <td class="cm-data-table__td is-center">
                                <?= (int) ($m['nb_evaluations'] ?? 0) ?>
                            </td>
                            <td class="cm-data-table__td is-center">
                                <?= $renderDecisionBadge((string) ($m['decisions'] ?? '')) ?>
                            </td>
                            <td class="cm-data-table__td is-center">
                                <div class="cm-table-actions">
                                    <button type="button"
                                            class="cm-btn-action is-view"
                                            title="Voir le mémoire"
                                            onclick="CM.openDocViewer('document', '<?= $idDoc ?>', {title: '<?= htmlspecialchars($truncate((string) ($m['theme_memoire'] ?? 'Mémoire'), 40), ENT_QUOTES) ?>'})">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="?page=docviewer&type=document&id=<?= $idDoc ?>&action=download"
                                       class="cm-btn-action is-download"
                                       title="Télécharger">
                                        <i class="fas fa-download"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

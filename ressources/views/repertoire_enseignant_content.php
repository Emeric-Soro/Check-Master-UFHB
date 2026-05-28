<?php
$data = is_array($GLOBALS['repertoire_data'] ?? null) ? $GLOBALS['repertoire_data'] : [];

$teacherId = trim((string) ($data['teacher_id'] ?? ''));
$teacherName = trim((string) ($data['teacher_name'] ?? 'Enseignant'));
$tab = (string) ($data['tab'] ?? 'rapports');
$items = is_array($data['data'] ?? null) ? $data['data'] : [];
$pagination = is_array($data['pagination'] ?? null) ? $data['pagination'] : [];
$anneeOptions = is_array($data['annee_options'] ?? null) ? $data['annee_options'] : [];
$sessionOptions = is_array($data['session_options'] ?? null) ? $data['session_options'] : [];
$filtreAnnee = $data['filtre_annee'] ?? null;
$filtreSession = $data['filtre_session'] ?? null;
$search = trim((string) ($data['search'] ?? ''));
$error = trim((string) ($data['error'] ?? ''));
$tabCounts = array_merge(
    ['rapports' => 0, 'comptes_rendus' => 0, 'memoires' => 0],
    is_array($data['tab_counts'] ?? null) ? $data['tab_counts'] : []
);

if (!in_array($tab, ['rapports', 'comptes_rendus', 'memoires'], true)) {
    $tab = 'rapports';
}

$formatDate = static function (?string $date): string {
    if ($date === null || $date === '' || $date === '0000-00-00') {
        return '—';
    }

    $timestamp = strtotime($date);
    return $timestamp !== false ? date('d/m/Y', $timestamp) : '—';
};

$truncate = static function (string $text, int $max = 80): string {
    $text = trim($text);
    if ($text === '') {
        return '—';
    }

    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($text, 'UTF-8') <= $max
            ? $text
            : mb_substr($text, 0, $max - 3, 'UTF-8') . '...';
    }

    return strlen($text) <= $max ? $text : substr($text, 0, $max - 3) . '...';
};

$buildUrl = static function (array $overrides = []) use ($tab, $filtreAnnee, $filtreSession, $search): string {
    $params = [
        'page' => 'repertoire_enseignant',
        'tab' => $tab,
        'id_annee_acad' => $filtreAnnee,
        'id_session' => $filtreSession,
        'search' => $search,
    ];

    foreach ($overrides as $key => $value) {
        if ($value === null) {
            unset($params[$key]);
            continue;
        }
        $params[$key] = $value;
    }

    foreach ($params as $key => $value) {
        if ($value === null || $value === '') {
            unset($params[$key]);
        }
    }

    return '?' . http_build_query($params);
};

$renderStatusBadge = static function (string $status, string $type = 'rapport'): string {
    $normalized = trim($status);

    if ($type === 'compte_rendu') {
        $badgeClass = $normalized === 'Publié' ? 'success' : 'warning';
        $label = $normalized !== '' ? $normalized : 'Brouillon';
    } else {
        $badgeClass = 'info';
        if ($normalized === 'valide') {
            $badgeClass = 'success';
        } elseif ($normalized === 'en_attente') {
            $badgeClass = 'warning';
        } elseif ($normalized === 'rejete') {
            $badgeClass = 'danger';
        }

        $label = $normalized !== ''
            ? ucfirst(str_replace('_', ' ', $normalized))
            : 'Inconnu';
    }

    return '<span class="cm-badge cm-badge--' . htmlspecialchars($badgeClass, ENT_QUOTES, 'UTF-8') . '">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '</span>';
};

$tabs = [
    [
        'id' => 'rapports',
        'label' => 'Rapports (' . (int) ($tabCounts['rapports'] ?? 0) . ')',
        'href' => $buildUrl(['tab' => 'rapports', 'page_num' => null]),
    ],
    [
        'id' => 'comptes_rendus',
        'label' => 'Comptes-rendus (' . (int) ($tabCounts['comptes_rendus'] ?? 0) . ')',
        'href' => $buildUrl(['tab' => 'comptes_rendus', 'page_num' => null]),
    ],
    [
        'id' => 'memoires',
        'label' => 'Mémoires (' . (int) ($tabCounts['memoires'] ?? 0) . ')',
        'href' => $buildUrl(['tab' => 'memoires', 'page_num' => null]),
    ],
];

$resultTotal = (int) ($pagination['total'] ?? count($items));
?>
<section class="cm-prd3-screen cm-prd3-crud-screen">
    <?php if ($error !== ''): ?>
        <?php cm_component('ui/alert-box', [
            'type' => 'danger',
            'message' => $error,
        ]); ?>
    <?php endif; ?>

    <?php if ($teacherId === ''): ?>
        <?php cm_component('ui/empty-state', [
            'title' => 'Aucun enseignant rattaché',
            'message' => "Cette page ne peut pas charger le répertoire tant qu'aucun profil enseignant n'est lié à ce compte.",
            'icon' => 'fa-user-slash',
        ]); ?>
        <?php return; ?>
    <?php endif; ?>

    <div class="cm-page-header">
        <div class="cm-page-header__main">
            <div class="cm-page-header__heading">
                <h1 class="cm-page-header__title">
                    <i class="fas fa-folder-open" aria-hidden="true"></i>
                    Répertoire des documents
                </h1>
                <p class="cm-page-header__subtitle">
                    <?= htmlspecialchars($teacherName, ENT_QUOTES, 'UTF-8') ?>
                    <?php if ($teacherId !== ''): ?>
                        · <?= htmlspecialchars($teacherId, ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </p>
            </div>
        </div>
    </div>

    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur is-compact">
            <form method="GET" action="">
                <input type="hidden" name="page" value="repertoire_enseignant">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">

                <div class="cm-grid-4">
                    <?php
                    cm_component('form/select', [
                        'name' => 'id_annee_acad',
                        'label' => 'Année académique',
                        'options' => $anneeOptions,
                        'selected' => (string) ($filtreAnnee ?? ''),
                        'placeholder' => '-- Toutes les années --',
                    ]);

                    cm_component('form/select', [
                        'name' => 'id_session',
                        'label' => 'Session',
                        'options' => $sessionOptions,
                        'selected' => (string) ($filtreSession ?? ''),
                        'placeholder' => '-- Toutes les sessions --',
                    ]);

                    cm_component('form/input-text', [
                        'name' => 'search',
                        'label' => 'Recherche',
                        'value' => $search,
                        'placeholder' => 'Étudiant, thème, document...',
                        'control_class' => 'cm-field-lg',
                    ]);
                    ?>
                </div>

                <div class="cm-flex cm-flex-wrap cm-flex-gap-sm cm-flex-end">
                    <a href="<?= htmlspecialchars($buildUrl(['id_annee_acad' => null, 'id_session' => null, 'search' => null, 'page_num' => null]), ENT_QUOTES, 'UTF-8') ?>"
                       class="cm-btn is-light is-sm"
                       data-cm-ajax-link="true">
                        <i class="fas fa-rotate-left" aria-hidden="true"></i>
                        Réinitialiser
                    </a>
                    <button type="submit" class="cm-btn is-primary is-sm">
                        <i class="fas fa-filter" aria-hidden="true"></i>
                        Filtrer
                    </button>
                </div>
            </form>
        </div>

        <div class="cm-pole-inferieur">
            <div class="cm-flex-between cm-flex-wrap cm-flex-gap-sm cm-mb-sm">
                <span class="cm-text-muted">
                    <?= $resultTotal ?> résultat(s) dans l'onglet actif
                </span>
            </div>

            <div class="cm-tab-nav" role="tablist" aria-label="Onglets du répertoire enseignant">
                <?php foreach ($tabs as $tabItem): ?>
                    <?php $isActiveTab = $tabItem['id'] === $tab; ?>
                    <a href="<?= htmlspecialchars((string) $tabItem['href'], ENT_QUOTES, 'UTF-8') ?>"
                       class="cm-tab-nav__item <?= $isActiveTab ? 'is-active' : '' ?>"
                       role="tab"
                       aria-selected="<?= $isActiveTab ? 'true' : 'false' ?>"
                       <?= $isActiveTab ? 'aria-current="page"' : '' ?>
                       data-cm-ajax-link="true">
                        <?= htmlspecialchars((string) $tabItem['label'], ENT_QUOTES, 'UTF-8') ?>
                    </a>
                <?php endforeach; ?>
            </div>

            <section class="cm-tab-panel is-active" data-tab-panel="<?= htmlspecialchars($tab, ENT_QUOTES, 'UTF-8') ?>">
                <div class="cm-table-wrapper">
                    <?php if ($tab === 'rapports'): ?>
                        <table class="cm-table">
                            <thead>
                                <tr>
                                    <th>Nom &amp; prénom étudiant</th>
                                    <th>Identifiant</th>
                                    <th>Thème</th>
                                    <th>Statut</th>
                                    <th>Date dépôt</th>
                                    <th>Année acad.</th>
                                    <th>Session</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <?php cm_component('ui/empty-state', [
                                        'title' => 'Aucun rapport',
                                        'message' => 'Aucun rapport associé aux critères sélectionnés.',
                                        'icon' => 'fa-file-alt',
                                        'in_table' => true,
                                        'colspan' => 8,
                                    ]); ?>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $reportId = (string) ($item['id_rapport'] ?? '');
                                        $studentIdentifier = (string) ($item['display_id'] ?? $item['num_carte_etud'] ?? '—');
                                        $fullName = trim(strtoupper((string) ($item['nom_etu'] ?? '')) . ' ' . (string) ($item['prenom_etu'] ?? ''));
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fullName !== '' ? $fullName : '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($studentIdentifier, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($truncate((string) ($item['theme_rapport'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= $renderStatusBadge((string) ($item['statut_rapport'] ?? ''), 'rapport') ?></td>
                                            <td><?= htmlspecialchars($formatDate($item['date_redaction_rapport'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($item['annee_academique'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($item['lib_session'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <button type="button"
                                                        class="cm-btn cm-btn--sm cm-btn--ghost"
                                                        title="Voir"
                                                        onclick="CM.openDocViewer('rapport', '<?= htmlspecialchars($reportId, ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars((string) ($item['theme_rapport'] ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>'})">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                </button>
                                                <a href="?page=docviewer&type=rapport&id=<?= urlencode($reportId) ?>&action=download"
                                                   class="cm-btn cm-btn--sm cm-btn--ghost"
                                                   title="Télécharger">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php elseif ($tab === 'comptes_rendus'): ?>
                        <table class="cm-table">
                            <thead>
                                <tr>
                                    <th>Nom du compte-rendu</th>
                                    <th>Rapports inclus</th>
                                    <th>Étudiant</th>
                                    <th>Date rédaction</th>
                                    <th>Statut</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <?php cm_component('ui/empty-state', [
                                        'title' => 'Aucun compte-rendu',
                                        'message' => 'Aucun compte-rendu trouvé pour les critères sélectionnés.',
                                        'icon' => 'fa-file-contract',
                                        'in_table' => true,
                                        'colspan' => 6,
                                    ]); ?>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $compteRenduId = (string) ($item['id_CR'] ?? '');
                                        $studentName = trim(strtoupper((string) ($item['nom_etu'] ?? '')) . ' ' . (string) ($item['prenom_etu'] ?? ''));
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($truncate((string) ($item['nom_CR'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($item['rapports_inclus'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($studentName !== '' ? $studentName : '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($formatDate($item['date_CR'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= $renderStatusBadge((string) ($item['statut_CR'] ?? ''), 'compte_rendu') ?></td>
                                            <td>
                                                <button type="button"
                                                        class="cm-btn cm-btn--sm cm-btn--ghost"
                                                        title="Voir"
                                                        onclick="CM.openDocViewer('compte_rendu', '<?= htmlspecialchars($compteRenduId, ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars((string) ($item['nom_CR'] ?? 'Compte-rendu'), ENT_QUOTES, 'UTF-8') ?>'})">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                </button>
                                                <a href="?page=docviewer&type=compte_rendu&id=<?= urlencode($compteRenduId) ?>&action=download"
                                                   class="cm-btn cm-btn--sm cm-btn--ghost"
                                                   title="Télécharger PDF">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php else: ?>
                        <table class="cm-table">
                            <thead>
                                <tr>
                                    <th>Nom &amp; prénom étudiant</th>
                                    <th>Identifiant</th>
                                    <th>Thème</th>
                                    <th>Date document</th>
                                    <th>Taille (Mo)</th>
                                    <th>Année acad.</th>
                                    <th>Session</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($items)): ?>
                                    <?php cm_component('ui/empty-state', [
                                        'title' => 'Aucun mémoire',
                                        'message' => 'Aucun mémoire trouvé pour les critères sélectionnés.',
                                        'icon' => 'fa-graduation-cap',
                                        'in_table' => true,
                                        'colspan' => 8,
                                    ]); ?>
                                <?php else: ?>
                                    <?php foreach ($items as $item): ?>
                                        <?php
                                        $soutenanceId = (string) ($item['num_soutenance'] ?? '');
                                        $studentIdentifier = (string) ($item['display_id'] ?? $item['num_carte_etud'] ?? '—');
                                        $fullName = trim(strtoupper((string) ($item['nom_etu'] ?? '')) . ' ' . (string) ($item['prenom_etu'] ?? ''));
                                        $tailleMo = !empty($item['taille_fichier'])
                                            ? number_format((float) $item['taille_fichier'] / 1048576, 2) . ' Mo'
                                            : '—';
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($fullName !== '' ? $fullName : '—', ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($studentIdentifier, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($truncate((string) ($item['theme_soutenance'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($formatDate($item['date_depot_memoire'] ?? $item['date_soutenance'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars($tailleMo, ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($item['annee_academique'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= htmlspecialchars((string) ($item['lib_session'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td>
                                                <button type="button"
                                                        class="cm-btn cm-btn--sm cm-btn--ghost"
                                                        title="Voir"
                                                        onclick="CM.openDocViewer('pv_final', '<?= htmlspecialchars($soutenanceId, ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars((string) ($item['theme_soutenance'] ?? 'PV final'), ENT_QUOTES, 'UTF-8') ?>'})">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                </button>
                                                <a href="?page=docviewer&type=pv_final&id=<?= urlencode($soutenanceId) ?>&action=download"
                                                   class="cm-btn cm-btn--sm cm-btn--ghost"
                                                   title="Télécharger">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

                <?php
                cm_component('crud/pagination', [
                    'pagination' => $pagination,
                    'base_url' => $buildUrl(['page_num' => null]),
                ]);
                ?>
            </section>
        </div>
    </div>
</section>

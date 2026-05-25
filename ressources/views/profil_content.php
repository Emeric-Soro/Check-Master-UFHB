<?php
$nomUser = (string) ($_SESSION['nom_utilisateur'] ?? '');
$loginUser = (string) ($_SESSION['login_utilisateur'] ?? '');
$statutUser = (string) ($_SESSION['statut_utilisateur'] ?? '');

$libTypeUtilisateur = (string) ($_SESSION['type_utilisateur'] ?? '');
$libNiveauAcces = (string) ($_SESSION['niveau_acces'] ?? '');
$libGroupeUtilisateur = (string) ($_SESSION['lib_GU'] ?? '');
$contactEmail = (string) ($GLOBALS['profileContactEmail'] ?? '');

$specialite = (string) ($_SESSION['specialite'] ?? '');
$grade = (string) ($_SESSION['grade'] ?? '');
$fonction = (string) ($_SESSION['fonction'] ?? '');
$dateGrade = (string) ($_SESSION['date_grade'] ?? '');
$dateFonction = (string) ($_SESSION['date_fonction'] ?? '');
$telephone = (string) ($_SESSION['telephone'] ?? '');
$poste = (string) ($_SESSION['poste'] ?? '');
$dateEmbauche = (string) ($_SESSION['date_embauche'] ?? '');

$requestedTab = (string) ($_GET['tab'] ?? 'profile');
$allowedTabs = ['profile', 'password', 'history'];
$currentTab = in_array($requestedTab, $allowedTabs, true) ? $requestedTab : 'profile';

$emailError = (string) ($_SESSION['error'] ?? '');
$emailSuccess = (string) ($_SESSION['success'] ?? '');
$passwordError = (string) ($_SESSION['error'] ?? '');
$passwordSuccess = (string) ($_SESSION['success'] ?? '');
unset($_SESSION['error'], $_SESSION['success']);

$historyLogs = is_array($GLOBALS['profileAuditHistory'] ?? null) ? $GLOBALS['profileAuditHistory'] : [];
$historyFilters = is_array($GLOBALS['profileAuditHistoryFilters'] ?? null) ? $GLOBALS['profileAuditHistoryFilters'] : [
    'date_debut' => '',
    'date_fin' => '',
    'statut' => '',
    'search' => '',
];
$historyPage = max(1, (int) ($GLOBALS['profileAuditHistoryPage'] ?? 1));
$historyPerPage = max(1, (int) ($GLOBALS['profileAuditHistoryPerPage'] ?? 10));
$historyTotalPages = max(1, (int) ($GLOBALS['profileAuditHistoryTotalPages'] ?? 1));
$historyTotal = max(0, (int) ($GLOBALS['profileAuditHistoryTotal'] ?? count($historyLogs)));

$badgeStatut = strtolower($statutUser) === 'actif' ? ['label' => 'Actif', 'type' => 'success'] : ['label' => 'Inactif', 'type' => 'danger'];
$isEnseignant = in_array($libTypeUtilisateur, ['Enseignant simple', 'Enseignant administratif'], true);
$isPersonnel = $libTypeUtilisateur === 'Personnel administratif';

$renderField = static function (string $label, string $value): string {
    return '<div class="cm-profile-item"><span class="cm-profile-item__label">'
        . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
        . '</span><span class="cm-profile-item__value">'
        . htmlspecialchars($value !== '' ? $value : '-', ENT_QUOTES, 'UTF-8')
        . '</span></div>';
};

ob_start();
?>
<section class="cm-profile-card">
    <header class="cm-profile-card__header">

    </header>
    <div class="cm-profile-grid">
        <?= $renderField('Nom utilisateur', $nomUser) ?>
        <?= $renderField('Login', $loginUser) ?>
        <?= $renderField('Email de contact', $contactEmail) ?>
        <?= $renderField('Type utilisateur', $libTypeUtilisateur) ?>
        <?= $renderField('Groupe', $libGroupeUtilisateur) ?>
        <?= $renderField('Niveau d\'accès', $libNiveauAcces) ?>
        <div class="cm-profile-item">
            <span class="cm-profile-item__label">Statut</span>
            <span class="cm-profile-item__value">
                <?php cm_component('ui/badge', ['text' => $badgeStatut['label'], 'type' => $badgeStatut['type']]); ?>
            </span>
        </div>
    </div>
</section>

<section class="cm-profile-card">
    <header class="cm-profile-card__header"></header>

    <?php if ($emailSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $emailSuccess]); ?>
    <?php endif; ?>
    <?php if ($emailError !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $emailError]); ?>
    <?php endif; ?>

    <form action="?page=profil&tab=profile" method="POST" class="cm-profile-email-form" data-cm-ajax-form="true">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\CheckMaster\Core\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id_utilisateur" value="<?php echo (int) ($_SESSION['id_utilisateur'] ?? 0); ?>">

        <div class="cm-profile-grid">
            <div>
                <?php cm_component('form/input-email', [
                    'name' => 'currentContactEmail',
                    'id' => 'currentContactEmail',
                    'label' => 'Email actuel',
                    'value' => $contactEmail,
                    'readonly' => true,
                ]); ?>
            </div>
            <div>
                <?php cm_component('form/input-email', [
                    'name' => 'newEmail',
                    'id' => 'newEmail',
                    'label' => 'Nouvel email de contact',
                    'required' => true,
                    'value' => $contactEmail,
                    'hint' => 'Adresse utilisée pour les notifications email.',
                    'maxlength' => 100,
                    'attrs' => ['autocomplete' => 'email'],
                ]); ?>
            </div>
            <div>
                <?php cm_component('form/input-email', [
                    'name' => 'confirmEmail',
                    'id' => 'confirmEmail',
                    'label' => 'Confirmer l\'email de contact',
                    'required' => true,
                    'maxlength' => 100,
                    'attrs' => ['autocomplete' => 'email'],
                ]); ?>
            </div>
        </div>

        <div class="cm-form-buttons">
            <button type="submit" name="update_email" class="cm-btn is-primary">
                <i class="fas fa-envelope"></i>
                <span>Mettre à jour l'email de contact</span>
            </button>
        </div>
    </form>
</section>

<section class="cm-profile-card">
    <header class="cm-profile-card__header">

    </header>
    <div class="cm-profile-grid">
        <?php if ($isEnseignant): ?>
            <?= $renderField('Spécialité', $specialite) ?>
            <?= $renderField('Grade', $grade) ?>
            <?= $renderField('Fonction', $fonction) ?>
            <?= $renderField('Date grade', $dateGrade) ?>
            <?= $renderField('Date fonction', $dateFonction) ?>
            <?= $renderField('Téléphone', $telephone) ?>
        <?php elseif ($isPersonnel): ?>
            <?= $renderField('Téléphone', $telephone) ?>
            <?= $renderField('Poste', $poste) ?>
            <?= $renderField('Date embauche', $dateEmbauche) ?>
        <?php else: ?>
            <?= $renderField('Profil', 'Données complémentaires non disponibles pour ce type') ?>
        <?php endif; ?>
    </div>
</section>
<?php
$profileTabHtml = (string) ob_get_clean();

ob_start();
?>
<section class="cm-profile-card">
    <header class="cm-profile-card__header">

    </header>

    <?php if ($passwordSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $passwordSuccess]); ?>
    <?php endif; ?>
    <?php if ($passwordError !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $passwordError]); ?>
    <?php endif; ?>

    <form action="?page=profil&tab=password" method="POST" class="cm-profile-password-form" data-cm-ajax-form="true">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars(\CheckMaster\Core\Csrf::token(), ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="id_utilisateur" value="<?php echo (int) ($_SESSION['id_utilisateur'] ?? 0); ?>">

        <div class="cm-profile-grid">
            <div>
                <?php cm_component('form/input-password', [
                    'name' => 'currentPassword',
                    'id' => 'currentPassword',
                    'label' => 'Mot de passe actuel',
                    'required' => true,
                    'attrs' => ['autocomplete' => 'current-password'],
                ]); ?>
            </div>
            <div>
                <?php cm_component('form/input-password', [
                    'name' => 'newPassword',
                    'id' => 'newPassword',
                    'label' => 'Nouveau mot de passe',
                    'required' => true,
                    'attrs' => ['autocomplete' => 'new-password'],
                ]); ?>
            </div>
            <div>
                <?php cm_component('form/input-password', [
                    'name' => 'confirmPassword',
                    'id' => 'confirmPassword',
                    'label' => 'Confirmer le mot de passe',
                    'required' => true,
                    'attrs' => ['autocomplete' => 'new-password'],
                ]); ?>
            </div>
        </div>

        <div class="cm-form-buttons">
            <button type="submit" name="update_password" class="cm-btn is-success">
                <i class="fas fa-save"></i>
                <span>Mettre à jour le mot de passe</span>
            </button>
        </div>
    </form>
</section>
<?php
$passwordTabHtml = (string) ob_get_clean();

ob_start();
?>
<section class="cm-profile-card">
    <header class="cm-profile-card__header"></header>
    <form method="GET" class="cm-profile-history-filters">
        <input type="hidden" name="page" value="profil">
        <input type="hidden" name="tab" value="history">
        <div class="cm-grid-4">
            <?php cm_component('form/input-date', [
                'name' => 'history_date_debut',
                'label' => 'Date début',
                'value' => (string) ($historyFilters['date_debut'] ?? ''),
            ]); ?>
            <?php cm_component('form/input-date', [
                'name' => 'history_date_fin',
                'label' => 'Date fin',
                'value' => (string) ($historyFilters['date_fin'] ?? ''),
            ]); ?>
            <?php cm_component('form/select', [
                'name' => 'history_statut',
                'label' => 'Statut',
                'options' => [
                    '' => '-- Tous --',
                    'Succès' => 'Succès',
                    'Erreur' => 'Erreur',
                ],
                'selected' => (string) ($historyFilters['statut'] ?? ''),
            ]); ?>
            <?php cm_component('form/select', [
                'name' => 'history_limit',
                'label' => 'Lignes',
                'options' => [
                    '10' => '10',
                    '25' => '25',
                    '50' => '50',
                ],
                'selected' => (string) $historyPerPage,
            ]); ?>
        </div>
        <div class="cm-grid-1">
            <?php cm_component('form/input-text', [
                'name' => 'history_search',
                'label' => 'Recherche',
                'value' => (string) ($historyFilters['search'] ?? ''),
                'placeholder' => 'Action ou contexte...',
            ]); ?>
        </div>
        <div class="cm-form-buttons">
            <a href="?page=profil&tab=history" class="cm-btn is-light">Réinitialiser</a>
            <button type="submit" class="cm-btn is-primary">Filtrer</button>
        </div>
    </form>
</section>

<?php
$historyRows = [];
foreach ($historyLogs as $log) {
    $dateCreation = (string) ($log['date_creation'] ?? '');
    $dateText = $dateCreation !== '' ? date('d/m/Y H:i:s', strtotime($dateCreation)) : '-';
    $statut = (string) ($log['statut_action'] ?? '');
    $badgeType = 'info';
    if (strcasecmp($statut, 'Succès') === 0 || strcasecmp($statut, 'Succes') === 0) {
        $badgeType = 'success';
    } elseif (strcasecmp($statut, 'Erreur') === 0 || strcasecmp($statut, 'Echec') === 0) {
        $badgeType = 'danger';
    }

    $historyRows[] = [
        'id' => (string) ($log['id_piste'] ?? ''),
        'date_creation' => $dateText,
        'action' => function_exists('cm_audit_humanize_action')
            ? cm_audit_humanize_action($log)
            : (string) ($log['action'] ?? '-'),
        'contexte' => function_exists('cm_audit_humanize_context')
            ? cm_audit_humanize_context($log)
            : (string) ($log['nom_table'] ?? '-'),
        'statut_action' => ['label' => $statut === '' ? '-' : $statut, 'type' => $badgeType],
    ];
}

$historyPagination = cm_paginate($historyTotal, $historyPerPage, $historyPage);
$historyPagination['last'] = $historyTotalPages;
$historyQuery = array_filter([
    'page' => 'profil',
    'tab' => 'history',
    'history_date_debut' => (string) ($historyFilters['date_debut'] ?? ''),
    'history_date_fin' => (string) ($historyFilters['date_fin'] ?? ''),
    'history_statut' => (string) ($historyFilters['statut'] ?? ''),
    'history_search' => (string) ($historyFilters['search'] ?? ''),
    'history_limit' => (string) $historyPerPage,
], static function ($value) {
    return $value !== '';
});
$historyPagerBase = '?' . http_build_query($historyQuery);
?>
<section class="cm-profile-card">
    <?php cm_component('crud/data-table', [
        'id' => 'cmProfileAuditHistoryTable',
        'columns' => [
            cm_column('date_creation', 'Date &amp; heure'),
            cm_column('action', 'Action'),
            cm_column('contexte', 'Contexte'),
            cm_column('statut_action', 'Statut', ['type' => 'badge', 'align' => 'center']),
        ],
        'rows' => $historyRows,
        'row_key' => 'id',
        'selectable' => false,
        'empty_title' => 'Aucune action tracée',
        'empty_message' => 'Votre historique est vide pour les filtres sélectionnés.',
    ]); ?>

    <?php cm_component('crud/pagination', [
        'pagination' => $historyPagination,
        'base_url' => $historyPagerBase,
        'param_name' => 'history_page',
    ]); ?>
</section>
<?php
$historyTabHtml = (string) ob_get_clean();
?>

<section class="cm-prd3-screen">
    <section class="cm-profile-screen">
        <?php cm_component('tabs/tab-nav', [
            'tabs' => [
                ['id' => 'profile', 'label' => 'Informations'],
                ['id' => 'password', 'label' => 'Mot de passe'],
                ['id' => 'history', 'label' => 'Historique'],
            ],
            'active' => $currentTab,
        ]); ?>

        <?php cm_component('tabs/tab-content', ['id' => 'profile', 'active' => $currentTab === 'profile', 'content' => $profileTabHtml]); ?>
        <?php cm_component('tabs/tab-content', ['id' => 'password', 'active' => $currentTab === 'password', 'content' => $passwordTabHtml]); ?>
        <?php cm_component('tabs/tab-content', ['id' => 'history', 'active' => $currentTab === 'history', 'content' => $historyTabHtml]); ?>
    </section>
</section>

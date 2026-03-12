<?php
$caps = getPermissionCaps('profil');
$nomUser = (string) ($_SESSION['nom_utilisateur'] ?? '');
$loginUser = (string) ($_SESSION['login_utilisateur'] ?? '');
$statutUser = (string) ($_SESSION['statut_utilisateur'] ?? '');

$libTypeUtilisateur = (string) ($_SESSION['type_utilisateur'] ?? '');
$libNiveauAcces = (string) ($_SESSION['niveau_acces'] ?? '');
$libGroupeUtilisateur = (string) ($_SESSION['lib_GU'] ?? '');

$specialite = (string) ($_SESSION['specialite'] ?? '');
$grade = (string) ($_SESSION['grade'] ?? '');
$fonction = (string) ($_SESSION['fonction'] ?? '');
$dateGrade = (string) ($_SESSION['date_grade'] ?? '');
$dateFonction = (string) ($_SESSION['date_fonction'] ?? '');
$telephone = (string) ($_SESSION['telephone'] ?? '');
$poste = (string) ($_SESSION['poste'] ?? '');
$dateEmbauche = (string) ($_SESSION['date_embauche'] ?? '');

$currentTab = ((string) ($_GET['tab'] ?? '')) === 'password' ? 'password' : 'profile';

$passwordError = (string) ($_SESSION['password_error'] ?? '');
$passwordSuccess = (string) ($_SESSION['password_success'] ?? '');
unset($_SESSION['password_error'], $_SESSION['password_success']);

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
        <h3 class="cm-profile-card__title">Informations du compte</h3>
    </header>
    <div class="cm-profile-grid">
        <?= $renderField('Nom utilisateur', $nomUser) ?>
        <?= $renderField('Login', $loginUser) ?>
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
    <header class="cm-profile-card__header">
        <h3 class="cm-profile-card__title">Détails professionnels</h3>
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

<div class="cm-form-buttons">
    <div class="cm-form-buttons__right">
        <button type="button" class="cm-btn is-primary is-sm" data-edit-profile="1">
            <i class="fas fa-pen" aria-hidden="true"></i>
            Modifier mes informations
        </button>
    </div>
</div>
<?php
$profileTabHtml = (string) ob_get_clean();

ob_start();
?>
<section class="cm-profile-card">
    <header class="cm-profile-card__header">
        <h3 class="cm-profile-card__title">Changer le mot de passe</h3>
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
        <input type="hidden" name="action" value="update_password">

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

        <?php cm_component('crud/form-actions', [
            'actions' => [
                ['label' => 'Réinitialiser', 'type' => 'reset', 'class' => 'cm-btn is-secondary is-sm'],
                ['label' => 'Changer le mot de passe', 'type' => 'submit', 'class' => 'cm-btn is-primary is-sm', 'attrs' => ['name' => 'update_password']],
            ],
        ]); ?>
    </form>
</section>
<?php
$passwordTabHtml = (string) ob_get_clean();
?>

<section class="cm-profile-screen">
    <?php cm_component('tabs/tab-nav', [
        'tabs' => [
            ['id' => 'profile', 'label' => 'Informations'],
            ['id' => 'password', 'label' => 'Mot de passe'],
        ],
        'active' => $currentTab,
    ]); ?>

    <?php cm_component('tabs/tab-content', ['id' => 'profile', 'active' => $currentTab === 'profile', 'content' => $profileTabHtml]); ?>
    <?php
    $passwordContent = $caps['edit']
        ? $passwordTabHtml
        : showNoPermissionMessage('modifier', 'profil');
    cm_component('tabs/tab-content', ['id' => 'password', 'active' => $currentTab === 'password', 'content' => $passwordContent]);
    ?>
</section>

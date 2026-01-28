<?php
$nom_user = $_SESSION['nom_utilisateur'] ?? '';
$login_user = $_SESSION['login_utilisateur'] ?? '';
$lib_GU = $_SESSION['lib_GU'] ?? '';
$libelle_type_utilisateur = $_SESSION['type_utilisateur'] ?? '';
$libelle_niveau_acces = $_SESSION['niveau_acces'] ?? '';
$specialite = $_SESSION['specialite'] ?? '';
$grade = $_SESSION['grade'] ?? '';
$fonction = $_SESSION['fonction'] ?? '';
$date_grade = $_SESSION['date_grade'] ?? '';
$date_fonction = $_SESSION['date_fonction'] ?? '';
$telephone = $_SESSION['telephone'] ?? '';
$poste = $_SESSION['poste'] ?? '';
$date_embauche = $_SESSION['date_embauche'] ?? '';
?>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const alerts = document.querySelectorAll('.alert-message');
    alerts.forEach(function (alert) {
        setTimeout(() => alert.remove(), 3000);
    });
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('tab') === 'password') {
        const el = document.querySelector('[data-tab="password"]');
        if (el) el.click();
    }
});
</script>

<div class="container" x-data="{ currentTab: '<?php echo isset($_GET['tab']) && $_GET['tab'] === 'password' ? 'password' : 'profile'; ?>' }">
    <div class="page-header">
        <h1 class="page-title">
            <i class="fas fa-user-circle"></i> Mon Profil
        </h1>
        <p class="page-subtitle">Gérez vos informations personnelles</p>
    </div>
    
    <div class="tabs">
        <button @click="currentTab = 'profile'" :class="currentTab === 'profile' ? 'tab-active' : ''" class="tab">
            <i class="fas fa-user"></i> Informations
        </button>
        <button @click="currentTab = 'password'" :class="currentTab === 'password' ? 'tab-active' : ''" class="tab">
            <i class="fas fa-lock"></i> Mot de passe
        </button>
    </div>
    
    <div x-show="currentTab === 'profile'">
        <div class="card">
            <div class="card-body">
                <h3 class="card-title">Informations personnelles</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Nom complet</label>
                        <div class="input-group">
                            <i class="fas fa-user input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($nom_user) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email</label>
                        <div class="input-group">
                            <i class="fas fa-envelope input-icon"></i>
                            <input type="email" value="<?= htmlspecialchars($login_user) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Identifiant</label>
                        <div class="input-group">
                            <i class="fas fa-at input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($login_user) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Groupe utilisateur</label>
                        <div class="input-group">
                            <i class="fas fa-users input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($lib_GU) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Type d'utilisateur</label>
                        <div class="input-group">
                            <i class="fas fa-user-tag input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($libelle_type_utilisateur) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Niveau d'accès</label>
                        <div class="input-group">
                            <i class="fas fa-shield-alt input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($libelle_niveau_acces) ?>" disabled class="form-control">
                        </div>
                    </div>
                </div>
                
                <?php if ($libelle_type_utilisateur === 'Enseignant simple' || $libelle_type_utilisateur === 'Enseignant administratif'): ?>
                <div class="separator"></div>
                <h3 class="card-title">Informations professionnelles</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Spécialité</label>
                        <div class="input-group">
                            <i class="fas fa-graduation-cap input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($specialite) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Grade</label>
                        <div class="input-group">
                            <i class="fas fa-award input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($grade) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Fonction</label>
                        <div class="input-group">
                            <i class="fas fa-briefcase input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($fonction) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date d'obtention du grade</label>
                        <div class="input-group">
                            <i class="fas fa-calendar-check input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($date_grade) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date d'occupation de la fonction</label>
                        <div class="input-group">
                            <i class="fas fa-calendar-alt input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($date_fonction) ?>" disabled class="form-control">
                        </div>
                    </div>
                </div>
                <?php elseif ($libelle_type_utilisateur === 'Personnel administratif'): ?>
                <div class="separator"></div>
                <h3 class="card-title">Informations professionnelles</h3>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Téléphone</label>
                        <div class="input-group">
                            <i class="fas fa-phone input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($telephone) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Poste</label>
                        <div class="input-group">
                            <i class="fas fa-briefcase input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($poste) ?>" disabled class="form-control">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date d'embauche</label>
                        <div class="input-group">
                            <i class="fas fa-calendar-alt input-icon"></i>
                            <input type="text" value="<?= htmlspecialchars($date_embauche) ?>" disabled class="form-control">
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div x-show="currentTab === 'password'" x-transition>
        <form action="?page=profil&tab=password" method="POST">
            <div class="card">
                <div class="card-body">
                    <h3 class="card-title">Changer mon mot de passe</h3>
                    <?php if (isset($_SESSION['password_error'])): ?>
                        <div class="alert alert-danger">
                            <p><?= htmlspecialchars($_SESSION['password_error']) ?></p>
                        </div>
                        <?php unset($_SESSION['password_error']); ?>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['password_success'])): ?>
                        <div class="alert alert-success">
                            <p><?= htmlspecialchars($_SESSION['password_success']) ?></p>
                        </div>
                        <?php unset($_SESSION['password_success']); ?>
                    <?php endif; ?>
                    
                    <input type="hidden" name="id_utilisateur" value="<?php echo $_SESSION['id_utilisateur']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label">Mot de passe actuel</label>
                        <div class="input-group">
                            <i class="fas fa-lock input-icon"></i>
                            <input type="password" name="currentPassword" required class="form-control">
                        </div>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Nouveau mot de passe</label>
                            <div class="input-group">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="newPassword" required class="form-control">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Confirmer le mot de passe</label>
                            <div class="input-group">
                                <i class="fas fa-lock input-icon"></i>
                                <input type="password" name="confirmPassword" required class="form-control">
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="update_password" class="btn btn-primary btn-block">
                        <i class="fas fa-save"></i> Mettre à jour le mot de passe
                    </button>
                </div>
            </div>
        </form>
        
        <div class="card card-info">
            <div class="card-body">
                <h4 class="card-title">
                    <i class="fas fa-info-circle"></i> Exigences pour le mot de passe
                </h4>
                <ul class="info-list">
                    <li><i class="fas fa-check-circle"></i> Minimum 8 caractères</li>
                    <li><i class="fas fa-check-circle"></i> Au moins une majuscule</li>
                    <li><i class="fas fa-check-circle"></i> Au moins un chiffre</li>
                    <li><i class="fas fa-check-circle"></i> Au moins un caractère spécial</li>
                </ul>
            </div>
        </div>
    </div>
</div>

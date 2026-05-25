<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CheckMaster - Gestion des Soutenances</title>
    <link rel="stylesheet" href="public/assets/css/checkmaster-theme.css">
    <link rel="stylesheet" href="public/assets/css/components.css">
    <link rel="stylesheet" href="public/assets/css/responsive.css">
    <link rel="shortcut icon" href="public/image/logo_cm_sbg.png" type="image/x-icon">
    <link rel="stylesheet" href="public/assets/vendor/font-awesome/css/all.min.css">
</head>

<body class="cm-login-page" style="min-height: 100vh; display: flex; flex-direction: column;">
    <!-- Header minimaliste -->
    <header style="padding: 1rem 2rem; background: var(--cm-primary);">
        <div style="max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center;">
            <div style="display: flex; align-items: center; gap: 0.75rem;">
                <img src="public/image/logo_cm_sbg.png" alt="CheckMaster" style="height: 40px; width: auto;">
                <span style="color: white; font-weight: 600; font-size: 1.1rem;">CheckMaster</span>
            </div>
            <a href="public/app/index.php?_path=/login" class="cm-btn is-light" style="padding: 0.5rem 1.25rem;">
                <i class="fas fa-sign-in-alt"></i>
                <span>Connexion</span>
            </a>
        </div>
    </header>

    <!-- Hero minimaliste -->
    <main style="flex: 1; display: flex; align-items: center; justify-content: center; padding: 2rem;">
        <div style="text-align: center; max-width: 600px;">
            <div style="margin-bottom: 2rem;">
                <img src="public/image/logo_cm_sbg.png" alt="CheckMaster" style="height: 100px; width: auto; margin-bottom: 1.5rem;">
                <h1 style="font-size: 2.5rem; font-weight: 700; color: var(--cm-primary); margin-bottom: 1rem;">
                    CheckMaster
                </h1>
                <p style="font-size: 1.1rem; color: var(--cm-text-muted); line-height: 1.6;">
                    Plateforme de gestion des soutenances MIAGE
                </p>
            </div>

            <div style="display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap;">
                <a href="public/app/index.php?_path=/login" class="cm-btn is-outline-primary is-lg">
                    <i class="fas fa-sign-in-alt"></i>
                    <span>Accéder à la plateforme</span>
                </a>
            </div>
        </div>
    </main>

    <!-- Footer minimaliste -->
    <footer style="padding: 1.5rem; text-align: center; border-top: 1px solid var(--cm-border-color); background: var(--cm-box-bg);">
        <p style="color: var(--cm-text-muted); font-size: 0.9rem;">
            &copy; <?php echo date('Y'); ?> CheckMaster - UFHB. Tous droits réservés.
        </p>
    </footer>
</body>

</html>

<?php
$pageTitle = !empty($title) ? $title . ' - CheckMaster UFRMI' : 'CheckMaster UFRMI';
$showLogo = $show_logo ?? true;
$bgStyle = $background ?? 'default';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token ?? '') ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="/assets/images/favicon.png">
    
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="/assets/bulma/css/bulma.min.css">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/checkmaster-theme.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    
    <style>
        .cm-auth-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f5f5f5;
        }
        .cm-auth-page.cm-auth-bg--image {
            background-image: url('/assets/images/auth-bg.jpg');
            background-size: cover;
            background-position: center;
        }
        .cm-auth-page.cm-auth-bg--gradient {
            background: linear-gradient(135deg, #1a5276 0%, #3498db 100%);
        }
        .cm-auth-card {
            width: 100%;
            max-width: 420px;
            padding: 2rem;
        }
        .cm-auth-logo {
            text-align: center;
            margin-bottom: 2rem;
        }
        .cm-auth-logo img {
            max-height: 80px;
        }
        .cm-auth-logo h1 {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a5276;
            margin-top: 0.5rem;
        }
        .cm-auth-logo h2 {
            font-size: 0.9rem;
            font-weight: 400;
            color: #666;
        }
    </style>
    
    <?php foreach ($extra_css ?? [] as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
</head>
<body>
    <div class="cm-auth-page cm-auth-bg--<?= htmlspecialchars($bgStyle) ?>">
        <div class="cm-auth-card">
            <?php if ($showLogo): ?>
            <div class="cm-auth-logo">
                <img src="/assets/images/logo.png" alt="UFRMI Logo">
                <h1>CheckMaster</h1>
                <h2>UFR des Sciences de l'Information et de la Communication</h2>
            </div>
            <?php endif; ?>
            
            <!-- Flash messages -->
            <?php if (!empty($flash_message)): ?>
            <div class="mb-4">
                <?php
                $type = $flash_message['type'] ?? 'info';
                $message = $flash_message['message'] ?? '';
                include dirname(__DIR__) . '/components/feedback/alert.php';
                ?>
            </div>
            <?php endif; ?>
            
            <!-- Main content -->
            <?= $content ?? '' ?>
            
            <!-- Footer -->
            <div class="has-text-centered mt-4">
                <p class="is-size-7 has-text-grey">
                    &copy; <?= date('Y') ?> UFRMI - UFHB. Tous droits réservés.
                </p>
            </div>
        </div>
    </div>
    
    <!-- JavaScript -->
    <script src="/assets/js/app.js"></script>
    <script src="/assets/js/components/form-validation.js"></script>
    
    <?php foreach ($extra_js ?? [] as $js): ?>
    <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
</body>
</html>

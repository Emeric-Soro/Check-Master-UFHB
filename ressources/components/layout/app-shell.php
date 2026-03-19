<?php
$page_title = $page_title ?? 'CheckMaster';
$current_page = $current_page ?? '';
$content = $content ?? '';
$user = $user ?? [];
$annee = $annee ?? '';
$include_chart = !empty($include_chart);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string) $page_title, ENT_QUOTES, 'UTF-8') ?> - CheckMaster</title>
    <link rel="stylesheet" href="<?= htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/checkmaster-theme.css') : 'assets/css/checkmaster-theme.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/components.css') : 'assets/css/components.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/utilities.css') : 'assets/css/utilities.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars(function_exists('cm_asset') ? cm_asset('css/responsive.css') : 'assets/css/responsive.css', ENT_QUOTES, 'UTF-8') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if ($include_chart): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <?php endif; ?>
</head>
<body class="cm-app-body">
<?php cm_component('layout/sidebar', ['current_page' => $current_page, 'user' => $user]); ?>

<div class="cm-main-wrapper" id="mainWrapper">
    <?php cm_component('layout/navbar', ['user' => $user, 'annee' => $annee]); ?>
    <main class="cm-content-area" id="contentArea" data-page="<?= htmlspecialchars((string) $current_page, ENT_QUOTES, 'UTF-8') ?>">
        <?= $content ?>
        <?php cm_component('ui/toast'); ?>
    </main>
</div>

<script src="<?= htmlspecialchars(function_exists('cm_asset') ? cm_asset('js/app.js') : 'assets/js/app.js', ENT_QUOTES, 'UTF-8') ?>"></script>
</body>
</html>

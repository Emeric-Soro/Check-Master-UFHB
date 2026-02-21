<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= $csrf_token ?? '' ?>">
    <title><?= htmlspecialchars($title ?? 'CheckMaster') ?> - UFRMI</title>
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="/assets/bulma/css/bulma.min.css">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="/assets/fontawesome/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/checkmaster-theme.css">
    <link rel="stylesheet" href="/assets/css/components.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <?php foreach ($extra_css ?? [] as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
    <?php endforeach; ?>
</head>
<body>
    <?= $content ?? '' ?>
    <!-- JavaScript -->
    <script src="/assets/js/app.js"></script>
    <?php foreach ($extra_js ?? [] as $js): ?>
    <script src="<?= htmlspecialchars($js) ?>"></script>
    <?php endforeach; ?>
</body>
</html>

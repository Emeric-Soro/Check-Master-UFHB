<?php
// Initialize ComponentHelper if not available
if (!isset($c)) {
    require_once dirname(__DIR__, 3) . '/src/Support/ComponentHelper.php';
    $c = new ComponentHelper();
}

// Build page title
$pageTitle = !empty($page_title) ? $page_title . ' - CheckMaster' : ($title ?? 'CheckMaster') . ' - CheckMaster';

// Get current path for active state
$currentPath = $current_path ?? $_SERVER['REQUEST_URI'] ?? '/';

// Build deterministic base URLs so assets resolve from both /public/layout.php and /public/app/layout.php.
$scriptDir = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/public/layout.php')), '/');
$publicBaseUrl = (substr($scriptDir, -4) === '/app') ? substr($scriptDir, 0, -4) : $scriptDir;
if ($publicBaseUrl === '') {
    $publicBaseUrl = '/public';
}
$assetBaseUrl = $publicBaseUrl . '/assets';
$toPublicUrl = static function (string $path) use ($publicBaseUrl): string {
    if ($path === '') {
        return $publicBaseUrl;
    }
    if (preg_match('#^(?:[a-z]+:)?//#i', $path) || str_starts_with($path, '/')) {
        return $path;
    }
    return $publicBaseUrl . '/' . ltrim($path, '/');
};

// Asset fallbacks for environments where some bundles are not present locally.
$publicRoot = dirname(__DIR__, 3) . '/public';
$faviconHref = is_file($publicRoot . '/assets/images/favicon.png')
    ? ($assetBaseUrl . '/images/favicon.png')
    : ($publicBaseUrl . '/images/logo-sm.png');
$fontAwesomeHref = is_file($publicRoot . '/assets/fontawesome/css/all.min.css')
    ? ($assetBaseUrl . '/fontawesome/css/all.min.css')
    : 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= htmlspecialchars($csrf_token ?? '') ?>">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= htmlspecialchars($faviconHref) ?>">
    
    <!-- Bulma CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBaseUrl . '/bulma/css/bulma.min.css') ?>">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="<?= htmlspecialchars($fontAwesomeHref) ?>">
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBaseUrl . '/css/checkmaster-theme.css') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBaseUrl . '/css/components.css') ?>">
    <link rel="stylesheet" href="<?= htmlspecialchars($assetBaseUrl . '/css/responsive.css') ?>">
    
    <?php foreach ($extra_css ?? [] as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($toPublicUrl((string) $css)) ?>">
    <?php endforeach; ?>
</head>
<body>
    <div class="cm-app-wrapper">
        <!-- Sidebar -->
        <?php
        $menus = $menus ?? [];
        include dirname(__DIR__) . '/components/layout/sidebar.php';
        ?>
        
        <!-- Main content area -->
        <div class="cm-main">
            <!-- Header -->
            <?php
            $headerPageTitle = $page_title ?? $title ?? '';
            $headerUser = $user ?? [];
            $headerAnnee = $annee_academique ?? [];
            $headerNotif = $notifications_count ?? 0;
            include dirname(__DIR__) . '/components/layout/header.php';
            ?>
            
            <!-- Page content -->
            <main class="cm-content">
                <!-- Breadcrumb (if available) -->
                <?php if (!empty($breadcrumb)): ?>
                <div class="mb-4">
                    <?php
                    $items = $breadcrumb;
                    include dirname(__DIR__) . '/components/nav/breadcrumb.php';
                    ?>
                </div>
                <?php endif; ?>
                
                <!-- Flash messages / Alerts -->
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
            </main>
        </div>
        
        <!-- Toast container -->
        <?php if (!empty($toasts)): ?>
        <div class="cm-toast-container cm-toast-container--top-right">
            <?php foreach ($toasts as $toast): ?>
            <?php include dirname(__DIR__) . '/components/feedback/toast.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Mobile drawer -->
    <?php
    $menus = $menus ?? [];
    $drawerUser = $user ?? [];
    include dirname(__DIR__) . '/components/nav/mobile-drawer.php';
    ?>
    
    <!-- Confirmation modal container -->
    <div id="cm-modal-container"></div>
    
    <!-- JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/app.js') ?>"></script>
    
    <!-- Component JS files -->
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/sidebar.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/data-table.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/select-search.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/side-panel.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/confirm-modal.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/tabs.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/steps.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/auto-save.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/toast.js') ?>"></script>
    <script src="<?= htmlspecialchars($assetBaseUrl . '/js/components/form-validation.js') ?>"></script>
    
    <?php foreach ($extra_js ?? [] as $js): ?>
    <script src="<?= htmlspecialchars($toPublicUrl((string) $js)) ?>"></script>
    <?php endforeach; ?>
</body>
</html>

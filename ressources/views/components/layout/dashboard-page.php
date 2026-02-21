<div class="cm-dashboard-wrapper">
    <div class="cm-dashboard-header">
        <h1 class="title"><?= htmlspecialchars($page_title ?? 'Tableau de bord') ?></h1>
    </div>
    <div class="cm-dashboard-grid">
        <?php foreach ($widgets ?? [] as $widget): ?>
        <div class="cm-dashboard-cell <?= $widget['class'] ?? '' ?>">
            <?php
            // Render widget based on type
            $widgetType = $widget['type'] ?? 'stat-card';
            $widgetParams = $widget['params'] ?? $widget;
            include dirname(__DIR__) . '/widget/' . $widgetType . '.php';
            ?>
        </div>
        <?php endforeach; ?>
    </div>
</div>

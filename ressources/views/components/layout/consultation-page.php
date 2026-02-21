<div class="cm-consultation-wrapper">
    <header class="cm-consultation-header">
        <h1 class="title"><?= htmlspecialchars($title ?? '') ?></h1>
        <?php if (!empty($actions)): ?>
        <div class="cm-consultation-actions">
            <?php foreach ($actions as $action): ?>
            <button class="button is-small <?= $action['class'] ?? 'is-light' ?>"
                    data-action="<?= htmlspecialchars($action['action'] ?? '') ?>">
                <i class="fas <?= $action['icon'] ?? '' ?>"></i>
                <span><?= htmlspecialchars($action['label'] ?? '') ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </header>
    
    <?php if (!empty($timeline)): ?>
    <aside class="cm-consultation-timeline">
        <?php 
        $steps = $timeline;
        include dirname(__DIR__) . '/widget/timeline.php';
        ?>
    </aside>
    <?php endif; ?>
    
    <main class="cm-consultation-content">
        <?php foreach ($sections ?? [] as $section): ?>
        <section class="cm-consultation-section">
            <h2 class="cm-section-title">
                <i class="fas <?= $section['icon'] ?? 'fa-folder' ?>"></i>
                <?= htmlspecialchars($section['title'] ?? '') ?>
            </h2>
            <div class="cm-section-content">
                <?= $section['content'] ?? '' ?>
            </div>
        </section>
        <?php endforeach; ?>
    </main>
</div>

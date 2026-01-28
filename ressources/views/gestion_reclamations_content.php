<div class="container">
    <?php if (isset($_SESSION['message'])): ?>
        <div class="alert alert-<?= $_SESSION['message']['type'] === 'success' ? 'success' : 'danger' ?>">
            <i class="fas fa-<?= $_SESSION['message']['type'] === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <strong><?php if ($_SESSION['message']['type'] === 'success'): ?>Succès !<?php else: ?>Erreur !<?php endif; ?></strong>
            <?= htmlspecialchars($_SESSION['message']['text']) ?>
        </div>
        <?php unset($_SESSION['message']); ?>
    <?php endif; ?>

    <div class="page-header">
        <h1>Gestion des Réclamations</h1>
        <p class="page-subtitle">Gérez et suivez les réclamations des étudiants</p>
    </div>

    <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));">
        <?php foreach ($cardReclamation as $card): ?>
            <a href="<?php echo htmlspecialchars($card['link']); ?>" class="action-card">
                <div class="action-card-icon <?php 
                    if (strpos($card['bg_color'], 'yellow') !== false) echo 'warning';
                    elseif (strpos($card['bg_color'], 'green') !== false) echo 'success';
                    elseif (strpos($card['bg_color'], 'blue') !== false) echo 'primary';
                    else echo 'danger';
                ?>">
                    <?php if (!empty($card['icon'])): ?>
                        <i class="<?php echo htmlspecialchars($card['icon']); ?>"></i>
                    <?php endif ?>
                </div>
                <div class="action-card-content">
                    <h3 class="action-card-title"><?php echo htmlspecialchars($card['title']); ?></h3>
                    <p class="action-card-description"><?php echo htmlspecialchars($card['description']); ?></p>
                    <div class="action-card-link">
                        <?php echo htmlspecialchars($card['title_link']); ?>
                        <i class="fas fa-arrow-right"></i>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</div>
<div class="container">
    <div class="card-grid">
        <?php if (isset($cardPGeneraux) && is_array($cardPGeneraux)): ?>
            <?php foreach ($cardPGeneraux as $card): ?>
                <div class="card card-hover">
                    <div class="card-body">
                        <a href="<?php echo htmlspecialchars($card['link']); ?>" class="card-link" aria-label="<?php echo htmlspecialchars($card['title']); ?>">
                            <?php if (!empty($card['icon'])): ?>
                                <div class="card-icon-wrapper">
                                    <img src="<?php echo htmlspecialchars($card['icon']); ?>" alt="icone" class="card-icon-img">
                                </div>
                            <?php endif; ?>
                            <h5 class="card-title">
                                <?php echo htmlspecialchars($card['title']); ?>
                            </h5>
                            <p class="card-description">
                                <?php echo htmlspecialchars($card['description']); ?>
                            </p>
                        </a>
                        <div class="card-footer">
                            <a href="<?php echo htmlspecialchars($card['link']); ?>" class="btn btn-primary btn-sm" aria-label="Accéder <?php echo htmlspecialchars($card['title']); ?>">
                                Accéder
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

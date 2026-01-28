<div class="container">
    <div class="empty-state">
        <div class="empty-state-icon danger">
            <i class="fas fa-lock"></i>
        </div>
        <h1 class="empty-state-title">Accès refusé</h1>
        <p class="empty-state-description">Vous n'avez pas les permissions nécessaires pour accéder à cette page.</p>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <div>
                    <h3 class="alert-title">Détails de l'erreur</h3>
                    <p><?= htmlspecialchars($_SESSION['error_message']) ?></p>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <h2 class="card-title">Que faire maintenant ?</h2>
                <ul class="info-list">
                    <li class="info-item">
                        <i class="fas fa-arrow-right"></i>
                        <div>
                            <strong>Contactez votre administrateur</strong>
                            <p>Si vous pensez avoir besoin de ces permissions, contactez l'administrateur système pour demander un accès.</p>
                        </div>
                    </li>
                    <li class="info-item">
                        <i class="fas fa-arrow-right"></i>
                        <div>
                            <strong>Vérifiez votre rôle</strong>
                            <p>Vous êtes actuellement connecté en tant que : <strong><?= htmlspecialchars($_SESSION['lib_GU']) ?></strong></p>
                        </div>
                    </li>
                    <li class="info-item">
                        <i class="fas fa-arrow-right"></i>
                        <div>
                            <strong>Retournez à une page autorisée</strong>
                            <p>Utilisez le menu de gauche pour naviguer vers une page pour laquelle vous avez les permissions.</p>
                        </div>
                    </li>
                </ul>
            </div>
        </div>

        <div class="button-group">
            <button onclick="window.history.back()" class="btn btn-ghost">
                <i class="fas fa-arrow-left"></i>
                Retour
            </button>
            <?php
            $firstAccessiblePage = '';
            if (isset($menuHierarchique) && !empty($menuHierarchique)) {
                foreach ($menuHierarchique as $categorie) {
                    if (!empty($categorie['fonctionnalites'])) {
                        $firstFonc = $categorie['fonctionnalites'][0];
                        $query = parse_url($firstFonc->url_fonctionnalite, PHP_URL_QUERY);
                        if ($query) {
                            parse_str($query, $params);
                            if (isset($params['page'])) {
                                $firstAccessiblePage = $params['page'];
                                break;
                            }
                        }
                    }
                }
            }
            ?>
            <?php if (!empty($firstAccessiblePage)): ?>
                <a href="layout.php?page=<?= urlencode($firstAccessiblePage) ?>" class="btn btn-primary">
                    <i class="fas fa-home"></i>
                    Aller à ma page d'accueil
                </a>
            <?php endif; ?>
        </div>

        <?php
        unset($_SESSION['error_message']);
        unset($_SESSION['error_type']);
        ?>
    </div>

    <?php if (isset($_SESSION['id_GU']) && $_SESSION['id_GU'] == 5): ?>
        <div class="card card-warning">
            <div class="card-body">
                <h3 class="card-title">
                    <i class="fas fa-tools"></i>
                    Informations de débogage (Admin uniquement)
                </h3>
                <div class="debug-info">
                    <p><strong>Utilisateur ID:</strong> <?= $_SESSION['id_utilisateur'] ?></p>
                    <p><strong>Groupe ID:</strong> <?= $_SESSION['id_GU'] ?></p>
                    <p><strong>Groupe:</strong> <?= $_SESSION['lib_GU'] ?></p>
                    <p><strong>Page demandée:</strong> <?= isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'N/A' ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>
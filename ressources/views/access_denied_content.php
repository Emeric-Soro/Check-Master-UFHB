<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-lg shadow-lg p-8">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-red-100 rounded-full mb-4">
                <i class="fas fa-lock text-red-500 text-4xl"></i>
            </div>

            <p class="text-gray-600">Vous n'avez pas les permissions nécessaires pour accéder à cette page.</p>
        </div>

        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-exclamation-circle text-red-500 text-xl"></i>
                    </div>
                    <div class="ml-3">

                        <p class="mt-1 text-sm text-red-700">
                            <?= htmlspecialchars($_SESSION['error_message']) ?>
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="bg-gray-50 rounded-lg p-6 mb-6">

            <ul class="space-y-3">
                <li class="flex items-start">
                    <i class="fas fa-arrow-right text-blue-500 mt-1 mr-3"></i>
                    <div>
                        <strong class="text-gray-800">Contactez votre administrateur</strong>
                        <p class="text-gray-600 text-sm">Si vous pensez avoir besoin de ces permissions, contactez
                            l'administrateur système pour demander un accès.</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-arrow-right text-blue-500 mt-1 mr-3"></i>
                    <div>
                        <strong class="text-gray-800">Vérifiez votre rôle</strong>
                        <p class="text-gray-600 text-sm">Vous êtes actuellement connecté en tant que :
                            <strong><?= htmlspecialchars($_SESSION['lib_GU']) ?></strong>
                        </p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-arrow-right text-blue-500 mt-1 mr-3"></i>
                    <div>
                        <strong class="text-gray-800">Retournez à une page autorisée</strong>
                        <p class="text-gray-600 text-sm">Utilisez le menu de gauche pour naviguer vers une page pour
                            laquelle vous avez les permissions.</p>
                    </div>
                </li>
            </ul>
        </div>

        <div class="flex justify-center gap-4">
            <button onclick="window.history.back()"
                class="btn bg-gray-500 hover:bg-gray-600 text-white px-6 py-2 rounded-lg">
                <i class="fas fa-arrow-left mr-2"></i>
                Retour
            </button>
            <?php
            // Trouver la première page accessible pour cet utilisateur
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
                <a href="layout.php?page=<?= urlencode($firstAccessiblePage) ?>"
                    class="btn bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg">
                    <i class="fas fa-home mr-2"></i>
                    Aller à ma page d'accueil
                </a>
            <?php endif; ?>
        </div>

        <?php
        // Nettoyer les messages d'erreur après affichage
        unset($_SESSION['error_message']);
        unset($_SESSION['error_type']);
        ?>
    </div>

    <?php if (isAdmin()): ?>
        <!-- Section de debug pour l'administrateur -->
        <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-6">

            </h3>
            <div class="text-sm space-y-2">
                <p><strong>Utilisateur ID:</strong> <?= $_SESSION['id_utilisateur'] ?></p>
                <p><strong>Groupe ID:</strong> <?= $_SESSION['id_GU'] ?></p>
                <p><strong>Groupe:</strong> <?= $_SESSION['lib_GU'] ?></p>
                <p><strong>Page demandée:</strong>
                    <?= isset($_SERVER['HTTP_REFERER']) ? htmlspecialchars($_SERVER['HTTP_REFERER']) : 'N/A' ?></p>
            </div>
        </div>
    <?php endif; ?>
</div>
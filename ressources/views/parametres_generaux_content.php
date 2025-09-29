<div class="container mx-auto px-4 py-8">

    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
        <!-- Réduction du gap -->
        <?php 
require_once __DIR__ . '/../../app/utils/StyleManager.php';
foreach ($cardPGeneraux as $card): ?>
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm flex flex-col h-full transition-all duration-300 ease-in-out hover:shadow-lg hover:transform hover:-translate-y-1"
            style="min-height: 200px;">
            <!-- Hauteur fixe -->
            <div class="p-4 flex flex-col h-full">
                <!-- Padding réduit et flex column -->
                <a href="<?php echo htmlspecialchars($card['link']); ?>" class="group flex-grow">
                    <?php if (!empty($card['icon'])): ?>
                    <div
                        class="bg-primary-100 inline-flex items-center justify-center p-2 rounded-md mb-2 transition-colors">
                        <!-- Taille réduite -->
                        <img src="<?php echo htmlspecialchars($card['icon']); ?>" alt="icone" class="w-5 h-5">
                        <!-- Icône plus petite -->
                    </div>
                    <?php endif; ?>
                    <h5 class="mb-1 text-lg font-semibold text-gray-900 group-hover:text-primary-600 transition-colors">
                        <!-- Texte plus petit -->
                        <?php echo htmlspecialchars($card['title']); ?>
                    </h5>
                    <p class="text-xs text-gray-600 mb-2">
                        <!-- Texte plus petit et marge réduite -->
                        <?php echo htmlspecialchars($card['description']); ?>
                    </p>
                </a>
                <div class="mt-auto">
                    <!-- Pousse le bouton vers le bas -->
                    <a href="<?php echo htmlspecialchars($card['link']); ?>"
                        class="inline-flex items-center px-3 py-1 text-xs font-medium text-center text-white bg-green-300 rounded-md hover:bg-primary-600 transition-colors">
                        Accéder
                        <i class="ml-1 fas fa-chevron-right text-xs"></i> <!-- Icône plus petite -->
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>
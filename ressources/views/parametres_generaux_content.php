<div class="container mx-auto px-4 py-8">
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <?php if (isset($cardPGeneraux) && is_array($cardPGeneraux)): ?>
            <?php foreach ($cardPGeneraux as $card): ?>
                <div class="card card-hover min-h-[160px] cursor-pointer">
                    <div class="p-3 flex flex-col h-full">
                        <a href="<?php echo htmlspecialchars($card['link']); ?>" class="group flex-grow" aria-label="<?php echo htmlspecialchars($card['title']); ?>">
                            <?php if (!empty($card['icon'])): ?>
                                <div class="inline-flex items-center justify-center p-1.5 bg-primary/10 rounded-md mb-2 transition-all group-hover:bg-primary/20">
                                    <img src="<?php echo htmlspecialchars($card['icon']); ?>" alt="icone" class="w-5 h-5 transition-transform group-hover:scale-110">
                                </div>
                            <?php endif; ?>
                            <h5 class="mb-1 text-primary font-semibold text-base transition-colors group-hover:text-primary-light">
                                <?php echo htmlspecialchars($card['title']); ?>
                            </h5>
                            <p class="text-gray-600 text-sm leading-tight mb-2">
                                <?php echo htmlspecialchars($card['description']); ?>
                            </p>
                        </a>
                        <div class="mt-auto">
                            <a href="<?php echo htmlspecialchars($card['link']); ?>" 
                               class="inline-flex items-center justify-center bg-accent text-white px-2.5 py-1.5 rounded-md text-sm font-medium transition-all hover:bg-accent/90 hover:scale-105" 
                               aria-label="Accéder <?php echo htmlspecialchars($card['title']); ?>">
                                Accéder
                                <i class="ml-1 fas fa-chevron-right text-xs transition-transform hover:translate-x-0.5"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

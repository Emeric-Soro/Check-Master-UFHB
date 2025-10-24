<?php
require_once __DIR__ . '/../../app/utils/permissions.php';
?>
<div class="container mx-auto px-4 py-8">
    <style>
        :root{--ufhb-blue:#0F4C75;--ufhb-blue-light:#3282B8;--ufhb-green:#10b981;--muted:#64748B}
        .card-bg {
            background: #ffffff;
            border: 1px solid rgba(15,76,117,0.08);
            border-radius: 0.5rem;
            box-shadow: 0 4px 12px rgba(15,76,117,0.06);
            transition: all 0.3s ease;
            min-height: 160px;
            display: flex;
            flex-direction: column;
            cursor: pointer;
        }
        .card-bg:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 25px rgba(15,76,117,0.15);
            border-color: rgba(15,76,117,0.15);
        }
        .card-icon {
            background: rgba(15,76,117,0.08);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.375rem;
            border-radius: 0.375rem;
            transition: background 0.3s ease;
        }
        .card-bg:hover .card-icon {
            background: rgba(15,76,117,0.12);
        }
        .card-title {
            color: var(--ufhb-blue);
            font-weight: 600;
            font-size: 1rem;
            transition: color 0.3s ease;
        }
        .card-bg:hover .card-title {
            color: var(--ufhb-blue-light);
        }
        .card-desc {
            color: #374151;
            font-size: 0.8rem;
            line-height: 1.4;
        }
        .access-btn {
            background: linear-gradient(135deg, var(--ufhb-green), var(--ufhb-green));
            color: #fff;
            padding: 0.375rem 0.625rem;
            border-radius: 0.375rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-decoration: none;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }
        .access-btn:hover {
            background: linear-gradient(135deg, #059669, #10b981);
            transform: scale(1.02);
        }
        .icon-img {
            width: 1.125rem;
            height: 1.125rem;
            transition: transform 0.3s ease;
        }
        .card-bg:hover .icon-img {
            transform: scale(1.1);
        }
        .access-btn i {
            transition: transform 0.3s ease;
        }
        .access-btn:hover i {
            transform: translateX(2px);
        }
    </style>
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-3">
        <?php if (isset($cardPGeneraux) && is_array($cardPGeneraux)): ?>
            <?php foreach ($cardPGeneraux as $card): ?>
                <div class="card-bg">
                    <div class="p-3 flex flex-col h-full">
                        <a href="<?php echo htmlspecialchars($card['link']); ?>" class="group flex-grow" aria-label="<?php echo htmlspecialchars($card['title']); ?>">
                            <?php if (!empty($card['icon'])): ?>
                                <div class="card-icon mb-2">
                                    <img src="<?php echo htmlspecialchars($card['icon']); ?>" alt="icone" class="icon-img">
                                </div>
                            <?php endif; ?>
                            <h5 class="mb-1 card-title">
                                <?php echo htmlspecialchars($card['title']); ?>
                            </h5>
                            <p class="card-desc mb-2">
                                <?php echo htmlspecialchars($card['description']); ?>
                            </p>
                        </a>
                        <div class="mt-auto">
                            <a href="<?php echo htmlspecialchars($card['link']); ?>" class="access-btn" aria-label="Accéder <?php echo htmlspecialchars($card['title']); ?>">
                                Accéder
                                <i class="ml-1 fas fa-chevron-right" style="font-size:0.7rem;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

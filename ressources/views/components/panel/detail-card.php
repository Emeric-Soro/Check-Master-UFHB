<?php
$cardId = 'detail-card-' . uniqid();
$isCollapsible = $collapsible ?? false;
$isCollapsed = $collapsed ?? false;
?>
<div class="cm-detail-card card <?= $isCollapsed ? 'is-collapsed' : '' ?>">
    <?php if (!empty($title)): ?>
    <header class="card-header <?= $isCollapsible ? 'is-clickable' : '' ?>" 
            <?= $isCollapsible ? 'data-action="toggle-card" data-target="' . $cardId . '"' : '' ?>>
        <p class="card-header-title">
            <?php if (!empty($icon)): ?>
            <span class="icon mr-2"><i class="fas <?= $icon ?>"></i></span>
            <?php endif; ?>
            <?= htmlspecialchars($title) ?>
        </p>
        <?php if ($isCollapsible): ?>
        <button class="card-header-icon" aria-label="plus d'options">
            <span class="icon">
                <i class="fas fa-angle-<?= $isCollapsed ? 'down' : 'up' ?>" data-chevron="<?= $cardId ?>"></i>
            </span>
        </button>
        <?php endif; ?>
    </header>
    <?php endif; ?>
    
    <div class="card-content <?= $isCollapsed ? 'is-hidden' : '' ?>" id="<?= $cardId ?>">
        <dl class="cm-detail-list">
            <?php foreach ($items ?? [] as $item): ?>
            <?php if (isset($item['items'])): ?>
            <!-- Nested group -->
            <div class="cm-detail-group">
                <?php if (!empty($item['label'])): ?>
                <dt class="cm-detail-group-title has-text-weight-semibold">
                    <?= htmlspecialchars($item['label']) ?>
                </dt>
                <?php endif; ?>
                <?php foreach ($item['items'] as $subItem): ?>
                <div class="cm-detail-row">
                    <dt class="cm-detail-label has-text-grey"><?= htmlspecialchars($subItem['label'] ?? '') ?></dt>
                    <dd class="cm-detail-value">
                        <?php
                        $type = $subItem['type'] ?? 'text';
                        $value = $subItem['value'] ?? '';
                        switch ($type) {
                            case 'badge':
                                $badgeType = $subItem['badge_type'] ?? 'info';
                                echo '<span class="tag is-' . $badgeType . ' is-light">' . htmlspecialchars($value) . '</span>';
                                break;
                            case 'date':
                                echo !empty($value) ? date('d/m/Y', strtotime($value)) : '-';
                                break;
                            case 'datetime':
                                echo !empty($value) ? date('d/m/Y H:i', strtotime($value)) : '-';
                                break;
                            case 'email':
                                echo '<a href="mailto:' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</a>';
                                break;
                            case 'phone':
                                echo '<a href="tel:' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</a>';
                                break;
                            case 'link':
                                echo '<a href="' . htmlspecialchars($subItem['url'] ?? '#') . '">' . htmlspecialchars($value) . '</a>';
                                break;
                            case 'boolean':
                                echo $value ? '<span class="tag is-success is-light">Oui</span>' : '<span class="tag is-danger is-light">Non</span>';
                                break;
                            default:
                                echo htmlspecialchars($value ?: '-');
                        }
                        ?>
                    </dd>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="cm-detail-row">
                <dt class="cm-detail-label has-text-grey"><?= htmlspecialchars($item['label'] ?? '') ?></dt>
                <dd class="cm-detail-value">
                    <?php
                    $type = $item['type'] ?? 'text';
                    $value = $item['value'] ?? '';
                    switch ($type) {
                        case 'badge':
                            $badgeType = $item['badge_type'] ?? 'info';
                            echo '<span class="tag is-' . $badgeType . ' is-light">' . htmlspecialchars($value) . '</span>';
                            break;
                        case 'date':
                            echo !empty($value) ? date('d/m/Y', strtotime($value)) : '-';
                            break;
                        case 'datetime':
                            echo !empty($value) ? date('d/m/Y H:i', strtotime($value)) : '-';
                            break;
                        case 'email':
                            echo '<a href="mailto:' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</a>';
                            break;
                        case 'phone':
                            echo '<a href="tel:' . htmlspecialchars($value) . '">' . htmlspecialchars($value) . '</a>';
                            break;
                        case 'link':
                            echo '<a href="' . htmlspecialchars($item['url'] ?? '#') . '">' . htmlspecialchars($value) . '</a>';
                            break;
                        case 'boolean':
                            echo $value ? '<span class="tag is-success is-light">Oui</span>' : '<span class="tag is-danger is-light">Non</span>';
                            break;
                        default:
                            echo htmlspecialchars($value ?: '-');
                    }
                    ?>
                </dd>
            </div>
            <?php endif; ?>
            <?php endforeach; ?>
        </dl>
        
        <?php if (!empty($actions)): ?>
        <div class="cm-detail-actions mt-4">
            <?php foreach ($actions as $action): ?>
            <a href="<?= htmlspecialchars($action['url'] ?? '#') ?>" 
               class="button is-small <?= $action['class'] ?? 'is-light' ?>"
               <?= !empty($action['data']) ? implode(' ', array_map(fn($k, $v) => 'data-' . $k . '="' . htmlspecialchars($v) . '"', array_keys($action['data']), $action['data'])) : '' ?>>
                <?php if (!empty($action['icon'])): ?>
                <span class="icon"><i class="fas <?= $action['icon'] ?>"></i></span>
                <?php endif; ?>
                <span><?= htmlspecialchars($action['label'] ?? '') ?></span>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

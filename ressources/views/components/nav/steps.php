<?php
$current = (int)($current_step ?? 1);
$canClick = $clickable ?? true;
$showLabels = $show_labels ?? true;
$isVertical = ($orientation ?? 'horizontal') === 'vertical';
?>
<div class="cm-steps <?= $isVertical ? 'is-vertical' : 'is-horizontal' ?>">
    <ul class="steps has-content-centered">
        <?php foreach ($steps ?? [] as $index => $step): ?>
        <?php
        $stepNum = $index + 1;
        $status = $step['status'] ?? '';
        if (!$status) {
            if ($stepNum < $current) {
                $status = 'completed';
            } elseif ($stepNum === $current) {
                $status = 'current';
            } else {
                $status = 'pending';
            }
        }
        
        $stepClass = 'steps-segment';
        $stepClass .= $status === 'completed' ? ' is-completed' : '';
        $stepClass .= $status === 'current' ? ' is-active' : '';
        $stepClass .= $status === 'pending' ? ' is-pending' : '';
        
        $isClickable = $canClick && ($status === 'completed' || $stepNum === $current);
        ?>
        <li class="<?= $stepClass ?>">
            <?php if ($isClickable && !empty($step['url'])): ?>
            <a href="<?= htmlspecialchars($step['url']) ?>" class="steps-marker cm-step" data-step="<?= $stepNum ?>">
                <?php if (!empty($step['icon'])): ?>
                <span class="icon"><i class="fas <?= $step['icon'] ?>"></i></span>
                <?php elseif ($status === 'completed'): ?>
                <span class="icon"><i class="fas fa-check"></i></span>
                <?php else: ?>
                <span><?= $stepNum ?></span>
                <?php endif; ?>
            </a>
            <?php else: ?>
            <span class="steps-marker">
                <?php if (!empty($step['icon'])): ?>
                <span class="icon"><i class="fas <?= $step['icon'] ?>"></i></span>
                <?php elseif ($status === 'completed'): ?>
                <span class="icon"><i class="fas fa-check"></i></span>
                <?php else: ?>
                <span><?= $stepNum ?></span>
                <?php endif; ?>
            </span>
            <?php endif; ?>
            
            <?php if ($showLabels && !empty($step['label'])): ?>
            <div class="steps-content">
                <span class="cm-step-label <?= $status === 'current' ? 'has-text-primary' : '' ?>">
                    <?= htmlspecialchars($step['label']) ?>
                </span>
            </div>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ul>
</div>

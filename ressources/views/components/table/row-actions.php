<div class="cm-row-actions buttons are-small">
    <?php foreach ($row_actions ?? [] as $action): ?>
    <?php
    $actionType = $action['type'] ?? 'view';
    $url = str_replace('{id}', $row_id ?? '', $action['url'] ?? '#');
    // Support for multiple placeholders
    if (is_array($row ?? [])) {
        foreach ($row as $key => $val) {
            $url = str_replace('{' . $key . '}', $val, $url);
        }
    }
    
    $icon = $action['icon'] ?? '';
    $label = $action['label'] ?? '';
    $class = $action['class'] ?? 'is-light';
    $title = $action['title'] ?? '';
    $confirm = $action['confirm_message'] ?? '';
    
    switch ($actionType) {
        case 'edit':
            $icon = $icon ?: 'fa-edit';
            $label = $label ?: '';
            $class = $class ?: 'is-info is-light';
            $title = $title ?: 'Modifier';
            break;
        case 'view':
        case 'detail':
            $icon = $icon ?: 'fa-eye';
            $label = $label ?: '';
            $class = $class ?: 'is-light';
            $title = $title ?: 'Voir détails';
            break;
        case 'delete':
            $icon = $icon ?: 'fa-trash';
            $label = $label ?: '';
            $class = $class ?: 'is-danger is-light';
            $title = $title ?: 'Supprimer';
            break;
        case 'print':
            $icon = $icon ?: 'fa-print';
            $label = $label ?: '';
            $class = $class ?: 'is-light';
            $title = $title ?: 'Imprimer';
            break;
        case 'download':
            $icon = $icon ?: 'fa-download';
            $label = $label ?: '';
            $class = $class ?: 'is-light';
            $title = $title ?: 'Télécharger';
            break;
    }
    ?>
    <?php if ($actionType === 'delete' || !empty($confirm)): ?>
    <button type="button" 
            class="button <?= $class ?>" 
            title="<?= htmlspecialchars($title) ?>"
            data-action="confirm"
            data-confirm-message="<?= htmlspecialchars($confirm ?: 'Êtes-vous sûr de vouloir supprimer cet élément ?') ?>"
            data-confirm-url="<?= htmlspecialchars($url) ?>"
            data-confirm-method="POST">
        <span class="icon"><i class="fas <?= $icon ?>"></i></span>
        <?php if ($label): ?><span><?= htmlspecialchars($label) ?></span><?php endif; ?>
    </button>
    <?php else: ?>
    <a href="<?= htmlspecialchars($url) ?>" 
       class="button <?= $class ?>" 
       title="<?= htmlspecialchars($title) ?>">
        <span class="icon"><i class="fas <?= $icon ?>"></i></span>
        <?php if ($label): ?><span><?= htmlspecialchars($label) ?></span><?php endif; ?>
    </a>
    <?php endif; ?>
    <?php endforeach; ?>
</div>

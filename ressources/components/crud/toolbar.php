<?php
if (!function_exists('cm_form_attr_string')) {
    require_once __DIR__ . '/../../../app/utils/FormHelper.php';
}

$left_html = (string) ($left_html ?? '');
$center_html = (string) ($center_html ?? '');
$right_html = (string) ($right_html ?? '');

$per_page = isset($per_page) ? max(1, (int) $per_page) : null;
$per_page_options = is_array($per_page_options ?? null) ? $per_page_options : [10, 25, 50, 100];
$search_name = (string) ($search_name ?? 'search');
$search_value = (string) ($search_value ?? '');
$search_placeholder = (string) ($search_placeholder ?? 'Rechercher...');
$actions = is_array($actions ?? null) ? $actions : [];
$show_default_controls = !empty($show_default_controls);
?>
<div class="cm-barre-intermediaire">
    <div class="cm-toolbar">
        <div class="cm-toolbar-left">
            <?php if ($left_html !== ''): ?>
                <?= $left_html ?>
            <?php elseif ($show_default_controls && $per_page !== null): ?>
            <label class="cm-toolbar__control">
                <span>Afficher:</span>
                <select class="cm-form-control cm-toolbar__select" name="per_page">
                    <?php foreach ($per_page_options as $opt): ?>
                    <?php $opt_i = (int) $opt; ?>
                    <option value="<?= $opt_i ?>" <?= $opt_i === $per_page ? 'selected' : '' ?>><?= $opt_i ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
        </div>

        <div class="cm-toolbar-center">
            <?php if ($center_html !== ''): ?>
                <?= $center_html ?>
            <?php elseif ($show_default_controls): ?>
            <label class="cm-toolbar__search">
                <span class="cm-sr-only">Recherche</span>
                <input type="search"
                       class="cm-form-control"
                       name="<?= htmlspecialchars($search_name, ENT_QUOTES, 'UTF-8') ?>"
                       value="<?= htmlspecialchars($search_value, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="<?= htmlspecialchars($search_placeholder, ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <?php endif; ?>
        </div>

        <div class="cm-toolbar-right">
            <?php if ($right_html !== ''): ?>
                <?= $right_html ?>
            <?php else: ?>
                <?php foreach ($actions as $action): ?>
                    <?php
                    $tag = strtolower((string) ($action['tag'] ?? 'button'));
                    $label = (string) ($action['label'] ?? 'Action');
                    $icon = (string) ($action['icon'] ?? '');
                    $class = (string) ($action['class'] ?? 'cm-btn is-info is-sm');
                    $attrs = is_array($action['attrs'] ?? null) ? $action['attrs'] : [];
                    ?>
                    <?php if ($tag === 'a'): ?>
                    <a href="<?= htmlspecialchars((string) ($action['href'] ?? '#'), ENT_QUOTES, 'UTF-8') ?>"
                       class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>"<?= cm_form_attr_string($attrs) ?>>
                        <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
                        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                    <?php else: ?>
                    <button type="<?= htmlspecialchars((string) ($action['type'] ?? 'button'), ENT_QUOTES, 'UTF-8') ?>"
                            class="<?= htmlspecialchars($class, ENT_QUOTES, 'UTF-8') ?>"<?= cm_form_attr_string($attrs) ?>>
                        <?php if ($icon !== ''): ?><i class="fas <?= htmlspecialchars($icon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i><?php endif; ?>
                        <span><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></span>
                    </button>
                    <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

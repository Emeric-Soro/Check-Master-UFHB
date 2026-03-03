<?php
// FormHelper déjà inclus par layout.php

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
$toolbar_class = trim((string) ($toolbar_class ?? ''));
$left_class = trim((string) ($left_class ?? ''));
$center_class = trim((string) ($center_class ?? ''));
$right_class = trim((string) ($right_class ?? ''));
?>
<div class="cm-barre-intermediaire">
    <div class="cm-toolbar<?= $toolbar_class !== '' ? ' ' . htmlspecialchars($toolbar_class, ENT_QUOTES, 'UTF-8') : '' ?>">
        <div class="cm-toolbar-left<?= $left_class !== '' ? ' ' . htmlspecialchars($left_class, ENT_QUOTES, 'UTF-8') : '' ?>">
            <?php if ($left_html !== ''): ?>
                <?= $left_html ?>
            <?php elseif ($show_default_controls && $per_page !== null): ?>
            <label class="cm-toolbar__control">
                <span>Afficher:</span>
                <select class="cm-form-control cm-form-select is-sm cm-toolbar__select cm-toolbar-field-xs" name="per_page">
                    <?php foreach ($per_page_options as $opt): ?>
                    <?php $opt_i = (int) $opt; ?>
                    <option value="<?= $opt_i ?>" <?= $opt_i === $per_page ? 'selected' : '' ?>><?= $opt_i ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <?php endif; ?>
        </div>

        <div class="cm-toolbar-center<?= $center_class !== '' ? ' ' . htmlspecialchars($center_class, ENT_QUOTES, 'UTF-8') : '' ?>">
            <?php if ($center_html !== ''): ?>
                <?= $center_html ?>
            <?php elseif ($show_default_controls): ?>
            <label class="cm-toolbar__search">
                <span class="cm-sr-only">Recherche</span>
                <input type="search"
                       class="cm-form-control is-sm cm-toolbar-field-lg"
                       name="<?= htmlspecialchars($search_name, ENT_QUOTES, 'UTF-8') ?>"
                       value="<?= htmlspecialchars($search_value, ENT_QUOTES, 'UTF-8') ?>"
                       placeholder="<?= htmlspecialchars($search_placeholder, ENT_QUOTES, 'UTF-8') ?>">
            </label>
            <?php endif; ?>
        </div>

        <div class="cm-toolbar-right<?= $right_class !== '' ? ' ' . htmlspecialchars($right_class, ENT_QUOTES, 'UTF-8') : '' ?>">
            <?php if ($right_html !== ''): ?>
                <?= $right_html ?>
            <?php else: ?>
                <?php foreach ($actions as $action): ?>
                    <?php
                    $tag = strtolower((string) ($action['tag'] ?? 'button'));
                    $label = (string) ($action['label'] ?? 'Action');
                    $icon = (string) ($action['icon'] ?? '');
                    $class = (string) ($action['class'] ?? 'cm-btn is-light is-sm');
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

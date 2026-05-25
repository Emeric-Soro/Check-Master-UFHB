<?php
require_once __DIR__ . '/_helpers.php';

$name = $name ?? '';
$id = $id ?? $name;
$label = $label ?? '';
$value = cm_form_old_value((string) $name, $value ?? '');
$required = $required ?? false;
$disabled = $disabled ?? false;
$readonly = $readonly ?? false;
$hint = $hint ?? '';
$error = cm_form_field_error((string) $name, $error ?? '');
$attrs = $attrs ?? [];
$size = trim((string) ($size ?? 'sm'));
if ($size === '') {
    $size = 'sm';
}
$dense = isset($dense) ? !empty($dense) : true;
$group_class = cm_form_class_names('cm-field--select-search', (string) ($group_class ?? ''));
$label_class = (string) ($label_class ?? '');
$control_class = (string) ($control_class ?? '');
$wrapper_class = (string) ($wrapper_class ?? '');
$show_selected_label = !isset($show_selected_label) || (bool) $show_selected_label;

$options = $options ?? [];
$placeholder = $placeholder ?? '-- Selectionner --';
$selected = (string) ($selected ?? $value ?? '');
$search_placeholder = $search_placeholder ?? 'Rechercher...';
$min_search = isset($min_search) ? max(0, (int) $min_search) : 5;

$normalized_options = cm_form_normalize_options((array) $options);
$show_search = count($normalized_options) >= $min_search;
if (!$show_search) {
    $show_selected_label = true;
}

$group_classes = cm_form_group_class((bool) $required, $error, [
    'readonly' => $readonly,
    'disabled' => $disabled,
    'dense' => $dense,
    'size' => $size,
    'group_class' => $group_class,
]);
$labelClass = cm_form_label_class([
    'readonly' => $readonly,
    'dense' => $dense,
    'size' => $size,
    'label_class' => $label_class,
]);
$searchInputClass = cm_form_control_class('cm-form-control cm-select-search__input', [
    'readonly' => $readonly,
    'disabled' => $disabled,
    'dense' => $dense,
    'size' => $size,
    'control_class' => $control_class,
]);
$wrapperClass = cm_form_class_names(
    'cm-select-search',
    $dense ? 'is-dense' : '',
    $size !== '' ? 'is-' . $size : '',
    $wrapper_class
);

$search_id = (string) $id . '_search';
$list_id = (string) $id . '_list';
$hidden_id = (string) $id . '_hidden';

$selected_label = $placeholder;
foreach ($normalized_options as $option) {
    if ((string) $option['value'] === $selected) {
        $selected_label = (string) $option['label'];
        break;
    }
}
?>
<div class="<?= htmlspecialchars($group_classes, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($label !== ''): ?>
    <label for="<?= htmlspecialchars($hidden_id, ENT_QUOTES, 'UTF-8') ?>" class="<?= htmlspecialchars($labelClass, ENT_QUOTES, 'UTF-8') ?>">
        <?= htmlspecialchars((string) $label, ENT_QUOTES, 'UTF-8') ?><?= $required ? ' <span class="cm-required-star">*</span>' : '' ?>
    </label>
    <?php endif; ?>

    <div class="<?= htmlspecialchars($wrapperClass, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>_wrapper" aria-expanded="false">
        <?php if ($show_search): ?>
        <input type="text"
               id="<?= htmlspecialchars($search_id, ENT_QUOTES, 'UTF-8') ?>"
               class="<?= htmlspecialchars($searchInputClass, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="<?= htmlspecialchars((string) $search_placeholder, ENT_QUOTES, 'UTF-8') ?>"
               autocomplete="off"
               <?= $disabled ? 'disabled' : '' ?>>
        <?php endif; ?>

        <div class="cm-select-search__list"
             id="<?= htmlspecialchars($list_id, ENT_QUOTES, 'UTF-8') ?>"
             role="listbox"
             aria-label="Liste des options"
             aria-hidden="true">
            <?php foreach ($normalized_options as $option): ?>
            <?php
            $opt_value = (string) $option['value'];
            $opt_label = (string) $option['label'];
            $is_selected = $opt_value === $selected;
            $opt_disabled = !empty($option['disabled']);
            ?>
            <button type="button"
                    class="cm-select-search__option <?= $is_selected ? 'is-selected' : '' ?>"
                    data-value="<?= htmlspecialchars($opt_value, ENT_QUOTES, 'UTF-8') ?>"
                    data-label="<?= htmlspecialchars($opt_label, ENT_QUOTES, 'UTF-8') ?>"
                    role="option"
                    aria-selected="<?= $is_selected ? 'true' : 'false' ?>"
                    <?= $disabled || $readonly || $opt_disabled ? 'disabled' : '' ?>>
                <?= htmlspecialchars($opt_label, ENT_QUOTES, 'UTF-8') ?>
            </button>
            <?php endforeach; ?>
            <?php if (empty($normalized_options)): ?>
            <div class="cm-select-search__empty">Aucune option disponible.</div>
            <?php endif; ?>
        </div>

        <input type="hidden"
               name="<?= htmlspecialchars((string) $name, ENT_QUOTES, 'UTF-8') ?>"
               id="<?= htmlspecialchars($hidden_id, ENT_QUOTES, 'UTF-8') ?>"
               value="<?= htmlspecialchars($selected, ENT_QUOTES, 'UTF-8') ?>"
               <?= $required ? 'required' : '' ?><?= cm_form_attr_string((array) $attrs) ?>>

        <?php if ($show_selected_label): ?>
        <div class="cm-form-hint cm-select-search__selected-label" id="<?= htmlspecialchars((string) $id, ENT_QUOTES, 'UTF-8') ?>_selected_label">
            <?= htmlspecialchars($selected_label, ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>
    </div>

    <?php if ($error !== ''): ?>
    <span class="cm-form-error" role="alert"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>

    <?php if ($hint !== ''): ?>
    <span class="cm-form-hint"><?= htmlspecialchars((string) $hint, ENT_QUOTES, 'UTF-8') ?></span>
    <?php endif; ?>
</div>

<script>
(function () {
    const controller = typeof AbortController === 'function' ? new AbortController() : null;
    const listenerOptions = controller ? { signal: controller.signal } : undefined;

    if (window.CM && window.CM.pageLifecycle && controller) {
        window.CM.pageLifecycle.registerCleanup(function () {
            controller.abort();
        });
    }

    const wrapper = document.getElementById(<?= json_encode((string) $id . '_wrapper') ?>);
    const searchInput = document.getElementById(<?= json_encode($search_id) ?>);
    const list = document.getElementById(<?= json_encode($list_id) ?>);
    const hidden = document.getElementById(<?= json_encode($hidden_id) ?>);
    const label = document.getElementById(<?= json_encode((string) $id . '_selected_label') ?>);

    if (!wrapper || !list || !hidden) {
        return;
    }

    const options = Array.from(list.querySelectorAll('.cm-select-search__option'));
    const placeholder = <?= json_encode((string) $placeholder) ?>;
    const selectedLabel = <?= json_encode((string) $selected_label) ?>;
    const keepSelectedInInput = <?= json_encode($show_search && !$show_selected_label) ?>;
    const normalizeSearchText = function (value) {
        const text = String(value || '').toLowerCase().trim();
        if (typeof text.normalize !== 'function') {
            return text;
        }
        return text.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    };

    const resetFilteredOptions = function () {
        options.forEach(function (button) {
            button.classList.remove('cm-hidden');
            button.hidden = false;
        });
        const empty = list.querySelector('.cm-select-search__empty');
        if (empty && options.length > 0) {
            empty.remove();
        }
    };

    const openList = function () {
        resetFilteredOptions();
        wrapper.classList.add('is-open');
        wrapper.setAttribute('aria-expanded', 'true');
        list.setAttribute('aria-hidden', 'false');
    };

    const closeList = function () {
        wrapper.classList.remove('is-open');
        wrapper.setAttribute('aria-expanded', 'false');
        list.setAttribute('aria-hidden', 'true');
    };

    const setSelected = function (button) {
        const nextValue = button.dataset.value || '';
        const nextLabel = button.dataset.label || placeholder;
        const previousValue = hidden.value;

        options.forEach(function (opt) {
            opt.classList.remove('is-selected');
            opt.setAttribute('aria-selected', 'false');
        });

        button.classList.add('is-selected');
        button.setAttribute('aria-selected', 'true');

        hidden.value = nextValue;
        if (label) {
            label.textContent = nextLabel;
        }
        if (searchInput) {
            searchInput.value = keepSelectedInInput && nextLabel !== placeholder ? nextLabel : '';
        }

        if (previousValue !== nextValue) {
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
        }
    };

    list.addEventListener('click', function (event) {
        var button = event.target.closest('.cm-select-search__option');
        if (!button) {
            return;
        }
        event.stopPropagation();
        if (button.hasAttribute('disabled')) {
            return;
        }
        setSelected(button);
        closeList();
    });

    wrapper.addEventListener('click', function (event) {
        if (event.target.closest('.cm-select-search__option')) {
            return;
        }
        if (searchInput && event.target !== searchInput && event.target !== label && !event.target.closest('.cm-form-hint')) {
            return;
        }
        openList();
    });

    if (searchInput) {
        if (keepSelectedInInput && hidden.value !== '') {
            searchInput.value = selectedLabel !== placeholder ? selectedLabel : '';
        }

        searchInput.addEventListener('focus', function () {
            if (keepSelectedInInput && searchInput.value !== '') {
                searchInput.select();
            }
            openList();
        });

        searchInput.addEventListener('input', function () {
            const term = normalizeSearchText(searchInput.value);
            let visible = 0;

            openList();

            options.forEach(function (button) {
                const labelText = normalizeSearchText(button.dataset.label || '');
                const match = labelText.includes(term);
                button.classList.toggle('cm-hidden', !match);
                button.hidden = !match;
                if (match) {
                    visible += 1;
                }
            });

            let empty = list.querySelector('.cm-select-search__empty');
            if (visible === 0) {
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'cm-select-search__empty';
                    empty.textContent = 'Aucun resultat.';
                    list.appendChild(empty);
                }
            } else if (empty) {
                empty.remove();
            }
        });
    }

    document.addEventListener('click', function (event) {
        if (!wrapper.contains(event.target)) {
            closeList();
        }
    }, listenerOptions);

    document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
            closeList();
        }
    }, listenerOptions);

    closeList();
})();
</script>

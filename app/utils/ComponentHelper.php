<?php

/**
 * ComponentHelper - centralized rendering for reusable UI components.
 *
 * This file intentionally exposes global procedural helpers (`cm_component`,
 * `cm_render_component`, `cm_asset`) to match the legacy view layer.
 */
class ComponentHelper
{
    private string $basePath;

    public function __construct(?string $basePath = null)
    {
        $this->basePath = $basePath
            ?? dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR;
    }

    /**
     * Render a component and return HTML.
     *
     * @param string $path   Component path without extension.
     * @param array<string, mixed> $params
     */
    public function render(string $path, array $params = []): string
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        if ($normalized === '' || strpos($normalized, '..') !== false) {
            return '<!-- Invalid component path -->';
        }

        $file = $this->basePath . str_replace('/', DIRECTORY_SEPARATOR, $normalized) . '.php';
        if (!is_file($file)) {
            return "<!-- Component not found: {$normalized} -->";
        }

        ob_start();
        extract($params, EXTR_SKIP);
        include $file;
        return (string) ob_get_clean();
    }
}

if (!function_exists('cm_component')) {
    /**
     * Include a reusable component from ressources/components.
     *
     * @param string $name
     * @param array<string, mixed> $props
     *
     * @throws RuntimeException if component does not exist.
     */
    function cm_component(string $name, array $props = []): void
    {
        $normalized = trim(str_replace('\\', '/', $name), '/');
        if ($normalized === '' || strpos($normalized, '..') !== false) {
            throw new RuntimeException('[cm_component] Invalid component name: ' . $name);
        }

        $base = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'ressources' . DIRECTORY_SEPARATOR . 'components';
        $file = $base . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized) . '.php';

        if (!is_file($file)) {
            throw new RuntimeException('[cm_component] Component not found: ' . $normalized . ' (' . $file . ')');
        }

        (static function (string $_file, array $_props): void {
            extract($_props, EXTR_SKIP);
            require $_file;
        })($file, $props);
    }
}

if (!function_exists('cm_render_component')) {
    /**
     * Render a component and return its HTML.
     *
     * @param string $name
     * @param array<string, mixed> $props
     */
    function cm_render_component(string $name, array $props = []): string
    {
        ob_start();
        cm_component($name, $props);
        return (string) ob_get_clean();
    }
}

if (!function_exists('cm_asset')) {
    /**
     * Build an asset URL with cache busting from public/assets.
     *
     * Works for both `/public/layout.php` and `/public/app/layout.php`.
     */
    function cm_asset(string $path): string
    {
        $clean = ltrim(str_replace('\\', '/', $path), '/');
        if ($clean === '') {
            return 'assets';
        }

        $full = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'assets' . DIRECTORY_SEPARATOR
            . str_replace('/', DIRECTORY_SEPARATOR, $clean);
        $version = is_file($full) ? (string) filemtime($full) : (string) time();

        $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
        $prefix = strpos($script, '/app/') !== false ? '../' : '';

        return $prefix . 'assets/' . $clean . '?v=' . rawurlencode($version);
    }
}

if (!function_exists('cm_render_param_crud_view')) {
    /**
     * Render a standardized PRD6/7 CRUD screen for PARAM_* pages.
     *
     * @param array<string, mixed> $config
     */
    function cm_render_param_crud_view(array $config): void
    {
        if (!function_exists('canCreate')) {
            $permFile = __DIR__ . DIRECTORY_SEPARATOR . 'permissions_helper.php';
            if (is_file($permFile)) {
                require_once $permFile;
            }
        }

        $pageSlug = (string) ($config['page_slug'] ?? ($_GET['page'] ?? 'parametres_generaux'));
        $action = (string) ($config['action'] ?? ($_GET['action'] ?? ''));
        if ($action === '') {
            echo '<!-- cm_render_param_crud_view: action manquante -->';
            return;
        }

        $title = (string) ($config['title'] ?? ucfirst(str_replace('_', ' ', $action)));
        $icon = (string) ($config['icon'] ?? 'fa-sliders-h');
        $formTitleAdd = (string) ($config['form_title_add'] ?? ('Ajouter ' . strtolower($title)));
        $formTitleEdit = (string) ($config['form_title_edit'] ?? ('Modifier ' . strtolower($title)));
        $idKey = (string) ($config['id_key'] ?? 'id');
        $idFieldName = (string) ($config['id_field_name'] ?? $idKey);
        $idParam = (string) ($config['id_param'] ?? $idKey);
        $searchParam = (string) ($config['search_param'] ?? 'search');
        $limitParam = (string) ($config['limit_param'] ?? 'limit');
        $pageParam = (string) ($config['page_param'] ?? 'p');
        $perPageOptions = is_array($config['per_page_options'] ?? null) ? $config['per_page_options'] : [10, 25, 50, 100];
        $searchFields = is_array($config['search_fields'] ?? null) ? $config['search_fields'] : [];
        $formFields = is_array($config['form_fields'] ?? null) ? $config['form_fields'] : [];
        $columnDefs = is_array($config['columns'] ?? null) ? $config['columns'] : [];
        $extraQuery = is_array($config['extra_query'] ?? null) ? $config['extra_query'] : [];
        $hiddenFields = is_array($config['hidden_fields'] ?? null) ? $config['hidden_fields'] : [];

        $addButtonName = (string) ($config['add_button_name'] ?? '');
        $editButtonName = (string) ($config['edit_button_name'] ?? '');
        $addButtonLabel = (string) ($config['add_button_label'] ?? 'Ajouter');
        $editButtonLabel = (string) ($config['edit_button_label'] ?? 'Modifier');

        $messageSuccess = (string) ($config['message_success'] ?? ($GLOBALS['messageSuccess'] ?? ''));
        $messageErreur = (string) ($config['message_error'] ?? ($GLOBALS['messageErreur'] ?? ''));

        $list = is_array($config['list'] ?? null) ? $config['list'] : [];
        $editObject = $config['edit'] ?? null;
        $isEdit = is_object($editObject) || is_array($editObject);

        $extraQueryString = '';
        if (!empty($extraQuery)) {
            $extraQueryString = '&' . http_build_query($extraQuery);
        }

        $baseUrl = '?page=' . rawurlencode($pageSlug) . '&action=' . rawurlencode($action) . $extraQueryString;

        $search = trim((string) ($_GET[$searchParam] ?? ''));
        $perPage = (int) ($_GET[$limitParam] ?? ($config['default_per_page'] ?? 10));
        if (!in_array($perPage, array_map('intval', $perPageOptions), true)) {
            $perPage = (int) ($config['default_per_page'] ?? 10);
        }
        $currentPage = max(1, (int) ($_GET[$pageParam] ?? 1));

        $rowValue = static function ($row, string $key, $default = '') {
            if (is_array($row)) {
                return $row[$key] ?? $default;
            }
            if (is_object($row)) {
                return $row->{$key} ?? $default;
            }
            return $default;
        };

        if ($search !== '') {
            $toLower = static function (string $v): string {
                return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
            };
            $contains = static function (string $haystack, string $needle): bool {
                if (function_exists('mb_strpos')) {
                    return mb_strpos($haystack, $needle) !== false;
                }
                return strpos($haystack, $needle) !== false;
            };

            $needle = $toLower($search);
            $list = array_values(array_filter($list, static function ($row) use ($searchFields, $rowValue, $needle): bool {
                $toLower = static function (string $v): string {
                    return function_exists('mb_strtolower') ? mb_strtolower($v, 'UTF-8') : strtolower($v);
                };
                $contains = static function (string $haystack, string $needle): bool {
                    if (function_exists('mb_strpos')) {
                        return mb_strpos($haystack, $needle) !== false;
                    }
                    return strpos($haystack, $needle) !== false;
                };

                if (empty($searchFields)) {
                    $text = is_array($row) || is_object($row) ? json_encode($row, JSON_UNESCAPED_UNICODE) : (string) $row;
                    return $contains($toLower((string) $text), $needle);
                }
                foreach ($searchFields as $field) {
                    $value = (string) $rowValue($row, (string) $field, '');
                    if ($value !== '' && $contains($toLower($value), $needle)) {
                        return true;
                    }
                }
                return false;
            }));
        }

        $pagination = function_exists('cm_paginate')
            ? cm_paginate(count($list), $perPage, $currentPage)
            : [
                'total' => count($list),
                'per_page' => $perPage,
                'current' => $currentPage,
                'last' => 1,
                'offset' => 0,
                'has_prev' => false,
                'has_next' => false,
                'pages' => [1],
            ];

        $rowsPage = array_slice($list, (int) ($pagination['offset'] ?? 0), $perPage);

        $uid = 'cmParamCrud_' . preg_replace('/[^a-zA-Z0-9]/', '_', $action) . '_' . substr(md5($action . $title), 0, 8);
        $tableId = $uid . '_table';
        $filterFormId = $uid . '_filters';
        $bulkFormId = $uid . '_bulk';
        $limitId = $uid . '_limit';
        $searchId = $uid . '_search';
        $deleteBtnId = $uid . '_delete_selected';
        $selectAllBtnId = $uid . '_select_all';
        $deselectAllBtnId = $uid . '_deselect_all';
        $selectedCountId = $uid . '_selected_count';
        $printBtnId = $uid . '_print';
        $exportBtnId = $uid . '_export';
        $deleteFlagId = $uid . '_delete_flag';

        $columns = [];
        foreach ($columnDefs as $def) {
            $key = (string) ($def['key'] ?? '');
            $label = (string) ($def['label'] ?? $key);
            if ($key === '') {
                continue;
            }
            $options = [];
            if (isset($def['align'])) {
                $options['align'] = (string) $def['align'];
            }
            if (isset($def['type'])) {
                $options['type'] = (string) $def['type'];
            }
            $columns[] = cm_column($key, $label, $options);
        }

        $tableRows = [];
        foreach ($rowsPage as $row) {
            $normalized = ['_id' => (string) $rowValue($row, $idKey, '')];
            foreach ($columnDefs as $def) {
                $key = (string) ($def['key'] ?? '');
                if ($key === '') {
                    continue;
                }
                if (isset($def['value']) && is_callable($def['value'])) {
                    $normalized[$key] = (string) $def['value']($row);
                } else {
                    $sourceKey = (string) ($def['source'] ?? $key);
                    $normalized[$key] = (string) $rowValue($row, $sourceKey, '');
                }
            }
            $tableRows[] = $normalized;
        }

        ob_start();
        ?>
<form id="<?= htmlspecialchars($baseUrl . '_form', ENT_QUOTES, 'UTF-8') ?>" method="POST" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>" data-cm-ajax-form="true">
    <?php cm_component('form/csrf-token'); ?>
    <?php
    if ($isEdit) {
            $idValue = (string) $rowValue($editObject, $idKey, '');
            if ($idValue !== '') {
            ?>
    <input type="hidden" name="<?= htmlspecialchars($idFieldName, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars($idValue, ENT_QUOTES, 'UTF-8') ?>">
            <?php
        }
    }
    foreach ($hiddenFields as $hiddenName => $hiddenValue) {
        ?>
    <input type="hidden" name="<?= htmlspecialchars((string) $hiddenName, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $hiddenValue, ENT_QUOTES, 'UTF-8') ?>">
        <?php
    }
    ?>
    <div class="cm-grid-4">
        <?php foreach ($formFields as $field): ?>
            <?php
            $fieldName = (string) ($field['name'] ?? '');
            if ($fieldName === '') {
                continue;
            }
            $componentType = (string) ($field['type'] ?? 'text');
            $component = 'form/input-text';
            if ($componentType === 'number') {
                $component = 'form/input-number';
            } elseif ($componentType === 'date') {
                $component = 'form/input-date';
            } elseif ($componentType === 'email') {
                $component = 'form/input-email';
            } elseif ($componentType === 'select') {
                $component = 'form/select';
            } elseif ($componentType === 'select-search') {
                $component = 'form/select-search';
            } elseif ($componentType === 'textarea') {
                $component = 'form/textarea';
            }

            $valueKey = (string) ($field['value_key'] ?? $fieldName);
            $value = $field['value'] ?? $rowValue($editObject, $valueKey, '');
            $props = [
                'name' => $fieldName,
                'id' => (string) ($field['id'] ?? $fieldName),
                'label' => (string) ($field['label'] ?? $fieldName),
                'required' => !empty($field['required']),
                'placeholder' => (string) ($field['placeholder'] ?? ''),
                'value' => (string) $value,
                'readonly' => !empty($field['readonly']),
                'disabled' => !empty($field['disabled']),
                'attrs' => is_array($field['attrs'] ?? null) ? $field['attrs'] : [],
            ];

            if ($componentType === 'select' || $componentType === 'select-search') {
                $options = $field['options'] ?? [];
                if (is_callable($options)) {
                    $options = $options($config);
                }
                $props['options'] = is_array($options) ? $options : [];
                $props['selected'] = (string) $value;
            }

            if ($componentType === 'number') {
                if (isset($field['min'])) {
                    $props['min'] = (string) $field['min'];
                }
                if (isset($field['max'])) {
                    $props['max'] = (string) $field['max'];
                }
                if (isset($field['step'])) {
                    $props['step'] = (string) $field['step'];
                }
            }

            if ($componentType === 'textarea' && isset($field['rows'])) {
                $props['rows'] = (int) $field['rows'];
            }

            cm_component($component, $props);
            ?>
        <?php endforeach; ?>
    </div>

    <?php
    $actions = [];
    if ($isEdit) {
        $actions[] = [
            'tag' => 'a',
            'href' => $baseUrl,
            'label' => 'Annuler',
            'icon' => 'fa-xmark',
            'class' => 'cm-btn is-light',
        ];
        if (function_exists('canEdit') ? canEdit() : true) {
            $actions[] = [
                'tag' => 'button',
                'type' => 'submit',
                'label' => $editButtonLabel,
                'icon' => 'fa-floppy-disk',
                'class' => 'cm-btn is-success',
                'attrs' => ['name' => $editButtonName],
            ];
        }
    } else {
        $actions[] = [
            'tag' => 'button',
            'type' => 'reset',
            'label' => 'Reinitialiser',
            'icon' => 'fa-rotate-left',
            'class' => 'cm-btn is-light',
        ];
        if (function_exists('canCreate') ? canCreate() : true) {
            $actions[] = [
                'tag' => 'button',
                'type' => 'submit',
                'label' => $addButtonLabel,
                'icon' => 'fa-floppy-disk',
                'class' => 'cm-btn is-success',
                'attrs' => ['name' => $addButtonName],
            ];
        }
    }
    cm_component('crud/form-actions', ['actions' => $actions]);
    ?>
</form>
        <?php
        $formContent = (string) ob_get_clean();

        ob_start();
        ?>
<form id="<?= htmlspecialchars($filterFormId, ENT_QUOTES, 'UTF-8') ?>" method="GET" action="" data-cm-ajax-form="true">
    <input type="hidden" name="page" value="<?= htmlspecialchars($pageSlug, ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="action" value="<?= htmlspecialchars($action, ENT_QUOTES, 'UTF-8') ?>">
    <?php foreach ($extraQuery as $k => $v): ?>
    <input type="hidden" name="<?= htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') ?>" value="<?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
    <input type="hidden" name="<?= htmlspecialchars($pageParam, ENT_QUOTES, 'UTF-8') ?>" value="1">
    <?php
    ob_start();
    ?>
    <label class="cm-toolbar__control">
        <span>Afficher:</span>
        <select id="<?= htmlspecialchars($limitId, ENT_QUOTES, 'UTF-8') ?>" class="cm-form-control cm-toolbar__select" name="<?= htmlspecialchars($limitParam, ENT_QUOTES, 'UTF-8') ?>">
            <?php foreach ($perPageOptions as $opt): ?>
                <?php $optValue = (int) $opt; ?>
            <option value="<?= $optValue ?>" <?= $optValue === $perPage ? 'selected' : '' ?>><?= $optValue ?></option>
            <?php endforeach; ?>
        </select>
    </label>
    <?php
    $leftHtml = (string) ob_get_clean();

    ob_start();
    ?>
    <div class="cm-toolbar__search-wrap">
        <input id="<?= htmlspecialchars($searchId, ENT_QUOTES, 'UTF-8') ?>"
               type="search"
               class="cm-form-control"
               name="<?= htmlspecialchars($searchParam, ENT_QUOTES, 'UTF-8') ?>"
               value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
               placeholder="Rechercher...">
        <button type="submit" class="cm-btn is-info is-sm">
            <i class="fas fa-search" aria-hidden="true"></i>
            <span>Rechercher</span>
        </button>
    </div>
    <?php
    $centerHtml = (string) ob_get_clean();

    ob_start();
    ?>
    <div class="cm-toolbar__actions">
        <?php if (function_exists('canDelete') ? canDelete() : true): ?>
        <button type="button" id="<?= htmlspecialchars($selectAllBtnId, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-info is-sm">
            <i class="fas fa-check-square" aria-hidden="true"></i>
            <span>Tout selectionner</span>
        </button>
        <button type="button" id="<?= htmlspecialchars($deselectAllBtnId, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-light is-sm">
            <i class="fas fa-square" aria-hidden="true"></i>
            <span>Deselectionner</span>
        </button>
        <button type="button" id="<?= htmlspecialchars($deleteBtnId, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-danger is-sm" disabled>
            <i class="fas fa-trash" aria-hidden="true"></i>
            <span>Supprimer (<span id="<?= htmlspecialchars($selectedCountId, ENT_QUOTES, 'UTF-8') ?>">0</span>)</span>
        </button>
        <?php endif; ?>
        <?php if (function_exists('canView') ? canView() : true): ?>
        <button type="button" id="<?= htmlspecialchars($printBtnId, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-info is-sm">
            <i class="fas fa-print" aria-hidden="true"></i>
            <span>Imprimer</span>
        </button>
        <button type="button" id="<?= htmlspecialchars($exportBtnId, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-info is-sm">
            <i class="fas fa-file-export" aria-hidden="true"></i>
            <span>Exporter</span>
        </button>
        <?php endif; ?>
        <button type="button" id="<?= htmlspecialchars($printBtnId, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-info is-sm">
            <i class="fas fa-print" aria-hidden="true"></i>
            <span>Imprimer</span>
        </button>
        <button type="button" id="<?= htmlspecialchars($exportBtnId, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-info is-sm">
            <i class="fas fa-file-export" aria-hidden="true"></i>
            <span>Exporter</span>
        </button>
    </div>
    <?php
    $rightHtml = (string) ob_get_clean();

    cm_component('crud/toolbar', [
        'left_html' => $leftHtml,
        'center_html' => $centerHtml,
        'right_html' => $rightHtml,
    ]);
    ?>
</form>
        <?php
        $toolbarHtml = (string) ob_get_clean();

        $tableActions = [];
        if (function_exists('canEdit') ? canEdit() : true) {
            $tableActions[] = [
                'tag' => 'button',
                'type' => 'button',
                'label' => 'Modifier',
                'icon' => 'fa-pen',
                'class' => 'cm-btn-action is-edit',
            ];
        }
        if (function_exists('canDelete') ? canDelete() : true) {
            $tableActions[] = [
                'tag' => 'button',
                'type' => 'button',
                'label' => 'Supprimer',
                'icon' => 'fa-trash',
                'class' => 'cm-btn-action is-delete',
            ];
        }

        $paginationParams = array_merge($extraQuery, [
            'page' => $pageSlug,
            'action' => $action,
            $limitParam => $perPage,
        ]);
        if ($search !== '') {
            $paginationParams[$searchParam] = $search;
        }
        $paginationBaseUrl = '?' . http_build_query($paginationParams);
        $screenClass = trim((string) ($config['screen_class'] ?? 'cm-prd3-crud-screen cm-prd6-admin-screen'));
        ?>
<section class="<?= htmlspecialchars($screenClass, ENT_QUOTES, 'UTF-8') ?>" id="<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') ?>">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php
        cm_component('crud/form-pole', [
            'title' => $isEdit ? $formTitleEdit : $formTitleAdd,
            'icon' => $icon,
            'content' => $formContent,
        ]);
        ?>

        <?= $toolbarHtml ?>

        <div class="cm-pole-inferieur">
            <form id="<?= htmlspecialchars($bulkFormId, ENT_QUOTES, 'UTF-8') ?>" method="POST" action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="submit_delete_multiple" id="<?= htmlspecialchars($deleteFlagId, ENT_QUOTES, 'UTF-8') ?>" value="0">
                <?php
                cm_component('crud/data-table', [
                    'id' => $tableId,
                    'columns' => $columns,
                    'rows' => $tableRows,
                    'row_key' => '_id',
                    'selectable' => (function_exists('canDelete') ? canDelete() : true),
                    'actions' => $tableActions,
                    'empty_title' => 'Aucune donnee',
                    'empty_message' => 'Aucun enregistrement trouve.',
                ]);
                ?>
            </form>

            <?php cm_component('crud/pagination', [
                'pagination' => $pagination,
                'base_url' => $paginationBaseUrl,
                'param_name' => $pageParam,
            ]); ?>
        </div>
    </div>
</section>

<script>
(function () {
    const root = document.getElementById(<?= json_encode($uid) ?>);
    if (!root) {
        return;
    }

    const filterForm = document.getElementById(<?= json_encode($filterFormId) ?>);
    const bulkForm = document.getElementById(<?= json_encode($bulkFormId) ?>);
    const table = document.getElementById(<?= json_encode($tableId) ?>);
    const limitSelect = document.getElementById(<?= json_encode($limitId) ?>);
    const deleteFlag = document.getElementById(<?= json_encode($deleteFlagId) ?>);
    const deleteBtn = document.getElementById(<?= json_encode($deleteBtnId) ?>);
    const selectAllBtn = document.getElementById(<?= json_encode($selectAllBtnId) ?>);
    const deselectAllBtn = document.getElementById(<?= json_encode($deselectAllBtnId) ?>);
    const selectedCount = document.getElementById(<?= json_encode($selectedCountId) ?>);
    const printBtn = document.getElementById(<?= json_encode($printBtnId) ?>);
    const exportBtn = document.getElementById(<?= json_encode($exportBtnId) ?>);

    const navigate = function (url) {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url);
            return;
        }
        window.location.href = url;
    };

    const rowChecks = function () {
        if (!table) {
            return [];
        }
        return Array.from(table.querySelectorAll('.cm-table-check-row'));
    };

    const clearGeneratedInputs = function () {
        if (!bulkForm) {
            return;
        }
        bulkForm.querySelectorAll('input[data-generated-selected="1"]').forEach(function (el) {
            el.remove();
        });
    };

    const updateSelectionState = function () {
        const checks = rowChecks();
        const selected = checks.filter(function (input) { return input.checked; });
        if (selectedCount) {
            selectedCount.textContent = String(selected.length);
        }
        if (deleteBtn) {
            deleteBtn.disabled = selected.length === 0;
        }
        const checkAll = table ? table.querySelector('.cm-table-check-all') : null;
        if (checkAll) {
            checkAll.checked = checks.length > 0 && selected.length === checks.length;
        }
    };

    const submitDelete = function (ids) {
        if (!bulkForm || !deleteFlag) {
            return;
        }
        clearGeneratedInputs();
        ids.forEach(function (id) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = 'selected_ids[]';
            hidden.value = String(id);
            hidden.setAttribute('data-generated-selected', '1');
            bulkForm.appendChild(hidden);
        });
        deleteFlag.value = '1';
        if (typeof bulkForm.requestSubmit === 'function') {
            bulkForm.requestSubmit();
        } else {
            bulkForm.submit();
        }
    };

    if (limitSelect && filterForm) {
        limitSelect.addEventListener('change', function () {
            if (typeof filterForm.requestSubmit === 'function') {
                filterForm.requestSubmit();
            } else {
                filterForm.submit();
            }
        });
    }

    if (table) {
        table.addEventListener('change', function (event) {
            if (event.target.classList.contains('cm-table-check-row') || event.target.classList.contains('cm-table-check-all')) {
                if (event.target.classList.contains('cm-table-check-all')) {
                    rowChecks().forEach(function (cb) { cb.checked = event.target.checked; });
                }
                updateSelectionState();
            }
        });

        table.addEventListener('click', function (event) {
            const editButton = event.target.closest('.cm-btn-action.is-edit');
            if (editButton) {
                const rowId = editButton.getAttribute('data-row-id') || '';
                if (rowId !== '') {
                    navigate(<?= json_encode($baseUrl) ?> + '&' + <?= json_encode($idParam) ?> + '=' + encodeURIComponent(rowId));
                }
                return;
            }

            const deleteButton = event.target.closest('.cm-btn-action.is-delete');
            if (deleteButton) {
                const rowId = deleteButton.getAttribute('data-row-id') || '';
                if (rowId !== '' && window.confirm('Confirmer la suppression de cet element ?')) {
                    submitDelete([rowId]);
                }
            }
        });
    }

    if (selectAllBtn) {
        selectAllBtn.addEventListener('click', function () {
            rowChecks().forEach(function (cb) { cb.checked = true; });
            updateSelectionState();
        });
    }

    if (deselectAllBtn) {
        deselectAllBtn.addEventListener('click', function () {
            rowChecks().forEach(function (cb) { cb.checked = false; });
            updateSelectionState();
        });
    }

    if (deleteBtn) {
        deleteBtn.addEventListener('click', function () {
            const ids = rowChecks().filter(function (cb) { return cb.checked; }).map(function (cb) { return cb.value; });
            if (ids.length === 0) {
                return;
            }
            if (window.confirm('Confirmer la suppression des elements selectionnes ?')) {
                submitDelete(ids);
            }
        });
    }

    if (printBtn) {
        printBtn.addEventListener('click', function () {
            if (!table) {
                return;
            }
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                return;
            }
            printWindow.document.write('<html><head><title>Impression</title><style>body{font-family:Arial,sans-serif;padding:16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d1d5db;padding:8px;text-align:left}th{background:#f3f4f6}</style></head><body>');
            printWindow.document.write(table.outerHTML);
            printWindow.document.write('</body></html>');
            printWindow.document.close();
            printWindow.focus();
            printWindow.print();
        });
    }

    if (exportBtn) {
        exportBtn.addEventListener('click', function () {
            if (!table) {
                return;
            }
            const rows = Array.from(table.querySelectorAll('tr'));
            const csv = rows.map(function (tr) {
                return Array.from(tr.querySelectorAll('th,td')).map(function (cell) {
                    const text = (cell.textContent || '').replace(/\\s+/g, ' ').trim();
                    return '\"' + text.replace(/\"/g, '\"\"') + '\"';
                }).join(',');
            }).join('\\n');

            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = <?= json_encode($action . '.csv') ?>;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
        });
    }

    updateSelectionState();
})();
</script>
        <?php
    }
}

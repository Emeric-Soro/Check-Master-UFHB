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

        (static function (string $_file, array $_props): void{
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
        <form id="<?= htmlspecialchars($baseUrl . '_form', ENT_QUOTES, 'UTF-8') ?>" method="POST"
            action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>" data-cm-ajax-form="true">
            <?php cm_component('form/csrf-token'); ?>
            <?php
            if ($isEdit) {
                $idValue = (string) $rowValue($editObject, $idKey, '');
                if ($idValue !== '') {
                    ?>
                    <input type="hidden" name="<?= htmlspecialchars($idFieldName, ENT_QUOTES, 'UTF-8') ?>"
                        value="<?= htmlspecialchars($idValue, ENT_QUOTES, 'UTF-8') ?>">
                    <?php
                }
            }
            foreach ($hiddenFields as $hiddenName => $hiddenValue) {
                ?>
                <input type="hidden" name="<?= htmlspecialchars((string) $hiddenName, ENT_QUOTES, 'UTF-8') ?>"
                    value="<?= htmlspecialchars((string) $hiddenValue, ENT_QUOTES, 'UTF-8') ?>">
                <?php
            }
            ?>
            <?php
            $formGridFields = [];
            foreach ($formFields as $field) {
                $fieldName = (string) ($field['name'] ?? '');
                if ($fieldName === '') {
                    continue;
                }

                $componentType = (string) ($field['type'] ?? 'text');
                $valueKey = (string) ($field['value_key'] ?? $fieldName);
                $value = $field['value'] ?? $rowValue($editObject, $valueKey, '');
                $attrs = is_array($field['attrs'] ?? null) ? $field['attrs'] : [];

                $props = [
                    'name' => $fieldName,
                    'id' => (string) ($field['id'] ?? $fieldName),
                    'label' => (string) ($field['label'] ?? $fieldName),
                    'required' => !empty($field['required']),
                    'placeholder' => (string) ($field['placeholder'] ?? ''),
                    'value' => (string) $value,
                    'readonly' => !empty($field['readonly']),
                    'disabled' => !empty($field['disabled']),
                    'attrs' => $attrs,
                    'type' => $componentType,
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

                $size = (string) ($field['size'] ?? '');
                $legacyControlClass = (string) ($field['control_class'] ?? '');
                $legacySize = '';
                if ($legacyControlClass !== '' && preg_match('/cm-field-+(xs|sm|md|lg|xl|date|year|full)/', $legacyControlClass, $matches)) {
                    $legacySize = (string) ($matches[1] ?? '');
                }
                if ($size === '' && $legacySize !== '') {
                    $size = $legacySize;
                }
                if ($size === '') {
                    if ($componentType === 'date') {
                        $size = 'date';
                    } elseif ($componentType === 'email') {
                        $size = 'lg';
                    } elseif ($componentType === 'textarea') {
                        $size = 'full';
                    } elseif ($componentType === 'number') {
                        $size = 'sm';
                    } elseif ($componentType === 'select' || $componentType === 'select-search') {
                        $size = 'md';
                    } else {
                        $size = 'md';
                    }
                }
                $props['size'] = $size;

                if ($componentType === 'select-search') {
                    $props['type'] = 'select';
                    $props['component'] = 'form/select-search';
                }

                if ($legacyControlClass !== '' && $legacySize === '') {
                    $props['control_class'] = $legacyControlClass;
                }

                $formGridFields[] = $props;
            }

            cm_component('form/form-grid', [
                'cols' => 4,
                'fields' => $formGridFields,
            ]);
            ?>

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
                <input type="hidden" name="<?= htmlspecialchars((string) $k, ENT_QUOTES, 'UTF-8') ?>"
                    value="<?= htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8') ?>">
            <?php endforeach; ?>
            <input type="hidden" name="<?= htmlspecialchars($pageParam, ENT_QUOTES, 'UTF-8') ?>" value="1">
            <?php
            ob_start();
            ?>
            <label class="cm-toolbar__control">
                <span>Afficher:</span>
                <select id="<?= htmlspecialchars($limitId, ENT_QUOTES, 'UTF-8') ?>" class="cm-form-control cm-toolbar__select"
                    name="<?= htmlspecialchars($limitParam, ENT_QUOTES, 'UTF-8') ?>">
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
                <input id="<?= htmlspecialchars($searchId, ENT_QUOTES, 'UTF-8') ?>" type="search" class="cm-form-control"
                    name="<?= htmlspecialchars($searchParam, ENT_QUOTES, 'UTF-8') ?>"
                    value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher...">
                <button type="submit" class="cm-btn is-info is-sm">
                    <span>Rechercher</span>
                </button>
            </div>
            <?php
            $centerHtml = (string) ob_get_clean();

            ob_start();
            ?>
            <div class="cm-toolbar__actions">
                <?php if (function_exists('canDelete') ? canDelete() : true): ?>
                    <button type="button" id="<?= htmlspecialchars($selectAllBtnId, ENT_QUOTES, 'UTF-8') ?>"
                        class="cm-btn is-info is-sm">
                        <span>Tout sélectionner</span>
                    </button>
                    <button type="button" id="<?= htmlspecialchars($deselectAllBtnId, ENT_QUOTES, 'UTF-8') ?>"
                        class="cm-btn is-light is-sm">
                        <span>Tout désélectionner</span>
                    </button>
                    <button type="button" id="<?= htmlspecialchars($deleteBtnId, ENT_QUOTES, 'UTF-8') ?>"
                        class="cm-btn is-danger is-sm" disabled>
                        <span>Supprimer (<span id="<?= htmlspecialchars($selectedCountId, ENT_QUOTES, 'UTF-8') ?>">0</span>)</span>
                    </button>
                <?php endif; ?>
                <?php if (function_exists('canView') ? canView() : true): ?>
                    <button type="button" id="<?= htmlspecialchars($printBtnId, ENT_QUOTES, 'UTF-8') ?>"
                        class="cm-btn is-info is-sm">
                        <span>Imprimer</span>
                    </button>
                    <!-- <button type="button" id="<?= htmlspecialchars($exportBtnId, ENT_QUOTES, 'UTF-8') ?>"
                        class="cm-btn is-info is-sm">
                        <span>Exporter</span>
                    </button> -->
                <?php endif; ?>
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
        <section class="<?= htmlspecialchars($screenClass, ENT_QUOTES, 'UTF-8') ?>"
            id="<?= htmlspecialchars($uid, ENT_QUOTES, 'UTF-8') ?>">
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
                    <form id="<?= htmlspecialchars($bulkFormId, ENT_QUOTES, 'UTF-8') ?>" method="POST"
                        action="<?= htmlspecialchars($baseUrl, ENT_QUOTES, 'UTF-8') ?>" data-cm-ajax-form="true">
                        <?php cm_component('form/csrf-token'); ?>
                        <input type="hidden" name="submit_delete_multiple"
                            id="<?= htmlspecialchars($deleteFlagId, ENT_QUOTES, 'UTF-8') ?>" value="0">
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
                // const exportBtn = document.getElementById(<?= json_encode($exportBtnId) ?>);

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

/**
 * Configuration des filtres par écran pour cm_toolbar()
 * Retourne la configuration des filtres spécifiques à chaque page
 */
if (!function_exists('cm_toolbar_filter_configs')) {
    function cm_toolbar_filter_configs(): array
    {
        return [
            // Gestion des étudiants
            'gestion_etudiants' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'specialite', 'label' => 'Spécialité', 'options_url' => 'api/specialites'],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif']],
                ['type' => 'select', 'name' => 'genre', 'label' => 'Genre', 'options' => ['' => 'Tous', 'M' => 'Masculin', 'F' => 'Féminin']],
                ['type' => 'date_range', 'name' => 'date_inscription', 'label' => 'Date d\'inscription'],
            ],
            // Gestion de la scolarité (versements/inscriptions)
            'gestion_scolarite' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'statut_paiement', 'label' => 'Statut paiement', 'options' => ['' => 'Tous', 'solde' => 'Soldé', 'partiel' => 'Partiel', 'non_solde' => 'Non soldé']],
                ['type' => 'select', 'name' => 'mode_paiement', 'label' => 'Mode paiement', 'options' => ['' => 'Tous', 'especes' => 'Espèces', 'cheque' => 'Chèque', 'virement' => 'Virement', 'mobile' => 'Mobile Money']],
                ['type' => 'date_range', 'name' => 'date_versement', 'label' => 'Date de versement'],
            ],
            // Gestion des notes
            'gestion_notes_evaluations' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'session', 'label' => 'Session', 'options' => ['' => 'Toutes', 'normale' => 'Normale', 'rattrapage' => 'Rattrapage']],
                ['type' => 'select', 'name' => 'semestre', 'label' => 'Semestre', 'options' => ['' => 'Tous', 'S1' => 'Semestre 1', 'S2' => 'Semestre 2']],
                ['type' => 'select', 'name' => 'statut_validation', 'label' => 'Statut', 'options' => ['' => 'Tous', 'validee' => 'Validée', 'en_attente' => 'En attente', 'non_saisie' => 'Non saisie']],
            ],
            // Soutenances
            'evaluation_soutenance' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'evaluee' => 'Évaluée', 'non_evaluee' => 'Non évaluée', 'planifiee' => 'Planifiée', 'non_planifiee' => 'Non planifiée']],
                ['type' => 'select', 'name' => 'salle', 'label' => 'Salle', 'options_url' => 'api/salles'],
                ['type' => 'select', 'name' => 'jury', 'label' => 'Jury', 'options_url' => 'api/jurys'],
                ['type' => 'date_range', 'name' => 'date_soutenance', 'label' => 'Date de soutenance'],
            ],
            'programmation_soutenance' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'planifiee' => 'Planifiée', 'non_planifiee' => 'Non planifiée']],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
            // Candidatures
            'gestion_candidatures' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'validee' => 'Validée', 'en_attente' => 'En attente', 'rejetee' => 'Rejetée', 'verifiee' => 'Vérifiée']],
                ['type' => 'select', 'name' => 'specialite', 'label' => 'Spécialité', 'options_url' => 'api/specialites'],
                ['type' => 'date_range', 'name' => 'date_depot', 'label' => 'Date de dépôt'],
            ],
            'verification_candidatures' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'en_attente' => 'En attente', 'verifiee' => 'Vérifiée', 'rejetee' => 'Rejetée']],
                ['type' => 'select', 'name' => 'completude', 'label' => 'Complétude', 'options' => ['' => 'Tous', 'complet' => 'Complet', 'incomplet' => 'Incomplet']],
            ],
            // Rapports
            'rapport_a_valider' => [
                ['type' => 'select', 'name' => 'type_rapport', 'label' => 'Type', 'options' => ['' => 'Tous', 'soutenance' => 'Soutenance', 'stage' => 'Stage', 'projet' => 'Projet']],
                ['type' => 'select', 'name' => 'statut_validation', 'label' => 'Statut validation', 'options' => ['' => 'Tous', 'valide' => 'Validé', 'en_attente' => 'En attente', 'rejete' => 'Rejeté']],
                ['type' => 'date_range', 'name' => 'date_soumission', 'label' => 'Date de soumission'],
            ],
            'gestion_rapports' => [
                ['type' => 'select', 'name' => 'type_rapport', 'label' => 'Type', 'options' => ['' => 'Tous', 'soutenance' => 'Soutenance', 'stage' => 'Stage', 'projet' => 'Projet']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'brouillon' => 'Brouillon', 'soumis' => 'Soumis', 'valide' => 'Validé', 'rejete' => 'Rejeté']],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
            // Réclamations
            'gestion_reclamations' => [
                ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['' => 'Tous', 'note' => 'Note', 'inscription' => 'Inscription', 'soutenance' => 'Soutenance', 'autre' => 'Autre']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'ouverte' => 'Ouverte', 'en_cours' => 'En cours', 'fermee' => 'Fermée', 'rejetee' => 'Rejetée']],
                ['type' => 'select', 'name' => 'priorite', 'label' => 'Priorité', 'options' => ['' => 'Toutes', 'basse' => 'Basse', 'moyenne' => 'Moyenne', 'haute' => 'Haute', 'critique' => 'Critique']],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
            'gestion_reclamations_scolarite' => [
                ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['' => 'Tous', 'paiement' => 'Paiement', 'inscription' => 'Inscription', 'versement' => 'Versement']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'ouverte' => 'Ouverte', 'en_cours' => 'En cours', 'fermee' => 'Fermée']],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
            // Utilisateurs
            'gestion_utilisateurs' => [
                ['type' => 'select', 'name' => 'groupe', 'label' => 'Groupe', 'options_url' => 'api/groupes'],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif']],
                ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['' => 'Tous', 'admin' => 'Admin', 'enseignant' => 'Enseignant', 'etudiant' => 'Étudiant', 'secretaire' => 'Secrétaire']],
            ],
            // RH
            'maj_enseignant' => [
                ['type' => 'select', 'name' => 'grade', 'label' => 'Grade', 'options_url' => 'api/grades'],
                ['type' => 'select', 'name' => 'specialite', 'label' => 'Spécialité', 'options_url' => 'api/specialites'],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif']],
            ],
            'maj_personnel_admin' => [
                ['type' => 'select', 'name' => 'fonction', 'label' => 'Fonction', 'options_url' => 'api/fonctions'],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif']],
            ],
            // Audit
            'piste_audit' => [
                ['type' => 'select', 'name' => 'action', 'label' => 'Action', 'options' => ['' => 'Toutes', 'create' => 'Création', 'update' => 'Modification', 'delete' => 'Suppression', 'view' => 'Consultation', 'export' => 'Export', 'print' => 'Impression']],
                ['type' => 'select', 'name' => 'entite', 'label' => 'Entité', 'options' => ['' => 'Toutes', 'etudiant' => 'Étudiant', 'inscription' => 'Inscription', 'note' => 'Note', 'paiement' => 'Paiement', 'utilisateur' => 'Utilisateur']],
                ['type' => 'select', 'name' => 'utilisateur', 'label' => 'Utilisateur', 'options_url' => 'api/utilisateurs'],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Période'],
            ],
            // Paramètres
            'parametres_generaux' => [
                ['type' => 'select', 'name' => 'categorie', 'label' => 'Catégorie', 'options' => ['' => 'Toutes', 'structure' => 'Structure', 'pedagogie' => 'Pédagogie', 'admin' => 'Administration']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif']],
            ],
            'parametres_specifiques' => [
                ['type' => 'select', 'name' => 'categorie', 'label' => 'Catégorie', 'options' => ['' => 'Toutes', 'operationnel' => 'Opérationnel', 'menus' => 'Menus']],
            ],
            // Archives
            'admin_historique' => [
                ['type' => 'select', 'name' => 'type_archive', 'label' => 'Type', 'options' => ['' => 'Tous', 'etudiant' => 'Étudiant', 'note' => 'Note', 'paiement' => 'Paiement', 'soutenance' => 'Soutenance']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'archive' => 'Archivé', 'restaure' => 'Restauré']],
                ['type' => 'date_range', 'name' => 'date_archivage', 'label' => 'Date d\'archivage'],
            ],
            'archives_dossiers_soutenance' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'annee', 'label' => 'Année académique', 'options_url' => 'api/annees'],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
            'archive_comptes_rendus' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'annee', 'label' => 'Année académique', 'options_url' => 'api/annees'],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
            // Processus/Workflow
            'processus_validation' => [
                ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['' => 'Tous', 'candidature' => 'Candidature', 'rapport' => 'Rapport', 'note' => 'Note']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'en_attente' => 'En attente', 'valide' => 'Validé', 'rejete' => 'Rejeté']],
                ['type' => 'select', 'name' => 'niveau_validation', 'label' => 'Niveau validation', 'options' => ['' => 'Tous', 'n1' => 'Niveau 1', 'n2' => 'Niveau 2', 'final' => 'Final']],
            ],
            // Compte rendu
            'redaction_compte_rendu' => [
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'brouillon' => 'Brouillon', 'soumis' => 'Soumis', 'valide' => 'Validé']],
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
            // Liste étudiants (enseignant/responsable)
            'liste_etudiants_resp' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'specialite', 'label' => 'Spécialité', 'options_url' => 'api/specialites'],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'actif' => 'Actif', 'inactif' => 'Inactif']],
            ],
            'liste_etudiants_ens' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'specialite', 'label' => 'Spécialité', 'options_url' => 'api/specialites'],
            ],
            // Évaluation dossiers
            'evaluation_dossiers' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'evalue' => 'Évalué', 'non_evalue' => 'Non évalué', 'en_cours' => 'En cours']],
                ['type' => 'select', 'name' => 'note_range', 'label' => 'Note', 'options' => ['' => 'Toutes', '0-10' => '0-10', '10-12' => '10-12', '12-14' => '12-14', '14-16' => '14-16', '16-20' => '16-20']],
            ],
            // Dossiers académiques
            'dossiers_academiques' => [
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'complet' => 'Complet', 'incomplet' => 'Incomplet', 'en_attente' => 'En attente']],
            ],
            // Répertoire enseignant
            'repertoire_enseignant' => [
                ['type' => 'select', 'name' => 'type_document', 'label' => 'Type', 'options' => ['' => 'Tous', 'cours' => 'Cours', 'td' => 'TD', 'tp' => 'TP', 'examen' => 'Examen', 'autre' => 'Autre']],
                ['type' => 'select', 'name' => 'niveau', 'label' => 'Niveau', 'options' => ['' => 'Tous', 'L1' => 'Licence 1', 'L2' => 'Licence 2', 'M1' => 'Master 1', 'M2' => 'Master 2']],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date ajout'],
            ],
            // Sauvegarde/Restauration
            'sauvegarde_restauration' => [
                ['type' => 'select', 'name' => 'type', 'label' => 'Type', 'options' => ['' => 'Tous', 'manuelle' => 'Manuelle', 'automatique' => 'Automatique']],
                ['type' => 'select', 'name' => 'statut', 'label' => 'Statut', 'options' => ['' => 'Tous', 'succes' => 'Succès', 'echec' => 'Échec']],
                ['type' => 'date_range', 'name' => 'date', 'label' => 'Date'],
            ],
        ];
    }
}

/**
 * Render a unified toolbar component (search + filters + actions)
 *
 * @param array<string, mixed> $config Configuration array with:
 *   - screen: string Screen identifier for default filters
 *   - filters: array Custom filters (overrides defaults)
 *   - search_placeholder: string Default: "Rechercher..."
 *   - search_name: string Default: "search"
 *   - search_value: string Current search value
 *   - limit: int Current items per page
 *   - limit_options: array Default: [10, 25, 50, 100]
 *   - limit_name: string Default: "limit"
 *   - show_actions: bool Show select/delete/export/print buttons (default: true)
 *   - show_filters: bool Show filter dropdown (default: true)
 *   - custom_actions: array Additional action buttons
 *   - id_prefix: string Prefix for HTML IDs (default: auto-generated)
 *   - on_filter_apply: string JS callback for filter apply
 *   - on_filter_reset: string JS callback for filter reset
 *   - align: string 'left'|'center'|'right'|'space-between' (default: 'space-between')
 */
if (!function_exists('cm_toolbar')) {
    function cm_toolbar(array $config = []): void
    {
        $screen = (string) ($config['screen'] ?? '');
        $idPrefix = (string) ($config['id_prefix'] ?? 'cmToolbar_' . substr(md5(uniqid()), 0, 8));

        // IDs
        $toolbarId = $idPrefix . '_toolbar';
        $searchId = $idPrefix . '_search';
        $limitId = $idPrefix . '_limit';
        $filterDropdownId = $idPrefix . '_filterDropdown';
        $filterToggleId = $idPrefix . '_filterToggle';
        $filterCountId = $idPrefix . '_filterCount';
        $selectAllId = $idPrefix . '_selectAll';
        $deselectAllId = $idPrefix . '_deselectAll';
        $deleteBtnId = $idPrefix . '_deleteBtn';
        $exportBtnId = $idPrefix . '_exportBtn';
        $printBtnId = $idPrefix . '_printBtn';
        $filterFormId = $idPrefix . '_filterForm';

        // Search
        $searchName = (string) ($config['search_name'] ?? 'search');
        $searchValue = (string) ($config['search_value'] ?? '');
        $searchPlaceholder = (string) ($config['search_placeholder'] ?? 'Rechercher...');

        // Pagination
        $limit = (int) ($config['limit'] ?? 10);
        $limitOptions = is_array($config['limit_options'] ?? null) ? $config['limit_options'] : [10, 25, 50, 100];
        $limitName = (string) ($config['limit_name'] ?? 'limit');

        // Actions visibility
        $showActions = (bool) ($config['show_actions'] ?? true);
        $showFilters = (bool) ($config['show_filters'] ?? true);
        $canDelete = (bool) ($config['can_delete'] ?? (function_exists('canDelete') ? canDelete() : true));
        $canView = (bool) ($config['can_view'] ?? (function_exists('canView') ? canView() : true));

        // Filters
        $customFilters = is_array($config['filters'] ?? null) ? $config['filters'] : null;
        if ($customFilters === null && $screen !== '') {
            $allConfigs = cm_toolbar_filter_configs();
            $customFilters = $allConfigs[$screen] ?? [];
        }
        $customFilters = $customFilters ?? [];

        // Active filters count (from URL params)
        $activeFilterCount = 0;
        foreach ($customFilters as $filter) {
            $name = $filter['name'] ?? '';
            if ($name !== '' && !empty($_GET[$name])) {
                $activeFilterCount++;
            }
        }

        $align = (string) ($config['align'] ?? 'space-between');
        $alignClass = 'cm-toolbar--' . $align;

        ?>
        <div class="cm-barre-intermediaire" id="<?= htmlspecialchars($toolbarId, ENT_QUOTES, 'UTF-8') ?>">
            <div class="cm-toolbar cm-toolbar--unified <?= htmlspecialchars($alignClass, ENT_QUOTES, 'UTF-8') ?>">
                <!-- GAUCHE : Pagination -->
                <div class="cm-toolbar-left">
                    <label class="cm-toolbar__control">
                        <span>Afficher:</span>
                        <select id="<?= htmlspecialchars($limitId, ENT_QUOTES, 'UTF-8') ?>"
                            name="<?= htmlspecialchars($limitName, ENT_QUOTES, 'UTF-8') ?>"
                            class="cm-form-control cm-form-select is-sm cm-toolbar-field-xs">
                            <?php foreach ($limitOptions as $opt): ?>
                                <?php $optValue = (int) $opt; ?>
                                <option value="<?= $optValue ?>" <?= $optValue === $limit ? 'selected' : '' ?>><?= $optValue ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                </div>

                <!-- CENTRE : Recherche -->
                <div class="cm-toolbar-center">
                    <div class="cm-toolbar__search-wrap">
                        <i class="fas fa-search cm-toolbar__search-icon" aria-hidden="true"></i>
                        <input type="search" id="<?= htmlspecialchars($searchId, ENT_QUOTES, 'UTF-8') ?>"
                            name="<?= htmlspecialchars($searchName, ENT_QUOTES, 'UTF-8') ?>"
                            class="cm-form-control is-sm cm-toolbar-field-lg"
                            value="<?= htmlspecialchars($searchValue, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="<?= htmlspecialchars($searchPlaceholder, ENT_QUOTES, 'UTF-8') ?>"
                            data-cm-toolbar-action="search">
                    </div>
                </div>

                <!-- DROITE : Filtres + Actions -->
                <div class="cm-toolbar-right">
                    <?php if ($showFilters && !empty($customFilters)): ?>
                        <!-- Bouton Filtre unique avec dropdown -->
                        <div class="cm-dropdown cm-dropdown--toolbar"
                            id="<?= htmlspecialchars($filterDropdownId, ENT_QUOTES, 'UTF-8') ?>">
                            <button type="button" class="cm-btn is-light is-sm cm-dropdown__toggle"
                                id="<?= htmlspecialchars($filterToggleId, ENT_QUOTES, 'UTF-8') ?>" aria-haspopup="true"
                                aria-expanded="false">
                                <span>Filtres</span>
                                <?php if ($activeFilterCount > 0): ?>
                                    <span class="cm-badge cm-badge--filter"
                                        id="<?= htmlspecialchars($filterCountId, ENT_QUOTES, 'UTF-8') ?>"><?= $activeFilterCount ?></span>
                                <?php else: ?>
                                    <span class="cm-badge cm-badge--filter"
                                        id="<?= htmlspecialchars($filterCountId, ENT_QUOTES, 'UTF-8') ?>" style="display:none">0</span>
                                <?php endif; ?>
                            </button>
                            <div class="cm-dropdown__menu cm-dropdown__menu--right cm-dropdown__menu--filters" role="menu"
                                aria-labelledby="<?= htmlspecialchars($filterToggleId, ENT_QUOTES, 'UTF-8') ?>">
                                <div id="<?= htmlspecialchars($filterFormId, ENT_QUOTES, 'UTF-8') ?>" class="cm-filter-form">
                                    <?php foreach ($customFilters as $filter): ?>
                                        <?php
                                        $fType = $filter['type'] ?? 'select';
                                        $fName = $filter['name'] ?? '';
                                        $fLabel = $filter['label'] ?? $fName;
                                        $fValue = $_GET[$fName] ?? '';
                                        ?>
                                        <div class="cm-filter-section">
                                            <label class="cm-filter-section__label"
                                                for="<?= htmlspecialchars($idPrefix . '_filter_' . $fName, ENT_QUOTES, 'UTF-8') ?>">
                                                <?= htmlspecialchars($fLabel, ENT_QUOTES, 'UTF-8') ?>
                                            </label>
                                            <?php if ($fType === 'select'): ?>
                                                <select name="<?= htmlspecialchars($fName, ENT_QUOTES, 'UTF-8') ?>"
                                                    id="<?= htmlspecialchars($idPrefix . '_filter_' . $fName, ENT_QUOTES, 'UTF-8') ?>"
                                                    class="cm-form-control is-sm cm-filter-field"
                                                    data-filter-name="<?= htmlspecialchars($fName, ENT_QUOTES, 'UTF-8') ?>">
                                                    <?php
                                                    $fOptions = $filter['options'] ?? ['' => 'Tous'];
                                                    foreach ($fOptions as $optValue => $optLabel):
                                                        ?>
                                                        <option value="<?= htmlspecialchars((string) $optValue, ENT_QUOTES, 'UTF-8') ?>"
                                                            <?= (string) $fValue === (string) $optValue ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars((string) $optLabel, ENT_QUOTES, 'UTF-8') ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            <?php elseif ($fType === 'date'): ?>
                                                <input type="date" name="<?= htmlspecialchars($fName, ENT_QUOTES, 'UTF-8') ?>"
                                                    id="<?= htmlspecialchars($idPrefix . '_filter_' . $fName, ENT_QUOTES, 'UTF-8') ?>"
                                                    class="cm-form-control is-sm cm-filter-field"
                                                    value="<?= htmlspecialchars((string) $fValue, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-filter-name="<?= htmlspecialchars($fName, ENT_QUOTES, 'UTF-8') ?>">
                                            <?php elseif ($fType === 'date_range'): ?>
                                                <div class="cm-filter-date-range">
                                                    <input type="date"
                                                        name="<?= htmlspecialchars($fName . '_debut', ENT_QUOTES, 'UTF-8') ?>"
                                                        id="<?= htmlspecialchars($idPrefix . '_filter_' . $fName . '_debut', ENT_QUOTES, 'UTF-8') ?>"
                                                        class="cm-form-control is-sm cm-filter-field"
                                                        value="<?= htmlspecialchars((string) ($_GET[$fName . '_debut'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        placeholder="Du"
                                                        data-filter-name="<?= htmlspecialchars($fName, ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="date" name="<?= htmlspecialchars($fName . '_fin', ENT_QUOTES, 'UTF-8') ?>"
                                                        id="<?= htmlspecialchars($idPrefix . '_filter_' . $fName . '_fin', ENT_QUOTES, 'UTF-8') ?>"
                                                        class="cm-form-control is-sm cm-filter-field"
                                                        value="<?= htmlspecialchars((string) ($_GET[$fName . '_fin'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                                        placeholder="Au"
                                                        data-filter-name="<?= htmlspecialchars($fName, ENT_QUOTES, 'UTF-8') ?>">
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                    <div class="cm-filter-actions">
                                        <button type="button" class="cm-btn is-light is-xs cm-filter-reset"
                                            data-cm-toolbar-action="filter-reset">
                                            Réinitialiser
                                        </button>
                                        <button type="button" class="cm-btn is-info is-xs cm-filter-apply"
                                            data-cm-toolbar-action="filter-apply">
                                            Appliquer
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($showActions): ?>
                        <!-- Groupe Sélection -->
                        <div class="cm-toolbar__actions-group" role="group" aria-label="Actions de sélection">
                            <button type="button" id="<?= htmlspecialchars($selectAllId, ENT_QUOTES, 'UTF-8') ?>"
                                class="cm-btn is-light is-sm" title="Tout sélectionner" data-cm-toolbar-action="select-all">
                                <span>Tout sélectionner</span>
                            </button>
                            <button type="button" id="<?= htmlspecialchars($deselectAllId, ENT_QUOTES, 'UTF-8') ?>"
                                class="cm-btn is-light is-sm" title="Tout désélectionner" data-cm-toolbar-action="deselect-all">
                                <span>Tout désélectionner</span>
                            </button>
                        </div>

                        <?php if ($canDelete): ?>
                            <button type="button" id="<?= htmlspecialchars($deleteBtnId, ENT_QUOTES, 'UTF-8') ?>"
                                class="cm-btn is-danger is-sm" disabled data-cm-toolbar-action="delete">
                                <span>Supprimer</span>
                                <span class="cm-delete-count" data-selected-count="0"></span>
                            </button>
                        <?php endif; ?>

                        <?php if ($canView): ?>
                            <!-- <button type="button" id="<?= htmlspecialchars($exportBtnId, ENT_QUOTES, 'UTF-8') ?>"
                                class="cm-btn is-info is-sm" title="Exporter" data-cm-toolbar-action="export">
                                <span>Exporter</span>
                            </button> -->
                            <button type="button" id="<?= htmlspecialchars($printBtnId, ENT_QUOTES, 'UTF-8') ?>"
                                class="cm-btn is-info is-sm" title="Imprimer" data-cm-toolbar-action="print">
                                <span>Imprimer</span>
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Actions personnalisées -->
                    <?php if (!empty($config['custom_actions']) && is_array($config['custom_actions'])): ?>
                        <?php foreach ($config['custom_actions'] as $action): ?>
                            <?php
                            $aTag = strtolower((string) ($action['tag'] ?? 'button'));
                            $aLabel = (string) ($action['label'] ?? 'Action');
                            $aClass = (string) ($action['class'] ?? 'cm-btn is-light is-sm');
                            $aAttrs = is_array($action['attrs'] ?? null) ? $action['attrs'] : [];
                            $aHref = (string) ($action['href'] ?? '#');
                            $aType = (string) ($action['type'] ?? 'button');
                            $aId = !empty($action['id']) ? ' id="' . htmlspecialchars((string) $action['id'], ENT_QUOTES, 'UTF-8') . '"' : '';
                            ?>
                            <?php if ($aTag === 'a'): ?>
                                <a href="<?= htmlspecialchars($aHref, ENT_QUOTES, 'UTF-8') ?>" <?= $aId ?>
                                    class="<?= htmlspecialchars($aClass, ENT_QUOTES, 'UTF-8') ?>" <?= cm_form_attr_string($aAttrs) ?>>
                                    <span><?= htmlspecialchars($aLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                </a>
                            <?php else: ?>
                                <button type="<?= htmlspecialchars($aType, ENT_QUOTES, 'UTF-8') ?>" <?= $aId ?>
                                    class="<?= htmlspecialchars($aClass, ENT_QUOTES, 'UTF-8') ?>" <?= cm_form_attr_string($aAttrs) ?>>
                                    <span><?= htmlspecialchars($aLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                </button>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <script>
            (function () {
                const toolbarId = <?= json_encode($toolbarId) ?>;
                const searchInputId = <?= json_encode($searchId) ?>;
                const limitSelectId = <?= json_encode($limitId) ?>;
                const searchParamName = <?= json_encode($searchName) ?>;
                const limitParamName = <?= json_encode($limitName) ?>;
                const filterDropdownId = <?= json_encode($filterDropdownId) ?>;
                const filterToggleId = <?= json_encode($filterToggleId) ?>;
                const filterCountId = <?= json_encode($filterCountId) ?>;
                const filterFormId = <?= json_encode($filterFormId) ?>;
                const selectAllId = <?= json_encode($selectAllId) ?>;
                const deselectAllId = <?= json_encode($deselectAllId) ?>;
                const deleteBtnId = <?= json_encode($deleteBtnId) ?>;

                const onFilterApply = <?= json_encode($config['on_filter_apply'] ?? null) ?>;
                const onFilterReset = <?= json_encode($config['on_filter_reset'] ?? null) ?>;

                function initToolbar() {
                    const toolbar = document.getElementById(toolbarId);
                    if (!toolbar) return;
                    if (toolbar.getAttribute('data-cm-toolbar-bound') === '1') return;
                    toolbar.setAttribute('data-cm-toolbar-bound', '1');
                    const toolbarForm = toolbar.closest('form');
                    const searchInput = document.getElementById(searchInputId);
                    const limitSelect = document.getElementById(limitSelectId);
                    const deleteBtn = document.getElementById(deleteBtnId);

                    // Dropdown toggle
                    const filterDropdown = document.getElementById(filterDropdownId);
                    const filterToggle = document.getElementById(filterToggleId);
                    const filterMenu = filterDropdown ? filterDropdown.querySelector('.cm-dropdown__menu--filters') : null;
                    let filterMenuAnchor = null;

                    function ensureFilterMenuAnchor() {
                        if (!filterMenu || filterMenuAnchor) return;
                        if (!filterMenu.parentNode) return;
                        filterMenuAnchor = document.createElement('span');
                        filterMenuAnchor.className = 'cm-dropdown__menu-anchor';
                        filterMenuAnchor.style.display = 'none';
                        filterMenu.parentNode.insertBefore(filterMenuAnchor, filterMenu);
                    }

                    function positionFilterMenu() {
                        if (!filterMenu || !filterToggle) return;
                        if (!filterMenu.classList.contains('is-floating')) return;

                        const rect = filterToggle.getBoundingClientRect();
                        const viewportW = window.innerWidth || document.documentElement.clientWidth;
                        const viewportH = window.innerHeight || document.documentElement.clientHeight;

                        const width = Math.min(Math.max(300, Math.round(rect.width + 220)), viewportW - 20);
                        const left = Math.max(10, Math.min(rect.right - width, viewportW - width - 10));
                        const belowTop = rect.bottom + 8;
                        const availableBelow = Math.max(180, viewportH - belowTop - 10);
                        const availableAbove = Math.max(180, rect.top - 10);
                        const openAbove = availableBelow < 260 && availableAbove > availableBelow;
                        const top = openAbove ? Math.max(10, rect.top - Math.min(availableAbove, 520) - 8) : belowTop;
                        const maxHeight = Math.max(180, openAbove ? availableAbove - 8 : availableBelow);

                        filterMenu.style.setProperty('position', 'fixed', 'important');
                        filterMenu.style.setProperty('right', 'auto', 'important');
                        filterMenu.style.setProperty('bottom', 'auto', 'important');
                        filterMenu.style.setProperty('left', left + 'px', 'important');
                        filterMenu.style.setProperty('width', width + 'px', 'important');
                        filterMenu.style.setProperty('top', top + 'px', 'important');
                        filterMenu.style.setProperty('max-height', maxHeight + 'px', 'important');
                        filterMenu.style.setProperty('overflow-y', 'auto', 'important');
                    }

                    function openFilterMenu() {
                        if (!filterDropdown || !filterToggle || !filterMenu) return;
                        ensureFilterMenuAnchor();
                        if (filterMenu.parentNode !== document.body) {
                            document.body.appendChild(filterMenu);
                        }
                        filterMenu.classList.add('is-floating');
                        filterDropdown.classList.add('is-open');
                        filterToggle.setAttribute('aria-expanded', 'true');
                        positionFilterMenu();
                    }

                    function closeFilterMenu() {
                        if (!filterDropdown || !filterToggle || !filterMenu) return;
                        filterDropdown.classList.remove('is-open');
                        filterToggle.setAttribute('aria-expanded', 'false');

                        if (filterMenu.classList.contains('is-floating')) {
                            filterMenu.classList.remove('is-floating');
                            filterMenu.style.removeProperty('left');
                            filterMenu.style.removeProperty('top');
                            filterMenu.style.removeProperty('width');
                            filterMenu.style.removeProperty('max-height');
                            filterMenu.style.removeProperty('right');
                            filterMenu.style.removeProperty('bottom');
                            filterMenu.style.removeProperty('position');
                        }

                        if (filterMenuAnchor && filterMenuAnchor.parentNode && filterMenu.parentNode === document.body) {
                            filterMenuAnchor.parentNode.insertBefore(filterMenu, filterMenuAnchor.nextSibling);
                        }
                    }

                    if (filterToggle && filterDropdown) {
                        filterToggle.addEventListener('click', function (e) {
                            e.stopPropagation();
                            const isOpen = filterDropdown.classList.contains('is-open');
                            if (isOpen) {
                                closeFilterMenu();
                            } else {
                                openFilterMenu();
                            }
                        });

                        document.addEventListener('click', function (e) {
                            if (!document.body.contains(toolbar)) return;
                            const clickedInsideDropdown = filterDropdown.contains(e.target);
                            const clickedInsideFloatingMenu = filterMenu ? filterMenu.contains(e.target) : false;
                            if (!clickedInsideDropdown && !clickedInsideFloatingMenu) {
                                closeFilterMenu();
                            }
                        });

                        window.addEventListener('resize', function () {
                            if (!document.body.contains(toolbar)) return;
                            if (filterDropdown.classList.contains('is-open')) {
                                positionFilterMenu();
                            }
                        }, { passive: true });

                        window.addEventListener('scroll', function (e) {
                            if (!document.body.contains(toolbar)) return;
                            if (filterMenu && e && e.target && e.target !== document && filterMenu.contains(e.target)) return;
                            if (filterDropdown.classList.contains('is-open')) {
                                positionFilterMenu();
                            }
                        }, true);

                        // Keep wheel/trackpad scrolling inside the floating filter panel.
                        filterMenu.addEventListener('wheel', function (e) {
                            e.stopPropagation();
                        }, { passive: true });
                    }

                    function dispatchToolbarEvent(name, detail) {
                        const event = new CustomEvent(name, { detail: detail, cancelable: true });
                        document.dispatchEvent(event);
                        return !event.defaultPrevented;
                    }

                    function setOrDeleteParam(params, key, value) {
                        const normalized = (value || '').toString().trim();
                        if (normalized === '') {
                            params.delete(key);
                            return;
                        }
                        params.set(key, normalized);
                    }

                    function resetPageToFirst(url) {
                        ['p', 'page_num', 'current_page'].forEach(function (name) {
                            if (url.searchParams.has(name)) {
                                url.searchParams.set(name, '1');
                            }
                        });
                    }

                    function applyFiltersToUrl(url) {
                        if (searchInput && searchParamName) {
                            setOrDeleteParam(url.searchParams, searchParamName, searchInput.value);
                        }
                        if (limitSelect) {
                            const limitParamNames = [];
                            if (limitParamName) {
                                limitParamNames.push(limitParamName);
                            }
                            url.searchParams.forEach(function (_, key) {
                                if (/^limit(_|$)/i.test(key) && limitParamNames.indexOf(key) === -1) {
                                    limitParamNames.push(key);
                                }
                            });
                            if (limitParamNames.length === 0) {
                                limitParamNames.push('limit');
                            }
                            limitParamNames.forEach(function (paramName) {
                                setOrDeleteParam(url.searchParams, paramName, limitSelect.value);
                            });
                        }

                        const filterForm = document.getElementById(filterFormId);
                        if (!filterForm) return;
                        filterForm.querySelectorAll('.cm-filter-field').forEach(function (field) {
                            if (field.name) {
                                setOrDeleteParam(url.searchParams, field.name, field.value);
                            }
                        });
                    }

                    function submitToolbarState() {
                        if (toolbarForm) {
                            ['p', 'page_num', 'current_page'].forEach(function (name) {
                                const input = toolbarForm.querySelector('[name="' + name + '"]');
                                if (input) input.value = '1';
                            });
                            if (typeof toolbarForm.requestSubmit === 'function') {
                                toolbarForm.requestSubmit();
                            } else {
                                toolbarForm.submit();
                            }
                            return;
                        }

                        const url = new URL(window.location.href);
                        applyFiltersToUrl(url);
                        resetPageToFirst(url);
                        window.location.assign(url.toString());
                    }

                    function getDataTableInScope() {
                        const scope = toolbar.closest('.cm-prd3-screen, .cm-crud-wrapper, .cm-content-area') || document;
                        return scope.querySelector('.cm-table-wrapper table, table.cm-data-table, table');
                    }

                    function exportTableAsCsv(table) {
                        if (!table) return;
                        const rows = Array.from(table.querySelectorAll('tr')).filter(function (row) {
                            return row.style.display !== 'none';
                        });
                        const csv = rows.map(function (tr) {
                            return Array.from(tr.querySelectorAll('th,td')).map(function (cell) {
                                const text = (cell.textContent || '').replace(/\s+/g, ' ').trim();
                                return '"' + text.replace(/"/g, '""') + '"';
                            }).join(',');
                        }).join('\n');

                        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                        const link = document.createElement('a');
                        link.href = URL.createObjectURL(blob);
                        link.download = 'export.csv';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                        URL.revokeObjectURL(link.href);
                    }

                    function printTable(table) {
                        if (!table) return;
                        const printWindow = window.open('', '_blank');
                        if (!printWindow) return;

                        const clone = table.cloneNode(true);
                        clone.querySelectorAll('tr').forEach(function (row) {
                            if (row.style.display === 'none') {
                                row.remove();
                            }
                        });

                        printWindow.document.write('<html><head><title>Impression</title><style>body{font-family:Arial,sans-serif;padding:16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d1d5db;padding:8px;text-align:left}th{background:#f3f4f6}</style></head><body>');
                        printWindow.document.write(clone.outerHTML);
                        printWindow.document.write('</body></html>');
                        printWindow.document.close();
                        printWindow.focus();
                        printWindow.print();
                    }

                    function getSelectableCheckboxes() {
                        const scope = toolbar.closest('.cm-prd3-screen, .cm-crud-wrapper, .cm-content-area') || document;
                        return Array.from(scope.querySelectorAll('table tbody input[type="checkbox"]')).filter(function (cb) {
                            return !cb.disabled;
                        });
                    }

                    function updateDeleteState() {
                        if (!deleteBtn) return;
                        const selectedCount = getSelectableCheckboxes().filter(function (cb) { return cb.checked; }).length;
                        const countEl = deleteBtn.querySelector('.cm-delete-count');
                        deleteBtn.disabled = selectedCount === 0;
                        if (countEl) {
                            countEl.textContent = selectedCount > 0 ? ' (' + selectedCount + ')' : '';
                            countEl.setAttribute('data-selected-count', String(selectedCount));
                        }
                    }

                    function normalizeText(value) {
                        return (value || '')
                            .toString()
                            .toLowerCase()
                            .normalize('NFD')
                            .replace(/[\u0300-\u036f]/g, '')
                            .trim();
                    }

                    function getFilterableRows() {
                        const table = getDataTableInScope();
                        if (!table) return [];
                        const body = (table.tBodies && table.tBodies.length > 0) ? table.tBodies[0] : table.querySelector('tbody');
                        if (!body) return [];

                        return Array.from(body.querySelectorAll('tr')).filter(function (row) {
                            if (row.querySelector('th')) return false;
                            if (row.classList.contains('cm-hidden')) return false;
                            if (row.classList.contains('cm-cand-detail-row') || row.classList.contains('cm-rec-detail-row')) return false;
                            return row.querySelectorAll('td').length > 0;
                        });
                    }

                    function toComparableDate(value) {
                        const raw = (value || '').toString().trim();
                        if (raw === '') return '';

                        const isoMatch = raw.match(/(\d{4})-(\d{2})-(\d{2})/);
                        if (isoMatch) {
                            return isoMatch[1] + '-' + isoMatch[2] + '-' + isoMatch[3];
                        }

                        const frMatch = raw.match(/(\d{2})[\/\.-](\d{2})[\/\.-](\d{4})/);
                        if (frMatch) {
                            return frMatch[3] + '-' + frMatch[2] + '-' + frMatch[1];
                        }

                        return '';
                    }

                    function getColumnValueByFilterName(row, filterName) {
                        const table = row.closest('table');
                        if (!table) return '';

                        const headerRow = table.querySelector('thead tr:last-child');
                        if (!headerRow) return '';

                        const headers = Array.from(headerRow.querySelectorAll('th')).map(function (th) {
                            return normalizeText(th.textContent || '');
                        });
                        const cells = Array.from(row.querySelectorAll('td'));
                        if (headers.length === 0 || cells.length === 0) return '';

                        const normalizedName = normalizeText(
                            (filterName || '')
                                .replace(/_(debut|fin)$/i, '')
                                .replace(/_/g, ' ')
                        );
                        if (normalizedName === '') return '';

                        const tokens = normalizedName.split(/\s+/).filter(function (token) {
                            return token.length > 1 && ['de', 'du', 'la', 'le', 'des', 'd', 'a'].indexOf(token) === -1;
                        });

                        let bestIndex = -1;
                        let bestScore = 0;

                        headers.forEach(function (header, index) {
                            if (header === '') return;

                            let score = 0;
                            if (header.indexOf(normalizedName) !== -1) {
                                score += 2;
                            }
                            tokens.forEach(function (token) {
                                if (header.indexOf(token) !== -1) {
                                    score += 1;
                                }
                            });

                            if (score > bestScore) {
                                bestScore = score;
                                bestIndex = index;
                            }
                        });

                        if (bestIndex < 0 || bestScore === 0) return '';

                        let cellIndex = bestIndex;
                        if (cellIndex >= cells.length && cellIndex > 0 && (cellIndex - 1) < cells.length) {
                            cellIndex = cellIndex - 1;
                        }

                        const cell = cells[cellIndex];
                        if (!cell) return '';
                        return cell.getAttribute('data-value') || cell.textContent || '';
                    }

                    function getRowValue(row, key) {
                        const dashed = key.replace(/_/g, '-');
                        const candidates = [
                            row.getAttribute('data-' + key),
                            row.getAttribute('data-' + dashed),
                            row.querySelector('[data-' + key + ']') ? row.querySelector('[data-' + key + ']').getAttribute('data-' + key) : null,
                            row.querySelector('[data-' + dashed + ']') ? row.querySelector('[data-' + dashed + ']').getAttribute('data-' + dashed) : null,
                        ];
                        for (let i = 0; i < candidates.length; i++) {
                            if (candidates[i] !== null && candidates[i] !== '') {
                                return candidates[i];
                            }
                        }
                        const byColumn = getColumnValueByFilterName(row, key);
                        if (byColumn !== '') {
                            return byColumn;
                        }
                        return '';
                    }

                    function matchesFilterField(row, field) {
                        const name = (field.name || '').trim();
                        if (name === '') return true;
                        const value = (field.value || '').toString().trim();
                        if (value === '') return true;

                        const normalizedValue = normalizeText(value);
                        const fieldType = (field.type || '').toLowerCase();
                        const baseName = name.replace(/_(debut|fin)$/i, '');

                        if (/_debut$/i.test(name) || /_fin$/i.test(name)) {
                            const rowDateRaw = getRowValue(row, 'date') || getRowValue(row, baseName);
                            const rowDate = toComparableDate(rowDateRaw);
                            const filterDate = toComparableDate(value) || value;
                            if (rowDate === '' || filterDate === '') return false;
                            if (/_debut$/i.test(name)) return rowDate >= filterDate;
                            return rowDate <= filterDate;
                        }

                        let rowVal = getRowValue(row, name);
                        if (rowVal === '' && name.indexOf('statut') !== -1) {
                            rowVal = getRowValue(row, 'statut') || getRowValue(row, 'status');
                        }
                        if (rowVal === '' && name.indexOf('niveau') !== -1) {
                            rowVal = getRowValue(row, 'niveau_id') || getRowValue(row, 'niveau');
                        }
                        if (rowVal === '' && name.indexOf('mode') !== -1) {
                            rowVal = getRowValue(row, 'mode');
                        }
                        if (rowVal === '' && name.indexOf('date') !== -1) {
                            rowVal = getRowValue(row, 'date');
                        }
                        if (rowVal === '' && name.indexOf('type') !== -1) {
                            rowVal = getRowValue(row, 'type');
                        }
                        if (rowVal === '' && name.indexOf('utilisateur') !== -1) {
                            rowVal = getRowValue(row, 'utilisateur');
                        }
                        if (rowVal === '' && name.indexOf('action') !== -1) {
                            rowVal = getRowValue(row, 'action');
                        }

                        const normalizedRowVal = normalizeText(rowVal);
                        const normalizedRowText = normalizeText(row.getAttribute('data-search') || row.textContent || '');

                        if (fieldType === 'date') {
                            const rowDate = toComparableDate(getRowValue(row, 'date') || rowVal);
                            const filterDate = toComparableDate(value) || value;
                            return rowDate !== '' && filterDate !== '' && rowDate === filterDate;
                        }
                        if (fieldType === 'select-one' || field.tagName === 'SELECT') {
                            if (normalizedRowVal === '') {
                                const selectedOption = field.options && field.selectedIndex >= 0 ? field.options[field.selectedIndex] : null;
                                const selectedLabel = normalizeText(selectedOption ? (selectedOption.textContent || '') : '');
                                if (normalizedValue !== '' && normalizedRowText.indexOf(normalizedValue) !== -1) {
                                    return true;
                                }
                                if (selectedLabel !== '' && selectedLabel !== 'tous' && selectedLabel !== 'toutes' && normalizedRowText.indexOf(selectedLabel) !== -1) {
                                    return true;
                                }
                                return false;
                            }
                            return normalizedRowVal === normalizedValue;
                        }

                        if (normalizedRowVal === '') {
                            return normalizedRowText.indexOf(normalizedValue) !== -1;
                        }
                        return normalizedRowVal.indexOf(normalizedValue) !== -1;
                    }

                    function hideLinkedDetailRows(mainRow) {
                        const idRef = mainRow.getAttribute('data-row-id') || mainRow.getAttribute('data-id') || '';
                        if (idRef === '') return;
                        [
                            '#cmCandDetail_' + idRef,
                            '#cmRecDetail_' + idRef,
                            '#cmDetail_' + idRef
                        ].forEach(function (selector) {
                            const detailRow = document.querySelector(selector);
                            if (detailRow) {
                                detailRow.style.display = 'none';
                            }
                        });
                    }

                    function applyClientSideFiltering() {
                        const rows = getFilterableRows();
                        if (rows.length === 0) return false;

                        const filterFormEl = document.getElementById(filterFormId);
                        const term = normalizeText(searchInput ? searchInput.value : '');

                        rows.forEach(function (row) {
                            const rowSearch = normalizeText(row.getAttribute('data-search') || row.textContent || '');
                            const matchSearch = term === '' || rowSearch.indexOf(term) !== -1;

                            let matchFilters = true;
                            if (filterFormEl) {
                                const fields = filterFormEl.querySelectorAll('.cm-filter-field');
                                fields.forEach(function (field) {
                                    if (!matchFilters) return;
                                    if (!matchesFilterField(row, field)) {
                                        matchFilters = false;
                                    }
                                });
                            }

                            const visible = matchSearch && matchFilters;
                            row.style.display = visible ? '' : 'none';
                            if (!visible) {
                                hideLinkedDetailRows(row);
                            }
                        });

                        return true;
                    }

                    // Filter actions
                    const filterForm = document.getElementById(filterFormId);
                    if (filterForm) {
                        const applyBtn = filterForm.querySelector('[data-cm-toolbar-action="filter-apply"]');
                        const resetBtn = filterForm.querySelector('[data-cm-toolbar-action="filter-reset"]');

                        if (applyBtn) {
                            applyBtn.addEventListener('click', function () {
                                if (onFilterApply && typeof window[onFilterApply] === 'function') {
                                    window[onFilterApply](filterForm);
                                } else {
                                    const shouldRunDefault = dispatchToolbarEvent('cm:toolbar:filter:apply', { form: filterForm, toolbar: toolbar });
                                    closeFilterMenu();
                                    if (shouldRunDefault) {
                                        const applied = applyClientSideFiltering();
                                        if (!applied) {
                                            submitToolbarState();
                                        }
                                    }
                                }
                                updateFilterCount();
                            });
                        }

                        if (resetBtn) {
                            resetBtn.addEventListener('click', function () {
                                filterForm.querySelectorAll('.cm-filter-field').forEach(function (field) {
                                    field.value = '';
                                });
                                if (onFilterReset && typeof window[onFilterReset] === 'function') {
                                    window[onFilterReset](filterForm);
                                } else {
                                    const shouldRunDefault = dispatchToolbarEvent('cm:toolbar:filter:reset', { form: filterForm, toolbar: toolbar });
                                    closeFilterMenu();
                                    if (shouldRunDefault) {
                                        const applied = applyClientSideFiltering();
                                        if (!applied) {
                                            submitToolbarState();
                                        }
                                    }
                                }
                                updateFilterCount();
                            });
                        }
                    }

                    // Update filter count badge
                    function updateFilterCount() {
                        const badge = document.getElementById(filterCountId);
                        if (!badge || !filterForm) return;

                        let count = 0;
                        filterForm.querySelectorAll('.cm-filter-field').forEach(function (field) {
                            if (field.value !== '') count++;
                        });

                        badge.textContent = count;
                        badge.style.display = count > 0 ? 'inline-block' : 'none';
                    }

                    // Selection actions (delegate events)
                    toolbar.addEventListener('click', function (e) {
                        const btn = e.target.closest('[data-cm-toolbar-action]');
                        if (!btn) return;

                        const action = btn.getAttribute('data-cm-toolbar-action');

                        switch (action) {
                            case 'select-all':
                                if (dispatchToolbarEvent('cm:toolbar:select:all', { toolbar: toolbar })) {
                                    getSelectableCheckboxes().forEach(function (cb) { cb.checked = true; });
                                    updateDeleteState();
                                }
                                break;
                            case 'deselect-all':
                                if (dispatchToolbarEvent('cm:toolbar:select:none', { toolbar: toolbar })) {
                                    getSelectableCheckboxes().forEach(function (cb) { cb.checked = false; });
                                    updateDeleteState();
                                }
                                break;
                            case 'delete':
                                dispatchToolbarEvent('cm:toolbar:delete', { toolbar: toolbar, button: btn });
                                break;
                            case 'export':
                                if (dispatchToolbarEvent('cm:toolbar:export', { toolbar: toolbar, button: btn })) {
                                    exportTableAsCsv(getDataTableInScope());
                                }
                                break;
                            case 'print':
                                if (dispatchToolbarEvent('cm:toolbar:print', { toolbar: toolbar, button: btn })) {
                                    printTable(getDataTableInScope());
                                }
                                break;
                            case 'limit-change':
                                if (btn && btn.tagName === 'SELECT') {
                                    const limitValue = btn.value;
                                    if (dispatchToolbarEvent('cm:toolbar:limit:change', { toolbar: toolbar, limit: limitValue })) {
                                        submitToolbarState();
                                    }
                                }
                                break;
                        }
                    });

                    if (limitSelect) {
                        limitSelect.addEventListener('change', function () {
                            const limitValue = limitSelect.value;
                            if (dispatchToolbarEvent('cm:toolbar:limit:change', { toolbar: toolbar, limit: limitValue })) {
                                submitToolbarState();
                            }
                        });
                    }

                    // Search input fallback
                    if (searchInput) {
                        let searchTimeout;
                        searchInput.addEventListener('input', function () {
                            clearTimeout(searchTimeout);
                            searchTimeout = setTimeout(function () {
                                if (dispatchToolbarEvent('cm:toolbar:search', { toolbar: toolbar, value: searchInput.value })) {
                                    const applied = applyClientSideFiltering();
                                    if (!applied) {
                                        submitToolbarState();
                                    }
                                }
                            }, 450);
                        });
                    }

                    document.addEventListener('change', function (e) {
                        if (!document.body.contains(toolbar)) return;
                        if (e.target && e.target.matches('table tbody input[type="checkbox"]')) {
                            updateDeleteState();
                        }
                    });

                    updateDeleteState();
                    updateFilterCount();
                    applyClientSideFiltering();
                }

                if (document.readyState === 'loading') {
                    document.addEventListener('DOMContentLoaded', initToolbar, { once: true });
                } else {
                    initToolbar();
                }

                document.addEventListener('cm:ajax:navigation:done', function () {
                    initToolbar();
                });
            })();
        </script>
        <?php
    }
}

/**
 * Render a data table with selection checkboxes (to be used with cm_toolbar)
 *
 * @param array<string, mixed> $config
 */
if (!function_exists('cm_data_table_selectable')) {
    function cm_data_table_selectable(array $config): void
    {
        $id = (string) ($config['id'] ?? 'cmDataTable_' . substr(md5(uniqid()), 0, 8));
        $columns = is_array($config['columns'] ?? null) ? $config['columns'] : [];
        $rows = is_array($config['rows'] ?? null) ? $config['rows'] : [];
        $rowKey = (string) ($config['row_key'] ?? 'id');
        $selectable = (bool) ($config['selectable'] ?? true);
        $actions = is_array($config['actions'] ?? null) ? $config['actions'] : [];
        $emptyTitle = (string) ($config['empty_title'] ?? 'Aucune donnée');
        $emptyMessage = (string) ($config['empty_message'] ?? 'Aucun enregistrement trouvé.');
        $toolbarId = (string) ($config['toolbar_id'] ?? '');

        ?>
        <div class="cm-table-wrapper" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>_wrapper">
            <table class="cm-data-table" id="<?= htmlspecialchars($id, ENT_QUOTES, 'UTF-8') ?>">
                <thead>
                    <tr>
                        <?php if ($selectable): ?>
                            <th class="cm-data-table__th cm-data-table__th--check">
                                <input type="checkbox" class="cm-table-check-all" aria-label="Tout sélectionner">
                            </th>
                        <?php endif; ?>
                        <?php foreach ($columns as $col): ?>
                            <th class="cm-data-table__th <?= htmlspecialchars($col['class'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                <?= !empty($col['style']) ? 'style="' . htmlspecialchars($col['style'], ENT_QUOTES, 'UTF-8') . '"' : '' ?>>
                                <?= htmlspecialchars($col['label'] ?? '', ENT_QUOTES, 'UTF-8') ?>
                            </th>
                        <?php endforeach; ?>
                        <?php if (!empty($actions)): ?>
                            <th class="cm-data-table__th cm-data-table__th--actions">Actions</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                        <tr>
                            <td colspan="<?= count($columns) + ($selectable ? 1 : 0) + (!empty($actions) ? 1 : 0) ?>"
                                class="cm-data-table__empty">
                                <div class="cm-empty-state">
                                    <i class="fas fa-inbox cm-empty-state__icon"></i>
                                    <h4 class="cm-empty-state__title"><?= htmlspecialchars($emptyTitle, ENT_QUOTES, 'UTF-8') ?></h4>
                                    <p class="cm-empty-state__message"><?= htmlspecialchars($emptyMessage, ENT_QUOTES, 'UTF-8') ?>
                                    </p>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $row): ?>
                            <?php $rowId = is_array($row) ? ($row[$rowKey] ?? '') : ($row->{$rowKey} ?? ''); ?>
                            <tr class="cm-data-table__tr" data-row-id="<?= htmlspecialchars((string) $rowId, ENT_QUOTES, 'UTF-8') ?>">
                                <?php if ($selectable): ?>
                                    <td class="cm-data-table__td cm-data-table__td--check">
                                        <input type="checkbox" class="cm-table-check-row"
                                            value="<?= htmlspecialchars((string) $rowId, ENT_QUOTES, 'UTF-8') ?>" aria-label="Sélectionner">
                                    </td>
                                <?php endif; ?>
                                <?php foreach ($columns as $col): ?>
                                    <?php $cellKey = $col['key'] ?? ''; ?>
                                    <?php $cellValue = is_array($row) ? ($row[$cellKey] ?? '') : ($row->{$cellKey} ?? ''); ?>
                                    <td class="cm-data-table__td">
                                        <?= $cellValue ?>
                                    </td>
                                <?php endforeach; ?>
                                <?php if (!empty($actions)): ?>
                                    <td class="cm-data-table__td cm-data-table__td--actions">
                                        <div class="cm-data-table__actions">
                                            <?php foreach ($actions as $action): ?>
                                                <?php
                                                $aClass = (string) ($action['class'] ?? 'cm-btn-action');
                                                $aIcon = (string) ($action['icon'] ?? '');
                                                $aLabel = (string) ($action['label'] ?? '');
                                                $aConfirm = (string) ($action['confirm'] ?? '');
                                                $aAttrs = '';
                                                if ($aConfirm !== '') {
                                                    $aAttrs .= ' data-confirm="' . htmlspecialchars($aConfirm, ENT_QUOTES, 'UTF-8') . '"';
                                                }
                                                ?>
                                                <button type="button" class="<?= htmlspecialchars($aClass, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-row-id="<?= htmlspecialchars((string) $rowId, ENT_QUOTES, 'UTF-8') ?>"
                                                    data-action="<?= htmlspecialchars(strtolower($aLabel), ENT_QUOTES, 'UTF-8') ?>"
                                                    title="<?= htmlspecialchars($aLabel, ENT_QUOTES, 'UTF-8') ?>" <?= $aAttrs ?>>
                                                    <?php if ($aIcon !== ''): ?>
                                                        <i class="fas <?= htmlspecialchars($aIcon, ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                                    <?php endif; ?>
                                                    <span class="cm-sr-only"><?= htmlspecialchars($aLabel, ENT_QUOTES, 'UTF-8') ?></span>
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <?php if ($toolbarId !== ''): ?>
            <script>
                (function () {
                    const tableId = <?= json_encode($id) ?>;
                    const toolbarId = <?= json_encode($toolbarId) ?>;

                    function initSelectableTable() {
                        const table = document.getElementById(tableId);
                        const toolbar = document.getElementById(toolbarId);
                        if (!table || !toolbar) return;
                        if (table.getAttribute('data-cm-selectable-bound') === '1') return;
                        table.setAttribute('data-cm-selectable-bound', '1');

                        const checkAll = table.querySelector('.cm-table-check-all');
                        const checkRows = table.querySelectorAll('.cm-table-check-row');
                        const deleteBtn = toolbar.querySelector('[data-cm-toolbar-action="delete"]');
                        const deleteCount = deleteBtn ? deleteBtn.querySelector('.cm-delete-count') : null;

                        function isSameToolbarEvent(evt) {
                            if (!evt || !evt.detail || !evt.detail.toolbar) return false;
                            return evt.detail.toolbar === toolbar;
                        }

                        function isStillMounted() {
                            return document.body.contains(table) && document.body.contains(toolbar);
                        }

                        function updateSelection() {
                            const checked = table.querySelectorAll('.cm-table-check-row:checked');
                            const count = checked.length;

                            if (checkAll) {
                                checkAll.checked = count > 0 && count === checkRows.length;
                                checkAll.indeterminate = count > 0 && count < checkRows.length;
                            }

                            if (deleteBtn) {
                                deleteBtn.disabled = count === 0;
                            }

                            if (deleteCount) {
                                deleteCount.textContent = count > 0 ? ' (' + count + ')' : '';
                                deleteCount.setAttribute('data-selected-count', count);
                            }

                            // Dispatch selection change event
                            document.dispatchEvent(new CustomEvent('cm:table:selection:change', {
                                detail: { table: table, count: count, selected: Array.from(checked).map(cb => cb.value) }
                            }));
                        }

                        // Check all toggle
                        if (checkAll) {
                            checkAll.addEventListener('change', function () {
                                checkRows.forEach(function (cb) {
                                    cb.checked = checkAll.checked;
                                });
                                updateSelection();
                            });
                        }

                        // Individual row toggle
                        checkRows.forEach(function (cb) {
                            cb.addEventListener('change', updateSelection);
                        });

                        // Listen to toolbar events
                        document.addEventListener('cm:toolbar:select:all', function (evt) {
                            if (!isSameToolbarEvent(evt) || !isStillMounted()) return;
                            checkRows.forEach(function (cb) { cb.checked = true; });
                            updateSelection();
                        });

                        document.addEventListener('cm:toolbar:select:none', function (evt) {
                            if (!isSameToolbarEvent(evt) || !isStillMounted()) return;
                            checkRows.forEach(function (cb) { cb.checked = false; });
                            updateSelection();
                        });

                        document.addEventListener('cm:toolbar:delete', function (evt) {
                            if (!isSameToolbarEvent(evt) || !isStillMounted()) return;
                            const checked = table.querySelectorAll('.cm-table-check-row:checked');
                            if (checked.length === 0) return;

                            const ids = Array.from(checked).map(cb => cb.value);
                            document.dispatchEvent(new CustomEvent('cm:table:delete:selected', {
                                detail: { table: table, ids: ids }
                            }));
                        });

                        // Row action buttons
                        table.addEventListener('click', function (e) {
                            const btn = e.target.closest('.cm-btn-action');
                            if (!btn) return;

                            const rowId = btn.getAttribute('data-row-id');
                            const action = btn.getAttribute('data-action');
                            const confirmMsg = btn.getAttribute('data-confirm');

                            if (confirmMsg && !confirm(confirmMsg)) return;

                            document.dispatchEvent(new CustomEvent('cm:table:row:action', {
                                detail: { table: table, rowId: rowId, action: action, button: btn }
                            }));
                        });

                        // Initial state
                        updateSelection();
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initSelectableTable, { once: true });
                    } else {
                        initSelectableTable();
                    }

                    document.addEventListener('cm:ajax:navigation:done', function () {
                        initSelectableTable();
                    });
                })();
            </script>
        <?php endif; ?>
    <?php
    }
}

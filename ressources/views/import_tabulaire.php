<?php
$config = is_array($GLOBALS['tabularImportConfig'] ?? null) ? $GLOBALS['tabularImportConfig'] : [];
$fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
$rows = is_array($GLOBALS['tabularImportRows'] ?? null) ? $GLOBALS['tabularImportRows'] : [];
$filename = (string) ($GLOBALS['tabularImportFilename'] ?? '');
$summary = $GLOBALS['tabularImportSummary'] ?? null;
$expectedHeaders = is_array($config['expected_headers'] ?? null) ? $config['expected_headers'] : [];
$title = (string) ($config['title'] ?? 'Import tabulaire');
$subtitle = (string) ($config['subtitle'] ?? 'Chargez un fichier puis vérifiez les lignes avant validation.');
$backUrl = (string) ($config['back_url'] ?? '?');
$uploadUrl = (string) ($config['upload_url'] ?? '?');
$entity = (string) ($config['entity'] ?? 'import');
$rowCount = count($rows);
?>
<section class="cm-prd3-screen cm-prd3-crud-screen">
    <?php
    cm_component('layout/page-header', [
        'title' => $title,
        'subtitle' => $subtitle,
        'annee' => (string) ($_SESSION['global_annee_selected'] ?? $_SESSION['global_annee_active_label'] ?? ''),
        'icon' => 'fa-file-import',
    ]);
    ?>

    <?php if (!empty($GLOBALS['messageSuccess'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => (string) $GLOBALS['messageSuccess']]); ?>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['messageErreur'])): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => (string) $GLOBALS['messageErreur']]); ?>
    <?php endif; ?>

    <style>
        .cm-import-screen {
            display: grid;
            gap: 1.25rem;
            padding: 0.25rem 0.5rem;
        }

        .cm-import-card {
            background: transparent;
            border: none;
            border-radius: 0;
            padding: 0;
            box-shadow: none;
        }

        .cm-import-card__title {
            margin: 0 0 0.35rem;
            font-size: 1.05rem;
            font-weight: 700;
            color: #18405f;
            letter-spacing: -0.01em;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .cm-import-card__text {
            margin: 0 0 0.75rem;
            color: #4b6478;
            font-size: 0.88rem;
            line-height: 1.4;
        }

        .cm-import-grid {
            display: grid;
            gap: 1.25rem;
            grid-template-columns: minmax(0, 1.4fr) minmax(18rem, 0.8fr);
        }

        .cm-import-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.4rem;
        }

        .cm-import-badge {
            border-radius: 6px;
            padding: 0.3rem 0.6rem;
            background: rgba(47, 103, 140, 0.08);
            border: 1px solid rgba(47, 103, 140, 0.12);
            color: #1d587f;
            font-size: 0.8rem;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .cm-import-badge:hover {
            background: rgba(47, 103, 140, 0.12);
            transform: translateY(-1px);
        }

        .cm-import-upload {
            display: grid;
            gap: 0.75rem;
        }

        .cm-import-upload input[type="file"] {
            border: 1px dashed rgba(42, 95, 130, 0.25);
            background: rgba(255, 255, 255, 0.4);
            border-radius: 10px;
            padding: 0.6rem 0.8rem;
            cursor: pointer;
            transition: all 0.2s ease;
            color: #4b6478;
            font-size: 0.88rem;
        }

        .cm-import-upload input[type="file"]:hover {
            border-color: rgba(42, 95, 130, 0.5);
            background: rgba(255, 255, 255, 0.6);
        }

        .cm-import-upload__actions,
        .cm-import-preview__actions {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            align-items: center;
        }

        .cm-import-preview__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.6rem;
            align-items: center;
            margin-bottom: 0.85rem;
            padding-bottom: 0.6rem;
            border-bottom: 1px solid rgba(42, 95, 130, 0.08);
        }

        .cm-import-meta-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
            background: rgba(27, 77, 112, 0.06);
            border: 1px solid rgba(27, 77, 112, 0.1);
            color: #1b4d70;
            font-size: 0.78rem;
            font-weight: 600;
        }

        .cm-import-table {
            width: 100%;
            min-width: 980px;
            border-collapse: collapse;
            margin-top: 0.75rem;
        }

        .cm-import-table th {
            background: transparent;
            color: #18405f;
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.03em;
            padding: 0.45rem 0.35rem;
            border-bottom: 2px solid rgba(42, 95, 130, 0.18);
            text-align: left;
        }

        .cm-import-table td {
            border: none;
            border-bottom: 1px solid rgba(42, 95, 130, 0.08);
            padding: 0.35rem 0.35rem;
            vertical-align: middle;
            background: transparent;
        }

        .cm-import-table tbody tr {
            transition: background-color 0.15s ease;
        }

        .cm-import-table tbody tr:hover {
            background-color: rgba(42, 95, 130, 0.03);
        }

        .cm-import-table td .cm-form-control {
            width: 100%;
            min-width: 6.5rem;
            background: rgba(255, 255, 255, 0.45);
            border: 1px solid rgba(42, 95, 130, 0.12);
            border-radius: 6px;
            padding: 0.25rem 0.45rem;
            font-size: 0.82rem;
            color: #18405f;
            transition: all 0.2s ease;
            height: auto;
        }

        .cm-import-table td .cm-form-control:focus {
            background: #ffffff;
            border-color: #2f678c;
            box-shadow: 0 0 0 3px rgba(47, 103, 140, 0.12);
            outline: none;
        }

        .cm-import-table__remove {
            min-width: 2.2rem;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            border-radius: 6px;
            transition: all 0.15s ease;
        }

        .cm-import-table__remove:hover {
            transform: scale(1.03);
        }

        .cm-import-errors {
            margin: 0.5rem 0 0;
            padding: 0.6rem 0.8rem 0.6rem 2rem;
            border-radius: 6px;
            background: rgba(166, 42, 60, 0.06);
            border-left: 3px solid #a62a3c;
            color: #a62a3c;
            font-size: 0.85rem;
            line-height: 1.5;
        }

        .cm-import-empty {
            padding: 1.5rem 0;
            color: #547185;
            text-align: center;
            font-style: italic;
            font-size: 0.9rem;
        }

        .cm-import-screen .cm-btn {
            padding: 0.35rem 0.75rem;
            font-size: 0.82rem;
            border-radius: 8px;
        }

        @media (max-width: 980px) {
            .cm-import-grid {
                grid-template-columns: 1fr;
                gap: 1.25rem;
            }
        }
    </style>

    <div class="cm-import-screen">
        <div class="cm-import-grid">
            <div class="cm-import-card">
                <h3 class="cm-import-card__title">Fichier source</h3>
                <p class="cm-import-card__text">Formats acceptés: `csv`, `xls`, `xlsx`. Le bouton ci-dessous charge le fichier et ouvre une prévisualisation éditable avant import.</p>
                <form class="cm-import-upload" method="POST" enctype="multipart/form-data"
                    action="<?= htmlspecialchars($uploadUrl, ENT_QUOTES, 'UTF-8') ?>">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="file" name="import_file" accept=".csv,.xls,.xlsx" class="cm-form-control" required>
                    <div class="cm-import-upload__actions">
                        <button type="submit" name="submit_import_upload" class="cm-btn is-primary is-sm">
                            <i class="fas fa-upload" aria-hidden="true"></i>
                            <span>Charger le fichier</span>
                        </button>
                        <a href="<?= htmlspecialchars($backUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-light is-sm">
                            <span>Retour</span>
                        </a>
                    </div>
                </form>
            </div>

            <div class="cm-import-card">
                <h3 class="cm-import-card__title">En-têtes attendus</h3>
                <p class="cm-import-card__text">Le système reconnaît les colonnes ci-dessous dans cet ordre de référence.</p>
                <div class="cm-import-badges">
                    <?php foreach ($expectedHeaders as $header): ?>
                        <span class="cm-import-badge"><?= htmlspecialchars((string) $header, ENT_QUOTES, 'UTF-8') ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (is_array($summary) && !empty($summary['errors'])): ?>
            <div class="cm-import-card">
                <h3 class="cm-import-card__title">Résultat du dernier import</h3>
                <p class="cm-import-card__text">
                    Succès: <?= (int) ($summary['success_count'] ?? 0) ?> ligne(s) |
                    Erreurs: <?= (int) ($summary['error_count'] ?? 0) ?> ligne(s)
                </p>
                <ul class="cm-import-errors">
                    <?php foreach ((array) ($summary['errors'] ?? []) as $error): ?>
                        <li>
                            Ligne <?= htmlspecialchars((string) ($error['line'] ?? '?'), ENT_QUOTES, 'UTF-8') ?>:
                            <?= htmlspecialchars((string) ($error['message'] ?? 'Erreur inconnue'), ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="cm-import-card">
            <div class="cm-import-preview__meta">
                <span class="cm-import-meta-pill">
                    <i class="fas fa-database" aria-hidden="true"></i>
                    <span>Type: <?= htmlspecialchars($entity, ENT_QUOTES, 'UTF-8') ?></span>
                </span>
                <span class="cm-import-meta-pill">
                    <i class="fas fa-list-ol" aria-hidden="true"></i>
                    <span id="cmImportRowCount"><?= (int) $rowCount ?></span> ligne(s)
                </span>
                <?php if ($filename !== ''): ?>
                    <span class="cm-import-meta-pill">
                        <i class="fas fa-file-alt" aria-hidden="true"></i>
                        <span><?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?></span>
                    </span>
                <?php endif; ?>
            </div>

            <form id="cmImportCommitForm" method="POST" action="<?= htmlspecialchars($uploadUrl, ENT_QUOTES, 'UTF-8') ?>">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="import_filename" value="<?= htmlspecialchars($filename, ENT_QUOTES, 'UTF-8') ?>">
                <textarea name="import_payload" id="cmImportPayload" hidden></textarea>

                <div class="cm-import-preview__actions">
                    <button type="button" id="cmImportAddRow" class="cm-btn is-secondary is-sm">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span>Ajouter une ligne</span>
                    </button>
                    <button type="button" id="cmImportClearRows" class="cm-btn is-light is-sm">
                        <span>Vider la prévisualisation</span>
                    </button>
                    <button type="submit" name="submit_import_commit" class="cm-btn is-primary is-sm">
                        <i class="fas fa-file-import" aria-hidden="true"></i>
                        <span>Importer les lignes</span>
                    </button>
                </div>

                <div class="cm-table-wrapper" style="margin-top: 0.9rem;">
                    <table class="cm-import-table" id="cmImportTable">
                        <thead>
                            <tr>
                                <?php foreach ($fields as $field): ?>
                                    <th><?= htmlspecialchars((string) ($field['label'] ?? $field['name'] ?? ''), ENT_QUOTES, 'UTF-8') ?></th>
                                <?php endforeach; ?>
                                <th style="width: 5rem;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="cmImportTableBody">
                        </tbody>
                    </table>
                    <div id="cmImportEmptyState" class="cm-import-empty"<?= $rowCount > 0 ? ' style="display:none;"' : '' ?>>
                        Aucune ligne chargée pour l'instant. Téléversez un fichier ou ajoutez une ligne manuellement.
                    </div>
                </div>
            </form>
        </div>
    </div>
</section>

<script>
    (function () {
        const fieldDefinitions = <?= json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
        const tableBody = document.getElementById('cmImportTableBody');
        const payloadInput = document.getElementById('cmImportPayload');
        const addRowButton = document.getElementById('cmImportAddRow');
        const clearRowsButton = document.getElementById('cmImportClearRows');
        const commitForm = document.getElementById('cmImportCommitForm');
        const rowCountLabel = document.getElementById('cmImportRowCount');
        const emptyState = document.getElementById('cmImportEmptyState');
        let rows = <?= json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

        const defaultRow = function () {
            const row = {};
            fieldDefinitions.forEach(function (field) {
                const name = String(field.name || '');
                if (!name) {
                    return;
                }
                row[name] = '';
            });
            return row;
        };

        const ensureSelectOption = function (options, value) {
            const normalized = String(value || '');
            if (!normalized) {
                return options;
            }
            if (Object.prototype.hasOwnProperty.call(options, normalized)) {
                return options;
            }
            const extended = Object.assign({}, options);
            extended[normalized] = normalized;
            return extended;
        };

        const buildFieldControl = function (field, rowIndex, value) {
            const name = String(field.name || '');
            const type = String(field.type || 'text');
            const required = !!field.required;
            const inputId = 'cmImport_' + rowIndex + '_' + name;

            if (type === 'select') {
                const select = document.createElement('select');
                select.className = 'cm-form-control';
                select.id = inputId;
                select.dataset.fieldName = name;
                select.dataset.rowIndex = String(rowIndex);
                if (required) {
                    select.required = true;
                }

                const rawOptions = (field.options && typeof field.options === 'object') ? field.options : {};
                const options = ensureSelectOption(rawOptions, value);
                Object.keys(options).forEach(function (optionValue) {
                    const option = document.createElement('option');
                    option.value = optionValue;
                    option.textContent = String(options[optionValue]);
                    if (String(value || '') === optionValue) {
                        option.selected = true;
                    }
                    select.appendChild(option);
                });

                if (select.options.length === 0) {
                    const emptyOption = document.createElement('option');
                    emptyOption.value = '';
                    emptyOption.textContent = '-';
                    select.appendChild(emptyOption);
                }

                return select;
            }

            const input = document.createElement('input');
            input.className = 'cm-form-control';
            input.id = inputId;
            input.dataset.fieldName = name;
            input.dataset.rowIndex = String(rowIndex);
            input.type = type === 'date' ? 'date' : (type === 'email' ? 'email' : 'text');
            input.value = String(value || '');
            if (required) {
                input.required = true;
            }
            return input;
        };

        const updateMeta = function () {
            rowCountLabel.textContent = String(rows.length);
            emptyState.style.display = rows.length === 0 ? '' : 'none';
        };

        const renderTable = function () {
            tableBody.innerHTML = '';
            rows.forEach(function (row, rowIndex) {
                const tr = document.createElement('tr');

                fieldDefinitions.forEach(function (field) {
                    const td = document.createElement('td');
                    const name = String(field.name || '');
                    const control = buildFieldControl(field, rowIndex, row[name] || '');
                    td.appendChild(control);
                    tr.appendChild(td);
                });

                const actionTd = document.createElement('td');
                const removeButton = document.createElement('button');
                removeButton.type = 'button';
                removeButton.className = 'cm-btn is-danger is-xs cm-import-table__remove';
                removeButton.textContent = 'Suppr.';
                removeButton.dataset.removeRow = String(rowIndex);
                actionTd.appendChild(removeButton);
                tr.appendChild(actionTd);

                tableBody.appendChild(tr);
            });

            updateMeta();
        };

        tableBody.addEventListener('input', function (event) {
            const target = event.target;
            if (!target || !target.dataset) {
                return;
            }
            const rowIndex = Number(target.dataset.rowIndex || '-1');
            const fieldName = String(target.dataset.fieldName || '');
            if (!Number.isInteger(rowIndex) || rowIndex < 0 || !fieldName || !rows[rowIndex]) {
                return;
            }
            rows[rowIndex][fieldName] = target.value;
        });

        tableBody.addEventListener('change', function (event) {
            const target = event.target;
            if (!target || !target.dataset) {
                return;
            }
            const rowIndex = Number(target.dataset.rowIndex || '-1');
            const fieldName = String(target.dataset.fieldName || '');
            if (!Number.isInteger(rowIndex) || rowIndex < 0 || !fieldName || !rows[rowIndex]) {
                return;
            }
            rows[rowIndex][fieldName] = target.value;
        });

        tableBody.addEventListener('click', function (event) {
            const button = event.target.closest('[data-remove-row]');
            if (!button) {
                return;
            }
            const rowIndex = Number(button.dataset.removeRow || '-1');
            if (!Number.isInteger(rowIndex) || rowIndex < 0) {
                return;
            }
            rows.splice(rowIndex, 1);
            renderTable();
        });

        addRowButton.addEventListener('click', function () {
            rows.push(defaultRow());
            renderTable();
        });

        clearRowsButton.addEventListener('click', function () {
            rows = [];
            renderTable();
        });

        commitForm.addEventListener('submit', function () {
            payloadInput.value = JSON.stringify(rows);
        });

        renderTable();
    })();
</script>

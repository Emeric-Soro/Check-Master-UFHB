<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_generaux');
$action = 'gestion_attribution';

$listeGroupes = is_array($GLOBALS['listeGroupes'] ?? null) ? $GLOBALS['listeGroupes'] : [];
$listeTypesAll = is_array($GLOBALS['listeTypesAll'] ?? null) ? $GLOBALS['listeTypesAll'] : [];
$listeFonctionnalites = is_array($GLOBALS['listeFonctionnalites'] ?? null) ? $GLOBALS['listeFonctionnalites'] : [];
$selectedTypeId = (string) ($GLOBALS['selectedTypeId'] ?? ($_GET['type'] ?? 'all'));
$selectedGroupe = $GLOBALS['selectedGroupe'] ?? null;
$permissionsGroupe = is_array($GLOBALS['permissionsGroupe'] ?? null) ? $GLOBALS['permissionsGroupe'] : [];
$messageSuccess = (string) ($GLOBALS['messageSuccess'] ?? '');
$messageErreur = (string) ($GLOBALS['messageErreur'] ?? '');

$permissionsMap = [];
foreach ($permissionsGroupe as $permission) {
    $idFonc = (int) ($permission->id_fonctionnalite ?? 0);
    if ($idFonc <= 0) {
        continue;
    }
    $permissionsMap[$idFonc] = [
        'voir' => (int) ($permission->peut_voir ?? 0) === 1,
        'creer' => (int) ($permission->peut_creer ?? 0) === 1,
        'modifier' => (int) ($permission->peut_modifier ?? 0) === 1,
        'supprimer' => (int) ($permission->peut_supprimer ?? 0) === 1,
    ];
}

$fonctionnalitesByCat = [];
foreach ($listeFonctionnalites as $fonctionnalite) {
    $catCode = (string) ($fonctionnalite->code_categorie ?? 'AUTRES');
    $catLabel = (string) ($fonctionnalite->lib_categorie ?? 'Autres');
    if (!isset($fonctionnalitesByCat[$catCode])) {
        $fonctionnalitesByCat[$catCode] = [
            'label' => $catLabel,
            'rows' => [],
        ];
    }
    $fonctionnalitesByCat[$catCode]['rows'][] = $fonctionnalite;
}

$selectedGroupeId = (string) ($selectedGroupe->id_GU ?? ($_GET['groupe'] ?? ''));
$baseActionUrl = '?page=' . rawurlencode($pageSlug) . '&action=' . rawurlencode($action);
if ($selectedTypeId !== '') {
    $baseActionUrl .= '&type=' . rawurlencode($selectedTypeId);
}
if ($selectedGroupeId !== '') {
    $baseActionUrl .= '&groupe=' . rawurlencode($selectedGroupeId);
}

$isEditable = function_exists('canEdit') ? (bool) canEdit() : true;
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <?php
        ob_start();
        ?>
        <div class="cm-grid-2">
            <div class="cm-form-group">
                <label class="cm-form-label" for="cmAttribType">Type utilisateur</label>
                <select id="cmAttribType" class="cm-form-control">
                    <option value="all" <?= $selectedTypeId === 'all' ? 'selected' : '' ?>>Tous les types</option>
                    <?php foreach ($listeTypesAll as $type): ?>
                        <?php $typeId = (string) ($type->id_type_utilisateur ?? ''); ?>
                        <?php if ($typeId === '') {
                            continue;
                        } ?>
                    <option value="<?= htmlspecialchars($typeId, ENT_QUOTES, 'UTF-8') ?>" <?= $typeId === $selectedTypeId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($type->lib_type_utilisateur ?? ('Type ' . $typeId)), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="cm-form-group">
                <label class="cm-form-label" for="cmAttribGroup">Groupe utilisateur</label>
                <select id="cmAttribGroup" class="cm-form-control">
                    <option value="">-- Sélectionner un groupe --</option>
                    <?php foreach ($listeGroupes as $groupe): ?>
                        <?php $groupeId = (string) ($groupe->id_GU ?? ''); ?>
                        <?php if ($groupeId === '') {
                            continue;
                        } ?>
                    <option value="<?= htmlspecialchars($groupeId, ENT_QUOTES, 'UTF-8') ?>" <?= $groupeId === $selectedGroupeId ? 'selected' : '' ?>>
                        <?= htmlspecialchars((string) ($groupe->lib_GU ?? ('Groupe ' . $groupeId)), ENT_QUOTES, 'UTF-8') ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <?php
        cm_component('crud/form-pole', [
            'title' => '',
            'icon' => 'fa-user-shield',
            'content' => (string) ob_get_clean(),
        ]);
        ?>
        <?php
        $attribActions = [
            ['tag' => 'button', 'type' => 'button', 'id' => 'cmAttribCheckAll', 'label' => 'Tout cocher', 'class' => 'cm-btn is-info is-sm'],
            ['tag' => 'button', 'type' => 'button', 'id' => 'cmAttribUncheckAll', 'label' => 'Tout decocher', 'class' => 'cm-btn is-light is-sm'],
        ];
        if ($selectedGroupeId !== '' && $isEditable) {
            $attribActions[] = ['tag' => 'button', 'type' => 'submit', 'label' => 'Enregistrer', 'class' => 'cm-btn is-success is-sm', 'attrs' => ['form' => 'cmAttribForm']];
        }
        cm_toolbar([
            'screen' => 'gestion_attribution',
            'id_prefix' => 'cmAttribToolbar',
            'search_placeholder' => 'Rechercher un ecran...',
            'show_actions' => false,
            'show_filters' => false,
            'custom_actions' => $attribActions,
        ]);
        ?>

        <div class="cm-pole-inferieur">
            <?php if ($selectedGroupeId === '' || !$selectedGroupe): ?>
                <?php cm_component('ui/empty-state', [
                    'title' => '',
                    'message' => 'Sélectionnez un type puis un groupe pour modifier les permissions.',
                ]); ?>
            <?php else: ?>
            <form id="cmAttribForm" method="POST" action="<?= htmlspecialchars($baseActionUrl, ENT_QUOTES, 'UTF-8') ?>" class="cm-table-form" data-cm-ajax-form="true">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="id_GU" value="<?= htmlspecialchars($selectedGroupeId, ENT_QUOTES, 'UTF-8') ?>">

                <div class="cm-table-wrapper">
                    <table class="cm-data-table" id="cmAttribTable">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th">Ecran</th>
                                <th class="cm-data-table__th is-center">
                                    <label class="permHeader">
                                        <input type="checkbox" data-col-toggle="voir">
                                        <span>Voir</span>
                                    </label>
                                </th>
                                <th class="cm-data-table__th is-center">
                                    <label class="permHeader">
                                        <input type="checkbox" data-col-toggle="creer">
                                        <span>Creer</span>
                                    </label>
                                </th>
                                <th class="cm-data-table__th is-center">
                                    <label class="permHeader">
                                        <input type="checkbox" data-col-toggle="modifier">
                                        <span>Modifier</span>
                                    </label>
                                </th>
                                <th class="cm-data-table__th is-center">
                                    <label class="permHeader">
                                        <input type="checkbox" data-col-toggle="supprimer">
                                        <span>Supprimer</span>
                                    </label>
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($fonctionnalitesByCat as $catCode => $catData): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td" colspan="5">
                                    <strong><?= htmlspecialchars((string) $catData['label'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                            </tr>
                                <?php foreach ($catData['rows'] as $row): ?>
                                    <?php
                                    $idFonc = (int) ($row->id_fonctionnalite ?? 0);
                                    if ($idFonc <= 0) {
                                        continue;
                                    }
                                    $rowPerm = $permissionsMap[$idFonc] ?? ['voir' => false, 'creer' => false, 'modifier' => false, 'supprimer' => false];
                                    $label = (string) ($row->label_fonctionnalite ?? $row->lib_fonctionnalite ?? $row->code_fonctionnalite ?? ('Ecran ' . $idFonc));
                                    ?>
                            <tr class="cm-data-table__row" data-fonc-id="<?= $idFonc ?>">
                                <td class="cm-data-table__td">
                                    <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <input type="checkbox"
                                           data-perm="voir"
                                           name="permissions[<?= $idFonc ?>][voir]"
                                           <?= $rowPerm['voir'] ? 'checked' : '' ?>
                                           <?= $isEditable ? '' : 'disabled' ?>>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <input type="checkbox"
                                           data-perm="creer"
                                           name="permissions[<?= $idFonc ?>][creer]"
                                           <?= $rowPerm['creer'] ? 'checked' : '' ?>
                                           <?= $isEditable ? '' : 'disabled' ?>>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <input type="checkbox"
                                           data-perm="modifier"
                                           name="permissions[<?= $idFonc ?>][modifier]"
                                           <?= $rowPerm['modifier'] ? 'checked' : '' ?>
                                           <?= $isEditable ? '' : 'disabled' ?>>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <input type="checkbox"
                                           data-perm="supprimer"
                                           name="permissions[<?= $idFonc ?>][supprimer]"
                                           <?= $rowPerm['supprimer'] ? 'checked' : '' ?>
                                           <?= $isEditable ? '' : 'disabled' ?>>
                                </td>
                            </tr>
                                <?php endforeach; ?>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </form>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
(function () {
    const typeSelect = document.getElementById('cmAttribType');
    const groupSelect = document.getElementById('cmAttribGroup');
    const page = <?= json_encode($pageSlug) ?>;
    const action = <?= json_encode($action) ?>;

    function buildUrl(typeValue, groupValue) {
        const params = new URLSearchParams();
        params.set('page', page);
        params.set('action', action);
        if (typeValue && typeValue !== '') {
            params.set('type', typeValue);
        }
        if (groupValue && groupValue !== '') {
            params.set('groupe', groupValue);
        }
        return '?' + params.toString();
    }

    function navigate(url) {
        if (window.CM && window.CM.ajax && typeof window.CM.ajax.load === 'function') {
            window.CM.ajax.load(url);
            return;
        }
        window.location.href = url;
    }

    if (typeSelect) {
        typeSelect.addEventListener('change', function () {
            navigate(buildUrl(typeSelect.value, ''));
        });
    }

    if (groupSelect) {
        groupSelect.addEventListener('change', function () {
            const typeValue = typeSelect ? typeSelect.value : 'all';
            navigate(buildUrl(typeValue, groupSelect.value));
        });
    }

    const table = document.getElementById('cmAttribTable');
    if (!table) {
        return;
    }

    function syncRowRules(row) {
        const voir = row.querySelector('input[data-perm="voir"]');
        const creer = row.querySelector('input[data-perm="creer"]');
        const modifier = row.querySelector('input[data-perm="modifier"]');
        const supprimer = row.querySelector('input[data-perm="supprimer"]');
        if (!voir || !creer || !modifier || !supprimer) {
            return;
        }

        if (!voir.checked) {
            creer.checked = false;
            modifier.checked = false;
            supprimer.checked = false;
        }
        if (creer.checked || modifier.checked || supprimer.checked) {
            voir.checked = true;
        }
    }

    table.addEventListener('change', function (event) {
        const input = event.target.closest('input[type="checkbox"][data-perm]');
        if (!input) {
            return;
        }
        const row = input.closest('tr[data-fonc-id]');
        if (row) {
            syncRowRules(row);
        }
    });

    function toggleAll(checked) {
        table.querySelectorAll('tr[data-fonc-id]').forEach(function (row) {
            row.querySelectorAll('input[type="checkbox"][data-perm]').forEach(function (input) {
                if (!input.disabled) {
                    input.checked = checked;
                }
            });
            syncRowRules(row);
        });
    }

    const checkAllBtn = document.getElementById('cmAttribCheckAll');
    const uncheckAllBtn = document.getElementById('cmAttribUncheckAll');
    if (checkAllBtn) {
        checkAllBtn.addEventListener('click', function () { toggleAll(true); });
    }
    if (uncheckAllBtn) {
        uncheckAllBtn.addEventListener('click', function () { toggleAll(false); });
    }

    table.querySelectorAll('input[data-col-toggle]').forEach(function (toggle) {
        toggle.addEventListener('change', function () {
            const col = toggle.getAttribute('data-col-toggle');
            if (!col) {
                return;
            }
            table.querySelectorAll('input[data-perm="' + col + '"]').forEach(function (input) {
                if (!input.disabled) {
                    input.checked = toggle.checked;
                    const row = input.closest('tr[data-fonc-id]');
                    if (row) {
                        syncRowRules(row);
                    }
                }
            });
        });
    });
})();
</script>

<?php
$categories = $GLOBALS['menuMgmtCategories'] ?? [];
$tree = $GLOBALS['menuMgmtTree'] ?? [];
$isEditable = $GLOBALS['menuMgmtIsEditable'] ?? (function_exists('canEdit') ? (bool) canEdit() : true);
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
$csrfToken = \CheckMaster\Core\Csrf::token();

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function js(string $s): string
{
    return htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

function isChecked($v): string
{
    return !empty($v) ? 'checked' : '';
}
?>

<section class="cm-prd3-crud-screen cm-prd6-admin-screen cm-screen-scrollable">
    <?php if ($messageSuccess): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <!-- â”€â”€ Pôle supérieur : Formulaires de création â”€â”€ -->
        <?php
        ob_start();
        ?>
        <div class="cm-flex-col cm-flex-gap-sm">
            <!-- Collapse : Menu (Catégorie) -->
            <div class="cm-collapse is-open" id="collapseCategory">
                <button type="button" class="cm-collapse__trigger" onclick="toggleCollapse('collapseCategory')">
                    <span class="cm-collapse__trigger-label">
                        <i class="fas fa-layer-group" aria-hidden="true"></i>
                        Menu (Catégorie)
                        <span class="cm-text-muted cm-text-xs">categories_fonctionnalites</span>
                    </span>
                    <i class="fas fa-chevron-down cm-collapse__trigger-chevron" aria-hidden="true"></i>
                </button>
                <div class="cm-collapse__body">
                    <form method="POST" class="cm-grid-2">
                        <?php cm_component('form/csrf-token'); ?>
                        <input type="hidden" name="op" value="create_category">

                        <div class="cm-form-group">
                            <label class="cm-form-label">Code (unique) <span class="cm-required-star">*</span></label>
                            <input name="code_categorie" class="cm-form-control"
                                   placeholder="SCOLARITE" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Libellé <span class="cm-required-star">*</span></label>
                            <input name="lib_categorie" class="cm-form-control"
                                   placeholder="Gestion de la scolarité" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="cm-form-group" style="grid-column: 1 / -1">
                            <label class="cm-form-label">Description</label>
                            <textarea name="description_categorie" class="cm-form-control"
                                      placeholder="Description de la catégorie..."
                                      rows="2" style="min-height: 60px; resize: vertical;" <?= $isEditable ? '' : 'disabled' ?>></textarea>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Icône (FontAwesome)</label>
                            <input name="icone_categorie" class="cm-form-control"
                                   placeholder="fas fa-school" <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Ordre</label>
                            <input name="ordre_categorie" type="number" class="cm-form-control" value="0"
                                    <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="cm-flex-between cm-align-center" style="grid-column: 1 / -1">
                            <label class="cm-flex cm-flex-gap-sm cm-align-center cm-text-sm" style="cursor: pointer;">
                                <input type="checkbox" name="actif" value="1" checked <?= $isEditable ? '' : 'disabled' ?>>
                                <span>Actif</span>
                            </label>
                            <button type="submit"
                                    class="cm-btn is-success is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                    <?= $isEditable ? '' : 'disabled' ?>>
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                <span>Créer</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Collapse : Sous-menu / Écran -->
            <div class="cm-collapse is-open" id="collapseItem">
                <button type="button" class="cm-collapse__trigger" onclick="toggleCollapse('collapseItem')">
                    <span class="cm-collapse__trigger-label">
                        <i class="fas fa-file-alt" aria-hidden="true"></i>
                        Sous-menu / Écran (Fonctionnalité)
                        <span class="cm-text-muted cm-text-xs">fonctionnalites</span>
                    </span>
                    <i class="fas fa-chevron-down cm-collapse__trigger-chevron" aria-hidden="true"></i>
                </button>
                <div class="cm-collapse__body">
                    <form method="POST" class="cm-grid-2" id="createItemForm">
                        <?php cm_component('form/csrf-token'); ?>
                        <input type="hidden" name="op" value="create_fonctionnalite">

                        <div class="cm-form-group">
                            <label class="cm-form-label">Catégorie <span class="cm-required-star">*</span></label>
                            <select name="id_categorie" class="cm-form-control" required <?= $isEditable ? '' : 'disabled' ?>>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int) $c->id_categorie ?>"><?= h((string) $c->lib_categorie) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Type</label>
                            <select name="type_item" id="typeItem" class="cm-form-control" <?= $isEditable ? '' : 'disabled' ?>>
                                <option value="parent">Sous-menu</option>
                                <option value="child">Écran</option>
                            </select>
                        </div>

                        <div class="cm-form-group" id="parentPicker" style="display:none; grid-column: 1 / -1">
                            <label class="cm-form-label">Parent (code sous-menu)</label>
                            <input name="page_parente" class="cm-form-control"
                                   placeholder="SCOL_INSCRIPTIONS" <?= $isEditable ? '' : 'disabled' ?>>
                            <span class="cm-form-hint">Astuce : le parent est le <strong>code_fonctionnalite</strong> du sous-menu.</span>
                        </div>

                        <div class="cm-form-group">
                            <label class="cm-form-label">Code (unique) <span class="cm-required-star">*</span></label>
                            <input name="code_fonctionnalite" class="cm-form-control"
                                   placeholder="SCOL_INSCRIPTIONS" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Libellé (menu) <span class="cm-required-star">*</span></label>
                            <input name="label_fonctionnalite" class="cm-form-control"
                                   placeholder="Inscriptions" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="cm-form-group" style="grid-column: 1 / -1">
                            <label class="cm-form-label">URL</label>
                            <input name="url_fonctionnalite" class="cm-form-control"
                                   placeholder="?page=parametres_generaux&action=gestion_attribution" <?= $isEditable ? '' : 'disabled' ?>>
                            <span class="cm-form-hint">Pour un sous-menu, tu peux laisser <code>#</code> (non cliquable).</span>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Icône</label>
                            <div class="cm-flex cm-flex-gap-xs">
                                <input name="icone_fonctionnalite" class="cm-form-control"
                                       placeholder="fas fa-folder-open"
                                       oninput="var prev = this.nextElementSibling.querySelector('i'); if(prev) prev.className = this.value || 'fas fa-question';"
                                        <?= $isEditable ? '' : 'disabled' ?>>
                                <div class="cm-flex cm-align-center cm-justify-center" style="min-width: 40px; background: var(--cm-table-header-bg); border: 1px solid var(--cm-input-border); border-radius: var(--cm-border-radius);">
                                    <i class="fas fa-question" aria-hidden="true" style="color: var(--cm-primary); font-size: 1rem;"></i>
                                </div>
                            </div>
                        </div>
                        <div class="cm-form-group">
                            <label class="cm-form-label">Ordre</label>
                            <input name="ordre_fonctionnalite" type="number" class="cm-form-control" value="0"
                                    <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="cm-form-group" style="grid-column: 1 / -1">
                            <label class="cm-flex cm-flex-gap-sm cm-align-center cm-text-sm">
                                <input type="checkbox" name="actif" value="1" checked <?= $isEditable ? '' : 'disabled' ?>>
                                Actif
                            </label>
                        </div>
                        <div class="cm-flex-end" style="grid-column: 1 / -1">
                            <button type="submit"
                                    class="cm-btn is-success is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                    <?= $isEditable ? '' : 'disabled' ?>>
                                <i class="fas fa-plus" aria-hidden="true"></i>
                                <span>Créer</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php
        cm_component('crud/form-pole', [
                'title' => '',
                'icon'  => 'fa-plus-circle',
                'content' => (string) ob_get_clean(),
        ]);
        ?>

        <!-- â”€â”€ Barre intermédiaire â”€â”€ -->
        <?php
        ob_start();
        ?>
        <?php if (!$isEditable): ?>
            <span class="cm-badge is-light"><i class="fas fa-lock" aria-hidden="true"></i> Lecture seule</span>
        <?php endif; ?>
        <?php
        cm_component('crud/toolbar', [
                'left_html' => '<span class="cm-text-muted">(Parent: <code>est_sous_page=0</code>, Enfant: <code>est_sous_page=1</code> + <code>page_parente</code>)</span>',
                'right_html' => (string) ob_get_clean(),
        ]);
        ?>

        <!-- â”€â”€ Pôle inférieur : Table hiérarchique â”€â”€ -->
        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmMenuTree">
                    <thead>
                    <tr>
                        <th class="cm-data-table__th">Type</th>
                        <th class="cm-data-table__th">Code</th>
                        <th class="cm-data-table__th">Libellé</th>
                        <th class="cm-data-table__th">URL</th>
                        <th class="cm-data-table__th is-center">Ordre</th>
                        <th class="cm-data-table__th is-center">Statut</th>
                        <th class="cm-data-table__th is-right">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($tree as $cid => $node): ?>
                        <?php $c = $node['categorie']; ?>
                        <!-- Category header -->
                        <tr class="cm-tree-header">
                            <td class="cm-data-table__td" colspan="7">
                                    <span class="cm-flex cm-flex-gap-sm cm-align-center">
                                        <i class="<?= h((string) ($c->icone_categorie ?? 'fas fa-folder')) ?>" aria-hidden="true"></i>
                                        <?= h((string) $c->lib_categorie) ?>
                                        <span class="cm-text-muted cm-text-xs">(<?= h((string) $c->code_categorie) ?>)</span>
                                    </span>
                            </td>
                        </tr>

                        <!-- Category row -->
                        <tr class="cm-data-table__row">
                            <td class="cm-data-table__td">
                                <?php cm_component('ui/badge', ['text' => 'Menu', 'type' => 'primary']); ?>
                            </td>
                            <td class="cm-data-table__td"><?= h((string) $c->code_categorie) ?></td>
                            <td class="cm-data-table__td cm-text-semibold"><?= h((string) $c->lib_categorie) ?></td>
                            <td class="cm-data-table__td cm-text-muted">—</td>
                            <td class="cm-data-table__td is-center"><?= (int) ($c->ordre_categorie ?? 0) ?></td>
                            <td class="cm-data-table__td is-center">
                                <?php cm_component('ui/badge', [
                                        'text' => !empty($c->actif) ? 'Actif' : 'Inactif',
                                        'type' => !empty($c->actif) ? 'success' : 'danger',
                                ]); ?>
                            </td>
                            <td class="cm-data-table__td is-right">
                                <div class="cm-table-actions">
                                    <button type="button" class="cm-btn is-light is-sm"
                                            onclick="openEditCategory(<?= (int) $c->id_categorie ?>, <?= js((string) $c->lib_categorie) ?>, <?= js((string) ($c->description_categorie ?? '')) ?>, <?= js((string) ($c->icone_categorie ?? '')) ?>, <?= (int) ($c->ordre_categorie ?? 0) ?>, <?= (int) ($c->actif ?? 0) ?>)"
                                            <?= $isEditable ? '' : 'disabled' ?>>
                                        <i class="fas fa-pen" aria-hidden="true"></i>
                                        <span>Modifier</span>
                                    </button>

                                    <?php if (!empty($c->actif)): ?>
                                        <form method="POST" onsubmit="return confirm('Désactiver cette catégorie et ses fonctionnalités ?');" class="cm-flex">
                                            <?php cm_component('form/csrf-token'); ?>
                                            <input type="hidden" name="op" value="deactivate_category">
                                            <input type="hidden" name="id_categorie" value="<?= (int) $c->id_categorie ?>">
                                            <button class="cm-btn is-danger is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                                    <?= $isEditable ? '' : 'disabled' ?>>
                                                <i class="fas fa-ban" aria-hidden="true"></i>
                                                <span>Désactiver</span>
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form method="POST" onsubmit="return confirm('Réactiver cette catégorie et ses fonctionnalités ?');" class="cm-flex">
                                            <?php cm_component('form/csrf-token'); ?>
                                            <input type="hidden" name="op" value="activate_category">
                                            <input type="hidden" name="id_categorie" value="<?= (int) $c->id_categorie ?>">
                                            <button class="cm-btn is-success is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                                    <?= $isEditable ? '' : 'disabled' ?>>
                                                <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                <span>Réactiver</span>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>

                        <?php $parents = $node['parents'] ?? []; ?>
                        <?php foreach ($parents as $p): ?>
                            <?php
                            $pid = (int) ($p->id_fonctionnalite ?? 0);
                            $pcode = (string) ($p->code_fonctionnalite ?? '');
                            $plabel = (string) ($p->label_fonctionnalite ?? $p->lib_fonctionnalite ?? $pcode);
                            $purl = (string) ($p->url_fonctionnalite ?? '#');
                            $pordre = (int) ($p->ordre_fonctionnalite ?? 0);
                            $children = (isset($p->children) && is_array($p->children)) ? $p->children : [];
                            $hasChildren = !empty($children);
                            $groupId = 'grp-' . md5((string) $cid . '|' . $pcode . '|' . (string) $pid);
                            ?>
                            <!-- Parent (sous-menu) row -->
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td">
                                        <span class="cm-flex cm-flex-gap-sm cm-align-center">
                                            <?php if ($hasChildren): ?>
                                                <button type="button"
                                                        class="cm-tree-toggle toggleRow"
                                                        data-target="<?= h($groupId) ?>" aria-label="Déplier/Replier">
                                                    <span class="toggleIcon">+</span>
                                                </button>
                                            <?php else: ?>
                                                <span class="cm-tree-toggle is-empty">-</span>
                                            <?php endif; ?>
                                            <?php cm_component('ui/badge', ['text' => 'Sous-menu', 'type' => 'light']); ?>
                                        </span>
                                </td>
                                <td class="cm-data-table__td"><?= h($pcode) ?></td>
                                <td class="cm-data-table__td cm-text-semibold"><?= h($plabel) ?></td>
                                <td class="cm-data-table__td cm-text-muted"><?= h($purl) ?></td>
                                <td class="cm-data-table__td is-center"><?= $pordre ?></td>
                                <td class="cm-data-table__td is-center">
                                    <?php cm_component('ui/badge', [
                                            'text' => !empty($p->actif) ? 'Actif' : 'Inactif',
                                            'type' => !empty($p->actif) ? 'success' : 'danger',
                                    ]); ?>
                                </td>
                                <td class="cm-data-table__td is-right">
                                    <div class="cm-table-actions">
                                        <button type="button" class="cm-btn is-light is-sm"
                                                onclick="openEditItem(<?= (int) $pid ?>, <?= (int) $cid ?>, 'parent', <?= js((string) ($p->lib_fonctionnalite ?? '')) ?>, <?= js((string) ($p->label_fonctionnalite ?? '')) ?>, <?= js((string) ($p->description_fonctionnalite ?? '')) ?>, <?= js((string) ($p->url_fonctionnalite ?? '#')) ?>, <?= js((string) ($p->icone_fonctionnalite ?? '')) ?>, <?= (int) ($p->ordre_fonctionnalite ?? 0) ?>, <?= js('') ?>, <?= (int) ($p->actif ?? 0) ?>)"
                                                <?= $isEditable ? '' : 'disabled' ?>>
                                            <i class="fas fa-pen" aria-hidden="true"></i>
                                            <span>Modifier</span>
                                        </button>
                                        <?php if (!empty($p->actif)): ?>
                                            <form method="POST" onsubmit="return confirm('Désactiver cet élément ?');" class="cm-flex">
                                                <?php cm_component('form/csrf-token'); ?>
                                                <input type="hidden" name="op" value="deactivate_fonctionnalite">
                                                <input type="hidden" name="id_fonctionnalite" value="<?= (int) $pid ?>">
                                                <button class="cm-btn is-danger is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                                        <?= $isEditable ? '' : 'disabled' ?>>
                                                    <i class="fas fa-ban" aria-hidden="true"></i>
                                                    <span>Désactiver</span>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" onsubmit="return confirm('Réactiver cet élément ?');" class="cm-flex">
                                                <?php cm_component('form/csrf-token'); ?>
                                                <input type="hidden" name="op" value="activate_fonctionnalite">
                                                <input type="hidden" name="id_fonctionnalite" value="<?= (int) $pid ?>">
                                                <button class="cm-btn is-success is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                                        <?= $isEditable ? '' : 'disabled' ?>>
                                                    <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                    <span>Réactiver</span>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <?php foreach ($children as $ch): ?>
                                <?php
                                $chid = (int) ($ch->id_fonctionnalite ?? 0);
                                $chcode = (string) ($ch->code_fonctionnalite ?? '');
                                $chlabel = (string) ($ch->label_fonctionnalite ?? $ch->lib_fonctionnalite ?? $chcode);
                                $churl = (string) ($ch->url_fonctionnalite ?? '#');
                                $chordre = (int) ($ch->ordre_fonctionnalite ?? 0);
                                ?>
                                <!-- Child (écran) row -->
                                <tr class="cm-data-table__row childRow cm-hidden" data-parent="<?= h($groupId) ?>">
                                    <td class="cm-data-table__td cm-tree-indent">
                                        <?php cm_component('ui/badge', ['text' => 'Écran', 'type' => 'info']); ?>
                                    </td>
                                    <td class="cm-data-table__td"><?= h($chcode) ?></td>
                                    <td class="cm-data-table__td"><?= h($chlabel) ?></td>
                                    <td class="cm-data-table__td cm-text-muted"><?= h($churl) ?></td>
                                    <td class="cm-data-table__td is-center"><?= $chordre ?></td>
                                    <td class="cm-data-table__td is-center">
                                        <?php cm_component('ui/badge', [
                                                'text' => !empty($ch->actif) ? 'Actif' : 'Inactif',
                                                'type' => !empty($ch->actif) ? 'success' : 'danger',
                                        ]); ?>
                                    </td>
                                    <td class="cm-data-table__td is-right">
                                        <div class="cm-table-actions">
                                            <button type="button" class="cm-btn is-light is-sm"
                                                    onclick="openEditItem(<?= (int) $chid ?>, <?= (int) $cid ?>, 'child', <?= js((string) ($ch->lib_fonctionnalite ?? '')) ?>, <?= js((string) ($ch->label_fonctionnalite ?? '')) ?>, <?= js((string) ($ch->description_fonctionnalite ?? '')) ?>, <?= js((string) ($ch->url_fonctionnalite ?? '#')) ?>, <?= js((string) ($ch->icone_fonctionnalite ?? '')) ?>, <?= (int) ($ch->ordre_fonctionnalite ?? 0) ?>, <?= js((string) ($ch->page_parente ?? '')) ?>, <?= (int) ($ch->actif ?? 0) ?>)"
                                                    <?= $isEditable ? '' : 'disabled' ?>>
                                                <i class="fas fa-pen" aria-hidden="true"></i>
                                                <span>Modifier</span>
                                            </button>
                                            <?php if (!empty($ch->actif)): ?>
                                                <form method="POST" onsubmit="return confirm('Désactiver cet écran ?');" class="cm-flex">
                                                    <?php cm_component('form/csrf-token'); ?>
                                                    <input type="hidden" name="op" value="deactivate_fonctionnalite">
                                                    <input type="hidden" name="id_fonctionnalite" value="<?= (int) $chid ?>">
                                                    <button class="cm-btn is-danger is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                                            <?= $isEditable ? '' : 'disabled' ?>>
                                                        <i class="fas fa-ban" aria-hidden="true"></i>
                                                        <span>Désactiver</span>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" onsubmit="return confirm('Réactiver cet écran ?');" class="cm-flex">
                                                    <?php cm_component('form/csrf-token'); ?>
                                                    <input type="hidden" name="op" value="activate_fonctionnalite">
                                                    <input type="hidden" name="id_fonctionnalite" value="<?= (int) $chid ?>">
                                                    <button class="cm-btn is-success is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                                                            <?= $isEditable ? '' : 'disabled' ?>>
                                                        <i class="fas fa-check-circle" aria-hidden="true"></i>
                                                        <span>Réactiver</span>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<div id="editCategoryModal" class="cm-modal-overlay">
    <div class="cm-modal">
        <div class="cm-modal__header">

            <button type="button" class="cm-modal__close" onclick="closeModal('editCategoryModal')">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form method="POST">
            <div class="cm-modal__body">
                <div class="cm-grid-2">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="op" value="update_category">
                    <input type="hidden" name="id_categorie" id="editCatId">

                    <div class="cm-form-group" style="grid-column: 1 / -1">
                        <label class="cm-form-label">Libellé <span class="cm-required-star">*</span></label>
                        <input name="lib_categorie" id="editCatLib" class="cm-form-control"
                               required <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group" style="grid-column: 1 / -1">
                        <label class="cm-form-label">Description</label>
                        <input name="description_categorie" id="editCatDesc" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group">
                        <label class="cm-form-label">Icône</label>
                        <input name="icone_categorie" id="editCatIcon" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group">
                        <label class="cm-form-label">Ordre</label>
                        <input name="ordre_categorie" id="editCatOrdre" type="number" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group" style="grid-column: 1 / -1">
                        <label class="cm-flex cm-flex-gap-sm cm-align-center cm-text-sm">
                            <input type="checkbox" name="actif" id="editCatActif" value="1" <?= $isEditable ? '' : 'disabled' ?>>
                            Actif
                        </label>
                    </div>
                </div>
            </div>
            <div class="cm-modal__footer">
                <button type="button" class="cm-btn is-light is-sm" onclick="closeModal('editCategoryModal')">
                    <span>Annuler</span>
                </button>
                <button type="submit"
                        class="cm-btn is-success is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                        <?= $isEditable ? '' : 'disabled' ?>>
                    <i class="fas fa-save" aria-hidden="true"></i>
                    <span>Enregistrer</span>
                </button>
            </div>
        </form>
    </div>
</div>

<div id="editItemModal" class="cm-modal-overlay">
    <div class="cm-modal">
        <div class="cm-modal__header">

            <button type="button" class="cm-modal__close" onclick="closeModal('editItemModal')">
                <i class="fas fa-times" aria-hidden="true"></i>
            </button>
        </div>
        <form method="POST">
            <div class="cm-modal__body">
                <div class="cm-grid-2">
                    <?php cm_component('form/csrf-token'); ?>
                    <input type="hidden" name="op" value="update_fonctionnalite">
                    <input type="hidden" name="id_fonctionnalite" id="editItemId">

                    <div class="cm-form-group">
                        <label class="cm-form-label">Catégorie <span class="cm-required-star">*</span></label>
                        <select name="id_categorie" id="editItemCat" class="cm-form-control"
                                required <?= $isEditable ? '' : 'disabled' ?>>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c->id_categorie ?>"><?= h((string) $c->lib_categorie) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="cm-form-group">
                        <label class="cm-form-label">Type</label>
                        <select name="type_item" id="editItemType" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                            <option value="parent">Sous-menu</option>
                            <option value="child">Écran</option>
                        </select>
                    </div>

                    <div class="cm-form-group" id="editItemParentWrap" style="display:none; grid-column: 1 / -1">
                        <label class="cm-form-label">Parent (code sous-menu)</label>
                        <input name="page_parente" id="editItemParent" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Libellé (menu) <span class="cm-required-star">*</span></label>
                        <input name="label_fonctionnalite" id="editItemLabel" class="cm-form-control"
                               required <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group">
                        <label class="cm-form-label">Libellé interne</label>
                        <input name="lib_fonctionnalite" id="editItemLib" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group" style="grid-column: 1 / -1">
                        <label class="cm-form-label">Description</label>
                        <input name="description_fonctionnalite" id="editItemDesc" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group" style="grid-column: 1 / -1">
                        <label class="cm-form-label">URL</label>
                        <input name="url_fonctionnalite" id="editItemUrl" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group">
                        <label class="cm-form-label">Icône</label>
                        <input name="icone_fonctionnalite" id="editItemIcon" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group">
                        <label class="cm-form-label">Ordre</label>
                        <input name="ordre_fonctionnalite" id="editItemOrdre" type="number" class="cm-form-control"
                                <?= $isEditable ? '' : 'disabled' ?>>
                    </div>
                    <div class="cm-form-group" style="grid-column: 1 / -1">
                        <label class="cm-flex cm-flex-gap-sm cm-align-center cm-text-sm">
                            <input type="checkbox" name="actif" id="editItemActif" value="1" <?= $isEditable ? '' : 'disabled' ?>>
                            Actif
                        </label>
                    </div>
                </div>
            </div>
            <div class="cm-modal__footer">
                <button type="button" class="cm-btn is-light is-sm" onclick="closeModal('editItemModal')">
                    <span>Annuler</span>
                </button>
                <button type="submit"
                        class="cm-btn is-success is-sm <?= $isEditable ? '' : 'is-disabled' ?>"
                        <?= $isEditable ? '' : 'disabled' ?>>
                    <i class="fas fa-save" aria-hidden="true"></i>
                    <span>Enregistrer</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        /* === Scroll lock for modal - prevents background scroll === */
        function setScrollLock(lock) {
            document.body.style.overflow = lock ? 'hidden' : '';
            document.body.style.paddingRight = lock ? (window.innerWidth - document.documentElement.clientWidth) + 'px' : '';
        }

        /* â”€â”€ Collapsible sections â”€â”€ */
        window.toggleCollapse = function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.toggle('is-open');
        };

        /* â”€â”€ Create form: type â†’ parent picker â”€â”€ */
        var typeItem = document.getElementById('typeItem');
        var parentPicker = document.getElementById('parentPicker');
        function syncCreate() {
            if (!typeItem || !parentPicker) return;
            parentPicker.style.display = (typeItem.value === 'child') ? '' : 'none';
        }
        if (typeItem) typeItem.addEventListener('change', syncCreate);
        syncCreate();

        /* â”€â”€ Modal helpers â”€â”€ */
        window.closeModal = function (id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('is-open');
            setScrollLock(false);
        };

        function openModal(id) {
            var el = document.getElementById(id);
            if (!el) return;
            el.classList.add('is-open');
            setScrollLock(true);
        }

        /* Close on overlay click */
        document.querySelectorAll('.cm-modal-overlay').forEach(function (overlay) {
            overlay.addEventListener('click', function (e) {
                if (e.target === overlay) {
                    overlay.classList.remove('is-open');
                    setScrollLock(false);
                }
            });
        });

        /* Close on Escape key */
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                document.querySelectorAll('.cm-modal-overlay.is-open').forEach(function (m) {
                    m.classList.remove('is-open');
                });
                setScrollLock(false);
            }
        });

        /* â”€â”€ Edit category â”€â”€ */
        window.openEditCategory = function (id, lib, desc, icon, ordre, actif) {
            var byId = function (x) { return document.getElementById(x); };
            byId('editCatId').value = String(id);
            byId('editCatLib').value = lib || '';
            byId('editCatDesc').value = desc || '';
            byId('editCatIcon').value = icon || '';
            byId('editCatOrdre').value = String(ordre || 0);
            byId('editCatActif').checked = !!actif;
            openModal('editCategoryModal');
        };

        /* â”€â”€ Edit fonctionnalité â”€â”€ */
        function syncEditParent() {
            var type = document.getElementById('editItemType');
            var wrap = document.getElementById('editItemParentWrap');
            if (!type || !wrap) return;
            wrap.style.display = (type.value === 'child') ? '' : 'none';
        }

        window.openEditItem = function (id, idCategorie, type, lib, label, desc, url, icon, ordre, parent, actif) {
            var byId = function (x) { return document.getElementById(x); };
            byId('editItemId').value = String(id);
            byId('editItemCat').value = String(idCategorie);
            byId('editItemType').value = type || 'parent';
            byId('editItemLib').value = lib || '';
            byId('editItemLabel').value = label || '';
            byId('editItemDesc').value = desc || '';
            byId('editItemUrl').value = url || '';
            byId('editItemIcon').value = icon || '';
            byId('editItemOrdre').value = String(ordre || 0);
            byId('editItemParent').value = parent || '';
            byId('editItemActif').checked = !!actif;
            syncEditParent();
            openModal('editItemModal');
        };

        var editType = document.getElementById('editItemType');
        if (editType) editType.addEventListener('change', syncEditParent);

        /* â”€â”€ Expand / collapse child rows â”€â”€ */
        document.addEventListener('click', function (e) {
            var btn = e.target && e.target.closest ? e.target.closest('.toggleRow') : null;
            if (!btn) return;
            var target = btn.getAttribute('data-target');
            if (!target) return;
            var rows = document.querySelectorAll('tr.childRow[data-parent="' + target + '"]');
            var icon = btn.querySelector('.toggleIcon');
            var isHidden = rows.length > 0 ? rows[0].classList.contains('cm-hidden') : true;
            rows.forEach(function (r) { r.classList.toggle('cm-hidden', !isHidden); });
            if (icon) icon.textContent = isHidden ? '-' : '+';
        });
    })();
</script>
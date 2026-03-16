<?php
$categories = $GLOBALS['menuMgmtCategories'] ?? [];
$tree = $GLOBALS['menuMgmtTree'] ?? [];
$isEditable = $GLOBALS['menuMgmtIsEditable'] ?? (function_exists('canEdit') ? (bool) canEdit() : true);
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
$currentPage = (string) ($_GET['page'] ?? 'parametres_generaux');
$currentAction = (string) ($_GET['action'] ?? 'gestion_menus');
$formAction = '?page=' . rawurlencode($currentPage) . '&action=' . rawurlencode($currentAction);
$createBtnClass = 'cm-btn is-success is-sm' . ($isEditable ? '' : ' is-disabled');
$editBtnClass = 'cm-btn is-warning is-sm' . ($isEditable ? '' : ' is-disabled');

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }
function js(string $s): string { return htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8'); }
function renderToggleForm(string $formAction, string $op, string $idField, int $idValue, string $confirmMessage, string $confirmType, string $confirmText, string $btnClass, string $icon, string $label, bool $disabled): void
{
    ?>
    <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
.cm-content-area form .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="POST" action="<?= h($formAction) ?>" class="cm-flex me-2" data-cm-confirm-message="<?= h($confirmMessage) ?>" data-cm-confirm-type="<?= h($confirmType) ?>" data-cm-confirm-text="<?= h($confirmText) ?>">
        <?php cm_component('form/csrf-token'); ?>
        <input type="hidden" name="op" value="<?= h($op) ?>">
        <input type="hidden" name="<?= h($idField) ?>" value="<?= $idValue ?>">
        <button class="<?= h($btnClass . ($disabled ? ' is-disabled' : '')) ?>" <?= $disabled ? 'disabled' : '' ?>>
            <i class="<?= h($icon) ?>" aria-hidden="true"></i>
            <span><?= h($label) ?></span>
        </button>
    </form>
    <?php
}
?>
<style>
/* Écran gestion menus: forcer le tableau à tenir dans la largeur visible */
#cmMenuTree {
    width: 100%;
    table-layout: fixed;
}

#cmMenuTree th:nth-child(1),
#cmMenuTree td:nth-child(1) { width: 92px; }
#cmMenuTree th:nth-child(2),
#cmMenuTree td:nth-child(2) { width: 170px; }
#cmMenuTree th:nth-child(5),
#cmMenuTree td:nth-child(5) { width: 74px; }
#cmMenuTree th:nth-child(6),
#cmMenuTree td:nth-child(6) { width: 92px; }
#cmMenuTree th:nth-child(7),
#cmMenuTree td:nth-child(7) { width: 240px; }

#cmMenuTree th,
#cmMenuTree td {
    vertical-align: middle;
}

#cmMenuTree td:nth-child(3),
#cmMenuTree td:nth-child(4) {
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

#cmMenuTree .cm-table-actions {
    display: flex;
    flex-wrap: wrap;
    justify-content: flex-end;
    gap: 0.35rem;
}

#cmMenuTree .cm-table-actions form {
    margin: 0;
}

#cmMenuTree .cm-table-actions .cm-btn {
    min-height: 34px !important;
    height: 34px !important;
    padding: 0.28rem 0.55rem !important;
    font-size: 0.8rem !important;
}

@media (max-width: 1280px) {
    #cmMenuTree th:nth-child(2),
    #cmMenuTree td:nth-child(2) { width: 145px; }

    #cmMenuTree th:nth-child(7),
    #cmMenuTree td:nth-child(7) { width: 200px; }

    #cmMenuTree .cm-table-actions .cm-btn span {
        display: none;
    }

    #cmMenuTree .cm-table-actions .cm-btn {
        min-width: 34px;
        padding: 0.28rem 0.45rem !important;
    }
}
</style>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen cm-screen-scrollable">
    <?php if ($messageSuccess): ?><?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?><?php endif; ?>
    <?php if ($messageErreur): ?><?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?><?php endif; ?>
    <div class="cm-crud-wrapper">
        <?php ob_start(); ?>
        <div class="cm-flex-col cm-flex-gap-sm">
            <div class="cm-collapse is-open" id="collapseCategory">
                <button type="button" class="cm-collapse__trigger" onclick="toggleCollapse('collapseCategory')">
                    <span class="cm-collapse__trigger-label"><i class="fas fa-layer-group" aria-hidden="true"></i>Menu (Catégorie)<span class="cm-text-muted cm-text-xs">categories_fonctionnalites</span></span>
                    <i class="fas fa-chevron-down cm-collapse__trigger-chevron" aria-hidden="true"></i>
                </button>
                <div class="cm-collapse__body">
                    <form method="POST" action="<?= h($formAction) ?>" class="cm-grid-2" id="categoryForm">
                        <?php cm_component('form/csrf-token'); ?>
                        <input type="hidden" name="op" id="categoryOp" value="create_category">
                        <input type="hidden" name="id_categorie" id="editCategoryId" value="">
                        <div class="cm-form-group" style="grid-column: 1 / -1"><div id="categoryEditNotice" class="cm-text-muted cm-text-sm cm-hidden">Mode modification actif. Le code reste en lecture seule.</div></div>
                        <div class="cm-form-group"><label class="cm-form-label">Code (unique) <span class="cm-required-star">*</span></label><input id="code_categorie" name="code_categorie" class="cm-form-control" placeholder="SCOLARITE" required <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group"><label class="cm-form-label">Libellé <span class="cm-required-star">*</span></label><input id="lib_categorie" name="lib_categorie" class="cm-form-control" placeholder="Gestion de la scolarité" required <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group" style="grid-column: 1 / -1"><label class="cm-form-label">Description</label><textarea id="description_categorie" name="description_categorie" class="cm-form-control" rows="2" style="min-height: 60px; resize: vertical;" placeholder="Description de la catégorie..." <?= $isEditable ? '' : 'disabled' ?>></textarea></div>
                        <div class="cm-form-group"><label class="cm-form-label">Icône (FontAwesome)</label><input id="icone_categorie" name="icone_categorie" class="cm-form-control" placeholder="fas fa-school" <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group"><label class="cm-form-label">Ordre</label><input id="ordre_categorie" name="ordre_categorie" type="number" class="cm-form-control" value="0" <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-flex-between cm-align-center" style="grid-column: 1 / -1">
                            <label class="cm-flex cm-flex-gap-sm cm-align-center cm-text-sm"><input id="categoryActif" type="checkbox" name="actif" value="1" checked <?= $isEditable ? '' : 'disabled' ?>><span>Actif</span></label>
                            <div class="cm-flex cm-flex-gap-sm">
                                <button type="button" id="categoryCancelButton" class="cm-btn is-light is-sm cm-hidden" <?= $isEditable ? '' : 'disabled' ?>><span>Annuler</span></button>
                                <button type="submit" id="categorySubmitButton" class="<?= h($createBtnClass) ?>" <?= $isEditable ? '' : 'disabled' ?>><i id="categorySubmitIcon" class="fas fa-plus" aria-hidden="true"></i><span id="categorySubmitLabel">Créer</span></button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="cm-collapse is-open" id="collapseItem">
                <button type="button" class="cm-collapse__trigger" onclick="toggleCollapse('collapseItem')">
                    <span class="cm-collapse__trigger-label"><i class="fas fa-file-alt" aria-hidden="true"></i>Sous-menu / Écran (Fonctionnalité)<span class="cm-text-muted cm-text-xs">fonctionnalites</span></span>
                    <i class="fas fa-chevron-down cm-collapse__trigger-chevron" aria-hidden="true"></i>
                </button>
                <div class="cm-collapse__body">
                    <form method="POST" action="<?= h($formAction) ?>" class="cm-grid-2" id="itemForm">
                        <?php cm_component('form/csrf-token'); ?>
                        <input type="hidden" name="op" id="itemOp" value="create_fonctionnalite">
                        <input type="hidden" name="id_fonctionnalite" id="editItemId" value="">
                        <div class="cm-form-group" style="grid-column: 1 / -1"><div id="itemEditNotice" class="cm-text-muted cm-text-sm cm-hidden">Mode modification actif. Le code reste en lecture seule.</div></div>
                        <div class="cm-form-group"><label class="cm-form-label">Catégorie <span class="cm-required-star">*</span></label><select id="itemCategory" name="id_categorie" class="cm-form-control" required <?= $isEditable ? '' : 'disabled' ?>><option value="">-- Choisir --</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c->id_categorie ?>"><?= h((string) $c->lib_categorie) ?></option><?php endforeach; ?></select></div>
                        <div class="cm-form-group"><label class="cm-form-label">Type</label><select id="typeItem" name="type_item" class="cm-form-control" <?= $isEditable ? '' : 'disabled' ?>><option value="parent">Sous-menu</option><option value="child">Écran</option></select></div>
                        <div class="cm-form-group" id="parentPicker" style="display:none; grid-column: 1 / -1"><label class="cm-form-label">Parent (code sous-menu)</label><input id="page_parente" name="page_parente" class="cm-form-control" placeholder="SCOL_INSCRIPTIONS" <?= $isEditable ? '' : 'disabled' ?>><span class="cm-form-hint">Astuce : le parent est le <strong>code_fonctionnalite</strong> du sous-menu.</span></div>
                        <div class="cm-form-group"><label class="cm-form-label">Code (unique) <span class="cm-required-star">*</span></label><input id="code_fonctionnalite" name="code_fonctionnalite" class="cm-form-control" placeholder="SCOL_INSCRIPTIONS" required <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group"><label class="cm-form-label">Libellé (menu) <span class="cm-required-star">*</span></label><input id="label_fonctionnalite" name="label_fonctionnalite" class="cm-form-control" placeholder="Inscriptions" required <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group"><label class="cm-form-label">Libellé interne</label><input id="lib_fonctionnalite" name="lib_fonctionnalite" class="cm-form-control" placeholder="Inscriptions" <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group"><label class="cm-form-label">Description</label><input id="description_fonctionnalite" name="description_fonctionnalite" class="cm-form-control" placeholder="Description de la fonctionnalité..." <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group" style="grid-column: 1 / -1"><label class="cm-form-label">URL</label><input id="url_fonctionnalite" name="url_fonctionnalite" class="cm-form-control" placeholder="?page=parametres_generaux&action=gestion_attribution" <?= $isEditable ? '' : 'disabled' ?>><span class="cm-form-hint">Pour un sous-menu, tu peux laisser <code>#</code> (non cliquable).</span></div>
                        <div class="cm-form-group"><label class="cm-form-label">Icône</label><div class="cm-flex cm-flex-gap-xs"><input id="icone_fonctionnalite" name="icone_fonctionnalite" class="cm-form-control" placeholder="fas fa-folder-open" oninput="updateItemIconPreview(this.value)" <?= $isEditable ? '' : 'disabled' ?>><div class="cm-flex cm-align-center cm-justify-center" style="min-width: 40px; background: var(--cm-table-header-bg); border: 1px solid var(--cm-input-border); border-radius: var(--cm-border-radius);"><i id="itemIconPreview" class="fas fa-question" aria-hidden="true" style="color: var(--cm-primary); font-size: 1rem;"></i></div></div></div>
                        <div class="cm-form-group"><label class="cm-form-label">Ordre</label><input id="ordre_fonctionnalite" name="ordre_fonctionnalite" type="number" class="cm-form-control" value="0" <?= $isEditable ? '' : 'disabled' ?>></div>
                        <div class="cm-form-group" style="grid-column: 1 / -1"><label class="cm-flex cm-flex-gap-sm cm-align-center cm-text-sm"><input id="itemActif" type="checkbox" name="actif" value="1" checked <?= $isEditable ? '' : 'disabled' ?>>Actif</label></div>
                        <div class="cm-flex-end cm-flex-gap-sm" style="grid-column: 1 / -1">
                            <button type="button" id="itemCancelButton" class="cm-btn is-light is-sm cm-hidden" <?= $isEditable ? '' : 'disabled' ?>><span>Annuler</span></button>
                            <button type="submit" id="itemSubmitButton" class="<?= h($createBtnClass) ?>" <?= $isEditable ? '' : 'disabled' ?>><i id="itemSubmitIcon" class="fas fa-plus" aria-hidden="true"></i><span id="itemSubmitLabel">Créer</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php cm_component('crud/form-pole', ['title' => '', 'icon' => 'fa-plus-circle', 'content' => (string) ob_get_clean()]); ?>

        <div class="cm-text-muted cm-mb-sm">(Parent: <code>est_sous_page=0</code>, Enfant: <code>est_sous_page=1</code> + <code>page_parente</code>)</div>
        <?php cm_toolbar(['screen' => 'gestion_menus', 'id_prefix' => 'cmMenuToolbar', 'search_placeholder' => 'Rechercher un menu, code, libellé...', 'show_actions' => false, 'show_filters' => false, 'custom_actions' => !$isEditable ? [['tag' => 'button', 'type' => 'button', 'label' => 'Lecture seule', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['disabled' => 'disabled']]] : []]); ?>

        <div class="cm-pole-inferieur"><div class="cm-table-wrapper"><table class="cm-data-table" id="cmMenuTree"><thead><tr><th class="cm-data-table__th">Type</th><th class="cm-data-table__th">Code</th><th class="cm-data-table__th">Libellé</th><th class="cm-data-table__th">URL</th><th class="cm-data-table__th is-center">Ordre</th><th class="cm-data-table__th is-center">Statut</th><th class="cm-data-table__th is-right">Actions</th></tr></thead><tbody>
        <?php foreach ($tree as $cid => $node): $c = $node['categorie']; ?>
            <tr class="cm-tree-header"><td class="cm-data-table__td" colspan="7"><span class="cm-flex cm-flex-gap-sm cm-align-center"><i class="<?= h((string) ($c->icone_categorie ?? 'fas fa-folder')) ?>" aria-hidden="true"></i><?= h((string) $c->lib_categorie) ?><span class="cm-text-muted cm-text-xs">(<?= h((string) $c->code_categorie) ?>)</span></span></td></tr>
            <tr class="cm-data-table__row">
                <td class="cm-data-table__td"><?php cm_component('ui/badge', ['text' => 'Menu', 'type' => 'primary']); ?></td>
                <td class="cm-data-table__td"><?= h((string) $c->code_categorie) ?></td>
                <td class="cm-data-table__td cm-text-semibold"><?= h((string) $c->lib_categorie) ?></td>
                <td class="cm-data-table__td cm-text-muted">—</td>
                <td class="cm-data-table__td is-center"><?= (int) ($c->ordre_categorie ?? 0) ?></td>
                <td class="cm-data-table__td is-center"><?php cm_component('ui/badge', ['text' => !empty($c->actif) ? 'Actif' : 'Inactif', 'type' => !empty($c->actif) ? 'success' : 'danger']); ?></td>
                <td class="cm-data-table__td is-right"><div class="cm-table-actions">
                    <?php if (!empty($c->actif)) { renderToggleForm($formAction, 'deactivate_category', 'id_categorie', (int) $c->id_categorie, 'Désactiver cette catégorie et ses fonctionnalités ?', 'danger', 'Désactiver', 'cm-btn is-danger is-sm', 'fas fa-ban', 'Désactiver', !$isEditable); } else { renderToggleForm($formAction, 'activate_category', 'id_categorie', (int) $c->id_categorie, 'Réactiver cette catégorie et ses fonctionnalités ?', 'warning', 'Réactiver', 'cm-btn is-success is-sm', 'fas fa-check-circle', 'Réactiver', !$isEditable); } ?>
                    <?php if ($isEditable): ?><button type="button" class="cm-btn is-info is-sm me-2" onclick="editCategory(<?= (int) $c->id_categorie ?>, <?= js((string) $c->code_categorie) ?>, <?= js((string) $c->lib_categorie) ?>, <?= js((string) ($c->description_categorie ?? '')) ?>, <?= js((string) ($c->icone_categorie ?? '')) ?>, <?= (int) ($c->ordre_categorie ?? 0) ?>, <?= !empty($c->actif) ? 'true' : 'false' ?>)"><i class="fas fa-pen" aria-hidden="true"></i><span>Modifier</span></button><?php endif; ?>
                </div></td>
            </tr>
            <?php foreach (($node['parents'] ?? []) as $p):
                $pid = (int) ($p->id_fonctionnalite ?? 0); $pcode = (string) ($p->code_fonctionnalite ?? ''); $plabel = (string) ($p->label_fonctionnalite ?? $p->lib_fonctionnalite ?? $pcode); $plib = (string) ($p->lib_fonctionnalite ?? $plabel); $pdesc = (string) ($p->description_fonctionnalite ?? ''); $purl = (string) ($p->url_fonctionnalite ?? '#'); $pordre = (int) ($p->ordre_fonctionnalite ?? 0); $picon = (string) ($p->icone_fonctionnalite ?? 'fas fa-folder'); $children = is_array($p->children ?? null) ? $p->children : []; $groupId = 'grp-' . md5((string) $cid . '|' . $pcode . '|' . (string) $pid);
            ?>
                <tr class="cm-data-table__row">
                    <td class="cm-data-table__td"><span class="cm-flex cm-flex-gap-sm cm-align-center"><?php if ($children): ?><button type="button" class="cm-tree-toggle toggleRow" data-target="<?= h($groupId) ?>" aria-label="Déplier/Replier"><span class="toggleIcon">+</span></button><?php else: ?><span class="cm-tree-toggle is-empty">-</span><?php endif; ?><?php cm_component('ui/badge', ['text' => 'Sous-menu', 'type' => 'light']); ?></span></td>
                    <td class="cm-data-table__td"><?= h($pcode) ?></td>
                    <td class="cm-data-table__td cm-text-semibold"><?= h($plabel) ?></td>
                    <td class="cm-data-table__td cm-text-muted"><?= h($purl) ?></td>
                    <td class="cm-data-table__td is-center"><?= $pordre ?></td>
                    <td class="cm-data-table__td is-center"><?php cm_component('ui/badge', ['text' => !empty($p->actif) ? 'Actif' : 'Inactif', 'type' => !empty($p->actif) ? 'success' : 'danger']); ?></td>
                    <td class="cm-data-table__td is-right"><div class="cm-table-actions">
                        <?php if (!empty($p->actif)) { renderToggleForm($formAction, 'deactivate_fonctionnalite', 'id_fonctionnalite', $pid, 'Désactiver cet élément ?', 'danger', 'Désactiver', 'cm-btn is-danger is-sm', 'fas fa-ban', 'Désactiver', !$isEditable); } else { renderToggleForm($formAction, 'activate_fonctionnalite', 'id_fonctionnalite', $pid, 'Réactiver cet élément ?', 'warning', 'Réactiver', 'cm-btn is-success is-sm', 'fas fa-check-circle', 'Réactiver', !$isEditable); } ?>
                        <?php if ($isEditable): ?><button type="button" class="cm-btn is-info is-sm me-2" onclick="editItem(<?= $pid ?>, <?= (int) $p->id_categorie ?>, 'parent', <?= js($pcode) ?>, <?= js($plabel) ?>, <?= js($plib) ?>, <?= js($pdesc) ?>, <?= js($purl) ?>, <?= js($picon) ?>, <?= $pordre ?>, <?= js('') ?>, <?= !empty($p->actif) ? 'true' : 'false' ?>)"><i class="fas fa-pen" aria-hidden="true"></i><span>Modifier</span></button><?php endif; ?>
                    </div></td>
                </tr>
                <?php foreach ($children as $ch):
                    $chid = (int) ($ch->id_fonctionnalite ?? 0); $chcode = (string) ($ch->code_fonctionnalite ?? ''); $chlabel = (string) ($ch->label_fonctionnalite ?? $ch->lib_fonctionnalite ?? $chcode); $chlib = (string) ($ch->lib_fonctionnalite ?? $chlabel); $chdesc = (string) ($ch->description_fonctionnalite ?? ''); $churl = (string) ($ch->url_fonctionnalite ?? '#'); $chordre = (int) ($ch->ordre_fonctionnalite ?? 0); $chicon = (string) ($ch->icone_fonctionnalite ?? 'fas fa-desktop');
                ?>
                    <tr class="cm-data-table__row childRow cm-hidden" data-parent="<?= h($groupId) ?>">
                        <td class="cm-data-table__td cm-tree-indent"><?php cm_component('ui/badge', ['text' => 'Écran', 'type' => 'info']); ?></td>
                        <td class="cm-data-table__td"><?= h($chcode) ?></td>
                        <td class="cm-data-table__td"><?= h($chlabel) ?></td>
                        <td class="cm-data-table__td cm-text-muted"><?= h($churl) ?></td>
                        <td class="cm-data-table__td is-center"><?= $chordre ?></td>
                        <td class="cm-data-table__td is-center"><?php cm_component('ui/badge', ['text' => !empty($ch->actif) ? 'Actif' : 'Inactif', 'type' => !empty($ch->actif) ? 'success' : 'danger']); ?></td>
                        <td class="cm-data-table__td is-right"><div class="cm-table-actions">
                            <?php if (!empty($ch->actif)) { renderToggleForm($formAction, 'deactivate_fonctionnalite', 'id_fonctionnalite', $chid, 'Désactiver cet écran ?', 'danger', 'Désactiver', 'cm-btn is-danger is-sm', 'fas fa-ban', 'Désactiver', !$isEditable); } else { renderToggleForm($formAction, 'activate_fonctionnalite', 'id_fonctionnalite', $chid, 'Réactiver cet écran ?', 'warning', 'Réactiver', 'cm-btn is-success is-sm', 'fas fa-check-circle', 'Réactiver', !$isEditable); } ?>
                            <?php if ($isEditable): ?><button type="button" class="cm-btn is-info is-sm me-2" onclick="editItem(<?= $chid ?>, <?= (int) $ch->id_categorie ?>, 'child', <?= js($chcode) ?>, <?= js($chlabel) ?>, <?= js($chlib) ?>, <?= js($chdesc) ?>, <?= js($churl) ?>, <?= js($chicon) ?>, <?= $chordre ?>, <?= js((string) ($ch->page_parente ?? '')) ?>, <?= !empty($ch->actif) ? 'true' : 'false' ?>)"><i class="fas fa-pen" aria-hidden="true"></i><span>Modifier</span></button><?php endif; ?>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
        </tbody></table></div></div>
    </div>
</section>

<script>
(function () {
    var categoryForm = document.getElementById('categoryForm');
    var categoryOp = document.getElementById('categoryOp');
    var categoryId = document.getElementById('editCategoryId');
    var categoryCode = document.getElementById('code_categorie');
    var categorySubmitButton = document.getElementById('categorySubmitButton');
    var categorySubmitIcon = document.getElementById('categorySubmitIcon');
    var categorySubmitLabel = document.getElementById('categorySubmitLabel');
    var categoryCancelButton = document.getElementById('categoryCancelButton');
    var categoryEditNotice = document.getElementById('categoryEditNotice');
    var itemForm = document.getElementById('itemForm');
    var itemOp = document.getElementById('itemOp');
    var itemId = document.getElementById('editItemId');
    var itemType = document.getElementById('typeItem');
    var itemParentWrap = document.getElementById('parentPicker');
    var itemCode = document.getElementById('code_fonctionnalite');
    var itemSubmitButton = document.getElementById('itemSubmitButton');
    var itemSubmitIcon = document.getElementById('itemSubmitIcon');
    var itemSubmitLabel = document.getElementById('itemSubmitLabel');
    var itemCancelButton = document.getElementById('itemCancelButton');
    var itemEditNotice = document.getElementById('itemEditNotice');
    var createBtnClass = <?= json_encode($createBtnClass, JSON_UNESCAPED_UNICODE) ?>;
    var editBtnClass = <?= json_encode($editBtnClass, JSON_UNESCAPED_UNICODE) ?>;
    window.toggleCollapse = function (id) { var el = document.getElementById(id); if (el) { el.classList.toggle('is-open'); } };
    window.updateItemIconPreview = function (value) { var preview = document.getElementById('itemIconPreview'); if (preview) { preview.className = value && value.trim() !== '' ? value : 'fas fa-question'; } };
    function setEditState(form, notice, cancelBtn, submitBtn, submitIcon, submitLabel, isEditing) { if (form) { form.style.borderLeft = isEditing ? '4px solid var(--cm-warning)' : ''; } if (notice) { notice.classList.toggle('cm-hidden', !isEditing); } if (cancelBtn) { cancelBtn.classList.toggle('cm-hidden', !isEditing); } if (submitBtn) { submitBtn.className = isEditing ? editBtnClass : createBtnClass; } if (submitIcon) { submitIcon.className = isEditing ? 'fas fa-save' : 'fas fa-plus'; } if (submitLabel) { submitLabel.textContent = isEditing ? 'Mettre à jour' : 'Créer'; } }
    function syncItemParent() { if (itemType && itemParentWrap) { itemParentWrap.style.display = itemType.value === 'child' ? '' : 'none'; } }
    function resetCategoryForm() { if (!categoryForm) { return; } categoryForm.reset(); categoryOp.value = 'create_category'; categoryId.value = ''; categoryCode.readOnly = false; categoryCode.removeAttribute('aria-readonly'); setEditState(categoryForm, categoryEditNotice, categoryCancelButton, categorySubmitButton, categorySubmitIcon, categorySubmitLabel, false); }
    function resetItemForm() { if (!itemForm) { return; } itemForm.reset(); itemOp.value = 'create_fonctionnalite'; itemId.value = ''; itemCode.readOnly = false; itemCode.removeAttribute('aria-readonly'); syncItemParent(); window.updateItemIconPreview(''); setEditState(itemForm, itemEditNotice, itemCancelButton, itemSubmitButton, itemSubmitIcon, itemSubmitLabel, false); }
    window.editCategory = function (id, code, lib, desc, icon, ordre, actif) { categoryOp.value = 'update_category'; categoryId.value = String(id); categoryCode.value = code || ''; categoryCode.readOnly = true; categoryCode.setAttribute('aria-readonly', 'true'); document.getElementById('lib_categorie').value = lib || ''; document.getElementById('description_categorie').value = desc || ''; document.getElementById('icone_categorie').value = icon || ''; document.getElementById('ordre_categorie').value = String(ordre || 0); document.getElementById('categoryActif').checked = !!actif; document.getElementById('collapseCategory').classList.add('is-open'); setEditState(categoryForm, categoryEditNotice, categoryCancelButton, categorySubmitButton, categorySubmitIcon, categorySubmitLabel, true); categoryForm.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
    window.editItem = function (id, idCategorie, type, code, label, lib, desc, url, icon, ordre, parent, actif) { itemOp.value = 'update_fonctionnalite'; itemId.value = String(id); document.getElementById('itemCategory').value = String(idCategorie || ''); itemType.value = type || 'parent'; itemCode.value = code || ''; itemCode.readOnly = true; itemCode.setAttribute('aria-readonly', 'true'); document.getElementById('label_fonctionnalite').value = label || ''; document.getElementById('lib_fonctionnalite').value = lib || label || ''; document.getElementById('description_fonctionnalite').value = desc || ''; document.getElementById('url_fonctionnalite').value = url || ''; document.getElementById('icone_fonctionnalite').value = icon || ''; document.getElementById('ordre_fonctionnalite').value = String(ordre || 0); document.getElementById('page_parente').value = parent || ''; document.getElementById('itemActif').checked = !!actif; document.getElementById('collapseItem').classList.add('is-open'); syncItemParent(); window.updateItemIconPreview(icon || ''); setEditState(itemForm, itemEditNotice, itemCancelButton, itemSubmitButton, itemSubmitIcon, itemSubmitLabel, true); itemForm.scrollIntoView({ behavior: 'smooth', block: 'start' }); };
    if (categoryCancelButton) { categoryCancelButton.addEventListener('click', resetCategoryForm); }
    if (itemCancelButton) { itemCancelButton.addEventListener('click', resetItemForm); }
    if (itemType) { itemType.addEventListener('change', syncItemParent); }
    resetCategoryForm(); resetItemForm();
    document.addEventListener('click', function (e) { var btn = e.target && e.target.closest ? e.target.closest('.toggleRow') : null; if (!btn) { return; } var target = btn.getAttribute('data-target'); var rows = target ? document.querySelectorAll('tr.childRow[data-parent="' + target + '"]') : []; var icon = btn.querySelector('.toggleIcon'); var isHidden = rows.length > 0 ? rows[0].classList.contains('cm-hidden') : true; rows.forEach(function (row) { row.classList.toggle('cm-hidden', !isHidden); }); if (icon) { icon.textContent = isHidden ? '-' : '+'; } });
})();
</script>


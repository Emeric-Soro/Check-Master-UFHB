<?php
$categories = $GLOBALS['menuMgmtCategories'] ?? [];
$tree = $GLOBALS['menuMgmtTree'] ?? [];
$isEditable = $GLOBALS['menuMgmtIsEditable'] ?? (function_exists('canEdit') ? (bool)canEdit() : true);
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
$csrfToken = \CheckMaster\Core\Csrf::token();

function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function js(string $s): string
{
    // JSON string literal safe for HTML attributes
    return htmlspecialchars(json_encode($s, JSON_UNESCAPED_UNICODE), ENT_QUOTES, 'UTF-8');
}

function isChecked($v): string
{
    return !empty($v) ? 'checked' : '';
}
?>

<div class="container mx-auto px-4 py-8">
    <div class="flex items-start justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                <i class="fas fa-sitemap mr-3 text-emerald-600"></i>
                Gestion des menus
            </h1>
            <p class="text-gray-600 mt-2">Créer / modifier / désactiver les menus, sous-menus et écrans (tables <code>categories_fonctionnalites</code> / <code>fonctionnalites</code>).</p>
        </div>
    </div>

    <?php if ($messageSuccess): ?>
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800">
            <i class="fas fa-check-circle mr-2"></i><?= h($messageSuccess) ?>
        </div>
    <?php endif; ?>
    <?php if ($messageErreur): ?>
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-800">
            <i class="fas fa-exclamation-circle mr-2"></i><?= h($messageErreur) ?>
        </div>
    <?php endif; ?>

    <!-- Layout vertical: formulaire puis liste en dessous -->
    <div class="grid grid-cols-1 gap-6">
        <!-- Création / Ajout -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <div class="font-semibold text-gray-800">
                    <i class="fas fa-plus-circle text-emerald-600 mr-2"></i>
                    Ajouter
                </div>
                <?php if (!$isEditable): ?>
                    <span class="text-xs font-semibold text-gray-500">Lecture seule</span>
                <?php endif; ?>
            </div>

            <div class="p-4 space-y-5">
                <details class="rounded-lg border border-gray-200 p-4" open>
                    <summary class="cursor-pointer font-semibold text-gray-800">
                        Menu (Catégorie)
                        <span class="text-xs text-gray-500 font-normal ml-2">categories_fonctionnalites</span>
                    </summary>
                    <form method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="op" value="create_category">

                        <div>
                            <label class="text-sm font-semibold text-gray-700">Code (unique)</label>
                            <input name="code_categorie" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="SCOLARITE" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Libellé</label>
                            <input name="lib_categorie" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Gestion de la scolarité" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-semibold text-gray-700">Description</label>
                            <input name="description_categorie" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Description..." <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Icône (FontAwesome)</label>
                            <input name="icone_categorie" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="fas fa-school" <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Ordre</label>
                            <input name="ordre_categorie" type="number" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" value="0" <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="md:col-span-2 flex items-center justify-between">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="actif" value="1" checked <?= $isEditable ? '' : 'disabled' ?>>
                                Actif
                            </label>
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                Créer
                            </button>
                        </div>
                    </form>
                </details>

                <details class="rounded-lg border border-gray-200 p-4">
                    <summary class="cursor-pointer font-semibold text-gray-800">
                        Sous-menu / Écran (Fonctionnalité)
                        <span class="text-xs text-gray-500 font-normal ml-2">fonctionnalites</span>
                    </summary>

                    <form method="POST" class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-3" id="createItemForm">
                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                        <input type="hidden" name="op" value="create_fonctionnalite">

                        <div>
                            <label class="text-sm font-semibold text-gray-700">Catégorie</label>
                            <select name="id_categorie" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required <?= $isEditable ? '' : 'disabled' ?>>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($categories as $c): ?>
                                    <option value="<?= (int)$c->id_categorie ?>"><?= h((string)$c->lib_categorie) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Type</label>
                            <select name="type_item" id="typeItem" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                                <option value="parent">Sous-menu</option>
                                <option value="child">Écran</option>
                            </select>
                        </div>

                        <div class="md:col-span-2" id="parentPicker" style="display:none;">
                            <label class="text-sm font-semibold text-gray-700">Parent (code sous-menu)</label>
                            <input name="page_parente" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="SCOL_INSCRIPTIONS" <?= $isEditable ? '' : 'disabled' ?>>
                            <p class="text-xs text-gray-500 mt-1">Astuce: le parent est le <b>code_fonctionnalite</b> du sous-menu.</p>
                        </div>

                        <div>
                            <label class="text-sm font-semibold text-gray-700">Code (unique)</label>
                            <input name="code_fonctionnalite" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="SCOL_INSCRIPTIONS" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Libellé (menu)</label>
                            <input name="label_fonctionnalite" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="Inscriptions" required <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="md:col-span-2">
                            <label class="text-sm font-semibold text-gray-700">URL</label>
                            <input name="url_fonctionnalite" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="?page=parametres_generaux&action=gestion_attribution" <?= $isEditable ? '' : 'disabled' ?>>
                            <p class="text-xs text-gray-500 mt-1">Pour un sous-menu, tu peux laisser <code>#</code> (non cliquable).</p>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Icône</label>
                            <input name="icone_fonctionnalite" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" placeholder="fas fa-folder-open" <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div>
                            <label class="text-sm font-semibold text-gray-700">Ordre</label>
                            <input name="ordre_fonctionnalite" type="number" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" value="0" <?= $isEditable ? '' : 'disabled' ?>>
                        </div>
                        <div class="md:col-span-2">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                                <input type="checkbox" name="actif" value="1" checked <?= $isEditable ? '' : 'disabled' ?>>
                                Actif
                            </label>
                        </div>

                        <div class="md:col-span-2 flex justify-end">
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                Créer
                            </button>
                        </div>
                    </form>
                </details>
            </div>
        </div>

        <!-- Consultation / Edition (en dessous) -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <div class="font-semibold text-gray-800">
                    <i class="fas fa-list text-indigo-600 mr-2"></i>
                    Liste & hiérarchie
                </div>
                <div class="text-xs text-gray-500">
                    (Parent: <code>est_sous_page=0</code>, Enfant: <code>est_sous_page=1</code> + <code>page_parente</code>)
                </div>
            </div>

            <div class="p-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Type</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Code</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Libellé</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">URL</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Ordre</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Statut</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($tree as $cid => $node): ?>
                            <?php $c = $node['categorie']; ?>
                            <tr class="bg-gray-100">
                                <td colspan="7" class="px-4 py-2 text-sm font-semibold text-gray-700">
                                    <i class="<?= h((string)($c->icone_categorie ?? 'fas fa-folder')) ?> text-gray-700 mr-2"></i>
                                    <?= h((string)$c->lib_categorie) ?>
                                    <span class="ml-2 text-xs text-gray-500">(<?= h((string)$c->code_categorie) ?>)</span>
                                </td>
                            </tr>

                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-2 py-0.5 text-xs font-semibold text-indigo-700">
                                        <i class="fas fa-layer-group"></i>Menu
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= h((string)$c->code_categorie) ?></td>
                                <td class="px-4 py-3 text-sm font-semibold text-gray-900"><?= h((string)$c->lib_categorie) ?></td>
                                <td class="px-4 py-3 text-sm text-gray-500">—</td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= (int)($c->ordre_categorie ?? 0) ?></td>
                                <td class="px-4 py-3 text-sm">
                                    <?php if (!empty($c->actif)): ?>
                                        <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Actif</span>
                                    <?php else: ?>
                                        <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Inactif</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-right text-sm">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 hover:bg-gray-50"
                                                onclick="openEditCategory(<?= (int)$c->id_categorie ?>, <?= js((string)$c->lib_categorie) ?>, <?= js((string)($c->description_categorie ?? '')) ?>, <?= js((string)($c->icone_categorie ?? '')) ?>, <?= (int)($c->ordre_categorie ?? 0) ?>, <?= (int)($c->actif ?? 0) ?>)"
                                                <?= $isEditable ? '' : 'disabled' ?>>
                                            Modifier
                                        </button>

                                        <?php if (!empty($c->actif)): ?>
                                            <form method="POST" onsubmit="return confirm('Désactiver cette catégorie et ses fonctionnalités ?');" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="op" value="deactivate_category">
                                                <input type="hidden" name="id_categorie" value="<?= (int)$c->id_categorie ?>">
                                                <button class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 text-red-700 hover:bg-red-50 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                                    Désactiver
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" onsubmit="return confirm('Réactiver cette catégorie et ses fonctionnalités ?');" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                <input type="hidden" name="op" value="activate_category">
                                                <input type="hidden" name="id_categorie" value="<?= (int)$c->id_categorie ?>">
                                                <button class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                                    Réactiver
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <?php $parents = $node['parents'] ?? []; ?>
                            <?php foreach ($parents as $p): ?>
                                <?php
                                $pid = (int)($p->id_fonctionnalite ?? 0);
                                $pcode = (string)($p->code_fonctionnalite ?? '');
                                $plabel = (string)($p->label_fonctionnalite ?? $p->lib_fonctionnalite ?? $pcode);
                                $purl = (string)($p->url_fonctionnalite ?? '#');
                                $pordre = (int)($p->ordre_fonctionnalite ?? 0);
                                $children = (isset($p->children) && is_array($p->children)) ? $p->children : [];
                                $hasChildren = !empty($children);
                                $groupId = 'grp-' . md5((string)$cid . '|' . $pcode . '|' . (string)$pid);
                                ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-3 text-sm text-gray-700">
                                        <div class="flex items-center gap-2">
                                            <?php if ($hasChildren): ?>
                                                <button type="button" class="toggleRow inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-blue-700 border border-blue-200"
                                                    data-target="<?= h($groupId) ?>" aria-label="Déplier/Replier">
                                                    <span class="toggleIcon">+</span>
                                                </button>
                                            <?php else: ?>
                                                <span class="inline-flex h-7 w-7 items-center justify-center rounded-full bg-gray-50 text-gray-300 border border-gray-200">-</span>
                                            <?php endif; ?>
                                            <span class="inline-flex items-center gap-2 rounded-full bg-slate-50 px-2 py-0.5 text-xs font-semibold text-slate-700">
                                                <i class="fas fa-folder"></i>Sous-menu
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= h($pcode) ?></td>
                                    <td class="px-4 py-3 text-sm font-semibold text-gray-900"><?= h($plabel) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-600"><?= h($purl) ?></td>
                                    <td class="px-4 py-3 text-sm text-gray-700"><?= $pordre ?></td>
                                    <td class="px-4 py-3 text-sm">
                                        <?php if (!empty($p->actif)): ?>
                                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Actif</span>
                                        <?php else: ?>
                                            <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Inactif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-right text-sm">
                                        <div class="inline-flex items-center gap-2">
                                            <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 hover:bg-gray-50"
                                                onclick="openEditItem(<?= (int)$pid ?>, <?= (int)$cid ?>, 'parent', <?= js((string)($p->lib_fonctionnalite ?? '')) ?>, <?= js((string)($p->label_fonctionnalite ?? '')) ?>, <?= js((string)($p->description_fonctionnalite ?? '')) ?>, <?= js((string)($p->url_fonctionnalite ?? '#')) ?>, <?= js((string)($p->icone_fonctionnalite ?? '')) ?>, <?= (int)($p->ordre_fonctionnalite ?? 0) ?>, <?= js('') ?>, <?= (int)($p->actif ?? 0) ?>)"
                                                <?= $isEditable ? '' : 'disabled' ?>>
                                                Modifier
                                            </button>

                                            <?php if (!empty($p->actif)): ?>
                                                <form method="POST" onsubmit="return confirm('Désactiver cet élément ?');" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                    <input type="hidden" name="op" value="deactivate_fonctionnalite">
                                                    <input type="hidden" name="id_fonctionnalite" value="<?= (int)$pid ?>">
                                                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 text-red-700 hover:bg-red-50 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                                        Désactiver
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <form method="POST" onsubmit="return confirm('Réactiver cet élément ?');" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                    <input type="hidden" name="op" value="activate_fonctionnalite">
                                                    <input type="hidden" name="id_fonctionnalite" value="<?= (int)$pid ?>">
                                                    <button class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                                        Réactiver
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>

                                <?php foreach ($children as $ch): ?>
                                    <?php
                                    $chid = (int)($ch->id_fonctionnalite ?? 0);
                                    $chcode = (string)($ch->code_fonctionnalite ?? '');
                                    $chlabel = (string)($ch->label_fonctionnalite ?? $ch->lib_fonctionnalite ?? $chcode);
                                    $churl = (string)($ch->url_fonctionnalite ?? '#');
                                    $chordre = (int)($ch->ordre_fonctionnalite ?? 0);
                                    ?>
                                    <tr class="childRow hidden hover:bg-gray-50" data-parent="<?= h($groupId) ?>">
                                        <td class="px-4 py-3 text-sm text-gray-700">
                                            <span class="inline-flex items-center gap-2 rounded-full bg-cyan-50 px-2 py-0.5 text-xs font-semibold text-cyan-800 ml-10">
                                                <i class="fas fa-file-alt"></i>Écran
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?= h($chcode) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-900"><?= h($chlabel) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-600"><?= h($churl) ?></td>
                                        <td class="px-4 py-3 text-sm text-gray-700"><?= $chordre ?></td>
                                        <td class="px-4 py-3 text-sm">
                                            <?php if (!empty($ch->actif)): ?>
                                                <span class="inline-flex items-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700">Actif</span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-semibold text-red-700">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3 text-right text-sm">
                                            <div class="inline-flex items-center gap-2">
                                                <button type="button" class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-gray-300 hover:bg-gray-50"
                                                    onclick="openEditItem(<?= (int)$chid ?>, <?= (int)$cid ?>, 'child', <?= js((string)($ch->lib_fonctionnalite ?? '')) ?>, <?= js((string)($ch->label_fonctionnalite ?? '')) ?>, <?= js((string)($ch->description_fonctionnalite ?? '')) ?>, <?= js((string)($ch->url_fonctionnalite ?? '#')) ?>, <?= js((string)($ch->icone_fonctionnalite ?? '')) ?>, <?= (int)($ch->ordre_fonctionnalite ?? 0) ?>, <?= js((string)($ch->page_parente ?? '')) ?>, <?= (int)($ch->actif ?? 0) ?>)"
                                                    <?= $isEditable ? '' : 'disabled' ?>>
                                                    Modifier
                                                </button>
                                                <?php if (!empty($ch->actif)): ?>
                                                    <form method="POST" onsubmit="return confirm('Désactiver cet écran ?');" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                        <input type="hidden" name="op" value="deactivate_fonctionnalite">
                                                        <input type="hidden" name="id_fonctionnalite" value="<?= (int)$chid ?>">
                                                        <button class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-red-200 text-red-700 hover:bg-red-50 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                                            Désactiver
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="POST" onsubmit="return confirm('Réactiver cet écran ?');" class="inline">
                                                        <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                                                        <input type="hidden" name="op" value="activate_fonctionnalite">
                                                        <input type="hidden" name="id_fonctionnalite" value="<?= (int)$chid ?>">
                                                        <button class="px-3 py-1.5 text-xs font-semibold rounded-lg border border-emerald-200 text-emerald-700 hover:bg-emerald-50 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                                                            Réactiver
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

    <!-- Modale: edit category -->
    <div id="editCategoryModal" class="fixed inset-0 hidden items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/40" onclick="closeModal('editCategoryModal')"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <div class="font-semibold text-gray-800">Modifier une catégorie</div>
                <button class="text-gray-400 hover:text-gray-600" onclick="closeModal('editCategoryModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="op" value="update_category">
                <input type="hidden" name="id_categorie" id="editCatId">

                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Libellé</label>
                    <input name="lib_categorie" id="editCatLib" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Description</label>
                    <input name="description_categorie" id="editCatDesc" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700">Icône</label>
                    <input name="icone_categorie" id="editCatIcon" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700">Ordre</label>
                    <input name="ordre_categorie" id="editCatOrdre" type="number" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div class="md:col-span-2 flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="actif" id="editCatActif" value="1" <?= $isEditable ? '' : 'disabled' ?>>
                        Actif
                    </label>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modale: edit fonctionnalite -->
    <div id="editItemModal" class="fixed inset-0 hidden items-center justify-center z-50">
        <div class="absolute inset-0 bg-black/40" onclick="closeModal('editItemModal')"></div>
        <div class="relative bg-white rounded-xl shadow-xl w-full max-w-2xl mx-4 overflow-hidden">
            <div class="p-4 border-b border-gray-100 flex items-center justify-between">
                <div class="font-semibold text-gray-800">Modifier un sous-menu / écran</div>
                <button class="text-gray-400 hover:text-gray-600" onclick="closeModal('editItemModal')"><i class="fas fa-times"></i></button>
            </div>
            <form method="POST" class="p-4 grid grid-cols-1 md:grid-cols-2 gap-3">
                <input type="hidden" name="csrf_token" value="<?= h($csrfToken) ?>">
                <input type="hidden" name="op" value="update_fonctionnalite">
                <input type="hidden" name="id_fonctionnalite" id="editItemId">

                <div>
                    <label class="text-sm font-semibold text-gray-700">Catégorie</label>
                    <select name="id_categorie" id="editItemCat" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required <?= $isEditable ? '' : 'disabled' ?>>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c->id_categorie ?>"><?= h((string)$c->lib_categorie) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700">Type</label>
                    <select name="type_item" id="editItemType" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                        <option value="parent">Sous-menu</option>
                        <option value="child">Écran</option>
                    </select>
                </div>

                <div class="md:col-span-2" id="editItemParentWrap" style="display:none;">
                    <label class="text-sm font-semibold text-gray-700">Parent (code sous-menu)</label>
                    <input name="page_parente" id="editItemParent" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>

                <div>
                    <label class="text-sm font-semibold text-gray-700">Libellé (menu)</label>
                    <input name="label_fonctionnalite" id="editItemLabel" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" required <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700">Libellé interne</label>
                    <input name="lib_fonctionnalite" id="editItemLib" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">Description</label>
                    <input name="description_fonctionnalite" id="editItemDesc" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div class="md:col-span-2">
                    <label class="text-sm font-semibold text-gray-700">URL</label>
                    <input name="url_fonctionnalite" id="editItemUrl" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700">Icône</label>
                    <input name="icone_fonctionnalite" id="editItemIcon" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>
                <div>
                    <label class="text-sm font-semibold text-gray-700">Ordre</label>
                    <input name="ordre_fonctionnalite" id="editItemOrdre" type="number" class="mt-1 w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" <?= $isEditable ? '' : 'disabled' ?>>
                </div>

                <div class="md:col-span-2 flex items-center justify-between">
                    <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                        <input type="checkbox" name="actif" id="editItemActif" value="1" <?= $isEditable ? '' : 'disabled' ?>>
                        Actif
                    </label>
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 <?= $isEditable ? '' : 'opacity-50 cursor-not-allowed' ?>" <?= $isEditable ? '' : 'disabled' ?>>
                        Enregistrer
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        const typeItem = document.getElementById('typeItem');
        const parentPicker = document.getElementById('parentPicker');
        function syncCreate() {
            if (!typeItem || !parentPicker) return;
            parentPicker.style.display = (typeItem.value === 'child') ? '' : 'none';
        }
        if (typeItem) typeItem.addEventListener('change', syncCreate);
        syncCreate();

        window.closeModal = function (id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.add('hidden');
            el.classList.remove('flex');
        };

        function openModal(id) {
            const el = document.getElementById(id);
            if (!el) return;
            el.classList.remove('hidden');
            el.classList.add('flex');
        }

        window.openEditCategory = function (id, lib, desc, icon, ordre, actif) {
            const byId = (x) => document.getElementById(x);
            byId('editCatId').value = String(id);
            byId('editCatLib').value = lib || '';
            byId('editCatDesc').value = desc || '';
            byId('editCatIcon').value = icon || '';
            byId('editCatOrdre').value = String(ordre || 0);
            byId('editCatActif').checked = !!actif;
            openModal('editCategoryModal');
        };

        function syncEditParent() {
            const type = document.getElementById('editItemType');
            const wrap = document.getElementById('editItemParentWrap');
            if (!type || !wrap) return;
            wrap.style.display = (type.value === 'child') ? '' : 'none';
        }

        window.openEditItem = function (id, idCategorie, type, lib, label, desc, url, icon, ordre, parent, actif) {
            const byId = (x) => document.getElementById(x);
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

        const editType = document.getElementById('editItemType');
        if (editType) editType.addEventListener('change', syncEditParent);

        // Expand/collapse des écrans (table)
        document.addEventListener('click', function (e) {
            const btn = e.target && e.target.closest ? e.target.closest('.toggleRow') : null;
            if (!btn) return;
            const target = btn.getAttribute('data-target');
            if (!target) return;
            const rows = document.querySelectorAll(`tr.childRow[data-parent="${target}"]`);
            const icon = btn.querySelector('.toggleIcon');
            const isHidden = rows.length > 0 ? rows[0].classList.contains('hidden') : true;
            rows.forEach(r => r.classList.toggle('hidden', !isHidden));
            if (icon) icon.textContent = isHidden ? '-' : '+';
        });
    })();
</script>


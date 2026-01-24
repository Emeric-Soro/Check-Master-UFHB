<?php
$listeGroupes = $GLOBALS['listeGroupes'] ?? [];
$listeTypesAll = $GLOBALS['listeTypesAll'] ?? [];
$selectedTypeId = $GLOBALS['selectedTypeId'] ?? ($_GET['type'] ?? 'all');

$listeFonctionnalites = $GLOBALS['listeFonctionnalites'] ?? ($GLOBALS['listeTraitements'] ?? []);
$selectedGroupe = $GLOBALS['selectedGroupe'] ?? null;
$permissionsGroupe = $GLOBALS['permissionsGroupe'] ?? ($GLOBALS['attributionsGroupe'] ?? []);
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';

// CSRF (centralisé)
$csrfToken = \CheckMaster\Core\Csrf::token();

// Map permissions par id_fonctionnalite (accès O(1))
$permByFoncId = [];
foreach ($permissionsGroupe as $p) {
    if (isset($p->id_fonctionnalite)) {
        $permByFoncId[(int)$p->id_fonctionnalite] = $p;
    }
}

function parseLegacyPageAction(?string $url): array
{
    $url = (string) $url;
    $query = parse_url($url, PHP_URL_QUERY);
    if (!$query) {
        return ['', ''];
    }
    $params = [];
    parse_str($query, $params);
    $page = isset($params['page']) ? (string) $params['page'] : '';
    $action = isset($params['action']) ? (string) $params['action'] : '';
    return [$page, $action];
}

// Construire une structure hiérarchique "menu": Catégorie -> Sous-menu(parent) -> Écrans(enfants)
// Basé sur `est_sous_page` + `page_parente` (cohérent avec le menu affiché dans l'app).
$tree = [];
foreach ($listeFonctionnalites as $f) {
    $cat = (string)($f->lib_categorie ?? 'Autres');
    $catCode = (string)($f->code_categorie ?? 'autres');

    if (!isset($tree[$catCode])) {
        $tree[$catCode] = [
            'label' => $cat,
            'items' => [],
            'parents' => [],
            'orphans' => [],
        ];
    }

    $tree[$catCode]['items'][] = $f;
}

foreach ($tree as $catCode => &$catData) {
    $items = $catData['items'];
    $parentsByCode = [];
    $childrenByParent = [];
    $orphans = [];

    foreach ($items as $f) {
        $isSousPage = !empty($f->est_sous_page);
        $code = isset($f->code_fonctionnalite) ? (string)$f->code_fonctionnalite : '';
        $parentCode = isset($f->page_parente) ? (string)$f->page_parente : '';

        if ($isSousPage) {
            if ($parentCode !== '') {
                if (!isset($childrenByParent[$parentCode])) {
                    $childrenByParent[$parentCode] = [];
                }
                $childrenByParent[$parentCode][] = $f;
            } else {
                $orphans[] = $f;
            }
            continue;
        }

        if ($code !== '') {
            $parentsByCode[$code] = $f;
        } else {
            $orphans[] = $f;
        }
    }

    foreach ($parentsByCode as $code => $p) {
        $children = $childrenByParent[$code] ?? [];
        usort($children, function ($a, $b) {
            return ((int)($a->ordre_fonctionnalite ?? 0)) <=> ((int)($b->ordre_fonctionnalite ?? 0));
        });
        $p->children = array_values($children);
    }

    // Parents manquants: créer un hub virtuel (au besoin)
    foreach ($childrenByParent as $pcode => $children) {
        if (isset($parentsByCode[$pcode])) {
            continue;
        }
        if (empty($children)) {
            continue;
        }
        usort($children, function ($a, $b) {
            return ((int)($a->ordre_fonctionnalite ?? 0)) <=> ((int)($b->ordre_fonctionnalite ?? 0));
        });
        $hub = new stdClass();
        $hub->id_fonctionnalite = 0;
        $hub->code_fonctionnalite = (string)$pcode;
        $hub->lib_fonctionnalite = (string)$pcode;
        $hub->label_fonctionnalite = (string)$pcode;
        $hub->url_fonctionnalite = '#';
        $hub->icone_fonctionnalite = 'fas fa-folder';
        $hub->ordre_fonctionnalite = (int)($children[0]->ordre_fonctionnalite ?? 0);
        $hub->est_sous_page = 0;
        $hub->page_parente = null;
        $hub->children = array_values($children);
        $hub->is_virtual = true;
        $parentsByCode[$pcode] = $hub;
    }

    $parents = array_values($parentsByCode);
    usort($parents, function ($a, $b) {
        return ((int)($a->ordre_fonctionnalite ?? 0)) <=> ((int)($b->ordre_fonctionnalite ?? 0));
    });

    $catData['parents'] = $parents;
    $catData['orphans'] = $orphans;
}
unset($catData);

$isEditable = function_exists('canEdit') ? (bool)canEdit() : true;
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Attributions</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* Animations et transitions */
        .animate__animated {
            animation-duration: 0.3s;
        }

        .transition-all {
            transition-property: all;
            transition-timing-function: cubic-bezier(0.4, 0, 0.2, 1);
            transition-duration: 200ms;
        }

        /* Personnalisation des inputs */
        .form-input:focus {
            border-color: #22c55e;
            box-shadow: 0 0 0 2px rgba(34, 197, 94, 0.2);
            background-color: #f0fdf4;
        }

        /* Style pour le hover des lignes du tableau */
        .table-row:hover {
            background-color: #f0fdf4;
        }

        /* Style pour les checkboxes */
        input[type="checkbox"]:checked {
            background-color: #22c55e;
            border-color: #22c55e;
        }

        /* Hiérarchie visuelle (table) */
        .treeParentCell {
            position: relative;
        }
        .treeChildCell {
            position: relative;
            padding-left: 2.25rem !important;
        }
        .treeChildCell:before {
            content: "";
            position: absolute;
            left: 1.1rem;
            top: 0;
            bottom: 0;
            border-left: 1px dashed #cbd5e1;
            opacity: 0.8;
        }
        .treeChildCell:after {
            content: "";
            position: absolute;
            left: 1.1rem;
            top: 50%;
            width: 0.75rem;
            border-top: 1px dashed #cbd5e1;
            transform: translateY(-50%);
            opacity: 0.8;
        }
        .chip {
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            border-radius: 9999px;
            padding: 0.125rem 0.5rem;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.02em;
        }
        .chipParent {
            background: #eef2ff;
            color: #3730a3;
        }
        .chipChild {
            background: #ecfeff;
            color: #0e7490;
        }
        .permHeader {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            white-space: nowrap;
        }
        .permHeader input[type="checkbox"] {
            width: 1rem;
            height: 1rem;
        }

        /* Style pour la pagination active */
        .pagination-active {
            background-color: #22c55e;
            border-color: #22c55e;
        }

        /* Boutons avec dégradés */
        .btn-gradient-primary {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .btn-gradient-secondary {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
        }

        .btn-gradient-warning {
            background: linear-gradient(135deg, #eab308 0%, #ca8a04 100%);
        }

        .btn-gradient-danger {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        /* Effet de hover sur les boutons */
        .btn-hover {
            transition: all 0.3s ease;
        }

        .btn-hover:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .container table,
            .container table * {
                visibility: visible;
            }

            .container table {
                position: absolute;
                left: 0;
                top: 0;
            }

            button,
            .actions,
            input[type="checkbox"] {
                display: none !important;
            }
        }

        /* Styles pour les notifications */
        .notification {
            position: fixed;
            top: 1rem;
            right: 1rem;
            padding: 1rem;
            border-radius: 0.5rem;
            color: white;
            max-width: 24rem;
            z-index: 50;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            animation: slideIn 0.5s ease-out;
        }

        .notification.success {
            background: linear-gradient(135deg, #22c55e 0%, #16a34a 100%);
        }

        .notification.error {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }

            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }

            to {
                opacity: 0;
            }
        }

        /* Centrage du message quand aucun groupe n'est sélectionné */
        #noSelectionMessage {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            min-height: 300px;
        }

        /* Amélioration du scroll pour la liste des groupes */
        #groupesList {
            max-height: calc(100vh - 300px);
            overflow-y: auto;
        }

        /* Style pour les éléments de traitement */
        .traitement-item {
            transition: all 0.3s ease;
        }

        .traitement-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .traitement-checkbox:checked+label {
            color: #059669;
        }

        /* Style pour les boutons de groupe */
        .groupe-btn {
            transition: all 0.2s ease;
        }

        .groupe-btn:hover {
            background-color: #f0fdf4;
        }

        .groupe-btn.selected {
            background-color: #ecfdf5;
            border-left: 4px solid #059669;
        }

        /* Animation pour la modale */
        .modal-enter {
            animation: modalEnter 0.3s ease-out;
        }

        @keyframes modalEnter {
            from {
                opacity: 0;
                transform: scale(0.95);
            }

            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        /* Style pour le compteur d'attributions */
        #attributionCounter {
            transition: all 0.3s ease;
        }
    </style>
</head>

<body class="min-h-screen" style="background-color: #DFF2FF;">

    <!-- Système de notification -->
    <?php if (!empty($messageSuccess)): ?>
        <div id="successNotification" class="notification success animate__animated animate__fadeIn">
            <div class="flex items-center">
                <i class="fas fa-check-circle mr-2"></i>
                <p><?= htmlspecialchars($messageSuccess) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <?php if (!empty($messageErreur)): ?>
        <div id="errorNotification" class="notification error animate__animated animate__fadeIn">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle mr-2"></i>
                <p><?= htmlspecialchars($messageErreur) ?></p>
            </div>
        </div>
    <?php endif; ?>

    <div class="container mx-auto px-4 py-8">
        <header class="mb-6">
            <div class="flex items-start justify-between gap-4">
                <div>
                    <h1 class="text-2xl md:text-3xl font-bold text-gray-800">
                        <i class="fas fa-user-shield mr-3 text-emerald-600"></i>
                        Gestion des habilitations
                    </h1>
                    <p class="text-gray-600 mt-2">Définissez les permissions par groupe utilisateur (CRUD)</p>
                </div>
            </div>
        </header>

        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2 mb-2">
                            <i class="fas fa-user-tag text-emerald-600"></i>
                            Type d'utilisateur
                        </label>
                        <select id="typeSelect" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="all" <?= ($selectedTypeId === 'all') ? 'selected' : '' ?>>Tous les types</option>
                            <?php foreach ($listeTypesAll as $t): ?>
                                <option value="<?= (int)$t->id_type_utilisateur ?>" <?= ((string)$selectedTypeId === (string)$t->id_type_utilisateur) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t->lib_type_utilisateur) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="text-sm font-semibold text-gray-700 flex items-center gap-2 mb-2">
                            <i class="fas fa-users text-emerald-600"></i>
                            Groupe utilisateur
                        </label>
                        <select id="groupSelect" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                            <option value="">Sélectionnez un groupe utilisateur</option>
                            <?php foreach ($listeGroupes as $g): ?>
                                <option value="<?= (int)$g->id_GU ?>" <?= ($selectedGroupe && (int)$selectedGroupe->id_GU === (int)$g->id_GU) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($g->lib_GU) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <?php if ($selectedGroupe): ?>
                <form method="POST" class="p-4" id="permForm">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="id_GU" value="<?= (int)$selectedGroupe->id_GU ?>">

                    <div class="overflow-x-auto rounded-lg border border-gray-200">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">#</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Code</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Menu / Écran</th>
                                    <th class="px-2 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">+/-</th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <div class="permHeader">
                                            <input type="checkbox" class="permColToggle" data-col="creer" <?= $isEditable ? '' : 'disabled' ?> aria-label="Tout cocher: Ajouter">
                                            <span class="inline-flex items-center gap-2"><i class="fas fa-plus text-emerald-600"></i>Ajouter</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <div class="permHeader">
                                            <input type="checkbox" class="permColToggle" data-col="modifier" <?= $isEditable ? '' : 'disabled' ?> aria-label="Tout cocher: Modifier">
                                            <span class="inline-flex items-center gap-2"><i class="fas fa-pen text-amber-600"></i>Modifier</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <div class="permHeader">
                                            <input type="checkbox" class="permColToggle" data-col="supprimer" <?= $isEditable ? '' : 'disabled' ?> aria-label="Tout cocher: Supprimer">
                                            <span class="inline-flex items-center gap-2"><i class="fas fa-trash text-red-600"></i>Supprimer</span>
                                        </div>
                                    </th>
                                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-600 uppercase tracking-wider">
                                        <div class="permHeader">
                                            <input type="checkbox" class="permColToggle" data-col="voir" <?= $isEditable ? '' : 'disabled' ?> aria-label="Tout cocher: Consulter">
                                            <span class="inline-flex items-center gap-2"><i class="fas fa-eye text-blue-600"></i>Consulter</span>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-100" id="permTableBody">
                                <?php $rowNum = 0; ?>
                                <?php foreach ($tree as $catCode => $catData): ?>
                                    <tr class="bg-gray-100">
                                        <td colspan="8" class="px-4 py-2 text-sm font-semibold text-gray-700">
                                            <?= htmlspecialchars($catData['label']) ?>
                                        </td>
                                    </tr>

                                    <?php foreach (($catData['parents'] ?? []) as $parent): ?>
                                        <?php
                                        $parentId = (int)($parent->id_fonctionnalite ?? 0);
                                        $parentCodeF = (string)($parent->code_fonctionnalite ?? '');
                                        $parentLabel = (string)($parent->label_fonctionnalite ?? $parent->lib_fonctionnalite ?? $parentCodeF);
                                        $children = (isset($parent->children) && is_array($parent->children)) ? $parent->children : [];
                                        $hasChildren = !empty($children);
                                        $groupId = 'grp-' . md5($catCode . '|' . $parentCodeF . '|' . (string)$parentId);
                                        $parentPerm = $parentId > 0 && isset($permByFoncId[$parentId]) ? $permByFoncId[$parentId] : null;
                                        $disabled = ($parentId <= 0) || !$isEditable;
                                        $nameBase = $parentId > 0 ? "permissions[$parentId]" : '';
                                        ?>

                                        <tr class="hover:bg-gray-50">
                                            <td class="px-4 py-3 text-sm text-gray-500"><?= ++$rowNum ?></td>
                                            <td class="px-4 py-3 text-sm text-gray-700">
                                                <?= htmlspecialchars($parentCodeF !== '' ? $parentCodeF : (string)$parentId) ?>
                                            </td>
                                            <td class="px-4 py-3 text-sm font-medium text-gray-900 treeParentCell">
                                                <div class="flex items-center gap-2">
                                                    <i class="<?= $hasChildren ? 'fas fa-folder text-indigo-600' : 'fas fa-file-alt text-slate-500' ?>"></i>
                                                    <span><?= htmlspecialchars($parentLabel) ?></span>
                                                    <span class="chip <?= $hasChildren ? 'chipParent' : 'chipChild' ?>">
                                                        <?= $hasChildren ? 'SOUS-MENU' : 'ÉCRAN' ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td class="px-2 py-3 text-center">
                                                <?php if ($hasChildren): ?>
                                                    <button type="button" class="toggleRow inline-flex h-7 w-7 items-center justify-center rounded-full bg-blue-50 text-blue-700 border border-blue-200"
                                                        data-target="<?= $groupId ?>" aria-label="Déplier/Replier">
                                                        <span class="toggleIcon">+</span>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="text-gray-300">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if ($parentId <= 0): ?>-<?php else: ?>
                                                    <input class="permBox" data-col="creer" type="checkbox" name="<?= $nameBase ?>[creer]" value="1"
                                                        <?= ($parentPerm && !empty($parentPerm->peut_creer)) ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if ($parentId <= 0): ?>-<?php else: ?>
                                                    <input class="permBox" data-col="modifier" type="checkbox" name="<?= $nameBase ?>[modifier]" value="1"
                                                        <?= ($parentPerm && !empty($parentPerm->peut_modifier)) ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if ($parentId <= 0): ?>-<?php else: ?>
                                                    <input class="permBox" data-col="supprimer" type="checkbox" name="<?= $nameBase ?>[supprimer]" value="1"
                                                        <?= ($parentPerm && !empty($parentPerm->peut_supprimer)) ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-4 py-3 text-center">
                                                <?php if ($parentId <= 0): ?>-<?php else: ?>
                                                    <input class="permBox" data-col="voir" type="checkbox" name="<?= $nameBase ?>[voir]" value="1"
                                                        <?= ($parentPerm && !empty($parentPerm->peut_voir)) ? 'checked' : '' ?> <?= $disabled ? 'disabled' : '' ?>>
                                                <?php endif; ?>
                                            </td>
                                        </tr>

                                        <?php foreach ($children as $child): ?>
                                            <?php
                                            $cid = (int)($child->id_fonctionnalite ?? 0);
                                            $ccode = (string)($child->code_fonctionnalite ?? (string)$cid);
                                            $clabel = (string)($child->label_fonctionnalite ?? $child->lib_fonctionnalite ?? $ccode);
                                            $cperm = $cid > 0 && isset($permByFoncId[$cid]) ? $permByFoncId[$cid] : null;
                                            $cdisabled = !$isEditable;
                                            ?>
                                            <tr class="childRow hidden bg-white hover:bg-gray-50" data-parent="<?= $groupId ?>">
                                                <td class="px-4 py-3 text-sm text-gray-400"><?= ++$rowNum ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-600"><?= htmlspecialchars($ccode) ?></td>
                                                <td class="px-4 py-3 text-sm text-gray-900 treeChildCell">
                                                    <div class="flex items-center gap-2">
                                                        <i class="fas fa-file-alt text-slate-400"></i>
                                                        <span><?= htmlspecialchars($clabel) ?></span>
                                                        <span class="chip chipChild">ÉCRAN</span>
                                                    </div>
                                                </td>
                                                <td class="px-2 py-3 text-center text-gray-300"> </td>
                                                <td class="px-4 py-3 text-center">
                                                    <input class="permBox" data-col="creer" type="checkbox" name="permissions[<?= $cid ?>][creer]" value="1" <?= ($cperm && !empty($cperm->peut_creer)) ? 'checked' : '' ?> <?= $cdisabled ? 'disabled' : '' ?>>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <input class="permBox" data-col="modifier" type="checkbox" name="permissions[<?= $cid ?>][modifier]" value="1" <?= ($cperm && !empty($cperm->peut_modifier)) ? 'checked' : '' ?> <?= $cdisabled ? 'disabled' : '' ?>>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <input class="permBox" data-col="supprimer" type="checkbox" name="permissions[<?= $cid ?>][supprimer]" value="1" <?= ($cperm && !empty($cperm->peut_supprimer)) ? 'checked' : '' ?> <?= $cdisabled ? 'disabled' : '' ?>>
                                                </td>
                                                <td class="px-4 py-3 text-center">
                                                    <input class="permBox" data-col="voir" type="checkbox" name="permissions[<?= $cid ?>][voir]" value="1" <?= ($cperm && !empty($cperm->peut_voir)) ? 'checked' : '' ?> <?= $cdisabled ? 'disabled' : '' ?>>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="flex justify-end gap-3 mt-4">
                        <a href="?page=parametres_generaux&action=gestion_attribution&type=<?= urlencode((string)$selectedTypeId) ?>&groupe=<?= (int)$selectedGroupe->id_GU ?>"
                            class="px-4 py-2 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                            Réinitialiser
                        </a>
                        <?php if ($isEditable): ?>
                            <button type="submit"
                                class="px-4 py-2 rounded-lg text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700">
                                Enregistrer
                            </button>
                        <?php endif; ?>
                    </div>
                </form>
            <?php else: ?>
                <div class="p-10 text-center text-gray-500">
                    Sélectionnez un type et un groupe utilisateur pour gérer les permissions.
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modale de détails du traitement -->
    <div id="traitementDetailsModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
        <div class="bg-white rounded-lg p-6 max-w-lg w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-semibold text-gray-900" id="traitementDetailsTitle"></h3>
                <button type="button" onclick="closeTraitementDetails()" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="text-gray-600" id="traitementDetailsContent"></div>
        </div>
    </div>

    <script>
        (function () {
            const typeSelect = document.getElementById('typeSelect');
            const groupSelect = document.getElementById('groupSelect');

            function buildUrl(params) {
                // Garder _r=1 pour éviter la canonicalisation (sinon perte de type/groupe via redirection Router)
                const q = new URLSearchParams();
                q.set('page', 'parametres_generaux');
                q.set('action', 'gestion_attribution');
                q.set('_r', '1');
                Object.keys(params || {}).forEach((k) => {
                    const v = params[k];
                    if (v === undefined || v === null || v === '') {
                        q.delete(k);
                    } else {
                        q.set(k, String(v));
                    }
                });
                return '?' + q.toString();
            }

            // Type -> recharge en filtrant les groupes (on reset le groupe)
            if (typeSelect) {
                typeSelect.addEventListener('change', function () {
                    const t = typeSelect.value || 'all';
                    window.location.href = buildUrl({ type: t });
                });
            }

            // Groupe -> recharge en gardant le type sélectionné
            if (groupSelect) {
                groupSelect.addEventListener('change', function () {
                    const g = groupSelect.value || '';
                    const t = typeSelect ? (typeSelect.value || 'all') : 'all';
                    window.location.href = buildUrl({ type: t, groupe: g });
                });
            }

            // Expand/collapse
            document.addEventListener('click', function (e) {
                const btn = e.target.closest ? e.target.closest('.toggleRow') : null;
                if (!btn) return;
                const target = btn.getAttribute('data-target');
                if (!target) return;
                const rows = document.querySelectorAll(`tr.childRow[data-parent="${target}"]`);
                const icon = btn.querySelector('.toggleIcon');
                const isHidden = rows.length > 0 ? rows[0].classList.contains('hidden') : true;
                rows.forEach(r => r.classList.toggle('hidden', !isHidden));
                if (icon) icon.textContent = isHidden ? '-' : '+';
            });

            // Tout cocher par colonne (CRUD)
            // IMPORTANT: il peut y avoir d'autres <form> dans le layout global (logout, recherche, etc.)
            // Donc on cible explicitement le formulaire des permissions.
            const form = document.getElementById('permForm');
            if (form) {
                const colToggles = form.querySelectorAll('.permColToggle');

                function getBoxes(col) {
                    return Array.from(form.querySelectorAll(`input.permBox[data-col="${col}"]`))
                        .filter((el) => el && el.type === 'checkbox');
                }

                function updateColState(col) {
                    const toggle = form.querySelector(`.permColToggle[data-col="${col}"]`);
                    if (!toggle) return;
                    const boxes = getBoxes(col).filter((b) => !b.disabled);
                    if (boxes.length === 0) {
                        toggle.checked = false;
                        toggle.indeterminate = false;
                        return;
                    }
                    const checkedCount = boxes.reduce((acc, b) => acc + (b.checked ? 1 : 0), 0);
                    toggle.checked = checkedCount === boxes.length;
                    toggle.indeterminate = checkedCount > 0 && checkedCount < boxes.length;
                }

                function setAll(col, checked) {
                    const boxes = getBoxes(col);
                    boxes.forEach((b) => {
                        if (!b.disabled) b.checked = checked;
                    });
                    updateColState(col);
                }

                colToggles.forEach((t) => {
                    const col = t.getAttribute('data-col');
                    if (!col) return;
                    updateColState(col);
                    t.addEventListener('change', () => {
                        t.indeterminate = false;
                        setAll(col, !!t.checked);
                    });
                });

                form.addEventListener('change', (e) => {
                    const el = e.target;
                    if (!el || !el.classList || !el.classList.contains('permBox')) return;
                    const col = el.getAttribute('data-col');
                    if (!col) return;
                    updateColState(col);
                });
            }
        })();
    </script>

    <?php if (isset($_GET['debug']) && $_GET['debug'] === 'attributions'): ?>
        <div class="fixed bottom-4 left-4 p-4 bg-gray-800 text-white rounded-lg text-xs max-w-lg max-h-64 overflow-auto">
            <h4 class="font-bold mb-2">Débug des attributions:</h4>
            <pre><?php echo json_encode($GLOBALS['attributionsMap'] ?? [], JSON_PRETTY_PRINT); ?></pre>
        </div>
    <?php endif; ?>
</body>

</html>
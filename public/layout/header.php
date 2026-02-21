<?php
/**
 * Layout Header – migré vers le nouveau layout Bulma (ressources/views/layouts/app.php).
 *
 * Ce fichier est inclus par public/layout.php APRÈS init.php et router.php.
 * Il convertit les données legacy ($menuHierarchique, $menuHTML, etc.) au format
 * attendu par le nouveau layout, puis ouvre la page HTML via app.php.
 *
 * ► Aucun contrôleur, aucune route, aucun modèle n'est modifié.
 */

// ────────────────────────────────────────────────────────────────────────────
// 1. Convertir $menuHierarchique en $menus[] (format attendu par sidebar.php)
// ────────────────────────────────────────────────────────────────────────────
$menus = [];

if (!empty($menuHierarchique) && is_array($menuHierarchique)) {
    $currentGetPage = isset($_GET['page']) ? (string)$_GET['page'] : '';
    $currentGetAction = isset($_GET['action']) ? (string)$_GET['action'] : '';

    /**
     * Vérifie si une URL de fonctionnalité est active.
     */
    $isActive = static function (string $url) use ($currentMenuSlug, $currentGetPage, $currentGetAction): bool {
        $query = parse_url($url, PHP_URL_QUERY);
        if (!$query) {
            return false;
        }
        parse_str($query, $params);
        $pageName = $params['page'] ?? '';
        $action = $params['action'] ?? '';

        $pageMatches = ($currentMenuSlug === $pageName) || ($currentGetPage === $pageName);
        if (!$pageMatches) {
            return false;
        }
        if ($action !== '') {
            return $currentGetAction === $action;
        }
        return true;
    };

    $displayLabel = static function ($fonc): string {
        $label = isset($fonc->label_fonctionnalite) ? trim((string)$fonc->label_fonctionnalite) : '';
        $lib = isset($fonc->lib_fonctionnalite) ? trim((string)$fonc->lib_fonctionnalite) : '';
        $code = isset($fonc->code_fonctionnalite) ? trim((string)$fonc->code_fonctionnalite) : '';

        if ($label !== '' && $label !== $code)
            return $label;
        if ($lib !== '' && $lib !== $code)
            return $lib;
        if ($code !== '') {
            $code = preg_replace('/^(SCOL|ETU|COM|ADM)_/u', '', $code);
            $code = str_replace('_', ' ', $code);
            return function_exists('mb_convert_case')
            ? (string)mb_convert_case(strtolower($code), MB_CASE_TITLE, 'UTF-8')
            : ucwords(strtolower($code));
        }
        return '';
    };

    foreach ($menuHierarchique as $item) {
        $categorie = $item['categorie'];
        $fonctionnalites = $item['fonctionnalites'];

        $menuItem = [
            'label' => $categorie->lib_categorie ?? '',
            'icon' => $categorie->icone_categorie ?? 'fa-folder',
            'is_active' => false,
            'children' => [],
        ];

        foreach ($fonctionnalites as $fonc) {
            $children = isset($fonc->children) && is_array($fonc->children) ? $fonc->children : [];
            $foncActive = $isActive((string)($fonc->url_fonctionnalite ?? ''));

            if (!empty($children)) {
                // Fonctionnalité avec sous-éléments → ajouter comme sous-groupe (Level 2)
                $subMenu = [
                    'label' => $displayLabel($fonc),
                    'icon' => $fonc->icone_fonctionnalite ?? 'fa-folder',
                    'is_active' => $foncActive,
                    'children' => [],
                ];

                foreach ($children as $child) {
                    $childActive = $isActive((string)($child->url_fonctionnalite ?? ''));
                    if ($childActive) {
                        $foncActive = true;
                        $subMenu['is_active'] = true;
                    }
                    $subMenu['children'][] = [
                        'label' => $displayLabel($child),
                        'url' => (string)($child->url_fonctionnalite ?? '#'),
                        'icon' => $child->icone_fonctionnalite ?? 'fa-circle',
                        'is_active' => $childActive,
                    ];
                }
                $menuItem['children'][] = $subMenu;
            }
            else {
                // Fonctionnalité simple (Level 2)
                $menuItem['children'][] = [
                    'label' => $displayLabel($fonc),
                    'url' => (string)($fonc->url_fonctionnalite ?? '#'),
                    'icon' => $fonc->icone_fonctionnalite ?? 'fa-circle',
                    'is_active' => $foncActive,
                ];
            }

            if ($foncActive) {
                $menuItem['is_active'] = true;
            }
        }

        $menus[] = $menuItem;
    }
}

// ────────────────────────────────────────────────────────────────────────────
// 2. Préparer les variables attendues par le nouveau layout (app.php)
// ────────────────────────────────────────────────────────────────────────────

// Titre de la page
$page_title = $currentPageLabel ?? '';
$title = $currentPageLabel ?? '';

// Informations utilisateur
$user = [
    'nom' => $_SESSION['nom_utilisateur'] ?? '',
    'prenom' => '',
    'groupe' => $_SESSION['lib_GU'] ?? '',
];

// Token CSRF
$csrf_token = \CheckMaster\Core\Csrf::token();

// Flash messages (session)
$flash_message = null;
if (!empty($_SESSION['error_message'])) {
    $flash_message = [
        'type' => 'danger',
        'message' => $_SESSION['error_message'],
    ];
    unset($_SESSION['error_message']);
}
elseif (!empty($_SESSION['success_message'])) {
    $flash_message = [
        'type' => 'success',
        'message' => $_SESSION['success_message'],
    ];
    unset($_SESSION['success_message']);
}

// Extra CSS: ajouter l'ancien CSS output.css pour les vues legacy
$extra_css = ['../css/output.css'];

// Extra JS: ajouter les scripts legacy
$extra_js = [
    '../js/suivi_reclamation.js',
    '../js/historique_reclamation.js',
];

// Flatpickr (utilisé dans les vues legacy)
$extra_css[] = 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.css';
$extra_js[] = 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/flatpickr.min.js';
$extra_js[] = 'https://cdnjs.cloudflare.com/ajax/libs/flatpickr/4.6.13/l10n/fr.js';

// Animate.css
$extra_css[] = 'https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css';

// Initialize ComponentHelper for the layout
if (!isset($c)) {
    require_once dirname(__DIR__, 1) . '/../src/Support/ComponentHelper.php';
    $c = new ComponentHelper();
}

// ────────────────────────────────────────────────────────────────────────────
// 3. Rendre le début du layout via app.php
//    Le layout ouvre toutes les balises jusqu'au slot $content.
//    On ne peut pas l'inclure directement car il affiche $content en inline.
//    Stratégie: on bufferise le contenu, puis on l'injecte dans app.php via $content.
// ────────────────────────────────────────────────────────────────────────────
// On démarre un buffer pour capturer le contenu de la page (entre header et footer).
ob_start();

// Le router a déjà déterminé $contentFile. On l'inclut ici.
if (!empty($contentFile) && file_exists($contentFile)) {
    include $contentFile;
}
else {
    echo "<div class='notification is-danger is-light'>";
    echo "<div class='has-text-weight-bold mb-2'>Erreur de chargement</div>";
    if (empty($contentFile)) {
        echo "<div>Aucun fichier de contenu n'a été spécifié pour cette vue.</div>";
    }
    else {
        echo "<div>Le fichier de contenu pour '" . htmlspecialchars($currentPageLabel ?? '') . "' est introuvable.</div>";
    }
    echo "</div>";
}

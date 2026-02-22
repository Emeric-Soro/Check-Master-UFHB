# PRD 2 — Bibliothèque de Composants PHP Réutilisables
## CheckMaster — Refonte UI v2

**Statut** : À implémenter  
**Dépend de** : PRD 1 (Design System & CSS Variables)  
**Objectif** : Zéro duplication dans les nouvelles vues — tout élément répété devient un composant

---

## 1. Vue d'ensemble

### 1.1 Philosophie

Chaque fragment HTML qui apparaît dans plus d'une vue DOIT être extrait en composant PHP. Un composant reçoit ses données via un tableau `$props`, produit du HTML pur, ne contient aucune logique métier, et n'émet aucune requête SQL.

**Règle d'or** : Si tu copie-colle du HTML, tu crées un composant.

### 1.2 Mécanisme d'inclusion

Tous les composants sont inclus via la fonction centrale `cm_component()` définie dans `app/utils/ComponentHelper.php` :

```php
cm_component('ui/toast', [
    'type'    => 'success',
    'message' => 'Enregistrement réussi',
]);
```

La fonction résout le chemin, injecte les props comme variables locales, et fait l'include. Elle lève une exception claire si le composant n'existe pas.

### 1.3 Conventions de nommage

- **Fichiers** : `kebab-case.php` (ex : `stat-widget.php`)
- **Classes CSS** : `.cm-[composant]` + modificateurs `.is-[état]` (ex : `.cm-badge.is-success`)
- **Props PHP** : `snake_case` (ex : `$props['row_actions']`)
- **Variables locales** : extraites avec `extract($props)` en début de composant — toujours avec valeurs par défaut

---

## 2. Structure des fichiers à créer

```
ressources/
└── components/
    ├── layout/
    │   ├── app-shell.php
    │   ├── navbar.php
    │   ├── sidebar.php
    │   └── page-header.php
    ├── ui/
    │   ├── toast.php
    │   ├── badge.php
    │   ├── alert-box.php
    │   └── empty-state.php
    ├── crud/
    │   ├── form-pole.php
    │   ├── toolbar.php
    │   ├── data-table.php
    │   ├── pagination.php
    │   └── form-actions.php
    ├── form/
    │   ├── input-text.php
    │   ├── input-date.php
    │   ├── input-email.php
    │   ├── input-password.php
    │   ├── input-number.php
    │   ├── select.php
    │   ├── select-search.php
    │   ├── textarea.php
    │   ├── file-upload.php
    │   └── csrf-token.php
    ├── dashboard/
    │   ├── stat-widget.php
    │   ├── chart-container.php
    │   ├── activity-list.php
    │   └── alert-list.php
    ├── hub/
    │   ├── hub-tile.php
    │   └── hub-grid.php
    ├── tabs/
    │   ├── tab-nav.php
    │   └── tab-content.php
    ├── editor/
    │   ├── wysiwyg-editor.php
    │   └── word-counter.php
    ├── consultation/
    │   ├── detail-card.php
    │   └── pdf-viewer.php
    └── timeline/
        └── timeline.php

app/
└── utils/
    ├── ComponentHelper.php
    ├── FormHelper.php
    ├── PaginationHelper.php
    └── TableHelper.php

ressources/
└── views/
    └── v2/
        └── test_components.php
```

---

## 3. Helpers PHP (`app/utils/`)

### 3.1 `ComponentHelper.php`

```php
<?php
/**
 * ComponentHelper — inclusion centralisée des composants réutilisables
 * Aucune logique métier. Aucune requête SQL.
 */

if (!function_exists('cm_component')) {
    /**
     * Inclut un composant avec ses props.
     *
     * @param string $name  Chemin relatif depuis ressources/components/ sans .php
     *                      Exemple : 'ui/badge', 'crud/data-table'
     * @param array  $props Tableau associatif de données passées au composant
     * @throws RuntimeException si le fichier composant n'existe pas
     */
    function cm_component(string $name, array $props = []): void
    {
        $base = dirname(__DIR__, 2) . '/ressources/components/';
        $file = $base . $name . '.php';

        if (!file_exists($file)) {
            throw new RuntimeException(
                "[cm_component] Composant introuvable : {$name} — chemin : {$file}"
            );
        }

        // Isole les variables : extract dans une closure pour éviter la pollution de scope
        (static function (string $_file, array $props) {
            extract($props, EXTR_SKIP);
            require $_file;
        })($file, $props);
    }
}

if (!function_exists('cm_asset')) {
    /**
     * Retourne l'URL d'un asset avec cache-busting basé sur la date de modification.
     *
     * @param string $path Chemin relatif depuis public/assets/
     * @return string URL complète ou chemin relatif
     */
    function cm_asset(string $path): string
    {
        $full = dirname(__DIR__, 2) . '/public/assets/' . $path;
        $v    = file_exists($full) ? filemtime($full) : time();
        return '/assets/' . $path . '?v=' . $v;
    }
}
```

**Règles d'utilisation** :
- TOUJOURS passer les données via `$props`, jamais via des globales
- Ne jamais appeler `cm_component()` depuis un contrôleur — uniquement depuis les vues
- Le composant lui-même doit définir des valeurs par défaut pour toutes les props optionnelles

---

### 3.2 `FormHelper.php`

```php
<?php
/**
 * FormHelper — génération de tokens CSRF, helpers de formulaires
 */

if (!function_exists('cm_csrf_token')) {
    /**
     * Génère (si absent) et retourne le token CSRF de la session courante.
     */
    function cm_csrf_token(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('cm_csrf_verify')) {
    /**
     * Vérifie le token CSRF soumis. Lève une exception si invalide.
     *
     * @param string $submitted Token soumis (POST)
     * @throws RuntimeException si token invalide ou absent
     */
    function cm_csrf_verify(string $submitted): void
    {
        $token = $_SESSION['csrf_token'] ?? '';
        if (!hash_equals($token, $submitted)) {
            throw new RuntimeException('Token CSRF invalide.');
        }
    }
}

if (!function_exists('cm_old')) {
    /**
     * Retourne la valeur d'un champ du formulaire précédent (re-population).
     *
     * @param string $field   Nom du champ
     * @param mixed  $default Valeur par défaut si absent
     */
    function cm_old(string $field, mixed $default = ''): mixed
    {
        return $_SESSION['old_input'][$field] ?? $default;
    }
}

if (!function_exists('cm_error')) {
    /**
     * Retourne le message d'erreur de validation pour un champ.
     *
     * @param string $field Nom du champ
     */
    function cm_error(string $field): string
    {
        return $_SESSION['validation_errors'][$field] ?? '';
    }
}
```

---

### 3.3 `PaginationHelper.php`

```php
<?php
/**
 * PaginationHelper — calcul de la pagination
 */

if (!function_exists('cm_paginate')) {
    /**
     * Calcule les données de pagination.
     *
     * @param int $total       Nombre total d'enregistrements
     * @param int $per_page    Enregistrements par page
     * @param int $current     Page courante (1-indexée)
     * @param int $window      Nombre de pages adjacentes affichées (défaut : 2)
     * @return array {
     *   total: int, per_page: int, current: int, last: int,
     *   offset: int, has_prev: bool, has_next: bool,
     *   pages: int[]   // tableau des numéros de pages à afficher (avec -1 = ellipsis)
     * }
     */
    function cm_paginate(int $total, int $per_page, int $current, int $window = 2): array
    {
        $per_page = max(1, $per_page);
        $last     = max(1, (int) ceil($total / $per_page));
        $current  = max(1, min($current, $last));
        $offset   = ($current - 1) * $per_page;

        // Calcul des pages à afficher avec ellipsis (-1)
        $pages = [];
        $from  = max(1, $current - $window);
        $to    = min($last, $current + $window);

        if ($from > 1) {
            $pages[] = 1;
            if ($from > 2) $pages[] = -1; // ellipsis
        }
        for ($i = $from; $i <= $to; $i++) {
            $pages[] = $i;
        }
        if ($to < $last) {
            if ($to < $last - 1) $pages[] = -1; // ellipsis
            $pages[] = $last;
        }

        return [
            'total'    => $total,
            'per_page' => $per_page,
            'current'  => $current,
            'last'     => $last,
            'offset'   => $offset,
            'has_prev' => $current > 1,
            'has_next' => $current < $last,
            'pages'    => $pages,
        ];
    }
}
```

---

### 3.4 `TableHelper.php`

```php
<?php
/**
 * TableHelper — construction déclarative des colonnes de tableau
 */

if (!function_exists('cm_column')) {
    /**
     * Définit une colonne de tableau.
     *
     * @param string      $key       Clé dans le tableau de données
     * @param string      $label     En-tête de colonne
     * @param array       $options   Options optionnelles :
     *   - sortable (bool)     : colonne triable, défaut false
     *   - width   (string)    : largeur CSS, ex '120px'
     *   - align   (string)    : 'left'|'center'|'right', défaut 'left'
     *   - type    (string)    : 'text'|'badge'|'date'|'number'|'actions', défaut 'text'
     *   - format  (callable)  : fonction de formatage fn($value, $row): string
     * @return array Définition de colonne normalisée
     */
    function cm_column(string $key, string $label, array $options = []): array
    {
        return array_merge([
            'key'      => $key,
            'label'    => $label,
            'sortable' => false,
            'width'    => null,
            'align'    => 'left',
            'type'     => 'text',
            'format'   => null,
        ], $options);
    }
}

if (!function_exists('cm_action_column')) {
    /**
     * Définit la colonne d'actions standard (Modifier / Supprimer).
     *
     * @param array $actions Liste d'actions : [['label', 'icon', 'action', 'class?'], ...]
     * @return array Définition de colonne d'actions
     */
    function cm_action_column(array $actions = []): array
    {
        if (empty($actions)) {
            $actions = [
                ['label' => 'Modifier',    'icon' => 'fa-edit',    'action' => 'edit',   'class' => 'cm-btn-action is-edit'],
                ['label' => 'Supprimer',   'icon' => 'fa-trash',   'action' => 'delete', 'class' => 'cm-btn-action is-delete'],
            ];
        }
        return cm_column('_actions', 'Actions', [
            'type'    => 'actions',
            'align'   => 'center',
            'width'   => '120px',
            'actions' => $actions,
        ]);
    }
}
```

---

## 4. Composants Layout (`ressources/components/layout/`)

### 4.1 `app-shell.php`

**Rôle** : Structure complète de la page (sidebar + header + zone de contenu principal). C'est le squelette HTML de toutes les pages internes.

**Props** :
```php
// Props reçues via $props
$page_title   = $page_title   ?? 'CheckMaster';   // Titre <title>
$current_page = $current_page ?? '';               // Clé de page active pour la sidebar
$content      = $content      ?? '';               // HTML du contenu principal (ob_get_clean)
$user         = $user         ?? [];               // Données utilisateur connecté
$annee        = $annee        ?? '';               // Année académique courante
$breadcrumbs  = $breadcrumbs  ?? [];               // Array [['label' => '...', 'url' => '...']]
```

**HTML produit** :
```html
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$page_title} — CheckMaster</title>
    <link rel="stylesheet" href="/assets/css/checkmaster-theme.css?v=...">
    <link rel="stylesheet" href="/assets/css/components.css?v=...">
    <link rel="stylesheet" href="/assets/css/utilities.css?v=...">
    <link rel="stylesheet" href="/assets/css/responsive.css?v=...">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="cm-app-body">
    <!-- Sidebar -->
    [cm_component('layout/sidebar', ['current_page' => $current_page, 'user' => $user])]

    <!-- Wrapper principal -->
    <div class="cm-main-wrapper" id="mainWrapper">
        <!-- Navbar top -->
        [cm_component('layout/navbar', ['user' => $user, 'annee' => $annee])]

        <!-- Zone de contenu -->
        <main class="cm-content-area" id="contentArea">
            [cm_component('ui/toast')]
            {$content}
        </main>
    </div>

    <script src="/assets/js/app.js"></script>
</body>
</html>
```

**Comportement du sidebar toggle** : Le bouton hamburger dans la navbar ajoute/retire la classe `.is-collapsed` sur `.cm-main-wrapper`. La sidebar répond avec une transition CSS de `--cm-transition-normal`.

---

### 4.2 `navbar.php`

**Rôle** : Barre de navigation supérieure fixe.

**Props** :
```php
$user  = $user  ?? [];  // ['nom' => '', 'prenom' => '', 'role' => '', 'avatar' => '']
$annee = $annee ?? '';  // Ex: '2024-2025'
```

**HTML produit** :
```html
<header class="cm-navbar" id="cmNavbar">
    <div class="cm-navbar__left">
        <button class="cm-navbar__toggle" id="sidebarToggle" aria-label="Ouvrir/fermer le menu">
            <i class="fas fa-bars"></i>
        </button>
        <span class="cm-navbar__app-name">CheckMaster</span>
    </div>

    <div class="cm-navbar__center">
        <!-- Année académique badge -->
        <span class="cm-navbar__year-badge">
            <i class="fas fa-calendar-alt"></i>
            Année : {$annee}
        </span>
    </div>

    <div class="cm-navbar__right">
        <!-- Notifications -->
        <button class="cm-navbar__icon-btn" aria-label="Notifications">
            <i class="fas fa-bell"></i>
            <span class="cm-navbar__badge">3</span>
        </button>

        <!-- Profil utilisateur -->
        <div class="cm-navbar__user" id="userDropdown">
            <div class="cm-navbar__avatar">
                {initiales ou image}
            </div>
            <span class="cm-navbar__username">{$user['prenom']} {$user['nom']}</span>
            <i class="fas fa-chevron-down cm-navbar__chevron"></i>

            <!-- Dropdown -->
            <div class="cm-navbar__dropdown" id="userDropdownMenu">
                <a href="?page=profil" class="cm-navbar__dropdown-item">
                    <i class="fas fa-user"></i> Mon profil
                </a>
                <a href="?page=logout" class="cm-navbar__dropdown-item is-danger">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </a>
            </div>
        </div>
    </div>
</header>
```

**CSS requis** (dans `components.css`) :
```css
.cm-navbar {
    position: fixed;
    top: 0; left: var(--cm-sidebar-width); right: 0;
    height: var(--cm-header-height);
    background: var(--cm-content-bg);
    border-bottom: 1px solid var(--cm-border-color);
    display: flex; align-items: center; justify-content: space-between;
    padding: 0 var(--cm-spacing-lg);
    z-index: var(--cm-z-header);
    transition: left var(--cm-transition-normal);
}
.cm-main-wrapper.is-collapsed .cm-navbar { left: 0; }
.cm-navbar__badge {
    position: absolute; top: 2px; right: 2px;
    background: var(--cm-feedback-danger);
    color: #fff; border-radius: 50%; font-size: 10px;
    width: 16px; height: 16px; display: flex; align-items: center; justify-content: center;
}
.cm-navbar__dropdown {
    display: none; position: absolute; top: 100%; right: 0;
    background: var(--cm-content-bg); border: 1px solid var(--cm-border-color);
    border-radius: var(--cm-border-radius); min-width: 180px;
    box-shadow: var(--cm-shadow-lg); z-index: var(--cm-z-dropdown);
}
.cm-navbar__user:hover .cm-navbar__dropdown { display: block; }
```

---

### 4.3 `sidebar.php`

**Rôle** : Navigation latérale gauche avec menu à 3 niveaux.

**Props** :
```php
$current_page = $current_page ?? '';   // Page active (clé de route)
$user         = $user         ?? [];   // Données utilisateur pour affichage conditionnel
$menu_items   = $menu_items   ?? [];   // Tableau de la structure de menu (voir format ci-dessous)
```

**Format `$menu_items`** :
```php
[
    [
        'key'      => 'dashboard',
        'label'    => 'Tableau de bord',
        'icon'     => 'fa-tachometer-alt',
        'url'      => '?page=dashboard',
        'roles'    => ['admin', 'scolarite', 'enseignant'],
        'children' => [],   // pas de sous-menu
    ],
    [
        'key'      => 'scolarite',
        'label'    => 'Scolarité',
        'icon'     => 'fa-graduation-cap',
        'url'      => '#',
        'roles'    => ['admin', 'scolarite'],
        'children' => [
            [
                'key'   => 'etudiants',
                'label' => 'Gestion Étudiants',
                'url'   => '?page=etudiants',
                'icon'  => 'fa-users',
            ],
            // ...
        ],
    ],
]
```

**HTML produit** :
```html
<aside class="cm-sidebar" id="cmSidebar">
    <!-- Logo -->
    <div class="cm-sidebar__logo">
        <img src="/assets/img/logo.png" alt="CheckMaster" class="cm-sidebar__logo-img">
        <span class="cm-sidebar__logo-text">CheckMaster</span>
    </div>

    <!-- Menu -->
    <nav class="cm-sidebar__nav" role="navigation" aria-label="Menu principal">
        <ul class="cm-sidebar__menu">
            <!-- Élément sans sous-menu -->
            <li class="cm-sidebar__item [is-active]">
                <a href="{url}" class="cm-sidebar__link">
                    <i class="fas {icon} cm-sidebar__icon"></i>
                    <span class="cm-sidebar__label">{label}</span>
                </a>
            </li>

            <!-- Élément avec sous-menu -->
            <li class="cm-sidebar__item has-children [is-open]">
                <button class="cm-sidebar__link cm-sidebar__toggle-btn" aria-expanded="false">
                    <i class="fas {icon} cm-sidebar__icon"></i>
                    <span class="cm-sidebar__label">{label}</span>
                    <i class="fas fa-chevron-right cm-sidebar__arrow"></i>
                </button>
                <ul class="cm-sidebar__submenu">
                    <li class="cm-sidebar__subitem [is-active]">
                        <a href="{url}" class="cm-sidebar__sublink">
                            <i class="fas {icon}"></i>
                            <span>{label}</span>
                        </a>
                    </li>
                </ul>
            </li>
        </ul>
    </nav>

    <!-- Footer sidebar -->
    <div class="cm-sidebar__footer">
        <div class="cm-sidebar__user-info">
            <span class="cm-sidebar__user-name">{$user['prenom']} {$user['nom']}</span>
            <span class="cm-sidebar__user-role">{$user['role']}</span>
        </div>
    </div>
</aside>
```

**CSS requis** :
```css
.cm-sidebar {
    position: fixed; left: 0; top: 0; bottom: 0;
    width: var(--cm-sidebar-width);
    background: var(--cm-sidebar-bg);
    color: var(--cm-sidebar-text);
    display: flex; flex-direction: column;
    overflow-y: auto; overflow-x: hidden;
    z-index: var(--cm-z-sidebar);
    transition: transform var(--cm-transition-normal);
}
.cm-sidebar__submenu { display: none; }
.cm-sidebar__item.has-children.is-open .cm-sidebar__submenu { display: block; }
.cm-sidebar__item.has-children.is-open .cm-sidebar__arrow { transform: rotate(90deg); }
.cm-sidebar__arrow { transition: transform var(--cm-transition-fast); margin-left: auto; }
.cm-sidebar__link.is-active,
.cm-sidebar__sublink.is-active { background: var(--cm-sidebar-active-bg); }
.cm-sidebar__link:hover,
.cm-sidebar__sublink:hover { background: var(--cm-sidebar-hover-bg); }
/* Collapsed state */
.cm-main-wrapper.is-collapsed .cm-sidebar { transform: translateX(-100%); }
```

**JavaScript (inline dans le composant)** : Un `<script>` délégué sur `.cm-sidebar__toggle-btn` gère le `is-open` sur le `<li>` parent et l'attribut `aria-expanded`.

---

### 4.4 `page-header.php`

**Rôle** : En-tête de section de page (titre + badge année + fil d'Ariane).

**Props** :
```php
$title       = $title       ?? 'Page';       // Titre principal
$subtitle    = $subtitle    ?? '';            // Sous-titre optionnel
$annee       = $annee       ?? '';            // Badge année académique
$breadcrumbs = $breadcrumbs ?? [];            // [['label' => '...', 'url' => '...'], ...]
$icon        = $icon        ?? '';            // Icône Font Awesome ex: 'fa-users'
```

**HTML produit** :
```html
<div class="cm-page-header">
    <div class="cm-page-header__main">
        <div class="cm-page-header__title-group">
            <?php if ($icon): ?>
            <i class="fas <?= htmlspecialchars($icon) ?> cm-page-header__icon"></i>
            <?php endif; ?>
            <div>
                <h1 class="cm-page-header__title"><?= htmlspecialchars($title) ?></h1>
                <?php if ($subtitle): ?>
                <p class="cm-page-header__subtitle"><?= htmlspecialchars($subtitle) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php if ($annee): ?>
        <span class="cm-badge is-info cm-page-header__year">
            <i class="fas fa-calendar-alt"></i>
            <?= htmlspecialchars($annee) ?>
        </span>
        <?php endif; ?>
    </div>
    <?php if ($breadcrumbs): ?>
    <nav class="cm-breadcrumb" aria-label="Fil d'Ariane">
        <ol class="cm-breadcrumb__list">
            <?php foreach ($breadcrumbs as $i => $crumb): ?>
            <?php $is_last = ($i === count($breadcrumbs) - 1); ?>
            <li class="cm-breadcrumb__item <?= $is_last ? 'is-active' : '' ?>">
                <?php if (!$is_last && !empty($crumb['url'])): ?>
                <a href="<?= htmlspecialchars($crumb['url']) ?>" class="cm-breadcrumb__link">
                    <?= htmlspecialchars($crumb['label']) ?>
                </a>
                <i class="fas fa-chevron-right cm-breadcrumb__sep"></i>
                <?php else: ?>
                <span><?= htmlspecialchars($crumb['label']) ?></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>
    </nav>
    <?php endif; ?>
</div>
```

---

## 5. Composants UI (`ressources/components/ui/`)

### 5.1 `toast.php`

**Rôle** : Système de notifications flash (succès, erreur, avertissement, info). Lit `$_SESSION['toast']` et expose une fonction JS `cmToast()`.

**Props** : Aucune (lit directement la session)

**Session attendue** : `$_SESSION['toast'] = ['type' => 'success|error|warning|info', 'message' => '...']`

**HTML produit** :
```html
<!-- Conteneur de toasts (persistant dans le DOM) -->
<div class="cm-toast-container" id="cmToastContainer" role="alert" aria-live="polite"></div>

<?php if (!empty($_SESSION['toast'])): ?>
<script>
// Toast de session (déclenché au chargement)
document.addEventListener('DOMContentLoaded', function() {
    cmToast(
        '<?= addslashes($_SESSION['toast']['type']) ?>',
        '<?= addslashes($_SESSION['toast']['message']) ?>'
    );
});
</script>
<?php unset($_SESSION['toast']); ?>
<?php endif; ?>

<script>
/**
 * cmToast(type, message, duration)
 * Affiche un toast dans #cmToastContainer.
 * @param {string} type     'success'|'error'|'warning'|'info'
 * @param {string} message  Message à afficher
 * @param {number} duration Durée en ms (défaut: 4000)
 */
function cmToast(type, message, duration = 4000) {
    const icons = {
        success: 'fa-check-circle',
        error:   'fa-times-circle',
        warning: 'fa-exclamation-triangle',
        info:    'fa-info-circle'
    };
    const container = document.getElementById('cmToastContainer');
    const toast = document.createElement('div');
    toast.className = 'cm-toast is-' + type;
    toast.innerHTML =
        '<i class="fas ' + (icons[type] || 'fa-info-circle') + ' cm-toast__icon"></i>' +
        '<span class="cm-toast__message">' + message + '</span>' +
        '<button class="cm-toast__close" onclick="this.closest(\'.cm-toast\').remove()">' +
            '<i class="fas fa-times"></i>' +
        '</button>';
    container.appendChild(toast);
    // Animation entrée
    requestAnimationFrame(() => toast.classList.add('is-visible'));
    // Auto-dismiss
    setTimeout(() => {
        toast.classList.remove('is-visible');
        setTimeout(() => toast.remove(), 300);
    }, duration);
}
</script>
```

**CSS requis** :
```css
.cm-toast-container {
    position: fixed; bottom: var(--cm-spacing-lg); right: var(--cm-spacing-lg);
    z-index: var(--cm-z-toast); display: flex; flex-direction: column; gap: var(--cm-spacing-sm);
    max-width: 380px;
}
.cm-toast {
    display: flex; align-items: center; gap: var(--cm-spacing-sm);
    padding: var(--cm-spacing-sm) var(--cm-spacing-md);
    border-radius: var(--cm-border-radius); background: var(--cm-content-bg);
    border-left: 4px solid; box-shadow: var(--cm-shadow-lg);
    opacity: 0; transform: translateX(20px);
    transition: opacity var(--cm-transition-normal), transform var(--cm-transition-normal);
}
.cm-toast.is-visible     { opacity: 1; transform: translateX(0); }
.cm-toast.is-success     { border-color: var(--cm-feedback-success); }
.cm-toast.is-error       { border-color: var(--cm-feedback-danger); }
.cm-toast.is-warning     { border-color: var(--cm-feedback-warning); }
.cm-toast.is-info        { border-color: var(--cm-feedback-info); }
.cm-toast__icon          { font-size: 1.1rem; }
.cm-toast.is-success .cm-toast__icon { color: var(--cm-feedback-success); }
.cm-toast.is-error   .cm-toast__icon { color: var(--cm-feedback-danger); }
.cm-toast.is-warning .cm-toast__icon { color: var(--cm-feedback-warning); }
.cm-toast.is-info    .cm-toast__icon { color: var(--cm-feedback-info); }
.cm-toast__close { margin-left: auto; background: none; border: none; cursor: pointer; opacity: 0.5; }
.cm-toast__close:hover { opacity: 1; }
```

---

### 5.2 `badge.php`

**Rôle** : Badge de statut coloré (pill).

**Props** :
```php
$text  = $text  ?? '';        // Texte du badge
$type  = $type  ?? 'default'; // 'success'|'warning'|'danger'|'info'|'light'|'default'
$icon  = $icon  ?? '';        // Icône FA optionnelle ex: 'fa-check'
$title = $title ?? '';        // Attribut title pour accessibilité
```

**HTML produit** :
```html
<span class="cm-badge is-{$type}" <?= $title ? 'title="' . htmlspecialchars($title) . '"' : '' ?>>
    <?php if ($icon): ?>
    <i class="fas <?= htmlspecialchars($icon) ?>"></i>
    <?php endif; ?>
    <?= htmlspecialchars($text) ?>
</span>
```

**CSS requis** :
```css
.cm-badge {
    display: inline-flex; align-items: center; gap: 4px;
    padding: 2px 10px; border-radius: 999px;
    font-size: var(--cm-font-size-xs); font-weight: 600;
    white-space: nowrap; line-height: 1.5;
}
.cm-badge.is-success { background: #d4efdf; color: #1e8449; }
.cm-badge.is-warning { background: #fdebd0; color: #d35400; }
.cm-badge.is-danger  { background: #fadbd8; color: #cb4335; }
.cm-badge.is-info    { background: #d6eaf8; color: #1a6599; }
.cm-badge.is-light   { background: var(--cm-btn-light); color: var(--cm-primary); }
.cm-badge.is-default { background: #ecf0f1; color: #555; }
```

---

### 5.3 `alert-box.php`

**Rôle** : Boîte d'alerte inline non-dismissible (info contextuelle, avertissement global).

**Props** :
```php
$type    = $type    ?? 'info';   // 'success'|'warning'|'danger'|'info'
$message = $message ?? '';       // Message principal (HTML autorisé)
$title   = $title   ?? '';       // Titre optionnel en gras
$icon    = $icon    ?? '';       // Icône FA override (sinon icône par défaut selon type)
```

**Icônes par défaut** :
- `info` → `fa-info-circle`
- `success` → `fa-check-circle`
- `warning` → `fa-exclamation-triangle`
- `danger` → `fa-times-circle`

**HTML produit** :
```html
<div class="cm-alert is-{$type}" role="alert">
    <i class="fas {$resolved_icon} cm-alert__icon"></i>
    <div class="cm-alert__body">
        <?php if ($title): ?>
        <strong class="cm-alert__title"><?= htmlspecialchars($title) ?></strong>
        <?php endif; ?>
        <span class="cm-alert__message"><?= $message /* HTML autorisé */ ?></span>
    </div>
</div>
```

**CSS requis** :
```css
.cm-alert {
    display: flex; align-items: flex-start; gap: var(--cm-spacing-sm);
    padding: var(--cm-spacing-sm) var(--cm-spacing-md);
    border-radius: var(--cm-border-radius);
    border-left: 4px solid; margin-bottom: var(--cm-spacing-md);
}
.cm-alert.is-info    { background: #eaf4fd; border-color: var(--cm-feedback-info); }
.cm-alert.is-success { background: #eafaf1; border-color: var(--cm-feedback-success); }
.cm-alert.is-warning { background: #fef9e7; border-color: var(--cm-feedback-warning); }
.cm-alert.is-danger  { background: #fdedec; border-color: var(--cm-feedback-danger); }
.cm-alert__icon      { margin-top: 2px; font-size: 1rem; }
.cm-alert.is-info    .cm-alert__icon { color: var(--cm-feedback-info); }
.cm-alert.is-success .cm-alert__icon { color: var(--cm-feedback-success); }
.cm-alert.is-warning .cm-alert__icon { color: var(--cm-feedback-warning); }
.cm-alert.is-danger  .cm-alert__icon { color: var(--cm-feedback-danger); }
.cm-alert__title     { display: block; font-weight: 600; margin-bottom: 2px; }
```

---

### 5.4 `empty-state.php`

**Rôle** : Affichage "aucun résultat" dans un tableau ou une liste.

**Props** :
```php
$icon    = $icon    ?? 'fa-inbox';          // Icône FA
$title   = $title   ?? 'Aucun résultat';    // Titre
$message = $message ?? 'Aucune donnée à afficher pour les critères sélectionnés.';
$colspan = $colspan ?? 1;                   // Nombre de colonnes pour le <td> (si dans un tableau)
$action  = $action  ?? [];                  // ['label' => '...', 'url' => '...'] optionnel
```

**HTML produit** :
```html
<!-- Utilisation dans un tableau -->
<tr>
    <td colspan="{$colspan}" class="cm-empty-state">
        <i class="fas {$icon} cm-empty-state__icon"></i>
        <p class="cm-empty-state__title">{$title}</p>
        <p class="cm-empty-state__message">{$message}</p>
        <?php if ($action): ?>
        <a href="<?= htmlspecialchars($action['url']) ?>" class="cm-btn is-primary is-sm">
            <?= htmlspecialchars($action['label']) ?>
        </a>
        <?php endif; ?>
    </td>
</tr>

<!-- Utilisation hors tableau -->
<div class="cm-empty-state is-standalone">
    <i class="fas {$icon} cm-empty-state__icon"></i>
    ...
</div>
```

**CSS requis** :
```css
.cm-empty-state {
    text-align: center; padding: var(--cm-spacing-xl) var(--cm-spacing-md);
    color: var(--cm-text-muted, #7f8c8d);
}
.cm-empty-state__icon { font-size: 3rem; opacity: 0.3; display: block; margin-bottom: var(--cm-spacing-sm); }
.cm-empty-state__title { font-weight: 600; margin-bottom: var(--cm-spacing-xs); }
.cm-empty-state__message { font-size: var(--cm-font-size-sm); }
```

---

## 6. Composants CRUD (`ressources/components/crud/`)

### 6.1 `form-pole.php`

**Rôle** : Conteneur du pôle supérieur (zone de saisie/formulaire) dans la segmentation polarisée. Sticky en haut lors du scroll.

**Props** :
```php
$title   = $title   ?? '';    // Titre du formulaire ex: 'Ajouter un étudiant'
$form_id = $form_id ?? 'crudForm';  // ID de la balise <form>
$action  = $action  ?? '';    // Action du formulaire (?page=...&action=save)
$method  = $method  ?? 'POST';
$content = $content ?? '';    // HTML interne (champs du formulaire)
```

**HTML produit** :
```html
<div class="cm-pole-superieur" id="poleSup">
    <?php if ($title): ?>
    <div class="cm-pole-superieur__header">
        <h3 class="cm-pole-superieur__title">
            <i class="fas fa-edit"></i>
            <?= htmlspecialchars($title) ?>
        </h3>
    </div>
    <?php endif; ?>
    <form id="<?= htmlspecialchars($form_id) ?>"
          action="<?= htmlspecialchars($action) ?>"
          method="<?= htmlspecialchars($method) ?>"
          novalidate>
        <?php cm_component('form/csrf-token'); ?>
        <?= $content ?>
    </form>
</div>
```

**CSS requis** :
```css
.cm-pole-superieur {
    background: var(--cm-content-bg);
    border: 1px solid var(--cm-border-color);
    border-radius: var(--cm-border-radius-lg);
    padding: var(--cm-spacing-lg);
    margin-bottom: var(--cm-spacing-md);
    position: sticky; top: calc(var(--cm-header-height) + var(--cm-spacing-sm));
    z-index: 10;
    box-shadow: var(--cm-shadow-sm, 0 2px 4px rgba(0,0,0,0.06));
}
.cm-pole-superieur__header {
    border-bottom: 1px solid var(--cm-border-color);
    padding-bottom: var(--cm-spacing-sm);
    margin-bottom: var(--cm-spacing-md);
}
.cm-pole-superieur__title {
    font-size: var(--cm-font-size-base);
    font-weight: 600; color: var(--cm-primary);
    display: flex; align-items: center; gap: var(--cm-spacing-sm);
}
```

---

### 6.2 `toolbar.php`

**Rôle** : Barre d'outils intermédiaire (barre de recherche + actions en masse + exports).

**Props** :
```php
$search_placeholder = $search_placeholder ?? 'Rechercher...';
$search_id          = $search_id          ?? 'cmSearch';
$show_export        = $show_export        ?? true;   // Bouton Excel
$show_print         = $show_print         ?? true;   // Bouton Imprimer
$show_select_all    = $show_select_all    ?? true;   // Bouton Tout sélectionner
$show_delete_mass   = $show_delete_mass   ?? true;   // Bouton suppression en masse
$per_page_options   = $per_page_options   ?? [10, 25, 50, 100];
$per_page_current   = $per_page_current   ?? 25;
$extra_buttons      = $extra_buttons      ?? [];     // Boutons additionnels [['label','url','icon','class'], ...]
$form_id            = $form_id            ?? 'tableForm'; // ID du form pour les checkboxes
```

**HTML produit** :
```html
<div class="cm-barre-intermediaire">
    <!-- Recherche -->
    <div class="cm-toolbar__search">
        <i class="fas fa-search cm-toolbar__search-icon"></i>
        <input type="text"
               id="{$search_id}"
               class="cm-toolbar__search-input"
               placeholder="{$search_placeholder}"
               autocomplete="off">
    </div>

    <!-- Actions -->
    <div class="cm-toolbar__actions">
        <!-- Sélection -->
        <?php if ($show_select_all): ?>
        <button type="button" class="cm-btn is-light is-sm" id="selectAll" data-form="{$form_id}">
            <i class="fas fa-check-square"></i> Tout sélectionner
        </button>
        <button type="button" class="cm-btn is-light is-sm" id="deselectAll" data-form="{$form_id}">
            <i class="fas fa-square"></i> Désélectionner
        </button>
        <?php endif; ?>

        <!-- Suppression en masse -->
        <?php if ($show_delete_mass): ?>
        <button type="button" class="cm-btn is-light is-sm cm-btn--delete-mass" id="deleteMass"
                data-form="{$form_id}" style="display:none">
            <i class="fas fa-trash"></i> Supprimer (<span id="deleteCount">0</span>)
        </button>
        <?php endif; ?>

        <!-- Boutons extra -->
        <?php foreach ($extra_buttons as $btn): ?>
        <a href="<?= htmlspecialchars($btn['url'] ?? '#') ?>"
           class="cm-btn is-sm <?= htmlspecialchars($btn['class'] ?? 'is-light') ?>">
            <?php if (!empty($btn['icon'])): ?>
            <i class="fas <?= htmlspecialchars($btn['icon']) ?>"></i>
            <?php endif; ?>
            <?= htmlspecialchars($btn['label']) ?>
        </a>
        <?php endforeach; ?>

        <!-- Export Excel -->
        <?php if ($show_export): ?>
        <button type="button" class="cm-btn is-success is-sm" id="exportExcel">
            <i class="fas fa-file-excel"></i> Excel
        </button>
        <?php endif; ?>

        <!-- Impression -->
        <?php if ($show_print): ?>
        <button type="button" class="cm-btn is-info is-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimer
        </button>
        <?php endif; ?>

        <!-- Par page -->
        <div class="cm-toolbar__per-page">
            <label for="perPageSelect" class="cm-toolbar__per-page-label">Afficher</label>
            <select id="perPageSelect" class="cm-form-select is-sm" name="per_page">
                <?php foreach ($per_page_options as $opt): ?>
                <option value="<?= $opt ?>" <?= $opt == $per_page_current ? 'selected' : '' ?>>
                    <?= $opt ?>
                </option>
                <?php endforeach; ?>
            </select>
            <span class="cm-toolbar__per-page-label">entrées</span>
        </div>
    </div>
</div>
```

**JavaScript inline** : Le composant inclut un `<script>` minimal gérant :
- Filtre de recherche sur le tableau cible (délégation sur `$search_id`)
- `#selectAll` / `#deselectAll` cochant toutes les cases du formulaire
- Compteur live `#deleteCount` et affichage/masquage de `#deleteMass`
- `#exportExcel` : déclenchement de `?...&export=excel`

**CSS requis** :
```css
.cm-barre-intermediaire {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: var(--cm-spacing-sm); padding: var(--cm-spacing-sm) 0;
    margin-bottom: var(--cm-spacing-sm);
}
.cm-toolbar__search { position: relative; flex: 1; min-width: 200px; max-width: 400px; }
.cm-toolbar__search-icon {
    position: absolute; left: 10px; top: 50%; transform: translateY(-50%);
    color: var(--cm-text-muted, #7f8c8d); pointer-events: none;
}
.cm-toolbar__search-input {
    width: 100%; padding: 6px 10px 6px 32px;
    border: 1px solid var(--cm-border-color);
    border-radius: var(--cm-border-radius);
    font-size: var(--cm-font-size-sm);
}
.cm-toolbar__actions { display: flex; align-items: center; flex-wrap: wrap; gap: var(--cm-spacing-xs); margin-left: auto; }
.cm-toolbar__per-page { display: flex; align-items: center; gap: 4px; font-size: var(--cm-font-size-sm); }
```

---

### 6.3 `data-table.php`

**Rôle** : Tableau de données avec checkboxes, colonnes triables, colonne d'actions.

**Props** :
```php
$columns       = $columns       ?? [];     // Tableau de colonnes via cm_column() / cm_action_column()
$rows          = $rows          ?? [];     // Données (tableau associatif)
$table_id      = $table_id      ?? 'cmDataTable';
$form_id       = $form_id       ?? 'tableForm';    // ID du <form> englobant
$row_id_key    = $row_id_key    ?? 'id';           // Clé utilisée comme valeur de checkbox
$sort_key      = $sort_key      ?? '';             // Colonne triée actuellement
$sort_dir      = $sort_dir      ?? 'asc';          // 'asc'|'desc'
$base_url      = $base_url      ?? '';             // URL de base pour les liens de tri
$show_checkbox = $show_checkbox ?? true;           // Afficher la colonne de checkboxes
$caption       = $caption       ?? '';             // <caption> pour accessibilité
```

**HTML produit** :
```html
<div class="cm-table-wrapper">
    <form id="{$form_id}" method="POST">
        <?php cm_component('form/csrf-token'); ?>
        <table class="cm-data-table" id="{$table_id}" role="grid">
            <?php if ($caption): ?>
            <caption class="cm-visually-hidden"><?= htmlspecialchars($caption) ?></caption>
            <?php endif; ?>
            <thead>
                <tr>
                    <!-- Checkbox globale -->
                    <?php if ($show_checkbox): ?>
                    <th class="cm-data-table__th is-checkbox" scope="col">
                        <input type="checkbox" class="cm-checkbox" id="checkAll_{$table_id}"
                               aria-label="Tout sélectionner">
                    </th>
                    <?php endif; ?>
                    <!-- Colonnes -->
                    <?php foreach ($columns as $col): ?>
                    <th class="cm-data-table__th <?= $col['align'] === 'center' ? 'is-center' : '' ?>"
                        scope="col"
                        <?= $col['width'] ? 'style="width:' . $col['width'] . '"' : '' ?>>
                        <?php if ($col['sortable']): ?>
                        <a href="{sort_url}" class="cm-data-table__sort-link
                            <?= $sort_key === $col['key'] ? 'is-sorted is-' . $sort_dir : '' ?>">
                            <?= htmlspecialchars($col['label']) ?>
                            <i class="fas fa-sort<?= $sort_key === $col['key'] ? ($sort_dir === 'asc' ? '-up' : '-down') : '' ?>"></i>
                        </a>
                        <?php else: ?>
                        <?= htmlspecialchars($col['label']) ?>
                        <?php endif; ?>
                    </th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="{$table_id}_body">
                <?php if (empty($rows)): ?>
                <?php cm_component('ui/empty-state', ['colspan' => count($columns) + ($show_checkbox ? 1 : 0)]); ?>
                <?php else: ?>
                <?php foreach ($rows as $row): ?>
                <tr class="cm-data-table__row" data-id="<?= htmlspecialchars($row[$row_id_key]) ?>">
                    <?php if ($show_checkbox): ?>
                    <td class="cm-data-table__td is-checkbox">
                        <input type="checkbox" name="selected[]"
                               value="<?= htmlspecialchars($row[$row_id_key]) ?>"
                               class="cm-checkbox cm-row-checkbox">
                    </td>
                    <?php endif; ?>
                    <?php foreach ($columns as $col): ?>
                    <td class="cm-data-table__td <?= $col['align'] === 'center' ? 'is-center' : '' ?>">
                        <?php
                        if ($col['type'] === 'actions') {
                            // Colonne d'actions
                            echo '<div class="cm-row-actions">';
                            foreach ($col['actions'] as $act) {
                                $url = str_replace('{id}', $row[$row_id_key], $act['url'] ?? '#');
                                echo '<a href="' . htmlspecialchars($url) . '" class="' . htmlspecialchars($act['class'] ?? 'cm-btn-action') . '" title="' . htmlspecialchars($act['label']) . '">';
                                echo '<i class="fas ' . htmlspecialchars($act['icon']) . '"></i>';
                                echo '</a>';
                            }
                            echo '</div>';
                        } elseif ($col['type'] === 'badge') {
                            $val = $row[$col['key']] ?? '';
                            cm_component('ui/badge', is_array($val) ? $val : ['text' => $val]);
                        } elseif (is_callable($col['format'])) {
                            echo ($col['format'])($row[$col['key']] ?? '', $row);
                        } else {
                            echo htmlspecialchars($row[$col['key']] ?? '');
                        }
                        ?>
                    </td>
                    <?php endforeach; ?>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </form>
</div>
```

**CSS requis** :
```css
.cm-table-wrapper { overflow-x: auto; background: var(--cm-content-bg); }
.cm-data-table { width: 100%; border-collapse: collapse; font-size: var(--cm-font-size-sm); }
.cm-data-table__th {
    padding: var(--cm-spacing-sm) var(--cm-spacing-md);
    background: var(--cm-primary); color: #fff;
    text-align: left; font-weight: 600;
    white-space: nowrap; position: sticky; top: 0; z-index: 1;
}
.cm-data-table__th.is-checkbox { width: 40px; text-align: center; }
.cm-data-table__th.is-center   { text-align: center; }
.cm-data-table__td { padding: var(--cm-spacing-xs) var(--cm-spacing-md); border-bottom: 1px solid var(--cm-border-color); }
.cm-data-table__td.is-center   { text-align: center; }
.cm-data-table__row:hover      { background: #f8fbff; }
.cm-data-table__row.is-selected { background: #eaf4fd; }
.cm-data-table__sort-link { color: inherit; text-decoration: none; display: flex; align-items: center; gap: 4px; }
.cm-row-actions { display: flex; gap: 4px; justify-content: center; }
.cm-btn-action { padding: 4px 8px; border-radius: var(--cm-border-radius); font-size: 12px; border: none; cursor: pointer; text-decoration: none; display: inline-flex; align-items: center; }
.cm-btn-action.is-edit   { background: var(--cm-btn-info); color: #fff; }
.cm-btn-action.is-delete { background: #c0392b; color: #fff; }
.cm-btn-action:hover     { opacity: 0.85; }
```

---

### 6.4 `pagination.php`

**Rôle** : Barre de pagination générée à partir des données de `cm_paginate()`.

**Props** :
```php
$pagination = $pagination ?? [];   // Résultat de cm_paginate()
$base_url   = $base_url   ?? '?'; // URL de base à laquelle on ajoute &page=N
$param_name = $param_name ?? 'page_num'; // Nom du paramètre GET de page
```

**HTML produit** :
```html
<?php if ($pagination['last'] > 1): ?>
<nav class="cm-pagination" role="navigation" aria-label="Pagination">
    <div class="cm-pagination__info">
        Affichage de
        <?= $pagination['offset'] + 1 ?> à
        <?= min($pagination['offset'] + $pagination['per_page'], $pagination['total']) ?> sur
        <?= $pagination['total'] ?> entrées
    </div>
    <ul class="cm-pagination__list">
        <!-- Précédent -->
        <li class="cm-pagination__item <?= !$pagination['has_prev'] ? 'is-disabled' : '' ?>">
            <a href="{prev_url}" class="cm-pagination__link" aria-label="Page précédente">
                <i class="fas fa-chevron-left"></i>
            </a>
        </li>
        <!-- Pages -->
        <?php foreach ($pagination['pages'] as $p): ?>
        <?php if ($p === -1): ?>
        <li class="cm-pagination__item is-ellipsis">
            <span class="cm-pagination__link">…</span>
        </li>
        <?php else: ?>
        <li class="cm-pagination__item <?= $p === $pagination['current'] ? 'is-active' : '' ?>">
            <a href="{page_url}" class="cm-pagination__link" aria-label="Page <?= $p ?>"
               <?= $p === $pagination['current'] ? 'aria-current="page"' : '' ?>>
                <?= $p ?>
            </a>
        </li>
        <?php endif; ?>
        <?php endforeach; ?>
        <!-- Suivant -->
        <li class="cm-pagination__item <?= !$pagination['has_next'] ? 'is-disabled' : '' ?>">
            <a href="{next_url}" class="cm-pagination__link" aria-label="Page suivante">
                <i class="fas fa-chevron-right"></i>
            </a>
        </li>
    </ul>
</nav>
<?php endif; ?>
```

**CSS requis** :
```css
.cm-pagination { display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: var(--cm-spacing-sm); padding: var(--cm-spacing-sm) 0; }
.cm-pagination__info { font-size: var(--cm-font-size-sm); color: var(--cm-text-muted, #7f8c8d); }
.cm-pagination__list { display: flex; list-style: none; gap: 2px; margin: 0; padding: 0; }
.cm-pagination__link {
    display: flex; align-items: center; justify-content: center;
    min-width: 32px; height: 32px; padding: 0 8px;
    border: 1px solid var(--cm-border-color); border-radius: var(--cm-border-radius);
    text-decoration: none; color: var(--cm-primary); font-size: var(--cm-font-size-sm);
    transition: background var(--cm-transition-fast);
}
.cm-pagination__link:hover { background: var(--cm-primary-lightest); }
.cm-pagination__item.is-active .cm-pagination__link { background: var(--cm-primary); color: #fff; border-color: var(--cm-primary); }
.cm-pagination__item.is-disabled .cm-pagination__link { opacity: 0.4; pointer-events: none; }
.cm-pagination__item.is-ellipsis .cm-pagination__link { border: none; cursor: default; }
```

---

### 6.5 `form-actions.php`

**Rôle** : Groupe de boutons d'action du formulaire (Enregistrer / Modifier / Réinitialiser / Annuler).

**Props** :
```php
$mode           = $mode           ?? 'create';  // 'create'|'edit'
$submit_label   = $submit_label   ?? '';         // Override du libellé du bouton principal
$show_reset     = $show_reset     ?? true;       // Afficher Réinitialiser
$show_cancel    = $show_cancel    ?? true;       // Afficher Annuler
$cancel_url     = $cancel_url     ?? '#';        // URL du bouton Annuler
$form_id        = $form_id        ?? 'crudForm';
$extra_actions  = $extra_actions  ?? [];         // Boutons additionnels
```

**Logique** :
- `mode = 'create'` → bouton principal = "Enregistrer" (icon `fa-save`)
- `mode = 'edit'` → bouton principal = "Modifier" (icon `fa-pencil-alt`)
- `$submit_label` override si fourni

**HTML produit** :
```html
<div class="cm-form-actions">
    <button type="submit" form="{$form_id}" class="cm-btn is-primary">
        <i class="fas {icon}"></i>
        {$resolved_label}
    </button>

    <?php foreach ($extra_actions as $act): ?>
    <button type="button" class="cm-btn <?= htmlspecialchars($act['class'] ?? 'is-info') ?>">
        <?php if (!empty($act['icon'])): ?>
        <i class="fas <?= htmlspecialchars($act['icon']) ?>"></i>
        <?php endif; ?>
        <?= htmlspecialchars($act['label']) ?>
    </button>
    <?php endforeach; ?>

    <?php if ($show_reset): ?>
    <button type="reset" form="{$form_id}" class="cm-btn is-light">
        <i class="fas fa-undo"></i> Réinitialiser
    </button>
    <?php endif; ?>

    <?php if ($show_cancel): ?>
    <a href="{$cancel_url}" class="cm-btn is-light">
        <i class="fas fa-times"></i> Annuler
    </a>
    <?php endif; ?>
</div>
```

**CSS requis** :
```css
.cm-form-actions {
    display: flex; align-items: center; flex-wrap: wrap;
    gap: var(--cm-spacing-sm); padding-top: var(--cm-spacing-md);
    border-top: 1px solid var(--cm-border-color);
    margin-top: var(--cm-spacing-md);
}
/* Bouton générique */
.cm-btn {
    display: inline-flex; align-items: center; gap: var(--cm-spacing-xs);
    padding: 8px 16px; border-radius: var(--cm-border-radius);
    font-size: var(--cm-font-size-sm); font-weight: 500;
    border: none; cursor: pointer; text-decoration: none;
    transition: background var(--cm-transition-fast), opacity var(--cm-transition-fast);
    white-space: nowrap;
}
.cm-btn.is-primary  { background: var(--cm-btn-primary); color: #fff; }
.cm-btn.is-primary:hover { background: var(--cm-btn-primary-hover); }
.cm-btn.is-success  { background: var(--cm-btn-success); color: #fff; }
.cm-btn.is-success:hover { background: var(--cm-btn-success-hover); }
.cm-btn.is-info     { background: var(--cm-btn-info); color: #fff; }
.cm-btn.is-info:hover    { background: var(--cm-btn-info-hover); }
.cm-btn.is-light    { background: var(--cm-btn-light); color: var(--cm-primary); }
.cm-btn.is-light:hover   { background: var(--cm-btn-light-hover); }
.cm-btn.is-sm { padding: 5px 10px; font-size: var(--cm-font-size-xs); }
```

---

## 7. Composants Formulaire (`ressources/components/form/`)

### 7.1 Convention commune à tous les champs

Chaque composant de champ respecte la structure suivante :

```html
<div class="cm-form-group <?= $required ? 'is-required' : '' ?> <?= $error ? 'is-invalid' : '' ?>">
    <label for="{$id}" class="cm-form-label">{$label}</label>
    <input ... class="cm-form-control <?= $error ? 'is-invalid' : '' ?>">
    <?php if ($error): ?>
    <span class="cm-form-error" role="alert"><?= htmlspecialchars($error) ?></span>
    <?php endif; ?>
    <?php if ($hint): ?>
    <span class="cm-form-hint"><?= htmlspecialchars($hint) ?></span>
    <?php endif; ?>
</div>
```

**CSS commun** :
```css
.cm-form-group { margin-bottom: var(--cm-spacing-md); }
.cm-form-label { display: block; font-size: var(--cm-font-size-sm); font-weight: 600; margin-bottom: 4px; color: var(--cm-primary); }
.cm-form-group.is-required .cm-form-label::after { content: ' *'; color: var(--cm-feedback-danger); }
.cm-form-control {
    width: 100%; padding: 7px 10px;
    border: 1px solid var(--cm-border-color);
    border-radius: var(--cm-border-radius);
    font-size: var(--cm-font-size-sm);
    transition: border-color var(--cm-transition-fast), box-shadow var(--cm-transition-fast);
}
.cm-form-control:focus {
    outline: none;
    border-color: var(--cm-primary-light);
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
}
.cm-form-control.is-invalid { border-color: var(--cm-feedback-danger); }
.cm-form-error { font-size: var(--cm-font-size-xs); color: var(--cm-feedback-danger); margin-top: 2px; display: block; }
.cm-form-hint  { font-size: var(--cm-font-size-xs); color: var(--cm-text-muted, #7f8c8d); margin-top: 2px; display: block; }
.cm-form-select { appearance: none; background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='10' height='6'%3E%3Cpath d='M0 0l5 6 5-6z' fill='%23666'/%3E%3C/svg%3E"); background-repeat: no-repeat; background-position: right 10px center; padding-right: 28px; }
```

---

### 7.2 Props communes à tous les champs formulaire

```php
$name     = $name     ?? '';       // Attribut name
$id       = $id       ?? $name;    // Attribut id (= name par défaut)
$label    = $label    ?? '';       // Label affiché
$value    = $value    ?? '';       // Valeur initiale
$required = $required ?? false;    // Champ obligatoire
$disabled = $disabled ?? false;    // Champ désactivé
$readonly = $readonly ?? false;    // Champ lecture seule
$hint     = $hint     ?? '';       // Texte d'aide
$error    = $error    ?? cm_error($name);   // Message d'erreur
$attrs    = $attrs    ?? [];       // Attributs HTML supplémentaires [clé => valeur]
```

---

### 7.3 `input-text.php`

Props supplémentaires :
```php
$placeholder = $placeholder ?? '';
$maxlength   = $maxlength   ?? '';
$pattern     = $pattern     ?? '';
$autocomplete = $autocomplete ?? 'off';
```

---

### 7.4 `input-date.php`

Props supplémentaires :
```php
$min  = $min  ?? '';   // Date min ISO
$max  = $max  ?? '';   // Date max ISO
$type = $type ?? 'date'; // 'date'|'datetime-local'|'month'
```

---

### 7.5 `input-email.php`

Props supplémentaires :
```php
$placeholder = $placeholder ?? 'exemple@domaine.ci';
```

---

### 7.6 `input-password.php`

**Particularité** : Inclut un bouton œil pour toggle de visibilité.

Props supplémentaires :
```php
$show_toggle  = $show_toggle  ?? true;   // Bouton afficher/masquer
$autocomplete = $autocomplete ?? 'current-password';
```

---

### 7.7 `input-number.php`

Props supplémentaires :
```php
$min  = $min  ?? '';
$max  = $max  ?? '';
$step = $step ?? '1';
```

---

### 7.8 `select.php`

Props supplémentaires :
```php
$options       = $options       ?? [];    // [['value' => ..., 'label' => ...], ...] ou ['val' => 'label', ...]
$placeholder   = $placeholder   ?? '-- Sélectionner --';
$multiple      = $multiple      ?? false;
$selected      = $selected      ?? $value;
```

**Normalisation des options** : Le composant accepte les deux formats (tableau associatif ou tableau d'objets).

---

### 7.9 `select-search.php`

**Rôle** : Select avec recherche en temps réel (filtrage client-side via JS).

Props : identiques à `select.php` +
```php
$search_placeholder = $search_placeholder ?? 'Rechercher...';
$min_search         = $min_search         ?? 5;   // Nb d'options minimum pour afficher la recherche
```

**HTML produit** : Un `<div class="cm-select-search">` contenant un champ de recherche + la liste filtrée. JS intégré dans le composant.

---

### 7.10 `textarea.php`

Props supplémentaires :
```php
$rows       = $rows       ?? 4;
$maxlength  = $maxlength  ?? '';
$resize     = $resize     ?? 'vertical';   // 'none'|'horizontal'|'vertical'|'both'
```

---

### 7.11 `file-upload.php`

Props supplémentaires :
```php
$accept       = $accept       ?? '';         // Ex: '.pdf,.doc'
$max_size_mb  = $max_size_mb  ?? 10;
$show_preview = $show_preview ?? false;      // Prévisualisation image
```

**HTML produit** : Zone de drop stylisée + input file + texte indicatif de taille max + validation client-side JS.

---

### 7.12 `csrf-token.php`

```html
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars(cm_csrf_token()) ?>">
```

---

## 8. Composants Dashboard (`ressources/components/dashboard/`)

### 8.1 `stat-widget.php`

**Rôle** : Carte statistique avec icône, valeur numérique, libellé et tendance optionnelle.

**Props** :
```php
$value    = $value    ?? '0';
$label    = $label    ?? 'Statistique';
$icon     = $icon     ?? 'fa-chart-bar';
$color    = $color    ?? 'primary';  // 'primary'|'success'|'warning'|'info'|'danger'
$trend    = $trend    ?? null;       // null ou ['value' => '+12%', 'direction' => 'up'|'down', 'label' => 'vs mois dernier']
$link     = $link     ?? '';         // URL de détail optionnelle
$subtitle = $subtitle ?? '';         // Texte secondaire sous la valeur
```

**HTML produit** :
```html
<div class="cm-stat-card is-{$color} <?= $link ? 'is-clickable' : '' ?>"
     <?= $link ? 'onclick="location.href=\'' . htmlspecialchars($link) . '\'"' : '' ?>>
    <div class="cm-stat-card__icon">
        <i class="fas <?= htmlspecialchars($icon) ?>"></i>
    </div>
    <div class="cm-stat-card__body">
        <div class="cm-stat-card__value"><?= htmlspecialchars($value) ?></div>
        <div class="cm-stat-card__label"><?= htmlspecialchars($label) ?></div>
        <?php if ($subtitle): ?>
        <div class="cm-stat-card__subtitle"><?= htmlspecialchars($subtitle) ?></div>
        <?php endif; ?>
        <?php if ($trend): ?>
        <div class="cm-stat-card__trend is-<?= $trend['direction'] ?>">
            <i class="fas fa-arrow-<?= $trend['direction'] ?>"></i>
            <?= htmlspecialchars($trend['value']) ?>
            <?php if (!empty($trend['label'])): ?>
            <span class="cm-stat-card__trend-label"><?= htmlspecialchars($trend['label']) ?></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>
```

**CSS requis** :
```css
.cm-stat-card {
    background: var(--cm-content-bg); border-radius: var(--cm-border-radius-lg);
    padding: var(--cm-spacing-lg); display: flex; align-items: center; gap: var(--cm-spacing-md);
    box-shadow: var(--cm-shadow-sm, 0 2px 4px rgba(0,0,0,0.06));
    border-left: 4px solid;
}
.cm-stat-card.is-primary  { border-color: var(--cm-primary); }
.cm-stat-card.is-success  { border-color: var(--cm-feedback-success); }
.cm-stat-card.is-warning  { border-color: var(--cm-feedback-warning); }
.cm-stat-card.is-info     { border-color: var(--cm-feedback-info); }
.cm-stat-card.is-danger   { border-color: var(--cm-feedback-danger); }
.cm-stat-card__icon {
    width: 52px; height: 52px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-size: 1.4rem; flex-shrink: 0;
}
.cm-stat-card.is-primary .cm-stat-card__icon { background: rgba(26,82,118,0.12); color: var(--cm-primary); }
.cm-stat-card.is-success .cm-stat-card__icon { background: rgba(39,174,96,0.12); color: var(--cm-feedback-success); }
.cm-stat-card.is-warning .cm-stat-card__icon { background: rgba(243,156,18,0.12); color: var(--cm-feedback-warning); }
.cm-stat-card.is-info    .cm-stat-card__icon { background: rgba(52,152,219,0.12); color: var(--cm-feedback-info); }
.cm-stat-card.is-danger  .cm-stat-card__icon { background: rgba(231,76,60,0.12); color: var(--cm-feedback-danger); }
.cm-stat-card__value { font-size: 2rem; font-weight: 700; line-height: 1; }
.cm-stat-card__label { font-size: var(--cm-font-size-sm); color: var(--cm-text-muted, #7f8c8d); margin-top: 2px; }
.cm-stat-card__trend { font-size: var(--cm-font-size-xs); margin-top: var(--cm-spacing-xs); display: flex; align-items: center; gap: 4px; }
.cm-stat-card__trend.is-up   { color: var(--cm-feedback-success); }
.cm-stat-card__trend.is-down { color: var(--cm-feedback-danger); }
.cm-stat-card.is-clickable   { cursor: pointer; transition: box-shadow var(--cm-transition-fast); }
.cm-stat-card.is-clickable:hover { box-shadow: 0 4px 12px rgba(0,0,0,0.12); }
```

---

### 8.2 `chart-container.php`

**Rôle** : Conteneur de graphique Chart.js avec titre, sous-titre et canvas.

**Props** :
```php
$chart_id   = $chart_id   ?? 'cmChart_' . uniqid();
$title      = $title      ?? '';
$subtitle   = $subtitle   ?? '';
$height     = $height     ?? '300px';
$type       = $type       ?? 'bar';     // 'bar'|'line'|'pie'|'doughnut'
$data       = $data       ?? [];        // Données Chart.js : {labels:[], datasets:[]}
$options    = $options    ?? [];        // Options Chart.js override
```

**HTML produit** :
```html
<div class="cm-chart-container">
    <?php if ($title): ?>
    <div class="cm-chart-container__header">
        <h3 class="cm-chart-container__title"><?= htmlspecialchars($title) ?></h3>
        <?php if ($subtitle): ?>
        <p class="cm-chart-container__subtitle"><?= htmlspecialchars($subtitle) ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="cm-chart-container__body" style="height: <?= htmlspecialchars($height) ?>">
        <canvas id="<?= htmlspecialchars($chart_id) ?>" aria-label="<?= htmlspecialchars($title) ?>" role="img"></canvas>
    </div>
</div>
<script>
(function() {
    const ctx = document.getElementById('<?= $chart_id ?>');
    if (!ctx || !window.Chart) return;
    new Chart(ctx, {
        type: '<?= $type ?>',
        data: <?= json_encode($data) ?>,
        options: Object.assign({
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { position: 'bottom' } }
        }, <?= json_encode($options) ?>)
    });
})();
</script>
```

---

### 8.3 `activity-list.php`

**Rôle** : Liste d'activités récentes (flux d'événements horodatés).

**Props** :
```php
$title     = $title     ?? 'Activités récentes';
$items     = $items     ?? [];   // [['icon', 'text', 'time', 'type'], ...]
$max_items = $max_items ?? 5;
$view_all  = $view_all  ?? '';   // URL "Voir tout"
```

**HTML produit** : `.cm-activity-list` + items avec icône colorée selon `type` (success/warning/info/danger), texte, horodatage relatif.

---

### 8.4 `alert-list.php`

**Rôle** : Liste d'alertes/notifications en attente (rappels, deadlines).

**Props** :
```php
$title    = $title    ?? 'Alertes';
$items    = $items    ?? [];   // [['message', 'type', 'action_url', 'action_label'], ...]
$max_show = $max_show ?? 3;
```

---

## 9. Composants Hub (`ressources/components/hub/`)

### 9.1 `hub-tile.php`

**Rôle** : Tuile de navigation vers un sous-module (page Hub d'accueil de module).

**Props** :
```php
$title       = $title       ?? '';
$description = $description ?? '';
$icon        = $icon        ?? 'fa-folder';
$url         = $url         ?? '#';
$color       = $color       ?? 'primary';   // Couleur d'accent de la tuile
$count       = $count       ?? null;        // Compteur optionnel (nb d'éléments)
$disabled    = $disabled    ?? false;       // Tuile grisée (accès refusé)
```

**HTML produit** :
```html
<a href="{url}" class="cm-hub-tile is-{$color} <?= $disabled ? 'is-disabled' : '' ?>"
   <?= $disabled ? 'aria-disabled="true"' : '' ?>>
    <div class="cm-hub-tile__icon">
        <i class="fas {$icon}"></i>
    </div>
    <div class="cm-hub-tile__body">
        <h3 class="cm-hub-tile__title">{$title}</h3>
        <p class="cm-hub-tile__desc">{$description}</p>
    </div>
    <?php if ($count !== null): ?>
    <span class="cm-hub-tile__count"><?= $count ?></span>
    <?php endif; ?>
    <i class="fas fa-chevron-right cm-hub-tile__arrow"></i>
</a>
```

**CSS requis** :
```css
.cm-hub-tile {
    display: flex; align-items: center; gap: var(--cm-spacing-md);
    padding: var(--cm-spacing-lg); background: var(--cm-content-bg);
    border-radius: var(--cm-border-radius-lg); border: 1px solid var(--cm-border-color);
    text-decoration: none; color: inherit;
    transition: box-shadow var(--cm-transition-fast), transform var(--cm-transition-fast);
    border-left: 4px solid;
}
.cm-hub-tile:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.1); transform: translateY(-2px); }
.cm-hub-tile.is-primary  { border-left-color: var(--cm-primary); }
.cm-hub-tile.is-success  { border-left-color: var(--cm-feedback-success); }
.cm-hub-tile.is-info     { border-left-color: var(--cm-feedback-info); }
.cm-hub-tile.is-warning  { border-left-color: var(--cm-feedback-warning); }
.cm-hub-tile.is-disabled { opacity: 0.5; pointer-events: none; }
.cm-hub-tile__icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; flex-shrink: 0; }
.cm-hub-tile.is-primary .cm-hub-tile__icon { background: rgba(26,82,118,0.1); color: var(--cm-primary); }
.cm-hub-tile__title { font-weight: 600; font-size: var(--cm-font-size-base); margin-bottom: 2px; }
.cm-hub-tile__desc  { font-size: var(--cm-font-size-xs); color: var(--cm-text-muted, #7f8c8d); }
.cm-hub-tile__count { margin-left: auto; background: var(--cm-primary); color: #fff; border-radius: 999px; padding: 2px 10px; font-size: var(--cm-font-size-xs); font-weight: 700; }
.cm-hub-tile__arrow { color: var(--cm-border-color); margin-left: var(--cm-spacing-xs); }
```

---

### 9.2 `hub-grid.php`

**Rôle** : Grille conteneur pour les tuiles Hub.

**Props** :
```php
$tiles   = $tiles   ?? [];   // Tableau de props pour hub-tile
$columns = $columns ?? 2;    // 1|2|3|4 colonnes
$title   = $title   ?? '';   // Titre de section optionnel
```

**HTML produit** : `.cm-hub-grid.has-{$columns}-cols` + boucle sur `$tiles` → `cm_component('hub/hub-tile', $tile)`.

**CSS** :
```css
.cm-hub-grid { display: grid; gap: var(--cm-spacing-md); }
.cm-hub-grid.has-2-cols { grid-template-columns: repeat(2, 1fr); }
.cm-hub-grid.has-3-cols { grid-template-columns: repeat(3, 1fr); }
.cm-hub-grid.has-4-cols { grid-template-columns: repeat(4, 1fr); }
@media (max-width: 768px) {
    .cm-hub-grid.has-2-cols,
    .cm-hub-grid.has-3-cols,
    .cm-hub-grid.has-4-cols { grid-template-columns: 1fr; }
}
```

---

## 10. Composants Onglets (`ressources/components/tabs/`)

### 10.1 `tab-nav.php`

**Rôle** : Barre de navigation par onglets.

**Props** :
```php
$tabs      = $tabs      ?? [];      // [['key', 'label', 'icon', 'count'], ...]
$active    = $active    ?? '';      // Clé de l'onglet actif
$container = $container ?? 'cmTabsContainer';  // ID du conteneur parent
```

**HTML produit** :
```html
<div class="cm-tab-nav" role="tablist">
    <?php foreach ($tabs as $tab): ?>
    <button class="cm-tab-nav__item <?= $tab['key'] === $active ? 'is-active' : '' ?>"
            role="tab"
            aria-selected="<?= $tab['key'] === $active ? 'true' : 'false' ?>"
            aria-controls="tab-panel-{$tab['key']}"
            data-tab="<?= htmlspecialchars($tab['key']) ?>"
            id="tab-btn-<?= htmlspecialchars($tab['key']) ?>">
        <?php if (!empty($tab['icon'])): ?>
        <i class="fas <?= htmlspecialchars($tab['icon']) ?>"></i>
        <?php endif; ?>
        <?= htmlspecialchars($tab['label']) ?>
        <?php if (isset($tab['count'])): ?>
        <span class="cm-tab-nav__count"><?= intval($tab['count']) ?></span>
        <?php endif; ?>
    </button>
    <?php endforeach; ?>
</div>
```

**CSS requis** :
```css
.cm-tab-nav { display: flex; border-bottom: 2px solid var(--cm-border-color); gap: 0; margin-bottom: var(--cm-spacing-md); overflow-x: auto; }
.cm-tab-nav__item {
    padding: 10px var(--cm-spacing-lg); background: none; border: none;
    cursor: pointer; font-size: var(--cm-font-size-sm); font-weight: 500;
    color: var(--cm-text-muted, #7f8c8d); border-bottom: 2px solid transparent;
    margin-bottom: -2px; white-space: nowrap; display: flex; align-items: center; gap: 6px;
    transition: color var(--cm-transition-fast), border-color var(--cm-transition-fast);
}
.cm-tab-nav__item:hover { color: var(--cm-primary); }
.cm-tab-nav__item.is-active { color: var(--cm-primary); border-bottom-color: var(--cm-primary); }
.cm-tab-nav__count { background: var(--cm-primary); color: #fff; border-radius: 999px; padding: 1px 7px; font-size: 11px; }
```

**JavaScript** (inline dans le composant) : Délégation sur `.cm-tab-nav__item` → masque/affiche le panneau `.cm-tab-panel` correspondant via `data-tab`.

---

### 10.2 `tab-content.php`

**Rôle** : Panneau de contenu d'un onglet.

**Props** :
```php
$tab_key = $tab_key ?? '';     // Doit correspondre à la clé dans tab-nav
$active  = $active  ?? false;  // Visible par défaut
$content = $content ?? '';     // HTML du panneau
```

**HTML produit** :
```html
<div class="cm-tab-panel <?= $active ? 'is-active' : '' ?>"
     id="tab-panel-{$tab_key}"
     role="tabpanel"
     aria-labelledby="tab-btn-{$tab_key}"
     <?= !$active ? 'hidden' : '' ?>>
    <?= $content ?>
</div>
```

---

## 11. Composants Éditeur (`ressources/components/editor/`)

### 11.1 `wysiwyg-editor.php`

**Rôle** : Éditeur de texte riche (barre d'outils + zone éditable contenteditable ou intégration Quill.js).

**Props** :
```php
$name        = $name        ?? 'content';
$id          = $id          ?? 'cmEditor';
$label       = $label       ?? '';
$value       = $value       ?? '';
$required    = $required    ?? false;
$min_words   = $min_words   ?? 0;    // Validation : nombre minimum de mots
$max_words   = $max_words   ?? 0;    // 0 = pas de limite
$show_counter = $show_counter ?? true;
$height      = $height      ?? '300px';
$toolbar     = $toolbar     ?? 'full';  // 'full'|'minimal'
```

**Architecture** : Le composant utilise une `<div contenteditable>` ou Quill.js (CDN), avec un `<input type="hidden">` synchronisé pour la soumission du formulaire.

**HTML produit** :
```html
<div class="cm-form-group cm-editor-group <?= $required ? 'is-required' : '' ?>">
    <?php if ($label): ?>
    <label class="cm-form-label"><?= htmlspecialchars($label) ?></label>
    <?php endif; ?>
    <div class="cm-editor-wrapper" id="{$id}_wrapper">
        <div class="cm-editor-toolbar">
            <!-- Boutons de formatage : Gras, Italique, Souligné, Listes, Alignement -->
            <button type="button" data-cmd="bold"          title="Gras (Ctrl+B)"><i class="fas fa-bold"></i></button>
            <button type="button" data-cmd="italic"        title="Italique (Ctrl+I)"><i class="fas fa-italic"></i></button>
            <button type="button" data-cmd="underline"     title="Souligné (Ctrl+U)"><i class="fas fa-underline"></i></button>
            <span class="cm-editor-toolbar__sep"></span>
            <button type="button" data-cmd="insertUnorderedList" title="Liste à puces"><i class="fas fa-list-ul"></i></button>
            <button type="button" data-cmd="insertOrderedList"   title="Liste numérotée"><i class="fas fa-list-ol"></i></button>
            <span class="cm-editor-toolbar__sep"></span>
            <button type="button" data-cmd="justifyLeft"   title="Aligner à gauche"><i class="fas fa-align-left"></i></button>
            <button type="button" data-cmd="justifyCenter" title="Centrer"><i class="fas fa-align-center"></i></button>
            <button type="button" data-cmd="justifyRight"  title="Aligner à droite"><i class="fas fa-align-right"></i></button>
        </div>
        <div class="cm-rich-editor" id="{$id}"
             contenteditable="true"
             style="min-height: <?= htmlspecialchars($height) ?>"
             aria-label="<?= htmlspecialchars($label) ?>"
             aria-required="<?= $required ? 'true' : 'false' ?>"
             aria-multiline="true">
            <?= $value ?>
        </div>
    </div>
    <?php cm_component('editor/word-counter', ['editor_id' => $id, 'min' => $min_words, 'max' => $max_words]); ?>
    <input type="hidden" name="{$name}" id="{$id}_hidden" value="<?= htmlspecialchars(strip_tags($value)) ?>">
</div>
<script>
(function() {
    const editor = document.getElementById('<?= $id ?>');
    const hidden = document.getElementById('<?= $id ?>_hidden');
    if (!editor || !hidden) return;
    // Synchro hidden input sur chaque input
    editor.addEventListener('input', () => { hidden.value = editor.innerHTML; });
    // Boutons de toolbar
    document.querySelectorAll('[data-cmd]').forEach(btn => {
        btn.addEventListener('mousedown', (e) => {
            e.preventDefault();
            document.execCommand(btn.dataset.cmd, false, null);
            editor.focus();
        });
    });
})();
</script>
```

---

### 11.2 `word-counter.php`

**Rôle** : Compteur de mots/caractères en temps réel.

**Props** :
```php
$editor_id = $editor_id ?? '';    // ID de la zone éditable
$min       = $min       ?? 0;     // Minimum de mots (0 = pas de limite)
$max       = $max       ?? 0;     // Maximum de mots (0 = pas de limite)
```

**HTML produit** :
```html
<div class="cm-word-counter" id="{$editor_id}_counter">
    <span class="cm-word-counter__words">
        <span id="{$editor_id}_word_count">0</span> mots
    </span>
    <?php if ($min > 0): ?>
    <span class="cm-word-counter__min is-unmet" id="{$editor_id}_min_status">
        Minimum <?= $min ?> mots requis
    </span>
    <?php endif; ?>
    <?php if ($max > 0): ?>
    <span class="cm-word-counter__max" id="{$editor_id}_max_info">
        / <?= $max ?> maximum
    </span>
    <?php endif; ?>
</div>
<script>
(function() {
    const ed    = document.getElementById('<?= $editor_id ?>');
    const count = document.getElementById('<?= $editor_id ?>_word_count');
    if (!ed || !count) return;
    function update() {
        const words = (ed.innerText || '').trim().split(/\s+/).filter(w => w.length > 0).length;
        count.textContent = words;
        <?php if ($min > 0): ?>
        const minEl = document.getElementById('<?= $editor_id ?>_min_status');
        if (minEl) minEl.className = 'cm-word-counter__min ' + (words >= <?= $min ?> ? 'is-met' : 'is-unmet');
        <?php endif; ?>
        <?php if ($max > 0): ?>
        count.style.color = words > <?= $max ?> ? 'var(--cm-feedback-danger)' : '';
        <?php endif; ?>
    }
    ed.addEventListener('input', update);
    update();
})();
</script>
```

---

## 12. Composants Consultation (`ressources/components/consultation/`)

### 12.1 `detail-card.php`

**Rôle** : Affichage en lecture seule d'un enregistrement (carte de détail).

**Props** :
```php
$title  = $title  ?? '';    // Titre de la carte
$fields = $fields ?? [];    // [['label' => '...', 'value' => '...', 'type' => 'text|badge|date|html'], ...]
$icon   = $icon   ?? '';    // Icône de titre
$footer = $footer ?? '';    // HTML du pied de carte (boutons d'action)
```

**HTML produit** :
```html
<div class="cm-detail-card">
    <?php if ($title): ?>
    <div class="cm-detail-card__header">
        <?php if ($icon): ?><i class="fas <?= htmlspecialchars($icon) ?>"></i><?php endif; ?>
        <h3 class="cm-detail-card__title"><?= htmlspecialchars($title) ?></h3>
    </div>
    <?php endif; ?>
    <div class="cm-detail-card__body">
        <?php foreach ($fields as $field): ?>
        <div class="cm-detail-field">
            <span class="cm-detail-field__label"><?= htmlspecialchars($field['label']) ?></span>
            <span class="cm-detail-field__value">
                <?php
                switch ($field['type'] ?? 'text') {
                    case 'badge':
                        cm_component('ui/badge', is_array($field['value']) ? $field['value'] : ['text' => $field['value']]);
                        break;
                    case 'html':
                        echo $field['value'];
                        break;
                    case 'date':
                        echo $field['value'] ? date('d/m/Y', strtotime($field['value'])) : '—';
                        break;
                    default:
                        echo $field['value'] ? htmlspecialchars($field['value']) : '<em class="cm-detail-field__empty">—</em>';
                }
                ?>
            </span>
        </div>
        <?php endforeach; ?>
    </div>
    <?php if ($footer): ?>
    <div class="cm-detail-card__footer"><?= $footer ?></div>
    <?php endif; ?>
</div>
```

**CSS requis** :
```css
.cm-detail-card { background: var(--cm-content-bg); border: 1px solid var(--cm-border-color); border-radius: var(--cm-border-radius-lg); overflow: hidden; }
.cm-detail-card__header { background: var(--cm-primary); color: #fff; padding: var(--cm-spacing-md) var(--cm-spacing-lg); display: flex; align-items: center; gap: var(--cm-spacing-sm); }
.cm-detail-card__title { font-size: var(--cm-font-size-base); font-weight: 600; }
.cm-detail-card__body { padding: var(--cm-spacing-md) var(--cm-spacing-lg); }
.cm-detail-field { display: grid; grid-template-columns: 180px 1fr; gap: var(--cm-spacing-sm); padding: var(--cm-spacing-xs) 0; border-bottom: 1px solid var(--cm-border-color); align-items: start; }
.cm-detail-field:last-child { border-bottom: none; }
.cm-detail-field__label { font-weight: 600; font-size: var(--cm-font-size-sm); color: var(--cm-primary); }
.cm-detail-field__empty { color: var(--cm-text-muted, #7f8c8d); font-style: normal; }
.cm-detail-card__footer { padding: var(--cm-spacing-md) var(--cm-spacing-lg); border-top: 1px solid var(--cm-border-color); display: flex; gap: var(--cm-spacing-sm); }
```

---

### 12.2 `pdf-viewer.php`

**Rôle** : Visionneuse de PDF embarquée.

**Props** :
```php
$url      = $url      ?? '';           // URL du PDF
$title    = $title    ?? 'Document';
$height   = $height   ?? '600px';
$fallback = $fallback ?? '';           // URL de téléchargement si l'embed échoue
```

**HTML produit** : `.cm-pdf-viewer` wrapper + `<embed>` ou `<iframe>` + lien de téléchargement de secours.

---

## 13. Composant Timeline (`ressources/components/timeline/`)

### 13.1 `timeline.php`

**Rôle** : Affichage des étapes de progression (statut de soumission, workflow de validation).

**Props** :
```php
$steps   = $steps   ?? [];      // [['label', 'date', 'status', 'description'], ...]
$current = $current ?? 0;       // Index de l'étape courante (0-indexé)
$vertical = $vertical ?? true;  // Orientation : true=vertical, false=horizontal
```

**Statuts possibles** : `'done'` (complété), `'current'` (en cours), `'pending'` (à venir), `'error'` (rejeté)

**HTML produit** :
```html
<div class="cm-timeline <?= $vertical ? 'is-vertical' : 'is-horizontal' ?>">
    <?php foreach ($steps as $i => $step): ?>
    <div class="cm-timeline__step is-<?= htmlspecialchars($step['status']) ?>">
        <div class="cm-timeline__dot">
            <?php
            $icons = ['done' => 'fa-check', 'current' => 'fa-circle-dot', 'pending' => 'fa-circle', 'error' => 'fa-times'];
            echo '<i class="fas ' . ($icons[$step['status']] ?? 'fa-circle') . '"></i>';
            ?>
        </div>
        <div class="cm-timeline__content">
            <div class="cm-timeline__label"><?= htmlspecialchars($step['label']) ?></div>
            <?php if (!empty($step['date'])): ?>
            <div class="cm-timeline__date"><?= htmlspecialchars($step['date']) ?></div>
            <?php endif; ?>
            <?php if (!empty($step['description'])): ?>
            <div class="cm-timeline__desc"><?= htmlspecialchars($step['description']) ?></div>
            <?php endif; ?>
        </div>
        <?php if ($i < count($steps) - 1): ?>
        <div class="cm-timeline__connector"></div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
```

**CSS requis** :
```css
.cm-timeline.is-vertical { display: flex; flex-direction: column; gap: 0; }
.cm-timeline__step { display: flex; gap: var(--cm-spacing-md); position: relative; padding-bottom: var(--cm-spacing-md); }
.cm-timeline__dot { width: 32px; height: 32px; border-radius: 50%; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 14px; z-index: 1; }
.cm-timeline__step.is-done    .cm-timeline__dot { background: var(--cm-feedback-success); color: #fff; }
.cm-timeline__step.is-current .cm-timeline__dot { background: var(--cm-primary); color: #fff; box-shadow: 0 0 0 4px rgba(26,82,118,0.2); }
.cm-timeline__step.is-pending .cm-timeline__dot { background: var(--cm-border-color); color: #999; }
.cm-timeline__step.is-error   .cm-timeline__dot { background: var(--cm-feedback-danger); color: #fff; }
.cm-timeline__connector { position: absolute; left: 16px; top: 32px; bottom: 0; width: 2px; background: var(--cm-border-color); }
.cm-timeline__step.is-done ~ .cm-timeline__step .cm-timeline__connector,
.cm-timeline__step.is-done .cm-timeline__connector { background: var(--cm-feedback-success); }
.cm-timeline__label { font-weight: 600; font-size: var(--cm-font-size-sm); }
.cm-timeline__date  { font-size: var(--cm-font-size-xs); color: var(--cm-text-muted, #7f8c8d); }
.cm-timeline__desc  { font-size: var(--cm-font-size-xs); margin-top: 2px; }
```

---

## 14. Fichier de test (`ressources/views/v2/test_components.php`)

### 14.1 Objectif

Ce fichier est **la validation de l'implémentation**. Il rend chaque composant avec des données de test et permet de vérifier visuellement que tout fonctionne correctement avant de l'intégrer dans les vraies pages.

### 14.2 Données de test

```php
<?php
// test_components.php — Rendu de test de tous les composants CheckMaster
// Accès : directement via PHP ou via ?page=test_components (à désactiver en production)

require_once dirname(__DIR__, 3) . '/app/utils/ComponentHelper.php';
require_once dirname(__DIR__, 3) . '/app/utils/FormHelper.php';
require_once dirname(__DIR__, 3) . '/app/utils/PaginationHelper.php';
require_once dirname(__DIR__, 3) . '/app/utils/TableHelper.php';

session_start();
$_SESSION['csrf_token'] = 'test_token_' . bin2hex(random_bytes(8));

// Données de test communes
$test_user = ['nom' => 'KOUASSI', 'prenom' => 'Ama', 'role' => 'Scolarité'];
$test_annee = '2024-2025';
$test_pagination = cm_paginate(127, 10, 3);
$test_rows = [
    ['id' => 1, 'nom' => 'YAPI KOUAME', 'matricule' => 'ETU-2024-001', 'filiere' => 'Informatique', 'statut' => ['text' => 'Actif', 'type' => 'success']],
    ['id' => 2, 'nom' => 'BROU AFFOUE', 'matricule' => 'ETU-2024-002', 'filiere' => 'Mathématiques', 'statut' => ['text' => 'Suspendu', 'type' => 'warning']],
    ['id' => 3, 'nom' => 'KOFFI DIDIER', 'matricule' => 'ETU-2024-003', 'filiere' => 'Physique', 'statut' => ['text' => 'Inactif', 'type' => 'danger']],
];
$test_columns = [
    cm_column('matricule', 'Matricule', ['sortable' => true, 'width' => '140px']),
    cm_column('nom',       'Nom & Prénom', ['sortable' => true]),
    cm_column('filiere',   'Filière'),
    cm_column('statut',    'Statut', ['type' => 'badge', 'align' => 'center']),
    cm_action_column([
        ['label' => 'Modifier', 'icon' => 'fa-edit',  'action' => 'edit',   'class' => 'cm-btn-action is-edit',   'url' => '?action=edit&id={id}'],
        ['label' => 'Supprimer','icon' => 'fa-trash', 'action' => 'delete', 'class' => 'cm-btn-action is-delete', 'url' => '?action=delete&id={id}'],
    ]),
];
$test_steps = [
    ['label' => 'Soumission du sujet',     'date' => '10/01/2025', 'status' => 'done',    'description' => 'Sujet validé par le directeur'],
    ['label' => 'Validation jury',          'date' => '15/01/2025', 'status' => 'done',    'description' => 'Jury constitué'],
    ['label' => 'Soutenance planifiée',     'date' => '20/02/2025', 'status' => 'current', 'description' => 'Salle A204 — 09h00'],
    ['label' => 'Résultats publiés',        'date' => '',           'status' => 'pending', 'description' => ''],
];
$test_tabs = [
    ['key' => 'infos',    'label' => 'Informations', 'icon' => 'fa-user',          'count' => null],
    ['key' => 'docs',     'label' => 'Documents',    'icon' => 'fa-file',          'count' => 3],
    ['key' => 'historique','label' => 'Historique',  'icon' => 'fa-history',       'count' => null],
];
$test_stats = [
    ['value' => '1 248', 'label' => 'Étudiants inscrits', 'icon' => 'fa-user-graduate', 'color' => 'primary'],
    ['value' => '87%',   'label' => 'Taux de réussite',   'icon' => 'fa-chart-line',    'color' => 'success', 'trend' => ['value' => '+3%', 'direction' => 'up', 'label' => 'vs 2023']],
    ['value' => '34',    'label' => 'Soutenances prévues','icon' => 'fa-calendar-check','color' => 'info'],
    ['value' => '12',    'label' => 'Dossiers en attente','icon' => 'fa-clock',         'color' => 'warning'],
];
$test_hub_tiles = [
    ['title' => 'Gestion des Étudiants',  'description' => 'Inscription, modification, suppression', 'icon' => 'fa-users',          'url' => '#', 'color' => 'primary', 'count' => 1248],
    ['title' => 'Gestion des Diplômes',   'description' => 'Délivrance et archivage',                'icon' => 'fa-graduation-cap', 'url' => '#', 'color' => 'success'],
    ['title' => 'Relevés de Notes',       'description' => 'Consultation et export',                 'icon' => 'fa-file-alt',       'url' => '#', 'color' => 'info'],
    ['title' => 'Module désactivé',       'description' => 'Accès restreint',                        'icon' => 'fa-lock',           'url' => '#', 'color' => 'warning', 'disabled' => true],
];
```

### 14.3 Structure du rendu de test

Le fichier de test HTML doit contenir un layout simplifié (sans sidebar complète) pour ne pas dépendre de données de session réelles. Il utilise les CSS via lien direct.

**Sections testées dans l'ordre** :

1. **Layout** — `page-header` avec breadcrumbs
2. **UI — Badges** — tous les types (success, warning, danger, info, light, default)
3. **UI — Alert Boxes** — tous les types avec et sans titre
4. **UI — Toast** — rendu statique du conteneur + démo via `cmToast()` au click
5. **UI — Empty State** — standalone et dans un tableau
6. **Dashboard — Stat Widgets** — grille 4 colonnes avec toutes les variantes
7. **Dashboard — Chart Container** — bar chart + line chart avec données de test
8. **Hub — Hub Grid** — grille 2 colonnes avec tuiles test
9. **CRUD — Toolbar** — avec toutes les options activées
10. **CRUD — Data Table** — avec données de test + pagination en dessous
11. **CRUD — Form Pole** — avec champs texte, date, select
12. **CRUD — Form Actions** — mode create + mode edit
13. **Form — Tous les inputs** — grille de tous les champs formulaire
14. **Tabs — Tab Nav + Tab Panels** — 3 onglets avec contenu
15. **Timeline** — vertical avec 4 étapes (done/done/current/pending)
16. **Consultation — Detail Card** — avec 5 champs dont un badge et un champ HTML
17. **Activity List** — 5 activités de test
18. **Alert List** — 3 alertes de test

---

## 15. Règles d'implémentation critique

### 15.1 Sécurité

- Tout output de données utilisateur : TOUJOURS `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')`
- Exception : colonnes `type='html'` dans `detail-card` et `alert-box` (HTML contrôlé côté serveur)
- CSRF token sur TOUS les formulaires via `cm_component('form/csrf-token')`
- Jamais d'`eval()`, jamais de `innerHTML` avec données non maîtrisées

### 15.2 Performance

- Pas de requête SQL dans les composants
- Les composants reçoivent leurs données entièrement préparées
- Le JS est minimal et inline dans le composant — pas de dépendances JS externes ajoutées
- Utiliser `cm_asset()` pour les liens CSS/JS (cache-busting automatique)

### 15.3 Accessibilité

- Tous les éléments interactifs ont `aria-label` ou `title`
- Les tableaux ont `role="grid"`, les headers `scope="col"`
- Les onglets respectent le pattern WAI-ARIA (role="tablist", role="tab", role="tabpanel")
- Les toasts utilisent `role="alert"` + `aria-live="polite"`
- Les images et icônes décoratives ont `aria-hidden="true"`

### 15.4 Responsive

Chaque composant est responsive par défaut :
- `data-table` → horizontal scroll sur mobile via `.cm-table-wrapper`
- `toolbar` → `flex-wrap: wrap` → les boutons passent à la ligne
- `hub-grid` → 1 colonne sous 768px
- `stat-widget` → adaptatif (voir PRD 1 breakpoints)
- `sidebar` → masquée sur mobile (toggle hamburger)

---

## 16. Checklist de validation de PRD 2

Avant de marquer PRD 2 comme implémenté, vérifier :

- [ ] `ComponentHelper.php` créé et fonctionnel dans `app/utils/`
- [ ] `FormHelper.php` créé et fonctionnel dans `app/utils/`
- [ ] `PaginationHelper.php` créé et fonctionnel dans `app/utils/`
- [ ] `TableHelper.php` créé et fonctionnel dans `app/utils/`
- [ ] Dossiers créés : `ressources/components/layout|ui|crud|form|dashboard|hub|tabs|editor|consultation|timeline/`
- [ ] Dossier créé : `ressources/views/v2/`
- [ ] Tous les composants listés en section 2 existent
- [ ] Chaque composant a des valeurs par défaut pour toutes ses props optionnelles
- [ ] `test_components.php` créé et rendu sans erreur PHP
- [ ] Chaque composant visible et correct dans la page de test
- [ ] Aucun appel SQL dans aucun composant
- [ ] CSRF token présent dans tous les formulaires
- [ ] `htmlspecialchars()` présent sur tous les outputs dynamiques
- [ ] Aucune classe Tailwind dans les composants (uniquement `.cm-*`)
- [ ] Aucune variable `:root` locale dans les composants (tout vient de `checkmaster-theme.css`)

---

## 17. Dépendances et pré-requis

### 17.1 PRD 1 doit être implémenté avant PRD 2

PRD 2 dépend de :
- `public/assets/css/checkmaster-theme.css` → variables CSS (existant + nouvelles de PRD 1)
- `public/assets/css/components.css` → classes de base (existant + extensions de PRD 1)
- `public/assets/css/utilities.css` → classes utilitaires (à créer dans PRD 1)
- `public/assets/css/responsive.css` → breakpoints (existant)

### 17.2 CDN requis dans `app-shell.php`

```html
<!-- Font Awesome 6.4.0 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Chart.js (uniquement si page dashboard) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

### 17.3 Structure de session attendue

```php
$_SESSION['toast']             = ['type' => 'success|error|warning|info', 'message' => '...'];
$_SESSION['csrf_token']        = 'hex_string';
$_SESSION['old_input']         = ['field_name' => 'old_value', ...];
$_SESSION['validation_errors'] = ['field_name' => 'error_message', ...];
```

---

*PRD 2 — Version 1.0 — Référence pour l'implémentation de la bibliothèque de composants PHP*

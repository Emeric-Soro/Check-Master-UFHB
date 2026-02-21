# GUIDE DES COMPOSANTS - CheckMaster UFRMI

Guide complet d'utilisation de la bibliothèque de composants réutilisables.  
Dernière mise à jour : 2026-02-13.

---

## Table des matières

1. [Architecture générale](#1-architecture-générale)
2. [Comment ça marche](#2-comment-ça-marche)
3. [Palette de couleurs](#3-palette-de-couleurs)
4. [CSS : fichiers et classes](#4-css--fichiers-et-classes)
5. [JavaScript : fichiers et comportements](#5-javascript--fichiers-et-comportements)
6. [PHP Helpers : ComponentHelper, FormHelper, TableHelper, NavigationHelper](#6-php-helpers)
7. [Composants Layout](#7-composants-layout)
8. [Composants Formulaire](#8-composants-formulaire)
9. [Composants Tableau](#9-composants-tableau)
10. [Composants Panneau latéral](#10-composants-panneau-latéral)
11. [Composants Navigation](#11-composants-navigation)
12. [Composants Widget](#12-composants-widget)
13. [Composants Feedback](#13-composants-feedback)
14. [Composants Spéciaux](#14-composants-spéciaux)
15. [Layouts](#15-layouts)
16. [Créer une nouvelle page](#16-créer-une-nouvelle-page)
17. [Showcase (prévisualisation)](#17-showcase)

---

## 1. Architecture générale

```
checkmaster.ufrmi/
├── public/
│   └── assets/
│       ├── bulma/css/bulma.min.css     # Bulma CSS (framework)
│       ├── css/
│       │   ├── checkmaster-theme.css   # Variables CSS, palette, layouts
│       │   ├── components.css          # Styles spécifiques aux composants
│       │   └── responsive.css          # Adaptations mobile/tablette
│       └── js/
│           ├── app.js                  # Point d'entrée JS global
│           └── components/
│               ├── sidebar.js          # Toggle sidebar mobile
│               ├── data-table.js       # Tri, recherche, export CSV
│               ├── select-search.js    # Autocomplete + auto-remplissage
│               ├── side-panel.js       # Panneau latéral AJAX
│               ├── confirm-modal.js    # Modales de confirmation
│               ├── tabs.js            # Onglets + accordéon mobile
│               ├── steps.js           # Navigation multi-étapes
│               ├── auto-save.js       # Sauvegarde automatique
│               ├── toast.js           # Notifications temporaires
│               └── form-validation.js # Validation côté client
├── views/
│   ├── layouts/
│   │   ├── app.php                    # Layout UNIQUE (sidebar + header + contenu)
│   │   └── auth.php                   # Layout authentification (sans sidebar)
│   └── components/
│       ├── layout/                    # Composants de structure de page
│       │   ├── base.php               # Enveloppe HTML complète
│       │   ├── sidebar.php            # Menu latéral de navigation
│       │   ├── header.php             # Barre supérieure
│       │   ├── crud-page.php          # Template CRUD (pôle sup + barre + pôle inf)
│       │   ├── dashboard-page.php     # Template Dashboard (grille widgets)
│       │   ├── editor-page.php        # Template Éditeur (pleine page)
│       │   └── consultation-page.php  # Template Consultation (lecture seule)
│       ├── form/                      # 12 composants de formulaire
│       ├── table/                     # 6 composants de tableau
│       ├── panel/                     # 3 composants panneau/modale
│       ├── nav/                       # 4 composants de navigation
│       ├── widget/                    # 5 composants de widgets
│       ├── feedback/                  # 3 composants de feedback
│       └── special/                   # 3 composants spéciaux
└── src/Support/
    ├── ComponentHelper.php            # Fonction component() pour les vues
    ├── FormHelper.php                 # Registre de champs par entité
    ├── TableHelper.php                # Registre de colonnes par entité
    └── NavigationHelper.php           # Menu dynamique RBAC, breadcrumb, tabs
```

**Total : ~60 fichiers** (35 composants PHP, 3 CSS, 11 JS, 4 Helpers PHP, 2 Layouts, 1 Showcase, 1 Doc)

---

## 2. Comment ça marche

### Principe fondamental

Les composants sont des **partials PHP** (fichiers `.php` dans `views/components/`). Ils reçoivent des paramètres via la fonction `extract()` du `ViewRenderer` et produisent du HTML avec les classes Bulma + classes custom `cm-*`.

### Flux de rendu

```
1. Routeur → Controller::action(Request)
2. Controller prépare les données
3. Controller appelle $this->renderHtml('scolarite/etudiants/index', [...])
4. ViewRenderer::render() charge le fichier PHP de la page
5. La page inclut le layout (app.php) qui compose sidebar + header + contenu
6. Le contenu utilise les composants (crud-page, field, data-table, etc.)
7. Chaque composant reçoit ses paramètres et produit du HTML
8. Le HTML complet est renvoyé au navigateur
```

### Inclusion de composants dans une vue

**Méthode 1 : Via ComponentHelper (`$c`)**
```php
<?= $c->form('field', ['name' => 'email', 'label' => 'Email', 'type' => 'email']) ?>
<?= $c->table('data-table', ['columns' => $columns, 'rows' => $rows]) ?>
<?= $c->feedback('badge', ['label' => 'Actif', 'type' => 'success']) ?>
```

**Méthode 2 : Via include direct**
```php
<?php
$name = 'email'; $label = 'Email'; $type = 'email'; $value = '';
include __DIR__ . '/../components/form/field.php';
?>
```

**Méthode 3 : Via la fonction closure dans le layout**
```php
<?= $component('form/field', ['name' => 'email', 'label' => 'Email', 'type' => 'email']) ?>
```

---

## 3. Palette de couleurs

| Variable CSS | Valeur | Usage |
|-------------|--------|-------|
| `--cm-primary` | `#1a5276` | Sidebar, éléments principaux |
| `--cm-primary-dark` | `#154360` | Hover sidebar |
| `--cm-primary-light` | `#3498db` | Fond app, liens, éléments actifs |
| `--cm-primary-lighter` | `#5dade2` | Bordures actives |
| `--cm-primary-lightest` | `#aed6f1` | Fonds légers, badges année |
| `--cm-btn-primary` | `#2980b9` | Bouton primaire (bleu) |
| `--cm-btn-success` | `#27ae60` | Bouton succès (vert) |
| `--cm-feedback-warning` | `#f39c12` | ⚠️ Notifications/badges UNIQUEMENT |
| `--cm-feedback-danger` | `#e74c3c` | ⚠️ Notifications/badges UNIQUEMENT |

**Règle absolue** : Les boutons d'action n'utilisent que le bleu (`is-primary`, `is-info`) ou le vert (`is-success`). L'orange et le rouge sont **réservés** aux badges de statut, alertes et notifications.

---

## 4. CSS : fichiers et classes

### checkmaster-theme.css
Variables CSS globales, surcharges Bulma, styles de layout (sidebar, header, segmentation polarisée, panneau latéral, modale, boutons, badges, tableaux, toolbar, formulaires, onglets, widgets, timeline, steps, breadcrumb, toast, éditeur, PDF viewer).

### components.css
Styles granulaires des composants individuels : champs de formulaire, select-search, file-upload, checkbox, radio group, date-picker, pagination, column-header, rich-editor, chart-container, formulaire validation, skeleton loading, styles d'impression.

### responsive.css
Adaptations responsive :
- **Tablette** (769-1024px) : sidebar réduite, toolbar wrappée, table compacte
- **Mobile** (< 768px) : sidebar en tiroir, burger visible, formulaires stackés, timeline verticale, tabs en accordéon, panneau plein écran
- **Petit mobile** (< 480px) : stat-cards verticales
- **Desktop large** (> 1400px) : contenu centré, grille 4 colonnes

### Préfixe CSS : `cm-`

Toutes les classes custom utilisent le préfixe `cm-` :
- Layout : `cm-app-wrapper`, `cm-sidebar`, `cm-header`, `cm-main`, `cm-content`
- CRUD : `cm-crud-wrapper`, `cm-pole-superieur`, `cm-barre-intermediaire`, `cm-pole-inferieur`
- Panel : `cm-side-panel`, `cm-modal`, `cm-side-panel-overlay`, `cm-modal-overlay`
- Formulaire : `cm-field`, `cm-field-compresse`, `cm-field-standard`, `cm-field-etendue`
- Tableau : `cm-table-wrapper`, `cm-toolbar`, `cm-row-actions`, `cm-empty-state`
- Feedback : `cm-badge`, `cm-toast`, `cm-alert`
- Widget : `cm-stat-card`, `cm-timeline`, `cm-chart-container`

---

## 5. JavaScript : fichiers et comportements

### Namespace global : `window.CM`

Tous les scripts utilisent le namespace global `CM` :
```javascript
CM.utils.debounce(fn, delay)     // Anti-rebond
CM.utils.escapeHtml(str)         // Échappement HTML
CM.utils.formatNumber(n)         // Formatage numérique
CM.utils.fetchJson(url, options) // Fetch avec CSRF auto
CM.events.on(name, fn)           // Bus d'événements
CM.events.emit(name, data)       // Émission d'événement
CM.toast.show(message, type, duration)  // Toast notification
```

### Fichiers JS

| Fichier | Auto-init | Déclencheur |
|---------|-----------|-------------|
| `app.js` | ✓ | DOMContentLoaded |
| `sidebar.js` | ✓ | `.cm-burger` click |
| `data-table.js` | ✓ | `.cm-toolbar-search` input, `th.cm-sortable` click |
| `select-search.js` | ✓ | `.cm-select-search` input |
| `side-panel.js` | ✓ | `[data-action="open-panel"]` click |
| `confirm-modal.js` | ✓ | `[data-action="confirm"]` click |
| `tabs.js` | ✓ | `.cm-tabs-container .tabs a` click |
| `steps.js` | ✓ | `.cm-step[data-step-url]` click |
| `auto-save.js` | ✓ | `[data-autosave]` forms, debounced |
| `toast.js` | ✓ | API `CM.toast.show()` |
| `form-validation.js` | ✓ | `[data-validate]` forms, submit/blur |

### CSRF automatique

Le token CSRF est lu depuis `<meta name="csrf-token">` et injecté automatiquement dans les requêtes `fetch`.

---

## 6. PHP Helpers

### ComponentHelper (`$c` dans les vues)

```php
$c->render('form/field', ['name' => 'email', ...])  // Composant générique
$c->form('field', [...])     // Raccourci formulaire
$c->table('data-table', [...])  // Raccourci tableau
$c->panel('side-panel', [...])  // Raccourci panneau
$c->nav('tabs', [...])       // Raccourci navigation
$c->widget('stat-card', [...])  // Raccourci widget
$c->feedback('alert', [...])   // Raccourci feedback
$c->special('rich-editor', [...])  // Raccourci spécial
```

### FormHelper

Registre centralisé des champs par entité. Évite de redéfinir les attributs de chaque champ dans chaque page.

```php
// Obtenir la config d'un champ
$config = $formHelper->fieldConfig('etudiant', 'email_etudiant');
// → ['type' => 'email', 'label' => 'Email', 'size' => 'etendue', 'required' => true, ...]

// Construire tous les champs avec valeurs et erreurs pré-remplies
$fields = $formHelper->buildFormFields('etudiant', $values, $errors);
```

**Entités supportées** : `etudiant`, `inscription`, `enseignant`, `utilisateur`, `versement`, `candidature`, `annee_academique`

### TableHelper

Registre centralisé des colonnes par entité.

```php
$columns = $tableHelper->columnsConfig('etudiant');
$pagination = $tableHelper->paginationData($total, $page, $perPage);
$actions = $tableHelper->actionsConfig('etudiant', '/scolarite/etudiants');
```

### NavigationHelper

Menu dynamique filtré par RBAC.

```php
$menus = $navigationHelper->buildMenu($user);        // Menu filtré par permissions
$breadcrumb = $navigationHelper->buildBreadcrumb($currentPath, $menus);
$tabs = $navigationHelper->buildTabs('parametres_generaux', 'commission');
```

---

## 7. Composants Layout

### base.php
Enveloppe HTML5 complète. Charge Bulma + CSS custom + JS.

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `$title` | string | 'CheckMaster' | Titre de la page |
| `$content` | string | '' | Contenu HTML du body |
| `$extra_css` | array | [] | CSS additionnels |
| `$extra_js` | array | [] | Scripts additionnels |

### sidebar.php
Menu latéral filtré par RBAC.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$menus` | array | Arborescence des menus `[{label, icon, url, children, is_active}]` |
| `$current_path` | string | URL courante pour le marquage actif |

### header.php
Barre supérieure.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$page_title` | string | Titre de la page |
| `$annee_academique` | array | `{libelle, est_active}` |
| `$user` | array | `{nom, prenom, login, groupe}` |
| `$notifications_count` | int | Nombre de notifications |

### crud-page.php ⭐ (composant le plus critique)
Template CRUD avec segmentation polarisée. Utilisé par ~70% des écrans.

| Paramètre | Type | Description |
|-----------|------|-------------|
| `$form_content` | string | HTML du formulaire (pôle supérieur) |
| `$toolbar_config` | array | Config de la barre intermédiaire |
| `$table_content` | string | HTML du tableau (pôle inférieur) |
| `$panel_content` | string | HTML du panneau latéral (optionnel) |
| `$form_title` | string | Titre du formulaire |
| `$form_mode` | string | 'creation' ou 'edition' |

### dashboard-page.php, editor-page.php, consultation-page.php
Templates pour dashboards, éditeurs et pages en lecture seule.

---

## 8. Composants Formulaire

### Tailles (densité sémantique)

| Taille | Classe CSS | Usage |
|--------|-----------|-------|
| `compresse` | `cm-field-compresse` (max 120px) | Codes courts : matricule, genre |
| `standard` | `cm-field-standard` (max 280px) | Champs moyens : nom, date |
| `etendue` | `cm-field-etendue` (max 480px) | Champs longs : email, adresse |

### field.php
```php
<?= $c->form('field', [
    'name'        => 'email_etudiant',
    'label'       => 'Email',
    'type'        => 'email',
    'value'       => $etudiant['email'] ?? '',
    'required'    => true,
    'size'        => 'etendue',
    'placeholder' => 'etudiant@ufrmi.ci',
    'error'       => $errors['email_etudiant'] ?? '',
    'help'        => 'Adresse email professionnelle',
    'attrs'       => ['data-rules' => 'required|email']
]) ?>
```

### select-search.php (auto-remplissage)
```php
<?= $c->form('select-search', [
    'name'             => 'id_etudiant',
    'label'            => 'Étudiant',
    'api_url'          => '/api/etudiants/search',
    'autofill_targets' => ['nom' => '#field-nom', 'prenom' => '#field-prenom'],
    'min_chars'        => 2,
    'required'         => true,
]) ?>
```

### form-buttons.php
```php
<?= $c->form('form-buttons', [
    'mode'         => 'creation',  // ou 'edition'
    'show_new'     => true,
    'show_cancel'  => false,
]) ?>
```

### Autres composants form
`textarea.php`, `select.php`, `date-picker.php`, `file-upload.php`, `checkbox.php`, `radio-group.php`, `hidden.php`, `csrf-token.php`, `form-group.php`

---

## 9. Composants Tableau

### data-table.php
```php
<?= $c->table('data-table', [
    'columns' => [
        ['key' => 'matricule', 'label' => 'Matricule', 'sortable' => true],
        ['key' => 'nom',       'label' => 'Nom',       'sortable' => true],
        ['key' => 'statut',    'label' => 'Statut',    'renderer' => 'badge', 'badge_entity' => 'utilisateur'],
        ['key' => 'actions',   'label' => 'Actions',   'renderer' => 'actions'],
    ],
    'rows'           => $etudiants,
    'actions'        => [
        ['type' => 'edit',   'url' => '/scolarite/etudiants/{id}/modifier'],
        ['type' => 'detail', 'url' => '/scolarite/etudiants/{id}'],
        ['type' => 'delete', 'url' => '/scolarite/etudiants/{id}', 'confirm_message' => 'Supprimer ?'],
    ],
    'sort_column'    => $sort ?? 'matricule',
    'sort_direction' => $dir ?? 'asc',
    'empty_message'  => 'Aucun étudiant trouvé',
]) ?>
```

### toolbar.php
```php
<?= $c->table('toolbar', [
    'total_results'      => $total,
    'search_value'       => $search ?? '',
    'search_placeholder' => 'Rechercher par nom, matricule...',
    'show_export'        => true,
    'show_import'        => true,
    'export_url'         => '/scolarite/etudiants/export',
    'filters'            => [
        ['name' => 'statut', 'type' => 'select', 'options' => [...], 'selected' => $filter_statut],
    ],
]) ?>
```

---

## 10. Composants Panneau latéral

### side-panel.php
Panneau latéral droit (« Perspective Profonde »). S'ouvre via JS avec animation slide-in.

### confirm-modal.php
Modale de confirmation pour actions destructrices. Seule modale autorisée par la « Philosophie Zéro Rupture ».

### detail-card.php
Carte de détail au format dl/dt/dd pour le panneau latéral.

---

## 11. Composants Navigation

### tabs.php
Onglets horizontaux sur desktop, accordéon sur mobile. Utilisé pour les écrans de paramétrage (10+ onglets).

### breadcrumb.php
Fil d'Ariane : `Admin > Gestion Scolarité > Mise à jour Étudiant`.

### steps.php
Navigation multi-étapes pour le module LOT : `Info Stage → Rédaction → Soumission`.

---

## 12. Composants Widget

### stat-card.php
Carte statistique pour les dashboards : valeur + label + icône.

### progress-bar.php
Barre de progression Bulma avec label.

### timeline.php + timeline-step.php
Timeline interactive pour le suivi de dossier étudiant : `Dossier → Scolarité → Commission → Soutenance`.

### chart-container.php
Conteneur pour graphiques (prévu pour Chart.js).

---

## 13. Composants Feedback

### alert.php
Message de succès/erreur/warning/info (notification Bulma).

### toast.php
Notification temporaire en haut à droite (piloté par JS `CM.toast.show()`).

### badge.php
Badge de statut coloré. S'intègre avec `ViewHelper::getStatusLabel()` et `ViewHelper::getStatusBadgeClass()`.

---

## 14. Composants Spéciaux

### rich-editor.php
Éditeur WYSIWYG pour la rédaction du rapport (LOT) et du Compte Rendu commission.

### pdf-viewer.php
Aperçu PDF inline via iframe.

### academic-year.php
Affichage de l'année académique active dans le header.

---

## 15. Layouts

### app.php (Layout unique)
Le **seul** layout pour **tous** les utilisateurs. La distinction se fait par le filtrage RBAC des menus.

Compose : `base.php` (HTML) + `sidebar.php` + `header.php` + contenu.

### auth.php
Layout minimal pour les pages d'authentification (login, reset password, 2FA). Sans sidebar ni header.

---

## 16. Créer une nouvelle page

### Étape 1 : Créer le fichier de vue

```php
<!-- views/scolarite/etudiants/index.php -->
<?php
// Données disponibles via extract() : $etudiants, $pagination, $errors, $edit_data, $csrf_token, ...
// Helpers disponibles : $c (ComponentHelper), $h (ViewHelper)

// Construire le formulaire (pôle supérieur)
ob_start();
?>
<form method="POST" action="/scolarite/etudiants" data-validate>
    <?= $c->form('csrf-token', ['token' => $csrf_token]) ?>
    <div class="cm-form-group">
        <div class="columns">
            <div class="column is-3">
                <?= $c->form('field', ['name' => 'matricule', 'label' => 'Matricule', 'size' => 'compresse', 'required' => true]) ?>
            </div>
            <div class="column is-4">
                <?= $c->form('field', ['name' => 'nom', 'label' => 'Nom', 'required' => true]) ?>
            </div>
            <div class="column is-4">
                <?= $c->form('field', ['name' => 'prenom', 'label' => 'Prénom', 'required' => true]) ?>
            </div>
        </div>
    </div>
    <?= $c->form('form-buttons', ['mode' => $edit_data ? 'edition' : 'creation']) ?>
</form>
<?php $form_html = ob_get_clean(); ?>

<?php
// Construire le tableau (pôle inférieur)
ob_start();
echo $c->table('data-table', [
    'columns' => $tableHelper->columnsConfig('etudiant'),
    'rows'    => $etudiants,
    'actions' => $tableHelper->actionsConfig('etudiant', '/scolarite/etudiants'),
]);
$table_html = ob_get_clean();
?>

<?= $c->layout('crud-page', [
    'form_content'   => $form_html,
    'table_content'  => $table_html,
    'form_title'     => $edit_data ? 'Modifier Étudiant' : 'Nouvel Étudiant',
    'form_mode'      => $edit_data ? 'edition' : 'creation',
    'toolbar_config' => [
        'total_results'      => count($etudiants),
        'search_placeholder' => 'Rechercher par nom, matricule...',
        'show_export'        => true,
    ],
]) ?>
```

### Étape 2 : Appeler depuis le contrôleur

```php
return $this->renderHtml('scolarite/etudiants/index', [
    'etudiants'  => $rows,
    'pagination' => $pagination,
    'errors'     => $errors,
    'edit_data'  => $editData,
    'csrf_token' => $this->csrf->generate('etudiant'),
    'page_title' => 'Mise à jour Étudiant',
    'layout'     => 'app',
]);
```

### Étape 3 : Déclarer la route

```php
// config/routes.php
$routes->add('GET', '/scolarite/etudiants', [EtudiantController::class, 'index'], ['auth', 'permission']);
$routes->add('POST', '/scolarite/etudiants', [EtudiantController::class, 'store'], ['auth', 'permission']);
```

---

## 17. Showcase

Le fichier **`public/showcase.html`** permet de prévisualiser visuellement **tous les composants** de la bibliothèque.

### Accéder au showcase

```
http://localhost/checkmaster.ufrmi/public/showcase.html
```

Le showcase contient :
- La palette de couleurs complète
- La typographie
- Tous les boutons (tailles, variantes)
- La sidebar avec menus dépliables
- Le header avec notifications
- Une page CRUD complète (formulaire + toolbar + tableau)
- Le dashboard avec stat-cards et graphiques
- L'éditeur riche
- Tous les composants de formulaire (champs, selects, date picker, file upload, checkbox, radio)
- Le data-table avec tri et badges
- La toolbar avec filtres
- La pagination
- Le panneau latéral avec detail-card
- La modale de confirmation
- Les onglets
- Le fil d'Ariane
- Les étapes multi-étapes
- Les stat-cards
- Les barres de progression
- La timeline interactive
- Les alertes (succès, erreur, warning, info)
- Les toasts
- Les badges de statut
- L'éditeur riche
- Le PDF viewer
- L'année académique

---

## Résumé des fichiers créés

| Catégorie | Fichiers | Total |
|-----------|----------|-------|
| CSS | checkmaster-theme.css, components.css, responsive.css | 3 |
| JS | app.js + 10 composants | 11 |
| PHP Helpers | ComponentHelper, FormHelper, TableHelper, NavigationHelper | 4 |
| Layout components | base, sidebar, header, crud-page, dashboard-page, editor-page, consultation-page | 7 |
| Layouts | app.php, auth.php | 2 |
| Form components | field, textarea, select, select-search, date-picker, file-upload, checkbox, radio-group, hidden, csrf-token, form-buttons, form-group | 12 |
| Table components | data-table, toolbar, row-actions, empty-state, pagination, column-header | 6 |
| Panel components | side-panel, detail-card, confirm-modal | 3 |
| Nav components | tabs, breadcrumb, steps, mobile-drawer | 4 |
| Widget components | stat-card, progress-bar, timeline, timeline-step, chart-container | 5 |
| Feedback components | alert, toast, badge | 3 |
| Special components | rich-editor, pdf-viewer, academic-year | 3 |
| Showcase | showcase.html | 1 |
| Documentation | GUIDE_COMPOSANTS.md | 1 |
| **Total** | | **~65 fichiers** |

**Gain attendu** : Réduction de ~70-80% de la duplication de code. Chaque nouvel écran ne définit que ses données spécifiques (champs, colonnes, actions) et compose les composants existants.

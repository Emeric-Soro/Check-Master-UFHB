# CheckMaster Premium Design System

Guide de migration et documentation des composants UI inspirés de Shadcn/UI.

## 📁 Structure des Fichiers

```
ressources/
└── views/
    └── components/
        ├── index.php          # Charge tous les composants
        ├── ui/                 # Composants d'interface
        │   ├── button.php
        │   ├── badge.php
        │   ├── card.php
        │   ├── stats-card.php
        │   ├── data-table.php
        │   ├── pagination.php
        │   ├── alert.php
        │   ├── modal.php
        │   ├── tabs.php
        │   ├── dropdown.php
        │   ├── progress.php
        │   ├── avatar.php
        │   ├── timeline.php
        │   ├── skeleton.php
        │   ├── tooltip.php
        │   └── separator.php
        ├── form/               # Composants de formulaire
        │   └── input.php
        └── layout/             # Composants de structure
            ├── sidebar.php
            ├── navbar.php
            └── breadcrumb.php

public/
├── css/
│   └── premium.css    # Styles CSS centralisés (Design Tokens)
└── js/
    └── premium.js     # JavaScript centralisé (CM namespace)
```

## 🚀 Démarrage Rapide

### 1. Inclure les composants

```php
<?php
// Dans votre layout ou au début de votre vue
require_once __DIR__ . '/../ressources/views/components/index.php';
```

### 2. Inclure les assets

```html
<!-- CSS Premium -->
<link rel="stylesheet" href="css/premium.css">

<!-- JS Premium (à la fin du body) -->
<script src="js/premium.js"></script>
```

## 📦 Guide des Composants

### Boutons

```php
// Bouton simple
echo renderButton('Enregistrer', 'primary');

// Bouton avec permission (sécurité centralisée)
echo renderButton('Supprimer', 'danger', canDelete());

// Bouton avec icône
echo renderButton('Ajouter', 'primary', true, '', '', 'fa-plus');

// Bouton lien
echo renderButtonLink('Voir', '?page=details&id=1', 'outline');
```

### Badges (Statuts)

```php
// Badge avec mapping automatique
echo renderBadge('valide');      // Vert: "Validé"
echo renderBadge('en_cours');    // Orange: "En cours"
echo renderBadge('rejete');      // Rouge: "Rejeté"

// Badge avec label personnalisé
echo renderBadge('custom', 'Mon Label');
```

### Stats Cards (KPI)

```php
// Carte statistique simple
echo renderStatsCard('Total Étudiants', 1234, 'users', 'primary');

// Avec tendance
echo renderStatsCard('Rapports', 89, 'file-alt', 'success', [
    'value' => '+12%',
    'direction' => 'up'
]);

// Grille de stats
echo renderStatsGrid([
    ['label' => 'Étudiants', 'value' => 1234, 'icon' => 'users', 'type' => 'primary'],
    ['label' => 'Validés', 'value' => 89, 'icon' => 'check', 'type' => 'success'],
    // ...
]);
```

### Data Table

```php
$data = [
    ['id' => 1, 'nom' => 'Dupont', 'statut' => 'valide', '_render' => ['statut' => renderBadge('valide')]],
    ['id' => 2, 'nom' => 'Martin', 'statut' => 'en_cours', '_render' => ['statut' => renderBadge('en_cours')]],
];

echo renderDataTable(
    ['nom' => 'Nom', 'statut' => 'Statut'],    // Headers
    $data,                                       // Données
    [
        'actions' => ['view', 'edit', 'delete'], // Actions CRUD
        'searchable' => true,                    // Barre de recherche
        'permissions' => [                       // Droits
            'edit' => canEdit(),
            'delete' => canDelete()
        ]
    ]
);
```

### Pagination

```php
echo renderPagination(
    $currentPage,       // Page actuelle
    $totalPages,        // Total des pages
    '?page=liste&p={page}'  // URL template
);

// Avec informations
echo renderPaginationInfo($currentPage, $totalPages, $totalItems, $perPage);
```

### Alertes / Toasts

```php
// Alertes statiques
echo renderAlert('Opération réussie !', 'success', 'Succès');
echo renderError('Une erreur est survenue');
echo renderWarning('Attention, action irréversible');

// Messages flash (depuis la session)
echo renderFlashMessages();  // Affiche et supprime $_SESSION['success/error/warning']
```

### Modales

```php
// Modale simple
echo renderModal(
    'ma-modale',              // ID
    'Titre de la modale',     // Titre
    '<p>Contenu HTML</p>',    // Contenu
    renderButton('Fermer', 'secondary', true, "onclick=\"CM.Modal.hide('ma-modale')\"")
);

// Ouvrir via JS
echo renderButton('Ouvrir', 'primary', true, "onclick=\"CM.Modal.show('ma-modale')\"");

// Confirmation dynamique
echo renderButton('Supprimer', 'danger', true, 
    "onclick=\"CM.Modal.confirm({title:'Confirmer', message:'Êtes-vous sûr?', type:'danger'}).then(ok => { if(ok) location.href='?delete=1'; })\""
);
```

### Formulaires

```php
// Input texte
echo renderInput('email', 'Email', $email, 'email', [
    'required' => true,
    'placeholder' => 'exemple@email.com',
    'hint' => 'Nous ne partagerons jamais votre email.'
]);

// Select
echo renderSelect('niveau', 
    ['L1' => 'Licence 1', 'L2' => 'Licence 2', 'L3' => 'Licence 3'],
    'Niveau d\'étude',
    'L2',
    ['required' => true]
);

// TextArea
echo renderTextArea('description', 'Description', $description, [
    'rows' => 4,
    'placeholder' => 'Entrez une description...'
]);

// Checkbox
echo renderCheckbox('newsletter', 'Recevoir les notifications', true);
```

### Tabs

```php
$tabs = [
    ['id' => 'general', 'label' => 'Général', 'icon' => 'fa-info-circle'],
    ['id' => 'notes', 'label' => 'Notes', 'icon' => 'fa-chart-bar', 'count' => 12],
];

$panels = [
    'general' => '<p>Contenu onglet 1</p>',
    'notes' => '<p>Contenu onglet 2</p>',
];

echo renderTabsWithPanels($tabs, $panels, 'general');
```

### Progress

```php
// Barre simple
echo renderProgress(75, 'success', '', true);  // 75%, vert, affiche %

// Avec label
echo renderProgressLabeled('Avancement', 45, 100, 'primary');

// Étapes (wizard)
echo renderStepProgress(['Étape 1', 'Étape 2', 'Étape 3'], 2);
```

### Timeline

```php
echo renderTimeline([
    ['title' => 'Dépôt', 'description' => 'Rapport soumis', 'date' => '15 Jan', 'status' => 'complete'],
    ['title' => 'Validation', 'description' => 'En cours', 'status' => 'active'],
    ['title' => 'Soutenance', 'status' => 'pending'],
]);
```

### Avatar

```php
// Avec initiales
echo renderAvatar('Jean Dupont');  // Affiche "JD"

// Avec image
echo renderAvatar('Jean Dupont', '', '/uploads/photo.jpg');

// Groupe d'avatars
echo renderAvatarGroup($users, 4);  // Max 4 affichés
```

## 🎨 Design Tokens (CSS Variables)

Toutes les couleurs et styles sont centralisés dans `premium.css`:

```css
:root {
  --primary: #1a5276;
  --accent: #3b82f6;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --radius: 0.5rem;
  --shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
}
```

Pour changer le thème, modifiez uniquement ce fichier.

## 🔧 JavaScript (namespace CM)

```javascript
// Modales
CM.Modal.show('id');
CM.Modal.hide('id');
CM.Modal.confirm({ title: '...', message: '...', type: 'danger' }).then(ok => { ... });

// Toasts
CM.Toast.success('Message');
CM.Toast.error('Erreur');
CM.Toast.warning('Attention');

// Sidebar
CM.Sidebar.toggleCategory('collapse-id');
CM.Sidebar.toggleMobile();

// Formulaires
CM.Form.validate(form);
CM.Form.serialize(form);

// Utilitaires
CM.Utils.formatDate(date);
CM.Utils.formatCurrency(amount);
CM.Utils.debounce(fn, delay);
```

## 🔄 Migration Progressive

1. **Nouveau layout**: Utilisez `layout_premium.php` au lieu de `layout.php`
2. **Vues existantes**: Remplacez progressivement les composants inline par les fonctions `render*()`
3. **Test**: Accédez à `?page=demo_components` pour voir tous les composants

## 📝 Exemple Complet de Migration

**Avant (ancien code):**
```php
<div class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-xs">
    <?php if ($statut === 'valide'): ?>
        Validé
    <?php elseif ($statut === 'rejete'): ?>
        Rejeté
    <?php else: ?>
        En cours
    <?php endif; ?>
</div>
```

**Après (nouveau code):**
```php
<?php echo renderBadge($statut); ?>
```

---

## 📚 Fichiers Clés

| Fichier | Description |
|---------|-------------|
| `public/css/premium.css` | Styles CSS centralisés |
| `public/js/premium.js` | JavaScript centralisé |
| `ressources/views/components/index.php` | Loader de tous les composants |
| `public/layout_premium.php` | Nouveau layout principal |
| `ressources/views/demo_components_content.php` | Page de démonstration |

---

*Design System inspiré de [Shadcn/UI](https://ui.shadcn.com/) adapté pour PHP.*

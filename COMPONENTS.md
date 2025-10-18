# Composants Réutilisables - Check Master UFHB

Ce fichier documente les composants HTML/PHP réutilisables disponibles pour la plateforme Check Master UFHB.

## 📋 Table des Matières

1. [Cartes (Cards)](#cartes-cards)
2. [Boutons](#boutons)
3. [Badges et Statuts](#badges-et-statuts)
4. [Modales](#modales)
5. [Formulaires](#formulaires)
6. [Tableaux](#tableaux)
7. [Notifications](#notifications)
8. [Navigation](#navigation)

---

## Cartes (Cards)

### Carte Standard

```php
<div class="card p-6">
    <h3 class="text-xl font-semibold text-primary mb-4">
        <?= htmlspecialchars($titre) ?>
    </h3>
    <p class="text-gray-600">
        <?= htmlspecialchars($contenu) ?>
    </p>
</div>
```

### Carte avec Hover Effect

```php
<div class="card card-hover p-6">
    <div class="inline-flex items-center justify-center w-12 h-12 bg-primary/10 rounded-lg mb-4">
        <i class="fas fa-icon text-primary text-xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-gray-900 mb-2">
        <?= htmlspecialchars($titre) ?>
    </h3>
    <p class="text-gray-600 text-sm">
        <?= htmlspecialchars($description) ?>
    </p>
</div>
```

### Carte Statistique avec Gradient

```php
<div class="gradient-purple rounded-xl p-4 text-white shadow-lg card-hover">
    <div class="flex justify-between items-center">
        <div class="bg-white bg-opacity-20 w-12 h-12 rounded-xl flex items-center justify-center">
            <i class="fas fa-<?= $icon ?> text-white text-xl"></i>
        </div>
        <div class="text-right">
            <h3 class="text-3xl font-bold"><?= $valeur ?></h3>
            <p class="text-sm opacity-90"><?= $label ?></p>
        </div>
    </div>
</div>
```

### Carte avec Image et Action

```php
<div class="card card-hover overflow-hidden">
    <div class="p-4">
        <?php if (!empty($image)): ?>
            <div class="inline-flex items-center justify-center p-2 bg-primary/10 rounded-md mb-3">
                <img src="<?= htmlspecialchars($image) ?>" alt="icon" class="w-6 h-6">
            </div>
        <?php endif; ?>
        
        <h5 class="text-primary font-semibold text-base mb-2">
            <?= htmlspecialchars($titre) ?>
        </h5>
        
        <p class="text-gray-600 text-sm mb-4">
            <?= htmlspecialchars($description) ?>
        </p>
        
        <a href="<?= htmlspecialchars($lien) ?>" 
           class="inline-flex items-center text-accent font-medium text-sm hover:text-accent/80 transition-colors">
            Voir plus
            <i class="ml-2 fas fa-arrow-right text-xs"></i>
        </a>
    </div>
</div>
```

---

## Boutons

### Bouton Primaire

```php
<button class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-light transition-all duration-300 transform hover:scale-105">
    <?= $texte ?>
</button>
```

### Bouton avec Icône

```php
<button class="inline-flex items-center bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-light transition-all duration-300">
    <i class="fas fa-<?= $icon ?> mr-2"></i>
    <?= $texte ?>
</button>
```

### Bouton Secondaire

```php
<button class="bg-white text-primary border-2 border-primary px-6 py-3 rounded-lg font-semibold hover:bg-primary hover:text-white transition-all duration-300">
    <?= $texte ?>
</button>
```

### Bouton Danger

```php
<button class="bg-danger text-white px-6 py-3 rounded-lg font-semibold hover:bg-red-600 transition-all duration-300">
    <?= $texte ?>
</button>
```

### Bouton Succès

```php
<button class="bg-accent text-white px-6 py-3 rounded-lg font-semibold hover:bg-green-600 transition-all duration-300">
    <?= $texte ?>
</button>
```

### Bouton avec Gradient (Card Button)

```php
<a href="<?= htmlspecialchars($lien) ?>" class="card-btn">
    <span><?= $texte ?></span>
    <i class="fas fa-arrow-right ml-2"></i>
</a>
```

---

## Badges et Statuts

### Badge Succès

```php
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/10 text-accent">
    <i class="fas fa-check-circle mr-2"></i>
    Validé
</span>
```

### Badge Warning

```php
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-secondary/10 text-secondary">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    En attente
</span>
```

### Badge Danger

```php
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-danger/10 text-danger">
    <i class="fas fa-times-circle mr-2"></i>
    Rejeté
</span>
```

### Badge Info

```php
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-primary/10 text-primary">
    <i class="fas fa-info-circle mr-2"></i>
    Information
</span>
```

### Badge Note (Grade)

```php
<?php
$gradeClass = '';
switch($note) {
    case 'A': $gradeClass = 'grade-A'; break;
    case 'B': $gradeClass = 'grade-B'; break;
    case 'C': $gradeClass = 'grade-C'; break;
    case 'D': $gradeClass = 'grade-D'; break;
    case 'F': $gradeClass = 'grade-F'; break;
}
?>
<span class="<?= $gradeClass ?> px-4 py-2 rounded-lg font-bold">
    <?= $note ?>
</span>
```

---

## Modales

### Structure de Modale Standard

```php
<!-- Backdrop -->
<div id="modal-backdrop" class="modal-backdrop hidden"></div>

<!-- Modal Container -->
<div id="modal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="modal-content max-w-md w-full">
        <!-- Icon -->
        <div class="modal-icon modal-icon-success">
            <i class="fas fa-check text-2xl"></i>
        </div>
        
        <!-- Title -->
        <h3 class="text-xl font-semibold text-center text-gray-900 mb-3">
            Titre de la modale
        </h3>
        
        <!-- Text -->
        <p class="text-sm text-center text-gray-600 mb-6">
            Message de la modale
        </p>
        
        <!-- Buttons -->
        <div class="flex justify-center gap-4">
            <button class="modal-button modal-button-primary" onclick="confirmer()">
                Confirmer
            </button>
            <button class="modal-button modal-button-secondary" onclick="fermerModal()">
                Annuler
            </button>
        </div>
    </div>
</div>

<script>
function ouvrirModal() {
    document.getElementById('modal').classList.remove('hidden');
    document.getElementById('modal-backdrop').classList.remove('hidden');
}

function fermerModal() {
    document.getElementById('modal').classList.add('hidden');
    document.getElementById('modal-backdrop').classList.add('hidden');
}
</script>
```

### Modale de Confirmation de Suppression

```php
<div id="delete-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center px-4">
    <div class="modal-backdrop"></div>
    <div class="modal-content max-w-md w-full">
        <div class="modal-icon modal-icon-warning">
            <i class="fas fa-exclamation-triangle text-2xl"></i>
        </div>
        
        <h3 class="text-xl font-semibold text-center text-gray-900 mb-3">
            Confirmer la suppression
        </h3>
        
        <p class="text-sm text-center text-gray-600 mb-6">
            Cette action est irréversible. Êtes-vous sûr de vouloir supprimer cet élément ?
        </p>
        
        <div class="flex justify-center gap-4">
            <button class="modal-button modal-button-danger" onclick="supprimerElement()">
                Supprimer
            </button>
            <button class="modal-button modal-button-secondary" onclick="fermerDeleteModal()">
                Annuler
            </button>
        </div>
    </div>
</div>
```

---

## Formulaires

### Input Standard

```php
<div class="space-y-2">
    <label for="<?= $id ?>" class="text-sm font-semibold text-gray-800">
        <?= $label ?>
    </label>
    <input 
        type="<?= $type ?>" 
        id="<?= $id ?>" 
        name="<?= $name ?>"
        class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition"
        placeholder="<?= $placeholder ?>"
        <?= $required ? 'required' : '' ?>
    >
</div>
```

### Input avec Icône

```php
<div class="space-y-2">
    <label for="<?= $id ?>" class="text-sm font-semibold text-gray-800">
        <?= $label ?>
    </label>
    <div class="relative">
        <input 
            type="<?= $type ?>" 
            id="<?= $id ?>" 
            name="<?= $name ?>"
            class="w-full rounded-lg border border-gray-200 pl-4 pr-12 py-3 text-sm font-medium text-gray-900 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition"
            placeholder="<?= $placeholder ?>"
        >
        <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
            <i class="fas fa-<?= $icon ?>"></i>
        </div>
    </div>
</div>
```

### Select

```php
<div class="space-y-2">
    <label for="<?= $id ?>" class="text-sm font-semibold text-gray-800">
        <?= $label ?>
    </label>
    <select 
        id="<?= $id ?>" 
        name="<?= $name ?>"
        class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition"
        <?= $required ? 'required' : '' ?>
    >
        <option value="">Sélectionnez une option</option>
        <?php foreach ($options as $value => $text): ?>
            <option value="<?= htmlspecialchars($value) ?>">
                <?= htmlspecialchars($text) ?>
            </option>
        <?php endforeach; ?>
    </select>
</div>
```

### Textarea

```php
<div class="space-y-2">
    <label for="<?= $id ?>" class="text-sm font-semibold text-gray-800">
        <?= $label ?>
    </label>
    <textarea 
        id="<?= $id ?>" 
        name="<?= $name ?>"
        rows="<?= $rows ?? 4 ?>"
        class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition resize-none"
        placeholder="<?= $placeholder ?>"
        <?= $required ? 'required' : '' ?>
    ><?= htmlspecialchars($value ?? '') ?></textarea>
</div>
```

### Checkbox

```php
<div class="flex items-center">
    <input 
        type="checkbox" 
        id="<?= $id ?>" 
        name="<?= $name ?>"
        class="w-5 h-5 text-primary border-gray-300 rounded focus:ring-primary focus:ring-2"
        <?= $checked ? 'checked' : '' ?>
    >
    <label for="<?= $id ?>" class="ml-3 text-sm font-medium text-gray-900">
        <?= $label ?>
    </label>
</div>
```

---

## Tableaux

### Tableau Standard

```php
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
            <tr>
                <?php foreach ($colonnes as $colonne): ?>
                    <th class="px-6 py-4 text-left text-sm font-semibold text-primary">
                        <?= htmlspecialchars($colonne) ?>
                    </th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php foreach ($donnees as $ligne): ?>
                <tr class="table-row-hover">
                    <?php foreach ($ligne as $cellule): ?>
                        <td class="px-6 py-4 text-sm text-gray-900">
                            <?= htmlspecialchars($cellule) ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

### Tableau avec Actions

```php
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold text-primary">Nom</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-primary">Email</th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-primary">Statut</th>
                <th class="px-6 py-4 text-right text-sm font-semibold text-primary">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <?php foreach ($utilisateurs as $user): ?>
                <tr class="table-row-hover">
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <?= htmlspecialchars($user['nom']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-gray-900">
                        <?= htmlspecialchars($user['email']) ?>
                    </td>
                    <td class="px-6 py-4 text-sm">
                        <?php if ($user['actif']): ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-accent/10 text-accent">
                                Actif
                            </span>
                        <?php else: ?>
                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                Inactif
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 text-sm text-right">
                        <div class="flex justify-end gap-2">
                            <button class="text-primary hover:text-primary-light transition-colors btn-icon" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="text-danger hover:text-red-600 transition-colors btn-icon" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

---

## Notifications

### Notification Succès

```php
<?php if (isset($_SESSION['success_message'])): ?>
    <div class="bg-accent/10 border border-accent text-accent px-4 py-3 rounded-lg mb-6 flex items-center">
        <i class="fas fa-check-circle mr-3 text-xl"></i>
        <div>
            <strong class="font-bold">Succès !</strong>
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['success_message']) ?></span>
        </div>
    </div>
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
```

### Notification Erreur

```php
<?php if (isset($_SESSION['error_message'])): ?>
    <div class="bg-danger/10 border border-danger text-danger px-4 py-3 rounded-lg mb-6 flex items-center">
        <i class="fas fa-exclamation-circle mr-3 text-xl"></i>
        <div>
            <strong class="font-bold">Erreur !</strong>
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['error_message']) ?></span>
        </div>
    </div>
    <?php unset($_SESSION['error_message']); ?>
<?php endif; ?>
```

### Notification Warning

```php
<?php if (isset($_SESSION['warning_message'])): ?>
    <div class="bg-secondary/10 border border-secondary text-secondary px-4 py-3 rounded-lg mb-6 flex items-center">
        <i class="fas fa-exclamation-triangle mr-3 text-xl"></i>
        <div>
            <strong class="font-bold">Attention !</strong>
            <span class="block sm:inline"><?= htmlspecialchars($_SESSION['warning_message']) ?></span>
        </div>
    </div>
    <?php unset($_SESSION['warning_message']); ?>
<?php endif; ?>
```

---

## Navigation

### Breadcrumb

```php
<nav class="flex mb-6" aria-label="Breadcrumb">
    <ol class="inline-flex items-center space-x-1 md:space-x-3">
        <?php foreach ($breadcrumbs as $index => $item): ?>
            <li class="inline-flex items-center">
                <?php if ($index > 0): ?>
                    <i class="fas fa-chevron-right text-gray-400 mx-2 text-xs"></i>
                <?php endif; ?>
                
                <?php if ($index < count($breadcrumbs) - 1): ?>
                    <a href="<?= htmlspecialchars($item['url']) ?>" 
                       class="inline-flex items-center text-sm font-medium text-gray-600 hover:text-primary transition-colors">
                        <?php if ($index === 0): ?>
                            <i class="fas fa-home mr-2"></i>
                        <?php endif; ?>
                        <?= htmlspecialchars($item['label']) ?>
                    </a>
                <?php else: ?>
                    <span class="text-sm font-medium text-primary">
                        <?= htmlspecialchars($item['label']) ?>
                    </span>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ol>
</nav>
```

### Tabs/Onglets

```php
<div class="border-b border-gray-200 mb-6">
    <nav class="flex space-x-8" aria-label="Tabs">
        <?php foreach ($tabs as $tabId => $tabLabel): ?>
            <button 
                class="btn-tab py-4 px-1 border-b-2 font-medium text-sm transition-all <?= $activeTab === $tabId ? 'active border-primary text-primary' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' ?>"
                onclick="changerTab('<?= $tabId ?>')"
            >
                <?= htmlspecialchars($tabLabel) ?>
            </button>
        <?php endforeach; ?>
    </nav>
</div>

<div id="tab-contents">
    <?php foreach ($tabs as $tabId => $tabLabel): ?>
        <div id="tab-<?= $tabId ?>" class="tab-content <?= $activeTab === $tabId ? 'active' : '' ?>">
            <!-- Contenu de l'onglet -->
        </div>
    <?php endforeach; ?>
</div>

<script>
function changerTab(tabId) {
    // Masquer tous les contenus
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    // Retirer la classe active de tous les boutons
    document.querySelectorAll('.btn-tab').forEach(btn => {
        btn.classList.remove('active', 'border-primary', 'text-primary');
        btn.classList.add('border-transparent', 'text-gray-500');
    });
    
    // Afficher le contenu sélectionné
    document.getElementById('tab-' + tabId).classList.add('active');
    
    // Activer le bouton sélectionné
    event.target.classList.add('active', 'border-primary', 'text-primary');
    event.target.classList.remove('border-transparent', 'text-gray-500');
}
</script>
```

---

## Notes d'Utilisation

### Bonnes Pratiques

1. **Sécurité** : Toujours utiliser `htmlspecialchars()` pour échapper les données affichées
2. **Accessibilité** : Ajouter des attributs `aria-label` et `title` aux éléments interactifs
3. **Responsive** : Tester tous les composants sur mobile, tablette et desktop
4. **Performance** : Minimiser l'utilisation de styles inline, privilégier les classes Tailwind
5. **Cohérence** : Utiliser systématiquement les classes et composants définis dans ce guide

### Classes Utiles

- `card` : Carte standard avec ombres et bordures
- `card-hover` : Ajoute un effet de survol à une carte
- `btn-icon` : Bouton icône avec animation de scale
- `gradient-purple`, `gradient-blue`, `gradient-green` : Gradients officiels
- `table-row-hover` : Effet de survol pour lignes de tableau
- `modal-*` : Classes pour modales
- `fade-in` : Animation d'apparition en fondu

---

**Version** : 1.0  
**Date** : Octobre 2024  
**Mainteneur** : Équipe Check Master UFHB

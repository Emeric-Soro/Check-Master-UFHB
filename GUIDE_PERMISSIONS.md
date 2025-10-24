# Guide d'implémentation des permissions CRUD dans les contrôleurs

Ce document explique comment intégrer les vérifications de permissions dans les contrôleurs existants.

## 1. Inclure le fichier de permissions

Au début de chaque contrôleur, ajoutez :

```php
require_once __DIR__ . '/../utils/permissions.php';
```

## 2. Vérifier les permissions dans les méthodes

### Pour les méthodes d'affichage (READ)

La vérification READ est déjà faite au niveau du `layout.php`, mais vous pouvez ajouter une vérification supplémentaire :

```php
public function index()
{
    // Vérifier la permission READ (optionnel, déjà fait dans layout.php)
    if (!hasPermission('gestion_utilisateurs', 'READ')) {
        $_SESSION['error_message'] = "Accès refusé.";
        header('Location: layout.php?page=dashboard');
        exit;
    }
    
    // Reste du code...
}
```

### Pour les méthodes de création (CREATE)

```php
if (isset($_POST['btn_add_utilisateur'])) {
    // Vérifier la permission CREATE
    if (!hasPermission('gestion_utilisateurs', 'CREATE')) {
        $_SESSION['error_message'] = "Vous n'avez pas la permission d'ajouter des utilisateurs.";
        header('Location: layout.php?page=gestion_utilisateurs');
        exit;
    }
    
    // Logique d'ajout
    $nom_utilisateur = $_POST['nom_utilisateur'] ?? '';
    // ... reste du code
}
```

### Pour les méthodes de modification (UPDATE)

```php
if (isset($_POST['btn_modifier_utilisateur'])) {
    // Vérifier la permission UPDATE
    if (!hasPermission('gestion_utilisateurs', 'UPDATE')) {
        $_SESSION['error_message'] = "Vous n'avez pas la permission de modifier des utilisateurs.";
        header('Location: layout.php?page=gestion_utilisateurs');
        exit;
    }
    
    // Logique de modification
    $id_utilisateur = $_POST['id_utilisateur'] ?? '';
    // ... reste du code
}
```

### Pour les méthodes de suppression (DELETE)

```php
if (isset($_POST['btn_delete_utilisateur'])) {
    // Vérifier la permission DELETE
    if (!hasPermission('gestion_utilisateurs', 'DELETE')) {
        $_SESSION['error_message'] = "Vous n'avez pas la permission de supprimer des utilisateurs.";
        header('Location: layout.php?page=gestion_utilisateurs');
        exit;
    }
    
    // Logique de suppression
    $id_utilisateur = $_POST['id_utilisateur'] ?? '';
    // ... reste du code
}
```

## 3. Adapter les vues pour masquer les boutons

Dans les vues, conditionnez l'affichage des boutons :

```php
<!-- Bouton Ajouter -->
<?php if (hasPermission('gestion_utilisateurs', 'CREATE')): ?>
    <button type="button" class="btn btn-primary" onclick="openAddModal()">
        <i class="fas fa-plus"></i> Ajouter un utilisateur
    </button>
<?php endif; ?>

<!-- Bouton Modifier -->
<?php if (hasPermission('gestion_utilisateurs', 'UPDATE')): ?>
    <button type="button" class="btn btn-warning" onclick="editUser(<?= $user->id ?>)">
        <i class="fas fa-edit"></i> Modifier
    </button>
<?php endif; ?>

<!-- Bouton Supprimer -->
<?php if (hasPermission('gestion_utilisateurs', 'DELETE')): ?>
    <button type="button" class="btn btn-danger" onclick="deleteUser(<?= $user->id ?>)">
        <i class="fas fa-trash"></i> Supprimer
    </button>
<?php endif; ?>
```

## 4. Utiliser les fonctions helper

### Vérifier plusieurs permissions

```php
// Vérifier si l'utilisateur a AU MOINS une des permissions
if (hasAnyPermission('gestion_utilisateurs', ['CREATE', 'UPDATE', 'DELETE'])) {
    // Afficher le formulaire d'édition
}

// Vérifier si l'utilisateur a TOUTES les permissions
if (hasAllPermissions('gestion_utilisateurs', ['READ', 'UPDATE'])) {
    // Afficher l'interface complète
}
```

### Obtenir toutes les permissions pour un traitement

```php
$permissions = getUserPermissions('gestion_utilisateurs');
// Retourne: ['CREATE' => true/false, 'READ' => true/false, 'UPDATE' => true/false, 'DELETE' => true/false]
```

### Fonction helper pour l'affichage conditionnel

```php
// Afficher un bouton seulement si autorisé
echo renderIfHasPermission('gestion_utilisateurs', 'CREATE', 
    '<button class="btn btn-primary">Ajouter</button>'
);
```

## 5. Exemples complets

### Exemple 1: Contrôleur simple

```php
<?php
require_once __DIR__ . '/../utils/permissions.php';

class MonController {
    
    public function index() {
        // READ déjà vérifié dans layout.php
        $data = $this->model->getAll();
        // Afficher la vue
    }
    
    public function create() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!hasPermission('mon_traitement', 'CREATE')) {
                $_SESSION['error_message'] = "Permission refusée";
                header('Location: layout.php?page=mon_traitement');
                exit;
            }
            
            // Logique de création
            $this->model->create($_POST);
        }
    }
    
    public function update() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!hasPermission('mon_traitement', 'UPDATE')) {
                $_SESSION['error_message'] = "Permission refusée";
                header('Location: layout.php?page=mon_traitement');
                exit;
            }
            
            // Logique de modification
            $this->model->update($_POST);
        }
    }
    
    public function delete() {
        if (!hasPermission('mon_traitement', 'DELETE')) {
            echo json_encode(['success' => false, 'message' => 'Permission refusée']);
            exit;
        }
        
        // Logique de suppression
        $this->model->delete($_POST['id']);
        echo json_encode(['success' => true]);
    }
}
```

### Exemple 2: Vue avec permissions conditionnelles

```php
<?php require_once __DIR__ . '/../../app/utils/permissions.php'; ?>

<div class="container">
    <h1>Liste des éléments</h1>
    
    <!-- Bouton d'ajout conditionnel -->
    <?php if (hasPermission('mon_traitement', 'CREATE')): ?>
    <button class="btn btn-primary" onclick="showAddForm()">
        <i class="fas fa-plus"></i> Ajouter
    </button>
    <?php endif; ?>
    
    <!-- Tableau -->
    <table>
        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <?php if (hasAnyPermission('mon_traitement', ['UPDATE', 'DELETE'])): ?>
                <th>Actions</th>
                <?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($items as $item): ?>
            <tr>
                <td><?= htmlspecialchars($item->nom) ?></td>
                <td><?= htmlspecialchars($item->description) ?></td>
                <?php if (hasAnyPermission('mon_traitement', ['UPDATE', 'DELETE'])): ?>
                <td>
                    <?php if (hasPermission('mon_traitement', 'UPDATE')): ?>
                    <button class="btn btn-sm btn-warning" onclick="edit(<?= $item->id ?>)">
                        <i class="fas fa-edit"></i>
                    </button>
                    <?php endif; ?>
                    
                    <?php if (hasPermission('mon_traitement', 'DELETE')): ?>
                    <button class="btn btn-sm btn-danger" onclick="deleteItem(<?= $item->id ?>)">
                        <i class="fas fa-trash"></i>
                    </button>
                    <?php endif; ?>
                </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
```

## 6. Bonnes pratiques

1. **Double vérification** : Vérifiez toujours les permissions côté serveur, même si vous les masquez côté client
2. **Messages clairs** : Fournissez des messages d'erreur explicites quand une permission est refusée
3. **Logs d'audit** : Enregistrez les tentatives d'accès non autorisées dans les logs
4. **Cohérence** : Utilisez le même libellé de traitement partout (ex: 'gestion_utilisateurs')
5. **Tests** : Testez chaque combinaison de permissions pour s'assurer qu'elle fonctionne correctement

## 7. Débogage

Pour voir les permissions de l'utilisateur connecté :

```php
// En mode développement uniquement
if ($_SERVER['REMOTE_ADDR'] === '127.0.0.1') {
    echo '<pre>';
    print_r($_SESSION['permissions']);
    echo '</pre>';
}
```

## 8. Migration progressive

Pour migrer progressivement :

1. Commencez par ajouter les vérifications dans les contrôleurs les plus critiques
2. Ajoutez ensuite les conditions dans les vues
3. Testez chaque module individuellement
4. Documentez les modules migrés dans un fichier MIGRATION.md

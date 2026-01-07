# Système de Permissions CRUD - Phase 4 ✅

## Vue d'ensemble

Le système de permissions CRUD permet de contrôler l'accès aux pages et aux actions (Créer, Voir, Modifier, Supprimer) pour chaque groupe d'utilisateurs.

## Architecture

### 1. **PermissionMiddleware.php** (`app/middlewares/`)

Vérifie automatiquement les permissions avant de charger une page.

**Fonctionnalités :**

- ✅ Détection automatique de l'action CRUD depuis l'URL/POST
- ✅ Vérification des permissions par page et action
- ✅ Blocage automatique avec message d'erreur
- ✅ Logging des tentatives d'accès non autorisés
- ✅ Admin bypass (id_GU = 5 a tous les droits)

### 2. **permissions_helper.php** (`app/utils/`)

Fonctions PHP pour vérifier les permissions dans les vues.

**Fonctions disponibles :**

```php
// Vérifier les permissions
canView($codeFonctionnalite = null);      // Peut voir la page
canCreate($codeFonctionnalite = null);    // Peut créer
canEdit($codeFonctionnalite = null);      // Peut modifier
canDelete($codeFonctionnalite = null);    // Peut supprimer

// Affichage conditionnel
showIfCan($action, $buttonHtml, $codeFonctionnalite = null);
showNoPermissionMessage($action, $codeFonctionnalite = null);

// Utilitaires
getCurrentPermissions($codeFonctionnalite = null);
isAdmin();
```

## Utilisation dans les vues

### Exemple 1 : Afficher un bouton "Ajouter" seulement si l'utilisateur peut créer

```php
<?php if (canCreate()): ?>
    <a href="?page=gestion_etudiants&action=ajouter"
       class="btn btn-primary">
        <i class="fas fa-plus"></i> Ajouter un étudiant
    </a>
<?php endif; ?>
```

### Exemple 2 : Masquer les boutons Modifier/Supprimer

```php
<td>
    <?php if (canEdit()): ?>
        <a href="?page=gestion_etudiants&action=modifier&id=<?= $etudiant->id ?>"
           class="btn btn-sm btn-warning">
            <i class="fas fa-edit"></i>
        </a>
    <?php endif; ?>

    <?php if (canDelete()): ?>
        <button onclick="confirmerSuppression(<?= $etudiant->id ?>)"
                class="btn btn-sm btn-danger">
            <i class="fas fa-trash"></i>
        </button>
    <?php endif; ?>
</td>
```

### Exemple 3 : Utiliser showIfCan()

```php
<?php
// Afficher le bouton seulement si l'utilisateur peut créer
echo showIfCan('creer', '
    <button type="submit" class="btn btn-primary">
        <i class="fas fa-save"></i> Enregistrer
    </button>
');
?>
```

### Exemple 4 : Vérifier toutes les permissions d'un coup

```php
<?php
$perms = getCurrentPermissions();
?>

<div class="permissions-info">
    <p>Vos permissions sur cette page :</p>
    <ul>
        <li>Voir : <?= $perms['peut_voir'] ? '✅' : '❌' ?></li>
        <li>Créer : <?= $perms['peut_creer'] ? '✅' : '❌' ?></li>
        <li>Modifier : <?= $perms['peut_modifier'] ? '✅' : '❌' ?></li>
        <li>Supprimer : <?= $perms['peut_supprimer'] ? '✅' : '❌' ?></li>
    </ul>
</div>
```

## Utilisation dans les contrôleurs

### Exemple : Vérification manuelle dans une méthode

```php
public function ajouterEtudiant()
{
    // Vérifier la permission de créer
    $middleware = new PermissionMiddleware();
    $hasPermission = $middleware->checkPageAccess(
        'gestion_etudiants',
        $_SESSION['id_GU'],
        'creer'
    );

    if (!$hasPermission) {
        $_SESSION['error_message'] = 'Vous n\'avez pas l\'autorisation de créer des étudiants.';
        header('Location: layout.php?page=dashboard&error=permission');
        exit;
    }

    // Suite du code...
}
```

## Détection automatique des actions

Le middleware détecte automatiquement l'action CRUD :

### Par URL :

- `?action=ajouter` → **creer**
- `?action=modifier` → **modifier**
- `?action=supprimer` → **supprimer**
- `?action=voir` → **voir**

### Par POST :

- `$_POST['submit_add']` → **creer**
- `$_POST['submit_edit']` → **modifier**
- `$_POST['submit_delete']` → **supprimer**

### Mots-clés détectés :

- **Créer** : ajouter, create, new
- **Modifier** : modifier, edit, update
- **Supprimer** : supprimer, delete, remove

## Pages publiques (sans vérification)

Certaines pages ne nécessitent pas de vérification :

- `page_connexion`
- `logout`
- `reset_password`

## Admin bypass

L'administrateur (id_GU = 5) bypasse **toutes** les vérifications et a accès à **tout**.

## Logging

Toutes les tentatives d'accès non autorisé sont enregistrées dans la table `audit_logs` :

```sql
SELECT * FROM audit_logs
WHERE type_action = 'acces_refuse'
ORDER BY date_action DESC;
```

## Configuration des permissions

Pour attribuer des permissions à un groupe, utilisez la page :

```
http://localhost:8000/.../layout.php?page=parametres_generaux&action=gestion_attribution
```

## Tables de la base de données

### `permissions`

```sql
CREATE TABLE permissions (
    id_permission INT AUTO_INCREMENT PRIMARY KEY,
    id_GU INT NOT NULL,
    id_fonctionnalite INT NOT NULL,
    peut_voir BOOLEAN DEFAULT FALSE,
    peut_creer BOOLEAN DEFAULT FALSE,
    peut_modifier BOOLEAN DEFAULT FALSE,
    peut_supprimer BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_GU) REFERENCES groupe_utilisateur(id_GU),
    FOREIGN KEY (id_fonctionnalite) REFERENCES fonctionnalites(id_fonctionnalite)
);
```

## Messages d'erreur

Lorsqu'un utilisateur tente d'accéder à une page sans permission :

1. **Message affiché** : Encadré rouge en haut de la page
2. **Redirection** : Vers le dashboard approprié
3. **Log** : Enregistrement dans audit_logs

## Exemple complet : Page de gestion

```php
<!-- En-tête de la page -->
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold">Gestion des étudiants</h1>

    <?php if (canCreate()): ?>
        <a href="?page=gestion_etudiants&action=ajouter" class="btn btn-primary">
            <i class="fas fa-plus"></i> Ajouter un étudiant
        </a>
    <?php endif; ?>
</div>

<!-- Tableau avec actions conditionnelles -->
<table class="table">
    <thead>
        <tr>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Email</th>
            <?php if (canEdit() || canDelete()): ?>
                <th>Actions</th>
            <?php endif; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($etudiants as $etudiant): ?>
            <tr>
                <td><?= htmlspecialchars($etudiant->nom) ?></td>
                <td><?= htmlspecialchars($etudiant->prenom) ?></td>
                <td><?= htmlspecialchars($etudiant->email) ?></td>
                <?php if (canEdit() || canDelete()): ?>
                    <td class="flex gap-2">
                        <?php if (canEdit()): ?>
                            <a href="?page=gestion_etudiants&action=modifier&id=<?= $etudiant->id ?>"
                               class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                        <?php endif; ?>

                        <?php if (canDelete()): ?>
                            <button onclick="confirmerSuppression(<?= $etudiant->id ?>)"
                                    class="btn btn-sm btn-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                <?php endif; ?>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
```

## Tests

Pour tester le système :

1. **Connectez-vous avec un utilisateur non-admin**
2. **Allez sur une page où vous n'avez pas de permissions**
3. **Vérifiez** : Message d'erreur rouge + redirection
4. **Vérifiez** : Log dans audit_logs

## Prochaines étapes

- ✅ Phase 4 : Middleware Permissions (TERMINÉ)
- ⏳ Phase 5 : Adapter la page gestion_attribution pour gérer les permissions CRUD
- ⏳ Phase 6 : Tests avec tous les groupes d'utilisateurs

---

**Créé le** : 6 janvier 2026  
**Statut** : ✅ Phase 4 complète et fonctionnelle

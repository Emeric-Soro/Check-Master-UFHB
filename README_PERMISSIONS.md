# Système de Permissions Granulaires CRUD - Implémentation Complète

## Vue d'ensemble

Ce projet a été amélioré avec un système de contrôle d'accès basé sur les rôles (RBAC) granulaire permettant un contrôle précis des actions CRUD (Consulter, Ajouter, Modifier, Supprimer) pour chaque rôle et fonctionnalité.

## Changements majeurs

### Avant
- Système de permissions binaire (accès à une fonctionnalité ou non)
- Table `rattacher` avec uniquement `id_GU` et `id_traitement`
- Impossible de différencier les droits de lecture, création, modification et suppression
- Tous les boutons d'action visibles pour tous les utilisateurs ayant accès

### Après
- Système de permissions granulaires CRUD
- Table `permissions` avec `id_GU`, `id_traitement` et `id_action`
- Contrôle précis des 4 actions: Consulter (READ), Ajouter (CREATE), Modifier (UPDATE), Supprimer (DELETE)
- Affichage conditionnel des boutons selon les permissions
- Vérification côté serveur de toutes les actions

## Architecture

### Couche Base de Données

**Table `permissions`**
```sql
CREATE TABLE `permissions` (
    `id_permission` INT NOT NULL AUTO_INCREMENT,
    `id_GU` INT NOT NULL,                  -- ID du groupe d'utilisateurs
    `id_traitement` INT NOT NULL,           -- ID de la fonctionnalité
    `id_action` INT NOT NULL,               -- ID de l'action (1,3,6,7)
    `date_creation` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id_permission`),
    UNIQUE KEY `unique_permission` (`id_GU`, `id_traitement`, `id_action`)
)
```

**Table `action` mise à jour**
- 1: Ajouter (CREATE)
- 3: Modifier (UPDATE)
- 6: Supprimer (DELETE)
- 7: Consulter (READ)

### Couche Backend

#### Modèles
- **`app/models/Permission.php`** : Gestion des permissions en base de données
  - `getPermissionsByGroupe()` : Récupère toutes les permissions d'un groupe
  - `hasPermission()` : Vérifie une permission spécifique
  - `ajouterPermission()` : Ajoute une permission
  - `supprimerPermission()` : Supprime une permission
  - `updatePermissionsTraitement()` : Met à jour toutes les permissions d'un traitement

#### Utilitaires
- **`app/utils/permissions.php`** : Fonctions globales de vérification
  - `hasPermission($traitement, $action)` : Vérifie si l'utilisateur a une permission
  - `hasAnyPermission($traitement, $actions)` : Au moins une permission
  - `hasAllPermissions($traitement, $actions)` : Toutes les permissions
  - `loadUserPermissions($db, $id_GU)` : Charge les permissions en session
  - `requirePermission($traitement, $action)` : Force une permission ou redirige
  - `getUserPermissions($traitement)` : Obtient toutes les permissions pour un traitement

#### Contrôleurs
- **`AuthController`** : Charge les permissions lors de la connexion
- **`ParametreController`** : Gestion des permissions via interface AJAX
- **`GestionUtilisateurController`** (exemple) : Vérifications CREATE/UPDATE

### Couche Frontend

#### Interface de gestion
- **`ressources/views/parametres_generaux/gestion_attribution.php`**
  - Matrice interactive de permissions
  - Mise à jour en temps réel via AJAX
  - Interface responsive et intuitive

#### Vues avec permissions
- **`ressources/views/gestion_utilisateurs_content.php`** (exemple)
  - Boutons conditionnels basés sur les permissions
  - Masquage automatique des actions non autorisées

### Flux de Sécurité

```
1. Connexion utilisateur
   └─> AuthController::login()
       └─> loadUserPermissions() charge les permissions en $_SESSION

2. Accès à une page
   └─> layout.php vérifie hasPermission($page, 'READ')
       └─> Si refusé: redirection vers dashboard
       └─> Si autorisé: affichage de la page

3. Action utilisateur (Create/Update/Delete)
   └─> Contrôleur vérifie hasPermission($page, $action)
       └─> Si refusé: message d'erreur + audit log
       └─> Si autorisé: exécution de l'action

4. Affichage des boutons
   └─> Vue vérifie hasPermission($page, $action)
       └─> Si refusé: bouton masqué
       └─> Si autorisé: bouton affiché
```

## Installation

### 1. Sauvegarder la base de données

```bash
mysqldump -u root -p soutenance_manager > backup_$(date +%Y%m%d).sql
```

### 2. Exécuter la migration

```bash
mysql -u root -p soutenance_manager < migrations/001_granular_rbac_migration.sql
```

### 3. Vérifier la migration

```sql
-- Vérifier que les permissions ont été créées
SELECT COUNT(*) FROM permissions;

-- Vérifier les actions
SELECT * FROM action;
```

### 4. Tester le système

1. Se connecter à l'application
2. Aller dans "Paramètres Généraux" → "Gestion des Attributions"
3. Sélectionner un groupe d'utilisateurs
4. Configurer les permissions CRUD
5. Se connecter avec un utilisateur du groupe
6. Vérifier que les boutons sont bien masqués/affichés

## Utilisation

### Pour les administrateurs

#### Configurer les permissions

1. Connectez-vous en tant qu'administrateur
2. Menu : Paramètres Généraux → Gestion des Attributions
3. Sélectionnez un groupe d'utilisateurs
4. Cochez/décochez les cases pour chaque fonctionnalité :
   - **C** (Consulter) : Voir la page
   - **A** (Ajouter) : Créer de nouveaux éléments
   - **M** (Modifier) : Modifier des éléments existants
   - **S** (Supprimer) : Supprimer des éléments
5. Les modifications sont automatiques

#### Créer un rôle "Lecture seule"

Pour un groupe qui peut seulement consulter :
- Cochez uniquement la colonne "Consulter" (C)
- Laissez Ajouter, Modifier et Supprimer décochés

#### Créer un rôle "Contributeur"

Pour un groupe qui peut lire et ajouter mais pas modifier/supprimer :
- Cochez "Consulter" et "Ajouter"
- Laissez "Modifier" et "Supprimer" décochés

### Pour les développeurs

#### Ajouter des vérifications dans un nouveau contrôleur

```php
<?php
require_once __DIR__ . '/../utils/permissions.php';

class MonController {
    
    public function create() {
        // Vérifier la permission CREATE
        if (!hasPermission('ma_fonctionnalite', 'CREATE')) {
            $_SESSION['error_message'] = "Permission refusée";
            header('Location: layout.php?page=dashboard');
            exit;
        }
        
        // Logique de création...
    }
}
```

#### Masquer des boutons dans une vue

```php
<?php require_once __DIR__ . '/../../app/utils/permissions.php'; ?>

<!-- Bouton visible seulement si l'utilisateur peut créer -->
<?php if (hasPermission('ma_fonctionnalite', 'CREATE')): ?>
    <button onclick="showAddForm()">
        <i class="fas fa-plus"></i> Ajouter
    </button>
<?php endif; ?>

<!-- Bouton visible seulement si l'utilisateur peut modifier -->
<?php if (hasPermission('ma_fonctionnalite', 'UPDATE')): ?>
    <button onclick="editItem(<?= $item->id ?>)">
        <i class="fas fa-edit"></i> Modifier
    </button>
<?php endif; ?>
```

## Documentation

- **[GUIDE_PERMISSIONS.md](GUIDE_PERMISSIONS.md)** : Guide complet pour implémenter les permissions
- **[MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)** : Instructions détaillées de migration

## Structure des fichiers

```
Check-Master-UFHB/
├── app/
│   ├── controllers/
│   │   ├── AuthController.php (modifié - charge les permissions)
│   │   ├── ParametreController.php (modifié - gestion AJAX)
│   │   └── GestionUtilisateurController.php (exemple)
│   ├── models/
│   │   └── Permission.php (nouveau)
│   └── utils/
│       └── permissions.php (nouveau)
├── migrations/
│   └── 001_granular_rbac_migration.sql (nouveau)
├── public/
│   └── layout.php (modifié - vérification READ)
├── ressources/
│   └── views/
│       ├── parametres_generaux/
│       │   ├── gestion_attribution.php (nouvelle interface)
│       │   └── gestion_attribution_backup.php (ancienne sauvegardée)
│       └── gestion_utilisateurs_content.php (exemple avec permissions)
├── GUIDE_PERMISSIONS.md (nouveau)
├── MIGRATION_GUIDE.md (nouveau)
└── README_PERMISSIONS.md (ce fichier)
```

## Sécurité

### Bonnes pratiques implémentées

✅ **Double vérification** : Les permissions sont vérifiées côté client (UI) ET côté serveur
✅ **Stockage sécurisé** : Les permissions sont chargées une fois en session, pas de requêtes répétées
✅ **Logs d'audit** : Toutes les tentatives d'accès non autorisées sont enregistrées
✅ **Protection des URLs** : Accès direct par URL bloqué si permission manquante
✅ **Granularité maximale** : Contrôle au niveau de chaque action

### Points de contrôle

1. **`layout.php`** : Vérification READ avant affichage de toute page
2. **Contrôleurs** : Vérification CREATE/UPDATE/DELETE avant actions
3. **Vues** : Masquage des boutons selon permissions
4. **AJAX** : Vérification des permissions avant traitement

## Tests

### Scénarios de test recommandés

1. **Test utilisateur en lecture seule**
   - Créer un groupe avec uniquement READ sur une fonctionnalité
   - Vérifier que les boutons Ajouter/Modifier/Supprimer sont masqués
   - Essayer d'accéder directement à une URL de modification
   - Vérifier le blocage et le message d'erreur

2. **Test utilisateur contributeur**
   - Créer un groupe avec READ et CREATE
   - Vérifier que le bouton Ajouter est visible
   - Vérifier que les boutons Modifier/Supprimer sont masqués
   - Tester la création d'un élément

3. **Test utilisateur complet**
   - Créer un groupe avec toutes les permissions
   - Vérifier que tous les boutons sont visibles
   - Tester toutes les opérations CRUD

4. **Test de sécurité**
   - Tenter d'accéder à une page sans permission READ
   - Tenter de modifier sans permission UPDATE
   - Vérifier les logs d'audit

## Dépannage

### Problème : Tous les utilisateurs sont redirigés vers le dashboard

**Cause** : Les permissions n'ont pas été migrées ou ne sont pas chargées en session

**Solution** :
1. Vérifier que la migration a été exécutée : `SELECT COUNT(*) FROM permissions;`
2. Vérifier les logs PHP pour voir si les permissions se chargent
3. Se déconnecter et se reconnecter pour recharger les permissions

### Problème : Les checkboxes de permissions ne se cochent pas

**Cause** : Problème AJAX ou JavaScript

**Solution** :
1. Ouvrir la console du navigateur (F12)
2. Vérifier les erreurs JavaScript
3. Vérifier que les requêtes AJAX sont bien envoyées
4. Vérifier que `ParametreController::handlePermissionAjax()` fonctionne

### Problème : Boutons visibles mais action bloquée

**Cause** : Décalage entre permissions UI et permissions serveur

**Solution** :
1. Vider le cache du navigateur
2. Se déconnecter et se reconnecter
3. Vérifier que les permissions en base sont correctes

## Évolutions futures possibles

- [ ] Interface de copie de permissions d'un groupe à un autre
- [ ] Historique des changements de permissions
- [ ] Permissions au niveau utilisateur (en plus du groupe)
- [ ] Permissions temporaires avec date d'expiration
- [ ] API REST pour gérer les permissions
- [ ] Tests automatisés pour les permissions

## Support

Pour toute question ou problème :
1. Consultez d'abord [GUIDE_PERMISSIONS.md](GUIDE_PERMISSIONS.md)
2. Consultez [MIGRATION_GUIDE.md](MIGRATION_GUIDE.md)
3. Vérifiez les logs d'erreur PHP et MySQL
4. Créez une issue sur le dépôt GitHub

## Auteurs

Implémentation du système de permissions granulaires CRUD pour Check-Master-UFHB

## License

Ce système fait partie du projet Check-Master-UFHB

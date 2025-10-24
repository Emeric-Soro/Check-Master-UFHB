# Migration vers le système de permissions granulaires CRUD

Ce document explique comment migrer de l'ancien système de permissions binaire vers le nouveau système granulaire CRUD.

## Contexte

L'ancien système utilisait la table `rattacher` qui permettait uniquement d'autoriser ou d'interdire l'accès à une fonctionnalité complète. Le nouveau système utilise la table `permissions` qui permet de contrôler finement les actions CRUD (Consulter, Ajouter, Modifier, Supprimer) pour chaque fonctionnalité.

## Prérequis

- Accès à la base de données MySQL
- Sauvegarde de la base de données effectuée
- Connexion SSH ou accès phpMyAdmin
- Docker running (si utilisation de Docker)

## Étapes de migration

### 1. Sauvegarder la base de données

Avant toute migration, créez une sauvegarde complète :

```bash
# Si vous utilisez Docker
docker exec -it <container_mysql> mysqldump -u root -p soutenance_manager > backup_before_migration_$(date +%Y%m%d).sql

# Ou avec mysql directement
mysqldump -u root -p soutenance_manager > backup_before_migration_$(date +%Y%m%d).sql
```

### 2. Exécuter le script de migration

#### Option A : Via Docker

```bash
# Copier le script dans le conteneur
docker cp migrations/001_granular_rbac_migration.sql <container_mysql>:/tmp/

# Exécuter le script
docker exec -it <container_mysql> mysql -u root -p soutenance_manager < /tmp/001_granular_rbac_migration.sql
```

#### Option B : Via mysql directement

```bash
mysql -u root -p soutenance_manager < migrations/001_granular_rbac_migration.sql
```

#### Option C : Via phpMyAdmin

1. Connectez-vous à phpMyAdmin
2. Sélectionnez la base de données `soutenance_manager`
3. Allez dans l'onglet "SQL"
4. Copiez-collez le contenu du fichier `migrations/001_granular_rbac_migration.sql`
5. Cliquez sur "Exécuter"

### 3. Vérifier la migration

Après l'exécution du script, vérifiez que :

#### 3.1 La table `action` contient les 4 actions CRUD

```sql
SELECT * FROM action ORDER BY id_action;
```

Résultat attendu :
```
+------------+--------------+
| id_action  | lib_action   |
+------------+--------------+
|          1 | Ajouter      |
|          3 | Modifier     |
|          6 | Supprimer    |
|          7 | Consulter    |
+------------+--------------+
```

#### 3.2 La table `permissions` a été créée

```sql
DESCRIBE permissions;
```

Résultat attendu :
```
+-----------------+------------+------+-----+-------------------+
| Field           | Type       | Null | Key | Default           |
+-----------------+------------+------+-----+-------------------+
| id_permission   | int        | NO   | PRI | NULL              |
| id_GU           | int        | NO   | MUL | NULL              |
| id_traitement   | int        | NO   |     | NULL              |
| id_action       | int        | NO   |     | NULL              |
| date_creation   | timestamp  | YES  |     | CURRENT_TIMESTAMP |
+-----------------+------------+------+-----+-------------------+
```

#### 3.3 Les permissions ont été migrées

```sql
-- Compter les anciennes attributions
SELECT COUNT(*) as nb_anciennes_attributions FROM rattacher;

-- Compter les nouvelles permissions (devrait être 4x le nombre d'attributions)
SELECT COUNT(*) as nb_nouvelles_permissions FROM permissions;

-- Voir un échantillon des permissions par groupe
SELECT 
    gu.lib_GU AS groupe,
    COUNT(DISTINCT p.id_traitement) AS nb_traitements,
    COUNT(*) AS nb_permissions_total
FROM permissions p
INNER JOIN groupe_utilisateur gu ON p.id_GU = gu.id_GU
GROUP BY gu.id_GU, gu.lib_GU
ORDER BY gu.lib_GU;
```

### 4. Tester le système

#### 4.1 Tester la connexion

1. Connectez-vous à l'application avec un compte test
2. Vérifiez que vous êtes bien redirigé vers le tableau de bord
3. Vérifiez qu'aucune erreur PHP n'apparaît dans les logs

```bash
# Voir les logs d'erreur PHP
tail -f /var/log/php/error.log
# Ou si Docker
docker logs -f <container_php>
```

#### 4.2 Tester l'interface de gestion des permissions

1. Connectez-vous avec un compte administrateur
2. Allez dans "Paramètres Généraux" → "Gestion des Attributions"
3. Sélectionnez un groupe d'utilisateurs
4. Vérifiez que la matrice CRUD s'affiche correctement
5. Essayez de cocher/décocher une permission
6. Vérifiez que la modification est enregistrée (rechargez la page)

#### 4.3 Tester les permissions en action

1. Créez un utilisateur de test avec des permissions limitées (par exemple, uniquement READ sur une fonctionnalité)
2. Connectez-vous avec ce compte
3. Vérifiez que :
   - Le menu affiche uniquement les fonctionnalités autorisées
   - Les boutons "Ajouter", "Modifier", "Supprimer" sont masqués si non autorisés
   - Les tentatives d'accès direct par URL sont bloquées

### 5. Vérifications de sécurité

#### 5.1 Tester le blocage d'accès direct

1. Connectez-vous avec un compte sans permission UPDATE sur "gestion_utilisateurs"
2. Essayez d'accéder directement à : `layout.php?page=gestion_utilisateurs&action=edit&id=1`
3. Vous devriez être redirigé avec un message d'erreur

#### 5.2 Vérifier les logs d'audit

```sql
SELECT * FROM audit_log 
WHERE table_name = 'permission' 
ORDER BY date_action DESC 
LIMIT 10;
```

### 6. Rollback (en cas de problème)

Si vous rencontrez des problèmes et devez annuler la migration :

```bash
# Restaurer la sauvegarde
mysql -u root -p soutenance_manager < backup_before_migration_YYYYMMDD.sql
```

Puis :

1. Supprimez la table `permissions` si elle existe
2. Restaurez les fichiers PHP modifiés depuis le commit précédent
3. Redémarrez l'application

### 7. Finalisation (après validation complète)

Une fois que tout fonctionne correctement pendant au moins une semaine :

#### 7.1 Renommer l'ancienne table (optionnel)

```sql
-- Renommer la table rattacher en backup
RENAME TABLE `rattacher` TO `rattacher_backup`;
```

#### 7.2 Nettoyer les fichiers de backup

```bash
# Supprimer le fichier de backup de la vue
rm ressources/views/parametres_generaux/gestion_attribution_backup.php
```

#### 7.3 Documenter les changements

Créez un fichier CHANGELOG.md pour documenter cette migration importante.

## Problèmes courants et solutions

### Problème : Erreur "Table 'permissions' doesn't exist"

**Solution** : Le script de migration n'a pas été exécuté correctement. Vérifiez les logs MySQL et réexécutez le script.

### Problème : Tous les utilisateurs sont redirigés vers le dashboard

**Solution** : Les permissions n'ont pas été chargées en session. Vérifiez que :
- La fonction `loadUserPermissions()` est appelée dans `AuthController`
- Le fichier `app/utils/permissions.php` est bien inclus
- Les permissions existent dans la table pour le groupe de l'utilisateur

### Problème : Les checkboxes de la matrice de permissions ne se cochent pas

**Solution** : Vérifiez que :
- Les requêtes AJAX atteignent bien le serveur (vérifier la console du navigateur)
- Le handler AJAX dans `ParametreController` fonctionne correctement
- Les permissions sont bien enregistrées dans la base de données

### Problème : Permission denied lors de l'accès à une page

**Solution** : Vérifiez que :
- L'utilisateur a au moins la permission READ sur la fonctionnalité
- Le `lib_traitement` utilisé dans `hasPermission()` correspond exactement à celui de la base de données
- Les permissions ont été rechargées après modification (déconnexion/reconnexion)

## Support

Pour toute question ou problème lors de la migration, consultez :
- Le fichier `GUIDE_PERMISSIONS.md` pour l'utilisation des permissions
- Les logs d'erreur PHP et MySQL
- Le code source des contrôleurs pour voir des exemples d'implémentation

## Checklist de migration

- [ ] Sauvegarde de la base de données effectuée
- [ ] Script de migration exécuté sans erreur
- [ ] Table `permissions` créée avec succès
- [ ] Action "Ajouter" (id=1) ajoutée dans la table action
- [ ] Permissions migrées depuis rattacher
- [ ] Test de connexion réussi
- [ ] Interface de gestion des permissions fonctionnelle
- [ ] Test avec utilisateur à permissions limitées
- [ ] Vérification des logs d'audit
- [ ] Documentation de la migration dans CHANGELOG.md
- [ ] Formation des administrateurs à la nouvelle interface

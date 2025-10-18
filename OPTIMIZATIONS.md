# Optimisations de Performance - Check Master UFHB

Ce document décrit les optimisations de performance mises en place dans l'application.

## Résumé des Optimisations

### 1. Pagination Côté Serveur

**Problème :** Les contrôleurs chargeaient toutes les données en mémoire avant de les paginer, causant une consommation excessive de mémoire et des temps de chargement lents.

**Solution :** Implémentation de la pagination au niveau de la base de données avec `LIMIT` et `OFFSET`.

#### Fichiers Modifiés

- `app/models/Etudiant.php`
  - Ajout du paramètre `$limit`, `$offset` et `$searchTerm` à `getAllEtudiants()`
  - Nouvelle méthode `countEtudiants($searchTerm)` pour compter les résultats
  
- `app/models/Utilisateur.php`
  - Ajout du paramètre `$limit`, `$offset` et `$searchTerm` à `getAllUtilisateurs()`
  - Nouvelle méthode `countUtilisateurs($searchTerm)` pour compter les résultats

- `app/controllers/GestionEtudiantController.php`
  - Utilisation de la pagination côté serveur
  - Utilisation de `PaginationHelper` pour calculer les paramètres

#### Exemple d'Utilisation

```php
// Avant (charge TOUS les étudiants)
$listeEtudiants = $this->etudiant->getAllEtudiants();
$currentPageItems = array_slice($listeEtudiants, $startIndex, $itemsPerPage);

// Après (charge seulement la page demandée)
$totalItems = $this->etudiant->countEtudiants($searchTerm);
$pagination = PaginationHelper::calculate($currentPage, $totalItems, $itemsPerPage);
$currentPageItems = $this->etudiant->getAllEtudiants($pagination['limit'], $pagination['offset'], $searchTerm);
```

### 2. Service de Cache

**Problème :** Absence de mise en cache pour les données peu volatiles, entraînant des requêtes SQL répétitives.

**Solution :** Création d'un service de cache basé sur le système de fichiers.

#### Fichier Créé

- `app/utils/CacheService.php`

#### Fonctionnalités

- **get($key)** : Récupère une valeur du cache
- **set($key, $value, $ttl)** : Stocke une valeur dans le cache avec TTL (Time To Live)
- **delete($key)** : Supprime une entrée du cache
- **clear()** : Vide tout le cache
- **remember($key, $callback, $ttl)** : Pattern "cache or execute"
- **cleanup()** : Nettoie les entrées expirées

#### Exemple d'Utilisation

```php
require_once __DIR__ . '/../utils/CacheService.php';

$cache = new CacheService();

// Pattern "cache or execute" - recommandé
$utilisateurs = $cache->remember('all_users', function() {
    return $this->utilisateur->getAllUtilisateurs();
}, 3600); // Cache pendant 1 heure

// Ou utilisation manuelle
$utilisateurs = $cache->get('all_users');
if ($utilisateurs === null) {
    $utilisateurs = $this->utilisateur->getAllUtilisateurs();
    $cache->set('all_users', $utilisateurs, 3600);
}

// Invalider le cache après une modification
$cache->delete('all_users');
```

#### Données Recommandées pour le Cache

- Listes de référence (types utilisateurs, groupes, niveaux d'accès)
- Statistiques de tableau de bord
- Résultats de calculs complexes
- Listes peu modifiées (enseignants, entreprises)

**Important :** Invalider le cache après chaque modification des données.

### 3. Helper de Pagination

**Problème :** Code de pagination dupliqué dans plusieurs contrôleurs.

**Solution :** Création d'une classe utilitaire pour centraliser la logique de pagination.

#### Fichier Créé

- `app/utils/PaginationHelper.php`

#### Méthodes

- **calculate($currentPage, $totalItems, $itemsPerPage)** : Calcule tous les paramètres de pagination
- **getPageNumbers($currentPage, $totalPages, $range)** : Génère les numéros de pages à afficher
- **buildUrl($page, $params)** : Construit l'URL pour une page donnée

#### Exemple d'Utilisation

```php
require_once __DIR__ . '/../utils/PaginationHelper.php';

$currentPage = $_GET['p'] ?? 1;
$itemsPerPage = 10;
$totalItems = $this->model->countItems();

$pagination = PaginationHelper::calculate($currentPage, $totalItems, $itemsPerPage);

// Utiliser $pagination['offset'] et $pagination['limit'] pour la requête SQL
$items = $this->model->getItems($pagination['limit'], $pagination['offset']);

// Passer à la vue
$GLOBALS['pagination'] = $pagination;
```

## Optimisations SQL Recommandées

### Indexes à Créer

Pour améliorer les performances des requêtes, créez les indexes suivants :

```sql
-- Table etudiants
CREATE INDEX idx_etudiants_nom_prenom ON etudiants(nom_etu, prenom_etu);
CREATE INDEX idx_etudiants_email ON etudiants(email_etu);

-- Table utilisateur
CREATE INDEX idx_utilisateur_login ON utilisateur(login_utilisateur);
CREATE INDEX idx_utilisateur_nom ON utilisateur(nom_utilisateur);

-- Table reclamations
CREATE INDEX idx_reclamations_statut ON reclamations(statut_reclamation);
CREATE INDEX idx_reclamations_num_etu ON reclamations(num_etu);

-- Table pister (audit)
CREATE INDEX idx_pister_date ON pister(date_creation);
CREATE INDEX idx_pister_action ON pister(action);
CREATE INDEX idx_pister_utilisateur ON pister(id_utilisateur);

-- Table notes
CREATE INDEX idx_notes_etudiant ON notes(num_etu);
CREATE INDEX idx_notes_ue ON notes(id_ue);

-- Table inscriptions
CREATE INDEX idx_inscriptions_etudiant ON inscriptions(id_etudiant);
CREATE INDEX idx_inscriptions_niveau ON inscriptions(id_niveau);
```

### Requêtes à Optimiser

1. **Éviter SELECT * quand ce n'est pas nécessaire**
   ```sql
   -- Mauvais
   SELECT * FROM etudiants;
   
   -- Bon
   SELECT num_etu, nom_etu, prenom_etu, email_etu FROM etudiants;
   ```

2. **Utiliser LIMIT pour les requêtes de liste**
   ```sql
   -- Toujours ajouter LIMIT pour les listes
   SELECT * FROM etudiants ORDER BY nom_etu LIMIT 10 OFFSET 0;
   ```

3. **Optimiser les JOINs**
   - Utiliser LEFT JOIN seulement quand nécessaire
   - Privilégier INNER JOIN quand possible
   - Mettre les conditions dans WHERE plutôt que JOIN quand approprié

## Monitoring des Performances

### Avec Xdebug (Développement)

1. **Installer Xdebug**
   ```bash
   # Déjà installé dans le conteneur Docker
   pecl install xdebug
   ```

2. **Configuration dans php.ini**
   ```ini
   [xdebug]
   xdebug.mode=profile
   xdebug.output_dir=/tmp/xdebug
   xdebug.profiler_enable=1
   xdebug.profiler_enable_trigger=1
   ```

3. **Analyser les profils**
   - Utiliser Webgrind : https://github.com/jokkedk/webgrind
   - Ou KCachegrind : https://kcachegrind.github.io/

### Avec le Query Log MySQL

1. **Activer le slow query log**
   ```sql
   SET GLOBAL slow_query_log = 'ON';
   SET GLOBAL long_query_time = 2; -- requêtes > 2 secondes
   SET GLOBAL slow_query_log_file = '/var/log/mysql/slow-query.log';
   ```

2. **Analyser les requêtes lentes**
   ```bash
   mysqldumpslow -s t -t 10 /var/log/mysql/slow-query.log
   ```

### Métriques de Performance à Surveiller

- **Temps de chargement des pages** : < 2 secondes
- **Temps d'exécution des requêtes SQL** : < 100ms
- **Utilisation mémoire PHP** : < 128MB par requête
- **Nombre de requêtes SQL par page** : < 20

## Bonnes Pratiques

### 1. Utiliser la Pagination Partout

Toujours paginer les listes :
- Listes d'étudiants
- Listes d'utilisateurs
- Listes de réclamations
- Logs d'audit
- Historiques

### 2. Mettre en Cache les Données Statiques

Utiliser le cache pour :
- Types d'utilisateurs
- Groupes d'utilisateurs
- Niveaux d'accès
- Années académiques
- Semestres
- Entreprises (si peu de changements)

### 3. Invalider le Cache Intelligemment

```php
// Après une création/modification/suppression
$cache->delete('cache_key');
// Ou vider une catégorie entière si nécessaire
```

### 4. Optimiser les Requêtes N+1

```php
// Mauvais (N+1 queries)
$etudiants = $this->etudiant->getAllEtudiants();
foreach ($etudiants as $etudiant) {
    $etudiant->notes = $this->note->getNotesByEtudiant($etudiant->num_etu);
}

// Bon (2 queries avec JOIN ou IN)
$etudiants = $this->etudiant->getAllEtudiantsWithNotes();
```

### 5. Utiliser les Transactions

Pour les opérations multiples :
```php
try {
    $this->db->beginTransaction();
    
    // Opérations multiples
    $this->etudiant->create($data);
    $this->inscription->create($data);
    
    $this->db->commit();
} catch (Exception $e) {
    $this->db->rollBack();
    throw $e;
}
```

## Prochaines Étapes

1. **Implémenter la pagination dans tous les contrôleurs**
   - GestionUtilisateurController
   - GestionReclamationsController
   - Autres contrôleurs avec listes volumineuses

2. **Ajouter le cache pour les données de référence**
   - Types d'utilisateurs
   - Groupes
   - Niveaux d'accès

3. **Créer les indexes recommandés en production**

4. **Mettre en place un monitoring avec Xdebug ou Blackfire**

5. **Optimiser les requêtes SQL identifiées comme lentes**

6. **Implémenter un système de cache plus robuste (Redis/Memcached)**
   - Pour les environnements de production à forte charge

## Résultats Attendus

- ✅ Temps de chargement < 2 secondes pour les pages critiques
- ✅ Pagination fluide et rapide
- ✅ Réduction de la consommation mémoire
- ✅ Amélioration de l'expérience utilisateur
- ✅ Réduction de la charge serveur

## Notes Importantes

- Les fichiers de cache sont stockés dans le répertoire `cache/` et sont exclus de Git
- Le TTL par défaut du cache est de 1 heure (3600 secondes)
- Pensez à nettoyer périodiquement le cache avec `$cache->cleanup()`
- En production, considérez l'utilisation de Redis ou Memcached pour un cache distribué

# Guide de Migration du Système de Routage

## Vue d'ensemble

Le système Check-Master-UFHB migre d'un système de routage basé sur des paramètres de requête (`?page=...&action=...`) vers un système moderne utilisant AltoRouter avec des URLs propres.

## Architecture du Nouveau Système

### Fichiers Principaux

1. **`/index.php`** - Point d'entrée principal utilisant AltoRouter
2. **`/app/config/routes.php`** - Configuration centralisée de toutes les routes
3. **`/app/utils/RouterHelper.php`** - Helper pour générer des URLs et assurer la rétrocompatibilité
4. **`/.htaccess`** - Configuration Apache pour la réécriture d'URLs

### Structure des Routes

Les routes sont définies dans `/app/config/routes.php` au format:

```php
$router->map('METHOD', 'PATH', 'Controller#method', 'route_name');
```

Exemples:
```php
$router->map('GET', '/utilisateurs', 'GestionUtilisateurController#index', 'gestion_utilisateurs');
$router->map('POST', '/rapports/creer', 'GestionRapportController#traiterCreationRapport', 'rapports_creer_post');
$router->map('GET', '/etudiants/imprimer-recu/[i:id]', 'GestionEtudiantController#imprimerRecu', 'etudiants_imprimer_recu');
```

## Comparaison Ancien vs Nouveau Système

### Ancien Système (layout.php)
```
URL: layout.php?page=gestion_etudiants&action=ajouter_des_etudiants
Fichier: ressources/routes/gestionEtudiantRoutes.php
Contrôleur: Appelé directement depuis le fichier de route
```

### Nouveau Système (index.php + AltoRouter)
```
URL: /etudiants/ajouter
Fichier: app/config/routes.php
Contrôleur: GestionEtudiantController#index
```

## Utilisation de RouterHelper

### Génération d'URLs

```php
// Dans les contrôleurs
require_once __DIR__ . '/../utils/RouterHelper.php';

// Redirection simple
header('Location: ' . RouterHelper::route('gestion_etudiants'));

// Avec paramètres
header('Location: ' . RouterHelper::route('etudiants_imprimer_recu', ['id' => $id_inscription]));
```

### Dans les Vues

```php
<!-- Ancien système -->
<a href="?page=gestion_etudiants">Gestion des étudiants</a>

<!-- Nouveau système -->
<a href="<?= RouterHelper::route('gestion_etudiants') ?>">Gestion des étudiants</a>

<!-- Avec paramètres -->
<a href="<?= RouterHelper::route('etudiants_imprimer_recu', ['id' => $id]) ?>">Imprimer</a>
```

## Mapping des Routes

### Routes de Base

| Ancien | Nouveau | Nom de Route |
|--------|---------|--------------|
| `?page=dashboard` | `/dashboard` | `dashboard` |
| `?page=gestion_utilisateurs` | `/utilisateurs` | `gestion_utilisateurs` |
| `?page=gestion_etudiants` | `/etudiants` | `gestion_etudiants` |
| `?page=gestion_rapports` | `/rapports` | `gestion_rapports` |

### Routes avec Actions

| Ancien | Nouveau | Nom de Route |
|--------|---------|--------------|
| `?page=gestion_etudiants&action=ajouter_des_etudiants` | `/etudiants/ajouter` | `etudiants_ajouter` |
| `?page=gestion_rapports&action=creer_rapport` | `/rapports/creer` | `rapports_creer` |
| `?page=gestion_reclamations&action=soumettre_reclamation` | `/reclamations/soumettre` | `reclamations_soumettre` |

### Routes avec Paramètres

| Ancien | Nouveau | Nom de Route |
|--------|---------|--------------|
| `?page=gestion_scolarite&action=imprimer_recu&id=123` | `/scolarite/imprimer-recu/123` | `scolarite_imprimer_recu` |
| `?page=gestion_etudiants&modalAction=imprimer_recu&id_inscription=123` | `/etudiants/imprimer-recu/123` | `etudiants_imprimer_recu` |

### Paramètres Généraux

| Ancien | Nouveau | Nom de Route |
|--------|---------|--------------|
| `?page=parametres_generaux` | `/parametres` | `parametres_generaux` |
| `?page=parametres_generaux&action=annees_academiques` | `/parametres/annees-academiques` | `parametres_annees` |
| `?page=parametres_generaux&action=grades` | `/parametres/grades` | `parametres_grades` |
| `?page=parametres_generaux&action=specialites` | `/parametres/specialites` | `parametres_specialites` |

## Rétrocompatibilité

Le système supporte les deux formats pendant la période de migration:

1. **Nouveau système** via `/index.php` et AltoRouter
2. **Ancien système** via `/layout.php` (toujours fonctionnel)

Le `RouterHelper::fallbackUrl()` assure que les routes non encore migrées pointent vers `layout.php`.

## Migration Progressive

### Phase 1: Infrastructure (✅ Complété)
- [x] Créer `app/config/routes.php`
- [x] Créer `app/utils/RouterHelper.php`
- [x] Mettre à jour `index.php`
- [x] Mettre à jour `.htaccess`
- [x] Mettre à jour `menu.php`

### Phase 2: Migration des Contrôleurs (En cours)
- [ ] Identifier tous les `header('Location: ...')` dans les contrôleurs
- [ ] Remplacer par `RouterHelper::route()`
- [ ] Tester chaque contrôleur

### Phase 3: Migration des Vues
- [ ] Identifier tous les liens avec `?page=`
- [ ] Remplacer par `RouterHelper::route()`
- [ ] Tester chaque vue

### Phase 4: Finalisation
- [ ] Supprimer les fichiers de routes dans `ressources/routes/`
- [ ] Déprécier `layout.php`
- [ ] Mettre à jour la documentation

## Bonnes Pratiques

### 1. Toujours utiliser RouterHelper pour les URLs

```php
// ✅ BON
header('Location: ' . RouterHelper::route('gestion_etudiants'));

// ❌ MAUVAIS
header('Location: ?page=gestion_etudiants');
```

### 2. Nommer les routes de manière cohérente

```php
// Format: {module}_{action}
$router->map('GET', '/etudiants', 'GestionEtudiantController#index', 'gestion_etudiants');
$router->map('GET', '/etudiants/ajouter', 'GestionEtudiantController#ajouter', 'etudiants_ajouter');
```

### 3. Utiliser des paramètres typés dans les routes

```php
// [i:id] pour les entiers
$router->map('GET', '/etudiants/[i:id]', 'GestionEtudiantController#show', 'etudiants_show');

// [a:slug] pour les chaînes alphanumériques
$router->map('GET', '/cours/[a:code]', 'CoursController#show', 'cours_show');
```

## Dépannage

### Problème: 404 sur toutes les nouvelles routes
**Solution**: Vérifier que `.htaccess` est activé et que mod_rewrite est chargé dans Apache.

### Problème: Les anciennes URLs ne fonctionnent plus
**Solution**: Vérifier que `layout.php` est accessible et que le fallback dans `RouterHelper` est correct.

### Problème: RouterHelper non trouvé
**Solution**: S'assurer que `RouterHelper` est chargé dans `index.php` et disponible dans le contexte.

## Références

- Documentation AltoRouter: https://altorouter.com/
- Patterns de routes: https://altorouter.com/usage/mapping-routes.html

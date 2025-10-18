# Exemples d'Utilisation du Cache

## 1. Cache des Données de Référence

```php
require_once __DIR__ . '/../utils/CacheService.php';

class ExempleController {
    private $cache;
    
    public function __construct() {
        $this->cache = new CacheService();
    }
    
    public function index() {
        // Pattern "remember" - récupère du cache ou exécute la fonction
        $typesUtilisateurs = $this->cache->remember('types_utilisateurs', function() {
            return $this->typeUtilisateur->getAllTypeUtilisateur();
        }, 3600); // Cache pendant 1 heure
        
        $GLOBALS['types_utilisateur'] = $typesUtilisateurs;
    }
    
    public function ajouterType($data) {
        $this->typeUtilisateur->ajouter($data);
        // IMPORTANT: Invalider le cache après modification
        $this->cache->delete('types_utilisateurs');
    }
}
```

## 2. Cache des Statistiques Dashboard

```php
public function getDashboardStats() {
    // Cache pendant 5 minutes seulement
    $stats = $this->cache->remember('dashboard_stats', function() {
        return [
            'total_etudiants' => $this->etudiant->countEtudiants(),
            'total_candidatures' => $this->candidature->countAll(),
            'candidatures_en_attente' => $this->candidature->countByStatus('En attente')
        ];
    }, 300); // 5 minutes
    
    return $stats;
}
```

## 3. Invalider Plusieurs Caches

```php
public function modifierEtudiant($numEtu, $data) {
    $this->etudiant->modifier($numEtu, $data);
    
    // Invalider tous les caches liés
    $this->cache->delete('liste_etudiants');
    $this->cache->delete('count_etudiants');
    $this->cache->delete('etudiant_' . $numEtu);
    $this->cache->delete('dashboard_stats');
}
```

## Bonnes Pratiques

✅ **DO:**
- Cacher les données de référence (types, groupes, niveaux)
- Cacher les listes peu volatiles
- Invalider le cache après CREATE, UPDATE, DELETE
- Utiliser des TTL appropriés (court pour semi-volatile, long pour statique)

❌ **DON'T:**
- Ne pas cacher les données sensibles en clair
- Ne pas cacher les données en temps réel
- Ne pas oublier d'invalider le cache
- Ne pas abuser du cache

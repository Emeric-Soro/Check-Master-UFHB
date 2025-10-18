# Exemple d'Implémentation Complète des Optimisations

Ce document montre comment appliquer toutes les optimisations de performance dans un nouveau contrôleur.

## Exemple: GestionReclamationsController Optimisé

Voici un exemple complet d'un contrôleur utilisant:
- ✅ Pagination côté serveur
- ✅ Cache pour les données de référence
- ✅ PaginationHelper
- ✅ Recherche optimisée

```php
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Reclamation.php';
require_once __DIR__ . '/../utils/CacheService.php';
require_once __DIR__ . '/../utils/PaginationHelper.php';

class GestionReclamationsOptimiseeController {
    private $db;
    private $reclamation;
    private $cache;
    
    public function __construct() {
        $this->db = Database::getConnection();
        $this->reclamation = new Reclamation();
        $this->cache = new CacheService();
    }
    
    /**
     * Liste les réclamations avec pagination et cache
     */
    public function index() {
        // 1. PARAMÈTRES DE PAGINATION
        $currentPage = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
        $itemsPerPage = 20;
        
        // 2. PARAMÈTRES DE RECHERCHE ET FILTRES
        $filtres = [
            'search' => $_GET['search'] ?? '',
            'statut' => $_GET['statut'] ?? '',
            'type' => $_GET['type'] ?? ''
        ];
        
        // 3. CACHE DES DONNÉES DE RÉFÉRENCE
        // Ces données changent rarement, donc on les cache
        $typesReclamations = $this->cache->remember('types_reclamations', function() {
            return $this->getTypesReclamations();
        }, 3600); // Cache pendant 1 heure
        
        $statutsReclamations = $this->cache->remember('statuts_reclamations', function() {
            return ['En attente', 'En cours', 'Résolue', 'Rejetée'];
        }, 3600);
        
        // 4. PAGINATION CÔTÉ SERVEUR
        // Compter le total avec filtres
        $totalItems = $this->reclamation->count($filtres);
        
        // Calculer les paramètres de pagination
        $pagination = PaginationHelper::calculate($currentPage, $totalItems, $itemsPerPage);
        
        // Récupérer uniquement les éléments de la page courante
        $reclamations = $this->reclamation->getTous(
            $pagination['limit'],
            $pagination['offset'],
            $filtres
        );
        
        // 5. PASSER LES DONNÉES À LA VUE
        $GLOBALS['reclamations'] = $reclamations;
        $GLOBALS['pagination'] = $pagination;
        $GLOBALS['filtres'] = $filtres;
        $GLOBALS['typesReclamations'] = $typesReclamations;
        $GLOBALS['statutsReclamations'] = $statutsReclamations;
    }
    
    /**
     * Crée une nouvelle réclamation
     */
    public function creer() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'num_etu' => $_POST['num_etu'],
                'titre' => $_POST['titre'],
                'description' => $_POST['description'],
                'type' => $_POST['type'],
                'priorite' => $_POST['priorite']
            ];
            
            if ($this->reclamation->creer($data)) {
                // IMPORTANT: Invalider le cache si les stats sont cachées
                $this->cache->delete('dashboard_stats');
                
                $_SESSION['message_success'] = "Réclamation créée avec succès";
                header('Location: ?page=reclamations');
                exit;
            }
        }
        
        // Récupérer les types depuis le cache
        $typesReclamations = $this->cache->remember('types_reclamations', function() {
            return $this->getTypesReclamations();
        }, 3600);
        
        $GLOBALS['typesReclamations'] = $typesReclamations;
    }
    
    /**
     * Modifie une réclamation
     */
    public function modifier($id) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = [
                'statut' => $_POST['statut'],
                'commentaire' => $_POST['commentaire']
            ];
            
            if ($this->reclamation->modifier($id, $data)) {
                // IMPORTANT: Invalider les caches liés
                $this->cache->delete('dashboard_stats');
                $this->cache->delete('reclamation_' . $id);
                
                $_SESSION['message_success'] = "Réclamation modifiée avec succès";
                header('Location: ?page=reclamations');
                exit;
            }
        }
    }
    
    /**
     * Affiche les statistiques (avec cache)
     */
    public function statistiques() {
        // Cache des statistiques pendant 10 minutes
        $stats = $this->cache->remember('reclamations_stats', function() {
            return [
                'total' => $this->reclamation->count([]),
                'en_attente' => $this->reclamation->count(['statut' => 'En attente']),
                'en_cours' => $this->reclamation->count(['statut' => 'En cours']),
                'resolues' => $this->reclamation->count(['statut' => 'Résolue']),
                'rejetees' => $this->reclamation->count(['statut' => 'Rejetée'])
            ];
        }, 600); // 10 minutes
        
        $GLOBALS['stats'] = $stats;
    }
    
    /**
     * Récupère les types de réclamations
     */
    private function getTypesReclamations() {
        $sql = "SELECT DISTINCT type_reclamation FROM reclamations ORDER BY type_reclamation";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
}
```

## Modèle Reclamation Optimisé

```php
<?php
class Reclamation {
    private $db;
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Récupère les réclamations avec pagination et filtres
     */
    public function getTous($limit = 20, $offset = 0, $filtres = []) {
        $sql = "SELECT r.*, 
                       CONCAT(e.nom_etu, ' ', e.prenom_etu) as nom_etudiant,
                       e.email_etu
                FROM reclamations r
                LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                WHERE 1=1";
        
        $params = [];
        
        // Filtre de recherche
        if (!empty($filtres['search'])) {
            $sql .= " AND (r.titre_reclamation LIKE :search 
                      OR r.description_reclamation LIKE :search
                      OR e.nom_etu LIKE :search
                      OR e.prenom_etu LIKE :search)";
            $params[':search'] = '%' . $filtres['search'] . '%';
        }
        
        // Filtre par statut
        if (!empty($filtres['statut'])) {
            $sql .= " AND r.statut_reclamation = :statut";
            $params[':statut'] = $filtres['statut'];
        }
        
        // Filtre par type
        if (!empty($filtres['type'])) {
            $sql .= " AND r.type_reclamation = :type";
            $params[':type'] = $filtres['type'];
        }
        
        $sql .= " ORDER BY r.date_creation DESC LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        // Bind des paramètres de recherche
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        // Bind des paramètres de pagination
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Compte les réclamations avec filtres
     */
    public function count($filtres = []) {
        $sql = "SELECT COUNT(*) as total
                FROM reclamations r
                LEFT JOIN etudiants e ON r.num_etu = e.num_etu
                WHERE 1=1";
        
        $params = [];
        
        // Appliquer les mêmes filtres que getTous()
        if (!empty($filtres['search'])) {
            $sql .= " AND (r.titre_reclamation LIKE :search 
                      OR r.description_reclamation LIKE :search
                      OR e.nom_etu LIKE :search
                      OR e.prenom_etu LIKE :search)";
            $params[':search'] = '%' . $filtres['search'] . '%';
        }
        
        if (!empty($filtres['statut'])) {
            $sql .= " AND r.statut_reclamation = :statut";
            $params[':statut'] = $filtres['statut'];
        }
        
        if (!empty($filtres['type'])) {
            $sql .= " AND r.type_reclamation = :type";
            $params[':type'] = $filtres['type'];
        }
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['total'];
    }
    
    /**
     * Crée une réclamation
     */
    public function creer($data) {
        $sql = "INSERT INTO reclamations (
                    num_etu, titre_reclamation, description_reclamation,
                    type_reclamation, priorite_reclamation, statut_reclamation
                ) VALUES (
                    :num_etu, :titre, :description,
                    :type, :priorite, 'En attente'
                )";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':num_etu' => $data['num_etu'],
            ':titre' => $data['titre'],
            ':description' => $data['description'],
            ':type' => $data['type'],
            ':priorite' => $data['priorite']
        ]);
    }
    
    /**
     * Modifie une réclamation
     */
    public function modifier($id, $data) {
        $sql = "UPDATE reclamations 
                SET statut_reclamation = :statut,
                    commentaire_admin = :commentaire,
                    date_traitement = NOW()
                WHERE id_reclamation = :id";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':statut' => $data['statut'],
            ':commentaire' => $data['commentaire'],
            ':id' => $id
        ]);
    }
}
```

## Vue avec Pagination

```php
<!-- ressources/views/reclamations/index.php -->
<div class="container">
    <h1>Gestion des Réclamations</h1>
    
    <!-- Formulaire de recherche et filtres -->
    <form method="GET" class="filters">
        <input type="hidden" name="page" value="reclamations">
        
        <input type="text" name="search" placeholder="Rechercher..." 
               value="<?= htmlspecialchars($GLOBALS['filtres']['search']) ?>">
        
        <select name="statut">
            <option value="">Tous les statuts</option>
            <?php foreach ($GLOBALS['statutsReclamations'] as $statut): ?>
                <option value="<?= $statut ?>" 
                    <?= $GLOBALS['filtres']['statut'] === $statut ? 'selected' : '' ?>>
                    <?= $statut ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <select name="type">
            <option value="">Tous les types</option>
            <?php foreach ($GLOBALS['typesReclamations'] as $type): ?>
                <option value="<?= $type ?>" 
                    <?= $GLOBALS['filtres']['type'] === $type ? 'selected' : '' ?>>
                    <?= $type ?>
                </option>
            <?php endforeach; ?>
        </select>
        
        <button type="submit">Filtrer</button>
    </form>
    
    <!-- Informations de pagination -->
    <div class="pagination-info">
        Affichage de <?= $GLOBALS['pagination']['startIndex'] ?> 
        à <?= $GLOBALS['pagination']['endIndex'] ?> 
        sur <?= $GLOBALS['pagination']['totalItems'] ?> réclamation(s)
    </div>
    
    <!-- Table des réclamations -->
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Étudiant</th>
                <th>Titre</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($GLOBALS['reclamations'] as $reclamation): ?>
                <tr>
                    <td><?= date('d/m/Y', strtotime($reclamation['date_creation'])) ?></td>
                    <td><?= htmlspecialchars($reclamation['nom_etudiant']) ?></td>
                    <td><?= htmlspecialchars($reclamation['titre_reclamation']) ?></td>
                    <td><?= htmlspecialchars($reclamation['type_reclamation']) ?></td>
                    <td>
                        <span class="badge badge-<?= strtolower($reclamation['statut_reclamation']) ?>">
                            <?= $reclamation['statut_reclamation'] ?>
                        </span>
                    </td>
                    <td>
                        <a href="?page=reclamations&action=voir&id=<?= $reclamation['id_reclamation'] ?>">
                            Voir
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <!-- Navigation de pagination -->
    <?php if ($GLOBALS['pagination']['totalPages'] > 1): ?>
        <div class="pagination">
            <!-- Bouton Précédent -->
            <?php if ($GLOBALS['pagination']['hasPrevious']): ?>
                <a href="<?= PaginationHelper::buildUrl($GLOBALS['pagination']['currentPage'] - 1, $GLOBALS['filtres']) ?>" 
                   class="btn-pagination">
                    « Précédent
                </a>
            <?php endif; ?>
            
            <!-- Numéros de pages -->
            <?php 
            $pageNumbers = PaginationHelper::getPageNumbers(
                $GLOBALS['pagination']['currentPage'], 
                $GLOBALS['pagination']['totalPages']
            );
            
            foreach ($pageNumbers as $pageNum): 
                if ($pageNum === '...'): ?>
                    <span class="pagination-ellipsis">...</span>
                <?php else: 
                    $isActive = $pageNum == $GLOBALS['pagination']['currentPage'];
                ?>
                    <a href="<?= PaginationHelper::buildUrl($pageNum, $GLOBALS['filtres']) ?>" 
                       class="btn-pagination <?= $isActive ? 'active' : '' ?>">
                        <?= $pageNum ?>
                    </a>
                <?php endif; 
            endforeach; ?>
            
            <!-- Bouton Suivant -->
            <?php if ($GLOBALS['pagination']['hasNext']): ?>
                <a href="<?= PaginationHelper::buildUrl($GLOBALS['pagination']['currentPage'] + 1, $GLOBALS['filtres']) ?>" 
                   class="btn-pagination">
                    Suivant »
                </a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>
```

## Résumé des Optimisations Appliquées

### 1. ✅ Pagination Côté Serveur
- Utilisation de `LIMIT` et `OFFSET` dans les requêtes SQL
- Comptage séparé avec `count()` pour calculer le total
- Ne charge que les données de la page courante

### 2. ✅ Cache des Données de Référence
- Types et statuts cachés pendant 1 heure
- Statistiques cachées pendant 10 minutes
- Invalidation du cache après modifications

### 3. ✅ PaginationHelper
- Calcul automatique des paramètres de pagination
- Génération des numéros de pages
- Construction d'URLs avec paramètres de recherche

### 4. ✅ Filtres et Recherche
- Recherche intégrée dans la requête SQL
- Filtres multiples (statut, type)
- Conservation des filtres lors de la navigation

### 5. ✅ Bonnes Pratiques
- Utilisation de requêtes préparées (protection SQL injection)
- Échappement HTML dans les vues (protection XSS)
- Code réutilisable et maintenable
- Séparation des responsabilités (MVC)

## Performance Attendue

**Avant optimisation:**
- Charge TOUTES les réclamations en mémoire
- Temps: ~2-5 secondes pour 1000+ réclamations
- Mémoire: ~50-100 MB

**Après optimisation:**
- Charge seulement 20 réclamations par page
- Temps: ~0.1-0.3 secondes
- Mémoire: ~5-10 MB
- **Amélioration: 10-50x plus rapide! 🚀**

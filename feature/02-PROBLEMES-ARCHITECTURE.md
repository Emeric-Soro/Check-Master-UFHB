# 🏗️ Analyse des Problèmes d'Architecture - Check-Master UFHB

## 📌 Sommaire

1. [Vue d'ensemble de l'architecture actuelle](#vue-densemble)
2. [Problèmes structurels majeurs](#problèmes-structurels-majeurs)
3. [Problèmes de découplage](#problèmes-de-découplage)
4. [Recommandations d'amélioration](#recommandations-damélioration)

---

## 🔍 Vue d'ensemble

### Architecture actuelle
```
Check-Master-UFHB/
├── app/
│   ├── config/         # Configuration (database.php, email.php)
│   ├── controllers/    # 35 contrôleurs
│   ├── models/         # 37 modèles
│   └── utils/          # 6 services utilitaires
├── public/             # Point d'entrée + assets
├── ressources/
│   ├── routes/         # 30 fichiers de routes
│   ├── templates/      # Templates DOCX
│   ├── uploads/        # Fichiers uploadés
│   └── views/          # ~50 vues PHP
├── src/                # Namespace PSR-4 (sous-utilisé)
└── docs/               # Documentation
```

### Pattern utilisé
- **Architecture:** MVC personnalisé (non-standard)
- **Routage:** Include-based, manuel dans `layout.php`
- **Injection de dépendances:** Manuelle (pas de conteneur utilisé)
- **Templates:** PHP natif (Twig disponible mais non utilisé)

---

## 🔴 Problèmes Structurels Majeurs

### 1. Routage centralisé dans un fichier monolithique

**Fichier:** `public/layout.php` (535 lignes)

```php
// Le routage est un énorme switch/case
switch ($currentMenuSlug) {
    case 'parametres_generaux':
        include __DIR__ . '/../ressources/routes/parametreGenerauxRouteur.php';
        // ... logique de routage interne
        break;
    case 'gestion_reclamations':
        include __DIR__ . '/../ressources/routes/gestionReclamationsRouteur.php';
        // ... plus de logique
        break;
    // 20+ autres cases...
}
```

**Problèmes:**
- Fichier trop volumineux et difficile à maintenir
- Logique métier mélangée avec le routage
- Impossible de tester unitairement
- Violation du principe SRP (Single Responsibility Principle)

**Solution recommandée:**
```php
// Utiliser un routeur dédié
$router = new Router();

$router->group('/gestion', function($router) {
    $router->get('/reclamations', [GestionReclamationsController::class, 'index']);
    $router->post('/reclamations', [GestionReclamationsController::class, 'store']);
});

$router->dispatch($_SERVER['REQUEST_URI'], $_SERVER['REQUEST_METHOD']);
```

---

### 2. Fichiers de routes dispersés et incohérents

**30 fichiers de routes** avec conventions différentes:
- `gestionReclamationsRouteur.php` (suffixe "Routeur")
- `gestionUtilisateurRoutes.php` (suffixe "Routes")
- `candidatureSoutenanceRoutes.php` (mixte)

**Exemple de fichier route problématique:**
```php
// ressources/routes/gestionUtilisateurRoutes.php
// Inclut les dépendances + instancie le contrôleur + appelle les méthodes
// Tout dans un seul fichier sans structure

require_once __DIR__ . '/../../app/controllers/GestionUtilisateurController.php';
$controller = new GestionUtilisateurController(Database::getConnection());

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            // ...
        }
    }
}
```

---

### 3. Absence d'injection de dépendances

**Problème actuel:** Chaque contrôleur crée ses propres dépendances

```php
// app/controllers/DashboardController.php
public function __construct() {
    $this->baseViewPath = __DIR__ . '/../../ressources/views/';
    $this->utilisateur = new Utilisateur(Database::getConnection());
    $this->etudiant = new Etudiant(Database::getConnection());
    $this->enseignant = new Enseignant(Database::getConnection());
    // Création manuelle de 5+ dépendances
}
```

**Problèmes:**
- Couplage fort entre les classes
- Difficile à tester (pas de mocks)
- Duplication de code
- Violation du principe DIP

**Solution avec PHP-DI (déjà en dépendance):**
```php
// config/container.php
use DI\ContainerBuilder;

$containerBuilder = new ContainerBuilder();
$containerBuilder->addDefinitions([
    PDO::class => function() {
        return Database::getConnection();
    },
    Utilisateur::class => \DI\autowire(),
    DashboardController::class => \DI\autowire(),
]);

return $containerBuilder->build();
```

---

### 4. Utilisation de $GLOBALS pour passer des données

**Fichier:** `app/controllers/DashboardController.php`
```php
private function getGlobalStats() {
    $GLOBALS['total_etudiants'] = count($this->etudiant->getAllEtudiants());
    $GLOBALS['etudiants_actifs'] = count($this->utilisateur->getEtudiantActif());
    // 15+ variables globales...
}
```

**Problèmes:**
- Anti-pattern majeur
- Risque de collision de noms
- Impossible à tracer et déboguer
- Couplage implicite entre contrôleur et vue

**Solution:**
```php
class DashboardController {
    public function index(): array {
        return [
            'total_etudiants' => count($this->etudiant->getAllEtudiants()),
            'etudiants_actifs' => count($this->utilisateur->getEtudiantActif()),
            // ...
        ];
    }
}

// Dans la vue ou le routeur
$data = $controller->index();
extract($data); // Si nécessaire
```

---

### 5. Vues PHP mixtes avec logique métier

**Exemple:** Les vues contiennent souvent de la logique complexe

```php
// ressources/views/gestion_etudiants_content.php
<?php
// Logique métier dans la vue
$etudiants = $controller->getAllEtudiants();
$filtered = array_filter($etudiants, function($e) {
    return $e->statut === 'actif';
});
// + HTML mélangé
?>
```

**Solution:** Utiliser Twig (déjà installé)
```twig
{# templates/etudiants/index.html.twig #}
{% for etudiant in etudiants|filter(e => e.statut == 'actif') %}
    <tr>
        <td>{{ etudiant.nom }}</td>
        <td>{{ etudiant.prenom }}</td>
    </tr>
{% endfor %}
```

---

### 6. Namespace PSR-4 sous-utilisé

**composer.json définit:**
```json
"autoload": {
    "psr-4": {
        "App\\": "src/"
    }
}
```

**Mais le dossier `src/` ne contient qu'un fichier CSS!**

**Solution:** Migrer progressivement vers le namespace `App\`
```php
// src/Controller/DashboardController.php
namespace App\Controller;

use App\Model\Utilisateur;
use App\Model\Etudiant;

class DashboardController {
    public function __construct(
        private Utilisateur $utilisateur,
        private Etudiant $etudiant
    ) {}
}
```

---

### 7. Duplication massive de code SQL

**Fichier:** `app/models/Utilisateur.php` - 600+ lignes

Les requêtes SQL sont répétées avec de légères variations:

```php
public function getAllUtilisateursActifs() {
    $query = "SELECT u.id_utilisateur, u.nom_utilisateur, ...
              FROM utilisateur u
              LEFT JOIN type_utilisateur t ON u.id_type_utilisateur = t.id_type_utilisateur
              LEFT JOIN groupe_utilisateur g ON u.id_GU = g.id_GU
              LEFT JOIN niveau_acces_donnees n ON u.id_niv_acces_donnee = n.id_niveau_acces_donnees
              WHERE u.statut_utilisateur = 'Actif'
              ORDER BY u.nom_utilisateur";
    // ...
}

public function getAllUtilisateursInactifs() {
    // MÊME requête avec WHERE ... = 'Inactif'
}

public function getEnseignantActif() {
    // MÊME requête avec filtres différents
}
// + 10 méthodes similaires
```

**Solution avec Query Builder ou Repository Pattern:**
```php
class UtilisateurRepository {
    private function baseQuery(): QueryBuilder {
        return $this->qb
            ->select('u.*, t.lib_type_utilisateur, g.lib_GU, n.lib_niveau_acces_donnees')
            ->from('utilisateur', 'u')
            ->leftJoin('u', 'type_utilisateur', 't', 'u.id_type_utilisateur = t.id_type_utilisateur')
            ->leftJoin('u', 'groupe_utilisateur', 'g', 'u.id_GU = g.id_GU')
            ->leftJoin('u', 'niveau_acces_donnees', 'n', 'u.id_niv_acces_donnee = n.id_niveau_acces_donnees');
    }

    public function findByStatus(string $status): array {
        return $this->baseQuery()
            ->where('u.statut_utilisateur = :status')
            ->setParameter('status', $status)
            ->fetchAllAssociative();
    }
}
```

---

### 8. Configuration dispersée

**Problème:** Configuration dans plusieurs endroits:
- `app/config/database.php` - BD
- `app/config/email.php` - Email
- Constantes dans les contrôleurs
- Variables en dur dans le code

**Solution:** Fichier de configuration centralisé
```php
// config/config.php
return [
    'database' => [
        'host' => $_ENV['DB_HOST'],
        'name' => $_ENV['DB_NAME'],
        // ...
    ],
    'mail' => [
        'host' => $_ENV['MAIL_HOST'],
        // ...
    ],
    'app' => [
        'debug' => $_ENV['APP_DEBUG'] ?? false,
        'url' => $_ENV['APP_URL'],
    ],
];
```

---

## 📊 Problèmes de Découplage

### Couplage entre couches

```
┌─────────────────────────────────────────────┐
│                 Vue (PHP)                    │
│  ⚠️ Accès direct aux modèles                │
│  ⚠️ Logique métier                          │
└──────────────────┬──────────────────────────┘
                   │ $GLOBALS
                   ▼
┌─────────────────────────────────────────────┐
│              Contrôleur                      │
│  ⚠️ Création manuelle des dépendances       │
│  ⚠️ Appel direct à Database::getConnection │
└──────────────────┬──────────────────────────┘
                   │
                   ▼
┌─────────────────────────────────────────────┐
│                Modèle                        │
│  ⚠️ SQL en dur                              │
│  ⚠️ Couplage avec PDO                       │
└─────────────────────────────────────────────┘
```

### Architecture cible recommandée

```
┌─────────────────────────────────────────────┐
│              Vue (Twig)                      │
│  ✅ Uniquement présentation                 │
└──────────────────┬──────────────────────────┘
                   │ DTO/ViewModel
                   ▼
┌─────────────────────────────────────────────┐
│              Contrôleur                      │
│  ✅ Injection de dépendances                │
│  ✅ Coordination uniquement                 │
└──────────────────┬──────────────────────────┘
                   │ Interface
                   ▼
┌─────────────────────────────────────────────┐
│               Service                        │
│  ✅ Logique métier                          │
└──────────────────┬──────────────────────────┘
                   │ Interface
                   ▼
┌─────────────────────────────────────────────┐
│             Repository                       │
│  ✅ Accès données abstrait                  │
└─────────────────────────────────────────────┘
```

---

## 📋 Recommandations d'Amélioration

### Phase 1 - Quick Wins (1-2 semaines)
1. [ ] Remplacer `$GLOBALS` par des retours de méthodes
2. [ ] Centraliser la configuration
3. [ ] Standardiser les noms de fichiers de routes

### Phase 2 - Refactoring (2-4 semaines)
4. [ ] Implémenter le conteneur d'injection PHP-DI
5. [ ] Migrer les classes vers le namespace `App\`
6. [ ] Créer un routeur centralisé

### Phase 3 - Architecture (1-2 mois)
7. [ ] Migrer les vues vers Twig
8. [ ] Implémenter le pattern Repository
9. [ ] Créer une couche Service
10. [ ] Ajouter des tests unitaires

---

## 📚 Ressources

- [Clean Architecture PHP](https://github.com/php-cleanarchitecture)
- [PHP-DI Documentation](https://php-di.org/doc/)
- [Twig Documentation](https://twig.symfony.com/doc/)
- [PSR-4 Autoloading](https://www.php-fig.org/psr/psr-4/)

---

*Analyse d'architecture réalisée le: 31 décembre 2024*

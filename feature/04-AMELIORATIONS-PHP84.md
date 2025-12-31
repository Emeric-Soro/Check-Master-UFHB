# 🚀 Améliorations PHP 8.4 - Check-Master UFHB

## 📌 Sommaire

1. [Nouvelles fonctionnalités PHP 8.4](#nouvelles-fonctionnalités-php-84)
2. [Fonctionnalités PHP 8.0-8.3 non utilisées](#fonctionnalités-php-80-83-non-utilisées)
3. [Exemples de migration](#exemples-de-migration)
4. [Plan de migration](#plan-de-migration)

---

## 🆕 Nouvelles Fonctionnalités PHP 8.4

### 1. Property Hooks (PHP 8.4)

**Avant (code actuel):**
```php
// app/models/Enseignant.php
class Enseignant {
    private $nom_enseignant;
    
    public function getNomEnseignant() { return $this->nom_enseignant; }
    public function setNomEnseignant($nom) { $this->nom_enseignant = $nom; }
}
```

**Après avec Property Hooks:**
```php
class Enseignant {
    public string $nom_enseignant {
        get => $this->nom_enseignant;
        set => $this->nom_enseignant = trim($value);
    }
}

// Ou avec validation
class Etudiant {
    public string $email {
        get => $this->email;
        set {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new InvalidArgumentException('Email invalide');
            }
            $this->email = strtolower($value);
        }
    }
}
```

---

### 2. Asymmetric Visibility (PHP 8.4)

**Cas d'usage:** Propriété lisible publiquement mais modifiable uniquement en privé

**Avant:**
```php
// app/models/Etudiant.php
class Etudiant {
    private int $id;
    
    public function getId(): int {
        return $this->id;
    }
    // Pas de setter public
}
```

**Après:**
```php
class Etudiant {
    public private(set) int $id;
    public protected(set) string $nom;
}

// Usage
$etudiant = new Etudiant();
echo $etudiant->id;        // ✅ Lecture OK
$etudiant->id = 5;         // ❌ Error: Cannot modify private(set)
```

---

### 3. new ClassName()->method() (PHP 8.4)

**Avant:**
```php
// app/controllers/DashboardController.php
$utilisateur = new Utilisateur(Database::getConnection());
$total = $utilisateur->getAllUtilisateurs();
```

**Après (chaînage direct):**
```php
$total = (new Utilisateur(Database::getConnection()))->getAllUtilisateurs();
```

---

### 4. Array Find Functions (PHP 8.4)

**Avant:**
```php
// app/controllers/GestionReclamationsController.php
$reclamations = array_filter($reclamations, function($rec) {
    return $rec['num_etu'] == $_SESSION['num_etu'];
});

// Trouver le premier élément
$found = null;
foreach ($items as $item) {
    if ($item['status'] === 'pending') {
        $found = $item;
        break;
    }
}
```

**Après avec array_find():**
```php
// Trouver le premier élément
$found = array_find($items, fn($item) => $item['status'] === 'pending');

// Trouver la clé
$key = array_find_key($reclamations, fn($r) => $r['id'] === $targetId);

// Vérifier si un élément existe
$exists = array_any($items, fn($i) => $i['statut'] === 'actif');

// Vérifier si tous les éléments correspondent
$allValid = array_all($notes, fn($n) => $n['moyenne'] >= 10);
```

---

### 5. Lazy Objects (PHP 8.4)

**Cas d'usage:** Chargement différé des objets coûteux

```php
class DashboardController {
    private Utilisateur $utilisateur;
    private Etudiant $etudiant;
    
    public function __construct() {
        // Initialisation paresseuse - l'objet n'est créé que lors du premier accès
        $this->utilisateur = ReflectionClass::newLazyGhost(
            Utilisateur::class,
            function(Utilisateur $u) {
                $u->__construct(Database::getConnection());
            }
        );
    }
}
```

---

## 🔧 Fonctionnalités PHP 8.0-8.3 Non Utilisées

### 6. Constructor Property Promotion (PHP 8.0)

**Avant (code actuel):**
```php
// app/controllers/AuthController.php
class AuthController {
    private $db;
    private $enseignantModel;
    private $persAdminModel;
    private $etudiantModel;
    private $auditLog;

    public function __construct($db) {
        $this->db = $db;
        $this->enseignantModel = new Enseignant($db);
        $this->persAdminModel = new PersAdmin($db);
        $this->etudiantModel = new Etudiant($db);
        $this->auditLog = new AuditLog($db);
    }
}
```

**Après:**
```php
class AuthController {
    public function __construct(
        private readonly PDO $db,
        private readonly Enseignant $enseignantModel,
        private readonly PersAdmin $persAdminModel,
        private readonly Etudiant $etudiantModel,
        private readonly AuditLog $auditLog,
    ) {}
}
```

---

### 7. Named Arguments (PHP 8.0)

**Avant:**
```php
// Appel ambigu
$utilisateur->updatePassword($hashedPassword, $_SESSION['id_utilisateur']);
// Qui est qui?
```

**Après:**
```php
$utilisateur->updatePassword(
    id: $_SESSION['id_utilisateur'],
    newPassword: $hashedPassword
);
```

---

### 8. Match Expression (PHP 8.0)

**Avant:**
```php
// app/controllers/AuthController.php
if ($type_utilisateur !== 'Etudiant') {
    if ($type_utilisateur === 'Enseignant simple' || $type_utilisateur === 'Enseignant administratif') {
        // ...
    } else if ($type_utilisateur === 'Personnel administratif') {
        // ...
    }
}
```

**Après:**
```php
match($type_utilisateur) {
    'Enseignant simple', 'Enseignant administratif' => $this->loadEnseignantSession($user),
    'Personnel administratif' => $this->loadPersAdminSession($user),
    'Etudiant' => $this->loadEtudiantSession($user),
    default => null,
};
```

---

### 9. Null-safe Operator (PHP 8.0)

**Avant:**
```php
// app/models/Etudiant.php
if ($enseignant) {
    $_SESSION['specialite'] = $enseignant->lib_specialite;
}
```

**Après:**
```php
$_SESSION['specialite'] = $enseignant?->lib_specialite ?? 'Non définie';
```

---

### 10. Union Types (PHP 8.0)

**Avant:**
```php
/**
 * @param int|string $id
 * @return array|null
 */
public function getEtudiantById($id) { ... }
```

**Après:**
```php
public function getEtudiantById(int|string $id): ?array { ... }
```

---

### 11. Enums (PHP 8.1)

**Avant (code actuel):**
```php
// Statuts utilisés comme chaînes magiques
if ($user['statut_utilisateur'] == 'Actif') { ... }
if ($reclamation['statut'] === 'En attente') { ... }
```

**Après:**
```php
enum StatutUtilisateur: string {
    case ACTIF = 'Actif';
    case INACTIF = 'Inactif';
    case SUSPENDU = 'Suspendu';
    
    public function label(): string {
        return match($this) {
            self::ACTIF => '🟢 Actif',
            self::INACTIF => '🔴 Inactif',
            self::SUSPENDU => '🟡 Suspendu',
        };
    }
}

enum StatutReclamation: string {
    case EN_ATTENTE = 'En attente';
    case EN_COURS = 'En cours';
    case RESOLUE = 'Résolue';
    case REJETEE = 'Rejetée';
}

enum TypeUtilisateur: string {
    case ETUDIANT = 'Etudiant';
    case ENSEIGNANT_SIMPLE = 'Enseignant simple';
    case ENSEIGNANT_ADMIN = 'Enseignant administratif';
    case PERSONNEL_ADMIN = 'Personnel administratif';
}

// Usage
public function login(): bool {
    if ($user->statut !== StatutUtilisateur::ACTIF) {
        return false;
    }
}
```

---

### 12. Readonly Properties (PHP 8.1)

**Application:**
```php
class Etudiant {
    public function __construct(
        public readonly string $num_etu,
        public readonly string $nom_etu,
        public readonly string $prenom_etu,
        public readonly string $email_etu,
    ) {}
}
```

---

### 13. First-class Callable Syntax (PHP 8.1)

**Avant:**
```php
$callback = [$this->reclamationModel, 'getParId'];
```

**Après:**
```php
$callback = $this->reclamationModel->getParId(...);
```

---

### 14. Readonly Classes (PHP 8.2)

**Application pour les Value Objects:**
```php
readonly class NoteResult {
    public function __construct(
        public float $moyenne,
        public int $total_unites,
        public int $unites_validees,
        public bool $resultats_disponibles,
        public string $message,
    ) {}
}
```

---

### 15. Typed Class Constants (PHP 8.3)

**Avant:**
```php
class NiveauEtude {
    const MASTER_2 = 9;  // Type non garanti
}
```

**Après:**
```php
class NiveauEtude {
    public const int MASTER_2 = 9;
    public const int CREDITS_SEMESTRE = 30;
    public const string LABEL_M2 = 'Master 2';
}
```

---

### 16. json_validate() (PHP 8.3)

**Avant:**
```php
// Valider JSON en essayant de le décoder
$data = json_decode($json);
if (json_last_error() !== JSON_ERROR_NONE) {
    throw new Exception('JSON invalide');
}
```

**Après:**
```php
if (!json_validate($json)) {
    throw new JsonException('JSON invalide');
}
$data = json_decode($json, true);
```

---

### 17. Override Attribute (PHP 8.3)

```php
class BaseController {
    protected function render(string $view, array $data = []): void { ... }
}

class DashboardController extends BaseController {
    #[\Override]
    protected function render(string $view, array $data = []): void {
        // Si la méthode parente change de signature, erreur à la compilation
        parent::render($view, $data);
    }
}
```

---

## 📝 Exemples de Migration Complets

### Migration du Modèle Utilisateur

```php
<?php

declare(strict_types=1);

namespace App\Model;

use App\Enum\StatutUtilisateur;
use App\Enum\TypeUtilisateur;
use PDO;

readonly class UtilisateurDTO {
    public function __construct(
        public int $id,
        public string $nom,
        public string $login,
        public StatutUtilisateur $statut,
        public TypeUtilisateur $type,
    ) {}
}

class UtilisateurRepository {
    public function __construct(
        private readonly PDO $db
    ) {}
    
    public function findById(int $id): ?UtilisateurDTO {
        $stmt = $this->db->prepare('SELECT * FROM utilisateur WHERE id_utilisateur = :id');
        $stmt->execute(id: $id);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $row 
            ? new UtilisateurDTO(
                id: $row['id_utilisateur'],
                nom: $row['nom_utilisateur'],
                login: $row['login_utilisateur'],
                statut: StatutUtilisateur::from($row['statut_utilisateur']),
                type: TypeUtilisateur::from($row['lib_type_utilisateur'] ?? 'Etudiant'),
            )
            : null;
    }
    
    public function findByStatut(StatutUtilisateur $statut): array {
        return array_map(
            fn(array $row) => new UtilisateurDTO(...$row),
            $this->query(statut: $statut)
        );
    }
}
```

---

### Migration du Contrôleur Auth

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\TypeUtilisateur;
use App\Model\{UtilisateurRepository, EnseignantRepository, EtudiantRepository};
use App\Service\AuditService;

final class AuthController {
    public function __construct(
        private readonly UtilisateurRepository $utilisateurRepo,
        private readonly EnseignantRepository $enseignantRepo,
        private readonly EtudiantRepository $etudiantRepo,
        private readonly AuditService $audit,
    ) {}
    
    public function login(string $login, string $password): bool {
        $user = $this->utilisateurRepo->verifierConnexion($login, $password);
        
        if (!$user) {
            return false;
        }
        
        $this->initSession($user);
        
        match($user->type) {
            TypeUtilisateur::ENSEIGNANT_SIMPLE,
            TypeUtilisateur::ENSEIGNANT_ADMIN => $this->loadEnseignantData($user->login),
            TypeUtilisateur::PERSONNEL_ADMIN => $this->loadPersAdminData($user->login),
            TypeUtilisateur::ETUDIANT => $this->loadEtudiantData($user->login),
        };
        
        $this->audit->logConnexion(userId: $user->id, status: 'Succès');
        
        return true;
    }
}
```

---

## 📋 Plan de Migration

### Phase 1 - Quick Wins (1 semaine)
1. [ ] Ajouter `declare(strict_types=1)` à tous les fichiers
2. [ ] Utiliser les named arguments pour les appels ambigus
3. [ ] Remplacer les switch par des match expressions
4. [ ] Utiliser le null-safe operator

### Phase 2 - Typage (2 semaines)
5. [ ] Ajouter les types de retour à toutes les méthodes
6. [ ] Créer les Enums pour les statuts
7. [ ] Utiliser les Union Types où approprié
8. [ ] Ajouter les Typed Class Constants

### Phase 3 - Refactoring (3-4 semaines)
9. [ ] Constructor Property Promotion
10. [ ] Readonly properties et classes
11. [ ] Property Hooks pour la validation
12. [ ] Asymmetric Visibility

### Phase 4 - Modernisation (1 mois+)
13. [ ] Migrer vers les namespaces PSR-4
14. [ ] Implémenter les Value Objects readonly
15. [ ] Utiliser les Lazy Objects pour les services
16. [ ] Profiter des nouvelles fonctions array_*

---

## 🔧 Configuration Recommandée

```ini
; php.ini
declare_strict_types = On
error_reporting = E_ALL
display_errors = Off
log_errors = On
```

```json
// composer.json
{
    "require": {
        "php": "^8.4"
    },
    "config": {
        "platform": {
            "php": "8.4"
        }
    }
}
```

---

## 📚 Ressources

- [PHP 8.4 Release Notes](https://www.php.net/releases/8.4/)
- [Property Hooks RFC](https://wiki.php.net/rfc/property-hooks)
- [Asymmetric Visibility RFC](https://wiki.php.net/rfc/asymmetric-visibility-v2)
- [PHP 8.x Migration Guide](https://www.php.net/manual/en/migration84.php)

---

*Analyse PHP 8.4 réalisée le: 31 décembre 2024*

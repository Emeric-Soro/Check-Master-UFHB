# 📖 Bonnes Pratiques Recommandées - Check-Master UFHB

## 📌 Sommaire

1. [Standards de codage](#standards-de-codage)
2. [Sécurité](#sécurité)
3. [Architecture](#architecture)
4. [Tests](#tests)
5. [Documentation](#documentation)
6. [DevOps](#devops)
7. [Performance](#performance)

---

## 📝 Standards de Codage

### PSR-12 Extended Coding Style

#### Déclaration des fichiers

```php
<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\Utilisateur;
use App\Service\AuthService;
use PDO;

/**
 * Controller for authentication operations.
 */
final class AuthController
{
    // ...
}
```

#### Convention de nommage

| Élément | Convention | Exemple |
|---------|-----------|---------|
| Classes | PascalCase | `AuthController` |
| Méthodes | camelCase | `getUserById` |
| Variables | camelCase | `$userName` |
| Constantes | UPPER_SNAKE | `MAX_LOGIN_ATTEMPTS` |
| Interfaces | PascalCase + suffix | `UserRepositoryInterface` |
| Traits | PascalCase + suffix | `LoggableTrait` |

#### Règles de formatage

```php
// ✅ Correct
public function getUser(int $id): ?User
{
    if ($id <= 0) {
        throw new InvalidArgumentException('ID must be positive');
    }

    return $this->repository->find($id);
}

// ❌ Incorrect
public function getUser( int $id ) : ?User {
    if($id <= 0){throw new InvalidArgumentException('ID must be positive');}
    return $this->repository->find($id);
}
```

### Configuration PHP CS Fixer

```php
// .php-cs-fixer.php
<?php

$finder = PhpCsFixer\Finder::create()
    ->in(['app', 'src', 'ressources'])
    ->exclude(['vendor', 'node_modules']);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12' => true,
        'strict_param' => true,
        'array_syntax' => ['syntax' => 'short'],
        'declare_strict_types' => true,
        'ordered_imports' => ['sort_algorithm' => 'alpha'],
        'no_unused_imports' => true,
        'single_quote' => true,
    ])
    ->setFinder($finder);
```

---

## 🔐 Sécurité

### Checklist de sécurité

#### Authentification
- [ ] Utiliser `password_hash()` avec `PASSWORD_DEFAULT`
- [ ] Implémenter le rate limiting sur les connexions
- [ ] Régénérer l'ID de session après connexion
- [ ] Implémenter 2FA pour les comptes sensibles
- [ ] Expiration des sessions après inactivité

```php
// Exemple de configuration session sécurisée
session_set_cookie_params([
    'lifetime' => 3600,
    'path' => '/',
    'domain' => $_SERVER['HTTP_HOST'],
    'secure' => true,      // HTTPS uniquement
    'httponly' => true,    // Non accessible en JS
    'samesite' => 'Strict' // Protection CSRF
]);

// Après connexion réussie
session_regenerate_id(true);
```

#### Protection CSRF

```php
// Génération du token
class CsrfService
{
    public function generateToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public function validateToken(?string $token): bool
    {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
}

// Dans le formulaire
<input type="hidden" name="csrf_token" value="<?= $csrfService->generateToken() ?>">

// Dans le contrôleur
if (!$csrfService->validateToken($_POST['csrf_token'] ?? null)) {
    throw new SecurityException('Token CSRF invalide');
}
```

#### Validation des entrées

```php
// Utiliser Valitron (déjà en dépendance)
use Valitron\Validator;

class UserValidator
{
    public function validate(array $data): ValidationResult
    {
        $v = new Validator($data);
        
        $v->rule('required', ['email', 'password', 'nom']);
        $v->rule('email', 'email');
        $v->rule('lengthMin', 'password', 8);
        $v->rule('lengthMax', 'nom', 100);
        $v->rule('alpha', 'nom');
        
        $v->validate();
        
        return new ValidationResult($v->errors());
    }
}
```

#### Headers de sécurité

```php
// middleware/SecurityHeaders.php
class SecurityHeadersMiddleware
{
    public function handle(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        
        if (isset($_SERVER['HTTPS'])) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // CSP personnalisé
        header("Content-Security-Policy: default-src 'self'; " .
            "script-src 'self' cdn.tailwindcss.com cdnjs.cloudflare.com; " .
            "style-src 'self' 'unsafe-inline' fonts.googleapis.com cdnjs.cloudflare.com; " .
            "font-src 'self' fonts.gstatic.com; " .
            "img-src 'self' data:;"
        );
    }
}
```

---

## 🏗️ Architecture

### Structure de dossiers recommandée

```
Check-Master-UFHB/
├── src/                          # Code source principal (PSR-4)
│   ├── Controller/               # Contrôleurs
│   ├── Model/                    # Entités/DTOs
│   ├── Repository/               # Accès données
│   ├── Service/                  # Logique métier
│   ├── Middleware/               # Middlewares HTTP
│   ├── Validator/                # Validation des données
│   ├── Enum/                     # Énumérations PHP 8.1+
│   └── Exception/                # Exceptions personnalisées
├── config/                       # Configuration
│   ├── container.php             # Configuration DI
│   ├── routes.php                # Routes centralisées
│   └── config.php                # Configuration générale
├── templates/                    # Templates Twig
│   ├── layouts/
│   ├── partials/
│   └── pages/
├── public/                       # Point d'entrée web
│   ├── index.php                 # Front controller unique
│   ├── css/
│   ├── js/
│   └── images/
├── storage/                      # Fichiers générés
│   ├── cache/
│   ├── logs/
│   └── uploads/
├── tests/                        # Tests
│   ├── Unit/
│   └── Integration/
└── docker/                       # Configuration Docker
```

### Injection de dépendances

```php
// config/container.php
use DI\ContainerBuilder;
use function DI\autowire;
use function DI\get;

$containerBuilder = new ContainerBuilder();

$containerBuilder->addDefinitions([
    // Configuration
    'config' => require __DIR__ . '/config.php',
    
    // Database
    PDO::class => function ($c) {
        $config = $c->get('config')['database'];
        return new PDO(
            "mysql:host={$config['host']};dbname={$config['name']};charset=utf8mb4",
            $config['user'],
            $config['pass'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
    },
    
    // Repositories
    UtilisateurRepository::class => autowire(),
    EtudiantRepository::class => autowire(),
    
    // Services
    AuthService::class => autowire()
        ->constructorParameter('auditLog', get(AuditService::class)),
]);

return $containerBuilder->build();
```

### Pattern Repository

```php
// src/Repository/AbstractRepository.php
abstract class AbstractRepository
{
    public function __construct(
        protected readonly PDO $db
    ) {}
    
    abstract protected function getTableName(): string;
    abstract protected function hydrate(array $row): object;
    
    public function find(int $id): ?object
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->getTableName()} WHERE id = :id"
        );
        $stmt->execute(['id' => $id]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? $this->hydrate($row) : null;
    }
    
    public function findAll(): array
    {
        $stmt = $this->db->query("SELECT * FROM {$this->getTableName()}");
        return array_map(
            fn($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}

// src/Repository/UtilisateurRepository.php
class UtilisateurRepository extends AbstractRepository
{
    protected function getTableName(): string
    {
        return 'utilisateur';
    }
    
    protected function hydrate(array $row): Utilisateur
    {
        return new Utilisateur(
            id: $row['id_utilisateur'],
            nom: $row['nom_utilisateur'],
            login: $row['login_utilisateur'],
            statut: StatutUtilisateur::from($row['statut_utilisateur']),
        );
    }
    
    public function findByStatut(StatutUtilisateur $statut): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM utilisateur WHERE statut_utilisateur = :statut"
        );
        $stmt->execute(['statut' => $statut->value]);
        
        return array_map(
            fn($row) => $this->hydrate($row),
            $stmt->fetchAll(PDO::FETCH_ASSOC)
        );
    }
}
```

---

## 🧪 Tests

### Structure des tests

```
tests/
├── Unit/
│   ├── Model/
│   │   └── UtilisateurTest.php
│   ├── Service/
│   │   └── AuthServiceTest.php
│   └── Validator/
│       └── UserValidatorTest.php
├── Integration/
│   ├── Repository/
│   │   └── UtilisateurRepositoryTest.php
│   └── Controller/
│       └── AuthControllerTest.php
├── Fixtures/
│   └── UserFixture.php
└── bootstrap.php
```

### Exemple de test unitaire

```php
// tests/Unit/Service/AuthServiceTest.php
use PHPUnit\Framework\TestCase;

class AuthServiceTest extends TestCase
{
    private AuthService $authService;
    private MockObject $userRepository;
    private MockObject $auditService;
    
    protected function setUp(): void
    {
        $this->userRepository = $this->createMock(UtilisateurRepository::class);
        $this->auditService = $this->createMock(AuditService::class);
        
        $this->authService = new AuthService(
            $this->userRepository,
            $this->auditService
        );
    }
    
    public function testLoginWithValidCredentials(): void
    {
        $user = new Utilisateur(
            id: 1,
            nom: 'Test User',
            login: 'test@example.com',
            passwordHash: password_hash('password123', PASSWORD_DEFAULT),
            statut: StatutUtilisateur::ACTIF
        );
        
        $this->userRepository
            ->expects($this->once())
            ->method('findByLogin')
            ->with('test@example.com')
            ->willReturn($user);
            
        $this->auditService
            ->expects($this->once())
            ->method('logConnexion')
            ->with(1, 'Succès');
        
        $result = $this->authService->login('test@example.com', 'password123');
        
        $this->assertTrue($result);
    }
    
    public function testLoginWithInvalidPassword(): void
    {
        $user = new Utilisateur(
            id: 1,
            nom: 'Test User',
            login: 'test@example.com',
            passwordHash: password_hash('password123', PASSWORD_DEFAULT),
            statut: StatutUtilisateur::ACTIF
        );
        
        $this->userRepository
            ->method('findByLogin')
            ->willReturn($user);
        
        $result = $this->authService->login('test@example.com', 'wrongpassword');
        
        $this->assertFalse($result);
    }
    
    public function testLoginWithInactiveUser(): void
    {
        $user = new Utilisateur(
            id: 1,
            nom: 'Test User',
            login: 'test@example.com',
            passwordHash: password_hash('password123', PASSWORD_DEFAULT),
            statut: StatutUtilisateur::INACTIF
        );
        
        $this->userRepository
            ->method('findByLogin')
            ->willReturn($user);
        
        $result = $this->authService->login('test@example.com', 'password123');
        
        $this->assertFalse($result);
    }
}
```

### Configuration PHPUnit

```xml
<!-- phpunit.xml -->
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="tests/bootstrap.php"
         colors="true"
         strict="true">
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
    </testsuites>
    <coverage>
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <report>
            <html outputDirectory="coverage"/>
            <text outputFile="php://stdout"/>
        </report>
    </coverage>
</phpunit>
```

---

## 📚 Documentation

### Docblocks standards

```php
/**
 * Authenticate a user with email and password.
 *
 * @param string $email    The user's email address
 * @param string $password The plaintext password
 *
 * @return bool True if authentication successful
 *
 * @throws InvalidCredentialsException If credentials are invalid
 * @throws UserDisabledException If user account is disabled
 *
 * @example
 * ```php
 * $auth->login('user@example.com', 'password123');
 * ```
 */
public function login(string $email, string $password): bool
{
    // ...
}
```

### README structure

```markdown
# Check-Master UFHB

## Description
Système de gestion des soutenances pour la filière MIAGE de l'UFHB.

## Prérequis
- PHP >= 8.4
- MySQL >= 8.0
- Composer

## Installation
1. Cloner le dépôt
2. `composer install`
3. Copier `.env.example` vers `.env`
4. Configurer les variables d'environnement
5. `php artisan migrate`

## Tests
```bash
composer test
```

## Contribution
Voir [CONTRIBUTING.md](CONTRIBUTING.md)

## Licence
MIT
```

---

## 🚀 DevOps

### Configuration Git

```gitignore
# .gitignore
/vendor/
/node_modules/
/storage/logs/*
/storage/cache/*
/.env
/public/css/output.css
*.log
.DS_Store
.idea/
.vscode/
```

### GitHub Actions CI

```yaml
# .github/workflows/ci.yml
name: CI

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    
    steps:
      - uses: actions/checkout@v4
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.4'
          extensions: pdo, pdo_mysql
          coverage: xdebug
      
      - name: Install dependencies
        run: composer install --no-progress
      
      - name: Run PHPStan
        run: vendor/bin/phpstan analyse
      
      - name: Run PHP CS Fixer
        run: vendor/bin/php-cs-fixer fix --dry-run --diff
      
      - name: Run tests
        run: vendor/bin/phpunit --coverage-text
```

### Docker Compose pour développement

```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: docker/Dockerfile
    volumes:
      - .:/var/www/html
    ports:
      - "8080:80"
    depends_on:
      - db
    environment:
      - DB_HOST=db
      - DB_NAME=checkmaster
      - DB_USER=root
      - DB_PASS=secret

  db:
    image: mysql:8.0
    volumes:
      - mysql_data:/var/lib/mysql
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: checkmaster
    ports:
      - "3306:3306"

volumes:
  mysql_data:
```

---

## ⚡ Performance

### Optimisations recommandées

#### 1. Mise en cache des requêtes

```php
class CachedRepository
{
    public function __construct(
        private readonly UtilisateurRepository $repository,
        private readonly CacheInterface $cache
    ) {}
    
    public function findById(int $id): ?Utilisateur
    {
        $cacheKey = "user_{$id}";
        
        return $this->cache->get($cacheKey, function() use ($id) {
            return $this->repository->findById($id);
        });
    }
}
```

#### 2. Lazy loading des relations

```php
class Etudiant
{
    private ?array $notes = null;
    
    public function getNotes(): array
    {
        if ($this->notes === null) {
            $this->notes = $this->noteRepository->findByEtudiant($this->id);
        }
        return $this->notes;
    }
}
```

#### 3. Pagination des résultats

```php
class PaginatedResult
{
    public function __construct(
        public readonly array $items,
        public readonly int $currentPage,
        public readonly int $lastPage,
        public readonly int $total,
        public readonly int $perPage,
    ) {}
    
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage;
    }
}

class UtilisateurRepository
{
    public function paginate(int $page = 1, int $perPage = 20): PaginatedResult
    {
        $offset = ($page - 1) * $perPage;
        
        $total = $this->count();
        $items = $this->findWithLimit($perPage, $offset);
        
        return new PaginatedResult(
            items: $items,
            currentPage: $page,
            lastPage: (int) ceil($total / $perPage),
            total: $total,
            perPage: $perPage
        );
    }
}
```

#### 4. Optimisation des requêtes N+1

```php
// ❌ Problème N+1
foreach ($etudiants as $etudiant) {
    echo $etudiant->getNiveau()->getLabel(); // 1 requête par étudiant!
}

// ✅ Eager loading
public function findAllWithNiveau(): array
{
    $sql = "SELECT e.*, n.lib_niv_etude 
            FROM etudiants e 
            LEFT JOIN niveau_etude n ON e.id_niveau = n.id_niv_etude";
    // Une seule requête
}
```

---

## 📋 Checklist de Revue de Code

### Avant chaque commit
- [ ] Code formaté (php-cs-fixer)
- [ ] Pas d'erreurs PHPStan
- [ ] Tests passent
- [ ] Pas de TODO non résolu
- [ ] Pas de code commenté
- [ ] Documentation à jour

### Avant chaque merge
- [ ] Review par un pair
- [ ] Tests d'intégration passent
- [ ] Pas de régressions
- [ ] Changelog mis à jour
- [ ] Migration testée

---

*Bonnes pratiques définies le: 31 décembre 2024*

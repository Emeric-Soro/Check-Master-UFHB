# 🔐 Analyse des Problèmes de Sécurité - Check-Master UFHB

## 📌 Sommaire

1. [Vulnérabilités Critiques](#vulnérabilités-critiques)
2. [Vulnérabilités Moyennes](#vulnérabilités-moyennes)
3. [Vulnérabilités Faibles](#vulnérabilités-faibles)
4. [Recommandations de Correction](#recommandations-de-correction)

---

## 🔴 Vulnérabilités Critiques

### 1. Credentials en dur dans le code

**Fichier:** `app/config/database.php`
```php
private static $host = 'localhost';
private static $db = 'ufrmi1802974_2q2mpf';
private static $user = 'root';
private static $pass = '';
```

**Risque:** Exposition des identifiants de base de données
**Impact:** Accès non autorisé à la base de données

**Solution:**
```php
// Utiliser des variables d'environnement
private static function loadConfig(): array {
    return [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'db' => $_ENV['DB_NAME'] ?? throw new \RuntimeException('DB_NAME required'),
        'user' => $_ENV['DB_USER'] ?? throw new \RuntimeException('DB_USER required'),
        'pass' => $_ENV['DB_PASS'] ?? '',
    ];
}
```

---

### 2. Credentials Docker en dur

**Fichier:** `app/controllers/SauvegardeRestaurationController.php`
```php
public function getDbConfig() {
    return [
        'host' => 'db',
        'db'   => 'soutenance_manager',
        'user' => 'root',
        'pass' => 'password',  // ⚠️ MOT DE PASSE EN CLAIR
    ];
}
```

**Risque:** Mots de passe visibles dans le code source
**Impact:** Compromission complète de la base de données

---

### 3. Absence de validation CSRF sur certains formulaires

**Fichiers affectés:**
- `public/reset_password.php` - Formulaire de réinitialisation
- `ressources/views/gestion_reclamations/*.php`

**Problème:** Les formulaires POST ne vérifient pas systématiquement le token CSRF

**Code actuel (reset_password.php):**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'])) {
    // Pas de vérification CSRF
    $email = trim($_POST['email']);
```

**Solution:**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || 
        !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        throw new SecurityException('Token CSRF invalide');
    }
    // Traitement...
}
```

---

### 4. Injection SQL potentielle dans les paramètres non validés

**Fichier:** `app/models/Utilisateur.php`
```php
public function updatePassword($id, $newPassword) {
    $query = "UPDATE utilisateur SET mdp_utilisateur = :mdp WHERE id_utilisateur = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':mdp', $newPassword);
    $stmt->bindParam(':id', $id);
    return $stmt->execute();
}
```

**Problème:** L'ordre des paramètres est inversé dans l'appel (mdp, id vs id, mdp)

**Fichier:** `app/controllers/AuthController.php`
```php
// INCORRECT - paramètres inversés
if ($utilisateur->updatePassword($hashedPassword, $_SESSION['id_utilisateur'])) {
```

**Solution:** Corriger l'ordre des paramètres ou utiliser des paramètres nommés

---

### 5. Exposition des erreurs système

**Fichier:** `app/config/database.php`
```php
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
```

**Risque:** Fuite d'informations sensibles sur la configuration
**Impact:** Aide les attaquants à comprendre l'infrastructure

**Solution:**
```php
} catch (PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    throw new \RuntimeException("Erreur de connexion à la base de données");
}
```

---

### 6. Exécution de commandes shell non sécurisée

**Fichier:** `app/controllers/SauvegardeRestaurationController.php`
```php
$cmd = sprintf('docker exec -i %s mysqldump -h%s -u%s -p%s %s > %s 2>/dev/null',
    escapeshellarg($containerName),
    escapeshellarg($this->getDbConfig()['host']),
    // ... mot de passe en ligne de commande visible dans ps
```

**Risque:** Mot de passe visible dans la liste des processus
**Impact:** Capture possible du mot de passe

---

## 🟠 Vulnérabilités Moyennes

### 7. Session non sécurisée

**Problème:** Configuration session par défaut insuffisante

**Solution recommandée dans un fichier bootstrap:**
```php
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Si HTTPS
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.use_strict_mode', 1);
session_regenerate_id(true); // Après connexion
```

---

### 8. Upload de fichiers sans validation complète

**Fichier:** `app/controllers/ArchiveController.php`
```php
$allowedExtensions = ['csv', 'xlsx', 'xls'];
if (!in_array($fileExtension, $allowedExtensions)) {
    // Seulement vérification d'extension
```

**Problème:** Pas de vérification du type MIME réel
**Solution:**
```php
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowedMimes = [
    'text/csv',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
];
if (!in_array($mimeType, $allowedMimes)) {
    throw new SecurityException('Type de fichier non autorisé');
}
```

---

### 9. Génération de mots de passe faible

**Fichier:** `app/models/Utilisateur.php`
```php
function generateRandomPassword($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*()_+';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $chars[rand(0, strlen($chars) - 1)]; // ⚠️ rand() n'est pas cryptographiquement sûr
    }
    return $password;
}
```

**Solution:**
```php
function generateRandomPassword(int $length = 16): string {
    return bin2hex(random_bytes($length / 2));
    // Ou avec caractères spéciaux:
    $bytes = random_bytes($length);
    return base64_encode($bytes);
}
```

---

### 10. Absence de rate limiting

**Problème:** Pas de protection contre les attaques par force brute sur:
- Connexion (`public/login.php`)
- Réinitialisation de mot de passe (`public/reset_password.php`)

**Solution:** Implémenter un système de rate limiting
```php
class RateLimiter {
    public function isAllowed(string $key, int $maxAttempts = 5, int $decayMinutes = 15): bool {
        $attempts = $this->getAttempts($key);
        if ($attempts >= $maxAttempts) {
            return false;
        }
        $this->incrementAttempts($key, $decayMinutes);
        return true;
    }
}
```

---

### 11. Tokens de réinitialisation prévisibles

**Fichier:** `public/reset_password.php`
```php
function generateToken($length = 64) {
    return bin2hex(random_bytes($length / 2)); // ✅ Correct, mais...
}
```

**Amélioration:** Ajouter un délai d'expiration plus court et un hash du token en base
```php
// Stocker le hash, pas le token brut
$tokenHash = hash('sha256', $token);
$stmt = $db->prepare('INSERT INTO password_resets (email, token_hash, expires_at) VALUES (...)');
```

---

## 🟡 Vulnérabilités Faibles

### 12. Headers de sécurité manquants

**Problème:** Pas de headers de sécurité HTTP

**Solution (fichier .htaccess ou PHP):**
```php
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
header('Content-Security-Policy: default-src \'self\'');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

---

### 13. Information disclosure dans les logs

**Fichier:** `app/models/Etudiant.php`
```php
error_log("DEBUG - Étudiant $numEtu - Niveau: $id_niveau - Notes trouvées: " . count($debug_notes));
```

**Risque:** Données personnelles dans les logs
**Solution:** Supprimer les logs de debug en production

---

### 14. Path traversal potentiel

**Fichier:** `app/controllers/SauvegardeRestaurationController.php`
```php
$filename = basename($_POST['filename']); // ✅ basename() est utilisé
$filepath = $this->backupDir . $filename;
```

**Status:** Partiellement mitigé avec `basename()`, mais vérifier que le fichier est bien dans le dossier autorisé

---

## 📋 Recommandations de Correction

### Priorité 1 - Immédiat
1. [ ] Migrer les credentials vers des variables d'environnement
2. [ ] Implémenter la vérification CSRF sur tous les formulaires POST
3. [ ] Corriger l'inversion des paramètres dans `updatePassword()`
4. [ ] Sécuriser la génération de mots de passe avec `random_bytes()`

### Priorité 2 - Court terme
5. [ ] Ajouter les headers de sécurité HTTP
6. [ ] Implémenter le rate limiting sur les endpoints sensibles
7. [ ] Valider les types MIME pour les uploads
8. [ ] Configurer les sessions de manière sécurisée

### Priorité 3 - Moyen terme
9. [ ] Implémenter un système de logging sécurisé
10. [ ] Audit complet des requêtes SQL
11. [ ] Tests de pénétration automatisés
12. [ ] Formation sécurité pour l'équipe

---

## 📚 Ressources

- [OWASP Top 10](https://owasp.org/www-project-top-ten/)
- [PHP Security Best Practices](https://www.php.net/manual/en/security.php)
- [PDO Prepared Statements](https://www.php.net/manual/en/pdo.prepared-statements.php)

---

*Analyse de sécurité réalisée le: 31 décembre 2024*

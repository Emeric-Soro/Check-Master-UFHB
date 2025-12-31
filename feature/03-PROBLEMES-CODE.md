# 💻 Analyse des Problèmes de Code - Check-Master UFHB

## 📌 Sommaire

1. [Bugs et erreurs de logique](#bugs-et-erreurs-de-logique)
2. [Code smell et anti-patterns](#code-smell-et-anti-patterns)
3. [Problèmes de maintenabilité](#problèmes-de-maintenabilité)
4. [Violations des standards PSR](#violations-des-standards-psr)
5. [Recommandations](#recommandations)

---

## 🐛 Bugs et Erreurs de Logique

### 1. Inversion des paramètres dans updatePassword

**Fichier:** `app/models/Utilisateur.php` (ligne 219)
```php
public function updatePassword($id, $newPassword) {
    $query = "UPDATE utilisateur SET mdp_utilisateur = :mdp WHERE id_utilisateur = :id";
    $stmt = $this->db->prepare($query);
    $stmt->bindParam(':mdp', $newPassword);  // newPassword attend d'être 2ème param
    $stmt->bindParam(':id', $id);            // id attend d'être 1er param
    return $stmt->execute();
}
```

**Appel dans AuthController.php (ligne 182):**
```php
// ⚠️ INVERSÉ: Le hash est passé comme ID, l'ID comme hash!
if ($utilisateur->updatePassword($hashedPassword, $_SESSION['id_utilisateur'])) {
```

**Impact:** Le mot de passe n'est jamais correctement mis à jour!

**Correction:**
```php
// Option 1: Corriger l'appel
if ($utilisateur->updatePassword($_SESSION['id_utilisateur'], $hashedPassword)) {

// Option 2: Utiliser des paramètres nommés (PHP 8.0+)
if ($utilisateur->updatePassword(id: $_SESSION['id_utilisateur'], newPassword: $hashedPassword)) {
```

---

### 2. Vérification de null sur un tableau

**Fichier:** `app/controllers/DashboardController.php`
```php
$GLOBALS['etudiants_actifs'] = count($this->utilisateur->getEtudiantActif() ) ?? 0;
```

**Problème:** `count()` retourne toujours un entier, jamais `null`. L'opérateur `??` est inutile ici.

**Code corrigé:**
```php
$result = $this->utilisateur->getEtudiantActif();
$GLOBALS['etudiants_actifs'] = $result ? count($result) : 0;
```

---

### 3. Erreur de référence SQL

**Fichier:** `app/models/Utilisateur.php` (ligne 111)
```php
$query = "SELECT 
        g.lib_groupe,            // ⚠️ Colonne inexistante!
        f.lib_fonction,          // ⚠️ Pas de JOIN avec fonction
        ...
      FROM utilisateur u
      LEFT JOIN groupe_utilisateur g ON u.id_GU = g.id_GU
      // Manque: LEFT JOIN fonction f ON ...
```

**Problème:** Référence à `lib_groupe` au lieu de `lib_GU` et `lib_fonction` sans JOIN approprié

---

### 4. Gestion d'erreur incomplète

**Fichier:** `app/models/Note.php` (ligne 97)
```php
public function getSemestreByEtudiant($etudiantId) {
    $query = "SELECT id_niveau FROM inscriptions WHERE id_etudiant = ?";
    $stmt = $this->db->prepare($query);
    $stmt->execute([$etudiantId]);
    $niveauId = $stmt->fetch(PDO::FETCH_OBJ)->id_niveau; // ⚠️ Peut être false!
```

**Impact:** Fatal error si l'étudiant n'a pas d'inscription

**Correction:**
```php
$result = $stmt->fetch(PDO::FETCH_OBJ);
if (!$result) {
    return [];
}
$niveauId = $result->id_niveau;
```

---

### 5. Division par zéro potentielle

**Fichier:** `app/models/Note.php`
```php
$moyenne = (($moyMaj * $totalMaj) + ($moyMin * $totalMin)) / 30;
```

**Problème:** Division fixe par 30 sans vérification

**Fichier:** `app/models/Etudiant.php`
```php
$moyenne_semestre = ($moyenne_majeure + $moyenne_mineure) / 2;
```

**Amélioration recommandée:**
```php
$divisor = $totalMaj + $totalMin;
$moyenne = $divisor > 0 ? (($moyMaj * $totalMaj) + ($moyMin * $totalMin)) / $divisor : 0;
```

---

## 🔴 Code Smell et Anti-patterns

### 6. God Class - Utilisateur.php (600+ lignes)

**Problème:** La classe `Utilisateur` fait trop de choses:
- Authentification
- CRUD utilisateurs
- Gestion enseignants
- Gestion étudiants
- Gestion personnel
- Génération de mots de passe

**Solution:** Diviser en plusieurs classes
```
Utilisateur.php        → UtilisateurRepository.php
                       → AuthService.php
                       → PasswordService.php
                       → EnseignantRepository.php
                       → EtudiantRepository.php
```

---

### 7. Magic Numbers

**Fichier:** `app/models/Note.php`
```php
if ($niveauId != 9) {  // ⚠️ Qu'est-ce que 9?
    // ...
}

$moyenne = ($moy1 * 30 + $moy2 * 30) / 60;  // ⚠️ Pourquoi 30? 60?
```

**Solution:** Utiliser des constantes
```php
class NiveauEtude {
    public const MASTER_2 = 9;
    public const CREDITS_SEMESTRE = 30;
}

if ($niveauId !== NiveauEtude::MASTER_2) {
    // ...
}
```

---

### 8. Duplication de code massive

**Fichier:** `app/models/Utilisateur.php`

```php
public function getAllUtilisateursActifs() {
    $query = "SELECT u.id_utilisateur, u.nom_utilisateur, u.login_utilisateur, 
                u.statut_utilisateur,
                t.lib_type_utilisateur as role_utilisateur,
                g.lib_GU as gu,
                n.lib_niveau_acces_donnees as niveau_acces
          FROM utilisateur u
          LEFT JOIN type_utilisateur t ON u.id_type_utilisateur = t.id_type_utilisateur
          LEFT JOIN groupe_utilisateur g ON u.id_GU = g.id_GU
          LEFT JOIN niveau_acces_donnees n ON u.id_niv_acces_donnee = n.id_niveau_acces_donnees
          WHERE u.statut_utilisateur = 'Actif'
          ORDER BY u.nom_utilisateur";
    // ...
}

// Cette MÊME requête est copiée 15+ fois avec des variations mineures!
public function getAllUtilisateursInactifs() { ... }
public function getEnseignantActif() { ... }
public function getEnseignantInactif() { ... }
public function getEtudiantActif() { ... }
// etc.
```

**Solution - Pattern Query Builder:**
```php
class UtilisateurQueryBuilder {
    private array $conditions = [];
    private array $params = [];
    
    public function whereStatut(string $statut): self {
        $this->conditions[] = 'u.statut_utilisateur = :statut';
        $this->params['statut'] = $statut;
        return $this;
    }
    
    public function whereType(string $type): self {
        $this->conditions[] = 't.lib_type_utilisateur = :type';
        $this->params['type'] = $type;
        return $this;
    }
    
    public function execute(): array {
        $where = $this->conditions ? 'WHERE ' . implode(' AND ', $this->conditions) : '';
        // ...
    }
}
```

---

### 9. Catch générique sans action

**Fichier:** `app/controllers/SauvegardeRestaurationController.php`
```php
} catch (Exception $e) {
    return false;  // ⚠️ L'erreur est silencieusement ignorée
}
```

**Solution:**
```php
} catch (Exception $e) {
    $this->logger->error('Backup failed', [
        'exception' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    throw new BackupException('La sauvegarde a échoué', 0, $e);
}
```

---

### 10. Constructeur trop complexe

**Fichier:** `app/controllers/AuthController.php`
```php
public function __construct($db) {
    $this->db = $db;
    $this->enseignantModel = new Enseignant($db);
    $this->persAdminModel = new PersAdmin($db);
    $this->etudiantModel = new Etudiant($db);
    $this->auditLog = new AuditLog($db);
}
```

**Solution PHP 8.4 avec promotion de propriétés:**
```php
public function __construct(
    private readonly PDO $db,
    private readonly Enseignant $enseignantModel,
    private readonly PersAdmin $persAdminModel,
    private readonly Etudiant $etudiantModel,
    private readonly AuditLog $auditLog,
) {}
```

---

## ⚠️ Problèmes de Maintenabilité

### 11. Commentaires obsolètes ou inutiles

**Fichier:** `app/controllers/AuthController.php`
```php
// Si nécessaire pour d'autres opérations   // ⚠️ Commentaire vague et inutile
```

**Fichier:** `app/models/Etudiant.php`
```php
// Debug: Vérifier les notes de l'étudiant
error_log("DEBUG - Étudiant $numEtu...");  // ⚠️ Debug en production!
```

---

### 12. Logique conditionnelle profonde

**Fichier:** `app/controllers/AuthController.php`
```php
if ($infoUtilisateur) {
    // ...
    if ($type_utilisateur !== 'Etudiant') {
        if ($type_utilisateur === 'Enseignant simple' || $type_utilisateur === 'Enseignant administratif') {
            $enseignant = $this->enseignantModel->getEnseignantByLogin(...);
            if ($enseignant) {
                $_SESSION['specialite'] = $enseignant->lib_specialite;
                // 4 niveaux d'imbrication!
            }
        }
    }
}
```

**Solution - Early Return:**
```php
public function login(string $login, string $password): bool {
    $user = $this->utilisateur->verifierConnexion($login, $password);
    
    if (!$user) {
        return false;
    }
    
    $this->storeBaseSessionData($user);
    
    $type = $this->utilisateur->getLibelleTypeUtilisateur($user['id_utilisateur']);
    
    match($type) {
        'Enseignant simple', 'Enseignant administratif' => $this->loadEnseignantData($user),
        'Personnel administratif' => $this->loadPersAdminData($user),
        'Etudiant' => $this->loadEtudiantData($user),
        default => null
    };
    
    return true;
}
```

---

### 13. Typos et incohérences de nommage

```php
// Fichiers:
plannificaiton_soutenance_content.php  // ⚠️ Typo: "plannificaiton"
plannificationSoutenanceRoutes.php     // ⚠️ Typo: "plannification"

// Dans le code:
$_SESSION['specialite'] = $enseignant->lib_specialite;  // Français
$updateData['rapport']['statut_rapport'] = ...;          // Français
private string $gotenbergUrl;                            // Anglais
```

---

### 14. Méthodes trop longues

**Fichier:** `app/models/Etudiant.php` - `getNotesEtudiant()` (100+ lignes)

**Règle:** Une méthode devrait faire moins de 20 lignes

**Solution:** Extraire en sous-méthodes
```php
public function getNotesEtudiant(string $numEtu): array {
    $niveau = $this->getNiveauEtudiant($numEtu);
    if (!$niveau) {
        return $this->emptyNotesResponse('Niveau non trouvé');
    }
    
    $stats = $this->calculateUEStats($numEtu, $niveau);
    $moyenne = $this->calculateMoyenne($numEtu);
    $credits = $this->calculateCredits($numEtu, $niveau, $moyenne);
    
    return [
        'moyenne' => $moyenne,
        'total_unites' => $stats['total'],
        'unites_validees' => $credits,
        'resultats_disponibles' => true,
    ];
}
```

---

## 📐 Violations des Standards PSR

### PSR-1: Basic Coding Standard

```php
// ❌ Fichiers avec déclaration de classe ET code exécuté
// app/models/Utilisateur.php ne devrait contenir QUE la classe

// ❌ Nommage incohérent des méthodes
public function getEtudiantById()    // camelCase ✅
public function getAllListeEtudiants() // "Liste" redondant
```

### PSR-4: Autoloading

```php
// ❌ Namespace non utilisé
// app/models/Utilisateur.php
class Utilisateur { ... }  // Devrait être: namespace App\Model;

// composer.json définit App\ => src/ mais pas utilisé
```

### PSR-12: Extended Coding Style

```php
// ❌ Espaces incohérents
count($this->etudiant->getAllEtudiants() )  // Espace avant )
count( $this->enseignant->getAllEnseignants() )  // Espaces des deux côtés

// ❌ Accolades sur la même ligne (devrait être sur nouvelle ligne pour classes)
class Utilisateur {  // PSR-12: { sur nouvelle ligne
```

---

## 📋 Recommandations

### Priorité Haute
1. [ ] Corriger le bug `updatePassword()` immédiatement
2. [ ] Ajouter la gestion d'erreur pour les `fetch()` pouvant retourner false
3. [ ] Supprimer les logs de debug en production
4. [ ] Corriger les erreurs SQL

### Priorité Moyenne
5. [ ] Refactoriser `Utilisateur.php` en plusieurs classes
6. [ ] Remplacer les magic numbers par des constantes
7. [ ] Réduire la duplication avec un Query Builder
8. [ ] Simplifier les conditions imbriquées

### Priorité Basse
9. [ ] Corriger les typos dans les noms de fichiers
10. [ ] Uniformiser le style de code selon PSR-12
11. [ ] Ajouter les types PHP stricts
12. [ ] Supprimer les commentaires obsolètes

---

## 🛠️ Outils Recommandés

- **PHPStan** - Analyse statique niveau 8
- **PHP CS Fixer** - Correction automatique PSR-12
- **Rector** - Refactoring automatisé PHP 8.4
- **PHPUnit** - Tests unitaires

---

*Analyse de code réalisée le: 31 décembre 2024*

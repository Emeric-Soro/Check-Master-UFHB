# Migration de la table entreprises

## Date: 27 février 2026

## Objectif

Adapter le code pour la nouvelle structure de la table `entreprises` qui a été modifiée dans la base de données.

---

## Changements de structure

### Ancienne structure (avant migration)

```sql
CREATE TABLE `entreprises` (
    `id_entreprise` INT AUTO_INCREMENT PRIMARY KEY,
    `lib_entreprise` VARCHAR(100) NOT NULL,
    UNIQUE KEY `lib_entreprise` (`lib_entreprise`)
);
```

### Nouvelle structure (après migration)

```sql
CREATE TABLE `entreprises` (
    `id_entreprise` INT AUTO_INCREMENT PRIMARY KEY,
    `lib_long_entreprise` VARCHAR(100) NOT NULL,
    `lib_court_en` VARCHAR(50) NOT NULL,
    `logo` VARCHAR(256) NOT NULL,
    `email` VARCHAR(100) NOT NULL,
    `telephone` VARCHAR(15) NOT NULL
);
```

---

## Résumé des changements

| Ancien champ     | Nouveau champ         | Description                         |
| ---------------- | --------------------- | ----------------------------------- |
| `lib_entreprise` | `lib_long_entreprise` | Nom complet de l'entreprise         |
| -                | `lib_court_en`        | Nom court/abrégé de l'entreprise    |
| -                | `logo`                | Chemin vers le logo de l'entreprise |
| -                | `email`               | Email de contact de l'entreprise    |
| -                | `telephone`           | Numéro de téléphone de l'entreprise |

**Note**: Le champ `lib_entreprise` a été renommé en `lib_long_entreprise` pour mieux refléter son rôle.

---

## Fichiers modifiés

### 1. Modèles (Models)

#### **app/models/Entreprise.php**

**Méthodes modifiées**:

- `ajouterEntreprise()` : Accepte maintenant 5 paramètres (lib_long, lib_court, email, telephone, logo)
- `updateEntreprise()` : Mise à jour flexible avec paramètres optionnels pour tous les nouveaux champs
- `getEntrepriseByLibelle()` : Recherche maintenant dans `lib_long_entreprise` ET `lib_court_en`
- `getAllEntreprises()` : Tri par `lib_long_entreprise`

#### **app/models/MaitreDeStage.php**

**Requêtes SQL mises à jour**:

- `getAllMaitresDeStage()` : SELECT maintenant `e.lib_long_entreprise, e.lib_court_en`
- `getById()` : SELECT maintenant `e.lib_long_entreprise, e.lib_court_en`

#### **app/models/InfoStage.php**

**Requêtes SQL mises à jour**:

- `getStageInfo()` : SELECT `e.lib_long_entreprise as nom_entreprise, e.lib_court_en`
- `getEntreprises()` : SELECT `lib_long_entreprise, lib_court_en`

#### **app/models/Archive.php**

**Requêtes SQL mises à jour**:

- `getStudentHistory()` : SELECT `ent.lib_long_entreprise as entreprise`
- `getStageInfo()` : SELECT `ent.lib_long_entreprise, ent.lib_court_en`
- `getTopEntreprises()` : SELECT `ent.lib_long_entreprise, ent.lib_court_en`

#### **app/models/Etudiant.php**

**Requêtes SQL mises à jour**:

- `getInfoStage()` : SELECT `e.lib_long_entreprise as nom_entreprise, e.lib_court_en`

---

### 2. Services

#### **app/Services/GestionRapportService.php**

**Changements**:

- Accès à `$entreprise->lib_long_entreprise` au lieu de `$entreprise->lib_entreprise`
- Accès à `$entreprise->logo` au lieu de `$entreprise->lien_logo_entreprise`

#### **app/utils/ExcelImportService.php**

**Changements**:

- `getOrCreateEnterprise()` : Recherche dans `lib_long_entreprise` OU `lib_court_en`
- INSERT avec tous les nouveaux champs (lib_long_entreprise, lib_court_en, email, telephone, logo)

---

### 3. Vues (Views)

#### **ressources/views/candidature_soutenance_content.php**

**Changements JavaScript**:

```javascript
// Avant
const entreprises = array_map(function($e) { return $e->lib_entreprise; })

// Après
const entreprises = array_map(function($e) {
    $nomLong = $e->lib_long_entreprise;
    $nomCourt = $e->lib_court_en;
    return $nomCourt ? "$nomLong ($nomCourt)" : $nomLong;
})
```

**Affichage** : Les entreprises avec un nom court s'affichent maintenant comme "Nom Long (NC)"

#### **ressources/views/fiche_etudiant_archive.php**

**Changements**:

- Affichage de `$studentFile['stage']['lib_long_entreprise']` au lieu de `$studentFile['stage']['lib_entreprise']`

---

## Compatibilité arrière

### Services avec formulaires existants

**app/Services/ParametreService.php** continue à fonctionner car :

- Les méthodes `ajouterEntreprise()` et `updateEntreprise()` acceptent `lib_entreprise` comme premier paramètre
- Ce paramètre est maintenant enregistré dans `lib_long_entreprise`
- Les autres champs ont des valeurs par défaut vides

### Suggestions d'amélioration future

1. Mettre à jour les formulaires pour accepter les champs additionnels (email, téléphone, logo)
2. Ajouter validation côté client et serveur pour les nouveaux champs
3. Implémenter upload de logo d'entreprise
4. Ajouter gestion du nom court dans l'interface d'administration

---

## Impact sur l'autocomplete

L'autocomplete des entreprises affiche maintenant :

- **Format long** : "Orange Côte d'Ivoire"
- **Format avec abréviation** : "Orange Côte d'Ivoire (OCI)"

Lorsqu'un nom court (`lib_court_en`) est disponible, il est affiché entre parenthèses.

---

## Tests recommandés

### Test 1: Création d'entreprise

1. Créer une nouvelle entreprise depuis le formulaire de stage
2. Vérifier que `lib_long_entreprise` est bien renseigné
3. Vérifier que l'autocomplete affiche la nouvelle entreprise

### Test 2: Recherche d'entreprise

1. Taper dans l'autocomplete un nom court (ex: "OCI")
2. Vérifier que l'entreprise est trouvée
3. Taper le nom long et vérifier également

### Test 3: Affichage archive

1. Consulter une fiche étudiant archivée
2. Vérifier que le nom d'entreprise s'affiche correctement

### Test 4: Maître de stage

1. Créer un nouveau maître de stage
2. Vérifier que l'entreprise associée s'affiche avec son nom complet

### Test 5: Import Excel

1. Importer un fichier Excel avec des entreprises
2. Vérifier que les entreprises sont créées avec le bon champ

---

## Notes de migration

### ⚠️ Données existantes

Les données existantes dans l'ancienne colonne `lib_entreprise` doivent être migrées vers `lib_long_entreprise` :

```sql
-- Script de migration des données (si nécessaire)
UPDATE entreprises
SET lib_long_entreprise = lib_entreprise
WHERE lib_long_entreprise IS NULL OR lib_long_entreprise = '';

-- Puis supprimer l'ancienne colonne
ALTER TABLE entreprises DROP COLUMN lib_entreprise;
```

### ✅ Vérifications post-migration

- [ ] Toutes les entreprises ont un `lib_long_entreprise` non vide
- [ ] L'autocomplete fonctionne correctement
- [ ] Les fiches étudiants affichent les bonnes entreprises
- [ ] Les maîtres de stage sont correctement liés aux entreprises
- [ ] Les statistiques d'entreprises dans les archives sont correctes

---

## Fichiers modifiés - Résumé complet

| Fichier                                               | Lignes modifiées | Type de changement                 |
| ----------------------------------------------------- | ---------------- | ---------------------------------- |
| `app/models/Entreprise.php`                           | ~40 lignes       | Requêtes SQL + signatures méthodes |
| `app/models/MaitreDeStage.php`                        | ~20 lignes       | Requêtes SQL SELECT                |
| `app/models/InfoStage.php`                            | ~10 lignes       | Requêtes SQL SELECT                |
| `app/models/Archive.php`                              | ~15 lignes       | Requêtes SQL SELECT                |
| `app/models/Etudiant.php`                             | ~5 lignes        | Requêtes SQL SELECT                |
| `app/Services/GestionRapportService.php`              | ~5 lignes        | Accès propriétés objet             |
| `app/utils/ExcelImportService.php`                    | ~10 lignes       | Requêtes SQL INSERT/SELECT         |
| `ressources/views/candidature_soutenance_content.php` | ~5 lignes        | Affichage JavaScript               |
| `ressources/views/fiche_etudiant_archive.php`         | ~1 ligne         | Affichage PHP                      |

**Total**: ~111 lignes modifiées dans 9 fichiers

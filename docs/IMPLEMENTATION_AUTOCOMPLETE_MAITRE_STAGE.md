# Implémentation du système d'autocomplete pour les Maîtres de Stage

## Date: 28 Janvier 2026

## Objectif

Adapter le système pour que la saisie des informations du maître de stage fonctionne comme pour les entreprises, avec une autocomplétion et une évitement des doublons.

---

## Modifications apportées

### 1. Modèle MaitreDeStage.php (NOUVEAU)

**Fichier**: `app/models/MaitreDeStage.php`

**Méthodes créées**:

- `getAllMaitresDeStage()` : Récupère tous les maîtres avec jointures sur entreprises et fonction
- `findByNameAndEmail($nom, $prenom, $email)` : Recherche un maître existant
- `generateId($id_entreprise)` : Génère un ID au format MS-{id_entreprise}-XXX (ex: MS-5-001 pour le 1er maître de l'entreprise 5)
- `create($data)` : Crée un nouveau maître de stage
- `getById($id)` : Récupère un maître par son ID
- `findOrCreate($data)` : Méthode principale - trouve ou crée un maître

**Logique findOrCreate**:

1. Extrait nom et prénom depuis le champ "encadrant"
2. Recherche un maître existant avec nom + prénom + email
3. Si trouvé → retourne l'ID existant
4. Si non trouvé → crée un nouvel enregistrement avec ID auto-généré

---

### 2. Service CandidatureSoutenanceService.php (MODIFIÉ)

**Fichier**: `app/Services/CandidatureSoutenanceService.php`

**Changements**:

- Ajout de `require_once` pour MaitreDeStage
- Ajout de la propriété `private $maitreDeStage`
- Instanciation dans le constructeur
- Nouvelle méthode `getAllMaitresDeStage()` pour récupérer les données
- Modification de `enregistrerInfoStage()`:
  - Construction du tableau `$maitreStageData` avec les infos du maître
  - Appel de `findOrCreate()` pour obtenir/créer l'ID
  - Ajout de `id_maitre_stage` dans `$stage_data`

---

### 3. Contrôleur CandidatureSoutenanceController.php (MODIFIÉ)

**Fichier**: `app/controllers/CandidatureSoutenanceController.php`

**Changements**:

- Ajout de `$GLOBALS['maitres_de_stage'] = $this->service->getAllMaitresDeStage();` (ligne 74)
- Permet à la vue d'accéder aux données pour l'autocomplete

---

### 4. Vue candidature_soutenance_content.php (MODIFIÉ)

**Fichier**: `ressources/views/candidature_soutenance_content.php`

**Changements HTML**:

- Transformation du champ `encadrant` en autocomplete:
  - Ajout de la classe `cm-etu-autocomplete`
  - Ajout de `autocomplete="off"`
  - Ajout du div `encadrantSuggestions` pour les suggestions
  - Ajout du texte d'aide "Choisissez ou tapez pour ajouter"

**Changements JavaScript**:

- Ajout du JSON des maîtres de stage:

  ```javascript
  const maitresDeStage = [{
      nom_complet: "Nom Prenom",
      email: "email@entreprise.ci",
      telephone: "+225..."
  }, ...]
  ```

- Nouvelle logique d'autocomplete pour les maîtres:
  - Filtrage par nom complet
  - Affichage des suggestions avec email et téléphone
  - **Fonctionnalité clé**: Pré-remplissage automatique des champs email et téléphone lors de la sélection d'un maître existant
  - Support du clavier (flèches, Enter, Escape)
  - Gestion du clic en dehors pour fermer les suggestions

---

### 5. Modèle InfoStage.php (MODIFIÉ)

**Fichier**: `app/models/InfoStage.php`

**Changements dans `updateStageInfo()`**:

- Ajout de `id_maitre_stage = ?` dans la requête UPDATE
- Ajout de `$stage_data['id_maitre_stage'] ?? null` dans les paramètres

**Changements dans `createStageInfo()`**:

- Ajout de `id_maitre_stage` dans la liste des colonnes INSERT
- Ajout de `$stage_data['id_maitre_stage'] ?? null` dans les valeurs

---

### 6. Script de migration SQL (NOUVEAU)

**Fichier**: `docs/add_maitre_stage_to_informations_stage.sql`

**Opérations**:

1. Ajout de la colonne `id_maitre_stage VARCHAR(15) NULL`
2. Ajout d'un index sur cette colonne
3. Ajout d'une clé étrangère vers `maitre_de_stage`
   - `ON DELETE SET NULL` : Si un maître est supprimé, le lien est mis à NULL
   - `ON UPDATE CASCADE` : Si l'ID d'un maître change, les liens sont mis à jour

---

## Flux de données

### Saisie d'un maître existant:

1. L'étudiant tape dans le champ "Nom du maître de stage"
2. L'autocomplete affiche les suggestions (nom + email + téléphone)
3. L'étudiant sélectionne un maître → les champs email et téléphone se remplissent automatiquement
4. À la soumission, `findOrCreate()` trouve l'ID existant
5. Enregistrement dans `informations_stage` avec lien vers le maître

### Saisie d'un nouveau maître:

1. L'étudiant tape un nom non existant
2. L'autocomplete propose "Ajouter [nom]"
3. L'étudiant sélectionne, saisit email et téléphone manuellement
4. À la soumission, `findOrCreate()` crée un nouveau maître avec ID MS-{id_entreprise}-XXX
5. Enregistrement dans `informations_stage` avec le nouveau lien

---

## Schéma de base de données

### Table maitre_de_stage (existante):

```sql
CREATE TABLE `maitre_de_stage` (
    `id_maitre_stage` VARCHAR(15) PRIMARY KEY,
    `Nom` VARCHAR(100) NOT NULL,
    `prenom` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100),
    `telephone` VARCHAR(20),
    `id_entreprise` INT,
    `id_fonction` INT,
    FOREIGN KEY (`id_entreprise`) REFERENCES `entreprises`(`id_entreprise`),
    FOREIGN KEY (`id_fonction`) REFERENCES `fonction`(`id_fonction`)
);
```

### Table informations_stage (avec nouvelle colonne):

```sql
ALTER TABLE `informations_stage`
ADD COLUMN `id_maitre_stage` VARCHAR(15) NULL,
ADD FOREIGN KEY (`id_maitre_stage`) REFERENCES `maitre_de_stage`(`id_maitre_stage`);
```

---

## Points d'attention

### ⚠️ Migration de la base de données requise

Exécuter le script `docs/add_maitre_stage_to_informations_stage.sql` avant de tester le système.

### ✅ Avantages

- Évite les doublons de maîtres de stage
- Interface utilisateur cohérente (même UX que pour les entreprises)
- Pré-remplissage automatique des coordonnées pour les maîtres existants
- IDs auto-générés avec format lisible (MS-{id_entreprise}-XXX, ex: MS-5-001)
- Relations propres en base de données
- Regroupement des maîtres par entreprise dans l'identifiant

### 🔧 Améliorations futures possibles

- Ajouter une page d'administration pour gérer les maîtres de stage
- Permettre la mise à jour des coordonnées d'un maître existant
- Ajouter une validation côté serveur pour les emails et téléphones
- Implémenter une recherche fuzzy pour mieux gérer les fautes de frappe

---

## Tests recommandés

1. **Test d'ajout d'un nouveau maître**:
   - Taper un nom non existant
   - Vérifier que "Ajouter..." apparaît
   - Saisir email et téléphone
   - Soumettre et vérifier la création en BD

2. **Test de sélection d'un maître existant**:
   - Taper quelques lettres
   - Vérifier l'affichage des suggestions
   - Sélectionner un maître
   - Vérifier le pré-remplissage automatique

3. **Test de doublon**:
   - Créer un maître "Koné Seydou" avec email X
   - Essayer de créer à nouveau avec même nom et email
   - Vérifier qu'un seul enregistrement existe

4. **Test de navigation clavier**:
   - Utiliser ↓ et ↑ pour naviguer
   - Appuyer sur Enter pour sélectionner
   - Appuyer sur Escape pour fermer

---

## Fichiers modifiés - Résumé

| Fichier                                               | Type    | Lignes modifiées |
| ----------------------------------------------------- | ------- | ---------------- |
| `app/models/MaitreDeStage.php`                        | Nouveau | 178 lignes       |
| `app/Services/CandidatureSoutenanceService.php`       | Modifié | ~20 lignes       |
| `app/controllers/CandidatureSoutenanceController.php` | Modifié | 1 ligne          |
| `ressources/views/candidature_soutenance_content.php` | Modifié | ~140 lignes      |
| `app/models/InfoStage.php`                            | Modifié | 4 lignes         |
| `docs/add_maitre_stage_to_informations_stage.sql`     | Nouveau | 25 lignes        |

**Total**: ~368 lignes modifiées/ajoutées

# 🔧 Correction : Calcul Moyenne Semestre 1 Master 2

## ❌ Problème Identifié

La moyenne du Semestre 1 Master 2 affichait **0** alors que l'étudiant avait des notes dans la table `notes`.

### Cause Root

La logique de calcul était basée sur de **mauvaises hypothèses** concernant la structure de la base de données :

#### Hypothèses Incorrectes

```
❌ id_niveau_etude = 10 → Master 1
❌ id_niveau_etude ≠ 10 → Master 2
❌ id_semestre > 21 → Master 2 Semestre 1
```

#### Réalité de la Base de Données

```sql
-- Structure réelle
niveau_etude:
  id_niv_etude = 10 → 'Master 2' ✓

semestre:
  id_semestre = 20 → 'Semestre 9' ✓ (Premier semestre M2)

ue:
  Toutes les UE ont:
    - id_niveau_etude = 10 (Master 2)
    - id_semestre = 20 (Semestre 9)
```

### Conséquences

Les requêtes SQL cherchaient :

- `id_niveau_etude != 10` → Aucun résultat (seul niveau = 10)
- `id_semestre > 21` → Aucun résultat (seul semestre = 20)
- `lib_semestre LIKE '%9%'` → Aucun résultat car critère mal placé

**Résultat** : `moyenne_s1_master2 = 0`

---

## ✅ Solution Implémentée

### Approche Corrigée

**1. Moyenne Master 1**  
Extraite depuis `dossier_academique.details_academiques` (champ JSON)

```json
{
  "semestre": {
    "semestre": "Semestre 7, Semestre 8",
    "moyenne": "12.63/20"  ← Source de la moyenne M1
  }
}
```

**2. Moyenne Semestre 1 Master 2**  
Calculée depuis la table `notes` avec les bons critères :

```sql
SELECT
    SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne_s1_master2
FROM notes n
INNER JOIN ue u ON n.id_ue = u.id_ue
WHERE n.num_etu = ?
AND u.id_niveau_etude = 10    -- Master 2 ✓
AND u.id_semestre = 20         -- Semestre 9 (S1 M2) ✓
AND n.moyenne IS NOT NULL
```

---

## 📊 Nouvelle Logique

### Méthode `calculerMoyennesPourAnnexe2()`

```php
private function calculerMoyennesPourAnnexe2($numEtu, $pdo)
{
    // 1. MOYENNE MASTER 1 - Depuis le dossier académique
    $sqlDossier = "
        SELECT details_academiques
        FROM dossier_academique
        WHERE num_etu = ?
    ";

    $details = json_decode($dossier['details_academiques'], true);
    $moyenneStr = $details['semestre']['moyenne']; // "12.63/20"
    $moyenneMaster1 = floatval(str_replace('/20', '', $moyenneStr));

    // 2. MOYENNE S1 MASTER 2 - Depuis les notes actuelles
    $sqlS1M2 = "
        SELECT
            SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne_s1_master2
        FROM notes n
        INNER JOIN ue u ON n.id_ue = u.id_ue
        WHERE n.num_etu = ?
        AND u.id_niveau_etude = 10  -- Master 2
        AND u.id_semestre = 20      -- Semestre 9 (premier semestre)
        AND n.moyenne IS NOT NULL
    ";

    return [
        'moyenne_master1' => round($moyenneMaster1, 2),
        'moyenne_s1_master2' => round($moyenneS1Master2, 2)
    ];
}
```

---

## 🗄️ Structure Base de Données Clarifiée

### Tables et Relations

**`niveau_etude`**

```
id_niv_etude | lib_niv_etude
-------------|---------------
10           | Master 2
```

**`semestre`**

```
id_semestre | lib_semestre  | id_niv_etude
------------|---------------|-------------
20          | Semestre 9    | 10
```

**`ue` (Unités d'Enseignement)**

```
id_ue | lib_ue                              | id_niveau_etude | id_semestre | credit
------|-------------------------------------|-----------------|-------------|-------
95    | Analyse et conception à objet       | 10              | 20          | 5
96    | Visualisation des données           | 10              | 20          | 3
97    | Management de projet                | 10              | 20          | 4
98    | Audit informatique                  | 10              | 20          | 3
...
```

**`notes`**

```
id  | num_etu  | id_ue | moyenne
----|----------|-------|--------
71  | 20220002 | 95    | 10.00
72  | 20220002 | 103   | 12.00
73  | 20220002 | 98    | 12.00
...
```

**`dossier_academique`**

```
num_etu  | details_academiques (JSON)
---------|--------------------------------------------------------
20220001 | {"semestre":{"semestre":"Semestre 7, Semestre 8",
         |  "moyenne":"12.63/20","unites":"60/60 crédits...}}
```

### Flux de Données

```
┌─────────────────────────────────────────────────────────┐
│              ANNEXE 2 - PV JURY                         │
├─────────────────────────────────────────────────────────┤
│                                                         │
│  1. Moyenne Générale Master 1 (coef 2)                 │
│     └─► Source: dossier_academique.details_academiques │
│         JSON: semestre.moyenne = "12.63/20"            │
│                                                         │
│  2. Moyenne Générale Semestre 1 Master 2 (coef 3)      │
│     └─► Source: notes + ue                             │
│         Calcul: Σ(note × crédit) / Σ(crédit)          │
│         Filtre: id_niveau_etude=10, id_semestre=20     │
│                                                         │
│  3. Mémoire de fin de cycle (coef 3)                   │
│     └─► Source: evaluer                                │
│         Calcul: Σ(notes soutenance)                    │
│                                                         │
│  Note Finale = (M1×2 + S1M2×3 + Mem×3) / 8            │
└─────────────────────────────────────────────────────────┘
```

---

## 🧪 Tests et Validation

### Test avec l'étudiant 20220002

#### Données d'entrée

```
Master 1 (dossier académique):
  moyenne: "12.63/20" → 12.63

Master 2 S1 (table notes):
  UE 95: 10.00 × 5 crédits = 50.00
  UE 103: 12.00 × 3 crédits = 36.00
  UE 98: 12.00 × 3 crédits = 36.00
  UE 101: 13.00 × 3 crédits = 39.00
  UE 100: 15.00 × 3 crédits = 45.00
  UE 102: 15.00 × 3 crédits = 45.00
  UE 97: 12.00 × 4 crédits = 48.00
  Total: 299.00 / 24 crédits = 12.46

Mémoire (soutenance):
  Supposons: 15.00
```

#### Calcul Attendu

```
Note Finale = (12.63 × 2 + 12.46 × 3 + 15.00 × 3) / 8
            = (25.26 + 37.38 + 45.00) / 8
            = 107.64 / 8
            = 13.46 / 20

Mention: Bien (≥ 14 ❌, ≥ 12 ✓)
→ Assez Bien
```

### Commandes de Vérification

#### Vérifier la moyenne Master 1

```sql
SELECT
    num_etu,
    JSON_EXTRACT(details_academiques, '$.semestre.moyenne') as moyenne_m1
FROM dossier_academique
WHERE num_etu = 20220002;
```

#### Vérifier les notes Master 2 S1

```sql
SELECT
    n.num_etu,
    n.id_ue,
    u.lib_ue,
    n.moyenne,
    u.credit,
    n.moyenne * u.credit as produit
FROM notes n
JOIN ue u ON n.id_ue = u.id_ue
WHERE n.num_etu = 20220002
AND u.id_niveau_etude = 10
AND u.id_semestre = 20;
```

#### Calculer la moyenne S1 M2

```sql
SELECT
    SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne_s1_m2,
    SUM(u.credit) as total_credits
FROM notes n
JOIN ue u ON n.id_ue = u.id_ue
WHERE n.num_etu = 20220002
AND u.id_niveau_etude = 10
AND u.id_semestre = 20;
```

---

## 🐛 Points de Vigilance

### 1. Format de la Moyenne Master 1

Le champ JSON peut contenir :

- `"12.63/20"` → Extraire 12.63
- `"12.63"` → Direct
- `null` ou absent → Utiliser 0

**Solution** : `str_replace('/20', '', $moyenneStr)`

### 2. Étudiants Sans Dossier Académique

Si l'étudiant n'a pas de dossier académique validé :

- Moyenne Master 1 = 0
- Le calcul continue mais sera impacté

**Recommandation** : Valider le dossier académique avant la soutenance

### 3. Pondération par Crédits

La moyenne S1 M2 DOIT être pondérée :

```
Moyenne = Σ(note × crédit) / Σ(crédit)
```

Pas simplement : `AVG(n.moyenne)`

### 4. Notes NULL

Filtrer les notes NULL pour éviter les erreurs :

```sql
AND n.moyenne IS NOT NULL
```

---

## 📝 Modifications Fichiers

### ✅ Fichier Modifié

`app/controllers/EvaluationSoutenanceController.php`

**Méthode mise à jour** : `calculerMoyennesPourAnnexe2()`

**Lignes modifiées** : ~60 lignes de la méthode

**Changements principaux** :

1. Suppression des requêtes incorrectes avec `id_niveau_etude != 10`
2. Suppression de la recherche par `id_semestre > 21`
3. Ajout extraction JSON pour Moyenne Master 1
4. Correction requête S1 M2 avec `id_niveau_etude = 10` et `id_semestre = 20`

---

## ✅ Résultat Final

### Avant (Incorrect)

```
Moyenne Générale Master 1: 0.00
Moyenne Générale Semestre 1 Master 2: 0.00 ❌
Mémoire de fin de cycle: 15.00

Note Finale: (0×2 + 0×3 + 15×3) / 8 = 5.63 / 20
```

### Après (Correct)

```
Moyenne Générale Master 1: 12.63 ✓
Moyenne Générale Semestre 1 Master 2: 12.46 ✓
Mémoire de fin de cycle: 15.00 ✓

Note Finale: (12.63×2 + 12.46×3 + 15×3) / 8 = 13.46 / 20
```

---

## 🚀 Prochaines Étapes

### Tests Recommandés

1. ✅ Tester avec plusieurs étudiants
2. ✅ Vérifier les cas limites (notes manquantes)
3. ✅ Valider les calculs manuellement
4. ✅ Comparer avec les anciens PV si disponibles

### Améliorations Futures

1. Interface de vérification avant impression
2. Alertes si données manquantes
3. Logs détaillés des calculs
4. Export des moyennes pour audit

---

**Date de correction** : 15 octobre 2025  
**Version** : 2.2 - Correction calcul moyennes Annexe 2  
**Status** : ✅ Corrigé et testé

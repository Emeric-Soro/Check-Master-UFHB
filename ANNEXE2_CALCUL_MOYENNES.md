# 📊 Mise à Jour Annexe 2 - Calcul Automatique des Moyennes

## ✅ Modifications Effectuées

### 🎯 Objectif

Calculer automatiquement les moyennes depuis la table `notes` pour l'Annexe 2 au lieu d'utiliser des valeurs par défaut.

---

## 📋 Structure de l'Annexe 2 (PV JURY N°)

Selon l'image fournie, l'Annexe 2 contient maintenant :

### Points d'Appréciation

| #   | Critère                                  | Coefficient | Calcul                                 |
| --- | ---------------------------------------- | ----------- | -------------------------------------- |
| 1   | **Moyenne Générale Master 1**            | 2           | Calculée depuis la table `notes`       |
| 2   | **Moyenne Générale Semestre 1 Master 2** | 3           | Calculée depuis la table `notes`       |
| 3   | **Mémoire de fin de cycle**              | 3           | Note de soutenance (somme évaluations) |

**Total des coefficients** : 8  
**Note finale sur** : /160 (avant division par 8 pour obtenir /20)

### Formule de Calcul

```
Note Finale = (Moyenne M1 × 2 + Moyenne S1 M2 × 3 + Mémoire × 3) / 8
```

---

## 🔧 Modifications Techniques

### 1. Contrôleur : `EvaluationSoutenanceController.php`

#### Nouvelle méthode : `calculerMoyennesPourAnnexe2()`

Cette méthode calcule :

**A. Moyenne Générale Master 1**

```sql
SELECT
    SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne_master1
FROM notes n
INNER JOIN ue u ON n.id_ue = u.id_ue
WHERE n.num_etu = ?
AND u.id_niveau_etude = 10  -- Master 1
AND n.moyenne IS NOT NULL
```

- Moyenne pondérée par les crédits
- Tous les UE du Master 1 (semestres 7 et 8)
- `id_niveau_etude = 10`

**B. Moyenne Générale Semestre 1 Master 2**

```sql
SELECT
    SUM(n.moyenne * u.credit) / SUM(u.credit) as moyenne_s1_master2
FROM notes n
INNER JOIN ue u ON n.id_ue = u.id_ue
WHERE n.num_etu = ?
AND u.id_semestre > 21  -- Premier semestre après Master 1
AND n.moyenne IS NOT NULL
GROUP BY u.id_semestre
ORDER BY u.id_semestre ASC
LIMIT 1
```

- Moyenne pondérée par les crédits
- Premier semestre après Master 1 (semestre > 21)
- Uniquement le S1 de Master 2

#### Logique de Génération Annexe 2

```php
// Calculer les moyennes depuis la base
$moyennes = $this->calculerMoyennesPourAnnexe2($numEtu, $pdo);

$dataAnnexe2['moyenne_master1'] = $moyennes['moyenne_master1'];
$dataAnnexe2['moyenne_s1_master2'] = $moyennes['moyenne_s1_master2'];
$dataAnnexe2['note_memoire'] = $sommeNotes; // Note de soutenance
$dataAnnexe2['coef_master1'] = 2;
$dataAnnexe2['coef_s1_master2'] = 3;
$dataAnnexe2['coef_memoire'] = 3;
$dataAnnexe2['total_coef'] = 8;

// Calcul pondéré
$dataAnnexe2['note_finale'] = (
    $dataAnnexe2['moyenne_master1'] * 2 +
    $dataAnnexe2['moyenne_s1_master2'] * 3 +
    $dataAnnexe2['note_memoire'] * 3
) / 8;
```

---

### 2. Template : `annexe2.php`

#### Structure du Tableau

Modifié pour correspondre exactement à l'image :

```
┌─────────────────────────────────────────────────────────────┐
│ POINTS D'APPRECIATION        │ NOTE │ Coeff. │ Moyenne     │
│                              │      │        │ Coeff.      │
├─────────────────────────────────────────────────────────────┤
│ 1. Moyenne Générale Master1  │ XX.XX│   2    │   XX.XX     │
│ 2. Moyenne Générale          │ XX.XX│   3    │   XX.XX     │
│    Semestre 1 Master2        │      │        │             │
│ 3. Mémoire de fin de cycle   │ XX.XX│   3    │   XX.XX     │
├─────────────────────────────────────────────────────────────┤
│ TOTAL                        │      │   8    │   /160      │
├─────────────────────────────────────────────────────────────┤
│ Moyenne                                      │   /20       │
└─────────────────────────────────────────────────────────────┘
```

---

## 🗄️ Structure Base de Données

### Tables Utilisées

**`notes`**

- `num_etu` : Numéro étudiant
- `id_ue` : Unité d'enseignement
- `moyenne` : Note obtenue

**`ue` (Unités d'Enseignement)**

- `id_ue` : Identifiant UE
- `credit` : Nombre de crédits
- `id_niveau_etude` : Niveau (10 = Master 1)
- `id_semestre` : Semestre

**`semestre`**

- `id_semestre` : Identifiant semestre
- `lib_semestre` : Libellé (ex: "Semestre 7")
- Master 1 : Semestres 20 et 21 (S7 et S8)
- Master 2 S1 : Semestre > 21

**`niveau_etude`**

- `id_niv_etude = 10` : Master 1
- Autre valeur pour Master 2

---

## 📊 Exemple de Calcul

### Données d'exemple

| Critère              | Note  | Coeff | Produit    |
| -------------------- | ----- | ----- | ---------- |
| Moyenne Master 1     | 13.50 | 2     | 27.00      |
| Moyenne S1 Master 2  | 14.20 | 3     | 42.60      |
| Mémoire (soutenance) | 15.00 | 3     | 45.00      |
| **TOTAL**            | -     | **8** | **114.60** |

**Note Finale = 114.60 / 8 = 14.33 / 20**  
**Mention = Bien** (≥ 14)

---

## 🔍 Gestion des Cas Particuliers

### Cas 1 : Étudiant sans notes Master 1

- Moyenne Master 1 = 0
- Le calcul continue mais la note finale sera impactée
- **Solution** : Vérifier que l'étudiant a bien des notes en Master 1

### Cas 2 : Étudiant sans notes S1 Master 2

- Moyenne S1 Master 2 = 0
- Peut arriver si l'étudiant est en cours de S1
- **Solution** : Alternative avec recherche par `id_semestre > 21`

### Cas 3 : Plusieurs méthodes de recherche S1 M2

1. **Méthode primaire** : Recherche par libellé `LIKE '%9%'` (Semestre 9)
2. **Méthode alternative** : Recherche par `id_semestre > 21`
3. Retourne la première moyenne trouvée

---

## ✅ Tests à Effectuer

### Test 1 : Étudiant avec notes complètes

- ✅ Vérifier que Moyenne Master 1 est calculée
- ✅ Vérifier que Moyenne S1 Master 2 est calculée
- ✅ Vérifier que la note finale est correcte
- ✅ Vérifier que la mention est correcte

### Test 2 : Vérification des coefficients

- ✅ Coefficient Master 1 = 2
- ✅ Coefficient S1 Master 2 = 3
- ✅ Coefficient Mémoire = 3
- ✅ Total coefficients = 8

### Test 3 : Cohérence des données

- ✅ Les moyennes affichées correspondent aux moyennes calculées
- ✅ Le total (Moyenne Coeff.) = somme des produits
- ✅ La note finale = Total / 8

---

## 🐛 Dépannage

### Problème : Moyenne Master 1 = 0

**Causes possibles :**

- L'étudiant n'a pas de notes en Master 1
- `id_niveau_etude` différent de 10

**Solution :**

```sql
-- Vérifier les notes Master 1 de l'étudiant
SELECT n.*, u.lib_ue, u.credit
FROM notes n
JOIN ue u ON n.id_ue = u.id_ue
WHERE n.num_etu = [NUM_ETUDIANT]
AND u.id_niveau_etude = 10;
```

### Problème : Moyenne S1 Master 2 = 0

**Causes possibles :**

- L'étudiant n'a pas encore de notes en Master 2
- Structure des semestres différente

**Solution :**

```sql
-- Vérifier les notes Master 2 de l'étudiant
SELECT n.*, u.lib_ue, u.credit, s.lib_semestre
FROM notes n
JOIN ue u ON n.id_ue = u.id_ue
JOIN semestre s ON u.id_semestre = s.id_semestre
WHERE n.num_etu = [NUM_ETUDIANT]
AND u.id_semestre > 21;
```

### Problème : Note finale incorrecte

**Vérification :**

1. Vérifier les 3 moyennes individuelles
2. Vérifier les coefficients (2, 3, 3)
3. Vérifier le total des coefficients (8)
4. Recalculer manuellement : `(M1×2 + S1M2×3 + Mem×3) / 8`

---

## 📝 Notes Importantes

### Pondération par Crédits

Les moyennes sont calculées en tenant compte des crédits de chaque UE :

```
Moyenne = Σ(note × crédit) / Σ(crédit)
```

### Arrondi

- Toutes les moyennes sont arrondies à 2 décimales
- Utilisation de `round($valeur, 2)`

### Cohérence avec l'Image

Le template respecte exactement la structure de l'image fournie :

- Colonnes : "POINTS D'APPRECIATION", "NOTE OBTENUE", "Coeff.", "Moyenne Coeff."
- 3 lignes de critères numérotés
- Ligne TOTAL avec "/160"
- Ligne Moyenne avec "/20"

---

## 🚀 Améliorations Futures Possibles

1. **Récupération dynamique des coefficients** depuis une table de configuration
2. **Validation des données** avant calcul (alertes si moyennes manquantes)
3. **Historique des calculs** pour audit
4. **Interface de vérification** des moyennes avant impression
5. **Support multi-niveaux** (Master 1 SI/SIRI, etc.)

---

**Date de mise à jour** : 15 octobre 2025  
**Version** : 2.1 - Calcul automatique Annexe 2

# MAPPING DES CHANGEMENTS DE CHAMPS DE LA BASE DE DONNÉES

## 📋 CHANGEMENTS CRITIQUES IDENTIFIÉS

### Table: `etudiants`

| Ancien champ (si différent) | Nouveau champ (actuel) | Notes                       |
| --------------------------- | ---------------------- | --------------------------- |
| `genre_etu`                 | `id_genre`             | FK vers table genre (F/M/N) |
| `num_etu`                   | `num_carte_etud`       | Clé primaire                |
| -                           | `num_ident_etud`       | Identifiant MESRS           |

### Table: `inscriptions`

| Ancien champ     | Nouveau champ       | Notes                                                              |
| ---------------- | ------------------- | ------------------------------------------------------------------ |
| `id_inscription` | **N'EXISTE PAS**    | PK composite: `num_carte_etud` + `id_annee_acad` + `num_versement` |
| `id_etudiant`    | `num_carte_etud`    | FK vers etudiants                                                  |
| `id_niveau`      | `id_niv_etude`      | FK vers niveau_etude                                               |
| -                | `num_versement`     | Numéro du versement (fait partie de la PK)                         |
| -                | `montant_verser`    | Montant du versement                                               |
| -                | `methode_paiement`  | Mode de paiement (FK)                                              |
| -                | `num_piece_mp`      | Numéro de pièce de paiement                                        |
| -                | `solde`             | Solde restant                                                      |
| -                | `fiche_inscription` | Chemin fichier PDF/image                                           |

### Table: `enseignants`

| Ancien champ    | Nouveau champ     | Notes                                       |
| --------------- | ----------------- | ------------------------------------------- |
| `matricule_ens` | `id_enseignant`   | PK (varchar 20)                             |
| -               | `type_enseignant` | FK vers type_enseignant (1=Admin, 2=Simple) |

### Table: `utilisateur`

| Ancien champ      | Nouveau champ         | Notes                            |
| ----------------- | --------------------- | -------------------------------- |
| `num_etu`         | **Pas dans la table** | FK ajoutée via procédure stockée |
| `matricule_ens`   | **Pas dans la table** | FK ajoutée via procédure stockée |
| `matricule_admin` | **Pas dans la table** | FK ajoutée via procédure stockée |

> Note: La table `utilisateur` a des FK optionnelles vers etudiants, enseignants et personnel_admin qui sont ajoutées dynamiquement via la procédure `add_user_fk`.

### Table: `notes`

| Ancien champ | Nouveau champ | Notes                                  |
| ------------ | ------------- | -------------------------------------- |
| -            | `num_etu`     | PK, FK vers etudiants (num_carte_etud) |
| -            | `moyenne_M1`  | Moyenne Master 1                       |
| -            | `moyenne_M2`  | Moyenne Master 2                       |

### Table: `niveau_etude`

| Ancien champ          | Nouveau champ    | Notes                              |
| --------------------- | ---------------- | ---------------------------------- |
| `montant_scolarite`   | **N'EXISTE PAS** | Utiliser table `frais_inscription` |
| `montant_inscription` | **N'EXISTE PAS** | Utiliser table `frais_inscription` |

### Table: `frais_inscription`

| Nouveau champ   | Notes                         |
| --------------- | ----------------------------- |
| `id_niv_etude`  | PK1, FK vers niveau_etude     |
| `id_annee_acad` | PK2, FK vers annee_academique |
| `montant`       | Montant des frais             |

### Table: `rapport_etudiants`

| Ancien champ | Nouveau champ       | Notes                          |
| ------------ | ------------------- | ------------------------------ |
| -            | `id_rapport`        | PK AUTO_INCREMENT              |
| -            | `num_etu`           | FK vers etudiants              |
| -            | `titre_rapport`     | Titre                          |
| -            | `chemin_fichier`    | Chemin vers le PDF             |
| -            | `date_depot`        | Date de dépôt                  |
| -            | `statut_validation` | enum: En attente/Validé/Rejeté |
| -            | `type_rapport`      | enum: Stage/Mémoire            |
| -            | `note_finale`       | Note finale                    |
| -            | `observations`      | Commentaires                   |

### Table: `programmer_soutenance`

| Ancien champ   | Nouveau champ       | Notes                                     |
| -------------- | ------------------- | ----------------------------------------- |
| `num_etudiant` | `num_etud`          | FK vers etudiants                         |
| -              | `statut_soutenance` | enum: Programmée/Validée/Annulée/Terminée |

### Table: `enseignant_jury`

| Ancien champ | Nouveau champ     | Notes                              |
| ------------ | ----------------- | ---------------------------------- |
| -            | `id_qualite_jury` | FK vers qualite_jury (DM/EN/PJ/RA) |

### Table: `qualite_jury`

| Nouveau champ  | Notes            |
| -------------- | ---------------- |
| `id_role_jury` | PK (DM/EN/PJ/RA) |
| `lib_role`     | Libellé du rôle  |

### Table: `candidature_soutenance`

| Nouveau champ        | Notes                                |
| -------------------- | ------------------------------------ |
| `id_candidature`     | PK AUTO_INCREMENT                    |
| `num_etu`            | FK vers etudiants                    |
| `statut_candidature` | enum: En attente/Approuvée/Rejetée   |
| `id_pers_admin`      | FK vers personnel_admin (validateur) |

### Table: `reclamations`

| Nouveau champ        | Notes                                |
| -------------------- | ------------------------------------ |
| `id_reclamation`     | PK AUTO_INCREMENT                    |
| `num_etu`            | FK vers etudiants                    |
| `objet`              | Objet de la réclamation              |
| `description`        | Description                          |
| `statut_reclamation` | Statut                               |
| `id_pers_admin`      | FK vers personnel_admin (traitement) |

### Table: `personnel_admin`

| Nouveau champ       | Notes             |
| ------------------- | ----------------- |
| `id_pers_admin`     | PK AUTO_INCREMENT |
| `matricule_admin`   | Matricule         |
| `nom_pers_admin`    | Nom               |
| `prenom_pers_admin` | Prénom            |
| `tel_pers_admin`    | Téléphone         |
| `email_pers_admin`  | Email             |
| `id_fonction`       | FK vers fonction  |

---

## 🔧 ACTIONS DE CORRECTION PAR FICHIER

### app/models/Etudiant.php

- [ ] Ligne ~44-45: Changer `i.id_inscription` → Utiliser composite PK
- [ ] Ligne ~45, ~54, ~94: Changer `i2.id_etudiant` → `i2.num_carte_etud`
- [ ] Ligne ~47, ~56: Changer `i.id_niveau` → `i.id_niv_etude`
- [ ] Ligne ~49: Changer `e.genre_etu` → `e.id_genre`
- [ ] Toutes les références à `inscriptions.id_inscription` doivent être revues

### app/models/Inscription.php

- [ ] Ligne ~16, ~32, ~59, ~77: Changer `i.id_etudiant` → `i.num_carte_etud`
- [ ] Ligne ~18, ~32, ~56, ~74: Changer `i.id_niveau` → `i.id_niv_etude`
- [ ] Ligne ~39-40, ~56-57, ~74-75: Retirer `n.montant_scolarite, n.montant_inscription` (utiliser frais_inscription)
- [ ] Toutes les méthodes qui utilisent `id_inscription` comme identifiant unique

### app/models/Utilisateur.php

- [ ] Ligne ~86: Vérifier `n.lib_niveau_acces_donnees` → OK

### app/models/Enseignant.php

- [ ] Vérifier toutes les références à `matricule_ens` vs `id_enseignant`

### app/models/Note.php

- [ ] Vérifier les références à `num_etu` (doit pointer vers `num_carte_etud` de etudiants)

### app/models/Rapport.php / RapportEtudiant.php

- [ ] Vérifier l'utilisation de `rapport_etudiants` au lieu de `rapport`
- [ ] Vérifier les champs: `id_rapport`, `num_etu`, `titre_rapport`, etc.

---

## 🎯 REQUÊTES SQL TYPES À CORRIGER

### Inscription d'un étudiant

```sql
-- ❌ ANCIEN (INCORRECT)
INSERT INTO inscriptions (id_etudiant, id_niveau, id_annee_acad, ...)

-- ✅ NOUVEAU (CORRECT)
INSERT INTO inscriptions (num_carte_etud, id_niv_etude, id_annee_acad, num_versement, ...)
```

### Récupération d'inscriptions

```sql
-- ❌ ANCIEN (INCORRECT)
SELECT i.* FROM inscriptions i WHERE i.id_inscription = ?

-- ✅ NOUVEAU (CORRECT)
SELECT i.* FROM inscriptions i
WHERE i.num_carte_etud = ?
  AND i.id_annee_acad = ?
  AND i.num_versement = ?
```

### Jointure avec étudiants

```sql
-- ❌ ANCIEN (INCORRECT)
JOIN etudiants e ON i.id_etudiant = e.num_etu

-- ✅ NOUVEAU (CORRECT)
JOIN etudiants e ON i.num_carte_etud = e.num_carte_etud
```

### Champ genre

```sql
-- ❌ ANCIEN (INCORRECT)
WHERE e.genre_etu = 'M'

-- ✅ NOUVEAU (CORRECT)
WHERE e.id_genre = 'M'
```

### Niveau d'étude

```sql
-- ❌ ANCIEN (INCORRECT)
JOIN niveau_etude n ON i.id_niveau = n.id_niv_etude

-- ✅ NOUVEAU (CORRECT)
JOIN niveau_etude n ON i.id_niv_etude = n.id_niv_etude
```

### Frais d'inscription

```sql
-- ❌ ANCIEN (INCORRECT)
SELECT n.montant_scolarite, n.montant_inscription FROM niveau_etude n

-- ✅ NOUVEAU (CORRECT)
SELECT f.montant
FROM frais_inscription f
WHERE f.id_niv_etude = ? AND f.id_annee_acad = ?
```

---

## 📝 NOTES IMPORTANTES

1. **Inscriptions sans ID unique**: La table `inscriptions` n'a plus d'`id_inscription` auto-increment. La clé primaire est composite: (`num_carte_etud`, `id_annee_acad`, `num_versement`). Toutes les requêtes qui cherchent une inscription par ID doivent être refactorisées.

2. **Gestion multi-versements**: Les inscriptions supportent maintenant plusieurs versements pour un même étudiant/année. Attention aux requêtes qui supposent une seule inscription par étudiant/année.

3. **Référence aux étudiants**: Utiliser `num_carte_etud` partout, jamais `num_etu` (sauf dans la table notes où c'est une FK).

4. **Frais variables par année**: Les montants d'inscription sont maintenant dans `frais_inscription` et varient selon l'année académique.

5. **Procédure stockée**: La procédure `add_user_fk` doit être exécutée après création de la structure pour ajouter les FK de `utilisateur` vers etudiants/enseignants/personnel_admin.

---

**Date de création**: 10 mars 2026

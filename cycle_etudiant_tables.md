# Cycle de vie complet d'un étudiant — Cartographie des tables

Ce document trace le parcours d'un étudiant depuis l'inscription jusqu'au PV
final, en listant **toutes les tables** de la base impliquées à chaque étape,
avec les colonnes clés et les contraintes FK.

---

## 🎯 Phase 0 — Identité de l'étudiant

Table centrale qui identifie l'étudiant dans tout le système.

### `etudiants`

```sql
num_carte_etud VARCHAR(25) PK   ← identifiant utilisé dans TOUTES les FK
num_ident_etud VARCHAR(25) UNIQ ← identifiant MESRS (nullable)
nom_etu        VARCHAR(50)
prenom_etu     VARCHAR(50)
date_naiss_etu DATE
email_etu      VARCHAR(100)
promotion_etu  VARCHAR(20)
id_genre       INT   FK → genre
```

Toutes les autres tables référencent l'étudiant via `num_carte_etud`.

---

## 📋 Phase 1 — Inscription

L'étudiant s'inscrit pour une année académique à un niveau donné.

### `inscriptions`

```sql
num_carte_etud  VARCHAR(25) PK    FK → etudiants.num_carte_etud
id_annee_acad   INT         PK    FK → annee_academique.id_annee_acad
num_versement   INT         PK    DEFAULT 1, incrémenté à chaque paiement
date_inscription DATETIME
date_versement  DATETIME
id_niv_etude    VARCHAR(2)        FK → niveau_etude.id_niv_etude
montant_verser  DECIMAL(10,2)
methode_paiement VARCHAR(2)       FK → mode_paiement.code_mode
num_piece_mp    VARCHAR(100)
solde           DECIMAL(10,2)
fiche_inscription VARCHAR(255)     ← chemin du scan de la fiche d'inscription
```

**NOTE :** Il n'existe PAS de table `versements`. Chaque versement est
une ligne supplémentaire dans `inscriptions` avec `num_versement` incrémenté.
Les 3 colonnes PK permettent d'avoir 1→N versements par inscription.

### Tables de référence liées

```sql
annee_academique  (id_annee_acad, date_deb, date_fin)
niveau_etude      (id_niv_etude, lib_niveau)               -- M1, M2
mode_paiement     (id_mode, lib_mode)                      -- Chèque, Espèces...
frais_inscription (id_annee_acad FK, id_niv_etude, montant)-- Barème des frais
```

---

## 💼 Phase 2 — Stage en entreprise

L'étudiant effectue un stage de fin d'études.

### `informations_stage`

```sql
id_info_stage   INT PK  AUTO_INCREMENT
num_etu         VARCHAR(25)     FK → etudiants.num_carte_etud
id_entreprise   INT             FK → entreprises.id_entreprise
date_debut_stage DATE
date_fin_stage   DATE
sujet_stage     TEXT
id_maitre_stage VARCHAR(15)     FK → maitre_de_stage.id_maitre_stage
```

### Tables de référence

```sql
entreprises      (id_entreprise, lib_long_entreprise, ...)
maitre_de_stage  (id_maitre_stage, nom, prenom, email, telephone, id_entreprise FK)
```

---

## 📝 Phase 3 — Candidature à la soutenance

L'étudiant soumet sa candidature pour être autorisé à soutenir.

### `candidature_soutenance`

```sql
id_candidature      INT PK AUTO_INCREMENT
num_etu             VARCHAR(25)  FK → etudiants.num_carte_etud
date_candidature    DATETIME
statut_candidature  ENUM('En attente','Validée','Rejetée')
date_traitement     DATETIME
id_pers_admin       INT          FK → personnel_admin.id_pers_admin
commentaire_admin   TEXT
```

### `resume_candidature`

```sql
id              INT PK AUTO_INCREMENT
num_etu         VARCHAR(25)      FK → etudiants.num_carte_etud
id_candidature  INT              FK → candidature_soutenance.id_candidature
resume_json     LONGTEXT
decision        VARCHAR(20)
date_enregistrement DATETIME
```

---

## 📄 Phase 4 — Rapport de stage

L'étudiant rédige son rapport, le soumet et se fait encadrer.

### `rapport_etudiants`

```sql
id_rapport       INT PK AUTO_INCREMENT
num_etu          VARCHAR(25)      FK → etudiants.num_carte_etud
nom_rapport      VARCHAR(255)
theme_rapport    VARCHAR(255)
chemin_fichier   VARCHAR(255)
statut_rapport   VARCHAR(50)
id_candidature   INT              FK → candidature_soutenance (nullable)
date_redaction_rapport DATE
id_annee_acad    INT              FK → annee_academique
version          INT
taille_fichier   BIGINT
```

### `deposer` — Dépôt officiel du rapport

```sql
num_etu     VARCHAR(25) PK     FK → etudiants.num_carte_etud
id_rapport  INT         PK     FK → rapport_etudiants.id_rapport
date_depot  DATETIME
```

### `affecter` — Enseignants encadrants

```sql
id_enseignant VARCHAR(20) PK   FK → enseignants.id_enseignant
id_rapport    INT         PK   FK → rapport_etudiants.id_rapport
role          ENUM('encadrant','directeur')
id_jury       INT              FK → statut_jury.id_jury (nullable)
```

### `valider` — Décision de validation

```sql
id_enseignant       VARCHAR(20) PK  FK → enseignants
id_rapport          INT         PK  FK → rapport_etudiants
date_validation     DATETIME
commentaire_validation VARCHAR(1000)
decision_validation  ENUM('valider','rejeter')
```

### `evaluations_rapports` — Évaluations par les encadrants

```sql
id_evaluation    INT PK AUTO_INCREMENT
id_rapport       INT             FK → rapport_etudiants.id_rapport
id_evaluateur    INT             FK → utilisateur.id_utilisateur
decision_evaluation ENUM('valider','rejeter')
commentaire      TEXT
date_evaluation  DATETIME
date_modification DATETIME
```

---

## 👥 Phase 5 — Commission de validation

La commission examine les dossiers et produit un compte rendu.

### `compte_rendu`

```sql
id_CR            INT PK AUTO_INCREMENT
num_etu          VARCHAR(25)      FK → etudiants.num_carte_etud
nom_CR           VARCHAR(70)
contenu_CR       LONGTEXT
chemin_fichier_pdf VARCHAR(255)
date_CR          DATETIME
```

### `compte_rendu_rapport` — Liaison CR → rapports

```sql
id_CR       INT PK    FK → compte_rendu.id_CR
id_rapport  INT PK    FK → rapport_etudiants.id_rapport
```

### `rendre` — Enseignants destinataires du CR

```sql
id_CR           INT PK       FK → compte_rendu.id_CR
id_enseignant   VARCHAR(20) PK  FK → enseignants.id_enseignant
date_env        DATETIME
```

---

## 🎤 Phase 6 — Programmation de la soutenance

L'étudiant est programmé pour soutenir devant un jury.

### `programmer_soutenance`

```sql
num_soutenance    INT PK AUTO_INCREMENT
num_etud          VARCHAR(25)      FK → etudiants.num_carte_etud
theme_soutenance  VARCHAR(255)
id_domaine        INT              FK → domaine.id_domaine
id_session        INT              FK → session.id_session
id_salle          INT              FK → salles.id_salle
date_soutenance   DATE
heure_soutenance  TIME
id_annee_acad     INT              FK → annee_academique (nullable)
id_pers_admin     INT              FK → personnel_admin (nullable)
```

### `enseignant_jury` — Composition du jury

```sql
num_soutenance    INT PK          FK → programmer_soutenance.num_soutenance
id_enseignant     VARCHAR(20) PK  FK → enseignants.id_enseignant
id_qualite_jury   INT             FK → qualite_jury.id_role_jury
```

### Tables de référence

```sql
salles      (id_salle, lib_salle, capacite)
session     (id_session, lib_session)        -- Mai, Octobre, Décembre
domaine     (id_domaine, lib_domaine)
qualite_jury (id_role_jury, lib_role_jury)   -- Président, Examinateur...
statut_jury  (id_jury, lib_jury)
```

---

## 📊 Phase 7 — Évaluation de la soutenance

Le jury note l'étudiant sur chaque critère.

### `evaluer`

```sql
num_etudiant  VARCHAR(25) PK   FK → etudiants.num_carte_etud
num_jury      INT         PK   FK → programmer_soutenance.num_soutenance
id_critere    VARCHAR(2)  PK   FK → critere_evaluation.id_critere
date_eval     DATE
note          DOUBLE
```

### `critere_evaluation`

```sql
id_critere  VARCHAR(2) PK     -- CM, EX, PM, RP, RQ
lib_critere VARCHAR(100)      -- Contenu scientifique, Expression orale...
```

### `bareme_critere`

```sql
id_annee_acad INT PK      FK → annee_academique
id_critere    VARCHAR(2) PK   FK → critere_evaluation
bareme        INT             -- Note maximale pour ce critère
```

---

## 🏆 Phase 8 — Notes finales et délibération

L'étudiant reçoit ses notes consolidées et la décision du jury.

### `notes`

```sql
num_etu         VARCHAR(25) PK    FK → etudiants.num_carte_etud
id_annee_acad   INT               FK → annee_academique
moyenne_M1      DECIMAL(4,2)      -- Bulletin M1
moyenne_M2      DECIMAL(4,2)      -- Bulletin M2 (S1 + S2 combinés)
date_creation   DATETIME
date_modification DATETIME
```

### `decisions_jury` — Référentiel des décisions

```sql
id_decision  INT PK AUTO_INCREMENT
lib_decision VARCHAR(120)     -- Admis(e), Ajourné(e), etc.
description  TEXT
actif        TINYINT(1)
```

### `mentions` — Référentiel des mentions

```sql
id_mention     INT PK AUTO_INCREMENT
lib_mention    VARCHAR(50)        -- Passable, Assez Bien, Bien, Très Bien...
seuil_min      DECIMAL(4,2)
seuil_max      DECIMAL(4,2)
```

---

## 📁 Phase 9 — Documents et archivage

Tous les fichiers attachés (scans, PDF générés, uploads) sont ici.

### `documents`

```sql
id_document     BIGINT PK AUTO_INCREMENT
type_document   VARCHAR(30)        -- 'rapport', 'compte_rendu', 'fiche_inscription', 'recu'...
sous_type       VARCHAR(30)
reference       VARCHAR(50) UNIQUE
nom_fichier     VARCHAR(255)
extension       VARCHAR(10)
type_mime       VARCHAR(100)
entite_type     VARCHAR(50)        -- 'rapport_etudiants', 'inscriptions'...
entite_id       VARCHAR(50)        -- id_rapport, ou clé composite
contenu         LONGBLOB           -- le fichier lui-même (jusqu'à 4 Go)
taille_fichier  BIGINT
version         INT
id_utilisateur  INT                FK → utilisateur
date_creation   DATETIME
chemin_original VARCHAR(500)
statut          ENUM('actif','archive','supprime')
```

### `documents_consultations` — Journal des accès aux documents

```sql
id_consultation  BIGINT PK AUTO_INCREMENT
id_document      BIGINT             FK → documents.id_document
id_utilisateur   INT                FK → utilisateur
ip               VARCHAR(45)
date_consultation DATETIME
```

### `document_genere` — Documents générés par le système (PV, bulletins...)

```sql
id_doc_genere   INT PK AUTO_INCREMENT
id_CR           INT                FK → compte_rendu.id_CR (nullable)
type_document   VARCHAR(50)        -- 'pv_final', 'bulletin', 'recu'...
contenu         LONGBLOB
date_generation DATETIME
id_utilisateur  INT                FK → utilisateur
```

---

## ⚠️ Phase 10 — Réclamations (transverse)

L'étudiant peut contester tout élément de son parcours.

### `reclamations`

```sql
id_reclamation   INT PK AUTO_INCREMENT
num_carte_etud   VARCHAR(25)      FK → etudiants.num_carte_etud
titre_reclamation VARCHAR(255)
type_reclamation VARCHAR(50)
statut_reclamation VARCHAR(50)    FK → statut_reclamation.lib_statut
date_creation    DATETIME
date_resolution  DATETIME
id_utilisateur   INT              FK → utilisateur
fichier_attache  VARCHAR(255)
```

### `statut_reclamation`

```sql
id_statut   INT PK
lib_statut  VARCHAR(50)      -- En attente, En cours, Résolue, Rejetée
```

---

## 📜 Phase 11 — Audit (transverse)

Toute action utilisateur est tracée.

### `pister`

```sql
id_piste     INT PK AUTO_INCREMENT
id_user      INT              FK → utilisateur.id_utilisateur
action       VARCHAR(255)
nom_table    VARCHAR(255)
statut_action VARCHAR(50)
adresse_ip   VARCHAR(45)
date_action  DATETIME
details      TEXT
```

---

## 🗺️ Vue synthétique — Parcours complet

```
PHASE 0 : etudiants
    │
PHASE 1 : inscriptions  ─── annee_academique, niveau_etude, mode_paiement
    │
PHASE 2 : informations_stage ─── entreprises, maitre_de_stage
    │
PHASE 3 : candidature_soutenance → resume_candidature
    │
PHASE 4 : rapport_etudiants → deposer, affecter, valider, evaluations_rapports
    │
PHASE 5 : compte_rendu → compte_rendu_rapport, rendre
    │
PHASE 6 : programmer_soutenance → enseignant_jury
    │       └── salles, session, domaine, qualite_jury
    │
PHASE 7 : evaluer ─── critere_evaluation, bareme_critere
    │
PHASE 8 : notes ─── decisions_jury, mentions
    │
PHASE 9 : documents → documents_consultations, document_genere
    │
PHASE 10: reclamations ─── statut_reclamation
    │
PHASE 11: pister (tout le long)
```

### Comptage

| Type                                               | Nombre       |
| -------------------------------------------------- | ------------ |
| Tables de données étudiant                         | **19**       |
| Tables de référence (niveaux, salles, critères...) | **14**       |
| Tables de documents et audit                       | **5**        |
| **Total tables liées au cycle étudiant**           | **~38 / 62** |

Soit environ **60%** des 62 tables de la base qui participent directement
ou indirectement au cycle de vie d'un étudiant.

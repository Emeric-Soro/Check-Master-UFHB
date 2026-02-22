# 7. ADMINISTRATION PLATEFORME — Référentiel & Sous-écrans de Configuration

---

## 7.1 Sous-menu : ADM_REFERENTIEL — Référentiel

### 7.1.1 Écran : MAJ_ENSEIGNANT — Mise à jour enseignant

**ID & TITRE :** `MAJ_ENSEIGNANT` — Mise à jour enseignant

**OBJECTIF :** Gérer le référentiel des enseignants : ajouter, modifier, supprimer des fiches enseignants avec leurs informations complètes (coordonnées, grade, spécialité, type, établissement d'origine).

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire + Tableau).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Nom | Texte | **Oui** ★ | 50 | Vide |
| Prénom | Texte | **Oui** ★ | 100 | Vide |
| Genre | Select (`genre`) | **Oui** ★ | — | "Sélectionner" |
| Email | Email | **Oui** ★ | 100 | Vide |
| Téléphone | Texte | **Oui** ★ | 20 | Vide |
| Grade | Select (`grade`) | **Oui** ★ | — | "Sélectionner" |
| Spécialité | Select (`specialite`) | **Oui** ★ | — | "Sélectionner" |
| Type enseignant | Select (`type_enseignant`) | **Oui** ★ | — | "Sélectionner" |
| Établissement d'origine | Select (`etablissement_origine`) | **Oui** ★ | — | "Sélectionner" |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| ID | `enseignants.id_enseignant` |
| Nom | `enseignants.nom_enseignant` |
| Prénom | `enseignants.prenom_enseignant` |
| Email | `enseignants.email_enseignant` |
| Tél. | `enseignants.telephone_enseignant` |
| Grade | `grade.lib_grade` (JOIN via `avoir`) |
| Spécialité | `specialite.lib_specialite` (JOIN) |
| Type | `type_enseignant.libelle` (JOIN) |
| Étab. | `etablissement_origine.libelle_court` (JOIN) |
| Actions | Modifier / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert | POST → crée `enseignants` + `avoir` (grade) |
| Modifier | Vert | Met à jour l'enseignant |
| Supprimer | Rouge (avec `confirm()`) | Supprime (vérifie dépendances : jury, affectation) |
| Rechercher / Exporter / Imprimer | Bleu | Actions standard |

**RÈGLES MÉTIER :**
- Le grade est lié via la table d'association `avoir` (un enseignant peut avoir un grade).
- La spécialité est liée directement via `id_specialite`.
- L'établissement d'origine peut être universitaire ou entreprise.
- Types d'enseignant : "Administratif" (1) ou "Simple" (2).
  - Administratif = responsable de filière/niveau/commission.
  - Simple = sans responsabilité administrative.
- La suppression vérifie que l'enseignant n'est pas membre d'un jury actif ou affecté à un rapport.
- Grades disponibles : Assistant (AS), Maître assistant (MA), Maître de conférence (MC), Professeur titulaire (PT).

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : "Enseignant enregistré avec succès".
- Erreur dépendance : "Impossible de supprimer : cet enseignant est affecté à un jury/rapport".

**LA ROUTE :** `?page=maj_enseignant`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée complète.
- Réinjection circulaire pour modification.
- Connexion avec `SOUT_COMPOS_JURY` (les enseignants sont utilisés dans la composition du jury) et `COM_RECEPTION_RAPPORT` (affectation encadrant/directeur).

**Critères de filtrage :** Recherche par nom, grade, spécialité, type.

**Traçabilité :** Oui, logué dans `pister`.

**Sécurité :** Admin uniquement.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ MISE À JOUR ENSEIGNANT                                  [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Nom *                  Prénom *              Genre *                  │   │
│ │ [___________________]  [___________________] [▼ Sélect.]             │   │
│ │                                                                       │   │
│ │ Email *                Téléphone *                                    │   │
│ │ [___________________]  [___________________]                          │   │
│ │                                                                       │   │
│ │ Grade *          Spécialité *       Type *         Étab. origine *   │   │
│ │ [▼ Sélect.]      [▼ Sélect.]       [▼ Sélect.]    [▼ Sélect.]       │   │
│ │                                                                       │   │
│ │                                    [Réinitialiser]  [✔ Enregistrer]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│ID│Nom       │Prénom    │Email    │Tél.    │Grade │Spé.│Type│Étab│Act. │   │
│ │──┼───┼──────────┼─────────┼─────────┼────────┼──────┼────┼────┼────┼─────│   │
│ │ ☐│1 │Brou      │Patrice  │b@ufhb.ci│07000000│MC    │Info│Adm │UFHB│ ✏🗑│   │
│ │ ☐│2 │Soro      │Emeric   │s@ufhb.ci│07111111│MA    │SI  │Sim │UFHB│ ✏🗑│   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur 65 entrées              [◀ Préc.][1][2]...[▶]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 7.1.2 Écran : MAJ_PERSONNEL_ADMIN — Mise à jour personnel administratif

**ID & TITRE :** `MAJ_PERSONNEL_ADMIN` — Mise à jour personnel administratif

**OBJECTIF :** Gérer le référentiel du personnel administratif (secrétaires, responsables scolarité, chargés de communication, etc.).

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire + Tableau).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Nom | Texte | **Oui** ★ | 50 | Vide |
| Prénom | Texte | **Oui** ★ | 100 | Vide |
| Email | Email | **Oui** ★ | 100 | Vide |
| Téléphone | Texte | **Oui** ★ | 20 | Vide |
| Poste | Texte | **Oui** ★ | 30 | Vide |
| Date d'embauche | Date | **Oui** ★ | 10 | Vide |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| ID | `personnel_admin.id_pers_admin` |
| Nom | `personnel_admin.nom_pers_admin` |
| Prénom | `personnel_admin.prenom_pers_admin` |
| Email | `personnel_admin.email_pers_admin` |
| Tél. | `personnel_admin.tel_pers_admin` |
| Poste | `personnel_admin.poste` |
| Date embauche | `personnel_admin.date_embauche` |
| Actions | Modifier / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert | POST → crée `personnel_admin` |
| Modifier | Vert | Met à jour |
| Supprimer | Rouge | Supprime (vérifie dépendances : candidatures traitées) |
| Rechercher / Exporter / Imprimer | Bleu | Actions standard |

**RÈGLES MÉTIER :**
- Le personnel administratif est référencé dans `candidature_soutenance` (comme traitant de candidatures).
- La suppression vérifie les dépendances.
- Le poste est un texte libre (pas de table de référence).

**ÉTAT DE SORTIE / FEEDBACK :** Notifications standard.

**LA ROUTE :** `?page=maj_personnel_admin`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée standard.
- Réinjection circulaire pour modification.
- Connexion avec `DOSSIER_CANDIDATURE` (le personnel traite les candidatures).

**Critères de filtrage :** Recherche par nom, poste.

**Traçabilité :** Oui.

**Sécurité :** Admin uniquement.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ MISE À JOUR PERSONNEL ADMINISTRATIF                     [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Nom *                  Prénom *                                       │   │
│ │ [___________________]  [___________________]                          │   │
│ │                                                                       │   │
│ │ Email *                Téléphone *                                    │   │
│ │ [___________________]  [___________________]                          │   │
│ │                                                                       │   │
│ │ Poste *                Date d'embauche *                              │   │
│ │ [___________________]  [____/____/____]                               │   │
│ │                                                                       │   │
│ │                                    [Réinitialiser]  [✔ Enregistrer]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│ID │Nom       │Prénom    │Email    │Tél.     │Poste     │Dt Emb.│Act. │   │
│ │──┼────┼──────────┼─────────┼─────────┼─────────┼──────────┼───────┼─────│   │
│ │ ☐│1  │Koua      │Brou     │k@ufhb.ci│07000000 │Secrétaire│01/2020│ ✏🗑 │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 7.2 Sous-écrans de Configuration (PARAM_*)

Tous ces écrans suivent le même modèle CRUD avec segmentation polarisée. Ils sont accessibles depuis le Hub Paramètres Généraux (`PARAM_HUB`).

### 7.2.1 Écran : PARAM_ACTIONS — Actions Système

| Champ | Type | Table source |
|---|---|---|
| Libellé action | Texte | `action_route` / `route_actions` |

**LA ROUTE :** `?page=parametres_generaux&action=actions`

### 7.2.2 Écran : PARAM_ANNEES — Années Académiques

Identique à `ADMIN_ANNEE_ACADEMIQUE` (voir fichier 05). Même écran accessible par deux chemins.

**LA ROUTE :** `?page=parametres_generaux&action=annees_academiques`

### 7.2.3 Écran : PARAM_CRITERES — Critères Évaluation

**OBJECTIF :** Gérer les critères d'évaluation de soutenance et leurs barèmes (pondérations).

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé critère | Texte | **Oui** ★ | `critere_evaluation.lib_critere` |
| Barème (pondération) | Nombre | **Oui** ★ | `bareme_critere.bareme` |
| Année académique | Select | **Oui** ★ | `bareme_critere.id_annee_acad` |

**RÈGLES MÉTIER :**
- Les barèmes sont définis par année académique (permettant de changer les pondérations chaque année).
- Critères existants : Exposé (EX), Réponses aux questions (RQ), Présentation du mémoire (PM), Contenu du mémoire (CM), Résolution du problème (RP).

**LA ROUTE :** `?page=parametres_generaux&action=criteres_evaluation`

### 7.2.4 Écran : PARAM_ECUE — ECUE

**OBJECTIF :** Gérer les Éléments Constitutifs d'Unité d'Enseignement.

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé ECUE | Texte | **Oui** ★ | `ecue.lib_ecue` |
| UE parent | Select | **Oui** ★ | `ecue.id_ue` → `ue` |

**LA ROUTE :** `?page=parametres_generaux&action=ecue`

### 7.2.5 Écran : PARAM_ENTREPRISES — Entreprises

**OBJECTIF :** Gérer la base des entreprises partenaires (lieux de stage).

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé entreprise | Texte | **Oui** ★ | `entreprises.lib_entreprise` |
| Logo | File upload (image) | Non | `entreprises.lien_logo_entreprise` |

**RÈGLES MÉTIER :**
- Les entreprises sont référencées dans `informations_stage` pour les stages étudiants.

**LA ROUTE :** `?page=parametres_generaux&action=entreprises`

### 7.2.6 Écran : PARAM_FONCTIONS — Fonctions Personnel

**OBJECTIF :** Gérer les fonctions des personnels (Chef de département, Directeur, etc.).

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Code fonction | Texte (2 car.) | **Oui** ★ | `fonction.id_fonction` |
| Libellé | Texte | **Oui** ★ | `fonction.lib_fonction` |
| Origine entreprise | Checkbox | Non | `fonction.origine_entreprise` |

**RÈGLES MÉTIER :**
- `origine_entreprise` = 1 → fonction provenant d'une entreprise (Chef de projet, DG, DT).
- `origine_entreprise` = 0 → fonction universitaire (Chef de département, Directeur pédagogique, etc.).

**LA ROUTE :** `?page=parametres_generaux&action=fonctions`

### 7.2.7 Écran : PARAM_FONC_USER — Fonctions Utilisateurs (Types & Groupes)

**OBJECTIF :** Gérer les **types utilisateurs** ET les **groupes utilisateurs** via 2 onglets.

**VUE PRINCIPALE :** Vue à 2 onglets.

*Onglet 1 — Types Utilisateur :*

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé type | Texte | **Oui** ★ | `type_utilisateur.lib_type_utilisateur` |

*Onglet 2 — Groupes Utilisateur :*

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé groupe | Texte | **Oui** ★ | `groupe_utilisateur.lib_GU` |
| Type utilisateur | Select | **Oui** ★ | `groupe_utilisateur.id_type_utilisateur` → `type_utilisateur` |

**RÈGLES MÉTIER :**
- L'onglet affiché dépend du paramètre `tab` dans l'URL (`types` ou `groupes`).
- Un groupe est obligatoirement rattaché à un type.
- Suppression multiple supportée (checkboxes + bouton suppression groupée).

**LA ROUTE :** `?page=parametres_generaux&action=fonction_utilisateur&tab=types` / `&tab=groupes`

### 7.2.8 Écran : PARAM_ATTRIB — Gestion des Attributions (Permissions + Types/Groupes)

**ID & TITRE :** `PARAM_ATTRIB` — Gestion des Attributions

**OBJECTIF :** **Triple rôle** :
1. Créer/gérer les **types utilisateur** (onglet Types)
2. Créer/gérer les **groupes utilisateur** (onglet Groupes)
3. Définir les **permissions CRUD** (Voir, Créer, Modifier, Supprimer) pour chaque groupe sur chaque fonctionnalité (onglet Permissions)

**VUE PRINCIPALE :** Vue à 3 onglets.

*Onglet 1 — Types Utilisateur :*

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé type | Texte | **Oui** ★ | `type_utilisateur.lib_type_utilisateur` |

*Onglet 2 — Groupes Utilisateur :*

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé groupe | Texte | **Oui** ★ | `groupe_utilisateur.lib_GU` |
| Type utilisateur | Select | **Oui** ★ | `groupe_utilisateur.id_type_utilisateur` → `type_utilisateur` |

*Onglet 3 — Matrice de permissions :*

| Axe | Source |
|---|---|
| Lignes | `fonctionnalites` (toutes les fonctionnalités) |
| Colonnes | `peut_voir`, `peut_creer`, `peut_modifier`, `peut_supprimer` |
| Filtre | `groupe_utilisateur` (select pour choisir le groupe) |

**ACTIONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Ajouter type/groupe | Vert | POST → crée dans la table correspondante |
| Enregistrer permissions | Vert | POST → met à jour `permissions` pour le groupe sélectionné |
| Supprimer (multi) | Rouge | Suppression groupée |

**RÈGLES MÉTIER :**
- Les 3 actions (types, groupes, permissions) sont gérées dans le même écran via des onglets.
- Un groupe est rattaché à un type. La suppression d'un type vérifie les groupes dépendants.
- Chaque combinaison (groupe, fonctionnalité) est unique.
- Le changement de groupe dans l'onglet Permissions recharge la matrice.

**LA ROUTE :** `?page=parametres_generaux&action=gestion_attribution` (+ `&tab=types` / `&tab=groupes` / `&tab=permissions`)

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ GESTION DES ATTRIBUTIONS                                [Année A.: 2025-26] │
│                                                                              │
│ [ Types Utilis. ] [ Groupes Utilis. ] [ Permissions ]   ← Onglets          │
│ ═══════════════════════════════════════════════════════                      │
│                                                                              │
│ ─── ONGLET TYPES ─── (affiché si tab=types)                                 │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Libellé type utilisateur *  [_______________________]                │   │
│ │                                         [✔ Ajouter type]             │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │                                                  Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│ ID │ Libellé type                    │ Actions                     │   │
│ │──┼────┼─────────────────────────────────┼─────────────────────────────│   │
│ │ ☐│  4 │ Personnel administratif          │ ✏️ 🗑                      │   │
│ │ ☐│  5 │ Enseignant administratif          │ ✏️ 🗑                      │   │
│ │ ☐│  6 │ Enseignant simple                 │ ✏️ 🗑                      │   │
│ │ ☐│  7 │ Etudiant                          │ ✏️ 🗑                      │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées             [◀ Préc.][1][▶ Suiv.]        │
│                                                                              │
│ ─── ONGLET PERMISSIONS ─── (affiché si tab=permissions)                     │
│ Groupe utilisateur: [▼ Administrateur ▼]                                    │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Fonctionnalité           │ Voir │ Créer │Modifier│Supprimer│         │   │
│ │──────────────────────────┼──────┼───────┼────────┼─────────│         │   │
│ │ Dashboard Scolarité      │  ☑   │  ☑    │   ☑    │    ☑    │         │   │
│ │ Gestion Étudiants        │  ☑   │  ☐    │   ☐    │    ☐    │         │   │
│ │  ├─ Mise à jour étud.   │  ☑   │  ☑    │   ☑    │    ☑    │         │   │
│ │  ├─ Inscription étud.   │  ☑   │  ☑    │   ☑    │    ☑    │         │   │
│ │  └─ Saisie moyennes     │  ☑   │  ☑    │   ☑    │    ☑    │         │   │
│ │ ...                      │  ... │  ...  │   ...  │   ...   │         │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                               [✔ Enregistrer les permissions]│
└──────────────────────────────────────────────────────────────────────────────┘
```

### 7.2.9 Écran : PARAM_GRADES — Grades Enseignants

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Code grade | Texte (2 car.) | **Oui** ★ | `grade.id_grade` |
| Libellé grade | Texte | **Oui** ★ | `grade.lib_grade` |

**LA ROUTE :** `?page=parametres_generaux&action=grades`

### 7.2.10 Écran : PARAM_MESSAGES — Messages Système

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé message | Texte | **Oui** ★ | `messages.lib_message` |
| Contenu | Textarea | **Oui** ★ | `messages.contenu_message` |
| Type message | Select | **Oui** ★ | `messages.type_message` |

**LA ROUTE :** `?page=parametres_generaux&action=messages`

### 7.2.11 Écran : PARAM_NIV_ACCES — Niveaux Accès

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé niveau | Texte | **Oui** ★ | `niveau_acces_donnees.lib_niveau_acces_donnees` |

Valeurs existantes : "Lecture seule", "Écriture".

**LA ROUTE :** `?page=parametres_generaux&action=niveaux_acces`

### 7.2.12 Écran : PARAM_NIV_APPRO — Niveaux Approbation (Workflow)

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé niveau approbation | Texte | **Oui** ★ | `niveau_approbation.lib_approb` |

**LA ROUTE :** `?page=parametres_generaux&action=niveaux_approbation`

### 7.2.13 Écran : PARAM_NIV_ETUDE — Niveaux Étude (M1/M2)

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé niveau | Texte | **Oui** ★ | `niveau_etude.lib_niv_etude` |
| Responsable (enseignant) | Select | Non | `niveau_etude.id_enseignant` → `enseignants` |
| Montant scolarité | Nombre (décimal) | **Oui** ★ | `niveau_etude.montant_scolarite` |
| Montant inscription | Nombre (décimal) | **Oui** ★ | `niveau_etude.montant_inscription` |

**RÈGLES MÉTIER :**
- Master 1 : scolarité 975 000 FCFA, inscription 450 000 FCFA.
- Master 2 : scolarité 1 025 000 FCFA, inscription 450 000 FCFA.
- Le responsable de niveau est un enseignant (référence à `enseignants`).

**LA ROUTE :** `?page=parametres_generaux&action=niveaux_etude`

### 7.2.14 Écran : PARAM_SALLES — Salles

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé salle | Texte | **Oui** ★ | `salles.lib_salle` |
| Capacité | Nombre | Non | `salles.capacite` (si existant) |

**LA ROUTE :** `?page=parametres_generaux&action=salles`

### 7.2.15 Écran : PARAM_SEMESTRES — Semestres

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé semestre | Texte | **Oui** ★ | `semestre.lib_semestre` |
| Niveau d'étude | Select | **Oui** ★ | `semestre.id_niv_etude` → `niveau_etude` |

**LA ROUTE :** `?page=parametres_generaux&action=semestres`

### 7.2.16 Écran : PARAM_SPECIALITES — Spécialités

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé spécialité | Texte | **Oui** ★ | `specialite.lib_specialite` |

**LA ROUTE :** `?page=parametres_generaux&action=specialites`

### 7.2.17 Écran : PARAM_STATUT_JURY — Statuts Jury

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Code qualité jury | Texte (2 car.) | **Oui** ★ | `qualite_jury.code_qltjury` |
| Libellé rôle | Texte | **Oui** ★ | `qualite_jury.lib_role` |

Valeurs existantes : Président (PJ), Directeur mémoire (DM), Examinateur (EX), Encadrant (EN), Maître de stage (MS).

**LA ROUTE :** `?page=parametres_generaux&action=statut_jury`

### 7.2.18 Écran : PARAM_TRAITEMENTS — Traitements Menu (Legacy)

**OBJECTIF :** Gérer la table legacy du menu de navigation.

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| ID traitement | Auto-incrémenté | — | `traitement_legacy.id_traitement` |
| Code page | Texte | **Oui** ★ | `traitement_legacy.code_page` |
| Libellé traitement | Texte | **Oui** ★ | `traitement_legacy.lib_traitement` |
| Icône | Texte (classe CSS) | Non | `traitement_legacy.icone` |
| Ordre | Nombre | **Oui** ★ | `traitement_legacy.ordre` |

**RÈGLES MÉTIER :**
- C'est l'ancien système de menu. Les droits d'accès au menu sont définis via `rattacher_legacy` (association entre groupes utilisateurs et traitements).

**LA ROUTE :** `?page=parametres_generaux&action=traitements`

### 7.2.19 Écran : PARAM_UE — Unités d'Enseignement

| Champ | Type | Obligatoire | Table source |
|---|---|---|---|
| Libellé UE | Texte | **Oui** ★ | `ue.lib_ue` |
| Semestre | Select | **Oui** ★ | `ue.id_semestre` → `semestre` |

**LA ROUTE :** `?page=parametres_generaux&action=ue`

---

**MODÈLE COMMUN À TOUS LES SOUS-ÉCRANS PARAM_*:**

Chaque sous-écran PARAM_* suit exactement la même structure :

```
┌──────────────────────────────────────────────────────────────────────────────┐
│ [NOM DU PARAMÈTRE]                                      [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [Champ 1]  [Champ 2]  [Champ 3 si applicable]                       │   │
│ │                                                                       │   │
│ │                                    [Réinitialiser]  [✔ Enregistrer]   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]             [📤 Export]   [🖨 Impr.]   Afficher: [▼10]    │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│ ID │[Col1]│[Col2]│...│Actions│                                     │   │
│ │──┼────┼──────┼──────┼───┼───────│                                     │   │
│ │ ☐│ 1  │ Val1 │ Val2 │...│ ✏️ 🗑 │                                     │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

- **Pôle supérieur** (statique) : formulaire de saisie.
- **Pôle inférieur** (défilant) : tableau avec recherche, pagination, export, impression.
- **Barre d'outils** : Afficher [▼10], [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)].
- **Actions** : Enregistrer (vert), Modifier (vert), Supprimer (rouge avec confirmation), Rechercher/Exporter/Imprimer (bleu).
- **Réinjection circulaire** : clic sur ✏️ → données remontent dans le formulaire, bouton passe de "Enregistrer" à "Modifier".
- **Protection CSRF** : token dans chaque formulaire.
- **Traçabilité** : chaque action logguée dans `pister`.
- **Sécurité** : admin uniquement. Vérification peut_voir/peut_creer/peut_modifier/peut_supprimer.

(End of file - total 549 lines)

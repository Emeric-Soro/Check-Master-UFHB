# 6. ADMINISTRATION PLATEFORME — Paramétrage & Sécurité

---

## 6.3 Sous-menu : ADM_PARAMETRAGE — Paramétrage

### 6.3.1 Écran : PARAM_HUB — Paramètres Généraux

**ID & TITRE :** `PARAM_HUB` — Paramètres Généraux

**OBJECTIF :** Hub centralisant l'accès à tous les paramètres généraux du système (actions, années, critères, ECUE, entreprises, fonctions, grades, messages, niveaux, salles, semestres, spécialités, statuts jury, traitements, UE).

**VUE PRINCIPALE :** Dashboard Hub (grille de cartes/tuiles cliquables).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Tuile | Description | Lien |
|---|---|---|
| Actions Système | Gérer les actions système | `?page=parametres_generaux&action=actions` |
| Années Académiques | Gérer les années | `?page=parametres_generaux&action=annees_academiques` |
| Critères Évaluation | Gérer les critères et barèmes | `?page=parametres_generaux&action=criteres_evaluation` |
| ECUE | Gérer les éléments constitutifs d'UE | `?page=parametres_generaux&action=ecue` |
| Entreprises | Gérer la base des entreprises | `?page=parametres_generaux&action=entreprises` |
| Fonctions Personnel | Gérer les fonctions | `?page=parametres_generaux&action=fonctions` |
| Fonctions Utilisateurs | Gérer les rôles utilisateurs | `?page=parametres_generaux&action=fonction_utilisateur` |
| Gestion Attributions | Gérer les permissions | `?page=parametres_generaux&action=gestion_attribution` |
| Grades Enseignants | Gérer les grades | `?page=parametres_generaux&action=grades` |
| Messages Système | Gérer les templates de messages | `?page=parametres_generaux&action=messages` |
| Niveaux Accès | Gérer les niveaux d'accès | `?page=parametres_generaux&action=niveaux_acces` |
| Niveaux Approbation | Gérer les workflow d'approbation | `?page=parametres_generaux&action=niveaux_approbation` |
| Niveaux Étude | Gérer les niveaux (M1/M2) | `?page=parametres_generaux&action=niveaux_etude` |
| Salles | Gérer les salles de soutenance | `?page=parametres_generaux&action=salles` |
| Semestres | Gérer les semestres | `?page=parametres_generaux&action=semestres` |
| Spécialités | Gérer les spécialités | `?page=parametres_generaux&action=specialites` |
| Statuts Jury | Gérer les rôles du jury | `?page=parametres_generaux&action=statut_jury` |
| Traitements Menu | Gérer le menu legacy | `?page=parametres_generaux&action=traitements` |
| UE | Gérer les Unités d'Enseignement | `?page=parametres_generaux&action=ue` |

**ACTIONS & BOUTONS :** Chaque tuile est un lien vers le sous-écran correspondant.

**RÈGLES MÉTIER :**
- Chaque tuile affiche un compteur du nombre d'éléments existants.
- Les tuiles sont organisées par catégorie fonctionnelle.

**ÉTAT DE SORTIE / FEEDBACK :** Navigation (lecture seule).

**LA ROUTE :** `?page=parametres_generaux`

**COMPORTEMENT DE LA PAGE :**
- Grille de tuiles cliquables. Pas de segmentation polarisée.
- Chaque clic redirige vers le sous-écran CRUD spécifique.
- Sur mobile : tuiles en colonnes de 2 puis de 1.

**Critères de filtrage :** Aucun.

**Traçabilité :** Accès logué.

**Sécurité :** Admin uniquement.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ PARAMÈTRES GÉNÉRAUX                                     [Année A.: 2025-26] │
│                                                                              │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐          │
│ │⚡Actions │ │📅Années  │ │📋Critères│ │🧩 ECUE   │ │🏢Entrepr.│          │
│ │  Système │ │ Académ.  │ │ Évaluat. │ │          │ │          │          │
│ │   (5)    │ │   (25)   │ │   (5)    │ │   (0)    │ │   (12)   │          │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘          │
│                                                                              │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐          │
│ │💼Fonctions│ │🏷️Fonc.  │ │🔑Attrib. │ │🏅Grades  │ │✉️Messages│          │
│ │Personnel │ │ Utilisat.│ │(Permiss.)│ │Enseign.  │ │ Système  │          │
│ │   (13)   │ │   (0)    │ │  (40+)   │ │   (4)    │ │   (0)    │          │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘          │
│                                                                              │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐          │
│ │🔒Niv.    │ │📊Niv.    │ │📚Niveaux │ │🚪Salles  │ │📆Semestres│          │
│ │ Accès    │ │ Approb.  │ │ Étude    │ │          │ │          │          │
│ │   (2)    │ │   (0)    │ │   (2)    │ │   (3+)   │ │   (4)    │          │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘ └──────────┘          │
│                                                                              │
│ ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐                        │
│ │🎓Spécial.│ │🛡️Statuts │ │📋Traitem.│ │📖 UE     │                        │
│ │          │ │  Jury    │ │  Menu    │ │          │                        │
│ │   (6+)   │ │   (5)    │ │  (48)    │ │   (0)    │                        │
│ └──────────┘ └──────────┘ └──────────┘ └──────────┘                        │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 6.3.2 Écran : PARAM_SPEC — Paramètres Spécifiques

**ID & TITRE :** `PARAM_SPEC` — Paramètres Spécifiques

**OBJECTIF :** Gérer les paramètres dynamiques et fréquemment mis à jour : UE, ECUE, Salles, Menus. Contrairement aux paramètres généraux (statiques), ces éléments sont modifiés régulièrement.

**VUE PRINCIPALE :** Vue à onglets (tabs) avec un CRUD par onglet.

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Onglet | Table source | Champs |
|---|---|---|
| UE | `ue` | id_ue, lib_ue, id_semestre |
| ECUE | `ecue` | id_ecue, lib_ecue, id_ue |
| Salles | `salles` | id_salle, lib_salle, capacite |
| Menu | `fonctionnalites` | code, lib, url, icône, ordre, catégorie |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Ajouter | Vert | POST → crée l'élément dans la table correspondante |
| Modifier | Bleu | Réinjection circulaire |
| Supprimer | Rouge (alerte) | Suppression avec vérification de dépendances |

**RÈGLES MÉTIER :**
- Chaque onglet est un mini-CRUD indépendant avec segmentation polarisée.
- Les suppressions vérifient les dépendances (ex : ne pas supprimer une UE qui a des ECUE).

**ÉTAT DE SORTIE / FEEDBACK :** Notifications standard.

**LA ROUTE :** `?page=parametres_specifiques`

**COMPORTEMENT DE LA PAGE :**
- Vue à onglets. Clic sur un onglet → charge le CRUD correspondant.
- Chaque onglet suit la segmentation polarisée (formulaire en haut, tableau en bas).

**Critères de filtrage :** Recherche dans chaque onglet.

**Traçabilité :** Oui, logué.

**Sécurité :** Admin uniquement.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ PARAMÈTRES SPÉCIFIQUES                                  [Année A.: 2025-26] │
│                                                                              │
│ [  UE  ] [ ECUE ] [ Salles ] [ Menu ]   ← Onglets                          │
│ ════════                                                                     │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Libellé UE *              Semestre *                                 │   │
│ │ [_______________________] [▼ Sélect. semestre]                       │   │
│ │                                                  [✔ Ajouter]          │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│  ID   │     Libellé UE      │   Semestre    │   Actions              │   │
│ │──┼────────┼─────────────────────┼──────────────┼────────────────────────│   │
│ │ ☐│  1    │  Systèmes d'info    │ Semestre 1   │    ✏️  🗑              │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

## 6.4 Sous-menu : ADM_SECURITE — Sécurité

### 6.4.1 Écran : SYS_UTILISATEURS — Gestion Utilisateurs

**ID & TITRE :** `SYS_UTILISATEURS` — Gestion Utilisateurs

**OBJECTIF :** Centraliser la création, modification et gestion de tous les comptes utilisateurs du système (aucun compte ne doit être créé ailleurs).

**VUE PRINCIPALE :** Segmentation Polarisée (Formulaire + Tableau).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie (Pôle Supérieur) :*

| Champ | Type | Obligatoire | Taille max | Valeur par défaut |
|---|---|---|---|---|
| Nom utilisateur | Texte | **Oui** ★ | 200 | Vide |
| Type utilisateur | Select (`type_utilisateur`) | **Oui** ★ | — | "Sélectionner" |
| Groupe utilisateur | Select (`groupe_utilisateur`, filtré par type) | **Oui** ★ | — | "Sélectionner" |
| Niveau accès données | Select (`niveau_acces_donnees`) | **Oui** ★ | — | "Sélectionner" |
| Statut | Select ("Actif", "Inactif") | **Oui** ★ | — | "Actif" |
| Login | Texte (email) | **Oui** ★ | 60 | Vide |
| Mot de passe | Password | **Oui** ★ (création) | 255 | Vide |
| Confirmation mot de passe | Password | **Oui** ★ (création) | 255 | Vide |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| ID | `utilisateur.id_utilisateur` |
| Nom | `utilisateur.nom_utilisateur` |
| Type | `type_utilisateur.lib_type_utilisateur` (JOIN) |
| Groupe | `groupe_utilisateur.lib_GU` (JOIN) |
| Niveau accès | `niveau_acces_donnees.lib_niveau_acces_donnees` (JOIN) |
| Statut | `utilisateur.statut_utilisateur` (badge) |
| Login | `utilisateur.login_utilisateur` |
| Actions | Modifier / Désactiver / Réinitialiser MDP |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Créer | Vert | POST → crée `utilisateur` avec mot de passe hashé (bcrypt) |
| Modifier | Vert | Met à jour l'utilisateur (sans toucher au MDP sauf si renseigné) |
| Désactiver | Orange (alerte) | Change `statut_utilisateur` = 'Inactif' |
| Réactiver | Vert | Change `statut_utilisateur` = 'Actif' |
| Réinitialiser MDP | Bleu | Génère un nouveau mot de passe temporaire |
| Rechercher / Exporter / Imprimer | Bleu | Actions standard |

**RÈGLES MÉTIER :**
- **Règle métier de référence** : Aucun compte utilisateur ne doit être créé automatiquement ailleurs. TOUS les comptes passent par cet écran.
- **Auto-remplissage** : Quand le type utilisateur est sélectionné, le select "Groupe utilisateur" ne propose que les groupes rattachés à ce type.
  - Type "Personnel administratif" (4) → Groupes : Administrateur, Secrétaire, Chargée de communication, Responsable scolarité
  - Type "Enseignant administratif" (5) → Groupes : Responsable Filière, Responsable niveau, Commission de validation
  - Type "Enseignant simple" (6) → Groupe : Enseignant sans responsabilité administrative
  - Type "Etudiant" (7) → Groupe : Etudiant
- Si le type est "Etudiant", le nom est sélectionné parmi les étudiants existants (select au lieu de texte libre).
- Si le type est "Enseignant", le nom est sélectionné parmi les enseignants existants.
- Le mot de passe est hashé en bcrypt (`$2y$10$...`).
- Le login doit être unique et il est généré automatiquement, on peut tout de même le modifier, la première lettre est la première lettre du nom, le reste est le prénom.
- Niveaux d'accès : "Lecture seule" (4), "Écriture" (5).

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès création : "Utilisateur créé avec succès" (vert).
- Erreur login dupliqué : "Ce login existe déjà" (rouge).

**LA ROUTE :** `?page=gestion_utilisateurs`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée complète.
- Le select Type → filtre dynamiquement le select Groupe.
- Pour type Étudiant : le champ "Nom utilisateur" devient un select alimenté par la table `etudiants`.
- Pour type Enseignant : le champ "Nom utilisateur" devient un select alimenté par la table `enseignants`.
- Réinjection circulaire pour modification.
- Connexion avec `PARAM_ATTRIB` pour attribuer les permissions au groupe.

**Critères de filtrage :** Type, groupe, statut, recherche par nom/login.

**Traçabilité :** Oui, toute création/modification/désactivation logguée dans `pister`.

**Sécurité :** Admin uniquement. Mot de passe JAMAIS affiché en clair. Le champ MDP est vide en mode modification.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ GESTION DES UTILISATEURS                                [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Nom utilisateur *        Type utilisateur *     Groupe *             │   │
│ │ [___________________]    [▼ Sélect. type]       [▼ Sélect. groupe]   │   │
│ │                                                                       │   │
│ │ Niveau accès *           Statut *               Login *              │   │
│ │ [▼ Sélect.]             [▼ Actif]              [_______________]    │   │
│ │                                                                       │   │
│ │                                    [Réinitialiser]  [✔ Créer]         │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ☐│ID │Nom          │Type         │Groupe    │Accès  │Statut │Login│Act. │   │
│ │──┼────┼─────────────┼─────────────┼──────────┼───────┼───────┼─────┼─────│   │
│ │ ☐│5  │Koua Brou    │Ens. admin.  │Admin     │Écritu.│🟢Actif│k@g  │✏🔧🔒│   │
│ │ ☐│110 │Irie A.J.J.  │Étudiant     │Étudiant  │Écritu.│🟢Actif│iadj │✏🔧🔒│   │
│ │ ☐│111 │WAH MEDARD   │Ens. admin.  │Admin     │Écritu.│🟢Actif│wmed │✏🔧🔒│   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur 3 entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 6.4.2 Écran : SYS_AUDIT — Piste Audit

**ID & TITRE :** `SYS_AUDIT` — Piste Audit

**OBJECTIF :** Consulter le journal d'audit de toutes les actions effectuées dans le système (connexions, créations, modifications, suppressions, erreurs).

**VUE PRINCIPALE :** Tableau avec filtres avancés (pas de formulaire CRUD, lecture seule).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| ID Piste | `pister.id_piste` |
| Utilisateur | `utilisateur.nom_utilisateur` (JOIN) |
| Action | `pister.action` |
| Statut action | `pister.statut_action` (badge Succès=vert, Erreur=rouge) |
| Table concernée | `pister.nom_table` |
| Date/Heure | `pister.date_creation` |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Filtrer | Bleu | Applique les filtres sélectionnés |
| Réinitialiser filtres | Gris | Remet les filtres à zéro |
| Exporter | Bleu | Export CSV du journal filtré |
| Imprimer | Bleu | Impression |

**RÈGLES MÉTIER :**
- Page en lecture seule. Aucune suppression possible des logs d'audit.
- Les logs sont générés automatiquement par le système (via `pister`).
- Actions types : Connexion, Déconnexion, Création, Modification, Suppression, acces_refuse.
- Chaque ligne contient l'ID de l'utilisateur, permettant la traçabilité complète.

**ÉTAT DE SORTIE / FEEDBACK :** Aucune action de modification.

**LA ROUTE :** `?page=piste_audit`

**COMPORTEMENT DE LA PAGE :**
- Pas de segmentation polarisée classique (pas de formulaire de saisie).
- Zone de filtres avancés en haut + tableau défilant en bas.
- Tri par date décroissante par défaut.
- Pagination côté serveur.

**Critères de filtrage :**
- Utilisateur (select multi).
- Action (select multi : Connexion, Création, Modification, etc.).
- Statut (Succès / Erreur).
- Table concernée.
- Plage de dates (date début / date fin).
- Logique : ET entre les filtres.

**Traçabilité :** Cette page EST le journal de traçabilité.

**Sécurité :** Admin uniquement. Les logs ne peuvent être ni modifiés ni supprimés.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ PISTE D'AUDIT                                           [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Utilisateur:        Action:            Statut:         Table:        │   │
│ │ [▼ Tous]            [▼ Toutes]         [▼ Tous]        [▼ Toutes]    │   │
│ │                                                                       │   │
│ │ Date début:         Date fin:                                        │   │
│ │ [____/____/____]    [____/____/____]                                  │   │
│ │                                                                       │   │
│ │                        [Réinitialiser]  [🔍 Filtrer]                  │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ [🔍 Rech.]  [📤 Export]  [🖨 Impr.]              Afficher: [▼10]     │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ID │Utilisateur  │Action       │Statut    │Table      │Date/Heure    │   │
│ │────┼─────────────┼─────────────┼──────────┼───────────┼──────────────│   │
│ │ 68 │Koua Brou    │Connexion    │🟢Succès  │utilisateur│22/02 01:00   │   │
│ │ 67 │Koua Brou    │Modification │🟢Succès  │etudiants  │21/02 23:45   │   │
│ │ 66 │Wah Medard   │acces_refuse │🔴Erreur  │permission │21/02 22:30   │   │
│ │ 65 │Koua Brou    │Création     │🟢Succès  │annee_acad.│21/02 20:00   │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur 68 entrées              [◀ Préc.][1][2]...[7][▶]    │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 6.4.3 Écran : SYS_BACKUP — Sauvegarde / Restauration

**ID & TITRE :** `SYS_BACKUP` — Sauvegarde / Restauration

**OBJECTIF :** Permettre à l'administrateur de créer des sauvegardes de la base de données et de restaurer le système à un état antérieur en cas de besoin.

**VUE PRINCIPALE :** Segmentation Polarisée (Actions en haut + Historique des sauvegardes en bas).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Actions (Pôle Supérieur) :*

| Action | Description |
|---|---|
| Sauvegarde complète | Exporte l'intégralité de la base de données en fichier SQL |
| Sauvegarde sélective | Exporte uniquement certaines tables |
| Restauration | Importe un fichier SQL pour restaurer la base |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| Nom fichier | Nom du fichier de sauvegarde |
| Date | Date de création |
| Taille | Taille du fichier |
| Type | Complète / Sélective |
| Actions | Télécharger / Restaurer / Supprimer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| 💾 Créer une sauvegarde | Vert | Génère un dump SQL complet |
| 📥 Restaurer | Bleu | Upload + exécution d'un fichier SQL |
| 📥 Télécharger | Bleu | Télécharge le fichier de sauvegarde |
| 🗑 Supprimer | Rouge (alerte) | Supprime le fichier de sauvegarde (avec confirmation) |

**RÈGLES MÉTIER :**
- La restauration est une opération DESTRUCTRICE : confirmation double obligatoire (confirm + saisie d'un mot de confirmation).
- Les sauvegardes sont stockées dans un répertoire sécurisé du serveur.
- Le nom du fichier contient la date/heure de création.
- Seul l'administrateur peut effectuer ces opérations.

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès sauvegarde : "Sauvegarde créée avec succès : backup_2026-02-22_010000.sql (5.2 MB)".
- Succès restauration : "Base de données restaurée avec succès à partir de backup_XXXX.sql".
- Erreur restauration : "Erreur lors de la restauration: [détail]".

**LA ROUTE :** `?page=sauvegarde_restauration`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée : zone d'action en haut, historique en bas.
- La restauration nécessite un upload de fichier SQL.
- Panneau de confirmation pour les opérations destructrices.

**Critères de filtrage :** Date, type de sauvegarde.

**Traçabilité :** Oui, chaque sauvegarde/restauration logguée dans `pister`.

**Sécurité :** Admin uniquement. Opérations critiques avec double confirmation.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ SAUVEGARDE ET RESTAURATION                              [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ ┌───────────────────────┐  ┌───────────────────────────────────────┐  │   │
│ │ │ 💾 CRÉER UNE          │  │ 📥 RESTAURER                         │  │   │
│ │ │    SAUVEGARDE          │  │                                      │  │   │
│ │ │                        │  │ Fichier SQL : [Parcourir...]         │  │   │
│ │ │ [Sauvegarde complète]  │  │                                      │  │   │
│ │ │ [Sauvegarde sélective] │  │ [⚠ Restaurer la base]                │  │   │
│ │ └───────────────────────┘  └───────────────────────────────────────┘  │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │          HISTORIQUE DES SAUVEGARDES                                    │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ Nom fichier                     │Date       │Taille│Type    │Actions  │   │
│ │─────────────────────────────────┼───────────┼──────┼────────┼─────────│   │
│ │ backup_2026-02-22_010000.sql    │22/02/2026 │5.2MB │Complète│📥 ⏪ 🗑 │   │
│ │ backup_2026-02-15_140000.sql    │15/02/2026 │4.8MB │Complète│📥 ⏪ 🗑 │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

---

### 6.4.4 Écran : SYS_HISTORIQUE — Historique et Archivage

**ID & TITRE :** `SYS_HISTORIQUE` — Historique et Archivage

**OBJECTIF :** Gérer l'archivage des données anciennes (soutenances passées, étudiants diplômés, rapports validés) et permettre leur consultation sans encombrer les tables de travail.

**VUE PRINCIPALE :** Segmentation Polarisée (Zone d'import/archivage + Tableau des archives).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Actions (Pôle Supérieur) :*

| Action | Description |
|---|---|
| Archiver une année | Déplace les données d'une année académ. vers les archives |
| Importer des données | Upload de fichiers pour import en masse |
| Consulter les archives | Navigation dans les données archivées |

*Données affichées (Tableau) :*

| Colonne | Source |
|---|---|
| ID Archive | `pdf_cr_pv_rapetd.id_arch` ou table d'archivage |
| Libellé | Description de l'archive |
| Année concernée | Année académique archivée |
| Date archivage | Date de l'opération |
| Nb d'éléments | Nombre de lignes archivées |
| Actions | Consulter / Exporter / Restaurer |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Archiver | Vert | Déplace les données vers les archives |
| Importer | Bleu | Upload et import en masse |
| Consulter | Bleu | Ouvre la vue détaillée de l'archive |
| Exporter | Bleu | Export CSV/Excel de l'archive |

**RÈGLES MÉTIER :**
- L'archivage ne supprime PAS les données, il les marque comme archivées.
- Les données archivées ne sont plus visibles dans les écrans de travail courants.
- Elles restent consultables depuis cet écran.
- L'import permet de charger des données historiques (ex : anciens étudiants).

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès archivage : "Données de l'année 2023-2024 archivées avec succès (342 étudiants, 65 rapports)".

**LA ROUTE :** `?page=admin_historique`

**COMPORTEMENT DE LA PAGE :**
- Segmentation polarisée.
- Zone d'actions d'archivage en haut.
- Tableau des archives existantes en bas.
- Panneau latéral pour consulter le contenu d'une archive.

**Critères de filtrage :** Année, type de données.

**Traçabilité :** Oui, archivages logués.

**Sécurité :** Admin uniquement.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────┐
│ HISTORIQUE ET ARCHIVAGE                                 [Année A.: 2025-26] │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │ Année à archiver: [▼ Sélect. année]                                  │   │
│ │                                                                       │   │
│ │ Importer des données: [Parcourir...]                                 │   │
│ │                                                                       │   │
│ │                            [📥 Importer]  [📦 Archiver l'année]       │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│                                                                              │
│ ┌────────────────────────────────────────────────────────────────────────┐   │
│ │          ARCHIVES EXISTANTES                                          │   │
│ ├────────────────────────────────────────────────────────────────────────┤   │
│ │ ID │Libellé              │Année    │Date archiv.│Nb élem│Actions      │   │
│ │────┼─────────────────────┼─────────┼────────────┼───────┼─────────────│   │
│ │ 1  │Soutenances 2023-24  │2023-2024│01/10/2024  │  45   │👁 📤 ⏪     │   │
│ │ 2  │Étudiants diplômés   │2022-2023│01/10/2023  │  38   │👁 📤 ⏪     │   │
│ └────────────────────────────────────────────────────────────────────────┘   │
│ Affichage de 1 à 10 sur N entrées               [◀ Préc.][1][▶ Suiv.]      │
└──────────────────────────────────────────────────────────────────────────────┘
```

(End of file - total 534 lines)

## CONTRAINTTES ABSOLUES
- ❌ NE PAS modifier les contrôleurs dans `app/controllers/`
- ❌ NE PAS modifier les routes dans `config/routes.php`
- ❌ NE PAS modifier les modèles dans `app/models/`
- ✅ UNIQUEMENT créer de nouveaux fichiers dans views/pages`
# DESCRIPTION EXHAUSTIVE DES ÉCRANS — CHECKMASTER
## PARTIE 3 : ADMINISTRATION PLATEFORME & PROFIL

---

## ═══════════════════════════════════════════
## MENU : ADMIN_PLATEFORME
## ═══════════════════════════════════════════

---

### 📊 ÉCRAN ADM-01 : Dashboard Admin

**ID & TITRE :** `ADM_DASHBOARD` — Dashboard

**OBJECTIF :** Donner à l'administrateur une vision globale de l'état du système : utilisateurs actifs, activité récente, statistiques clés et alertes système.

**VUE PRINCIPALE :** Dashboard (Widgets + Graphiques + Activités récentes)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Donnée | Source | Calcul |
|---|---|---|
| Utilisateurs actifs | `utilisateur` WHERE statut='Actif' | COUNT |
| Utilisateurs inactifs | `utilisateur` WHERE statut='Inactif' | COUNT |
| Total étudiants | `etudiants` | COUNT |
| Total enseignants | `enseignants` | COUNT |
| Année académique active | `annee_academique` (la plus récente) | Affichage |
| Activités récentes | `pister` ORDER BY date_creation DESC LIMIT 10 | Liste |
| Répartition utilisateurs par groupe | `utilisateur` JOIN `groupe_utilisateur` | GROUP BY |
| Connexions récentes | `pister` WHERE action='Connexion' | COUNT par jour |

**ACTIONS & BOUTONS :** Widgets cliquables → redirection vers les sections correspondantes.

**RÈGLES MÉTIER :**
- Seuls les Administrateurs ont accès au dashboard admin complet
- Les Secrétaires ont accès au dashboard secrétariat (`?page=dashboard_secretaire`)
- Log d'accès systématique

**ÉTAT DE SORTIE / FEEDBACK :** Lecture seule.

**LA ROUTE :** `?page=dashboard`

**COMPORTEMENT DE LA PAGE :**
- Chargement via `DashboardController`
- Widgets animés avec compteurs
- Graphiques d'évolution (effectifs, connexions)
- Section "Activités récentes" en temps réel
- Responsive : widgets empilés sur mobile

**Critères de filtrage :** Année académique (automatique)

**Traçabilité :** Oui — `pister` (action: 'Accès', nom_table: 'tableau_de_bord')

**Sécurité :** Accès `ADM_DASHBOARD` (Administrateur, id_GU=5)

---

### 📅 ÉCRAN ADM-02 : Ouverture/Fermeture Année Académique

**ID & TITRE :** `ADMIN_ANNEE_ACADEMIQUE` — Ouverture/Fermeture AC

**OBJECTIF :** Gérer les années académiques du système : créer, ouvrir ou fermer une année académique, ce qui conditionne toutes les opérations du système.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Date début | Date | Oui | 01/09/YYYY |
| Date fin | Date | Oui | 31/07/YYYY+1 |

*Données affichées :*

| Colonne | Source |
|---|---|
| ID | `annee_academique.id_annee_acad` |
| Date début | `annee_academique.date_deb` |
| Date fin | `annee_academique.date_fin` |
| Statut | Calculé (active si la plus récente) |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Créer année | Vert | INSERT dans `annee_academique` |
| Modifier | Bleu | UPDATE des dates |
| Supprimer | Bleu | DELETE (si aucune donnée liée) |

**RÈGLES MÉTIER :**
- La date de fin doit être supérieure à la date de début
- Il ne peut y avoir qu'UNE SEULE année académique active à la fois
- La suppression est impossible si des inscriptions, notes ou candidatures sont liées
- L'année active est affichée en haut à droite sur TOUTES les pages
- Le changement d'année active impacte toutes les données filtrées

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Année académique créée avec succès »
- Erreur : « Les dates sont invalides » / « Impossible de supprimer — des données sont liées »

**LA ROUTE :** `?page=parametres_generaux&action=annees_academiques`

**COMPORTEMENT DE LA PAGE :**
- Formulaire de création/modification en haut
- Liste des années en bas avec la plus récente en surbrillance
- Réinjection circulaire pour modification
- Connectée à TOUTES les pages via l'indicateur en haut à droite

**Critères de filtrage :** Aucun (peu de données)

**Traçabilité :** Oui — `pister` (action: 'Création'/'Modification', nom_table: 'annee_academique')

**Sécurité :** Accès `ADMIN_ANNEE_ACADEMIQUE`. **Administrateur uniquement**. C'est une opération critique.

---

### ⚙️ ÉCRAN ADM-03 : Paramètres Généraux

**ID & TITRE :** `PARAM_HUB` — Paramètres Généraux

**OBJECTIF :** Centraliser la gestion de tous les référentiels statiques du système (données rarement modifiées) : grades, fonctions, groupes utilisateurs, spécialités, niveaux, actions, messages, etc.

**VUE PRINCIPALE :** Interface multi-onglets — chaque onglet est un CRUD de type Segmentation polarisée

**SOUS-ÉCRANS (chacun = un onglet CRUD) :**

| Sous-écran | Code | Table source | Route |
|---|---|---|---|
| Actions Système | `PARAM_ACTIONS` | `action` | `&action=actions` |
| Grades Enseignants | `PARAM_GRADES` | `grade` | `&action=grades` |
| Fonctions Utilisateurs | `PARAM_FONC_USER` | `groupe_utilisateur` | `&action=fonction_utilisateur` |
| Spécialités | `PARAM_SPECIALITES` | `specialite` | `&action=specialites` |
| Niveaux d'Étude | `PARAM_NIV_ETUDE` | `niveau_etude` | `&action=niveaux_etude` |
| Niveaux d'Accès | `PARAM_NIV_ACCES` | `niveau_acces_donnees` | `&action=niveaux_acces` |
| Niveaux d'Approbation | `PARAM_NIV_APPRO` | `niveau_approbation` | `&action=niveaux_approbation` |
| Statuts Jury | `PARAM_STATUT_JURY` | `qualite_jury` | `&action=statut_jury` |
| Entreprises | `PARAM_ENTREPRISES` | `entreprises` | `&action=entreprises` |
| Fonctions Personnel | `PARAM_FONCTIONS` | `fonction` | `&action=fonctions` |
| Messages Système | `PARAM_MESSAGES` | `messages` | `&action=messages` |
| Traitements Menu | `PARAM_TRAITEMENTS` | `traitement_legacy` | `&action=traitements` |
| Gestion Attributions | `PARAM_ATTRIB` | `permissions` | `&action=gestion_attribution` |

**Structure commune de chaque sous-écran :**
- **Pôle supérieur :** Formulaire avec champs selon la table (code, libellé, description…)
- **Pôle inférieur :** Tableau de la table avec boutons Modifier/Supprimer
- **Boutons :** Enregistrer (vert), Modifier (bleu), Supprimer (bleu)

**RÈGLES MÉTIER :**
- Les codes sont uniques dans chaque table
- La suppression vérifie les dépendances (cascade ou blocage)
- Les grades d'enseignant : AS (Assistant), MA (Maître assistant), MC (Maître de conférence), PT (Professeur titulaire)
- Les groupes utilisateurs sont liés aux types utilisateurs
- Les permissions sont gérées en matrice CRUD (peut_voir, peut_creer, peut_modifier, peut_supprimer)
- **Gestion Attributions** est une matrice spéciale : lignes = groupes utilisateurs, colonnes = fonctionnalités, cases = droits CRUD

**ÉTAT DE SORTIE / FEEDBACK :**
- Messages de succès/erreur selon l'opération

**LA ROUTE :** `?page=parametres_generaux` puis `&action={sous-écran}`

**COMPORTEMENT DE LA PAGE :**
- Navigation par onglets ou menu latéral entre les sous-écrans
- Chaque sous-écran suit la philosophie de segmentation polarisée
- Réinjection circulaire pour modification
- Les modifications de permissions se propagent immédiatement

**Critères de filtrage :** Recherche textuelle par libellé dans chaque sous-écran

**Traçabilité :** Oui — pour chaque opération CRUD

**Sécurité :** Accès `PARAM_HUB` + sous-droits par onglet. **Administrateur uniquement.**

---

### 🎛️ ÉCRAN ADM-04 : Paramètres Spécifiques

**ID & TITRE :** `PARAM_SPEC` — Paramètres Spécifiques

**OBJECTIF :** Gérer les paramètres dynamiques fréquemment mis à jour : UE, ECUE, Semestres, Salles, Critères d'évaluation et leurs barèmes.

**VUE PRINCIPALE :** Interface multi-onglets — CRUD pour chaque entité

**SOUS-ÉCRANS :**

| Sous-écran | Code | Table source | Route |
|---|---|---|---|
| Unités d'Enseignement | `PARAM_UE` | `ue` + `semestre` + `niveau_etude` | `&action=ue` |
| ECUE | `PARAM_ECUE` | `ecue` + `ue` | `&action=ecue` |
| Semestres | `PARAM_SEMESTRES` | `semestre` + `niveau_etude` | `&action=semestres` |
| Salles | `PARAM_SALLES` | `salles` | `&action=salles` |
| Critères d'Évaluation | `PARAM_CRITERES` | `critere_evaluation` + `bareme_critere` | `?page=criteres_evaluation` |

**Spécificité Critères d'Évaluation :**

*Champs de saisie :*
| Champ | Type | Obligatoire |
|---|---|---|
| Code critère | Texte (2 car.) | Oui |
| Libellé critère | Texte (100 car.) | Oui |
| Barème par année | Numérique | Oui |
| Année académique | Liste déroulante | Oui |

- Les barèmes peuvent varier d'une année à l'autre (table `bareme_critere`)
- 5 critères définis : Exposé (EX), Réponses (RQ), Présentation (PM), Contenu (CM), Résolution (RP)
- AJAX pour chargement dynamique des critères par année

**RÈGLES MÉTIER :**
- Les UE sont liées aux semestres, eux-mêmes liés aux niveaux d'étude
- Les ECUE sont des sous-composantes des UE
- La modification des barèmes impacte le calcul des notes de soutenance
- Les salles sont utilisées pour la programmation des soutenances

**LA ROUTE :** `?page=parametres_specifiques` ou `?page=criteres_evaluation`

**Traçabilité :** Oui

**Sécurité :** Accès `PARAM_SPEC`. Administrateur uniquement.

---

### 👤 ÉCRAN ADM-05 : Gestion Utilisateurs

**ID & TITRE :** `SYS_UTILISATEURS` — Gestion Utilisateurs

**OBJECTIF :** Centraliser la création, modification et gestion de TOUS les comptes utilisateurs du système. C'est l'UNIQUE point d'entrée pour la création de comptes.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Nom utilisateur | Texte (varchar 200) | Oui | Vide |
| Type utilisateur | Liste déroulante | Oui | — |
| Groupe utilisateur | Liste déroulante (filtrée) | Oui | — |
| Niveau accès données | Liste déroulante | Oui | — |
| Statut | Liste déroulante | Oui | Actif |
| Login | Texte (varchar 60) | Oui | Vide |
| Mot de passe | Mot de passe | Oui (création) | — |

*Sources listes :*
- Type utilisateur → `type_utilisateur` (Personnel admin, Enseignant admin, Enseignant simple, Etudiant)
- Groupe utilisateur → `groupe_utilisateur` **FILTRÉ par type** : si type=Etudiant → seul le groupe "Etudiant" apparaît
- Niveau accès → `niveau_acces_donnees` (Lecture seule, Écriture)

*Auto-remplissage critique :*
- Si type = "Etudiant" → le champ Nom ne propose QUE des étudiants
- Si type = "Enseignant" → le champ Nom ne propose QUE des enseignants
- Si type = "Personnel admin" → le champ Nom ne propose QUE le personnel admin

*Données affichées :*

| Colonne | Source |
|---|---|
| ID | `utilisateur.id_utilisateur` |
| Nom | `utilisateur.nom_utilisateur` |
| Type | JOIN `type_utilisateur` |
| Groupe | JOIN `groupe_utilisateur` |
| Statut | `utilisateur.statut_utilisateur` |
| Login | `utilisateur.login_utilisateur` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Créer utilisateur | Vert | INSERT dans `utilisateur` (mdp hashé bcrypt) |
| Modifier | Bleu | UPDATE + réinjection |
| Désactiver/Activer | Bleu | UPDATE statut 'Actif'↔'Inactif' |
| Envoyer accès | Bleu | Envoi d'email avec reset password via `password_resets` |
| Réinitialiser MDP | Bleu | Génère un token + email |

**RÈGLES MÉTIER :**
- **RÈGLE ABSOLUE** : Aucun compte utilisateur ne doit être créé ailleurs que sur cet écran
- Le login doit être unique (contrainte UNIQUE en base)
- Le mot de passe est hashé en bcrypt ($2y$10$...)
- Le type utilisateur détermine les groupes disponibles
- Le groupe utilisateur détermine les permissions (via table `permissions`)
- Le changement de groupe propage automatiquement les droits du nouveau groupe
- L'envoi d'accès génère un token dans `password_resets` (expire après 1h)
- Protection anti-brute force via `auth_rate_limits` (blocage après X tentatives)

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Utilisateur créé avec succès » / « Accès envoyés par email »
- Erreur : « Login déjà existant » / « Erreur d'envoi email »

**LA ROUTE :** `?page=gestion_utilisateurs`

**COMPORTEMENT DE LA PAGE :**
- Sélection du type → filtre automatique des groupes et des noms disponibles
- Le formulaire de modification pré-remplit tous les champs (sauf mot de passe)
- Gestion du profil aussi accessible via `?page=profil`
- Tableau avec badges de statut (Actif=vert, Inactif=rouge)

**Critères de filtrage :** Type, Groupe, Statut, Nom — logique ET

**Traçabilité :** Oui — `pister` (Création/Modification/Erreur pour chaque action)

**Sécurité :** Accès `SYS_UTILISATEURS`. **Administrateur uniquement.** Mot de passe jamais affiché. Login et MDP sont des données sensibles (RGPD). Rate limiting sur les tentatives.

---

### 📋 ÉCRAN ADM-06 : Piste d'Audit

**ID & TITRE :** `SYS_AUDIT` — Piste Audit / Journal d'audit

**OBJECTIF :** Consulter l'historique complet de toutes les actions effectuées dans le système pour assurer la traçabilité et la conformité.

**VUE PRINCIPALE :** Tableau chronologique (Timeline)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Colonne | Source |
|---|---|
| ID Piste | `pister.id_piste` |
| Utilisateur | JOIN `utilisateur.nom_utilisateur` |
| Action | `pister.action` (Connexion, Déconnexion, Création, Modification, Suppression, Accès, acces_refuse) |
| Statut action | `pister.statut_action` (Succès/Erreur) |
| Table concernée | `pister.nom_table` |
| Date/Heure | `pister.date_creation` |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Exporter | Bleu | Export CSV/PDF du journal |
| Filtrer | Bleu | Application des filtres |

**RÈGLES MÉTIER :**
- Aucune modification/suppression possible (lecture seule absolue)
- Les logs ne peuvent jamais être effacés via l'interface
- Les actions incluent : Connexion, Déconnexion, Création, Modification, Suppression, Accès aux pages, Accès refusé, Envoi d'accès
- Chaque log est horodaté avec timestamp

**ÉTAT DE SORTIE / FEEDBACK :** Lecture seule.

**LA ROUTE :** `?page=piste_audit`

**COMPORTEMENT DE LA PAGE :**
- Tableau chronologique inversé (plus récent en premier)
- Codes couleur : Succès=vert, Erreur=rouge
- Pagination importante (peut contenir des milliers d'entrées)
- Export pour analyse externe

**Critères de filtrage :** Utilisateur, Action, Statut, Table, Plage de dates — logique ET, multi-sélection

**Traçabilité :** C'est LA table de traçabilité du système.

**Sécurité :** Accès `SYS_AUDIT`. **Administrateur uniquement.** Les données d'audit sont en lecture seule absolue.

---

### 💾 ÉCRAN ADM-07 : Sauvegarde/Restauration

**ID & TITRE :** `SYS_BACKUP` — Sauvegarde/Restauration

**OBJECTIF :** Permettre la création de sauvegardes complètes de la base de données, la restauration depuis une sauvegarde, et le téléchargement des fichiers de backup.

**VUE PRINCIPALE :** Tableau des sauvegardes + Actions

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées :*

| Colonne | Source |
|---|---|
| Nom du backup | Nom du fichier SQL |
| Date de création | Métadonnées du fichier |
| Taille | Taille du fichier |
| Actions | Restaurer / Télécharger / Supprimer |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Créer sauvegarde | Vert | POST → génération du dump SQL complet |
| Restaurer | Bleu (confirmation requise) | POST → restauration de la base depuis le backup |
| Télécharger | Bleu | GET → téléchargement du fichier .sql |
| Supprimer | Bleu (confirmation) | POST → suppression du fichier backup |

**RÈGLES MÉTIER :**
- La restauration écrase TOUTES les données actuelles (action irréversible → confirmation obligatoire avec modale)
- La sauvegarde génère un dump complet (structure + données)
- Les fichiers sont stockés côté serveur dans un répertoire sécurisé
- La suppression d'un backup ne peut pas être annulée

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès création : « Sauvegarde créée avec succès : backup_YYYY-MM-DD.sql »
- Succès restauration : « Base de données restaurée avec succès »
- Erreur : « Erreur lors de la sauvegarde » / « Fichier de backup introuvable »

**LA ROUTE :** `?page=sauvegarde_restauration`

**COMPORTEMENT DE LA PAGE :**
- Liste des backups existants avec taille et date
- Bouton de création en haut
- Actions par ligne (restaurer, télécharger, supprimer)
- Modale de confirmation pour restauration et suppression (seule exception à la règle "zéro modale" — action destructrice)

**Critères de filtrage :** Date de création

**Traçabilité :** Oui — log critique dans `pister`

**Sécurité :** Accès `SYS_BACKUP`. **Administrateur uniquement.** Opération critique. Fichiers stockés hors du webroot.

---

### 📚 ÉCRAN ADM-08 : Historique et Archivage

**ID & TITRE :** `SYS_HISTORIQUE` — Historique et Archivage

**OBJECTIF :** Importer des données historiques, consulter et exporter l'historique des dossiers étudiants, et gérer l'archivage des données anciennes.

**VUE PRINCIPALE :** Segmentation polarisée (Actions d'import + Tableau d'historique)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Actions d'import :*
| Champ | Type | Obligatoire |
|---|---|---|
| Fichier d'import | Upload (CSV/Excel) | Oui |

*Données affichées :*
- Historique des dossiers étudiants par année
- Résultat des imports (succès/erreurs)

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Importer | Vert | Upload et traitement du fichier d'import |
| Exporter historique | Bleu | Export CSV/Excel de l'historique |
| Consulter dossier | Bleu | Vue détaillée d'un dossier étudiant |
| Modifier dossier | Bleu | Modification d'un enregistrement historique |

**RÈGLES MÉTIER :**
- L'import vérifie le format et la validité des données avant insertion
- Les données archivées sont en lecture seule (sauf Admin)
- L'export génère un fichier avec toutes les colonnes

**LA ROUTE :** `?page=admin_historique`
- Import : `&action=import`
- Export : `&action=export`
- Détail étudiant : `&action=view_student`
- Modification : `&action=update_student`

**Traçabilité :** Oui

**Sécurité :** Accès `SYS_HISTORIQUE`. Administrateur uniquement.

---

### 👨‍🏫 ÉCRAN ADM-09 : Mise à Jour Enseignant

**ID & TITRE :** `MAJ_ENSEIGNANT` — Mise à jour enseignant

**OBJECTIF :** Gérer le référentiel des enseignants : créer, modifier, supprimer les fiches enseignants avec leurs informations professionnelles.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| ID Enseignant | Texte (varchar 20) | Oui | Vide |
| Nom | Texte (varchar 50) | Oui | Vide |
| Prénom | Texte (varchar 100) | Oui | Vide |
| Téléphone | Texte (varchar 15) | Non | Vide |
| Email | Email (varchar 100) | Non | Vide |
| Spécialité | Liste déroulante | Non | NULL |
| Type enseignant | Liste déroulante | Non | NULL |
| Établissement d'origine | Liste déroulante | Non | NULL |

*Sources listes :*
- Spécialité → `specialite` (Informatique, Comptabilité, Mathématique, etc.)
- Type → `type_enseignant` (Administratif, Simple)
- Établissement → `etablissement_origine` (UFHB, INPHB, UNA, Entreprise)

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert | INSERT dans `enseignants` |
| Modifier | Bleu | UPDATE |
| Supprimer | Bleu | DELETE (vérification dépendances) |

**RÈGLES MÉTIER :**
- L'ID enseignant est la clé primaire (format alphanumériquelibre)
- La suppression vérifie les contraintes FK (affecter, enseignant_jury, avoir, occuper)
- Un enseignant peut avoir un grade (via table `avoir`) et une fonction (via `occuper`)

**LA ROUTE :** `?page=maj_enseignant`

**Traçabilité :** Oui

**Sécurité :** Accès `MAJ_ENSEIGNANT`. Admin uniquement.

---

### 🏢 ÉCRAN ADM-10 : Mise à Jour Personnel Administratif

**ID & TITRE :** `MAJ_PERSONNEL_ADMIN` — Mise à jour personnel administratif

**OBJECTIF :** Gérer le référentiel du personnel administratif de l'UFR.

**VUE PRINCIPALE :** Segmentation polarisée (Formulaire + Tableau)

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Champs de saisie :*

| Champ | Type | Obligatoire | Valeur par défaut |
|---|---|---|---|
| Nom | Texte (varchar 50) | Oui | Vide |
| Prénom | Texte (varchar 100) | Oui | Vide |
| Email | Email (varchar 100) | Oui | Vide |
| Téléphone | Texte (varchar 20) | Oui | Vide |
| Poste | Texte (varchar 30) | Oui | Vide |
| Date d'embauche | Date | Oui | Vide |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Enregistrer | Vert | INSERT dans `personnel_admin` |
| Modifier | Bleu | UPDATE |
| Supprimer | Bleu | DELETE |

**RÈGLES MÉTIER :**
- Le personnel admin est lié aux candidatures (traitement des dossiers)
- La suppression vérifie les dépendances (candidature_soutenance.id_pers_admin)

**LA ROUTE :** `?page=maj_personnel_admin`

**Traçabilité :** Oui

**Sécurité :** Accès `MAJ_PERSONNEL_ADMIN`. Admin uniquement. Données personnelles protégées (RGPD).

---

## ═══════════════════════════════════════════
## MENU : PROFIL
## ═══════════════════════════════════════════

---

### 👤 ÉCRAN PRO-01 : Mon Profil

**ID & TITRE :** `PROFIL` — Mon Profil

**OBJECTIF :** Permettre à chaque utilisateur de consulter ses informations personnelles et de modifier son mot de passe.

**VUE PRINCIPALE :** Formulaire de profil

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées (lecture seule sauf MDP) :*

| Champ | Source | Modifiable |
|---|---|---|
| Nom | `utilisateur.nom_utilisateur` | Non |
| Login | `utilisateur.login_utilisateur` | Non |
| Type | JOIN `type_utilisateur` | Non |
| Groupe | JOIN `groupe_utilisateur` | Non |
| Niveau d'accès | JOIN `niveau_acces_donnees` | Non |
| Statut | `utilisateur.statut_utilisateur` | Non |

*Champs de saisie (modification MDP) :*

| Champ | Type | Obligatoire |
|---|---|---|
| Ancien mot de passe | Password | Oui |
| Nouveau mot de passe | Password | Oui |
| Confirmer nouveau MDP | Password | Oui |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Changer mot de passe | Bleu | UPDATE mdp dans `utilisateur` (bcrypt) |

**RÈGLES MÉTIER :**
- L'ancien mot de passe doit être vérifié avant modification
- Le nouveau mot de passe doit respecter les règles de complexité
- Confirmation obligatoire (nouveau MDP = confirmer MDP)
- Les informations de profil ne sont PAS modifiables ici (modification via Gestion Utilisateurs uniquement)
- L'année académique active est affichée en haut à droite (lecture seule pour tous sauf Admin)

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : « Mot de passe modifié avec succès »
- Erreur : « Ancien mot de passe incorrect » / « Les mots de passe ne correspondent pas »

**LA ROUTE :** `?page=profil`

**COMPORTEMENT DE LA PAGE :**
- Même contrôleur que Gestion Utilisateurs (`GestionUtilisateurController`)
- Affichage des informations du user connecté en session
- Section changement de mot de passe séparée
- Accessible depuis TOUTES les pages (icône profil dans le header)

**Critères de filtrage :** Aucun (vue personnelle)

**Traçabilité :** Oui — changement de MDP logué

**Sécurité :** Accès `PROFIL` (tous les utilisateurs). L'utilisateur ne peut modifier que SON mot de passe. Protection anti-brute force via `auth_rate_limits`.

---

## ═══════════════════════════════════════════
## ANNEXE : ÉCRAN TRANSVERSAL — Authentification
## ═══════════════════════════════════════════

---

### 🔐 ÉCRAN AUTH-01 : Connexion

**ID & TITRE :** `AUTH_LOGIN` — Page de connexion

**OBJECTIF :** Authentifier les utilisateurs du système.

**VUE PRINCIPALE :** Formulaire centré

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

| Champ | Type | Obligatoire |
|---|---|---|
| Login (email) | Texte | Oui |
| Mot de passe | Password | Oui |

**ACTIONS & BOUTONS :**
| Bouton | Couleur | Conséquence |
|---|---|---|
| Se connecter | Bleu (#1a5276) | Vérification bcrypt → session → redirection dashboard |
| Mot de passe oublié | Lien texte | Redirection vers formulaire de réinitialisation |

**RÈGLES MÉTIER :**
- Vérification du login dans `utilisateur.login_utilisateur`
- Vérification du mot de passe via `password_verify()` (bcrypt)
- L'utilisateur doit avoir `statut_utilisateur = 'Actif'`
- Protection anti-brute force via `auth_rate_limits` (blocage après X tentatives par IP)
- Session créée avec : id_utilisateur, nom, type, groupe, permissions
- Redirection vers le dashboard correspondant au profil (Admin→dashboard, Étudiant→candidature, etc.)
- La réinitialisation génère un token dans `password_resets` (expire après 1h, usage unique)

**ÉTAT DE SORTIE / FEEDBACK :**
- Succès : Redirection vers le dashboard + log Connexion/Succès
- Erreur : « Identifiants incorrects » + log Connexion/Erreur
- Compte bloqué : « Trop de tentatives. Réessayez dans X minutes. »

**LA ROUTE :** Page publique (pas de `?page=`)

**Traçabilité :** Oui — chaque tentative (succès ou échec) est loguée dans `pister`

**Sécurité :** Rate limiting. Tokens à usage unique pour reset. Mots de passe hashés bcrypt. Session sécurisée.

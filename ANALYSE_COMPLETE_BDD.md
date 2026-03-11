# ANALYSE COMPLÈTE DE LA STRUCTURE DE LA BASE DE DONNÉES

## Base: ufrmi1802974_2q2mpf

**Date d'analyse:** 10 mars 2026

---

## 📊 STATISTIQUES GÉNÉRALES

- **Nombre total de tables:** 62
- **Tables principales:** 15
- **Tables de liaison:** 12
- **Tables de référence:** 35

---

## 🔑 TABLES PRINCIPALES ET LEURS CHAMPS

### 1. **utilisateur** (8 champs)

Gestion des comptes utilisateurs du système

- `id_utilisateur` (int, AUTO_INCREMENT, PK)
- `nom_utilisateur` (varchar 200)
- `id_type_utilisateur` (int, FK)
- `id_GU` (int, FK → groupe_utilisateur)
- `id_niv_acces_donnee` (int, FK)
- `statut_utilisateur` (enum: Actif/Inactif)
- `login_utilisateur` (varchar 60)
- `mdp_utilisateur` (varchar 255, hashé)

**Relations:** Liée via FK aux tables etudiants (num_etu), enseignants (matricule_ens), personnel_admin (matricule_admin)

---

### 2. **etudiants** (8 champs)

Informations sur les étudiants

- `num_ident_etud` (varchar 25, identifiant national)
- `num_carte_etud` (varchar 25, PK, numéro carte étudiant)
- `nom_etu` (varchar 50)
- `prenom_etu` (varchar 100)
- `date_naiss_etu` (date)
- `id_genre` (char 1, FK → genre: F/M/N)
- `email_etu` (varchar 100)
- `promotion_etu` (varchar 30)

---

### 3. **enseignants** (8 champs)

Informations sur les enseignants

- `id_enseignant` (varchar 20, PK, matricule)
- `nom_enseignant` (varchar 50)
- `prenom_enseignant` (varchar 100)
- `tel_enseignant` (varchar 20)
- `mail_enseignant` (varchar 100)
- `id_specialite` (int, FK)
- `type_enseignant` (int, FK → type_enseignant)
- `id_etablissement_origin` (int, FK)

---

### 4. **inscriptions** (11 champs)

Gestion des inscriptions et paiements

- `num_carte_etud` (varchar 25, PK, FK)
- `id_annee_acad` (int, PK, FK)
- `num_versement` (int, numéro du versement)
- `date_inscription` (datetime)
- `date_versement` (datetime)
- `id_niv_etude` (varchar 2, FK: M2)
- `montant_verser` (decimal 10,2)
- `methode_paiement` (varchar 2, FK → mode_paiement)
- `num_piece_mp` (varchar 100, numéro de pièce)
- `solde` (decimal 10,2, solde restant)
- `fiche_inscription` (varchar 255, chemin PDF/image)

---

### 5. **permissions** (8 champs)

Système de permissions CRUD par groupe

- `id_permission` (int, AUTO_INCREMENT, PK)
- `id_GU` (int, FK → groupe_utilisateur)
- `id_fonctionnalite` (int, FK)
- `peut_voir` (tinyint 1, boolean)
- `peut_creer` (tinyint 1, boolean)
- `peut_modifier` (tinyint 1, boolean)
- `peut_supprimer` (tinyint 1, boolean)
- `date_attribution` (datetime)

---

### 6. **fonctionnalites** (13 champs)

Définition des fonctionnalités/pages du système

- `id_fonctionnalite` (int, AUTO_INCREMENT, PK)
- `id_categorie` (int, FK → categories_fonctionnalites)
- `code_fonctionnalite` (varchar 50, unique)
- `lib_fonctionnalite` (varchar 100, libellé)
- `label_fonctionnalite` (varchar 150)
- `description_fonctionnalite` (text)
- `url_fonctionnalite` (varchar 255, URL de la page)
- `icone_fonctionnalite` (varchar 100)
- `ordre_fonctionnalite` (int, ordre d'affichage)
- `est_sous_page` (tinyint 1, boolean)
- `page_parente` (varchar 50)
- `actif` (tinyint 1, boolean)
- `date_creation` (timestamp)

---

### 7. **notes** (6 champs)

Stockage des moyennes M1 et M2

- `num_etu` (varchar 25, PK, FK)
- `id_annee_acad` (int, FK)
- `moyenne_M1` (decimal 4,2)
- `moyenne_M2` (decimal 4,2)
- `date_creation` (datetime)
- `date_modification` (datetime)

---

### 8. **programmer_soutenance** (9 champs)

Planification des soutenances

- `num_soutenance` (varchar 20, PK, format: YYYYSS#######-##)
- `num_etud` (varchar 25, FK → etudiants)
- `date_soutenance` (date)
- `heure_debut` (time)
- `heure_fin` (time)
- `id_salle` (int, FK → salles)
- `id_session` (int, FK → session)
- `id_annee_acad` (int, FK)
- `statut_soutenance` (enum: Programmée/Validée/Annulée/Terminée)

---

### 9. **rapport_etudiants** (10 champs)

Gestion des rapports de stage/mémoire

- `id_rapport` (int, AUTO_INCREMENT, PK)
- `num_etu` (varchar 25, FK)
- `titre_rapport` (varchar 255)
- `chemin_fichier` (varchar 255)
- `date_depot` (datetime)
- `statut_validation` (enum: En attente/Validé/Rejeté)
- `id_annee_acad` (int, FK)
- `type_rapport` (enum: Stage/Mémoire)
- `note_finale` (decimal 4,2)
- `observations` (text)

---

### 10. **entreprises** (6 champs)

Informations sur les entreprises d'accueil

- `id_entreprise` (int, PK)
- `nom_entreprise` (varchar 100)
- `adresse_entreprise` (varchar 255)
- `tel_entreprise` (varchar 20)
- `email_entreprise` (varchar 100)
- `secteur_activite` (varchar 100)

---

### 11. **maitre_de_stage** (7 champs)

Informations sur les maîtres de stage

- `id_maitre_stage` (varchar 15, PK, format: MS-##-###)
- `nom_maitre_stage` (varchar 50)
- `prenom_maitre_stage` (varchar 100)
- `tel_maitre_stage` (varchar 20)
- `email_maitre_stage` (varchar 100)
- `id_entreprise` (int, FK)
- `id_fonction` (varchar 2, FK)

---

### 12. **groupe_utilisateur** (3 champs)

Définition des groupes/rôles

- `id_GU` (int, AUTO_INCREMENT, PK)
- `lib_GU` (varchar 50, nom du groupe)
- `id_type_utilisateur` (int, FK)

**Groupes existants:**

- Administrateur (id=5, type=4)
- Secretaire (id=6, type=4)
- Etudiant (id=13, type=7)
- Enseignant (types 5/6)

---

### 13. **annee_academique** (3 champs)

Années académiques

- `id_annee_acad` (int, PK, format: AAAAB où B=décennie)
- `date_deb` (date)
- `date_fin` (date)

**Exemple:** 22625 = année 2025-2026

---

### 14. **pister** (6 champs)

Audit trail / Logs d'activité

- `id_piste` (int, AUTO_INCREMENT, PK)
- `id_utilisateur` (int, FK)
- `action` (varchar 60, type: CREATE/READ/UPDATE/DELETE/LOGIN)
- `statut_action` (enum: Erreur/Succès)
- `nom_table` (varchar 50)
- `date_creation` (timestamp)

---

### 15. **candidature_soutenance** (7 champs)

Demandes de candidature à la soutenance

- `id_candidature` (int, AUTO_INCREMENT, PK)
- `num_etu` (varchar 25, FK)
- `id_annee_acad` (int, FK)
- `date_candidature` (datetime)
- `statut_candidature` (enum: En attente/Approuvée/Rejetée)
- `id_pers_admin` (int, FK, validateur)
- `observations` (text)

---

## 🔗 TABLES DE LIAISON / RELATIONS

### affecter

Relation enseignant ↔ rapport (encadrement)

- `id_enseignant` + `id_rapport` (PK composite)
- `role` (enum: encadrant/directeur)
- `id_jury` (int, nullable)

### avoir

Relation enseignant ↔ grade

- `id_grade` + `id_enseignant` (PK composite)
- `date_grade` (date)

### enseignant_jury

Composition des jurys de soutenance

- `num_soutenance` (varchar 20, FK)
- `id_enseignant` (varchar 20, FK)
- `id_qualite_jury` (varchar 2, FK: PJ/DM/EN/RA)
- `note_jury` (decimal 4,2)

### evaluer

Évaluation des étudiants

- `num_etudiant` (varchar 25, FK)
- `num_jury` (int, FK)
- `id_critere` (varchar 2, FK)
- `note` (decimal 4,2)
- `id_annee_acad` (int, FK)

### deposer

Dépôt de rapport

- `num_etu` + `id_rapport` (FK)
- `date_depot` (datetime)

### compte_rendu_rapport

Liaison compte rendu ↔ rapport

- `id_CR` + `id_rapport` (FK)

### rendre

Remise de compte rendu

- `id_CR` + `id_enseignant` (FK)
- `date_rendu` (datetime)

---

## 📋 TABLES DE RÉFÉRENCE (lookup)

### genre

- F (Féminin), M (Masculin), N (Neutre)

### grade

- AS (Assistant), MA (Maître assistant)
- MC (Maître de conférence), PT (Professeur titulaire)

### qualite_jury

- DM (Directeur mémoire), EN (Encadrant)
- RA (Rapporteur), PJ (Président)

### fonction

Fonctions dans les entreprises/administration

- CC, CE, DA, DG, DR, etc.

### mode_paiement

- CH (Chèque), ES (Espèce), MG (Mandat), OM (Orange Money), WV (Wave)

### session

- 1 (Mai), 2 (Octobre), 3 (Décembre)

### niveau_etude

- M2 (Master 2)

### domaine

Domaines de recherche (21 domaines)

### specialite

16 spécialités dont Informatique, Comptabilité, Marketing, etc.

### type_utilisateur

- 4 (Personnel administratif)
- 5 (Enseignant administratif)
- 6 (Enseignant simple)
- 7 (Etudiant)

### type_enseignant

- 1 (Administratif), 2 (Simple)

### mentions

- 1 (Insuffisant), 2 (Passable), 3 (Assez Bien)
- 4 (Bien), 5 (Très Bien), 6 (Honorable)

### critere_evaluation

- CM (Contenu du mémoire), EX (Exposé)
- PR (Présentation), RQ (Réponses aux questions)

---

## 🔒 TABLES DE SÉCURITÉ & CONFIGURATION

### auth_rate_limits (10 champs)

Limitation de taux pour tentatives de connexion

- id, action, ip, identifier, attempts
- window_start, last_attempt, blocked_until
- created_at, updated_at

### password_resets (6 champs)

Jetons de réinitialisation de mot de passe

- email, token, expires_at, used, created_at

### app_settings (4 champs)

Configuration applicative

- setting_key (PK), setting_value
- is_sensitive, updated_at

### route_actions (8 champs)

Mapping routes ↔ actions pour permissions dynamiques

- id_route_action, route_pattern, http_method
- action_name, description, required_permission
- actif, created_at

---

## 📊 TABLES SPÉCIFIQUES MÉTIER

### bareme_critere

Barème de notation par critère

- `id_annee_acad` + `id_critere` (PK composite)
- `bareme` (int, points maximum)

### frais_inscription

Montants d'inscription par niveau/année

- `id_niv_etude` + `id_annee_acad` (PK composite)
- `montant` (decimal)

### informations_stage

Détails du stage de l'étudiant

- id_info_stage, num_etu, id_entreprise
- id_maitre_stage, date_debut, date_fin
- sujet_stage

### evaluations_rapports

Évaluations détaillées des rapports

- id_evaluation, id_rapport, id_enseignant
- note_contenu, note_presentation, note_redaction
- observations, date_evaluation

### compte_rendu

Comptes rendus de stage

- id_CR, num_etu, titre, contenu
- date_creation, date_modification

### reclamations (7 champs)

Gestion des réclamations étudiantes

- id_reclamation, num_etu, objet, description
- date_reclamation, statut_reclamation
- id_pers_admin (traitement)

### decisions_jury

Décisions finales de jury

- id_decision, num_soutenance
- decision (enum: Admis/Ajourné/Refusé)
- id_mention

### messages

Système de messagerie interne

- id_message, expediteur, destinataire
- contenu, date_envoi

---

## 🏛️ TABLES D'ORGANISATION

### categories_fonctionnalites (8 champs)

Catégories pour organiser les fonctionnalités

- id_categorie, code_categorie, lib_categorie
- label_categorie, description, icone, ordre, actif

### etablissement_origine

Établissements d'origine des enseignants

- id_etablissement, lib_etablissement_court, lib_etablissement_long

### salles

Salles de soutenance

- id_salle, lib_salle

### niveau_acces_donnees

Niveaux d'accès aux données

- 4 (Lecture seule), 5 (Écriture)

### niveau_approbation

Workflow d'approbation

- id_approb, lib_niveau_approb

### statut_jury

États d'un jury

- id_jury, lib_statut

### statut_reclamation

États d'une réclamation

- id_statut_reclamation, lib_statut

---

## ⚠️ POINTS IMPORTANTS À NOTER

### 1. **Clés primaires non standard**

- `etudiants`: PK = `num_carte_etud` (varchar), pas d'id numérique
- `enseignants`: PK = `id_enseignant` (varchar = matricule)
- `utilisateur`: id numérique mais liens via num_etu/matricule_ens via FK

### 2. **Inscription multi-versements**

- Table `inscriptions` permet plusieurs versements par étudiant/année
- Gestion du solde restant
- Traçabilité des pièces de paiement

### 3. **Système de permissions granulaire**

- CRUD complet par groupe + fonctionnalité
- peut_voir, peut_creer, peut_modifier, peut_supprimer

### 4. **Architecture MVC + RBAC**

- `fonctionnalites` = pages/URLs du système
- `categories_fonctionnalites` = organisation des menus
- `permissions` = droits d'accès RBAC
- `groupe_utilisateur` = rôles

### 5. **Codage des années académiques**

- Format: AAAAB (ex: 22625 = 2025-2026)
- A = millésime de début, B = décennie

### 6. **Soutenances**

- Numéros au format: YYYYSS#######-##
- Gestion complète: programmation, jury, notes, décision

### 7. **Traçabilité**

- Table `pister` pour audit trail
- Horodatage sur la plupart des tables

### 8. **Enseignants avec double casquette**

- `type_enseignant`: Administratif (1) vs Simple (2)
- Grades académiques séparés (table `grade`)
- Attribution des grades datée (table `avoir`)

---

## 📁 FICHIERS GÉNÉRÉS

1. **STRUCTURE_COMPLETE_BDD.md** - Documentation Markdown complète (900+ lignes)
2. **STRUCTURE_COMPLETE_BDD.json** - Structure en format JSON
3. **Ce document** - Analyse et résumé

---

## 🎯 RECOMMANDATIONS

1. **Documentation du code**
   - Mettre à jour les modèles PHP pour refléter la structure exacte
   - Documenter les relations FK manquantes
2. **Contrôleurs à vérifier**
   - Vérifier que tous les contrôleurs utilisent les bons noms de champs
   - Attention aux champs qui ont pu changer de nom
3. **Formulaires**
   - Valider que tous les formulaires utilisent les bons noms de champs
   - Vérifier les validations côté serveur

4. **Requêtes SQL**
   - Auditer toutes les requêtes SQL pour correspondance des noms
   - Vérifier les JOINs et les FK

5. **Sessions utilisateur**
   - Vérifier les données stockées en session
   - Adapter aux nouveaux noms de champs

---

**Fin du rapport d'analyse**

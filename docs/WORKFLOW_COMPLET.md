# WORKFLOW COMPLET DE L'APPLICATION CHECKMASTER

## 📋 Vue d'ensemble générale

**CheckMaster** est une plateforme web complète dédiée à la gestion des commissions de validation des soutenances pour les étudiants en Master 2 MIAGE de l'Université Félix Houphouët-Boigny (UFHB). Elle facilite l'interaction entre étudiants, enseignants, membres de commission, scolarité et personnel administratif.

### Identité de l'application
- **Nom** : CheckMaster - GSCV+ (Gestion du Système de Commissions de Validation Plus)
- **Université** : Université Félix Houphouët-Boigny (UFHB), Cocody, Abidjan, Côte d'Ivoire
- **Filière** : MIAGE (Méthodes Informatiques Appliquées à la Gestion des Entreprises)
- **Niveau** : Master 2
- **Stack technologique** : PHP 7.4+, MySQL, Tailwind CSS, FontAwesome

---

## 🔧 ARCHITECTURE TECHNIQUE

### 1. STRUCTURE DU PROJET

```
checkmaster.ufrmi-ufhb-ci/
├── public/                          # Fichiers publiquement accessibles
│   ├── index.php                   # Page d'accueil UFHB
│   ├── indexCM.php                 # Landing page CheckMaster
│   ├── page_connexion.php          # Formulaire de connexion
│   ├── layout.php                  # Layout principal de l'application
│   ├── login.php                   # Traitement de connexion
│   ├── logout.php                  # Déconnexion
│   ├── reset_password.php          # Réinitialisation de mot de passe
│   ├── menu.php                    # Générer le menu dynamique
│   ├── image/                      # Ressources visuelles (logos, bannières)
│   └── css/                        # Styles compilés (Tailwind CSS)
│
├── app/                            # Logique métier
│   ├── config/
│   │   ├── database.php            # Configuration de la connexion MySQL
│   │   └── email.php               # Configuration de l'email (PHPMailer)
│   │
│   ├── controllers/                # Contrôleurs (33 fichiers)
│   │   ├── AuthController.php      # Authentification (login, logout, changement pwd)
│   │   ├── MenuController.php      # Génération du menu dynamique
│   │   ├── DashboardController.php # Tableau de bord global
│   │   ├── DashboardScolariteController.php
│   │   ├── DashboardEnseignantController.php
│   │   ├── DashboardCommissionController.php
│   │   ├── DashboardSecretaireController.php
│   │   ├── GestionUtilisateurController.php
│   │   ├── GestionEtudiantController.php
│   │   ├── GestionRhController.php
│   │   ├── GestionScolariteController.php
│   │   ├── GestionCandidaturesController.php
│   │   ├── GestionDossiersCandidaturesController.php
│   │   ├── CandidatureSoutenanceController.php
│   │   ├── EvaluationDossiersController.php
│   │   ├── EvaluationSoutenanceController.php
│   │   ├── VerificationRapportsController.php
│   │   ├── GestionRapportController.php
│   │   ├── DossierAcademiqueController.php
│   │   ├── NotesController.php
│   │   ├── NotesResultatsController.php
│   │   ├── PlanificationSoutenanceController.php
│   │   ├── ProgrammationSoutenanceController.php
│   │   ├── ProcessusValidationController.php
│   │   ├── RedactionCompteRenduController.php
│   │   ├── GestionReclamationsController.php
│   │   ├── GestionReclamationsScolariteController.php
│   │   ├── ArchiveController.php
│   │   ├── ArchivesDossiersSoutenanceController.php
│   │   ├── ArchivesCompteRenduController.php
│   │   ├── CriteresEvaluationController.php
│   │   ├── AuditController.php
│   │   ├── ParametreController.php
│   │   └── SauvegardeRestaurationController.php
│   │
│   ├── models/                     # Modèles de données (47 fichiers)
│   │   ├── Utilisateur.php
│   │   ├── Etudiant.php
│   │   ├── Enseignant.php
│   │   ├── PersAdmin.php
│   │   ├── CandidatureSoutenance.php
│   │   ├── RapportEtudiant.php
│   │   ├── Evaluation.php
│   │   ├── EvaluationRapport.php
│   │   ├── Note.php
│   │   ├── CompteRendu.php
│   │   ├── DossierAcademique.php
│   │   ├── AuditLog.php
│   │   ├── AnneeAcademique.php
│   │   ├── Semestre.php
│   │   ├── Ue.php
│   │   ├── Ecue.php
│   │   ├── NiveauEtude.php
│   │   ├── Specialite.php
│   │   ├── Grade.php
│   │   ├── Fonction.php
│   │   ├── StatutJury.php
│   │   ├── NiveauApprobation.php
│   │   ├── NiveauAccesDonnees.php
│   │   ├── GroupeUtilisateur.php
│   │   ├── TypeUtilisateur.php
│   │   ├── Traitement.php
│   │   ├── Entreprise.php
│   │   ├── InfoStage.php
│   │   ├── Cours.php
│   │   ├── Message.php
│   │   ├── Reclamation.php
│   │   ├── Action.php
│   │   ├── Valider.php
│   │   ├── Approuver.php
│   │   ├── Attribution.php
│   │   ├── Archive.php
│   │   ├── Scolarite.php
│   │   └── plus...
│   │
│   └── utils/                      # Services utilitaires
│       ├── EmailService.php        # Envoi d'emails (PHPMailer)
│       ├── DocumentGeneratorService.php
│       ├── ExcelImportService.php
│       ├── HTMLPurifierService.php # Sécurité XSS
│       ├── FormattingUtils.php
│       └── ReceiptUtils.php
│
├── ressources/
│   ├── routes/                     # Fichiers de routage (30 fichiers)
│   │   ├── gestionUtilisateurRoutes.php
│   │   ├── gestionRhRoutes.php
│   │   ├── gestionDashboardRoutes.php
│   │   ├── dashboardEnseignantRoutes.php
│   │   ├── gestionScolariteRoutes.php
│   │   ├── gestionNotesRoutes.php
│   │   ├── gestionCandidaturesRoutes.php
│   │   ├── dossierAcademiqueRoutes.php
│   │   ├── verificationRapportsRoutes.php
│   │   ├── evaluationDossiersRoutes.php
│   │   ├── gestionDossiersCandidaturesRoutes.php
│   │   ├── sauvegardeRestaurationRoutes.php
│   │   ├── notesResultatsRoutes.php
│   │   ├── archivesDossiersSoutenanceRoutes.php
│   │   ├── auditRoutes.php
│   │   ├── redactionCompteRenduRoutes.php
│   │   ├── archivesCompteRenduRoutes.php
│   │   └── plus...
│   │
│   └── views/                      # Vues HTML/PHP (multiples dossiers)
│       ├── parametres_generaux/
│       ├── gestion_utilisateurs/
│       ├── gestion_etudiants/
│       ├── gestion_candidatures/
│       ├── evaluation_dossiers/
│       ├── archives/
│       └── plus...
│
├── vendor/                         # Dépendances Composer
├── public/                         # Assets publics
├── logs/                          # Fichiers de log
└── composer.json                  # Dépendances PHP
```

### 2. BASE DE DONNÉES

**Base** : `ufrmi1802974_2q2mpf`

#### Tables principales :

| Table | Responsabilité |
|-------|-----------------|
| `utilisateur` | Authentification, gestion des connexions |
| `etudiant` | Données des étudiants en Master 2 MIAGE |
| `enseignant` | Profils, spécialités, grades, fonctions |
| `personnel_administratif` | Personnel de scolarité, administration |
| `candidature_soutenance` | Candidatures aux soutenances |
| `rapport_etudiant` | Mémoires et rapports de stage |
| `evaluation` | Évaluations des candidatures |
| `evaluation_rapport` | Évaluations des rapports |
| `note` | Notes attribuées par les évaluateurs |
| `compte_rendu` | Comptes rendus de commission |
| `dossier_academique` | Dossiers académiques des étudiants |
| `annee_academique` | Années académiques |
| `semestre` | Semestres d'étude |
| `ue` | Unités d'enseignement |
| `ecue` | Éléments constitutifs d'UE |
| `groupe_utilisateur` | Groupes de permissions (Étudiant, Enseignant, Admin, etc.) |
| `traitement` | Actions/modules disponibles selon le groupe |
| `audit_log` | Logs d'audits et traces d'actions |
| `entreprise` | Entreprises pour les stages |
| `info_stage` | Informations de stage |
| `reclamation` | Réclamations des étudiants |
| `archive` | Archivage de données |

---

## 👥 ROLES ET ACTEURS

### 1. ÉTUDIANT (Master 2 MIAGE)
**Cas d'usage** :
- Se connecter à la plateforme
- Soumettre sa candidature de soutenance
- Télécharger son mémoire/rapport
- Consulter la composition de son jury
- Recevoir les évaluations et notes
- Consulter ses résultats
- Soumettre des réclamations en cas de litige

**Workflows spécifiques** :
1. **Candidature** : Étudiant → Formulaire → Submission → Scolarité (validation)
2. **Dépôt de rapport** : Étudiant → Upload PDF → Vérification → Acceptation/Rejet
3. **Suivi** : Voir l'état d'avancement (En attente, Évalué, Noté, Validé)

---

### 2. ENSEIGNANT/JURY
**Rôles spécifiques** :
- **Enseignant simple** : Évalue et note les étudiants
- **Enseignant administratif** : Gère les candidatures + évaluation

**Cas d'usage** :
- Se connecter
- Consulter la liste de leurs étudiants à évaluer
- Télécharger et analyser les rapports
- Saisir leurs notes et commentaires
- Générer des rapports d'évaluation
- Participer à la commission de validation

**Workflows spécifiques** :
1. **Évaluation** : Accès dossier → Lecture rapport → Notation → Sauvegarde
2. **Commission** : Réunion de commission → Délibération → Validation

---

### 3. SCOLARITÉ
**Cas d'usage** :
- Valider les candidatures soumises
- Vérifier la conformité des dossiers
- Gérer la programmation des soutenances
- Planifier les dates et lieux
- Générer les convocations
- Suivre l'avancement global

**Workflows spécifiques** :
1. **Validation candidatures** : Reçoit → Analyse → Accepte/Rejette
2. **Programmation** : Crée calendrier → Assigne étudiants → Notifie

---

### 4. COMMISSION DE VALIDATION
**Rôles** :
- **Président de commission**
- **Membres de commission**
- **Rapporteur**

**Cas d'usage** :
- Accéder aux dossiers complets
- Consulter les évaluations individuelles
- Délibérer ensemble
- Valider ou refuser les candidatures
- Rédiger les comptes rendus officiels
- Archiver les décisions

**Workflows spécifiques** :
1. **Réunion de commission** : Présentation → Discussion → Vote → Décision
2. **Rédaction compte rendu** : Synthèse → Approbation → Publication

---

### 5. PERSONNEL ADMINISTRATIF/RH
**Cas d'usage** :
- Gérer les utilisateurs (création, modification, suppression)
- Gérer les enseignants (grades, fonctions, spécialités)
- Gérer les étudiants (inscriptions, niveaux)
- Configurer les paramètres généraux
- Consulter les logs d'audit
- Gérer les archives
- Effectuer des sauvegardes/restaurations

**Workflows spécifiques** :
1. **Gestion d'utilisateur** : Création → Attribution groupe → Génération password
2. **Configuration système** : Définit années académiques, semestres, UE, etc.

---

### 6. ADMINISTRATEUR SYSTÈME
**Cas d'usage** :
- Accès total au système
- Gestion des paramètres critiques
- Audit complet
- Sauvegarde et restauration
- Maintenance

---

## 🔄 WORKFLOWS TRANSVERSAUX

### A. FLUX COMPLET D'UNE SOUTENANCE

#### Phase 1 : CANDIDATURE (Semaine 1-2)
```
Étudiant
    ↓
Remplit formulaire candidature
    ↓
Télécharge son mémoire/rapport (PDF)
    ↓
Soumet la candidature
    ↓
(TRIGGER : Email notification scolarité)
    ↓
Scolarité
    ↓
Reçoit notification
    ↓
Vérifie conformité dossier
    ↓
Valide ou Rejette
    ↓
(TRIGGER : Email notification étudiant du résultat)
    ↓
État : Candidature acceptée / refusée
```

#### Phase 2 : COMPOSITION DU JURY (Semaine 2-3)
```
Scolarité
    ↓
Assigne enseignants/jury pour évaluation
    ↓
Crée l'attribution (Rapporteur, Examinateur, etc.)
    ↓
(TRIGGER : Email notification enseignants)
    ↓
Enseignants reçoivent notification
    ↓
État : Jury composé
```

#### Phase 3 : ÉVALUATION INDIVIDUELLE (Semaine 3-5)
```
Enseignant A, B, C (membres jury)
    ↓
Accèdent au dossier de l'étudiant
    ↓
Téléchargent et lisent le rapport
    ↓
Notation selon critères d'évaluation :
    - Qualité du travail (0-20)
    - Originalité (0-5)
    - Clarté de présentation (0-5)
    - Résultat final (moyenne)
    ↓
Sauvegarde notes + commentaires
    ↓
(TRIGGER : Enregistrement evaluation_rapport)
    ↓
État : Évaluation en cours / Complétée
```

#### Phase 4 : RÉUNION DE COMMISSION (Semaine 5)
```
Commission (Président + Membres)
    ↓
Se réunit (date programmée)
    ↓
Présentation synthétique de chaque candidat
    ↓
Discussion sur les notes individuelles
    ↓
Délibération
    ↓
Vote (Accepté / Rejeté / Conditionnel)
    ↓
État : Décision prise
```

#### Phase 5 : RÉDACTION COMPTE RENDU (Jour même ou +1)
```
Président ou Rapporteur
    ↓
Rédige compte rendu de commission
    ↓
Inclut :
    - Liste des présents
    - Résumé des délibérations
    - Décisions prises
    - Signature électronique
    ↓
Approuve compte rendu
    ↓
(TRIGGER : Email notification participants)
    ↓
État : Compte rendu validé
```

#### Phase 6 : NOTIFICATION RÉSULTATS (Semaine 6)
```
Scolarité
    ↓
Extrait les décisions du compte rendu
    ↓
Génère lettres de résultats
    ↓
Envoie par email aux étudiants
    ↓
(TRIGGER : Email officiel résultat)
    ↓
État : Résultats publiés
```

#### Phase 7 : ARCHIVAGE (Après fin de semestre)
```
Administrateur
    ↓
Archive tous les dossiers de l'année
    ↓
Crée snapshot du year_academique
    ↓
Stocke en base archive
    ↓
Génère rapport d'archivage
    ↓
État : Archivé
```

---

### B. AUTHENTIFICATION COMPLÈTE

#### 1. LOGIN
```
Utilisateur (page_connexion.php)
    ↓
Entre login + mot de passe + CSRF token
    ↓
Soumet formulaire
    ↓
(POST vers login.php)
    ↓
AuthController::login($login, $password)
    ↓
Utilisateur::verifierConnexion()
    ↓
Requête : SELECT * FROM utilisateur WHERE login = :login
    ↓
Password_verify(password_input, password_hash_BD)
    ↓
Si OK :
    ├─ Récupère type_utilisateur
    ├─ Récupère groupe_utilisateur
    ├─ Récupère niveau_acces
    ├─ Récupère données spécifiques (enseignant, admin, étudiant)
    ├─ Stocke en $_SESSION
    ├─ AuditLog::logConnexion() [SUCCESS]
    ├─ Redirect vers layout.php
    └─ État : Connecté
    
Si KO :
    ├─ AuditLog::logConnexion() [ne pas enregistrer]
    ├─ Affiche message erreur
    └─ Reste sur page_connexion.php
```

#### 2. MENU DYNAMIQUE
```
layout.php (après login)
    ↓
MenuController::genererMenu($_SESSION['id_GU'])
    ↓
Requête : SELECT traitement FROM groupe_utilisateur_traitement WHERE id_GU = :id
    ↓
Charge les traitements (actions) autorisés pour le groupe
    ↓
MenuView::afficherMenu($traitements, $currentMenuSlug)
    ↓
Génère HTML du menu avec items actifs/inactifs
    ↓
État : Menu personnalisé affiché
```

#### 3. ROUTAGE PAR PAGE
```
layout.php reçoit ?page=XXX
    ↓
Récupère $currentMenuSlug
    ↓
Switch sur $currentMenuSlug
    ↓
Inclut le fichier route correspondant
    ↓
La route appelle le contrôleur approprié
    ↓
Le contrôleur charge la vue
    ↓
La vue affiche le contenu
```

#### 4. LOGOUT
```
Utilisateur clique logout
    ↓
logout.php
    ↓
AuthController::logout()
    ↓
AuditLog::logDeconnexion() [SUCCESS]
    ↓
Détruit $_SESSION complètement
    ↓
Efface cookie de session
    ↓
session_destroy()
    ↓
Redirect vers page_connexion.php
    ↓
État : Déconnecté
```

#### 5. CHANGEMENT MOT DE PASSE
```
Utilisateur accède réinitialisation
    ↓
Formulaire avec champs :
    - Mot de passe actuel
    - Nouveau mot de passe
    - Confirmation
    ↓
Soumis à AuthController::updatePassword()
    ↓
Validations :
    ├─ Ancien password_verify() OK ?
    ├─ Nouveaux mots de passe identiques ?
    ├─ Différent de l'ancien ?
    ├─ Minimum 8 caractères ?
    ├─ Au moins 1 majuscule ?
    ├─ Au moins 1 chiffre ?
    └─ Au moins 1 caractère spécial ?
    ↓
Si OK :
    ├─ Hash du nouveau mot de passe
    ├─ UPDATE utilisateur SET mdp_utilisateur = hash WHERE id = ?
    ├─ AuditLog::logModification() [SUCCESS]
    └─ Message succès
    
Si KO :
    ├─ AuditLog::logModification() [ERREUR]
    └─ Message erreur détaillé
```

---

## 🎯 MODULES MÉTIER DÉTAILLÉS

### 1. GESTION DES UTILISATEURS

#### Création d'utilisateur
```
Admin → Formulaire création
    ↓
Saisit : login, nom, email, groupe, type
    ↓
GestionUtilisateurController::creerUtilisateur()
    ↓
Génère password temporaire (8 caractères)
    ↓
Hash du password
    ↓
INSERT INTO utilisateur (login, nom, mdp_hash, id_GU, id_type)
    ↓
EmailService::envoyerCredentialsTemporaires()
    ↓
Email reçu par utilisateur avec login + password temporaire
    ↓
État : Utilisateur créé
```

#### Modification d'utilisateur
```
Admin → Sélectionne utilisateur
    ↓
Affiche formulaire pré-rempli
    ↓
Modifie champs autorisés
    ↓
GestionUtilisateurController::modifierUtilisateur()
    ↓
UPDATE utilisateur SET ... WHERE id = ?
    ↓
AuditLog::logModification()
    ↓
État : Utilisateur modifié
```

#### Suppression d'utilisateur
```
Admin → Sélectionne utilisateur
    ↓
Confirme suppression
    ↓
GestionUtilisateurController::supprimerUtilisateur()
    ↓
Soft delete (flag statut = 'Inactif') OU Hard delete
    ↓
AuditLog::logSuppression()
    ↓
État : Utilisateur supprimé/inactivé
```

---

### 2. GESTION DES CANDIDATURES

#### Cycle de candidature
```
Étudiant → Accès "Candidature soutenance"
    ↓
Remplit formulaire :
    ├─ Titre mémoire
    ├─ Résumé
    ├─ Dates proposées
    └─ Fichier rapport (PDF)
    ↓
CandidatureSoutenanceController::soumettreCandidature()
    ↓
Validations :
    ├─ Étudiant autorisé (statut Master 2) ?
    ├─ Rapport existe et est valide ?
    └─ Date limite dépassée ?
    ↓
INSERT INTO candidature_soutenance (id_etudiant, titre, date_submission)
    ↓
INSERT INTO rapport_etudiant (id_candidature, chemin_fichier)
    ↓
VerificationRapportsController::verifierRapport()
    ↓
Vérification PDF :
    ├─ Format valide
    ├─ Taille acceptable (< 50MB)
    ├─ Pas de virus (analyse)
    └─ Indexage du texte pour recherche
    ↓
UPDATE candidature_soutenance SET statut = 'Rapport vérifiée'
    ↓
EmailService::notifierScolarite()
    ↓
État : Candidature soumise, en attente validation scolarité
```

---

### 3. GESTION DES ÉVALUATIONS

#### Workflow d'évaluation
```
Enseignant A (assigné) → Accès "Mes évaluations"
    ↓
Liste des candidatures à évaluer
    ↓
Clique sur candidature → DossierAcademiqueController::afficherDossier()
    ↓
Affiche :
    ├─ Infos étudiant (nom, numéro)
    ├─ Titre mémoire
    ├─ Rapport (PDF viewer)
    ├─ Formulaire d'évaluation
    └─ Notes antérieures de l'étudiant
    ↓
Remplit évaluation :
    ├─ Qualité du travail (0-20)
    ├─ Originalité (0-5)
    ├─ Méthodologie (0-5)
    ├─ Commentaires détaillés
    └─ Recommandation (Accepté/Refusé/Conditionnel)
    ↓
EvaluationDossiersController::sauvegarderEvaluation()
    ↓
INSERT INTO evaluation_rapport (id_rapport, id_enseignant, note, commentaires)
    ↓
UPDATE rapport_etudiant SET statut = 'Évalué'
    ↓
AuditLog::logModification()
    ↓
État : Évaluation enregistrée
```

#### Calcul des résultats
```
NotesResultatsController::calculerResultats()
    ↓
Pour chaque étudiant :
    ├─ Récupère toutes les évaluations
    ├─ Calcule moyenne : SUM(notes) / COUNT(evaluations)
    ├─ Applique pondérations si nécessaire
    ├─ Détermine classement global
    ├─ Détermine classement par niveau d'étude
    └─ Calcule rang (1er, 2e, etc.)
    ↓
INSERT INTO note (id_etudiant, id_niveau_etude, valeur, classement)
    ↓
État : Résultats calculés
```

---

### 4. GESTION DES ARCHIVES

#### Archivage des dossiers
```
Admin → "Gestion archives"
    ↓
Sélectionne année académique et semestre à archiver
    ↓
ArchiveController::archiversDossiers()
    ↓
Pour chaque candidature :
    ├─ Crée snapshot complet (infos + rapports + évaluations)
    ├─ Exporte en PDF
    ├─ Compresse fichiers
    ├─ Crée ZIP
    └─ Stocke en base archive
    ↓
INSERT INTO archive (année, semestre, contenu_zip, date_archivage)
    ↓
Marque candidatures comme archivées
    ↓
EmailService::notifierArchivage()
    ↓
État : Dossiers archivés
```

---

### 5. AUDIT ET LOGS

#### Enregistrement des actions
```
Toute action utilisateur
    ↓
AuditLog::logAction(user_id, type_action, entité, statut)
    ↓
INSERT INTO audit_log (id_utilisateur, action, entite, statut, timestamp)
    ↓
Exemples d'actions tracées :
    ├─ LOGIN/LOGOUT
    ├─ CRÉATION utilisateur
    ├─ MODIFICATION candidature
    ├─ ÉVALUATION rapport
    ├─ VALIDATION commission
    └─ ARCHIVAGE dossier
    ↓
Consultation logs
    ↓
AuditController::afficherLogs()
    ↓
Affiche tableau de tous les logs avec filtres
    ↓
État : Audit disponible
```

---

### 6. GESTION DES RÉCLAMATIONS

#### Soumission réclamation
```
Étudiant → Accès "Réclamations"
    ↓
Remplit formulaire :
    ├─ Sujet
    ├─ Description
    ├─ Document justificatif (optionnel)
    └─ Candidature concernée
    ↓
GestionReclamationsController::soumettre()
    ↓
INSERT INTO reclamation (id_etudiant, sujet, description, date_submission)
    ↓
EmailService::notifierScolarite()
    ↓
État : Réclamation soumise
```

#### Traitement réclamation
```
Scolarité → Accès "Réclamations"
    ↓
Affiche réclamations en attente
    ↓
Analyse cas
    ↓
Propose résolution :
    ├─ Accepte reclamation → Ajuste notes/résultats
    ├─ Rejette reclamation → Envoie justification
    └─ En cours → Indique délais
    ↓
GestionReclamationsScolariteController::traiter()
    ↓
UPDATE reclamation SET statut = ?, resolution = ?
    ↓
EmailService::notifierEtudiant()
    ↓
État : Réclamation traitée
```

---

## 🔐 SÉCURITÉ

### 1. Authentification
- ✅ Mot de passe hashe (PASSWORD_DEFAULT)
- ✅ Vérification statut utilisateur (Actif/Inactif)
- ✅ Sessions PHP sécurisées
- ✅ CSRF tokens sur formulaires

### 2. Autorisation
- ✅ Groupes d'utilisateurs (Étudiant, Enseignant, Admin, etc.)
- ✅ Menu généré dynamiquement selon groupe
- ✅ Niveaux d'approbation pour actions critiques
- ✅ Niveaux d'accès aux données

### 3. Validation des données
- ✅ HTMLPurifierService pour XSS prevention
- ✅ Validation type fichiers (PDF uniquement)
- ✅ Limite de taille fichiers
- ✅ Validation champs formulaires côté serveur

### 4. Audit et traçabilité
- ✅ AuditLog pour chaque action
- ✅ Enregistrement timestamps
- ✅ Enregistrement user_id
- ✅ Consultation logs sécurisée

---

## 📧 SYSTÈME DE NOTIFICATIONS

### EmailService (PHPMailer)
```
Événements qui déclenchent email :
├─ Candidature soumise → Notification scolarité
├─ Candidature validée → Email étudiant (confirmation)
├─ Candidature refusée → Email étudiant (rejet)
├─ Jury composé → Email enseignants (assignation)
├─ Évaluation complétée → Email commission (mise à jour)
├─ Décision prise → Email étudiant (résultat)
├─ Compte rendu rédigé → Email participants
├─ Réclamation soumise → Email scolarité
├─ Réclamation traitée → Email étudiant
└─ User créé → Email new user (credentials temporaires)
```

### Contenu des emails
- Template HTML personnalisé
- Logo UFHB et CheckMaster
- Lien direct vers la plateforme
- Informations contextuelles complètes

---

## 📊 TABLEAUX DE BORD

### DashboardEnseignant
- Liste des étudiants à évaluer
- Progression des évaluations
- Graphique des notes moyennes
- Historique des actions

### DashboardScolarite
- Candidatures en attente
- Candidatures validées/refusées
- Programmation soutenances
- Statistiques globales

### DashboardCommission
- Dossiers complets de chaque candidat
- Synthèse des évaluations
- État de délibération
- Comptes rendus

### DashboardSecretaire
- Tâches à accomplir
- Communications en cours
- Archivage à faire
- Statistiques de la période

---

## 🛠️ PARAMÈTRES SYSTÈME

### Configurations gérables
```
Administrateur → Paramètres généraux
    ↓
├─ Années académiques (2023-2024, 2024-2025, etc.)
├─ Semestres (S1, S2, S3, S4)
├─ Unités d'enseignement (UE1, UE2, etc.)
├─ Éléments d'UE (ECUE1.1, ECUE1.2, etc.)
├─ Grades enseignants (Professeur, Maître de conf, etc.)
├─ Fonctions (Directeur, Coordinateur, etc.)
├─ Spécialités (Informatique, RH, Finance, etc.)
├─ Niveaux d'étude (Master 1, Master 2, etc.)
├─ Niveaux d'approbation (Niveau 1, 2, 3)
├─ Niveaux d'accès aux données (Public, Restreint, Confidentiel)
├─ Entreprises (pour les stages)
├─ Statuts jury (Rapporteur, Examinateur, etc.)
└─ Actions (modules disponibles pour chaque groupe)
```

---

## 📁 DÉPENDANCES EXTERNES

### Composer (PHP)
```json
{
  "phpmailer/phpmailer": "^6.10"      // Envoi emails
  "smalot/pdfparser": "^2.12"         // Analyse PDF
  "phpoffice/phpword": "^1.1"         // Génération DOC/DOCX
  "mpdf/mpdf": "^8.2"                 // Génération PDF
  "dompdf/dompdf": "^3.1"             // PDF HTML vers PDF
  "ezyang/htmlpurifier": "^4.19"      // Nettoyage HTML (XSS)
  "psr/log": "^1.1"                   // Interface logging
  "mpdf/psr-log-aware-trait": "^2.0"  // Trait logging
  "symfony/var-dumper": "^5.4" (dev)  // Debugging
}
```

### Frontend
- Tailwind CSS 4.1.7 (via CDN ou compilation)
- FontAwesome 6.5.0 (icons)
- Google Fonts (Poppins, Montserrat)

---

## 🎨 INTERFACE UTILISATEUR

### Palettes de couleurs
```
Primary: #1a5276      (Bleu profond - UFHB)
Primary Light: #2980b9
Primary Lighter: #3498db
Secondary: #ff8c00    (Orange)
Accent: #4caf50       (Vert)
Success: #4caf50      (Vert)
Warning: #f39c12      (Jaune/Orange)
Danger: #e74c3c       (Rouge)
```

### Navigation
- Barre supérieure fixe
- Sidebar avec menu déroulant (responsive)
- Breadcrumbs pour contextualisation
- Bouton retour vers top
- Menu mobile adaptatif

### Animations
- Fade-in, slide-up, slide-in-left/right
- Bounce-in, float
- Pulse lente
- Hover effects

---

## 📈 FLUX DE DONNÉES

### Entrée de données
1. Formulaires HTML avec validation JS + serveur
2. Upload fichiers (PDF, Excel)
3. Import de masse (Excel)
4. Saisie directe en base

### Traitement de données
1. Controllers traitent les requêtes
2. Models valident et transforment
3. Services appliquent la logique métier
4. AuditLog enregistre chaque action

### Sortie de données
1. Vues HTML/PHP affichent résultats
2. Exports PDF/Excel
3. Emails de notification
4. Rapports statistiques
5. Logs audit consultables

---

## 🔄 CYCLE DE VIE DES DONNÉES

### Candidature soutenance
```
État initial : NULL
    ↓
1. SOUMISE (Étudiant soumet)
    ↓
2. EN VÉRIFICATION (Scolarité vérifie rapport)
    ↓
3. VALIDÉE (Scolarité accepte) OU REFUSÉE (Scolarité rejette)
    ↓
4. JURY COMPOSÉ (Enseignants assignés)
    ↓
5. EN ÉVALUATION (Enseignants évaluent)
    ↓
6. ÉVALUATIONS COMPLÈTES (Toutes évaluations reçues)
    ↓
7. EN COMMISSION (Commission délibère)
    ↓
8. DÉCISION PRISE (Commission vote)
    ↓
9. RÉSULTATS PUBLIÉS (Résultats transmis étudiant)
    ↓
10. ARCHIVÉE (Dossier archivé fin de semestre)
```

---

## 🚀 PERFORMANCES ET OPTIMISATIONS

### Base de données
- Indexes sur colonnes fréquemment recherchées
- Requêtes préparées (PDO prepared statements)
- Pagination des listes longues
- Caching des configurations

### Frontend
- CSS Tailwind compilé (production)
- Images optimisées
- Lazy loading des documents lourds
- Compression gzip

### Backend
- Session caching
- Requêtes optimisées
- Logs en fichier pour historique

---

## ⚙️ CONFIGURATION ET DÉPLOIEMENT

### Fichier de configuration
```php
// app/config/database.php
$host = 'localhost';
$db = 'ufrmi1802974_2q2mpf';
$user = 'root';
$pass = '';
$charset = 'utf8';
```

### Prérequis serveur
- PHP 7.4.33+
- MySQL 5.7+
- Extensions : PDO, cURL, FileInfo, ZIP
- Serveur web (Apache/Nginx)

### Installation
```bash
1. Clone repository
2. composer install
3. npm install (pour Tailwind)
4. Créer base de données
5. Importer structure SQL
6. Configurer app/config/database.php
7. npm run tailwind:dev (développement)
8. Accéder via http://localhost/checkmaster.ufrmi-ufhb-ci
```

---

## 📝 AMÉLIORATIONS POSSIBLES

1. ✅ Migration vers framework (Laravel, Symfony)
2. ✅ API REST complète
3. ✅ Dashboard temps réel (WebSocket)
4. ✅ Notifications push
5. ✅ Mobile app native
6. ✅ Intégration Moodle
7. ✅ Authentification SSO (LDAP)
8. ✅ Backup automatique
9. ✅ CDN pour assets
10. ✅ Analytics avancé

---

## 📞 CONTACTS ET SUPPORT

**Université Félix Houphouët-Boigny (UFHB)**
- 📍 Cocody, Abidjan, Côte d'Ivoire
- 📧 info@univ-fhb.edu.ci
- 📱 +225 22 44 81 00

**CheckMaster Support**
- 📧 checkmaster@univ-fhb.edu.ci
- 🕐 Disponible pendant heures bureau

---

## 📄 VERSIONS

| Version | Date | Modifications |
|---------|------|-----------------|
| 1.0.0 | 2024 | Lancement initial |

---

## 📎 ANNEXES

### A. Liste des fichiers de routage
- gestionUtilisateurRoutes.php
- gestionRhRoutes.php
- gestionDashboardRoutes.php
- dashboardEnseignantRoutes.php
- gestionScolariteRoutes.php
- gestionNotesRoutes.php
- gestionCandidaturesRoutes.php
- dossierAcademiqueRoutes.php
- verificationRapportsRoutes.php
- evaluationDossiersRoutes.php
- gestionDossiersCandidaturesRoutes.php
- sauvegardeRestaurationRoutes.php
- notesResultatsRoutes.php
- archivesDossiersSoutenanceRoutes.php
- auditRoutes.php
- redactionCompteRenduRoutes.php
- archivesCompteRenduRoutes.php
- archiveHistoryRoutes.php
- listeEtudiantsRoutes.php
- gestionRapportsRoutes.php
- gestionEtudiantRoutes.php
- candidatureSoutenanceRoutes.php
- criteresEvaluationRoutes.php
- evaluationSoutenanceRoutes.php
- gestionReclamationsRouteur.php
- gestionReclamationsScolariteRoutes.php
- parametreGenerauxRouteur.php
- plannificationSoutenanceRoutes.php
- programmationSoutenanceRoutes.php

### B. Modèles de données détaillés
47 modèles couvrant tous les aspects du système :
Utilisateur, Etudiant, Enseignant, PersAdmin, etc.

### C. Contrôleurs métier
33 contrôleurs spécialisés gérant la logique métier complète.

---

**Fin de la documentation CheckMaster v1.0.0**
*Plateforme de Gestion des Commissions de Validation - MIAGE UFHB*

# Workflow fonctionnel de l'application GSCV+

## 1. Vue generale et architecture
- Pile: PHP 7.4 sans framework, MySQL, PHPMailer, Dompdf/MPDF, Tailwind pour le front public. Autoload PSR-4 `App\\` vers `src/`.
- Points d'entree: `index.php` (landing public) puis `public/page_connexion.php` pour l'authentification; l'espace connecte passe par `public/layout.php` qui charge toutes les routes dans `ressources/routes/*`.
- Menus et droits: les traitements visibles sont lies au groupe utilisateur (`id_GU`) via `Traitement` + table `rattacher`. Les droits fins utilisent aussi `niveau_acces_donnees` et `hasPermission` quand il est implemente.
- Dossiers applicatifs clefs: `app/controllers` (logique), `app/models` (acces donnees), `app/utils` (services transverses), `ressources/views` (vues), `ressources/uploads` (fichiers deposes/archives), `logs` (trace applicative).

## 2. Roles et controle d'acces
- Types usuels: Administrateur systeme, Personnel administratif (incl. scolarite/communication/secretaire), Commission (enseignants), Enseignant simple, Etudiant.
- Authentification: `AuthController` (hash bcrypt, session), mise a jour de mot de passe avec contraintes fortes. Deconnexion journalisee.
- Menu dynamique: `MenuController` genere la barre laterale selon le groupe; les traitements pointent vers les routeurs specifiques (parametres, RH, scolarite, rapports, etc.).

## 3. Deroulement global (vue chronologique)
1) **Parametrage initial**: creation des referentiels (annees, grades, fonctions, niveaux d'etude/semestre, UE/ECUE, roles jury, niveaux d'approbation/acces, entreprises, messages, salles) puis attribution des traitements aux groupes.
2) **Population RH**: saisie des enseignants et personnels administratifs (grades, specialites, fonctions, dates) via `GestionRhController`.
3) **Comptes utilisateurs**: creation manuelle ou en masse depuis les personnes existantes via `GestionUtilisateurController`, attribution type/groupe/niveau, mot de passe genere et envoye par email (PHPMailer).
4) **Etudiants**: creation/edition/suppression (`GestionEtudiantController`) avec generation automatique du matricule, recherche et pagination.
5) **Inscriptions et scolarite**: inscription a une annee/niveau (`InscriptionController`), emission de recu PDF; suivi des versements/tranches et mises a jour (`GestionScolariteController`).
6) **Notes et resultats**: saisie des notes UE/ECUE (`NotesController`), calculs et export releve PDF (`NotesResultatsController`).
7) **Stage et candidature**: l'etudiant renseigne son stage (`InfoStage`), puis depose une candidature de soutenance (`CandidatureSoutenanceController`).
8) **Examen des candidatures**: personnel valide etapes scolarite/stage/semestres, produit un resume et envoie la decision par email (`GestionCandidaturesController`).
9) **Depot et verification de rapport**: l'etudiant redige/depose un rapport (`GestionRapportController`); la communication verifie/approuve (`VerificationRapportsController`); la commission evalue/vote (`EvaluationDossiersController` + `ProcessusValidationController`).
10) **Programmation et planification**: affectation du jury et des roles (`ProgrammationSoutenanceController`), puis planification salle/date/heure avec controle de doublon (`PlanificationSoutenanceController`).
11) **Evaluation de la soutenance**: jury saisit les notes par critere/barreme (`EvaluationSoutenanceController`), calcule somme et mention, genere PV/annexes PDF (`DocumentGeneratorService`).
12) **Compte rendu et archives**: redaction de compte rendu puis archivage/export (`RedactionCompteRenduController`, `ArchivesCompteRenduController`); archivage dossiers de soutenance (`ArchivesDossiersSoutenanceController`) et historique CSV (`ArchiveController`, doc `docs/HISTORIQUE_ARCHIVAGE.md`).
13) **Reclamations et support**: soumission/suivi de reclamations et traitement par scolarite (`GestionReclamationsController`, `GestionReclamationsScolariteController`).
14) **Sauvegarde, restauration, audit**: sauvegardes SQL dans `ressources/uploads/backups` et restauration (`SauvegardeRestaurationController`), audit consultable/filtrable/exportable (`AuditController`).

## 4. Modules detailes

### 4.1 Parametres generaux (`ParametreController` via `parametreGenerauxRouteur.php`)
- Gestion des referentiels: annees academiques, grades, fonctions utilisateurs, specialites, niveaux et semestres, UE/ECUE, statuts de jury, niveaux d'approbation et d'acces, traitements (menus), entreprises, actions et messages d'erreur, salles.
- Attribution des traitements aux groupes (`gestion_attribution`) pour afficher/autoriser les menus; donnees stockees dans `rattacher`.

### 4.2 Ressources humaines et comptes
- **Enseignants / personnels**: CRUD avec selection du grade, specialite, fonction, type (simple/administratif), dates d'affectation; suppression multiple; traces audit.
- **Utilisateurs**: ajout ponctuel ou en masse (enseignants, personnels, etudiants deja inscrits) avec generation de mot de passe aleatoire, hashage, email d'invitation; edition statut/groupe/niveau; desactivation; verification unicite login.

### 4.3 Etudiants, inscriptions et scolarite
- **Creation et gestion etudiant**: matricule auto (prefixe annee + incr), validation email, edition/suppression en masse, recherche/pagination; audit des operations.
- **Inscriptions**: affectation a une annee/niveau, edition/suppression, impression de recu PDF (Dompdf) via `recu_inscription.php`.
- **Versements**: enregistrement d'un paiement ou d'une tranche avec controle du reste a payer, mise a jour des montants cumules, modification uniquement des tranches, recu PDF via `layout.php?action=imprimer_recu`.
- **Scolarite**: vue synthese des inscrits/non inscrits, filtres par etudiant, verification des montants payes/restants.

### 4.4 Notes et resultats
- **Saisie**: `NotesController` charge etudiants/ECUE/UE, enregistre notes et stocke l'historique; accessible aux roles autorises (enseignants/scolarite).
- **Resultats**: `NotesResultatsController` exporte releves au format PDF (Dompdf) pour un etudiant et un niveau, avec agregation des notes.

### 4.5 Stage et candidature de soutenance (etudiant)
- **Infos de stage**: creation/mise a jour des informations (entreprise, sujet, dates, encadrant) dans `InfoStage`; creation d'une entreprise a la volee si absente.
- **Candidature**: depot unique tant qu'une candidature "en attente"/"validee" existe; verifie la presence d'infos de stage avant soumission; historique des candidatures accessible dans la vue.
- **Compte rendu**: consultation par l'etudiant des comptes rendus de la commission via `compteRenduRapport`.

### 4.6 Examen des candidatures (administratif)
- **Parcours etapes** (`GestionCandidaturesController`): 3 etapes (scolarite, stage, semestres/notes) avec validation ou rejet par etape; progression forcee par session.
- **Resume final**: consolidation des montants payes/reste, infos de stage, moyenne/credits; decision finale (valider/rejeter) stockee dans `candidature` et `resume_candidature`.
- **Notification**: email detaille (via `EmailService` + config `app/config/email.php`) au candidat; audit des validations/rejets.

### 4.7 Rapports et dossiers de candidature
- **Redaction/depot** (`GestionRapportController`):
  - Creation/edition de rapports (titre/theme/contenu HTML sauvegarde dans `ressources/uploads/rapports/rapport_{id}.html`).
  - Controle de depot: un seul rapport en evaluation a la fois, impossibilite d'editer apres depot; suppression possible tant que non depose.
  - Export PDF/Word via MPDF/PHPWord; calcul de statistiques par etudiant ou globales; commentaires par evaluateurs.
  - Depot enregistre dans la table `deposer`, `statut_rapport` (en_attente/en_cours) et `etape_validation` initiaux.
- **Verification communication** (`VerificationRapportsController`):
  - Liste des rapports deposes, statistiques par etat (`en_attente_communication`, `approuve_communication`, `desapprouve_communication`).
  - Approber/rejeter avec commentaire (table `approuver`, id_approb=4); mise a jour de l'etape; audit et email si branche.
- **Historique dossiers verifiees** (`GestionDossiersCandidaturesController`):
  - Tableau des rapports approuves/rejetes avec details et telechargement PDF du contenu HTML; statistiques agregees.

### 4.8 Evaluation commission (dossiers)
- **Votes commission** (`EvaluationDossiersController` + `EvaluationRapport` + `ProcessusValidationController`):
  - Recuperation des rapports `approuve_communication` ou `en_attente_commission`.
  - Chaque membre (enseignant lie a l'utilisateur) enregistre sa decision avec commentaire; mises a jour ou creations dans `evaluations_rapports`.
  - Statut de vote calcule (en cours / pret a finaliser quand 4 votes); finalisation inscrit dans `valider` et met `etape_validation` a `valide` ou `desapprouve_commission`, `statut_rapport` a `valider` ou `rejeter`.
  - Pages tableau de bord commission (`DashboardCommissionController`, `ProcessusValidationController::getDonneesPage`) fournissent compteurs et liste des rapports avec votes.

### 4.9 Programmation et planification des soutenances
- **Attribution du jury** (`ProgrammationSoutenanceController`):
  - Liste des etudiants eligibles et enseignants disponibles; creation/edition/suppression d'attributions dans `programmer` et `composer_jury` avec roles (president, examinateur, directeur de memoire, encadrant).
  - Protections: gestion des professeurs titulaires, recup des donnees via vues `getEtudiantsForView`, `getEnseignantsForView`, `getAttributionsForView`.
- **Planification** (`PlanificationSoutenanceController`):
  - Selection d'une attribution existante, choix salle/date/heure; verifie dates futures, unicite et absence de doublon pour l'etudiant et la salle.
  - Possibilite de modifier ou supprimer la planification (remise a NULL des champs).
  - Vue de toutes les planifications finalisees et de celles partiellement renseignees.

### 4.10 Evaluation des soutenances
- **Referentiel**: criteres d'evaluation et baremes relies a l'annee academique (`CriteresEvaluationController`, tables `critere_evaluation` et `correspondre`).
- **Saisie** (`EvaluationSoutenanceController`):
  - Charge les soutenances planifiees (salle/date/jury, infos etudiant/stage).
  - Verifie permission `evaluation_soutenance/CREATE`, prend l'annee academique courante par date.
  - Controle des notes vs bareme, supprime les notes existantes avant re-saisie; stocke dans `evaluer` avec `num_jury`.
  - Calcul somme des notes et mention; calculs annexes (moyenne M1, M2 S1) pour PV.
  - Impression PV/Annexe 2/3 via `DocumentGeneratorService` + `FormattingUtils`; suppression d'une evaluation possible.
  - API AJAX pour recuperer criteres par annee.

### 4.11 Comptes rendus et archives
- **Redaction** (`RedactionCompteRenduController`): saisie de contenu, sauvegarde en base/fichier, export PDF, audit; accessible via `page=redaction_compte_rendu`.
- **Archives des comptes rendus** (`ArchivesCompteRenduController`): liste/filtre/export CSV, telechargement PDF stocke dans `ressources/uploads/comptes_rendus`, suppression securisee (verif chemin).
- **Archives dossiers de soutenance** (`ArchivesDossiersSoutenanceController`): aggrege dossiers depuis tables courantes ou tables d'archives, filtres par statut/annee, stats, details rapport + evaluations.
- **Historique et archivage CSV** (`ArchiveController`, route `admin_historique`): import CSV 20 colonnes avec `ExcelImportService`, creation automatique des entites manquantes (annee, enseignant, salle, entreprise), consultation/edition fiche archive, export CSV.

### 4.12 Reclamations
- **Etudiants** (`GestionReclamationsController`): soumission de reclamation (type mappe vers domaine), validation des champs, stockage (table `reclamation`), stats, historique consultable; export CSV; acces role admin via `verifierDroitsAdmin`.
- **Scolarite** (`GestionReclamationsScolariteController`): vue de traitement cote scolarite, reponses et suivi dedies.

### 4.13 Tableau de bord par role
- **Dashboard general** (`DashboardController`): compteurs utilisateurs/etudiants/enseignants/personnel, taux d'activite, activites recentes, jeux de donnees pour graphiques.
- **Dashboards specifiques**: scolarite (`DashboardScolariteController`), secretaire (`DashboardSecretaireController`), enseignant (`DashboardEnseignantController`), commission (`DashboardCommissionController`) avec indicateurs adaptes (versements, dossiers, jurys, etc.).

### 4.14 Sauvegarde, restauration et audit
- **Sauvegardes** (`SauvegardeRestaurationController`):
  - Detecte Docker ou non; tente `mysqldump` sinon fallback PHP (`createBackupWithPHP`).
  - Sauvegardes placees dans `ressources/uploads/backups/{nom_base}_YYYYmmdd_HHMMSS.sql`; liste/telechargement/suppression disponible; tests de restauration.
  - Restauration via `mysql` ou fallback PHP (`restoreBackupWithPHP`) apres purge des tables; diagnostique des requetes en cas d'echec.
- **Audit** (`AuditController`):
  - Filtrage par table, statut, utilisateur, plage de dates; pagination.
  - Actions speciales: export CSV, nettoyage global ou suppression ciblee, listing des tables et statuts pour filtres.
  - Journalisation diffusee dans la plupart des controlleurs (`logCreation`, `logModification`, `logValidation`, etc.).

### 4.15 Services transverses
- **Email** (`app/utils/EmailService.php`, `app/config/email.php`): envoi SMTP via Gmail (TLS 587) pour invitations utilisateurs, resultats candidatures, notifications diverses.
- **Generation de documents** (`DocumentGeneratorService`, `ReceiptUtils`): gabarits Word/PDF pour PV, releves, recu, annexes; base path configure pour images.
- **Nettoyage HTML** (`HTMLPurifierService`): purification des contenus WYSIWYG avant stockage.

## 5. Points techniques et chemins utiles
- **Routes**: chargees depuis `ressources/routes/*.php` dans `public/layout.php` (parametres, RH, dashboard, scolarite, notes, candidatures, dossiers, sauvegarde, audit, archives, etc.).
- **Stockage fichiers**: rapports HTML dans `ressources/uploads/rapports`, comptes rendus dans `ressources/uploads/comptes_rendus`, sauvegardes SQL dans `ressources/uploads/backups`, images publiques dans `public/image|images`.
- **Tests/demos**: fichiers de test dans `docs/` (`test_ajax_evaluation.html`, `test_criteres.php`, inserts SQL, annexes de calcul).
- **Securite**: controle d'acces par groupe/traitement, verifications de chemin avant telechargement, validation des entrees (emails, montants, dates futures), transactions pour les imports CSV d'archives, suppression conditionnelle des logs.

Ce document couvre l'ensemble des flux fonctionnels observes dans les controlleurs et routes de l'application afin de disposer d'une vision precise et complete du parcours utilisateur et des enchainements de donnees.

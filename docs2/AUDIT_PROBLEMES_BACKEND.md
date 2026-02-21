# Audit Backend - Problemes Actuels (app + public)

Date audit: 2026-02-06  
Perimetre lu: `app/`, `public/` (et routes legacy incluses depuis `public/layout.php`)

Ce document recense les problemes observes pour une refactorisation backend serieuse.  
Les references `fichier:ligne` pointent les preuves techniques.

## 1) Problemes critiques (P0)

1. Ordre d'execution dangereux: routes legacy executees avant controle session et CSRF global
- Preuves:
  - Inclusion des routes avant garde session: `public/layout.php:22`, `public/layout.php:41`
  - Garde session arrive apres: `public/layout.php:42`
  - Verification CSRF globale arrive encore apres: `public/layout.php:47`
  - Actions POST dans routes avec `exit` (court-circuit): `ressources/routes/verificationRapportsRoutes.php:9`, `ressources/routes/gestionRapportsRoutes.php:17`, `ressources/routes/sauvegardeRestaurationRoutes.php:12`
- Impact:
  - Bypass du garde-fou CSRF global pour plusieurs actions POST.
  - Risque d'execution d'actions metier avant controle d'acces central.

2. Surface d'attaque non authentifiee possible sur des controleurs modifies par POST
- Preuves:
  - Route charge le controleur gestion utilisateurs: `ressources/routes/gestionUtilisateurRoutes.php:8`
  - `GestionUtilisateurController` ne contient pas de garde session/role dans son flux POST: `app/controllers/GestionUtilisateurController.php:70` (debut index), `app/controllers/GestionUtilisateurController.php:102` (POST)
  - Le controle session de `layout.php` est execute plus tard: `public/layout.php:42`
- Impact:
  - Actions d'ecriture potentiellement lancees avant redirection vers login.

3. Bug metier bloquant: rejet de rapport marque comme "approuve"
- Preuve:
  - Dans `rejeterRapport()`, mise a jour vers `approuve_communication`: `app/controllers/VerificationRapportsController.php:177`
- Impact:
  - Incoherence fonctionnelle et corruption d'etat workflow.

4. Telechargement de sauvegarde sans exigence admin stricte
- Preuves:
  - Route de download accessible via action GET: `ressources/routes/sauvegardeRestaurationRoutes.php:31`
  - Methode `downloadBackup()` sans `requireAdmin()`: `app/controllers/SauvegardeRestaurationController.php:528`
  - Controle present seulement sur create/restore/delete: `app/controllers/SauvegardeRestaurationController.php:138`, `app/controllers/SauvegardeRestaurationController.php:209`, `app/controllers/SauvegardeRestaurationController.php:503`
- Impact:
  - Utilisateur authentifie non admin peut tenter de recuperer des dumps SQL (si nom de fichier connu/devinable).

## 2) Problemes majeurs (P1)

1. Contenu HTML de rapports stocke et reaffiche brut (risque XSS stocke)
- Preuves:
  - Entree brute depuis `$_POST`: `app/controllers/GestionRapportController.php:267`
  - Ecriture fichier HTML brute: `app/controllers/GestionRapportController.php:386`
  - Reaffichage brut dans vue detail: `ressources/routes/verificationRapportsRoutes.php:115`
  - Injection brute dans HTML PDF: `ressources/routes/verificationRapportsRoutes.php:214`
- Impact:
  - Script malveillant possible dans contexte utilisateur/admin (XSS stocke).

2. Commandes shell en production + mot de passe DB dans la ligne de commande
- Preuves:
  - `shell_exec`/`system`: `app/controllers/SauvegardeRestaurationController.php:55`, `app/controllers/SauvegardeRestaurationController.php:174`, `app/controllers/SauvegardeRestaurationController.php:259`
  - Utilisation `-p%s` dans commandes mysql/mysqldump: `app/controllers/SauvegardeRestaurationController.php:165`, `app/controllers/SauvegardeRestaurationController.php:250`
- Impact:
  - Exposition secret (process list/log), dependance fragile a Docker/OS, maintenance complexe.

3. Credentials DB en dur dans le code
- Preuves:
  - `app/config/database.php:4`, `app/config/database.php:5`, `app/config/database.php:6`, `app/config/database.php:7`
- Impact:
  - Risque securite, derive de config entre environnements, difficulte de rotation des secrets.

4. SQL dynamique non securise sur nom de colonne de permission
- Preuves:
  - `app/models/Permission.php:17`
  - `app/models/Permission.php:34`
- Impact:
  - Risque d'injection SQL si parametre non whitelist (meme si usage actuel semble interne).

5. Logging debug verbeux en code metier et donnees sensibles dans reponses
- Preuves:
  - Logs debug rapport: `app/controllers/GestionRapportController.php:287`, `app/controllers/GestionRapportController.php:288`
  - Payload debug renvoye au client: `app/controllers/GestionRapportController.php:345`
  - Logs debug permissions: `app/controllers/ParametreController.php:1432`
- Impact:
  - Exposition d'informations internes (PII/metier) et bruit operationnel.

## 3) Dette architecturale forte (P1/P2)

1. Controleurs monolithiques (complexite trop elevee)
- Metriques:
  - `app/controllers/ParametreController.php` = 1577 lignes
  - `app/controllers/GestionRapportController.php` = 1122 lignes
  - `app/controllers/SauvegardeRestaurationController.php` = 801 lignes
  - `app/controllers/ProgrammationSoutenanceController.php` = 782 lignes
  - `app/controllers/EvaluationSoutenanceController.php` = 777 lignes
- Impact:
  - Faible testabilite, regression facile, couplage eleve.

2. Couplage global par etat mutable (`$GLOBALS`) au lieu de DTO/view-model
- Metrique:
  - 344 occurrences de `$GLOBALS[...]` dans les controleurs.
- Exemples:
  - `app/controllers/ParametreController.php:154`
  - `app/controllers/DashboardController.php:85`
- Impact:
  - Flux de donnees implicite, effets de bord, maintenance difficile.

3. Usage massif de superglobales et includes ad-hoc
- Metriques:
  - 378 occurrences de `$_POST`
  - 203 occurrences de `$_GET`
  - 263 occurrences de `require/include`
- Impact:
  - Logique HTTP dispersee, peu testable, forte fragilite aux regressions.

4. Securite inegale selon controleurs
- Metriques:
  - 35 controleurs total
  - 22 manipulent POST/REQUEST_METHOD
  - 6 seulement contiennent un garde session explicite
  - 2 seulement appellent `Csrf::validate` (hors front controllers)
- Impact:
  - Politique d'acces non uniforme, failles d'integration.

## 4) Problemes de conception de la couche web (P2)

1. Coexistence non stabilisee de 2 architectures (legacy + nouveau router)
- Preuves:
  - Nouveau router: `public/app/index.php`
  - Legacy orchestration: `public/layout.php`
  - Routes scripts separes dans `ressources/routes/*.php`
- Impact:
  - Gouvernance difficile des acces, comportement non deterministe selon le point d'entree.

2. Wrappers HTML par output buffering + remplacements string fragiles
- Preuves:
  - `public/app/layout.php:35`
  - `public/site/index.php:21`
  - `public/site/indexCM.php:19`
- Impact:
  - Couche de presentation fragile, casse silencieuse des chemins/assets.

3. Code mort / unreachable dans anciens endpoints
- Preuves:
  - `public/login.php:4`, `public/login.php:5` (delegation + `exit`), puis ancien flux encore present
  - `public/logout.php:5`, `public/logout.php:6` (delegation + `exit`), puis ancien flux encore present
- Impact:
  - Confusion operative, maintenance a risque.

4. Autoload incoherent
- Preuves:
  - Composer PSR-4 sur `App\\ => src/`: `composer.json:28`, `composer.json:30`
  - `src/` ne contient qu'un fichier CSS (`src/input.css`)
  - Autoload reel custom `CheckMaster\\`: `app/Core/Autoload.php:7`
- Impact:
  - Tooling degrade (IDE, tests, static analysis, CI).

## 5) Bugs et incoherences supplementaires (P2/P3)

1. Journalisation import archive toujours en "Succes"
- Preuve:
  - Ternaire identique des 2 cotes: `app/controllers/ArchiveController.php:270`
- Impact:
  - Observabilite fausse, diagnostic prod degrade.

2. Methode export historique non implementee en prod
- Preuve:
  - TODO explicite: `app/controllers/ArchiveController.php:321`
- Impact:
  - Flux metier incomplet.

## 6) Qualite et industrialisation

1. Absence de socle de tests backend
- Constats:
  - Pas de `phpunit.xml`
  - Fichiers de test ad-hoc uniquement (`docs/test_protection.php`, `test_editeur.html`)
- Impact:
  - Refactor risquee sans filet de securite.

2. Point d'entree public melange presentation lourde + logique technique
- Preuves:
  - `public/layout.php` (routing, auth, csrf, rendering, menus, includes, injection HTML)
  - `public/app/index.php` (routing + rendu HTML inline)
- Impact:
  - Front controller sur-charge, difficile a faire evoluer vers un backend propre.

---

## Resume executif

Le backend actuel combine des couches heterogenes (legacy + nouveau routeur) avec des controles de securite executes trop tard dans le cycle de requete.  
Les priorites de refactorisation doivent etre:

1. Reordonner le pipeline HTTP (auth + CSRF + autorisation AVANT toute action route/controller).  
2. Centraliser routing et politiques d'acces dans une seule couche.  
3. Scinder les controleurs monolithes et supprimer la dependance a `$GLOBALS`.  
4. Durcir les flux sensibles (backup, verification rapports, stockage/affichage HTML).  
5. Mettre en place une base de tests automatises avant migration large.


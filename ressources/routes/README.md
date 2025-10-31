# Routes Directory - Architecture Documentation

## Overview

After the routing centralization refactoring, this directory contains route files that include **business logic** and **special handling** that cannot be easily moved to the central Router class without significant refactoring.

## Central Routing System

The main routing is now handled by:
- **`public/index.php`** - Single entry point for all requests
- **`app/Router.php`** - Central router class with route mappings
- **`public/layout.php`** - Simplified layout template (presentation only)

## Remaining Route Files

These files contain business logic and should be preserved:

### 1. `parametreGenerauxRouteur.php`
- **Purpose**: Handles sub-actions for general parameters page
- **Logic**: Calls different controller methods based on `$_GET['action']`
- **Actions**: annees_academiques, grades, fonctions, specialites, niveaux_etude, ue, ecue, statut_jury, niveaux_approbation, semestres, niveaux_acces, traitements, entreprises, actions, fonctions, messages, gestion_attribution, salles, modeles_documents, placeholders_documentation

### 2. `gestionReclamationsRouteur.php`
- **Purpose**: Routes actions for claims management
- **Logic**: Calls controller methods for submitting, tracking, processing, and exporting claims
- **Actions**: soumettre_reclamation, suivi_historique_reclamation, traiter, exporter_reclamations, get_reclamation_details (AJAX)

### 3. `gestionRapportsRoutes.php`
- **Purpose**: Handles report management actions
- **Logic**: Routes to different report actions including AJAX endpoints
- **Actions**: creer_rapport, suivi_rapport, commentaire_rapport, get_commentaires (AJAX), get_rapport (AJAX)

### 4. `candidatureSoutenanceRoutes.php`
- **Purpose**: Routes defense candidature actions
- **Logic**: Simple routing but included for special sub-actions
- **Actions**: compte_rendu_etudiant

### 5. `gestionEtudiantRoutes.php`
- **Purpose**: Handles student management actions
- **Logic**: Routes to different student actions (add, register)
- **Actions**: ajouter_des_etudiants, inscrire_des_etudiants

### 6. `evaluationSoutenanceRoutes.php`
- **Purpose**: Routes defense evaluation actions
- **Logic**: Switch-based routing for evaluation actions
- **Actions**: evaluerSoutenance, etc.

### 7. `verificationRapportsRoutes.php`
- **Purpose**: Complex report verification with AJAX and PDF generation
- **Logic**: Handles POST forms, AJAX actions, PDF downloads
- **Actions**: valider, rejeter, detail, telecharger_pdf
- **Note**: Contains extensive business logic for report validation workflow

### 8. `sauvegardeRestaurationRoutes.php`
- **Purpose**: Backup and restoration functionality
- **Logic**: Handles backup creation, restoration, deletion, and download
- **Actions**: create, restore, delete, download

### 9. `redactionCompteRenduRoutes.php`
- **Purpose**: Minutes/report drafting
- **Logic**: Handles POST submissions and PDF exports
- **Actions**: export_pdf

### 10. `criteresEvaluationRoutes.php`
- **Purpose**: Evaluation criteria management
- **Logic**: AJAX endpoints for loading years and criteria
- **Actions**: getAnnees (AJAX), getCriteres (AJAX), etc.

### 11. `evaluationDossiersRoutes.php`
- **Purpose**: Defense file evaluation
- **Logic**: Includes file serving logic for reports

### 12. `archivesDossiersSoutenanceRoutes.php`
- **Purpose**: Defense file archives
- **Logic**: Export and filtering logic

### 13. `archivesCompteRenduRoutes.php`
- **Purpose**: Minutes archives
- **Logic**: View, delete, and download actions

### 14-21. Other route files
Files like `gestionCandidaturesRoutes.php`, `gestionDossiersCandidaturesRoutes.php`, `gestionReclamationsScolariteRoutes.php`, `listeEtudiantsRoutes.php`, `notesResultatsRoutes.php`, `plannificationSoutenanceRoutes.php`, `programmationSoutenanceRoutes.php` contain specific business logic for their respective modules.

## Future Refactoring Opportunities

To further centralize routing, the following refactoring could be done:

1. **Move action-based logic to controllers**: The switch/if logic in route files should ideally be in controller methods
2. **Implement REST-like routing**: Use HTTP methods (GET, POST, PUT, DELETE) instead of action parameters
3. **Extract AJAX handlers**: Move AJAX response logic to dedicated API endpoints
4. **Consolidate PDF generation**: Create a centralized PDF service

## Migration from Old Architecture

### Before (layout.php)
```php
// layout.php contained:
// - Session management
// - Authentication checks
// - Massive switch statement for routing
// - Permission checks
// - Menu generation
// - Template rendering
// - Include of 30+ route files
```

### After (index.php + Router.php + layout.php)
```php
// public/index.php: Entry point
// - Session management
// - Authentication checks
// - Router instantiation
// - Permission checks
// - Menu generation
// - Controller dispatch
// - Include layout template

// app/Router.php: Central routing
// - Route definitions
// - Controller instantiation
// - Method dispatch
// - Error handling

// public/layout.php: Pure template
// - HTML structure
// - Display $content variable
// - No routing logic
```

## Benefits of New Architecture

1. **Separation of Concerns**: Routing, business logic, and presentation are now separate
2. **Maintainability**: Single point of routing makes it easier to understand the application flow
3. **Testability**: Controllers can be tested independently
4. **Scalability**: Easy to add new routes without modifying layout
5. **Security**: Centralized authentication and permission checks
6. **Clean URLs**: Better URL structure with .htaccess rewriting

## Removed Files (Now Obsolete)

The following simple route files were removed as they only instantiated controllers without any business logic:

- `auditRoutes.php`
- `dashboardEnseignantRoutes.php`
- `dossierAcademiqueRoutes.php`
- `gestionDashboardRoutes.php`
- `gestionNotesRoutes.php`
- `gestionRhRoutes.php`
- `gestionScolariteRoutes.php`
- `gestionUtilisateurRoutes.php`

These are now handled by the central Router class in `app/Router.php`.

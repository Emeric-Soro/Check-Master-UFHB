# Architecture Refactoring Summary

## Issue #5: Centralize Routing and Decouple layout.php Logic

### Problem Statement
The `public/layout.php` file was acting as a "God Object" that handled:
- Authentication
- Routing (large switch statement)
- Permission checks
- Controller inclusion
- Template rendering

This made the file extremely complex (544 lines), difficult to maintain, and fragile.

### Solution Implemented

#### 1. Central Router Class (`app/Router.php`)
- Created a dedicated Router class that maps page names to controller classes and methods
- Defines all application routes in one centralized location
- Handles controller instantiation and method dispatch
- Provides error handling for missing routes/controllers
- Includes special handling for PDF generation and other edge cases

#### 2. Single Entry Point (`public/index.php`)
- Refactored to serve as the main entry point for all requests
- Handles:
  - Session management
  - Security headers
  - Authentication checks
  - Router instantiation
  - Permission verification
  - Menu generation
  - Special page handling (parametres_generaux, gestion_reclamations, etc.)
  - Content generation via Router
  - Layout template inclusion

#### 3. Simplified Layout Template (`public/layout.php`)
- Reduced from 544 lines to ~150 lines
- Now acts as a pure presentation template
- Only contains:
  - HTML structure
  - Sidebar with menu
  - Top bar with user info
  - Main content area (displays `$content` variable)
  - Mobile menu toggle script
- **No routing logic** - all routing handled by index.php and Router

#### 4. Updated URL Rewriting (`.htaccess`)
- Modified to redirect all requests to `index.php`
- Preserves direct access to static assets (CSS, JS, images)
- Enables cleaner URL structure

#### 5. Updated References
- Changed all `layout.php` references to `index.php` in:
  - Controllers (RedactionCompteRenduController, ArchivesCompteRenduController)
  - Utilities (permissions.php)
  - Views (archives_compte_rendu_content.php)
  - Public files (login.php, logout.php)
- Updated landing page references in indexCM.php, reset_password.php, page_connexion.php

#### 6. Removed Obsolete Route Files
Removed 8 simple route files that only instantiated controllers (now handled by Router):
- `auditRoutes.php`
- `dashboardEnseignantRoutes.php`
- `dossierAcademiqueRoutes.php`
- `gestionDashboardRoutes.php`
- `gestionNotesRoutes.php`
- `gestionRhRoutes.php`
- `gestionScolariteRoutes.php`
- `gestionUtilisateurRoutes.php`

#### 7. Documented Remaining Route Files
Created `ressources/routes/README.md` documenting:
- Purpose of each remaining route file
- Why they contain business logic and should be preserved
- Future refactoring opportunities
- Migration guide from old to new architecture

### Architecture Benefits

#### Before
```
Request → .htaccess → layout.php (God Object)
                      ├─ Authentication
                      ├─ Session management
                      ├─ Permission checks
                      ├─ Menu generation
                      ├─ HUGE switch statement (routing)
                      ├─ Include 30+ route files
                      ├─ Controller execution
                      └─ Template rendering
```

#### After
```
Request → .htaccess → index.php (Entry Point)
                      ├─ Authentication
                      ├─ Session management
                      ├─ Permission checks
                      ├─ Menu generation
                      ├─ Router.dispatch() → Controller → View
                      └─ Include layout.php (Pure Template)
                                           └─ Display $content
```

### Key Improvements

1. **Separation of Concerns**
   - Routing logic: `app/Router.php`
   - Entry point logic: `public/index.php`
   - Presentation: `public/layout.php`

2. **Maintainability**
   - Single point of route definition
   - Easy to understand application flow
   - Reduced file complexity

3. **Testability**
   - Controllers can be tested independently
   - Router can be unit tested
   - Clear boundaries between components

4. **Security**
   - Centralized authentication checks
   - Consolidated permission verification
   - Single entry point reduces attack surface

5. **Scalability**
   - Easy to add new routes
   - No need to modify layout for new pages
   - Clear pattern for new features

6. **Clean Architecture**
   - Follows MVC principles
   - Proper separation of layers
   - Standard routing pattern

### Files Modified
- `public/index.php` - Now the main entry point
- `public/layout.php` - Simplified to pure template
- `public/.htaccess` - Updated URL rewriting
- `public/login.php` - Updated redirect
- `public/logout.php` - Updated redirect
- `public/indexCM.php` - Updated landing page link
- `public/reset_password.php` - Updated landing page link
- `public/page_connexion.php` - Updated landing page link
- `app/controllers/RedactionCompteRenduController.php` - Updated redirects
- `app/controllers/ArchivesCompteRenduController.php` - Updated redirects
- `app/utils/permissions.php` - Updated redirect
- `ressources/views/redaction_compte_rendu/archives_compte_rendu_content.php` - Updated links
- `.gitignore` - Added patterns for backup files

### Files Created
- `app/Router.php` - Central routing class
- `public/landing.php` - Renamed from old index.php
- `ressources/routes/README.md` - Documentation

### Files Removed
- 8 obsolete route files (listed above)

### No Database Changes
✅ This refactoring is purely architectural and requires **no database modifications**.

### Acceptance Criteria Status

✅ **public/index.php is the sole entry point** for application pages

✅ **public/layout.php contains no routing logic** - it's now a pure template

✅ **URLs are cleaner and centrally managed** via Router class and .htaccess

✅ **Code is more maintainable** with proper separation of concerns

✅ **Obsolete route files removed** - 8 simple files that are now handled by Router

### Future Improvements

The `ressources/routes/README.md` file documents opportunities for further refactoring:
1. Move action-based logic from route files to controllers
2. Implement REST-like routing with HTTP methods
3. Extract AJAX handlers to dedicated API endpoints
4. Consolidate PDF generation into a centralized service
5. Implement dependency injection container for controllers

### Testing Recommendations

While no automated tests were added (per instructions to make minimal changes), manual testing should verify:
1. All pages load correctly via new routing system
2. Authentication and permission checks work as before
3. PDF generation features still function
4. AJAX endpoints respond correctly
5. Special page handlers (parametres_generaux, gestion_reclamations) work properly
6. Mobile menu toggle works
7. Logout redirects correctly
8. Login redirects to appropriate dashboard

### Conclusion

This refactoring successfully addresses Issue #5 by:
- Centralizing routing in a dedicated Router class
- Decoupling layout.php from routing logic
- Making the codebase more maintainable and testable
- Following better architectural principles
- Maintaining backward compatibility
- Requiring no database changes

The application now has a solid foundation for future feature development with clear separation of concerns and a standard routing pattern.

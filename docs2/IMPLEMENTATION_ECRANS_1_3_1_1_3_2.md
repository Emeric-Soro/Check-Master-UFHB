# Implémentation des Écrans 1.3.1 et 1.3.2

## Résumé

Cette implémentation ajoute deux nouveaux écrans administratifs pour la gestion des candidatures et des réclamations dans le système CheckMaster.

---

## Écran 1.3.1: Candidature (Validation Administrative et Technique)

### Fichiers créés/modifiés:

1. **Controller**: `app/controllers/AdminCandidatureController.php`
   - Gestion de la liste des candidatures avec filtres
   - Détail d'une candidature via panneau latéral
   - Validation d'une candidature avec vérification de scolarité
   - Rejet d'une candidature avec motif obligatoire
   - Envoi d'emails de notification
   - Génération de snapshots JSON pour l'historique

2. **Vue**: `ressources/views/admin_candidatures_content.php`
   - Tableau des candidatures avec statuts colorés
   - Filtres par statut, année académique, filière, recherche
   - Statistiques en temps réel
   - Panneau latéral avec onglets (Informations générales, Stage, Historique)
   - Actions de validation en 2 étapes (bouton → confirmation)

3. **Routes**: `ressources/routes/adminCandidatureRoutes.php`
   - `GET /admin/candidatures` → liste
   - `GET /admin/candidatures/{id}` → détail (AJAX)
   - `POST /admin/candidatures/{id}/valider` → validation
   - `POST /admin/candidatures/{id}/rejeter` → rejet

### Règles métier implémentées:
- RG-CAND-001: Candidature unique par étudiant/année (contrainte gérée)
- RG-CAND-002: Validation possible uniquement si statut = 'soumise'
- RG-CAND-003: Vérification scolarité bloquante si solde > DETTE_TOLERANCE_MAX
- RG-CAND-004: Rejet = obligation de motif
- RG-CAND-005: Historique conservé (snapshot JSON)
- RG-CAND-006: Nombre de soumissions suivi
- RG-CAND-007: Durée stage minimum vérifiée

### Workflow:
```
Brouillon → Soumise → Validée/Rejetée
                    ↓
              Vérification scolarité
              Motif si rejet
              Notification email
```

---

## Écran 1.3.2: Réclamation (Suivi et Traitement)

### Fichiers créés/modifiés:

1. **Controller**: `app/controllers/AdminReclamationController.php`
   - Gestion de la liste des réclamations avec filtres
   - Détail d'une réclamation via panneau latéral
   - Prise en charge d'une réclamation (assignation)
   - Traitement/marquage comme traitée
   - Rejet d'une réclamation avec motif
   - Envoi d'emails de notification
   - Suivi du SLA (alerte si > 7 jours)

2. **Vue**: `ressources/views/admin_reclamations_content.php`
   - Tableau des réclamations avec codes couleur
   - Références auto-générées (RCL-AAAA-NNNN, DMT-AAAA-NNNN)
   - Filtres par type, statut, période
   - Alerte SLA (visuel si > 7 jours)
   - Panneau latéral avec onglets (Détails, Traitement)
   - Actions selon permissions (prendre en charge, traiter, rejeter)

3. **Routes**: `ressources/routes/adminReclamationRoutes.php`
   - `GET /admin/reclamations` → liste
   - `GET /admin/reclamations/{id}` → détail (AJAX)
   - `POST /admin/reclamations/{id}/traiter` → prendre en charge
   - `POST /admin/reclamations/{id}/terminer` → marquer traitée
   - `POST /admin/reclamations/{id}/rejeter` → rejeter

### Règles métier implémentées:
- RG-REC-001: Référence générée automatiquement (RCL/DMT-AAAA-NNNN)
- RG-REC-002: Seul le traiteur assigné peut modifier le statut
- RG-REC-003: Commentaire obligatoire pour passage à "traitée"
- RG-REC-004: Motif obligatoire pour rejet
- RG-REC-005: Historique conservé (qui a fait quoi, quand)

### Workflow:
```
En attente → En cours (Prendre en charge)
                  ↓
           Traitée/Rejetée
                  ↓
           Notification email
```

---

## Fichiers modifiés dans le système existant:

### 1. `public/layout.php`
- Ajout des includes des routes
- Ajout des cases dans le switch pour les nouvelles pages

```php
include __DIR__ . '/../ressources/routes/adminCandidatureRoutes.php';
include __DIR__ . '/../ressources/routes/adminReclamationRoutes.php';
```

```php
case 'admin_candidatures':
    $contentFile = $partialsBasePath . 'admin_candidatures_content.php';
    $currentPageLabel = 'Validation des Candidatures';
    break;
case 'admin_reclamations':
    $contentFile = $partialsBasePath . 'admin_reclamations_content.php';
    $currentPageLabel = 'Traitement des Réclamations';
    break;
```

---

## Migration de base de données (optionnelle)

Fichier: `database_migration_ecrans_1_3_1_1_3_2.sql`

### Colonnes ajoutées à `candidature_soutenance`:
- `nombre_soumissions` INT DEFAULT 1
- `id_validateur` INT NULL
- `motif_rejet` VARCHAR(100) NULL
- `commentaire_rejet` TEXT NULL
- `snapshot_json` JSON NULL

### Colonnes ajoutées à `reclamations`:
- `titre_reclamation` VARCHAR(150) NULL
- `type_reclamation` VARCHAR(50) DEFAULT 'RCL'
- `priorite_reclamation` ENUM('Basse', 'Moyenne', 'Haute') DEFAULT 'Moyenne'
- `id_admin_assigne` INT NULL
- `commentaire_traitement` TEXT NULL
- `date_traitement` DATETIME NULL
- `motif_rejet` VARCHAR(100) NULL

### Nouvelles tables:
- `historique_candidature` - Historique des actions
- `historique_reclamation` - Historique des actions
- `motif_rejet_candidature` - Motifs paramétrables
- `parametres_systeme` - Paramètres configurables

**Note**: La migration est optionnelle. Les contrôleurs fonctionnent avec la structure existante mais avec des fonctionnalités réduites.

---

## Accès aux écrans

### Menu de navigation:
Les écrans sont accessibles via le menu hiérarchique:
- **Gestion Scolarité** → **Gestion Candidature** → **Candidature** (Validation)
- **Gestion Scolarité** → **Gestion Candidature** → **Réclamation** (Traitement)

### URLs directes:
- Candidatures: `layout.php?page=admin_candidatures`
- Réclamations: `layout.php?page=admin_reclamations`

### Permissions requises:
- Les contrôleurs utilisent `Session::start()` pour vérifier l'authentification
- Permission suggérée: `valider_candidatures` et `traiter_reclamations`
- Middleware: `['auth', 'permission']` (à configurer selon votre système de permissions)

---

## Design UI/UX

### Patterns utilisés:
- **Tableau avec filtres**: Barre de filtres en haut, tableau responsive
- **Panneau latéral (Slide-over)**: Détail sans changement de page
- **Onglets**: Organisation du contenu dans le panneau latéral
- **Badges colorés**: Codes couleur pour les statuts
- **Statistiques**: Cartes récapitulatives en haut de page
- **Workflow en 2 étapes**: Bouton → Confirmation → Action
- **Feedback utilisateur**: Messages flash (succès/erreur)
- **Alertes visuelles**: Couleurs différentes pour les seuils (SLA > 7 jours)

### Couleurs des statuts:
- **Candidatures**:
  - Brouillon: Gris
  - Soumise: Jaune
  - Validée: Vert (#10B981)
  - Rejetée: Rouge (#EF4444)

- **Réclamations**:
  - En attente: Jaune
  - En cours: Bleu
  - Traitée: Vert
  - Rejetée: Rouge

---

## Tests recommandés

### Écran Candidatures:
1. Filtrer par chaque statut
2. Ouvrir le panneau latéral d'une candidature
3. Valider une candidature (vérifier email envoyé)
4. Rejeter une candidature avec motif (vérifier email envoyé)
5. Vérifier le blocage si scolarité non soldée
6. Vérifier l'historique des actions

### Écran Réclamations:
1. Filtrer par type (RCL/DMT) et statut
2. Prendre en charge une réclamation
3. Marquer comme traitée avec commentaire
4. Rejeter une réclamation avec motif
5. Vérifier l'alerte SLA (> 7 jours)
6. Vérifier que seul le traiteur peut modifier

---

## Dépendances

### Modèles utilisés:
- `Etudiant` - Informations étudiant
- `Scolarite` - Vérification des paiements
- `InfoStage` - Détails du stage
- `PersAdmin` - Informations validateur
- `AuditLog` - Journalisation des actions
- `Reclamation` - Gestion des réclamations

### Services:
- `EmailService` - Envoi de notifications

### Librairies frontend:
- Tailwind CSS (déjà inclus dans layout.php)
- Font Awesome 6.5.0 (déjà inclus)
- JavaScript vanilla (pas de dépendances externes)

---

## Notes techniques

### Compatibilité:
- PHP 8.1+ (conforme au composer.json du projet)
- MySQL 5.7+ / MariaDB 10.2+
- Navigateurs modernes (Chrome, Firefox, Safari, Edge)

### Sécurité:
- Protection CSRF via tokens
- Échappement des sorties HTML (htmlspecialchars)
- Vérification des permissions
- Validation des entrées utilisateur
- Requêtes préparées (PDO)

### Performance:
- Requêtes optimisées avec INDEX
- Chargement AJAX pour le panneau latéral
- Pagination prête à être implémentée
- Cache des requêtes fréquentes possible

---

## Support et maintenance

### Logs:
- Les actions sont journalisées dans la table `pister` via `AuditLog`
- Erreurs SQL loguées via `error_log()`

### Debugging:
- Mode debug disponible via `error_log()`
- Messages flash pour les erreurs utilisateur

### Extensions futures:
- Ajout de la pagination
- Export CSV/Excel
- Impression des fiches
- Notifications push
- Tableau de bord avec graphiques

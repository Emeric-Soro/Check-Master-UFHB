# Schéma du Système de Permissions

## Architecture Actuelle

```mermaid
erDiagram
    groupe_utilisateur ||--o{ permissions : "a plusieurs"
    fonctionnalites ||--o{ permissions : "contrôle"
    categories_fonctionnalites ||--o{ fonctionnalites : "regroupe"

    groupe_utilisateur {
        int id_GU PK
        string lib_GU
        string description
    }

    permissions {
        int id_permission PK
        int id_GU FK
        int id_fonctionnalite FK
        boolean peut_voir
        boolean peut_creer
        boolean peut_modifier
        boolean peut_supprimer
        datetime date_attribution
    }

    fonctionnalites {
        int id_fonctionnalite PK
        int id_categorie FK
        string code_fonctionnalite
        string lib_fonctionnalite
        string url_fonctionnalite
        string icone_fonctionnalite
        int ordre_fonctionnalite
        boolean actif
    }

    categories_fonctionnalites {
        int id_categorie PK
        string code_categorie
        string lib_categorie
        string icone_categorie
        int ordre_categorie
        boolean actif
    }
```

## Flux de Vérification des Permissions

```mermaid
flowchart TD
    A[Utilisateur accède à une page] --> B{Authentifié?}
    B -->|Non| C[Redirection page_connexion]
    B -->|Oui| D[Récupérer id_GU de session]

    D --> E{Admin id_GU=5?}
    E -->|Oui| F[✅ Accès autorisé]
    E -->|Non| G[Extraire page de URL]

    G --> H[Rechercher dans fonctionnalites<br/>par url_fonctionnalite]
    H --> I{Fonctionnalité trouvée?}
    I -->|Non| J[❌ Accès refusé]

    I -->|Oui| K[Rechercher dans permissions<br/>WHERE id_GU = ? AND id_fonctionnalite = ?]
    K --> L{Permission trouvée?}
    L -->|Non| J

    L -->|Oui| M[Détecter action CRUD<br/>voir/creer/modifier/supprimer]
    M --> N{Permission pour action?}
    N -->|Oui| F
    N -->|Non| J

    J --> O[Redirection access_denied]
    F --> P[Afficher la page]
```

## Exemple de Données

### Groupe: Responsable scolarité (id_GU = 8)

```mermaid
graph LR
    A[Responsable scolarité<br/>id_GU=8] --> B[4 Permissions]

    B --> C[Dashboard Scolarité<br/>✓ Voir ✓ Créer ✓ Modifier ✓ Supprimer]
    B --> D[Examiner Candidatures<br/>✓ Voir ✓ Créer ✓ Modifier ✓ Supprimer]
    B --> E[Inscription Étudiants<br/>✓ Voir ✓ Créer ✓ Modifier ✓ Supprimer]
    B --> F[Liste Étudiants<br/>✓ Voir ✓ Créer ✓ Modifier ✓ Supprimer]
```

## Comparaison Ancien vs Nouveau Système

```mermaid
graph TB
    subgraph "ANCIEN SYSTÈME (Legacy)"
        A1[traitement_legacy]
        A2[rattacher_legacy]
        A3[groupe_utilisateur]

        A3 -->|id_GU| A2
        A1 -->|id_traitement| A2

        A2[Simple ON/OFF<br/>pas de granularité CRUD]
    end

    subgraph "NOUVEAU SYSTÈME"
        N1[fonctionnalites]
        N2[permissions]
        N3[groupe_utilisateur]
        N4[categories_fonctionnalites]

        N4 -->|id_categorie| N1
        N3 -->|id_GU| N2
        N1 -->|id_fonctionnalite| N2

        N2[Granularité CRUD<br/>4 droits indépendants:<br/>Voir/Créer/Modifier/Supprimer]
    end

    style A2 fill:#ffcccc
    style N2 fill:#ccffcc
```

## Menu Hiérarchique

```mermaid
graph TD
    A[Menu] --> B[Catégorie: DASHBOARD]
    A --> C[Catégorie: ETUDIANTS]
    A --> D[Catégorie: RAPPORTS]

    B --> B1[Dashboard Global]
    B --> B2[Dashboard Enseignant]
    B --> B3[Dashboard Scolarité]
    B --> B4[Dashboard Secrétaire]
    B --> B5[Dashboard Commission]

    C --> C1[Inscription Étudiants]
    C --> C2[Liste Étudiants Enseignant]
    C --> C3[Liste Étudiants Responsable]

    D --> D1[Gestion Rapports]
    D --> D2[Créer Rapport]
    D --> D3[Valider Rapport]

    style B fill:#e1f5ff
    style C fill:#fff4e1
    style D fill:#e7f5e1
```

## Statistiques

| Élément               | Ancien Système            | Nouveau Système                              |
| --------------------- | ------------------------- | -------------------------------------------- |
| **Tables**            | 2 (traitement, rattacher) | 3 (categories, fonctionnalites, permissions) |
| **Granularité**       | Binaire (ON/OFF)          | CRUD (4 droits)                              |
| **Fonctionnalités**   | ~51                       | 52                                           |
| **Catégories**        | 0                         | 12                                           |
| **Permissions Admin** | ~51                       | 52 × 4 = 208 droits                          |
| **Hiérarchie**        | Non                       | Oui (menu par catégories)                    |
| **Middleware**        | Non                       | Oui (vérification automatique)               |

## Fichiers Clés

```
app/
├── middlewares/
│   └── PermissionMiddleware.php    (Vérification automatique)
├── models/
│   ├── Permission.php               (CRUD permissions)
│   ├── Fonctionnalite.php          (Gestion fonctionnalités)
│   └── Categorie.php                (Gestion catégories)
├── controllers/
│   ├── MenuController.php           (Menu hiérarchique)
│   └── ParametreController.php      (Attribution UI)
└── utils/
    └── permissions_helper.php       (Fonctions canView, canCreate, etc.)

public/
├── layout.php                       (Intégration middleware)
└── menu.php                         (Affichage menu)

ressources/views/
└── parametres_generaux/
    └── gestion_attribution.php      (Interface attribution CRUD)
```

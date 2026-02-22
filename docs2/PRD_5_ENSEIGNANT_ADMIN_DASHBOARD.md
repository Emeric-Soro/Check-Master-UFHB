# PRD 5 : Refonte des Vues - Enseignant & Admin Dashboard

## 1. Vue d'ensemble
Ce document spécifie la refonte complète de l'interface utilisateur pour les tableaux de bord (Dashboards) des espaces **Enseignant** et **Administrateur**, ainsi que l'écran d'ouverture/fermeture de l'année académique. L'objectif est d'utiliser exclusivement les nouveaux composants standardisés (`dashboard-layout`, `stat-card`, `chart-container`) pour une présentation homogène et professionnelle, sans aucune duplication de code HTML/CSS ni utilisation de classes externes (Tailwind).

**Règles communes :**
- Utilisation exclusive des CSS variables (`checkmaster-theme.css`) et composants du Design System.
- Utilisation de `cm_component('dashboard-layout')` pour les vues de type tableau de bord.
- Contrôleurs, modèles et routes restent inchangés.
- Les tableaux de bord sont majoritairement en lecture seule (à l'exception des liens de navigation).

---

## 2. Espace Enseignant

### 2.1 Tableau de bord Enseignant (`ENS_DASHBOARD`)
**Route :** `?page=tableau_bord_enseignant` (et `?page=dashboard_enseignant` via `COM_ESPACES`)
**Type de vue :** Dashboard (`cm_component('dashboard-layout')`).
**Particularité :** Affichage strict des données liées à l'enseignant connecté (`id_enseignant` de la session).

**Structure de la vue :**
1. **En-tête du Dashboard :**
   - Message de bienvenue dynamique : "Bonjour, Pr. [Nom Prénom]".
   - Année académique active affichée en haut à droite.

2. **Section Statistiques (Widgets) :**
   Grille de 4 cartes utilisant `cm_component('stat-card')` :
   - **Rapports à évaluer** (avec lien "Voir ▸" vers les évaluations).
   - **Soutenances planifiées** (avec lien "Voir ▸" vers le planning).
   - **Étudiants encadrés** (cumul encadrement + direction, avec lien "Liste ▸").
   - **Prochaine soutenance** (affiche la date/heure de la prochaine soutenance, ou "Aucune").

3. **Section Activités Récentes :**
   - Liste des dernières activités (rapports reçus, soutenances programmées) affichée dans un conteneur simple (`cm_component('card')` ou composant de liste stylisé).

### 2.2 Alias : Espace Enseignant (`COM_ESPACES`)
**Route :** `?page=dashboard_enseignant`
- Cette vue est **strictement identique** à `ENS_DASHBOARD`. Elle doit charger le même composant ou fichier de vue pour éviter toute duplication. Elle sert uniquement de point d'entrée alternatif depuis l'espace Commission.

---

## 3. Administration Plateforme

### 3.1 Dashboard Admin (`ADM_DASHBOARD`)
**Route :** `?page=dashboard`
**Type de vue :** Dashboard (`cm_component('dashboard-layout')`).
**Particularité :** Vue globale (cross-année) de l'état du système, accessible uniquement aux administrateurs.

**Structure de la vue :**
1. **En-tête du Dashboard :**
   - Titre "Dashboard Administrateur".
   - Affichage de l'année académique courante.

2. **Section Statistiques (Widgets) :**
   Grille de 5 cartes utilisant `cm_component('stat-card')` :
   - **Utilisateurs actifs** (Couleur Primary).
   - **Total Étudiants** (Couleur Info).
   - **Total Enseignants** (Couleur Success).
   - **Total Personnel Admin** (Couleur Warning).
   - **Erreurs 24h** (Couleur Danger, basé sur `pister`).

3. **Section Graphiques & Activités :**
   Grille de 2 colonnes (`cm_component('chart-container')` ou conteneurs équivalents) :
   - **Graphique de répartition :** Bar chart horizontal ou Doughnut chart (Chart.js) montrant la proportion d'Admin, Enseignants et Étudiants.
   - **Dernières Connexions :** Liste (TOP 10) issue de la table `pister` montrant Nom, Date et Heure des dernières connexions réussies.

4. **Section Actions Rapides :**
   Boutons d'accès rapide (`cm_component('button')`) vers :
   - Gérer les utilisateurs (`?page=gestion_utilisateurs`).
   - Piste d'audit (`?page=piste_audit`).
   - Paramétrage (`?page=parametres_generaux`).

### 3.2 Ouverture/Fermeture Année Académique (`ADMIN_ANNEE_ACADEMIQUE`)
**Route :** `?page=parametres_generaux&action=annees_academiques`
**Type de vue :** Segmentation Polarisée (`cm_component('crud-layout')`).
**Particularité :** Bien que classée comme paramètre général, cette vue est critique pour le déclenchement de l'année.

**Pôle Supérieur (Création/Modification) :**
- Utilisation de `FormHelper::render()`.
- Champs (2) :
  1. **Date de début** (Date, obligatoire).
  2. **Date de fin** (Date, obligatoire).
- L'identifiant (id_annee_acad) est géré en back-end (auto-incrément ou année de début).
- Le libellé est automatiquement généré ("YYYY-YYYY").

**Barre Intermédiaire :**
- `TableHelper::renderToolbar()` avec options standard (Sélection, Suppression groupée, Recherche locale).

**Pôle Inférieur (Tableau des années) :**
- Utilisation de `TableHelper::render()`.
- **Colonnes :** `[Checkbox], ID, Date début, Date fin, Libellé, Statut (Badge), Actions`.
- **Badge Statut :** Calculé dynamiquement. Si la date actuelle est entre début et fin : Vert (Active), sinon Gris/Blanc (Inactive).
- **Actions :** Modifier (réinjection), Supprimer (si aucune dépendance).

---

## 4. Checklist d'Implémentation
- [ ] Créer `ressources/views/v2/enseignant_dashboard.php` (utilisé par `?page=tableau_bord_enseignant` et `?page=dashboard_enseignant`).
- [ ] Créer `ressources/views/v2/admin_dashboard.php`.
- [ ] Créer `ressources/views/v2/admin_annee_academique.php` (utilisant `FormHelper` et `TableHelper`).
- [ ] Remplacer les appels dans les contrôleurs pour pointer vers les vues `v2/`.
- [ ] S'assurer que Chart.js est correctement instancié et utilise les variables CSS du thème (`var(--cm-primary)`, etc.).
- [ ] Supprimer tout CSS inline ou classes Tailwind résiduelles.
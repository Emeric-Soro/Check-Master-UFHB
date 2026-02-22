# PRD 3 : Refonte des Vues - Module Scolarité

## 1. Vue d'ensemble du Module
Ce document spécifie la refonte complète de l'interface utilisateur des 6 pages du module **Scolarité**. 
L'objectif principal est de migrer ces pages vers les nouveaux composants standardisés définis dans les PRD 1 et PRD 2, afin d'éliminer toute duplication de code (HTML, CSS, JS inline) et d'assurer une cohérence visuelle parfaite.

**Contraintes transversales :**
- **0 duplication CSS/HTML :** Utilisation exclusive de la librairie de composants `cm_component()` et des helpers PHP.
- **Variables CSS :** Utilisation exclusive des variables CSS du fichier `checkmaster-theme.css`. Aucune classe Tailwind.
- **Logique métier préservée :** Les contrôleurs, modèles et routes restent **intacts**. Seules les variables PHP existantes transmises par le contrôleur (ex: `$GLOBALS[...]`, tableaux d'objets) sont exploitées.
- **Mode de rendu :** L'intégralité du HTML est contenue dans le composant principal (Segmentation Polarisée ou Dashboard). Pas de headers/footers doublonnés (déjà gérés par `app.php`).
- **Responsive :** Utilisation systématique du système de grille CSS standard.

---

## 2. Inventaire des Pages à Refondre

1. **Dashboard Scolarité** (`?page=dashboard_scolarite`)
2. **Gestion Étudiants** (`?page=gestion_etudiants&action=ajouter_des_etudiants`)
3. **Inscription & Paiements** (`?page=gestion_scolarite`)
4. **Saisie des Moyennes** (`?page=gestion_notes_evaluations`)
5. **Dossiers de Candidatures** (`?page=gestion_dossiers_candidatures`)
6. **Réclamations (Scolarité)** (`?page=gestion_reclamations_scolarite`)

---

## 3. Spécifications Détaillées par Page

### 3.1. Dashboard Scolarité
**Route actuelle :** `?page=dashboard_scolarite`
**Type de vue :** Dashboard (`cm_component('dashboard-layout', ...)`).
**Variables Contrôleur disponibles :** Données retournées par `$dashboardController->getDashboardData()` (ex: `$dashboardData['stats']`, `$dashboardData['inscriptionsParNiveau']`).

**Composants à utiliser :**
- `dashboard-layout` : Conteneur principal.
- `stat-card` : 4 à 6 widgets.
- `chart-container` : 2 graphiques.
- `button` : Boutons de navigation rapide.

**Structure de la vue :**
1. **En-tête du Dashboard :**
   - Titre : "Tableau de Bord Scolarité".
   - Affichage de l'année académique active (haut droite).
2. **Section Statistiques (Widgets) :**
   Grille de 4 cartes utilisant `cm_component('stat-card')` :
   - Total des étudiants.
   - Inscriptions en cours.
   - Montant total perçu (FCFA).
   - Alertes groupées (Candidatures en attente, Réclamations non traitées, Reste à payer global).
3. **Section Graphiques :**
   Grille de 2 conteneurs utilisant `cm_component('chart-container')` :
   - Répartition par niveau (Bar chart Chart.js, basé sur `$dashboardData['inscriptionsParNiveau']`).
   - Répartition par genre (Bar chart).
4. **Section Actions Rapides :**
   Un conteneur de boutons (`cm_component('button')` - couleur primary `#3498db`) :
   - "Gérer les Étudiants" (`?page=gestion_etudiants`).
   - "Inscriptions / Paiements" (`?page=gestion_scolarite`).
   - "Dossiers de Candidatures" (`?page=gestion_dossiers_candidatures`).

*Note : La vue est en lecture seule, aucune action d'édition.*

---

### 3.2. Gestion Étudiants
**Route actuelle :** `?page=gestion_etudiants&action=ajouter_des_etudiants` (et modification)
**Type de vue :** Segmentation Polarisée (`cm_component('crud-layout', ...)`).
**Variables Contrôleur disponibles :** `$GLOBALS['listeEtudiants']`, `$GLOBALS['allEtudiants']`, `$GLOBALS['listeNiveaux']`, `$GLOBALS['listeAnneesAcad']`, `$GLOBALS['etudiant_a_modifier']`.

**Pôle Supérieur (Formulaire d'Ajout/Modification) :**
Utilisation de `FormHelper::render(...)` :
- Champs (10) :
  1. **Année Académique** (Select, readonly/default=année en cours).
  2. **Identifiant MESRS** (Texte, optionnel).
  3. **N° Étudiant** (Texte, PK, obligatoire).
  4. **Nom** (Texte, obligatoire).
  5. **Prénom** (Texte, obligatoire).
  6. **Date de Naissance** (Date, obligatoire).
  7. **Genre** (Select [M, F], obligatoire).
  8. **Niveau** (Select basé sur `$listeNiveaux`, optionnel).
  9. **Promotion** (Texte, par défaut = année en cours, obligatoire).
  10. **Email** (Email, obligatoire).
- **Mode Édition :** Si `$etudiant_a_modifier` existe, remplir les champs, ajouter champ caché `old_num_etu`, afficher les boutons "Modifier" et "Annuler" (lien vers version vierge).
- **Sécurité :** Inclusion du token CSRF.

**Barre Intermédiaire (Toolbar) :**
Utilisation de `TableHelper::renderToolbar(...)` :
- Boutons : "Tout Sélectionner" (bleu), "Désélectionner" (gris), "Supprimer la sélection" (rouge), "Imprimer" (bleu), "Exporter" (orange).
- Barre de recherche locale (JS).

**Pôle Inférieur (Tableau) :**
Utilisation de `TableHelper::render(...)` :
- **Colonnes :** `[Checkbox], N° Carte Étud., ID MESRS, Nom, Prénom, Date Nais., Genre, Email, Promotion, Actions`.
- **Lignes :** Itération sur `$GLOBALS['listeEtudiants']`.
- **Actions :** Icônes "Modifier" (crayon, lien vers `&num_etu=...`) et "Supprimer" (corbeille, conditionné par `canEdit()`).
- **Pagination :** Utilisation de `PaginationHelper::render(...)`.

---

### 3.3. Inscription & Paiements
**Route actuelle :** `?page=gestion_scolarite`
**Type de vue :** Segmentation Polarisée.
**Variables Contrôleur disponibles :** `$GLOBALS['etudiantsNonInscrits']`, `$GLOBALS['etudiantsInscrits']`, `$GLOBALS['niveaux']`, `$GLOBALS['listeAnnees']`, `$GLOBALS['listeVersement']`.

**Pôle Supérieur (Saisie d'un versement/inscription) :**
- Champs (12) :
  1. **Année Acad.** (Lecture seule).
  2. **Niveau** (Select, obligatoire, déclenche auto-remplissage du Frais Scolarité).
  3. **Frais Scolarité** (Lecture seule).
  4. **Nom & Prénom** (Select avec recherche sur `$etudiantsNonInscrits`/`$etudiantsInscrits`, obligatoire). Déclenche remplissage des champs 5 et 6 via JS.
  5. **Identifiant** (Lecture seule).
  6. **N° Carte** (Lecture seule).
  7. **N° Versement** (Auto-incrémenté, lecture seule).
  8. **Date Versement** (Date, défaut=aujourd'hui, obligatoire).
  9. **Montant Versé** (Nombre, obligatoire). Validation JS : `montant_verse <= reste_a_payer`.
  10. **Reste à Payer** (Calculé automatiquement, lecture seule).
  11. **Mode Paiement** (Select: Espèce, Chèque, Virement, Mobile Money, obligatoire).
  12. **N° Moyen Paiement** (Texte, optionnel).

**Barre Intermédiaire :**
- Filtres (Niveau, Statut de paiement) et recherche rapide.

**Pôle Inférieur (Tableau des versements/inscriptions) :**
- **Colonnes :** `[Checkbox], N° Étud., Nom & Prénom, N° Vers., Date Verse., Montant versé, Mode paie., N° M.P, Actions`.
- **Lignes :** Itération sur les versements/inscriptions.
- **Actions :** Modifier, Supprimer.

---

### 3.4. Saisie des Moyennes
**Route actuelle :** `?page=gestion_notes_evaluations`
**Type de vue :** Segmentation Polarisée. *Note : Refonte majeure par rapport à l'existant qui utilisait un système complexe UE/ECUE. Ce système est supprimé.*
**Variables Contrôleur disponibles :** `$GLOBALS['etudiants']`, `$GLOBALS['niveaux']`.

**Pôle Supérieur (Formulaire de notes) :**
- Champs (7) :
  1. **Année Acad.** (Lecture seule).
  2. **Étudiant** (Select avec recherche, obligatoire). Déclenche remplissage des champs 3, 4 et 5.
  3. **N° Carte** (Texte, max 25, auto).
  4. **Nom** (Texte, max 50, auto).
  5. **Prénom** (Texte, max 100, auto).
  6. **Moyenne M1** (Nombre, 0.00 à 20.00, obligatoire, decimal 4,2).
  7. **Moyenne M2** (Nombre, 0.00 à 20.00, obligatoire, decimal 4,2).
- **Règles :** 1 seule entrée par étudiant par année. Feedback erreur (rouge) en cas de doublon.

**Pôle Inférieur (Tableau des notes) :**
- **Colonnes :** `[Checkbox], N° Étudiant, Nom, Prénom, Moy. M1, Moy. M2, Date saisie, Actions`.
- **Actions :** Modifier, Supprimer.

---

### 3.5. Dossiers de Candidatures
**Route actuelle :** `?page=gestion_dossiers_candidatures`
**Type de vue :** Segmentation Polarisée. *Note : Suppression du modal existant.*
**Variables Contrôleur disponibles :** `$GLOBALS['rapports_verifies']` (à adapter pour candidatures).

**Pôle Supérieur (Traitement du dossier) :**
- Champs (4) :
  1. **Étudiant** (Texte, auto-rempli au clic sur une ligne).
  2. **Date candidature** (Lecture seule).
  3. **Statut** (Select: 'Validée', 'Rejetée', obligatoire).
  4. **Commentaire Admin** (Textarea, max 500, obligatoire si "Rejetée").

**Barre Intermédiaire :**
- Filtres : Statut (multi-select), Recherche textuelle, Filtre par date.

**Pôle Inférieur (Tableau des dossiers) :**
- Utilisation d'un tableau à **2 lignes visuelles par entrée** (ou utilisation d'un accordéon inline).
- **Ligne Principale :** `[Checkbox], N°C, N°Étud, Nom & Prénom, Niveau, Moy.M1, Moy.M2, Versé, Reste, Statut Paiement (Badge)`.
- **Ligne Secondaire (infos candidature) :** `Date Cand., Statut Candidature (Badge), Admin traitant, Date traitement, Actions`.
- **Badges Paiement :** `status-badge` (Vert: Soldé, Orange: Partiel, Rouge: Impayé).
- **Badges Candidature :** `status-badge` (Orange: En attente, Vert: Validée, Rouge: Rejetée).
- **Actions :** Consulter (œil - ouvre l'accordéon/détail inline), Traiter (crayon - remplit le Pôle Supérieur).

---

### 3.6. Réclamations (Scolarité)
**Route actuelle :** `?page=gestion_reclamations_scolarite`
**Type de vue :** Segmentation Polarisée. *Note : La scolarité traite mais ne crée pas.*
**Variables Contrôleur disponibles :** `$GLOBALS['reclamationsEnCours']`, `$GLOBALS['reclamationsTraitees']`.

**Pôle Supérieur (Traitement) :**
- Champs (4) :
  1. **Réclamation de** (Texte, auto-rempli au clic).
  2. **Objet** (Texte, auto-rempli au clic).
  3. **Nouveau Statut** (Select: En attente, En cours, Résolue, Rejetée, obligatoire).
  4. **Réponse** (Textarea, obligatoire).

**Barre Intermédiaire :**
- Filtres : Statut, Date, Étudiant.

**Pôle Inférieur (Tableau unifié) :**
*Remplacement des deux tableaux existants (En cours / Historique) par un seul tableau filtrable.*
- **Colonnes :** `[Checkbox], N° Récl., Étudiant, Objet, Date Récl., Statut (Badge), Actions`.
- **Badges Statut :** Jaune (En attente), Bleu (En cours), Vert (Résolue), Rouge (Rejetée).
- **Actions :** Consulter (œil - détail inline), Traiter (crayon - remplit Pôle Sup).

---

## 4. Stratégie d'Implémentation et Checklist

Pour chaque fichier de vue dans `ressources/views/v2/` :
- [ ] Créer le fichier avec le suffixe approprié (ex: `scolarite_dashboard.php`, `scolarite_etudiants.php`).
- [ ] Injecter l'appel au helper Layout (`cm_component('crud-layout', ...)` ou `dashboard-layout`).
- [ ] Câbler les variables globales PHP existantes (`$GLOBALS[...]`).
- [ ] Configurer les formulaires avec `FormHelper::render()`.
- [ ] Configurer les tableaux avec `TableHelper::render()`.
- [ ] Vérifier que **100% du CSS provient de `checkmaster-theme.css` et `components.css`**. Aucune classe utilitaire externe (ex: Tailwind) n'est présente dans le HTML généré.
- [ ] Intégrer la logique JS pour les interdépendances des formulaires (ex: auto-remplissage Nom/Prénom via Select Étudiant) à la fin de la vue.

Les nouvelles vues remplaceront les inclusions dans les contrôleurs une fois validées. Les contrôleurs ne nécessitent aucune modification.

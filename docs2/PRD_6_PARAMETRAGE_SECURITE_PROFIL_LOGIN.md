# PRD 6 : Refonte des Vues - Administration, Paramétrage, Sécurité, Profil & Login

## 1. Vue d'ensemble
Ce document spécifie la refonte complète des interfaces d'Administration globale de la plateforme, incluant les paramètres, la sécurité, le référentiel, le profil utilisateur et la page de connexion. L'objectif est d'uniformiser toutes ces interfaces critiques en utilisant la nouvelle librairie de composants PHP `cm_component()` et les utilitaires associés, tout en supprimant la duplication de code et l'utilisation de Tailwind.

**Règles communes :**
- Utilisation exclusive des CSS variables (`checkmaster-theme.css`) et composants du Design System.
- Utilisation systématique de `cm_component('crud-layout')` pour les vues de gestion.
- Les contrôleurs, modèles et routes restent **strictement inchangés**.
- Toute logique métier (ex: vérification de mot de passe, authentification) est préservée côté contrôleur.

---

## 2. Administration - Paramétrage

### 2.1 Hub Paramètres Généraux (`PARAM_HUB`)
**Route :** `?page=parametres_generaux`
**Type de vue :** Hub Dashboard (`cm_component('hub-layout')`).
**Structure :**
- Aucune segmentation polarisée. Grille de tuiles cliquables.
- **Tuiles (15+) :** Actions Système, Années, Critères, ECUE, Entreprises, Fonctions, Groupes, Attributions, Grades, Messages, Niveaux, Salles, Semestres, Spécialités, Statuts Jury, UE.
- Chaque tuile (`cm_component('hub-tile')`) contient une icône, un titre, un compteur (fourni par le contrôleur) et redirige vers le CRUD spécifique (`?page=parametres_generaux&action=...`).

### 2.2 Paramètres Spécifiques (`PARAM_SPEC`)
**Route :** `?page=parametres_specifiques`
**Type de vue :** Vue à onglets (`cm_component('tabs-container')`) contenant des CRUDs.
**Structure :**
- 4 onglets : UE, ECUE, Salles, Menu.
- Le contenu de l'onglet actif est une **Segmentation Polarisée** (`crud-layout`) standard gérée via `FormHelper::render()` et `TableHelper::render()`.
- L'URL gère l'onglet actif (ex: `&tab=ue`).

### 2.3 Sous-écrans de Configuration (`PARAM_*`)
**Route :** `?page=parametres_generaux&action=...`
**Type de vue :** Modèle CRUD Universel (Segmentation Polarisée).
**Structure :**
- **Pôle Supérieur :** Formulaire de saisie standard (1 à 3 champs maximum, ex: Libellé).
- **Barre Intermédiaire :** Barre d'outils standard (Recherche, Sélections).
- **Pôle Inférieur :** Tableau des données. Actions: Modifier, Supprimer.
- Cette structure s'applique à l'identique pour toutes les actions (grades, salles, semestres, etc.).

### 2.4 Gestion des Attributions (`PARAM_ATTRIB`)
**Route :** `?page=parametres_generaux&action=gestion_attribution`
**Type de vue :** Vue à 3 onglets (Types, Groupes, Permissions).
- **Onglets Types & Groupes :** Segmentation Polarisée standard.
- **Onglet Permissions :** Matrice de permissions.
  - Sélecteur de groupe en haut.
  - Tableau spécifique (`TableHelper` customisé avec checkboxes pour Voir/Créer/Modifier/Supprimer par fonctionnalité).
  - Bouton global "Enregistrer les permissions".

---

## 3. Administration - Sécurité & Historique

### 3.1 Gestion des Utilisateurs (`SYS_UTILISATEURS`)
**Route :** `?page=gestion_utilisateurs`
**Type de vue :** Segmentation Polarisée.
- **Pôle Supérieur :** Formulaire complexe. Type (select), Groupe (select dynamique filtré par type via JS), Nom, Statut, Login, Mot de passe, Confirmation.
- JS spécifique : Si Type="Etudiant", le champ Nom devient un `select` d'étudiants. Idem pour Enseignant.
- **Pôle Inférieur :** Tableau (ID, Nom, Type, Groupe, Accès, Statut, Login, Actions).
- **Actions :** Modifier, Activer/Désactiver (Badge rouge/vert), Réinitialiser MDP (icône cadenas).

### 3.2 Piste Audit (`SYS_AUDIT`)
**Route :** `?page=piste_audit`
**Type de vue :** Liste filtrable (Segmentation Polarisée sans formulaire haut).
- **Pôle Supérieur :** Remplacé par une zone de filtres complexes (`FormHelper` en mode GET) : Utilisateur, Action, Statut, Table, Plage de dates. Boutons "Filtrer" et "Réinitialiser".
- **Pôle Inférieur :** Tableau en lecture seule (ID, Utilisateur, Action, Statut, Table, Date/Heure).

### 3.3 Sauvegarde & Restauration (`SYS_BACKUP`)
**Route :** `?page=sauvegarde_restauration`
**Type de vue :** Segmentation Polarisée (Actions + Historique).
- **Pôle Supérieur :** Deux panneaux (flex/grid).
  - Gauche : Boutons "Sauvegarde Complète" et "Sauvegarde Sélective".
  - Droite : Formulaire d'upload (`input type="file"`) et bouton "Restaurer la base".
- **Pôle Inférieur :** Tableau des historiques de sauvegarde (Fichier, Date, Taille, Actions: Télécharger, Supprimer).

### 3.4 Historique et Archivage (`SYS_HISTORIQUE`)
**Route :** `?page=admin_historique`
**Type de vue :** Segmentation Polarisée (Actions + Historique).
- **Pôle Supérieur :** Formulaire (Select Année à archiver -> Bouton Archiver) + Formulaire (Upload fichier -> Importer).
- **Pôle Inférieur :** Tableau des archives existantes.

---

## 4. Administration - Référentiel

### 4.1 Mise à jour Enseignant (`MAJ_ENSEIGNANT`) & Personnel Admin (`MAJ_PERSONNEL_ADMIN`)
**Routes :** `?page=maj_enseignant` et `?page=maj_personnel_admin`
**Type de vue :** Segmentation Polarisée standard.
- Formulaires exhaustifs dans le pôle supérieur via `FormHelper`.
- Tableaux de listes dans le pôle inférieur via `TableHelper`.
- Réinjection circulaire (modification) gérée via PHP.

---

## 5. Espace Personnel & Authentification

### 5.1 Mon Profil (`PROFIL`)
**Route :** `?page=profil`
**Type de vue :** Vue Consultation/Formulaire (Pas de tableau).
- Structure en 3 blocs verticaux (`cm_component('card')`) :
  1. **Informations du Compte :** Lecture seule (Nom, Login, Type, Groupe, Statut).
  2. **Informations Complémentaires :** Lecture seule (Affiche dynamiquement les infos Étudiant, Enseignant ou Admin selon le rôle).
  3. **Changement de Mot de passe :** Formulaire (Ancien MDP, Nouveau MDP, Confirmation) + Bouton submit.

### 5.2 Connexion (`LOGIN`)
**Route :** `/` ou `?page=login`
**Type de vue :** Page d'authentification standalone.
- **Composant spécifique :** Pas de sidebar, pas de layout global de l'app. Uniquement le contenu centré.
- Logo CheckMaster + Formulaire (Login, Mot de passe) + Bouton "Se connecter" (Couleur `--cm-primary`).
- Lien "Mot de passe oublié ?".
- Alertes de retour (Identifiants incorrects) en haut du formulaire (`cm_component('alert')`).

---

## 6. Checklist d'Implémentation Globale
- [ ] Créer `ressources/views/v2/parametres_generaux.php` (Hub).
- [ ] Créer un template PHP dynamique unique (`parametres_crud.php`) capable de rendre tous les sous-écrans `PARAM_*` en fonction de la configuration passée par le contrôleur.
- [ ] Créer `ressources/views/v2/gestion_utilisateurs.php` avec la logique JS d'interdépendance Type->Groupe->Nom.
- [ ] Créer les vues spécifiques `piste_audit.php`, `sauvegarde.php` et `historique.php`.
- [ ] Mettre à jour `profil.php` en utilisant le système de grille/cartes du Design System.
- [ ] Reconstruire `page_connexion.php` (ou sa vue associée) en remplaçant tout CSS inline ou classes externes par les classes du thème CheckMaster.
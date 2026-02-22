# PRD 4 : Refonte des Vues - Étudiant, Commission & Soutenance

## 1. Vue d'ensemble
Ce document spécifie la refonte complète de l'interface utilisateur pour trois modules majeurs : **Environnement Étudiant**, **Commission de Validation**, et **Soutenance**. L'objectif est de standardiser ces interfaces en utilisant exclusivement la librairie de composants `cm_component()` et les helpers PHP, garantissant ainsi l'absence de duplication de code (HTML/CSS) et le respect du design system.

**Règles communes :**
- Utilisation exclusive des CSS variables (`checkmaster-theme.css`) et composants. Pas de Tailwind.
- Utilisation de `cm_component('crud-layout')` pour les listes/formulaires et `cm_component('dashboard-layout')` pour les tableaux de bord.
- Contrôleurs, modèles et routes restent inchangés. Seules les variables PHP existantes sont utilisées.
- Aucun header/footer dans les vues (gérés globalement par l'application).

---

## 2. Module Environnement Étudiant

### 2.1 Déclaration de stage (`ETU_INFO_STAGE`)
**Route :** `?page=candidature_soutenance` (et `action=info_stage`)
**Type de vue :** Segmentation Polarisée.

**Pôle Supérieur (Informations de stage) :**
- Formulaire d'ajout/modification (`FormHelper::render()`).
- Champs : Entreprise (recherche/autocomplétion), Date de début (Date), Date de fin (Date), Sujet de stage (Texte), Encadrant entreprise (Texte), Email encadrant (Email), Téléphone encadrant (Texte).
- Bouton : "Enregistrer et passer à la rédaction" (Vert). Déclenche une redirection auto vers l'éditeur.

**Pôle Inférieur (Historique des dépôts) :**
- Tableau (`TableHelper::render()`).
- Colonnes : `[Checkbox], N° Rapport, Nom rapport, Date dépôt, Statut rapport (badge), Statut candidature (badge), Commentaire`.
- Lecture seule des historiques de dépôts.

### 2.2 Gestion des rapports (`ETUD_RAPPORT`)
**Route :** `?page=gestion_rapports`

#### Sous-vue 1 : Dashboard des rapports
**Type de vue :** Mixte (Widgets en haut, Tableau en bas) encapsulé dans une Segmentation Polarisée sans formulaire haut.
- **Widgets (4) :** Rapports Créés, En Attente, Validés, Rejetés (`cm_component('stat-card')`).
- **Tableau :** Liste des rapports récents (N°, Nom, Thème, Statut, Date, Dépôt). Actions : Modifier (crayon), Déposer (icône upload), Supprimer (corbeille).
- Bouton global "Nouveau rapport" (déclenche `action=creer_rapport`).

#### Sous-vue 2 : Éditeur de rédaction (`RAPPORT_EDITEUR`)
**Route :** `?page=gestion_rapports&action=creer_rapport`
**Type de vue :** Éditeur WYSIWYG (`cm_component('editor-wrapper')`).
- Champs Méta : Nom du rapport (saisie), Thème (lecture seule).
- Toolbar riche : B, I, U, Titres, Listes, Tableaux.
- Zone de rédaction avec injection automatique de la page de garde (Logo, Thème, Étudiant, Encadrant).
- Barre inférieure : Compteur de mots (`X / 5000 minimum`), Statut auto-save.
- Boutons : Sauvegarder (Bleu), Aperçu PDF (Bleu), Déposer le tout (Vert, désactivé si < quota).

#### Sous-vue 3 : Suivi de rapport (`RAPPORT_SUIVI`)
**Route :** `?page=gestion_rapports&action=detail&id=ID`
**Type de vue :** Consultation (`cm_component('consultation-wrapper')`).
- Affichage structuré du statut, commentaires des évaluateurs, et historique d'approbation.

### 2.3 Réclamations (`ETU_RECLAMATION`)
**Route :** `?page=gestion_reclamations`
**Type de vue :** Segmentation Polarisée.
- **Pôle Supérieur :** Formulaire (Objet, Description, Pièce justificative upload). Bouton "Soumettre".
- **Pôle Inférieur :** Historique (N°, Objet, Date, Statut, Actions: Voir détails). Clic sur détails ouvre le panneau latéral ou accordéon.

### 2.4 Consultation Compte Rendu (`ETU_CONSULTATION_CR`)
**Route :** `?page=candidature_soutenance&action=compte_rendu_etudiant`
**Type de vue :** Consultation.
- Contenu du compte rendu généré affiché en lecture seule avec bouton "Télécharger PDF".

---

## 3. Module Commission Validation

### 3.1 Tableau de bord (`COM_DASHBOARD`)
**Route :** `?page=dashboard_commission`
**Type de vue :** Dashboard.
- **Widgets (4) :** En attente, Validés, Rejetés, CR Rédigés. Chacun avec un bouton "Voir".
- **Graphiques (2) :** Avancement des rapports (Camembert), Activité récente (Liste).

### 3.2 Réception des rapports (`COM_RECEPTION_RAPPORT`)
**Route :** `?page=reception_rapport_com`
**Type de vue :** Segmentation Polarisée (Tableau uniquement avec filtres avancés).
- **Barre supérieure :** Statistiques rapides (Total, Nouveaux marqués d'une pastille rouge 🔴, Traités).
- **Filtres :** Recherche, Statut.
- **Tableau :** Liste de TOUS les rapports. Clic sur rapport "nouveau" redirige automatiquement vers l'évaluation.

### 3.3 Analyse et Approbation (`ANA_APP_RAPPORT`)
**Route :** `?page=evaluation_dossiers`
**Type de vue :** Segmentation Polarisée.
- **Pôle Supérieur (Décision) :** Auto-remplissage du rapport sélectionné. Select Décision (Valider/Rejeter), Textarea Commentaire (obligatoire si rejet). Bouton "Voir rapport" ouvre panneau latéral. Bouton "Soumettre".
- **Pôle Inférieur (Historique des évaluations) :** Tableau des rapports évalués par ce membre spécifique.

### 3.4 Suivi de validation (`SUIVI_VALIDATION_COM`)
**Route :** `?page=processus_validation`
**Type de vue :** Segmentation Polarisée (Tableau lecture seule).
- Filtres (Statut, Encadrant, Directeur) et Tableau consolidé de l'avancement global (Jusqu'au statut d'évaluation et programmation).

### 3.5 Rédaction de CR & Affectations (`COM_REDACTION_CR`)
**Route :** `?page=redaction_compte_rendu`
**Type de vue :** Hub + Éditeur.
- **Hub :** Boutons (Nouveau CR, Brouillons, Archives).
- **Éditeur (Nouveau CR) :**
  - Section Métadonnées : Étudiant (Select), Nom CR (Texte).
  - Section Rapports liés : Checkboxes des rapports validés. Pour chaque rapport coché, affichage dynamique de 2 Selects (`enseignants`) pour affecter l'Encadrant pédagogique et le Directeur de mémoire.
  - Section WYSIWYG : Rédaction du compte rendu lui-même.
  - Boutons : Aperçu PDF, Enregistrer et générer PDF.

---

## 4. Module Soutenance

### 4.1 Programmation et Jury (`SOUT_COMPOS_JURY`)
**Route :** `?page=programmation_soutenance`
**Type de vue :** Segmentation Polarisée.
- **Pôle Supérieur :** 
  - Informations Soutenance : Date, Heure, Salle, Étudiant, Thème (auto).
  - Composition Jury : 5 selects d'enseignants (Président, Examinateur, Directeur M.S., Encadrant S., Directeur S.).
- **Pôle Inférieur :** Tableau des soutenances programmées. Actions: Modifier, Supprimer.

### 4.2 Évaluation Soutenance (`SOUT_EVALUATION`)
**Route :** `?page=evaluation_soutenance`
**Type de vue :** Segmentation Polarisée.
- **Pôle Supérieur :** Sélection de la soutenance programmée. 5 champs numériques (0-20) pour chaque critère (EX, RQ, PM, CM, RP). Champ lecture seule "Moyenne calculée". Select "Décision".
- **Pôle Inférieur :** Tableau des évaluations passées par l'enseignant connecté.

### 4.3 Édition des Bulletins (`SOUT_EDITION_BULLETIN`)
**Route :** `?page=edition_bulletin`
**Type de vue :** Segmentation Polarisée.
- **Pôle Supérieur :** Filtres uniquement (Session, Étudiant). Bouton global "Générer tous les bulletins".
- **Pôle Inférieur :** Tableau des notes consolidées (Jury, Moyenne, Décision). Action : "Générer PDF" pour chaque ligne.

---

## 5. Checklist d'Implémentation
- [ ] Créer/Mettre à jour les fichiers de vue pour chaque route listée.
- [ ] Utiliser `FormHelper` et `TableHelper` systématiquement pour assurer l'absence de HTML brut redondant.
- [ ] Intégrer la logique JS spécifique dans les vues appropriées (ex: auto-calcul de moyenne dans `SOUT_EVALUATION`, affichage conditionnel des champs de jury dans `COM_REDACTION_CR`).
- [ ] Utiliser uniquement les classes utilitaires générées depuis `checkmaster-theme.css`.
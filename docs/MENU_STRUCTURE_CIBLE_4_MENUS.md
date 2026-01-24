## Objectif

Réduire le menu à **4 grands menus** et organiser les **sous-menus / écrans** de façon logique.

Grands menus souhaités :
1. **Gestion de la scolarité**
2. **Environnement Étudiant**
3. **Commission validation**
4. **Administration plateforme**

Ce document se base sur le dump `ufrmi1802974_2q2mpf.sql` (tables `categories_fonctionnalites` / `fonctionnalites`).

---

## 1) Gestion de la scolarité

### Sous-menus (proposés)
- **Tableau de bord scolarité**
  - Écran : `Dashboard Scolarité` (`?page=dashboard_scolarite`, id_fonctionnalite=3)

- **Étudiants & inscriptions**
  - Écran : `Inscription Étudiants` (`?page=gestion_etudiants`, id=6)
  - Écran : `Tous les étudiants` (`?page=liste_etudiants_resp`, id=8)

- **Scolarité (paiements / reçus)**
  - Écran : `Gestion Scolarité` (`?page=gestion_scolarite`, id=53)

- **Candidatures (côté scolarité)**
  - Écran : `Examiner Candidatures` (`?page=gestion_candidatures`, id=14)
  - Écran : `Dossiers vérifiés` (`?page=gestion_dossiers_candidatures`, id=54)

- **Notes**
  - Écran : `Saisie Notes` (`?page=gestion_notes`, id=26)
  - Écran : `Notes & évaluations` (`?page=gestion_notes_evaluations`, id=58)

- **Réclamations (côté scolarité)**
  - Écran : `Gestion Réclamations` (`?page=gestion_reclamations_scolarite`, id=28)

---

## 2) Environnement Étudiant

### Sous-menus (proposés)
- **Tableau de bord**
  - Écran : `Dashboard Global` (`?page=dashboard`, id=1) *(si on veut que l’étudiant arrive ici)*

- **Candidature**
  - Écran : `Ma candidature` (`?page=candidature_soutenance`, id=13)

- **Rapports**
  - Écran : `Mes rapports` (`?page=gestion_rapports`, id=9)
  - Écran : `Nouveau rapport` (`?page=gestion_rapports&action=creer_rapport`, id=10)
  - Écran : `Suivre mes rapports` (`?page=gestion_rapports&action=suivi_rapport`, id=11)

- **Résultats**
  - Écran : `Bulletin de notes` (`?page=notes_resultats`, id=25)

- **Réclamations (côté étudiant)**
  - Écran : `Soumettre réclamation` (`?page=gestion_reclamations`, id=27)

---

## 3) Commission validation

### Sous-menus (proposés)
- **Tableaux de bord commission / enseignants**
  - Écran : `Dashboard Commission` (`?page=dashboard_commission`, id=5)
  - Écran : `Dashboard Enseignant` (`?page=dashboard_enseignant`, id=2)

- **Candidatures (côté commission)**
  - Écran : `Validation commission` (`?page=verification_candidatures`, id=15)

- **Rapports (côté validation)**
  - Écran : `Approuver rapports` (`?page=verification_rapports`, id=12)
  - Écran : `Rapports à Valider` (`?page=rapport_a_valider`, id=56)

- **Processus & évaluation**
  - Écran : `Processus Validation` (`?page=processus_validation`, id=16)
  - Écran : `Évaluation Dossiers` (`?page=evaluation_dossiers`, id=17)
  - Écran : `Évaluation dossiers soutenance` (`?page=evaluations_dossiers_soutenance`, id=57)

- **Soutenances**
  - Écran : `Composer jury` (`?page=programmation_soutenance`, id=18)
  - Écran : `Date/Heure/Salle` (`?page=planification_soutenance`, id=19)
  - Écran : `Grille évaluation` (`?page=evaluation_soutenance`, id=20)

- **Comptes-rendus**
  - Écran : `Comptes Rendus` (`?page=redaction_compte_rendu`, id=21/22)
  - Écran : `Brouillons` (`?page=redaction_compte_rendu&action=brouillons`, id=23)
  - Écran : `Archives` (`?page=redaction_compte_rendu&action=archives`, id=24)
  - Écran : `Archives CR` (`?page=archive_comptes_rendus`, id=59)

---

## 4) Administration plateforme

### Sous-menus (proposés)
- **Utilisateurs & rôles**
  - Écran : `Gestion Utilisateurs` (`?page=gestion_utilisateurs`, id=50)
  - Écran : `Fonctions Utilisateurs` (`?page=parametres_generaux&action=fonction_utilisateur`, id=37)

- **Paramètres généraux** *(référentiels)*
  - Écrans (dans `parametres_generaux`) :
    - Années académiques, Semestres, Niveaux d’étude, UE/ECUE
    - Grades, Spécialités, Statuts jury, Salles
    - Entreprises

- **Paramètres spécifiques** *(sécurité / plateforme)*
  - Écrans (dans `parametres_generaux`) :
    - Gestion des habilitations / permissions (`gestion_attribution`)
    - Niveaux d’accès (`niveaux_acces`)
    - Niveaux d’approbation (`niveaux_approbation`)
    - Messages système (`messages`)
    - Traitements menu (`traitements`)
    - Actions système (`actions`)

- **Audit & sauvegardes**
  - Écran : `Piste Audit` (`?page=piste_audit`, id=51)
  - Écran : `Sauvegarde/Restauration` (`?page=sauvegarde_restauration`, id=52)
  - Écran : `Historique` (`?page=admin_historique`, id=55)

- **Ressources humaines**
  - Écran : `Gestion Personnel` (`?page=gestion_rh`, id=29)

---

## Application en base

- Le script `docs/RESTRUCTURE_MENU_4_CATEGORIES.sql` regroupe déjà les **catégories** en 4 grands menus.
- La séparation **Paramètres généraux vs Paramètres spécifiques** peut se faire :
  - soit **dans l’UI** de `parametres_generaux` (deux sections / onglets),
  - soit en **créant deux entrées menu** (deux fonctionnalités parent) et en attachant les sous-pages (plus risqué car ça impacte l’affichage du menu selon les permissions).

Dis-moi quelle option tu veux pour “Paramètres généraux / Paramètres spécifiques”, et je te fournis le script SQL exact + les ajustements côté vues.


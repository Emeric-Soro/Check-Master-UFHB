# Liste des Corrections et Améliorations

## 1. Interface Utilisateur (UI) et Expérience (UX)

### Composants KPI

- Transformer les blocs d'indicateurs (ex : **"Total étudiants inscrits"**) en **hyperliens cliquables**.
- Cette modification doit être appliquée **partout où ce type de composant est utilisé**.

### Barre d'outils des soutenances

- Réduire la **largeur de la barre d'outils** dans l’écran **"Programmation des soutenances"**.
- Objectif : permettre au bouton **"Planning"** de s’afficher correctement.

### Formatage des montants

- Corriger le **séparateur de milliers dans la base de données** pour les valeurs monétaires.
- Exemple :
  - ❌ `500 FCFA`
  - ✅ `500 000 FCFA`

### Design des états vides

- Remplacer la **couleur noire** utilisée dans les états vides.
- Utiliser un **gris très clair** pour griser les éléments du tableau de bord lorsqu’aucune information n’est disponible.

---

## 2. Gestion des Soutenances et Exports (PDF)

### Mise à jour du PDF de planning

Modifier la génération du document de programmation :

- Ajouter les colonnes :
  - **Président**
  - **Maître de stage**
  - **Entreprise**
- Supprimer le système de **croix ("X")** actuellement utilisé pour indiquer les salles de soutenance.

### Ajout des soutenances manquantes

- Saisir les **soutenances qui n’apparaissent pas encore** dans le système.

### Liaison des données de stage

- Vérifier que les **rapports/dossiers étudiants** contiennent les informations de stage nécessaires.
- Permettre la **remontée automatique du "Maître de stage"**.

---

## 3. Fonctionnalités et Administration

### Espace Enseignant

- Ajouter l’affichage du **"Maître de stage"** dans l’interface dédiée aux enseignants.

### Piste d'audit (Logs)

- Améliorer la **visibilité et la richesse des logs**.
- Mettre en place un **historique détaillé des actions pour chaque utilisateur du système**.

### Mot de passe

- Vérifier et **réintégrer l’option permettant de modifier son mot de passe**, qui semble avoir disparu.

### Actions Administrateur

- Corriger les fonctionnalités liées à la **gestion de l’année académique** qui sont actuellement **inopérantes pour l’administrateur**.

---

## 4. Données et Configuration Système

### Identifiants Étudiants

- Effectuer une **révision complète des identifiants étudiants**.
- Tenir compte des **observations reçues**.

### Fuseau horaire

- Ajuster la gestion des heures dans toute l’application.
- Objectif : alignement sur le **fuseau horaire GMT+1**.

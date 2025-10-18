# 📖 Guide Utilisateur - Check Master UFHB

Guide complet pour utiliser l'application de gestion des soutenances académiques.

## Table des Matières

1. [Connexion à l'Application](#connexion-à-lapplication)
2. [Tableau de Bord](#tableau-de-bord)
3. [Gestion des Candidatures](#gestion-des-candidatures)
4. [Évaluation des Soutenances](#évaluation-des-soutenances)
5. [Gestion des Jurys](#gestion-des-jurys)
6. [Génération de Documents](#génération-de-documents)
7. [Administration](#administration)

---

## 🔐 Connexion à l'Application

### Première Connexion

1. Accédez à l'application via http://localhost:8080
2. Sur la page de connexion, entrez vos identifiants :
   - **Nom d'utilisateur** ou **Email**
   - **Mot de passe**
3. Cliquez sur "Se connecter"

### Réinitialisation du Mot de Passe

Si vous avez oublié votre mot de passe :

1. Cliquez sur "Mot de passe oublié ?" sur la page de connexion
2. Entrez votre adresse email
3. Suivez les instructions reçues par email pour réinitialiser

---

## 📊 Tableau de Bord

Une fois connecté, vous accédez au tableau de bord adapté à votre profil :

### Dashboard Étudiant
- **Mes Candidatures** : Voir l'état de vos demandes de soutenance
- **Dossier Académique** : Consulter vos notes et résultats
- **Soumettre une Candidature** : Déposer une nouvelle demande

### Dashboard Enseignant
- **Soutenances à Évaluer** : Liste des soutenances assignées
- **Mes Jurys** : Jurys auxquels vous participez
- **Calendrier** : Planning des soutenances

### Dashboard Administratif
- **Statistiques Générales** : Aperçu des soutenances
- **Candidatures en Attente** : Demandes à traiter
- **Gestion des Utilisateurs** : Administration des comptes

---

## 📝 Gestion des Candidatures

### Pour les Étudiants

#### Soumettre une Candidature

1. Accédez à **"Soumettre une Candidature"** depuis le menu
2. Remplissez le formulaire :
   - **Informations Personnelles** (pré-remplies)
   - **Titre du Mémoire**
   - **Résumé** (250 mots minimum)
   - **Directeur de Mémoire**
   - **Informations de Stage** (si applicable)
3. Téléchargez les documents requis :
   - Mémoire (PDF)
   - Rapport de stage (si applicable)
   - Attestation d'entreprise
4. Cliquez sur **"Soumettre la candidature"**

#### Suivre ma Candidature

1. Allez dans **"Mes Candidatures"**
2. Consultez le statut :
   - 🟡 **En attente** : Dossier en cours de vérification
   - 🟢 **Validé** : Candidature acceptée
   - 🔴 **Rejeté** : Candidature refusée (voir motif)
   - ⏳ **En cours d'évaluation** : Soutenance en cours
   - ✅ **Complété** : Soutenance terminée

### Pour les Administrateurs

#### Valider une Candidature

1. Menu **"Gestion des Candidatures"**
2. Cliquez sur une candidature **"En attente"**
3. Vérifiez les documents téléchargés
4. Actions possibles :
   - **Valider** : Accepter la candidature
   - **Rejeter** : Refuser avec justification
   - **Demander des corrections** : Demander des modifications

#### Planifier une Soutenance

1. Sélectionnez une candidature **validée**
2. Cliquez sur **"Planifier la soutenance"**
3. Définissez :
   - **Date et heure**
   - **Lieu**
   - **Composition du jury** (président, membres)
4. Enregistrez la planification
5. Les notifications sont envoyées automatiquement

---

## 🎯 Évaluation des Soutenances

### Pour les Membres du Jury

#### Accéder à l'Évaluation

1. Menu **"Mes Soutenances"** ou **"Soutenances à Évaluer"**
2. Sélectionnez la soutenance
3. Consultez le dossier de l'étudiant (mémoire, CV, etc.)

#### Remplir la Grille d'Évaluation

La grille comprend plusieurs critères notés :

1. **Qualité du Document (Mémoire)**
   - Structure et organisation
   - Qualité rédactionnelle
   - Bibliographie et références

2. **Présentation Orale**
   - Clarté de l'exposé
   - Gestion du temps
   - Support de présentation

3. **Maîtrise du Sujet**
   - Compréhension du sujet
   - Analyse et réflexion critique
   - Méthodologie

4. **Réponses aux Questions**
   - Pertinence des réponses
   - Argumentation
   - Capacité à défendre son travail

#### Soumettre l'Évaluation

1. Notez chaque critère selon la grille
2. Ajoutez des **commentaires** (optionnel mais recommandé)
3. Cliquez sur **"Enregistrer l'évaluation"**

> ⚠️ **Note** : Une fois soumise, l'évaluation ne peut plus être modifiée sans l'autorisation d'un administrateur.

### Calcul des Moyennes

L'application calcule automatiquement :
- **Note de soutenance** : Moyenne des évaluations des membres du jury
- **Note finale** : 
  ```
  Note Finale = (Moyenne M1 × 2 + Moyenne S1 M2 × 3 + Mémoire × 3) / 8
  ```

---

## 👥 Gestion des Jurys

### Composition d'un Jury

Un jury de soutenance comprend :
- **1 Président** (généralement un Professeur)
- **2-3 Membres** (Enseignants-chercheurs)
- **1 Directeur de Mémoire** (évaluateur)

### Assigner des Membres au Jury

1. Menu **"Gestion des Jurys"**
2. Sélectionnez une soutenance
3. Cliquez sur **"Composer le jury"**
4. Pour chaque rôle :
   - Sélectionnez l'enseignant dans la liste
   - Définissez son rôle (Président/Membre/Directeur)
5. Enregistrez la composition

### Notifications Automatiques

Les membres du jury reçoivent automatiquement :
- Email de nomination avec détails de la soutenance
- Rappels avant la date de soutenance
- Accès au dossier de l'étudiant

---

## 📄 Génération de Documents

L'application génère automatiquement plusieurs documents officiels.

### Types de Documents

#### 1. Procès-Verbal (PV)
- Généré après la délibération du jury
- Contient la note finale et la mention
- Signé par le président du jury

#### 2. Annexes
- **Annexe 1** : Grille d'évaluation détaillée
- **Annexe 2** : Calcul des moyennes (M1, S1 M2, Mémoire)
- **Annexe 3** : Critères d'évaluation

#### 3. Attestation de Réussite
- Délivrée si la soutenance est réussie
- Mentionne la note et l'année académique

### Générer un Document

1. Accédez au dossier de soutenance
2. Cliquez sur **"Documents"**
3. Sélectionnez le type de document :
   - Procès-Verbal
   - Annexes (1, 2, 3)
   - Attestation
4. Cliquez sur **"Générer PDF"**
5. Le document s'ouvre dans un nouvel onglet
6. Téléchargez ou imprimez selon besoin

### Archivage des Documents

Tous les documents générés sont automatiquement :
- Archivés dans le système
- Accessibles depuis **"Archives"**
- Liés au dossier de l'étudiant

---

## ⚙️ Administration

### Gestion des Utilisateurs

#### Créer un Nouvel Utilisateur

1. Menu **"Gestion des Utilisateurs"**
2. Cliquez sur **"Ajouter un utilisateur"**
3. Remplissez le formulaire :
   - Informations personnelles
   - Type d'utilisateur (Étudiant/Enseignant/Admin)
   - Email et identifiants
   - Groupe d'utilisateur et permissions
4. Enregistrez

#### Modifier les Droits d'Accès

1. Sélectionnez un utilisateur
2. Cliquez sur **"Modifier les droits"**
3. Choisissez le groupe d'utilisateur approprié
4. Définissez les actions autorisées
5. Enregistrez

### Paramètres Généraux

Accessible via **"Paramètres Généraux"**, permet de gérer :

- **Années Académiques** : Création et activation
- **Grades et Fonctions** : Grades universitaires des enseignants
- **Spécialités et Niveaux d'Étude**
- **UE et ECUE** : Unités d'enseignement
- **Statuts de Jury**
- **Niveaux d'Approbation** : Workflow de validation
- **Entreprises** : Partenaires de stage

### Gestion des Notes

1. Menu **"Gestion des Notes"**
2. Sélectionnez l'année académique et le semestre
3. Options disponibles :
   - Consulter les notes par étudiant
   - Modifier les notes (avec droits admin)
   - Exporter les résultats

### Sauvegarde et Restauration

#### Effectuer une Sauvegarde

1. Menu **"Sauvegarde et Restauration"**
2. Cliquez sur **"Créer une sauvegarde"**
3. Type de sauvegarde :
   - Complète (base de données + fichiers)
   - Base de données uniquement
4. La sauvegarde est créée et téléchargeable

#### Restaurer depuis une Sauvegarde

1. Menu **"Sauvegarde et Restauration"**
2. Cliquez sur **"Restaurer"**
3. Sélectionnez le fichier de sauvegarde
4. Confirmez la restauration

> ⚠️ **Attention** : La restauration écrase les données actuelles. Faites une sauvegarde avant toute restauration.

### Audit et Logs

1. Menu **"Audit"**
2. Consultez l'historique des actions :
   - Connexions/déconnexions
   - Modifications de données
   - Actions administratives
3. Filtrez par :
   - Date
   - Utilisateur
   - Type d'action

---

## 💡 Conseils et Bonnes Pratiques

### Pour les Étudiants

- ✅ Préparez tous vos documents **avant** de soumettre votre candidature
- ✅ Vérifiez que votre mémoire est au **format PDF**
- ✅ Assurez-vous que vos informations de contact sont **à jour**
- ✅ Soumettez votre candidature **au moins 3 semaines avant** la date souhaitée
- ✅ Consultez régulièrement l'état de votre candidature

### Pour les Enseignants

- ✅ Consultez vos **soutenances assignées** régulièrement
- ✅ Téléchargez et lisez le **mémoire à l'avance**
- ✅ Remplissez la grille d'évaluation **pendant ou juste après** la soutenance
- ✅ Ajoutez des **commentaires constructifs** pour l'étudiant

### Pour les Administrateurs

- ✅ Effectuez des **sauvegardes régulières** (hebdomadaires recommandé)
- ✅ Vérifiez les **candidatures en attente** quotidiennement
- ✅ Planifiez les soutenances avec un **délai raisonnable**
- ✅ Assurez-vous que les **jurys sont complets** avant la date de soutenance
- ✅ Consultez les **logs d'audit** pour détecter les anomalies

---

## 🆘 Besoin d'Aide ?

Si vous rencontrez des difficultés :

1. Consultez la [FAQ](./FAQ.md) pour les problèmes courants
2. Vérifiez que vous avez les **droits d'accès** nécessaires
3. Contactez l'**assistance technique**
4. Ouvrez une [issue](https://github.com/Emeric-Soro/Check-Master-UFHB/issues) sur GitHub

---

**Dernière mise à jour** : Octobre 2025

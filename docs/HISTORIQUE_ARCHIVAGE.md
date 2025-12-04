# Module Historique et Archivage - Documentation

## Vue d'ensemble

Le module "Historique et Archivage" permet aux administrateurs système de gérer les archives des soutenances passées. Il offre la possibilité d'importer des données historiques via fichier CSV et de consulter/modifier ces données à travers une interface web intuitive.

## Fonctionnalités principales

### 1. Importation de données historiques

Le système permet d'importer des archives de soutenances à partir de fichiers CSV. L'import gère automatiquement la création des entités manquantes (années académiques, enseignants, salles, entreprises).

#### Format du fichier CSV

Le fichier CSV doit contenir **20 colonnes** dans l'ordre suivant :

| # | Colonne | Type | Requis | Description |
|---|---------|------|--------|-------------|
| 1 | ANNEE_ACAD | Texte | Oui | Format: 2010-2011 |
| 2 | MATRICULE | Nombre | Oui | Matricule unique de l'étudiant |
| 3 | NOM | Texte | Oui | Nom de famille |
| 4 | PRENOMS | Texte | Oui | Prénoms |
| 5 | THEME | Texte | Oui | Thème du mémoire/rapport |
| 6 | ENTREPRISE | Texte | Oui | Nom de l'entreprise d'accueil |
| 7 | MAITRE_STAGE | Texte | Oui | Nom du maître de stage |
| 8 | ENCADREUR_PEDA | Texte | Oui | Nom complet de l'encadreur pédagogique |
| 9 | DIRECTEUR_MEMOIRE | Texte | Non | Nom complet du directeur (vide pour anciennes années) |
| 10 | DATE_COMMISSION | Date | Oui | Format: YYYY-MM-DD |
| 11 | AVIS_COMMISSION | Texte | Oui | Ex: Validé, Approuvé, Rejeté |
| 12 | OBSERVATIONS | Texte | Non | Commentaires/observations |
| 13 | DATE_SOUTENANCE | Date | Non | Format: YYYY-MM-DD |
| 14 | HEURE | Heure | Non | Format: HH:MM |
| 15 | SALLE | Texte | Non | Nom de la salle |
| 16 | PRESIDENT_JURY | Texte | Non | Nom complet du président du jury |
| 17 | EXAMINATEUR | Texte | Non | Nom complet de l'examinateur |
| 18 | NOTE_MEMOIRE | Nombre | Non | Note sur 20 |
| 19 | MOYENNE_M1 | Nombre | Non | Moyenne M1 |
| 20 | MOYENNE_M2_S1 | Nombre | Non | Moyenne M2 S1 |

#### Exemple de fichier CSV

```csv
ANNEE_ACAD,MATRICULE,NOM,PRENOMS,THEME,ENTREPRISE,MAITRE_STAGE,ENCADREUR_PEDA,DIRECTEUR_MEMOIRE,DATE_COMMISSION,AVIS_COMMISSION,OBSERVATIONS,DATE_SOUTENANCE,HEURE,SALLE,PRESIDENT_JURY,EXAMINATEUR,NOTE_MEMOIRE,MOYENNE_M1,MOYENNE_M2_S1
2010-2011,20100001,KOUASSI,Jean-Baptiste,Développement application web,SODECI,TRAORE Ibrahim,KOUA Brou,,,2011-06-15,Validé,Bon travail,,,,,,,,
2023-2024,20230001,ASSANDE,Patrick,Gestion de flotte,SITARAIL,KOFFI Jean,KOUA Brou,SORO Emeric,2024-05-15,Validé,Excellent,2024-09-25,08:30,Amphi A,SORO Emeric,N'GUESSAN Paul,15.5,14.5,15.0
```

### 2. Consultation de l'historique

#### 2.1 Historique des Étudiants

Vue principale affichant un tableau de tous les étudiants archivés avec:
- Matricule
- Nom et prénoms
- Thème
- Entreprise
- Année académique
- Statut (Validé/Rejeté/En cours)

**Filtres disponibles:**
- Par année académique
- Par statut
- Recherche par nom/matricule

**Pagination:** 20 résultats par page

#### 2.2 Historique des Jurys

Vue dédiée affichant la composition des jurys avec:
- Date de soutenance
- Étudiant concerné
- Président du jury
- Examinateur
- Encadreur
- Directeur de mémoire (si applicable)
- Année académique

### 3. Fiche détaillée d'étudiant

En cliquant sur un étudiant, une fiche complète s'affiche avec:

#### Informations personnelles
- Matricule (non modifiable)
- Nom (modifiable)
- Prénoms (modifiable)
- Email (modifiable)

#### Informations de stage
- Entreprise
- Maître de stage
- Dates de stage
- Sujet

#### Thème et validation
- Thème du mémoire (modifiable)
- Date de validation commission
- Statut (modifiable)
- Observations

#### Encadrement
- Encadreur pédagogique
- Directeur de mémoire (si applicable)

#### Soutenance
- Date et heure
- Salle
- Composition du jury
- Notes et évaluations

**Actions disponibles:**
- Modifier les informations
- Enregistrer les modifications
- Retour à la liste

## Installation et Configuration

### 1. Installation des fichiers

Les fichiers suivants ont été créés:
```
app/controllers/ArchiveController.php
app/models/Archive.php
app/utils/ExcelImportService.php
ressources/routes/archiveHistoryRoutes.php
ressources/views/admin_historique.php
ressources/views/fiche_etudiant_archive.php
ressources/views/import_result.php
docs/add_archive_menu.sql
docs/sample_archive_data.csv
```

### 2. Configuration de la base de données

Exécuter le script SQL pour ajouter le menu:
```sql
mysql -u root -p nom_base_de_donnees < docs/add_archive_menu.sql
```

Ou manuellement via phpMyAdmin en exécutant le contenu de `docs/add_archive_menu.sql`.

### 3. Vérification des permissions

S'assurer que les groupes d'utilisateurs suivants ont accès:
- Administrateur Système
- Personnel administratif (optionnel)

## Utilisation

### Import de données

1. Se connecter en tant qu'administrateur système
2. Naviguer vers "Historique et Archivage"
3. Cliquer sur "Choisir un fichier" dans la section "Importer des Archives"
4. Sélectionner un fichier CSV conforme au format
5. Cliquer sur "Importer"
6. Consulter le résultat de l'import

**Notes importantes:**
- Le fichier doit être au format CSV avec séparateur virgule (,)
- Les fichiers Excel (.xlsx, .xls) doivent d'abord être convertis en CSV
- L'import utilise des transactions : soit tout réussit, soit rien n'est importé (rollback)
- Les erreurs sont détaillées ligne par ligne

### Consultation et modification

1. Utiliser les filtres pour trouver des étudiants spécifiques
2. Cliquer sur une ligne pour voir la fiche détaillée
3. Modifier les champs nécessaires
4. Cliquer sur "Enregistrer les modifications"

## Logique métier

### Création automatique d'entités

Le système crée automatiquement les entités manquantes:

#### Années académiques
- Format détecté: "2010-2011"
- Converti en: date_deb=2010-09-01, date_fin=2011-08-31

#### Enseignants
- Créés avec le nom complet fourni
- Email généré: prenom.nom@ufhb.edu.ci
- Type: Simple
- Spécialité: 1 (par défaut)

#### Salles
- Créées avec le libellé fourni

#### Entreprises
- Créées avec le nom fourni

### Rétrocompatibilité

Le système gère la rétrocompatibilité pour les anciennes années:
- **Directeur de mémoire vide**: Le système comprend qu'il s'agit d'une ancienne soutenance où ce rôle n'existait pas
- Les champs optionnels peuvent être vides sans bloquer l'import

### Gestion des erreurs

Chaque ligne est traitée dans une transaction séparée:
- Si une erreur survient, la ligne est ignorée
- Les autres lignes continuent d'être traitées
- Un rapport détaillé est généré avec:
  - Nombre de succès
  - Nombre d'erreurs
  - Liste des erreurs avec numéro de ligne

## Tables de la base de données concernées

Le module interagit avec les tables suivantes:
- `etudiants` - Informations des étudiants
- `annee_academique` - Années académiques
- `entreprises` - Entreprises d'accueil
- `informations_stage` - Détails des stages
- `rapport_etudiants` - Rapports/mémoires
- `enseignants` - Corps enseignant
- `affecter` - Affectation encadreur/directeur
- `valider` - Validations de commission
- `programmer` - Programmation des soutenances
- `salles` - Salles de soutenance
- `composer_jury` - Composition des jurys
- `evaluer` - Évaluations et notes
- `roles_jury` - Rôles dans le jury

## Sécurité

- Accès réservé aux administrateurs système
- Validation des données à l'import
- Protection contre les injections SQL (requêtes préparées)
- Transactions pour garantir l'intégrité des données
- Logs d'audit pour toutes les actions (import, modification)

## Limitations actuelles

1. **Format de fichier**: Seuls les fichiers CSV sont supportés actuellement
   - Les fichiers Excel doivent être convertis en CSV avant import
   - Utiliser "Enregistrer sous" → "CSV (séparateur: virgule)" dans Excel

2. **Colonnes fixes**: Le nombre et l'ordre des colonnes sont fixes (20 colonnes)

3. **Gestion des doublons**: 
   - Les matricules existants ne sont pas réimportés
   - Aucune mise à jour automatique des données existantes

## Support et dépannage

### Erreurs courantes

**"Type de fichier non supporté"**
- Vérifier que le fichier est bien au format CSV
- Convertir les fichiers Excel en CSV

**"Matricule manquant" ou "Nom/prénoms manquants"**
- Vérifier que toutes les colonnes requises sont renseignées
- S'assurer qu'il n'y a pas de lignes vides dans le fichier

**"Format d'année académique invalide"**
- Utiliser le format: YYYY-YYYY (ex: 2010-2011)
- Vérifier qu'il n'y a pas d'espaces

**"Impossible d'ouvrir le fichier"**
- Vérifier les permissions du répertoire d'upload
- S'assurer que le fichier n'est pas corrompu

### Logs

Les erreurs sont enregistrées dans:
- `error_log` PHP (check avec `tail -f /var/log/apache2/error.log`)
- Table `audit_log` (pour les actions d'import et modification)

## Évolutions futures possibles

1. Support direct des fichiers Excel (.xlsx, .xls) via PhpSpreadsheet
2. Export des données historiques vers CSV/Excel
3. Statistiques et graphiques sur les années passées
4. Recherche avancée avec critères multiples
5. Import incrémental avec mise à jour des données existantes
6. Validation automatique des données avant import
7. Templates Excel téléchargeables
8. Aperçu des données avant import

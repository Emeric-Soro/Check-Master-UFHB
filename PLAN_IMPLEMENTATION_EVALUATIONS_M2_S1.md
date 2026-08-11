# Plan d’implémentation — Évaluations M2/S1

## État de réalisation

Implémentation réalisée dans CheckMaster pour le semestre canonique `M2_S1`, avec prise en charge des alias `S3`, `S9` et `M2 S1`.

### 1. Modèle de données — réalisé

- Migration rejouable ajoutée : `database/migrations/20260803_evaluations_m2_s1.sql`.
- Référentiel des semestres et table des alias.
- Table `ue` versionnée avec code officiel, libellé, crédits, date d’effet, parcours, niveau, ordre et état actif.
- Protection du versionnement : une UE déjà utilisée par une évaluation n’est pas modifiée ; une nouvelle version est créée.
- Tables `evaluation_s3`, `evaluation_s3_import_batch` et `evaluation_s3_import_row`.
- Unicité étudiant/UE/année/session.
- Conservation des notes normales et de rattrapage.
- Calcul centralisé des totaux pondérés et des moyennes.
- Colonnes `etudiants.nouveau` et `cycle_etudiant_statut.nouveau_m2`.
- Calcul automatique Nouveau/Redoublant à partir de l’historique M2.
- Table `suivi_encadrement_rdv` pour les rendez-vous d’encadrement.

### 2. Services et interfaces — réalisé

- `UeReferentielService` pour les UE, alias, versions et contrôles.
- `EvaluationS3Service` pour la grille, la sauvegarde transactionnelle, les calculs et le statut étudiant.
- Interface M2/S1 avec recherche étudiant, année, statut, notes normales/rattrapage, crédits, totaux et moyennes.
- Distinction correcte entre cellule vide et note zéro.
- Verrouillage possible des années anciennes via les contrôles de droits existants.
- Raccordement des moyennes détaillées M2 aux anciens relevés avec repli compatible vers `notes`.
- Onglet de gestion du référentiel UE.

### 3. Import Excel — réalisé

- Import XLS/XLSX/CSV via PhpSpreadsheet.
- Modèle officiel ajouté : `ressources/templates/evaluations_s3_template.csv`.
- Normalisation des dates, décimales, sessions et alias de semestre.
- Prévisualisation des lignes valides, erreurs, avertissements et doublons.
- Contrôle des étudiants, années, codes UE, notes, dates et versions applicables.
- Confirmation atomique par lot auditable.
- Rejouabilité sans doublon par mise à jour contrôlée.
- Lignes rejetées conservées avec leur motif.

### 4. Documents — réalisé

- Générateur `PV_EPREUVES_ECRITES` avec tableau à 8 colonnes, deux sessions, crédits, totaux, moyennes, soutenance et signatures.
- Générateurs pour l’autorisation de soutenance et les deux variantes de suivi d’encadrement.
- Enregistrement dans le registre documentaire et `document_genere`.
- Contrôle d’accès étudiant ajouté pour les documents générés.
- Formulaires de suivi compatibles avec les rendez-vous enregistrés.

### 5. Commission — réalisé

- Agrégation des membres ayant reçu un dossier, renseigné une observation ou validé.
- Lien vers le détail filtré par membre et commission.
- Affichage du commentaire, de la décision, de la date et du rapport concerné.
- Affichage automatique de `RAS` lorsqu’aucune observation n’est renseignée.

### 6. Migration et compatibilité — réalisé

- Exécution locale réussie sur la base configurée.
- Migration adaptée aux types et jeux de caractères réels de la base.
- Ancienne table `notes` conservée.
- Aucune note par UE inventée à partir d’une moyenne globale.
- PDO configuré en mode d’erreur exception et requêtes tamponnées.
- Script d’application ajouté : `scripts/apply_migration.php`.

## Vérifications effectuées

- Migration appliquée localement avec succès : 17 instructions exécutées.
- Suite de tests existante et tests M2/S1 passés : 104 tests, 104 réussis.
- Tests de calcul pondéré, alias, import, confirmation et rejouabilité réalisés.
- Lint PHP et `git diff --check` passés.
- Génération réelle vérifiée pour un PV avec 9 UE, une autorisation et une fiche de suivi ; les données de test ont été supprimées après vérification.

## Reste à faire avant mise en production

1. Renseigner les codes officiels et les crédits validés par la scolarité dans l’onglet Référentiel UE.
2. Effectuer la recette visuelle métier des PDF sur l’environnement cible : logos, en-têtes, sauts de page, signatures et conformité au modèle fourni.
3. Exécuter la migration sur la base de production ou de recette avec sauvegarde préalable :

   ```text
   php scripts/apply_migration.php database/migrations/20260803_evaluations_m2_s1.sql
   ```

4. Tester les permissions avec les profils administrateur, scolarité, commission et étudiant.
5. Importer un fichier réel en prévisualisation, corriger les erreurs, puis confirmer le lot.
6. Comparer quelques moyennes M2 détaillées avec les anciennes moyennes avant d’activer la nouvelle source comme référence officielle.
7. Ajouter, si souhaité, un test navigateur automatisé couvrant l’ouverture de la grille, la saisie et la confirmation d’import.

## Fichiers principaux

- `database/migrations/20260803_evaluations_m2_s1.sql`
- `scripts/apply_migration.php`
- `app/Services/UeReferentielService.php`
- `app/Services/EvaluationS3Service.php`
- `app/Services/EvaluationS3ImportService.php`
- `app/Services/Document/PvEpreuvesEcritesGeneratorService.php`
- `app/Services/Document/SoutenanceFormPdfService.php`
- `ressources/views/evaluation_s3_content.php`
- `ressources/views/ue_referentiel_content.php`
- `ressources/templates/evaluations_s3_template.csv`
- `tests/Integration/evaluations_s3_test.php`

Les modifications déjà présentes dans le dépôt ont été conservées.

# Installation rapide - Module Historique et Archivage

## Étapes d'installation

### 1. Ajouter le menu dans la base de données

Exécutez ce script SQL pour ajouter le menu "Historique et Archivage" accessible aux administrateurs:

```bash
mysql -u root -p votre_base_de_donnees < docs/add_archive_menu.sql
```

Ou via phpMyAdmin, copiez-collez le contenu de `docs/add_archive_menu.sql` et exécutez-le.

### 2. Vérifier les fichiers

Tous les fichiers nécessaires sont déjà en place:
- ✅ Controllers, Models, Views, Routes
- ✅ Documentation et exemples

### 3. Tester l'accès

1. Connectez-vous en tant qu'administrateur système
2. Le menu "Historique et Archivage" devrait apparaître dans la barre latérale
3. Cliquez dessus pour accéder au module

### 4. Tester l'import

Un fichier d'exemple est disponible: `docs/sample_archive_data.csv`

1. Dans le module, cliquez sur "Choisir un fichier"
2. Sélectionnez `docs/sample_archive_data.csv`
3. Cliquez sur "Importer"
4. Vérifiez les résultats

## Vérifications

### Si le menu n'apparaît pas

Vérifiez que votre utilisateur appartient au groupe "Administrateur Système":

```sql
SELECT gu.lib_GU 
FROM groupe_utilisateur gu
INNER JOIN utilisateurs u ON u.id_GU = gu.id_GU
WHERE u.id_utilisateur = VOTRE_ID;
```

Si nécessaire, ajoutez manuellement l'accès au menu:

```sql
-- Récupérer l'ID du traitement
SELECT id_traitement FROM traitement WHERE lib_traitement = 'admin_historique';

-- Ajouter l'accès pour votre groupe (remplacez ID_GU et ID_TRAITEMENT)
INSERT INTO avoir (id_GU, id_traitement) VALUES (ID_GU, ID_TRAITEMENT);
```

### Si l'import ne fonctionne pas

1. Vérifiez les logs d'erreur PHP
2. Assurez-vous que le fichier est bien au format CSV avec séparateur virgule
3. Vérifiez que toutes les colonnes requises sont présentes

## Support

Consultez la documentation complète: `docs/HISTORIQUE_ARCHIVAGE.md`

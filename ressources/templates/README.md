# Modèles de Documents - Guide de Création

Ce dossier contient les modèles Word (.docx) utilisés pour générer des documents PDF.

## Comment créer un modèle

### 1. Modèle PV de Soutenance (pv_soutenance.docx)

#### Structure recommandée :

**En-tête du document :**
```
UNIVERSITÉ FÉLIX HOUPHOUËT-BOIGNY
PROCÈS-VERBAL DE SOUTENANCE
Année Académique 2024-2025
```

**Corps du document :**
```
NIVEAU : ${niveau}
PROMOTION : ${promotion}

ÉTUDIANT : ${nom_etudiant}

THÈME DE MÉMOIRE :
${theme}

DATE DE SOUTENANCE : ${date_soutenance}

MEMBRES DU JURY :
- Président : ${president}
- Examinateur : ${examinateur}
- Directeur de mémoire : ${directeur}
- Encadreur : ${encadreur}
- Maître de stage : ${maitre_stage}

ÉVALUATION :

[Créer un tableau avec les colonnes suivantes]
Critère          | Barème | Note
${lib_critere}   | ${bareme} | ${note}

TOTAL : ${note_finale} / ${total_bareme}

MOYENNES :
- Moyenne Master 1 : ${moyenne_master1}
- Moyenne S1 Master 2 : ${moyenne_s1_master2}
- Note du mémoire : ${note_memoire}

NOTE FINALE : ${note_finale_pv}
MENTION : ${mention}
```

### 2. Placeholders Disponibles

#### Informations Étudiant :
- `${nom_etudiant}` - Nom complet de l'étudiant
- `${promotion}` - Promotion (ex: Master 2 GLSI 2024)
- `${niveau}` - Niveau d'étude (Master 1 / Master 2)

#### Soutenance :
- `${date_soutenance}` - Date de la soutenance (format dd/mm/yyyy)
- `${theme}` - Thème du mémoire

#### Jury :
- `${president}` - Président du jury
- `${examinateur}` - Examinateur
- `${directeur}` - Directeur de mémoire
- `${encadreur}` - Encadreur
- `${maitre_stage}` - Maître de stage

#### Notes et Moyennes :
- `${note_finale}` - Note finale de soutenance
- `${total_bareme}` - Total du barème
- `${moyenne_master1}` - Moyenne Master 1
- `${moyenne_s1_master2}` - Moyenne S1 Master 2
- `${note_memoire}` - Note du mémoire
- `${note_finale_pv}` - Note finale du PV
- `${mention}` - Mention obtenue

#### Tableau Répétitif (Critères d'évaluation) :
Dans un tableau Word, créez une ligne de données avec :
- `${lib_critere}` - Libellé du critère
- `${bareme}` - Barème du critère  
- `${note}` - Note obtenue

Le système dupliquera automatiquement cette ligne pour chaque critère.
**Important**: Le premier champ du tableau (`${lib_critere}`) sera utilisé comme référence pour la duplication.

## Instructions pour créer le modèle

1. Ouvrez Microsoft Word
2. Créez votre document avec la mise en page souhaitée
3. Insérez les placeholders en respectant la syntaxe `${nom_variable}`
4. Pour les tableaux répétitifs, créez un tableau et utilisez les placeholders dans les cellules
5. Enregistrez le fichier au format .docx
6. Téléversez-le via l'interface "Gestion des Modèles de Documents"

## Exemple de Tableau

Dans Word, créez un tableau comme ceci :

| Critère | Barème | Note |
|---------|--------|------|
| ${lib_critere} | ${bareme} | ${note} |

Le système remplacera cette ligne par autant de lignes qu'il y a de critères d'évaluation.

## Notes Importantes

- N'utilisez que des caractères alphanumériques et underscores pour les noms de placeholders
- Respectez exactement la casse (majuscules/minuscules)
- Les placeholders inexistants seront ignorés
- Testez toujours votre modèle après l'avoir créé

## Support

Pour voir la liste complète des placeholders disponibles, consultez la documentation dans l'application :
Paramètres Généraux > Modèles de Documents > Voir les placeholders disponibles

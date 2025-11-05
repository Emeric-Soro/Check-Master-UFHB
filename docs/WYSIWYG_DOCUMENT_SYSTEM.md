# Système d'Édition et de Génération de Documents WYSIWYG

## Vue d'ensemble

Ce système fournit une expérience d'édition de documents intégrée, intuitive et fidèle ("What You See Is What You Get") directement dans le navigateur, avec génération de PDF pixel-perfect.

## Architecture

### Composants Principaux

1. **Éditeur Frontend : CKEditor 5**
   - Interface d'édition riche simulant une page A4
   - Configuration en français avec toolbar adapté
   - Auto-sauvegarde toutes les 30 secondes

2. **Service Backend : DocumentGeneratorService.php**
   - Orchestrateur de la génération de documents
   - Support de deux pipelines distincts
   - Gestion des erreurs robuste

3. **Service de Conversion : Gotenberg (Docker)**
   - Moteur Chromium : HTML → PDF (Pipeline A)
   - Moteur LibreOffice : DOCX → PDF (Pipeline B)

4. **Service de Sécurité : HTMLPurifierService.php**
   - Purification du HTML pour prévenir les attaques XSS
   - Configuration personnalisable des tags et CSS autorisés
   - Logging des modifications suspectes

## Pipelines de Génération

### Pipeline A : Contenu Riche Éditable

**Utilisation :** Rapports étudiants, Comptes rendus de commission

**Flux de travail :**
```
1. Utilisateur édite dans CKEditor
2. Contenu HTML sauvegardé (après purification)
3. Génération PDF : HTML → Gotenberg (Chromium) → PDF
```

**Implémentation :**
```php
// Dans un contrôleur
require_once __DIR__ . '/../utils/DocumentGeneratorService.php';
require_once __DIR__ . '/../utils/HTMLPurifierService.php';

// Purifier le contenu HTML
$contenu = HTMLPurifierService::purifyWithLogging($contenu_brut, 'contexte');

// Préparer le HTML stylé
$htmlComplet = "<!DOCTYPE html>
<html lang='fr'>
<head>
    <meta charset='UTF-8'>
    <style>
        body { 
            font-family: 'Times New Roman', Times, serif; 
            font-size: 12pt; 
            line-height: 1.6; 
        }
    </style>
</head>
<body>{$contenu}</body>
</html>";

// Générer le PDF
$documentService = new DocumentGeneratorService();
$pdfPath = $documentService->convertHtmlToPdf($htmlComplet, [
    'paperSize' => 'A4',
    'marginTop' => '2',
    'marginBottom' => '2',
    'marginLeft' => '2',
    'marginRight' => '2'
]);

// Envoyer au client ou sauvegarder
readfile($pdfPath);
$documentService->cleanupTempFile($pdfPath);
```

### Pipeline B : Documents Structurés Automatisés

**Utilisation :** Relevés de notes, PV de soutenance, Reçus

**Flux de travail :**
```
1. Collecte des données depuis la base de données
2. Remplissage du gabarit .docx avec PHPWord
3. Conversion : DOCX → Gotenberg (LibreOffice) → PDF
```

**Implémentation :**
```php
require_once __DIR__ . '/../utils/DocumentGeneratorService.php';

$documentService = new DocumentGeneratorService();

// Données à injecter dans le template
$data = [
    'nom_etudiant' => 'Dupont',
    'prenom_etudiant' => 'Jean',
    'notes' => [
        ['matiere' => 'Mathématiques', 'note' => '15'],
        ['matiere' => 'Physique', 'note' => '17']
    ]
];

// Générer le PDF depuis le template
$pdfPath = $documentService->generateFromTemplate('releve_notes', $data);

// Envoyer ou sauvegarder
readfile($pdfPath);
$documentService->cleanupTempFile($pdfPath);
```

## Sécurité

### Protection XSS

Tout contenu HTML provenant de l'éditeur est automatiquement purifié :

```php
require_once __DIR__ . '/../utils/HTMLPurifierService.php';

// Purification basique
$contenuPropre = HTMLPurifierService::purifyHTML($contenuBrut);

// Purification avec logging
$contenuPropre = HTMLPurifierService::purifyWithLogging($contenuBrut, 'rapport');

// Validation sans modification
$estPropre = HTMLPurifierService::isCleanHTML($contenu);
```

### Configuration de la Purification

Par défaut, HTMLPurifierService autorise :
- **Tags HTML :** h1-h6, p, strong, em, ul, ol, li, table, img, div, span, a, etc.
- **Propriétés CSS :** font-size, color, margin, padding, border, display, flex, etc.
- **Attributs :** style, href, src, alt, colspan, rowspan, etc.

## Auto-Sauvegarde

### Fonctionnement

L'éditeur CKEditor sauvegarde automatiquement :
- **Délai :** 30 secondes après chaque modification
- **Condition :** Seulement si le contenu a changé et les champs requis sont remplis
- **Notifications :** Indicateurs visuels pour l'utilisateur

### Code JavaScript

```javascript
// Auto-save sur changement de contenu
editor.model.document.on('change:data', () => {
    clearTimeout(autoSaveTimeout);
    autoSaveTimeout = setTimeout(performAutoSave, 30000);
});

function performAutoSave() {
    // Vérifications et sauvegarde AJAX
    if (content === lastSavedContent || !nomRapport || !themeRapport) {
        return;
    }
    
    // Envoyer au serveur via fetch API
    fetch(window.location.href, {
        method: 'POST',
        body: formData,
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    });
}
```

## Export CSV

### Utilisation

Pour exporter des données en CSV avec compatibilité Excel :

```php
require_once __DIR__ . '/../utils/DocumentGeneratorService.php';

$documentService = new DocumentGeneratorService();

// Données à exporter
$data = [
    ['ID' => 1, 'Nom' => 'Dupont', 'Email' => 'dupont@email.com'],
    ['ID' => 2, 'Nom' => 'Martin', 'Email' => 'martin@email.com']
];

// En-têtes
$headers = ['ID', 'Nom', 'Email'];

// Export direct (téléchargement)
$documentService->exportToCsv($data, $headers, 'export_etudiants', true);

// Ou sauvegarde dans un fichier
$csvPath = $documentService->exportToCsv($data, $headers, 'export_etudiants', false);
```

**Caractéristiques :**
- BOM UTF-8 pour compatibilité Excel
- Séparateur point-virgule (;)
- Nom de fichier nettoyé automatiquement

## Gestion des Erreurs

### Messages Utilisateur

Le système fournit des messages clairs selon le type d'erreur :

1. **Service Gotenberg indisponible :**
   ```
   "Le service de génération de documents est temporairement indisponible. 
    Veuillez réessayer plus tard."
   ```

2. **Erreur de conversion :**
   ```
   "Le service de génération de documents a retourné une erreur (Code: 500). 
    Veuillez vérifier les logs du serveur."
   ```

3. **Template manquant :**
   ```
   "Template not found: nom_template.docx"
   ```

### Logging

Toutes les erreurs sont loguées avec détails :

```php
// Exemple de log Gotenberg
error_log("Gotenberg a retourné une erreur (Code: 500): [détails sanitizés]");

// Exemple de log HTMLPurifier
error_log("HTMLPurifier removed significant content in context 'rapport': 
          10000 bytes -> 8000 bytes (20.0% reduction)");
```

## Configuration Gotenberg

### docker-compose.yml

```yaml
gotenberg:
  image: gotenberg/gotenberg:8
  ports:
    - "3000:3000"
  command:
    - "gotenberg"
    - "--api-timeout=60s"
```

### URL dans DocumentGeneratorService

```php
// Pour Pipeline B (LibreOffice)
$this->gotenbergUrl = 'http://gotenberg:3000/forms/libreoffice/convert';

// Pour Pipeline A (Chromium) - dans convertHtmlToPdf()
$gotenbergHtmlUrl = 'http://gotenberg:3000/forms/chromium/convert/html';
```

## Templates

### Structure des Gabarits

Les templates .docx se trouvent dans `ressources/templates/` :

- `rapport_etudiant.docx` : Gabarit pour rapports étudiants (Pipeline A init)
- `compte_rendu.docx` : Gabarit pour comptes rendus (Pipeline A init)
- `releve_notes.docx` : Gabarit pour relevés (Pipeline B)
- `pv_soutenance.docx` : Gabarit pour PV (Pipeline B)
- `recu_inscription.docx` : Gabarit pour reçus (Pipeline B)
- `recu_versement.docx` : Gabarit pour reçus de paiement (Pipeline B)

### Placeholders dans les Templates

**Format :** `${nom_variable}`

**Exemple releve_notes.docx :**
```
Relevé de Notes - ${annee_academique}

Étudiant : ${prenom_etudiant} ${nom_etudiant}
Matricule : ${matricule}

Matière         | Note
----------------|------
${matiere#1}    | ${note#1}
${matiere#2}    | ${note#2}
```

### Blocs Répétitifs

Pour les tableaux avec données variables :

```php
$data = [
    'nom_etudiant' => 'Dupont',
    'notes' => [
        ['matiere' => 'Math', 'note' => '15'],
        ['matiere' => 'Info', 'note' => '18']
    ]
];
```

Le template utilisera `${matiere#1}`, `${note#1}`, etc.

## Performances

### Temps de Réponse Attendus

- **Chargement CKEditor :** < 3 secondes
- **Auto-save AJAX :** < 500 ms
- **Génération PDF (Pipeline A) :** < 10 secondes (95% des cas)
- **Génération PDF (Pipeline B) :** < 10 secondes (95% des cas)

### Optimisations

1. **Cache HTMLPurifier :** 
   ```php
   $config->set('Cache.SerializerPath', sys_get_temp_dir() . '/htmlpurifier');
   ```

2. **Fichiers temporaires :**
   ```php
   $tempPath = sys_get_temp_dir() . '/';
   ```

3. **Nettoyage automatique :**
   ```php
   $documentService->cleanupTempFile($pdfPath);
   ```

## Tests

### Test de Génération PDF (Pipeline A)

```php
// Test dans un contrôleur ou script
require_once __DIR__ . '/../utils/DocumentGeneratorService.php';

$html = "<h1>Test Document</h1><p>Contenu de test</p>";
$documentService = new DocumentGeneratorService();

try {
    $pdfPath = $documentService->convertHtmlToPdf($html);
    echo "✓ PDF généré : $pdfPath\n";
    $documentService->cleanupTempFile($pdfPath);
} catch (Exception $e) {
    echo "✗ Erreur : " . $e->getMessage() . "\n";
}
```

### Test de Purification HTML

```php
require_once __DIR__ . '/../utils/HTMLPurifierService.php';

$dirtyHtml = '<script>alert("XSS")</script><p style="color:red">Test</p>';
$cleanHtml = HTMLPurifierService::purifyHTML($dirtyHtml);

echo "Original: $dirtyHtml\n";
echo "Purifié : $cleanHtml\n";
// Résultat attendu : <p style="color:red;">Test</p>
```

### Test CSV Export

```php
require_once __DIR__ . '/../utils/DocumentGeneratorService.php';

$documentService = new DocumentGeneratorService();
$data = [['Nom' => 'Test', 'Prénom' => 'User']];
$headers = ['Nom', 'Prénom'];

$csvPath = $documentService->exportToCsv($data, $headers, 'test', false);
$content = file_get_contents($csvPath);

// Vérifier BOM UTF-8
echo substr($content, 0, 3) === "\xEF\xBB\xBF" ? "✓ BOM UTF-8 OK\n" : "✗ BOM manquant\n";

// Vérifier séparateur
echo strpos($content, ';') !== false ? "✓ Séparateur ; OK\n" : "✗ Séparateur incorrect\n";

unlink($csvPath);
```

## Dépannage

### Erreur "Class ZipArchive not found"

**Problème :** Extension PHP zip manquante

**Solution :**
```bash
# Reconstruire l'image Docker
docker-compose down
docker-compose build --no-cache
docker-compose up -d
```

### Gotenberg ne répond pas

**Vérification :**
```bash
# Vérifier que le conteneur est actif
docker-compose ps

# Tester l'endpoint
curl -X POST http://localhost:3000/forms/chromium/convert/html \
  -F 'files=@test.html'
```

### PDF vide ou corrompu

**Causes possibles :**
1. HTML invalide → Vérifier la structure HTML
2. Timeout Gotenberg → Augmenter `--api-timeout`
3. Mémoire insuffisante → Vérifier les ressources Docker

**Debug :**
```php
// Vérifier la taille du PDF généré
if (filesize($pdfPath) < 1000) {
    error_log("PDF suspect : " . filesize($pdfPath) . " bytes");
}
```

## Bonnes Pratiques

### 1. Toujours Purifier le HTML

```php
// ✓ Bon
$contenu = HTMLPurifierService::purifyHTML($_POST['contenu']);

// ✗ Mauvais (risque XSS)
$contenu = $_POST['contenu'];
```

### 2. Gérer les Erreurs Gotenberg

```php
try {
    $pdfPath = $documentService->convertHtmlToPdf($html);
} catch (Exception $e) {
    error_log("Erreur génération PDF: " . $e->getMessage());
    // Message utilisateur clair
    $_SESSION['error'] = "Impossible de générer le PDF. Veuillez réessayer.";
}
```

### 3. Nettoyer les Fichiers Temporaires

```php
// Toujours nettoyer après utilisation
$pdfPath = $documentService->convertHtmlToPdf($html);
readfile($pdfPath);
$documentService->cleanupTempFile($pdfPath);
```

### 4. Utiliser le Pipeline Approprié

- **Pipeline A :** Contenu riche, éditable par l'utilisateur
- **Pipeline B :** Documents structurés, données depuis la BDD

## Support

Pour toute question ou problème :
1. Consulter les logs : `logs/` directory
2. Vérifier la configuration Docker
3. Tester Gotenberg indépendamment
4. Vérifier les permissions fichiers

## Références

- [CKEditor 5 Documentation](https://ckeditor.com/docs/ckeditor5/latest/)
- [Gotenberg Documentation](https://gotenberg.dev/)
- [HTMLPurifier Documentation](http://htmlpurifier.org/docs)
- [PHPWord Documentation](https://phpword.readthedocs.io/)

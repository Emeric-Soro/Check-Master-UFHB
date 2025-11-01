# Implementation Notes: CKEditor 5 Integration and Document Generation

## Overview
This document describes the changes made to replace TinyMCE with CKEditor 5 and implement DOCX to HTML conversion for document templates.

## Changes Made

### 1. DocumentGeneratorService Enhancement
**File**: `app/utils/DocumentGeneratorService.php`

Added `convertDocxToHtml()` method that:
- Accepts a path to a DOCX file
- Uses Gotenberg service to convert DOCX to HTML
- Handles ZIP extraction (Gotenberg returns a ZIP containing index.html)
- Returns the HTML content for use in editors
- Includes proper error handling and logging

**Requirements**:
- PHP ZipArchive extension (installed and verified)
- Gotenberg service running at `http://gotenberg:3000`

### 2. Student Report Editor (Rapport Étudiant)
**Files Modified**:
- `ressources/views/gestion_rapports/creer_rapport.php`
- `app/controllers/GestionRapportController.php`
- `ressources/routes/gestionRapportsRoutes.php`

**Changes**:
1. **Editor Replacement**: Replaced TinyMCE with CKEditor 5 Classic
   - Changed from `tinymce.init()` to `ClassicEditor.create()`
   - Updated method calls: `getContent()` → `getData()`, `setContent()` → `setData()`
   - Configured French language support
   
2. **Template Loading**: 
   - Added "Charger le Modèle" button that fetches template from server
   - Endpoint: `?page=gestion_rapports&action=load_template_html`
   - Converts `rapport_etudiant.docx` to HTML on-the-fly
   
3. **Save Functionality**: 
   - Already saves as HTML files (no change needed)
   - Files saved to: `ressources/uploads/rapports/rapport_{id}.html`

### 3. Compte Rendu (Commission Report)
**Files Modified**:
- `ressources/views/redaction_compte_rendu_content.php`
- `app/controllers/RedactionCompteRenduController.php`
- `ressources/routes/redactionCompteRenduRoutes.php`

**Changes**:
1. Added optional server-side template loading (disabled by default)
2. Set `loadFromServer = true` in the `loadTemplate()` function to enable
3. Endpoint: `?page=redaction_compte_rendu&action=load_template_html`
4. Converts `compte_rendu.docx` to HTML

### 4. PDF Generation Improvements
**Files Modified**:
- `app/controllers/GestionScolariteController.php`

**Changes**:
- Added `Content-Length` header to recu_versement PDF generation for consistency
- All PDF generation methods now follow the same pattern

## Testing Instructions

### Prerequisites
1. Ensure Gotenberg service is running: `docker-compose up -d gotenberg`
2. Verify PHP extensions: `php -m | grep -i "zip\|curl\|fileinfo"`
3. Check templates exist: `ls -la ressources/templates/*.docx`

### Test 1: Student Report Editor
1. Log in as a student
2. Navigate to "Gestion des Rapports"
3. Click "Créer un rapport" or edit an existing report
4. Verify CKEditor 5 loads (should see modern editor toolbar)
5. Click "Charger le Modèle" button
6. Verify template loads from rapport_etudiant.docx
7. Edit content and click "Enregistrer"
8. Verify file saved to `ressources/uploads/rapports/rapport_{id}.html`
9. Click "Exporter" to generate PDF

### Test 2: Compte Rendu
1. Log in as commission member
2. Navigate to "Rédaction Compte Rendu"
3. Select reports and click "Charger le modèle"
4. Verify default hardcoded template loads
5. (Optional) Set `loadFromServer = true` and test server-side loading
6. Edit content and save
7. Export to PDF

### Test 3: PDF Generation
1. **Bulletin de Note (releve_notes)**:
   - Log in as student
   - Navigate to "Notes et Résultats"
   - Click "Exporter PDF"
   - Verify PDF generates correctly

2. **Reçu d'Inscription (recu_inscription)**:
   - Log in as admin/scolarite
   - Navigate to "Gestion Inscriptions"
   - Select an inscription
   - Click "Imprimer Reçu"
   - Verify PDF generates correctly

3. **Reçu de Versement (recu_versement)**:
   - Log in as admin/scolarite
   - Navigate to "Gestion Scolarité"
   - Select a versement
   - Click "Imprimer Reçu"
   - Verify PDF generates correctly

## Known Issues and Limitations

### CKEditor 5 Limitations
1. **Font Selector**: Dynamic font changes require custom plugins or manual selection
   - Current implementation shows an info notification
   - Users should select fonts before typing
   
2. **Font Size Selector**: Similar to font selector
   - Shows info notification when changed
   - Consider adding custom dropdown plugin if needed

### Template Conversion
1. **HTML Fidelity**: Gotenberg (via LibreOffice) conversion may not preserve complex formatting
   - Keep DOCX templates simple and well-structured
   - Test converted HTML thoroughly
   - Avoid floating text boxes and complex layouts

2. **Gotenberg Dependency**: All template loading requires Gotenberg service
   - Implement fallback or cached templates if Gotenberg is unavailable
   - Monitor Gotenberg service health

## Configuration Options

### Compte Rendu: Enable Server-Side Template Loading
In `ressources/views/redaction_compte_rendu_content.php`, line ~740:
```javascript
const loadFromServer = true; // Change from false to true
```

### Template Paths
Templates are stored in: `ressources/templates/`
- `rapport_etudiant.docx` - Student report template
- `compte_rendu.docx` - Commission report template
- `releve_notes.docx` - Grade report template
- `recu_inscription.docx` - Registration receipt template
- `recu_versement.docx` - Payment receipt template

## Troubleshooting

### CKEditor Not Loading
1. Check browser console for errors
2. Verify CDN is accessible: `https://cdn.ckeditor.com/ckeditor5/41.0.0/classic/ckeditor.js`
3. Check if JavaScript errors prevent initialization

### Template Loading Fails
1. Check Gotenberg service status: `docker-compose ps gotenberg`
2. Check PHP error logs: `tail -f logs/php-error.log`
3. Verify template file exists and is readable
4. Test Gotenberg directly: `curl -F files=@template.docx http://gotenberg:3000/forms/libreoffice/convert`

### PDF Generation Fails
1. Check DocumentGeneratorService error logs
2. Verify Gotenberg service is running
3. Check template placeholders match data keys
4. Verify file permissions on upload directories

## Security Considerations

1. **Input Validation**: All HTML content should be sanitized before saving
2. **File Upload**: Template uploads should validate file type and size
3. **Access Control**: Verify permissions are checked in all controller methods
4. **Error Messages**: Avoid exposing sensitive information in error messages

## Future Improvements

1. **Caching**: Implement template HTML caching to reduce Gotenberg calls
2. **Custom CKEditor Plugins**: Add font selector and size plugins for better UX
3. **Template Management**: Add UI for uploading and managing DOCX templates
4. **Offline Mode**: Implement fallback when Gotenberg is unavailable
5. **Template Validation**: Validate DOCX templates before conversion
6. **Performance**: Monitor and optimize conversion times

## References

- [CKEditor 5 Documentation](https://ckeditor.com/docs/ckeditor5/latest/)
- [Gotenberg Documentation](https://gotenberg.dev/)
- [PHPWord Documentation](https://phpword.readthedocs.io/)

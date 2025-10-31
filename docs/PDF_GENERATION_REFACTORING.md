# PDF Generation Refactoring - Documentation

## Overview
This document describes the refactoring of PDF generation throughout the application to use a centralized service (DocumentGeneratorService) that interfaces with Gotenberg for better performance and consistency.

## Changes Made

### 1. Word Templates Created
Created 5 Word document templates in `ressources/templates/`:

- **pv_soutenance.docx** - PV evaluation template
- **compte_rendu.docx** - Compte rendu template
- **rapport_etudiant.docx** - Student report template
- **recu_inscription.docx** - Registration receipt template
- **releve_notes.docx** - Grade report template

Each template uses placeholders in the format `${variable_name}` for dynamic content replacement.

### 2. Controllers Refactored

#### EvaluationSoutenanceController
- **Method Modified:** `imprimerPV()`
- **Changes:** 
  - Removed `imprimerPVLegacy()` method (deprecated HTML/Dompdf approach)
  - Removed `imprimerPVFromTemplate()` helper method
  - `imprimerPV()` now directly uses DocumentGeneratorService
  - Generates PV using `pv_soutenance.docx` template
- **Benefits:** Cleaner code, single method for PV generation, better error handling

#### RedactionCompteRenduController
- **Methods Modified:** 
  - `enregistrer()` - For saving compte rendu
  - `exporterPDF()` - For exporting compte rendu as PDF
- **Changes:**
  - Removed direct Dompdf instantiation
  - Removed `use Dompdf\Dompdf;` import
  - Now uses DocumentGeneratorService with `compte_rendu.docx` template
- **Benefits:** Consistent PDF generation, better template management

#### GestionDossiersCandidaturesController
- **Method Modified:** `telechargerPdf()`
- **Changes:**
  - Replaced HTML construction + Dompdf with DocumentGeneratorService
  - Uses `rapport_etudiant.docx` template
  - Improved error handling with proper exception messages
- **Benefits:** Cleaner separation of data and presentation, easier to modify report layout

#### InscriptionController
- **Method Modified:** `index()` (receipt generation section)
- **Changes:**
  - Replaced Dompdf instantiation with DocumentGeneratorService
  - Uses `recu_inscription.docx` template
  - Simplified data preparation logic
- **Benefits:** Easier to customize receipt layout, better error handling

#### NotesResultatsController
- **Method Modified:** `exportPdf()`
- **Changes:**
  - Removed HTML view generation
  - Removed Dompdf usage
  - Uses DocumentGeneratorService with `releve_notes.docx` template
  - Properly formats grade data for template
- **Benefits:** Cleaner code, easier to modify grade report layout

### 3. DocumentGeneratorService Improvements
The service already had proper error handling:
- ✅ Validates file existence before conversion
- ✅ Handles cURL errors properly
- ✅ Throws exception if HTTP code != 200
- ✅ Validates generated PDF file

## Technical Architecture

### Before Refactoring
```
Controller → Manual HTML Construction → Dompdf → PDF
```
Issues:
- Inconsistent HTML across different controllers
- Difficult to maintain styling
- No separation between data and presentation
- Error-prone manual string concatenation

### After Refactoring
```
Controller → DocumentGeneratorService → PhpWord Template Processing → Gotenberg → PDF
```
Benefits:
- Consistent document structure
- Easy visual customization through Word templates
- Clear separation of concerns
- Gotenberg provides better PDF rendering than Dompdf
- Centralized error handling

## Usage Examples

### Example 1: Generating PV de Soutenance
```php
$documentService = new DocumentGeneratorService();

$templateData = [
    'nom_etudiant' => 'John Doe',
    'niveau' => 'Master 2',
    'theme' => 'AI in Healthcare',
    'date_soutenance' => '15/12/2024',
    // ... other fields
];

$pdfPath = $documentService->generateFromTemplate('pv_soutenance', $templateData);

// Send to browser
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="pv.pdf"');
readfile($pdfPath);

// Cleanup
$documentService->cleanupTempFile($pdfPath);
```

### Example 2: Generating Receipt
```php
$documentService = new DocumentGeneratorService();

$templateData = [
    'nom_etudiant' => 'Jane Smith',
    'montant_total' => '500000',
    'montant_paye' => '250000',
    // ... other fields
];

$pdfPath = $documentService->generateFromTemplate('recu_inscription', $templateData);
```

## Placeholders Reference

### pv_soutenance.docx
- `${niveau}` - Student level (Master 1/2)
- `${promotion}` - Student promotion
- `${nom_etudiant}` - Student full name
- `${theme}` - Thesis theme
- `${date_soutenance}` - Defense date
- `${president}`, `${examinateur}`, `${directeur}`, `${encadreur}`, `${maitre_stage}` - Jury members
- `${note_finale}` - Final grade
- `${total_bareme}` - Total points possible
- `${mention}` - Honor mention

**Repeating Section (Criteria table):**
- `${lib_critere}` - Criterion name
- `${bareme}` - Points possible
- `${note}` - Points obtained

### compte_rendu.docx
- `${nom_CR}` - Report name
- `${date_CR}` - Report date
- `${contenu_CR}` - Report content

### rapport_etudiant.docx
- `${nom_etu}`, `${prenom_etu}` - Student name
- `${num_etu}` - Student number
- `${email_etu}` - Student email
- `${nom_rapport}` - Report name
- `${theme_rapport}` - Report theme
- `${date_depot}` - Submission date
- `${statut_approbation}` - Approval status
- `${commentaire}` - Comments
- `${contenu}` - Report content

### recu_inscription.docx
- `${id_inscription}` - Receipt number
- `${nom_etudiant}`, `${prenom_etudiant}` - Student name
- `${nom_niveau}` - Study level
- `${annee_academique}` - Academic year
- `${montant_total}` - Total amount
- `${montant_paye}` - Amount paid
- `${reste_a_payer}` - Remaining balance
- `${methode_paiement}` - Payment method
- `${date_inscription}` - Payment date
- `${nombre_tranche}` - Number of installments

### releve_notes.docx
- `${nom_etu}`, `${prenom_etu}` - Student name
- `${num_etu}` - Student number
- `${promotion_etu}` - Student promotion
- `${niveau}` - Study level
- `${moyenne_generale}` - Overall average
- `${nb_ue_valide}` - Number of validated UE
- `${classement}`, `${total_etudiants}` - Ranking

**Repeating Section (Grades table):**
- `${lib_ue}` - UE name
- `${credit}` - Credits
- `${moyenne}` - Average grade
- `${resultat}` - Result (Validé/Non validé)

## Gotenberg Configuration
Gotenberg is configured in `docker-compose.yml`:
```yaml
gotenberg:
  image: gotenberg/gotenberg:8
  ports:
    - "3000:3000"
  command:
    - "gotenberg"
    - "--api-timeout=60s"
```

The service is accessible at `http://gotenberg:3000` from within the Docker network.

## Error Handling
All controllers now properly handle exceptions from DocumentGeneratorService:

```php
try {
    $pdfPath = $documentService->generateFromTemplate('template_name', $data);
    // ... send PDF to browser
    $documentService->cleanupTempFile($pdfPath);
} catch (Exception $e) {
    error_log('PDF generation error: ' . $e->getMessage());
    // Display user-friendly error message
}
```

## Testing Checklist
- [ ] Test PV generation for different student levels (M1/M2)
- [ ] Test compte rendu creation and export
- [ ] Test student report download with different statuses
- [ ] Test registration receipt generation with installments
- [ ] Test grade report generation with multiple UE
- [ ] Verify Gotenberg service is running in Docker
- [ ] Verify PDF files are properly cleaned up after generation
- [ ] Test error handling when Gotenberg is unavailable

## Migration Notes
- **No database changes required**
- **No breaking changes to API/URLs**
- Dompdf is still available for other parts of the application (e.g., GestionRapportController)
- Old HTML views are preserved in `ressources/views/` for reference

## Future Improvements
1. Add template version control
2. Create admin interface for template management
3. Add support for multiple languages in templates
4. Implement PDF caching for frequently generated documents
5. Add digital signature support
6. Consider creating a template testing tool

## Dependencies
- **phpoffice/phpword**: ^1.3 - For Word template processing
- **gotenberg/gotenberg:8** (Docker) - For PDF conversion
- **ext-curl** - For HTTP communication with Gotenberg

## Troubleshooting

### Issue: Gotenberg connection error
**Solution:** Ensure Gotenberg service is running: `docker-compose ps`

### Issue: Template not found
**Solution:** Verify template exists in `ressources/templates/` and has .docx extension

### Issue: PDF generation timeout
**Solution:** Increase timeout in DocumentGeneratorService or Gotenberg configuration

### Issue: Placeholder not replaced
**Solution:** Check placeholder name matches exactly (case-sensitive) and is in correct format `${name}`

## References
- [PhpWord Documentation](https://phpword.readthedocs.io/)
- [Gotenberg Documentation](https://gotenberg.dev/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)

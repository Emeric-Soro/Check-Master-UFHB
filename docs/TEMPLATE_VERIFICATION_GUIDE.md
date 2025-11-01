# PDF Generation System - Template Verification Guide

## Important: Manual Template Verification Required

### Issue: Placeholder Fragmentation in Word Templates

When creating or editing Word templates for use with PHPWord's TemplateProcessor, placeholders like `${variable_name}` can become split across multiple XML text nodes. This happens when:
- Text is typed character by character
- Text is edited with formatting changes
- Text is copied and pasted from different sources

### Solution: Ensure Placeholders are Single Continuous Blocks

To fix fragmented placeholders in Microsoft Word:

1. **Method 1: Type in Notepad and Copy**
   - Open Notepad (or any plain text editor)
   - Type the complete placeholder: `${variable_name}`
   - Copy it from Notepad
   - In Word, delete the old placeholder completely
   - Paste the copied placeholder from Notepad

2. **Method 2: Delete and Retype**
   - Delete the entire placeholder in Word
   - Type the complete placeholder in one go without stopping: `${variable_name}`
   - Do not add formatting, styles, or make any edits after typing

## Templates to Verify

### 1. pv_soutenance.docx

**Location:** `ressources/templates/pv_soutenance.docx`

**Required Placeholders:**

#### Annexe 1 - General Information
- `${niveau}` - Academic level
- `${date_soutenance}` - Defense date (format: dd/mm/yyyy)
- `${promotion}` - Student's class/promotion
- `${theme}` - Thesis theme
- `${nom_etudiant}` - Student name
- `${president}` - Jury president name
- `${examinateur}` - Examiner name
- `${directeur}` - Thesis director name
- `${encadreur}` - Supervisor name
- `${maitre_stage}` - Internship master name
- `${note_finale}` - Final grade (formatted as "XX.XX")
- `${total_bareme}` - Total scale (formatted as "XX.XX")

#### Annexe 1 - Evaluation Criteria Table (Repeating Block)
**CRITICAL:** The criteria table must have ONE data row (plus header) with these placeholders:
- `${lib_critere}` - Criterion label
- `${note}` - Score obtained (formatted as "XX.XX")
- `${bareme}` - Maximum score (formatted as "XX.X")

**Verification Steps:**
1. Open the template in Word
2. Navigate to the evaluation criteria table
3. Ensure there is exactly ONE data row after the header
4. Verify each placeholder is continuous (use Method 1 or 2 above if needed)
5. The PHPWord cloneRow function will use the first key (`lib_critere`) to clone rows

#### Annexe 2 - PV Jury
- `${moyenne_master1}` - Master 1 average (formatted as "XX.XX")
- `${moyenne_s1_master2}` - Master 2 Semester 1 average (formatted as "XX.XX")
- `${note_memoire}` - Thesis grade (formatted as "XX.XX")
- `${coef_master1}` - Coefficient for Master 1 (value: 2)
- `${coef_s1_master2}` - Coefficient for S1 Master 2 (value: 3)
- `${coef_memoire}` - Coefficient for thesis (value: 3)
- `${note_finale_pv}` - Final weighted grade (formatted as "XX.XX")
- `${mention}` - Academic distinction (e.g., "Très Bien", "Bien", etc.)

#### Annexe 3 - Formation Continue
- `${coef_master1_fc}` - Coefficient for Master 1 (value: 1)
- `${coef_memoire_fc}` - Coefficient for thesis (value: 2)
- `${total_coef_fc}` - Total coefficient (value: 3)
- `${note_finale_fc}` - Final weighted grade (formatted as "XX.XX")
- `${mention_fc}` - Academic distinction

### 2. recu_versement.docx

**Location:** `ressources/templates/recu_versement.docx`

**Status:** ✅ Created programmatically with Python, placeholders should be correct

**Required Placeholders:**
- `${numero_recu}` - Receipt number (format: RXXXXX)
- `${nom_etudiant}` - Student full name
- `${montant_en_chiffres}` - Amount in numbers (formatted with spaces)
- `${montant_en_lettres}` - Amount in words (French)
- `${reglement_de}` - Payment description
- `${annee_etudes}` - Academic year/level
- `${methode_paiement}` - Payment method
- `${date_versement}` - Payment date (format: dd/mm/yyyy)
- `${montant_total_scolarite}` - Total tuition (formatted with spaces)
- `${montant_total_paye}` - Total amount paid (formatted with spaces)
- `${reste_a_payer}` - Remaining balance (formatted with spaces)

### 3. Other Templates (Should Already Work)

- `recu_inscription.docx` - Registration receipt
- `releve_notes.docx` - Grade report
- Other templates in `ressources/templates/`

## Verification Checklist

Before marking this issue as complete, verify:

- [ ] Open `pv_soutenance.docx` in Microsoft Word
- [ ] Check Annexe 1 general information placeholders are continuous
- [ ] Check Annexe 1 evaluation table has ONE data row with correct placeholders
- [ ] Check Annexe 2 placeholders are continuous
- [ ] Check Annexe 3 placeholders are continuous
- [ ] Save the template after any corrections
- [ ] Test generating a PV Soutenance PDF with real data
- [ ] Verify all three annexes appear correctly in the generated PDF
- [ ] Test generating a reçu de versement PDF
- [ ] Verify all data appears correctly in the receipt

## Code Changes Summary

### Files Modified:

1. **app/controllers/EvaluationSoutenanceController.php**
   - Restructured `$evaluations` data into `$criteresPourTemplate` array
   - Added proper formatting with `number_format()` for all numeric values
   - Ensured all template data is properly formatted before passing to DocumentGeneratorService

2. **app/utils/DocumentGeneratorService.php**
   - Enhanced error handling in `convertToPdf()` method
   - Added detailed error logging for debugging
   - Improved error messages for better user feedback

3. **app/controllers/GestionScolariteController.php**
   - Added new `imprimerRecuVersement()` method
   - Uses DocumentGeneratorService instead of old Dompdf approach
   - Properly formats all monetary values and dates

4. **ressources/routes/gestionScolariteRoutes.php**
   - Added route for `action=imprimer_recu_versement`
   - Route calls new controller method and exits

5. **ressources/views/gestion_scolarite_content.php**
   - Updated `imprimerRecu()` JavaScript function
   - Changed URL from `imprimer_recu` to `imprimer_recu_versement`

6. **ressources/templates/recu_versement.docx**
   - NEW: Created Word template with all required placeholders
   - Generated programmatically using Python python-docx library
   - Contains proper structure matching the old HTML version

## Testing Guidelines

### Test 1: PV Soutenance Generation

```
1. Navigate to Evaluation Soutenance page
2. Select a student who has been evaluated
3. Click "Imprimer PV"
4. Verify the PDF opens in a new tab
5. Check:
   - Annexe 1: All student and jury information appears
   - Annexe 1: Evaluation criteria table is populated with all criteria
   - Annexe 2: Averages and final grade appear correctly
   - Annexe 3: Formation Continue data appears correctly
   - PDF has exactly 3 pages
```

### Test 2: Reçu de Versement

```
1. Navigate to Gestion Scolarité page
2. Find a versement record
3. Click the "Imprimer" button
4. Verify the PDF opens in a new tab
5. Check:
   - Receipt number is formatted correctly (RXXXXX)
   - Student name appears
   - Amount in numbers is formatted with spaces
   - Amount in words is in French
   - Payment date is in dd/mm/yyyy format
   - Financial summary (total, paid, remaining) is correct
```

### Test 3: Existing Functionality

```
1. Test reçu d'inscription generation
2. Test relevé de notes generation
3. Verify no regressions in existing PDF generation
```

## Cleanup Tasks

After successful testing:

- [ ] Mark `ressources/views/recu_versement.php` as deprecated
- [ ] Consider removing `ressources/views/recu_versement.php` once migration is confirmed stable
- [ ] Update any documentation referencing the old receipt generation method

## Notes

- The old `recu_versement.php` used Dompdf and custom HTML rendering
- The new approach uses Word templates + Gotenberg, consistent with other documents
- All PDF generation now goes through DocumentGeneratorService for consistency
- Better error handling ensures issues are logged and reported to users

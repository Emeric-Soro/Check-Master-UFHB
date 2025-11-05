# Template Status and Verification Guide

## Overview
This document tracks the status of all DOCX templates used for PDF generation and provides verification guidelines.

## Template Inventory

### ✅ Templates Currently in Use

#### 1. pv_soutenance.docx (201 KB)
**Purpose**: Generate PV (Procès-Verbal) for thesis defense with 3 annexes
**Controller**: `EvaluationSoutenanceController::imprimerPV()`
**Status**: ✅ In Use
**Size**: Large (201 KB) - contains 3 pages/annexes

**Required Placeholders**:
- General Info: `${niveau}`, `${promotion}`, `${nom_etudiant}`, `${date_soutenance}`, `${theme}`
- Jury: `${president}`, `${examinateur}`, `${directeur}`, `${encadreur}`, `${maitre_stage}`
- Annexe 1 (Evaluation): `${lib_critere}`, `${note}`, `${bareme}`, `${note_finale}`, `${total_bareme}`
- Annexe 2 (Formation Initiale): `${moyenne_master1}`, `${coef_master1}`, `${moyenne_s1_master2}`, `${coef_s1_master2}`, `${note_memoire}`, `${coef_memoire}`, `${note_finale_pv}`, `${mention}`
- Annexe 3 (Formation Continue): `${coef_master1_fc}`, `${coef_memoire_fc}`, `${total_coef_fc}`, `${note_finale_fc}`, `${mention_fc}`

**Verification Steps**:
1. Open in Microsoft Word
2. Check for 3 distinct pages/sections
3. Verify all placeholders are present and not fragmented
4. Ensure evaluation criteria table has proper placeholder structure
5. Test generation with real data

---

#### 2. recu_inscription.docx (7.7 KB)
**Purpose**: Generate registration receipt
**Controller**: `InscriptionController::index()` with `modalAction=imprimer_recu`
**Status**: ✅ In Use

**Required Placeholders**:
- `${id_inscription}`, `${nom_etudiant}`, `${prenom_etudiant}`
- `${nom_niveau}`, `${annee_academique}`
- `${montant_total}`, `${montant_paye}`, `${reste_a_payer}`
- `${methode_paiement}`, `${date_inscription}`
- `${nombre_tranche}`, `${prochain_versement}`, `${date_prochain_versement}`

**Verification**: Test registration receipt generation

---

#### 3. recu_versement.docx (37 KB)
**Purpose**: Generate payment receipt
**Controller**: `GestionScolariteController::imprimerRecuVersement()`
**Status**: ✅ In Use (Migrated from deprecated PHP template)

**Required Placeholders**:
- `${numero_recu}`, `${nom_etudiant}`
- `${montant_en_chiffres}`, `${montant_en_lettres}`
- `${reglement_de}`, `${annee_etudes}`
- `${methode_paiement}`, `${date_versement}`
- `${montant_total_scolarite}`, `${montant_total_paye}`, `${reste_a_payer}`

**Verification**: Test payment receipt generation from Gestion Scolarité page

---

#### 4. releve_notes.docx (7.8 KB)
**Purpose**: Generate grade report
**Controller**: `NotesResultatsController::exportPdf()`
**Status**: ✅ In Use

**Required Placeholders**:
- General: `${nom_etu}`, `${prenom_etu}`, `${num_etu}`, `${promotion_etu}`, `${niveau}`
- Statistics: `${moyenne_generale}`, `${nb_ue_valide}`, `${classement}`, `${total_etudiants}`
- Repeating Block: `${lib_ue}`, `${credit}`, `${moyenne}`, `${resultat}`

**Verification**: Test grade report generation

---

#### 5. compte_rendu.docx (7.4 KB)
**Purpose**: Generate commission report
**Controller**: To be verified
**Status**: ⚠️ Template exists but usage needs verification

**Verification Needed**: Check if this template is actively used by any controller

---

#### 6. rapport_etudiant.docx (7.5 KB)
**Purpose**: Template for student report (used for loading template into editor)
**Controller**: `GestionRapportController::loadTemplateHtml()`
**Status**: ⚠️ Used for HTML conversion, not direct PDF generation

**Note**: This template is converted to HTML for the WYSIWYG editor. Students create reports using this as a starting template.

---

## PDF Generation Methods Summary

### Active PDF Generation Flows

1. **DOCX Template → PDF** (via PHPWord + Gotenberg)
   - Used by: PV Soutenance, Receipts, Grade Reports
   - Service: `DocumentGeneratorService::generateFromTemplate()`
   - Process: Template → PHPWord processing → DOCX → Gotenberg → PDF

2. **HTML → PDF** (via Gotenberg Chromium)
   - Used by: Student Reports, Report Verification
   - Service: `DocumentGeneratorService::convertHtmlToPdf()`
   - Process: HTML content → Gotenberg Chromium → PDF

3. **DOCX → HTML** (via Gotenberg)
   - Used by: Template loading for editor
   - Service: `DocumentGeneratorService::convertDocxToHtml()`
   - Process: DOCX → Gotenberg → ZIP archive → Extract HTML

### Deprecated/Removed

- ❌ **Dompdf** - Completely removed from active codebase
- ❌ **recu_versement.php** - Deprecated PHP template removed

---

## Verification Checklist

### Template Structure Verification

For each DOCX template:

- [ ] Open in Microsoft Word (not LibreOffice to avoid compatibility issues)
- [ ] Verify placeholders are typed as single text runs
  - Tip: Type placeholders in Notepad, then paste into Word
  - Problem: Word can split `${variable}` across XML nodes
- [ ] Check table structures for repeating blocks
- [ ] Verify page breaks and sections
- [ ] Test with DocumentGeneratorService

### Placeholder Verification Method

1. Open template in Word
2. For each placeholder:
   - Select the entire placeholder text `${variable}`
   - If selection highlights in fragments, the placeholder is broken
   - Solution: Delete and retype as continuous text

### Testing Procedure

For each template:

1. **Unit Test**: Generate PDF with test data
2. **Visual Inspection**: Open generated PDF
   - Check all placeholders are replaced
   - Verify formatting is correct
   - Ensure no blank sections
3. **Error Handling**: Test with missing data
   - Should not crash
   - Should log missing placeholders
4. **Real Data Test**: Generate with actual database data

---

## Common Issues and Solutions

### Issue 1: Empty Fields in PDF
**Cause**: Placeholder not found in template or data key mismatch
**Solution**: 
- Check DocumentGeneratorService logs for "Placeholder not found" messages
- Verify data keys match template placeholders exactly
- Ensure placeholders are not fragmented

### Issue 2: Table Not Repeating
**Cause**: PHPWord cannot find the row to clone
**Solution**:
- Ensure table structure is correct
- First column of data row must contain the reference placeholder
- Check data array structure in controller

### Issue 3: Formatting Lost
**Cause**: PHPWord or Gotenberg rendering issue
**Solution**:
- Keep formatting simple in templates
- Test complex formatting before deployment
- Consider using CSS for HTML-based PDFs

### Issue 4: PDF Not Generating
**Causes**: Multiple possible
**Solutions**:
1. Check Gotenberg is running: `docker ps | grep gotenberg`
2. Check PHP error logs for detailed messages
3. Verify template file exists and is readable
4. Check temp directory permissions
5. Look for "DocumentGeneratorService:" log entries

---

## Template Maintenance Guidelines

### When Creating New Templates

1. Use Microsoft Word (not alternatives)
2. Keep formatting simple
3. Test placeholders aren't fragmented
4. Document all placeholders used
5. Add entry to this document
6. Create corresponding controller method
7. Test thoroughly before deployment

### When Modifying Existing Templates

1. **Always backup** the current template first
2. Make changes in Word
3. Verify placeholders not fragmented
4. Test PDF generation
5. Compare new vs. old PDF output
6. Update documentation if placeholders changed

### Template Storage

- Location: `ressources/templates/*.docx`
- Backup: Keep versioned backups
- Permissions: Readable by web server (644)
- Version control: Track in Git

---

## Next Steps for Full Verification

1. **Manual Testing Required**:
   - [ ] PV Soutenance (all 3 annexes)
   - [ ] Reçu d'Inscription
   - [ ] Reçu de Versement
   - [ ] Relevé de Notes
   - [ ] Report Verification PDF

2. **Template Analysis**:
   - [ ] Open each template in Word
   - [ ] Document actual placeholders present
   - [ ] Identify any fragmented placeholders
   - [ ] Fix any issues found

3. **Controller Verification**:
   - [ ] Confirm compte_rendu.docx usage
   - [ ] Document any missing controller implementations

4. **Documentation Updates**:
   - [ ] Update DOCX_TEMPLATE_REQUIREMENTS.md with findings
   - [ ] Create troubleshooting guide
   - [ ] Document testing results

---

## Success Criteria

Template verification is complete when:

- ✅ All templates open successfully in Word
- ✅ All placeholders are verified as continuous text
- ✅ PDF generation tested for each template
- ✅ All generated PDFs display data correctly
- ✅ No "Placeholder not found" errors in logs
- ✅ Error handling tested and working
- ✅ Documentation is complete and accurate

---

**Last Updated**: 2025-11-05  
**Status**: Phase 3 - Template Verification In Progress

# Complete PDF Generation System Guide

## ✅ Status: Refactoring Complete - Ready for Testing

**Last Updated**: 2025-11-05

---

## Executive Summary

The PDF generation system has been successfully refactored and stabilized. All Dompdf usage has been eliminated, and the system now uses a unified approach through `DocumentGeneratorService` with Gotenberg for all PDF generation.

### What Changed

- ✅ **Dompdf Completely Removed**: No legacy PDF generation code remains
- ✅ **Enhanced Error Handling**: Comprehensive logging and user-friendly error messages
- ✅ **Unified Service**: All PDF generation goes through DocumentGeneratorService
- ✅ **Better Security**: Added HTMLPurifierService where needed
- ✅ **Complete Documentation**: All aspects documented

### What's Next

Manual testing is required to verify that all PDF generation features work correctly with real data.

---

## Quick Reference

### PDF Generation Methods

The system now supports three PDF generation workflows:

#### 1. DOCX Template → PDF
**Use Case**: Structured documents with predefined templates
**Examples**: PV Soutenance, Receipts, Grade Reports

```php
$documentService = new DocumentGeneratorService();
$pdfPath = $documentService->generateFromTemplate('template_name', $dataArray);
```

**Process**:
1. Template loaded from `ressources/templates/`
2. PHPWord replaces placeholders with data
3. Generated DOCX saved to temp file
4. Gotenberg converts DOCX to PDF
5. Returns PDF file path

#### 2. HTML → PDF
**Use Case**: Dynamic HTML content (reports, documents from editors)
**Examples**: Student Reports, Report Verification PDFs

```php
$documentService = new DocumentGeneratorService();
$pdfPath = $documentService->convertHtmlToPdf($htmlContent, $options);
```

**Options**:
- `paperSize`: 'A4' (default) or 'Letter'
- `landscape`: true/false
- `marginTop/Bottom/Left/Right`: margin in cm

**Process**:
1. HTML wrapped in complete document if needed
2. Saved to temporary HTML file
3. Gotenberg Chromium converts to PDF
4. Returns PDF file path

#### 3. DOCX → HTML
**Use Case**: Loading Word templates into HTML editors
**Examples**: Student report template loading

```php
$documentService = new DocumentGeneratorService();
$htmlContent = $documentService->convertDocxToHtml($docxPath);
```

---

## Document Types & Status

### Active PDF Generations

| Document Type | Template | Controller | Status | Route |
|--------------|----------|------------|--------|-------|
| PV Soutenance | pv_soutenance.docx | EvaluationSoutenanceController | ✅ Active | ?page=evaluation_soutenance&action=imprimer_pv |
| Registration Receipt | recu_inscription.docx | InscriptionController | ✅ Active | ?page=gestion_etudiants... |
| Payment Receipt | recu_versement.docx | GestionScolariteController | ✅ Active | ?page=gestion_scolarite&action=imprimer_recu_versement |
| Grade Report | releve_notes.docx | NotesResultatsController | ✅ Active | ?page=notes_resultats&action=export_pdf |
| Student Report | HTML-based | GestionRapportController | ✅ Active | ?page=gestion_rapports... |
| Verification PDF | HTML-based | verificationRapportsRoutes | ✅ Active | ?page=verification_candidatures... |

---

## Testing Guide

### Prerequisites

1. **Gotenberg Service Must Be Running**
   ```bash
   docker ps | grep gotenberg
   # Should show: gotenberg container running on port 3000
   ```

2. **Permissions Check**
   ```bash
   # Templates must be readable
   ls -la ressources/templates/*.docx
   
   # Temp directory must be writable
   ls -ld /tmp
   ```

3. **Test Data Available**
   - Students with complete records
   - Evaluations with criteria
   - Payment records
   - Grade records

### Manual Testing Checklist

#### 1. PV Soutenance (Priority: HIGH)

**Navigate**: Evaluation Soutenance page

**Steps**:
1. Select student with complete evaluation
2. Click "Imprimer PV"
3. PDF should open in new tab

**Verify**:
- [ ] PDF has exactly 3 pages (Annexe 1, 2, 3)
- [ ] Page 1: Evaluation criteria table populated with all criteria
- [ ] Page 1: Student info, jury members displayed
- [ ] Page 2: All averages displayed (Master 1, S1 M2, Mémoire)
- [ ] Page 2: Weighted calculation correct: (M1×2 + S1M2×3 + Mem×3) / 8
- [ ] Page 2: Mention correct (Très Bien, Bien, Assez Bien, Passable)
- [ ] Page 3: Formation Continue data present
- [ ] Page 3: FC calculation correct: (M1×1 + Mem×2) / 3
- [ ] No blank placeholders like `${variable}`
- [ ] All formatting intact

**If Issues**:
- Check PHP error logs for "DocumentGeneratorService:" entries
- Look for "Placeholder not found" messages
- Verify template placeholders in Word (see TEMPLATE_STATUS.md)

---

#### 2. Reçu de Versement (Priority: HIGH)

**Navigate**: Gestion Scolarité page

**Steps**:
1. Find a versement in the table
2. Click "Imprimer" button
3. PDF should download or open

**Verify**:
- [ ] Receipt number format correct (RXXXXX)
- [ ] Student name displayed
- [ ] Amount in numbers formatted with spaces (e.g., "150 000 FCFA")
- [ ] Amount in words in French
- [ ] Payment method shown
- [ ] Date formatted as dd/mm/yyyy
- [ ] Financial summary correct:
  - Total tuition
  - Total paid (as of this payment)
  - Remaining balance
- [ ] All sections have data (no blanks)

**If Issues**:
- Verify GestionScolariteController::imprimerRecuVersement() is being called
- Check that recu_versement.docx template exists
- Look for errors about missing template

---

#### 3. Reçu d'Inscription (Priority: MEDIUM)

**Navigate**: Gestion Étudiants → Inscrire des étudiants

**Steps**:
1. Find enrolled student
2. Click "Imprimer Reçu"
3. PDF should generate

**Verify**:
- [ ] Inscription ID displayed
- [ ] Student name and info correct
- [ ] Level/Program name shown
- [ ] Academic year correct
- [ ] All amounts formatted properly
- [ ] Payment schedule if applicable
- [ ] Date formatted correctly

---

#### 4. Relevé de Notes (Priority: MEDIUM)

**Navigate**: Notes et Résultats page

**Steps**:
1. Select student and semester
2. Click "Exporter PDF" or similar
3. PDF should generate

**Verify**:
- [ ] Student identification correct
- [ ] All UE (course units) listed
- [ ] Grades displayed for each UE
- [ ] Credits shown
- [ ] Overall average calculated
- [ ] Validation status per UE
- [ ] Ranking/Classification if shown
- [ ] No missing data

---

#### 5. Student Report Export (Priority: MEDIUM)

**Navigate**: Gestion Rapports → Créer/Modifier Rapport

**Steps**:
1. Open existing report or create new one
2. Add content (text, tables, images)
3. Click "Exporter PDF"
4. PDF should download

**Verify**:
- [ ] HTML content renders correctly
- [ ] Tables formatted properly
- [ ] Images display if present
- [ ] Text formatting preserved
- [ ] Page breaks reasonable
- [ ] No JavaScript/interactive elements (expected)

**Note**: This uses HTML→PDF conversion (Chromium engine)

---

#### 6. Report Verification PDF (Priority: LOW)

**Navigate**: Vérification Candidatures Soutenance

**Steps**:
1. View a submitted report
2. Click "Télécharger le rapport en PDF"
3. PDF should download

**Verify**:
- [ ] Report metadata shown (student, theme, date)
- [ ] Report content renders
- [ ] Status displayed
- [ ] Formatting acceptable

---

### Error Testing

For each document type, test error scenarios:

#### Test 1: Missing Data
- Try generating PDF with incomplete record
- **Expected**: Clear error message, not crash
- **Check logs**: Should log specific missing data

#### Test 2: Gotenberg Down
```bash
# Stop Gotenberg temporarily
docker stop gotenberg
```
- Try generating any PDF
- **Expected**: "Service temporarily unavailable" message
- **Check logs**: Should log cURL connection error

```bash
# Restart Gotenberg
docker start gotenberg
```

#### Test 3: Invalid Template
- Temporarily rename a template file
- Try generating that document type
- **Expected**: "Template not found" error
- **Check logs**: Should log missing template path

---

## Troubleshooting

### Issue: PDF Not Generating

**Symptoms**: User sees error, no PDF produced

**Diagnosis Steps**:
1. Check Gotenberg:
   ```bash
   docker ps | grep gotenberg
   curl http://localhost:3000/health
   ```

2. Check PHP error logs:
   ```bash
   tail -f /var/log/php/error.log
   # Or wherever your PHP logs are
   ```

3. Look for "DocumentGeneratorService:" log entries

**Common Causes**:
- Gotenberg not running → Start container
- Template missing → Check file exists
- Permissions issue → Check temp directory writable
- Placeholder mismatch → Check template vs data keys

---

### Issue: Empty Fields in PDF

**Symptoms**: PDF generates but has blank areas or `${placeholder}` text

**Diagnosis**:
1. Check logs for "Placeholder 'xxx' not found in template"
2. Open template in Word
3. Verify placeholder exists and isn't fragmented

**Fix**:
- If placeholder fragmented: Delete and retype as single text
- If placeholder missing: Add to template
- If data key wrong: Update controller to match template

**Placeholder Fragmentation Check**:
```
Problem: ${varia}${ble}  <- Split across formatting
Solution: ${variable}     <- Single continuous text
```

---

### Issue: Formatting Problems

**Symptoms**: PDF content looks different from template/HTML

**For DOCX templates**:
- Keep formatting simple in Word
- Avoid complex nested tables
- Test template after changes

**For HTML-based PDFs**:
- Check HTML is valid (closed tags, etc.)
- Use inline CSS (external CSS not loaded)
- Test with simpler HTML first

---

### Issue: Gotenberg Timeout

**Symptoms**: "Service temporarily unavailable" after ~60 seconds

**Causes**:
- Very large document
- Complex template processing
- Gotenberg overloaded

**Solutions**:
- Simplify template
- Reduce image sizes
- Increase timeout in DocumentGeneratorService (CURLOPT_TIMEOUT)
- Check Gotenberg container resources

---

## Log Analysis

### What to Look For

DocumentGeneratorService logs with specific prefixes:

```
DocumentGeneratorService: Conversion DOCX->PDF démarrée. Fichier: template.docx, Taille: 7892 bytes
DocumentGeneratorService: Conversion DOCX->PDF réussie. Taille PDF: 45678 bytes
```

### Error Patterns

**cURL Errors**:
```
Erreur cURL vers Gotenberg. Errno: 7, Message: Failed to connect
→ Gotenberg not accessible, check container
```

**HTTP Errors**:
```
Gotenberg a retourné une erreur (Code: 500): Internal Server Error
→ Gotenberg processing failed, check Gotenberg logs
```

**File Errors**:
```
Impossible d'écrire le fichier PDF: /tmp/pdf_xxx.pdf
→ Permissions issue on temp directory
```

**Empty Response**:
```
Gotenberg a retourné une réponse vide (Code: 200)
→ Gotenberg processed but returned no data (very rare)
```

---

## Performance Considerations

### Expected Generation Times

- Simple receipt: 1-3 seconds
- Grade report: 2-4 seconds
- PV Soutenance (3 pages): 3-5 seconds
- Large student report: 5-10 seconds

### If Generation is Slow

1. Check Gotenberg container CPU/memory
2. Check template complexity
3. Consider caching frequently generated documents
4. Monitor temp directory disk space

---

## Maintenance

### Regular Tasks

**Weekly**:
- Check temp directory size: `du -sh /tmp`
- Clean old temp files if needed
- Review error logs for patterns

**Monthly**:
- Test all PDF generation types
- Verify Gotenberg performance
- Review and archive logs

**As Needed**:
- Update templates (backup first!)
- Update DocumentGeneratorService for new features
- Adjust timeouts if needed

### Template Updates

When modifying templates:

1. **Backup current template**:
   ```bash
   cp ressources/templates/template.docx ressources/templates/template.docx.backup
   ```

2. **Modify in Microsoft Word** (not alternatives)

3. **Verify placeholders not fragmented**:
   - Select each ${placeholder}
   - Should highlight as continuous text

4. **Test immediately**:
   - Generate PDF with test data
   - Compare old vs new output

5. **Document changes**:
   - Update TEMPLATE_STATUS.md if placeholders changed
   - Note in CHANGELOG

6. **Deploy cautiously**:
   - Test in staging first
   - Have rollback plan (backup file)

---

## Architecture Reference

### Service Structure

```
DocumentGeneratorService
├── generateFromTemplate()    # DOCX template → PDF
│   ├── TemplateProcessor (PHPWord)
│   └── convertToPdf()
│
├── convertHtmlToPdf()        # HTML → PDF
│   └── Gotenberg Chromium API
│
├── convertDocxToHtml()       # DOCX → HTML
│   └── Gotenberg LibreOffice API
│
└── Helper Methods
    ├── cleanupTempFile()
    ├── listTemplates()
    └── ...
```

### Data Flow Examples

**DOCX Template Flow**:
```
Controller
  ↓ (calls with template name + data)
DocumentGeneratorService::generateFromTemplate()
  ↓ (loads template)
PHPWord TemplateProcessor
  ↓ (replaces placeholders)
Temp DOCX File
  ↓ (sends to Gotenberg)
Gotenberg LibreOffice
  ↓ (converts)
PDF File (temp)
  ↓ (serves to user)
Browser
  ↓ (cleanup)
Delete temp files
```

**HTML Flow**:
```
Controller (gets HTML content)
  ↓
DocumentGeneratorService::convertHtmlToPdf()
  ↓
Temp HTML File
  ↓
Gotenberg Chromium
  ↓
PDF File (temp)
  ↓
Browser
  ↓
Delete temp files
```

---

## Security Notes

### Implemented Protections

1. **XSS Prevention**: HTMLPurifierService used for HTML content
2. **Input Validation**: All IDs validated before processing
3. **Permission Checks**: Controllers verify user permissions
4. **Log Injection Prevention**: Error messages sanitized before logging
5. **File Validation**: Generated PDFs validated (exist, non-zero size)

### Best Practices

- Never trust user input in templates
- Always use HTMLPurifierService for HTML content
- Validate file uploads thoroughly
- Keep Gotenberg container isolated
- Monitor temp directory for suspicious files

---

## FAQ

**Q: Can I use LibreOffice to edit templates?**  
A: Not recommended. Use Microsoft Word to ensure full compatibility. PHPWord is optimized for Word formats.

**Q: What if Gotenberg container crashes?**  
A: PDF generation will fail gracefully with user error message. Restart container: `docker restart gotenberg`

**Q: Can I cache generated PDFs?**  
A: Yes, you can implement caching. Consider:
- Cache key based on document type + record ID + last modified date
- Store in database or filesystem
- Implement cache invalidation on record updates

**Q: How do I add a new document type?**  
A:
1. Create DOCX template with placeholders
2. Add to `ressources/templates/`
3. Create controller method
4. Call `generateFromTemplate()` with data
5. Document in TEMPLATE_STATUS.md
6. Test thoroughly

**Q: Can I generate PDFs in other languages?**  
A: Yes, templates support UTF-8. Ensure:
- Template uses appropriate fonts
- Database data is UTF-8 encoded
- Gotenberg container has language support

**Q: What's the maximum PDF size?**  
A: Limited by:
- Gotenberg memory (container config)
- PHP memory_limit
- Web server timeout
- Practical limit: ~50 MB PDFs, ~500 pages

**Q: How do I debug placeholder issues?**  
A:
1. Check logs for "Placeholder 'xxx' not found"
2. Add logging in controller: `error_log(print_r($data, true));`
3. Open template, verify placeholder exists
4. Test with minimal data first

---

## Success Criteria

Mark the refactoring as complete when:

- [x] All Dompdf code removed
- [x] DocumentGeneratorService enhanced with error handling
- [x] All documentation complete
- [ ] All 6 PDF types tested with real data
- [ ] All tests pass (listed above)
- [ ] Templates verified in Word
- [ ] No critical issues found
- [ ] Stakeholders approve quality

---

## Support & Escalation

### When to Escalate

- Persistent Gotenberg failures
- Performance degradation
- Data corruption in PDFs
- Security concerns
- Template corruption

### Information to Provide

When reporting issues:

1. **What**: Specific document type affected
2. **When**: Time of occurrence, frequency
3. **Who**: User role, affected users
4. **Error Messages**: From UI and logs
5. **Steps to Reproduce**: Exact actions taken
6. **Environment**: Dev/Staging/Production
7. **Recent Changes**: Any template or code updates

### Logs to Include

```bash
# PHP error logs (last 100 lines)
tail -n 100 /var/log/php/error.log | grep DocumentGeneratorService

# Gotenberg logs
docker logs gotenberg --tail 100

# System info
docker ps
df -h
free -m
```

---

## Conclusion

The PDF generation system is now:
- ✅ **Unified**: Single service for all PDF generation
- ✅ **Reliable**: Comprehensive error handling
- ✅ **Maintainable**: Clear code structure and documentation
- ✅ **Secure**: Input validation and XSS prevention
- ✅ **Observable**: Detailed logging for troubleshooting

**Next action**: Perform manual testing as outlined above to verify all functionality works correctly with real data.

---

**Document Version**: 1.0  
**Last Updated**: 2025-11-05  
**Maintained By**: Development Team

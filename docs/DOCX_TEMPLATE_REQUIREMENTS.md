# DOCX Template Requirements for PDF Generation

This document outlines the required placeholders for all DOCX templates used in the PDF generation system.

## Template: pv_soutenance.docx

This template should contain **3 pages** (use Word page breaks between sections):

### Page 1: Annexe 1 - Soutenance de Mémoire

**General Information Placeholders:**
- `${niveau}` - Niveau (Master 1 / Master 2)
- `${date_soutenance}` - Date de soutenance (format: dd/mm/yyyy)
- `${promotion}` - Promotion (e.g., "M2 SIRI")
- `${theme}` - Thème de soutenance
- `${nom_etudiant}` - Nom et prénoms de l'étudiant

**Jury Members:**
- `${president}` - Président du jury
- `${examinateur}` - Examinateur
- `${directeur}` - Directeur de mémoire
- `${encadreur}` - Encadrant
- `${maitre_stage}` - Maître de stage

**Criteria Table (Repeating Block):**
Create a table with one row containing these placeholders (PHPWord will clone this row for each criterion):
- `${lib_critere}` - Libellé du critère
- `${note}` - Note obtenue
- `${bareme}` - Barème maximum

**Totals:**
- `${note_finale}` - Note finale (somme des notes)
- `${total_bareme}` - Total barème

---

### Page 2: Annexe 2 - PV Jury (Formation Initiale)

**Title:** PROCÈS VERBAL DU JURY DE SOUTENANCE

**General Information:** (Same as Annexe 1)
- `${niveau}`
- `${date_soutenance}`
- `${promotion}`
- `${theme}`
- `${nom_etudiant}`

**Points d'Appréciation Table:**

| POINTS D'APPRECIATION | NOTE OBTENUE | Coeff. | Moyenne Coeff. |
|-----------------------|--------------|--------|----------------|
| 1. Moyenne Générale Master 1 | `${moyenne_master1}` | `${coef_master1}` | (calculated in template) |
| 2. Moyenne Générale Semestre 1 Master 2 | `${moyenne_s1_master2}` | `${coef_s1_master2}` | (calculated in template) |
| 3. Mémoire de fin de cycle | `${note_memoire}` | `${coef_memoire}` | (calculated in template) |
| **TOTAL** | | 8 | /160 |
| **Moyenne** | | | `${note_finale_pv}` / 20 |

**Mention:**
- `${mention}` - Mention (Très Bien / Bien / Assez Bien / Passable / Insuffisant)

**Coefficients (hardcoded values shown for reference):**
- Moyenne Master 1: coef = 2
- Moyenne S1 Master 2: coef = 3
- Mémoire: coef = 3
- Total: 8

**Formula:** Note Finale = (Moyenne M1 × 2 + Moyenne S1 M2 × 3 + Mémoire × 3) / 8

---

### Page 3: Annexe 3 - PV Jury Formation Continue

**Title:** PROCÈS VERBAL DU JURY DE SOUTENANCE (FC)

**General Information:** (Same as Annexe 1)
- `${niveau}`
- `${date_soutenance}`
- `${promotion}`
- `${theme}`
- `${nom_etudiant}`

**Éléments Table:**

| ÉLÉMENTS | MOYENNES | COEF | TOTAL |
|----------|----------|------|-------|
| MOYENNE GÉNÉRALE MASTER 1 | `${moyenne_master1}` | `${coef_master1_fc}` | (calculated) |
| NOTE DE MÉMOIRE | `${note_memoire}` | `${coef_memoire_fc}` | (calculated) |
| **NOTE FINALE** | | `${total_coef_fc}` | `${note_finale_fc}` / 20 |

**Mention:**
- `${mention_fc}` - Mention Formation Continue

**Coefficients (hardcoded values shown for reference):**
- Moyenne Master 1: coef = 1
- Mémoire: coef = 2
- Total: 3

**Formula:** Note Finale FC = (Moyenne M1 × 1 + Mémoire × 2) / 3

**Note:** This page is for students in Formation Continue (FC). The calculation uses only Master 1 average and thesis grade.

---

## Template: recu_inscription.docx

**Required Placeholders:**
- `${id_inscription}` - Numéro d'inscription
- `${nom_etudiant}` - Nom de l'étudiant
- `${prenom_etudiant}` - Prénom de l'étudiant
- `${nom_niveau}` - Nom du niveau (e.g., "Master 2 SIRI")
- `${annee_academique}` - Année académique (e.g., "2024-2025")
- `${montant_total}` - Montant total (formatted with spaces)
- `${montant_paye}` - Montant payé (formatted with spaces)
- `${reste_a_payer}` - Reste à payer (formatted with spaces)
- `${methode_paiement}` - Méthode de paiement
- `${date_inscription}` - Date d'inscription (format: dd/mm/yyyy)
- `${nombre_tranche}` - Nombre de tranches
- `${prochain_versement}` - Montant du prochain versement (formatted)
- `${date_prochain_versement}` - Date du prochain versement (format: dd/mm/yyyy)

---

## Template: releve_notes.docx

**General Information:**
- `${nom_etu}` - Nom de l'étudiant
- `${prenom_etu}` - Prénom de l'étudiant
- `${num_etu}` - Numéro étudiant
- `${promotion_etu}` - Promotion
- `${niveau}` - Niveau

**Statistics:**
- `${moyenne_generale}` - Moyenne générale
- `${nb_ue_valide}` - Nombre d'UE validées
- `${classement}` - Classement
- `${total_etudiants}` - Total étudiants

**Notes Table (Repeating Block):**
Create a table with one row containing these placeholders:
- `${lib_ue}` - Libellé de l'UE
- `${credit}` - Crédits
- `${moyenne}` - Moyenne
- `${resultat}` - Résultat (Validé / Non validé)

---

## Implementation Notes

### Data Provided by Controllers

All placeholders are populated by the respective controllers:
- **EvaluationSoutenanceController**: Provides data for pv_soutenance.docx (all 3 annexes)
- **InscriptionController**: Provides data for recu_inscription.docx
- **Notes/Evaluations Controller**: Should provide data for releve_notes.docx (migration pending)

### Repeating Blocks

PHPWord's `TemplateProcessor` handles repeating blocks automatically:
1. Create a table row with placeholders
2. The controller passes an array of data
3. PHPWord clones the row for each data item
4. Format: `${placeholder}#1`, `${placeholder}#2`, etc.

### Formatting

- Dates are formatted as: `dd/mm/yyyy`
- Numbers (amounts) are formatted with spaces as thousand separators
- Notes are formatted with 2 decimal places

---

## Migration Status

- ✅ **pv_soutenance.docx**: Already in use with DocumentGeneratorService
- ✅ **recu_inscription.docx**: Already in use with DocumentGeneratorService
- ⚠️ **releve_notes.docx**: Template exists but not yet connected to controller

---

## Next Steps for Template Creation/Update

1. Open `pv_soutenance.docx` in Microsoft Word
2. Ensure it has 3 pages (add page breaks if needed)
3. Add missing placeholders for Annexe 2 and Annexe 3 (see above)
4. Ensure all `${}` placeholders are typed as a single text run (not split across formatting changes)
5. Test the template with DocumentGeneratorService
6. Verify the generated PDF contains all 3 annexes correctly

**Tip:** To ensure placeholders aren't split, type them in Notepad first, then paste into Word.

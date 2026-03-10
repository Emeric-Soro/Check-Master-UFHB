#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Correction automatique id_inscription dans les Services
Pattern: Remplacer sous-requêtes SELECT id_inscription par LATERAL JOIN
"""

import re
import os
import shutil

# Fichiers à corriger (ceux avec id_inscription_select ou id_inscription_join)
FILES_TO_FIX = [
    'app/Services/ProgrammationSoutenanceService.php',
    'app/Services/EvaluationSoutenanceService.php',
    'app/Services/GestionDossiersCandidaturesService.php',
    'app/Services/PlanificationSoutenanceService.php',
    'app/Services/ProcessusValidationService.php',
    'app/Services/RedactionCompteRenduService.php',
   'app/Services/DossierAcademiqueService.php',
    'app/Services/GestionRapportService.php',
    'app/Services/NotesService.php',
]

def backup_file(filepath):
    """Créer sauvegarde"""
    backup_dir = 'backups_services_inscription'
    rel_path = os.path.relpath(filepath)
    backup_path = os.path.join(backup_dir, rel_path)
    backup_folder = os.path.dirname(backup_path)
    
    if not os.path.exists(backup_folder):
        os.makedirs(backup_folder)
    
    shutil.copy2(filepath, backup_path)
    return backup_path

def fix_simple_select_id_inscription(content):
    """Corriger les SELECT id_annee_acad FROM inscriptions WHERE id_etud"""
    changes = []
    
    # Pattern 1: SELECT id_annee_acad FROM inscriptions WHERE id_etudiant = ?
    pattern1 = r'SELECT\s+id_annee_acad\s+FROM\s+inscriptions\s+WHERE\s+id_etudiant\s*=\s*\?'
    if re.search(pattern1, content, re.IGNORECASE):
        new_pattern = 'SELECT id_annee_acad FROM inscriptions WHERE num_carte_etud = ? ORDER BY date_inscription DESC, num_versement DESC LIMIT 1'
        content = re.sub(pattern1, new_pattern, content, flags=re.IGNORECASE)
        changes.append('Corrigé: SELECT id_annee_acad WHERE id_etudiant')
    
    # Pattern 2: LEFT JOIN inscriptions ins ON ins.id_inscription = (subquery)
    pattern2 = r'LEFT\s+JOIN\s+inscriptions\s+(\w+)\s+ON\s+\1\.id_inscription\s*=\s*\(\s*SELECT[^)]+SELECT\s+i2\.id_inscription[^)]+\)'
    matches = list(re.finditer(pattern2, content, re.IGNORECASE | re.DOTALL))
    if matches:
        for match in matches:
            alias = match.group(1)
            # Remplacer par LATERAL JOIN
            new_join = f'''LEFT JOIN LATERAL (
                        SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement
                        FROM inscriptions i2 
                        WHERE i2.num_carte_etud = e.num_carte_etud 
                        ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                    ) {alias} ON TRUE'''
            content = content.replace(match.group(0), new_join)
            changes.append(f'Corrigé: LEFT JOIN {alias} avec LATERAL')
    
    return content, changes

def fix_lateral_join_pattern(content):
    """Remplacer pattern LEFT JOIN inscriptions ON id_inscription = (SELECT...) par LATERAL"""
    changes = []
    
    # Pattern complexe avec sous-requête
    pattern = r'''LEFT\s+JOIN\s+inscriptions\s+(\w+)\s+ON\s+\1\.id_inscription\s*=\s*\(
        \s*SELECT\s+i\d*\.id_inscription\s+FROM\s+inscriptions\s+i\d*
        \s+WHERE\s+i\d*\.(?:num_carte_etud|id_etudiant)[^)]+
        \s*\)'''
    
    def replace_with_lateral(match):
        alias = match.group(1)
        return f'''LEFT JOIN LATERAL (
                        SELECT i2.num_carte_etud, i2.id_annee_acad, i2.num_versement, i2.date_inscription
                        FROM inscriptions i2 
                        WHERE i2.num_carte_etud = e.num_carte_etud 
                        ORDER BY i2.date_inscription DESC, i2.num_versement DESC LIMIT 1
                    ) {alias} ON TRUE'''
    
    new_content = re.sub(pattern, replace_with_lateral, content, flags=re.IGNORECASE | re.VERBOSE)
    
    if new_content != content:
        changes.append('Appliqué LATERAL JOIN pour inscriptions')
        
    return new_content, changes

def fix_service_file(filepath):
    """Corriger un fichier service"""
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
        
        original_content = content
        all_changes = []
        
        # Appliquer corrections
        content, changes1 = fix_simple_select_id_inscription(content)
        all_changes.extend(changes1)
        
        content, changes2 = fix_lateral_join_pattern(content)
        all_changes.extend(changes2)
        
        if content != original_content:
            backup_path = backup_file(filepath)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            
            return {
                'filepath': filepath,
                'backup': backup_path,
                'changes': all_changes,
                'success': True
            }
        else:
            return {
                'filepath': filepath,
                'changes': [],
                'success': True,
                'no_changes': True
            }
    
    except Exception as e:
        return {
            'filepath': filepath,
            'success': False,
            'error': str(e)
        }

def main():
    print("🔧 CORRECTION id_inscription DANS LES SERVICES")
    print("=" * 80)
    
    results = []
    files_modified = 0
    total_changes = 0
    
    for filepath in FILES_TO_FIX:
        if not os.path.exists(filepath):
            print(f"⚠️  {filepath} - Fichier introuvable")
            continue
        
        result = fix_service_file(filepath)
        results.append(result)
        
        if result['success']:
            if not result.get('no_changes'):
                files_modified += 1
                num_changes = len(result['changes'])
                total_changes += num_changes
                print(f"✅ {os.path.basename(filepath)} - {num_changes} corrections")
                for change in result['changes']:
                    print(f"   • {change}")
            else:
                print(f"⏭️  {os.path.basename(filepath)} - Aucune correction nécessaire")
        else:
            print(f"❌ {os.path.basename(filepath)} - ERREUR: {result['error']}")
    
    print("\n" + "=" * 80)
    print(f"📊 RÉSUMÉ: {files_modified} fichiers modifiés, {total_changes} changements")
    print("=" * 80)
    
    # Rapport JSON
    import json
    with open('rapport_correction_id_inscription.json', 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2, ensure_ascii=False)
    
    print("📄 Rapport: rapport_correction_id_inscription.json")

if __name__ == '__main__':
    main()

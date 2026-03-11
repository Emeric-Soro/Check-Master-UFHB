#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Correction automatique des services - Patterns simples
"""

import re
import os
import shutil
from datetime import datetime

# Patterns de remplacement simples et sûrs
SAFE_REPLACEMENTS = [
    # genre_etu dans validations et conditions
    (r"empty\(\s*\$data\s*\[\s*['\"]genre_etu['\"]\s*\]\s*\)", 
     r"empty($data['id_genre'])", 
     'Validation genre_etu → id_genre'),
    
    (r"\$data\s*\[\s*['\"]genre_etu['\"]\s*\]", 
     r"$data['id_genre']", 
     'Accès genre_etu → id_genre'),
    
    (r"\$etudiant\s*\[\s*['\"]genre_etu['\"]\s*\]", 
     r"$etudiant['id_genre']", 
     'Etudiant genre_etu → id_genre'),
    
    (r"\$row\s*\[\s*['\"]genre_etu['\"]\s*\]", 
     r"$row['id_genre']", 
     'Row genre_etu → id_genre'),
    
    (r"'genre_etu'\s*=>\s*\$", 
     r"'id_genre' => $", 
     'Array key genre_etu → id_genre'),
]

def backup_file(filepath):
    """Créer une sauvegarde du fichier"""
    backup_dir = 'backups_services_correction'
    if not os.path.exists(backup_dir):
        os.makedirs(backup_dir)
    
    rel_path = os.path.relpath(filepath)
    backup_path = os.path.join(backup_dir, rel_path)
    backup_folder = os.path.dirname(backup_path)
    
    if not os.path.exists(backup_folder):
        os.makedirs(backup_folder)
    
    shutil.copy2(filepath, backup_path)
    return backup_path

def apply_replacements(content, filepath):
    """Appliquer les remplacements"""
    changes = []
    modified_content = content
    
    for pattern, replacement, description in SAFE_REPLACEMENTS:
        matches = list(re.finditer(pattern, modified_content))
        if matches:
            for match in matches:
                changes.append({
                    'type': 'replacement',
                    'description': description,
                    'old': match.group(0),
                    'new': re.sub(pattern, replacement, match.group(0)),
                    'line': content[:match.start()].count('\n') + 1
                })
            modified_content = re.sub(pattern, replacement, modified_content)
    
    return modified_content, changes

def fix_service(filepath):
    """Corriger un service"""
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            original_content = f.read()
        
        content, changes = apply_replacements(original_content, filepath)
        
        if content != original_content:
            backup_path = backup_file(filepath)
            
            with open(filepath, 'w', encoding='utf-8') as f:
                f.write(content)
            
            return {
                'filepath': filepath,
                'backup': backup_path,
                'changes': changes,
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
    # Liste des services à corriger (ceux détectés avec genre_etu)
    services_to_fix = [
        'app/Services/GestionEtudiantService.php',
        'app/Services/EtudiantService.php',
        'app/Services/Document/RapportPdfGeneratorService.php',
    ]
    
    print("🔧 CORRECTION AUTOMATIQUE DES SERVICES - Patterns simples")
    print("=" * 80)
    print(f"\n📝 Services à traiter: {len(services_to_fix)}")
    
    results = []
    files_modified = 0
    total_changes = 0
    
    for filepath in services_to_fix:
        if not os.path.exists(filepath):
            print(f"⚠️  {filepath} - Fichier introuvable")
            continue
        
        result = fix_service(filepath)
        results.append(result)
        
        if result['success']:
            if not result.get('no_changes'):
                files_modified += 1
                num_changes = len(result['changes'])
                total_changes += num_changes
                print(f"✅ {filepath} - {num_changes} changements")
                for change in result['changes'][:3]:  # Afficher 3 premiers
                    print(f"   L{change['line']}: {change['description']}")
            else:
                print(f"⏭️  {filepath} - Déjà à jour")
        else:
            print(f"❌ {filepath} - ERREUR: {result['error']}")
    
    print("\n" + "=" * 80)
    print("📊 RÉSUMÉ")
    print("=" * 80)
    print(f"✅ Fichiers modifiés: {files_modified}")
    print(f"📝 Total des changements: {total_changes}")
    print(f"💾 Sauvegardes: backups_services_correction/")
    
    # Rapport JSON
    import json
    rapport_file = f'rapport_correction_services_{datetime.now().strftime("%Y%m%d_%H%M%S")}.json'
    with open(rapport_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2, ensure_ascii=False)
    
    print(f"📄 Rapport: {rapport_file}")
    
    print("\n⚠️  ATTENTION:")
    print("Les problèmes suivants nécessitent une correction MANUELLE:")
    print("  • id_inscription dans SELECT/JOIN (28 occurrences)")
    print("  • montant_scolarite/montant_inscription (11 occurrences)")

if __name__ == '__main__':
    main()

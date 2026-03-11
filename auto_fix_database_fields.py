#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script pour corriger automatiquement les patterns communs dans les fichiers PHP
pour correspondre à la nouvelle structure de la base de données
"""

import re
import os
import shutil
from datetime import datetime

# Patterns de remplacement
REPLACEMENTS = [
    # Inscriptions - champs dans le SELECT
    (r'\bi\.id_etudiant\b', r'i.num_carte_etud', 'i.id_etudiant → i.num_carte_etud'),
    (r'\bi\.id_niveau\b', r'i.id_niv_etude', 'i.id_niveau → i.id_niv_etude'),
    
    # Inscriptions - dans WHERE/JOIN
    (r'WHERE\s+i\.id_etudiant\s*=', r'WHERE i.num_carte_etud =', 'WHERE i.id_etudiant → i.num_carte_etud'),
    (r'ON\s+i\.id_etudiant\s*=', r'ON i.num_carte_etud =', 'ON i.id_etudiant → i.num_carte_etud'),
    (r'ON\s+i\.id_niveau\s*=', r'ON i.id_niv_etude =', 'ON i.id_niveau → i.id_niv_etude'),
    
    # Inscriptions avec alias i2, i3, etc.
    (r'\bi2\.id_etudiant\b', r'i2.num_carte_etud', 'i2.id_etudiant → i2.num_carte_etud'),
    (r'\bi3\.id_etudiant\b', r'i3.num_carte_etud', 'i3.id_etudiant → i3.num_carte_etud'),
    (r'\bi4\.id_etudiant\b', r'i4.num_carte_etud', 'i4.id_etudiant → i4.num_carte_etud'),
    
    # Étudiants - genre
    (r'\be\.genre_etu\b', r'e.id_genre', 'e.genre_etu → e.id_genre'),
    (r'etudiants\.genre_etu\b', r'etudiants.id_genre', 'etudiants.genre_etu → id_genre'),
    
    # Inscriptions dans INSERT
    (r'INSERT\s+INTO\s+inscriptions\s*\([^)]*id_etudiant', 
     lambda m: m.group(0).replace('id_etudiant', 'num_carte_etud'),
     'INSERT inscriptions: id_etudiant → num_carte_etud'),
    
    # Inscriptions dans UPDATE SET
    (r'UPDATE\s+inscriptions\s+SET[^W]*id_niveau\s*=',
     lambda m: m.group(0).replace('id_niveau', 'id_niv_etude'),
     'UPDATE inscriptions: id_niveau → id_niv_etude'),
]

# Patterns plus complexes nécessitant une analyse contextuelle
COMPLEX_PATTERNS = [
    # Références à n.montant_scolarite ou n.montant_inscription
    {
        'pattern': r'(n\.(montant_scolarite|montant_inscription))',
        'context': 'niveau_etude',
        'action': 'comment',
        'comment': '/* FIXME: montant_* n\'existe plus dans niveau_etude. Utiliser frais_inscription */',
        'description': 'Montants dans niveau_etude'
    },
    
    # Références à id_inscription dans WHERE
    {
        'pattern': r'WHERE\s+i\.id_inscription\s*=',
        'context': 'inscriptions',
        'action': 'comment',
        'comment': '/* FIXME: id_inscription n\'existe plus. Utiliser PK composite (num_carte_etud, id_annee_acad, num_versement) */',
        'description': 'WHERE id_inscription'
    },
]

def backup_file(filepath):
    """Créer une sauvegarde du fichier"""
    backup_dir = 'backups_before_autofix'
    if not os.path.exists(backup_dir):
        os.makedirs(backup_dir)
    
    # Créer la structure de dossiers dans le backup
    rel_path = os.path.relpath(filepath)
    backup_path = os.path.join(backup_dir, rel_path)
    backup_folder = os.path.dirname(backup_path)
    
    if not os.path.exists(backup_folder):
        os.makedirs(backup_folder)
    
    shutil.copy2(filepath, backup_path)
    return backup_path

def apply_simple_replacements(content, filepath):
    """Appliquer les remplacements simples"""
    changes = []
    modified_content = content
    
    for pattern, replacement, description in REPLACEMENTS:
        if callable(replacement):
            # Remplacement avec fonction
            matches = list(re.finditer(pattern, modified_content, re.IGNORECASE | re.MULTILINE))
            for match in reversed(matches):  # Reversed pour ne pas affecter les positions
                old_text = match.group(0)
                new_text = replacement(match)
                if old_text != new_text:
                    modified_content = modified_content[:match.start()] + new_text + modified_content[match.end():]
                    changes.append({
                        'type': 'replacement',
                        'description': description,
                        'old': old_text,
                        'new': new_text,
                        'line': content[:match.start()].count('\n') + 1
                    })
        else:
            # Remplacement simple
            matches = list(re.finditer(pattern, modified_content, re.IGNORECASE))
            if matches:
                for match in matches:
                    changes.append({
                        'type': 'replacement',
                        'description': description,
                        'old': match.group(0),
                        'new': replacement,
                        'line': content[:match.start()].count('\n') + 1
                    })
                modified_content = re.sub(pattern, replacement, modified_content, flags=re.IGNORECASE)
    
    return modified_content, changes

def apply_complex_patterns(content, filepath):
    """Appliquer les patterns complexes qui nécessitent des commentaires"""
    changes = []
    modified_content = content
    
    for pattern_info in COMPLEX_PATTERNS:
        pattern = pattern_info['pattern']
        matches = list(re.finditer(pattern, modified_content, re.IGNORECASE | re.MULTILINE))
        
        for match in reversed(matches):
            if pattern_info['action'] == 'comment':
                # Ajouter un commentaire avant la ligne problématique
                line_start = modified_content.rfind('\n', 0, match.start()) + 1
                indent = len(modified_content[line_start:match.start()]) - len(modified_content[line_start:match.start()].lstrip())
                comment = ' ' * indent + pattern_info['comment'] + '\n'
                
                # Vérifier si le commentaire n'existe pas déjà
                if pattern_info['comment'] not in modified_content[max(0, line_start-200):line_start+200]:
                    modified_content = modified_content[:line_start] + comment + modified_content[line_start:]
                    changes.append({
                        'type': 'comment_added',
                        'description': pattern_info['description'],
                        'comment': pattern_info['comment'],
                        'line': content[:match.start()].count('\n') + 1
                    })
    
    return modified_content, changes

def fix_file(filepath):
    """Corriger un fichier PHP"""
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            original_content = f.read()
        
        # Appliquer les corrections
        content = original_content
        all_changes = []
        
        # Corrections simples
        content, simple_changes = apply_simple_replacements(content, filepath)
        all_changes.extend(simple_changes)
        
        # Corrections complexes
        content, complex_changes = apply_complex_patterns(content, filepath)
        all_changes.extend(complex_changes)
        
        # Si des changements ont été effectués
        if content != original_content:
            # Créer une sauvegarde
            backup_path = backup_file(filepath)
            
            # Écrire le nouveau contenu
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
    """Fonction principale"""
    import sys
    
    # Dossier app
    app_dir = 'app'
    
    # Rechercher tous les fichiers PHP
    php_files = []
    for root, dirs, files in os.walk(app_dir):
        for file in files:
            if file.endswith('.php'):
                php_files.append(os.path.join(root, file))
    
    print(f"🔍 {len(php_files)} fichiers PHP trouvés dans {app_dir}/")
    print("=" * 80)
    
    # Demander confirmation
    response = input(f"\n⚠️  Voulez-vous corriger automatiquement ces fichiers ? (y/n): ")
    if response.lower() != 'y':
        print("❌ Opération annulée")
        return
    
    # Traiter chaque fichier
    results = []
    files_modified = 0
    total_changes = 0
    
    print("\n🔧 Traitement en cours...\n")
    
    for i, filepath in enumerate(php_files, 1):
        result = fix_file(filepath)
        results.append(result)
        
        if result['success']:
            if not result.get('no_changes'):
                files_modified += 1
                num_changes = len(result['changes'])
                total_changes += num_changes
                print(f"✅ [{i}/{len(php_files)}] {filepath} - {num_changes} changements")
            else:
                print(f"⏭️  [{i}/{len(php_files)}] {filepath} - Aucun changement nécessaire")
        else:
            print(f"❌ [{i}/{len(php_files)}] {filepath} - ERREUR: {result['error']}")
    
    # Rapport final
    print("\n" + "=" * 80)
    print("📊 RAPPORT FINAL")
    print("=" * 80)
    print(f"✅ Fichiers traités: {len(php_files)}")
    print(f"🔧 Fichiers modifiés: {files_modified}")
    print(f"📝 Total des changements: {total_changes}")
    print(f"💾 Sauvegardes créées dans: backups_before_autofix/")
    
    # Détails des changements par type
    print("\n📋 DÉTAILS DES CHANGEMENTS:")
    change_types = {}
    for result in results:
        if result['success'] and not result.get('no_changes'):
            for change in result['changes']:
                desc = change['description']
                if desc not in change_types:
                    change_types[desc] = 0
                change_types[desc] += 1
    
    for desc, count in sorted(change_types.items(), key=lambda x: x[1], reverse=True):
        print(f"  • {desc}: {count} occurrences")
    
    # Générer un rapport JSON
    import json
    rapport_file = f'rapport_autofix_{datetime.now().strftime("%Y%m%d_%H%M%S")}.json'
    with open(rapport_file, 'w', encoding='utf-8') as f:
        json.dump(results, f, indent=2, ensure_ascii=False)
    
    print(f"\n📄 Rapport détaillé sauvegardé: {rapport_file}")
    
    # Liste des fichiers nécessitant une attention manuelle
    files_with_comments = [r for r in results if r['success'] and any(c['type'] == 'comment_added' for c in r.get('changes', []))]
    if files_with_comments:
        print(f"\n⚠️  {len(files_with_comments)} fichiers nécessitent une attention manuelle (marqués FIXME):")
        for result in files_with_comments[:10]:  # Limiter à 10
            print(f"  • {result['filepath']}")
        if len(files_with_comments) > 10:
            print(f"  ... et {len(files_with_comments) - 10} autres")
    
    print("\n✨ Correction automatique terminée!")
    print("⚠️  IMPORTANT: Vérifiez les fichiers modifiés avant de les utiliser en production!")

if __name__ == '__main__':
    main()

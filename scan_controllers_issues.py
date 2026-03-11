#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scanner de problèmes dans les contrôleurs
Identifie les références aux anciennes structures de la base de données
"""

import os
import re
from pathlib import Path
from collections import defaultdict

# Configuration
CONTROLLERS_DIR = "app/controllers"
EXCLUDE_FILES = ["enregistrerPaiement_method.php"]  # Fichiers legacy

# Patterns de problèmes
PROBLEMS = {
    'genre_etu': {
        'pattern': r'\bgenre_etu\b(?!\s*=>)',
        'severity': 'HIGH',
        'description': 'Référence à genre_etu au lieu de id_genre'
    },
    'id_inscription_select': {
        'pattern': r'\bSELECT\b.*?\bid_inscription\b',
        'severity': 'HIGH',
        'description': 'SELECT avec id_inscription (champ n\'existe plus)'
    },
    'id_inscription_join': {
        'pattern': r'\bJOIN\b.*?\bid_inscription\b',
        'severity': 'HIGH',
        'description': 'JOIN avec id_inscription (champ n\'existe plus)'
    },
    'id_inscription_where': {
        'pattern': r'\bWHERE\b.*?\bid_inscription\b',
        'severity': 'MEDIUM',
        'description': 'WHERE avec id_inscription (vérifier si OK)'
    },
    'montant_scolarite': {
        'pattern': r'\bmontant_scolarite\b',
        'severity': 'MEDIUM',
        'description': 'Référence à montant_scolarite (utiliser frais_inscription)'
    },
    'montant_inscription': {
        'pattern': r'\bmontant_inscription\b',
        'severity': 'MEDIUM',
        'description': 'Référence à montant_inscription (utiliser frais_inscription)'
    },
    'id_inscription_insert': {
        'pattern': r'\bINSERT\b.*?\bid_inscription\b',
        'severity': 'LOW',
        'description': 'INSERT avec id_inscription (vérifier si correct)'
    },
    'id_inscription_update': {
        'pattern': r'\bUPDATE\b.*?\bid_inscription\b',
        'severity': 'LOW',
        'description': 'UPDATE avec id_inscription (vérifier si correct)'
    }
}

def scan_file(filepath):
    """Scanne un fichier et retourne les problèmes trouvés"""
    try:
        with open(filepath, 'r', encoding='utf-8') as f:
            content = f.read()
    except Exception as e:
        return {'error': str(e)}
    
    problems_found = defaultdict(list)
    lines = content.split('\n')
    
    for problem_key, problem_info in PROBLEMS.items():
        pattern = re.compile(problem_info['pattern'], re.IGNORECASE | re.MULTILINE | re.DOTALL)
        
        # Chercher dans le contenu complet
        for match in pattern.finditer(content):
            # Trouver le numéro de ligne
            line_num = content[:match.start()].count('\n') + 1
            line_content = lines[line_num - 1].strip() if line_num <= len(lines) else ''
            
            # Filtres pour réduire les faux positifs
            if problem_key == 'genre_etu':
                # Ignorer si c'est dans un commentaire
                if '//' in line_content[:line_content.find('genre_etu')] or \
                   '/*' in content[max(0, match.start()-50):match.start()] or \
                   '*' in line_content[:line_content.find('genre_etu')]:
                    continue
                # Ignorer $data['genre_etu'] = ... si suivi correctement
                if "'genre_etu']" in line_content and "$data['id_genre']" in content[match.start()-100:match.start()+100]:
                    continue
            
            if 'id_inscription' in problem_key:
                # Ignorer les commentaires
                if '//' in line_content or '*' in line_content[:10]:
                    continue
                # Ignorer si c'est dans une string pour affichage
                if '"id_inscription"' in line_content or "'id_inscription'" in line_content:
                    if 'echo' in line_content or 'print' in line_content or 'label' in line_content.lower():
                        continue
            
            problems_found[problem_key].append({
                'line': line_num,
                'content': line_content,
                'severity': problem_info['severity']
            })
    
    return problems_found if problems_found else None

def main():
    print("=" * 80)
    print("SCANNER DE PROBLÈMES - CONTRÔLEURS")
    print("=" * 80)
    print()
    
    controllers_path = Path(CONTROLLERS_DIR)
    if not controllers_path.exists():
        print(f"❌ Dossier {CONTROLLERS_DIR} introuvable")
        return
    
    # Parcourir tous les fichiers PHP
    all_files = []
    for root, dirs, files in os.walk(controllers_path):
        for file in files:
            if file.endswith('.php') and file not in EXCLUDE_FILES:
                # Ignorer les backups
                full_path = os.path.join(root, file)
                if 'backup' not in full_path.lower():
                    all_files.append(full_path)
    
    print(f"📁 {len(all_files)} contrôleurs trouvés\n")
    
    # Scanner tous les fichiers
    results = {}
    total_problems = 0
    files_with_problems = 0
    problem_distribution = defaultdict(int)
    severity_count = {'HIGH': 0, 'MEDIUM': 0, 'LOW': 0}
    
    for filepath in sorted(all_files):
        problems = scan_file(filepath)
        if problems:
            if 'error' in problems:
                print(f"❌ Erreur lecture: {filepath}")
                print(f"   {problems['error']}\n")
            else:
                relative_path = os.path.relpath(filepath)
                results[relative_path] = problems
                files_with_problems += 1
                
                # Compter les problèmes
                file_problem_count = sum(len(instances) for instances in problems.values())
                total_problems += file_problem_count
                
                for problem_key, instances in problems.items():
                    problem_distribution[problem_key] += len(instances)
                    severity = PROBLEMS[problem_key]['severity']
                    severity_count[severity] += len(instances)
    
    # Afficher les résultats détaillés
    if results:
        print("🔍 PROBLÈMES DÉTECTÉS:\n")
        for filepath, problems in sorted(results.items()):
            filename = os.path.basename(filepath)
            print(f"📄 {filename}")
            print(f"   Chemin: {filepath}")
            
            for problem_key, instances in sorted(problems.items()):
                problem_info = PROBLEMS[problem_key]
                print(f"   [{problem_info['severity']}] {problem_info['description']}")
                print(f"   Occurrences: {len(instances)}")
                
                # Afficher quelques exemples
                for i, instance in enumerate(instances[:3], 1):
                    preview = instance['content'][:100] + '...' if len(instance['content']) > 100 else instance['content']
                    print(f"      Ligne {instance['line']}: {preview}")
                
                if len(instances) > 3:
                    print(f"      ... et {len(instances) - 3} autre(s) occurrence(s)")
            print()
    
    # Statistiques
    print("=" * 80)
    print("📊 STATISTIQUES")
    print("=" * 80)
    print(f"Contrôleurs scannés: {len(all_files)}")
    print(f"Contrôleurs avec problèmes: {files_with_problems}")
    print(f"Contrôleurs sans problèmes: {len(all_files) - files_with_problems}")
    print(f"Total de problèmes détectés: {total_problems}")
    print()
    
    # Distribution par sévérité
    print("Par sévérité:")
    print(f"  🔴 HIGH:   {severity_count['HIGH']}")
    print(f"  🟡 MEDIUM: {severity_count['MEDIUM']}")
    print(f"  🟢 LOW:    {severity_count['LOW']}")
    print()
    
    # Distribution par type
    if problem_distribution:
        print("Par type de problème:")
        for problem_key in sorted(problem_distribution.keys(), key=lambda k: problem_distribution[k], reverse=True):
            count = problem_distribution[problem_key]
            desc = PROBLEMS[problem_key]['description']
            print(f"  {problem_key}: {count} - {desc}")
    print()
    
    # Résumé des fichiers propres
    clean_files = [f for f in all_files if os.path.relpath(f) not in results]
    if clean_files:
        print("✅ CONTRÔLEURS SANS PROBLÈMES:")
        for filepath in sorted(clean_files):
            print(f"   ✓ {os.path.basename(filepath)}")
    
    print("=" * 80)

if __name__ == '__main__':
    main()

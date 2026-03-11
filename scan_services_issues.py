#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Scanner pour identifier les problèmes dans les Services
"""

import os
import re
from collections import defaultdict

# Patterns problématiques à rechercher
ISSUES = {
    'id_inscription_where': r'WHERE\s+[a-z_]*\.?id_inscription\s*=',
    'id_inscription_select': r'SELECT[^;]*\bid_inscription\b',
    'id_inscription_join': r'ON\s+[a-z_]*\.?id_inscription\s*=',
    'montant_scolarite': r'\bmontant_scolarite\b',
    'montant_inscription': r'\bmontant_inscription\b',
    'table_versements': r'FROM\s+versements|JOIN\s+versements',
    'id_etudiant_insc': r'i\d*\.id_etudiant',
    'id_niveau_insc': r'i\d*\.id_niveau\b',
    'genre_etu': r'\bgenre_etu\b',
}

def scan_file(filepath):
    """Scanner un fichier pour les issues"""
    issues_found = defaultdict(list)
    
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            
        for issue_name, pattern in ISSUES.items():
            matches = re.finditer(pattern, content, re.IGNORECASE | re.MULTILINE)
            for match in matches:
                # Calculer le numéro de ligne
                line_num = content[:match.start()].count('\n') + 1
                # Extraire contexte
                line_start = content.rfind('\n', 0, match.start()) + 1
                line_end = content.find('\n', match.end())
                if line_end == -1:
                    line_end = len(content)
                context = content[line_start:line_end].strip()
                
                issues_found[issue_name].append({
                    'line': line_num,
                    'context': context[:100]  # Limiter à 100 chars
                })
    
    except Exception as e:
        return {'error': str(e)}
    
    return dict(issues_found)

def main():
    services_dir = 'app/Services'
    
    # Scanner tous les services
    all_results = {}
    total_issues = 0
    
    for root, dirs, files in os.walk(services_dir):
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                rel_path = os.path.relpath(filepath)
                
                issues = scan_file(filepath)
                
                if issues and 'error' not in issues:
                    # Compter le nombre total d'issues
                    issue_count = sum(len(v) for v in issues.values())
                    if issue_count > 0:
                        all_results[rel_path] = {
                            'issues': issues,
                            'total': issue_count
                        }
                        total_issues += issue_count
    
    # Afficher les résultats
    print("=" * 80)
    print(f"🔍 SCAN DES SERVICES - {len(all_results)} fichiers avec problèmes")
    print("=" * 80)
    print()
    
    # Trier par nombre d'issues (du plus problématique au moins)
    sorted_results = sorted(all_results.items(), key=lambda x: x[1]['total'], reverse=True)
    
    # Catégoriser par gravité
    critical = []  # > 20 issues
    high = []      # 10-20 issues
    medium = []    # 5-9 issues
    low = []       # 1-4 issues
    
    for filepath, data in sorted_results:
        if data['total'] > 20:
            critical.append((filepath, data))
        elif data['total'] >= 10:
            high.append((filepath, data))
        elif data['total'] >= 5:
            medium.append((filepath, data))
        else:
            low.append((filepath, data))
    
    # Afficher par catégorie
    def print_category(name, items, emoji):
        if items:
            print(f"\n{emoji} {name} ({len(items)} fichiers)")
            print("-" * 80)
            for filepath, data in items:
                print(f"\n📄 {filepath} - {data['total']} problèmes")
                for issue_type, occurrences in data['issues'].items():
                    print(f"   • {issue_type}: {len(occurrences)} fois")
                    # Afficher première occurrence comme exemple
                    if occurrences:
                        first = occurrences[0]
                        print(f"     L{first['line']}: {first['context'][:80]}...")
    
    print_category("CRITIQUE", critical, "🔴")
    print_category("HAUTE PRIORITÉ", high, "🟠")
    print_category("PRIORITÉ MOYENNE", medium, "🟡")
    print_category("FAIBLE PRIORITÉ", low, "🟢")
    
    # Résumé des types d'issues
    print("\n" + "=" * 80)
    print("📊 RÉSUMÉ PAR TYPE DE PROBLÈME")
    print("=" * 80)
    
    issue_summary = defaultdict(int)
    for filepath, data in all_results.items():
        for issue_type, occurrences in data['issues'].items():
            issue_summary[issue_type] += len(occurrences)
    
    for issue_type, count in sorted(issue_summary.items(), key=lambda x: x[1], reverse=True):
        print(f"  • {issue_type}: {count} occurrences")
    
    print("\n" + "=" * 80)
    print(f"📈 TOTAL: {total_issues} problèmes dans {len(all_results)} services")
    print("=" * 80)
    
    # Lister les services sans problèmes
    all_services = []
    for root, dirs, files in os.walk(services_dir):
        for file in files:
            if file.endswith('.php'):
                filepath = os.path.join(root, file)
                all_services.append(os.path.relpath(filepath))
    
    clean_services = [s for s in all_services if s not in all_results]
    
    print(f"\n✅ {len(clean_services)} services sans problèmes détectés:")
    for service in sorted(clean_services):
        print(f"   • {service}")
    
    # Sauvegarder rapport détaillé
    import json
    with open('rapport_scan_services.json', 'w', encoding='utf-8') as f:
        json.dump(all_results, f, indent=2, ensure_ascii=False)
    
    print(f"\n📄 Rapport détaillé: rapport_scan_services.json")

if __name__ == '__main__':
    main()

#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script pour scanner tous les fichiers PHP et identifier les champs de BDD utilisés
Compare avec la structure réelle pour trouver les incohérences
"""

import re
import os
import json
from collections import defaultdict

# Charger la structure réelle de la BDD
with open('STRUCTURE_COMPLETE_BDD.json', 'r', encoding='utf-8') as f:
    db_structure = json.load(f)

# Créer un mapping table -> champs
table_fields = {}
for table_name, columns in db_structure.items():
    table_fields[table_name] = [col['nom'] for col in columns]

# Patterns SQL à rechercher
sql_patterns = [
    r'SELECT\s+(.+?)\s+FROM\s+`?(\w+)`?',
    r'INSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)',
    r'UPDATE\s+`?(\w+)`?\s+SET\s+(.+?)(?:WHERE|$)',
    r'WHERE\s+`?(\w+)`?\.`?(\w+)`?',
    r'->execute\(\s*\[\s*[\'"](\w+)[\'"]\s*=>\s*',
    r'\$row\[[\'"](\w+)[\'"]\]',
    r'\$_POST\[[\'"](\w+)[\'"]\]',
    r'\$data\[[\'"](\w+)[\'"]\]',
]

issues = []
files_processed = 0
php_files = []

# Scanner récursivement le dossier app
for root, dirs, files in os.walk('app'):
    for file in files:
        if file.endswith('.php'):
            php_files.append(os.path.join(root, file))

print(f"🔍 Analyse de {len(php_files)} fichiers PHP...")
print("=" * 80)

for filepath in php_files:
    files_processed += 1
    
    try:
        with open(filepath, 'r', encoding='utf-8', errors='ignore') as f:
            content = f.read()
            
            # Rechercher les requêtes SELECT
            select_matches = re.finditer(
                r'SELECT\s+(.+?)\s+FROM\s+`?(\w+)`?',
                content,
                re.IGNORECASE | re.DOTALL
            )
            
            for match in select_matches:
                fields_str = match.group(1)
                table_name = match.group(2)
                
                # Ignorer les SELECT * ou les fonctions
                if '*' in fields_str or 'COUNT(' in fields_str.upper():
                    continue
                
                # Extraire les champs individuels
                fields = re.findall(r'`?(\w+)`?(?:\s+as\s+\w+)?', fields_str)
                
                # Vérifier si la table existe
                if table_name in table_fields:
                    for field in fields:
                        if field.lower() not in ['select', 'from', 'as', 'distinct', 'all']:
                            if field not in table_fields[table_name]:
                                issues.append({
                                    'file': filepath,
                                    'type': 'FIELD_NOT_EXISTS',
                                    'table': table_name,
                                    'field': field,
                                    'context': match.group(0)[:100]
                                })
            
            # Rechercher les INSERT INTO
            insert_matches = re.finditer(
                r'INSERT\s+INTO\s+`?(\w+)`?\s*\(([^)]+)\)',
                content,
                re.IGNORECASE
            )
            
            for match in insert_matches:
                table_name = match.group(1)
                fields_str = match.group(2)
                
                fields = [f.strip().strip('`').strip('"').strip("'") 
                         for f in fields_str.split(',')]
                
                if table_name in table_fields:
                    for field in fields:
                        if field and field not in table_fields[table_name]:
                            issues.append({
                                'file': filepath,
                                'type': 'INSERT_FIELD_NOT_EXISTS',
                                'table': table_name,
                                'field': field,
                                'context': match.group(0)[:100]
                            })
            
            # Rechercher les UPDATE SET
            update_matches = re.finditer(
                r'UPDATE\s+`?(\w+)`?\s+SET\s+(.+?)(?:WHERE|;|$)',
                content,
                re.IGNORECASE | re.DOTALL
            )
            
            for match in update_matches:
                table_name = match.group(1)
                set_clause = match.group(2)
                
                # Extraire les champs mis à jour
                field_assignments = re.findall(r'`?(\w+)`?\s*=', set_clause)
                
                if table_name in table_fields:
                    for field in field_assignments:
                        if field and field not in table_fields[table_name]:
                            issues.append({
                                'file': filepath,
                                'type': 'UPDATE_FIELD_NOT_EXISTS',
                                'table': table_name,
                                'field': field,
                                'context': match.group(0)[:100]
                            })
            
            # Rechercher les références aux champs dans $row[], $data[], etc.
            field_refs = re.finditer(
                r'\$(?:row|data|result|etudiant|enseignant|inscription)\[[\'"](\w+)[\'"]\]',
                content
            )
            
            # On ne peut pas vérifier ces références sans contexte de la table
            # Mais on les note pour référence
            
    except Exception as e:
        print(f"⚠️  Erreur lors de l'analyse de {filepath}: {e}")

print(f"\n✅ Analyse terminée : {files_processed} fichiers traités")
print(f"⚠️  {len(issues)} problèmes potentiels détectés\n")

# Regrouper les problèmes par fichier
issues_by_file = defaultdict(list)
for issue in issues:
    issues_by_file[issue['file']].append(issue)

# Générer un rapport
print("=" * 80)
print("RAPPORT DES PROBLÈMES DÉTECTÉS")
print("=" * 80)

for filepath in sorted(issues_by_file.keys()):
    file_issues = issues_by_file[filepath]
    print(f"\n📄 {filepath} ({len(file_issues)} problèmes)")
    
    for issue in file_issues[:5]:  # Limiter à 5 par fichier pour la lisibilité
        print(f"   ❌ Table '{issue['table']}' - Champ '{issue['field']}' n'existe pas")
        print(f"      Type: {issue['type']}")
        print(f"      Contexte: {issue['context'][:80]}...")
    
    if len(file_issues) > 5:
        print(f"   ... et {len(file_issues) - 5} autres problèmes")

# Sauvegarder le rapport complet en JSON
with open('RAPPORT_SCAN_CHAMPS_BDD.json', 'w', encoding='utf-8') as f:
    json.dump(issues, f, indent=2, ensure_ascii=False)

print("\n" + "=" * 80)
print(f"📝 Rapport complet sauvegardé dans: RAPPORT_SCAN_CHAMPS_BDD.json")
print("=" * 80)

# Statistiques
tables_with_issues = set(issue['table'] for issue in issues)
print(f"\n📊 STATISTIQUES:")
print(f"  • Fichiers avec problèmes: {len(issues_by_file)}")
print(f"  • Nombre total de problèmes: {len(issues)}")
print(f"  • Tables concernées: {len(tables_with_issues)}")
print(f"\nTables avec le plus de problèmes:")

table_issue_counts = defaultdict(int)
for issue in issues:
    table_issue_counts[issue['table']] += 1

for table, count in sorted(table_issue_counts.items(), key=lambda x: x[1], reverse=True)[:10]:
    print(f"  • {table}: {count} problèmes")

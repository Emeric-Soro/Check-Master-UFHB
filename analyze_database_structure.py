#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Script pour analyser la structure complète de la base de données
depuis le fichier SQL dump
"""

import re
import json

def parse_sql_structure(sql_file_path):
    """Parse le fichier SQL et extrait toute la structure des tables"""
    
    tables_structure = {}
    current_table = None
    in_create_table = False
    table_lines = []
    
    with open(sql_file_path, 'r', encoding='utf-8') as f:
        for line in f:
            # Détection du début d'une création de table
            if 'CREATE TABLE IF NOT EXISTS' in line:
                # Extraire le nom de la table
                match = re.search(r'CREATE TABLE IF NOT EXISTS `(\w+)`', line)
                if match:
                    current_table = match.group(1)
                    in_create_table = True
                    table_lines = []
                    continue
            
            # Si on est dans une définition de table
            if in_create_table:
                table_lines.append(line)
                
                # Détection de la fin de la création de table
                if ') ENGINE' in line:
                    in_create_table = False
                    # Parser les colonnes
                    columns = parse_columns(table_lines)
                    tables_structure[current_table] = columns
                    current_table = None
                    table_lines = []
    
    return tables_structure

def parse_columns(table_lines):
    """Parse les lignes d'une table et extrait les colonnes avec leurs types"""
    columns = []
    
    for line in table_lines:
        line = line.strip()
        
        # Ignorer les lignes vides, les contraintes, les clés, etc.
        if not line or line.startswith('PRIMARY KEY') or line.startswith('KEY ') \
           or line.startswith('UNIQUE KEY') or line.startswith('CONSTRAINT') \
           or line.startswith('FOREIGN KEY') or line.startswith(')'):
            continue
        
        # Extraire le nom de la colonne et son type
        match = re.match(r'`(\w+)`\s+([^,]+)', line)
        if match:
            col_name = match.group(1)
            col_definition = match.group(2).strip().rstrip(',')
            
            # Nettoyer la définition pour extraire juste le type principal
            col_type_match = re.match(r'(\w+(?:\([^)]+\))?(?:\s+unsigned)?)', col_definition)
            col_type = col_type_match.group(1) if col_type_match else col_definition
            
            columns.append({
                'nom': col_name,
                'type': col_type,
                'definition_complete': col_definition
            })
    
    return columns

def generate_markdown_report(tables_structure, output_file):
    """Génère un rapport Markdown avec la structure complète de la BDD"""
    
    with open(output_file, 'w', encoding='utf-8') as f:
        f.write("# STRUCTURE COMPLÈTE DE LA BASE DE DONNÉES\n\n")
        f.write(f"**Date d'analyse:** 10 mars 2026\n")
        f.write(f"**Base de données:** ufrmi1802974_2q2mpf\n")
        f.write(f"**Nombre total de tables:** {len(tables_structure)}\n\n")
        
        f.write("---\n\n")
        f.write("## SOMMAIRE DES TABLES\n\n")
        
        # Index des tables
        for i, table_name in enumerate(sorted(tables_structure.keys()), 1):
            f.write(f"{i}. [{table_name}](#{table_name})\n")
        
        f.write("\n---\n\n")
        f.write("## STRUCTURE DÉTAILLÉE DES TABLES\n\n")
        
        # Détails de chaque table
        for table_name in sorted(tables_structure.keys()):
            columns = tables_structure[table_name]
            f.write(f"### {table_name}\n\n")
            f.write(f"**Nombre de champs:** {len(columns)}\n\n")
            
            if columns:
                f.write("| # | Nom du champ | Type | Définition complète |\n")
                f.write("|---|--------------|------|---------------------|\n")
                
                for i, col in enumerate(columns, 1):
                    nom = col['nom']
                    type_col = col['type']
                    definition = col['definition_complete'].replace('|', '\\|')
                    f.write(f"| {i} | `{nom}` | {type_col} | {definition} |\n")
            else:
                f.write("*Aucune colonne détectée*\n")
            
            f.write("\n---\n\n")

def generate_json_report(tables_structure, output_file):
    """Génère un rapport JSON avec la structure complète"""
    with open(output_file, 'w', encoding='utf-8') as f:
        json.dump(tables_structure, f, indent=2, ensure_ascii=False)

def main():
    sql_file = 'ufrmi1802974_2q2mpf.sql'
    md_output = 'STRUCTURE_COMPLETE_BDD.md'
    json_output = 'STRUCTURE_COMPLETE_BDD.json'
    
    print("🔍 Analyse du fichier SQL en cours...")
    tables_structure = parse_sql_structure(sql_file)
    
    print(f"\n✅ Analyse terminée!")
    print(f"📊 {len(tables_structure)} tables trouvées\n")
    
    # Affichage résumé
    print("=" * 80)
    print("RÉSUMÉ DES TABLES")
    print("=" * 80)
    
    for table_name in sorted(tables_structure.keys()):
        nb_cols = len(tables_structure[table_name])
        print(f"  • {table_name:<35} ({nb_cols} champs)")
    
    print("\n" + "=" * 80)
    
    # Génération des rapports
    print(f"\n📝 Génération du rapport Markdown: {md_output}")
    generate_markdown_report(tables_structure, md_output)
    
    print(f"📝 Génération du rapport JSON: {json_output}")
    generate_json_report(tables_structure, json_output)
    
    print("\n✨ Analyse complète terminée!\n")

if __name__ == '__main__':
    main()

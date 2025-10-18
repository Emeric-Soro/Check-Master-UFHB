# Guide d'Harmonisation Graphique - Views

Ce document décrit la procédure systématique pour harmoniser toutes les pages du dossier `ressources/views` selon la charte graphique officielle.

## 📊 État Actuel

### Audit Complet (Octobre 2024)
- **72 fichiers PHP** dans `ressources/views/`
- **252 styles inline** à nettoyer (attributs `style="..."`)
- **32 fichiers** avec anciennes couleurs hexadécimales
- **54 fichiers** avec classes CSS dépréciées  
- **64 fichiers** nécessitant des améliorations responsive
- **62 fichiers** avec ombres non standardisées

### Fichiers Harmonisés
✅ **piste_audit_content.php** - 38/42 inline styles supprimés (90%)

### Fichiers Prioritaires (à traiter en priorité)
1. `gestion_candidatures_soutenance_content.php` (19 styles inline + 2 couleurs)
2. `redaction_compte_rendu_content.php` (9 styles + 2 couleurs)
3. `gestion_utilisateurs_content.php` (1 style + 36 ombres)
4. `plannificaiton_soutenance_content.php` (8 styles inline)
5. `gestion_rapports_content.php` (1 style + 4 couleurs + 7 ombres)

## 🎨 Charte Graphique Officielle

### Palette de Couleurs

#### Couleurs Principales (Bleu)
```css
--primary: #1a5276        /* bg-primary, text-primary */
--primary-light: #2980b9  /* bg-primary-light, text-primary-light */
--primary-lighter: #3498db /* bg-primary-lighter, text-primary-lighter */
```

#### Couleurs d'Accent (Vert)
```css
--accent: #4caf50  /* bg-accent, text-accent */
--success: #4caf50 /* bg-success, text-success */
```

#### Couleurs d'Alerte (Uniquement pour alertes)
```css
--secondary: #ff8c00 /* bg-secondary, text-secondary - Avertissements */
--danger: #e74c3c   /* bg-danger, text-danger - Erreurs */
--warning: #f39c12  /* bg-warning, text-warning - Attention */
```

### Typographie
- **Police Principale**: Poppins (class `font-poppins`)
- **Police Secondaire**: Montserrat (class `font-montserrat`) - pour les titres

### Classes Standard à Utiliser

#### Remplacements Communs
| Style Inline | Classe Tailwind |
|--------------|-----------------|
| `style="display:none"` | `class="hidden"` |
| `style="display:flex"` | `class="flex"` |
| `style="display:block"` | `class="block"` |
| `style="color:#1a5276"` | `class="text-primary"` |
| `style="background:#1a5276"` | `class="bg-primary"` |
| `style="font-weight:bold"` | `class="font-bold"` |
| `style="font-size:18px"` | `class="text-lg"` |
| `style="text-align:center"` | `class="text-center"` |
| `style="margin:0"` | `class="m-0"` |
| `style="padding:16px"` | `class="p-4"` |

## 🔧 Procédure d'Harmonisation

### Étape 1: Préparation
```bash
# Créer une branche de travail
git checkout -b harmonisation-views

# Compiler le CSS
npx tailwindcss -i ./src/input.css -o ./public/css/output.css
```

### Étape 2: Audit du Fichier
Pour chaque fichier à harmoniser:

1. **Identifier les styles inline**
   ```bash
   grep -n 'style="' fichier.php
   ```

2. **Identifier les anciennes couleurs**
   ```bash
   grep -n '#0F4C75\|#3282B8\|#BBE1FA' fichier.php
   ```

3. **Identifier les classes dépréciées**
   ```bash
   grep -n 'bg-blue-500\|bg-green-500\|bg-red-500\|font-sans' fichier.php
   ```

### Étape 3: Harmonisation

#### A. Supprimer les Styles Inline

**Avant:**
```html
<div style="display:flex; justify-content:space-between; padding:16px;">
    <h2 style="color:#1a5276; font-size:18px; font-weight:bold;">Titre</h2>
</div>
```

**Après:**
```html
<div class="flex justify-between p-4">
    <h2 class="text-primary text-lg font-bold">Titre</h2>
</div>
```

#### B. Remplacer les Anciennes Couleurs

**Avant:**
```php
<div style="background:#0F4C75; color:#fff;">
```

**Après:**
```php
<div class="bg-primary text-white">
```

#### C. Mettre à Jour les Classes Dépréciées

**Avant:**
```html
<button class="bg-blue-500 text-white">
```

**Après:**
```html
<button class="bg-primary text-white">
```

#### D. Uniformiser les Composants

**Boutons:**
```html
<!-- Bouton primaire -->
<button class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-light transition-all duration-300 font-semibold">
    Action
</button>

<!-- Bouton secondaire -->
<button class="bg-white text-primary border-2 border-primary px-6 py-3 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 font-semibold">
    Action
</button>
```

**Cartes:**
```html
<div class="card p-6">
    <h3 class="text-xl font-semibold text-primary mb-4">Titre</h3>
    <p class="text-gray-600">Contenu</p>
</div>
```

**Badges:**
```html
<!-- Succès -->
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/10 text-accent">
    <i class="fas fa-check-circle mr-2"></i>
    Validé
</span>

<!-- Avertissement -->
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-secondary/10 text-secondary">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    En attente
</span>

<!-- Erreur -->
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-danger/10 text-danger">
    <i class="fas fa-times-circle mr-2"></i>
    Rejeté
</span>
```

### Étape 4: Validation

1. **Vérifier la syntaxe PHP**
   ```bash
   php -l ressources/views/fichier.php
   ```

2. **Tester visuellement**
   - Ouvrir la page dans le navigateur
   - Vérifier sur mobile/tablette/desktop
   - Vérifier les interactions (hover, focus, etc.)

3. **Vérifier les couleurs**
   - Toutes les couleurs doivent correspondre à la palette officielle
   - Pas de couleurs hard-codées en hexadécimal (sauf dans les blocs `<style>`)

4. **Commiter les changements**
   ```bash
   git add ressources/views/fichier.php
   git commit -m "Harmonize fichier.php: Remove inline styles, apply design system"
   ```

### Étape 5: Documentation
Pour chaque fichier harmonisé, documenter:
- Nombre de styles inline supprimés
- Changements majeurs apportés
- Éventuels problèmes rencontrés

## 📝 Checklist par Fichier

Pour chaque fichier, vérifier:

- [ ] Tous les styles inline (`style="..."`) sont supprimés
- [ ] Toutes les couleurs utilisent la palette officielle
- [ ] La police Poppins est utilisée (`font-poppins`)
- [ ] Les boutons utilisent les classes standard
- [ ] Les cartes utilisent la classe `card`
- [ ] Les badges utilisent les classes standard
- [ ] Le responsive design est correct (md:, lg:, etc.)
- [ ] Les transitions et animations sont appliquées
- [ ] Pas de code CSS redondant
- [ ] La syntaxe PHP est valide
- [ ] Le fichier a été testé visuellement

## 🚀 Scripts d'Aide

### Script d'Audit Rapide
```bash
# Compter les styles inline dans un fichier
grep -c 'style="' fichier.php

# Lister toutes les couleurs hexadécimales
grep -o '#[0-9A-Fa-f]\{6\}' fichier.php | sort | uniq

# Trouver les classes non-Tailwind
grep -o 'class="[^"]*"' fichier.php | grep -v 'bg-\|text-\|p-\|m-\|flex\|grid'
```

### Script de Validation
```bash
# Vérifier tous les fichiers PHP
find ressources/views/ -name "*.php" -exec php -l {} \; | grep -v "No syntax errors"
```

## 📚 Ressources

- **STYLE_GUIDE.md** - Guide complet de la charte graphique
- **COMPONENTS.md** - Bibliothèque de composants réutilisables
- **input.css** - Classes CSS personnalisées
- **tailwind.config.js** - Configuration Tailwind avec la palette officielle

## 🎯 Objectifs Finaux

Une fois l'harmonisation terminée:
- ✅ **0 style inline** dans les fichiers de vues
- ✅ **Palette officielle** utilisée partout
- ✅ **Composants standardisés** et documentés
- ✅ **Responsive design** cohérent
- ✅ **Code maintenable** et bien organisé

## ⚠️ À Éviter

1. **Ne pas** créer de nouveaux styles inline
2. **Ne pas** utiliser de couleurs non définies dans la palette
3. **Ne pas** supprimer les blocs `<style>` contenant des classes réutilisables
4. **Ne pas** modifier la logique PHP (uniquement les aspects visuels)
5. **Ne pas** casser le responsive existant

## 🤝 Contribution

Pour contribuer à l'harmonisation:
1. Choisir un fichier de la liste prioritaire
2. Suivre la procédure décrite
3. Tester soigneusement
4. Créer un commit clair
5. Mettre à jour ce document

---

**Dernière mise à jour**: Octobre 2024  
**Responsable**: Équipe Check Master UFHB

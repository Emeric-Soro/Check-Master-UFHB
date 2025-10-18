# Guide de la Charte Graphique - Check Master UFHB

## Vue d'ensemble

Ce document définit la charte graphique officielle de la plateforme Check Master UFHB. Tous les contributeurs doivent suivre ces directives pour assurer la cohérence visuelle de l'application.

---

## 🎨 Palette de Couleurs Officielle

### Couleurs Primaires (Bleu)
Utilisées pour les éléments principaux, navigation, boutons importants

- **Primary** (#1a5276) - `bg-primary`, `text-primary`
  - Utilisation : Sidebar, titres principaux, boutons primaires
- **Primary Light** (#2980b9) - `bg-primary-light`, `text-primary-light`
  - Utilisation : Hover states, liens, éléments secondaires
- **Primary Lighter** (#3498db) - `bg-primary-lighter`, `text-primary-lighter`
  - Utilisation : Backgrounds légers, accents

**Nuances disponibles** : 50, 100, 200, 300, 400, 500, 600, 700, 800, 900

### Couleur d'Accent (Vert)
Utilisée pour les succès, validations, statuts positifs

- **Accent/Success** (#4caf50) - `bg-accent`, `text-accent`, `bg-success`, `text-success`
  - Utilisation : Messages de succès, badges validés, icônes de confirmation

**Nuances disponibles** : 50, 100, 200, 300, 400, 500, 600, 700, 800, 900

### Couleurs d'Alerte (Orange et Rouge)
**⚠️ À utiliser UNIQUEMENT pour les alertes et notifications**

- **Secondary** (#ff8c00) - `bg-secondary`, `text-secondary`
  - Utilisation : Alertes d'avertissement, notifications importantes
- **Danger** (#e74c3c) - `bg-danger`, `text-danger`
  - Utilisation : Erreurs, suppressions, actions destructrices

**Nuances disponibles** : 50, 100, 200, 300, 400, 500, 600, 700, 800, 900

### Couleurs Neutres (Gris)
Utilisez les nuances Tailwind standard pour les textes et backgrounds

- `text-gray-900` - Texte principal
- `text-gray-600` - Texte secondaire
- `bg-gray-50`, `bg-gray-100` - Backgrounds légers
- `border-gray-200`, `border-gray-300` - Bordures

---

## 🔤 Typographie

### Polices Officielles

**Police Principale : Poppins**
```html
<!-- Import -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">

<!-- Utilisation -->
<body class="font-poppins">
```

**Police Secondaire : Montserrat**
```html
<!-- Import -->
<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- Utilisation pour les titres -->
<h1 class="font-montserrat font-bold">Titre Principal</h1>
```

### Hiérarchie des Tailles

- **Titres principaux** : `text-4xl md:text-5xl` ou `text-3xl md:text-4xl`
- **Titres secondaires** : `text-2xl md:text-3xl`
- **Sous-titres** : `text-xl md:text-2xl`
- **Texte normal** : `text-base` (par défaut)
- **Texte petit** : `text-sm`
- **Texte très petit** : `text-xs`

### Poids de Police

- **Light** : `font-light` (300)
- **Regular** : `font-normal` (400)
- **Medium** : `font-medium` (500)
- **Semibold** : `font-semibold` (600)
- **Bold** : `font-bold` (700)

---

## 🎭 Gradients Officiels

### Classes de Gradient Disponibles

```css
/* Gradient bleu principal */
.gradient-purple, .gradient-bg, .academic-gradient
background: linear-gradient(135deg, #1a5276 0%, #2980b9 100%)

/* Gradient bleu clair */
.gradient-blue
background: linear-gradient(135deg, #2980b9 0%, #3498db 100%)

/* Gradient vert */
.gradient-green, .gradient-red
background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%)

/* Gradient orange (alertes) */
.gradient-orange
background: linear-gradient(135deg, #f39c12 0%, #ff8c00 100%)
```

### Utilisation des Gradients

```html
<!-- Pour une carte avec gradient bleu -->
<div class="gradient-purple text-white p-6 rounded-xl">
    <h3>Titre</h3>
</div>

<!-- Pour un bouton avec gradient -->
<button class="card-btn">
    Action
</button>
```

---

## 📦 Composants Réutilisables

### Cartes (Cards)

```html
<!-- Carte standard -->
<div class="card p-6">
    <h3 class="text-xl font-semibold text-primary mb-4">Titre</h3>
    <p class="text-gray-600">Contenu de la carte</p>
</div>

<!-- Carte avec hover effect -->
<div class="card card-hover p-6">
    Contenu avec effet au survol
</div>

<!-- Carte avec gradient et icône -->
<div class="gradient-purple rounded-xl p-4 text-white shadow-lg card-hover">
    <div class="flex justify-between items-center">
        <div class="bg-white bg-opacity-20 w-12 h-12 rounded-xl flex items-center justify-center">
            <i class="fas fa-icon text-white text-xl"></i>
        </div>
        <div class="text-right">
            <h3 class="text-3xl font-bold">Valeur</h3>
            <p class="text-sm opacity-90">Description</p>
        </div>
    </div>
</div>
```

### Boutons

```html
<!-- Bouton primaire -->
<button class="bg-primary text-white px-6 py-3 rounded-lg hover:bg-primary-light transition-all duration-300 font-semibold">
    Action Primaire
</button>

<!-- Bouton avec gradient -->
<a href="#" class="card-btn">
    <span>Action</span>
    <i class="fas fa-arrow-right ml-2"></i>
</a>

<!-- Bouton secondaire -->
<button class="bg-white text-primary border-2 border-primary px-6 py-3 rounded-lg hover:bg-primary hover:text-white transition-all duration-300 font-semibold">
    Action Secondaire
</button>

<!-- Bouton danger -->
<button class="bg-danger text-white px-6 py-3 rounded-lg hover:bg-red-600 transition-all duration-300 font-semibold">
    Action Destructrice
</button>

<!-- Bouton succès -->
<button class="bg-accent text-white px-6 py-3 rounded-lg hover:bg-green-600 transition-all duration-300 font-semibold">
    Valider
</button>
```

### Badges et Statuts

```html
<!-- Badge succès -->
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/10 text-accent">
    <i class="fas fa-check-circle mr-2"></i>
    Validé
</span>

<!-- Badge warning -->
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-secondary/10 text-secondary">
    <i class="fas fa-exclamation-triangle mr-2"></i>
    En attente
</span>

<!-- Badge danger -->
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-danger/10 text-danger">
    <i class="fas fa-times-circle mr-2"></i>
    Rejeté
</span>
```

### Modales

```html
<!-- Structure de modale -->
<div class="modal-backdrop"></div>
<div class="modal-content">
    <!-- Icône -->
    <div class="modal-icon modal-icon-success">
        <i class="fas fa-check text-2xl"></i>
    </div>
    
    <!-- Titre et texte -->
    <h3 class="text-xl font-semibold text-center text-gray-900 mb-3">
        Titre de la modale
    </h3>
    <p class="text-sm text-center text-gray-600 mb-6">
        Message de la modale
    </p>
    
    <!-- Boutons -->
    <div class="flex justify-center gap-4">
        <button class="modal-button modal-button-primary">
            Confirmer
        </button>
        <button class="modal-button modal-button-secondary">
            Annuler
        </button>
    </div>
</div>
```

### Formulaires

```html
<!-- Input standard -->
<div class="space-y-2">
    <label for="input" class="text-sm font-semibold text-gray-800">Label</label>
    <input 
        type="text" 
        id="input" 
        class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition"
        placeholder="Placeholder"
    >
</div>

<!-- Input avec icône -->
<div class="space-y-2">
    <label for="email" class="text-sm font-semibold text-gray-800">Email</label>
    <div class="relative">
        <input 
            type="email" 
            id="email" 
            class="w-full rounded-lg border border-gray-200 pl-4 pr-12 py-3 text-sm font-medium text-gray-900 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition"
            placeholder="email@exemple.com"
        >
        <div class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-primary">
            <i class="fas fa-envelope"></i>
        </div>
    </div>
</div>

<!-- Select -->
<select class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm font-medium text-gray-900 focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition">
    <option>Option 1</option>
    <option>Option 2</option>
</select>
```

### Tableaux

```html
<div class="overflow-x-auto">
    <table class="min-w-full bg-white rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
            <tr>
                <th class="px-6 py-4 text-left text-sm font-semibold text-primary">
                    Colonne 1
                </th>
                <th class="px-6 py-4 text-left text-sm font-semibold text-primary">
                    Colonne 2
                </th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
            <tr class="table-row-hover">
                <td class="px-6 py-4 text-sm text-gray-900">Donnée 1</td>
                <td class="px-6 py-4 text-sm text-gray-900">Donnée 2</td>
            </tr>
        </tbody>
    </table>
</div>
```

---

## ✨ Effets et Transitions

### Effets de Survol (Hover)

```html
<!-- Hover scale -->
<div class="hover-scale transition-all duration-300">
    Contenu avec zoom léger
</div>

<!-- Hover translate Y -->
<div class="card-hover">
    Contenu qui remonte au survol
</div>

<!-- Hover avec transformation de bouton -->
<button class="btn-primary">
    Bouton avec effet
</button>
```

### Animations

```html
<!-- Animation de fade in -->
<div class="fade-in">
    Contenu qui apparaît en fondu
</div>

<!-- Animation flottante -->
<div class="floating">
    Élément flottant
</div>

<!-- Animation pulse -->
<div class="pulse">
    Élément qui pulse
</div>
```

### Ombres (Shadows)

- `shadow-sm` - Ombre légère
- `shadow` - Ombre standard (défaut Tailwind)
- `shadow-md` - Ombre moyenne
- `shadow-lg` - Ombre large
- `shadow-xl` - Ombre extra-large
- `shadow-card` - Ombre pour cartes (personnalisée)
- `shadow-elevate` - Ombre d'élévation (personnalisée)

---

## 📐 Espacements et Marges

### Padding Standard

- Petits éléments : `p-4` (1rem)
- Éléments moyens : `p-6` (1.5rem)
- Grands éléments : `p-8` (2rem)
- Sections : `py-20` (5rem vertical)

### Marges Entre Éléments

- Espacement léger : `gap-4` ou `space-y-4`
- Espacement moyen : `gap-6` ou `space-y-6`
- Espacement large : `gap-8` ou `space-y-8`

### Bordures Arrondies

- Léger : `rounded-lg` (0.5rem)
- Moyen : `rounded-xl` (0.75rem)
- Large : `rounded-2xl` (1rem)
- Complet : `rounded-full` (cercle parfait)

---

## 📱 Responsive Design

### Breakpoints Tailwind

- `sm:` - 640px et plus (mobile landscape, tablette portrait)
- `md:` - 768px et plus (tablette)
- `lg:` - 1024px et plus (laptop)
- `xl:` - 1280px et plus (desktop)
- `2xl:` - 1536px et plus (large desktop)

### Bonnes Pratiques Responsive

```html
<!-- Grid responsive -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Contenu -->
</div>

<!-- Texte responsive -->
<h1 class="text-3xl md:text-4xl lg:text-5xl font-bold">
    Titre adaptatif
</h1>

<!-- Padding responsive -->
<div class="px-4 sm:px-6 lg:px-8 py-12 lg:py-20">
    <!-- Contenu -->
</div>

<!-- Affichage conditionnel -->
<div class="hidden md:block">
    Visible uniquement sur desktop
</div>

<div class="block md:hidden">
    Visible uniquement sur mobile
</div>
```

---

## 🎯 Configuration Tailwind Inline

Pour les pages nécessitant une configuration Tailwind inline (sans build), utilisez :

```html
<script src="https://cdn.tailwindcss.com"></script>
<script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: '#1a5276',
                    'primary-light': '#2980b9',
                    'primary-lighter': '#3498db',
                    secondary: '#ff8c00',
                    accent: '#4caf50',
                    success: '#4caf50',
                    warning: '#f39c12',
                    danger: '#e74c3c'
                },
                fontFamily: {
                    poppins: ['Poppins', 'sans-serif'],
                    montserrat: ['Montserrat', 'sans-serif']
                }
            }
        }
    }
</script>
```

---

## ✅ Checklist pour Nouvelle Page

Avant de soumettre une nouvelle page ou composant, vérifiez :

- [ ] Les polices Poppins et Montserrat sont importées
- [ ] Les couleurs utilisées respectent la palette officielle
- [ ] Les boutons primaires utilisent `bg-primary` ou la classe `card-btn`
- [ ] Les alertes utilisent uniquement orange/rouge
- [ ] Les transitions et hover effects sont appliqués
- [ ] Le design est responsive (testé sur mobile, tablette, desktop)
- [ ] Les ombres utilisent les classes shadow officielles
- [ ] Les espacements suivent les standards définis
- [ ] Les icônes utilisent Font Awesome 6.x
- [ ] Le code HTML est propre et bien indenté

---

## 🚫 À Éviter

1. **Ne pas** utiliser d'autres couleurs bleues que celles définies
2. **Ne pas** utiliser orange/rouge en dehors des alertes
3. **Ne pas** créer de nouvelles classes CSS sans documentation
4. **Ne pas** utiliser de styles inline quand des classes Tailwind existent
5. **Ne pas** utiliser de polices différentes
6. **Ne pas** créer des composants sans vérifier s'ils existent déjà

---

## 📞 Support

Pour toute question sur la charte graphique :
- Consultez ce guide
- Vérifiez les exemples dans `public/index.php` et `public/layout.php`
- Consultez le fichier `src/input.css` pour les classes personnalisées

---

**Version du guide** : 1.0  
**Dernière mise à jour** : Octobre 2024  
**Mainteneur** : Équipe Check Master UFHB

# Guide de Contribution - Charte Graphique

Guide rapide pour contribuer au respect de la charte graphique Check Master UFHB.

## 🚀 Démarrage Rapide

### 1. Imports Obligatoires

Toute nouvelle page doit inclure :

```html
<!-- Polices -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">

<!-- CSS compilé -->
<link rel="stylesheet" href="css/output.css">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
```

### 2. Configuration Tailwind Inline (si nécessaire)

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

### 3. Structure de Base

```html
<body class="bg-gray-50 font-poppins">
    <div class="container mx-auto px-4 py-8">
        <!-- Votre contenu ici -->
    </div>
</body>
```

## 🎨 Palette de Couleurs Rapide

| Utilisation | Couleur | Classe Tailwind |
|-------------|---------|-----------------|
| Bleu principal | #1a5276 | `bg-primary`, `text-primary` |
| Bleu clair | #2980b9 | `bg-primary-light`, `text-primary-light` |
| Vert accent | #4caf50 | `bg-accent`, `text-accent` |
| Orange alerte | #ff8c00 | `bg-secondary`, `text-secondary` |
| Rouge erreur | #e74c3c | `bg-danger`, `text-danger` |

## 📝 Templates de Base

### Carte Simple

```php
<div class="card p-6">
    <h3 class="text-xl font-semibold text-primary mb-4">Titre</h3>
    <p class="text-gray-600">Contenu</p>
</div>
```

### Bouton Principal

```php
<button class="bg-primary text-white px-6 py-3 rounded-lg font-semibold hover:bg-primary-light transition-all duration-300">
    Action
</button>
```

### Input de Formulaire

```php
<div class="space-y-2">
    <label for="champ" class="text-sm font-semibold text-gray-800">Label</label>
    <input type="text" id="champ" 
           class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm focus:border-primary focus:outline-none focus:ring-4 focus:ring-primary/10 transition"
           placeholder="Placeholder">
</div>
```

### Badge Statut

```php
<span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-accent/10 text-accent">
    <i class="fas fa-check-circle mr-2"></i>
    Validé
</span>
```

## ⚠️ À Ne Pas Faire

❌ Utiliser des couleurs non définies dans la palette  
❌ Créer des styles inline sans documentation  
❌ Utiliser d'autres polices que Poppins/Montserrat  
❌ Utiliser orange/rouge en dehors des alertes  
❌ Créer des gradients personnalisés  

## ✅ Checklist Avant Commit

- [ ] Polices Poppins/Montserrat importées
- [ ] Couleurs officielles utilisées uniquement
- [ ] Pas de styles inline non documentés
- [ ] Classes Tailwind utilisées au maximum
- [ ] Composants testés en responsive
- [ ] Code PHP échappé avec `htmlspecialchars()`
- [ ] Icônes Font Awesome 6.x
- [ ] Build CSS généré : `npm run tailwind:dev`

## 🔧 Commandes Utiles

```bash
# Compiler le CSS en mode dev (watch)
npm run tailwind:dev

# Compiler le CSS une fois
npx tailwindcss -i ./src/input.css -o ./public/css/output.css

# Vérifier la syntaxe PHP
php -l fichier.php
```

## 📚 Documentation Complète

- [STYLE_GUIDE.md](STYLE_GUIDE.md) - Guide complet de la charte graphique
- [COMPONENTS.md](COMPONENTS.md) - Composants réutilisables avec exemples
- [src/input.css](src/input.css) - Classes CSS personnalisées

## 💡 Exemples de Pages

Consultez ces pages comme référence :

- `public/index.php` - Page d'accueil avec design moderne
- `public/layout.php` - Layout principal de l'application
- `public/page_connexion.php` - Page de connexion élégante
- `ressources/views/parametres_generaux_content.php` - Grille de cartes

## 🆘 Besoin d'Aide ?

1. Consultez le [STYLE_GUIDE.md](STYLE_GUIDE.md)
2. Regardez les exemples dans [COMPONENTS.md](COMPONENTS.md)
3. Examinez les pages existantes bien formatées
4. Contactez l'équipe pour toute question

---

**Rappel** : La cohérence visuelle est essentielle pour l'expérience utilisateur. Prenez le temps de suivre ces guidelines !

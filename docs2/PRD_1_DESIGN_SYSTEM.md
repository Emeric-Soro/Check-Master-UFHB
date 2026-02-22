# PRD 1 — Design System & CSS Variables
**CheckMaster UI Refonte — Document de Référence Absolu**

---

## 1. Contexte et Objectif

### 1.1 Problème actuel

Chaque vue existante dans `ressources/views/` redéfinit ses propres variables CSS sous des noms différents :
- `dashboard_scolarite_content.php` : `--blue`, `--blue-light`, `--light-bg`, `--dark-text`
- `parametres_generaux_content.php` : `--ufhb-blue`, `--ufhb-blue-light`, `--ufhb-green`
- `gestion_utilisateurs_content.php` : variables inline sans nommage cohérent
- Certains fichiers re-implémentent manuellement Tailwind dans des balises `<style>`

**Résultat** : aucune cohérence visuelle, maintenance impossible, moindre changement de couleur requiert de modifier 40+ fichiers.

### 1.2 Solution

Un fichier CSS central — `public/assets/css/checkmaster-theme.css` — est **déjà le système de design officiel**. Il contient 1627 lignes et couvre tous les tokens. Ce PRD formalise les règles d'utilisation de ce fichier comme **source unique de vérité** et étend les tokens manquants.

### 1.3 Règles absolues

- ❌ **Aucune variable CSS définie hors de `checkmaster-theme.css`**
- ❌ **Aucune couleur hexadécimale hardcodée dans une vue ou un composant**
- ❌ **Aucun usage de Tailwind CSS ou de ses utilitaires** (ni CDN, ni implémentation manuelle)
- ❌ **Aucun `<style>` inline dans les vues ou composants** (sauf `:root` global dans `checkmaster-theme.css`)
- ✅ Toutes les couleurs, espacements, typographies, ombres, rayons passent par des variables `--cm-*`
- ✅ Les classes utilitaires nécessaires sont définies dans `components.css` ou dans un nouveau `utilities.css`

---

## 2. Architecture des Fichiers CSS

```
public/assets/css/
├── checkmaster-theme.css     ← TOKENS (variables :root) — NE JAMAIS MODIFIER SANS PRD
├── components.css            ← Classes composants (.cm-table, .cm-badge, etc.)
├── responsive.css            ← Media queries uniquement (pas de nouvelles variables)
└── utilities.css             ← À CRÉER : classes utilitaires de layout (.cm-flex, .cm-grid, etc.)
```

**Règle de cascade** : `checkmaster-theme.css` → `components.css` → `utilities.css` → `responsive.css`

Les 4 fichiers sont chargés dans l'ordre ci-dessus dans le layout principal (`app.php` / `app-shell.php`).

---

## 3. Tokens de Couleur

### 3.1 Palette Principale (Brand)

| Token | Valeur | Usage |
|---|---|---|
| `--cm-primary` | `#1a5276` | Sidebar background, titres principaux |
| `--cm-primary-dark` | `#154360` | Sidebar hover sombre, états actifs |
| `--cm-primary-light` | `#3498db` | Fond global app, liens, accents |
| `--cm-primary-lighter` | `#5dade2` | Éléments interactifs secondaires |
| `--cm-primary-lightest` | `#aed6f1` | Fonds de survol légers, badges info |

### 3.2 Boutons d'Action

| Token | Valeur | Usage |
|---|---|---|
| `--cm-btn-primary` | `#2980b9` | Bouton principal (Enregistrer, Valider) |
| `--cm-btn-primary-hover` | `#2471a3` | Hover bouton principal |
| `--cm-btn-success` | `#27ae60` | Bouton succès (Confirmer, Approuver) |
| `--cm-btn-success-hover` | `#229954` | Hover bouton succès |
| `--cm-btn-info` | `#3498db` | Bouton info (Voir, Détails) |
| `--cm-btn-info-hover` | `#2e86c1` | Hover bouton info |
| `--cm-btn-light` | `#ecf0f1` | Bouton neutre (Annuler, Réinitialiser) |
| `--cm-btn-light-hover` | `#d5dbdb` | Hover bouton neutre |
| `--cm-btn-warning` | `#f39c12` | **RÉSERVÉ aux badges/statuts uniquement** |
| `--cm-btn-danger` | `#e74c3c` | **RÉSERVÉ aux badges/statuts uniquement** |

> ⚠️ **Règle absolue** : Les couleurs orange/rouge ne sont JAMAIS utilisées pour des boutons d'action. Elles sont exclusivement réservées aux notifications, badges de statut et messages d'alerte.

### 3.3 Feedback / Statuts

| Token | Valeur | Usage |
|---|---|---|
| `--cm-feedback-success` | `#27ae60` | Badge succès, toast succès, statut validé |
| `--cm-feedback-warning` | `#f39c12` | Badge avertissement, toast warning, statut en attente |
| `--cm-feedback-danger` | `#e74c3c` | Badge erreur, toast erreur, statut rejeté |
| `--cm-feedback-info` | `#3498db` | Badge info, toast info, statut en cours |

### 3.4 Tokens Manquants à Ajouter dans `checkmaster-theme.css`

Les tokens suivants **n'existent pas encore** et doivent être ajoutés à la section `:root` :

```css
/* Hub / Tuiles */
--cm-hub-tile-bg: #ffffff;
--cm-hub-tile-border: #e0e4e8;
--cm-hub-tile-hover-border: var(--cm-primary-light);
--cm-hub-tile-hover-shadow: 0 4px 12px rgba(52, 152, 219, 0.15);
--cm-hub-tile-icon-bg: var(--cm-primary-lightest);
--cm-hub-tile-icon-color: var(--cm-primary);

/* Tableau */
--cm-table-header-bg: #f7f9fb;
--cm-table-header-color: var(--cm-primary);
--cm-table-row-hover-bg: #f0f4f8;
--cm-table-border-color: #e8ecf0;
--cm-table-stripe-bg: #fafbfc;

/* Formulaires */
--cm-input-bg: #ffffff;
--cm-input-border: #ced4da;
--cm-input-focus-border: var(--cm-primary-light);
--cm-input-focus-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
--cm-input-placeholder-color: #adb5bd;
--cm-input-disabled-bg: #e9ecef;
--cm-label-color: var(--cm-primary-dark);
--cm-label-required-color: var(--cm-feedback-danger);

/* Cartes / Panneaux */
--cm-card-bg: #ffffff;
--cm-card-border: #e0e4e8;
--cm-card-shadow: 0 2px 8px rgba(0,0,0,0.06);
--cm-card-radius: var(--cm-border-radius-lg);

/* Sidebar Navigation */
--cm-sidebar-link-color: rgba(255,255,255,0.85);
--cm-sidebar-link-hover-color: #ffffff;
--cm-sidebar-submenu-bg: rgba(0,0,0,0.15);
--cm-sidebar-badge-bg: var(--cm-feedback-danger);

/* Timeline */
--cm-timeline-line-color: #e0e4e8;
--cm-timeline-dot-bg: var(--cm-primary-light);
--cm-timeline-dot-border: #ffffff;
--cm-timeline-dot-done-bg: var(--cm-feedback-success);
--cm-timeline-dot-current-bg: var(--cm-primary);
--cm-timeline-dot-pending-bg: #ced4da;

/* Steps */
--cm-step-active-bg: var(--cm-primary);
--cm-step-done-bg: var(--cm-feedback-success);
--cm-step-pending-bg: #ced4da;
--cm-step-connector-color: #e0e4e8;

/* Login */
--cm-login-bg: linear-gradient(135deg, var(--cm-primary) 0%, var(--cm-primary-light) 100%);
--cm-login-card-bg: #ffffff;
--cm-login-card-shadow: 0 8px 32px rgba(26, 82, 118, 0.25);

/* Skeleton Loading */
--cm-skeleton-base: #e9ecef;
--cm-skeleton-highlight: #f8f9fa;
```

---

## 4. Tokens de Typographie

| Token | Valeur | Usage |
|---|---|---|
| `--cm-font-family` | `-apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, sans-serif` | Toute la typographie |
| `--cm-font-size-base` | `0.95rem` | Corps de texte, cellules de tableau |
| `--cm-font-size-sm` | `0.85rem` | Labels de formulaire, texte secondaire |
| `--cm-font-size-xs` | `0.75rem` | Badges, légendes, aide contextuelle |
| `--cm-font-size-lg` | `1.1rem` | **À AJOUTER** — Titres de section, headers de pole |
| `--cm-font-size-xl` | `1.25rem` | **À AJOUTER** — Titres de page |
| `--cm-font-size-2xl` | `1.5rem` | **À AJOUTER** — Valeurs de stat cards |
| `--cm-font-weight-normal` | `400` | **À AJOUTER** — Corps de texte |
| `--cm-font-weight-medium` | `500` | **À AJOUTER** — Labels importants |
| `--cm-font-weight-semibold` | `600` | **À AJOUTER** — Titres de section |
| `--cm-font-weight-bold` | `700` | **À AJOUTER** — Titres de page, valeurs clés |
| `--cm-line-height-tight` | `1.25` | **À AJOUTER** — Titres |
| `--cm-line-height-normal` | `1.5` | **À AJOUTER** — Corps de texte |
| `--cm-line-height-relaxed` | `1.75` | **À AJOUTER** — Texte long |

---

## 5. Tokens d'Espacement

| Token | Valeur | Usage |
|---|---|---|
| `--cm-spacing-xs` | `0.25rem` (4px) | Écarts minimum, gaps entre badges |
| `--cm-spacing-sm` | `0.5rem` (8px) | Padding interne petits éléments |
| `--cm-spacing-md` | `1rem` (16px) | Spacing standard, gap de grille |
| `--cm-spacing-lg` | `1.5rem` (24px) | Padding de cards, espacement de section |
| `--cm-spacing-xl` | `2rem` (32px) | Espacement entre sections majeures |
| `--cm-spacing-2xl` | `3rem` (48px) | **À AJOUTER** — Espacement page entière |

---

## 6. Tokens de Layout

| Token | Valeur | Usage |
|---|---|---|
| `--cm-sidebar-width` | `260px` | Largeur sidebar desktop |
| `--cm-sidebar-width-tablet` | `220px` | **À AJOUTER** — Largeur sidebar tablette |
| `--cm-header-height` | `56px` | Hauteur header sticky |
| `--cm-panel-width` | `400px` | Largeur side panel (si utilisé) |
| `--cm-content-max-width` | `1400px` | **À AJOUTER** — Max-width du contenu |

---

## 7. Tokens d'Effets

| Token | Valeur | Usage |
|---|---|---|
| `--cm-border-radius` | `4px` | Radius standard (inputs, badges) |
| `--cm-border-radius-lg` | `8px` | Radius large (cards, panneaux) |
| `--cm-border-radius-xl` | `12px` | **À AJOUTER** — Radius extra-large (hub tiles) |
| `--cm-border-radius-full` | `9999px` | **À AJOUTER** — Radius pilule (badges ronds) |
| `--cm-border-color` | `#e0e4e8` | Bordure standard |
| `--cm-transition-fast` | `150ms ease` | Hover immédiat |
| `--cm-transition-normal` | `250ms ease` | Transitions standard |
| `--cm-transition-slow` | `350ms ease` | Animations d'entrée/sortie |
| `--cm-shadow-sm` | `0 1px 3px rgba(0,0,0,0.08)` | **À AJOUTER** — Ombre légère |
| `--cm-shadow-md` | `0 2px 8px rgba(0,0,0,0.10)` | **À AJOUTER** — Ombre moyenne |
| `--cm-shadow-lg` | `0 4px 16px rgba(0,0,0,0.12)` | **À AJOUTER** — Ombre forte |

---

## 8. Z-Index Stack

| Token | Valeur | Usage |
|---|---|---|
| `--cm-z-sidebar` | `100` | Sidebar |
| `--cm-z-header` | `90` | Header sticky |
| `--cm-z-panel-overlay` | `200` | Overlay side panel |
| `--cm-z-panel` | `210` | Side panel lui-même |
| `--cm-z-modal-overlay` | `300` | Overlay modal |
| `--cm-z-modal` | `310` | Modal lui-même |
| `--cm-z-toast` | `400` | Toasts (au-dessus de tout) |
| `--cm-z-pole-sup` | `10` | Pole supérieur sticky |

> ❌ **Pas de modals dans les nouvelles vues.** Les tokens `--cm-z-modal-*` sont conservés pour compatibilité legacy uniquement.

---

## 9. Classes Utilitaires à Créer dans `utilities.css`

Ces classes remplacent les utilitaires Tailwind identifiés dans les vues existantes.

### 9.1 Flexbox

```css
.cm-flex         { display: flex; }
.cm-flex-col     { display: flex; flex-direction: column; }
.cm-flex-center  { display: flex; align-items: center; justify-content: center; }
.cm-flex-between { display: flex; align-items: center; justify-content: space-between; }
.cm-flex-start   { display: flex; align-items: center; justify-content: flex-start; }
.cm-flex-end     { display: flex; align-items: center; justify-content: flex-end; }
.cm-flex-wrap    { flex-wrap: wrap; }
.cm-flex-gap-sm  { gap: var(--cm-spacing-sm); }
.cm-flex-gap-md  { gap: var(--cm-spacing-md); }
.cm-flex-gap-lg  { gap: var(--cm-spacing-lg); }
.cm-align-center { align-items: center; }
.cm-flex-1       { flex: 1; }
.cm-flex-shrink-0 { flex-shrink: 0; }
```

### 9.2 Grid

```css
.cm-grid          { display: grid; }
.cm-grid-2        { display: grid; grid-template-columns: repeat(2, 1fr); gap: var(--cm-spacing-md); }
.cm-grid-3        { display: grid; grid-template-columns: repeat(3, 1fr); gap: var(--cm-spacing-md); }
.cm-grid-4        { display: grid; grid-template-columns: repeat(4, 1fr); gap: var(--cm-spacing-md); }
.cm-grid-auto     { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: var(--cm-spacing-md); }
```

### 9.3 Espacement

```css
.cm-p-sm   { padding: var(--cm-spacing-sm); }
.cm-p-md   { padding: var(--cm-spacing-md); }
.cm-p-lg   { padding: var(--cm-spacing-lg); }
.cm-px-md  { padding-left: var(--cm-spacing-md); padding-right: var(--cm-spacing-md); }
.cm-py-md  { padding-top: var(--cm-spacing-md); padding-bottom: var(--cm-spacing-md); }
.cm-m-0    { margin: 0; }
.cm-mb-sm  { margin-bottom: var(--cm-spacing-sm); }
.cm-mb-md  { margin-bottom: var(--cm-spacing-md); }
.cm-mb-lg  { margin-bottom: var(--cm-spacing-lg); }
.cm-mt-md  { margin-top: var(--cm-spacing-md); }
.cm-mt-lg  { margin-top: var(--cm-spacing-lg); }
```

### 9.4 Typographie

```css
.cm-text-sm      { font-size: var(--cm-font-size-sm); }
.cm-text-xs      { font-size: var(--cm-font-size-xs); }
.cm-text-lg      { font-size: var(--cm-font-size-lg); }
.cm-text-xl      { font-size: var(--cm-font-size-xl); }
.cm-text-bold    { font-weight: var(--cm-font-weight-bold); }
.cm-text-semibold { font-weight: var(--cm-font-weight-semibold); }
.cm-text-muted   { color: #6c757d; }
.cm-text-primary { color: var(--cm-primary); }
.cm-text-success { color: var(--cm-feedback-success); }
.cm-text-danger  { color: var(--cm-feedback-danger); }
.cm-text-center  { text-align: center; }
.cm-text-right   { text-align: right; }
.cm-truncate     { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
```

### 9.5 Display / Visibilité

```css
.cm-hidden       { display: none; }
.cm-sr-only      { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
.cm-w-full       { width: 100%; }
.cm-h-full       { height: 100%; }
.cm-overflow-hidden { overflow: hidden; }
.cm-relative     { position: relative; }
.cm-absolute     { position: absolute; }
```

---

## 10. Règles d'Extension du Design System

### 10.1 Ajouter un nouveau token

1. L'ajouter **uniquement dans `checkmaster-theme.css`**, dans la section `:root`, avec le préfixe `--cm-`
2. Respecter la convention de nommage : `--cm-[catégorie]-[propriété]-[modificateur]`
3. Ne **jamais** créer une variable sans l'utiliser immédiatement dans au moins un composant

### 10.2 Convention de nommage des classes

Toutes les classes CSS du projet utilisent le préfixe `.cm-` :
```
.cm-[composant]                   → état par défaut
.cm-[composant]--[modificateur]   → variante (ex: .cm-btn--large)
.cm-[composant].is-[état]         → état JS (ex: .cm-toast.is-success)
.cm-[composant]__[élément]        → sous-élément (usage modéré, préférer classes plates)
```

### 10.3 Interdictions strictes

```css
/* ❌ INTERDIT */
color: #3498db;           /* Valeur hexadécimale hardcodée */
font-size: 14px;          /* Valeur de taille hardcodée */
padding: 16px;            /* Espacement hardcodé */
margin: 0 auto;           /* Exception : le centrage auto est autorisé */

/* ✅ CORRECT */
color: var(--cm-primary-light);
font-size: var(--cm-font-size-sm);
padding: var(--cm-spacing-md);
```

---

## 11. Système de Couleurs de Fond

| Contexte | Token | Valeur |
|---|---|---|
| Fond global de l'application | `--cm-app-bg` | `#f0f4f8` |
| Fond des panneaux/cards | `--cm-content-bg` | `#ffffff` |
| Fond des box internes | `--cm-box-bg` | `#ffffff` |
| Fond pôle supérieur | `--cm-pole-sup-bg` | `#ffffff` |
| Fond barre intermédiaire | `--cm-barre-bg` | `#f7f9fb` |
| Fond pôle inférieur | `--cm-pole-inf-bg` | `#ffffff` |

> Note : `--cm-app-bg` vaut `#f0f4f8` — c'est une teinte légèrement bleutée, cohérente avec `--cm-primary-light: #3498db`.

---

## 12. Responsive — Breakpoints Officiels

Définis dans `responsive.css`, ces breakpoints sont **les seuls autorisés** :

| Breakpoint | Seuil | Contexte |
|---|---|---|
| Mobile small | ≤ 480px | Smartphones portrait |
| Mobile | ≤ 768px | Smartphones + tablettes portrait |
| Tablet | 769–1024px | Tablettes landscape |
| Desktop | > 1024px | Ordinateurs (défaut) |
| Large Desktop | ≥ 1400px | Grands écrans |

**Comportements responsive obligatoires :**
- ≤ 768px : sidebar cachée → remplacée par hamburger + drawer
- ≤ 768px : `.cm-grid-4` → 2 colonnes ; `.cm-grid-3` → 2 colonnes ; `.cm-grid-2` → 1 colonne
- ≤ 480px : tout passage en 1 colonne
- ≤ 768px : `.cm-form-row` (champs côte à côte) → empilement vertical
- ≤ 768px : tableaux → scroll horizontal obligatoire via `overflow-x: auto`

---

## 13. Icônes

**Source unique** : Font Awesome 6 Free (déjà chargé dans `app.php`)
- ❌ Ne jamais charger Font Awesome depuis un CDN dans une vue ou un composant
- ❌ Ne jamais utiliser d'autres librairies d'icônes
- ✅ Utiliser uniquement `<i class="fas fa-[icon]"></i>` ou `<i class="far fa-[icon]"></i>`

**Icônes de référence par action :**

| Action | Classe Font Awesome |
|---|---|
| Créer / Ajouter | `fas fa-plus` |
| Modifier / Éditer | `fas fa-edit` |
| Supprimer | `fas fa-trash` |
| Voir / Détail | `fas fa-eye` |
| Rechercher | `fas fa-search` |
| Filtrer | `fas fa-filter` |
| Exporter Excel | `fas fa-file-excel` |
| Imprimer | `fas fa-print` |
| Valider / Confirmer | `fas fa-check` |
| Rejeter / Annuler | `fas fa-times` |
| Télécharger | `fas fa-download` |
| Envoyer | `fas fa-paper-plane` |
| Paramètres | `fas fa-cog` |
| Tableau de bord | `fas fa-tachometer-alt` |
| Utilisateur | `fas fa-user` |
| Étudiant | `fas fa-user-graduate` |
| Enseignant | `fas fa-chalkboard-teacher` |
| Commission | `fas fa-users` |
| Calendrier | `fas fa-calendar-alt` |
| Fichier / Document | `fas fa-file-alt` |
| Sécurité / Verrou | `fas fa-lock` |
| Déconnexion | `fas fa-sign-out-alt` |

---

## 14. Charte Animation

### 14.1 Animations standard

```css
/* Entrée de page — définie dans checkmaster-theme.css */
@keyframes cm-fade-in {
  from { opacity: 0; transform: translateY(8px); }
  to   { opacity: 1; transform: translateY(0); }
}

/* Toast slide-in */
@keyframes cm-slide-in-right {
  from { transform: translateX(120%); opacity: 0; }
  to   { transform: translateX(0); opacity: 1; }
}

/* Skeleton pulse */
@keyframes cm-skeleton-pulse {
  0%, 100% { background-color: var(--cm-skeleton-base); }
  50%       { background-color: var(--cm-skeleton-highlight); }
}
```

### 14.2 Règles d'usage

- ❌ Pas d'animation `@keyframes` définie dans une vue ou un composant individuel
- ✅ Toutes les animations sont dans `checkmaster-theme.css`
- ✅ Les transitions d'état (hover, focus) utilisent `var(--cm-transition-fast)` ou `var(--cm-transition-normal)`
- ✅ `prefers-reduced-motion` : toutes les animations respectent `@media (prefers-reduced-motion: reduce)`

---

## 15. Thème Sombre (Dark Mode)

Non requis pour la V1 de cette refonte. Les tokens sont nommés de façon sémantique pour permettre une future implémentation via `[data-theme="dark"] :root { ... }`.

---

## 16. Validation du Design System

Un développeur peut valider la conformité au design system via la checklist suivante :

- [ ] Aucun `#` de couleur hexadécimale hardcodée dans les fichiers PHP de `ressources/`
- [ ] Aucun `<style>` tag dans les fichiers de vue ou de composant
- [ ] Aucun import `@import` ou `<link>` CDN dans les composants
- [ ] Aucun nom de classe Tailwind (`text-`, `font-`, `p-`, `m-`, `bg-`, `flex`, `grid`) dans les vues
- [ ] Toutes les classes CSS commencent par `.cm-`
- [ ] Toutes les variables CSS commencent par `--cm-`
- [ ] Font Awesome chargé une seule fois dans `app.php` / `app-shell.php`

---

## 17. Fichiers à Modifier / Créer

| Fichier | Action | Description |
|---|---|---|
| `public/assets/css/checkmaster-theme.css` | MODIFIER | Ajouter les tokens manquants listés en section 3.4, 4, 5, 6, 7 |
| `public/assets/css/utilities.css` | CRÉER | Classes utilitaires listées en section 9 |
| `ressources/components/` | CRÉER | Dossier racine des composants PHP (voir PRD 2) |
| `ressources/views/v2/` | CRÉER | Dossier des nouvelles vues (voir PRD 3–6) |
| `app/utils/` | MODIFIER (additions) | Helpers PHP autorisés (voir PRD 2) |

---

*Fin du PRD 1 — Design System & CSS Variables*
*Prochaine étape : PRD 2 — Bibliothèque de Composants PHP*

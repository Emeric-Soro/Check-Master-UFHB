# 0. LAYOUT GLOBAL — Sidebar + Navbar (Pas de Footer)

> **Règle absolue : AUCUN FOOTER.** Le layout se compose exclusivement de :
> 1. **Navbar** (barre supérieure fixe)
> 2. **Sidebar** (panneau latéral gauche, collapsible)
> 3. **Zone de contenu** (slot central)

---

## 0.1 Écran : LAYOUT_NAVBAR — Barre de navigation supérieure

**ID & TITRE :** `LAYOUT_NAVBAR` — Barre de navigation supérieure

**OBJECTIF :** Fournir une barre de navigation fixe en haut de la page offrant l'identification de l'application, le burger menu (toggle sidebar), le titre de la page courante, et les contrôles utilisateur (notifications, profil, déconnexion).

**VUE PRINCIPALE :** Barre horizontale fixe (sticky top).

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Données affichées :*

| Élément | Source | Position |
|---|---|---|
| Logo CheckMaster + Nom | Image statique `logo.png` + texte "CheckMaster" | Gauche |
| Bouton Burger (☰) | Événement JS `CM.sidebar.toggle()` | Gauche, après logo |
| Titre page courante | `$currentPageLabel` (calculé dans `init.php`) | Centre |
| Année académique active | `annee_academique` WHERE active | Centre-droite |
| Icône Notifications (🔔) | `COUNT(notifications)` WHERE non lues | Droite |
| Badge compteur notif. | Nombre de notifications non lues | Sur l'icône 🔔 |
| Avatar + Nom utilisateur | `$_SESSION['nom_utilisateur']` | Droite |
| Groupe utilisateur | `$_SESSION['lib_GU']` | Sous le nom (petit texte) |
| Menu déroulant profil | Mon profil / Déconnexion | Clic sur avatar |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| ☰ Burger | Blanc sur fond foncé | Toggle sidebar (ouvre/ferme le panneau latéral). Sur mobile, ouvre un drawer. |
| 🔔 Notifications | Blanc, badge rouge si > 0 | Ouvre un dropdown avec les 5 dernières notifications |
| 👤 Mon profil (dropdown) | Transparent | Redirige vers `?page=profil` |
| 🚪 Déconnexion (dropdown) | Rouge | POST `?page=logout` → détruit session → redirection `page_connexion.php` |

**RÈGLES MÉTIER :**
- La navbar est **fixe en haut** (`position: sticky; top: 0; z-index: 1000`).
- Le **titre de la page** change dynamiquement à chaque navigation (via `$currentPageLabel`).
- L'**année académique active** est toujours visible (lecture seule). Elle est calculée via comparaison `date_deb <= NOW() <= date_fin` sur `annee_academique`.
- Le **badge de notifications** n'apparaît que si le compteur est > 0.
- Le **burger toggle** n'est visible que sur les écrans < 1024px (mobile/tablet). Sur desktop, la sidebar est toujours visible.
- Le menu déroulant du profil affiche le nom complet + groupe utilisateur et contient deux options : "Mon profil" et "Déconnexion".
- **Protection CSRF** : le bouton de déconnexion envoie un POST avec token CSRF.
- Le logo est **cliquable** et redirige vers le dashboard par défaut de l'utilisateur.

**ÉTAT DE SORTIE / FEEDBACK :**
- Clic déconnexion : destruction de session → redirection `page_connexion.php`
- Badge notification mis à jour en temps réel (AJAX polling ou websocket si disponible)

**LA ROUTE :** Composant global, présent sur toutes les pages authentifiées. Rendu dans `ressources/views/layouts/app.php`.

**COMPORTEMENT DE LA PAGE :**
- La navbar est rendue dans le fichier `app.php` via les variables `$page_title`, `$user`, `$csrf_token`.
- Le burger menu déclenche `CM.sidebar.toggle()` via `sidebar.js`.
- Sur mobile (< 1024px) : le burger est visible, la sidebar est en mode drawer (overlay).
- Sur desktop (≥ 1024px) : le burger est masqué, la sidebar est toujours visible.
- La navbar utilise les classes Bulma : `navbar`, `is-fixed-top`, et les classes custom `cm-header`, `cm-header-title`.
- Les notifications utilisent un dropdown Bulma.

**Critères de filtrage :** Aucun (statique).

**Traçabilité :** La déconnexion génère un log dans `pister` (action = "Déconnexion").

**Sécurité :** 
- Session vérifiée (`$_SESSION['id_utilisateur']`). Si absente → redirection vers `page_connexion.php`.
- Token CSRF sur toutes les actions POST.
- Le nom utilisateur est échappé avec `htmlspecialchars()`.

**MAQUETTE ASCII :**
```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ [🎓 CM]  [☰]  │  📄 Titre de la page courante      │  [2025-2026]  🔔(3)  👤 Koua ▼  │
│                │                                      │              Administrateur      │
└──────────────────────────────────────────────────────────────────────────────────────────┘
                                                                         │
                                                                ┌───────┴───────┐
                                                                │ 👤 Mon profil │
                                                                │ 🚪 Déconnexion│
                                                                └───────────────┘
```

**MAQUETTE COMPLÈTE — NAVBAR DÉPLOYÉE :**
```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│                                                                                          │
│  ┌────┐  ┌──┐                                                                           │
│  │ 🎓 │  │☰ │    MISE À JOUR ÉTUDIANT                [Année: 2025-2026]   🔔   👤 ▼    │
│  │ CM  │  └──┘                                                             (3)  Koua B. │
│  └────┘                                                                    Administrat. │
│                                                                                          │
└──────────────────────────────────────────────────────────────────────────────────────────┘
  Logo     Burger   Titre dynamique                      Année       Notif   Profil Menu
```

---

## 0.2 Écran : LAYOUT_SIDEBAR — Panneau de navigation latéral

**ID & TITRE :** `LAYOUT_SIDEBAR` — Panneau de navigation latéral

**OBJECTIF :** Offrir un panneau de navigation hiérarchique à **3 niveaux** (Catégorie → Fonctionnalité → Sous-fonctionnalité) permettant d'accéder à tous les écrans de l'application, avec sections collapsibles, indicateurs d'état actif, et comportement responsive drawer sur mobile.

**VUE PRINCIPALE :** Panneau latéral fixe avec menu arborescent.

**ÉLÉMENTS DE DONNÉES (Input/Output) :**

*Source des données du menu :*

| Donnée | Source BDD | Table |
|---|---|---|
| Catégories (niveau 1) | `categories_fonctionnalites` | `id_categorie`, `code_categorie`, `lib_categorie`, `icone_categorie`, `ordre_categorie` |
| Fonctionnalités (niveau 2) | `fonctionnalites` | `id_fonctionnalite`, `code_fonctionnalite`, `lib_fonctionnalite`, `label_fonctionnalite`, `url_fonctionnalite`, `icone_fonctionnalite` |
| Sous-fonctionnalités (niveau 3) | `fonctionnalites` (parent/enfant via `parent_id`) | Même structure, liens enfants |
| Permissions visibilité | `permissions` JOIN `groupe_utilisateur` | `peut_voir` = 1 pour afficher |
| Habilitations | `habilite` | Lie `id_GU` ↔ `id_fonctionnalite` → détermine les fonctionnalités visibles |

*Catégories du menu (depuis `categories_fonctionnalites` dans la BDD) :*

| ID | Code | Libellé | Icône | Ordre |
|---|---|---|---|---|
| 13 | `SCOLARITE` | Gestion de la scolarité | `fas fa-school` | 1 |
| 14 | `ETUDIANT_ENV` | Environnement Étudiant | `fas fa-user-graduate` | 2 |
| 15 | `COMMISSION` | Commission validation | `fas fa-check-double` | 3 |
| 17 | `SOUTENANCE` | Soutenance | `fa-solid fa-user-graduate` | 4 |
| 25 | `ENV_ENSEIGNANT` | Espace Enseignant | `fa-solid fa-person-chalkboard` | 5 |
| 16 | `ADMIN_PLATEFORME` | Administration plateforme | `fas fa-tools` | 6 |
| 24 | `PROFIL` | Profil utilisateur | `fa-solid fa-circle-user` | 6 |

*Structure hiérarchique du menu (3 niveaux) :*

| Niveau | Classe CSS | Comportement |
|---|---|---|
| **Niveau 1 — Catégorie** | `.cm-menu-section` | Cliquable → collapse/expand la section. Affiche icône + libellé + chevron ▾ |
| **Niveau 2 — Fonctionnalité** | `.cm-menu-item` ou `.cm-menu-parent` (si enfants) | Lien cliquable OU toggle sous-menu |
| **Niveau 3 — Sous-fonctionnalité** | `.cm-submenu .cm-menu-item` | Lien cliquable direct |

**ACTIONS & BOUTONS :**

| Libellé | Couleur | Conséquence |
|---|---|---|
| Catégorie (clic) | Fond sombre, texte blanc | Toggle collapse/expand de la section (`is-collapsed`) |
| Fonctionnalité simple (clic) | Bleu actif sur survol | Navigation vers `layout.php?page=XXX` |
| Fonctionnalité parent (clic) | Fond sombre | Toggle sous-menu visible/caché |
| Sous-fonctionnalité (clic) | Bleu actif | Navigation vers `layout.php?page=XXX&action=YYY` |
| Fermer drawer (✕) | Blanc | Ferme le drawer mobile (`CM.sidebar.close()`) |

**RÈGLES MÉTIER :**
- **Filtrage par permissions** : seules les fonctionnalités autorisées pour le `groupe_utilisateur` de l'utilisateur connecté sont affichées. La logique passe par `MenuController.genererMenuHierarchique($id_GU)`.
- **Section active auto-expand** : la section contenant le lien actif est automatiquement dépliée au chargement (`_setActiveSection()` dans `sidebar.js`).
- **Lien actif** : le lien correspondant à la page courante reçoit la classe `is-active` (fond bleu, texte blanc).
- **Ordre des catégories** : triées par `ordre_categorie` ASC. Les catégories avec `actif = 0` sont masquées.
- **Labels intelligents** : affichage de `label_fonctionnalite` en priorité, puis `lib_fonctionnalite`, puis `code_fonctionnalite` nettoyé (suppression du préfixe `SCOL_`, `ETU_`, etc. et remplacement des `_` par des espaces).
- **Responsive** :
  - Desktop (≥ 1024px) : sidebar fixe, toujours visible, largeur ~260px.
  - Mobile (< 1024px) : sidebar cachée, accessible via burger → drawer avec overlay sombre.
- **Accessibilité** : `aria-expanded` sur les sections, fermeture avec la touche `Escape`, focus trap sur mobile.
- **Pas de footer** : la sidebar s'étend sur toute la hauteur disponible (`height: 100vh - navbar`).

**ÉTAT DE SORTIE / FEEDBACK :**
- Aucune action de données. Navigation uniquement.
- L'indicateur visuel `is-active` sur le lien courant donne un feedback de position.
- L'overlay sombre (mobile) donne un feedback de contexte modal.

**LA ROUTE :** Composant global inclus dans `app.php`. Données générées par `MenuController.genererMenuHierarchique()` → converties en `$menus[]` dans `header.php`.

**COMPORTEMENT DE LA PAGE :**
- Au chargement, `init.php` appelle `MenuController.genererMenuHierarchique($_SESSION['id_GU'])` pour obtenir le menu filtré.
- `header.php` convertit la structure `$menuHierarchique` en tableau `$menus[]` au format attendu par le composant sidebar.
- La sidebar est rendue dans `app.php` via le composant `sidebar.php`.
- Le JS `sidebar.js` initialise les comportements : toggle, collapse, overlay, keyboard.
- Les animations de collapse utilisent `max-height` + `opacity` avec transition CSS 350ms.
- Le sous-menu (niveau 3) utilise `display: none/block` pour toggle.
- L'active detection compare `$_GET['page']` et `$_GET['action']` avec les `url_fonctionnalite` de chaque élément.
- Le drawer mobile a un overlay `.cm-drawer-overlay` cliquable pour fermer.

**Critères de filtrage :** Basé sur `id_GU` (groupe utilisateur) de la session → filtre les catégories et fonctionnalités via `habilite` et `permissions`.

**Traçabilité :** Navigation logguée dans `pister` (nom_table = "fonctionnalites", action = "Accès").

**Sécurité :**
- Le menu est **dynamique** : chaque utilisateur ne voit que les éléments autorisés.
- Les URLs sont vérifiées côté serveur même si le lien apparaît (double vérification `PermissionMiddleware`).
- Les données du menu ne sont pas stockées côté client (pas de localStorage).
- Les labels sont échappés avec `htmlspecialchars()`.

**MAQUETTE ASCII — SIDEBAR COMPLÈTE :**
```
┌────────────────────────────┐
│  🎓 CheckMaster UFRMI     │
│  ─────────────────────────  │
│                              │
│  ▾ 🏫 Gestion de la scol.  │  ← Catégorie (Niv.1) [COLLAPSIBLE]
│    ├─ 📊 Tableau de bord    │  ← Fonctionnalité (Niv.2)
│    ├─ 👨‍🎓 Gestion étudiants │  ← Fonctionnalité parent (Niv.2)
│    │   ├─ Mise à jour étud.│  ← Sous-fonctionnalité (Niv.3)
│    │   └─ Inscrire étud.   │  ← Sous-fonctionnalité (Niv.3)
│    ├─ 📝 Saisie moyennes    │
│    ├─ 📋 Dossiers candidat. │
│    └─ ⚠️ Réclamations       │
│                              │
│  ▸ 🎓 Environnement Étud.  │  ← Catégorie collapsée
│                              │
│  ▸ ✅ Commission validation │  ← Catégorie collapsée
│                              │
│  ▸ 🎓 Soutenance           │  ← Catégorie collapsée
│                              │
│  ▸ 👨‍🏫 Espace Enseignant    │  ← Catégorie collapsée
│                              │
│  ▾ 🔧 Admin. plateforme    │  ← Catégorie dépliée
│    ├─ ⚙️ Paramètres génér.  │
│    │   ├─ Grades            │  ← Sous-fonctionnalité
│    │   ├─ Spécialités       │
│    │   ├─ Niveaux étude     │
│    │   ├─ Semestre          │
│    │   ├─ UE                │
│    │   ├─ ECUE              │
│    │   ├─ Entreprises       │
│    │   ├─ Fonctions         │
│    │   ├─ Attributions      │  ← Types + Groupes + Perms
│    │   ├─ Critères éval.    │
│    │   ├─ Statut jury       │
│    │   ├─ Niv. accès        │
│    │   ├─ Niv. approbation  │
│    │   ├─ Messages          │
│    │   ├─ Traitements       │
│    │   └─ Actions           │
│    ├─ 👥 Gestion utilisat.  │
│    ├─ 📋 Piste d'audit      │
│    ├─ 💾 Sauveg. / Restaur. │
│    └─ 📦 Historique         │
│                              │
│  ▸ 👤 Profil utilisateur   │
│                              │
└────────────────────────────┘
```

---

## 0.3 LAYOUT GLOBAL — Assemblage Complet

**ID & TITRE :** `LAYOUT_GLOBAL` — Structure de page complète

**OBJECTIF :** Assembler la Navbar + Sidebar + Zone de contenu en une structure SPA-like cohérente, sans footer, exploitant Bulma CSS et le composant system CheckMaster.

**VUE PRINCIPALE :** Grille flexbox à 2 colonnes (sidebar + contenu).

**ÉLÉMENTS DE DONNÉES :**

| Variable | Source | Utilisation |
|---|---|---|
| `$menus` | `header.php` (converti depuis `$menuHierarchique`) | Structure du menu sidebar |
| `$page_title` / `$title` | `$currentPageLabel` (init.php + router.php) | Titre dans la navbar |
| `$user` | `$_SESSION['nom_utilisateur']`, `$_SESSION['lib_GU']` | Affichage profil navbar |
| `$csrf_token` | `Csrf::token()` | Injection dans tous les formulaires POST |
| `$flash_message` | `$_SESSION['error_message']` ou `$_SESSION['success_message']` | Notifications toast |
| `$content` | `ob_get_clean()` dans `footer.php` | Contenu de la page injecté |
| `$extra_css` | Tableau de chemins CSS additionnels | Styles legacy |
| `$extra_js` | Tableau de chemins JS additionnels | Scripts legacy |

**RÈGLES MÉTIER :**
- **Architecture** : le layout suit le pattern PHP buffer : `init.php` → `router.php` → `header.php` (ouvre buffer) → contenu → `footer.php` (ferme buffer, injecte dans `app.php`).
- **Pas de footer HTML** : l'application n'a aucun footer visible. La zone de contenu s'étend jusqu'en bas.
- **Flash messages** : les messages `$_SESSION['error_message']` et `$_SESSION['success_message']` sont affichés en notification Bulma en haut de la zone de contenu, puis supprimés de la session.
- **CSRF auto-injection** : `footer.php` injecte automatiquement un champ `csrf_token` dans tous les `<form method="post">` du HTML via regex.
- **Responsive breakpoints** :
  - `≥ 1024px` : sidebar fixe (260px) + contenu flex-grow
  - `< 1024px` : sidebar drawer (overlay) + contenu pleine largeur
- **Fichiers CSS** : Bulma framework + `checkmaster-theme.css` + `components.css` + `responsive.css` + `$extra_css[]`
- **Fichiers JS** : `sidebar.js` + `crud-layout.js` + `$extra_js[]`

**COMPORTEMENT DE LA PAGE :**
- L'utilisateur non authentifié est redirigé vers `page_connexion.php` (vérification dans `init.php`).
- Le router (`router.php`) détermine le `$contentFile` basé sur `$_GET['page']` et `$_GET['action']`.
- Le contenu est bufferisé et injecté dans le layout `app.php` via la variable `$content`.
- Le layout `app.php` produit le HTML complet : `<!DOCTYPE html>` → `<head>` → `<body>` → Navbar + Sidebar + Content.
- Les notifications flash apparaissent en haut de la zone de contenu et disparaissent après 5 secondes.
- La sidebar retient l'état de la section active (auto-expand via JS).

**Sécurité :**
- Vérification session sur chaque chargement.
- CSRF global sur toutes les requêtes POST.
- Rate limiting sur l'authentification (`auth_rate_limits`).
- Permissions vérifiées doublement : côté menu (affichage) ET côté serveur (middleware).

**MAQUETTE ASCII — LAYOUT COMPLET :**
```
┌──────────────────────────────────────────────────────────────────────────────────────────┐
│ [🎓 CM]  [☰]  │  📄 MISE À JOUR ÉTUDIANT             │  [2025-2026]  🔔(3)  👤 Koua ▼│
└──────────────────────────────────────────────────────────────────────────────────────────┘
┌────────────────────────┬─────────────────────────────────────────────────────────────────┐
│                        │                                                                 │
│ ▾ 🏫 Scolarité         │  ┌──────────────────────────────────────────────────────────┐  │
│   ├─ 📊 Dashboard      │  │ ✅ Étudiant ajouté avec succès                           │  │
│   ├─ 👨‍🎓 Étudiants     │  └──────────────────────────────────────────────────────────┘  │
│   │  ├─ MAJ Étud. ◄───│─ ← ACTIF (bleu)                                                │
│   │  └─ Inscrire       │  ┌──────────────────────────────────────────────────────────┐  │
│   ├─ 📝 Moyennes       │  │ PÔLE SUPÉRIEUR (Formulaire)                              │  │
│   ├─ 📋 Candidatures   │  │ N° Étudiant  [________]    Nom  [________]               │  │
│   └─ ⚠️ Réclamations   │  │ ...                                                      │  │
│                        │  │                              [Réinit.] [✔ Enregistrer]    │  │
│ ▸ 🎓 Env. Étudiant    │  └──────────────────────────────────────────────────────────┘  │
│                        │                                                                 │
│ ▸ ✅ Commission        │  ┌──────────────────────────────────────────────────────────┐  │
│                        │  │ PÔLE INFÉRIEUR (Tableau)                  Afficher: [▼10]│  │
│ ▸ 🎓 Soutenance       │  │ [☑ Sélect. tout] [☐ Désélect.] [🗑 Supprimer (0)]       │  │
│                        │  ├──────────────────────────────────────────────────────────┤  │
│ ▸ 👨‍🏫 Enseignant       │  │ ☐│N° Étud│Nom    │Prénom  │Email   │Promo│Actions        │  │
│                        │  │──┼───────┼───────┼────────┼────────┼─────┼───────────────│  │
│ ▾ 🔧 Admin            │  │ ☐│CI01.. │Konan  │Yao     │k@ufhb  │2025 │ ✏ 🗑          │  │
│   ├─ ⚙️ Paramètres    │  │ ☐│CI01.. │Traoré │Fatou   │t@ufhb  │2024 │ ✏ 🗑          │  │
│   ├─ 👥 Utilisateurs  │  └──────────────────────────────────────────────────────────┘  │
│   ├─ 📋 Audit         │  Affichage de 1 à 10 sur 120         [◀ Préc.][1][2]...[▶]     │
│   ├─ 💾 Sauveg.       │                                                                 │
│   └─ 📦 Historique    │                                                                 │
│                        │                                                                 │
│ ▸ 👤 Profil           │     ⛔ PAS DE FOOTER — Le contenu s'étend jusqu'en bas          │
│                        │                                                                 │
└────────────────────────┴─────────────────────────────────────────────────────────────────┘
  ◄── 260px (fixe) ──►   ◄──────────── flex-grow (responsive) ──────────────────────────►
```

**MAQUETTE ASCII — MOBILE (< 1024px) :**
```
┌─────────────────────────────────┐
│ [🎓] [☰]  MÀJ Étudiant  🔔 👤│    ← Navbar compacte
├─────────────────────────────────┤
│                                 │
│ ┌───────────────────────────┐  │    ← Contenu pleine largeur
│ │ Formulaire...             │  │
│ └───────────────────────────┘  │
│ ┌───────────────────────────┐  │
│ │ Tableau (scroll horiz.)   │  │
│ └───────────────────────────┘  │
│                                 │
└─────────────────────────────────┘

┌───────────┐ ░░░░░░░░░░░░░░░░░░░
│ ✕ Fermer  │ ░░░░░░░░░░░░░░░░░░░  ← Overlay sombre
│           │ ░░░░░░░░░░░░░░░░░░░
│ ▾ Scolarit│ ░░░░░░░░░░░░░░░░░░░  ← Drawer sidebar
│   ├─ Dash │ ░░░░░░░░░░░░░░░░░░░     (ouvert par ☰)
│   ├─ Étud │ ░░░░░░░░░░░░░░░░░░░
│   ├─ Moyns│ ░░░░░░░░░░░░░░░░░░░
│ ...       │ ░░░░░░░░░░░░░░░░░░░
└───────────┘ ░░░░░░░░░░░░░░░░░░░
  ◄─ 80% ──►
```

---

## 0.4 Résumé CSS — Classes principales

| Classe CSS | Élément | Rôle |
|---|---|---|
| `.cm-header` | `<nav>` | Navbar fixe en haut |
| `.cm-header-title` | `<span>` | Titre page dans la navbar |
| `.cm-burger` | `<button>` | Toggle sidebar (desktop) |
| `.cm-burger-mobile` | `<button>` | Toggle sidebar (mobile) |
| `#cm-sidebar` | `<aside>` | Container sidebar |
| `.cm-menu-section` | `<div>` | Catégorie niveau 1 |
| `.cm-menu-section-title` | `<a>` | Titre catégorie (cliquable) |
| `.cm-menu-items` | `<div>` | Container des items niveau 2 |
| `.cm-menu-item` | `<div>` | Item de menu simple (niveau 2/3) |
| `.cm-menu-parent` | `<div>` | Item de menu avec enfants |
| `.cm-menu-toggle` | `<a>` | Toggle sous-menu (niveau 2→3) |
| `.cm-submenu` | `<div>` | Container sous-menu niveau 3 |
| `.is-active` | Modificateur | État actif (lien courant) |
| `.is-collapsed` | Modificateur | Section repliée |
| `.cm-drawer-overlay` | `<div>` | Overlay sombre mobile |
| `.cm-mobile-drawer` | `<div>` | Container drawer mobile |
| `.cm-main-content` | `<main>` | Zone de contenu principal |

---

## 0.5 Résumé JS — Composant `CM.sidebar`

| Méthode | Description |
|---|---|
| `init()` | Initialise sidebar, bind events |
| `toggle()` | Toggle visibilité sidebar |
| `close()` | Ferme sidebar et overlay |
| `_bindBurgers()` | Lie les boutons burger au toggle |
| `_bindSections()` | Lie les sections collapsibles (niv.1 et niv.2) |
| `_bindOverlay()` | Lie le clic overlay → close |
| `_bindKeyboard()` | Escape → close |
| `_setActiveSection()` | Déplie auto la section contenant le lien actif |

(End of file - total 393 lines)

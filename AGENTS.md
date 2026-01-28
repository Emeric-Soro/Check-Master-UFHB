### 1. Diagnostic de l'existant
Votre projet est une **"machine de guerre" fonctionnelle** (ERP académique complet) mais piégée dans une interface **"obèse"**. Le PHP, le HTML, le CSS (Tailwind) et le JavaScript sont mélangés dans chaque fichier, ce qui rend la maintenance cauchemardesque et le design incohérent.

### 2. La Solution : Le "Model Pattern" Premium
Nous passons d'un code "spaghetti" à une architecture **modulaire inspirée de Shadcn/UI** :
*   **Abandon de Tailwind :** Remplacé par un système de **Variables CSS** centralisé pour un contrôle total et un rendu ultra-léger.
*   **Design System :** Sidebar bleu profond (`#1a5276`), Navbar et fond translucides, typographie raffinée et coins arrondis.
*   **Approche Mobile-First :** Une interface qui s'adapte nativement aux smartphones (menu tiroir, boutons larges) et aux ordinateurs.

### 3. La Méthodologie de Migration
On ne jette pas le travail accompli, on le **transvase** :
*   **Extraction :** On utilise le rapport de migration pour identifier les **données vitales** (variables), la **logique métier** (calculs de moyennes) et les **habilitations** (`canEdit`).
*   **Simplification :** On remplace les `if/else` et `foreach` visibles par des **Composants Paramétrables** et des **Tableaux de Mapping**.
*   **Composants A-Z :** Tout élément d'interface (Bouton, Carte, Tableau, Modale) devient un composant unique et réutilisable.

### 4. Structure Cible du Code
Vos fichiers de vues ne seront plus des pages de 500 lignes, mais des **fichiers d'assemblage** propres :
1.  **Appel du Layout** (Header/Sidebar).
2.  **Passage des données** aux composants (ex: `renderTable($etudiants)`).
3.  **Gestion des actions** via des boutons textuels clairs.

### 5. Résultat Attendu
*   **Maintenance facilitée :** Changez une couleur ou un style de bouton en modifiant **un seul fichier** au lieu de 70.
*   **Performance :** Suppression des frameworks lourds pour du CSS/JS natif ultra-rapide.
*   **Expérience Utilisateur (UX) :** Une application digne d'un logiciel professionnel moderne, fluide et intuitive sur tous les supports.

### 6. Détail des Composants & Logiques à Intégrer

### I. Composants de Structure (Layout)

| Composant | Logique à intégrer (Anciens fichiers) |
| :--- | :--- |
| **Sidebar** | Boucle sur `$menuHierarchique` pour générer les menus/sous-menus. Gestion de l'état `active` selon la page courante. |
| **Navbar** | Affichage dynamique du nom de l'utilisateur (`$_SESSION['nom_utilisateur']`) et de son rôle (`$_SESSION['lib_GU']`). |
| **Breadcrumb** | Extraction du chemin via l'URL (ex: Accueil > Scolarité > Inscriptions). |
| **Separator** | Simple ligne de division pour séparer les sections de formulaires (ex: Infos perso vs Infos académiques). |

### II. Composants d'Affichage & Data (Display)

| Composant | Logique à intégrer (Anciens fichiers) |
| :--- | :--- |
| **DataTable** | **La plus grosse logique :** Reçoit un tableau (ex: `$listeEtudiants`). Gère la pagination (`$totalPages`, `$currentPage`) et les actions CRUD selon les permissions (`canEdit`, `canDelete`). |
| **StatsCard** | Calcul des pourcentages (ex: `$pourcentageComplete`) et affichage des compteurs (ex: `$totalRapports`). |
| **Badge** | **Mapping des statuts :** Transforme 'valide' en "Admis" (Vert), 'rejeter' en "Ajourné" (Rouge), 'en_cours' en "En attente" (Orange). |
| **Timeline** | Logique de workflow des rapports : Vérifie si l'étape 1 (Dépôt), 2 (Com) ou 3 (Commission) est terminée pour colorer les points. |
| **Avatar** | Génération des initiales à partir de `$nom_user` (ex: "Jean Dupont" -> "JD"). |
| **Skeleton** | État visuel affiché pendant que les requêtes AJAX (ex: `get_stats`) sont en cours. |
| **EmptyState** | Condition `if (empty($data))` : Affiche une icône et un message si aucun résultat n'est trouvé. |

### III. Composants de Formulaire (Inputs)

| Composant | Logique à intégrer (Anciens fichiers) |
| :--- | :--- |
| **Input / TextArea** | Pré-remplissage des valeurs en mode édition (ex: `value="<?= $etudiant->nom ?>"`). |
| **Select** | Boucle sur les tables de référence (ex: `foreach ($listeAnnees)`, `foreach ($niveaux)`). |
| **DatePicker** | Formatage des dates SQL (`YYYY-MM-DD`) vers le format humain (`DD/MM/YYYY`). |
| **Checkbox** | Gestion de la sélection multiple pour les suppressions en masse (Audit, Étudiants). |
| **Progress** | Calcul du ratio (ex: `(notes_saisies / total_ecue) * 100`) pour les barres d'avancement. |

### IV. Composants d'Interaction (Feedback)

| Composant | Logique à intégrer (Anciens fichiers) |
| :--- | :--- |
| **Alert (Toast)** | Gestion des messages flash : `$_SESSION['success']` ou `$_SESSION['error']`. Auto-fermeture après 5s. |
| **Dialog (Modal)** | Logique de confirmation : "Êtes-vous sûr de vouloir supprimer...". Chargement dynamique du contenu via ID (ex: `id_rapport`). |
| **Dropdown** | Regroupement des actions secondaires (Exporter, Imprimer, Historique) pour ne pas encombrer le tableau. |
| **Tabs** | Logique de filtrage de vue (ex: basculer entre "Historique Étudiants" et "Historique Jurys" sans recharger la page). |
| **Tooltip** | Affichage des commentaires complets au survol des cellules tronquées dans les tableaux. |

---

### Exemple de Logique "Encapsulée" (Le composant `Badge`)

Au lieu de répéter des `if/else` dans chaque page, la logique est centralisée dans le composant :

```php
// ressources/views/components/ui/badge.php
function renderBadge($status) {
    // La logique métier est ici, une seule fois pour toute l'appli
    $mapping = [
        'valide'   => ['class' => 'success', 'label' => 'Validé'],
        'rejete'   => ['class' => 'danger',  'label' => 'Rejeté'],
        'en_cours' => ['class' => 'warning', 'label' => 'En cours'],
        'attente'  => ['class' => 'info',    'label' => 'En attente']
    ];

    $data = $mapping[$status] ?? ['class' => 'muted', 'label' => 'Inconnu'];

    echo "<span class='badge badge-{$data['class']}'>{$data['label']}</span>";
}
```

### Exemple de Logique "Encapsulée" (Le composant `Button`)

Il intègre nativement la sécurité :

```php
// ressources/views/components/ui/button.php
function renderButton($text, $action, $permission = true) {
    // Si la permission (ex: canEdit()) est fausse, on ne renvoie rien
    if (!$permission) return '';

    return "<button class='btn btn-primary' onclick='$action'>$text</button>";
}
```

### Pourquoi c'est puissant ?
Dans votre fichier `gestion_etudiants.php`, vous n'aurez plus que ceci :
```php
<!-- Très propre, très lisible -->
<div class="page-header">
    <h1>Liste des Étudiants</h1>
    <?= renderButton("Ajouter", "openModal()", canCreate()) ?>
</div>

<?= renderDataTable($listeEtudiants) ?>
```

# 🏗️ Design Pattern : CheckMaster Premium (Shadcn-Inspired)

## 1. Identité Visuelle (Tokens)
Les styles ne sont plus éparpillés mais centralisés dans des variables CSS.

| Token | Valeur | Usage |
| :--- | :--- | :--- |
| `--sidebar-bg` | `#1a5276` | Fond du menu latéral |
| `--nav-bg` | `rgba(59, 130, 246, 0.05)` | Navbar & Background (Glassmorphism) |
| `--primary` | `#1a5276` | Boutons principaux, liens actifs |
| `--accent` | `#3b82f6` | Focus, barres de progression |
| `--radius` | `0.75rem (12px)` | Arrondis des cartes et boutons |
| `--border` | `rgba(0, 0, 0, 0.08)` | Bordures ultra-fines |

---

## 2. Architecture des Composants (PHP Pattern)
Chaque composant est un fichier PHP autonome dans `ressources/views/components/`.

### A. Pattern de "Bouton" (Action Textuelle)
**Logique :** Intègre la vérification des permissions.
```php
// components/ui/button.php
function renderButton($text, $type = 'primary', $permission = true, $attr = '') {
    if (!$permission) return ''; // Sécurité centralisée
    
    $variants = [
        'primary' => 'btn-primary',
        'outline' => 'btn-outline',
        'danger'  => 'btn-danger',
        'ghost'   => 'btn-ghost'
    ];
    $class = $variants[$type] ?? $variants['primary'];
    
    return "<button class='btn $class' $attr>$text</button>";
}
```

### B. Pattern de "DataTable" (Gestion des listes)
**Logique :** Remplace les `foreach` répétitifs par une boucle interne.
```php
// components/ui/data-table.php
function renderTable($headers, $data, $actions = []) { ?>
    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <?php foreach($headers as $h): ?> <th><?= $h ?></th> <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach($data as $row): ?>
                    <tr>
                        <!-- Logique de mapping dynamique des colonnes ici -->
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php }
```

---

## 3. Structure d'une Page Type (Le "Template")
Voici comment une page complexe (ex: `gestion_scolarite`) est assemblée.

```php
<!-- pages/scolarite/paiements.php -->

<!-- 1. Layout Global -->
<?php include 'layouts/main_header.php'; ?>

<div class="container">
    <!-- 2. Breadcrumb & Header -->
    <?= renderBreadcrumb(['Accueil', 'Scolarité', 'Paiements']) ?>
    
    <div class="page-header">
        <h1>Gestion des Versements</h1>
        <?= renderButton("Enregistrer un versement", "primary", canCreate(), "onclick='openModal()'") ?>
    </div>

    <!-- 3. Stats Grid (KPI) -->
    <div class="stats-grid">
        <?= renderStatsCard("Total Perçu", $montantTotal, "wallet") ?>
        <?= renderStatsCard("En attente", $montantAttente, "clock", "warning") ?>
    </div>

    <!-- 4. Main Content (Card + Table) -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Historique des transactions</h3>
            <?= renderSearchBar("Rechercher un étudiant...") ?>
        </div>
        
        <?php 
            // On passe les données brutes, le composant gère l'affichage
            renderTable(
                ['Étudiant', 'Montant', 'Date', 'Statut'], 
                $versements_pages
            ); 
        ?>
        
        <?= renderPagination($currentPage, $totalPages) ?>
    </div>
</div>

<?php include 'layouts/main_footer.php'; ?>
```

---

## 4. Guide des Interactions (JS Pattern)

### Modales (Dialog)
Utilisation d'un seul moteur de modale pour toute l'appli.
```javascript
const Modal = {
    show: (id) => {
        const el = document.getElementById(id);
        el.style.display = 'flex';
        document.body.classList.add('modal-open');
    },
    hide: (id) => {
        const el = document.getElementById(id);
        el.style.display = 'none';
        document.body.classList.remove('modal-open');
    }
};
```

### Feedbacks (Alert/Toast)
```javascript
function notify(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `alert alert-${type} animate-slide-in`;
    toast.innerHTML = `<i class="fas fa-info-circle"></i> ${message}`;
    document.getElementById('toast-container').appendChild(toast);
    setTimeout(() => toast.remove(), 5000);
}
```

---

## 5. Pourquoi ce Pattern est supérieur ?

1.  **Code 80% plus court :** Vos fichiers de vues passent de 500 lignes à 50 lignes.
2.  **Cohérence Shadcn :** Tous les composants partagent les mêmes ombres, arrondis et espacements.
3.  **Maintenance Instantanée :** Vous voulez changer le style de tous les `Select` de l'application ? Modifiez uniquement `components/ui/select.php`.
4.  **Performance Mobile :** Le CSS est compilé et léger, les composants s'empilent naturellement sur smartphone grâce à la `stats-grid` et au `table-wrapper` scrollable.


**Ce pattern transforme votre projet d'un "site web" en une véritable "application logicielle" professionnelle.**
**Toute la complexité (boucles, conditions de couleurs, vérification de droits) est "cachée" à l'intérieur des composants.**
**En résumé : On garde le "cerveau" (votre logique PHP) et on lui donne un nouveau "corps" (une interface Premium modulaire).**
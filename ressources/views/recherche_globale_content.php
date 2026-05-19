<?php
/**
 * P2.11 — Barre Recherche Globale
 * Slug: recherche_globale | Permission: recherche_globale
 *
 * Affiche une page de recherche avec résultats groupés.
 * Support AJAX pour l'autocomplete (action=autocomplete).
 */

require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/Services/RechercheGlobaleService.php';

use CheckMaster\Services\RechercheGlobaleService;

$service = new RechercheGlobaleService(Database::getConnection());

// Endpoint AJAX autocomplete
if (isset($_GET['ajax']) && $_GET['ajax'] === '1' && isset($_GET['q'])) {
    header('Content-Type: application/json; charset=utf-8');
    $query = trim((string) ($_GET['q'] ?? ''));
    $results = $service->searchAjax($query);
    echo json_encode(['success' => true, 'results' => $results]);
    exit;
}

$query = trim((string) ($_GET['q'] ?? ''));
$results = $query !== '' ? $service->search($query, 20) : [];

$totalResults = array_sum(array_map('count', $results));

// Préparer les lignes pour chaque catégorie
$categoryLabels = [
    'etudiants'    => ['label' => 'Étudiants',     'icon' => 'fa-user-graduate',   'link_prefix' => '?page=fiche_etudiant_complete&id='],
    'enseignants'  => ['label' => 'Enseignants',   'icon' => 'fa-chalkboard-teacher', 'link_prefix' => '?page=fiche_enseignante&view=fiche&id='],
    'personnel'    => ['label' => 'Personnel admin','icon' => 'fa-users',           'link_prefix' => '?page=fiche_personnel_admin&id='],
    'utilisateurs' => ['label' => 'Utilisateurs',  'icon' => 'fa-user-circle',     'link_prefix' => '?page=gestion_utilisateurs&id='],
];
?>
<section class="cm-screen-scrollable">
    <div class="cm-crud-wrapper">

        <?php cm_component('crud/form-pole', [
            'title' => 'Recherche globale',
            'icon'  => 'fa-search',
            'content' => '<p class="cm-text-muted">Recherchez dans toute la base de données : étudiants, enseignants, personnel, utilisateurs.</p>',
        ]); ?>

        <!-- Barre de recherche -->
        <form method="GET" class="cm-mb-lg">
            <input type="hidden" name="page" value="recherche_globale">
            <div class="cm-flex cm-flex-gap-sm">
                <div class="cm-flex-1" style="position:relative;">
                    <input type="text"
                           name="q"
                           id="cmRechercheGlobaleInput"
                           class="cm-form-control"
                           placeholder="Rechercher un nom, prénom, matricule, email..."
                           value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
                           autocomplete="off"
                           style="font-size:1.1rem; padding:0.75rem 1rem;">
                    <div id="cmRechercheAutocomplete"
                         style="position:absolute; top:100%; left:0; right:0; z-index:1000;
                                background:var(--cm-box-bg); border:1px solid var(--cm-border-color);
                                border-radius:0 0 8px 8px; box-shadow:0 4px 12px rgba(0,0,0,0.1);
                                display:none; max-height:400px; overflow-y:auto;">
                    </div>
                </div>
                <button type="submit" class="cm-btn is-primary is-lg">
                    <i class="fas fa-search"></i>
                    <span>Rechercher</span>
                </button>
            </div>
        </form>

        <?php if ($query !== ''): ?>
        <div class="cm-text-muted cm-mb-md">
            <i class="fas fa-info-circle"></i>
            <?= $totalResults ?> résultat(s) pour "<strong><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></strong>"
        </div>
        <?php endif; ?>

        <?php if ($query === ''): ?>
            <?php cm_component('ui/empty-state', [
                'title' => 'Effectuez une recherche',
                'message' => 'Saisissez un terme dans la barre de recherche ci-dessus pour trouver des personnes dans la base de données.',
                'icon' => 'fa-search-plus',
            ]); ?>
        <?php elseif ($totalResults === 0): ?>
            <?php cm_component('ui/empty-state', [
                'title' => 'Aucun résultat',
                'message' => 'Aucune correspondance trouvée pour "' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . '".',
                'icon' => 'fa-search-minus',
            ]); ?>
        <?php else: ?>

            <?php foreach ($results as $category => $items): ?>
                <?php if (empty($items)) continue; ?>
                <?php $catInfo = $categoryLabels[$category] ?? ['label' => $category, 'icon' => 'fa-circle', 'link_prefix' => '#']; ?>

                <div class="cm-card cm-mb-md">
                    <div class="cm-card__header">
                        <h3 class="cm-card__title">
                            <i class="fas <?= $catInfo['icon'] ?>"></i>
                            <?= $catInfo['label'] ?>
                            <span class="cm-badge is-info is-sm"><?= count($items) ?></span>
                        </h3>
                    </div>
                    <div class="cm-card__body">
                        <table class="cm-data-table">
                            <tbody>
                                <?php foreach ($items as $item): ?>
                                <?php
                                    $id = $item['id'] ?? '';
                                    $link = $catInfo['link_prefix'] . urlencode((string) $id);
                                    $displayName = match ($category) {
                                        'etudiants'   => ($item['nom_etu'] ?? '') . ' ' . ($item['prenom_etu'] ?? ''),
                                        'enseignants' => ($item['nom_enseignant'] ?? '') . ' ' . ($item['prenom_enseignant'] ?? ''),
                                        'personnel'   => ($item['nom_pers_admin'] ?? '') . ' ' . ($item['prenom_pers_admin'] ?? ''),
                                        'utilisateurs' => $item['nom_utilisateur'] ?? $item['login'] ?? '',
                                        default       => $id,
                                    };
                                    $sub = match ($category) {
                                        'etudiants'   => $item['num_carte_etud'] ?? $item['id'] ?? '',
                                        'enseignants' => $item['id_enseignant'] ?? $item['id'] ?? '',
                                        'personnel'   => $item['id'] ?? '',
                                        'utilisateurs' => $item['login'] ?? '',
                                        default       => '',
                                    };
                                    $email = $item['email'] ?? $item['email_etu'] ?? $item['mail_enseignant'] ?? '';
                                ?>
                                <tr class="cm-data-table__row cm-clickable-row" data-href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>">
                                    <td class="cm-data-table__td">
                                        <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                                        <?php if ($sub): ?>
                                            <span class="cm-text-muted cm-text-xs">(<?= htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') ?>)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="cm-data-table__td cm-text-muted cm-text-sm">
                                        <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td class="cm-data-table__td is-right">
                                        <a href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" class="cm-btn is-ghost is-sm">
                                            <i class="fas fa-arrow-right"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>

        <?php endif; ?>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('cmRechercheGlobaleInput');
    const dropdown = document.getElementById('cmRechercheAutocomplete');
    let timeout = null;

    if (!input || !dropdown) return;

    input.addEventListener('input', function() {
        clearTimeout(timeout);
        const query = this.value.trim();

        if (query.length < 2) {
            dropdown.style.display = 'none';
            return;
        }

        timeout = setTimeout(function() {
            fetch('?page=recherche_globale&ajax=1&q=' + encodeURIComponent(query))
                .then(r => r.json())
                .then(data => {
                    dropdown.innerHTML = '';
                    if (!data.success || !data.results || data.results.length === 0) {
                        dropdown.style.display = 'none';
                        return;
                    }

                    data.results.forEach(function(item) {
                        const div = document.createElement('div');
                        if (item.type === 'category') {
                            div.className = 'cm-dropdown-category';
                            div.innerHTML = '<i class="fas ' + item.icon + '"></i> ' + item.label;
                            div.style.cssText = 'padding:0.5rem 1rem; font-weight:600; font-size:0.85rem; background:var(--cm-surface-muted); color:var(--cm-text-muted); text-transform:uppercase; letter-spacing:0.05em;';
                        } else {
                            div.className = 'cm-dropdown-item';
                            div.style.cssText = 'padding:0.5rem 1rem; cursor:pointer; display:flex; align-items:center; gap:1rem; border-bottom:1px solid var(--cm-border-color);';
                            div.innerHTML = '<span style="flex:1;"><strong>' + item.label + '</strong> <span class="cm-text-muted cm-text-xs">' + item.sub + '</span></span><i class="fas fa-chevron-right cm-text-muted"></i>';
                            div.addEventListener('click', function() {
                                window.location.href = item.url;
                            });
                            div.addEventListener('mouseenter', function() {
                                this.style.background = 'var(--cm-surface-hover)';
                            });
                            div.addEventListener('mouseleave', function() {
                                this.style.background = '';
                            });
                        }
                        dropdown.appendChild(div);
                    });

                    dropdown.style.display = 'block';
                })
                .catch(() => {
                    dropdown.style.display = 'none';
                });
        }, 300);
    });

    // Cacher le dropdown au clic externe
    document.addEventListener('click', function(e) {
        if (!input.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.style.display = 'none';
        }
    });

    // Cacher au blur avec délai
    input.addEventListener('blur', function() {
        setTimeout(function() {
            dropdown.style.display = 'none';
        }, 200);
    });
});
</script>

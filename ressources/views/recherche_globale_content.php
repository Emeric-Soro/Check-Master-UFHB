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
    'etudiants' => [
        'label' => 'Étudiants',
        'icon' => 'fa-user-graduate',
        'tone' => 'primary',
        'link_prefix' => '?page=fiche_etudiant_complete&id=',
    ],
    'enseignants' => [
        'label' => 'Enseignants',
        'icon' => 'fa-chalkboard-teacher',
        'tone' => 'info',
        'link_prefix' => '?page=fiche_enseignante&view=fiche&id=',
    ],
    'personnel' => [
        'label' => 'Personnel admin',
        'icon' => 'fa-users',
        'tone' => 'success',
        'link_prefix' => '?page=fiche_personnel_admin&id=',
    ],
    'utilisateurs' => [
        'label' => 'Utilisateurs',
        'icon' => 'fa-user-circle',
        'tone' => 'warning',
        'link_prefix' => '?page=gestion_utilisateurs&id=',
    ],
];

$categoryOrder = ['etudiants', 'enseignants', 'personnel', 'utilisateurs'];
$categoryCounts = [];
foreach ($categoryOrder as $key) {
    $categoryCounts[$key] = count($results[$key] ?? []);
}
?>
<section class="cm-screen-scrollable cm-search-global">
    <style>
        .cm-search-global {
            position: relative;
        }

        .cm-search-global__shell {
            display: grid;
            gap: 0.8rem;
        }

        .cm-search-global__hero {
            position: relative;
            overflow: hidden;
            background: transparent;
            border: 0;
            border-radius: 0;
            box-shadow: none;
            padding: 0;
        }

        .cm-search-global__hero-top {
            position: relative;
            display: grid;
            gap: 0.5rem;
        }

        .cm-search-global__eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            width: fit-content;
            padding: 0;
            border-radius: 0;
            background: transparent;
            color: #1a507b;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .cm-search-global__title {
            margin: 0;
            color: #13395c;
            font-size: clamp(1.65rem, 2vw + 1rem, 2.45rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            line-height: 1.05;
            text-wrap: balance;
        }

        .cm-search-global__subtitle {
            max-width: 60rem;
            margin: 0;
            color: #5e7386;
            font-size: 0.98rem;
            line-height: 1.65;
            text-wrap: pretty;
        }

        .cm-search-global__meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.4rem;
            margin-top: 0.25rem;
        }

        .cm-search-global__pill {
            display: inline-flex;
            align-items: center;
            gap: 0.45rem;
            padding: 0;
            border-radius: 0;
            background: transparent;
            border: 0;
            color: #254a68;
            font-size: 0.84rem;
            font-weight: 700;
        }

        .cm-search-global__search {
            position: relative;
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 0.85rem;
            align-items: end;
            margin-top: 0.2rem;
        }

        .cm-search-global__field-wrap {
            position: relative;
        }

        .cm-search-global__input {
            width: 100%;
            min-height: 58px;
            padding: 0.95rem 1.1rem;
            border-radius: 0.95rem;
            border: 1px solid rgba(30, 81, 121, 0.16);
            background: rgba(255, 255, 255, 0.72);
            color: #15395c;
            font-size: 1.03rem;
            font-weight: 600;
            box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.86);
            transition: border-color 180ms ease, box-shadow 180ms ease, transform 180ms ease;
        }

        .cm-search-global__input::placeholder {
            color: #6b8295;
            font-weight: 500;
        }

        .cm-search-global__input:focus {
            outline: none;
            border-color: rgba(31, 116, 191, 0.4);
            box-shadow: 0 0 0 4px rgba(31, 116, 191, 0.09);
        }

        .cm-search-global__submit {
            min-height: 58px;
            padding-inline: 1.15rem;
            border-radius: 0.95rem;
        }

        .cm-search-global__autocomplete {
            position: absolute;
            top: calc(100% + 0.45rem);
            left: 0;
            right: 0;
            z-index: 20;
            display: none;
            max-height: 420px;
            overflow: auto;
            border: 0;
            border-radius: 0;
            background: rgba(255, 255, 255, 0.96);
            box-shadow: 0 10px 30px rgba(28, 74, 110, 0.08);
            backdrop-filter: blur(10px);
        }

        .cm-search-global__results {
            display: grid;
            gap: 1rem;
        }

        .cm-search-global__status {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            width: fit-content;
            padding: 0;
            border-radius: 0;
            background: transparent;
            border: 0;
            color: #44617a;
            font-weight: 700;
        }

        .cm-search-global__section {
            overflow: hidden;
            border: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .cm-search-global__section-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            padding: 0.4rem 0 0.3rem;
            border-bottom: 0;
        }

        .cm-search-global__section-title {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            margin: 0;
            color: #163a5c;
            font-size: 1.04rem;
            font-weight: 800;
        }

        .cm-search-global__section-note {
            color: #607a90;
            font-size: 0.86rem;
            font-weight: 600;
        }

        .cm-search-global__list {
            display: grid;
        }

        .cm-search-global__row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            gap: 1rem;
            align-items: center;
            padding: 0.9rem 0;
            color: inherit;
            text-decoration: none;
            border-top: 0;
            transition: opacity 180ms ease, transform 180ms ease;
        }

        .cm-search-global__row:hover {
            opacity: 0.92;
            transform: translateY(-1px);
        }

        .cm-search-global__row-main {
            min-width: 0;
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
        }

        .cm-search-global__row-title {
            display: flex;
            flex-wrap: wrap;
            align-items: baseline;
            gap: 0.5rem;
            color: #173a5c;
            font-weight: 700;
            line-height: 1.3;
        }

        .cm-search-global__row-title strong {
            font-weight: 800;
        }

        .cm-search-global__row-sub {
            color: #6c8293;
            font-size: 0.86rem;
        }

        .cm-search-global__row-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
            color: #5c7387;
            font-size: 0.84rem;
        }

        .cm-search-global__row-arrow {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: auto;
            height: auto;
            border-radius: 0;
            background: transparent;
            color: #1f74bf;
        }

        .cm-search-global__empty {
            padding: 0.2rem 0 0;
        }

        .cm-search-global__empty .cm-empty-state {
            min-height: 0;
            border-radius: 0;
            background: transparent;
            box-shadow: none;
        }

        .cm-search-global__topline {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .cm-search-global__summary {
            display: flex;
            flex-wrap: wrap;
            gap: 0.55rem;
        }

        .cm-search-global__summary .cm-badge {
            background: transparent;
            color: #184264;
            border: 0;
        }

        .cm-search-global__grid {
            display: grid;
            gap: 1rem;
        }

        @media (max-width: 900px) {
            .cm-search-global__search {
                grid-template-columns: 1fr;
            }

            .cm-search-global__section-header,
            .cm-search-global__topline {
                align-items: flex-start;
            }

            .cm-search-global__row {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <div class="cm-search-global__shell">
        <section class="cm-search-global__hero">
            
            <form method="GET" class="cm-search-global__search">
                <input type="hidden" name="page" value="recherche_globale">
                <div class="cm-search-global__field-wrap">
                    <input type="text"
                           name="q"
                           id="cmRechercheGlobaleInput"
                           class="cm-search-global__input"
                           placeholder="Rechercher un nom, prénom, matricule, email..."
                           value="<?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?>"
                           autocomplete="off">
                    <div id="cmRechercheAutocomplete" class="cm-search-global__autocomplete" aria-label="Suggestions de recherche"></div>
                </div>
                <button type="submit" class="cm-btn is-primary is-lg cm-search-global__submit">
                    <i class="fas fa-search"></i>
                    <span>Rechercher</span>
                </button>
            </form>
        </section>

        <?php if ($query !== ''): ?>
            <div class="cm-search-global__topline">
                <div class="cm-search-global__status">
                    <i class="fas fa-circle-info" aria-hidden="true"></i>
                    <?= $totalResults ?> résultat(s) pour "<strong><?= htmlspecialchars($query, ENT_QUOTES, 'UTF-8') ?></strong>"
                </div>
                <div class="cm-search-global__summary" aria-label="Répartition des résultats">
                    <?php foreach ($categoryOrder as $key): ?>
                        <?php if (($categoryCounts[$key] ?? 0) > 0): ?>
                            <span class="cm-badge is-info is-sm">
                                <?= htmlspecialchars($categoryLabels[$key]['label'], ENT_QUOTES, 'UTF-8') ?>:
                                <?= (int) $categoryCounts[$key] ?>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if ($query === ''): ?>
            <div class="cm-search-global__empty">
                <?php cm_component('ui/empty-state', [
                    'title' => 'Lancez une recherche',
                    'message' => 'Saisissez un terme pour trouver rapidement un étudiant, un enseignant, un membre du personnel ou un utilisateur.',
                    'icon' => 'fa-search',
                ]); ?>
            </div>
        <?php elseif ($totalResults === 0): ?>
            <div class="cm-search-global__empty">
                <?php cm_component('ui/empty-state', [
                    'title' => 'Aucun résultat',
                    'message' => 'Aucune correspondance trouvée pour "' . htmlspecialchars($query, ENT_QUOTES, 'UTF-8') . '".',
                    'icon' => 'fa-magnifying-glass-minus',
                ]); ?>
            </div>
        <?php else: ?>
            <div class="cm-search-global__results">
                <?php foreach ($categoryOrder as $category): ?>
                    <?php $items = $results[$category] ?? []; ?>
                    <?php if (empty($items)) continue; ?>
                    <?php $catInfo = $categoryLabels[$category] ?? ['label' => $category, 'icon' => 'fa-circle', 'tone' => 'primary', 'link_prefix' => '#']; ?>

                    <section class="cm-search-global__section">
                        <header class="cm-search-global__section-header">
                            <h2 class="cm-search-global__section-title">
                                <i class="fas <?= htmlspecialchars($catInfo['icon'], ENT_QUOTES, 'UTF-8') ?>" aria-hidden="true"></i>
                                <span><?= htmlspecialchars($catInfo['label'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="cm-badge is-<?= htmlspecialchars($catInfo['tone'], ENT_QUOTES, 'UTF-8') ?> is-sm"><?= count($items) ?></span>
                            </h2>
                            <div class="cm-search-global__section-note">
                                <?= count($items) ?> élément<?= count($items) > 1 ? 's' : '' ?>
                            </div>
                        </header>

                        <div class="cm-search-global__list">
                            <?php foreach ($items as $item): ?>
                                <?php
                                    $id = $item['id'] ?? '';
                                    $link = $catInfo['link_prefix'] . urlencode((string) $id);
                                    $displayName = match ($category) {
                                        'etudiants' => trim((string) ($item['nom_etu'] ?? '') . ' ' . (string) ($item['prenom_etu'] ?? '')),
                                        'enseignants' => trim((string) ($item['nom_enseignant'] ?? '') . ' ' . (string) ($item['prenom_enseignant'] ?? '')),
                                        'personnel' => trim((string) ($item['nom_pers_admin'] ?? '') . ' ' . (string) ($item['prenom_pers_admin'] ?? '')),
                                        'utilisateurs' => (string) ($item['nom_utilisateur'] ?? $item['login'] ?? ''),
                                        default => (string) $id,
                                    };
                                    $sub = match ($category) {
                                        'etudiants' => (string) ($item['num_carte_etud'] ?? $item['id'] ?? ''),
                                        'enseignants' => (string) ($item['id_enseignant'] ?? $item['id'] ?? ''),
                                        'personnel' => (string) ($item['id'] ?? ''),
                                        'utilisateurs' => (string) ($item['login'] ?? ''),
                                        default => '',
                                    };
                                    $email = (string) ($item['email'] ?? $item['email_etu'] ?? $item['mail_enseignant'] ?? '');
                                ?>
                                <a class="cm-search-global__row cm-clickable-row" data-href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($link, ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="cm-search-global__row-main">
                                        <div class="cm-search-global__row-title">
                                            <strong><?= htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if ($sub !== ''): ?>
                                                <span class="cm-search-global__row-sub"><?= htmlspecialchars($sub, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="cm-search-global__row-meta">
                                            <?php if ($email !== ''): ?>
                                                <span><i class="fas fa-envelope" aria-hidden="true"></i> <?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></span>
                                            <?php endif; ?>
                                            <span><i class="fas fa-link" aria-hidden="true"></i> Ouvrir la fiche</span>
                                        </div>
                                    </div>
                                    <span class="cm-search-global__row-arrow" aria-hidden="true">
                                        <i class="fas fa-arrow-right"></i>
                                    </span>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </section>
                <?php endforeach; ?>
            </div>
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
                            div.className = 'cm-search-global__dropdown-category';
                            div.innerHTML = '<i class="fas ' + item.icon + '" aria-hidden="true"></i><span>' + item.label + '</span>';
                        } else {
                            div.className = 'cm-search-global__dropdown-item';
                            div.innerHTML = '<span class="cm-search-global__dropdown-main"><strong>' + item.label + '</strong><span>' + item.sub + '</span></span><i class="fas fa-chevron-right" aria-hidden="true"></i>';
                            div.addEventListener('click', function() {
                                window.location.href = item.url;
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

<style>
    .cm-search-global__dropdown-category {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.75rem 0.95rem;
        color: #5b7286;
        font-size: 0.72rem;
        font-weight: 800;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        background: transparent;
        border-bottom: 0;
    }

    .cm-search-global__dropdown-item {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.85rem 0.95rem;
        cursor: pointer;
        border-bottom: 0;
        transition: background-color 160ms ease;
    }

    .cm-search-global__dropdown-item:hover {
        background: rgba(31, 116, 191, 0.04);
    }

    .cm-search-global__dropdown-main {
        display: flex;
        flex-direction: column;
        gap: 0.2rem;
        min-width: 0;
    }

    .cm-search-global__dropdown-main strong {
        color: #173a5c;
        font-size: 0.93rem;
    }

    .cm-search-global__dropdown-main span {
        color: #6b8295;
        font-size: 0.8rem;
    }
</style>

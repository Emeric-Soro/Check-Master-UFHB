<?php
/**
 * Hub Commissions & Archives — Navigation par onglets
 * Regroupe : Archives documents, Archives étudiants, Archives comptes rendus,
 *            Fiche commission, Workflow
 */

$hubTabs = [
    ['id' => 'archives_documents',      'label' => 'Archives documents'],
    ['id' => 'archives_etudiants',      'label' => 'Archives étudiants'],
    ['id' => 'archive_comptes_rendus',  'label' => 'Archives comptes rendus'],
    ['id' => 'archives_memoires',       'label' => 'Mémoires'],
    ['id' => 'fiche_commission',        'label' => 'Fiche commission'],
    ['id' => 'workflow_validation',     'label' => 'Workflow'],
];

$requestedTab = (string) ($_GET['tab'] ?? 'archives_documents');
$allowedIds = array_column($hubTabs, 'id');
$currentTab = in_array($requestedTab, $allowedIds, true) ? $requestedTab : 'archives_documents';
$baseUrl = '?page=commissions_archives';
$partialsPath = __DIR__ . DIRECTORY_SEPARATOR;
$hubMeta = [
    'archives_documents' => [
        'title' => 'Centre d’archives de commission',
        'description' => 'Parcours unifié pour retrouver les pièces en base, filtrer les familles de documents et ouvrir directement les écrans métiers.',
    ],
    'archives_etudiants' => [
        'title' => 'Archives étudiantes',
        'description' => 'Lecture consolidée des dossiers archivés par étudiant, sans quitter le hub.',
    ],
    'archive_comptes_rendus' => [
        'title' => 'Comptes rendus archivés',
        'description' => 'Chaque ligne peut rouvrir la rédaction du compte rendu correspondant pour reprise ou correction.',
    ],
    'fiche_commission' => [
        'title' => 'Fiche commission',
        'description' => 'Synthèse de commission centralisée dans le même espace de travail.',
    ],
    'workflow_validation' => [
        'title' => 'Workflow de validation',
        'description' => 'Visualisation du cycle de validation et de ses jalons, sans navigation latérale parasite.',
    ],
    'archives_memoires' => [
        'title' => 'Archives des mémoires',
        'description' => 'Consultation des mémoires déposés par les étudiants, avec leurs évaluations et statuts de validation.',
    ],
];
$currentMeta = $hubMeta[$currentTab] ?? $hubMeta['archives_documents'];
?>
<section class="cm-prd3-screen">
    <style>
        .cm-hub-shell {
            display: grid;
            gap: 1rem;
        }

        .cm-hub-shell__header {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .cm-hub-shell__title {
            margin: 0;
            color: #12395c;
            font-size: 1.45rem;
            font-weight: 800;
        }

        .cm-hub-shell__text {
            max-width: 60rem;
            color: #5f7890;
            font-size: 0.96rem;
            line-height: 1.5;
        }

        .cm-hub-shell__hint {
            display: inline-flex;
            align-items: center;
            gap: 0.55rem;
            padding: 0.7rem 0.95rem;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.42);
            color: #1f5d90;
            font-weight: 700;
        }
    </style>
    <div class="cm-hub-shell">
    <nav class="cm-tab-nav" role="tablist" style="flex-wrap:wrap;">
        <?php foreach ($hubTabs as $tab): ?>
            <?php $isActive = $tab['id'] === $currentTab; ?>
            <a href="<?= $baseUrl ?>&tab=<?= urlencode($tab['id']) ?>"
               class="cm-tab-nav__item <?= $isActive ? 'is-active' : '' ?>"
               role="tab"
               aria-selected="<?= $isActive ? 'true' : 'false' ?>"
               style="text-decoration:none;">
                <?= htmlspecialchars($tab['label'], ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- Contenu de l'onglet actif -->
    <?php
    switch ($currentTab) {
        case 'archives_documents':
            include $partialsPath . 'v2/archives/archives_documents.php';
            break;
        case 'archives_etudiants':
            include $partialsPath . 'v2/archives/archives_etudiants.php';
            break;
        case 'archive_comptes_rendus':
            // La vue archive_comptes_rendus utilise le chemin redaction_compte_rendu
            include $partialsPath . 'redaction_compte_rendu/archives_compte_rendu_content.php';
            break;
        case 'fiche_commission':
            include $partialsPath . 'fiche_commission_content.php';
            break;
        case 'workflow_validation':
            include $partialsPath . 'workflow_validation_content.php';
            break;
        case 'archives_memoires':
            include $partialsPath . 'v2/archives/archives_memoires.php';
            break;
    }
    ?>
    </div>
</section>

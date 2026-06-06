<?php
/**
 * Comparaison de versions de documents
 * Table: documents (colonne version)
 * Permission: documents
 */
declare(strict_types=1);

require_once __DIR__ . '/../../app/utils/permissions_helper.php';

// @todo REFACTOR: Vue auto-contenue avec requêtes SQL directes (couplage vue ↔ données).
// Permission : accès via le hub outils_direction OU feature documents seule.
if (!canView('outils_direction') && !canView('documents')) {
    $_SESSION['error'] = "Acces refuse.";
    header('Location: ?page=dashboard');
    exit;
}

$db = Database::getConnection();

$documentId = (int) ($_GET['id_document'] ?? 0);
$version1 = (int) ($_GET['v1'] ?? 0);
$version2 = (int) ($_GET['v2'] ?? 0);

$document = null;
$versions = [];
$diffData = [];

// Si un ID document est fourni, recuperer ses versions
if ($documentId > 0) {
    // Infos du document
    $stmt = $db->prepare("SELECT * FROM documents WHERE id_document = ?");
    $stmt->execute([$documentId]);
    $document = $stmt->fetch(PDO::FETCH_ASSOC);

    // Recuperer toutes les versions de ce document (meme entite)
    if ($document) {
        $stmt = $db->prepare("SELECT * FROM documents
                              WHERE entite_type = ? AND entite_id = ? AND statut = 'actif'
                              ORDER BY version DESC");
        $stmt->execute([$document['entite_type'], $document['entite_id']]);
        $versions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

// Si deux versions sont specifiees, charger le detail
$v1Data = null;
$v2Data = null;
if ($version1 > 0 && $version2 > 0 && $document) {
    foreach ($versions as $v) {
        if ((int) ($v['version'] ?? 0) === $version1) {
            $v1Data = $v;
        }
        if ((int) ($v['version'] ?? 0) === $version2) {
            $v2Data = $v;
        }
    }

    // Calculer les differences
    if ($v1Data && $v2Data) {
        $compareFields = ['nom_fichier', 'taille_fichier', 'type_mime', 'sous_type'];
        foreach ($compareFields as $field) {
            $val1 = (string) ($v1Data[$field] ?? '');
            $val2 = (string) ($v2Data[$field] ?? '');
            if ($val1 !== $val2) {
                $diffData[] = [
                    'champ' => $field,
                    'v1' => $val1,
                    'v2' => $val2,
                    'modifie' => true,
                ];
            }
        }
        // Comparer les dates
        $dates = ['date_creation', 'date_modification'];
        foreach ($dates as $field) {
            $val1 = (string) ($v1Data[$field] ?? '');
            $val2 = (string) ($v2Data[$field] ?? '');
            $diffData[] = [
                'champ' => $field,
                'v1' => $val1 !== '' ? date('d/m/Y H:i:s', strtotime($val1)) : '-',
                'v2' => $val2 !== '' ? date('d/m/Y H:i:s', strtotime($val2)) : '-',
                'modifie' => $val1 !== $val2,
            ];
        }
    }
}
?>
<div class="cm-prd3-screen">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-title">
            <i class="fas fa-code-compare cm-text-primary"></i>
            Comparaison versions document
        </h1>
        <p class="cm-page-subtitle">Comparez les differentes versions d'un document</p>
    </div>

    <div class="cm-crud-wrapper">
        <!-- Selection du document -->
        <div class="cm-pole-superieur">
            <form method="GET" class="cm-form cm-grid-4">
                <input type="hidden" name="page" value="comparaison_versions_document">

                <?php
                // Liste des documents disponibles
                $stmt = $db->query("SELECT id_document, nom_fichier, entite_type, entite_id, version
                                    FROM documents WHERE statut = 'actif'
                                    ORDER BY date_creation DESC LIMIT 100");
                $docOptions = ['' => '-- Choisir --'];
                foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $doc) {
                    $label = htmlspecialchars((string) ($doc['nom_fichier'] ?? 'Sans nom'), ENT_QUOTES, 'UTF-8')
                           . ' (v' . (int) ($doc['version'] ?? 1) . ')';
                    $docOptions[(int) ($doc['id_document'] ?? 0)] = $label;
                }
                cm_component('form/select', [
                    'name' => 'id_document',
                    'label' => 'Document',
                    'options' => $docOptions,
                    'selected' => (string) $documentId,
                    'required' => true,
                ]);

                if (!empty($versions)) {
                    $verOptions = ['' => '-- Choisir --'];
                    foreach ($versions as $v) {
                        $verOptions[(int) ($v['version'] ?? 0)] = 'Version ' . (int) ($v['version'] ?? 0)
                            . ' (' . date('d/m/Y', strtotime((string) ($v['date_creation'] ?? ''))) . ')';
                    }
                    cm_component('form/select', [
                        'name' => 'v1',
                        'label' => 'Version 1 (ancienne)',
                        'options' => $verOptions,
                        'selected' => (string) $version1,
                        'required' => true,
                    ]);
                    cm_component('form/select', [
                        'name' => 'v2',
                        'label' => 'Version 2 (recente)',
                        'options' => $verOptions,
                        'selected' => (string) $version2,
                        'required' => true,
                    ]);
                } else {
                    echo '<div></div><div></div>';
                }
                ?>
                <div class="cm-form-group cm-flex-end">
                    <button type="submit" class="cm-btn is-primary is-sm cm-mt-6">
                        <i class="fas fa-search"></i> Comparer
                    </button>
                </div>
            </form>
        </div>

        <!-- Resultat de la comparaison -->
        <?php if ($v1Data && $v2Data): ?>
            <div class="cm-pole-inferieur cm-mt-4">
                <div class="cm-grid-2 cm-gap-4">
                    <!-- Version 1 (ancienne) -->
                    <div class="cm-card" style="border: 2px solid var(--cm-info);">
                        <div class="cm-card-header cm-p-3" style="background: var(--cm-info); color: white;">
                            <h3 class="cm-text-semibold">
                                <i class="fas fa-file"></i>
                                Version <?php echo (int) ($v1Data['version'] ?? 0); ?>
                                <small>(Ancienne)</small>
                            </h3>
                            <small><?php echo date('d/m/Y H:i', strtotime((string) ($v1Data['date_creation'] ?? ''))); ?></small>
                        </div>
                        <div class="cm-p-3">
                            <p><strong>Fichier:</strong>
                                <?php echo htmlspecialchars((string) ($v1Data['nom_fichier'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p><strong>Taille:</strong>
                                <?php
                                $size = (int) ($v1Data['taille_fichier'] ?? 0);
                                echo $size > 1024 ? round($size / 1024, 2) . ' Ko' : $size . ' o';
                                ?>
                            </p>
                            <p><strong>Type MIME:</strong>
                                <?php echo htmlspecialchars((string) ($v1Data['type_mime'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <?php if (!empty($v1Data['contenu'])): ?>
                                <div class="cm-mt-2">
                                    <strong>Apercu:</strong>
                                    <pre style="max-height: 300px; overflow: auto; background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 11px;">
<?php echo htmlspecialchars(substr((string) $v1Data['contenu'], 0, 2000), ENT_QUOTES, 'UTF-8'); ?>
                                    </pre>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Version 2 (recente) -->
                    <div class="cm-card" style="border: 2px solid var(--cm-success);">
                        <div class="cm-card-header cm-p-3" style="background: var(--cm-success); color: white;">
                            <h3 class="cm-text-semibold">
                                <i class="fas fa-file"></i>
                                Version <?php echo (int) ($v2Data['version'] ?? 0); ?>
                                <small>(Recente)</small>
                            </h3>
                            <small><?php echo date('d/m/Y H:i', strtotime((string) ($v2Data['date_creation'] ?? ''))); ?></small>
                        </div>
                        <div class="cm-p-3">
                            <p><strong>Fichier:</strong>
                                <?php echo htmlspecialchars((string) ($v2Data['nom_fichier'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <p><strong>Taille:</strong>
                                <?php
                                $size = (int) ($v2Data['taille_fichier'] ?? 0);
                                echo $size > 1024 ? round($size / 1024, 2) . ' Ko' : $size . ' o';
                                ?>
                            </p>
                            <p><strong>Type MIME:</strong>
                                <?php echo htmlspecialchars((string) ($v2Data['type_mime'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                            </p>
                            <?php if (!empty($v2Data['contenu'])): ?>
                                <div class="cm-mt-2">
                                    <strong>Apercu:</strong>
                                    <pre style="max-height: 300px; overflow: auto; background: #f8f9fa; padding: 8px; border-radius: 4px; font-size: 11px;">
<?php echo htmlspecialchars(substr((string) $v2Data['contenu'], 0, 2000), ENT_QUOTES, 'UTF-8'); ?>
                                    </pre>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Tableau des differences -->
                <?php if (!empty($diffData)): ?>
                    <div class="cm-card cm-mt-4">
                        <div class="cm-card-header cm-p-3 cm-bg-light">
                            <h3 class="cm-text-semibold">
                                <i class="fas fa-list"></i>
                                Differences detectees
                            </h3>
                        </div>
                        <div class="cm-p-3">
                            <table class="cm-data-table">
                                <thead>
                                    <tr>
                                        <th class="cm-data-table__th">Champ</th>
                                        <th class="cm-data-table__th">Version <?php echo (int) ($v1Data['version'] ?? 0); ?></th>
                                        <th class="cm-data-table__th">Version <?php echo (int) ($v2Data['version'] ?? 0); ?></th>
                                        <th class="cm-data-table__th is-center">Modifie</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diffData as $diff): ?>
                                        <tr class="cm-data-table__row<?php echo $diff['modifie'] ? ' cm-row-modified' : ''; ?>">
                                            <td class="cm-data-table__td">
                                                <strong><?php echo htmlspecialchars((string) ($diff['champ'] ?? ''), ENT_QUOTES, 'UTF-8'); ?></strong>
                                            </td>
                                            <td class="cm-data-table__td" style="background: <?php echo $diff['modifie'] ? '#fff5f5' : 'transparent'; ?>;">
                                                <?php echo htmlspecialchars((string) ($diff['v1'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="cm-data-table__td" style="background: <?php echo $diff['modifie'] ? '#f0fff4' : 'transparent'; ?>;">
                                                <?php echo htmlspecialchars((string) ($diff['v2'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?>
                                            </td>
                                            <td class="cm-data-table__td is-center">
                                                <?php if ($diff['modifie']): ?>
                                                    <span class="cm-badge is-warning">Modifie</span>
                                                <?php else: ?>
                                                    <span class="cm-badge is-success">Identique</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="cm-card cm-mt-4">
                        <div class="cm-p-3 cm-text-center cm-text-muted">
                            <i class="fas fa-check-circle cm-text-success cm-text-2xl"></i>
                            <p class="cm-mt-2">Aucune difference detectee entre ces deux versions.</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php elseif ($documentId > 0): ?>
            <!-- Document selectionne mais pas de comparaison -->
            <div class="cm-card cm-mt-4">
                <div class="cm-p-4 cm-text-center">
                    <i class="fas fa-info-circle cm-text-info cm-text-2xl"></i>
                    <p class="cm-mt-2">Selectionnez deux versions a comparer.</p>
                    <?php if (!empty($versions)): ?>
                        <p class="cm-text-muted"><?php echo count($versions); ?> version(s) disponible(s) pour ce document.</p>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

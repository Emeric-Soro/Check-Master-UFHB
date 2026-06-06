<?php
/**
 * Vue : Bibliothèque personnelle de documents
 * Route : ?page=parametres_generaux&action=documents
 */
$documents = $data['documents'] ?? [];
$typeFilter = $data['type_filter'] ?? '';
$anneeFilter = $data['annee_filter'] ?? null;
$annees = $data['annees'] ?? [];
$userGroup = $data['user_group'] ?? 0;

$typeLabels = [
    'rapport'       => 'Rapport',
    'compte_rendu'  => 'Compte-rendu',
    'pv_commission' => 'PV Commission',
    'pv_final'      => 'PV Final',
    'planning'      => 'Planning',
    'recu'          => 'Reçu',
    'bulletin'      => 'Bulletin',
];

$typeIcons = [
    'rapport'       => 'fa-file-alt',
    'compte_rendu'  => 'fa-clipboard',
    'pv_commission' => 'fa-users',
    'pv_final'      => 'fa-file-signature',
    'planning'      => 'fa-calendar-alt',
    'recu'          => 'fa-receipt',
    'bulletin'      => 'fa-file-invoice',
];

$statutBadge = [
    'valider'    => 'success',
    'valide'     => 'success',
    'en_attente' => 'warning',
    'rejeter'    => 'danger',
    'Finalise'   => 'success',
];

$statutLabels = [
    'valider'    => 'Validé',
    'valide'     => 'Validé',
    'en_attente' => 'En attente',
    'rejeter'    => 'Rejeté',
    'Finalise'   => 'Finalisé',
];

$baseUrl = '?page=parametres_generaux&action=documents';
?>

<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <div class="cm-crud-wrapper">

        <!-- En-tête -->
        <div class="cm-card" style="margin-bottom:1.25rem;">
            <div class="cm-card__header" style="display:flex;align-items:center;justify-content:space-between;">
                <h2 class="cm-card__title">
                    <i class="fas fa-folder-open" style="color:var(--cm-primary,#4f46e5);margin-right:.5rem;"></i>
                    Bibliothèque de documents
                </h2>
                <span class="cm-badge cm-badge--info" style="font-size:.8rem;">
                    <?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?>
                </span>
            </div>
        </div>

        <!-- Filtres -->
        <div class="cm-card" style="margin-bottom:1.25rem;">
            <div class="cm-card__body">
                <form method="get" style="display:flex;flex-wrap:wrap;align-items:flex-end;gap:1rem;">
                    <input type="hidden" name="page" value="parametres_generaux">
                    <input type="hidden" name="action" value="documents">

                    <div class="cm-form-group" style="flex:1;min-width:180px;">
                        <label for="type_doc" class="cm-form-label">
                            <i class="fas fa-tag" style="margin-right:.35rem;opacity:.5;"></i>Type de document
                        </label>
                        <select name="type_doc" id="type_doc" class="cm-form-control">
                            <option value="">Tous les types</option>
                            <?php foreach ($typeLabels as $code => $label): ?>
                                <option value="<?= $code ?>" <?= $typeFilter === $code ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cm-form-group" style="flex:1;min-width:180px;">
                        <label for="annee_id" class="cm-form-label">
                            <i class="fas fa-calendar" style="margin-right:.35rem;opacity:.5;"></i>Année académique
                        </label>
                        <select name="annee_id" id="annee_id" class="cm-form-control">
                            <option value="">Toutes les années</option>
                            <?php foreach ($annees as $annee): ?>
                                <?php $anneeId = (int) ($annee['id_annee_acad'] ?? 0); ?>
                                <option value="<?= $anneeId ?>" <?= (int) $anneeFilter === $anneeId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars((string) ($annee['libelle'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="cm-form-group" style="display:flex;gap:.5rem;">
                        <button type="submit" class="cm-btn is-primary">
                            <i class="fas fa-filter"></i> Filtrer
                        </button>
                        <a href="<?= $baseUrl ?>" class="cm-btn is-ghost">
                            <i class="fas fa-rotate-left"></i> Réinitialiser
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Liste des documents -->
        <div class="cm-card">
            <div class="cm-card__body" style="padding:0;">
                <?php if (empty($documents)): ?>
                    <div style="text-align:center;padding:3rem 1rem;">
                        <i class="fas fa-folder-open" style="font-size:2.5rem;color:var(--cm-gray-300,#d1d5db);margin-bottom:.75rem;display:block;"></i>
                        <p style="color:var(--cm-gray-400,#9ca3af);font-size:.95rem;margin:0;">Aucun document trouvé</p>
                        <p style="color:var(--cm-gray-400,#9ca3af);font-size:.85rem;margin:.25rem 0 0;">Essayez de modifier vos filtres de recherche.</p>
                    </div>
                <?php else: ?>
                    <div class="cm-table-responsive">
                        <table class="cm-data-table cm-data-table--hover">
                            <thead>
                                <tr>
                                    <th style="width:50px;"></th>
                                    <th>Type</th>
                                    <th>Titre</th>
                                    <?php if ($userGroup !== 13): ?>
                                        <th>Étudiant</th>
                                    <?php endif; ?>
                                    <th>Date</th>
                                    <th>Statut</th>
                                    <th class="is-center" style="width:100px;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($documents as $doc): ?>
                                    <?php
                                    $docType = $doc['type_doc'] ?? '';
                                    $docIcon = $typeIcons[$docType] ?? 'fa-file';
                                    $statut = $doc['statut'] ?? '';
                                    $badge = $statutBadge[$statut] ?? 'info';
                                    $statutLabel = $statutLabels[$statut] ?? $statut;
                                    $date = $doc['date_depot'] ?? null;
                                    ?>
                                    <tr>
                                        <td class="is-center">
                                            <div style="width:36px;height:36px;border-radius:8px;background:var(--cm-primary-light,#eef2ff);display:flex;align-items:center;justify-content:center;margin:0 auto;">
                                                <i class="fas <?= $docIcon ?>" style="color:var(--cm-primary,#4f46e5);font-size:.9rem;"></i>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="cm-badge cm-badge--info" style="font-size:.78rem;">
                                                <?= htmlspecialchars($typeLabels[$docType] ?? $docType, ENT_QUOTES, 'UTF-8') ?>
                                            </span>
                                        </td>
                                        <td>
                                            <strong style="font-size:.9rem;">
                                                <?= htmlspecialchars($doc['titre'] ?? 'Sans titre', ENT_QUOTES, 'UTF-8') ?>
                                            </strong>
                                        </td>
                                        <?php if ($userGroup !== 13): ?>
                                            <td style="font-size:.88rem;">
                                                <?= htmlspecialchars($doc['etudiant'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                            </td>
                                        <?php endif; ?>
                                        <td style="font-size:.88rem;white-space:nowrap;">
                                            <i class="fas fa-calendar-day" style="opacity:.4;margin-right:.3rem;font-size:.75rem;"></i>
                                            <?= $date ? htmlspecialchars(date('d/m/Y', strtotime($date)), ENT_QUOTES, 'UTF-8') : '—' ?>
                                        </td>
                                        <td>
                                            <?php if ($statut !== ''): ?>
                                                <span class="cm-badge cm-badge--<?= $badge ?>" style="font-size:.78rem;">
                                                    <?= htmlspecialchars($statutLabel, ENT_QUOTES, 'UTF-8') ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:var(--cm-gray-400,#9ca3af);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="is-center">
                                            <div class="cm-table-actions" style="display:flex;gap:.35rem;justify-content:center;">
                                                <button type="button"
                                                    class="cm-btn-action is-view" title="Voir le document"
                                                    onclick="CM.openDocViewer('<?= htmlspecialchars($docType, ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars((string) $doc['id_doc'], ENT_QUOTES, 'UTF-8') ?>', {title: '<?= htmlspecialchars($doc['titre'] ?? 'Document', ENT_QUOTES, 'UTF-8') ?>'})">
                                                    <i class="fas fa-eye" aria-hidden="true"></i>
                                                </button>
                                                <a href="?page=docviewer&type=<?= urlencode($docType) ?>&id=<?= urlencode((string) $doc['id_doc']) ?>&action=download"
                                                   class="cm-btn-action is-download" title="Télécharger">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pied de tableau -->
                    <div style="padding:.75rem 1.25rem;display:flex;align-items:center;justify-content:space-between;">
                        <span style="color:var(--cm-gray-400,#9ca3af);font-size:.82rem;">
                            <i class="fas fa-file" style="margin-right:.3rem;"></i>
                            <?= count($documents) ?> document<?= count($documents) > 1 ? 's' : '' ?> affiché<?= count($documents) > 1 ? 's' : '' ?>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>

    </div>
</section>

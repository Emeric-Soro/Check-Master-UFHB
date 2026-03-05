<?php
/**
 * Vue : Archives Documents
 * $data['documents']     - liste des documents archivés
 * $data['type_filter']   - filtre type actif ('rapport'|'compte_rendu'|null)
 *   Champs: type_doc, id_doc, chemin, titre, date_depot, taille, etudiant, num_carte_etud
 */
$documents   = $data['documents'] ?? [];
$type_filter = $data['type_filter'] ?? '';
?>

<div class="cm-archives-documents">

    <!-- En-tête -->
    <div class="cm-page-header cm-mb-5">
        <div>
            <h1 class="cm-page-title">
                <i class="fas fa-folder-open cm-mr-2"></i>Archives Documents
            </h1>
            <p class="cm-page-subtitle">
                Rapports et comptes rendus de l'année archivée
            </p>
        </div>
        <a href="?page=admin_historique" class="cm-btn cm-btn-outline cm-btn-sm">
            <i class="fas fa-arrow-left cm-mr-1"></i> Retour à Historique
        </a>
    </div>

    <!-- Filtres par type -->
    <div class="cm-card cm-mb-4">
        <div class="cm-card-body">
            <div class="cm-flex cm-gap-3 cm-flex-wrap cm-items-center">
                <span class="cm-text-muted cm-font-semibold">Type :</span>
                <a href="?page=archives_documents"
                   class="cm-btn cm-btn-sm <?= empty($type_filter) ? 'cm-btn-primary' : 'cm-btn-outline' ?>">
                    Tous (<?= count($documents) ?>)
                </a>
                <a href="?page=archives_documents&type=rapport"
                   class="cm-btn cm-btn-sm <?= $type_filter === 'rapport' ? 'cm-btn-primary' : 'cm-btn-outline' ?>">
                    <i class="fas fa-file-pdf cm-mr-1"></i>
                    Rapports
                    <?php $rapports = array_filter($documents, fn($d) => $d['type_doc'] === 'rapport'); ?>
                    (<?= count($rapports) ?>)
                </a>
                <a href="?page=archives_documents&type=compte_rendu"
                   class="cm-btn cm-btn-sm <?= $type_filter === 'compte_rendu' ? 'cm-btn-primary' : 'cm-btn-outline' ?>">
                    <i class="fas fa-file-alt cm-mr-1"></i>
                    Comptes rendus
                    <?php $crs = array_filter($documents, fn($d) => $d['type_doc'] === 'compte_rendu'); ?>
                    (<?= count($crs) ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Liste documents -->
    <?php
    $displayed = $documents;
    if (!empty($type_filter)) {
        $displayed = array_filter($documents, fn($d) => $d['type_doc'] === $type_filter);
    }
    ?>

    <?php if (empty($displayed)): ?>
        <div class="cm-card">
            <div class="cm-card-body cm-text-center cm-py-5">
                <div class="cm-icon-box cm-icon-box-lg cm-icon-box-secondary cm-mx-auto cm-mb-3">
                    <i class="fas fa-inbox fa-2x"></i>
                </div>
                <p class="cm-text-muted">Aucun document trouvé pour cette sélection.</p>
            </div>
        </div>
    <?php else: ?>
        <div class="cm-card">
            <div class="cm-card-body cm-p-0">
                <div class="cm-table-responsive">
                    <table class="cm-table cm-table-striped cm-table-hover">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Titre</th>
                                <th>Étudiant</th>
                                <th>Date de dépôt</th>
                                <th>Taille</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($displayed as $doc): ?>
                                <tr>
                                    <td>
                                        <?php if ($doc['type_doc'] === 'rapport'): ?>
                                            <span class="cm-badge cm-badge-primary">
                                                <i class="fas fa-file-pdf cm-mr-1"></i> Rapport
                                            </span>
                                        <?php else: ?>
                                            <span class="cm-badge cm-badge-info">
                                                <i class="fas fa-file-alt cm-mr-1"></i> Compte rendu
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <strong><?= htmlspecialchars($doc['titre'] ?? 'Sans titre') ?></strong>
                                    </td>
                                    <td>
                                        <a href="?page=fiche_etudiant_archive&matricule=<?= urlencode($doc['num_carte_etud']) ?>">
                                            <?= htmlspecialchars($doc['etudiant']) ?>
                                        </a>
                                        <br><small class="cm-text-muted"><?= htmlspecialchars($doc['num_carte_etud']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars(!empty($doc['date_depot']) ? date('d/m/Y', strtotime($doc['date_depot'])) : '—') ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($doc['taille'])): ?>
                                            <?= htmlspecialchars(number_format($doc['taille'] / 1024, 0)) ?> Ko
                                        <?php else: ?>
                                            <span class="cm-text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?page=visionneuse_document&id=<?= urlencode($doc['id_doc']) ?>&type=<?= urlencode($doc['type_doc']) ?>"
                                           class="cm-btn cm-btn-primary cm-btn-sm" title="Visualiser" target="_blank">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?page=telecharger_document&id=<?= urlencode($doc['id_doc']) ?>&type=<?= urlencode($doc['type_doc']) ?>"
                                           class="cm-btn cm-btn-outline cm-btn-sm" title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

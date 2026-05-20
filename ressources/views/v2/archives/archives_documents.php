<?php
/**
 * Vue : Archives Documents
 * $data['documents']     - liste des documents archivés
 * $data['type_filter']   - filtre type actif ('rapport'|'compte_rendu'|'pv_final'|null)
 * $data['annee_label']   - libellé année active
 */
$documents = is_array($data['documents'] ?? null) ? $data['documents'] : [];
$typeFilter = (string) ($data['type_filter'] ?? '');
$anneeLabel = trim((string) ($data['annee_label'] ?? ($_SESSION['archive_annee_libelle'] ?? '')));
$isHubContext = (string) ($_GET['page'] ?? '') === 'commissions_archives';
$baseUrl = $isHubContext
    ? '?page=commissions_archives&tab=archives_documents'
    : '?page=archives_documents';

$counts = [
    'all' => count($documents),
    'rapport' => 0,
    'compte_rendu' => 0,
    'pv_final' => 0,
];

foreach ($documents as $document) {
    $type = (string) ($document['type_doc'] ?? '');
    if (array_key_exists($type, $counts)) {
        $counts[$type]++;
    }
}

$displayed = $documents;
if ($typeFilter !== '') {
    $displayed = array_values(array_filter(
        $documents,
        static fn(array $document): bool => (string) ($document['type_doc'] ?? '') === $typeFilter
    ));
}

$formatDate = static function (?string $value): string {
    if (!is_string($value) || trim($value) === '') {
        return '—';
    }

    $timestamp = strtotime($value);
    if ($timestamp === false) {
        return '—';
    }

    return date('d/m/Y', $timestamp);
};

$formatSize = static function ($size): string {
    if (!is_numeric($size) || (float) $size <= 0) {
        return '—';
    }

    return number_format(((float) $size) / 1024, 0, ',', ' ') . ' Ko';
};

$buildFilterUrl = static function (string $type = '') use ($baseUrl): string {
    if ($type === '') {
        return $baseUrl;
    }

    return $baseUrl . '&type=' . urlencode($type);
};
?>

<style>
.cm-archives-documents {
    padding: 0;
}

.cm-archives-documents__topline {
    display: flex;
    justify-content: space-between;
    align-items: flex-end;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1rem;
}

.cm-archives-documents__heading {
    margin: 0;
    color: #12395c;
    font-size: 1.2rem;
    font-weight: 800;
}

.cm-archives-documents__year-label {
    color: #6b7f92;
    font-size: 0.85rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.cm-archives-documents__year-value {
    color: #163e63;
    font-size: 1.1rem;
    font-weight: 800;
}

.cm-archives-documents__stats {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: 0.75rem;
}

.cm-archives-documents__stat {
    padding: 0.9rem 0.95rem;
    border: 0;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.35);
    box-shadow: none;
}

.cm-archives-documents__stat-label {
    color: #688095;
    font-size: 0.84rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.04em;
}

.cm-archives-documents__stat-value {
    margin-top: 0.35rem;
    color: #163e63;
    font-size: 1.7rem;
    font-weight: 800;
}

.cm-archives-documents__toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    padding: 0.9rem 0;
    border-top: 1px solid rgba(24, 82, 128, 0.08);
    border-bottom: 1px solid rgba(24, 82, 128, 0.08);
}

.cm-archives-documents__filters {
    display: flex;
    gap: 0.75rem;
    flex-wrap: wrap;
}

.cm-archives-documents__filter {
    display: inline-flex;
    align-items: center;
    gap: 0.55rem;
    padding: 0.7rem 0.9rem;
    border: 0;
    border-radius: 14px;
    color: #1a5d91;
    background: rgba(255, 255, 255, 0.45);
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.18s ease, box-shadow 0.18s ease, background 0.18s ease;
}

.cm-archives-documents__filter:hover {
    transform: translateY(-1px);
    box-shadow: 0 10px 24px rgba(24, 82, 128, 0.10);
}

.cm-archives-documents__filter.is-active {
    color: #fff;
    border-color: transparent;
    background: linear-gradient(135deg, #2b97eb, #1f74bf);
    box-shadow: 0 12px 28px rgba(31, 116, 191, 0.24);
}

.cm-archives-documents__filter-count {
    display: inline-flex;
    min-width: 2.1rem;
    justify-content: center;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    background: rgba(21, 57, 92, 0.09);
    font-size: 0.82rem;
}

.cm-archives-documents__filter.is-active .cm-archives-documents__filter-count {
    background: rgba(255, 255, 255, 0.22);
}

.cm-archives-documents__toolbar-note {
    color: #5d7488;
    font-weight: 600;
}

.cm-archives-documents__table-card {
    overflow: hidden;
    background: transparent;
    border: 0;
    box-shadow: none;
}

.cm-archives-documents__table-head {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    padding: 1rem 0;
    border-bottom: 1px solid rgba(24, 82, 128, 0.08);
    background: transparent;
}

.cm-archives-documents__table-title {
    margin: 0;
    color: #15395c;
    font-size: 1rem;
    font-weight: 800;
}

.cm-archives-documents__table-meta {
    color: #6d8296;
    font-size: 0.92rem;
}

.cm-archives-documents__badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.7rem;
    border-radius: 999px;
    font-size: 0.82rem;
    font-weight: 700;
}

.cm-archives-documents__badge--rapport {
    background: rgba(36, 138, 236, 0.14);
    color: #135486;
}

.cm-archives-documents__badge--cr {
    background: rgba(22, 163, 74, 0.13);
    color: #166534;
}

.cm-archives-documents__badge--pv {
    background: rgba(217, 119, 6, 0.14);
    color: #9a3412;
}

.cm-archives-documents__title-cell {
    min-width: 260px;
}

.cm-archives-documents__title-main {
    color: #143a5d;
    font-weight: 700;
}

.cm-archives-documents__title-sub {
    margin-top: 0.25rem;
    color: #73889c;
    font-size: 0.88rem;
}

.cm-archives-documents__student-link {
    color: #16588b;
    font-weight: 700;
    text-decoration: none;
}

.cm-archives-documents__student-link:hover {
    text-decoration: underline;
}

.cm-archives-documents__student-code {
    margin-top: 0.25rem;
    color: #7a8d9f;
    font-size: 0.84rem;
}

.cm-archives-documents__actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.cm-archives-documents__row {
    cursor: pointer;
}

.cm-archives-documents__row:hover .cm-archives-documents__title-main {
    color: #1f74bf;
}

.cm-archives-documents__row.is-editable {
    background: rgba(31, 116, 191, 0.04);
}

.cm-archives-documents__empty {
    padding: 3rem 0;
    background: transparent;
    border: 0;
    box-shadow: none;
}

.cm-archives-documents__empty-icon {
    width: 68px;
    height: 68px;
    margin: 0 auto 1rem;
    border-radius: 20px;
    display: grid;
    place-items: center;
    background: linear-gradient(135deg, rgba(43, 151, 235, 0.12), rgba(31, 116, 191, 0.18));
    color: #1f74bf;
}

@media (max-width: 992px) {
    .cm-archives-documents__stats {
        grid-template-columns: 1fr 1fr;
    }
}

@media (max-width: 720px) {
    .cm-archives-documents__topline,
    .cm-archives-documents__stats {
        grid-template-columns: 1fr;
    }

    .cm-archives-documents__topline {
        align-items: flex-start;
    }

    .cm-archives-documents__table-head {
        align-items: flex-start;
        flex-direction: column;
    }
}
</style>

<div class="cm-archives-documents">
    <div class="cm-archives-documents__topline">
    </div>

    <div class="cm-archives-documents__toolbar cm-mb-4">
        <div class="cm-archives-documents__filters">
            <a href="<?= htmlspecialchars($buildFilterUrl(), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-archives-documents__filter <?= $typeFilter === '' ? 'is-active' : '' ?>">
                <i class="fas fa-layer-group"></i>
                Tous
                <span class="cm-archives-documents__filter-count"><?= number_format($counts['all']) ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildFilterUrl('rapport'), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-archives-documents__filter <?= $typeFilter === 'rapport' ? 'is-active' : '' ?>">
                <i class="fas fa-file-pdf"></i>
                Rapports
                <span class="cm-archives-documents__filter-count"><?= number_format($counts['rapport']) ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildFilterUrl('compte_rendu'), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-archives-documents__filter <?= $typeFilter === 'compte_rendu' ? 'is-active' : '' ?>">
                <i class="fas fa-file-lines"></i>
                Comptes rendus
                <span class="cm-archives-documents__filter-count"><?= number_format($counts['compte_rendu']) ?></span>
            </a>
            <a href="<?= htmlspecialchars($buildFilterUrl('pv_final'), ENT_QUOTES, 'UTF-8') ?>"
               class="cm-archives-documents__filter <?= $typeFilter === 'pv_final' ? 'is-active' : '' ?>">
                <i class="fas fa-gavel"></i>
                PV finaux
                <span class="cm-archives-documents__filter-count"><?= number_format($counts['pv_final']) ?></span>
            </a>
        </div>

        <div class="cm-archives-documents__toolbar-note">
            <?= number_format(count($displayed)) ?> document<?= count($displayed) > 1 ? 's' : '' ?> affiché<?= count($displayed) > 1 ? 's' : '' ?>
        </div>
    </div>

    <?php if ($displayed === []): ?>
        <div class="cm-archives-documents__empty">
            <div class="cm-text-center">
                <div class="cm-archives-documents__empty-icon">
                    <i class="fas fa-inbox fa-2x"></i>
                </div>
                <h3 class="cm-archives-documents__table-title">Aucun document pour ce filtre</h3>
                <p class="cm-text-muted">
                    Change le type affiché ou reviens à <a href="<?= htmlspecialchars($buildFilterUrl(), ENT_QUOTES, 'UTF-8') ?>">tous les documents</a>.
                </p>
            </div>
        </div>
    <?php else: ?>
        <div class="cm-archives-documents__table-card">
            <div class="cm-archives-documents__table-head">
                <div>
                    <h3 class="cm-archives-documents__table-title">Liste des documents archivés</h3>
                    <div class="cm-archives-documents__table-meta">
                        Clique sur une ligne pour ouvrir le document. Les comptes rendus renvoient vers la rédaction modifiable.
                    </div>
                </div>
                <div class="cm-archives-documents__table-meta">
                    Tri par date de dépôt décroissante
                </div>
            </div>

            <div class="cm-table-responsive">
                <table class="cm-table cm-table-striped cm-table-hover">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Document</th>
                            <th>Étudiant</th>
                            <th>Date</th>
                            <th>Taille</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($displayed as $doc): ?>
                            <?php
                            $docType = (string) ($doc['type_doc'] ?? '');
                            $docId = (string) ($doc['id_doc'] ?? '');
                            $docTitle = (string) ($doc['titre'] ?? 'Sans titre');
                            $studentCode = trim((string) ($doc['num_carte_etud'] ?? ''));
                            $studentName = trim((string) ($doc['etudiant'] ?? '—'));
                            $typeLabel = 'Compte rendu';
                            $typeClass = 'cm-archives-documents__badge--cr';
                            $typeIcon = 'fa-file-lines';
                            $rowHref = '?page=visionneuse_document&type=' . urlencode($docType) . '&id=' . urlencode($docId);
                            $rowHint = 'Ouvrir le document';

                            if ($docType === 'rapport') {
                                $typeLabel = 'Rapport';
                                $typeClass = 'cm-archives-documents__badge--rapport';
                                $typeIcon = 'fa-file-pdf';
                            } elseif ($docType === 'pv_final') {
                                $typeLabel = 'PV final';
                                $typeClass = 'cm-archives-documents__badge--pv';
                                $typeIcon = 'fa-gavel';
                            } elseif ($docType === 'compte_rendu') {
                                $rowHref = '?page=redaction_compte_rendu&id_CR=' . urlencode($docId);
                                $rowHint = 'Ouvrir ce compte rendu dans la rédaction';
                            }
                            ?>
                            <tr class="cm-archives-documents__row <?= $docType === 'compte_rendu' ? 'is-editable' : '' ?>"
                                data-href="<?= htmlspecialchars($rowHref, ENT_QUOTES, 'UTF-8') ?>"
                                data-row-hint="<?= htmlspecialchars($rowHint, ENT_QUOTES, 'UTF-8') ?>"
                                tabindex="0">
                                <td>
                                    <span class="cm-archives-documents__badge <?= $typeClass ?>">
                                        <i class="fas <?= $typeIcon ?>"></i>
                                        <?= htmlspecialchars($typeLabel, ENT_QUOTES, 'UTF-8') ?>
                                    </span>
                                </td>
                                <td class="cm-archives-documents__title-cell">
                                    <div class="cm-archives-documents__title-main"><?= htmlspecialchars($docTitle, ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="cm-archives-documents__title-sub">
                                        Référence #<?= htmlspecialchars($docId, ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($studentCode !== ''): ?>
                                        <a class="cm-archives-documents__student-link"
                                           data-row-ignore="true"
                                           href="?page=fiche_etudiant_archive&id=<?= urlencode($studentCode) ?>">
                                            <?= htmlspecialchars($studentName !== '' ? $studentName : $studentCode, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                        <div class="cm-archives-documents__student-code"><?= htmlspecialchars($studentCode, ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php else: ?>
                                        <span class="cm-text-muted"><?= htmlspecialchars($studentName !== '' ? $studentName : '—', ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($formatDate($doc['date_depot'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                <td><?= htmlspecialchars($formatSize($doc['taille'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                <td>
                                    <div class="cm-archives-documents__actions">
                                        <?php if ($docType === 'compte_rendu'): ?>
                                            <button
                                                type="button"
                                                class="cm-btn cm-btn-primary cm-btn-sm"
                                                data-row-ignore="true"
                                                title="Modifier ce compte rendu"
                                                onclick="window.location.href='?page=redaction_compte_rendu&id_CR=<?= urlencode($docId) ?>'">
                                                <i class="fas fa-pen-to-square"></i>
                                            </button>
                                        <?php else: ?>
                                            <button
                                                type="button"
                                                class="cm-btn cm-btn-primary cm-btn-sm"
                                                data-row-ignore="true"
                                                title="Visualiser"
                                                onclick="window.open('?page=visionneuse_document&type=<?= urlencode($docType) ?>&id=<?= urlencode($docId) ?>', '_blank', 'noopener')">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        <?php endif; ?>
                                        <a href="?page=telecharger_document&type=<?= urlencode($docType) ?>&id=<?= urlencode($docId) ?>"
                                           class="cm-btn cm-btn-outline cm-btn-sm"
                                           data-row-ignore="true"
                                           title="Télécharger">
                                            <i class="fas fa-download"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    function shouldIgnore(target) {
        return !!(target && target.closest('[data-row-ignore="true"]'));
    }

    document.querySelectorAll('.cm-archives-documents__row[data-href]').forEach(function (row) {
        row.addEventListener('click', function (event) {
            if (shouldIgnore(event.target)) {
                return;
            }
            const href = row.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });

        row.addEventListener('keydown', function (event) {
            if (event.key !== 'Enter' && event.key !== ' ') {
                return;
            }
            event.preventDefault();
            const href = row.getAttribute('data-href');
            if (href) {
                window.location.href = href;
            }
        });
    });
})();
</script>

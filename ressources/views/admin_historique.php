<?php
$currentTab = $GLOBALS['currentTab'] ?? 'students';
$students = $GLOBALS['students'] ?? [];
$juries = $GLOBALS['juries'] ?? [];
$academicYears = $GLOBALS['academicYears'] ?? [];
$filters = $GLOBALS['filters'] ?? [];
$totalPages = $GLOBALS['totalPages'] ?? 1;
$currentPage = $GLOBALS['currentPage'] ?? 1;
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';
?>

<style>
:root {
    --cm-primary: #1a5276;
    --cm-primary-strong: #17405e;
    --cm-primary-soft: #e8f0f6;
    --cm-accent: #ff8c00;
    --cm-muted: #64748b;
    --cm-border: #e2e8f0;
    --cm-surface: #ffffff;
    --cm-bg: #f8fafc;
}

.archive-shell { color: #0f172a; font-family: 'Poppins', 'Inter', system-ui, -apple-system, sans-serif; }
.page-header { background: linear-gradient(120deg, #ffffff 0%, #f5f9ff 100%); border: 1px solid var(--cm-border); border-radius: 16px; padding: 20px 24px; box-shadow: 0 12px 30px rgba(17,24,39,.06); }
.page-kicker { color: var(--cm-accent); font-weight: 600; font-size: 0.95rem; letter-spacing: 0.02em; }
.page-title { margin: 0.25rem 0; font-size: 1.75rem; color: var(--cm-primary); font-weight: 700; }
.page-subtitle { color: var(--cm-muted); font-size: 0.95rem; }

.card { background: var(--cm-surface); border: 1px solid var(--cm-border); border-radius: 16px; box-shadow: 0 10px 24px rgba(17,24,39,.06); }
.card-body { padding: 1.5rem; }
.section-title { font-size: 1.2rem; font-weight: 700; color: var(--cm-primary); }
.text-muted { color: var(--cm-muted); }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; border: 1px solid transparent; border-radius: 12px; padding: 0.65rem 1.2rem; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn-primary { background: linear-gradient(135deg, var(--cm-primary), var(--cm-primary-strong)); color: #fff; box-shadow: 0 10px 20px rgba(26,82,118,0.2); }
.btn-primary:hover { background: var(--cm-primary-strong); }
.btn-ghost { background: #f8fafc; color: var(--cm-primary); border-color: var(--cm-border); }
.btn-ghost:hover { background: #eef2f7; border-color: var(--cm-primary); color: var(--cm-primary); }
.btn-secondary { background: linear-gradient(135deg, #f97316, #f59e0b); color: #fff; box-shadow: 0 10px 20px rgba(249,115,22,0.18); }

.badge { display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 999px; padding: 0.35rem 0.75rem; font-weight: 600; font-size: 0.85rem; border: 1px solid transparent; }
.status-valider { background: #ecfdf3; color: #027a48; border-color: #bbf7d0; }
.status-rejeter { background: #fef2f2; color: #b91c1c; border-color: #fecdd3; }
.status-en_cours { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

.tab-nav { display: flex; gap: 1.5rem; padding: 0 1.5rem; border-bottom: 1px solid var(--cm-border); }
.tab-button { background: none; border: none; padding: 0.95rem 0; position: relative; color: var(--cm-muted); font-weight: 700; cursor: pointer; }
.tab-button::after { content: ''; position: absolute; left: 0; right: 0; bottom: -1px; height: 3px; background: transparent; border-radius: 999px; }
.tab-button.active { color: var(--cm-primary); }
.tab-button.active::after { background: var(--cm-primary); }

.tab-content { display: none; }
.tab-content.active { display: block; padding: 1.25rem 1.5rem 1.5rem; }

.filter-card { background: var(--cm-bg); border: 1px solid var(--cm-border); border-radius: 12px; padding: 1.1rem 1.25rem; }
.input-label { display: block; font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 0.35rem; }
.input-control { width: 100%; border: 1px solid var(--cm-border); border-radius: 10px; padding: 0.7rem 0.85rem; font-size: 0.95rem; background: #fff; }
.input-control:focus { outline: none; border-color: var(--cm-primary); box-shadow: 0 0 0 3px rgba(26,82,118,0.1); }

.table-shell { overflow-x: auto; border-radius: 14px; border: 1px solid var(--cm-border); background: var(--cm-surface); }
.table { width: 100%; border-collapse: collapse; }
.table th { background: var(--cm-bg); color: var(--cm-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; padding: 0.85rem 1rem; border-bottom: 1px solid var(--cm-border); text-align: left; }
.table td { padding: 0.9rem 1rem; border-bottom: 1px solid var(--cm-border); font-size: 0.95rem; color: #0f172a; }
.table tr:hover td { background: #f8fbff; }

.pagination { display: flex; gap: 0.35rem; justify-content: center; margin-top: 1.25rem; }
.page-link { display: inline-flex; align-items: center; justify-content: center; padding: 0.65rem 0.95rem; border-radius: 10px; border: 1px solid var(--cm-border); color: #0f172a; background: #fff; font-weight: 600; text-decoration: none; }
.page-link.active { background: var(--cm-primary); border-color: var(--cm-primary); color: #fff; box-shadow: 0 10px 20px rgba(26,82,118,0.2); }
.page-link:hover { background: #f1f5f9; border-color: var(--cm-primary); color: var(--cm-primary); }

.notice { position: fixed; top: 1rem; right: 1rem; z-index: 50; padding: 1rem 1.25rem; border-radius: 12px; box-shadow: 0 12px 30px rgba(0,0,0,.12); color: #fff; min-width: 280px; }
.notice.success { background: linear-gradient(135deg, #16a34a, #15803d); }
.notice.error { background: linear-gradient(135deg, #ef4444, #b91c1c); }
</style>

<div class="archive-shell space-y-6">
    <?php if ($messageSuccess): ?>
        <div class="notice success">
            <div class="flex items-center gap-2">
                <i class="fas fa-check-circle"></i>
                <span><?php echo htmlspecialchars($messageSuccess); ?></span>
            </div>
        </div>
        <script>setTimeout(() => document.querySelector('.notice.success')?.remove(), 5000);</script>
    <?php endif; ?>

    <?php if ($messageErreur): ?>
        <div class="notice error">
            <div class="flex items-center gap-2">
                <i class="fas fa-exclamation-circle"></i>
                <span><?php echo htmlspecialchars($messageErreur); ?></span>
            </div>
        </div>
        <script>setTimeout(() => document.querySelector('.notice.error')?.remove(), 5000);</script>
    <?php endif; ?>

    <div class="page-header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <div class="page-kicker">Archivage & Historique</div>
                <h1 class="page-title flex items-center gap-3">
                    <i class="fas fa-archive text-primary"></i>
                    Historique et archivage
                </h1>
                <p class="page-subtitle">Consultation et gestion des archives des soutenances passées.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <span class="badge status-en_cours">
                    <i class="fas fa-file-csv"></i>
                    CSV / XLSX
                </span>
                <span class="badge status-valider">
                    <i class="fas fa-shield-alt"></i>
                    Import sécurisé
                </span>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body flex flex-col lg:flex-row gap-6 lg:items-center">
            <div class="flex-1 space-y-3">
                <h2 class="section-title">Importer des archives</h2>
                <p class="text-muted leading-relaxed">
                    Déposez un fichier CSV ou Excel respectant les 20 colonnes attendues. Les entités manquantes
                    (enseignants, années académiques, salles, entreprises) sont créées automatiquement.
                </p>
                <div class="flex flex-wrap gap-2 text-sm">
                    <span class="badge status-valider"><i class="fas fa-lock"></i> Validation et nettoyage</span>
                    <span class="badge status-en_cours"><i class="fas fa-cloud-upload-alt"></i> Transactionnelle</span>
                </div>
            </div>
            <form action="?page=admin_historique&action=import" method="POST" enctype="multipart/form-data" class="w-full lg:max-w-xl space-y-3">
                <label class="input-label" for="archive_file">Fichier CSV ou Excel</label>
                <input type="file" id="archive_file" name="archive_file" accept=".csv,.xlsx,.xls" required class="input-control">
                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-cloud-upload-alt"></i>
                        Importer
                    </button>
                    <a href="?page=admin_historique&action=import_form" class="btn btn-ghost">
                        <i class="fas fa-info-circle"></i>
                        Voir le gabarit
                    </a>
                </div>
                <p class="text-muted text-sm flex items-center gap-2">
                    <i class="fas fa-circle-info text-primary"></i>
                    Format: ANNEE_ACAD, MATRICULE, NOM, PRENOMS, THEME, ...
                </p>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="tab-nav">
            <button class="tab-button <?php echo $currentTab === 'students' ? 'active' : ''; ?>" onclick="switchTab(event, 'students')">
                <i class="fas fa-user-graduate mr-2"></i>
                Historique des étudiants
            </button>
            <button class="tab-button <?php echo $currentTab === 'jury' ? 'active' : ''; ?>" onclick="switchTab(event, 'jury')">
                <i class="fas fa-users mr-2"></i>
                Historique des jurys
            </button>
        </div>

        <!-- Tab Content: Students -->
        <div id="students-tab" class="tab-content <?php echo $currentTab === 'students' ? 'active' : ''; ?>">
            <div class="space-y-4">
                <div class="filter-card">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4 items-end">
                        <input type="hidden" name="page" value="admin_historique">
                        <input type="hidden" name="tab" value="students">

                        <div>
                            <label class="input-label">Année académique</label>
                            <select name="annee" class="input-control">
                                <option value="">Toutes les années</option>
                                <?php foreach ($academicYears as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($filters['annee'] ?? '') === $year ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="input-label">Statut</label>
                            <select name="statut" class="input-control">
                                <option value="">Tous les statuts</option>
                                <option value="valider" <?php echo ($filters['statut'] ?? '') === 'valider' ? 'selected' : ''; ?>>Validé</option>
                                <option value="rejeter" <?php echo ($filters['statut'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Rejeté</option>
                                <option value="en_cours" <?php echo ($filters['statut'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                            </select>
                        </div>

                        <div>
                            <label class="input-label">Recherche</label>
                            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search'] ?? ''); ?>" placeholder="Nom, prénom, matricule..." class="input-control">
                        </div>

                        <div class="flex">
                            <button type="submit" class="btn btn-primary w-full justify-center">
                                <i class="fas fa-search"></i>
                                Filtrer
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-shell">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Matricule</th>
                            <th>Nom</th>
                            <th>Prénoms</th>
                            <th>Thème</th>
                            <th>Entreprise</th>
                            <th>Année</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-6 text-muted">
                                    <div class="flex flex-col items-center gap-2">
                                        <i class="fas fa-inbox text-3xl"></i>
                                        <p>Aucun étudiant trouvé</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $student): ?>
                                <tr class="cursor-pointer" onclick="window.location.href='?page=admin_historique&action=view_student&num_etu=<?php echo urlencode($student['matricule']); ?>'">
                                    <td class="font-semibold"><?php echo htmlspecialchars($student['matricule']); ?></td>
                                    <td><?php echo htmlspecialchars($student['nom']); ?></td>
                                    <td><?php echo htmlspecialchars($student['prenoms']); ?></td>
                                    <td><?php echo htmlspecialchars(substr($student['theme'] ?? '', 0, 60)) . (strlen($student['theme'] ?? '') > 60 ? '...' : ''); ?></td>
                                    <td><?php echo htmlspecialchars($student['entreprise'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($student['annee_academique'] ?? 'N/A'); ?></td>
                                    <td>
                                        <span class="badge status-<?php echo $student['statut'] ?? 'en_cours'; ?>">
                                            <?php
                                            $statutLabel = [
                                                'valider' => 'Validé',
                                                'rejeter' => 'Rejeté',
                                                'en_cours' => 'En cours'
                                            ];
                                            echo $statutLabel[$student['statut'] ?? 'en_cours'] ?? 'N/A';
                                            ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="?page=admin_historique&action=view_student&num_etu=<?php echo urlencode($student['matricule']); ?>" class="text-primary hover:text-primary-light font-semibold">
                                            <i class="fas fa-eye mr-1"></i>Voir
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?page=admin_historique&tab=students&p=<?php echo $i; ?>&annee=<?php echo urlencode($filters['annee'] ?? ''); ?>&statut=<?php echo urlencode($filters['statut'] ?? ''); ?>&search=<?php echo urlencode($filters['search'] ?? ''); ?>"
                               class="page-link <?php echo $i === $currentPage ? 'active' : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tab Content: Jury -->
        <div id="jury-tab" class="tab-content <?php echo $currentTab === 'jury' ? 'active' : ''; ?>">
            <div class="space-y-4">
                <div class="filter-card">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
                        <input type="hidden" name="page" value="admin_historique">
                        <input type="hidden" name="tab" value="jury">

                        <div>
                            <label class="input-label">Année académique</label>
                            <select name="annee" class="input-control">
                                <option value="">Toutes les années</option>
                                <?php foreach ($academicYears as $year): ?>
                                    <option value="<?php echo htmlspecialchars($year); ?>" <?php echo ($filters['annee'] ?? '') === $year ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($year); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="md:col-span-2 flex">
                            <button type="submit" class="btn btn-primary w-full justify-center">
                                <i class="fas fa-search"></i>
                                Filtrer
                            </button>
                        </div>
                    </form>
                </div>

                <div class="table-shell">
                    <table class="table">
                        <thead>
                        <tr>
                            <th>Date</th>
                            <th>Étudiant</th>
                            <th>Président</th>
                            <th>Examinateur</th>
                            <th>Encadreur</th>
                            <th>Directeur</th>
                            <th>Année</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php if (empty($juries)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-6 text-muted">
                                    <div class="flex flex-col items-center gap-2">
                                        <i class="fas fa-inbox text-3xl"></i>
                                        <p>Aucun jury trouvé</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($juries as $jury): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($jury['date_soutenance'] ?? 'now'))); ?></td>
                                    <td><?php echo htmlspecialchars($jury['etudiant'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($jury['president'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($jury['examinateur'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($jury['encadreur'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($jury['directeur'] ?? 'N/A'); ?></td>
                                    <td><?php echo htmlspecialchars($jury['annee_academique'] ?? 'N/A'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(event, tab) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tab);
    window.history.pushState({}, '', url);

    document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
    event.currentTarget.classList.add('active');

    document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
    document.getElementById(tab + '-tab').classList.add('active');
}
</script>

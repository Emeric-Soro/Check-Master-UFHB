<?php
$importSummary = $GLOBALS['importSummary'] ?? null;
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
    --cm-success: #16a34a;
    --cm-danger: #dc2626;
}

.archive-shell { color: #0f172a; font-family: 'Poppins', 'Inter', system-ui, -apple-system, sans-serif; }
.page-header { background: linear-gradient(120deg, #ffffff 0%, #f5f9ff 100%); border: 1px solid var(--cm-border); border-radius: 16px; padding: 20px 24px; box-shadow: 0 12px 30px rgba(17,24,39,.06); }
.page-kicker { color: var(--cm-accent); font-weight: 600; font-size: 0.95rem; letter-spacing: 0.02em; }
.page-title { margin: 0.25rem 0; font-size: 1.75rem; color: var(--cm-primary); font-weight: 700; }
.page-subtitle { color: var(--cm-muted); font-size: 0.95rem; }

.card { background: var(--cm-surface); border: 1px solid var(--cm-border); border-radius: 16px; box-shadow: 0 10px 24px rgba(17,24,39,.06); }
.card-body { padding: 1.5rem; }
.section-title { font-size: 1.1rem; font-weight: 700; color: var(--cm-primary); display: flex; align-items: center; gap: 0.65rem; }
.text-muted { color: var(--cm-muted); }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; border: 1px solid transparent; border-radius: 12px; padding: 0.65rem 1.2rem; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn-primary { background: linear-gradient(135deg, var(--cm-primary), var(--cm-primary-strong)); color: #fff; box-shadow: 0 10px 20px rgba(26,82,118,0.2); }
.btn-primary:hover { background: var(--cm-primary-strong); }
.btn-secondary { background: linear-gradient(135deg, #f97316, #f59e0b); color: #fff; box-shadow: 0 10px 20px rgba(249,115,22,0.18); }
.btn-ghost { background: #f8fafc; color: var(--cm-primary); border-color: var(--cm-border); }
.btn-ghost:hover { background: #eef2f7; border-color: var(--cm-primary); color: var(--cm-primary); }

.stat-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 1rem; }
.stat-card { padding: 1.25rem; border-radius: 14px; border: 1px solid var(--cm-border); background: var(--cm-bg); text-align: center; }
.stat-value { font-size: 2.25rem; font-weight: 800; }
.stat-success { color: var(--cm-success); }
.stat-error { color: var(--cm-danger); }
.stat-label { margin-top: 0.35rem; color: var(--cm-muted); font-weight: 600; letter-spacing: 0.02em; text-transform: uppercase; font-size: 0.85rem; }

.list-stack { display: grid; gap: 0.65rem; max-height: 24rem; overflow-y: auto; }
.list-item { display: flex; gap: 0.65rem; padding: 0.75rem; border-radius: 10px; border: 1px solid var(--cm-border); background: var(--cm-surface); align-items: flex-start; }
.list-item.success { border-color: #bbf7d0; background: #ecfdf3; }
.list-item.error { border-color: #fecdd3; background: #fef2f2; }
.list-icon { margin-top: 0.2rem; }

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
                <div class="page-kicker">Import d'archives</div>
                <h1 class="page-title flex items-center gap-3">
                    <i class="fas fa-file-import text-primary"></i>
                    Résultat de l'import
                </h1>
                <p class="page-subtitle">Synthèse des lignes traitées et liens rapides pour corriger ou relancer.</p>
            </div>
            <a href="?page=admin_historique" class="btn btn-ghost">
                <i class="fas fa-arrow-left"></i>
                Retour à l'historique
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body space-y-4">
            <div class="section-title">
                <i class="fas fa-chart-pie"></i>
                Résumé
            </div>
            <div class="stat-grid">
                <div class="stat-card">
                    <div class="stat-value stat-success"><?php echo $importSummary['total_success'] ?? 0; ?></div>
                    <div class="stat-label">Réussites</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value stat-error"><?php echo $importSummary['total_errors'] ?? 0; ?></div>
                    <div class="stat-label">Erreurs</div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($importSummary['successes'])): ?>
    <div class="card">
        <div class="card-body space-y-3">
            <div class="section-title">
                <i class="fas fa-check-circle"></i>
                Imports réussis
            </div>
            <div class="list-stack">
                <?php foreach ($importSummary['successes'] as $success): ?>
                <div class="list-item success">
                    <i class="fas fa-check text-green-600 list-icon"></i>
                    <span class="text-muted"><?php echo htmlspecialchars($success); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($importSummary['errors'])): ?>
    <div class="card">
        <div class="card-body space-y-3">
            <div class="section-title">
                <i class="fas fa-exclamation-triangle"></i>
                Erreurs rencontrées
            </div>
            <div class="list-stack">
                <?php foreach ($importSummary['errors'] as $error): ?>
                <div class="list-item error">
                    <i class="fas fa-times text-red-600 list-icon"></i>
                    <span class="text-muted"><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex flex-wrap justify-center gap-3">
        <a href="?page=admin_historique" class="btn btn-primary">
            <i class="fas fa-list"></i>
            Voir l'historique
        </a>
        <a href="?page=admin_historique&action=import_form" class="btn btn-secondary">
            <i class="fas fa-upload"></i>
            Nouvel import
        </a>
    </div>
</div>

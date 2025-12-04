<?php
$studentFile = $GLOBALS['studentFile'] ?? null;
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';

if (!$studentFile) {
    header('Location: ?page=admin_historique');
    exit;
}
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
.section-title { font-size: 1.1rem; font-weight: 700; color: var(--cm-primary); display: flex; align-items: center; gap: 0.65rem; }
.text-muted { color: var(--cm-muted); }

.btn { display: inline-flex; align-items: center; gap: 0.5rem; border: 1px solid transparent; border-radius: 12px; padding: 0.65rem 1.2rem; font-weight: 600; cursor: pointer; text-decoration: none; }
.btn-primary { background: linear-gradient(135deg, var(--cm-primary), var(--cm-primary-strong)); color: #fff; box-shadow: 0 10px 20px rgba(26,82,118,0.2); }
.btn-primary:hover { background: var(--cm-primary-strong); }
.btn-ghost { background: #f8fafc; color: var(--cm-primary); border-color: var(--cm-border); }
.btn-ghost:hover { background: #eef2f7; border-color: var(--cm-primary); color: var(--cm-primary); }

.badge { display: inline-flex; align-items: center; gap: 0.35rem; border-radius: 999px; padding: 0.35rem 0.75rem; font-weight: 600; font-size: 0.85rem; border: 1px solid transparent; }
.status-valider { background: #ecfdf3; color: #027a48; border-color: #bbf7d0; }
.status-rejeter { background: #fef2f2; color: #b91c1c; border-color: #fecdd3; }
.status-en_cours { background: #fff7ed; color: #c2410c; border-color: #fed7aa; }

.info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 1rem; }
.input-label { display: block; font-size: 0.9rem; font-weight: 600; color: #0f172a; margin-bottom: 0.35rem; }
.input-control { width: 100%; border: 1px solid var(--cm-border); border-radius: 10px; padding: 0.7rem 0.85rem; font-size: 0.95rem; background: #fff; }
.input-control:focus { outline: none; border-color: var(--cm-primary); box-shadow: 0 0 0 3px rgba(26,82,118,0.1); }
.input-control[readonly] { background: #f9fafb; color: var(--cm-muted); }
.textarea-control { min-height: 96px; }

.table-shell { overflow-x: auto; border-radius: 14px; border: 1px solid var(--cm-border); background: var(--cm-surface); }
.table { width: 100%; border-collapse: collapse; }
.table th { background: var(--cm-bg); color: var(--cm-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.03em; padding: 0.85rem 1rem; border-bottom: 1px solid var(--cm-border); text-align: left; }
.table td { padding: 0.9rem 1rem; border-bottom: 1px solid var(--cm-border); font-size: 0.95rem; color: #0f172a; }
.table tr:hover td { background: #f8fbff; }

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
                <div class="page-kicker">Fiche archive</div>
                <h1 class="page-title flex items-center gap-3">
                    <i class="fas fa-user-graduate text-primary"></i>
                    <?php echo htmlspecialchars($studentFile['nom_etu'] . ' ' . $studentFile['prenom_etu']); ?>
                </h1>
                <p class="page-subtitle">Matricule <?php echo htmlspecialchars($studentFile['num_etu']); ?> — mise à jour des informations archivées.</p>
            </div>
            <a href="?page=admin_historique" class="btn btn-ghost">
                <i class="fas fa-arrow-left"></i>
                Retour à l'historique
            </a>
        </div>
    </div>

    <form method="POST" action="?page=admin_historique&action=update_student" class="space-y-4">
        <input type="hidden" name="num_etu" value="<?php echo htmlspecialchars($studentFile['num_etu']); ?>">

        <div class="card">
            <div class="card-body space-y-4">
                <div class="section-title">
                    <i class="fas fa-id-card"></i>
                    Informations personnelles
                </div>
                <div class="info-grid">
                    <div>
                        <label class="input-label">Matricule</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['num_etu']); ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Nom</label>
                        <input type="text" name="nom_etu" class="input-control" value="<?php echo htmlspecialchars($studentFile['nom_etu']); ?>">
                    </div>
                    <div>
                        <label class="input-label">Prénoms</label>
                        <input type="text" name="prenom_etu" class="input-control" value="<?php echo htmlspecialchars($studentFile['prenom_etu']); ?>">
                    </div>
                    <div>
                        <label class="input-label">Email</label>
                        <input type="email" name="email_etu" class="input-control" value="<?php echo htmlspecialchars($studentFile['email_etu']); ?>">
                    </div>
                </div>
            </div>
        </div>

        <?php if ($studentFile['stage']): ?>
        <div class="card">
            <div class="card-body space-y-4">
                <div class="section-title">
                    <i class="fas fa-briefcase"></i>
                    Informations de stage
                </div>
                <div class="info-grid">
                    <div>
                        <label class="input-label">Entreprise</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['stage']['lib_entreprise'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Maître de stage</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['stage']['encadrant_entreprise'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Date début</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['stage']['date_debut_stage'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Date fin</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['stage']['date_fin_stage'] ?? 'N/A'); ?>" readonly>
                    </div>
                </div>
                <div>
                    <label class="input-label">Sujet de stage</label>
                    <textarea class="input-control textarea-control" readonly><?php echo htmlspecialchars($studentFile['stage']['sujet_stage'] ?? 'N/A'); ?></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($studentFile['rapport']): ?>
        <div class="card">
            <div class="card-body space-y-4">
                <div class="section-title">
                    <i class="fas fa-file-alt"></i>
                    Thème et validation
                </div>
                <div class="info-grid">
                    <div class="md:col-span-2">
                        <label class="input-label">Thème</label>
                        <input type="text" name="theme_rapport" class="input-control" value="<?php echo htmlspecialchars($studentFile['rapport']['theme_rapport'] ?? ''); ?>">
                    </div>
                    <div>
                        <label class="input-label">Date de validation commission</label>
                        <input type="text" class="input-control" value="<?php echo !empty($studentFile['rapport']['date_validation']) ? htmlspecialchars(date('d/m/Y', strtotime($studentFile['rapport']['date_validation']))) : 'N/A'; ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Statut</label>
                        <select name="statut_rapport" class="input-control">
                            <option value="valider" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'valider' ? 'selected' : ''; ?>>Validé</option>
                            <option value="rejeter" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Rejeté</option>
                            <option value="en_cours" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="input-label">Observations</label>
                    <textarea class="input-control textarea-control" readonly><?php echo htmlspecialchars($studentFile['rapport']['commentaire_validation'] ?? 'Aucune observation'); ?></textarea>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($studentFile['encadrement']): ?>
        <div class="card">
            <div class="card-body space-y-4">
                <div class="section-title">
                    <i class="fas fa-chalkboard-teacher"></i>
                    Encadrement
                </div>
                <div class="info-grid">
                    <?php if ($studentFile['encadrement']['encadrant']): ?>
                    <div>
                        <label class="input-label">Encadreur pédagogique</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['encadrement']['encadrant']['nom_enseignant'] . ' ' . $studentFile['encadrement']['encadrant']['prenom_enseignant']); ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Email encadreur</label>
                        <input type="email" class="input-control" value="<?php echo htmlspecialchars($studentFile['encadrement']['encadrant']['mail_enseignant'] ?? ''); ?>" readonly>
                    </div>
                    <?php endif; ?>

                    <?php if ($studentFile['encadrement']['directeur']): ?>
                    <div>
                        <label class="input-label">Directeur de mémoire</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['encadrement']['directeur']['nom_enseignant'] . ' ' . $studentFile['encadrement']['directeur']['prenom_enseignant']); ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Email directeur</label>
                        <input type="email" class="input-control" value="<?php echo htmlspecialchars($studentFile['encadrement']['directeur']['mail_enseignant'] ?? ''); ?>" readonly>
                    </div>
                    <?php else: ?>
                    <div class="md:col-span-2">
                        <label class="input-label">Directeur de mémoire</label>
                        <input type="text" class="input-control" value="Non assigné (données antérieures)" readonly>
                        <p class="text-sm text-muted mt-2 flex items-center gap-2">
                            <i class="fas fa-info-circle text-primary"></i>
                            Le rôle de directeur de mémoire n'était pas encore en vigueur pour cette année.
                        </p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($studentFile['soutenance']): ?>
        <div class="card">
            <div class="card-body space-y-4">
                <div class="section-title">
                    <i class="fas fa-graduation-cap"></i>
                    Soutenance
                </div>
                <div class="info-grid">
                    <div>
                        <label class="input-label">Date de soutenance</label>
                        <input type="text" class="input-control" value="<?php echo !empty($studentFile['soutenance']['date_soutenance']) ? htmlspecialchars(date('d/m/Y', strtotime($studentFile['soutenance']['date_soutenance']))) : 'N/A'; ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Heure</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['soutenance']['heure_soutenance'] ?? 'N/A'); ?>" readonly>
                    </div>
                    <div>
                        <label class="input-label">Salle</label>
                        <input type="text" class="input-control" value="<?php echo htmlspecialchars($studentFile['soutenance']['lib_salle'] ?? 'N/A'); ?>" readonly>
                    </div>
                </div>

                <?php if (!empty($studentFile['soutenance']['jury_members'])): ?>
                <div class="space-y-3">
                    <div class="text-sm font-semibold text-muted uppercase">Composition du jury</div>
                    <div class="info-grid">
                        <?php
                        $juryMembers = explode('|', $studentFile['soutenance']['jury_members']);
                        foreach ($juryMembers as $member):
                            if (empty(trim($member))) continue;
                            [$name, $role] = explode(':', $member);
                        ?>
                        <div>
                            <label class="input-label"><?php echo htmlspecialchars($role); ?></label>
                            <input type="text" class="input-control" value="<?php echo htmlspecialchars($name); ?>" readonly>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <?php if (!empty($studentFile['soutenance']['notes'])): ?>
                <div class="space-y-3">
                    <div class="text-sm font-semibold text-muted uppercase">Notes et évaluations</div>
                    <div class="table-shell">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Critère</th>
                                    <th>Note</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($studentFile['soutenance']['notes'] as $note): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($note['lib_critere'] ?? 'N/A'); ?></td>
                                    <td class="font-semibold"><?php echo htmlspecialchars($note['note']); ?>/20</td>
                                    <td><?php echo htmlspecialchars(date('d/m/Y', strtotime($note['date_eval']))); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="flex justify-end gap-3">
            <a href="?page=admin_historique" class="btn btn-ghost">
                <i class="fas fa-times"></i>
                Annuler
            </a>
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-save"></i>
                Enregistrer les modifications
            </button>
        </div>
    </form>
</div>

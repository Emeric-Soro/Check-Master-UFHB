<?php
/**
 * Fiche Étudiant Archive - Vue conforme au Design System CheckMaster
 * Route: ?page=admin_historique&action=view_student&num_etu=XXX
 */
$studentFile = $GLOBALS['studentFile'] ?? null;
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';
$messageErreur = $GLOBALS['messageErreur'] ?? '';

if (!$studentFile) {
    header('Location: ?page=admin_historique&error=not_found');
    exit;
}

// Helper pour les mentions
function getMention($note)
{
    if ($note === null)
        return null;
    if ($note >= 16)
        return ['text' => 'Très Bien', 'class' => 'cm-badge-purple'];
    if ($note >= 14)
        return ['text' => 'Bien', 'class' => 'cm-badge-info'];
    if ($note >= 12)
        return ['text' => 'Assez Bien', 'class' => 'cm-badge-success'];
    if ($note >= 10)
        return ['text' => 'Passable', 'class' => 'cm-badge-warning'];
    return ['text' => 'Ajourné', 'class' => 'cm-badge-danger'];
}

// Calcul moyennes
$soutenanceNote = $studentFile['soutenance']['note_soutenance'] ?? null;
$mention = getMention($soutenanceNote);
$m1_avg = $studentFile['moyenne_m1'] ?? null;
$m2_s1_avg = $studentFile['moyenne_m2_s1'] ?? null;
$memo_avg = $soutenanceNote;

// Moyenne générale pondérée: (M1*2 + M2S1*3 + Memo*3) / 8
$m1_calc = $m1_avg ?? 0;
$m2_s1_calc = $m2_s1_avg ?? 0;
$memo_calc = $memo_avg ?? 0;
$general_avg = ($m1_calc * 2 + $m2_s1_calc * 3 + $memo_calc * 3) / 8;
if ($m1_avg === null && $m2_s1_avg === null && $memo_avg === null) {
    $general_avg = null;
}
$general_mention = getMention($general_avg);

// Timeline data
$timeline_steps = [
    ['title' => 'Inscription', 'date' => $studentFile['date_inscription'] ?? null, 'icon' => 'fa-user-check', 'completed' => true],
    ['title' => 'Début de Stage', 'date' => $studentFile['stage']['date_debut_stage'] ?? null, 'icon' => 'fa-briefcase', 'completed' => !empty($studentFile['stage']['date_debut_stage'])],
    ['title' => 'Validation Thème', 'date' => $studentFile['rapport']['date_validation'] ?? null, 'icon' => 'fa-check-double', 'completed' => ($studentFile['rapport']['statut_rapport'] ?? '') === 'valider'],
    ['title' => 'Soutenance', 'date' => $studentFile['soutenance']['date_soutenance'] ?? null, 'icon' => 'fa-graduation-cap', 'completed' => !empty($studentFile['soutenance']['date_soutenance'])],
    ['title' => 'Diplômé', 'date' => ($soutenanceNote && $soutenanceNote >= 10) ? ($studentFile['soutenance']['date_soutenance'] ?? date('Y-m-d')) : null, 'icon' => 'fa-award', 'completed' => ($soutenanceNote && $soutenanceNote >= 10)],
];

// Déterminer le dernier step complété
$lastCompletedIndex = -1;
foreach ($timeline_steps as $i => $step) {
    if ($step['completed'] && !empty($step['date'])) {
        $lastCompletedIndex = $i;
    }
}
?>

<div class="cm-prd3-screen">
    <!-- Messages -->
    <?php if ($messageSuccess): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess, 'dismissible' => true]); ?>
    <?php endif; ?>
    <?php if ($messageErreur): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur, 'dismissible' => true]); ?>
    <?php endif; ?>

    <!-- Header avec navigation -->
    <div class="cm-crud-wrapper">
        <div class="cm-barre-intermediaire cm-mb-4">
            <a href="?page=admin_historique" class="cm-btn is-light is-sm">
                <i class="fas fa-arrow-left" aria-hidden="true"></i>
                <span>Retour à la liste</span>
            </a>
        </div>

        <!-- Carte principale étudiant -->
        <?php ob_start(); ?>
        <div class="cm-flex cm-flex-wrap cm-gap-4 cm-items-start">
            <div class="cm-flex-grow">
                <h1 class="cm-text-2xl cm-font-bold cm-text-gray-900">
                    <?php echo htmlspecialchars($studentFile['nom_etu'] . ' ' . $studentFile['prenom_etu']); ?>
                </h1>
                <p class="cm-text-gray-500 cm-mt-1">
                    Matricule: <span
                        class="cm-font-medium"><?php echo htmlspecialchars($studentFile['num_etu']); ?></span>
                </p>
                <p class="cm-text-gray-500">
                    Année: <span
                        class="cm-font-medium"><?php echo htmlspecialchars($studentFile['soutenance']['annee_academique'] ?? $studentFile['annee_academique'] ?? 'N/A'); ?></span>
                </p>
            </div>
            <div class="cm-text-right">
                <?php if ($mention): ?>
                    <div class="cm-badge <?php echo $mention['class']; ?> cm-text-lg">
                        <i class="fas fa-check-circle cm-mr-1"></i>
                        Diplômé - <?php echo $mention['text']; ?> (<?php echo number_format($soutenanceNote, 2); ?>/20)
                    </div>
                <?php else: ?>
                    <div class="cm-badge cm-badge-secondary cm-text-lg">
                        Status: <?php echo htmlspecialchars($studentFile['rapport']['statut_rapport'] ?? 'En cours'); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php
        cm_component('crud/form-pole', [
            'title' => 'Fiche Étudiant',
            'icon' => 'fa-id-card',
            'content' => ob_get_clean(),
        ]);
        ?>

        <!-- Timeline -->
        <?php ob_start(); ?>
        <div class="cm-timeline-vertical">
            <?php foreach ($timeline_steps as $i => $step):
                $isCompleted = $i <= $lastCompletedIndex;
                $hasDate = !empty($step['date']) && !str_starts_with($step['date'], '0000-00-00');
                ?>
                <div class="cm-timeline-item <?php echo $isCompleted ? 'cm-timeline-completed' : 'cm-timeline-pending'; ?>">
                    <div class="cm-timeline-marker">
                        <i class="fas <?php echo $step['icon']; ?>"></i>
                    </div>
                    <div class="cm-timeline-content">
                        <h4 class="cm-timeline-title">
                            <?php echo htmlspecialchars($step['title']); ?>
                            <?php if ($isCompleted): ?>
                                <span class="cm-badge cm-badge-success cm-ml-2">Terminé</span>
                            <?php else: ?>
                                <span class="cm-badge cm-badge-secondary cm-ml-2">En attente</span>
                            <?php endif; ?>
                        </h4>
                        <p class="cm-timeline-date">
                            <?php if ($hasDate): ?>
                                <i class="far fa-calendar-alt cm-mr-1"></i>
                                <?php echo date('d/m/Y', strtotime($step['date'])); ?>
                            <?php else: ?>
                                <span class="cm-text-muted">Date non spécifiée</span>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php
        cm_component('card', [
            'title' => 'Parcours',
            'icon' => 'fa-route',
            'content' => ob_get_clean(),
        ]);
        ?>

        <!-- Grid 2 colonnes -->
        <div class="cm-grid-2 cm-gap-4 cm-mt-4">
            <!-- Colonne gauche -->
            <div class="cm-stack cm-gap-4">
                <!-- Encadrement -->
                <?php if (!empty($studentFile['encadrement']['directeur']) || !empty($studentFile['encadrement']['encadrant'])): ?>
                    <?php ob_start(); ?>
                    <dl class="cm-dl">
                        <?php if (!empty($studentFile['encadrement']['directeur'])): ?>
                            <div class="cm-dl-item">
                                <dt>Directeur</dt>
                                <dd><?php echo htmlspecialchars($studentFile['encadrement']['directeur']['nom_enseignant'] . ' ' . $studentFile['encadrement']['directeur']['prenom_enseignant']); ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($studentFile['encadrement']['encadrant'])): ?>
                            <div class="cm-dl-item">
                                <dt>Encadrant</dt>
                                <dd><?php echo htmlspecialchars($studentFile['encadrement']['encadrant']['nom_enseignant'] . ' ' . $studentFile['encadrement']['encadrant']['prenom_enseignant']); ?>
                                </dd>
                            </div>
                        <?php endif; ?>
                    </dl>
                    <?php
                    cm_component('card', [
                        'title' => 'Encadrement',
                        'icon' => 'fa-chalkboard-teacher',
                        'content' => ob_get_clean(),
                    ]);
                    ?>
                <?php endif; ?>

                <!-- Stage -->
                <?php if (!empty($studentFile['stage'])): ?>
                    <?php ob_start(); ?>
                    <dl class="cm-dl">
                        <div class="cm-dl-item">
                            <dt>Entreprise</dt>
                            <dd><?php echo htmlspecialchars($studentFile['stage']['lib_entreprise'] ?? 'N/A'); ?></dd>
                        </div>
                        <div class="cm-dl-item">
                            <dt>Maître de stage</dt>
                            <dd><?php
                            $maitreNom = $studentFile['stage']['encadrant_nom'] ?? '';
                            $maitrePrenom = $studentFile['stage']['encadrant_prenom'] ?? '';
                            $maitreComplet = trim($maitreNom . ' ' . $maitrePrenom);
                            echo htmlspecialchars($maitreComplet !== '' ? $maitreComplet : 'N/A');
                            ?></dd>
                        </div>
                        <div class="cm-dl-item">
                            <dt>Période</dt>
                            <dd>
                                <?php
                                $debut = $studentFile['stage']['date_debut_stage'] ?? null;
                                $fin = $studentFile['stage']['date_fin_stage'] ?? null;
                                if ($debut && !str_starts_with($debut, '0000-00-00')) {
                                    echo 'Du ' . date('d/m/Y', strtotime($debut));
                                    if ($fin && !str_starts_with($fin, '0000-00-00')) {
                                        echo ' au ' . date('d/m/Y', strtotime($fin));
                                    }
                                } else {
                                    echo 'N/A';
                                }
                                ?>
                            </dd>
                        </div>
                    </dl>
                    <?php
                    cm_component('card', [
                        'title' => 'Stage',
                        'icon' => 'fa-briefcase',
                        'content' => ob_get_clean(),
                    ]);
                    ?>
                <?php endif; ?>
            </div>

            <!-- Colonne droite -->
            <div class="cm-stack cm-gap-4">
                <!-- Soutenance -->
                <?php if (!empty($studentFile['soutenance'])): ?>
                    <?php ob_start(); ?>
                    <dl class="cm-dl">
                        <?php
                        $rawDateSout = $studentFile['soutenance']['date_soutenance'] ?? null;
                        $rawHeureSout = $studentFile['soutenance']['heure_soutenance'] ?? null;
                        $hasDateSout = $rawDateSout && !str_starts_with($rawDateSout, '0000-00-00');
                        $hasHeureSout = $rawHeureSout && stripos($rawHeureSout, '00:00:00') === false;
                        ?>
                        <div class="cm-dl-item">
                            <dt>Date</dt>
                            <dd><?php echo $hasDateSout ? date('d/m/Y', strtotime($rawDateSout)) : 'N/A'; ?></dd>
                        </div>
                        <div class="cm-dl-item">
                            <dt>Heure</dt>
                            <dd><?php echo $hasHeureSout ? date('H:i', strtotime($rawHeureSout)) : 'N/A'; ?></dd>
                        </div>
                        <div class="cm-dl-item">
                            <dt>Salle</dt>
                            <dd><?php echo htmlspecialchars($studentFile['soutenance']['lib_salle'] ?? 'N/A'); ?></dd>
                        </div>
                    </dl>
                    <?php
                    cm_component('card', [
                        'title' => 'Soutenance',
                        'icon' => 'fa-graduation-cap',
                        'content' => ob_get_clean(),
                    ]);
                    ?>
                <?php endif; ?>

                <!-- Jury -->
                <?php if (!empty($studentFile['soutenance']['jury_members'])): ?>
                    <?php ob_start(); ?>
                    <dl class="cm-dl">
                        <?php
                        $juryMembers = explode('|', $studentFile['soutenance']['jury_members']);
                        foreach ($juryMembers as $member):
                            if (empty(trim($member)))
                                continue;
                            $parts = explode(':', $member);
                            $name = trim($parts[0] ?? 'N/A');
                            $role = trim($parts[1] ?? 'N/A');
                            ?>
                            <div class="cm-dl-item">
                                <dt><?php echo htmlspecialchars($role); ?></dt>
                                <dd><?php echo htmlspecialchars($name); ?></dd>
                            </div>
                        <?php endforeach; ?>
                    </dl>
                    <?php
                    cm_component('card', [
                        'title' => 'Composition du Jury',
                        'icon' => 'fa-users',
                        'content' => ob_get_clean(),
                    ]);
                    ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Relevé de notes -->
        <?php ob_start(); ?>
        <div class="cm-stack cm-gap-4">
            <!-- M1 -->
            <div class="cm-progress-row">
                <div class="cm-progress-label">
                    <span>Moyenne Master 1 (Annexe 3)</span>
                    <span class="cm-progress-value"><?php echo $m1_avg ? number_format($m1_avg, 2) : 'N/A'; ?></span>
                </div>
                <div class="cm-progress-bar">
                    <div class="cm-progress-fill <?php echo getMention($m1_avg)['class'] ?? 'cm-bg-gray'; ?>"
                        style="width: <?php echo $m1_avg ? ($m1_avg / 20) * 100 : 0; ?>%"></div>
                </div>
            </div>

            <!-- M2 S1 -->
            <div class="cm-progress-row">
                <div class="cm-progress-label">
                    <span>Moyenne Master 2 - S1 (Annexe 2)</span>
                    <span
                        class="cm-progress-value"><?php echo $m2_s1_avg ? number_format($m2_s1_avg, 2) : 'N/A'; ?></span>
                </div>
                <div class="cm-progress-bar">
                    <div class="cm-progress-fill <?php echo getMention($m2_s1_avg)['class'] ?? 'cm-bg-gray'; ?>"
                        style="width: <?php echo $m2_s1_avg ? ($m2_s1_avg / 20) * 100 : 0; ?>%"></div>
                </div>
            </div>

            <!-- Mémoire -->
            <div class="cm-progress-row">
                <div class="cm-progress-label">
                    <span>Note du Mémoire / Soutenance</span>
                    <span
                        class="cm-progress-value"><?php echo $memo_avg ? number_format($memo_avg, 2) : 'N/A'; ?></span>
                </div>
                <div class="cm-progress-bar">
                    <div class="cm-progress-fill <?php echo getMention($memo_avg)['class'] ?? 'cm-bg-gray'; ?>"
                        style="width: <?php echo $memo_avg ? ($memo_avg / 20) * 100 : 0; ?>%"></div>
                </div>
            </div>

            <!-- Ligne séparateur -->
            <div class="cm-divider"></div>

            <!-- Moyenne générale -->
            <div class="cm-progress-row cm-progress-row-highlight">
                <div class="cm-progress-label">
                    <span class="cm-font-bold">MOYENNE GÉNÉRALE</span>
                    <span
                        class="cm-progress-value cm-text-xl <?php echo $general_mention['class'] ? str_replace('cm-badge-', 'cm-text-', $general_mention['class']) : 'cm-text-gray'; ?>">
                        <?php echo $general_avg ? number_format($general_avg, 2) : 'N/A'; ?>
                    </span>
                </div>
                <div class="cm-progress-bar cm-progress-bar-lg">
                    <div class="cm-progress-fill <?php echo $general_mention['class'] ?? 'cm-bg-gray'; ?>"
                        style="width: <?php echo $general_avg ? ($general_avg / 20) * 100 : 0; ?>%"></div>
                </div>
            </div>
        </div>
        <?php
        cm_component('card', [
            'title' => 'Relevé de Notes',
            'icon' => 'fa-file-alt',
            'content' => ob_get_clean(),
        ]);
        ?>

        <!-- Notes détaillées -->
        <?php if (!empty($studentFile['soutenance']['notes'])): ?>
            <?php ob_start(); ?>
            <div class="cm-table-wrapper">
                <table class="cm-data-table">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Critère d'Évaluation</th>
                            <th class="cm-data-table__th">Note</th>
                            <th class="cm-data-table__th">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($studentFile['soutenance']['notes'] as $note): ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td"><?php echo htmlspecialchars($note['lib_critere'] ?? 'N/A'); ?>
                                </td>
                                <td class="cm-data-table__td cm-font-semibold">
                                    <?php echo htmlspecialchars($note['note']); ?>/20
                                </td>
                                <td class="cm-data-table__td">
                                    <?php echo date('d/m/Y', strtotime($note['date_eval'])); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php
            cm_component('card', [
                'title' => 'Notes Détaillées de Soutenance',
                'icon' => 'fa-list-ol',
                'content' => ob_get_clean(),
            ]);
            ?>
        <?php endif; ?>

        <!-- Formulaire de modification -->
        <?php if (canEdit()): ?>
            <form method="POST" action="?page=admin_historique&action=update_student" class="cm-mt-4">
                <?php cm_component('form/csrf-token'); ?>
                <input type="hidden" name="num_etu" value="<?php echo htmlspecialchars($studentFile['num_etu']); ?>">

                <?php ob_start(); ?>
                <div class="cm-grid-2 cm-gap-4">
                    <div class="cm-form-group">
                        <label class="cm-form-label">Nom</label>
                        <input type="text" name="nom_etu" value="<?php echo htmlspecialchars($studentFile['nom_etu']); ?>"
                            class="cm-input">
                    </div>
                    <div class="cm-form-group">
                        <label class="cm-form-label">Prénom</label>
                        <input type="text" name="prenom_etu"
                            value="<?php echo htmlspecialchars($studentFile['prenom_etu']); ?>" class="cm-input">
                    </div>
                </div>

                <div class="cm-form-group cm-mt-3">
                    <label class="cm-form-label">Email</label>
                    <input type="email" name="email_etu" value="<?php echo htmlspecialchars($studentFile['email_etu']); ?>"
                        class="cm-input">
                </div>

                <?php if (!empty($studentFile['rapport'])): ?>
                    <div class="cm-form-group cm-mt-3">
                        <label class="cm-form-label">Thème du mémoire</label>
                        <input type="text" name="theme_rapport"
                            value="<?php echo htmlspecialchars($studentFile['rapport']['theme_rapport'] ?? ''); ?>"
                            class="cm-input">
                    </div>
                    <div class="cm-form-group cm-mt-3">
                        <label class="cm-form-label">Statut de validation</label>
                        <select name="statut_rapport" class="cm-select">
                            <option value="valider" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'valider' ? 'selected' : ''; ?>>Validé</option>
                            <option value="rejeter" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Rejeté</option>
                            <option value="en_cours" <?php echo ($studentFile['rapport']['statut_rapport'] ?? '') === 'en_cours' ? 'selected' : ''; ?>>En cours</option>
                        </select>
                    </div>
                <?php endif; ?>

                <div class="cm-form-buttons cm-mt-4">
                    <a href="?page=admin_historique" class="cm-btn is-light">Annuler</a>
                    <button type="submit" class="cm-btn is-success">
                        <i class="fas fa-save cm-mr-1"></i>
                        Enregistrer les modifications
                    </button>
                </div>
                <?php
                $formContent = ob_get_clean();
                cm_component('card', [
                    'title' => 'Modifier les informations',
                    'icon' => 'fa-edit',
                    'content' => $formContent,
                ]);
                ?>
            </form>
        <?php endif; ?>
    </div>
</div>

<style>
    /* Timeline Design System */
    .cm-timeline-vertical {
        position: relative;
        padding-left: var(--cm-space-8);
    }

    .cm-timeline-vertical::before {
        content: '';
        position: absolute;
        left: calc(var(--cm-space-4) - 1px);
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--cm-border);
    }

    .cm-timeline-item {
        position: relative;
        padding-bottom: var(--cm-space-6);
    }

    .cm-timeline-marker {
        position: absolute;
        left: calc(var(--cm-space-4) * -1 - var(--cm-space-4));
        width: var(--cm-space-8);
        height: var(--cm-space-8);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: var(--cm-bg-white);
        border: 2px solid var(--cm-border);
        font-size: var(--cm-text-sm);
    }

    .cm-timeline-completed .cm-timeline-marker {
        border-color: var(--cm-success);
        color: var(--cm-success);
        background: var(--cm-success-light);
    }

    .cm-timeline-pending .cm-timeline-marker {
        border-color: var(--cm-gray-300);
        color: var(--cm-gray-400);
    }

    .cm-timeline-content {
        padding-left: var(--cm-space-4);
    }

    .cm-timeline-title {
        font-weight: 600;
        margin-bottom: var(--cm-space-1);
    }

    .cm-timeline-date {
        font-size: var(--cm-text-sm);
        color: var(--cm-text-muted);
    }

    /* Description List */
    .cm-dl {
        display: flex;
        flex-direction: column;
        gap: var(--cm-space-3);
    }

    .cm-dl-item {
        display: flex;
        gap: var(--cm-space-4);
    }

    .cm-dl-item dt {
        font-weight: 500;
        color: var(--cm-text-muted);
        min-width: 120px;
    }

    .cm-dl-item dd {
        color: var(--cm-text);
    }

    /* Progress Bars */
    .cm-progress-row {
        display: flex;
        flex-direction: column;
        gap: var(--cm-space-2);
    }

    .cm-progress-label {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .cm-progress-value {
        font-weight: 600;
    }

    .cm-progress-bar {
        height: 8px;
        background: var(--cm-gray-200);
        border-radius: var(--cm-radius-full);
        overflow: hidden;
    }

    .cm-progress-bar-lg {
        height: 12px;
    }

    .cm-progress-fill {
        height: 100%;
        border-radius: var(--cm-radius-full);
        transition: width 0.3s ease;
    }

    .cm-progress-row-highlight {
        padding-top: var(--cm-space-4);
        border-top: 1px solid var(--cm-border);
    }

    /* Additional badge colors */
    .cm-badge-purple {
        background: #9333ea;
        color: white;
    }

    /* Background colors for progress */
    .cm-bg-gray {
        background: var(--cm-gray-300);
    }
</style>
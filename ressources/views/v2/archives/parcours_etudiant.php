<?php
/**
 * Parcours Étudiant - Timeline chronologique
 */
$matricule = $data['matricule'] ?? '';
$evenements = $data['evenements'] ?? [];
?>
<div class="cm-parcours-etudiant">
    <div class="cm-page-header cm-mb-4">
        <h1 class="cm-page-title">📍 Parcours de l'Étudiant</h1>
        <p class="cm-page-subtitle">Chronologie complète du parcours</p>
        <a href="?page=fiche_etudiant_archive&id=<?php echo urlencode($matricule); ?>" class="cm-btn cm-btn-outline cm-mt-2">
            <i class="fas fa-arrow-left"></i> Retour à la fiche
        </a>
    </div>

    <div class="cm-timeline-vertical">
        <?php if (empty($evenements)): ?>
            <div class="cm-alert cm-alert-info">
                Aucun événement enregistré pour cet étudiant.
            </div>
        <?php else: ?>
            <?php foreach ($evenements as $i => $e): 
                $icon = match($e['type']) {
                    'inscription' => '📝',
                    'candidature' => '📤',
                    'depot_rapport' => '📄',
                    'validation' => '✅',
                    'soutenance' => '🎓',
                    'reclamation' => '📢',
                    default => '📌'
                };
                $color = match($e['type']) {
                    'inscription' => 'cm-timeline-primary',
                    'candidature' => 'cm-timeline-info',
                    'depot_rapport' => 'cm-timeline-success',
                    'validation' => 'cm-timeline-success',
                    'soutenance' => 'cm-timeline-warning',
                    'reclamation' => 'cm-timeline-danger',
                    default => 'cm-timeline-secondary'
                };
            ?>
                <div class="cm-timeline-item-vertical <?php echo $color; ?>">
                    <div class="cm-timeline-icon"><?php echo $icon; ?></div>
                    <div class="cm-timeline-content">
                        <div class="cm-timeline-date">
                            <?php echo date('d/m/Y H:i', strtotime($e['date'])); ?>
                        </div>
                        <h4 class="cm-timeline-title"><?php echo htmlspecialchars($e['titre']); ?></h4>
                        <p class="cm-timeline-desc"><?php echo htmlspecialchars($e['description']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>


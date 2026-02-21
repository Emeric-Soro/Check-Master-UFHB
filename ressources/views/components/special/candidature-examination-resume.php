<?php
/**
 * Résumé final de l'examen de candidature
 */
$etapeData = $etapeData ?? [];
$decision = 'Validée';
foreach ($etapeData as $data) {
    if (isset($data['validation']) && $data['validation'] === 'rejeté') {
        $decision = 'Rejetée';
        break;
    }
}
?>

<div class="resume-final">
    <div class="notification <?= $decision === 'Validée' ? 'is-success' : 'is-danger' ?> is-light has-text-centered py-5 mb-5">
        <h4 class="title is-4 mb-2"><?= ($decision === 'Validée' ? '🎉' : '❌') ?> Décision finale : <?= $decision ?></h4>
        <?php if ($decision === 'Validée'): ?>
            <p>La candidature a été validée avec succès. L'étudiant peut procéder à sa soutenance.</p>
        <?php else: ?>
            <p>La candidature a été rejetée. Les points bloquants sont listés ci-dessous.</p>
        <?php endif; ?>
    </div>

    <div class="resume-etapes box">
        <h4 class="title is-6 mb-4 border-bottom pb-2">Détail par étape :</h4>

        <?php foreach (['scolarite' => 'Scolarité', 'stage' => 'Stage', 'semestre' => 'Semestre'] as $key => $label): ?>
            <?php if (isset($etapeData[$key])): ?>
                <div class="etape-resume mb-3 p-3 border-radius <?= $etapeData[$key]['validation'] === 'validé' ? 'has-background-success-light' : 'has-background-danger-light' ?>" style="border-left: 4px solid <?= $etapeData[$key]['validation'] === 'validé' ? '#48c78e' : '#f14668' ?>">
                    <div class="is-flex is-justify-content-between is-align-items-center">
                        <h5 class="subtitle is-6 mb-0">
                            <i class="fas fa-<?= $key === 'scolarite' ? 'money-check' : ($key === 'stage' ? 'briefcase' : 'graduation-cap') ?> mr-2"></i>
                            <?= $label ?>
                        </h5>
                        <span class="tag <?= $etapeData[$key]['validation'] === 'validé' ? 'is-success' : 'is-danger' ?> is-rounded">
                            <?= strtoupper($etapeData[$key]['validation']) ?>
                        </span>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>

    <?php if (isset($_GET['email_envoye']) && $_GET['email_envoye'] == '1'): ?>
        <div class="notification is-success is-light">
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Email envoyé !</strong> Les résultats ont été transmis à l'étudiant.
        </div>
    <?php else: ?>
        <div class="notification is-info is-light">
            <i class="fas fa-envelope mr-2"></i>
            Cliquez sur "Envoyer les résultats" pour notifier l'étudiant.
        </div>
    <?php endif; ?>
</div>

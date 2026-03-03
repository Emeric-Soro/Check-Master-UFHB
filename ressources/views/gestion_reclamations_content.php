<?php
$statistiquesReclamations = $statistiquesReclamations ?? null;
$reclamationsRecentes = is_array($reclamationsRecentes ?? null) ? $reclamationsRecentes : [];

$totalReclamations = 0;
if (is_object($statistiquesReclamations) && isset($statistiquesReclamations->total)) {
    $totalReclamations = (int) $statistiquesReclamations->total;
} elseif (is_array($statistiquesReclamations) && isset($statistiquesReclamations['total'])) {
    $totalReclamations = (int) $statistiquesReclamations['total'];
}

$message = $_SESSION['message'] ?? null;
unset($_SESSION['message']);
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                
                <p class="cm-etu-panel__subtitle">Soumettez, suivez et consultez vos réclamations.</p>
            </div>
            <span class="cm-etu-count-badge"><?= (int) $totalReclamations ?> réclamation<?= (int) $totalReclamations > 1 ? 's' : '' ?></span>
        </header>

        <?php if (is_array($message)): ?>
            <?php cm_component('ui/alert-box', [
                    'type' => ($message['type'] ?? '') === 'success' ? 'success' : 'danger',
                    'message' => (string) ($message['text'] ?? ''),
            ]); ?>
        <?php endif; ?>

        <div class="cm-etu-hub-grid">
            <article class="cm-etu-hub-card">
                <div class="cm-etu-hub-card__icon"><i class="fas fa-pen-to-square" aria-hidden="true"></i></div>

                <p class="cm-etu-hub-card__desc">Rédigez et soumettez une nouvelle réclamation à la scolarité.</p>
                <?php if (canCreate()): ?>
                    <a class="cm-btn is-primary is-sm" href="?page=gestion_reclamations&action=soumettre_reclamation">Nouvelle réclamation</a>
                <?php endif; ?>
            </article>

            <article class="cm-etu-hub-card">
                <div class="cm-etu-hub-card__icon"><i class="fas fa-clock-rotate-left" aria-hidden="true"></i></div>

                <p class="cm-etu-hub-card__desc">Consultez le statut et l'historique de vos réclamations.</p>
                <a class="cm-btn is-info is-sm" href="?page=gestion_reclamations&action=suivi_historique_reclamation">Consulter</a>
            </article>
        </div>

        <?php if (!empty($reclamationsRecentes)): ?>
            <div class="cm-etu-list-header">

                <?php cm_component('ui/badge', ['type' => 'info', 'text' => count($reclamationsRecentes) . ' récente' . (count($reclamationsRecentes) > 1 ? 's' : '')]); ?>
            </div>

            <div class="cm-etu-report-list">
                <?php foreach ($reclamationsRecentes as $reclamation): ?>
                    <?php
                    $statutRecl = strtolower((string) ($reclamation->statut ?? $reclamation['statut'] ?? 'en_attente'));
                    $badgeType = 'light';
                    $badgeText = 'En attente';
                    if ($statutRecl === 'en_cours' || $statutRecl === 'en cours') {
                        $badgeType = 'info';
                        $badgeText = 'En cours';
                    } elseif ($statutRecl === 'traitee' || $statutRecl === 'traité' || $statutRecl === 'resolu') {
                        $badgeType = 'success';
                        $badgeText = 'Traitée';
                    } elseif ($statutRecl === 'rejetee' || $statutRecl === 'rejeté') {
                        $badgeType = 'danger';
                        $badgeText = 'Rejetée';
                    }

                    $objetRecl = (string) ($reclamation->objet ?? $reclamation['objet'] ?? 'Réclamation');
                    $typeRecl = (string) ($reclamation->type ?? $reclamation['type_reclamation'] ?? '');
                    $dateRecl = (string) ($reclamation->date_reclamation ?? $reclamation['date_reclamation'] ?? '');
                    ?>
                    <article class="cm-etu-report-item">
                        <div class="cm-etu-report-item__main">
                            <div class="cm-etu-report-item__head">
                                <span class="cm-etu-report-item__title"><?= htmlspecialchars($objetRecl, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                            </div>
                            <?php if ($typeRecl !== ''): ?>
                                <p class="cm-etu-report-item__meta"><strong>Type :</strong> <?= htmlspecialchars($typeRecl, ENT_QUOTES, 'UTF-8') ?></p>
                            <?php endif; ?>
                            <?php if ($dateRecl !== ''): ?>
                                <p class="cm-etu-report-item__meta">Soumise le <?= date('d/m/Y à H:i', strtotime($dateRecl)) ?></p>
                            <?php endif; ?>
                        </div>
                        <div class="cm-etu-report-item__actions">
                            <a href="?page=gestion_reclamations&action=suivi_historique_reclamation" class="cm-btn is-info is-sm">
                                <i class="fas fa-eye" aria-hidden="true"></i>
                                <span>Voir</span>
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cm-etu-empty">
                <i class="fas fa-inbox" aria-hidden="true"></i>
                <p>Aucune réclamation pour le moment.</p>
                <?php if (canCreate()): ?>
                    <a class="cm-btn is-primary" href="?page=gestion_reclamations&action=soumettre_reclamation">Soumettre ma première réclamation</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </section>
</div>
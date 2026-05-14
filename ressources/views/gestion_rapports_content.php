<?php
$infosDepot = is_array($GLOBALS['infosDepot'] ?? null) ? $GLOBALS['infosDepot'] : [];
$rapportsRecents = is_array($rapportsRecents ?? null) ? $rapportsRecents : [];
$totalRapports = isset($statistiquesRapports->total_rapports)
    ? (int) $statistiquesRapports->total_rapports
    : count($rapportsRecents);

$messageMap = [
    'depot_ok' => ['type' => 'success', 'text' => 'Le rapport a bien été déposé et votre candidature a été transmise.'],
    'depot_fail' => ['type' => 'danger', 'text' => 'Impossible de déposer le rapport. Vérifiez son état puis réessayez.'],
    'depot_en_cours' => ['type' => 'warning', 'text' => 'Vous avez déjà un rapport en cours d\'évaluation.'],
    'suppression_ok' => ['type' => 'success', 'text' => 'Le rapport a été supprimé avec succès.'],
];
$messageKey = (string) ($_GET['message'] ?? '');
$flash = $messageMap[$messageKey] ?? null;

// Trouver le rapport en cours de validation pour afficher le suivi
$rapportEnCours = null;
foreach ($rapportsRecents as $r) {
    $statut = strtolower((string) ($r->statut_rapport ?? ''));
    $id = (int) ($r->id_rapport ?? 0);
    $info = $infosDepot[$id] ?? [];
    if ($statut === 'en_cours' || !empty($info['dejaDepose'])) {
        $rapportEnCours = $r;
        break;
    }
}
?>

<div class="cm-etu-screen">
    <div style="max-width: 900px; margin: 0 auto; padding-top: 10px;">

        <?php if ($flash !== null): ?>
            <div style="margin-bottom: 20px;">
                <?php cm_component('ui/alert-box', ['type' => $flash['type'], 'message' => $flash['text']]); ?>
            </div>
        <?php endif; ?>

        <div class="cm-etu-panel" style="border: 1px solid #eaeaea; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; overflow: hidden;">
            
            <header class="cm-etu-panel__header" style="border-bottom: 1px solid #f0f0f0; padding: 20px 30px;">
                <div>
                    <h2 style="font-size: 1.3rem; color: #222; font-weight: 600; margin-bottom: 4px;">Mes rapports de stage</h2>
                    <p class="cm-etu-panel__subtitle" style="margin: 0;">Suivez l'avancée de vos validations et accédez à vos documents.</p>
                </div>
            </header>

            <!-- SUIVI DE VALIDATION (Si un rapport est en cours) -->
            <?php if ($rapportEnCours): ?>
                <?php
                $rapportId = (int) ($rapportEnCours->id_rapport ?? 0);
                $infoDepot = $infosDepot[$rapportId] ?? [];
                $isDepose = !empty($infoDepot['dejaDepose']);
                
                // Définir les étapes du timeline
                $validationSteps = [
                    [
                        'label' => 'Rédaction',
                        'state' => 'completed',
                        'icon' => 'fa-pen',
                        'desc' => 'Terminée'
                    ],
                    [
                        'label' => 'Dépôt',
                        'state' => $isDepose ? 'completed' : 'pending',
                        'icon' => 'fa-upload',
                        'desc' => $isDepose ? 'Effectué' : 'En attente'
                    ],
                    [
                        'label' => 'Évaluation',
                        'state' => $isDepose ? 'active' : 'pending',
                        'icon' => 'fa-search',
                        'desc' => $isDepose ? 'En cours' : 'Bloquée'
                    ],
                    [
                        'label' => 'Résultat',
                        'state' => 'pending',
                        'icon' => 'fa-flag-checkered',
                        'desc' => 'À venir'
                    ]
                ];
                ?>
                <div style="padding: 25px 30px; background: #fafafa; border-bottom: 1px solid #f0f0f0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
                        <h3 style="font-size: 1.1rem; color: #333; font-weight: 600; margin: 0;">
                            <i class="fas fa-tasks" style="color: #2196F3; margin-right: 8px;"></i>
                            Suivi de validation : <?= htmlspecialchars((string) ($rapportEnCours->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>
                        </h3>
                        <?php 
                        $statutBadge = 'info';
                        $statutText = 'En cours';
                        if ($isDepose) { $statutBadge = 'warning'; $statutText = 'Déposé'; }
                        cm_component('ui/badge', ['text' => $statutText, 'type' => $statutBadge]); 
                        ?>
                    </div>
                    <?php cm_component('timeline/timeline', ['steps' => $validationSteps, 'orientation' => 'horizontal']); ?>
                    
                    <div style="margin-top: 15px; text-align: right;">
                        <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-primary is-sm">
                            <i class="fas fa-eye" style="margin-right: 6px;"></i> Consulter le rapport
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- LISTE DES RAPPORTS -->
            <div style="padding: 25px 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; color: #333; font-weight: 600; margin: 0;">Historique des rapports</h3>
                    <?php if (canCreate()): ?>
                        <a href="?page=gestion_rapports&action=creer_rapport" class="cm-btn is-primary is-sm">
                            <i class="fas fa-plus" style="margin-right: 6px;"></i> Nouveau rapport
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($rapportsRecents)): ?>
                    <div class="cm-etu-report-list">
                        <?php foreach ($rapportsRecents as $rapport): ?>
                            <?php
                            $rapportId = (int) ($rapport->id_rapport ?? 0);
                            $infoDepot = $infosDepot[$rapportId] ?? ['peutDeposer' => true, 'messageDepot' => '', 'dejaDepose' => false];
                            $peutDeposer = (bool) ($infoDepot['peutDeposer'] ?? false);
                            $dejaDepose = (bool) ($infoDepot['dejaDepose'] ?? false);
                            $messageDepot = (string) ($infoDepot['messageDepot'] ?? '');

                            $statutRapport = strtolower((string) ($rapport->statut_rapport ?? ''));
                            $badgeType = 'light';
                            $badgeText = 'Brouillon';
                            if ($statutRapport === 'en_cours') {
                                $badgeType = 'info';
                                $badgeText = 'En cours';
                            } elseif ($statutRapport === 'valider') {
                                $badgeType = 'success';
                                $badgeText = 'Validé';
                            } elseif ($statutRapport === 'rejeter') {
                                $badgeType = 'danger';
                                $badgeText = 'Rejeté';
                            } elseif ($dejaDepose) {
                                $badgeType = 'warning';
                                $badgeText = 'Déposé';
                            }
                            ?>
                            <article class="cm-etu-report-item" style="display: flex; align-items: center; justify-content: space-between; padding: 15px; border: 1px solid #eee; border-radius: 8px; margin-bottom: 10px; background: #fff;">
                                <div style="flex: 1;">
                                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                                        <span style="font-weight: 600; color: #333;"><?= htmlspecialchars((string) ($rapport->nom_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                                    </div>
                                    <p style="margin: 0; color: #666; font-size: 0.9rem;">
                                        <strong>Thème:</strong> <?= htmlspecialchars((string) ($rapport->theme_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        <span style="margin: 0 8px;">•</span>
                                        Créé le <?= date('d/m/Y', strtotime((string) ($rapport->date_rapport ?? 'now'))) ?>
                                    </p>
                                </div>

                                <div style="display: flex; gap: 8px; align-items: center;">
                                    <?php if ($peutDeposer): ?>
                                        <form method="POST" action="?page=gestion_rapports" style="margin: 0;">
                                            <input type="hidden" name="action" value="deposer_rapport">
                                            <input type="hidden" name="id_rapport" value="<?= $rapportId ?>">
                                            <button type="submit" class="cm-btn is-success is-sm">
                                                <i class="fas fa-upload" style="margin-right: 4px;"></i> Déposer
                                            </button>
                                        </form>
                                    <?php elseif ($dejaDepose): ?>
                                        <span style="color: #888; font-size: 0.85rem;"><i class="fas fa-check-circle" style="color: #28a745;"></i> Déposé</span>
                                    <?php else: ?>
                                        <button type="button" class="cm-btn is-light is-sm" disabled title="<?= htmlspecialchars($messageDepot, ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fas fa-lock"></i>
                                        </button>
                                    <?php endif; ?>

                                    <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-info is-sm" style="background: white; border: 1px solid #ddd;">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #fafafa; border-radius: 8px;">
                        <i class="fas fa-file-circle-plus" style="font-size: 2.5rem; color: #ddd; margin-bottom: 15px;"></i>
                        <p style="color: #666; margin-bottom: 20px;">Aucun rapport disponible pour le moment.</p>
                        <?php if (canCreate()): ?>
                            <a class="cm-btn is-primary" href="?page=gestion_rapports&action=creer_rapport">Créer mon premier rapport</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function toggleDeleteForm(rapportId) {
    const form = document.getElementById('delete-form-' + rapportId);
    if (form) {
        form.classList.toggle('hidden');
    }
}
</script>

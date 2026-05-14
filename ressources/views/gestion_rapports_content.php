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

<style>
    .cm-etu-panel {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .cm-report-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 20px;
    }

    .cm-report-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 20px;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }

    .cm-report-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }

    .cm-btn {
        padding: 8px 16px !important;
        border-radius: 4px !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid transparent;
    }

    .cm-btn.is-primary {
        background-color: #3182ce !important;
        color: #fff !important;
        border-color: #3182ce !important;
    }

    .cm-btn.is-primary:hover {
        background-color: #2b6cb0 !important;
    }

    .cm-btn.is-outline {
        background-color: #fff !important;
        color: #4a5568 !important;
        border-color: #e2e8f0 !important;
    }

    .cm-btn.is-outline:hover {
        background-color: #f7fafc !important;
    }

    .cm-btn.is-success {
        background-color: #38a169 !important;
        color: #fff !important;
        border-color: #38a169 !important;
    }

    .cm-btn.is-success:hover {
        background-color: #2f855a !important;
    }
</style>

<div class="cm-etu-screen">
    <div style="max-width: 100%; padding: 20px;">

        <?php if ($flash !== null): ?>
            <div style="margin-bottom: 20px;">
                <?php cm_component('ui/alert-box', ['type' => $flash['type'], 'message' => $flash['text']]); ?>
            </div>
        <?php endif; ?>

        <div class="cm-etu-panel">
            
            <!-- SUIVI DE VALIDATION (Si un rapport est en cours) -->
            <?php if ($rapportEnCours): ?>
                <?php
                $rapportId = (int) ($rapportEnCours->id_rapport ?? 0);
                $infoDepot = $infosDepot[$rapportId] ?? [];
                $isDepose = !empty($infoDepot['dejaDepose']);
                
                $validationSteps = [
                    ['label' => 'Rédaction', 'state' => 'completed', 'icon' => 'fa-pen', 'desc' => 'Terminée'],
                    ['label' => 'Dépôt', 'state' => $isDepose ? 'completed' : 'pending', 'icon' => 'fa-upload', 'desc' => $isDepose ? 'Effectué' : 'En attente'],
                    ['label' => 'Évaluation', 'state' => $isDepose ? 'active' : 'pending', 'icon' => 'fa-search', 'desc' => $isDepose ? 'En cours' : 'Bloquée'],
                    ['label' => 'Résultat', 'state' => 'pending', 'icon' => 'fa-flag-checkered', 'desc' => 'À venir']
                ];
                ?>
                <div style="padding: 25px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <div>
                            <h3 style="font-size: 1rem; color: #2d3748; font-weight: 700; margin: 0;">
                                <i class="fas fa-satellite-dish" style="color: #3182ce; margin-right: 8px;"></i>
                                Suivi en temps réel : <?= htmlspecialchars((string) ($rapportEnCours->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>
                            </h3>
                        </div>
                        <?php 
                        $statutBadge = 'info';
                        $statutText = 'En cours';
                        if ($isDepose) { $statutBadge = 'warning'; $statutText = 'Déposé'; }
                        cm_component('ui/badge', ['text' => $statutText, 'type' => $statutBadge]); 
                        ?>
                    </div>
                    
                    <?php cm_component('timeline/timeline', ['steps' => $validationSteps, 'orientation' => 'horizontal']); ?>
                    
                    <div style="margin-top: 25px; display: flex; justify-content: flex-end;">
                        <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                            <i class="fas fa-external-link-alt"></i> Ouvrir le rapport
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- LISTE DES RAPPORTS -->
            <div>
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #e2e8f0;">
                    <h3 style="font-size: 1.1rem; color: #2d3748; font-weight: 700; margin: 0;">Mes rapports de stage</h3>
                    <?php if (canCreate()): ?>
                        <a href="?page=gestion_rapports&action=creer_rapport" class="cm-btn is-primary">
                            <i class="fas fa-plus"></i> Nouveau rapport
                        </a>
                    <?php endif; ?>
                </div>

                <?php if (!empty($rapportsRecents)): ?>
                    <div class="cm-report-grid">
                        <?php foreach ($rapportsRecents as $rapport): ?>
                            <?php
                            $rapportId = (int) ($rapport->id_rapport ?? 0);
                            $infoDepot = $infosDepot[$rapportId] ?? ['peutDeposer' => true, 'messageDepot' => '', 'dejaDepose' => false];
                            $peutDeposer = (bool) ($infoDepot['peutDeposer'] ?? false);
                            $dejaDepose = (bool) ($infoDepot['dejaDepose'] ?? false);

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
                            <div class="cm-report-card">
                                <div>
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                        <h4 style="margin: 0; font-size: 1rem; color: #2d3748; font-weight: 600;">
                                            <?= htmlspecialchars((string) ($rapport->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>
                                        </h4>
                                        <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                                    </div>

                                    <div style="margin-bottom: 20px; background: #f8fafc; padding: 12px; border-radius: 6px; border: 1px solid #edf2f7;">
                                        <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.025em; color: #718096; font-weight: 700; margin-bottom: 5px;">Thème du stage</div>
                                        <div style="color: #4a5568; font-size: 0.9rem; line-height: 1.5; font-style: italic;">
                                            <?= htmlspecialchars((string) ($rapport->theme_rapport ?? 'Non spécifié'), ENT_QUOTES, 'UTF-8') ?>
                                        </div>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #edf2f7; padding-top: 15px; margin-top: auto;">
                                    <span style="font-size: 0.8rem; color: #a0aec0;">
                                        Créé le <?= date('d/m/Y', strtotime((string) ($rapport->date_rapport ?? 'now'))) ?>
                                    </span>
                                    
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($peutDeposer): ?>
                                            <form method="POST" action="?page=gestion_rapports" style="margin: 0;">
                                                <input type="hidden" name="action" value="deposer_rapport">
                                                <input type="hidden" name="id_rapport" value="<?= $rapportId ?>">
                                                <button type="submit" class="cm-btn is-success" title="Soumettre ce rapport">
                                                    <i class="fas fa-paper-plane"></i> Déposer
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                                            <i class="fas fa-edit"></i> Modifier
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 60px; background: #fff; border: 1px dashed #cbd5e0; border-radius: 12px;">
                        <i class="fas fa-file-circle-plus" style="font-size: 3rem; color: #e2e8f0; margin-bottom: 20px;"></i>
                        <h4 style="color: #4a5568; margin-bottom: 10px;">Aucun rapport pour le moment</h4>
                        <p style="color: #718096; margin-bottom: 25px;">Commencez par créer votre premier rapport de stage en ligne.</p>
                        <?php if (canCreate()): ?>
                            <a class="cm-btn is-primary" href="?page=gestion_rapports&action=creer_rapport" style="padding: 12px 24px !important;">
                                <i class="fas fa-plus"></i> Créer mon premier rapport
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
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

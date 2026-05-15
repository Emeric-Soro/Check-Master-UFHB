<?php
$infosDepot = is_array($GLOBALS['infosDepot'] ?? null) ? $GLOBALS['infosDepot'] : [];
$rapportsRecents = is_array($rapportsRecents ?? null) ? $rapportsRecents : [];
$dernierRapportDepose = $GLOBALS['dernierRapportDepose'] ?? null;
$totalRapports = isset($statistiquesRapports->total_rapports)
    ? (int) $statistiquesRapports->total_rapports
    : count($rapportsRecents);

// Trouver le rapport en cours de validation pour afficher le suivi
$rapportEnCours = $dernierRapportDepose;
if (!$rapportEnCours) {
    foreach ($rapportsRecents as $r) {
        $statut = strtolower((string) ($r->statut_rapport ?? ''));
        $id = (int) ($r->id_rapport ?? 0);
        $info = $infosDepot[$id] ?? [];
        if ($statut === 'en_cours' || !empty($info['dejaDepose'])) {
            $rapportEnCours = $r;
            break;
        }
    }
}

// Déterminer si on peut créer un nouveau rapport
// Bloqué si un rapport est en cours d'évaluation (pas rejeté)
$peutCreerNouveau = true;
$dernierStatut = strtolower((string) ($rapportEnCours->statut_rapport ?? ''));
if ($rapportEnCours && $dernierStatut !== 'rejeter') {
    $peutCreerNouveau = false;
}
?>

<style>
    .cm-report-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 16px;
    }

    .cm-report-card {
        background: var(--cm-card-bg, rgba(236, 246, 255, 0.84));
        border: 1px solid var(--cm-card-border, rgba(26, 82, 118, 0.14));
        border-radius: var(--cm-card-radius, 10px);
        padding: 18px;
        transition: transform 0.2s, box-shadow 0.2s;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: var(--cm-card-shadow, 0 2px 8px rgba(0, 0, 0, 0.06));
    }

    .cm-report-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .cm-btn {
        padding: 8px 16px !important;
        border-radius: 6px !important;
        font-weight: 600 !important;
        font-size: 0.85rem !important;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border: 1px solid transparent;
        text-decoration: none;
    }

    .cm-btn.is-primary {
        background-color: var(--cm-primary, #2563eb) !important;
        color: #fff !important;
    }

    .cm-btn.is-primary:hover {
        background-color: var(--cm-primary-dark, #1d4ed8) !important;
    }

    .cm-btn.is-outline {
        background-color: rgba(255, 255, 255, 0.6) !important;
        color: var(--cm-primary-dark, #1e40af) !important;
        border-color: var(--cm-card-border, rgba(26, 82, 118, 0.14)) !important;
    }

    .cm-btn.is-outline:hover {
        background-color: rgba(255, 255, 255, 0.9) !important;
    }

    .cm-btn.is-success {
        background-color: #059669 !important;
        color: #fff !important;
    }

    .cm-btn.is-success:hover {
        background-color: #04785d !important;
    }

    .cm-suivi-card {
        background: var(--cm-card-bg, rgba(236, 246, 255, 0.84));
        border: 1px solid var(--cm-card-border, rgba(26, 82, 118, 0.14));
        border-radius: var(--cm-card-radius, 10px);
        padding: 24px;
        box-shadow: var(--cm-card-shadow, 0 2px 8px rgba(0, 0, 0, 0.06));
        margin-bottom: 24px;
    }

    .cm-empty-state {
        text-align: center;
        padding: 50px 20px;
        background: var(--cm-card-bg, rgba(236, 246, 255, 0.84));
        border: 1px dashed var(--cm-card-border, rgba(26, 82, 118, 0.14));
        border-radius: var(--cm-card-radius, 10px);
    }

    @media (max-width: 768px) {
        .cm-report-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div style="padding: 0;">

    <!-- SUIVI DE VALIDATION -->
    <?php if ($rapportEnCours): ?>
        <?php
        $rapportId = (int) ($rapportEnCours->id_rapport ?? 0);
        $infoDepot = $infosDepot[$rapportId] ?? [];
        $isDepose = !empty($infoDepot['dejaDepose']) || ($dernierRapportDepose !== null && $rapportId === (int) ($dernierRapportDepose->id_rapport ?? 0));

        $validationSteps = [
            ['label' => 'Rédaction', 'state' => 'completed', 'icon' => 'fa-pen', 'desc' => 'Terminée'],
            ['label' => 'Dépôt', 'state' => $isDepose ? 'completed' : 'pending', 'icon' => 'fa-upload', 'desc' => $isDepose ? 'Effectué' : 'En attente'],
            ['label' => 'Évaluation', 'state' => $isDepose ? 'active' : 'pending', 'icon' => 'fa-search', 'desc' => $isDepose ? 'En cours' : 'Bloquée'],
            ['label' => 'Résultat', 'state' => 'pending', 'icon' => 'fa-flag-checkered', 'desc' => 'À venir']
        ];
        ?>
        <div class="cm-suivi-card">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px;">
                <h3 style="font-size: 1rem; color: #1e3a5f; font-weight: 700; margin: 0;">
                    <i class="fas fa-satellite-dish" style="color: var(--cm-primary, #2563eb); margin-right: 8px;"></i>
                    Suivi : <?= htmlspecialchars((string) ($rapportEnCours->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>
                </h3>
                <?php
                $statutBadge = 'info';
                $statutText = 'En cours';
                if ($isDepose) { $statutBadge = 'warning'; $statutText = 'Déposé'; }
                if ($dernierStatut === 'rejeter') { $statutBadge = 'danger'; $statutText = 'Rejeté'; }
                if ($dernierStatut === 'valider') { $statutBadge = 'success'; $statutText = 'Validé'; }
                cm_component('ui/badge', ['text' => $statutText, 'type' => $statutBadge]);
                ?>
            </div>

            <?php cm_component('timeline/timeline', ['steps' => $validationSteps, 'orientation' => 'horizontal']); ?>

            <div style="margin-top: 20px; display: flex; justify-content: flex-end; gap: 10px;">
                <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                    <i class="fas fa-eye"></i> Voir le rapport
                </a>
            </div>
        </div>
    <?php endif; ?>

    <!-- LISTE DES RAPPORTS -->
    <div>
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="font-size: 1.05rem; color: #1e3a5f; font-weight: 700; margin: 0;">Mes rapports de stage</h3>
            <?php if ($peutCreerNouveau && canCreate()): ?>
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
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px;">
                                <h4 style="margin: 0; font-size: 0.95rem; color: #1e3a5f; font-weight: 600;">
                                    <?= htmlspecialchars((string) ($rapport->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>
                                </h4>
                                <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                            </div>

                            <div style="margin-bottom: 16px; background: rgba(255,255,255,0.4); padding: 10px 12px; border-radius: 6px; border: 1px solid rgba(26, 82, 118, 0.08);">
                                <div style="font-size: 0.7rem; text-transform: uppercase; letter-spacing: 0.03em; color: #64748b; font-weight: 700; margin-bottom: 4px;">Thème du stage</div>
                                <div style="color: #334155; font-size: 0.88rem; line-height: 1.4; font-style: italic;">
                                    <?= htmlspecialchars((string) ($rapport->theme_rapport ?? 'Non spécifié'), ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                        </div>

                        <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(26, 82, 118, 0.08); padding-top: 12px; margin-top: auto;">
                            <span style="font-size: 0.78rem; color: #94a3b8;">
                                <?= date('d/m/Y', strtotime((string) ($rapport->date_rapport ?? 'now'))) ?>
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

                                <?php if ($statutRapport !== 'en_cours' && $statutRapport !== 'valider'): ?>
                                    <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                                        <i class="fas fa-edit"></i> Modifier
                                    </a>
                                <?php else: ?>
                                    <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" class="cm-btn is-outline">
                                        <i class="fas fa-eye"></i> Voir
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="cm-empty-state">
                <i class="fas fa-file-circle-plus" style="font-size: 2.5rem; color: #94a3b8; margin-bottom: 16px;"></i>
                <h4 style="color: #475569; margin-bottom: 8px; font-size: 1rem;">Aucun rapport pour le moment</h4>
                <p style="color: #64748b; margin-bottom: 20px; font-size: 0.9rem;">Créez votre premier rapport de stage en ligne.</p>
                <?php if (canCreate()): ?>
                    <a class="cm-btn is-primary" href="?page=gestion_rapports&action=creer_rapport" style="padding: 10px 20px !important;">
                        <i class="fas fa-plus"></i> Créer mon premier rapport
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

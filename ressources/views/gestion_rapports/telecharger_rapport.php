<?php
/**
 * PRD 1 – Vue étudiant : Téléchargement du rapport étudiant
 * 
 * Permet à l'étudiant de :
 * - Télécharger un modèle de rapport (F1.1)
 * - Uploader son rapport (F1.2, F1.3)
 * - Consulter la liste de ses rapports (F1.4)
 * 
 * Variables attendues :
 * - $modeleRapportUrl : URL du modèle de rapport
 * - $rapportsRecents : array des rapports de l'étudiant
 * - $infosDepot : array des infos de dépôt par rapport
 * - $dernierRapport : dernier rapport uploadé (object|null)
 * - $statistiquesRapports : stats de l'étudiant
 * - $typesAutorises : extensions autorisées
 * - $tailleMax : taille max en bytes
 */

$modeleRapportUrl = $GLOBALS['modeleRapportUrl'] ?? '#';
$rapportsRecents = $GLOBALS['rapportsRecents'] ?? [];
$infosDepot = $GLOBALS['infosDepot'] ?? [];
$dernierRapport = $GLOBALS['dernierRapport'] ?? null;
$statistiquesRapports = $GLOBALS['statistiquesRapports'] ?? null;
$typesAutorises = $GLOBALS['typesAutorises'] ?? 'pdf,doc,docx';
$tailleMax = $GLOBALS['tailleMax'] ?? 20971520;

$totalRapports = $statistiquesRapports->total_rapports ?? count($rapportsRecents);
$num_etu = $_SESSION['num_etu'] ?? '';
$nomEtu = $_SESSION['nom_etu'] ?? '';
$prenomEtu = $_SESSION['prenom_etu'] ?? '';
$nomComplet = trim($nomEtu . ' ' . $prenomEtu);

$messageSuccess = $_SESSION['success'] ?? null;
$messageError = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);
?>

<div class="cm-etu-screen">
    <div style="max-width: 960px; margin: 0 auto; padding-top: 10px;">

        <!-- Messages flash -->
        <?php if ($messageSuccess): ?>
            <div style="margin-bottom: 20px;">
                <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
            </div>
        <?php endif; ?>
        <?php if ($messageError): ?>
            <div style="margin-bottom: 20px;">
                <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageError]); ?>
            </div>
        <?php endif; ?>

        <!-- Carte principale -->
        <div class="cm-etu-panel" style="border: 1px solid #eaeaea; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; overflow: hidden;">
            
            <!-- En-tête -->
            <header class="cm-etu-panel__header" style="border-bottom: 1px solid #f0f0f0; padding: 20px 30px;">
                <div>
                    <h2 style="font-size: 1.3rem; color: #222; font-weight: 600; margin-bottom: 4px;">
                        <i class="fas fa-file-upload" style="color: #2196F3; margin-right: 8px;"></i>
                        Téléchargement du rapport étudiant
                    </h2>
                    <p class="cm-etu-panel__subtitle" style="margin: 0; color: #666;">
                        Déposez votre rapport de stage au format PDF ou Word.
                    </p>
                </div>
            </header>

            <!-- Section 1 : Téléchargement du modèle -->
            <div style="padding: 20px 30px; border-bottom: 1px solid #f0f0f0; background: #fafafa;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 1rem; color: #333; font-weight: 600; margin: 0 0 4px 0;">
                            <i class="fas fa-download" style="color: #4CAF50; margin-right: 6px;"></i>
                            Modèle de rapport
                        </h3>
                        <p style="margin: 0; color: #666; font-size: 0.9rem;">
                            Téléchargez le modèle officiel pour la rédaction de votre rapport de stage.
                        </p>
                    </div>
                    <a href="?page=gestion_rapports&action=download_modele" class="cm-btn is-outline-primary is-sm">
                        <i class="fas fa-file-download"></i>
                        Télécharger le modèle
                    </a>
                </div>
            </div>

            <!-- Section 2 : Upload du rapport -->
            <div style="padding: 25px 30px; border-bottom: 1px solid #f0f0f0;">
                <h3 style="font-size: 1rem; color: #333; font-weight: 600; margin: 0 0 15px 0;">
                    <i class="fas fa-cloud-upload-alt" style="color: #FF9800; margin-right: 6px;"></i>
                    Déposer votre rapport
                </h3>

                <form method="POST" action="?page=gestion_rapports" enctype="multipart/form-data" style="max-width: 600px;">
                    <input type="hidden" name="action" value="upload_rapport">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Thème du rapport (optionnel) -->
                    <div style="margin-bottom: 16px;">
                        <label for="theme_rapport" style="display: block; font-weight: 500; color: #444; margin-bottom: 6px; font-size: 0.9rem;">
                            Thème du rapport <span style="color: #999;">(optionnel)</span>
                        </label>
                        <input type="text" 
                               id="theme_rapport" 
                               name="theme_rapport" 
                               class="cm-form-control" 
                               placeholder="Ex: Conception et réalisation d'une application de gestion..."
                               style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <!-- Upload fichier -->
                    <div style="margin-bottom: 16px;">
                        <?php
                        cm_component('form/file-upload', [
                            'name' => 'rapport_fichier',
                            'id' => 'rapport_fichier',
                            'label' => 'Fichier du rapport',
                            'required' => true,
                            'accept' => '.pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'hint' => 'Formats acceptés : PDF, Word (doc/docx). Taille maximale : 20 Mo.',
                            'size' => 'md',
                        ]);
                        ?>
                    </div>

                    <!-- Options supplémentaires -->
                    <div style="margin-bottom: 16px; display: flex; align-items: center; gap: 8px;">
                        <input type="checkbox" id="deposer_apres_upload" name="deposer_apres_upload" value="1" checked>
                        <label for="deposer_apres_upload" style="font-size: 0.9rem; color: #555; cursor: pointer;">
                            Déposer également le rapport pour évaluation (candidature à la soutenance)
                        </label>
                    </div>

                    <button type="submit" class="cm-btn is-primary">
                        <i class="fas fa-upload" style="margin-right: 6px;"></i>
                        Télécharger le rapport
                    </button>
                </form>
            </div>

            <!-- Section 3 : Dernier rapport uploadé -->
            <?php if ($dernierRapport): ?>
            <div style="padding: 20px 30px; border-bottom: 1px solid #f0f0f0; background: #f8fff8;">
                <h3 style="font-size: 1rem; color: #333; font-weight: 600; margin: 0 0 12px 0;">
                    <i class="fas fa-file-alt" style="color: #4CAF50; margin-right: 6px;"></i>
                    Dernier rapport uploadé
                </h3>
                <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <strong style="color: #333;"><?= htmlspecialchars((string) ($dernierRapport->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?></strong>
                        <span style="color: #888; margin-left: 10px; font-size: 0.85rem;">
                            <?php if (!empty($dernierRapport->taille_fichier)): ?>
                                <?= round($dernierRapport->taille_fichier / 1024, 1) ?> Ko
                            <?php endif; ?>
                            <?php if (!empty($dernierRapport->date_modification)): ?>
                                • <?= date('d/m/Y H:i', strtotime($dernierRapport->date_modification)) ?>
                            <?php endif; ?>
                        </span>
                    </div>
                    <?php
                    $statutBadge = 'light';
                    $statutText = 'Brouillon';
                    $statut = strtolower((string) ($dernierRapport->statut_rapport ?? ''));
                    if ($statut === 'en_cours' || $statut === 'en_attente') {
                        $statutBadge = 'info';
                        $statutText = 'En attente';
                    } elseif ($statut === 'valider') {
                        $statutBadge = 'success';
                        $statutText = 'Validé';
                    } elseif ($statut === 'rejeter') {
                        $statutBadge = 'danger';
                        $statutText = 'Rejeté';
                    }
                    cm_component('ui/badge', ['type' => $statutBadge, 'text' => $statutText]);
                    ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- Section 4 : Mes rapports -->
            <div style="padding: 25px 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1rem; color: #333; font-weight: 600; margin: 0;">
                        <i class="fas fa-history" style="color: #9C27B0; margin-right: 6px;"></i>
                        Mes rapports
                        <span style="font-weight: normal; font-size: 0.85rem; color: #888; margin-left: 8px;">
                            (<?= $totalRapports ?> rapport(s))
                        </span>
                    </h3>
                </div>

                <?php if (!empty($rapportsRecents)): ?>
                    <div class="cm-etu-report-list">
                        <table class="cm-data-table" style="width: 100%; border-collapse: collapse;">
                            <thead>
                                <tr style="background: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                                    <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555; font-size: 0.85rem;">Rapport</th>
                                    <th style="padding: 12px 15px; text-align: left; font-weight: 600; color: #555; font-size: 0.85rem;">Thème</th>
                                    <th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #555; font-size: 0.85rem;">Statut</th>
                                    <th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #555; font-size: 0.85rem;">Date</th>
                                    <th style="padding: 12px 15px; text-align: center; font-weight: 600; color: #555; font-size: 0.85rem;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rapportsRecents as $rapport): ?>
                                    <?php
                                    $rapportId = (int) ($rapport->id_rapport ?? 0);
                                    $infoDepot = $infosDepot[$rapportId] ?? ['peutDeposer' => false, 'dejaDepose' => false];
                                    $dejaDepose = (bool) ($infoDepot['dejaDepose'] ?? false);
                                    $peutDeposer = (bool) ($infoDepot['peutDeposer'] ?? false);
                                    
                                    $statut = strtolower((string) ($rapport->statut_rapport ?? ''));
                                    $badgeType = 'light';
                                    $badgeText = 'Brouillon';
                                    if ($statut === 'en_cours' || $statut === 'en_attente') {
                                        $badgeType = 'info';
                                        $badgeText = 'En attente';
                                    } elseif ($statut === 'valider') {
                                        $badgeType = 'success';
                                        $badgeText = 'Validé';
                                    } elseif ($statut === 'rejeter') {
                                        $badgeType = 'danger';
                                        $badgeText = 'Rejeté';
                                    }
                                    if ($dejaDepose) {
                                        $badgeType = 'warning';
                                        $badgeText = 'Déposé';
                                    }
                                    
                                    $dateAffichage = !empty($rapport->date_rapport) 
                                        ? date('d/m/Y', strtotime($rapport->date_rapport)) 
                                        : (!empty($rapport->date_modification) 
                                            ? date('d/m/Y', strtotime($rapport->date_modification)) 
                                            : '-');
                                    $aUnFichier = !empty($rapport->chemin_fichier);
                                    ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 12px 15px; font-weight: 500; color: #333;">
                                            <?= htmlspecialchars((string) ($rapport->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="padding: 12px 15px; color: #666; font-size: 0.9rem;">
                                            <?= htmlspecialchars((string) ($rapport->theme_rapport ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: center;">
                                            <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: center; color: #666; font-size: 0.9rem;">
                                            <?= $dateAffichage ?>
                                        </td>
                                        <td style="padding: 12px 15px; text-align: center;">
                                            <div style="display: flex; gap: 6px; justify-content: center;">
                                                <?php if ($aUnFichier): ?>
                                                    <a href="?page=gestion_rapports&action=download_fichier_rapport&id=<?= $rapportId ?>" 
                                                       class="cm-btn is-light is-sm" title="Télécharger le fichier">
                                                        <i class="fas fa-download"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" 
                                                   class="cm-btn is-light is-sm" title="Voir les détails">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #fafafa; border-radius: 8px;">
                        <i class="fas fa-file-circle-plus" style="font-size: 2.5rem; color: #ddd; margin-bottom: 15px;"></i>
                        <p style="color: #666; margin-bottom: 5px;">Aucun rapport disponible pour le moment.</p>
                        <p style="color: #999; font-size: 0.9rem;">Utilisez le formulaire ci-dessus pour déposer votre premier rapport.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

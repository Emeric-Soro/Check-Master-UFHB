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
<style>
    .cm-etu-panel {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    
    .cm-etu-field {
        margin-bottom: 20px;
    }
    
    .cm-etu-label {
        display: block !important;
        font-weight: 600 !important;
        color: #333 !important;
        margin-bottom: 8px !important;
        font-size: 0.85rem !important;
    }
    
    .cm-etu-input {
        background: #fff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 4px !important;
        padding: 12px 15px !important;
        font-size: 0.95rem !important;
        width: 100%;
        transition: border-color 0.2s;
        color: #333 !important;
    }
    
    .cm-etu-input:focus {
        border-color: #3182ce !important;
        outline: none !important;
        box-shadow: 0 0 0 1px #3182ce !important;
    }

    .cm-btn {
        padding: 10px 20px !important;
        border-radius: 4px !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        cursor: pointer;
        transition: all 0.2s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .cm-btn.is-primary {
        background-color: #3182ce !important;
        color: #fff !important;
        border: 1px solid #3182ce !important;
    }

    .cm-btn.is-primary:hover {
        background-color: #2b6cb0 !important;
    }

    .cm-btn.is-outline {
        background-color: #fff !important;
        color: #4a5568 !important;
        border: 1px solid #e2e8f0 !important;
    }

    .cm-btn.is-outline:hover {
        background-color: #f7fafc !important;
    }

    .cm-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
    }

    .cm-form-full-width {
        grid-column: span 2;
    }

    /* Adaption pour la liste des rapports */
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
    }

    .cm-report-card:hover {
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    }
</style>

<div class="cm-etu-screen">
    <div style="padding: 20px;">

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
        <div class="cm-etu-panel">
            
            <!-- Section 1 : Téléchargement du modèle -->
            <div style="padding: 20px; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 30px;">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 1rem; color: #2d3748; font-weight: 700; margin: 0 0 5px 0;">
                            Modèle de rapport officiel
                        </h3>
                        <p style="margin: 0; color: #718096; font-size: 0.9rem;">
                            Utilisez ce document comme base pour la rédaction de votre rapport de stage.
                        </p>
                    </div>
                    <a href="?page=gestion_rapports&action=download_modele" class="cm-btn is-outline">
                        <i class="fas fa-file-download"></i>
                        Télécharger le modèle
                    </a>
                </div>
            </div>

            <!-- Section 2 : Upload du rapport -->
            <div style="margin-bottom: 40px;">
                <h3 style="font-size: 1.1rem; color: #2d3748; font-weight: 700; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #e2e8f0;">
                    Déposer un nouveau rapport
                </h3>

                <form method="POST" action="?page=gestion_rapports" enctype="multipart/form-data">
                    <input type="hidden" name="action" value="upload_rapport">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <div class="cm-form-grid">
                        <!-- Thème du rapport -->
                        <div class="cm-etu-field cm-form-full-width">
                            <label class="cm-etu-label" for="theme_rapport">
                                Thème du rapport <span style="color: #a0aec0; font-weight: 400; font-style: italic;">(Important pour le jury)</span>
                            </label>
                            <textarea id="theme_rapport" name="theme_rapport" class="cm-etu-input" 
                                   placeholder="Saisissez le titre exact de votre rapport de stage" required style="resize: vertical; min-height: 80px;" rows="3"></textarea>
                        </div>

                        <!-- Upload fichier -->
                        <div class="cm-etu-field">
                            <label class="cm-etu-label">Fichier du rapport <span style="color: #e53e3e;">*</span></label>
                            <input type="file" name="rapport_fichier" id="rapport_fichier" class="cm-etu-input" 
                                   accept=".pdf,.doc,.docx" required style="padding: 9px 12px;">
                            <p style="font-size: 0.75rem; color: #718096; margin-top: 5px;">
                                PDF or Word. Max 20 Mo.
                            </p>
                        </div>

                        <!-- Options supplémentaires -->
                        <div class="cm-etu-field" style="display: flex; align-items: center; padding-top: 25px;">
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; color: #4a5568; font-size: 0.9rem; font-weight: 500;">
                                <input type="checkbox" name="deposer_apres_upload" value="1" checked style="width: 18px; height: 18px;">
                                Soumettre immédiatement pour évaluation
                            </label>
                        </div>
                    </div>

                    <div style="margin-top: 10px;">
                        <button type="submit" class="cm-btn is-primary">
                            <i class="fas fa-cloud-upload-alt"></i>
                            Enregistrer et télécharger le rapport
                        </button>
                    </div>
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
            <div style="padding-top: 20px;">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h3 style="font-size: 1.1rem; color: #2d3748; font-weight: 700; margin: 0;">
                        Historique des dépôts
                        <span style="font-weight: normal; font-size: 0.85rem; color: #718096; margin-left: 8px;">
                            (<?= $totalRapports ?> document(s))
                        </span>
                    </h3>
                </div>

                <?php if (!empty($rapportsRecents)): ?>
                    <div class="cm-report-grid">
                        <?php foreach ($rapportsRecents as $rapport): ?>
                            <?php
                            $rapportId = (int) ($rapport->id_rapport ?? 0);
                            $infoDepot = $infosDepot[$rapportId] ?? ['peutDeposer' => false, 'dejaDepose' => false];
                            $dejaDepose = (bool) ($infoDepot['dejaDepose'] ?? false);
                            
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
                            $aUnFichier = !empty($rapport->id_rapport);
                            ?>
                            <div class="cm-report-card">
                                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                                    <h4 style="margin: 0; font-size: 1rem; color: #2d3748; font-weight: 600; line-height: 1.4;">
                                        <?= htmlspecialchars((string) ($rapport->nom_rapport ?? 'Rapport'), ENT_QUOTES, 'UTF-8') ?>
                                    </h4>
                                    <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                                </div>
                                
                                <div style="margin-bottom: 15px; background: #f7fafc; padding: 10px; border-radius: 4px; border-left: 3px solid #cbd5e0;">
                                    <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; color: #718096; font-weight: 700; margin-bottom: 4px;">Thème</div>
                                    <div style="color: #4a5568; font-size: 0.9rem; font-style: italic; line-height: 1.5;">
                                        <?= htmlspecialchars((string) ($rapport->theme_rapport ?? 'Non spécifié'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #edf2f7; padding-top: 12px;">
                                    <span style="font-size: 0.8rem; color: #a0aec0;">
                                        <i class="far fa-calendar-alt"></i> <?= $dateAffichage ?>
                                    </span>
                                    <div style="display: flex; gap: 8px;">
                                        <?php if ($aUnFichier): ?>
                                            <a href="?page=gestion_rapports&action=download_fichier_rapport&id=<?= $rapportId ?>" 
                                               class="cm-btn is-outline" style="padding: 6px 12px !important; font-size: 0.8rem !important;" title="Télécharger">
                                                <i class="fas fa-download"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?page=gestion_rapports&action=creer_rapport&edit=<?= $rapportId ?>" 
                                           class="cm-btn is-outline" style="padding: 6px 12px !important; font-size: 0.8rem !important;" title="Voir">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #fff; border: 1px dashed #cbd5e0; border-radius: 8px;">
                        <i class="fas fa-folder-open" style="font-size: 2rem; color: #e2e8f0; margin-bottom: 15px;"></i>
                        <p style="color: #718096; margin: 0;">Aucun document déposé pour le moment.</p>
                    </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<?php
/**
 * PRD 2 – Vue administration : Téléchargement/import du rapport étudiant
 * 
 * Permet à l'admin de :
 * - Voir la liste des étudiants sans rapport (F2.1)
 * - Sélectionner un étudiant et importer son rapport (F2.2, F2.3)
 * - Consulter la liste de tous les rapports (F2.5)
 * - Exporter la liste (PRD 4 F4.3)
 * - Modifier la date d'opération (PRD 3)
 * - Télécharger le modèle de rapport
 * 
 * Variables attendues :
 * - $modeleRapportUrl : URL du modèle
 * - $etudiantsSansRapport : array des étudiants sans rapport
 * - $rapportsAdmin : array de tous les rapports
 * - $typesAutorises : extensions autorisées
 * - $tailleMax : taille max en bytes
 */

$modeleRapportUrl = $GLOBALS['modeleRapportUrl'] ?? '#';
$etudiantsSansRapport = $GLOBALS['etudiantsSansRapport'] ?? [];
$rapportsAdmin = $GLOBALS['rapportsAdmin'] ?? [];
$typesAutorises = $GLOBALS['typesAutorises'] ?? 'pdf,doc,docx';
$tailleMax = $GLOBALS['tailleMax'] ?? 20971520;

$messageSuccess = $_SESSION['success'] ?? null;
$messageError = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$dateSysteme = date('Y-m-d\TH:i');
?>

<div class="cm-admin-screen">
    <div style="max-width: 1200px; margin: 0 auto; padding-top: 10px;">

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

        <!-- En-tête -->
        <div class="cm-card" style="border: 1px solid #eaeaea; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; overflow: hidden; margin-bottom: 20px;">
            <div style="padding: 20px 30px; border-bottom: 1px solid #f0f0f0;">
                <h2 style="font-size: 1.3rem; color: #222; font-weight: 600; margin-bottom: 4px;">
                    <i class="fas fa-file-import" style="color: #2196F3; margin-right: 8px;"></i>
                    Téléchargement du rapport étudiant
                </h2>
                <p style="margin: 0; color: #666;">Importez les rapports de stage pour les étudiants et gérez les dates d'opération.</p>
            </div>

            <!-- Lien modèle -->
            <div style="padding: 15px 30px; background: #fafafa; border-bottom: 1px solid #f0f0f0;">
                <a href="?page=gestion_rapports&action=download_modele" class="cm-btn is-outline-primary is-sm">
                    <i class="fas fa-file-download"></i>
                    Télécharger le modèle de rapport
                </a>
            </div>

            <!-- Formulaire d'import -->
            <div style="padding: 25px 30px; border-bottom: 1px solid #f0f0f0;">
                <h3 style="font-size: 1rem; color: #333; font-weight: 600; margin: 0 0 15px 0;">
                    <i class="fas fa-upload" style="color: #FF9800; margin-right: 6px;"></i>
                    Importer un rapport pour un étudiant
                </h3>

                <form method="POST" action="?page=gestion_rapports" enctype="multipart/form-data" style="max-width: 700px;">
                    <input type="hidden" name="action" value="admin_upload_rapport">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">

                    <!-- Sélection étudiant -->
                    <div style="margin-bottom: 16px;">
                        <label for="num_etu_select" style="display: block; font-weight: 500; color: #444; margin-bottom: 6px; font-size: 0.9rem;">
                            Étudiant <span class="cm-required-star">*</span>
                        </label>
                        <select id="num_etu_select" name="num_etu" class="cm-form-control" required style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem;">
                            <option value="">-- Sélectionnez un étudiant --</option>
                            <?php foreach ($etudiantsSansRapport as $e): ?>
                                <option value="<?= htmlspecialchars($e->num_carte_etud ?? $e->num_ident_etud ?? '', ENT_QUOTES, 'UTF-8') ?>"
                                    data-nom="<?= htmlspecialchars(($e->nom_etu ?? '') . ' ' . ($e->prenom_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-promotion="<?= htmlspecialchars(\FormattingUtils::formatPromotion($e->promotion_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars(($e->nom_etu ?? '') . ' ' . ($e->prenom_etu ?? '') . ' (' . ($e->num_carte_etud ?? $e->num_ident_etud ?? '') . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="etudiant_info" style="margin-top: 8px; padding: 10px 14px; background: #f8f9fa; border-radius: 6px; display: none;">
                            <strong>Matricule :</strong> <span id="info_matricule"></span><br>
                            <strong>Promotion :</strong> <span id="info_promotion"></span>
                        </div>
                    </div>

                    <!-- Thème -->
                    <div style="margin-bottom: 16px;">
                        <label for="admin_theme_rapport" style="display: block; font-weight: 500; color: #444; margin-bottom: 6px; font-size: 0.9rem;">
                            Thème du rapport <span style="color: #999;">(optionnel)</span>
                        </label>
                        <input type="text" 
                               id="admin_theme_rapport" 
                               name="theme_rapport" 
                               class="cm-form-control" 
                               placeholder="Ex: Conception et réalisation d'une application..."
                               style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem;">
                    </div>

                    <!-- Dates -->
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
                        <div>
                            <label style="display: block; font-weight: 500; color: #444; margin-bottom: 6px; font-size: 0.9rem;">
                                Date système <i class="fas fa-info-circle" style="color: #999;" title="Date technique du serveur (non modifiable)"></i>
                            </label>
                            <input type="datetime-local" 
                                   class="cm-form-control" 
                                   value="<?= htmlspecialchars($dateSysteme, ENT_QUOTES, 'UTF-8') ?>" 
                                   disabled 
                                   style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem; background: #f5f5f5;">
                        </div>
                        <div>
                            <label for="date_operation" style="display: block; font-weight: 500; color: #444; margin-bottom: 6px; font-size: 0.9rem;">
                                Date d'opération <i class="fas fa-info-circle" style="color: #999;" title="Date métier modifiable (ex: date de rédaction réelle)"></i>
                            </label>
                            <input type="datetime-local" 
                                   id="date_operation" 
                                   name="date_operation" 
                                   class="cm-form-control" 
                                   value="<?= htmlspecialchars($dateSysteme, ENT_QUOTES, 'UTF-8') ?>"
                                   style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem;">
                        </div>
                    </div>

                    <!-- Upload fichier -->
                    <div style="margin-bottom: 16px;">
                        <?php
                        cm_component('form/file-upload', [
                            'name' => 'rapport_fichier',
                            'id' => 'admin_rapport_fichier',
                            'label' => 'Fichier du rapport',
                            'required' => true,
                            'accept' => '.pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'hint' => 'Formats : PDF, Word (doc/docx). Taille max : 20 Mo.',
                            'size' => 'md',
                        ]);
                        ?>
                    </div>

                    <button type="submit" class="cm-btn is-primary">
                        <i class="fas fa-upload" style="margin-right: 6px;"></i>
                        Importer le rapport
                    </button>
                </form>
            </div>
        </div>

        <!-- Liste des rapports avec export -->
        <div class="cm-card" style="border: 1px solid #eaeaea; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; overflow: hidden;">
            <div style="padding: 20px 30px; border-bottom: 1px solid #f0f0f0;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <h3 style="font-size: 1rem; color: #333; font-weight: 600; margin: 0;">
                        <i class="fas fa-list" style="color: #4CAF50; margin-right: 6px;"></i>
                        Liste des rapports téléchargés
                        <span style="font-weight: normal; color: #888; font-size: 0.85rem;">
                            (<?= count($rapportsAdmin) ?> rapport(s))
                        </span>
                    </h3>
                    <div style="display: flex; gap: 8px;">
                        <a href="?page=gestion_rapports&action=admin_telecharger_rapport<?= $searchTerm ? "&search=" . urlencode($searchTerm) : '' ?>" 
                           class="cm-btn is-light is-sm" onclick="window.print(); return false;">
                            <i class="fas fa-print"></i> Imprimer
                        </a>
                        <a href="?page=gestion_rapports&action=export_rapports_csv<?= $searchTerm ? "&search=" . urlencode($searchTerm) : '' ?>" 
                           class="cm-btn is-outline-primary is-sm">
                            <i class="fas fa-file-csv"></i> Exporter CSV
                        </a>
                    </div>
                </div>

                <!-- Filtre recherche -->
                <form method="GET" action="?page=gestion_rapports" style="margin-top: 15px; display: flex; gap: 10px; max-width: 400px;">
                    <input type="hidden" name="page" value="gestion_rapports">
                    <input type="hidden" name="action" value="admin_telecharger_rapport">
                    <input type="text" name="search" class="cm-form-control" placeholder="Rechercher un étudiant..." 
                           value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>"
                           style="flex: 1; padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 0.9rem;">
                    <button type="submit" class="cm-btn is-primary is-sm">
                        <i class="fas fa-search"></i> Filtrer
                    </button>
                    <?php if ($searchTerm): ?>
                        <a href="?page=gestion_rapports&action=admin_telecharger_rapport" class="cm-btn is-light is-sm">
                            <i class="fas fa-times"></i> Réinitialiser
                        </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Tableau des rapports -->
            <div style="padding: 20px 30px; overflow-x: auto;">
                <?php if (!empty($rapportsAdmin)): ?>
                    <table class="cm-data-table" style="width: 100%; border-collapse: collapse; font-size: 0.9rem;">
                        <thead>
                            <tr style="background: #f8f9fa; border-bottom: 2px solid #e9ecef;">
                                <th style="padding: 10px 12px; text-align: left; font-weight: 600; color: #555;">Étudiant</th>
                                <th style="padding: 10px 12px; text-align: center; font-weight: 600; color: #555;">Statut</th>
                                <th style="padding: 10px 12px; text-align: center; font-weight: 600; color: #555;">Date opération</th>
                                <th style="padding: 10px 12px; text-align: center; font-weight: 600; color: #555;">Taille</th>
                                <th style="padding: 10px 12px; text-align: center; font-weight: 600; color: #555;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapportsAdmin as $r): ?>
                                <?php
                                $statutBadge = 'light';
                                $statutText = 'Brouillon';
                                $s = strtolower((string) ($r->statut_rapport ?? ''));
                                if ($s === 'en_cours' || $s === 'en_attente') {
                                    $statutBadge = 'info';
                                    $statutText = 'En attente';
                                } elseif ($s === 'valider') {
                                    $statutBadge = 'success';
                                    $statutText = 'Validé';
                                } elseif ($s === 'rejeter') {
                                    $statutBadge = 'danger';
                                    $statutText = 'Rejeté';
                                }
                                $aUnFichier = !empty($r->chemin_fichier);
                                $taille = $r->taille_fichier ? round($r->taille_fichier / 1024, 1) . ' Ko' : '-';
                                $dateOp = !empty($r->date_operation) ? date('d/m/Y H:i', strtotime($r->date_operation)) : '-';
                                $matricule = $r->num_etu ?? '-';
                                ?>
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding: 10px 12px; font-weight: 500;">
                                        <?= htmlspecialchars(($r->nom_etu ?? '') . ' ' . ($r->prenom_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center;">
                                        <?php cm_component('ui/badge', ['type' => $statutBadge, 'text' => $statutText]); ?>
                                    </td>
                                    <td style="padding: 10px 12px; text-align: center; color: #666; font-size: 0.85rem;"><?= $dateOp ?></td>
                                    <td style="padding: 10px 12px; text-align: center; color: #666; font-size: 0.85rem;"><?= $taille ?></td>
                                    <td style="padding: 10px 12px; text-align: center;">
                                        <div style="display: flex; gap: 4px; justify-content: center; flex-wrap: wrap;">
                                            <?php if ($aUnFichier): ?>
                                                <a href="?page=gestion_rapports&action=download_fichier_rapport&id=<?= (int) ($r->id_rapport ?? 0) ?>" 
                                                   class="cm-btn is-light is-xs" title="Télécharger">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                            <?php endif; ?>
                                            <!-- Modal : modifier date opération (PRD 3) -->
                                            <button type="button" class="cm-btn is-light is-xs" title="Modifier la date" 
                                                    onclick="openDateModal(<?= (int) ($r->id_rapport ?? 0) ?>, '<?= htmlspecialchars($r->date_operation ?? '', ENT_QUOTES, 'UTF-8') ?>')">
                                                <i class="fas fa-calendar-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; background: #fafafa; border-radius: 8px;">
                        <i class="fas fa-inbox" style="font-size: 2.5rem; color: #ddd; margin-bottom: 15px;"></i>
                        <p style="color: #666;">Aucun rapport trouvé.</p>
                        <?php if ($searchTerm): ?>
                            <p style="color: #999; font-size: 0.9rem;">Essayez de modifier votre recherche.</p>
                        <?php else: ?>
                            <p style="color: #999; font-size: 0.9rem;">Importez un rapport via le formulaire ci-dessus.</p>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Modal : Modification date opération (PRD 3) -->
<div id="dateOperationModal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
    <div style="background: #fff; border-radius: 12px; padding: 30px; max-width: 450px; width: 90%; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
        <h3 style="font-size: 1.1rem; color: #222; margin: 0 0 15px 0;">
            <i class="fas fa-calendar-edit" style="color: #2196F3; margin-right: 8px;"></i>
            Modifier la date d'opération
        </h3>
        <form method="POST" action="?page=gestion_rapports" style="margin: 0;">
            <input type="hidden" name="action" value="update_date_operation">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Csrf::token(), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_rapport" id="modal_id_rapport" value="">

            <div style="margin-bottom: 16px;">
                <label for="modal_date_operation" style="display: block; font-weight: 500; color: #444; margin-bottom: 6px; font-size: 0.9rem;">
                    Nouvelle date d'opération <span class="cm-required-star">*</span>
                </label>
                <input type="datetime-local" 
                       id="modal_date_operation" 
                       name="date_operation" 
                       class="cm-form-control" 
                       required
                       style="width: 100%; padding: 10px 14px; border: 1px solid #ddd; border-radius: 8px; font-size: 0.9rem;">
            </div>

            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="cm-btn is-light" onclick="closeDateModal()">Annuler</button>
                <button type="submit" class="cm-btn is-primary">
                    <i class="fas fa-save" style="margin-right: 4px;"></i> Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openDateModal(idRapport, dateActuelle) {
    document.getElementById('modal_id_rapport').value = idRapport;
    var dateField = document.getElementById('modal_date_operation');
    if (dateActuelle && dateActuelle !== '-' && dateActuelle !== '') {
        // Format ISO pour datetime-local
        try {
            var d = new Date(dateActuelle);
            dateField.value = d.toISOString().slice(0, 16);
        } catch(e) {
            dateField.value = '';
        }
    } else {
        dateField.value = '';
    }
    document.getElementById('dateOperationModal').style.display = 'flex';
}

function closeDateModal() {
    document.getElementById('dateOperationModal').style.display = 'none';
}

// Fermer le modal en cliquant à l'extérieur
document.addEventListener('click', function(e) {
    var modal = document.getElementById('dateOperationModal');
    if (modal.style.display === 'flex' && e.target === modal) {
        closeDateModal();
    }
});

// Afficher les infos étudiant lors de la sélection
document.addEventListener('DOMContentLoaded', function() {
    var select = document.getElementById('num_etu_select');
    var infoDiv = document.getElementById('etudiant_info');
    
    if (select) {
        select.addEventListener('change', function() {
            var selected = select.options[select.selectedIndex];
            if (selected && selected.value) {
                document.getElementById('info_matricule').textContent = selected.value;
                document.getElementById('info_promotion').textContent = selected.getAttribute('data-promotion') || '-';
                infoDiv.style.display = 'block';
            } else {
                infoDiv.style.display = 'none';
            }
        });
    }
});
</script>

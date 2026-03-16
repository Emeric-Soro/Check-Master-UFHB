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

<div class="cm-prd3-screen cm-prd3-crud-screen">
    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <div class="cm-flex cm-flex-between cm-flex-align-center cm-mb-md">
                <span class="cm-etu-count-badge"><?= $totalReclamations ?> réclamation<?= $totalReclamations > 1 ? 's' : '' ?></span>
                <?php if (canCreate()): ?>
                    <button type="button" class="cm-btn is-primary is-sm" id="cmBtnNouvelleReclamation">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span>Nouvelle réclamation</span>
                    </button>
                <?php endif; ?>
            </div>

            <?php if (is_array($message)): ?>
                <?php cm_component('ui/alert-box', [
                    'type' => ($message['type'] ?? '') === 'success' ? 'success' : 'danger',
                    'message' => (string) ($message['text'] ?? ''),
                ]); ?>
            <?php endif; ?>

            <div id="cmReclamationFormSection" style="display:none;" class="cm-mt-md">
                <style>
/* cm-form-local-overrides: ajustements locaux de ce formulaire (editez dans ce fichier) */
#cmReclamationForm .cm-form-group:has(#FIELD_ID) {
    width: 10ch !important;
    min-width: 10ch !important;
    max-width: 10ch !important;
}
</style>
<form method="POST" action="?page=gestion_reclamations&action=soumettre_reclamation" id="cmReclamationForm">
                    <?php cm_component('form/csrf-token'); ?>
                    <div class="cm-grid-2">
                        <?php cm_component('form/input-text', [
                            'name' => 'objet',
                            'id' => 'cmReclObjet',
                            'label' => 'Objet',
                            'required' => true,
                            'control_class' => 'cm-field-md',
                        ]); ?>
                    </div>
                    <?php cm_component('form/textarea', [
                        'name' => 'content',
                        'id' => 'cmReclContent',
                        'label' => 'Description',
                        'rows' => 4,
                        'control_class' => 'cm-field-full',
                    ]); ?>
                    <?php cm_component('crud/form-actions', [
                        'cancel_action' => ['label' => 'Annuler', 'type' => 'button', 'class' => 'cm-btn is-light is-sm', 'attrs' => ['id' => 'cmReclAnnulerBtn', 'data-reset-form' => '1']],
                        'actions' => [
                            ['label' => 'Réinitialiser', 'type' => 'reset', 'class' => 'cm-btn is-secondary is-sm'],
                            ['label' => 'Envoyer', 'type' => 'submit', 'class' => 'cm-btn is-primary is-sm', 'icon' => 'fa-paper-plane'],
                        ],
                    ]); ?>
                </form>
            </div>
        </div>

        <?php cm_toolbar([
            'screen' => 'gestion_reclamations',
            'id_prefix' => 'cmReclamation',
            'search_value' => $_GET['search'] ?? '',
            'limit' => 10,
            'limit_options' => [5, 10, 25, 50, 100],
            'can_delete' => canDelete(),
            'can_view' => canView(),
        ]); ?>

        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmReclamationTable">
                    <thead>
                    <tr>
                        <th class="cm-data-table__th cm-data-table__th--check">
                            <input type="checkbox" id="cmReclamationCheckAll" aria-label="Tout sélectionner">
                        </th>
                        <th class="cm-data-table__th">N° Réclamation</th>
                        <th class="cm-data-table__th">Objet</th>
                        <th class="cm-data-table__th">Date dépôt</th>
                        <th class="cm-data-table__th">Statut</th>
                        <th class="cm-data-table__th is-center">Actions</th>
                    </tr>
                    </thead>
                    <tbody id="cmReclamationTableBody">
                    <?php if (empty($reclamationsRecentes)): ?>
                        <?php cm_component('ui/empty-state', [
                            'in_table' => true,
                            'colspan' => 6,
                            'title' => '',
                            'message' => 'Aucune réclamation pour le moment.',
                        ]); ?>
                    <?php else: ?>
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
                            $idRecl = (string) ($reclamation->id_reclamation ?? $reclamation['id_reclamation'] ?? '-');
                            $objetRecl = (string) ($reclamation->objet ?? $reclamation['objet'] ?? '-');
                            $dateRecl = (string) ($reclamation->date_reclamation ?? $reclamation['date_reclamation'] ?? '');
                            $dateAffichee = $dateRecl !== '' ? date('d/m/Y', strtotime($dateRecl)) : '-';
                            ?>
                            <tr class="cm-data-table__row">
                                <td class="cm-data-table__td cm-data-table__td--check">
                                    <input type="checkbox" class="cm-recl-check-row" value="<?= htmlspecialchars($idRecl, ENT_QUOTES, 'UTF-8') ?>" aria-label="Sélectionner réclamation <?= htmlspecialchars($idRecl, ENT_QUOTES, 'UTF-8') ?>">
                                </td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($idRecl, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($objetRecl, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td"><?= htmlspecialchars($dateAffichee, ENT_QUOTES, 'UTF-8') ?></td>
                                <td class="cm-data-table__td">
                                    <?php cm_component('ui/badge', ['type' => $badgeType, 'text' => $badgeText]); ?>
                                </td>
                                <td class="cm-data-table__td is-center">
                                    <div class="cm-table-actions">
                                        <a href="?page=gestion_reclamations&action=suivi_historique_reclamation"
                                           class="cm-btn-action is-view" title="Consulter">
                                            <i class="fas fa-eye" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
(function () {
    const btn = document.getElementById('cmBtnNouvelleReclamation');
    const section = document.getElementById('cmReclamationFormSection');
    const cancelBtn = document.getElementById('cmReclAnnulerBtn');
    if (btn && section) {
        btn.addEventListener('click', function () {
            section.style.display = section.style.display === 'none' ? '' : 'none';
        });
    }
    if (cancelBtn && section) {
        cancelBtn.addEventListener('click', function () {
            section.style.display = 'none';
        });
    }
})();
</script>

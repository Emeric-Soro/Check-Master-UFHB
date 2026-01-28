<?php
/**
 * CheckMaster Premium - Gestion des Ressources Humaines
 * Vue refactorisée avec Premium Design System
 */

// ========== DONNÉES ET LOGIQUE PHP ==========

$activeTab = $_GET['tab'] ?? 'pers_admin';
if (!in_array($activeTab, ['pers_admin', 'enseignant'])) {
    $activeTab = 'pers_admin';
}

$messageErreur = $GLOBALS['messageErreur'] ?? '';
$messageSuccess = $GLOBALS['messageSuccess'] ?? '';

$personnel_admin = $GLOBALS['listePersAdmin'] ?? [];
$enseignants = $GLOBALS['listeEnseignants'] ?? [];
$listeGrades = $GLOBALS['listeGrades'] ?? [];
$listeFonctions = $GLOBALS['listeFonctions'] ?? [];
$listeSpecialites = $GLOBALS['listeSpecialites'] ?? [];

$pers_admin_a_modifier = $GLOBALS['pers_admin_a_modifier'] ?? null;
$enseignant_a_modifier = $GLOBALS['enseignant_a_modifier'] ?? null;

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;

// Notifications
if (!empty($messageSuccess)) {
    echo renderAlert($messageSuccess, 'success');
}
if (!empty($messageErreur)) {
    echo renderAlert($messageErreur, 'error');
}

// ========== ONGLETS ==========
?>

<div class="tabs mb-lg">
    <button class="tab-button <?= $activeTab === 'pers_admin' ? 'active' : '' ?>" 
            onclick="window.location.href='?page=gestion_rh&tab=pers_admin'">
        <i class="fas fa-users-cog"></i> Personnel administratif
    </button>
    <button class="tab-button <?= $activeTab === 'enseignant' ? 'active' : '' ?>" 
            onclick="window.location.href='?page=gestion_rh&tab=enseignant'">
        <i class="fas fa-user-tag"></i> Enseignants
    </button>
</div>

<?php
// ========== ONGLET PERSONNEL ADMINISTRATIF ==========
if ($activeTab === 'pers_admin'):
    ob_start();
?>

<!-- Barre d'actions -->
<div class="flex flex-col sm:flex-row justify-between items-center gap-md mb-md">
    <?= renderSearchBar('Rechercher un personnel...', 'searchInput', '', [
        'attr' => 'onkeyup="searchTable(\'searchInput\', \'pers_admin\')"'
    ]) ?>
    
    <div class="flex gap-sm flex-wrap">
        <?= renderButton('Imprimer', 'outline', true, 'onclick="printTable(\'pers_admin\')" type="button"', '', 'fa-print') ?>
        <?= renderButton('Exporter', 'outline', true, 'onclick="exportToExcel(\'pers_admin\')" type="button"', '', 'fa-file-export') ?>
        <?= renderButton('Supprimer', 'danger', canDelete(), 'id="deleteButtonPersAdmin" type="button" disabled', '', 'fa-trash') ?>
    </div>
</div>

<!-- Tableau -->
<form method="POST" action="?page=gestion_rh&tab=pers_admin" id="formListePersAdmin">
    <input type="hidden" name="submit_delete_multiple" id="submitDeleteHidden" value="0">
    
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <?php if (canDelete()): ?>
                            <input type="checkbox" id="selectAllCheckbox" class="form-checkbox">
                        <?php endif; ?>
                    </th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Grade</th>
                    <th>Fonction</th>
                    <?php if (canEdit()): ?>
                    <th class="text-right">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($personnel_admin)): ?>
                <tr>
                    <td colspan="7" class="text-center py-8">
                        <?= renderEmptyState('Aucun personnel trouvé', 'fa-users-cog', 
                            renderButtonLink('Ajouter un personnel', '?page=gestion_rh&tab=pers_admin&action=add', 'primary', canCreate())
                        ) ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($personnel_admin as $admin): ?>
                    <tr>
                        <td>
                            <?php if (canDelete()): ?>
                                <input type="checkbox" name="selected_ids[]" 
                                       value="<?= htmlspecialchars($admin->id_pers_admin) ?>" 
                                       class="form-checkbox checkbox-item">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($admin->nom_pers_admin) ?></td>
                        <td><?= htmlspecialchars($admin->prenom_pers_admin) ?></td>
                        <td>
                            <div class="flex items-center gap-sm">
                                <i class="fas fa-envelope text-muted"></i>
                                <?= htmlspecialchars($admin->mail_pers_admin) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($admin->lib_grade ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($admin->lib_fonction ?? 'N/A') ?></td>
                        <?php if (canEdit()): ?>
                        <td>
                            <div class="table-actions justify-end">
                                <a href="?page=gestion_rh&tab=pers_admin&action=edit&id=<?= $admin->id_pers_admin ?>" 
                                   class="table-action edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<?php
    $tableHtml = ob_get_clean();
    
    $headerActions = '';
    if (canCreate()) {
        $headerActions = renderButtonLink('Ajouter un personnel', '?page=gestion_rh&tab=pers_admin&action=add', 'primary', true, '', '', 'fa-plus');
    }
    
    echo renderFullCard(
        'Gestion du personnel administratif',
        $tableHtml,
        $headerActions,
        '',
        'Liste et gestion des membres du personnel administratif'
    );
endif;

// ========== ONGLET ENSEIGNANTS ==========
if ($activeTab === 'enseignant'):
    ob_start();
?>

<!-- Barre d'actions -->
<div class="flex flex-col sm:flex-row justify-between items-center gap-md mb-md">
    <?= renderSearchBar('Rechercher un enseignant...', 'searchInputEnseignant', '', [
        'attr' => 'onkeyup="searchTable(\'searchInputEnseignant\', \'enseignant\')"'
    ]) ?>
    
    <div class="flex gap-sm flex-wrap">
        <?= renderButton('Imprimer', 'outline', true, 'onclick="printTable(\'enseignant\')" type="button"', '', 'fa-print') ?>
        <?= renderButton('Exporter', 'outline', true, 'onclick="exportToExcel(\'enseignant\')" type="button"', '', 'fa-file-export') ?>
        <?= renderButton('Supprimer', 'danger', canDelete(), 'id="deleteButtonEnseignant" type="button" disabled', '', 'fa-trash') ?>
    </div>
</div>

<!-- Tableau -->
<form method="POST" action="?page=gestion_rh&tab=enseignant" id="formListeEnseignant">
    <input type="hidden" name="submit_delete_multiple" id="submitDeleteHiddenEnseignant" value="0">
    
    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <?php if (canDelete()): ?>
                            <input type="checkbox" id="selectAllCheckboxEnseignant" class="form-checkbox">
                        <?php endif; ?>
                    </th>
                    <th>Nom</th>
                    <th>Prénom</th>
                    <th>Email</th>
                    <th>Spécialité</th>
                    <th>Grade</th>
                    <th>Fonction</th>
                    <th>Type</th>
                    <?php if (canEdit()): ?>
                    <th class="text-right">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($enseignants)): ?>
                <tr>
                    <td colspan="9" class="text-center py-8">
                        <?= renderEmptyState('Aucun enseignant trouvé', 'fa-user-tag', 
                            renderButtonLink('Ajouter un enseignant', '?page=gestion_rh&tab=enseignant&action=add', 'primary', canCreate())
                        ) ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($enseignants as $enseignant): ?>
                    <tr>
                        <td>
                            <?php if (canDelete()): ?>
                                <input type="checkbox" name="selected_ids[]" 
                                       value="<?= htmlspecialchars($enseignant->id_enseignant) ?>" 
                                       class="form-checkbox checkbox-item">
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($enseignant->nom_enseignant) ?></td>
                        <td><?= htmlspecialchars($enseignant->prenom_enseignant) ?></td>
                        <td>
                            <div class="flex items-center gap-sm">
                                <i class="fas fa-envelope text-muted"></i>
                                <?= htmlspecialchars($enseignant->mail_enseignant) ?>
                            </div>
                        </td>
                        <td><?= htmlspecialchars($enseignant->lib_specialite ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($enseignant->lib_grade ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($enseignant->lib_fonction ?? 'N/A') ?></td>
                        <td><?= renderBadge($enseignant->type_enseignant ?? 'Simple', $enseignant->type_enseignant === 'Administratif' ? 'info' : 'secondary') ?></td>
                        <?php if (canEdit()): ?>
                        <td>
                            <div class="table-actions justify-end">
                                <a href="?page=gestion_rh&tab=enseignant&action=edit&id=<?= $enseignant->id_enseignant ?>" 
                                   class="table-action edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<?php
    $tableHtml = ob_get_clean();
    
    $headerActions = '';
    if (canCreate()) {
        $headerActions = renderButtonLink('Ajouter un enseignant', '?page=gestion_rh&tab=enseignant&action=add', 'primary', true, '', '', 'fa-plus');
    }
    
    echo renderFullCard(
        'Gestion des enseignants',
        $tableHtml,
        $headerActions,
        '',
        'Liste et gestion des enseignants'
    );
endif;

// ========== MODALES ==========

// Modal Formulaire Personnel Administratif
if ($activeTab === 'pers_admin' && ($action === 'add' || $action === 'edit')):
    ob_start();
?>
<form action="?page=gestion_rh&tab=pers_admin" method="POST" id="formPersAdmin">
    <?php if ($action === 'edit' && $pers_admin_a_modifier): ?>
        <input type="hidden" name="id_pers_admin" value="<?= htmlspecialchars($pers_admin_a_modifier->id_pers_admin) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-user text-primary"></i> Nom
            </label>
            <input type="text" name="nom" required class="form-input"
                   value="<?= $action === 'edit' && $pers_admin_a_modifier ? htmlspecialchars($pers_admin_a_modifier->nom_pers_admin) : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-user text-primary"></i> Prénom
            </label>
            <input type="text" name="prenom" required class="form-input"
                   value="<?= $action === 'edit' && $pers_admin_a_modifier ? htmlspecialchars($pers_admin_a_modifier->prenom_pers_admin) : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-envelope text-primary"></i> Email
            </label>
            <input type="email" name="email" required class="form-input"
                   value="<?= $action === 'edit' && $pers_admin_a_modifier ? htmlspecialchars($pers_admin_a_modifier->mail_pers_admin) : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-graduation-cap text-primary"></i> Grade
            </label>
            <select name="id_grade" required class="form-select">
                <option value="">Sélectionner un grade</option>
                <?php foreach ($listeGrades as $grade): ?>
                    <option value="<?= htmlspecialchars($grade->id_grade) ?>"
                            <?= ($action === 'edit' && $pers_admin_a_modifier && $pers_admin_a_modifier->id_grade == $grade->id_grade) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($grade->lib_grade) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-briefcase text-primary"></i> Fonction
            </label>
            <select name="id_fonction" required class="form-select">
                <option value="">Sélectionner une fonction</option>
                <?php foreach ($listeFonctions as $fonction): ?>
                    <option value="<?= htmlspecialchars($fonction->id_fonction) ?>"
                            <?= ($action === 'edit' && $pers_admin_a_modifier && $pers_admin_a_modifier->id_fonction == $fonction->id_fonction) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fonction->lib_fonction) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-calendar text-primary"></i> Date d'occupation de la fonction
            </label>
            <input type="date" name="date_fonction" required class="form-input"
                   value="<?= $action === 'edit' && $pers_admin_a_modifier ? htmlspecialchars($pers_admin_a_modifier->date_occupation ?? '') : '' ?>">
        </div>
    </div>
</form>
<?php
    $formContent = ob_get_clean();
    
    $modalFooter = renderButton('Annuler', 'secondary', true, 'type="button" onclick="window.location.href=\'?page=gestion_rh&tab=pers_admin\'"');
    $modalFooter .= ' ';
    $modalFooter .= renderButton(
        $action === 'edit' ? 'Modifier' : 'Ajouter', 
        'primary', 
        true, 
        'type="submit" name="' . ($action === 'edit' ? 'btn_modifier_pers_admin' : 'btn_add_pers_admin') . '" form="formPersAdmin"',
        '',
        'fa-save'
    );
    
    echo renderModal(
        'modalPersAdmin',
        ($action === 'edit' ? 'Modifier' : 'Ajouter') . ' un personnel administratif',
        $formContent,
        $modalFooter,
        'lg'
    );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        CM.Modal.show('modalPersAdmin');
    });
    </script>
    <?php
endif;

// Modal Formulaire Enseignant
if ($activeTab === 'enseignant' && ($action === 'add' || $action === 'edit')):
    ob_start();
?>
<form action="?page=gestion_rh&tab=enseignant" method="POST" id="formEnseignant">
    <?php if ($action === 'edit' && $enseignant_a_modifier): ?>
        <input type="hidden" name="id_enseignant" value="<?= htmlspecialchars($enseignant_a_modifier->id_enseignant) ?>">
    <?php endif; ?>
    
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-user text-primary"></i> Nom
            </label>
            <input type="text" name="nom" required class="form-input"
                   value="<?= $action === 'edit' && $enseignant_a_modifier ? htmlspecialchars($enseignant_a_modifier->nom_enseignant) : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-user text-primary"></i> Prénom
            </label>
            <input type="text" name="prenom" required class="form-input"
                   value="<?= $action === 'edit' && $enseignant_a_modifier ? htmlspecialchars($enseignant_a_modifier->prenom_enseignant) : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-envelope text-primary"></i> Email
            </label>
            <input type="email" name="email" required class="form-input"
                   value="<?= $action === 'edit' && $enseignant_a_modifier ? htmlspecialchars($enseignant_a_modifier->mail_enseignant) : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-book text-primary"></i> Spécialité
            </label>
            <select name="id_specialite" required class="form-select">
                <option value="">Sélectionner une spécialité</option>
                <?php foreach ($listeSpecialites as $specialite): ?>
                    <option value="<?= htmlspecialchars($specialite->id_specialite) ?>"
                            <?= ($action === 'edit' && $enseignant_a_modifier && $enseignant_a_modifier->id_specialite == $specialite->id_specialite) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($specialite->lib_specialite) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-briefcase text-primary"></i> Fonction
            </label>
            <select name="id_fonction" required class="form-select">
                <option value="">Sélectionner une fonction</option>
                <?php foreach ($listeFonctions as $fonction): ?>
                    <option value="<?= htmlspecialchars($fonction->id_fonction) ?>"
                            <?= ($action === 'edit' && $enseignant_a_modifier && $enseignant_a_modifier->id_fonction == $fonction->id_fonction) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($fonction->lib_fonction) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-calendar text-primary"></i> Date d'occupation de la fonction
            </label>
            <input type="date" name="date_fonction" required class="form-input"
                   value="<?= $action === 'edit' && $enseignant_a_modifier ? htmlspecialchars($enseignant_a_modifier->date_occupation ?? '') : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-graduation-cap text-primary"></i> Grade
            </label>
            <select name="id_grade" required class="form-select">
                <option value="">Sélectionner un grade</option>
                <?php foreach ($listeGrades as $grade): ?>
                    <option value="<?= htmlspecialchars($grade->id_grade) ?>"
                            <?= ($action === 'edit' && $enseignant_a_modifier && $enseignant_a_modifier->id_grade == $grade->id_grade) ? 'selected' : '' ?>>
                        <?= htmlspecialchars($grade->lib_grade) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-calendar text-primary"></i> Date d'obtention du grade
            </label>
            <input type="date" name="date_grade" required class="form-input"
                   value="<?= $action === 'edit' && $enseignant_a_modifier ? htmlspecialchars($enseignant_a_modifier->date_grade ?? '') : '' ?>">
        </div>
        
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-tag text-primary"></i> Type d'enseignant
            </label>
            <select name="type_enseignant" required class="form-select">
                <option value="Simple" <?= ($action === 'edit' && $enseignant_a_modifier && $enseignant_a_modifier->type_enseignant === 'Simple') ? 'selected' : '' ?>>Simple</option>
                <option value="Administratif" <?= ($action === 'edit' && $enseignant_a_modifier && $enseignant_a_modifier->type_enseignant === 'Administratif') ? 'selected' : '' ?>>Administratif</option>
            </select>
        </div>
    </div>
</form>
<?php
    $formContent = ob_get_clean();
    
    $modalFooter = renderButton('Annuler', 'secondary', true, 'type="button" onclick="window.location.href=\'?page=gestion_rh&tab=enseignant\'"');
    $modalFooter .= ' ';
    $modalFooter .= renderButton(
        $action === 'edit' ? 'Modifier' : 'Ajouter', 
        'primary', 
        true, 
        'type="submit" name="' . ($action === 'edit' ? 'btn_modifier_enseignant' : 'btn_add_enseignant') . '" form="formEnseignant"',
        '',
        'fa-save'
    );
    
    echo renderModal(
        'modalEnseignant',
        ($action === 'edit' ? 'Modifier' : 'Ajouter') . ' un enseignant',
        $formContent,
        $modalFooter,
        'xl'
    );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        CM.Modal.show('modalEnseignant');
    });
    </script>
    <?php
endif;

// Modal Confirmation de suppression
echo renderConfirmModal(
    'deleteModal',
    'Confirmation de suppression',
    'Êtes-vous sûr de vouloir supprimer les éléments sélectionnés ?',
    '#',
    'Confirmer',
    'danger'
);
?>

<!-- ========== JAVASCRIPT ========== -->

<script>
// ========== GESTION DES CHECKBOXES ==========

function setupCheckboxes(formId, selectAllId, deleteButtonId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    const selectAllCheckbox = document.getElementById(selectAllId);
    const checkboxes = form.querySelectorAll('.checkbox-item');
    const deleteButton = document.getElementById(deleteButtonId);
    
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', function() {
            const visibleCheckboxes = Array.from(checkboxes).filter(cb => 
                cb.closest('tr').style.display !== 'none'
            );
            visibleCheckboxes.forEach(cb => cb.checked = this.checked);
            updateDeleteButton();
        });
    }
    
    checkboxes.forEach(cb => {
        cb.addEventListener('change', updateDeleteButton);
    });
    
    function updateDeleteButton() {
        const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
        if (deleteButton) {
            deleteButton.disabled = checkedCount === 0;
        }
        if (selectAllCheckbox) {
            const visibleCheckboxes = Array.from(checkboxes).filter(cb => 
                cb.closest('tr').style.display !== 'none'
            );
            const checkedVisible = visibleCheckboxes.filter(cb => cb.checked).length;
            selectAllCheckbox.checked = checkedVisible === visibleCheckboxes.length && visibleCheckboxes.length > 0;
        }
    }
    
    if (deleteButton) {
        deleteButton.addEventListener('click', function() {
            CM.Modal.show('deleteModal');
            document.querySelector('#deleteModal .modal-footer .btn-danger').onclick = function() {
                const submitField = form.querySelector('input[name="submit_delete_multiple"]');
                if (submitField) submitField.value = '1';
                form.submit();
            };
        });
    }
}

// ========== RECHERCHE ==========

function searchTable(inputId, type) {
    const input = document.getElementById(inputId);
    const table = document.querySelector(`#tab_${type} table, table`);
    const filter = input.value.toUpperCase();
    const rows = table.querySelectorAll('tbody tr');
    
    rows.forEach(row => {
        const text = row.textContent || row.innerText;
        row.style.display = text.toUpperCase().indexOf(filter) > -1 ? '' : 'none';
    });
}

// ========== EXPORT ET IMPRESSION ==========

function printTable(type) {
    window.print();
}

function exportToExcel(type) {
    const table = document.querySelector('table');
    let html = '<table>';
    
    table.querySelectorAll('thead tr').forEach(row => {
        html += '<tr>';
        row.querySelectorAll('th').forEach((th, i) => {
            if (i > 0 && i < row.querySelectorAll('th').length - 1) {
                html += '<th>' + th.textContent + '</th>';
            }
        });
        html += '</tr>';
    });
    
    table.querySelectorAll('tbody tr').forEach(row => {
        if (row.style.display !== 'none') {
            html += '<tr>';
            row.querySelectorAll('td').forEach((td, i) => {
                if (i > 0 && i < row.querySelectorAll('td').length - 1) {
                    html += '<td>' + td.textContent.trim() + '</td>';
                }
            });
            html += '</tr>';
        }
    });
    
    html += '</table>';
    
    const blob = new Blob(['\ufeff' + html], { type: 'application/vnd.ms-excel' });
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = type + '_' + new Date().toISOString().split('T')[0] + '.xls';
    link.click();
}

// ========== INITIALISATION ==========

document.addEventListener('DOMContentLoaded', function() {
    setupCheckboxes('formListePersAdmin', 'selectAllCheckbox', 'deleteButtonPersAdmin');
    setupCheckboxes('formListeEnseignant', 'selectAllCheckboxEnseignant', 'deleteButtonEnseignant');
});
</script>

<style>
.tabs {
    display: flex;
    border-bottom: 2px solid var(--border);
    gap: var(--spacing-xs);
}

.tab-button {
    padding: var(--spacing-sm) var(--spacing-md);
    border: none;
    background: transparent;
    cursor: pointer;
    font-weight: 500;
    color: var(--text-muted);
    border-bottom: 2px solid transparent;
    margin-bottom: -2px;
    transition: all 0.2s ease;
}

.tab-button:hover {
    color: var(--primary);
    background: var(--muted-light);
}

.tab-button.active {
    color: var(--primary);
    border-bottom-color: var(--primary);
    background: var(--primary-light);
}
</style>

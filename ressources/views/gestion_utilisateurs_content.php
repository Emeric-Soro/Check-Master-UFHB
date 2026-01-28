<?php
/**
 * CheckMaster Premium - Gestion des Utilisateurs
 * Vue refactorisée avec Premium Design System
 */

// ========== DONNÉES ET LOGIQUE PHP ==========

$utilisateur_a_modifier = $GLOBALS['utilisateur_a_modifier'] ?? null;
$showModal = isset($_GET['action']) && in_array($_GET['action'], ['edit', 'add', 'addMasse']);

$utilisateurs = $GLOBALS['utilisateurs'] ?? [];
$niveau_acces = $GLOBALS['niveau_acces'] ?? [];
$types_utilisateur = $GLOBALS['types_utilisateur'] ?? [];
$groupes_utilisateur = $GLOBALS['groupes_utilisateur'] ?? [];
$enseignantsNonUtilisateurs = $GLOBALS['enseignantsNonUtilisateurs'] ?? [];
$personnelNonUtilisateurs = $GLOBALS['personnelNonUtilisateurs'] ?? [];
$etudiantsNonUtilisateurs = $GLOBALS['etudiantsNonUtilisateurs'] ?? [];

// Statistiques
$allUtilisateurs = $GLOBALS['utilisateurs'] ?? [];
$totalUtilisateurs = count($allUtilisateurs);
$utilisateursActifs = count(array_filter($allUtilisateurs, fn($u) => $u->statut_utilisateur === 'Actif'));
$utilisateursInactifs = $totalUtilisateurs - $utilisateursActifs;

// Pagination
$page = isset($_GET['p']) ? max(1, (int)$_GET['p']) : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Recherche
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
if (!empty($search)) {
    $allUtilisateurs = array_filter($allUtilisateurs, function($utilisateur) use ($search) {
        return stripos($utilisateur->nom_utilisateur, $search) !== false ||
               stripos($utilisateur->prenom_utilisateur ?? '', $search) !== false ||
               stripos($utilisateur->email_utilisateur ?? '', $search) !== false;
    });
}

// Tri par ID décroissant
usort($allUtilisateurs, fn($a, $b) => ($b->id_utilisateur ?? 0) <=> ($a->id_utilisateur ?? 0));

// Calcul pagination
$total_items = count($allUtilisateurs);
$total_pages = max(1, ceil($total_items / $limit));
$page = min($page, $total_pages);

// Extraction de la page courante
$utilisateurs = array_slice($allUtilisateurs, $offset, $limit);

// Grouper les utilisateurs par groupe
$grouped = [];
foreach ($utilisateurs as $user) {
    $groupName = $user->lib_GU ?? 'Sans groupe';
    if (!isset($grouped[$groupName])) {
        $grouped[$groupName] = [];
    }
    $grouped[$groupName][] = $user;
}

// ========== CONSTRUCTION DU HTML DU TABLEAU ==========

ob_start();
?>

<!-- Barre d'actions -->
<div class="flex flex-col sm:flex-row justify-between items-center gap-md mb-md">
    <?php echo renderSearchBar('Rechercher un utilisateur...', 'search', $search, [
        'showButton' => false,
        'attr' => 'id="searchInput" onsubmit="return false"'
    ]); ?>
    
    <div class="flex gap-sm flex-wrap">
        <?php echo renderButton('Imprimer', 'outline', true, 'onclick="printTable()"', '', 'fa-print'); ?>
        <?php echo renderButton('Exporter', 'outline', true, 'onclick="exportToExcel()"', '', 'fa-file-export'); ?>
        <?php echo renderButton('Désactiver', 'danger', true, 'id="desactiverButton" type="button"', '', 'fa-eye-slash'); ?>
        <?php echo renderButton('Activer', 'success', true, 'id="activerButton" type="button"', '', 'fa-check'); ?>
    </div>
</div>

<!-- Formulaire tableau -->
<form method="POST" action="?page=gestion_utilisateurs" id="formListeUtilisateurs">
    <input type="hidden" name="submit_disable_multiple" id="submitDisableHidden" value="0">
    <input type="hidden" name="submit_enable_multiple" id="submitEnableHidden" value="0">

    <div class="table-wrapper">
        <table class="data-table">
            <thead>
                <tr>
                    <th style="width: 40px;">
                        <input type="checkbox" id="selectAllCheckbox" class="form-checkbox">
                    </th>
                    <th>Nom d'utilisateur</th>
                    <th>Groupe utilisateur</th>
                    <th>Statut</th>
                    <th>Login</th>
                    <?php if (canEdit()): ?>
                    <th class="text-right">Actions</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="usersTableBody">
                <?php if (empty($utilisateurs)): ?>
                <tr>
                    <td colspan="6" class="text-center py-8">
                        <?php echo renderEmptyState('Aucun utilisateur trouvé', 'fa-users', 
                            renderButtonLink('Ajouter un utilisateur', '?page=gestion_utilisateurs&action=add', 'primary', canCreate())
                        ); ?>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($grouped as $groupName => $usersGroup): ?>
                    <tr class="bg-muted-light group-header" data-group="<?php echo htmlspecialchars(md5($groupName)); ?>">
                        <td colspan="6" class="font-semibold text-sm cursor-pointer select-none">
                            <div class="flex items-center justify-between">
                                <div>
                                    Groupe: <?php echo htmlspecialchars($groupName); ?>
                                    <span class="text-muted text-xs">(<?php echo count($usersGroup); ?>)</span>
                                </div>
                                <div class="group-toggle">
                                    <i class="fas fa-chevron-down text-muted"></i>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php foreach ($usersGroup as $user): ?>
                    <tr class="group-row" data-group="<?php echo htmlspecialchars(md5($groupName)); ?>">
                        <td>
                            <input type="checkbox" name="selected_ids[]" 
                                   value="<?php echo htmlspecialchars($user->id_utilisateur); ?>" 
                                   class="user-checkbox form-checkbox">
                        </td>
                        <td><?php echo htmlspecialchars($user->nom_utilisateur); ?></td>
                        <td><?php echo htmlspecialchars($user->lib_GU); ?></td>
                        <td><?php echo renderBadge($user->statut_utilisateur); ?></td>
                        <td>
                            <div class="flex items-center gap-sm">
                                <i class="fas fa-envelope text-muted"></i>
                                <?php echo htmlspecialchars($user->login_utilisateur); ?>
                            </div>
                        </td>
                        <?php if (canEdit()): ?>
                        <td>
                            <div class="table-actions justify-end">
                                <a href="?page=gestion_utilisateurs&action=edit&id_utilisateur=<?php echo $user->id_utilisateur; ?>" 
                                   class="table-action edit" title="Modifier">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </div>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</form>

<?php
$tableHtml = ob_get_clean();

// ========== CONSTRUCTION DES ACTIONS D'EN-TÊTE ==========

$headerActions = '';
if (canCreate()) {
    $headerActions .= renderButtonLink('Ajouter un Utilisateur', '?page=gestion_utilisateurs&action=add', 'primary', true, '', '', 'fa-plus');
    $headerActions .= ' ';
    $headerActions .= renderButtonLink('Ajouter en masse', '?page=gestion_utilisateurs&action=addMasse', 'secondary', true, '', '', 'fa-users');
}

// ========== CONSTRUCTION DE LA PAGINATION ==========

$searchParam = !empty($search) ? '&search=' . urlencode($search) : '';
$baseUrl = '?page=gestion_utilisateurs&p={page}' . $searchParam;
$paginationHtml = renderPaginationInfo($page, $total_pages, $total_items, $limit, $baseUrl);

?>

<!-- ========== AFFICHAGE ========== -->

<!-- Stats Cards -->
<?php echo renderStatsGrid([
    [
        'label' => 'Total Utilisateurs',
        'value' => number_format($totalUtilisateurs),
        'icon' => 'users',
        'type' => 'primary'
    ],
    [
        'label' => 'Utilisateurs Actifs',
        'value' => number_format($utilisateursActifs),
        'icon' => 'user-check',
        'type' => 'success',
        'trend' => ['value' => round(($totalUtilisateurs > 0 ? $utilisateursActifs / $totalUtilisateurs : 0) * 100, 1) . '%', 'direction' => 'up']
    ],
    [
        'label' => 'Utilisateurs Inactifs',
        'value' => number_format($utilisateursInactifs),
        'icon' => 'user-times',
        'type' => 'warning'
    ]
]); ?>

<!-- Card principale -->
<?php echo renderFullCard(
    'Gestion des Utilisateurs',
    $tableHtml . $paginationHtml,
    $headerActions,
    '',
    'Gérez les comptes utilisateurs de l\'application'
); ?>

<!-- ========== MODALES ========== -->

<!-- Modal Ajout/Édition Utilisateur -->
<?php
ob_start();
?>
<form id="userForm" method="POST" action="?page=gestion_utilisateurs">
    <input type="hidden" name="id_utilisateur" value="<?php echo $utilisateur_a_modifier ? $utilisateur_a_modifier->id_utilisateur : ''; ?>">
    
    <div class="form-grid">
        <!-- Nom utilisateur -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-user text-primary"></i> Nom d'utilisateur
            </label>
            <?php if (!$utilisateur_a_modifier): ?>
            <select name="nom_utilisateur" id="nom_utilisateur" required class="form-select">
                <option value="">Sélectionner une personne</option>
                <optgroup label="Enseignants">
                    <?php foreach($enseignantsNonUtilisateurs as $enseignant): ?>
                    <option value="<?php echo htmlspecialchars($enseignant->nom_enseignant); ?>" 
                            data-login="<?php echo htmlspecialchars($enseignant->mail_enseignant ?? ''); ?>">
                        <?php echo htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Personnel Administratif">
                    <?php foreach($personnelNonUtilisateurs as $personnel): ?>
                    <option value="<?php echo htmlspecialchars($personnel->nom_pers_admin); ?>" 
                            data-login="<?php echo htmlspecialchars($personnel->mail_pers_admin ?? ''); ?>">
                        <?php echo htmlspecialchars($personnel->nom_pers_admin . ' ' . $personnel->prenom_pers_admin); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Étudiants">
                    <?php foreach($etudiantsNonUtilisateurs as $etudiant): ?>
                    <option value="<?php echo htmlspecialchars($etudiant->nom_etu); ?>" 
                            data-login="<?php echo htmlspecialchars($etudiant->email_etu ?? ''); ?>">
                        <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <?php else: ?>
            <input type="text" name="nom_utilisateur" required class="form-input"
                   value="<?php echo htmlspecialchars($utilisateur_a_modifier->nom_utilisateur); ?>">
            <?php endif; ?>
        </div>

        <!-- Login -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-envelope text-primary"></i> Login
            </label>
            <input type="email" name="login_utilisateur" id="login_utilisateur" required class="form-input"
                   value="<?php echo $utilisateur_a_modifier ? htmlspecialchars($utilisateur_a_modifier->login_utilisateur) : ''; ?>">
        </div>

        <!-- Type utilisateur -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-id-badge text-primary"></i> Type utilisateur
            </label>
            <select name="id_type_utilisateur" required class="form-select">
                <option value="">Sélectionner un type</option>
                <?php foreach($types_utilisateur as $type): ?>
                <option value="<?php echo htmlspecialchars($type->id_type_utilisateur); ?>"
                        <?php echo ($utilisateur_a_modifier && $type->id_type_utilisateur == $utilisateur_a_modifier->id_type_utilisateur) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($type->lib_type_utilisateur); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Statut -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-toggle-on text-primary"></i> Statut
            </label>
            <select name="statut_utilisateur" required class="form-select">
                <option value="">Sélectionner un statut</option>
                <option value="Actif" <?php echo ($utilisateur_a_modifier && $utilisateur_a_modifier->statut_utilisateur === 'Actif') ? 'selected' : ''; ?>>Actif</option>
                <option value="Inactif" <?php echo ($utilisateur_a_modifier && $utilisateur_a_modifier->statut_utilisateur === 'Inactif') ? 'selected' : ''; ?>>Inactif</option>
            </select>
        </div>

        <!-- Groupe utilisateur -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-users text-primary"></i> Groupe utilisateur
            </label>
            <select name="id_GU" required class="form-select">
                <option value="">Sélectionner un groupe</option>
                <?php foreach($groupes_utilisateur as $groupe): ?>
                <option value="<?php echo htmlspecialchars($groupe->id_GU); ?>"
                        <?php echo ($utilisateur_a_modifier && $groupe->id_GU == $utilisateur_a_modifier->id_GU) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($groupe->lib_GU); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Niveau d'accès -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-lock text-primary"></i> Niveau d'accès
            </label>
            <select name="id_niveau_acces" required class="form-select">
                <option value="">Sélectionner un niveau</option>
                <?php foreach($niveau_acces as $niveau): ?>
                <option value="<?php echo htmlspecialchars($niveau->id_niveau_acces_donnees); ?>"
                        <?php echo ($utilisateur_a_modifier && $niveau->id_niveau_acces_donnees == $utilisateur_a_modifier->id_niv_acces_donnee) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($niveau->lib_niveau_acces_donnees); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
</form>
<?php
$formContent = ob_get_clean();

$modalFooter = '';
if (isset($utilisateur_a_modifier) && $_GET['action']=='edit') {
    $modalFooter .= renderButton('Annuler', 'secondary', true, 'type="button" onclick="closeUserModal()"');
    $modalFooter .= ' ';
    $modalFooter .= renderButton('Modifier', 'primary', true, 'type="button" onclick="submitModifyForm()"', '', 'fa-save');
} else {
    $modalFooter .= renderButton('Annuler', 'secondary', true, 'type="button" onclick="closeUserModal()"');
    $modalFooter .= ' ';
    $modalFooter .= renderButton('Enregistrer', 'primary', true, 'type="submit" name="btn_add_utilisateur" form="userForm"', '', 'fa-save');
}

if ($showModal && $_GET['action'] !== 'addMasse'):
    echo renderModal(
        'userModal',
        isset($utilisateur_a_modifier) && $_GET['action']=='edit' ? 'Modifier un utilisateur' : 'Ajouter un Utilisateur',
        $formContent,
        $modalFooter,
        'lg'
    );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        CM.Modal.show('userModal');
    });
    </script>
    <?php
endif;
?>

<!-- Modal Ajout en masse -->
<?php
ob_start();
?>
<form method="POST" action="?page=gestion_utilisateurs" id="userMasse">
    <div class="form-grid">
        <!-- Sélection des personnes -->
        <div class="form-group" style="grid-column: 1 / -1;">
            <label class="form-label">
                <i class="fas fa-users text-primary"></i> Sélectionner les personnes
            </label>
            <select name="selected_persons[]" multiple size="10" required class="form-select" style="height: auto; min-height: 200px;">
                <optgroup label="Enseignants">
                    <?php foreach($enseignantsNonUtilisateurs as $enseignant): ?>
                    <option value="ens_<?php echo $enseignant->id_enseignant; ?>">
                        <?php echo htmlspecialchars($enseignant->nom_enseignant . ' ' . $enseignant->prenom_enseignant); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Personnel Administratif">
                    <?php foreach($personnelNonUtilisateurs as $personnel): ?>
                    <option value="pers_<?php echo $personnel->id_pers_admin; ?>">
                        <?php echo htmlspecialchars($personnel->nom_pers_admin . ' ' . $personnel->prenom_pers_admin); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
                <optgroup label="Étudiants">
                    <?php foreach($etudiantsNonUtilisateurs as $etudiant): ?>
                    <option value="etu_<?php echo $etudiant->num_etu; ?>">
                        <?php echo htmlspecialchars($etudiant->nom_etu . ' ' . $etudiant->prenom_etu); ?>
                    </option>
                    <?php endforeach; ?>
                </optgroup>
            </select>
            <p class="text-sm text-muted mt-sm">
                <i class="fas fa-info-circle"></i> Maintenez Shift ou Ctrl pour sélectionner plusieurs personnes
            </p>
        </div>

        <!-- Type utilisateur -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-id-badge text-primary"></i> Type utilisateur
            </label>
            <select name="id_type_utilisateur" required class="form-select">
                <option value="">Sélectionner un type</option>
                <?php foreach($types_utilisateur as $type): ?>
                <option value="<?php echo htmlspecialchars($type->id_type_utilisateur); ?>">
                    <?php echo htmlspecialchars($type->lib_type_utilisateur); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Groupe utilisateur -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-users text-primary"></i> Groupe utilisateur
            </label>
            <select name="id_GU" required class="form-select">
                <option value="">Sélectionner un groupe</option>
                <?php foreach($groupes_utilisateur as $groupe): ?>
                <option value="<?php echo htmlspecialchars($groupe->id_GU); ?>">
                    <?php echo htmlspecialchars($groupe->lib_GU); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Niveau d'accès -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-lock text-primary"></i> Niveau d'accès
            </label>
            <select name="id_niveau_acces" required class="form-select">
                <option value="">Sélectionner un niveau</option>
                <?php foreach($niveau_acces as $niveau): ?>
                <option value="<?php echo htmlspecialchars($niveau->id_niveau_acces_donnees); ?>">
                    <?php echo htmlspecialchars($niveau->lib_niveau_acces_donnees); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- Statut -->
        <div class="form-group">
            <label class="form-label">
                <i class="fas fa-toggle-on text-primary"></i> Statut
            </label>
            <select name="statut_utilisateur" required class="form-select">
                <option value="Actif">Actif</option>
                <option value="Inactif">Inactif</option>
            </select>
        </div>
    </div>
</form>
<?php
$massFormContent = ob_get_clean();

$massFooter = renderButton('Annuler', 'secondary', true, 'type="button" onclick="closeMasseModal()"');
$massFooter .= ' ';
$massFooter .= renderButton('Enregistrer', 'primary', true, 'type="submit" name="btn_add_multiple" form="userMasse"', '', 'fa-save');

if ($showModal && $_GET['action'] === 'addMasse'):
    echo renderModal(
        'userMasseModal',
        'Ajout en masse d\'utilisateurs',
        $massFormContent,
        $massFooter,
        'lg'
    );
    ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        CM.Modal.show('userMasseModal');
    });
    </script>
    <?php
endif;
?>

<!-- Modales de confirmation -->
<?php
echo renderConfirmModal(
    'disableModal',
    'Confirmation de désactivation',
    'Êtes-vous sûr de vouloir désactiver les utilisateurs sélectionnés ?',
    '#',
    'Confirmer',
    'danger'
);

echo renderConfirmModal(
    'enableModal',
    'Confirmation de réactivation',
    'Êtes-vous sûr de vouloir réactiver les utilisateurs sélectionnés ?',
    '#',
    'Confirmer',
    'success'
);

echo renderConfirmModal(
    'modifyModal',
    'Confirmation de modification',
    'Êtes-vous sûr de vouloir modifier cet utilisateur ?',
    '#',
    'Confirmer',
    'info'
);
?>

<!-- ========== JAVASCRIPT ========== -->

<script>
// ========== VARIABLES GLOBALES ==========
const userModal = document.getElementById('userModal');
const userForm = document.getElementById('userForm');
const searchInput = document.getElementById('searchInput');
const selectAllCheckbox = document.getElementById('selectAllCheckbox');
const userCheckboxes = document.querySelectorAll('.user-checkbox');

// ========== GESTION DES MODALES ==========

function closeUserModal() {
    window.location.href = '?page=gestion_utilisateurs';
}

function closeMasseModal() {
    window.location.href = '?page=gestion_utilisateurs';
}

// ========== RECHERCHE ==========

if (searchInput) {
    searchInput.addEventListener('input', function() {
        const query = this.value.toLowerCase().trim();
        const rows = document.querySelectorAll('#usersTableBody tr.group-row');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(query) ? '' : 'none';
        });
    });
}

// ========== SÉLECTION MULTIPLE ==========

if (selectAllCheckbox) {
    selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.user-checkbox:not([disabled])');
        checkboxes.forEach(cb => {
            if (cb.closest('tr').style.display !== 'none') {
                cb.checked = this.checked;
            }
        });
    });
}

// ========== ACTIONS GROUPÉES ==========

document.getElementById('desactiverButton')?.addEventListener('click', function() {
    const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).length;
    if (selected === 0) {
        CM.Toast.show('Veuillez sélectionner au moins un utilisateur', 'warning');
        return;
    }
    
    CM.Modal.show('disableModal');
    document.querySelector('#disableModal .modal-footer .btn-danger').onclick = function() {
        document.getElementById('submitDisableHidden').value = '1';
        document.getElementById('formListeUtilisateurs').submit();
    };
});

document.getElementById('activerButton')?.addEventListener('click', function() {
    const selected = Array.from(document.querySelectorAll('.user-checkbox:checked')).length;
    if (selected === 0) {
        CM.Toast.show('Veuillez sélectionner au moins un utilisateur', 'warning');
        return;
    }
    
    CM.Modal.show('enableModal');
    document.querySelector('#enableModal .modal-footer .btn-primary').onclick = function() {
        document.getElementById('submitEnableHidden').value = '1';
        document.getElementById('formListeUtilisateurs').submit();
    };
});

// ========== MODIFICATION ==========

function submitModifyForm() {
    CM.Modal.show('modifyModal');
    document.querySelector('#modifyModal .modal-footer .btn-primary').onclick = function() {
        document.getElementById('userForm').submit();
    };
}

// ========== EXPORT ET IMPRESSION ==========

function printTable() {
    window.print();
}

function exportToExcel() {
    const table = document.querySelector('.data-table');
    let html = '<table>';
    
    // Headers
    html += '<tr>';
    table.querySelectorAll('thead th').forEach((th, i) => {
        if (i > 0 && i < table.querySelectorAll('thead th').length - 1) {
            html += '<th>' + th.textContent + '</th>';
        }
    });
    html += '</tr>';
    
    // Rows
    table.querySelectorAll('tbody tr.group-row').forEach(row => {
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
    
    const blob = new Blob(['\ufeff' + html], {
        type: 'application/vnd.ms-excel'
    });
    
    const link = document.createElement('a');
    link.href = URL.createObjectURL(blob);
    link.download = 'utilisateurs_' + new Date().toISOString().split('T')[0] + '.xls';
    link.click();
}

// ========== AUTO-COMPLÉTION LOGIN ==========

document.getElementById('nom_utilisateur')?.addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const loginInput = document.getElementById('login_utilisateur');
    if (selectedOption && selectedOption.dataset.login) {
        loginInput.value = selectedOption.dataset.login;
    }
});

// ========== ACCORDION GROUPES ==========

document.addEventListener('DOMContentLoaded', function() {
    const headers = document.querySelectorAll('.group-header');
    headers.forEach(header => {
        header.style.cursor = 'pointer';
        header.addEventListener('click', function() {
            const group = this.getAttribute('data-group');
            const rows = document.querySelectorAll('.group-row[data-group="' + group + '"]');
            const chevron = this.querySelector('.group-toggle i');
            
            let anyVisible = false;
            rows.forEach(r => {
                if (getComputedStyle(r).display !== 'none') anyVisible = true;
            });
            
            if (anyVisible) {
                rows.forEach(r => r.style.display = 'none');
                if (chevron) {
                    chevron.classList.remove('fa-chevron-up');
                    chevron.classList.add('fa-chevron-down');
                }
            } else {
                rows.forEach(r => r.style.display = 'table-row');
                if (chevron) {
                    chevron.classList.remove('fa-chevron-down');
                    chevron.classList.add('fa-chevron-up');
                }
            }
        });
    });
});

// ========== LOADER AJOUT EN MASSE ==========

document.getElementById('userMasse')?.addEventListener('submit', function(e) {
    const loader = document.createElement('div');
    loader.id = 'loader';
    loader.innerHTML = `
        <div class="modal-overlay" style="display: flex;">
            <div class="modal modal-sm">
                <div class="modal-body text-center py-8">
                    <i class="fas fa-spinner fa-spin fa-3x text-primary mb-4"></i>
                    <h3 class="font-semibold mb-2">Traitement en cours</h3>
                    <p class="text-muted text-sm">Ajout des utilisateurs...</p>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(loader);
    
    setTimeout(() => {
        const successMessage = <?= json_encode($GLOBALS['messageSuccess'] ?? '') ?>;
        const errorMessage = <?= json_encode($GLOBALS['messageErreur'] ?? '') ?>;
        
        if (successMessage || errorMessage) {
            document.getElementById('loader')?.remove();
        }
    }, 1000);
});
</script>

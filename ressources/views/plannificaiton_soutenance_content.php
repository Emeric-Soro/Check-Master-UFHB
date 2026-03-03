<?php
// Initialiser le contrôleur et récupérer les données
require_once __DIR__ . '/../../app/controllers/PlanificationSoutenanceController.php';
$controller = new PlanificationSoutenanceController();

// Traitement des actions POST
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'planifier':
                $result = $controller->planifierSoutenance();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;

            case 'supprimer':
                $result = $controller->supprimerPlanification();
                $message = $result['message'];
                $messageType = $result['success'] ? 'success' : 'error';
                break;
        }
    }
}

$etudiantsAvecJury = $controller->getEtudiantsAvecJuryForView();
$etudiantsDisponibles = $controller->getEtudiantsDisponiblesForView();
$salles = $controller->getSallesForView();
$planifications = $controller->getPlanificationsForView();
$selectedYearLabel = \AcademicYear::getSelectedLabelFromSession();
$activeYearLabel = \AcademicYear::getActiveLabelFromSession();
$writeYearLabel = \AcademicYear::getWritableLabelFromSession();
$allYearsSelected = \AcademicYear::isAllSelectedFromSession();
$writeAllowed = \AcademicYear::isWriteAllowedFromSession();

$normalizePlanificationRow = static function ($row): array {
    if (is_array($row)) {
        return $row;
    }
    if (is_object($row)) {
        return get_object_vars($row);
    }

    return [];
};

$etudiantsAvecJury = array_values(array_map($normalizePlanificationRow, is_array($etudiantsAvecJury) ? $etudiantsAvecJury : []));
$etudiantsDisponibles = array_values(array_map($normalizePlanificationRow, is_array($etudiantsDisponibles) ? $etudiantsDisponibles : []));
$salles = array_values(array_map($normalizePlanificationRow, is_array($salles) ? $salles : []));
$planifications = array_values(array_map($normalizePlanificationRow, is_array($planifications) ? $planifications : []));


?>

<!-- Messages de notification -->
<?php if ($message): ?>
    <div class="fixed top-4 right-4 z-50 max-w-sm">
        <div
            class="<?= $messageType === 'success' ? 'bg-green-600' : 'bg-red-600' ?> text-white px-4 py-2 rounded-lg shadow-lg">
            <div class="flex items-center">
                <i class="fas <?= $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?> mr-2"></i>
                <?= htmlspecialchars($message) ?>
            </div>
        </div>
    </div>
    <script>
        // Auto-hide notification after 5 seconds
        setTimeout(() => {
            const notification = document.querySelector('.fixed.top-4.right-4');
            if (notification) {
                notification.style.opacity = '0';
                setTimeout(() => notification.style.display = 'none', 300);
            }
        }, 4000);
    </script>
<?php endif; ?>

<?php if ($selectedYearLabel !== ''): ?>
    <div class="mb-4">
        <?php cm_component('ui/alert-box', [
            'type' => $allYearsSelected || $writeAllowed ? 'info' : 'warning',
            'message' => $allYearsSelected
                ? "Affichage multi-années actif. Les nouvelles planifications restent réservées à l'année académique active {$writeYearLabel}."
                : ($writeAllowed
                    ? "Année académique affichée: {$selectedYearLabel}."
                    : "Consultation historique: {$selectedYearLabel}. Les planifications sont réservées à l'année académique active {$activeYearLabel}."),
        ]); ?>
    </div>
<?php endif; ?>

<!-- Section de planification des soutenances -->
<div class="bg-white shadow rounded-lg p-6 mb-6 relative cm-legacy-form-panel">
    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 px-3">
        <span class="text-lg font-medium text-blue-600" id="form-title">
            Planification Soutenance
        </span>
    </div>

    <!-- Message d'information -->
    <?php if (empty($etudiantsDisponibles)): ?>
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400"></i>
                </div>
                <div class="ml-3">

                    <div class="mt-1 text-sm text-blue-600">
                        Tous les étudiants avec attribution de jury ont déjà été planifiés ou aucun jury n'a encore été
                        attribué.
                        <br>Consultez la page <strong>Programmation Soutenance</strong> pour attribuer des jurys aux
                        étudiants.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <form method="POST" class="space-y-4 mt-4" id="planificationForm">
        <input type="hidden" name="action" value="planifier" id="formAction">
        <input type="hidden" name="edit_id" value="" id="editId">

        <div class="space-y-4">
            <!-- Première ligne : Étudiant et Thème -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Étudiant -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Étudiant <span class="cm-required-star">*</span></label>
                    <select name="id_programmation" id="etudiant" required onchange="updateTheme(this)"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Sélectionner un étudiant</option>
                        <?php if (empty($etudiantsAvecJury)): ?>
                            <option value="" disabled>Aucun étudiant avec jury attribué</option>
                        <?php else: ?>
                            <?php foreach ($etudiantsAvecJury as $etudiant): ?>
                                <option value="<?= htmlspecialchars($etudiant['id_programmation']) ?>"
                                    data-theme="<?= htmlspecialchars($etudiant['theme_soutenance'] ?? '') ?>"
                                    data-statut="<?= htmlspecialchars($etudiant['statut_planification'] ?? 'none') ?>">
                                    <?= htmlspecialchars($etudiant['nom_complet']) ?>
                                    (<?= htmlspecialchars($etudiant['matricule_etudiant']) ?>)
                                    <?php if ($allYearsSelected && !empty($etudiant['promotion_etu'])): ?>
                                        - <?= htmlspecialchars($etudiant['promotion_etu']) ?>
                                    <?php endif; ?>
                                    <?php if (isset($etudiant['statut_planification']) && $etudiant['statut_planification'] === 'complete'): ?>
                                        - ✅ Planifié
                                    <?php elseif (isset($etudiant['statut_planification']) && $etudiant['statut_planification'] === 'partial'): ?>
                                        - ⚠️ Jury assigné
                                    <?php endif; ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <!-- Thème Soutenance -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Thème Soutenance</label>
                    <div class="w-full px-3 py-2 border border-gray-200 rounded-md bg-gray-50 text-gray-700">
                        <span id="theme-display" class="text-gray-500 italic">
                            Sera affiché selon l'étudiant sélectionné
                        </span>
                    </div>
                </div>
            </div>

            <!-- Deuxième ligne : Salle, Date et Heure -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Salle -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Salle <span class="cm-required-star">*</span></label>
                    <select name="id_salle" id="salle" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Sélectionner une salle</option>
                        <?php foreach ($salles as $salle): ?>
                            <?php
                            $salleId = $salle['id_salle'] ?? '';
                            $salleLibelle = $salle['lib_salle'] ?? '';
                            ?>
                            <option value="<?= htmlspecialchars((string) $salleId) ?>">
                                <?= htmlspecialchars((string) $salleLibelle) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Date -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Date <span class="cm-required-star">*</span></label>
                    <input type="date" name="date_soutenance" id="date" required min="<?= date('Y-m-d') ?>"
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Heure -->
                <div class="space-y-2">
                    <label class="block text-sm font-medium text-gray-700">Heure <span class="cm-required-star">*</span></label>
                    <input type="time" name="heure_soutenance" id="heure" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                </div>
            </div>
        </div>

        <!-- Boutons d'action -->
        <?php if (canCreate() || canEdit()): ?>
        <div class="absolute -bottom-4 right-4 flex space-x-3" id="buttonContainer">
            <button type="submit" id="submitBtn"
                class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200">
                <i class="fas fa-calendar-plus mr-2"></i>Planifier
            </button>
        </div>
        <?php endif; ?>
    </form>
</div>

<!-- Section tableau des planifications -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-4 py-2 border-b border-gray-200 flex justify-between items-center">

        <div class="text-sm text-gray-500">
            <span><?= count($planifications) ?></span> soutenance(s) programmée(s)
        </div>
    </div>

    <div class="cm-table-wrapper">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N°</th>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant
                    </th>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Promotion
                    </th>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thème
                    </th>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salle
                    </th>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heure
                    </th>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut
                    </th>
                    <?php if (canEdit() || canDelete()): ?>
                    <th class="px-3 py-1.5 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($planifications)): ?>
                    <tr>
                        <td colspan="9" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 9l6-6m0 0l6 6M9 13h12" />
                                </svg>
                                <p class="text-sm text-gray-500">Aucune soutenance planifiée</p>
                                <p class="text-xs text-gray-400 mt-1">Commencez par planifier une soutenance</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($planifications as $index => $planification): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 whitespace-nowrap text-sm font-medium text-gray-900"><?= $index + 1 ?></td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($planification['nom_etudiant']) ?>
                                </div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($planification['matricule_etudiant']) ?>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-700">
                                <?= htmlspecialchars($planification['promotion_etu'] ?? '-') ?>
                            </td>
                            <td class="px-4 py-2">
                                <div class="text-sm text-gray-900 max-w-xs truncate"
                                    title="<?= htmlspecialchars($planification['theme_soutenance']) ?>">
                                    <?= htmlspecialchars($planification['theme_soutenance']) ?>
                                </div>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                    <?= htmlspecialchars($planification['nom_salle'] ?? 'Non définie') ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                <?= $planification['date_soutenance'] ? date('d/m/Y', strtotime($planification['date_soutenance'])) : '-' ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                <?= $planification['heure_soutenance'] ? date('H:i', strtotime($planification['heure_soutenance'])) : '-' ?>
                            </td>
                            <td class="px-4 py-2 whitespace-nowrap">
                                <span
                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    Planifiée
                                </span>
                            </td>
                            <?php if (canEdit() || canDelete()): ?>
                            <td class="px-4 py-2 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if (canEdit()): ?>
                                    <button onclick='editPlanification(<?= json_encode((string) ($planification["id_programmation"] ?? "")) ?>)'
                                        class="bg-yellow-600 hover:bg-yellow-700 text-white text-xs font-medium py-1 px-3 rounded transition-colors duration-200">
                                        Modifier
                                    </button>
                                    <?php endif; ?>

                                    <?php if (canDelete()): ?>
                                    <form method="POST" style="display: inline;"
                                        onsubmit="return confirm('Êtes-vous sûr de vouloir supprimer cette planification ?');">
                                        <input type="hidden" name="action" value="supprimer">
                                        <input type="hidden" name="id_programmation"
                                            value="<?= $planification['id_programmation'] ?>">
                                        <button type="submit"
                                            class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1 px-3 rounded transition-colors duration-200">
                                            Supprimer
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Variables globales pour le mode édition
    let isEditMode = false;
    let currentEditId = null;

    // Données des planifications pour JavaScript
    const planificationsData = <?= json_encode($planifications) ?>;

    // Fonction pour mettre à jour le thème selon l'étudiant sélectionné
    function updateTheme(selectElement) {
        // Vérifier que les éléments existent
        if (!selectElement) return;

        const themeDisplay = document.getElementById('theme-display');
        if (!themeDisplay) return;

        const selectedOption = selectElement.options[selectElement.selectedIndex];

        if (selectedOption && selectedOption.value) {
            const theme = selectedOption.getAttribute('data-theme');
            if (theme) {
                themeDisplay.textContent = theme;
                themeDisplay.classList.remove('text-gray-500', 'italic');
                themeDisplay.classList.add('text-gray-900');
            } else {
                themeDisplay.textContent = 'Aucun thème défini';
                themeDisplay.classList.add('text-gray-500', 'italic');
                themeDisplay.classList.remove('text-gray-900');
            }
        } else {
            themeDisplay.textContent = 'Sera affiché selon l\'étudiant sélectionné';
            themeDisplay.classList.add('text-gray-500', 'italic');
            themeDisplay.classList.remove('text-gray-900');
        }
    }

    // Modifier une planification
    function editPlanification(id) {
        // Vérifier que le formulaire existe
        const etudiantSelect = document.getElementById('etudiant');
        if (!etudiantSelect) {
            showNotification('Formulaire non disponible - aucun étudiant à planifier', 'error');
            return;
        }

        // Trouver les données de la planification
        const planification = planificationsData.find(p => p.id_programmation == id);
        if (!planification) {
            showNotification('Planification non trouvée', 'error');
            return;
        }

        isEditMode = true;
        currentEditId = id;

        // Remplir le formulaire
        etudiantSelect.value = planification.id_programmation;

        const salleSelect = document.getElementById('salle');
        const dateInput = document.getElementById('date');
        const heureInput = document.getElementById('heure');
        const editIdInput = document.getElementById('editId');

        if (salleSelect) salleSelect.value = planification.id_salle || '';
        if (dateInput) dateInput.value = planification.date_soutenance || '';
        if (heureInput) heureInput.value = planification.heure_soutenance || '';
        if (editIdInput) editIdInput.value = planification.id_programmation;

        // Mettre à jour l'affichage du thème
        updateTheme(etudiantSelect);

        // Changer le titre du formulaire
        const formTitle = document.getElementById('form-title');
        if (formTitle) formTitle.textContent = 'Modifier la Planification';

        // Changer l'action du formulaire et le bouton
        const formAction = document.getElementById('formAction');
        if (formAction) formAction.value = 'planifier';

        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-edit mr-2"></i>Modifier';
            submitBtn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
            submitBtn.classList.add('bg-yellow-600', 'hover:bg-yellow-700');
        }

        // Ajouter un bouton d'annulation si pas déjà présent
        const buttonContainer = document.getElementById('buttonContainer');
        if (buttonContainer && !document.getElementById('cancelBtn')) {
            const cancelBtn = document.createElement('button');
            cancelBtn.id = 'cancelBtn';
            cancelBtn.type = 'button';
            cancelBtn.onclick = resetForm;
            cancelBtn.className = 'bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200';
            cancelBtn.innerHTML = '<i class="fas fa-times mr-2"></i>Annuler';

            buttonContainer.insertBefore(cancelBtn, submitBtn);
        }

        showNotification('Mode modification activé', 'info');
    }

    // Réinitialiser le formulaire
    function resetForm() {
        // Vérifier que les éléments existent avant de les manipuler
        const etudiantSelect = document.getElementById('etudiant');
        const salleSelect = document.getElementById('salle');
        const dateInput = document.getElementById('date');
        const heureInput = document.getElementById('heure');
        const editIdInput = document.getElementById('editId');

        // Reset form fields si ils existent
        if (etudiantSelect) etudiantSelect.value = '';
        if (salleSelect) salleSelect.value = '';
        if (dateInput) dateInput.value = '';
        if (heureInput) heureInput.value = '';
        if (editIdInput) editIdInput.value = '';

        // Remettre à zéro l'affichage du thème
        if (etudiantSelect) updateTheme(etudiantSelect);

        // Reset mode
        isEditMode = false;
        currentEditId = null;

        // Changer le titre du formulaire
        const formTitle = document.getElementById('form-title');
        if (formTitle) formTitle.textContent = 'Planification Soutenance';

        // Reset button
        const submitBtn = document.getElementById('submitBtn');
        if (submitBtn) {
            submitBtn.innerHTML = '<i class="fas fa-calendar-plus mr-2"></i>Planifier';
            submitBtn.classList.remove('bg-yellow-600', 'hover:bg-yellow-700');
            submitBtn.classList.add('bg-blue-600', 'hover:bg-blue-700');
        }

        // Remove cancel button
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) {
            cancelBtn.remove();
        }
    }

    // Fonction pour afficher les notifications
    function showNotification(message, type = 'info') {
        // Supprimer toute notification existante
        const existingNotification = document.querySelector('.notification-temp');
        if (existingNotification) {
            existingNotification.remove();
        }

        const notification = document.createElement('div');
        notification.className = `notification-temp fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg text-white text-sm z-50 transition-opacity duration-300 ${type === 'success' ? 'bg-green-600' :
                type === 'error' ? 'bg-red-600' :
                    type === 'info' ? 'bg-blue-600' :
                        'bg-gray-600'
            }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
                ${message}
            </div>
        `;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
</script>

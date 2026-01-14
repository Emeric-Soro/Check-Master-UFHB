<?php
// Initialiser le contrôleur et récupérer les données
require_once __DIR__ . '/../../app/controllers/ProgrammationSoutenanceController.php';
$controller = new ProgrammationSoutenanceController();

$etudiants = $controller->getEtudiantsForView();
$etudiantsDisponibles = $controller->getEtudiantsDisponiblesForView();
$enseignants = $controller->getEnseignantsForView();
$professeursTitulaires = $controller->getProfesseursTitulairesForView();
$attributions = $controller->getAttributionsForView();
?>

<!-- Section d'attribution du jury -->
<div class="bg-white shadow rounded-lg p-6 mb-6 relative">
    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 px-3">
        <span class="text-lg font-medium text-green-600">Attribution Jury</span>
    </div>

    <!-- Message d'information -->
    <?php if (empty($etudiantsDisponibles)): ?>
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mt-4">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-yellow-800">Information</h3>
                    <div class="mt-1 text-sm text-yellow-600">
                        Aucun étudiant disponible pour la programmation de soutenance.
                        <br>Tous les étudiants validés ont déjà une attribution de jury ou aucun étudiant n'a encore été
                        validé.
                        <br>Consultez les pages de <strong>Gestion des Candidatures</strong> ou <strong>Évaluation des
                            Dossiers</strong> pour valider des étudiants.
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
        <!-- Étudiant -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Étudiant *</label>
            <select id="etudiant" required onchange="updateMaitreStage()"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                <option value="">Sélectionner un étudiant</option>
                <?php if (empty($etudiants)): ?>
                    <option value="" disabled>Aucun étudiant avec rapport validé</option>
                <?php else: ?>
                    <?php foreach ($etudiants as $etudiant): ?>
                        <option value="<?= htmlspecialchars($etudiant['id_etudiant']) ?>"
                            data-maitre-stage="<?= htmlspecialchars($etudiant['maitre_stage_nom'] ?? '') ?>"
                            data-directeur="<?= htmlspecialchars($etudiant['directeur_nom'] ?? '') ?>"
                            data-directeur-id="<?= htmlspecialchars($etudiant['directeur_id'] ?? '') ?>"
                            data-encadreur="<?= htmlspecialchars($etudiant['encadreur_nom'] ?? '') ?>"
                            data-encadreur-id="<?= htmlspecialchars($etudiant['encadreur_id'] ?? '') ?>">
                            <?= htmlspecialchars($etudiant['nom_complet']) ?>
                            (<?= htmlspecialchars($etudiant['matricule_etudiant']) ?>)
                            <?php if (isset($etudiant['statut_programmation']) && $etudiant['statut_programmation'] === 'programmed'): ?>
                                - ✅ Programmé
                            <?php endif; ?>
                        </option>
                    <?php endforeach; ?>
                <?php endif; ?>
            </select>
        </div>

        <!-- Thème Soutenance -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700">Thème Soutenance</label>
            <input type="text" id="theme"
                class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                placeholder="Thème de la soutenance">
        </div>

        <!-- Jury -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 mb-3">Composition du Jury</label>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="flex items-center space-x-3">
                    <select id="president" onchange="updateSelectOptions()"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Sélectionner un président</option>
                        <?php foreach ($professeursTitulaires as $professeur): ?>
                            <option value="<?= htmlspecialchars($professeur['id_enseignant']) ?>">
                                <?= htmlspecialchars($professeur['nom_complet']) ?>
                                (<?= htmlspecialchars($professeur['lib_fonction']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Président</span>
                </div>

                <div class="flex items-center space-x-3">
                    <select id="examinateur" onchange="updateSelectOptions()"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500">
                        <option value="">Sélectionner un examinateur</option>
                        <?php foreach ($enseignants as $enseignant): ?>
                            <option value="<?= htmlspecialchars($enseignant['id_enseignant']) ?>">
                                <?= htmlspecialchars($enseignant['nom_complet']) ?>
                                <?= !empty($enseignant['lib_fonction']) ? '(' . htmlspecialchars($enseignant['lib_fonction']) . ')' : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Examinateur</span>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-md bg-gray-50 text-gray-700"
                        id="directeur-display">
                        <span id="directeur-text" class="text-gray-500 italic">Sera déterminé automatiquement selon
                            l'étudiant sélectionné</span>
                    </div>
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Directeur de mémoire</span>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-md bg-gray-50 text-gray-700"
                        id="encadreur-display">
                        <span id="encadreur-text" class="text-gray-500 italic">Sera déterminé automatiquement selon
                            l'étudiant sélectionné</span>
                    </div>
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Encadreur</span>
                </div>

                <div class="flex items-center space-x-3">
                    <div class="flex-1 px-3 py-2 text-sm border border-gray-200 rounded-md bg-gray-50 text-gray-700"
                        id="maitre-stage-display">
                        <span id="maitre-stage-text" class="text-gray-500 italic">Sera déterminé automatiquement selon
                            l'étudiant sélectionné</span>
                    </div>
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Maître de Stage</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton Enregistrer -->
    <?php if (canCreate() || canEdit()): ?>
    <div class="absolute -bottom-4 right-4">
        <button onclick="addRow()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            Enregistrer
        </button>
    </div>
    <?php endif; ?>
</div>

<!-- Section tableau des soutenances -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h3 class="text-lg font-medium text-gray-900">Attributions programmées</h3>
    </div>

    <div class="overflow-x-auto">
        <table id="soutenanceTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thème
                        Mémoire</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Président
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Examinateur</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Directeur
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Encadreur
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Maître de
                        Stage</th>
                    <?php if (canEdit() || canDelete()): ?>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody id="tableBody" class="bg-white divide-y divide-gray-200">
                <?php if (empty($attributions)): ?>
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                            <div class="flex flex-col items-center">
                                <svg class="h-12 w-12 text-gray-400 mb-4" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                </svg>
                                <p class="text-sm text-gray-500">Aucune attribution de jury trouvée</p>
                                <p class="text-xs text-gray-400 mt-1">Commencez par ajouter une attribution</p>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($attributions as $attribution): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($attribution['nom_etudiant']) ?>
                                </div>
                                <div class="text-sm text-gray-500"><?= htmlspecialchars($attribution['matricule_etudiant']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900 max-w-xs truncate"
                                    title="<?= htmlspecialchars($attribution['theme_soutenance']) ?>">
                                    <?= htmlspecialchars($attribution['theme_soutenance']) ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($attribution['president_nom'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($attribution['examinateur_nom'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($attribution['directeur_nom'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($attribution['encadreur_nom'] ?? '-') ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?= htmlspecialchars($attribution['maitre_stage_nom'] ?? '-') ?>
                            </td>
                            <?php if (canEdit() || canDelete()): ?>
                            <td class="px-6 py-4 whitespace-nowrap text-center">
                                <div class="flex justify-center space-x-2">
                                    <?php if (canEdit()): ?>
                                    <button onclick="editAttribution(<?= $attribution['id_attribution'] ?>)"
                                        class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1 px-3 rounded transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                        Modifier
                                    </button>
                                    <?php endif; ?>
                                    <?php if (canDelete()): ?>
                                    <button onclick="deleteAttribution(<?= $attribution['id_attribution'] ?>)"
                                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1 px-3 rounded transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                        Supprimer
                                    </button>
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

    // Données des enseignants pour JavaScript
    const professeursTitulaires = <?= json_encode($professeursTitulaires) ?>;
    const enseignants = <?= json_encode($enseignants) ?>;

    // IDs des selects de jury (directeur et encadreur sont maintenant automatiques)
    const jurySelects = ['president', 'examinateur'];

    // Fonction pour mettre à jour le maître de stage, directeur et encadreur selon l'étudiant sélectionné
    function updateMaitreStage() {
        const etudiantSelect = document.getElementById('etudiant');
        const selectedOption = etudiantSelect.options[etudiantSelect.selectedIndex];

        // Éléments d'affichage
        const maitreStageText = document.getElementById('maitre-stage-text');
        const directeurText = document.getElementById('directeur-text');
        const encadreurText = document.getElementById('encadreur-text');

        if (selectedOption.value) {
            // Maître de stage
            const maitreStage = selectedOption.getAttribute('data-maitre-stage');
            if (maitreStage) {
                maitreStageText.textContent = maitreStage;
                maitreStageText.classList.remove('text-gray-500', 'italic');
                maitreStageText.classList.add('text-gray-900');
            } else {
                maitreStageText.textContent = 'Aucun maître de stage défini';
                maitreStageText.classList.add('text-gray-500', 'italic');
                maitreStageText.classList.remove('text-gray-900');
            }

            // Directeur de mémoire
            const directeur = selectedOption.getAttribute('data-directeur');
            if (directeur) {
                directeurText.textContent = directeur;
                directeurText.classList.remove('text-gray-500', 'italic');
                directeurText.classList.add('text-gray-900');
            } else {
                directeurText.textContent = 'Aucun directeur de mémoire défini';
                directeurText.classList.add('text-gray-500', 'italic');
                directeurText.classList.remove('text-gray-900');
            }

            // Encadreur
            const encadreur = selectedOption.getAttribute('data-encadreur');
            if (encadreur) {
                encadreurText.textContent = encadreur;
                encadreurText.classList.remove('text-gray-500', 'italic');
                encadreurText.classList.add('text-gray-900');
            } else {
                encadreurText.textContent = 'Aucun encadreur défini';
                encadreurText.classList.add('text-gray-500', 'italic');
                encadreurText.classList.remove('text-gray-900');
            }
        } else {
            // Reset tous les champs
            const defaultTexts = [
                { element: maitreStageText, text: 'Sera déterminé automatiquement selon l\'étudiant sélectionné' },
                { element: directeurText, text: 'Sera déterminé automatiquement selon l\'étudiant sélectionné' },
                { element: encadreurText, text: 'Sera déterminé automatiquement selon l\'étudiant sélectionné' }
            ];

            defaultTexts.forEach(({ element, text }) => {
                element.textContent = text;
                element.classList.add('text-gray-500', 'italic');
                element.classList.remove('text-gray-900');
            });
        }
    }

    // Fonction pour mettre à jour les options des selects (éviter les doublons)
    function updateSelectOptions() {
        // Récupérer les valeurs actuellement sélectionnées
        const selectedValues = {};
        jurySelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            selectedValues[selectId] = select.value;
        });

        // Mettre à jour chaque select
        jurySelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            const currentValue = select.value;

            // Déterminer quelle liste d'enseignants utiliser
            const teachersList = selectId === 'president' ? professeursTitulaires : enseignants;

            // Mettre à jour la disponibilité des options existantes
            Array.from(select.options).forEach(option => {
                if (option.value !== '') {
                    const teacherId = option.value;
                    const isSelectedElsewhere = Object.keys(selectedValues).some(otherSelectId =>
                        otherSelectId !== selectId && selectedValues[otherSelectId] === teacherId
                    );

                    // Masquer l'option si elle est sélectionnée ailleurs
                    if (isSelectedElsewhere) {
                        option.style.display = 'none';
                        option.disabled = true;
                    } else {
                        option.style.display = '';
                        option.disabled = false;
                    }
                }
            });
        });
    }

    // Fonction pour obtenir l'option par défaut selon le select
    function getDefaultOption(selectId) {
        switch (selectId) {
            case 'president':
                return '<option value="">Sélectionner un président</option>';
            case 'examinateur':
                return '<option value="">Sélectionner un examinateur</option>';
            default:
                return '<option value="">Sélectionner</option>';
        }
    }

    // Ajouter ou modifier une attribution
    function addRow() {
        const etudiantId = document.getElementById('etudiant').value;
        const theme = document.getElementById('theme').value.trim();
        const presidentId = document.getElementById('president').value;
        const examinateurId = document.getElementById('examinateur').value;

        // Récupérer les IDs du directeur et encadreur depuis les data attributes de l'étudiant sélectionné
        const etudiantSelect = document.getElementById('etudiant');
        const selectedOption = etudiantSelect.options[etudiantSelect.selectedIndex];
        const directeurId = selectedOption.getAttribute('data-directeur-id') || null;
        const encadreurId = selectedOption.getAttribute('data-encadreur-id') || null;

        // Validation
        if (!etudiantId || !theme) {
            showNotification('Veuillez sélectionner un étudiant et saisir le thème de soutenance', 'error');
            return;
        }

        const data = {
            id_etudiant: etudiantId,
            theme_soutenance: theme,
            president_id: presidentId || null,
            examinateur_id: examinateurId || null,
            directeur_id: directeurId,
            encadreur_id: encadreurId
        };

        const url = isEditMode ?
            '?page=programation_soutenance&action=updateAttribution' :
            '?page=programation_soutenance&action=createAttribution';

        if (isEditMode) {
            data.id = currentEditId;
        }

        fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(data)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    resetForm();
                    // Recharger la page pour voir les changements
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Erreur : ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
    }

    // Modifier une attribution
    function editAttribution(id) {
        // Récupérer les données de l'attribution depuis le serveur
        fetch('?page=programation_soutenance&action=getAttributions')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const attribution = data.data.find(a => a.id_attribution == id);
                    if (!attribution) return;

                    isEditMode = true;
                    currentEditId = id;

                    // Remplir le formulaire
                    document.getElementById('etudiant').value = attribution.id_etudiant;
                    document.getElementById('theme').value = attribution.theme_soutenance;
                    document.getElementById('president').value = attribution.president_id || '';
                    document.getElementById('examinateur').value = attribution.examinateur_id || '';

                    // Mettre à jour l'affichage automatique (maître de stage, directeur, encadreur)
                    updateMaitreStage();

                    // Mettre à jour les options des selects
                    updateSelectOptions();

                    // Changer le texte du bouton
                    const btn = document.querySelector('.bg-green-600');
                    btn.textContent = 'Modifier';
                    btn.classList.remove('bg-green-600', 'hover:bg-green-700');
                    btn.classList.add('bg-blue-600', 'hover:bg-blue-700');

                    // Ajouter un bouton d'annulation
                    if (!document.getElementById('cancelBtn')) {
                        const cancelBtn = document.createElement('button');
                        cancelBtn.id = 'cancelBtn';
                        cancelBtn.onclick = resetForm;
                        cancelBtn.className = 'ml-3 bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2';
                        cancelBtn.textContent = 'Annuler';
                        btn.parentNode.appendChild(cancelBtn);
                    }

                    showNotification('Mode modification activé', 'info');
                } else {
                    showNotification('Erreur lors du chargement de l\'attribution', 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
    }

    // Supprimer une attribution
    function deleteAttribution(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette attribution ?')) {
            return;
        }

        fetch('?page=programation_soutenance&action=deleteAttribution', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    // Recharger la page pour voir les changements
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    showNotification('Erreur : ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion', 'error');
            });
    }

    // Réinitialiser le formulaire
    function resetForm() {
        // Reset form fields
        document.getElementById('etudiant').value = '';
        document.getElementById('theme').value = '';
        document.getElementById('president').value = '';
        document.getElementById('examinateur').value = '';

        // Remettre à zéro les affichages automatiques
        updateMaitreStage();

        // Réactiver toutes les options (enlever les restrictions)
        jurySelects.forEach(selectId => {
            const select = document.getElementById(selectId);
            Array.from(select.options).forEach(option => {
                option.style.display = '';
                option.disabled = false;
            });
        });

        // Reset mode
        isEditMode = false;
        currentEditId = null;

        // Reset button
        const btn = document.querySelector('.bg-blue-600, .bg-green-600');
        btn.textContent = 'Enregistrer';
        btn.classList.remove('bg-blue-600', 'hover:bg-blue-700');
        btn.classList.add('bg-green-600', 'hover:bg-green-700');

        // Remove cancel button
        const cancelBtn = document.getElementById('cancelBtn');
        if (cancelBtn) {
            cancelBtn.remove();
        }
    }

    // Fonction utilitaire
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialisation au chargement de la page
    document.addEventListener('DOMContentLoaded', function () {
        // Pas besoin d'appeler updateSelectOptions() au chargement initial
        // car il n'y a pas encore de sélections
    });

    // Notification system
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg text-white text-sm z-50 transition-opacity duration-300 ${type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
                type === 'info' ? 'bg-blue-600' :
                    'bg-gray-600'
            }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
</script>
<?php
require_once __DIR__ . '/../../../app/utils/permissions.php';
?>
<!-- Gestion des critères d'évaluation -->
<div class="space-y-6">
    <!-- En-tête -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Critères d'Évaluation</h2>
                <p class="text-gray-600 mt-1">Gérez les critères d'évaluation et leurs barèmes par année académique</p>
            </div>
            <?php if (hasPermission('criteres_evaluation', 'CREATE')): ?>
            <button onclick="openAddCritereModal()"
                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                <i class="fas fa-plus mr-2"></i>Nouveau Critère
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtre par année académique -->
    <div class="bg-white shadow rounded-lg p-6">
        <div class="flex flex-col sm:flex-row gap-4 items-start sm:items-center">
            <label class="text-sm font-medium text-gray-700 whitespace-nowrap">Année Académique:</label>
            <select id="anneeFilter" onchange="filterByAnnee()"
                class="flex-1 sm:max-w-xs px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                <option value="">Toutes les années</option>
                <!-- Options seront ajoutées dynamiquement -->
            </select>
            <div class="flex items-center space-x-4 text-sm">
                <span class="text-gray-500">
                    Total: <span id="totalCriteres">0</span> critère(s)
                </span>
            </div>
        </div>
    </div>

    <!-- Liste des critères -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200">
            <h3 class="text-lg font-medium text-gray-900">Liste des Critères d'Évaluation</h3>
        </div>

        <div class="overflow-x-auto">
            <table id="criteresTable" class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Critère</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Année
                            Académique</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Barème (points)</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                </thead>
                <tbody id="criteresBody" class="bg-white divide-y divide-gray-200">
                    <!-- Les lignes seront ajoutées dynamiquement -->
                </tbody>
            </table>
        </div>

        <!-- État vide -->
        <div id="emptyStateCriteres" class="text-center py-12" style="display: none;">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucun critère trouvé</h3>
            <p class="mt-1 text-sm text-gray-500">Commencez par ajouter un critère d'évaluation.</p>
        </div>
    </div>
</div>

<!-- Modal d'ajout/modification de critère -->
<div id="critereModal"
    class="fixed inset-0 bg-opacity-60 flex items-center justify-center overflow-y-auto h-full w-full z-50"
    style="display: none;">
    <div class="relative mx-auto p-5  w-full max-w-2xl shadow-lg rounded-lg bg-white m-4">
        <div class="mt-3">
            <!-- En-tête du modal -->
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-lg font-medium text-gray-900" id="modalTitle">Nouveau Critère d'Évaluation</h3>
                <button onclick="closeCritereModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Formulaire -->
            <form id="critereForm" class="space-y-6">
                <input type="hidden" id="critereId" value="">

                <!-- Libellé du critère -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Libellé du Critère *</label>
                    <input type="text" id="libCritere" required
                        class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                        placeholder="Ex: Qualité de la présentation, Maîtrise du sujet...">
                </div>

                <!-- Configuration des barèmes par année -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-3">Configuration des Barèmes</label>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <div class="space-y-4" id="baremesContainer">
                            <!-- Les barèmes seront ajoutés dynamiquement -->
                        </div>
                        <button type="button" onclick="addBaremeRow()"
                            class="mt-3 text-green-600 hover:text-green-800 text-sm font-medium">
                            <i class="fas fa-plus mr-1"></i>Ajouter une année
                        </button>
                    </div>

                    <!-- Affichage des totaux par année -->
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                        <h4 class="text-sm font-medium text-blue-800 mb-2">
                            <i class="fas fa-calculator mr-2"></i>Récapitulatif des barèmes par année
                        </h4>
                        <div id="totalBaremeDisplay" class="text-sm">
                            <div class="text-gray-500 italic">Aucun barème configuré</div>
                        </div>
                    </div>

                    <!-- Zone d'affichage des erreurs -->
                    <div id="baremeErrorMessage" style="display: none;"></div>
                </div>

                <!-- Boutons d'action -->
                <div class="flex justify-end space-x-3 pt-6 border-t">
                    <button type="button" onclick="closeCritereModal()"
                        class="px-4 py-2 border border-gray-300 rounded-md text-sm font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 border border-transparent rounded-md text-sm font-medium text-white hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500">
                        <i class="fas fa-save mr-2"></i><span id="submitButtonText">Enregistrer</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Variables globales
    let criteresData = [];
    let anneesAcademiques = [];
    let isEditMode = false;

    // Initialisation
    document.addEventListener('DOMContentLoaded', function () {
        loadAnneesAcademiques();
        loadCriteres();
    });

    // Charger les années académiques
    function loadAnneesAcademiques() {
        fetch('?page=criteres_evaluation&action=getAnnees')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    anneesAcademiques = data.data;

                    const select = document.getElementById('anneeFilter');
                    select.innerHTML = '<option value="">Toutes les années</option>';

                    anneesAcademiques.forEach(annee => {
                        select.innerHTML += `<option value="${annee.id}">${annee.lib}</option>`;
                    });
                } else {
                    showNotification('Erreur lors du chargement des années académiques : ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion lors du chargement des années académiques', 'error');
            });
    }

    // Charger les critères
    function loadCriteres() {
        fetch('?page=criteres_evaluation&action=getCriteres')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    criteresData = data.data;
                    displayCriteres();
                } else {
                    showNotification('Erreur lors du chargement des critères : ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion lors du chargement des critères', 'error');
            });
    }

    // Afficher les critères
    function displayCriteres() {
        const tbody = document.getElementById('criteresBody');
        const emptyState = document.getElementById('emptyStateCriteres');
        const totalElement = document.getElementById('totalCriteres');

        const filteredData = filterCriteres();

        if (filteredData.length === 0) {
            tbody.innerHTML = '';
            emptyState.style.display = 'block';
            totalElement.textContent = '0';
            return;
        }

        emptyState.style.display = 'none';
        totalElement.textContent = filteredData.length;

        tbody.innerHTML = filteredData.map(critere => {
            const baremesDisplay = critere.baremes.map(b =>
                `<span class="inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full mr-1 mb-1">
                    ${b.annee_lib}: ${b.bareme}pts
                </span>`
            ).join('');



            return `
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4">
                        <div class="text-sm font-medium text-gray-900">${escapeHtml(critere.libelle)}</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="text-sm text-gray-900">${critere.baremes.length} année(s) configurée(s)</div>
                    </td>
                    <td class="px-6 py-4">
                        <div class="space-y-1">
                            ${baremesDisplay}
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${critere.baremes.length > 0 ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'
                }">
                            ${critere.baremes.length > 0 ? 'Configuré' : 'Non configuré'}
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm font-medium space-x-2">
                        <button onclick="editCritere(${critere.id})" 
                                class="text-green-600 hover:text-green-900 transition-colors duration-200" title="Modifier">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button onclick="deleteCritere(${critere.id})" 
                                class="text-red-600 hover:text-red-900 transition-colors duration-200" title="Supprimer">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
    }

    // Filtrer les critères par année
    function filterCriteres() {
        const anneeFilter = document.getElementById('anneeFilter').value;

        if (!anneeFilter) {
            return criteresData;
        }

        return criteresData.filter(critere =>
            critere.baremes.some(b => b.annee_id.toString() === anneeFilter)
        );
    }

    // Filtrer par année (appelé par le select)
    function filterByAnnee() {
        displayCriteres();
    }

    // Ouvrir le modal d'ajout
    function openAddCritereModal() {
        console.log('Ouverture du modal...');
        console.log('Années académiques disponibles:', anneesAcademiques);

        isEditMode = false;
        document.getElementById('modalTitle').textContent = 'Nouveau Critère d\'Évaluation';
        document.getElementById('submitButtonText').textContent = 'Enregistrer';
        document.getElementById('critereForm').reset();
        document.getElementById('critereId').value = '';

        // Ajouter une ligne de barème vide
        document.getElementById('baremesContainer').innerHTML = '';
        addBaremeRow();

        // Initialiser la validation
        setTimeout(() => {
            validateTotalBareme();
        }, 100);

        console.log('Affichage du modal...');
        document.getElementById('critereModal').style.display = 'block';
    }

    // Ouvrir le modal d'édition
    function editCritere(id) {
        isEditMode = true;
        const critere = criteresData.find(c => c.id === id);

        if (!critere) return;

        document.getElementById('modalTitle').textContent = 'Modifier le Critère';
        document.getElementById('submitButtonText').textContent = 'Mettre à jour';
        document.getElementById('critereId').value = critere.id;
        document.getElementById('libCritere').value = critere.libelle;

        // Remplir les barèmes
        const container = document.getElementById('baremesContainer');
        container.innerHTML = '';

        critere.baremes.forEach(bareme => {
            addBaremeRow(bareme.annee_id, bareme.bareme);
        });

        if (critere.baremes.length === 0) {
            addBaremeRow();
        }

        // Initialiser la validation
        setTimeout(() => {
            validateTotalBareme();
        }, 100);

        document.getElementById('critereModal').style.display = 'block';
    }

    // Ajouter une ligne de barème
    function addBaremeRow(selectedAnnee = '', bareme = '') {
        const container = document.getElementById('baremesContainer');
        const rowId = 'bareme_' + Date.now() + '_' + Math.random();

        const anneesOptions = anneesAcademiques.map(annee =>
            `<option value="${annee.id}" ${annee.id.toString() === selectedAnnee.toString() ? 'selected' : ''}>${annee.lib}</option>`
        ).join('');

        const rowHtml = `
            <div class="flex items-center space-x-3 bg-white p-3 rounded border" id="${rowId}">
                <div class="flex-1">
                    <select class="bareme-annee w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" onchange="validateTotalBareme()">
                        <option value="">Sélectionner une année</option>
                        ${anneesOptions}
                    </select>
                </div>
                <div class="w-32">
                    <input type="number" class="bareme-points w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500" 
                           placeholder="Points" min="0" max="20" value="${bareme}" oninput="validateTotalBareme()">
                </div>
                <button type="button" onclick="removeBaremeRow('${rowId}')" 
                        class="text-red-600 hover:text-red-800 p-1">
                    <i class="fas fa-trash text-sm"></i>
                </button>
            </div>
        `;

        container.insertAdjacentHTML('beforeend', rowHtml);
    }

    // Supprimer une ligne de barème
    function removeBaremeRow(rowId) {
        const row = document.getElementById(rowId);
        if (row && document.querySelectorAll('#baremesContainer > div').length > 1) {
            row.remove();
            validateTotalBareme();
        } else if (document.querySelectorAll('#baremesContainer > div').length === 1) {
            showNotification('Vous devez conserver au moins un barème', 'error');
        }
    }

    // Validation en temps réel des barèmes
    function validateTotalBareme() {
        const anneesBaremes = {};
        let isValid = true;
        let errorMessage = '';

        // Calculer les totaux par année
        const baremeRows = document.querySelectorAll('#baremesContainer > div');
        baremeRows.forEach(row => {
            const anneeSelect = row.querySelector('.bareme-annee');
            const pointsInput = row.querySelector('.bareme-points');

            if (anneeSelect.value && pointsInput.value) {
                const anneeId = anneeSelect.value;
                const points = parseFloat(pointsInput.value) || 0;

                if (!anneesBaremes[anneeId]) {
                    anneesBaremes[anneeId] = {
                        total: 0,
                        lib: anneeSelect.options[anneeSelect.selectedIndex].text,
                        currentCritere: 0
                    };
                }
                anneesBaremes[anneeId].total += points;
                anneesBaremes[anneeId].currentCritere += points;
            }
        });

        // Ajouter les barèmes existants des autres critères (pour le mode édition)
        const critereId = document.getElementById('critereId').value;
        if (critereId) {
            // Si on est en mode édition, exclure les barèmes actuels du critère en cours
            criteresData.forEach(critere => {
                if (critere.id_critere.toString() !== critereId.toString()) {
                    critere.baremes.forEach(bareme => {
                        const anneeId = bareme.annee_id.toString();
                        if (!anneesBaremes[anneeId]) {
                            anneesBaremes[anneeId] = {
                                total: 0,
                                lib: bareme.annee_lib,
                                currentCritere: 0
                            };
                        }
                        anneesBaremes[anneeId].total += parseFloat(bareme.bareme) || 0;
                    });
                }
            });
        } else {
            // Mode création : ajouter tous les barèmes existants
            criteresData.forEach(critere => {
                critere.baremes.forEach(bareme => {
                    const anneeId = bareme.annee_id.toString();
                    if (!anneesBaremes[anneeId]) {
                        anneesBaremes[anneeId] = {
                            total: 0,
                            lib: bareme.annee_lib,
                            currentCritere: 0
                        };
                    }
                    anneesBaremes[anneeId].total += parseFloat(bareme.bareme) || 0;
                });
            });
        }

        // Vérifier les limites et mettre à jour l'affichage
        let displayHtml = '';
        for (const anneeId in anneesBaremes) {
            const data = anneesBaremes[anneeId];
            const isOverLimit = data.total > 20;

            if (isOverLimit) {
                isValid = false;
                errorMessage = `Le total des points pour ${data.lib} dépasse 20 points (${data.total} points)`;
            }

            displayHtml += `
                <div class="flex justify-between items-center py-1 ${isOverLimit ? 'text-red-600 font-semibold' : data.total === 20 ? 'text-green-600' : 'text-gray-700'}">
                    <span>${data.lib}:</span>
                    <span>${data.total} / 20 points ${data.currentCritere > 0 ? '(+' + data.currentCritere + ')' : ''}</span>
                </div>
            `;
        }

        // Afficher les totaux
        const totalDisplay = document.getElementById('totalBaremeDisplay');
        if (totalDisplay) {
            totalDisplay.innerHTML = displayHtml;
        }

        // Afficher/masquer le message d'erreur
        const errorDiv = document.getElementById('baremeErrorMessage');
        if (errorDiv) {
            if (!isValid) {
                errorDiv.innerHTML = `<div class="text-red-600 text-sm mt-2 p-2 bg-red-50 border border-red-200 rounded"><i class="fas fa-exclamation-triangle mr-2"></i>${errorMessage}</div>`;
                errorDiv.style.display = 'block';
            } else {
                errorDiv.style.display = 'none';
            }
        }

        // Désactiver/activer le bouton de soumission
        const submitBtn = document.querySelector('#critereForm button[type="submit"]');
        if (submitBtn) {
            if (isValid) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('opacity-50', 'cursor-not-allowed');
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('opacity-50', 'cursor-not-allowed');
            }
        }

        return isValid;
    }

    // Fermer le modal
    function closeCritereModal() {
        document.getElementById('critereModal').style.display = 'none';
    }

    // Soumettre le formulaire
    document.getElementById('critereForm').addEventListener('submit', function (e) {
        e.preventDefault();

        const libCritere = document.getElementById('libCritere').value.trim();
        const critereId = document.getElementById('critereId').value;

        if (!libCritere) {
            showNotification('Veuillez saisir le libellé du critère', 'error');
            return;
        }

        // Récupérer les barèmes
        const baremes = [];
        const baremesRows = document.querySelectorAll('#baremesContainer > div');

        baremesRows.forEach(row => {
            const anneeSelect = row.querySelector('.bareme-annee');
            const pointsInput = row.querySelector('.bareme-points');

            if (anneeSelect.value && pointsInput.value) {
                const annee = anneesAcademiques.find(a => a.id.toString() === anneeSelect.value);
                baremes.push({
                    annee_id: parseInt(anneeSelect.value),
                    annee_lib: annee ? annee.lib : '',
                    bareme: parseInt(pointsInput.value)
                });
            }
        });

        if (baremes.length === 0) {
            showNotification('Veuillez configurer au moins un barème', 'error');
            return;
        }

        // Vérifier que le total des barèmes par année ne dépasse pas 20 points
        const anneesBaremes = {};
        baremes.forEach(bareme => {
            if (!anneesBaremes[bareme.annee_id]) {
                anneesBaremes[bareme.annee_id] = 0;
            }
            anneesBaremes[bareme.annee_id] += bareme.bareme;
        });

        for (const [anneeId, totalBareme] of Object.entries(anneesBaremes)) {
            if (totalBareme > 20) {
                const annee = anneesAcademiques.find(a => a.id.toString() === anneeId);
                showNotification(`Le total des barèmes pour l'année ${annee ? annee.lib : anneeId} (${totalBareme} points) dépasse 20 points`, 'error');
                return;
            }
        }

        // Préparer les données pour l'envoi
        const requestData = {
            libelle: libCritere,
            baremes: baremes
        };

        if (isEditMode && critereId) {
            requestData.id = parseInt(critereId);
        }

        // Définir l'URL et la méthode selon le mode
        const url = isEditMode
            ? '?page=criteres_evaluation&action=updateCritere'
            : '?page=criteres_evaluation&action=createCritere';
        const method = 'POST';

        // Envoyer la requête AJAX
        fetch(url, {
            method: method,
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(requestData)
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'success');
                    loadCriteres(); // Recharger la liste
                    closeCritereModal();
                } else {
                    showNotification('Erreur : ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion lors de la sauvegarde', 'error');
            });
    });

    // Supprimer un critère
    function deleteCritere(id) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce critère d\'évaluation ?')) {
            return;
        }

        fetch('ressources/routes/criteresEvaluationRoutes.php?page=criteres_evaluation&action=deleteCritere', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ id: id })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showNotification(data.message, 'info');
                    loadCriteres(); // Recharger la liste
                } else {
                    showNotification('Erreur : ' + data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showNotification('Erreur de connexion lors de la suppression', 'error');
            });
    }

    // Fonction utilitaire pour échapper le HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // Notifications
    function showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg text-white text-sm z-50 transition-opacity duration-300 ${type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
                'bg-blue-600'
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

    // Fermer le modal en cliquant à l'extérieur
    window.onclick = function (event) {
        const modal = document.getElementById('critereModal');
        if (event.target === modal) {
            closeCritereModal();
        }
    }
</script>
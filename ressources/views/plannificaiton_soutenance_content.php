<!-- Section de planification des soutenances -->
<div class="bg-white shadow rounded-lg p-6 mb-6 relative">

    <div class="space-y-4 mt-4">
        <!-- Première ligne : Étudiant et Thème -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Étudiant -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Étudiant</label>
                <input type="text" id="etudiant"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    placeholder="Nom de l'étudiant">
            </div>

            <!-- Thème Soutenance -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Thème Soutenance</label>
                <input type="text" id="theme"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
                    placeholder="Thème de la soutenance">
            </div>
        </div>

        <!-- Deuxième ligne : Salle, Date et Heure -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Salle -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Salle</label>
                <select id="salle"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
                    <option value="">Sélectionner une salle</option>
                    <option value="Salle 101">Salle 101</option>
                    <option value="Salle 102">Salle 102</option>
                    <option value="Salle 201">Salle 201</option>
                    <option value="Salle 202">Salle 202</option>
                    <option value="Amphithéâtre A">Amphithéâtre A</option>
                    <option value="Amphithéâtre B">Amphithéâtre B</option>
                </select>
            </div>

            <!-- Date -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" id="date"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
            </div>

            <!-- Heure -->
            <div class="space-y-2">
                <label class="block text-sm font-medium text-gray-700">Heure</label>
                <input type="time" id="heure"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500">
            </div>
        </div>
    </div>

    <!-- Bouton Programmer -->
    <div class="absolute -bottom-4 right-4">
        <button onclick="addPlanification()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            <i class="fas fa-calendar-plus mr-2"></i>Programmer
        </button>
    </div>
</div>

<!-- Section tableau des planifications -->
<div class="bg-white shadow rounded-lg overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
        <h3 class="text-lg font-medium text-gray-900">Planning des Soutenances</h3>
        <div class="text-sm text-gray-500">
            <span id="totalPlanifications">0</span> soutenance(s) programmée(s)
        </div>
    </div>

    <div class="overflow-x-auto">
        <table id="planificationTable" class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">N°</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Étudiant
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Thème
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Salle
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Heure
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Statut
                    </th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody id="planificationBody" class="bg-white divide-y divide-gray-200">
                <!-- Les lignes seront ajoutées dynamiquement -->
            </tbody>
        </table>

        <!-- État vide -->
        <div id="emptyState" class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4m-6 9l6-6m0 0l6 6M9 13h12" />
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Aucune soutenance programmée</h3>
            <p class="mt-1 text-sm text-gray-500">Commencez par programmer votre première soutenance.</p>
        </div>
    </div>
</div>

<script>
    let planificationCounter = 0;

    function addPlanification() {
        const etudiant = document.getElementById('etudiant').value.trim();
        const theme = document.getElementById('theme').value.trim();
        const salle = document.getElementById('salle').value;
        const date = document.getElementById('date').value;
        const heure = document.getElementById('heure').value;

        // Validation
        if (!etudiant || !theme || !salle || !date || !heure) {
            alert('Veuillez renseigner tous les champs obligatoires.');
            return;
        }

        // Validation de la date (ne doit pas être dans le passé)
        const selectedDate = new Date(date + 'T' + heure);
        const now = new Date();

        if (selectedDate <= now) {
            alert('La date et l\'heure de soutenance doivent être dans le futur.');
            return;
        }

        // Vérifier les conflits de salle
        if (checkSalleConflict(salle, date, heure)) {
            if (!confirm('Attention : Cette salle est déjà occupée à cette date et heure. Voulez-vous continuer ?')) {
                return;
            }
        }

        const tbody = document.getElementById('planificationBody');
        const emptyState = document.getElementById('emptyState');

        // Masquer l'état vide
        emptyState.style.display = 'none';

        planificationCounter++;
        const row = tbody.insertRow();
        row.className = 'hover:bg-gray-50';
        row.dataset.planificationId = planificationCounter;

        // Formater la date pour l'affichage
        const dateFormatted = formatDate(date);
        const heureFormatted = formatTime(heure);

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${planificationCounter}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <div class="text-sm font-medium text-gray-900">${escapeHtml(etudiant)}</div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm text-gray-900 max-w-xs truncate" title="${escapeHtml(theme)}">${escapeHtml(theme)}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                    ${escapeHtml(salle)}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${dateFormatted}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">${heureFormatted}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                    Programmée
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                <button onclick="editPlanification(this)" 
                        class="text-green-600 hover:text-green-900 transition-colors duration-200" title="Modifier">
                    <i class="fas fa-edit"></i>
                </button>
                <button onclick="deletePlanification(this)" 
                        class="text-red-600 hover:text-red-900 transition-colors duration-200" title="Supprimer">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        `;

        // Reset form
        resetForm();

        // Mettre à jour le compteur
        updateCounter();

        // Show success message
        showNotification('Soutenance programmée avec succès', 'success');
    }

    function checkSalleConflict(salle, date, heure) {
        const tbody = document.getElementById('planificationBody');
        const rows = tbody.querySelectorAll('tr');

        for (let row of rows) {
            const rowSalle = row.cells[3].textContent.trim();
            const rowDate = row.cells[4].textContent.trim();
            const rowHeure = row.cells[5].textContent.trim();

            if (rowSalle === salle && rowDate === formatDate(date) && rowHeure === formatTime(heure)) {
                return true;
            }
        }
        return false;
    }

    function deletePlanification(btn) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer cette planification ?')) {
            return;
        }

        const row = btn.closest('tr');
        row.remove();

        updateCounter();
        showNotification('Planification supprimée', 'info');

        // Afficher l'état vide si plus de lignes
        const tbody = document.getElementById('planificationBody');
        const emptyState = document.getElementById('emptyState');

        if (tbody.children.length === 0) {
            emptyState.style.display = 'block';
        }
    }

    function editPlanification(btn) {
        const row = btn.closest('tr');
        const cells = row.cells;

        // Remplir le formulaire avec les données de la ligne
        document.getElementById('etudiant').value = cells[1].textContent.trim();
        document.getElementById('theme').value = cells[2].querySelector('div').title || cells[2].textContent.trim();
        document.getElementById('salle').value = cells[3].textContent.trim();

        // Reconvertir les dates/heures formatées
        const dateFormatted = cells[4].textContent.trim();
        const heureFormatted = cells[5].textContent.trim();

        // Supprimer la ligne actuelle
        row.remove();
        updateCounter();

        showNotification('Données chargées pour modification', 'info');
    }

    function resetForm() {
        document.getElementById('etudiant').value = '';
        document.getElementById('theme').value = '';
        document.getElementById('salle').value = '';
        document.getElementById('date').value = '';
        document.getElementById('heure').value = '';
    }

    function updateCounter() {
        const tbody = document.getElementById('planificationBody');
        const count = tbody.children.length;
        document.getElementById('totalPlanifications').textContent = count;
    }

    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            weekday: 'short',
            year: 'numeric',
            month: 'short',
            day: 'numeric'
        });
    }

    function formatTime(timeString) {
        return timeString;
    }

    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

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

    // Initialisation
    document.addEventListener('DOMContentLoaded', function () {
        // Définir la date minimale à aujourd'hui
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('date').setAttribute('min', today);

        updateCounter();
    });
</script>
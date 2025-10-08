<!-- Section d'attribution du jury -->
<div class="bg-white shadow rounded-lg p-6 mb-6 relative">
    <div class="absolute -top-3 left-1/2 transform -translate-x-1/2 bg-white px-3">
        <span class="text-lg font-medium text-green-600">Attribution Jury</span>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-4">
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

        <!-- Jury -->
        <div class="space-y-2">
            <label class="block text-sm font-medium text-gray-700 mb-3">Composition du Jury</label>
            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
                <div class="flex items-center space-x-3">
                    <input type="text" id="president"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                        placeholder="Président">
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Président</span>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="text" id="examinateur"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                        placeholder="Examinateur">
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Examinateur</span>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="text" id="dir-memoire"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                        placeholder="Directeur Mémoire">
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Directeur de memoire</span>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="text" id="cotuteur"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                        placeholder="Cotuteur">
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Encadreur</span>
                </div>

                <div class="flex items-center space-x-3">
                    <input type="text" id="rapporteur"
                        class="flex-1 px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-green-500 focus:border-green-500"
                        placeholder="Rapporteur du Stage">
                    <span class="text-sm text-gray-600 min-w-0 w-20 text-right">Maître de Stage</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bouton Enregistrer -->
    <div class="absolute -bottom-4 right-4">
        <button onclick="addRow()"
            class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg shadow-md transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
            Enregistrer
        </button>
    </div>
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
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions
                    </th>
                </tr>
            </thead>
            <tbody id="tableBody" class="bg-white divide-y divide-gray-200">
                <!-- Les lignes seront ajoutées dynamiquement -->
            </tbody>
        </table>
    </div>
</div>

<script>
    function addRow() {
        const etudiant = document.getElementById('etudiant').value.trim();
        const theme = document.getElementById('theme').value.trim();
        const president = document.getElementById('president').value.trim();
        const examinateur = document.getElementById('examinateur').value.trim();
        const dirMemoire = document.getElementById('dir-memoire').value.trim();
        const cotuteur = document.getElementById('cotuteur').value.trim();
        const rapporteur = document.getElementById('rapporteur').value.trim();

        // Validation
        if (!etudiant || !theme) {
            alert('Veuillez renseigner au minimum l\'étudiant et le thème de soutenance.');
            return;
        }

        const tbody = document.getElementById('tableBody');
        const row = tbody.insertRow();
        row.className = 'hover:bg-gray-50';

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="text" value="${escapeHtml(etudiant)}" 
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="text" value="${escapeHtml(theme)}" 
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="text" value="${escapeHtml(president)}" 
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="text" value="${escapeHtml(examinateur)}" 
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="text" value="${escapeHtml(dirMemoire)}" 
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="text" value="${escapeHtml(cotuteur)}" 
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
                <input type="text" value="${escapeHtml(rapporteur)}" 
                       class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:outline-none focus:ring-1 focus:ring-green-500 focus:border-green-500">
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-center">
                <button onclick="deleteRow(this)" 
                        class="bg-red-600 hover:bg-red-700 text-white text-xs font-medium py-1 px-3 rounded transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                    Supprimer
                </button>
            </td>
        `;

        // Reset form
        document.getElementById('etudiant').value = '';
        document.getElementById('theme').value = '';
        document.getElementById('president').value = '';
        document.getElementById('examinateur').value = '';
        document.getElementById('dir-memoire').value = '';
        document.getElementById('cotuteur').value = '';
        document.getElementById('rapporteur').value = '';

        // Show success message (optional)
        showNotification('Attribution ajoutée avec succès', 'success');
    }

    function deleteRow(btn) {
        const row = btn.closest('tr');
        row.remove();
        showNotification('Attribution supprimée', 'info');
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
        // Simple notification (you can enhance this)
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 px-4 py-2 rounded-lg shadow-lg text-white text-sm z-50 transition-opacity duration-300 ${type === 'success' ? 'bg-green-600' :
            type === 'error' ? 'bg-red-600' :
                'bg-blue-600'
            }`;
        notification.textContent = message;

        document.body.appendChild(notification);

        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }
</script>
// Données de démonstration
(() => {
const reclamations = [
    {
        id: "REC-2023-001",
        sujet: "Problème de facturation",
        description: "La facture du mois de janvier contient des erreurs de calcul.",
        date: "2023-01-15",
        lastUpdate: "2023-01-20",
        statut: "en-cours",
        priorite: "haute"
    },
    {
        id: "REC-2023-002",
        sujet: "Service non fonctionnel",
        description: "Le service en ligne ne répond pas depuis 2 jours.",
        date: "2023-02-05",
        lastUpdate: "2023-02-10",
        statut: "resolue",
        priorite: "moyenne"
    },
    {
        id: "REC-2023-003",
        sujet: "Retard de livraison",
        description: "Commande non livrée dans les délais annoncés.",
        date: "2023-02-18",
        lastUpdate: "2023-02-25",
        statut: "sans-suite",
        priorite: "basse"
    },
    {
        id: "REC-2023-004",
        sujet: "Produit défectueux",
        description: "Le produit reçu ne correspond pas à la description.",
        date: "2023-03-02",
        lastUpdate: "2023-03-05",
        statut: "en-cours",
        priorite: "haute"
    },
    {
        id: "REC-2023-005",
        sujet: "Demande de remboursement",
        description: "Demande de remboursement pour un produit retourné.",
        date: "2023-03-10",
        lastUpdate: "2023-03-15",
        statut: "en-cours",
        priorite: "moyenne"
    },
    {
        id: "REC-2023-006",
        sujet: "Question sur la garantie",
        description: "Demande d'information sur les conditions de garantie.",
        date: "2023-03-12",
        lastUpdate: "2023-03-12",
        statut: "resolue",
        priorite: "basse"
    },
    {
        id: "REC-2023-007",
        sujet: "Problème technique",
        description: "L'application mobile plante régulièrement.",
        date: "2023-03-20",
        lastUpdate: "2023-03-22",
        statut: "en-cours",
        priorite: "haute"
    },
    {
        id: "REC-2023-008",
        sujet: "Demande d'information",
        description: "Besoin de précisions sur les tarifs.",
        date: "2023-03-25",
        lastUpdate: "2023-03-26",
        statut: "resolue",
        priorite: "basse"
    }
];

// Variables pour la pagination (scopées pour éviter conflits globaux)
let hrCurrentPage = 1;
const hrItemsPerPage = 5;
let hrFilteredReclamations = [...reclamations];

function hrHasDom() {
    return (
        document.getElementById('reclamations-body') !== null &&
        document.getElementById('status-filter') !== null &&
        document.getElementById('date-filter') !== null &&
        document.querySelector('.prev-page') !== null &&
        document.querySelector('.next-page') !== null
    );
}

// Fonction pour afficher les réclamations
function displayReclamations() {
    const tbody = document.getElementById('reclamations-body');
    if (!tbody) return;
    tbody.innerHTML = '';

    // Calcul des éléments à afficher pour la pagination
    const startIndex = (hrCurrentPage - 1) * hrItemsPerPage;
    const endIndex = startIndex + hrItemsPerPage;
    const paginatedItems = hrFilteredReclamations.slice(startIndex, endIndex);

    // Mise à jour des infos de pagination
    const startEl = document.getElementById('start-item');
    const endEl = document.getElementById('end-item');
    const totalEl = document.getElementById('total-items');
    if (startEl) startEl.textContent = String(startIndex + 1);
    if (endEl) endEl.textContent = String(Math.min(endIndex, hrFilteredReclamations.length));
    if (totalEl) totalEl.textContent = String(hrFilteredReclamations.length);

    // Génération des lignes du tableau
    paginatedItems.forEach(reclamation => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-gray-50';

        // Déterminer la classe CSS en fonction du statut
        let statusClass = '';
        let statusText = '';
        switch(reclamation.statut) {
            case 'en-cours':
                statusClass = 'bg-blue-100 text-blue-800';
                statusText = 'En cours';
                break;
            case 'resolue':
                statusClass = 'bg-green-100 text-green-800';
                statusText = 'Résolue';
                break;
            case 'sans-suite':
                statusClass = 'bg-red-100 text-red-800';
                statusText = 'Sans suite';
                break;
        }

        row.innerHTML = `
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${reclamation.id}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                <div class="font-medium text-gray-900">${reclamation.sujet}</div>
                <div class="text-gray-500 truncate max-w-xs">${reclamation.description}</div>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(reclamation.date)}</td>
            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(reclamation.lastUpdate)}</td>
            <td class="px-6 py-4 whitespace-nowrap">
                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}">
                    ${statusText}
                </span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                <button class="text-indigo-600 hover:text-indigo-900 mr-3 view-btn" data-id="${reclamation.id}">
                    <i class="fas fa-eye"></i>
                </button>
                <button class="text-gray-600 hover:text-gray-900 print-btn" data-id="${reclamation.id}">
                    <i class="fas fa-print"></i>
                </button>
            </td>
        `;

        tbody.appendChild(row);
    });

    // Mettre à jour la pagination
    updatePagination();
}

// Fonction pour formater la date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'short', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Fonction pour mettre à jour les compteurs de statut
function updateStatusCounts() {
    const enCours = reclamations.filter(r => r.statut === 'en-cours').length;
    const resolues = reclamations.filter(r => r.statut === 'resolue').length;
    const sansSuite = reclamations.filter(r => r.statut === 'sans-suite').length;

    const enCoursEl = document.getElementById('en-cours-count');
    const resoluesEl = document.getElementById('resolues-count');
    const sansSuiteEl = document.getElementById('sans-suite-count');
    if (enCoursEl) enCoursEl.textContent = String(enCours);
    if (resoluesEl) resoluesEl.textContent = String(resolues);
    if (sansSuiteEl) sansSuiteEl.textContent = String(sansSuite);
}

// Fonction pour filtrer les réclamations
function filterReclamations() {
    const statusEl = document.getElementById('status-filter');
    const dateEl = document.getElementById('date-filter');
    if (!statusEl || !dateEl) return;

    const statusFilter = statusEl.value;
    const dateFilter = dateEl.value;

    hrFilteredReclamations = reclamations.filter(reclamation => {
        // Filtre par statut
        if (statusFilter !== 'all' && reclamation.statut !== statusFilter) {
            return false;
        }
        return true;
    });

    // Trier par date
    if (dateFilter === 'recent') {
        hrFilteredReclamations.sort((a, b) => new Date(b.date) - new Date(a.date));
    } else {
        hrFilteredReclamations.sort((a, b) => new Date(a.date) - new Date(b.date));
    }

    // Réinitialiser à la première page après filtrage
    hrCurrentPage = 1;
    displayReclamations();
}

// Fonction pour mettre à jour la pagination
function updatePagination() {
    const totalPages = Math.ceil(hrFilteredReclamations.length / hrItemsPerPage);
    const paginationContainer = document.querySelector('nav.relative');
    if (!paginationContainer) return;

    // Supprimer les numéros de page existants (sauf les boutons précédent/suivant)
    const existingPageNumbers = document.querySelectorAll('.page-number');
    existingPageNumbers.forEach(el => el.remove());

    // Ajouter les numéros de page
    for (let i = 1; i <= totalPages; i++) {
        const pageLink = document.createElement('a');
        pageLink.href = '#';
        pageLink.className = `relative inline-flex items-center px-4 py-2 border text-sm font-medium page-number ${i === hrCurrentPage ? 'bg-indigo-50 border-indigo-500 text-indigo-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'}`;
        pageLink.textContent = i;
        pageLink.addEventListener('click', (e) => {
            e.preventDefault();
            hrCurrentPage = i;
            displayReclamations();
        });

        // Insérer avant le bouton suivant
        const nextButton = document.querySelector('.next-page');
        paginationContainer.insertBefore(pageLink, nextButton);
    }

    // Désactiver les boutons précédent/suivant si nécessaire
    const prevBtn = document.querySelector('.prev-page');
    const nextBtn = document.querySelector('.next-page');
    if (prevBtn) prevBtn.classList.toggle('opacity-50', hrCurrentPage === 1);
    if (nextBtn) nextBtn.classList.toggle('opacity-50', hrCurrentPage === totalPages);
}

document.addEventListener('DOMContentLoaded', () => {
    // Si on n'est pas sur la page "historique réclamations", ne rien exécuter (évite erreurs globales).
    if (!hrHasDom()) return;

    // Gestion des événements
    document.getElementById('status-filter')?.addEventListener('change', filterReclamations);
    document.getElementById('date-filter')?.addEventListener('change', filterReclamations);

    document.querySelector('.prev-page')?.addEventListener('click', (e) => {
        e.preventDefault();
        if (hrCurrentPage > 1) {
            hrCurrentPage--;
            displayReclamations();
        }
    });

    document.querySelector('.next-page')?.addEventListener('click', (e) => {
        e.preventDefault();
        const totalPages = Math.ceil(hrFilteredReclamations.length / hrItemsPerPage);
        if (hrCurrentPage < totalPages) {
            hrCurrentPage++;
            displayReclamations();
        }
    });

    // Simulation de bouton "Voir" / "Imprimer"
    document.addEventListener('click', (e) => {
        const viewBtn = e.target && e.target.closest ? e.target.closest('.view-btn') : null;
        if (viewBtn) {
            const id = viewBtn.getAttribute('data-id');
            if (id) alert(`Affichage des détails de la réclamation ${id}`);
        }
        const printBtn = e.target && e.target.closest ? e.target.closest('.print-btn') : null;
        if (printBtn) {
            const id = printBtn.getAttribute('data-id');
            if (id) alert(`Impression de la réclamation ${id}`);
        }
    });

    // Initialisation
    updateStatusCounts();
    displayReclamations();
});

})();
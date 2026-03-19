// Données de démonstration
const claimsData = [
    {
        id: "CLAIM-2023-001",
        subject: "Problème de note en mathématiques",
        type: "academic",
        date: "2023-05-15",
        status: "pending",
        description: "Je pense qu'il y a une erreur dans la notation de mon examen final de mathématiques. J'ai comparé avec mes camarades et il semble y avoir une incohérence.",
        attachments: ["examen_math.pdf"],
        response: null
    },
    {
        id: "CLAIM-2023-002",
        subject: "Remboursement de frais de scolarité",
        type: "financial",
        date: "2023-05-10",
        status: "in_progress",
        description: "J'ai payé des frais de scolarité en double par erreur et je demande un remboursement pour le montant excédentaire.",
        attachments: ["paiement1.jpg", "paiement2.jpg"],
        response: "Votre demande est en cours de vérification par notre service financier. Nous reviendrons vers vous dans les 5 jours ouvrables."
    },
    {
        id: "CLAIM-2023-003",
        subject: "Accès à la plateforme e-learning",
        type: "technical",
        date: "2023-05-05",
        status: "resolved",
        description: "Je n'arrive pas à accéder à la plateforme e-learning depuis 3 jours. J'ai essayé sur différents appareils et réseaux sans succès.",
        attachments: ["erreur_login.png"],
        response: "Le problème a été identifié et résolu. Il s'agissait d'un problème de configuration de compte. Vous devriez maintenant pouvoir accéder normalement à la plateforme."
    },
    {
        id: "CLAIM-2023-004",
        subject: "Changement d'emploi du temps",
        type: "administrative",
        date: "2023-04-28",
        status: "rejected",
        description: "Je souhaite changer mon emploi du temps pour le semestre prochain en raison d'un conflit avec mon travail à temps partiel.",
        attachments: ["emploi_du_temps.pdf", "contrat_travail.pdf"],
        response: "Nous regrettons de ne pas pouvoir accéder à votre demande car les emplois du temps sont fixés en fonction des contraintes pédagogiques et ne peuvent être modifiés pour des raisons personnelles."
    },
    {
        id: "CLAIM-2023-005",
        subject: "Demande de certificat de scolarité",
        type: "administrative",
        date: "2023-04-20",
        status: "resolved",
        description: "J'ai besoin d'un certificat de scolarité pour ma demande de bourse. Je l'ai demandé il y a 2 semaines mais ne l'ai toujours pas reçu.",
        attachments: [],
        response: "Votre certificat de scolarité a été envoyé par email le 25/04/2023. Vous pouvez également le télécharger depuis votre espace étudiant."
    }
];

// Variables (scopées pour éviter conflits globaux)
let srCurrentPage = 1;
const srItemsPerPage = 5;
let srFilteredClaims = [...claimsData];

function srIsClaimsPage() {
    // Si les éléments principaux n'existent pas, on est sur une autre page.
    return document.getElementById('claimsTableBody') && document.getElementById('applyFilters');
}

// Fonction pour formater la date
function formatDate(dateString) {
    const options = { year: 'numeric', month: 'long', day: 'numeric' };
    return new Date(dateString).toLocaleDateString('fr-FR', options);
}

// Fonction pour obtenir la classe de statut
function getStatusClass(status) {
    switch (status) {
        case 'pending': return 'bg-yellow-100 text-yellow-800';
        case 'in_progress': return 'bg-purple-100 text-purple-800';
        case 'resolved': return 'bg-green-100 text-green-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        default: return 'bg-gray-100 text-gray-800';
    }
}

// Fonction pour obtenir le texte du statut
function getStatusText(status) {
    switch (status) {
        case 'pending': return 'En attente';
        case 'in_progress': return 'En traitement';
        case 'resolved': return 'Résolue';
        case 'rejected': return 'Rejetée';
        default: return status;
    }
}

// Fonction pour obtenir le texte du type
function getTypeText(type) {
    switch (type) {
        case 'academic': return 'Académique';
        case 'financial': return 'Financière';
        case 'administrative': return 'Administrative';
        case 'technical': return 'Technique';
        default: return type;
    }
}

// Fonction pour afficher les réclamations
function displayClaims() {
    const tableBody = document.getElementById('claimsTableBody');
    if (!tableBody) return;

    const startIndex = (srCurrentPage - 1) * srItemsPerPage;
    const endIndex = startIndex + srItemsPerPage;
    const paginatedClaims = srFilteredClaims.slice(startIndex, endIndex);

    tableBody.innerHTML = '';

    if (paginatedClaims.length === 0) {
        tableBody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                    Aucune réclamation trouvée avec les critères sélectionnés
                </td>
            </tr>
        `;
    } else {
        paginatedClaims.forEach(claim => {
            const row = document.createElement('tr');
            row.className = 'hover:bg-gray-50';
            row.innerHTML = `
                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">${claim.id}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${claim.subject}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${getTypeText(claim.type)}</td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${formatDate(claim.date)}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${getStatusClass(claim.status)}">
                        ${getStatusText(claim.status)}
                    </span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button onclick="showClaimDetails('${claim.id}')" class="text-blue-600 hover:text-blue-900 mr-3">
                        <i class="fas fa-eye"></i> Voir
                    </button>
                </td>
            `;
            tableBody.appendChild(row);
        });
    }

    // Mettre à jour la pagination
    const startEl = document.getElementById('startItem');
    const endEl = document.getElementById('endItem');
    const totalEl = document.getElementById('totalItems');
    if (startEl) startEl.textContent = String(startIndex + 1);
    if (endEl) endEl.textContent = String(Math.min(endIndex, srFilteredClaims.length));
    if (totalEl) totalEl.textContent = String(srFilteredClaims.length);

    // Activer/désactiver les boutons de pagination
    const prevBtn = document.getElementById('prevPage');
    const nextBtn = document.getElementById('nextPage');
    if (prevBtn) prevBtn.disabled = srCurrentPage === 1;
    if (nextBtn) nextBtn.disabled = endIndex >= srFilteredClaims.length;
}

// Fonction pour afficher les statistiques
function updateStats() {
    const total = document.getElementById('totalClaims');
    const pending = document.getElementById('pendingClaims');
    const inProgress = document.getElementById('inProgressClaims');
    const resolved = document.getElementById('resolvedClaims');
    if (total) total.textContent = String(claimsData.length);
    if (pending) pending.textContent = String(claimsData.filter(c => c.status === 'pending').length);
    if (inProgress) inProgress.textContent = String(claimsData.filter(c => c.status === 'in_progress').length);
    if (resolved) resolved.textContent = String(claimsData.filter(c => c.status === 'resolved').length);
}

// Fonction pour filtrer les réclamations
function filterClaims() {
    const statusEl = document.getElementById('statusFilter');
    const typeEl = document.getElementById('typeFilter');
    const dateEl = document.getElementById('dateFilter');
    if (!statusEl || !typeEl || !dateEl) return;

    const statusFilter = statusEl.value;
    const typeFilter = typeEl.value;
    const dateFilter = dateEl.value;

    srFilteredClaims = claimsData.filter(claim => {
        // Filtre par statut
        if (statusFilter !== 'all' && claim.status !== statusFilter) {
            return false;
        }

        // Filtre par type
        if (typeFilter !== 'all' && claim.type !== typeFilter) {
            return false;
        }

        // Filtre par date
        if (dateFilter !== 'all') {
            const claimDate = new Date(claim.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (dateFilter === 'today') {
                const claimDay = claimDate.toDateString();
                const todayDay = today.toDateString();
                if (claimDay !== todayDay) return false;
            } else if (dateFilter === 'week') {
                const weekStart = new Date(today);
                weekStart.setDate(today.getDate() - today.getDay()); // Dimanche de cette semaine
                if (claimDate < weekStart || claimDate > today) return false;
            } else if (dateFilter === 'month') {
                const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                if (claimDate < monthStart || claimDate > today) return false;
            }
        }

        return true;
    });

    srCurrentPage = 1; // Réinitialiser à la première page après filtrage
    displayClaims();
}

// Événements
document.addEventListener('DOMContentLoaded', () => {
    if (!srIsClaimsPage()) return;

    // Initialiser l'affichage
    updateStats();
    displayClaims();

    // Gestion des filtres
    document.getElementById('applyFilters')?.addEventListener('click', filterClaims);

    // Gestion de la pagination
    document.getElementById('prevPage')?.addEventListener('click', () => {
        if (srCurrentPage > 1) {
            srCurrentPage--;
            displayClaims();
        }
    });

    document.getElementById('nextPage')?.addEventListener('click', () => {
        const totalPages = Math.ceil(srFilteredClaims.length / srItemsPerPage);
        if (srCurrentPage < totalPages) {
            srCurrentPage++;
            displayClaims();
        }
    });
});
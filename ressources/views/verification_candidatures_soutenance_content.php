<?php
// Récupérer les données des rapports depuis le contrôleur
$rapports = $GLOBALS['rapports'] ?? [];
$nbRapports = $GLOBALS['nbRapports'] ?? 0;
$statsRapports = $GLOBALS['statsRapports'] ?? [];

// Fonction pour obtenir la classe CSS du statut
function getStatutClass($statut) {
    switch ($statut) {
        case 'valide':
            return 'text-green-500 ';
        case 'rejete':
            return 'text-red-500 ';
        case 'en_cours':
            return 'text-blue-500 ';
        case 'en_attente':
            return 'text-yellow-500 ';
        default:
            return 'text-gray-500 ';
    }
}

// Fonction pour traduire le statut
function traduireStatut($statut) {
    switch ($statut) {
        case 'valide':
            return 'Validé';
        case 'rejete':
            return 'Rejeté';
        case 'en_cours':
            return 'En cours';
        case 'en_attente':
            return 'En attente';
        default:
            return ucfirst($statut);
    }
}
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vérification des rapports étudiants</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
    :root {
        --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        --warning-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
        --danger-gradient: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
        --info-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
        --card-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        --hover-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
    }

    body {
        min-height: 100vh;
    }

    .stat-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 16px;
        padding: 0.5rem;
        box-shadow: var(--card-shadow);
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--hover-shadow);
    }

    .stat-card.blue {
        background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    }

    .stat-card.yellow {
        background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
    }

    .stat-card.green {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
    }

    .stat-card.red {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
    }

    .stat-card.orange {
        background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
    }

    .search-container {
        position: relative;
        background: rgba(255, 255, 255, 0.9);
        border-radius: 20px;
        padding: 0.5rem;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
    }

    .search-input {
        width: 100%;
        padding: 1rem 1rem 1rem 3rem;
        border: none;
        border-radius: 15px;
        background: transparent;
        font-size: 1rem;
        color: #374151;
        outline: none;
    }

    .search-input::placeholder {
        color: #9ca3af;
    }

    .search-icon {
        position: absolute;
        left: 1.5rem;
        top: 50%;
        transform: translateY(-50%);
        color: #667eea;
        font-size: 1.2rem;
    }

    .table-container {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: var(--card-shadow);
    }

    .table-header {
        color: white;
        padding: 1rem;
    }

    .table-header h2 {
        margin: 0;
        font-size: 1.5rem;
        font-weight: 700;
    }

    .table-header p {
        margin: 0.5rem 0 0;
        opacity: 0.9;
        font-size: 1rem;
    }

    .table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
    }

    .table th {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        color: #374151;
        font-weight: 600;
        padding: 1rem;
        text-align: center;
        border-bottom: 2px solid #e2e8f0;
        font-size: 0.9rem;
        text-transform: capitalize;
    }

    .table td {
        padding: 1.2rem 1rem;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .table tbody tr {
        transition: all 0.3s ease;
    }

    .table tbody tr:hover {
        background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
        transform: scale(1.01);
    }


    .action-btn {
        padding: 0.6rem 1.2rem;
        border: none;
        border-radius: 12px;
        font-size: 0.8rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        margin: 0 0.2rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 100px;
    }

    .action-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
    }

    .btn-validate {
        background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        color: white;
    }

    .btn-validate:hover {
        background: linear-gradient(135deg, #059669 0%, #047857 100%);
    }

    .btn-reject {
        background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
        color: white;
    }

    .btn-reject:hover {
        background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
    }

    .btn-detail {
        background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        color: white;
    }

    .btn-detail:hover {
        background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    }

    .action-buttons {
        display: flex;
        flex-wrap: wrap;
        gap: 0.5rem;
        justify-content: center;
        align-items: center;
    }

    .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #6b7280;
    }

    .empty-state i {
        font-size: 4rem;
        margin-bottom: 1rem;
        opacity: 0.5;
    }

    .modal {
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        background: white;
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
    }

    /* Désactiver le scroll quand les modals sont ouvertes */
    body.modal-open {
        overflow: hidden;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .fade-in {
        animation: fadeIn 0.5s ease-out;
    }

    @media (max-width: 768px) {
        .stats-grid {
            grid-template-columns: 1fr;
        }

        .table-container {
            overflow-x: auto;
        }

        .action-buttons {
            flex-direction: column;
            gap: 0.3rem;
        }

        .action-btn {
            min-width: 80px;
            padding: 0.5rem 1rem;
            font-size: 0.7rem;
        }
    }
    </style>
</head>

<body class="min-h-screen p-4 md:p-8">
    <?php
    // Afficher les messages de session
    if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
        $message = $_SESSION['message'];
        $messageType = $_SESSION['message_type'] ?? 'info';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
        
        echo '<div id="notification" class="fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 ' . 
             (($messageType === 'success') ? 'bg-green-500 text-white' : 
              (($messageType === 'error') ? 'bg-red-500 text-white' : 
              'bg-blue-500 text-white')) . '">';
        echo '<div class="flex items-center">';
        echo '<i class="fas ' . (($messageType === 'success') ? 'fa-check-circle' : 
                               (($messageType === 'error') ? 'fa-exclamation-circle' : 
                               'fa-info-circle')) . ' mr-2"></i>';
        echo '<span>' . htmlspecialchars($message) . '</span>';
        echo '</div>';
        echo '</div>';
        
        echo '<script>
            setTimeout(function() {
                const notification = document.getElementById("notification");
                if (notification) {
                    notification.style.transform = "translateX(full)";
                    setTimeout(function() {
                        if (notification.parentNode) {
                            notification.parentNode.removeChild(notification);
                        }
                    }, 300);
                }
            }, 3000);
        </script>';
    }
    ?>
    <div class="max-w-7xl mx-auto">
        <!-- Header Section -->
        <div class="glass-card rounded-2xl p-6 md:p-8 mb-8 fade-in">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div class="flex items-center gap-4">
                    <div class="w-16 h-16 bg-green-600 rounded-2xl flex items-center justify-center shadow-lg">
                        <i class="fas fa-clipboard-check text-2xl text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl md:text-4xl font-bold text-green-600">
                            Vérification des Rapports
                        </h1>
                        <p class="text-gray-600 mt-2 text-lg">
                            Gérez et validez les rapports soumis par les étudiants
                        </p>
                    </div>
                </div>

                <!-- Statistics Cards -->
                
            </div>
        </div>

        <!-- Search Section -->

        <div class="search-container mb-6">
            <i class="fas fa-search search-icon"></i>
            <input type="text" id="searchInput" class="search-input"
                placeholder="Rechercher par nom d'étudiant, rapport ou thème...">
        </div>


        <!-- Table Section -->
        <div class="table-container fade-in">
            <div class="table-header bg-green-600">
                <h2><i class="fas fa-list-ul mr-3"></i>Liste des Rapports</h2>
                <p>Vérifiez, validez ou rejetez les rapports soumis par les étudiants</p>
            </div>

            <div class="overflow-x-auto">
                <table id="rapportsTable" class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user-graduate mr-2"></i>Étudiant</th>
                            <th><i class="fas fa-file-lines mr-2"></i>Rapport</th>
                            <th><i class="fas fa-lightbulb mr-2"></i>Thème</th>
                            <th><i class="fas fa-calendar-day mr-2"></i>Date de dépôt</th>
                            <th class="text-center"><i class="fas fa-cogs mr-2"></i>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rapports)): ?>
                        <tr>
                            <td colspan="6">
                                <div class="empty-state">
                                    <i class="fas fa-file-circle-xmark"></i>
                                    <h3 class="text-xl font-semibold mb-2">Aucun rapport trouvé</h3>
                                    <p class="text-gray-500">Les rapports soumis par les étudiants apparaîtront ici</p>
                                </div>
                            </td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($rapports as $i => $rapport): ?>
                        <tr
                            class="hover:bg-gradient-to-r hover:from-blue-50 hover:to-purple-50 transition-all duration-300">
                            <td class="font-semibold text-gray-800">
                                <div class="flex items-center gap-3">
                                    <div>
                                        <div class="font-semibold">
                                            <?= htmlspecialchars($rapport->nom_etu . ' ' . $rapport->prenom_etu) ?>
                                        </div>
                                        <div class="text-sm text-gray-500">Étudiant</div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="font-bold text-gray-900"><?= htmlspecialchars($rapport->nom_rapport) ?>
                                </div>
                                <div class="text-sm text-gray-500">Rapport de master</div>
                            </td>
                            <td>
                                <div class="italic text-blue-700 max-w-xs truncate">
                                    <?= htmlspecialchars($rapport->theme_rapport) ?></div>
                            </td>
                            <td>
                                <div class="flex items-center gap-2">
                                    <i class="fas fa-clock text-orange-500"></i>
                                    <span
                                        class="font-semibold text-gray-700"><?= date('d/m/Y', strtotime($rapport->date_depot)) ?></span>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="flex items-center justify-center gap-2 action-buttons">
                                    <button onclick="voirDetail(<?= $rapport->id_rapport ?>)"
                                        class="action-btn btn-detail">
                                        <i class="fas fa-eye mr-1"></i> Voir détail
                                    </button>

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

    <!-- Modal pour les détails du rapport -->
    <div id="detailModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-4xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-file-alt mr-2 text-blue-500"></i>
                    Détails du rapport
                </h2>
                <button onclick="fermerModal()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div id="modalContent">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>

    <!-- Modal de confirmation validation/rejet -->
    <div id="confirmModal" class="fixed inset-0 hidden z-50 items-center justify-center p-4">
        <div class="modal-content bg-white max-w-md w-full rounded-xl shadow-2xl p-6 transform transition-all duration-300 scale-95 opacity-0"
            id="confirmModalContent">
            <h3 id="confirmModalTitle" class="text-xl font-bold mb-4 text-center text-gray-800"></h3>

            <!-- Formulaire PHP pour valider -->
            <form id="validerForm" method="POST" action="?page=verification_candidatures_soutenance"
                style="display: none;">
                <input type="hidden" name="valider" value="1">
                <input type="hidden" id="validerRapportId" name="id_rapport">
                <div class="mb-4">
                    <label for="validerComment" class="block text-sm font-medium text-gray-700 mb-2">Commentaire
                        (obligatoire)</label>
                    <textarea id="validerComment" name="commentaire" rows="3"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500 resize-none"
                        placeholder="Entrez votre commentaire..." required></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeConfirmModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors font-medium">
                        Confirmer l'approbation
                    </button>
                </div>
            </form>

            <!-- Formulaire PHP pour rejeter -->
            <form id="rejeterForm" method="POST" action="?page=verification_candidatures_soutenance"
                style="display: none;">
                <input type="hidden" name="rejeter" value="1">
                <input type="hidden" id="rejeterRapportId" name="id_rapport">
                <div class="mb-4">
                    <label for="rejeterComment" class="block text-sm font-medium text-gray-700 mb-2">Commentaire
                        (obligatoire)</label>
                    <textarea id="rejeterComment" name="commentaire" rows="3"
                        class="w-full p-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500 resize-none"
                        placeholder="Entrez votre commentaire..." required></textarea>
                </div>
                <div class="flex justify-end gap-3 mt-6">
                    <button type="button" onclick="closeConfirmModal()"
                        class="px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-colors font-medium">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors font-medium">
                        Confirmer le rejet
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Recherche dynamique dans le tableau
    const searchInput = document.getElementById('searchInput');
    searchInput.addEventListener('input', function() {
        const term = this.value.toLowerCase();
        const rows = document.querySelectorAll('#rapportsTable tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });

    // Remplace les fonctions validerRapport/rejeterRapport par ouverture de modale
    let pendingAction = null;
    let pendingRapportId = null;

    function validerRapport(idRapport) {
        openConfirmModal('valider', idRapport);
    }

    function rejeterRapport(idRapport) {
        openConfirmModal('rejeter', idRapport);
    }

    function openConfirmModal(action, idRapport) {
        console.log('Ouverture modal pour action:', action, 'ID:', idRapport); // Debug

        // Stocke l'action et l'ID du rapport
        pendingAction = action;
        pendingRapportId = idRapport;

        // Change le titre selon l'action
        document.getElementById('confirmModalTitle').textContent = (action === 'valider') ?
            'Confirmer l\'approbation du rapport ?' : 'Confirmer la désapprobation du rapport ?';

        // Afficher le bon formulaire selon l'action
        const validerForm = document.getElementById('validerForm');
        const rejeterForm = document.getElementById('rejeterForm');

        if (action === 'valider') {
            validerForm.style.display = 'block';
            rejeterForm.style.display = 'none';
            document.getElementById('validerRapportId').value = idRapport;
            document.getElementById('validerComment').value = '';
        } else {
            validerForm.style.display = 'none';
            rejeterForm.style.display = 'block';
            document.getElementById('rejeterRapportId').value = idRapport;
            document.getElementById('rejeterComment').value = '';
        }

        // Affiche la modal
        const modal = document.getElementById('confirmModal');
        const modalContent = document.getElementById('confirmModalContent');

        // S'assurer que la modal est bien cachée au début
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        // Attendre un peu avant d'afficher
        setTimeout(() => {
            modal.classList.remove('hidden');
            modal.classList.add('flex');

            // Désactiver le scroll de la page
            document.body.classList.add('modal-open');

            // Animation d'ouverture avec un délai plus long
            setTimeout(() => {
                modalContent.style.transform = 'scale(1)';
                modalContent.style.opacity = '1';
            }, 50);
        }, 10);
    }

    function closeConfirmModal() {
        const modal = document.getElementById('confirmModal');
        const modalContent = document.getElementById('confirmModalContent');

        // Animation de fermeture
        modalContent.style.transform = 'scale(0.95)';
        modalContent.style.opacity = '0';

        setTimeout(() => {
            modal.classList.add('hidden');
            modal.classList.remove('flex');

            // Réinitialiser les styles
            modalContent.style.transform = 'scale(0.95)';
            modalContent.style.opacity = '0';

            // Réactiver le scroll de la page
            document.body.classList.remove('modal-open');
        }, 300);
    }

    // Gestion des formulaires PHP
    document.getElementById('validerForm').addEventListener('submit', function(e) {
        console.log('Formulaire de validation soumis');
        // Le formulaire sera soumis normalement via POST
    });

    document.getElementById('rejeterForm').addEventListener('submit', function(e) {
        console.log('Formulaire de rejet soumis');
        // Le formulaire sera soumis normalement via POST
    });

    // Fonction pour voir les détails d'un rapport
    function voirDetail(idRapport) {
        // Afficher la modal avec overlay
        const modal = document.getElementById('detailModal');
        modal.classList.remove('hidden');
        modal.classList.add('flex');

        // Désactiver le scroll de la page
        document.body.classList.add('modal-open');

        // Afficher un loader
        document.getElementById('modalContent').innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500"></div>
                <span class="ml-2 text-gray-600">Chargement des détails...</span>
            </div>
        `;

        // Charger les détails via AJAX
        fetch('?page=verification_candidatures_soutenance&action=detail&id=' + idRapport)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erreur lors du chargement');
                }
                return response.text();
            })
            .then(html => {
                document.getElementById('modalContent').innerHTML = html;
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('modalContent').innerHTML = `
                    <div class="text-center py-8 text-red-500">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Erreur lors du chargement des détails</p>
                    </div>
                `;
            });
    }

    // Fonction pour fermer le modal
    function fermerModal() {
        const modal = document.getElementById('detailModal');
        modal.classList.add('hidden');
        modal.classList.remove('flex');

        // Réactiver le scroll de la page
        document.body.classList.remove('modal-open');
    }

    // Fermer les modals en cliquant à l'extérieur
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('detailModal');
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fermerModal();
            }
        });

        const confirmModal = document.getElementById('confirmModal');
        const confirmModalContent = document.getElementById('confirmModalContent');

        // Empêcher la propagation des clics à l'intérieur de la modal
        confirmModalContent.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        // Empêcher la propagation des clics sur les boutons
        const confirmButtons = confirmModalContent.querySelectorAll('button');
        confirmButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        });

        // Empêcher la propagation des clics sur le formulaire
        const confirmForm = document.getElementById('validerForm');
        confirmForm.addEventListener('click', function(e) {
            e.stopPropagation();
        });

        confirmModal.addEventListener('click', function(e) {
            // Ne fermer que si on clique sur l'overlay (pas sur le contenu de la modal)
            if (e.target === confirmModal && !confirmModalContent.contains(e.target)) {
                closeConfirmModal();
            }
        });
    });

    // Fermer les modals avec la touche Escape
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            fermerModal();
            closeConfirmModal();
        }
    });

    // Fonction pour afficher des notifications
    function showNotification(type, message) {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 p-4 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full ${
            type === 'success' ? 'bg-green-500 text-white' : 
            type === 'error' ? 'bg-red-500 text-white' : 
            'bg-blue-500 text-white'
        }`;
        notification.innerHTML = `
            <div class="flex items-center">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'} mr-2"></i>
                <span>${message}</span>
            </div>
        `;

        document.body.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);

        // Auto-suppression après 3 secondes
        setTimeout(() => {
            notification.style.transform = 'translateX(full)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }
    </script>
</body>

</html>
<?php
// Include permissions helper
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Brouillons | Mr. Diarra</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-hover:hover {
            background-color: #fef3c7;
            border-left: 4px solid #f59e0b;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .draft-card {
            transition: all 0.3s ease;
        }
        .draft-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        .status-badge {
            animation: pulse 2s infinite;
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 border-r border-gray-200 bg-white">
                <div class="flex items-center justify-center h-16 px-4 bg-yellow-100 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-pen-nib text-yellow-600 mr-2"></i>
                        <span class="text-yellow-600 font-bold">Rédaction CR</span>
                    </div>
                </div>
                <div class="flex flex-col flex-grow px-4 py-4 overflow-y-auto">
                    <nav class="space-y-1">
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-edit mr-3 text-gray-500"></i>
                            Nouveau compte rendu
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-yellow-700 bg-yellow-50">
                            <i class="fas fa-file-alt mr-3 text-yellow-500"></i>
                            Brouillons
                            <span class="ml-auto bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">3</span>
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-check-circle mr-3 text-gray-500"></i>
                            Comptes rendus finalisés
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-archive mr-3 text-gray-500"></i>
                            Archives
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-template mr-3 text-gray-500"></i>
                            Modèles
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-chart-line mr-3 text-gray-500"></i>
                            Statistiques
                        </a>
                    </nav>
                    <div class="mt-auto pt-4 border-t border-gray-200">
                        <a href="#" class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-3 text-gray-500"></i>
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Top navigation -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 bg-white shadow-sm">
                <div class="flex items-center">
                    <button id="mobileMenuButton" class="md:hidden text-gray-500 focus:outline-none mr-3">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800">Mes Brouillons</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center text-sm text-gray-600">
                        <i class="fas fa-clock mr-1"></i>
                        <span>Dernière mise à jour: il y a 5 min</span>
                    </div>
                    <div class="relative">
                        <button class="flex items-center space-x-2 focus:outline-none">
                            <img class="w-8 h-8 rounded-full" src="https://randomuser.me/api/portraits/men/55.jpg" alt="Mr. Diarra">
                            <span class="text-sm font-medium text-gray-700">Mr. Diarra</span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main content area -->
            <div class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto p-6">
                    <!-- Header Section -->
                    <div class="mb-8">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between">
                                <div class="mb-4 md:mb-0">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Mes Brouillons</h2>
                                    <p class="text-gray-600">Gérez vos comptes rendus en cours de rédaction</p>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="relative">
                                        <input type="text" id="searchInput" placeholder="Rechercher un brouillon..." 
                                               class="search-input pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 w-64">
                                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                    </div>
                                    <select id="sortSelect" class="px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                                        <option value="date">Trier par date</option>
                                        <option value="name">Trier par nom</option>
                                        <option value="status">Trier par statut</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 mr-4">
                                    <i class="fas fa-file-alt text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Brouillons</p>
                                    <p class="text-2xl font-bold text-gray-900">3</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 mr-4">
                                    <i class="fas fa-edit text-yellow-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">En cours</p>
                                    <p class="text-2xl font-bold text-gray-900">2</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 mr-4">
                                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Prêts</p>
                                    <p class="text-2xl font-bold text-gray-900">1</p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 mr-4">
                                    <i class="fas fa-clock text-purple-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Derniers 7j</p>
                                    <p class="text-2xl font-bold text-gray-900">2</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Drafts List -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6" id="draftsContainer">
                        <!-- Draft Card 1 -->
                        <div class="draft-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 fade-in">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                    <span class="text-sm font-medium text-gray-600">Brouillon</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="status-badge px-2 py-1 text-xs rounded-full bg-yellow-100 text-yellow-800">En cours</span>
                                    <div class="relative">
                                        <button onclick="toggleDropdown(1)" class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div id="dropdown1" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                            <?php if (canEdit()): ?>
                                            <a href="#" onclick="editDraft(1)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-edit mr-2"></i>Continuer la rédaction
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canCreate()): ?>
                                            <a href="#" onclick="duplicateDraft(1)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-copy mr-2"></i>Dupliquer
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canEdit()): ?>
                                            <a href="#" onclick="renameDraft(1)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-signature mr-2"></i>Renommer
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canDelete()): ?>
                                            <hr class="my-1">
                                            <a href="#" onclick="deleteDraft(1)" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <i class="fas fa-trash mr-2"></i>Supprimer
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">IA Diagnostic Médical</h3>
                            <p class="text-sm text-gray-600 mb-3">Évaluation du rapport de Marie Lambert sur l'utilisation de l'IA dans le diagnostic médical...</p>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-user mr-2 w-4"></i>
                                    <span>Étudiant: Marie Lambert</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-calendar mr-2 w-4"></i>
                                    <span>Modifié: 23/05/2025 14:30</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-percentage mr-2 w-4"></i>
                                    <span>Progression: 65%</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                <div class="bg-yellow-600 h-2 rounded-full" style="width: 65%"></div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <?php if (canEdit()): ?>
                                <button onclick="editDraft(1)" class="flex items-center px-3 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700">
                                    <i class="fas fa-edit mr-2"></i>Continuer
                                </button>
                                <?php endif; ?>
                                <button onclick="previewDraft(1)" class="flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                    <i class="fas fa-eye mr-2"></i>Aperçu
                                </button>
                            </div>
                        </div>

                        <!-- Draft Card 2 -->
                        <div class="draft-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 fade-in">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                    <span class="text-sm font-medium text-gray-600">Brouillon</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-800">Prêt</span>
                                    <div class="relative">
                                        <button onclick="toggleDropdown(2)" class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div id="dropdown2" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                            <?php if (canEdit()): ?>
                                            <a href="#" onclick="editDraft(2)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-edit mr-2"></i>Modifier
                                            </a>
                                            <a href="#" onclick="finalizeDraft(2)" class="block px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                                <i class="fas fa-check mr-2"></i>Finaliser
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canCreate()): ?>
                                            <a href="#" onclick="duplicateDraft(2)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-copy mr-2"></i>Dupliquer
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canDelete()): ?>
                                            <hr class="my-1">
                                            <a href="#" onclick="deleteDraft(2)" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <i class="fas fa-trash mr-2"></i>Supprimer
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Blockchain Sécurité</h3>
                            <p class="text-sm text-gray-600 mb-3">Compte rendu d'évaluation du rapport de Jean Dupont sur la sécurité blockchain...</p>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-user mr-2 w-4"></i>
                                    <span>Étudiant: Jean Dupont</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-calendar mr-2 w-4"></i>
                                    <span>Modifié: 22/05/2025 09:15</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-percentage mr-2 w-4"></i>
                                    <span>Progression: 95%</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                <div class="bg-green-600 h-2 rounded-full" style="width: 95%"></div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <?php if (canEdit()): ?>
                                <button onclick="finalizeDraft(2)" class="flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                    <i class="fas fa-check mr-2"></i>Finaliser
                                </button>
                                <?php endif; ?>
                                <button onclick="previewDraft(2)" class="flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                    <i class="fas fa-eye mr-2"></i>Aperçu
                                </button>
                            </div>
                        </div>

                        <!-- Draft Card 3 -->
                        <div class="draft-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 fade-in">
                            <div class="flex items-center justify-between mb-4">
                                <div class="flex items-center space-x-2">
                                    <i class="fas fa-file-alt text-blue-600"></i>
                                    <span class="text-sm font-medium text-gray-600">Brouillon</span>
                                </div>
                                <div class="flex items-center space-x-2">
                                    <span class="status-badge px-2 py-1 text-xs rounded-full bg-orange-100 text-orange-800">Début</span>
                                    <div class="relative">
                                        <button onclick="toggleDropdown(3)" class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-ellipsis-v"></i>
                                        </button>
                                        <div id="dropdown3" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                            <?php if (canEdit()): ?>
                                            <a href="#" onclick="editDraft(3)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-edit mr-2"></i>Continuer la rédaction
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canCreate()): ?>
                                            <a href="#" onclick="duplicateDraft(3)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-copy mr-2"></i>Dupliquer
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canEdit()): ?>
                                            <a href="#" onclick="renameDraft(3)" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                                <i class="fas fa-signature mr-2"></i>Renommer
                                            </a>
                                            <?php endif; ?>
                                            <?php if (canDelete()): ?>
                                            <hr class="my-1">
                                            <a href="#" onclick="deleteDraft(3)" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                                <i class="fas fa-trash mr-2"></i>Supprimer
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <h3 class="text-lg font-semibold text-gray-900 mb-2">Réseaux de Neurones</h3>
                            <p class="text-sm text-gray-600 mb-3">Évaluation du rapport de Thomas Moreau sur les réseaux de neurones convolutifs...</p>
                            
                            <div class="space-y-2 mb-4">
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-user mr-2 w-4"></i>
                                    <span>Étudiant: Thomas Moreau</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-calendar mr-2 w-4"></i>
                                    <span>Modifié: 20/05/2025 16:45</span>
                                </div>
                                <div class="flex items-center text-sm text-gray-500">
                                    <i class="fas fa-percentage mr-2 w-4"></i>
                                    <span>Progression: 25%</span>
                                </div>
                            </div>
                            
                            <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                                <div class="bg-orange-500 h-2 rounded-full" style="width: 25%"></div>
                            </div>
                            
                            <div class="flex items-center justify-between">
                                <?php if (canEdit()): ?>
                                <button onclick="editDraft(3)" class="flex items-center px-3 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700">
                                    <i class="fas fa-edit mr-2"></i>Continuer
                                </button>
                                <?php endif; ?>
                                <button onclick="previewDraft(3)" class="flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                    <i class="fas fa-eye mr-2"></i>Aperçu
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State (hidden by default) -->
                    <div id="emptyState" class="hidden text-center py-12">
                        <div class="mx-auto h-24 w-24 text-gray-400 mb-4">
                            <i class="fas fa-file-alt text-6xl"></i>
                        </div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun brouillon trouvé</h3>
                        <p class="text-gray-500 mb-6">Commencez par créer un nouveau compte rendu</p>
                        <button class="inline-flex items-center px-4 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700">
                            <i class="fas fa-plus mr-2"></i>
                            Nouveau compte rendu
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity bg-black bg-opacity-50" onclick="closeModal()"></div>
            
            <div class="inline-block w-full max-w-4xl p-0 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-eye text-blue-600 mr-2"></i>
                        Aperçu du brouillon
                    </h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-8 py-6 max-h-96 overflow-y-auto">
                    <div id="previewContent" class="prose max-w-none">
                        <!-- Preview content will be inserted here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Permission variables from PHP
        const canEditPermission = <?php echo canEdit() ? 'true' : 'false'; ?>;
        const canDeletePermission = <?php echo canDelete() ? 'true' : 'false'; ?>;
        const canCreatePermission = <?php echo canCreate() ? 'true' : 'false'; ?>;

        // Variables globales
        let draftsData = [
            {
                id: 1,
                title: "IA Diagnostic Médical",
                student: "Marie Lambert",
                lastModified: "23/05/2025 14:30",
                progress: 65,
                status: "En cours"
            },
            {
                id: 2,
                title: "Blockchain Sécurité", 
                student: "Jean Dupont",
                lastModified: "22/05/2025 09:15",
                progress: 95,
                status: "Prêt"
            },
            {
                id: 3,
                title: "Réseaux de Neurones",
                student: "Thomas Moreau", 
                lastModified: "20/05/2025 16:45",
                progress: 25,
                status: "Début"
            }
        ];

        // Gestion des dropdowns
        function toggleDropdown(id) {
            const dropdown = document.getElementById(`dropdown${id}`);
            // Fermer tous les autres dropdowns
            document.querySelectorAll('[id^="dropdown"]').forEach(d => {
                if (d.id !== `dropdown${id}`) {
                    d.classList.add('hidden');
                }
            });
            dropdown.classList.toggle('hidden');
        }

        // Fermer les dropdowns quand on clique ailleurs
        document.addEventListener('click', function(event) {
            if (!event.target.closest('[onclick^="toggleDropdown"]')) {
                document.querySelectorAll('[id^="dropdown"]').forEach(d => {
                    d.classList.add('hidden');
                });
            }
        });

        // Actions sur les brouillons
        function editDraft(id) {
            showNotification(`Ouverture du brouillon ${id} pour édition...`, 'info');
            // Redirection vers l'éditeur
            setTimeout(() => {
                window.location.href = '#editor';
            }, 1000);
        }

        function previewDraft(id) {
            const draft = draftsData.find(d => d.id === id);
            document.getElementById('previewContent').innerHTML = `
                <div class="text-center mb-6">
                    <h1 class="text-2xl font-bold mb-2">COMPTE RENDU D'ÉVALUATION</h1>
                    <h2 class="text-lg font-semibold text-gray-700">Commission de Validation des Rapports</h2>
                    <p class="text-gray-600 mt-2">Université Félix Houphouët-Boigny</p>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold border-b border-gray-300 pb-2 mb-3">INFORMATIONS GÉNÉRALES</h3>
                    <p><strong>Titre du rapport :</strong> ${draft.title}</p>
                    <p><strong>Étudiant(e) :</strong> ${draft.student}</p>
                    <p><strong>Date d'évaluation :</strong> ${new Date().toLocaleDateString('fr-FR')}</p>
                    <p><strong>Statut :</strong> ${draft.status}</p>
                </div>
                
                <div class="mb-6">
                    <h3 class="text-lg font-semibold border-b border-gray-300 pb-2 mb-3">CONTENU (APERÇU PARTIEL)</h3>
                    <p class="text-gray-600 italic">Ce brouillon est complété à ${draft.progress}%</p>
                    <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                        <div class="bg-yellow-600 h-2 rounded-full" style="width: ${draft.progress}%"></div>
                    </div>
                </div>
            `;
            document.getElementById('previewModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function duplicateDraft(id) {
            const draft = draftsData.find(d => d.id === id);
            showNotification(`Duplication du brouillon "${draft.title}"...`, 'info');
            
            // Simuler la duplication
            setTimeout(() => {
                const newDraft = {
                    ...draft,
                    id: Math.max(...draftsData.map(d => d.id)) + 1,
                    title: draft.title + " (Copie)",
                    lastModified: new Date().toLocaleDateString('fr-FR') + " " + new Date().toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'}),
                    progress: 0,
                    status: "Début"
                };
                draftsData.push(newDraft);
                renderDrafts();
                showNotification('Brouillon dupliqué avec succès', 'success');
            }, 1500);
        }

        function renameDraft(id) {
            const draft = draftsData.find(d => d.id === id);
            const newTitle = prompt('Nouveau titre:', draft.title);
            if (newTitle && newTitle.trim() !== '') {
                draft.title = newTitle.trim();
                renderDrafts();
                showNotification('Brouillon renommé avec succès', 'success');
            }
        }

        function deleteDraft(id) {
            const draft = draftsData.find(d => d.id === id);
            if (confirm(`Êtes-vous sûr de vouloir supprimer le brouillon "${draft.title}" ?`)) {
                draftsData = draftsData.filter(d => d.id !== id);
                renderDrafts();
                updateStats();
                showNotification('Brouillon supprimé avec succès', 'success');
            }
        }

        function finalizeDraft(id) {
            const draft = draftsData.find(d => d.id === id);
            if (confirm(`Finaliser le compte rendu "${draft.title}" ? Cette action est irréversible.`)) {
                showNotification('Finalisation en cours...', 'info');
                setTimeout(() => {
                    // Supprimer de la liste des brouillons
                    draftsData = draftsData.filter(d => d.id !== id);
                    renderDrafts();
                    updateStats();
                    showNotification('Compte rendu finalisé avec succès', 'success');
                }, 2000);
            }
        }

        // Fonction pour fermer le modal
        function closeModal() {
            document.getElementById('previewModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        // Fonction de recherche
        function searchDrafts() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filteredDrafts = draftsData.filter(draft => 
                draft.title.toLowerCase().includes(searchTerm) || 
                draft.student.toLowerCase().includes(searchTerm)
            );
            renderDrafts(filteredDrafts);
        }

        // Fonction de tri
        function sortDrafts() {
            const sortBy = document.getElementById('sortSelect').value;
            let sortedDrafts = [...draftsData];
            
            switch(sortBy) {
                case 'name':
                    sortedDrafts.sort((a, b) => a.title.localeCompare(b.title));
                    break;
                case 'date':
                    sortedDrafts.sort((a, b) => new Date(b.lastModified) - new Date(a.lastModified));
                    break;
                case 'status':
                    sortedDrafts.sort((a, b) => a.status.localeCompare(b.status));
                    break;
            }
            
            renderDrafts(sortedDrafts);
        }

        // Fonction pour rendre les brouillons
        function renderDrafts(draftsToRender = draftsData) {
            const container = document.getElementById('draftsContainer');
            const emptyState = document.getElementById('emptyState');
            
            if (draftsToRender.length === 0) {
                container.classList.add('hidden');
                emptyState.classList.remove('hidden');
                return;
            }
            
            container.classList.remove('hidden');
            emptyState.classList.add('hidden');
            
            container.innerHTML = draftsToRender.map(draft => {
                const statusColor = draft.status === 'Prêt' ? 'green' : 
                                   draft.status === 'En cours' ? 'yellow' : 'orange';
                const progressColor = draft.progress >= 80 ? 'green' : 
                                     draft.progress >= 50 ? 'yellow' : 'orange';
                
                return `
                    <div class="draft-card bg-white rounded-lg shadow-sm border border-gray-200 p-6 fade-in">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-2">
                                <i class="fas fa-file-alt text-blue-600"></i>
                                <span class="text-sm font-medium text-gray-600">Brouillon</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="px-2 py-1 text-xs rounded-full bg-${statusColor}-100 text-${statusColor}-800">${draft.status}</span>
                                <div class="relative">
                                    <button onclick="toggleDropdown(${draft.id})" class="text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-ellipsis-v"></i>
                                    </button>
                                    <div id="dropdown${draft.id}" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                                        ${canEditPermission ? `
                                        <a href="#" onclick="editDraft(${draft.id})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-edit mr-2"></i>Continuer la rédaction
                                        </a>` : ''}
                                        ${draft.status === 'Prêt' && canEditPermission ? `
                                        <a href="#" onclick="finalizeDraft(${draft.id})" class="block px-4 py-2 text-sm text-green-600 hover:bg-green-50">
                                            <i class="fas fa-check mr-2"></i>Finaliser
                                        </a>` : ''}
                                        ${canCreatePermission ? `
                                        <a href="#" onclick="duplicateDraft(${draft.id})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-copy mr-2"></i>Dupliquer
                                        </a>` : ''}
                                        ${canEditPermission ? `
                                        <a href="#" onclick="renameDraft(${draft.id})" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                            <i class="fas fa-signature mr-2"></i>Renommer
                                        </a>` : ''}
                                        ${canDeletePermission ? `
                                        <hr class="my-1">
                                        <a href="#" onclick="deleteDraft(${draft.id})" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                            <i class="fas fa-trash mr-2"></i>Supprimer
                                        </a>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">${draft.title}</h3>
                        <p class="text-sm text-gray-600 mb-3">Évaluation du rapport de ${draft.student}...</p>
                        
                        <div class="space-y-2 mb-4">
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-user mr-2 w-4"></i>
                                <span>Étudiant: ${draft.student}</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-calendar mr-2 w-4"></i>
                                <span>Modifié: ${draft.lastModified}</span>
                            </div>
                            <div class="flex items-center text-sm text-gray-500">
                                <i class="fas fa-percentage mr-2 w-4"></i>
                                <span>Progression: ${draft.progress}%</span>
                            </div>
                        </div>
                        
                        <div class="w-full bg-gray-200 rounded-full h-2 mb-4">
                            <div class="bg-${progressColor}-600 h-2 rounded-full" style="width: ${draft.progress}%"></div>
                        </div>
                        
                        <div class="flex items-center justify-between">
                            ${draft.status === 'Prêt' && canEditPermission ? 
                                `<button onclick="finalizeDraft(${draft.id})" class="flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                    <i class="fas fa-check mr-2"></i>Finaliser
                                </button>` :
                                (canEditPermission ? `<button onclick="editDraft(${draft.id})" class="flex items-center px-3 py-2 bg-yellow-600 text-white text-sm font-medium rounded-md hover:bg-yellow-700">
                                    <i class="fas fa-edit mr-2"></i>Continuer
                                </button>` : '')
                            }
                            <button onclick="previewDraft(${draft.id})" class="flex items-center px-3 py-2 border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                                <i class="fas fa-eye mr-2"></i>Aperçu
                            </button>
                        </div>
                    </div>
                `;
            }).join('');
        }

        // Fonction pour mettre à jour les statistiques
        function updateStats() {
            const total = draftsData.length;
            const inProgress = draftsData.filter(d => d.status === 'En cours').length;
            const ready = draftsData.filter(d => d.status === 'Prêt').length;
            const recent = draftsData.filter(d => {
                const draftDate = new Date(d.lastModified.split(' ')[0].split('/').reverse().join('-'));
                const weekAgo = new Date();
                weekAgo.setDate(weekAgo.getDate() - 7);
                return draftDate >= weekAgo;
            }).length;

            document.querySelector('.grid.grid-cols-1.md\\:grid-cols-4 .bg-white:nth-child(1) .text-2xl').textContent = total;
            document.querySelector('.grid.grid-cols-1.md\\:grid-cols-4 .bg-white:nth-child(2) .text-2xl').textContent = inProgress;
            document.querySelector('.grid.grid-cols-1.md\\:grid-cols-4 .bg-white:nth-child(3) .text-2xl').textContent = ready;
            document.querySelector('.grid.grid-cols-1.md\\:grid-cols-4 .bg-white:nth-child(4) .text-2xl').textContent = recent;
        }

        // Système de notifications
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `fixed top-4 right-4 px-4 py-2 rounded-md text-white text-sm font-medium z-50 ${
                type === 'success' ? 'bg-green-600' : 
                type === 'error' ? 'bg-red-600' : 
                type === 'info' ? 'bg-blue-600' : 'bg-gray-600'
            }`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'} mr-2"></i>
                    ${message}
                </div>
            `;
            
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }

        // Initialisation
        document.addEventListener('DOMContentLoaded', function() {
            // Event listeners
            document.getElementById('searchInput').addEventListener('input', searchDrafts);
            document.getElementById('sortSelect').addEventListener('change', sortDrafts);
            
            // Raccourcis clavier
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
                if (e.ctrlKey && e.key === 'f') {
                    e.preventDefault();
                    document.getElementById('searchInput').focus();
                }
            });
        });
    </script>
</body>
</html>

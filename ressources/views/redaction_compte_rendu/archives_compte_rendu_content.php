<?php
// Extract variables from GLOBALS with defaults
$archives = $GLOBALS['archives'] ?? [];
$stats = $GLOBALS['stats'] ?? [];
$currentPage = $GLOBALS['currentPage'] ?? 1;
$totalPages = $GLOBALS['totalPages'] ?? 1;
$search = $GLOBALS['search'] ?? null;
$year = $GLOBALS['year'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives | Mr. Diarra</title>
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
        .archive-card {
            transition: all 0.3s ease;
        }
        .archive-card:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }
        .search-input:focus {
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.1);
        }
        .timeline-item {
            position: relative;
            padding-left: 2rem;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: 0.5rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, #e5e7eb, transparent);
        }
        .timeline-item:last-child::before {
            display: none;
        }
        .timeline-dot {
            position: absolute;
            left: 0.25rem;
            top: 0.5rem;
            width: 0.75rem;
            height: 0.75rem;
            border-radius: 50%;
            z-index: 1;
        }
        .filter-badge {
            transition: all 0.2s ease;
        }
        .filter-badge.active {
            background-color: #f59e0b;
            color: white;
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Main content area -->
            <div class="flex-1 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto p-6">
                    <!-- Header Section -->
                    <div class="mb-8">
                        <div class="bg-white rounded-lg shadow-sm p-6">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between">
                                <div class="mb-4 lg:mb-0">
                                    <h2 class="text-2xl font-bold text-gray-900 mb-2">Archives des Comptes Rendus</h2>
                                    <p class="text-gray-600">Accédez à l'historique complet de vos évaluations</p>
                                </div>
                                <div class="flex flex-col sm:flex-row items-center space-y-2 sm:space-y-0 sm:space-x-4">
                                    <div class="relative">
                                        <input type="text" id="searchInput" placeholder="Rechercher dans les archives..." 
                                               class="search-input pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 w-64"
                                               value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                                    </div>
                                    <button onclick="exportArchives()" class="flex items-center px-3 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700">
                                        <i class="fas fa-download mr-2"></i>Exporter
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="mb-6">
                        <div class="bg-white rounded-lg shadow-sm p-4">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="text-sm font-medium text-gray-700">Filtrer par:</span>
                                <button onclick="filterArchives('all')" class="filter-badge <?php echo !isset($year) ? 'active' : ''; ?> px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">
                                    Tous
                                </button>
                                <?php foreach ($stats['par_annee'] ?? [] as $annee): ?>
                                <button onclick="filterArchives('<?php echo $annee['annee']; ?>')" class="filter-badge <?php echo ($year == $annee['annee']) ? 'active' : ''; ?> px-3 py-1 text-sm rounded-full bg-gray-100 text-gray-700 hover:bg-gray-200">
                                    <?php echo $annee['annee']; ?>
                                </button>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Summary -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 mr-4">
                                    <i class="fas fa-archive text-blue-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Total Archives</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['total'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 mr-4">
                                    <i class="fas fa-calendar-week text-green-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Cette Semaine</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['cette_semaine'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 mr-4">
                                    <i class="fas fa-calendar-alt text-yellow-600 text-xl"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-600">Ce Mois</p>
                                    <p class="text-2xl font-bold text-gray-900"><?php echo $stats['ce_mois'] ?? 0; ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Archives List -->
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h3 class="text-lg font-medium text-gray-900">Liste des Archives</h3>
                                            </div>
                        <div class="divide-y divide-gray-200">
                            <?php if (empty($archives)): ?>
                            <div class="px-6 py-12 text-center">
                                <i class="fas fa-archive text-gray-400 text-4xl mb-4"></i>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Aucune archive trouvée</h3>
                                <p class="text-gray-600">Aucun compte rendu n'a été archivé pour le moment.</p>
                                                </div>
                            <?php else: ?>
                            <?php foreach ($archives as $archive): ?>
                            <div class="archive-card p-6 hover:bg-gray-50">
                                            <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <div class="flex items-center space-x-3 mb-2">
                                            <h4 class="text-lg font-semibold text-gray-900">
                                                <?php echo htmlspecialchars($archive['nom_CR']); ?>
                                            </h4>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                <i class="fas fa-check mr-1"></i>Finalisé
                                            </span>
                                        </div>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm text-gray-600">
                                            <div>
                                                <span class="font-medium">Étudiant:</span>
                                                <?php echo htmlspecialchars($archive['prenom_etu'] . ' ' . $archive['nom_etu']); ?>
                                            </div>
                                            <div>
                                                <span class="font-medium">Email:</span>
                                                <?php echo htmlspecialchars($archive['email_etu']); ?>
                                            </div>
                                            <div>
                                                <span class="font-medium">Date:</span>
                                                <?php echo date('d/m/Y H:i', strtotime($archive['date_CR'])); ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2 ml-4">
                                        
                                        <button onclick="window.location.href='/compte-rendu/archives/telecharger/<?php echo $archive['id_hashed']; ?>'" class="flex items-center px-3 py-1 text-sm text-green-600 hover:text-green-800">
                                            <i class="fas fa-download mr-1"></i>PDF
                                        </button>
                                       
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            <?php endif; ?>
                                </div>
                            </div>

                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                    <div class="mt-6 flex items-center justify-between">
                        <div class="text-sm text-gray-700">
                            Page <?php echo $currentPage; ?> sur <?php echo $totalPages; ?>
                                </div>
                        <div class="flex space-x-2">
                            <?php if ($currentPage > 1): ?>
                            <a href="/compte-rendu/archives?page=<?php echo $currentPage - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $year ? '&year=' . $year : ''; ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">
                                Précédent
                            </a>
                            <?php endif; ?>
                            <?php if ($currentPage < $totalPages): ?>
                            <a href="/compte-rendu/archives?page=<?php echo $currentPage + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?><?php echo $year ? '&year=' . $year : ''; ?>" class="px-3 py-2 text-sm border border-gray-300 rounded-md hover:bg-gray-50">
                                Suivant
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Archive View Modal -->
    <div id="archiveModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity modal-overlay" onclick="closeArchiveModal()"></div>
            
            <div class="inline-block w-full max-w-4xl p-0 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-archive text-yellow-600 mr-2"></i>
                        Détails de l'Archive
                    </h3>
                    <div class="flex items-center space-x-2">
                        <button onclick="printArchive()" class="px-3 py-1 text-sm bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-print mr-1"></i>Imprimer
                        </button>
                        <button onclick="closeArchiveModal()" class="text-gray-400 hover:text-gray-600">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <div class="px-8 py-6 max-h-96 overflow-y-auto">
                    <div id="archiveContent" class="archive-content">
                        <!-- Archive content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentFilter = '<?php echo $year ?? "all"; ?>';
        let currentSearch = '<?php echo htmlspecialchars($search ?? ""); ?>';

        // Fonctions de filtrage et recherche
        function filterArchives(filter) {
            currentFilter = filter;
            const url = new URL(window.location.origin + '/compte-rendu/archives');
            if (filter !== 'all') {
                url.searchParams.set('year', filter);
            }
            if (currentSearch) {
                url.searchParams.set('search', currentSearch);
            }
            window.location.href = url.toString();
        }

        function searchArchives() {
            const searchTerm = document.getElementById('searchInput').value;
            const url = new URL(window.location.origin + '/compte-rendu/archives');
            if (searchTerm.trim()) {
                url.searchParams.set('search', searchTerm);
            }
            if (currentFilter !== 'all') {
                url.searchParams.set('year', currentFilter);
            }
            window.location.href = url.toString();
        }

        // Fonctions d'action
        function viewArchive(id) {
            // Charger les détails de l'archive via AJAX
            fetch(`/compte-rendu/archives/view/${id}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('archiveContent').innerHTML = html;
                    document.getElementById('archiveModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
                })
                .catch(error => {
                    console.error('Erreur lors du chargement de l\'archive:', error);
                    alert('Erreur lors du chargement de l\'archive');
                });
        }

        function closeArchiveModal() {
            document.getElementById('archiveModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
        }

        function deleteArchive(id) {
            if (confirm('Êtes-vous sûr de vouloir supprimer cette archive ? Cette action est irréversible.')) {
                const formData = new FormData();
                formData.append('id_CR', id);
                
                fetch('/compte-rendu/archives/delete', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Archive supprimée avec succès');
                        location.reload();
                    } else {
                        alert('Erreur lors de la suppression: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la suppression');
                });
            }
        }

        function exportArchives() {
            const url = new URL(window.location.origin + '/compte-rendu/archives/export');
            if (currentSearch) url.searchParams.set('search', currentSearch);
            if (currentFilter !== 'all') url.searchParams.set('year', currentFilter);
            
            window.location.href = url.toString();
        }

        function printArchive() {
            const content = document.getElementById('archiveContent').innerHTML;
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Archive - Compte Rendu</title>
                    <style>
                        body { font-family: 'Times New Roman', serif; line-height: 1.6; margin: 40px; }
                        h1, h2, h3 { color: #333; }
                        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
                        .border-b-2 { border-bottom: 2px solid #ccc; padding-bottom: 12px; margin-bottom: 16px; }
                        .mb-8 { margin-bottom: 32px; }
                        .p-4 { padding: 16px; }
                        .rounded-lg { border-radius: 8px; }
                        .text-center { text-align: center; }
                        .font-bold { font-weight: bold; }
                    </style>
                </head>
                <body>${content}</body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Recherche en temps réel
            const searchInput = document.getElementById('searchInput');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    searchArchives();
                }, 500);
            });

            // Gestion du menu mobile
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0 > .flex.flex-col.w-64');

            if (mobileMenuButton && sidebar) {
                mobileMenuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                    sidebar.classList.toggle('absolute');
                    sidebar.classList.toggle('z-20');
                });
            }
        });
    </script>
</body>
</html>
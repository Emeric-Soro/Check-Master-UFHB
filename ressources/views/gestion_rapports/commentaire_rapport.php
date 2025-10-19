<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Rendu des Rapports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body>
    <div class="container mx-auto px-4 py-8">
        <!-- En-tête avec statistiques -->
        <?php if (isset($statistiquesCompteRendu)): ?>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-primary">
                        <i class="fas fa-file-alt text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm">Total Rapports</h3>
                        <p class="text-2xl font-semibold text-gray-700"><?= $statistiquesCompteRendu['total'] ?? 0 ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-accent">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm">Cette semaine</h3>
                        <p class="text-2xl font-semibold text-gray-700"><?= $statistiquesCompteRendu['semaine'] ?? 0 ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                        <i class="fas fa-calendar-alt text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-gray-500 text-sm">Ce mois</h3>
                        <p class="text-2xl font-semibold text-gray-700"><?= $statistiquesCompteRendu['mois'] ?? 0 ?></p>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Liste des rapports avec commentaires -->
        <div class="bg-white rounded-lg shadow-md">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800">Liste des Rapports</h2>
            </div>

            <!-- Filtres -->
            <div class="p-4 border-b border-gray-200 bg-gray-50">
                <form method="GET" class="flex flex-wrap gap-4">
                    <input type="hidden" name="page" value="gestion_rapports">
                    <input type="hidden" name="action" value="commentaire_rapport">

                    <select name="statut"
                        class="px-4 py-2  rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Tous les statuts</option>
                        <option value="en_attente"
                            <?= (isset($_GET['statut']) && $_GET['statut'] === 'en_attente') ? 'selected' : '' ?>>En
                            attente</option>
                        <option value="en_cours"
                            <?= (isset($_GET['statut']) && $_GET['statut'] === 'en_cours') ? 'selected' : '' ?>>En cours
                        </option>
                        <option value="valider"
                            <?= (isset($_GET['statut']) && $_GET['statut'] === 'valider') ? 'selected' : '' ?>>Validé
                        </option>
                        <option value="rejeter"
                            <?= (isset($_GET['statut']) && $_GET['statut'] === 'rejeter') ? 'selected' : '' ?>>Rejeté
                        </option>
                    </select>

                    <input type="text" name="search" placeholder="Rechercher un rapport..."
                        value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                        class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">

                    <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700">
                        <i class="fas fa-search mr-2"></i>Filtrer
                    </button>

                    <?php if (!empty($_GET['statut']) || !empty($_GET['search'])): ?>
                    <a href="?page=gestion_rapports&action=commentaire_rapport"
                        class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-accent">
                        <i class="fas fa-times mr-2"></i>Effacer
                    </a>
                    <?php endif; ?>
                </form>
            </div>

            <!-- Liste des rapports -->
            <div class="divide-y divide-gray-200">
                <?php if (isset($rapports) && !empty($rapports)): ?>
                <?php foreach ($rapports as $rapport): ?>
                <div class="p-6 hover:bg-gray-50 transition-colors">
                    <div class="flex justify-between items-start">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900">
                                <?= htmlspecialchars($rapport['nom_rapport']) ?>
                            </h3>
                            <p class="text-sm text-gray-600 mb-2">
                                <strong>Thème:</strong> <?= htmlspecialchars($rapport['theme_rapport']) ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                Soumis le <?= date('d/m/Y à H:i', strtotime($rapport['date_rapport'])) ?>
                            </p>
                            <?php if (isset($rapport['nom_etu']) && isset($rapport['prenom_etu'])): ?>
                            <p class="text-sm text-gray-500">
                                Étudiant: <?= htmlspecialchars($rapport['prenom_etu'] . ' ' . $rapport['nom_etu']) ?>
                            </p>
                            <?php endif; ?>
                        </div>
                        <span class="px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                            <?php if ($rapport['statut_rapport'] == 'en_attente') {
                            echo 'En attente';
                        } else if ($rapport['statut_rapport'] == 'en_cours') {
                            echo 'En cours';
                        } else if ($rapport['statut_rapport'] == 'valider') {
                            echo 'Validé';
                        } else if ($rapport['statut_rapport'] == 'rejeter') {
                            echo 'Rejeté';
                        }
                    
                        ?>
                        </span>
                    </div>

                    <div class="mt-4">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">Actions :</h4>
                        <div class="flex space-x-2">
                            <button onclick="voirDetailsRapport(<?= $rapport['id_rapport'] ?>)" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm
                            transition-colors">
                                <i class="fas fa-eye mr-1"></i> Voir détails
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-file-alt text-4xl mb-4"></i>
                    <p class="text-lg font-medium mb-2">Aucun rapport trouvé</p>
                    <p class="text-sm">Aucun rapport disponible pour le moment.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Modal pour les détails des commentaires -->
    <div id="detailsModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-4xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-semibold text-gray-800">
                    <i class="fas fa-comments mr-2 text-primary"></i>
                    Commentaires des évaluateurs
                </h2>
                <button onclick="fermerModalDetails()" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <div id="modalContent">
                <!-- Le contenu sera chargé dynamiquement -->
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Animation des cartes au survol
        const cards = document.querySelectorAll('.bg-white');
        cards.forEach(card => {
            card.addEventListener('mouseenter', () => {
                card.style.transform = 'translateY(-2px)';
                card.style.transition = 'transform 0.2s ease-in-out';
            });

            card.addEventListener('mouseleave', () => {
                card.style.transform = 'translateY(0)';
            });
        });
    });

    function voirDetailsRapport(rapportId) {
        // Afficher la modal
        document.getElementById('detailsModal').classList.remove('hidden');
        document.getElementById('detailsModal').classList.add('flex');

        // Afficher un loader
        document.getElementById('modalContent').innerHTML = `
            <div class="flex justify-center items-center py-8">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
                <span class="ml-2 text-gray-600">Chargement des commentaires...</span>
            </div>
        `;

        // Charger les commentaires via AJAX
        fetch(`?page=gestion_rapports&action=get_commentaires&id=${rapportId}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('modalContent').innerHTML = html;
            })
            .catch(error => {
                console.error('Erreur:', error);
                document.getElementById('modalContent').innerHTML = `
                    <div class="text-center py-8 text-danger">
                        <i class="fas fa-exclamation-triangle text-2xl mb-2"></i>
                        <p>Erreur lors du chargement des commentaires</p>
                    </div>
                `;
            });
    }

    function fermerModalDetails() {
        document.getElementById('detailsModal').classList.add('hidden');
        document.getElementById('detailsModal').classList.remove('flex');
    }

    // Fermer la modal en cliquant à l'extérieur
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('detailsModal');
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                fermerModalDetails();
            }
        });
    });
    </script>
</body>

</html>
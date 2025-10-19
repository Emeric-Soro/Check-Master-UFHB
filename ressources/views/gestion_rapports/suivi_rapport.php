<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi des Rapports</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php
    // Connexion à la base de données
    require_once __DIR__ . '/../../../app/config/database.php';
    $pdo = Database::getConnection();
    ?>
    <style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
    }

    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }

    .stat-card {
        background: white;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .stat-card i {
        font-size: 24px;
        margin-right: 10px;
        color: #4a90e2;
    }

    .stat-card h3 {
        margin: 0;
        font-size: 14px;
        color: #666;
    }

    .stat-card p {
        margin: 5px 0 0;
        font-size: 24px;
        font-weight: bold;
        color: #333;
    }

    .main-content {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        padding: 20px;
    }

    .filters {
        display: flex;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }

    .filters select,
    .filters input {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
    }

    .filters input {
        flex: 1;
        padding-left: 35px;
    }

    .search-icon {
        position: absolute;
        left: 10px;
        top: 50%;
        transform: translateY(-50%);
        color: #999;
    }

    .report-item {
        padding: 20px 0;
        border-bottom: 1px solid #eee;
    }

    .report-item:last-child {
        border-bottom: none;
    }

    .report-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
    }

    .report-title {
        font-size: 16px;
        font-weight: bold;
        margin: 0;
    }

    .report-date {
        font-size: 14px;
        color: #666;
        margin: 5px 0 0;
    }

    .status {
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 500;
    }

    .status.en-cours {
        background: #e3f2fd;
        color: #1976d2;
    }

    .status.en-revision {
        background: #fff3e0;
        color: #f57c00;
    }

    .status.valide {
        background: #e8f5e9;
        color: #388e3c;
    }

    .status.a-corriger {
        background: #ffebee;
        color: #d32f2f;
    }

    .timeline {
        margin-left: 20px;
        padding-left: 20px;
        border-left: 2px solid #eee;
    }

    .timeline-item {
        position: relative;
        margin-bottom: 20px;
    }

    .timeline-item:last-child {
        margin-bottom: 0;
    }

    .timeline-dot {
        position: absolute;
        left: -31px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #4a90e2;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: 12px;
    }

    .timeline-content {
        margin-left: 10px;
    }

    .timeline-title {
        font-size: 14px;
        font-weight: 500;
        margin: 0;
    }

    .timeline-date {
        font-size: 12px;
        color: #666;
        margin: 2px 0 0;
    }

    .progress-bar {
        height: 4px;
        background: #eee;
        border-radius: 2px;
        margin-top: 8px;
    }

    .progress-bar-fill {
        height: 100%;
        background: #4a90e2;
        border-radius: 2px;
    }
    </style>
</head>

<body>
    <div class="bg-gray-100 min-h-screen">
        <div class="container mx-auto px-4 py-8">

            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Suivi des Rapports</h1>
                    <p class="text-gray-600 mt-2">Suivez l'avancement de vos rapports en temps réel</p>
                </div>

            </div>
            <div class="container">
                <!-- Statistiques -->
                <?php if (isset($statistiques)): ?>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-lg shadow-md p-6">
                        <div class="flex items-center">
                            <div class="p-3 rounded-full bg-blue-100 text-primary">
                                <i class="fas fa-file-alt text-2xl"></i>
                            </div>
                            <div class="ml-4">
                                <h3 class="text-gray-500 text-sm">Total</h3>
                                <p class="text-2xl font-semibold text-gray-700"><?= $statistiques['total'] ?? 0 ?></p>
                            </div>
                        </div>
                    </div>
                    <!-- Répéter pour les autres statistiques... -->
                </div>
                <?php endif; ?>

                <!-- Contenu principal -->
                <div class="main-content">
                    <h2 style="margin: 0 0 20px;">Suivi des Rapports</h2>

                    <!-- Filtres -->
                    <div class="filters">
                        <select class="border-2 border-gray-300 rounded-md p-2 outline-green-500">
                            <option value="">Tous les statuts</option>
                            <option value="en_attente">En attente</option>
                            <option value="en_cours">En cours</option>
                            <option value="rejete">Rejeté</option>
                            <option value="valide">Validé</option>
                        </select>
                        <div style="position: relative; flex: 1;">
                            <i class="fas fa-search search-icon"></i>
                            <input class="border-2 border-gray-300 rounded-md p-2 outline-green-500" type="text"
                                placeholder="Rechercher un rapport...">
                        </div>
                    </div>

                    <!-- Liste des rapports -->
                    <div class="reports-list">
                        <?php foreach ($rapports as $rapport): ?>
                        <div class="report-item">
                            <div class="report-header">
                                <div>
                                    <h3 class="report-title"><?= htmlspecialchars($rapport['nom_rapport']) ?></h3>
                                    <p class="report-date">Soumis le
                                        <?= date('d M Y', strtotime($rapport['date_rapport'])) ?></p>
                                </div>
                                <?php
                                // Statut global
                                $status_class = 'en-attente';
                                $status_label = 'En attente';
                                if ($rapport['statut_rapport'] === 'valider') {
                                    $status_class = 'valide';
                                    $status_label = 'Validé';
                                } elseif ($rapport['statut_rapport'] === 'rejeter') {
                                    $status_class = 'rejete';
                                    $status_label = 'Rejeté';
                                } elseif ($rapport['statut_rapport'] === 'en_cours') {
                                    $status_class = 'en-cours';
                                    $status_label = 'En cours';
                                }
                            ?>
                                <span class="status <?= $status_class ?>"><?= $status_label ?></span>
                            </div>

                            <div class="timeline">
                                <!-- Étape 1 : Enregistrement du rapport -->
                                <?php
                            // Vérifier si le rapport a été déposé
                            $stmt = $pdo->prepare("SELECT COUNT(*) FROM deposer WHERE num_etu = ? AND id_rapport = ?");
                            $stmt->execute([$_SESSION['num_etu'], $rapport['id_rapport']]);
                            $estDepose = $stmt->fetchColumn() > 0;
                            
                            $enregistrementColor = '#1976d2'; // Toujours complété car le rapport existe
                            $enregistrementIcon = 'fa-check';
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot" style="background: <?= $enregistrementColor ?>;">
                                        <i class="fas <?= $enregistrementIcon ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4 class="timeline-title">Enregistrement du rapport</h4>
                                        <p class="timeline-date">
                                            <?= date('d M Y - H:i', strtotime($rapport['date_rapport'])) ?></p>
                                    </div>
                                </div>

                                <!-- Étape 2 : Soumission du rapport (dépôt) -->
                                <?php
                                $soumissionColor = $estDepose ? '#1976d2' : '#9e9e9e';
                                $soumissionIcon = $estDepose ? 'fa-check' : 'fa-hourglass';
                                $soumissionDate = 'En attente';
                                
                                if ($estDepose) {
                                    $stmt = $pdo->prepare("SELECT date_depot FROM deposer WHERE num_etu = ? AND id_rapport = ?");
                                    $stmt->execute([$_SESSION['num_etu'], $rapport['id_rapport']]);
                                    $dateDepot = $stmt->fetchColumn();
                                    $soumissionDate = $dateDepot ? date('d M Y - H:i', strtotime($dateDepot)) : 'Date inconnue';
                                }
                                ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot" style="background: <?= $soumissionColor ?>;">
                                        <i class="fas <?= $soumissionIcon ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4 class="timeline-title">Soumission du rapport</h4>
                                        <p class="timeline-date">
                                            <?= $soumissionDate ?>
                                        </p>
                                        <?php if (!$estDepose): ?>
                                        <p style="margin: 5px 0 0; font-size: 13px; color: #666;">
                                            Cliquez sur "Déposer" pour soumettre votre rapport
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>


                                <!-- Étape 3 : Évaluation par la chargé de communication -->
                                <?php
                            $eval = null;
                            foreach ($rapport['decisions'] as $decision) {
                                if ($decision['lib_approb'] === 'Niveau 2') {
                                    $eval = $decision;
                                    break;
                                }
                            }
                            $evalColor = '#9e9e9e';
                            $evalIcon = 'fa-hourglass';
                            if ($eval) {
                                if ($eval['decision'] === 'approuve') {
                                    $evalColor = '#1976d2';
                                    $evalIcon = 'fa-check';
                                } elseif ($eval['decision'] === 'desapprouve') {
                                    $evalColor = '#d32f2f';
                                    $evalIcon = 'fa-times';
                                }
                            }
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot" style="background: <?= $evalColor ?>;">
                                        <i class="fas <?= $evalIcon ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4 class="timeline-title">Évaluation par la chargé de communication</h4>
                                        <p class="timeline-date">
                                            <?= $eval ? date('d M Y - H:i', strtotime($eval['date_approv'])) : 'En attente' ?>
                                        </p>
                                        <?php if ($eval): ?>
                                        <p
                                            style="margin: 5px 0 0; font-size: 13px; color: <?= $eval['decision'] === 'approuve' ? '#666' : '#d32f2f' ?>;">
                                            <?= htmlspecialchars($eval['commentaire_approv']) ?>
                                        </p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Étape 4 : Décision finale de la commission -->
                                <?php
                            $finalColor = ($eval && $eval['decision'] === 'approuve' && $rapport['statut_rapport'] === 'valider') ? '#388e3c' : '#9e9e9e';
                            $finalLabel = ($eval && $eval['decision'] === 'approuve' && $rapport['statut_rapport'] === 'valider') ? 'Validé' : 'En attente';
                            ?>
                                <div class="timeline-item">
                                    <div class="timeline-dot" style="background: <?= $finalColor ?>;">
                                        <i class="fas fa-hourglass"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h4 class="timeline-title">Décision finale de la commission</h4>
                                        <p class="timeline-date">
                                            <?= $finalLabel ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <script>
            document.addEventListener('DOMContentLoaded', function() {
                const filterSelect = document.querySelector('select');
                const searchInput = document.querySelector('input[type="text"]');
                const reportItems = document.querySelectorAll('.report-item');

                filterSelect.addEventListener('change', filterReports);
                searchInput.addEventListener('input', filterReports);

                function filterReports() {
                    const status = filterSelect.value;
                    const search = searchInput.value.toLowerCase();

                    reportItems.forEach(item => {
                        const title = item.querySelector('.report-title').textContent.toLowerCase();
                        const statusElement = item.querySelector('.status');
                        const statusText = statusElement.textContent.toLowerCase();

                        let showItem = true;

                        // Filtrage par statut
                        if (status && status !== '') {
                            const statusClass = statusElement.className;
                            let itemStatus = '';

                            if (statusClass.includes('en-attente')) itemStatus = 'en_attente';
                            else if (statusClass.includes('en-cours')) itemStatus = 'en_cours';
                            else if (statusClass.includes('rejete')) itemStatus = 'rejete';
                            else if (statusClass.includes('valide')) itemStatus = 'valide';

                            if (itemStatus !== status) {
                                showItem = false;
                            }
                        }

                        // Filtrage par recherche
                        if (showItem && search) {
                            if (!title.includes(search)) {
                                showItem = false;
                            }
                        }

                        // Afficher/masquer l'élément
                        item.style.display = showItem ? 'block' : 'none';
                    });

                    // Afficher un message si aucun résultat
                    let visibleCount = 0;
                    reportItems.forEach(item => {
                        if (item.style.display !== 'none') {
                            visibleCount++;
                        }
                    });

                    let noResultsMessage = document.getElementById('noResultsMessage');

                    if (visibleCount === 0) {
                        if (!noResultsMessage) {
                            noResultsMessage = document.createElement('div');
                            noResultsMessage.id = 'noResultsMessage';
                            noResultsMessage.className = 'text-center py-8 text-gray-500';
                            noResultsMessage.innerHTML =
                                '<i class="fas fa-search text-4xl mb-4"></i><p>Aucun rapport trouvé avec les critères sélectionnés.</p>';
                            document.querySelector('.reports-list').appendChild(noResultsMessage);
                        }
                        noResultsMessage.style.display = 'block';
                    } else {
                        if (noResultsMessage) {
                            noResultsMessage.style.display = 'none';
                        }
                    }
                }
            });
            </script>
</body>

</html>
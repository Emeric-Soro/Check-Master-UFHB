<?php
global $archives;
$archivesData = $archives ?? [];
$rapportsArchives = $archivesData['rapports_archives'] ?? [];
$statistiques = $archivesData['statistiques'] ?? [];
$filtres = $archivesData['filtres'] ?? [];

function getStatusClass($status) {
    switch ($status) {
        case 'valider': return 'bg-green-100 text-green-800 border-green-200';
        case 'rejeter': return 'bg-red-100 text-red-800 border-red-200';
        default: return 'bg-gray-100 text-gray-800 border-gray-200';
    }
}

function getStatusIcon($status) {
    switch ($status) {
        case 'valider': return 'fas fa-check-circle';
        case 'rejeter': return 'fas fa-times-circle';
        default: return 'fas fa-question-circle';
    }
}

function formatDate($date) {
    if (!$date) return 'N/A';
    return date('d/m/Y', strtotime($date));
}

function getTimeAgo($date) {
    if (!$date) return 'N/A';
    $time = time() - strtotime($date);
    if ($time < 3600) return floor($time/60) . 'min';
    if ($time < 86400) return floor($time/3600) . 'h';
    return floor($time/86400) . 'j';
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archives des Dossiers de Soutenance</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root{--blue:#0F4C75;--blue-light:#3282B8;--green:#10b981;--muted:#64748B;--bg:#F8FAFC}
        .text-blue-600,.text-indigo-600,.text-blue-500{color:var(--blue) !important}
        .bg-blue-600,.bg-indigo-600{background-color:var(--blue) !important}
        .bg-blue-100{background-color:rgba(15,76,117,0.08) !important}
        .bg-green-100{background-color:rgba(16,185,129,0.08) !important}
        .bg-red-100{background-color:rgba(15,76,117,0.08) !important}
        .text-green-600{color:var(--green)}
        .filter-section{background:linear-gradient(135deg,var(--bg) 0%, rgba(15,76,117,0.06) 100%)}
        .bg-white{background-color:#ffffff}
        .text-gray-600{color:var(--muted)}
        .text-gray-900{color:#0f1720}
        .bg-gray-50{background-color:#f8fafc}
        .border-gray-200,.border-gray-300{border-color:rgba(15,76,117,0.12)}
        .status-badge:hover{transform:scale(1.05)}
        .fade-in{animation:fadeIn .5s ease-in}
        @keyframes fadeIn{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .card-hover{transition:all .3s ease}
        .card-hover:hover{transform:translateY(-5px);box-shadow:0 10px 25px rgba(0,0,0,.15)}
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
<div class="min-h-screen">
    <div class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center py-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">
                        <i class="fas fa-archive text-blue-600 mr-3"></i>
                        Archives des Dossiers de Soutenance
                    </h1>
                    <p class="mt-2 text-gray-600">
                        Consultation des rapports validés et rejetés par la commission
                    </p>
                </div>
                <div class="flex items-center space-x-4">
                    <button onclick="exportArchives()"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-download mr-2"></i>
                        Exporter
                    </button>
                    <button onclick="printArchives()"
                            class="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors">
                        <i class="fas fa-print mr-2"></i>
                        Imprimer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <i class="fas fa-archive text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Archives</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $statistiques['total_archives'] ?? 0; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Validés</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php
                            $valides = 0;
                            if (!empty($statistiques['repartition_statuts'])) {
                                foreach ($statistiques['repartition_statuts'] as $stat) {
                                    if ($stat['statut'] === 'valider') {
                                        $valides = $stat['nombre'];
                                        break;
                                    }
                                }
                            }
                            echo $valides;
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-red-100">
                        <i class="fas fa-times-circle text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Rejetés</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php
                            $rejetes = 0;
                            if (!empty($statistiques['repartition_statuts'])) {
                                foreach ($statistiques['repartition_statuts'] as $stat) {
                                    if ($stat['statut'] === 'rejeter') {
                                        $rejetes = $stat['nombre'];
                                        break;
                                    }
                                }
                            }
                            echo $rejetes;
                            ?>
                        </p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 fade-in">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <i class="fas fa-clock text-blue-600 text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Temps Moyen</p>
                        <p class="text-2xl font-bold text-gray-900">
                            <?php echo $statistiques['temps_moyen_traitement'] ?? 0; ?>j</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="filter-section rounded-lg p-6 mb-8 fade-in">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">
                <i class="fas fa-filter text-blue-600 mr-2"></i>
                Filtres de recherche
            </h3>
            <form method="GET" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Statut</label>
                    <select name="statut"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                        <option value="">Tous les statuts</option>
                        <option value="valider"
                            <?php echo ($filtres['statut'] ?? '') === 'valider' ? 'selected' : ''; ?>>Validés
                        </option>
                        <option value="rejeter"
                            <?php echo ($filtres['statut'] ?? '') === 'rejeter' ? 'selected' : ''; ?>>Rejetés
                        </option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Année</label>
                    <select name="annee"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                        <option value="">Toutes les années</option>
                        <?php
                        if (!empty($statistiques['repartition_annees'])) {
                            foreach ($statistiques['repartition_annees'] as $annee) {
                                $selected = ($filtres['annee'] ?? '') == $annee['annee'] ? 'selected' : '';
                                echo "<option value=\"{$annee['annee']}\" {$selected}>{$annee['annee']}</option>";
                            }
                        }
                        ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enseignant</label>
                    <input type="text" name="enseignant"
                           value="<?php echo htmlspecialchars($filtres['enseignant'] ?? ''); ?>"
                           placeholder="Nom ou prénom"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Étudiant</label>
                    <input type="text" name="etudiant"
                           value="<?php echo htmlspecialchars($filtres['etudiant'] ?? ''); ?>"
                           placeholder="Nom ou prénom"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date début</label>
                    <input type="date" name="date_debut" value="<?php echo $filtres['date_debut'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date fin</label>
                    <input type="date" name="date_fin" value="<?php echo $filtres['date_fin'] ?? ''; ?>"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-600 focus:border-blue-600">
                </div>

                <div class="xl:col-span-6 flex justify-end space-x-4">
                    <button type="submit"
                            class="flex items-center bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i>
                        Rechercher
                    </button>
                    <a href="?page=archives_dossiers_soutenance"
                       class="flex items-center bg-blue-600 text-white px-6 py-2 rounded-md hover:bg-blue-700 transition-colors">
                        <i class="fas fa-times mr-2"></i>
                        Réinitialiser
                    </a>
                </div>
            </form>
        </div>

        <div class="mb-6">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-list text-blue-600 mr-2"></i>
                    Rapports archivés (<?php echo count($rapportsArchives); ?>
                    résultat<?php echo count($rapportsArchives) > 1 ? 's' : ''; ?>)
                </h3>
                <div class="flex items-center space-x-2">
                    <span class="text-sm text-gray-600">Affichage :</span>
                    <select id="displayMode" class="px-3 py-1 border border-gray-300 rounded-md text-sm">
                        <option value="cards">Cartes</option>
                        <option value="table">Tableau</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="cardsView" class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            <?php if (!empty($rapportsArchives)): ?>
                <?php foreach ($rapportsArchives as $rapport): ?>
                    <div class="bg-white rounded-lg shadow-md border border-gray-200 card-hover fade-in">
                        <div class="p-6 border-b border-gray-200">
                            <div class="flex justify-between items-start mb-4">
                                <div class="flex-1">
                                    <h4 class="text-lg font-semibold text-gray-900 mb-2">
                                        <?php echo htmlspecialchars($rapport['nom_rapport'] ?? 'Rapport #' . $rapport['id_rapport']); ?>
                                    </h4>
                                    <p class="text-sm text-gray-600 mb-2">
                                        <i class="fas fa-tag mr-1"></i>
                                        <?php echo htmlspecialchars($rapport['theme_rapport'] ?? 'Thème non spécifié'); ?>
                                    </p>
                                    <p class="text-sm text-gray-600">
                                        <i class="fas fa-calendar mr-1"></i>
                                        Promotion : <?php echo htmlspecialchars($rapport['promotion_etu'] ?? 'N/A'); ?>
                                    </p>
                                </div>
                                <div class="ml-4">
                                <span
                                        class="status-badge inline-flex items-center px-3 py-1 rounded-full text-sm font-medium border <?php echo getStatusClass($rapport['decision_validation']); ?>">
                                    <i class="<?php echo getStatusIcon($rapport['decision_validation']); ?> mr-1"></i>
                                    <?php echo ucfirst($rapport['decision_validation']); ?>
                                </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-6">
                            <div class="mb-4">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-user-graduate mr-1"></i>
                                    Étudiant
                                </h5>
                                <p class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars(($rapport['prenom_etu'] ?? '') . ' ' . ($rapport['nom_etu'] ?? '')); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($rapport['email_etu'] ?? ''); ?>
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-chalkboard-teacher mr-1"></i>
                                    Enseignant responsable
                                </h5>
                                <p class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars(($rapport['prenom_enseignant'] ?? '') . ' ' . ($rapport['nom_enseignant'] ?? '')); ?>
                                </p>
                                <p class="text-xs text-gray-500">
                                    <?php echo htmlspecialchars($rapport['email_enseignant'] ?? ''); ?>
                                </p>
                            </div>

                            <div class="mb-4">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-calendar-alt mr-1"></i>
                                    Dates importantes
                                </h5>
                                <div class="grid grid-cols-2 gap-2 text-xs">
                                    <div>
                                        <span class="text-gray-500">Dépôt :</span>
                                        <p class="text-gray-900"><?php echo formatDate($rapport['date_rapport']); ?></p>
                                    </div>
                                    <div>
                                        <span class="text-gray-500">Validation :</span>
                                        <p class="text-gray-900"><?php echo formatDate($rapport['date_validation']); ?></p>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-1">
                                    <i class="fas fa-clock mr-1"></i>
                                    Temps de traitement : <?php echo $rapport['temps_traitement'] ?? 0; ?> jours
                                </p>
                            </div>

                            <?php if (!empty($rapport['commentaire_validation'])): ?>
                                <div class="mb-4">
                                    <h5 class="text-sm font-medium text-gray-700 mb-2">
                                        <i class="fas fa-comment mr-1"></i>
                                        Commentaire de validation
                                    </h5>
                                    <p class="text-sm text-gray-700 bg-gray-50 p-3 rounded-md">
                                        <?php echo htmlspecialchars($rapport['commentaire_validation']); ?>
                                    </p>
                                </div>
                            <?php endif; ?>

                            <div class="mb-4">
                                <h5 class="text-sm font-medium text-gray-700 mb-2">
                                    <i class="fas fa-chart-bar mr-1"></i>
                                    Statistiques
                                </h5>
                                <div class="flex justify-between text-xs">
                                    <span class="text-gray-500">Évaluations :</span>
                                    <span class="text-gray-900 font-medium"><?php echo $rapport['nombre_evaluations'] ?? 0; ?></span>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-2 pt-4 border-t border-gray-200">
                                <button onclick="viewDetails(<?php echo $rapport['id_rapport']; ?>)"
                                        class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                    <i class="fas fa-eye mr-1"></i>
                                    Détails
                                </button>
                                <button onclick="downloadRapport(<?php echo $rapport['id_rapport']; ?>)"
                                        class="text-green-600 hover:text-green-800 text-sm font-medium">
                                    <i class="fas fa-download mr-1"></i>
                                    Télécharger
                                </button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-span-full">
                    <div class="text-center py-12">
                        <i class="fas fa-archive text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">Aucun rapport trouvé</h3>
                        <p class="text-gray-500">Aucun rapport ne correspond aux critères de recherche.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div id="tableView" class="hidden">
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Statut</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Rapport</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Étudiant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Enseignant</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Date validation</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Actions</th>
                    </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (!empty($rapportsArchives)): ?>
                        <?php foreach ($rapportsArchives as $rapport): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?php echo getStatusClass($rapport['decision_validation']); ?>">
                                        <i class="<?php echo getStatusIcon($rapport['decision_validation']); ?> mr-1"></i>
                                        <?php echo ucfirst($rapport['decision_validation']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($rapport['nom_rapport'] ?? 'Rapport #' . $rapport['id_rapport']); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($rapport['theme_rapport'] ?? 'Thème non spécifié'); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars(($rapport['prenom_etu'] ?? '') . ' ' . ($rapport['nom_etu'] ?? '')); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($rapport['email_etu'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">
                                        <?php echo htmlspecialchars(($rapport['prenom_enseignant'] ?? '') . ' ' . ($rapport['nom_enseignant'] ?? '')); ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php echo htmlspecialchars($rapport['email_enseignant'] ?? ''); ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo formatDate($rapport['date_validation']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button onclick="viewDetails(<?php echo $rapport['id_rapport']; ?>)"
                                            class="text-blue-600 hover:text-blue-900 mr-3">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <button onclick="downloadRapport(<?php echo $rapport['id_rapport']; ?>)"
                                            class="text-green-600 hover:text-green-900">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                <i class="fas fa-archive text-2xl mb-2"></i>
                                <p>Aucun rapport trouvé</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('displayMode').addEventListener('change', function() {
        const mode = this.value;
        const cardsView = document.getElementById('cardsView');
        const tableView = document.getElementById('tableView');
        if (mode === 'cards') {
            cardsView.classList.remove('hidden');
            tableView.classList.add('hidden');
        } else {
            cardsView.classList.add('hidden');
            tableView.classList.remove('hidden');
        }
    });

    function viewDetails(idRapport) {
        window.open(`?page=evaluations_dossiers_soutenance&detail=${idRapport}`, '_blank');
    }

    function downloadRapport(idRapport) {
        window.open(`?page=archives_dossiers_soutenance&action=download_rapport&id=${idRapport}`, '_blank');
    }

    function exportArchives() {
        const currentUrl = new URL(window.location);
        currentUrl.searchParams.set('export', '1');
        window.open(currentUrl.toString(), '_blank');
    }

    function printArchives() {
        window.print();
    }

    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.fade-in');
        cards.forEach((card, index) => {
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>
</body>
</html>
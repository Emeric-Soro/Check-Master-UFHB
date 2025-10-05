<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluations des Dossiers | Mr. Diarra</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
    .sidebar-hover:hover {
        background-color: #fef3c7;
        border-left: 4px solid #f59e0b;
    }

    .fade-in {
        animation: fadeIn 0.3s ease-in;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .stat-card {
        transition: all 0.3s ease;
    }

    .stat-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
    }

    .chart-container {
        position: relative;
        height: 300px;
    }

    .metric-value {
        font-size: 2.5rem;
        font-weight: 700;
        background: linear-gradient(135deg, #f59e0b, #d97706);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .trend-up {
        color: #10b981;
    }

    .trend-down {
        color: #ef4444;
    }

    .trend-stable {
        color: #6b7280;
    }

    .evaluation-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
    }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50">
<div class="flex h-screen overflow-hidden">
    <!-- Main content area -->
    <div class="flex-1 overflow-y-auto bg-gray-50">
        <div class="max-w-7xl mx-auto p-6">
            <!-- Header -->
            <div class="flex justify-between items-center mb-8">
                <h1 class="text-2xl font-bold text-gray-800">
                    <i class="fas fa-file-alt text-yellow-600 mr-2"></i>
                    Évaluations des Dossiers de Soutenance
                </h1>
                <div class="flex space-x-3">
                    <div class="relative">
                        <select class="appearance-none bg-white border border-gray-300 rounded-md pl-3 pr-8 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500">
                            <option>Tous les dossiers</option>
                            <option>En attente</option>
                            <option>Validés</option>
                            <option>À corriger</option>
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-gray-700">
                            <i class="fas fa-chevron-down text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (isset($detail)): ?>
                <div class="max-w-3xl mx-auto mb-8 p-6 bg-white rounded-lg shadow-lg fade-in">
                    <h2 class="text-xl font-bold mb-4 text-gray-800">
                        <i class="fas fa-file-alt text-yellow-600 mr-2"></i>
                        Détail du dossier de soutenance
                    </h2>
                    <div class="mb-4">
                        <span class="font-semibold">Étudiant :</span> <?= htmlspecialchars(($detail['rapport']['prenom_etu'] ?? '') . ' ' . ($detail['rapport']['nom_etu'] ?? '')) ?> <br>
                        <span class="font-semibold">Email :</span> <?= htmlspecialchars($detail['rapport']['email_etu'] ?? 'Non renseigné') ?> <br>
                        <span class="font-semibold">Promotion :</span> <?= htmlspecialchars($detail['rapport']['promotion_etu'] ?? 'Non renseignée') ?> <br>
                        <span class="font-semibold">Sujet :</span> <?= htmlspecialchars($detail['rapport']['theme_rapport'] ?? 'Non renseigné') ?> <br>
                        <span class="font-semibold">Date de dépôt :</span> <?= !empty($detail['rapport']['date_depot']) ? date('d/m/Y', strtotime($detail['rapport']['date_depot'])) : 'Non déposé' ?>
                    </div>
                    <div class="mb-4">
                        <span class="font-semibold">Statut actuel :</span> <?= htmlspecialchars($detail['rapport']['etape_validation'] ?? 'Non défini') ?>
                    </div>
                    <div class="mb-4">
                        <span class="font-semibold">Historique des décisions :</span>
                        <ul class="mt-2 ml-4 list-disc text-gray-700">
                            <?php foreach ($detail['decisions'] as $decision): ?>
                                <li>
                                    <span class="font-semibold"><?= htmlspecialchars($decision['decision_validation'] === 'valider' ? 'Validation' : 'Rejet') ?> :</span>
                                    <?= htmlspecialchars($decision['decision_validation']) ?>
                                    par <?= htmlspecialchars($decision['prenom_enseignant'] . ' ' . $decision['nom_enseignant']) ?>
                                    le <?= date('d/m/Y H:i', strtotime($decision['date_validation'])) ?>
                                    <?php if (!empty($decision['commentaire_validation'])): ?>
                                        <br><span class="italic text-gray-500">"<?= htmlspecialchars($decision['commentaire_validation']) ?>"</span>
                                    <?php endif; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Formulaire de décision pour la commission -->
                    <?php if (($detail['rapport']['etape_validation'] ?? '') === 'approuve_communication'): ?>
                    <div class="mt-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4">
                            <i class="fas fa-gavel text-yellow-600 mr-2"></i>
                            Décision de la Commission
                        </h3>
                        <form id="decisionForm" class="space-y-4">
                            <input type="hidden" name="id_rapport" value="<?= $detail['rapport']['id_rapport'] ?? '' ?>">

                            <div class="flex space-x-4">
                                <label class="flex items-center">
                                    <input type="radio" name="decision" value="valider" class="mr-2 text-yellow-600 focus:ring-yellow-500">
                                    <span class="text-green-700 font-medium">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Valider le rapport
                                    </span>
                                </label>
                                <label class="flex items-center">
                                    <input type="radio" name="decision" value="rejeter" class="mr-2 text-yellow-600 focus:ring-yellow-500">
                                    <span class="text-red-700 font-medium">
                                        <i class="fas fa-times-circle mr-1"></i>
                                        Demander des corrections
                                    </span>
                                </label>
                            </div>

                            <div id="commentaireSection">
                                <label for="commentaire" class="block text-sm font-medium text-gray-700 mb-2">
                                    Commentaires :
                                </label>
                                <textarea
                                    id="commentaire"
                                    name="commentaire"
                                    rows="4"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500"
                                    placeholder="Ajoutez un commentaire pour expliquer votre décision..."
                                ></textarea>
                                <p class="text-xs text-gray-500 mt-1">
                                    <span id="commentaireHint">Commentaire optionnel pour expliquer votre décision</span>
                                </p>
                            </div>

                            <div class="flex space-x-3">
                                <button
                                    type="submit"
                                    class="px-6 py-2 bg-yellow-600 text-white font-medium rounded-md hover:bg-yellow-700 focus:outline-none focus:ring-2 focus:ring-yellow-500 focus:ring-offset-2 transition-colors"
                                >
                                    <i class="fas fa-paper-plane mr-2"></i>
                                    Soumettre la décision
                                </button>
                                <button
                                    type="button"
                                    onclick="window.location.href='?page=evaluations_dossiers_soutenance'"
                                    class="px-6 py-2 bg-gray-300 text-gray-700 font-medium rounded-md hover:bg-gray-400 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors"
                                >
                                    <i class="fas fa-times mr-2"></i>
                                    Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                    <?php endif; ?>

                    <div class="flex space-x-3 mt-4">
                        <a href="?page=evaluations_dossiers_soutenance" class="px-4 py-2 bg-gray-200 text-gray-700 rounded hover:bg-gray-300">
                            <i class="fas fa-arrow-left mr-1"></i> Retour à la liste
                        </a>
                        <a href="?page=evaluations_dossiers_soutenance&fichier=<?= $detail['rapport']['id_rapport'] ?? '' ?>" target="_blank" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                            <i class="fas fa-file-pdf mr-1"></i> Lire le rapport
                        </a>
                    </div>
                </div>

                <!-- Script JavaScript pour le formulaire de décision -->
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const decisionForm = document.getElementById('decisionForm');
                    const commentaireSection = document.getElementById('commentaireSection');
                    const commentaireField = document.getElementById('commentaire');
                    const radioButtons = document.querySelectorAll('input[name="decision"]');

                    // Fonction pour afficher les notifications
                    function showNotification(message, type = 'success') {
                        // Créer la notification
                        const notification = document.createElement('div');
                        notification.className = `fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transition-all duration-300 transform translate-x-full ${
                            type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
                        }`;
                        notification.innerHTML = `
                            <div class="flex items-center">
                                <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'} mr-2"></i>
                                <span>${message}</span>
                            </div>
                        `;

                        document.body.appendChild(notification);

                        // Animer l'entrée
                        setTimeout(() => {
                            notification.classList.remove('translate-x-full');
                        }, 100);

                        // Supprimer après 3 secondes
                        setTimeout(() => {
                            notification.classList.add('translate-x-full');
                            setTimeout(() => {
                                document.body.removeChild(notification);
                            }, 300);
                        }, 3000);
                    }

                    // Afficher/masquer la section commentaire selon la décision
                    radioButtons.forEach(radio => {
                        radio.addEventListener('change', function() {
                            const commentaireField = document.getElementById('commentaire');
                            const commentaireHint = document.getElementById('commentaireHint');

                            if (this.value === 'rejeter') {
                                commentaireField.placeholder = "Détaillez les corrections à apporter au rapport...";
                                commentaireHint.textContent = "Commentaire recommandé pour expliquer les corrections demandées";
                                commentaireHint.className = "text-xs text-orange-500 mt-1";
                            } else if (this.value === 'valider') {
                                commentaireField.placeholder = "Ajoutez un commentaire pour expliquer pourquoi vous validez ce rapport...";
                                commentaireHint.textContent = "Commentaire optionnel pour expliquer votre validation";
                                commentaireHint.className = "text-xs text-gray-500 mt-1";
                            }
                        });
                    });

                    // Gestion de la soumission du formulaire
                    decisionForm.addEventListener('submit', function(e) {
                        e.preventDefault();

                        const formData = new FormData(this);
                        const decision = formData.get('decision');
                        const commentaire = formData.get('commentaire');

                        // Validation
                        if (!decision) {
                            showNotification('Veuillez sélectionner une décision.', 'error');
                            return;
                        }

                        // Le commentaire est maintenant optionnel pour toutes les décisions

                        // Confirmation
                        const action = decision === 'valider' ? 'valider' : 'rejeter';
                        if (!confirm(`Êtes-vous sûr de vouloir ${action} ce rapport ?`)) {
                            return;
                        }

                        // Ajouter l'action au FormData
                        formData.append('action', 'traiter_decision');

                        // Désactiver le bouton pendant le traitement
                        const submitButton = this.querySelector('button[type="submit"]');
                        const originalText = submitButton.innerHTML;
                        submitButton.disabled = true;
                        submitButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Traitement...';

                        // Envoi de la requête AJAX
                        fetch('?page=evaluations_dossiers_soutenance&action=traiter_decision', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showNotification(data.message || 'Décision enregistrée avec succès !', 'success');
                                setTimeout(() => {
                                    window.location.href = '?page=evaluations_dossiers_soutenance';
                                }, 1500);
                            } else {
                                showNotification('Erreur lors de l\'enregistrement de la décision : ' + (data.message || 'Erreur inconnue'), 'error');
                                // Réactiver le bouton
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            showNotification('Erreur lors de l\'enregistrement de la décision.', 'error');
                            // Réactiver le bouton
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                        });
                    });
                });
                </script>
            <?php endif; ?>

            <!-- KPI Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card bg-white rounded-lg shadow p-6 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Dossiers à évaluer</p>
                            <p class="metric-value"><?= $stats['a_evaluer'] ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-blue-100">
                            <i class="fas fa-inbox text-blue-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-lg shadow p-6 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Dossiers validés</p>
                            <p class="metric-value"><?= $stats['valides'] ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-green-100">
                            <i class="fas fa-check-circle text-green-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

                <div class="stat-card bg-white rounded-lg shadow p-6 fade-in">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-sm font-medium text-gray-600">Dossiers à corriger</p>
                            <p class="metric-value"><?= $stats['a_corriger'] ?></p>
                        </div>
                        <div class="p-3 rounded-full bg-red-100">
                            <i class="fas fa-exclamation-circle text-red-600 text-2xl"></i>
                        </div>
                    </div>
                </div>

            
            </div>

            <!-- Evaluation Grid -->
            <div class="evaluation-grid mb-8">
                <?php if (empty($dossiers)): ?>
                <div class="col-span-full text-center py-8">
                    <i class="fas fa-inbox text-gray-400 text-4xl mb-4"></i>
                    <p class="text-gray-500">Aucun dossier à évaluer pour le moment.</p>
                </div>
                <?php else: ?>
                <?php foreach ($dossiers as $dossier): ?>
                <div class="bg-white rounded-lg shadow overflow-hidden fade-in hover:shadow-lg transition-shadow duration-300">
                    <div class="p-5">
                        <div class="flex justify-between items-start mb-3">
                            <div>
                                <h3 class="font-bold text-lg text-gray-800"><?= htmlspecialchars($dossier['nom_rapport']) ?></h3>
                                <p class="text-sm text-gray-500">Étudiant: <?= htmlspecialchars($dossier['prenom_etu'] . ' ' . $dossier['nom_etu']) ?></p>
                            </div>
                            <?php
                            $statusClass = '';
                            $statusText = '';
                            switch ($dossier['etape_validation']) {
                                case 'approuve_communication':
                                    $statusClass = 'bg-blue-100 text-blue-800';
                                    $statusText = 'Nouveau';
                                    break;
                                case 'valide':
                                    $statusClass = 'bg-green-100 text-green-800';
                                    $statusText = 'Validé';
                                    break;
                                case 'desapprouve_commission':
                                    $statusClass = 'bg-orange-100 text-orange-800';
                                    $statusText = 'À corriger';
                                    break;
                                default:
                                    $statusClass = 'bg-gray-100 text-gray-800';
                                    $statusText = 'En cours';
                            }
                            ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full <?= $statusClass ?>"><?= $statusText ?></span>
                        </div>
                        <p class="text-sm text-gray-600 mb-4"><?= htmlspecialchars($dossier['theme_rapport']) ?></p>

                        <div class="flex items-center justify-between text-sm mb-4">
                            <div>
                                <p class="font-medium text-gray-700">Date de dépôt:</p>
                                <p class="text-gray-500"><?= $dossier['date_depot'] ? date('d/m/Y', strtotime($dossier['date_depot'])) : 'Non déposé' ?></p>
                            </div>
                            <div>
                                <p class="font-medium text-gray-700">Promotion:</p>
                                <p class="text-gray-500"><?= htmlspecialchars($dossier['promotion_etu']) ?></p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <?php if ($dossier['etape_validation'] === 'valide'): ?>
                            <p class="text-sm font-medium text-gray-700 mb-1">Note:</p>
                            <div class="flex items-center">
                                <span class="text-lg font-bold text-yellow-600 mr-2">16.5/20</span>
                                <div class="flex-1 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: 82%"></div>
                                </div>
                            </div>
                            <?php elseif ($dossier['etape_validation'] === 'desapprouve_commission'): ?>
                            <p class="text-sm font-medium text-gray-700 mb-1">Retours:</p>
                            <div class="flex items-center text-sm text-orange-600">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                <span>Corrections demandées</span>
                            </div>
                            <?php else: ?>
                            <p class="text-sm font-medium text-gray-700 mb-1">Progression:</p>
                            <div class="w-full bg-gray-200 rounded-full h-2">
                                <div class="bg-yellow-600 h-2 rounded-full" style="width: 75%"></div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex space-x-2">
                            <?php if ($dossier['etape_validation'] === 'approuve_communication'): ?>
                            <a href="?page=evaluations_dossiers_soutenance&detail=<?= $dossier['id_rapport'] ?>" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-3 rounded-md text-sm font-medium transition-colors text-center">
                                <i class="fas fa-eye mr-1"></i> Évaluer
                            </a>
                            <?php elseif ($dossier['etape_validation'] === 'valide'): ?>
                            <button class="flex-1 bg-white border border-yellow-600 hover:bg-yellow-50 text-yellow-600 py-2 px-3 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-edit mr-1"></i> Modifier
                            </button>
                            <?php elseif ($dossier['etape_validation'] === 'desapprouve_commission'): ?>
                            <button class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-3 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-eye mr-1"></i> Voir retours
                            </button>
                            <?php endif; ?>
                            <button class="flex-1 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 py-2 px-3 rounded-md text-sm font-medium transition-colors">
                                <i class="fas fa-download mr-1"></i> Télécharger
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>           
        </div>
    </div>
</div>

<script>
    // Initialisation des graphiques
    document.addEventListener('DOMContentLoaded', function() {
        // Graphique de distribution des notes
        const gradesCtx = document.getElementById('gradesChart').getContext('2d');
        const gradesChart = new Chart(gradesCtx, {
            type: 'bar',
            data: {
                labels: ['0-5', '5-10', '10-12', '12-14', '14-16', '16-18', '18-20'],
                datasets: [{
                    label: '2025',
                    data: [2, 5, 8, 12, 15, 10, 3],
                    backgroundColor: '#3b82f6',
                    borderColor: '#2563eb',
                    borderWidth: 1
                }, {
                    label: '2024',
                    data: [3, 7, 10, 14, 12, 8, 2],
                    backgroundColor: '#e5e7eb',
                    borderColor: '#d1d5db',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });

        // Graphique d'évolution des statuts
        const statusCtx = document.getElementById('statusEvolutionChart').getContext('2d');
        const statusChart = new Chart(statusCtx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Fév', 'Mar', 'Avr', 'Mai'],
                datasets: [{
                    label: 'Nouveaux',
                    data: [5, 8, 12, 15, 18],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Validés',
                    data: [3, 6, 10, 14, 20],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'À corriger',
                    data: [2, 4, 5, 8, 7],
                    borderColor: '#f59e0b',
                    backgroundColor: 'rgba(245, 158, 11, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            usePointStyle: true,
                            padding: 20
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.05)'
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                },
                elements: {
                    point: {
                        radius: 4,
                        hoverRadius: 6
                    }
                }
            }
        });

        // Animation des métriques
        const metrics = document.querySelectorAll('.metric-value');
        metrics.forEach((metric, index) => {
            const finalValue = metric.textContent;
            metric.textContent = '0';

            setTimeout(() => {
                const increment = finalValue.includes('/') ? 0.5 : 1;
                const target = parseFloat(finalValue);
                let current = 0;

                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        current = target;
                        clearInterval(timer);
                    }

                    if (finalValue.includes('/')) {
                        metric.textContent = current.toFixed(1) + '/20';
                    } else {
                        metric.textContent = Math.round(current);
                    }
                }, 50);
            }, index * 200);
        });

        // Animation d'entrée pour les éléments
        const elements = document.querySelectorAll('.fade-in');
        elements.forEach((el, index) => {
            setTimeout(() => {
                el.style.opacity = '1';
                el.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });

    // Fonction de rafraîchissement
    function refreshCharts() {
        // Simuler le rafraîchissement
        console.log("Actualisation des graphiques...");
        // En réalité, ici vous feriez une requête AJAX pour récupérer les nouvelles données
    }
</script>
</body>

</html>
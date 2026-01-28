<?php
// Initialiser les statistiques par défaut
$stats = [
    'a_evaluer' => 0,
    'valides' => 0,
    'a_corriger' => 0,
    'total' => 0
];
?>

<div class="container p-lg">
    <!-- Header -->
    <div class="page-header mb-lg">
        <h1><i class="fas fa-file-alt"></i> Évaluations des Dossiers de Soutenance</h1>
        <div class="header-actions">
            <select class="form-select">
                <option>Tous les dossiers</option>
                <option>En attente</option>
                <option>Validés</option>
                <option>À corriger</option>
            </select>
        </div>
    </div>

    <?php if (isset($detail)): ?>
        <div class="card mb-lg">
            <div class="card-header">
                <h2 class="card-title"><i class="fas fa-file-alt"></i> Détail du dossier de soutenance</h2>
            </div>
            <div class="card-content">
                <div class="info-grid mb-md">
                    <div class="info-item">
                        <span class="text-muted">Étudiant :</span>
                        <strong><?= htmlspecialchars(($detail['rapport']['prenom_etu'] ?? '') . ' ' . ($detail['rapport']['nom_etu'] ?? '')) ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Email :</span>
                        <strong><?= htmlspecialchars($detail['rapport']['email_etu'] ?? 'Non renseigné') ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Promotion :</span>
                        <strong><?= htmlspecialchars($detail['rapport']['promotion_etu'] ?? 'Non renseignée') ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Sujet :</span>
                        <strong><?= htmlspecialchars($detail['rapport']['theme_rapport'] ?? 'Non renseigné') ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Date de dépôt :</span>
                        <strong><?= !empty($detail['rapport']['date_depot']) ? date('d/m/Y', strtotime($detail['rapport']['date_depot'])) : 'Non déposé' ?></strong>
                    </div>
                    <div class="info-item">
                        <span class="text-muted">Statut actuel :</span>
                        <strong><?= htmlspecialchars($detail['rapport']['etape_validation'] ?? 'Non défini') ?></strong>
                    </div>
                </div>

                <div class="mb-md">
                    <h3 class="text-md font-semibold mb-sm">Historique des décisions</h3>
                    <ul class="list-disc ml-lg text-muted">
                        <?php foreach ($detail['decisions'] as $decision): ?>
                            <li class="mb-xs">
                                <strong><?= htmlspecialchars($decision['decision_validation'] === 'valider' ? 'Validation' : 'Rejet') ?>:</strong>
                                <?= htmlspecialchars($decision['decision_validation']) ?>
                                par <?= htmlspecialchars($decision['prenom_enseignant'] . ' ' . $decision['nom_enseignant']) ?>
                                le <?= date('d/m/Y H:i', strtotime($decision['date_validation'])) ?>
                                <?php if (!empty($decision['commentaire_validation'])): ?>
                                    <br><em class="text-muted">"<?= htmlspecialchars($decision['commentaire_validation']) ?>"</em>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <!-- Formulaire de décision pour la commission -->
                <?php if (($detail['rapport']['etape_validation'] ?? '') === 'approuve_communication' && canEdit()): ?>
                    <div class="alert alert-warning mb-md">
                        <h3 class="text-lg font-semibold mb-md">
                            <i class="fas fa-gavel"></i> Décision de la Commission
                        </h3>
                        <form id="decisionForm" class="space-y-md">
                            <input type="hidden" name="id_rapport" value="<?= $detail['rapport']['id_rapport'] ?? '' ?>">

                            <div class="form-group">
                                <label class="radio-label">
                                    <input type="radio" name="decision" value="valider" class="radio-input">
                                    <span class="text-success"><i class="fas fa-check-circle"></i> Valider le rapport</span>
                                </label>
                                <label class="radio-label">
                                    <input type="radio" name="decision" value="rejeter" class="radio-input">
                                    <span class="text-danger"><i class="fas fa-times-circle"></i> Demander des corrections</span>
                                </label>
                            </div>

                            <div id="commentaireSection" class="form-group">
                                <label for="commentaire" class="form-label">Commentaires :</label>
                                <textarea id="commentaire" name="commentaire" rows="4" class="form-textarea" 
                                    placeholder="Ajoutez un commentaire pour expliquer votre décision..."></textarea>
                                <p class="text-xs text-muted mt-xs">
                                    <span id="commentaireHint">Commentaire optionnel pour expliquer votre décision</span>
                                </p>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Soumettre la décision
                                </button>
                                <button type="button" onclick="window.location.href='?page=evaluations_dossiers_soutenance'" 
                                    class="btn btn-secondary">
                                    <i class="fas fa-times"></i> Annuler
                                </button>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>

                <div class="btn-group">
                    <a href="?page=evaluations_dossiers_soutenance" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Retour à la liste
                    </a>
                    <a href="?page=evaluations_dossiers_soutenance&fichier=<?= $detail['rapport']['id_rapport'] ?? '' ?>" 
                        target="_blank" class="btn btn-primary">
                        <i class="fas fa-file-pdf"></i> Lire le rapport
                    </a>
                </div>
            </div>
        </div>

        <!-- Script JavaScript pour le formulaire de décision -->
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const decisionForm = document.getElementById('decisionForm');
                const commentaireSection = document.getElementById('commentaireSection');
                const commentaireField = document.getElementById('commentaire');
                const radioButtons = document.querySelectorAll('input[name="decision"]');

                // Fonction pour afficher les notifications
                function showNotification(message, type = 'success') {
                    const notification = document.createElement('div');
                    notification.className = `alert alert-${type} fixed top-4 right-4 z-50 animate-slide-in`;
                    notification.innerHTML = `
                        <i class="fas ${type === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i>
                        <span>${message}</span>
                    `;
                    document.body.appendChild(notification);
                    setTimeout(() => notification.remove(), 3000);
                }

                // Afficher/masquer la section commentaire selon la décision
                radioButtons.forEach(radio => {
                    radio.addEventListener('change', function () {
                        const commentaireField = document.getElementById('commentaire');
                        const commentaireHint = document.getElementById('commentaireHint');

                        if (this.value === 'rejeter') {
                            commentaireField.placeholder = "Détaillez les corrections à apporter au rapport...";
                            commentaireHint.textContent = "Commentaire recommandé pour expliquer les corrections demandées";
                            commentaireHint.className = "text-xs text-warning mt-xs";
                        } else if (this.value === 'valider') {
                            commentaireField.placeholder = "Ajoutez un commentaire pour expliquer pourquoi vous validez ce rapport...";
                            commentaireHint.textContent = "Commentaire optionnel pour expliquer votre validation";
                            commentaireHint.className = "text-xs text-muted mt-xs";
                        }
                    });
                });

                // Gestion de la soumission du formulaire
                decisionForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const decision = formData.get('decision');
                    const commentaire = formData.get('commentaire');

                    // Validation
                    if (!decision) {
                        showNotification('Veuillez sélectionner une décision.', 'error');
                        return;
                    }

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
                    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Traitement...';

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
                                submitButton.disabled = false;
                                submitButton.innerHTML = originalText;
                            }
                        })
                        .catch(error => {
                            console.error('Erreur:', error);
                            showNotification('Erreur lors de l\'enregistrement de la décision.', 'error');
                            submitButton.disabled = false;
                            submitButton.innerHTML = originalText;
                        });
                });
            });
        </script>
    <?php endif; ?>

    <!-- KPI Cards -->
    <div class="stats-grid mb-lg">
        <div class="stats-card">
            <div class="stats-icon bg-info">
                <i class="fas fa-inbox"></i>
            </div>
            <div class="stats-content">
                <div class="stats-label">Dossiers à évaluer</div>
                <div class="stats-value"><?= $stats['a_evaluer'] ?></div>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon bg-success">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stats-content">
                <div class="stats-label">Dossiers validés</div>
                <div class="stats-value"><?= $stats['valides'] ?></div>
            </div>
        </div>

        <div class="stats-card">
            <div class="stats-icon bg-danger">
                <i class="fas fa-exclamation-circle"></i>
            </div>
            <div class="stats-content">
                <div class="stats-label">Dossiers à corriger</div>
                <div class="stats-value"><?= $stats['a_corriger'] ?></div>
            </div>
        </div>
    </div>

    <!-- Evaluation Grid -->
    <div class="grid-auto mb-lg">
        <?php if (empty($dossiers)): ?>
            <div class="empty-state">
                <i class="fas fa-inbox"></i>
                <p>Aucun dossier à évaluer pour le moment.</p>
            </div>
        <?php else: ?>
            <?php foreach ($dossiers as $dossier): ?>
                <div class="card card-hover">
                    <div class="card-content">
                        <div class="flex justify-between items-start mb-sm">
                            <div>
                                <h3 class="font-bold text-lg"><?= htmlspecialchars($dossier['nom_rapport']) ?></h3>
                                <p class="text-sm text-muted">
                                    Étudiant: <?= htmlspecialchars($dossier['prenom_etu'] . ' ' . $dossier['nom_etu']) ?>
                                </p>
                            </div>
                            <?php
                            $statusMapping = [
                                'approuve_communication' => ['class' => 'info', 'text' => 'Nouveau'],
                                'valide' => ['class' => 'success', 'text' => 'Validé'],
                                'desapprouve_commission' => ['class' => 'warning', 'text' => 'À corriger'],
                            ];
                            $status = $statusMapping[$dossier['etape_validation']] ?? ['class' => 'muted', 'text' => 'En cours'];
                            ?>
                            <span class="badge badge-<?= $status['class'] ?>"><?= $status['text'] ?></span>
                        </div>

                        <p class="text-sm text-muted mb-md"><?= htmlspecialchars($dossier['theme_rapport']) ?></p>

                        <div class="info-grid mb-md">
                            <div class="info-item">
                                <span class="text-muted">Date de dépôt:</span>
                                <strong><?= $dossier['date_depot'] ? date('d/m/Y', strtotime($dossier['date_depot'])) : 'Non déposé' ?></strong>
                            </div>
                            <div class="info-item">
                                <span class="text-muted">Promotion:</span>
                                <strong><?= htmlspecialchars($dossier['promotion_etu']) ?></strong>
                            </div>
                        </div>

                        <div class="mb-md">
                            <?php if ($dossier['etape_validation'] === 'valide'): ?>
                                <p class="text-sm font-medium mb-xs">Note:</p>
                                <div class="flex items-center">
                                    <span class="text-lg font-bold text-success mr-sm">16.5/20</span>
                                    <div class="progress flex-1">
                                        <div class="progress-bar bg-success" style="width: 82%"></div>
                                    </div>
                                </div>
                            <?php elseif ($dossier['etape_validation'] === 'desapprouve_commission'): ?>
                                <p class="text-sm font-medium mb-xs">Retours:</p>
                                <div class="text-sm text-warning">
                                    <i class="fas fa-exclamation-circle"></i> Corrections demandées
                                </div>
                            <?php else: ?>
                                <p class="text-sm font-medium mb-xs">Progression:</p>
                                <div class="progress">
                                    <div class="progress-bar" style="width: 75%"></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="btn-group">
                            <?php if ($dossier['etape_validation'] === 'approuve_communication' && canEdit()): ?>
                                <a href="?page=evaluations_dossiers_soutenance&detail=<?= $dossier['id_rapport'] ?>" 
                                    class="btn btn-primary flex-1">
                                    <i class="fas fa-eye"></i> Évaluer
                                </a>
                            <?php elseif ($dossier['etape_validation'] === 'valide' && canEdit()): ?>
                                <button class="btn btn-outline flex-1">
                                    <i class="fas fa-edit"></i> Modifier
                                </button>
                            <?php elseif ($dossier['etape_validation'] === 'desapprouve_commission'): ?>
                                <button class="btn btn-primary flex-1">
                                    <i class="fas fa-eye"></i> Voir retours
                                </button>
                            <?php endif; ?>
                            <button class="btn btn-secondary flex-1">
                                <i class="fas fa-download"></i> Télécharger
                            </button>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
    // Initialisation des graphiques
    document.addEventListener('DOMContentLoaded', function () {
        // Graphique de distribution des notes
        const gradesCtx = document.getElementById('gradesChart');
        if (gradesCtx) {
            const gradesChart = new Chart(gradesCtx.getContext('2d'), {
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
        }

        // Graphique d'évolution des statuts
        const statusCtx = document.getElementById('statusEvolutionChart');
        if (statusCtx) {
            const statusChart = new Chart(statusCtx.getContext('2d'), {
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
        }

        // Animation des métriques
        const metrics = document.querySelectorAll('.stats-value');
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
    });
</script>

</html>
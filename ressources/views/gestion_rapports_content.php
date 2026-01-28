<?php
// Initialiser les variables globales
$infosDepot = isset($GLOBALS['infosDepot']) ? $GLOBALS['infosDepot'] : [];

// Vérifier si l'étudiant a une candidature validée
$candidature_validee = false;
$message_candidature = '';

if (isset($_SESSION['num_etu'])) {
    // Récupérer le statut de candidature de l'étudiant
    $candidatures_etudiant = isset($GLOBALS['candidatures_etudiant']) ? $GLOBALS['candidatures_etudiant'] : [];

    foreach ($candidatures_etudiant as $candidature) {
        if ($candidature['statut_candidature'] === 'Validée') {
            $candidature_validee = true;
            break;
        }
    }

    if (!$candidature_validee) {
        $message_candidature = "Vous devez avoir une candidature validée pour accéder aux fonctionnalités de gestion des rapports.";
    }
}
?>

<div id="notificationContainer" class="notification-container">
    <!-- Les notifications seront ajoutées ici dynamiquement -->
</div>

<div id="deleteModal" class="modal" style="display: none;">
    <div class="modal-overlay" onclick="fermerModalSuppression()"></div>
    <div class="modal-content">
        <div class="modal-header">
            <div class="modal-icon danger">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <div>
                <h3 class="modal-title">Confirmer la suppression</h3>
                <p class="modal-subtitle">Cette action est irréversible</p>
            </div>
        </div>

        <div class="modal-body">
            <p>
                Êtes-vous sûr de vouloir supprimer le rapport
                <span id="rapportNom" class="text-emphasis"></span> ?
            </p>
            <p class="text-muted">
                Cette action ne peut pas être annulée et supprimera définitivement le rapport et toutes ses données
                associées.
            </p>
        </div>

        <form method="POST" action="?page=gestion_rapports">
            <input type="hidden" name="action" value="supprimer_rapport">
            <input type="hidden" name="rapport_id" id="rapportIdToDelete">
            <div class="modal-footer">
                <button type="button" onclick="fermerModalSuppression()" class="btn btn-ghost">Annuler</button>
                <button type="submit" class="btn btn-danger">
                    <i class="fas fa-trash"></i>Supprimer définitivement
                </button>
            </div>
        </form>
    </div>
</div>

<section class="stats-grid">
    <div class="stat-card primary">
        <div class="stat-card-icon">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
            </svg>
        </div>
        <h3 class="stat-card-title">Créer un Rapport</h3>
        <p class="stat-card-description">Rédigez et soumettez votre rapport de master directement via notre plateforme sécurisée.</p>
        <div class="stat-card-footer">
            <?php if (canCreate()): ?>
                <?php if ($candidature_validee): ?>
                    <a href="?page=gestion_rapports&action=creer_rapport" class="btn btn-primary">Commencer</a>
                <?php else: ?>
                    <button onclick="showCandidatureRequiredMessage()" class="btn btn-ghost" disabled>Commencer</button>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        <div class="stat-card-meta">
            <span class="text-muted">Facile à utiliser</span>
            <span class="badge badge-primary">Étape 1</span>
        </div>
    </div>

    <div class="stat-card success">
        <div class="stat-card-icon">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
            </svg>
        </div>
        <h3 class="stat-card-title">Suivre l'Avancée</h3>
        <p class="stat-card-description">Surveillez l'état de votre soumission en temps réel à chaque étape du processus de validation.</p>
        <div class="stat-card-footer">
            <?php if ($candidature_validee): ?>
                <a href="?page=gestion_rapports&action=suivi_rapport" class="btn btn-success">Consulter</a>
            <?php else: ?>
                <button onclick="showCandidatureRequiredMessage()" class="btn btn-ghost" disabled>Consulter</button>
            <?php endif; ?>
        </div>
        <div class="stat-card-meta">
            <span class="text-muted">En temps réel</span>
            <span class="badge badge-success">Étape 2</span>
        </div>
    </div>

    <div class="stat-card warning">
        <div class="stat-card-icon">
            <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
            </svg>
        </div>
        <h3 class="stat-card-title">Consulter les commentaires</h3>
        <p class="stat-card-description">Accédez aux retours détaillés des évaluateurs pour améliorer votre travail académique.</p>
        <div class="stat-card-footer">
            <?php if ($candidature_validee): ?>
                <a href="?page=gestion_rapports&action=commentaire_rapport" class="btn btn-warning">Voir les retours</a>
            <?php else: ?>
                <button onclick="showCandidatureRequiredMessage()" class="btn btn-ghost" disabled>Voir les retours</button>
            <?php endif; ?>
        </div>
        <div class="stat-card-meta">
            <span class="text-muted">Retours d'experts</span>
            <span class="badge badge-warning">Étape 3</span>
        </div>
    </div>
</section>

<section class="container">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Mes Rapports</h3>
            <span class="badge badge-primary">
                <?= isset($statistiquesRapports) ? ($statistiquesRapports->total_rapports ?? 0) : 0 ?>
                rapport(s)
            </span>
        </div>

        <div class="card-body">
            <?php if (isset($rapportsRecents) && !empty($rapportsRecents)): ?>
                <div class="report-list">
                    <?php foreach ($rapportsRecents as $rapport): ?>
                        <div id="rapport-<?= $rapport->id_rapport ?>" class="report-item">
                            <div class="report-info">
                                <h4 class="report-title">
                                    <?= htmlspecialchars($rapport->nom_rapport) ?>
                                </h4>
                                <p class="report-meta">
                                    <strong>Thème:</strong> <?= htmlspecialchars($rapport->theme_rapport) ?>
                                </p>
                                <p class="text-muted">
                                    Créé le <?= date('d/m/Y à H:i', strtotime($rapport->date_rapport)) ?>
                                </p>
                            </div>
                            <div class="report-actions">
                                <?php
                                $infoDepot = $infosDepot[$rapport->id_rapport] ?? ['peutDeposer' => true, 'messageDepot' => '', 'dejaDepose' => false];
                                $peutDeposer = $infoDepot['peutDeposer'];
                                $messageDepot = $infoDepot['messageDepot'];
                                $dejaDepose = $infoDepot['dejaDepose'];
                                ?>

                                <?php if ($peutDeposer): ?>
                                    <form method="POST" action="?page=gestion_rapports" style="display:inline;" id="deposerForm-<?= $rapport->id_rapport ?>">
                                        <input type="hidden" name="id_rapport" value="<?= $rapport->id_rapport ?>">
                                        <input type="hidden" name="action" value="deposer_rapport">
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="fas fa-upload"></i> Déposer
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <button disabled class="btn btn-ghost btn-sm" title="<?= htmlspecialchars($messageDepot) ?>">
                                        <i class="fas fa-upload"></i> <?= htmlspecialchars($messageDepot) ?>
                                    </button>
                                <?php endif; ?>

                                <button onclick="voirRapport(<?= $rapport->id_rapport ?>)" class="btn btn-success btn-sm" title="Voir le rapport">
                                    <i class="fa-solid fa-eye"></i> Voir
                                </button>

                                <?php if (!$dejaDepose): ?>
                                    <?php if (canDelete()): ?>
                                        <button onclick="confirmerSuppression(<?= $rapport->id_rapport ?>, '<?= htmlspecialchars(addslashes($rapport->nom_rapport)) ?>')" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i> Supprimer
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($rapportsRecents) >= 5): ?>
                    <div class="card-footer">
                        <a href="?page=gestion_rapports&action=suivi_rapport" class="btn btn-outline">
                            <i class="fas fa-list"></i> Voir tous mes rapports
                        </a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-file-alt empty-state-icon"></i>
                    <p class="empty-state-title">Aucun rapport créé pour le moment</p>
                    <?php if (canCreate()): ?>
                        <?php if ($candidature_validee): ?>
                            <a href="?page=gestion_rapports&action=creer_rapport" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Créer mon premier rapport
                            </a>
                        <?php else: ?>
                            <button onclick="showCandidatureRequiredMessage()" class="btn btn-ghost" disabled>
                                <i class="fas fa-plus"></i> Créer mon premier rapport
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="container">
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Processus de validation</h3>
        </div>
        <div class="card-body">
            <div class="timeline">
                <div class="timeline-item">
                    <div class="timeline-marker primary">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Dépôt du rapport</h4>
                        <p class="timeline-description">L'étudiant rédige et soumet son rapport dans l'application</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker primary">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Vérification initiale</h4>
                        <p class="timeline-description">vérification de son admissibilité et respect des normes formelles</p>
                    </div>
                </div>
                <div class="timeline-item">
                    <div class="timeline-marker primary">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="timeline-content">
                        <h4 class="timeline-title">Validation par la commission</h4>
                        <p class="timeline-description">Examen par le jury et autorisation de soutenance</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        <?php if (isset($_GET['message'])): ?>
            <?php if ($_GET['message'] === 'depot_ok'): ?>
                showNotification('success', 'Le rapport a bien été déposé.');
            <?php elseif ($_GET['message'] === 'depot_fail'): ?>
                showNotification('error', 'Impossible de déposer le rapport (déjà déposé ou erreur technique).');
            <?php elseif ($_GET['message'] === 'depot_en_cours'): ?>
                showNotification('warning', 'Vous ne pouvez pas déposer ce rapport car vous avez déjà un rapport en cours d\'évaluation. Attendez que votre rapport précédent soit approuvé ou rejeté.');
            <?php elseif ($_GET['message'] === 'suppression_ok'): ?>
                showNotification('success', 'Le rapport a été supprimé avec succès.');
            <?php endif; ?>
        <?php endif; ?>
    });

    function voirRapport(rapportId) {
        window.location.href = `?page=gestion_rapports&action=creer_rapport&edit=${rapportId}`;
    }

    function showNotification(type, message, title = null) {
        const notificationContainer = document.getElementById('notificationContainer');
        const notification = document.createElement('div');
        notification.className = `alert alert-${type}`;

        let iconPath = '';
        let displayTitle = title || type.charAt(0).toUpperCase() + type.slice(1);

        switch (type) {
            case 'success':
                iconPath = 'M5 13l4 4L19 7';
                break;
            case 'error':
                iconPath = 'M6 18L18 6M6 6l12 12';
                break;
            case 'info':
                iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
                break;
            case 'warning':
                iconPath = 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z';
                break;
            default:
                iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        }

        notification.innerHTML = `
            <div class="alert-icon">
                <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path>
                </svg>
            </div>
            <div class="alert-content">
                <div class="alert-title">${displayTitle}</div>
                <div class="alert-message">${message}</div>
            </div>
            <button class="alert-close">
                <svg class="icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        const closeButton = notification.querySelector('.alert-close');
        closeButton.addEventListener('click', function () {
            hideNotification(notification);
        });

        notificationContainer.appendChild(notification);

        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        setTimeout(() => {
            hideNotification(notification);
        }, 5000);

        function hideNotification(notification) {
            notification.classList.remove('show');
            notification.classList.add('hide');
            setTimeout(() => {
                if (notificationContainer.contains(notification)) {
                    notificationContainer.removeChild(notification);
                }
            }, 300);
        }
    }

    function showCandidatureRequiredMessage() {
        showNotification('error', 'Vous devez avoir une candidature validée pour accéder à cette fonctionnalité.');
    }

    function confirmerSuppression(rapportId, rapportNom) {
        document.getElementById('rapportIdToDelete').value = rapportId;
        document.getElementById('rapportNom').textContent = '"' + rapportNom + '"';
        document.getElementById('deleteModal').style.display = 'flex';
    }

    function fermerModalSuppression() {
        document.getElementById('deleteModal').style.display = 'none';
    }
</script>

</html>
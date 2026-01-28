<?php
// Récupérer les données des rapports depuis le contrôleur
$rapports = $GLOBALS['rapports'] ?? [];
$nbRapports = $GLOBALS['nbRapports'] ?? 0;
$statsRapports = $GLOBALS['statsRapports'] ?? [];

// Charger le modèle Approuver si disponible
require_once __DIR__ . '/../../app/models/Approuver.php';

// Afficher les messages de session
if (isset($_SESSION['message']) && !empty($_SESSION['message'])) {
    $message = $_SESSION['message'];
    $messageType = $_SESSION['message_type'] ?? 'info';
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);

    $alertClass = ($messageType === 'success') ? 'alert-success' : (($messageType === 'error') ? 'alert-danger' : 'alert-info');
    $iconClass = ($messageType === 'success') ? 'fa-check-circle' : (($messageType === 'error') ? 'fa-exclamation-circle' : 'fa-info-circle');
    
    echo '<div id="notification" class="alert ' . $alertClass . '" style="position: fixed; top: 1rem; right: 1rem; z-index: 1000; min-width: 300px;">';
    echo '<i class="fas ' . $iconClass . '"></i>';
    echo '<span>' . htmlspecialchars($message) . '</span>';
    echo '</div>';

    echo '<script>
        setTimeout(function() {
            const notification = document.getElementById("notification");
            if (notification) {
                notification.style.opacity = "0";
                notification.style.transform = "translateX(100%)";
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

<div class="container">
    <!-- Header Section -->
    <div class="card">
        <div class="card-header">
            <div style="display: flex; align-items: center; gap: 1rem;">
                <div style="width: 4rem; height: 4rem; background: var(--success); border-radius: 12px; display: flex; align-items: center; justify-content: center;">
                    <i class="fas fa-clipboard-check" style="font-size: 1.5rem; color: white;"></i>
                </div>
                <div>
                    <h1 style="font-size: 2rem; font-weight: 700; color: var(--success); margin: 0;">
                        Vérification des Rapports
                    </h1>
                    <p style="color: var(--text-secondary); margin: 0.5rem 0 0;">
                        Gérez et validez les rapports soumis par les étudiants
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="filter-bar" style="margin: 1.5rem 0;">
        <div style="position: relative; flex: 1;">
            <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: var(--primary);"></i>
            <input type="text" id="searchInput" class="input" style="padding-left: 3rem;"
                placeholder="Rechercher par nom d'étudiant, rapport ou thème...">
        </div>
    </div>

    <!-- Table Section -->
    <div class="card">
        <div class="card-header" style="background: var(--success); color: white;">
            <h2 class="card-title" style="color: white; margin: 0;">
                <i class="fas fa-list-ul"></i> Liste des Rapports
            </h2>
            <p style="margin: 0.5rem 0 0; opacity: 0.9;">Vérifiez, validez ou rejetez les rapports soumis par les étudiants</p>
        </div>

        <div class="table-wrapper">
            <table id="rapportsTable">
                <thead>
                    <tr>
                        <th><i class="fas fa-user-graduate"></i> Étudiant</th>
                        <th><i class="fas fa-file-lines"></i> Rapport</th>
                        <th><i class="fas fa-lightbulb"></i> Thème</th>
                        <th><i class="fas fa-calendar-day"></i> Date de dépôt</th>
                        <th><i class="fas fa-check-circle"></i> Approbation</th>
                        <th style="text-align: center;"><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rapports)): ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 4rem 2rem;">
                                <i class="fas fa-file-circle-xmark" style="font-size: 4rem; opacity: 0.5; color: var(--text-secondary);"></i>
                                <h3 style="font-size: 1.25rem; font-weight: 600; margin: 1rem 0;">Aucun rapport trouvé</h3>
                                <p style="color: var(--text-secondary);">Les rapports soumis par les étudiants apparaîtront ici</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($rapports as $i => $rapport): ?>
                            <tr>
                                <td>
                                    <div style="font-weight: 600; color: var(--text);">
                                        <?= htmlspecialchars($rapport->nom_etu . ' ' . $rapport->prenom_etu) ?>
                                    </div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">Étudiant</div>
                                </td>
                                <td>
                                    <div style="font-weight: 700;"><?= htmlspecialchars($rapport->nom_rapport) ?></div>
                                    <div style="font-size: 0.875rem; color: var(--text-secondary);">Rapport de master</div>
                                </td>
                                <td>
                                    <div style="font-style: italic; color: var(--primary); max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        <?= htmlspecialchars($rapport->theme_rapport) ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 600;"><?= date('d/m/Y', strtotime($rapport->date_depot)) ?></span>
                                </td>
                                <?php
                                // Récupérer l'approbation la plus récente si elle existe
                                $approb = null;
                                try {
                                    $apprList = Approuver::getByRapport($rapport->id_rapport);
                                    if (!empty($apprList)) {
                                        $last = end($apprList);
                                        $approb = isset($last['decision']) ? $last['decision'] : ($last->decision ?? null);
                                    }
                                } catch (Exception $e) {
                                    $approb = null; // en cas d'erreur, considérer comme non approuvé
                                }
                                ?>
                                <td style="text-align: center;">
                                    <?php if ($approb === 'approuve'): ?>
                                        <span class="badge badge-success">
                                            <i class="fas fa-check"></i> Approuvé
                                        </span>
                                    <?php elseif ($approb === 'desapprouve' || $approb === 'rejete'): ?>
                                        <span class="badge badge-danger">
                                            <i class="fas fa-times"></i> Désapprouvé
                                        </span>
                                    <?php else: ?>
                                        <span class="badge badge-warning">
                                            <i class="fas fa-clock"></i> En attente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <button onclick="voirDetail(<?= $rapport->id_rapport ?>)" class="btn btn-primary">
                                        <i class="fas fa-eye"></i> Voir détail
                                    </button>
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
<div id="detailModal" class="modal" style="display: none; position: fixed; inset: 0; z-index: 1000; align-items: center; justify-content: center; background: rgba(0, 0, 0, 0.5); backdrop-filter: blur(5px);">
    <div class="modal-content" style="max-width: 56rem; width: 90%; margin: 2rem; max-height: 90vh; overflow-y: auto;">
        <div class="modal-header">
            <h2 class="modal-title">
                <i class="fas fa-file-alt"></i> Détails du rapport
            </h2>
            <button onclick="fermerModal()" class="btn-close">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <div class="modal-body" id="modalContent">
            <!-- Le contenu sera chargé dynamiquement -->
        </div>
    </div>
</div>

<!-- Modal de confirmation validation/rejet -->
<div id="confirmModal" class="modal" style="display: none; position: fixed; inset: 0; z-index: 1000; align-items: center; justify-content: center; padding: 1rem;">
    <div class="modal-content" style="max-width: 28rem; width: 100%;" id="confirmModalContent">
        <h3 id="confirmModalTitle" style="font-size: 1.25rem; font-weight: 700; margin-bottom: 1rem; text-align: center;"></h3>

        <?php if (canEdit()): ?>
        <!-- Formulaire PHP pour valider -->
        <form id="validerForm" method="POST" action="?page=verification_candidatures_soutenance" style="display: none;">
            <input type="hidden" name="valider" value="1">
            <input type="hidden" id="validerRapportId" name="id_rapport">
            <div class="form-group">
                <label for="validerComment">Commentaire (obligatoire)</label>
                <textarea id="validerComment" name="commentaire" rows="3" class="input" placeholder="Entrez votre commentaire..." required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeConfirmModal()" class="btn">
                    Annuler
                </button>
                <button type="submit" class="btn btn-success">
                    Confirmer l'approbation
                </button>
            </div>
        </form>

        <!-- Formulaire PHP pour rejeter -->
        <form id="rejeterForm" method="POST" action="?page=verification_candidatures_soutenance" style="display: none;">
            <input type="hidden" name="rejeter" value="1">
            <input type="hidden" id="rejeterRapportId" name="id_rapport">
            <div class="form-group">
                <label for="rejeterComment">Commentaire (obligatoire)</label>
                <textarea id="rejeterComment" name="commentaire" rows="3" class="input" placeholder="Entrez votre commentaire..." required></textarea>
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                <button type="button" onclick="closeConfirmModal()" class="btn">
                    Annuler
                </button>
                <button type="submit" class="btn btn-danger">
                    Confirmer le rejet
                </button>
            </div>
        </form>
        <?php else: ?>
        <div style="text-align: center; color: var(--text-secondary); padding: 1rem;">
            <i class="fas fa-lock" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
            <p>Vous n'avez pas les permissions nécessaires pour effectuer cette action.</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Recherche dynamique dans le tableau
const searchInput = document.getElementById('searchInput');
searchInput.addEventListener('input', function () {
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
    modal.style.display = 'flex';
    document.body.classList.add('modal-open');
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

// Gestion des formulaires PHP
document.getElementById('validerForm').addEventListener('submit', function (e) {
    console.log('Formulaire de validation soumis');
    // Le formulaire sera soumis normalement via POST
});

document.getElementById('rejeterForm').addEventListener('submit', function (e) {
    console.log('Formulaire de rejet soumis');
    // Le formulaire sera soumis normalement via POST
});

// Fonction pour voir les détails d'un rapport
function voirDetail(idRapport) {
    // Afficher la modal avec overlay
    const modal = document.getElementById('detailModal');
    modal.style.display = 'flex';

    // Désactiver le scroll de la page
    document.body.classList.add('modal-open');

    // Afficher un loader
    document.getElementById('modalContent').innerHTML = `
        <div style="display: flex; justify-content: center; align-items: center; padding: 2rem; gap: 0.5rem;">
            <div style="width: 2rem; height: 2rem; border: 3px solid var(--border); border-top-color: var(--primary); border-radius: 50%; animation: spin 1s linear infinite;"></div>
            <span style="color: var(--text-secondary);">Chargement des détails...</span>
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
                <div style="text-align: center; padding: 2rem; color: var(--danger);">
                    <i class="fas fa-exclamation-triangle" style="font-size: 2rem; margin-bottom: 0.5rem;"></i>
                    <p>Erreur lors du chargement des détails</p>
                </div>
            `;
        });
}

// Fonction pour fermer le modal
function fermerModal() {
    const modal = document.getElementById('detailModal');
    modal.style.display = 'none';
    document.body.classList.remove('modal-open');
}

// Fermer les modals en cliquant à l'extérieur
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('detailModal');
    modal.addEventListener('click', function (e) {
        if (e.target === modal) {
            fermerModal();
        }
    });

    const confirmModal = document.getElementById('confirmModal');
    const confirmModalContent = document.getElementById('confirmModalContent');

    // Empêcher la propagation des clics à l'intérieur de la modal
    confirmModalContent.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    // Empêcher la propagation des clics sur les boutons
    const confirmButtons = confirmModalContent.querySelectorAll('button');
    confirmButtons.forEach(button => {
        button.addEventListener('click', function (e) {
            e.stopPropagation();
        });
    });

    // Empêcher la propagation des clics sur le formulaire
    const confirmForm = document.getElementById('validerForm');
    confirmForm.addEventListener('click', function (e) {
        e.stopPropagation();
    });

    confirmModal.addEventListener('click', function (e) {
        // Ne fermer que si on clique sur l'overlay (pas sur le contenu de la modal)
        if (e.target === confirmModal && !confirmModalContent.contains(e.target)) {
            closeConfirmModal();
        }
    });
});

// Fermer les modals avec la touche Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        fermerModal();
        closeConfirmModal();
    }
});

// Fonction pour afficher des notifications
function showNotification(type, message) {
    const notification = document.createElement('div');
    notification.className = `alert alert-${type === 'success' ? 'success' : type === 'error' ? 'danger' : 'info'}`;
    notification.style.cssText = 'position: fixed; top: 1rem; right: 1rem; z-index: 1000; min-width: 300px; transform: translateX(100%); transition: transform 0.3s ease;';
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;

    document.body.appendChild(notification);

    // Animation d'entrée
    setTimeout(() => {
        notification.style.transform = 'translateX(0)';
    }, 10);

    // Auto-suppression après 3 secondes
    setTimeout(() => {
        notification.style.transform = 'translateX(100%)';
        setTimeout(() => {
            document.body.removeChild(notification);
        }, 300);
    }, 3000);
}
</script>

<style>
@keyframes spin {
    to { transform: rotate(360deg); }
}

body.modal-open {
    overflow: hidden;
}

.btn-close {
    background: transparent;
    border: none;
    color: var(--text-secondary);
    cursor: pointer;
    font-size: 1.25rem;
    padding: 0.5rem;
    transition: color 0.2s;
}

.btn-close:hover {
    color: var(--text);
}
</style>

</html>
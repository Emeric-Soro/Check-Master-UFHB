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
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des rapports</title>
    <style>
    .notification {
        background: white;
        border-radius: 12px;
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        padding: 16px 20px;
        min-width: 320px;
        max-width: 480px;
        border-left: 4px solid;
        transform: translateX(100%);
        opacity: 0;
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        display: flex;
        align-items: flex-start;
        gap: 12px;
    }

    .notification.show {
        transform: translateX(0);
        opacity: 1;
    }

    .notification.hide {
        transform: translateX(100%);
        opacity: 0;
    }

    .notification.success {
        border-left-color: #10b981;
        background: linear-gradient(135deg, #f0fdf4 0%, #ecfdf5 100%);
    }

    .notification.error {
        border-left-color: #ef4444;
        background: linear-gradient(135deg, #fef2f2 0%, #fef2f2 100%);
    }

    .notification.info {
        border-left-color: #3b82f6;
        background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    }

    .notification.warning {
        border-left-color: #f59e0b;
        background: linear-gradient(135deg, #fffbeb 0%, #fef3c7 100%);
    }

    .notification-icon {
        flex-shrink: 0;
        width: 24px;
        height: 24px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .notification-content {
        flex: 1;
        min-width: 0;
    }

    .notification-title {
        font-weight: 600;
        font-size: 14px;
        margin-bottom: 4px;
        color: #1f2937;
    }

    .notification-message {
        font-size: 13px;
        color: #6b7280;
        line-height: 1.4;
        word-wrap: break-word;
    }

    .notification-close {
        flex-shrink: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 50%;
        cursor: pointer;
        transition: all 0.2s;
        opacity: 0.6;
    }

    .notification-close:hover {
        opacity: 1;
        background: rgba(0, 0, 0, 0.2);
    }

    .notification-progress {
        position: absolute;
        bottom: 0;
        left: 0;
        height: 3px;
        background: rgba(0, 0, 0, 0.1);
        border-radius: 0 0 12px 12px;
        transition: width linear;
    }

    .notification.success .notification-progress {
        background: #10b981;
    }

    .notification.error .notification-progress {
        background: #ef4444;
    }

    .notification.info .notification-progress {
        background: #3b82f6;
    }

    .notification.warning .notification-progress {
        background: #f59e0b;
    }
    </style>
</head>

<body class="min-h-screen">

    <!-- Notifications -->
    <div id="notificationContainer" class="fixed top-4 right-4 z-50 space-y-3">
        <!-- Les notifications seront ajoutées ici dynamiquement -->
    </div>

    <!-- Modal de confirmation pour suppression -->
    <div id="deleteModal" class="fixed inset-0 z-50 hidden items-center justify-center">
        <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex items-center mb-4">
                <div class="flex-shrink-0">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-red-600"></i>
                    </div>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-semibold text-gray-900">Confirmer la suppression</h3>
                    <p class="text-sm text-gray-600">Cette action est irréversible</p>
                </div>
            </div>

            <p class="text-gray-700 mb-6">
                Êtes-vous sûr de vouloir supprimer le rapport
                <span id="rapportNom" class="font-semibold text-gray-900"></span>
                ?
            </p>
            <p class="text-sm text-gray-600 mb-6">
                Cette action ne peut pas être annulée et supprimera définitivement le rapport et toutes ses données
                associées.
            </p>

            <form method="POST" action="?page=gestion_rapports">
                <input type="hidden" name="action" value="supprimer_rapport">
                <input type="hidden" name="rapport_id" id="rapportIdToDelete">
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="fermerModalSuppression()"
                        class="px-4 py-2 text-gray-600 hover:text-gray-800">Annuler</button>
                    <button type="submit" class="px-4 py-2 bg-red-500 text-white rounded hover:bg-red-600">
                        <i class="fas fa-trash mr-2"></i>Supprimer définitivement
                    </button>
                </div>
            </form>
        </div>
    </div>

    <section class="grid grid-cols-1 md:grid-cols-3 gap-8 max-w-6xl mx-auto">
        <!-- Carte 1: Créer un rapport -->
        <div class="card rounded-xl overflow-hidden shadow-lg text-white cursor-pointer">
            <div class="p-6 flex flex-col items-center card-gradient-1">
                <div class="icon-container card-gradient-1 bg-opacity-20 p-5 rounded-full mb-6">
                    <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Créer un Rapport</h3>
                <p class="text-white text-opacity-80 mb-6 text-center">Rédigez et soumettez votre rapport de
                    master directement via notre plateforme sécurisée.</p>
                <div class="mt-auto">
                    <?php if ($candidature_validee): ?>
                    <button
                        class="bg-white text-indigo-700 font-semibold py-2 px-6 rounded-lg hover:bg-opacity-90 transition duration-300 pulse">
                        <a href="?page=gestion_rapports&action=creer_rapport">Commencer</a>
                    </button>
                    <?php else: ?>
                    <button onclick="showCandidatureRequiredMessage()"
                        class="bg-gray-300 text-gray-500 font-semibold py-2 px-6 rounded-lg cursor-not-allowed transition duration-300"
                        disabled>
                        Commencer
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-gradient-to-r from-white to-transparent bg-opacity-10 h-1"></div>
            <div class="px-6 py-4 bg-indigo-500 bg-opacity-10">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-white text-opacity-70">Facile à utiliser</span>
                    <span class="text-xs font-medium bg-indigo-800 px-2 py-1 rounded-full">Étape 1</span>
                </div>
            </div>
        </div>

        <!-- Carte 2: Suivre l'avancée -->
        <div class="card rounded-xl overflow-hidden shadow-lg text-white cursor-pointer">
            <div class="p-6 flex flex-col items-center card-gradient-2">
                <div class="icon-container card-gradient-2 bg-opacity-20 p-5 rounded-full mb-6">
                    <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="white">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Suivre l'Avancée</h3>
                <p class="text-white text-opacity-80 mb-6 text-center">Surveillez l'état de votre soumission en
                    temps réel à chaque étape du processus de validation.</p>
                <div class="mt-auto">
                    <?php if ($candidature_validee): ?>
                    <button
                        class="bg-white text-green-700 font-semibold py-2 px-6 rounded-lg hover:bg-opacity-90 transition duration-300 floating">
                        <a href="?page=gestion_rapports&action=suivi_rapport">Consulter</a>
                    </button>
                    <?php else: ?>
                    <button onclick="showCandidatureRequiredMessage()"
                        class="bg-gray-300 text-gray-500 font-semibold py-2 px-6 rounded-lg cursor-not-allowed transition duration-300"
                        disabled>
                        Consulter
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-gradient-to-r from-white to-transparent bg-opacity-10 h-1"></div>
            <div class="px-6 py-4 bg-green-500 bg-opacity-10">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-white text-opacity-70">En temps réel</span>
                    <span class="text-xs font-medium bg-green-800 px-2 py-1 rounded-full">Étape 2</span>
                </div>
            </div>
        </div>

        <!-- Carte 3: Consulter les commentaires -->
        <div class="card rounded-xl overflow-hidden shadow-lg text-white cursor-pointer">
            <div class="p-6 flex flex-col items-center card-gradient-3">
                <div class="icon-container card-gradient-3 bg-opacity-20 p-5 rounded-full mb-6">
                    <svg class="h-10 w-10" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                    </svg>
                </div>
                <h3 class="text-xl font-bold mb-2">Consulter les commentaires</h3>
                <p class="text-white text-opacity-80 mb-6 text-center">Accédez aux retours détaillés des
                    évaluateurs pour améliorer votre travail académique.</p>
                <div class="mt-auto">
                    <?php if ($candidature_validee): ?>
                    <button
                        class="bg-white text-yellow-700 font-semibold py-2 px-6 rounded-lg hover:bg-opacity-90 transition duration-300 pulse">
                        <a href="?page=gestion_rapports&action=commentaire_rapport"> Voir les retours</a>
                    </button>
                    <?php else: ?>
                    <button onclick="showCandidatureRequiredMessage()"
                        class="bg-gray-300 text-gray-500 font-semibold py-2 px-6 rounded-lg cursor-not-allowed transition duration-300"
                        disabled>
                        Voir les retours
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <div class="bg-gradient-to-r from-white to-transparent bg-opacity-10 h-1"></div>
            <div class="px-6 py-4 bg-yellow-500 bg-opacity-10">
                <div class="flex justify-between items-center">
                    <span class="text-xs text-white text-opacity-70">Retours d'experts</span>
                    <span class="text-xs font-medium bg-yellow-800 px-2 py-1 rounded-full">Étape 3</span>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-16 max-w-6xl mx-auto">
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <div class="flex justify-between items-center">
                    <h3 class="text-2xl font-bold text-gray-800">Mes Rapports</h3>
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-3 py-1 rounded-full">
                        <?= isset($statistiquesRapports) ? ($statistiquesRapports->total_rapports ?? 0) : 0 ?>
                        rapport(s)
                    </span>
                </div>
            </div>

            <div class="p-6">
                <?php if (isset($rapportsRecents) && !empty($rapportsRecents)): ?>
                <div class="space-y-4">
                    <?php foreach ($rapportsRecents as $rapport): ?>
                    <div id="rapport-<?= $rapport->id_rapport ?>"
                        class="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                        <div class="flex justify-between items-start">
                            <div class="flex-1">
                                <h4 class="text-lg font-semibold text-gray-800 mb-2">
                                    <?= htmlspecialchars($rapport->nom_rapport) ?>
                                </h4>
                                <p class="text-gray-600 mb-2">
                                    <strong>Thème:</strong> <?= htmlspecialchars($rapport->theme_rapport) ?>
                                </p>
                                <p class="text-sm text-gray-500">
                                    Créé le <?= date('d/m/Y à H:i', strtotime($rapport->date_rapport)) ?>
                                </p>
                            </div>
                            <div class="flex space-x-2 ml-4">
                                <?php 
                                // Utiliser les informations de dépôt passées par le contrôleur
                                $infoDepot = $infosDepot[$rapport->id_rapport] ?? ['peutDeposer' => true, 'messageDepot' => '', 'dejaDepose' => false];
                                $peutDeposer = $infoDepot['peutDeposer'];
                                $messageDepot = $infoDepot['messageDepot'];
                                $dejaDepose = $infoDepot['dejaDepose'];
                                ?>

                                <?php if ($peutDeposer): ?>
                                <form method="POST" action="?page=gestion_rapports" style="display:inline;"
                                    id="deposerForm-<?= $rapport->id_rapport ?>">
                                    <input type="hidden" name="id_rapport" value="<?= $rapport->id_rapport ?>">
                                    <input type="hidden" name="action" value="deposer_rapport">
                                    <button type="submit"
                                        class="bg-blue-500 hover:bg-purple-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                        <i class="fas fa-upload mr-1"></i> Déposer
                                    </button>
                                </form>
                                <?php else: ?>
                                <button disabled
                                    class="bg-gray-400 text-gray-600 px-3 py-1 rounded text-sm cursor-not-allowed"
                                    title="<?= htmlspecialchars($messageDepot) ?>">
                                    <i class="fas fa-upload mr-1"></i> <?= htmlspecialchars($messageDepot) ?>
                                </button>
                                <?php endif; ?>

                                <?php if ($dejaDepose): ?>
                                <!-- Rapport déjà déposé - bouton "Voir" seulement -->
                                <button onclick="voirRapport(<?= $rapport->id_rapport ?>)"
                                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-sm flex items-center gap-1 shadow transition-colors"
                                    title="">
                                    <i class="fa-solid fa-eye"></i>
                                    Voir
                                </button>
                                <?php else: ?>
                                <!-- Rapport non déposé - bouton "Voir" -->
                                <button onclick="voirRapport(<?= $rapport->id_rapport ?>)"
                                    class="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded text-sm flex items-center gap-1 shadow transition-colors"
                                    title="Voir le rapport">
                                    <i class="fa-solid fa-eye"></i>
                                    Voir
                                </button>
                                <?php endif; ?>

                                <?php if (!$dejaDepose): ?>
                                <!-- Bouton supprimer seulement si le rapport n'est pas déposé -->
                                <button
                                    onclick="confirmerSuppression(<?= $rapport->id_rapport ?>, '<?= htmlspecialchars(addslashes($rapport->nom_rapport)) ?>')"
                                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm transition-colors">
                                    <i class="fas fa-trash mr-1"></i> Supprimer
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if (count($rapportsRecents) >= 5): ?>
                <div class="mt-6 text-center">
                    <a href="?page=gestion_rapports&action=suivi_rapport"
                        class="bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-list mr-2"></i> Voir tous mes rapports
                    </a>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="text-center py-8">
                    <i class="fas fa-file-alt text-4xl text-gray-300 mb-4"></i>
                    <p class="text-gray-500 text-lg mb-4">Aucun rapport créé pour le moment</p>
                    <?php if ($candidature_validee): ?>
                    <a href="?page=gestion_rapports&action=creer_rapport"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg inline-flex items-center">
                        <i class="fas fa-plus mr-2"></i> Créer mon premier rapport
                    </a>
                    <?php else: ?>
                    <button onclick="showCandidatureRequiredMessage()"
                        class="bg-gray-400 text-gray-600 px-6 py-2 rounded-lg inline-flex items-center cursor-not-allowed"
                        disabled>
                        <i class="fas fa-plus mr-2"></i> Créer mon premier rapport
                    </button>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>
    <section class="mt-16 max-w-4xl mx-auto bg-white rounded-xl shadow-md overflow-hidden">
        <div class="p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-4">Processus de validation</h3>
            <div class="flex flex-wrap">
                <div class="w-full md:w-1/2">
                    <ol class="relative border-l border-gray-200 ml-3">
                        <li class="mb-10 ml-6">
                            <span
                                class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -left-4 ring-4 ring-white">
                                <svg class="w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z" />
                                </svg>
                            </span>
                            <h3 class="font-medium text-gray-900">Dépôt du rapport</h3>
                            <p class="text-sm text-gray-500">L'étudiant rédige et soumet son rapport dans
                                l'application</p>
                        </li>
                        <li class="mb-10 ml-6">
                            <span
                                class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -left-4 ring-4 ring-white">
                                <svg class="w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                </svg>
                            </span>
                            <h3 class="font-medium text-gray-900">Vérification initiale</h3>
                            <p class="text-sm text-gray-500">vérification de son admissibilité et respect des normes
                                formelles</p>
                        </li>
                        <li class="ml-6">
                            <span
                                class="absolute flex items-center justify-center w-8 h-8 bg-blue-100 rounded-full -left-4 ring-4 ring-white">
                                <svg class="w-4 h-4 text-blue-600" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </span>
                            <h3 class="font-medium text-gray-900">Validation par la commission</h3>
                            <p class="text-sm text-gray-500">Examen par le jury et autorisation de soutenance
                            </p>
                        </li>
                    </ol>
                </div>

            </div>
        </div>
    </section>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        // Afficher les messages PHP au chargement de la page
        <?php if (isset($_GET['message'])): ?>
        <?php if ($_GET['message'] === 'depot_ok'): ?>
        showNotification('success', 'Le rapport a bien été déposé.');
        <?php elseif ($_GET['message'] === 'depot_fail'): ?>
        showNotification('error', 'Impossible de déposer le rapport (déjà déposé ou erreur technique).');
        <?php elseif ($_GET['message'] === 'depot_en_cours'): ?>
        showNotification('warning',
            'Vous ne pouvez pas déposer ce rapport car vous avez déjà un rapport en cours d\'évaluation. Attendez que votre rapport précédent soit approuvé ou rejeté.'
        );
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
        notification.className = `notification ${type}`;

        // Définir les icônes selon le type
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
                iconPath =
                    'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z';
                break;
            default:
                iconPath = 'M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z';
        }

        notification.innerHTML = `
            <div class="notification-icon">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${iconPath}"></path>
                </svg>
        </div>
            <div class="notification-content">
                <div class="notification-title">${displayTitle}</div>
                <div class="notification-message">${message}</div>
        </div>
            <button class="notification-close">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <div class="notification-progress"></div>
`;

        const closeButton = notification.querySelector('.notification-close');
        const progressBar = notification.querySelector('.notification-progress');

        // Gestion de la fermeture manuelle
        closeButton.addEventListener('click', function() {
            hideNotification(notification);
        });

        // Animation de la barre de progression
        let progress = 100;
        const progressInterval = setInterval(() => {
            progress -= 1;
            progressBar.style.width = progress + '%';
            if (progress <= 0) {
                clearInterval(progressInterval);
            }
        }, 30); // 3000ms / 100 = 30ms par étape

        notificationContainer.appendChild(notification);

        // Animation d'entrée
        setTimeout(() => {
            notification.classList.add('show');
        }, 10);

        // Auto-fermeture après 3 secondes
        setTimeout(() => {
            hideNotification(notification);
            clearInterval(progressInterval);
        }, 3000);

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

    // Fonctions pour la modal de suppression
    function confirmerSuppression(rapportId, rapportNom) {
        document.getElementById('rapportIdToDelete').value = rapportId;
        document.getElementById('rapportNom').textContent = '"' + rapportNom + '"';
        document.getElementById('deleteModal').classList.remove('hidden');
        document.getElementById('deleteModal').classList.add('flex');
    }

    function fermerModalSuppression() {
        document.getElementById('deleteModal').classList.add('hidden');
        document.getElementById('deleteModal').classList.remove('flex');
    }
    </script>
</body>

</html>
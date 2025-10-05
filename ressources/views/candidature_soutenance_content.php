<?php
$stage_info = isset($GLOBALS['stage_info']) ? $GLOBALS['stage_info'] : [];
$compte_rendu = isset($GLOBALS['compte_rendu']) ? $GLOBALS['compte_rendu'] : [];
$has_candidature = isset($GLOBALS['has_candidature']) ? $GLOBALS['has_candidature'] : false;
$candidature = isset($GLOBALS['candidature']) ? $GLOBALS['candidature'] : null;
$candidatures_etudiant = isset($GLOBALS['candidatures_etudiant']) ? $GLOBALS['candidatures_etudiant'] : [];
$disableCandidature = empty($stage_info);
foreach ($candidatures_etudiant as $cand) {
    if (in_array($cand['statut_candidature'], ['En attente', 'Validée'])) {
        $disableCandidature = true;
        break;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidater à la soutenance</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root{--blue:#0F4C75;--blue-light:#3282B8;--green:#10b981;--muted:#64748B}
        .text-blue-500{color:var(--blue) !important}
        .bg-blue-500{background-color:var(--blue) !important}
        .bg-green-500{background-color:var(--green) !important}
        .card-btn{background:linear-gradient(135deg,var(--blue),var(--blue-light)) !important}
        .card{background:#ffffff}
        .card-icon svg{color:var(--blue)}
        .bg-red-100{background-color:rgba(15,76,117,0.08)}
        .bg-yellow-100{background-color:rgba(16,185,129,0.08)}
        .rounded-lg{border-radius:0.5rem}
        .shadow-lg{box-shadow:0 20px 25px -5px rgba(0,0,0,0.1)}
        .transition-shadow{transition:box-shadow .3s ease}
        .text-text-dark{color:#0f1720}
        .text-text-light{color:var(--muted)}
        .floating-shape{opacity:.07;position:absolute;z-index:-10}
    </style>
</head>
<body class="bg-gradient-to-br from-[#f6f7ff] to-[#e9ebfa] min-h-screen">
<div class="floating-shape shape-1"></div>
<div class="floating-shape shape-2"></div>

<div class="container max-w-6xl mx-auto px-4 py-8 md:px-4 md:py-6">
    <?php if (isset($_SESSION['success'])): ?>
        <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            <?php
            echo $_SESSION['success'];
            unset($_SESSION['success']);
            ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="mb-4 p-4 bg-red-100 border border-red-400 text-blue-700 rounded">
            <?php
            echo $_SESSION['error'];
            unset($_SESSION['error']);
            ?>
        </div>
    <?php endif; ?>

    <div id="warningMessage" class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-blue-700 rounded hidden">
        Veuillez d'abord remplir les informations de stage pour accéder aux autres fonctionnalités.
    </div>

    <div id="globalCandidatureError" style="display:none;z-index:9999;color:#b91c1c;background:#fee2e2;border:1px solid #fca5a5;margin-bottom:22px;padding:12px 24px;border-radius:6px;max-width:90vw;box-shadow:0 2px 8px rgba(0,0,0,0.08);font-size:1rem;opacity:0;transition:opacity 0.4s;">
    </div>

    <div class="header text-center mb-8">
        <h1 class="text-3xl font-bold text-text-dark mb-3 md:text-2xl text-green-500">Candidature à la soutenance
        </h1>
        <p class="text-base text-text-light max-w-2xl mx-auto md:text-sm">Faite votre demande de candidature à la
            soutenance et accédez aux comptes rendus de la commission d'évaluation.</p>
    </div>

    <div class="cards-container grid grid-cols-1 md:grid-cols-2 gap-6 max-w-4xl mx-auto">
        <div class="card bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="card-content p-6 py-6 flex flex-col items-center text-center h-full">
                <div class="card-icon text-blue-500 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-text-dark mb-3">Informations du stage</h2>
                <p class="text-text-light mb-6 flex-grow text-base leading-relaxed">Remplissez les informations
                    concernant votre stage avant de faire votre demande de candidature.</p>
                <button onclick="openStageInfoModal()" class="card-btn bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition-colors duration-300 text-sm">
                        <span class="flex items-center">
                            Remplir les informations
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2">
                                <path d="M5 12h14"></path>
                                <path d="M12 5l7 7-7 7"></path>
                            </svg>
                        </span>
                </button>
            </div>
        </div>

        <div class="card bg-white rounded-lg shadow-lg hover:shadow-xl transition-shadow duration-300">
            <div class="card-content p-6 py-6 flex flex-col items-center text-center h-full">
                <div class="card-icon text-green-500 mb-3">
                    <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <h2 class="text-xl font-bold text-text-dark mb-3">Demande de candidature</h2>
                <p class="text-text-light mb-6 flex-grow text-base leading-relaxed">Faite votre demande de candidature
                    au près de l'administration et obtenez une réponse sur votre statut après vérification.</p>
                <?php
                $onclick = empty($stage_info)
                    ? 'showWarningMessage(); return false;'
                    : ($disableCandidature
                        ? 'showCandidatureExistsMessage(); return false;'
                        : 'openConfirmationModal()');
                $btnClass = $disableCandidature
                    ? 'bg-gray-400 cursor-not-allowed'
                    : 'bg-green-500 hover:bg-green-600';
                ?>
                <button id="btnDemandeCandidature" onclick="<?php echo $onclick; ?>" class="card-btn <?php echo $btnClass; ?> text-white px-4 py-2 rounded-lg transition-colors duration-300 text-sm" style="<?php echo $disableCandidature ? 'opacity:0.6;cursor:not-allowed;' : ''; ?>">
                        <span class="flex items-center">
                            Demande de candidature
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="ml-2">
                                <path d="M5 12h14"></path>
                                <path d="M12 5l7 7-7 7"></path>
                            </svg>
                        </span>
                </button>
                <div id="demandeCandidatureError" style="display:none;color:#b91c1c;background:#fee2e2;border:1px solid #fca5a5;padding:8px 12px;border-radius:4px;max-width:400px;margin-top:8px;">
                </div>
            </div>
        </div>
    </div>
</div>

<div id="confirmationModal" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4 shadow-2xl">
        <h3 class="text-2xl mb-4">Confirmer votre demande</h3>
        <p class="text-blue-600 mb-6">Êtes-vous sûr de vouloir soumettre votre demande de candidature à la soutenance ?</p>
        <form method="POST" action="?page=candidature_soutenance&action=demande_candidature">
            <div class="flex justify-end space-x-4">
                <button type="button" onclick="closeConfirmationModal()" class="px-4 py-2 text-blue-600 hover:text-blue-800 shadow-2xs">Annuler</button>
                <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600 shadow-2xs">Confirmer</button>
            </div>
        </form>
    </div>
</div>

<div id="stageInfoModal" class="fixed inset-0 hidden items-center z-50 justify-center">
    <div class="bg-white rounded-lg p-8 max-w-2xl w-full mx-4 shadow-2xl max-h-[90vh] overflow-y-auto">
        <h3 class="text-2xl font-bold mb-6 text-gray-800 sticky top-0 bg-white pb-4">Informations du stage</h3>
        <form id="stageInfoForm" class="space-y-6" method="POST" action="?page=candidature_soutenance&action=info_stage">
            <div class="grid grid-cols-2 gap-6">
                <div class="col-span-2">
                    <label for="entreprise" class="block text-sm font-medium text-gray-700 mb-1">Entreprise</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <input type="text" name="entreprise" required value="<?php echo isset($stage_info['nom_entreprise']) ? htmlspecialchars($stage_info['nom_entreprise']) : ''; ?>" class="pl-10 block w-full py-2 outline-green-500 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <div>
                    <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-1">Date de début</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="date" name="date_debut" required value="<?php echo isset($stage_info['date_debut_stage']) ? htmlspecialchars($stage_info['date_debut_stage']) : ''; ?>" class="pl-10 block w-full py-2 outline-green-500 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <div>
                    <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="date" name="date_fin" required value="<?php echo isset($stage_info['date_fin_stage']) ? htmlspecialchars($stage_info['date_fin_stage']) : ''; ?>" class="pl-10 block w-full py-2 outline-green-500 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                    </div>
                </div>

                <div class="col-span-2">
                    <label for="sujet" class="block text-sm font-medium text-gray-700 mb-1">Sujet du stage</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <input type="text" name="sujet" required value="<?php echo isset($stage_info['sujet_stage']) ? htmlspecialchars($stage_info['sujet_stage']) : ''; ?>" class="pl-10 py-2 outline-green-500 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Ex: Développement d'une application web">
                    </div>
                </div>

                <div class="col-span-2">
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description du stage</label>
                    <div class="relative">
                        <div class="absolute top-3 left-3">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h7" />
                            </svg>
                        </div>
                        <textarea name="description" required rows="4" class="pl-10 py-2 outline-green-500 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Décrivez les principales missions et objectifs de votre stage..."><?php echo isset($stage_info['description_stage']) ? htmlspecialchars($stage_info['description_stage']) : ''; ?></textarea>
                    </div>
                </div>

                <div class="col-span-2">
                    <label for="encadrant" class="block text-sm font-medium text-gray-700 mb-1">Nom de l'encadrant</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <input type="text" name="encadrant" required value="<?php echo isset($stage_info['encadrant_entreprise']) ? htmlspecialchars($stage_info['encadrant_entreprise']) : ''; ?>" class="pl-10 py-2 outline-green-500 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="Nom complet de l'encadrant">
                    </div>
                </div>

                <div>
                    <label for="email_encadrant" class="block text-sm font-medium text-gray-700 mb-1">Email de l'encadrant</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <input type="email" name="email_encadrant" required value="<?php echo isset($stage_info['email_encadrant']) ? htmlspecialchars($stage_info['email_encadrant']) : ''; ?>" class="pl-10 py-2 outline-green-500 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="email@entreprise.com">
                    </div>
                </div>

                <div>
                    <label for="telephone_encadrant" class="block text-sm font-medium text-gray-700 mb-1">Téléphone de l'encadrant</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                            </svg>
                        </div>
                        <input type="tel" name="telephone_encadrant" required value="<?php echo isset($stage_info['telephone_encadrant']) ? htmlspecialchars($stage_info['telephone_encadrant']) : ''; ?>" class="pl-10 block py-2 outline-green-500 w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" placeholder="+225 07 07 07 07 07">
                    </div>
                </div>
            </div>

            <div class="flex justify-end space-x-4 mt-8 sticky bottom-0 bg-white pt-4">
                <button type="button" onclick="closeStageInfoModal()" class="px-6 py-2.5 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors duration-200">Annuler</button>
                <button type="submit" name="btn_enregistrer" value="1" class="px-6 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium transition-colors duration-200 flex items-center">
                    <svg class="w-5 h-5 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Enregistrer
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openConfirmationModal() {
        document.getElementById('confirmationModal').classList.remove('hidden');
        document.getElementById('confirmationModal').classList.add('flex');
    }

    function closeConfirmationModal() {
        document.getElementById('confirmationModal').classList.add('hidden');
        document.getElementById('confirmationModal').classList.remove('flex');
    }

    function openStageInfoModal() {
        document.getElementById('stageInfoModal').classList.remove('hidden');
        document.getElementById('stageInfoModal').classList.add('flex');
    }

    function closeStageInfoModal() {
        document.getElementById('stageInfoModal').classList.remove('flex');
        document.getElementById('stageInfoModal').classList.add('hidden');
    }

    function showWarningMessage() {
        const warningMessage = document.getElementById('warningMessage');
        warningMessage.classList.remove('hidden');
        warningMessage.scrollIntoView({behavior:'smooth',block:'start'});
        setTimeout(()=>{warningMessage.classList.add('hidden');},5000);
    }

    function showNoCompteRenduMessage() {
        const warningMessage = document.getElementById('warningMessage');
        warningMessage.textContent = "Aucun compte rendu disponible pour le moment.";
        warningMessage.classList.remove('hidden');
        warningMessage.scrollIntoView({behavior:'smooth',block:'start'});
        setTimeout(()=>{warningMessage.classList.add('hidden');},5000);
    }

    function showCandidatureExistsMessage() {
        const warningMessage = document.getElementById('warningMessage');
        warningMessage.textContent = "Vous avez déjà soumis une candidature qui est en attente ou validée. Vous ne pouvez pas en soumettre une nouvelle pour le moment.";
        warningMessage.classList.remove('hidden');
        warningMessage.scrollIntoView({behavior:'smooth',block:'start'});
        setTimeout(()=>{warningMessage.classList.add('hidden');},5000);
    }

    document.addEventListener('DOMContentLoaded', function() {
        const messages = document.querySelectorAll('.mb-4:not(#warningMessage)');
        messages.forEach(function(message) {
            setTimeout(function() {
                message.style.opacity = '0';
                message.style.transition = 'opacity 0.5s ease';
                setTimeout(function() {
                    message.remove();
                }, 500);
            }, 5000);
        });
    });

    function showGlobalCandidatureError(message) {
        var errorDiv = document.getElementById('globalCandidatureError');
        errorDiv.textContent = message;
        errorDiv.style.display = 'block';
        setTimeout(function() { errorDiv.style.opacity = '1'; }, 10);
        setTimeout(function() {
            errorDiv.style.opacity = '0';
            setTimeout(function() { errorDiv.style.display = 'none'; }, 400);
        }, 4000);
    }
</script>
</body>
</html>
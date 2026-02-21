<?php
// Vérifier que l'utilisateur est un étudiant
if (!isset($_SESSION['num_etu'])) {
    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">';
    echo '<strong class="font-bold">Accès refusé!</strong>';
    echo '<span class="block sm:inline"> Cette page est réservée aux étudiants.</span>';
    echo '</div>';
    return;
}
// Récupérer le compte rendu de l'étudiant
require_once __DIR__ . '/../../app/config/database.php';
require_once __DIR__ . '/../../app/models/Etudiant.php';

$etudiantModel = new Etudiant(Database::getConnection());
$compte_rendu = $etudiantModel->getCompteRendu($_SESSION['num_etu']);
?>

<div class="min-h-screen" style="background: linear-gradient(135deg, #DFF2FF 0%, #C8E8FF 100%);">
    <div class="container mx-auto px-4 py-6">
        <!-- Header -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-primary flex items-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 mr-3" fill="none" viewBox="0 0 24 24"
                    stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Mon Compte Rendu d'Évaluation
            </h1>
            <p class="text-gray-600 mt-2">Consultez le compte rendu d'évaluation de votre dossier de candidature à la
                soutenance</p>
        </div>

        <?php if (!$compte_rendu): ?>
            <!-- Aucun compte rendu disponible -->
            <div class="bg-white rounded-lg shadow-md p-8 text-center">
                <div class="mb-6">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-24 w-24 mx-auto text-gray-400" fill="none"
                        viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                </div>
                <h2 class="text-2xl font-bold text-gray-700 mb-3">Aucun compte rendu disponible</h2>
                <p class="text-gray-600 mb-6">
                    Le compte rendu d'évaluation de votre dossier n'est pas encore disponible.<br>
                    Il sera publié par la commission d'évaluation après l'examen de votre candidature.
                </p>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mt-6 text-left">
                    <h3 class="font-medium text-blue-800 mb-2 flex items-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Que faire en attendant ?
                    </h3>
                    <ul class="text-sm text-blue-700 space-y-2">
                        <li class="flex items-start">
                            <span class="mr-2">✓</span>
                            <span>Assurez-vous d'avoir soumis votre candidature avec tous les documents requis</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">✓</span>
                            <span>Vérifiez régulièrement cette page pour consulter votre compte rendu dès sa
                                publication</span>
                        </li>
                        <li class="flex items-start">
                            <span class="mr-2">✓</span>
                            <span>Vous recevrez une notification par email lorsque le compte rendu sera disponible</span>
                        </li>
                    </ul>
                </div>
                <div class="mt-6">
                    <a href="?page=candidature_soutenance"
                        class="inline-block bg-primary hover:bg-primary-light text-white px-6 py-3 rounded-lg font-medium transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-2" fill="none"
                            viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Retour à ma candidature
                    </a>
                </div>
            </div>
        <?php else: ?>
            <!-- Affichage du compte rendu -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <!-- En-tête du compte rendu -->
                <div class="bg-gradient-to-r from-primary to-primary-light px-6 py-4">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-xl font-bold text-white">
                                <?php echo htmlspecialchars($compte_rendu['nom_CR']); ?>
                            </h2>
                            <p class="text-blue-100 text-sm mt-1">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                                Publié le <?php echo date('d/m/Y à H:i', strtotime($compte_rendu['date_CR'])); ?>
                            </p>
                        </div>
                        <?php if (!empty($compte_rendu['chemin_fichier_pdf'])): ?>
                            <a href="<?php echo htmlspecialchars($compte_rendu['chemin_fichier_pdf']); ?>" target="_blank"
                                class="bg-white text-primary hover:bg-blue-50 px-4 py-2 rounded-lg font-medium transition-colors flex items-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                                    stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                                Télécharger PDF
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Contenu du compte rendu -->
                <div class="p-6">
                    <div class="prose max-w-none">
                        <?php if (!empty($compte_rendu['contenu_CR'])): ?>
                            <div class="text-gray-700 leading-relaxed">
                                <?php echo nl2br(htmlspecialchars($compte_rendu['contenu_CR'])); ?>
                            </div>
                        <?php else: ?>
                            <p class="text-gray-500 italic">Le contenu du compte rendu n'est pas disponible en version texte.
                                Veuillez consulter le PDF ci-dessus.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Pied du compte rendu -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-600">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 inline-block mr-1 text-gray-400"
                                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            Numéro étudiant : <strong><?php echo htmlspecialchars($compte_rendu['num_etu']); ?></strong>
                        </div>
                        <a href="?page=candidature_soutenance"
                            class="inline-block bg-primary hover:bg-primary-light text-white px-4 py-2 rounded-lg font-medium transition-colors text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Retour à ma candidature
                        </a>
                    </div>
                </div>
            </div>

            <!-- Informations supplémentaires -->
            <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <h3 class="font-medium text-yellow-800 mb-2 flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    Note importante
                </h3>
                <p class="text-sm text-yellow-700">
                    Ce compte rendu est un document officiel émis par la commission d'évaluation. Pour toute question ou
                    contestation, veuillez contacter le secrétariat pédagogique.
                </p>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .prose {
        line-height: 1.8;
    }

    .prose p {
        margin-bottom: 1rem;
    }
</style>
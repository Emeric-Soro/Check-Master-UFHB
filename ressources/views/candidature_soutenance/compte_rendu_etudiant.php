<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Compte Rendu de Soutenance</title>
</head>

<body class="bg-gradient-to-br from-[#f6f7ff] to-[#e9ebfa] min-h-screen">
    <div class="container max-w-4xl mx-auto px-4 py-8">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Compte Rendu de Soutenance</h1>
                <a href="?page=candidature_soutenance" class="text-primary hover:text-blue-600">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                        stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                    </svg>
                </a>
            </div>

            <?php 
            $compte_rendu = $GLOBALS['compte_rendu'] ?? null;
            if (!$compte_rendu): 
            ?>
                <div class="text-center py-8">
                    <p class="text-gray-500 text-lg">Aucun compte rendu disponible pour cette soutenance.</p>
                </div>
            <?php else: ?>
            <div class="space-y-6">
                <!-- Statut de la candidature -->
                <div class="border-b pb-4">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Statut de la candidature</h2>
                    <div class="flex items-center">
                        <span
                            class="px-3 py-1 rounded-full text-sm font-medium
                            <?php 
                            $statut = $compte_rendu['statut'] ?? 'en_attente';
                            echo $statut === 'accepté' ? 'bg-green-100 text-primary' : 
                                    ($statut === 'refusé' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800'); 
                            ?>">
                            <?php echo ucfirst($statut); ?>
                        </span>
                    </div>
                </div>

                <!-- Date de la soutenance -->
                <div class="border-b pb-4">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Date de la soutenance</h2>
                    <p class="text-gray-600">
                        <?php 
                        $date_soutenance = $compte_rendu['date_soutenance'] ?? null;
                        echo $date_soutenance ? date('d/m/Y', strtotime($date_soutenance)) : 'Non définie';
                        ?>
                    </p>
                </div>

                <!-- Évaluation technique -->
                <div class="border-b pb-4">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Évaluation technique</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Qualité du travail</span>
                            <span class="font-medium">
                                <?php 
                                $note_technique = $compte_rendu['note_technique'] ?? 0;
                                echo $note_technique . '/20';
                                ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full"
                                style="width: <?php echo ($note_technique/20)*100; ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Évaluation de la présentation -->
                <div class="border-b pb-4">
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Évaluation de la présentation</h2>
                    <div class="space-y-2">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600">Qualité de la présentation</span>
                            <span class="font-medium">
                                <?php 
                                $note_presentation = $compte_rendu['note_presentation'] ?? 0;
                                echo $note_presentation . '/20';
                                ?>
                            </span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-primary h-2 rounded-full"
                                style="width: <?php echo ($note_presentation/20)*100; ?>%"></div>
                        </div>
                    </div>
                </div>

                <!-- Commentaires -->
                <div>
                    <h2 class="text-lg font-semibold text-gray-700 mb-2">Commentaires</h2>
                    <div class="bg-gray-50 rounded-lg p-4">
                        <p class="text-gray-600 whitespace-pre-line">
                            <?php 
                            $commentaires = $compte_rendu['commentaires'] ?? 'Aucun commentaire disponible.';
                            echo nl2br(htmlspecialchars($commentaires));
                            ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</body>

</html>
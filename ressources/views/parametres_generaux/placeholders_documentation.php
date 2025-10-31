<?php
require_once __DIR__ . '/../../../app/utils/permissions.php';
?>

<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Documentation des Placeholders</title>
    <style>
    .code-box {
        background-color: #f3f4f6;
        border: 1px solid #d1d5db;
        border-radius: 0.5rem;
        padding: 0.75rem;
        font-family: 'Courier New', monospace;
        font-size: 0.875rem;
        color: #1f2937;
    }
    
    .section-card {
        background-color: white;
        border-radius: 0.5rem;
        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1);
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }
    
    .placeholder-item {
        padding: 0.5rem 0;
        border-bottom: 1px solid #e5e7eb;
    }
    
    .placeholder-item:last-child {
        border-bottom: none;
    }
    </style>
</head>

<body class="bg-gray-50">

    <div class="container mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-800 mb-2">
                        <i class="fas fa-book text-blue-600 mr-2"></i>
                        Documentation des Placeholders
                    </h1>
                    <p class="text-gray-600">Variables disponibles pour les modèles de documents</p>
                </div>
                <a 
                    href="?page=parametres_generaux&action=modeles_documents" 
                    class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg font-medium"
                >
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour
                </a>
            </div>
        </div>

        <!-- Introduction -->
        <div class="section-card">
            <h2 class="text-xl font-bold text-gray-800 mb-3">
                <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                Comment utiliser les placeholders
            </h2>
            <p class="text-gray-700 mb-3">
                Dans vos modèles Word (.docx), vous pouvez insérer des variables dynamiques qui seront remplacées par les données réelles lors de la génération du PDF.
            </p>
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-3">
                <p class="text-sm text-yellow-800">
                    <strong>Syntaxe :</strong> Les placeholders doivent être entourés de <code class="bg-yellow-200 px-1 rounded">${'{'}</code> et <code class="bg-yellow-200 px-1 rounded">{'}'}</code>
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="font-semibold text-gray-700 mb-2">Exemple dans Word :</p>
                    <div class="code-box">
                        L'étudiant ${'{nom_etudiant}'} a soutenu<br>
                        le ${'{date_soutenance}'} son mémoire<br>
                        intitulé "${'{theme}'}".
                    </div>
                </div>
                <div>
                    <p class="font-semibold text-gray-700 mb-2">Résultat dans le PDF :</p>
                    <div class="code-box">
                        L'étudiant Jean Kouassi a soutenu<br>
                        le 15/12/2024 son mémoire<br>
                        intitulé "Intelligence Artificielle".
                    </div>
                </div>
            </div>
        </div>

        <!-- PV Soutenance Placeholders -->
        <div class="section-card">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-graduation-cap text-green-600 mr-2"></i>
                Procès-Verbal de Soutenance (PV)
            </h2>
            <p class="text-sm text-gray-600 mb-4">Modèle : <code class="bg-gray-200 px-2 py-1 rounded">pv_soutenance.docx</code></p>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 text-sm uppercase">Informations Étudiant</h3>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{nom_etudiant}'}</code>
                        <p class="text-sm text-gray-600">Nom complet de l'étudiant</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{promotion}'}</code>
                        <p class="text-sm text-gray-600">Promotion (ex: Master 2 GLSI 2024)</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{niveau}'}</code>
                        <p class="text-sm text-gray-600">Niveau d'étude (Master 1 / Master 2)</p>
                    </div>
                </div>
                
                <div>
                    <h3 class="font-semibold text-gray-700 mb-2 text-sm uppercase">Soutenance</h3>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{date_soutenance}'}</code>
                        <p class="text-sm text-gray-600">Date de la soutenance</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{theme}'}</code>
                        <p class="text-sm text-gray-600">Thème du mémoire</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{note_finale}'}</code>
                        <p class="text-sm text-gray-600">Note finale de soutenance</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{mention}'}</code>
                        <p class="text-sm text-gray-600">Mention obtenue</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h3 class="font-semibold text-gray-700 mb-2 text-sm uppercase">Membres du Jury</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2">
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{president}'}</code>
                        <p class="text-sm text-gray-600">Président du jury</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{examinateur}'}</code>
                        <p class="text-sm text-gray-600">Examinateur</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{directeur}'}</code>
                        <p class="text-sm text-gray-600">Directeur de mémoire</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{encadreur}'}</code>
                        <p class="text-sm text-gray-600">Encadreur</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{maitre_stage}'}</code>
                        <p class="text-sm text-gray-600">Maître de stage</p>
                    </div>
                </div>
            </div>
            
            <div class="mt-4">
                <h3 class="font-semibold text-gray-700 mb-2 text-sm uppercase">Moyennes</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2">
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{moyenne_master1}'}</code>
                        <p class="text-sm text-gray-600">Moyenne Master 1</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{moyenne_s1_master2}'}</code>
                        <p class="text-sm text-gray-600">Moyenne S1 Master 2</p>
                    </div>
                    <div class="placeholder-item">
                        <code class="text-blue-600">${'{note_memoire}'}</code>
                        <p class="text-sm text-gray-600">Note du mémoire</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Repeating Blocks -->
        <div class="section-card">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-table text-purple-600 mr-2"></i>
                Blocs Répétitifs (Tableaux)
            </h2>
            <p class="text-gray-700 mb-3">
                Pour afficher des listes ou tableaux avec plusieurs lignes (ex: critères d'évaluation, notes par UE), utilisez des placeholders dans un tableau Word.
            </p>
            
            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-4">
                <p class="font-semibold text-blue-800 mb-2">Exemple : Tableau des critères d'évaluation</p>
                <p class="text-sm text-blue-900 mb-3">Dans Word, créez un tableau avec une ligne de données contenant :</p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-2 mb-3">
                    <div class="code-box text-center">
                        <code>${'{lib_critere}'}</code>
                    </div>
                    <div class="code-box text-center">
                        <code>${'{bareme}'}</code>
                    </div>
                    <div class="code-box text-center">
                        <code>${'{note}'}</code>
                    </div>
                </div>
                <p class="text-sm text-blue-900">
                    Le système dupliquera automatiquement cette ligne pour chaque critère d'évaluation.
                </p>
            </div>
            
            <div>
                <h3 class="font-semibold text-gray-700 mb-2 text-sm uppercase">Variables disponibles pour les tableaux</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <p class="font-medium text-gray-700 mb-2">Critères d'évaluation :</p>
                        <div class="placeholder-item">
                            <code class="text-purple-600">${'{lib_critere}'}</code>
                            <p class="text-sm text-gray-600">Libellé du critère</p>
                        </div>
                        <div class="placeholder-item">
                            <code class="text-purple-600">${'{bareme}'}</code>
                            <p class="text-sm text-gray-600">Barème du critère</p>
                        </div>
                        <div class="placeholder-item">
                            <code class="text-purple-600">${'{note}'}</code>
                            <p class="text-sm text-gray-600">Note obtenue</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Usage -->
        <div class="section-card">
            <h2 class="text-xl font-bold text-gray-800 mb-4">
                <i class="fas fa-rocket text-orange-600 mr-2"></i>
                Conseils et Bonnes Pratiques
            </h2>
            <ul class="space-y-3">
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                    <div>
                        <p class="font-semibold text-gray-700">Utilisez des noms de placeholders clairs</p>
                        <p class="text-sm text-gray-600">Préférez <code class="bg-gray-200 px-1 rounded">${'{nom_etudiant}'}</code> plutôt que <code class="bg-gray-200 px-1 rounded">${'{n}'}</code></p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                    <div>
                        <p class="font-semibold text-gray-700">Testez vos modèles avec des données réelles</p>
                        <p class="text-sm text-gray-600">Générez un document de test pour vérifier le rendu final</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                    <div>
                        <p class="font-semibold text-gray-700">Conservez une copie de sauvegarde</p>
                        <p class="text-sm text-gray-600">Téléchargez vos modèles avant de les modifier</p>
                    </div>
                </li>
                <li class="flex items-start">
                    <i class="fas fa-check-circle text-green-600 mt-1 mr-3"></i>
                    <div>
                        <p class="font-semibold text-gray-700">Utilisez le formatage Word</p>
                        <p class="text-sm text-gray-600">Les styles, polices et couleurs de Word seront préservés dans le PDF</p>
                    </div>
                </li>
            </ul>
        </div>

    </div>

</body>
</html>

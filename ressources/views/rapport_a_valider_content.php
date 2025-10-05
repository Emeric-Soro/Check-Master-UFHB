<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Évaluation des Rapports | Commission de Validation</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar-hover:hover {
            background-color: #f0fdf4;
            border-left: 4px solid #10b981;
        }
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-overlay {
            background-color: rgba(0, 0, 0, 0.5);
        }
        .evaluation-card {
            transition: all 0.3s ease;
        }
        .evaluation-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .vote-button {
            transition: all 0.2s ease;
        }
        .vote-button:hover {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <div class="hidden md:flex md:flex-shrink-0">
            <div class="flex flex-col w-64 border-r border-gray-200 bg-white">
                <div class="flex items-center justify-center h-16 px-4 bg-green-100 shadow-sm">
                    <div class="flex items-center">
                        <i class="fas fa-users-cog text-green-600 mr-2"></i>
                        <span class="text-green-600 font-bold">Commission</span>
                    </div>
                </div>
                <div class="flex flex-col flex-grow px-4 py-4 overflow-y-auto">
                    <nav class="space-y-1">
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-tachometer-alt mr-3 text-gray-500"></i>
                            Tableau de bord
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-green-700 bg-green-50">
                            <i class="fas fa-clipboard-check mr-3 text-green-500"></i>
                            Rapports à évaluer
                            <span class="ml-auto bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">8</span>
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-check-double mr-3 text-gray-500"></i>
                            Mes évaluations
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-gavel mr-3 text-gray-500"></i>
                            Décisions finales
                        </a>
                        <a href="#" class="flex items-center px-2 py-3 text-sm font-medium rounded-md sidebar-hover text-gray-700 hover:text-gray-900">
                            <i class="fas fa-history mr-3 text-gray-500"></i>
                            Historique
                        </a>
                    </nav>
                    <div class="mt-auto pt-4 border-t border-gray-200">
                        <a href="#" class="flex items-center px-2 py-2 text-sm font-medium rounded-md text-gray-700 hover:text-gray-900 hover:bg-gray-100">
                            <i class="fas fa-sign-out-alt mr-3 text-gray-500"></i>
                            Déconnexion
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <div class="flex flex-col flex-1 overflow-hidden">
            <!-- Top navigation -->
            <div class="flex items-center justify-between h-16 px-4 border-b border-gray-200 bg-white shadow-sm">
                <div class="flex items-center">
                    <button id="mobileMenuButton" class="md:hidden text-gray-500 focus:outline-none mr-3">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-xl font-semibold text-gray-800">Rapports approuvés par Miss Seri</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="relative">
                        <button class="flex items-center space-x-2 focus:outline-none">
                            <img class="w-8 h-8 rounded-full" src="https://randomuser.me/api/portraits/men/32.jpg" alt="User photo">
                            <span class="text-sm font-medium text-gray-700">Dr. Kouassi</span>
                            <i class="fas fa-chevron-down text-gray-500 text-xs"></i>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Main content area -->
            <div class="flex-1 p-4 md:p-6 overflow-y-auto bg-gray-50">
                <div class="max-w-7xl mx-auto">
                    <!-- Stats -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-blue-100 text-blue-600 mr-4">
                                    <i class="fas fa-file-alt text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">À évaluer</p>
                                    <p class="text-2xl font-semibold text-gray-800">8</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-green-100 text-green-600 mr-4">
                                    <i class="fas fa-check text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Mes évaluations</p>
                                    <p class="text-2xl font-semibold text-gray-800">12</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 mr-4">
                                    <i class="fas fa-clock text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">En attente décision</p>
                                    <p class="text-2xl font-semibold text-gray-800">3</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="bg-white rounded-lg shadow p-6 fade-in">
                            <div class="flex items-center">
                                <div class="p-3 rounded-full bg-purple-100 text-purple-600 mr-4">
                                    <i class="fas fa-gavel text-lg"></i>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-500">Décisions finales</p>
                                    <p class="text-2xl font-semibold text-gray-800">45</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reports List -->
                    <div class="bg-white rounded-lg shadow overflow-hidden">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <div class="flex items-center justify-between">
                                <h2 class="text-lg font-semibold text-gray-800">
                                    <i class="fas fa-clipboard-check text-green-600 mr-2"></i>
                                    Rapports approuvés - En attente d'évaluation
                                </h2>
                                <div class="flex items-center space-x-2">
                                    <select class="text-sm border-gray-300 rounded-md">
                                        <option>Tous les rapports</option>
                                        <option>Non évalués</option>
                                        <option>Évalués</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <!-- Report 1 -->
                            <div class="px-6 py-4 evaluation-card hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center flex-1">
                                        <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                                            <i class="fas fa-file-alt text-green-600"></i>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900">Intelligence Artificielle dans le Diagnostic Médical</p>
                                                <div class="flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>Approuvé par Miss Seri
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-blue-100 text-blue-800">
                                                        Vote en cours
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-500">Étudiant: Marie Lambert • Encadrant: Dr. Martin</p>
                                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                                <span>Approuvé le 20/05/2025</span>
                                                <span class="mx-2">•</span>
                                                <span class="text-green-600">2 validations</span>
                                                <span class="mx-2">•</span>
                                                <span class="text-red-600">0 rejets</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="viewReport(1)" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>Consulter
                                        </button>
                                        <button onclick="openEvaluationModal(1)" class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors">
                                            <i class="fas fa-vote-yea mr-1"></i>Voter
                                        </button>
                                        <button onclick="viewEvaluations(1)" class="px-3 py-1 text-sm bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 transition-colors">
                                            <i class="fas fa-users mr-1"></i>Votes
                                        </button>
                                        <button onclick="makeFinalDecision(1)" class="px-3 py-1 text-sm bg-orange-100 text-orange-700 rounded-md hover:bg-orange-200 transition-colors" disabled>
                                            <i class="fas fa-gavel mr-1"></i>Finaliser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Report 2 -->
                            <div class="px-6 py-4 evaluation-card hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center flex-1">
                                        <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                                            <i class="fas fa-file-alt text-green-600"></i>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900">Système de Gestion des Ressources Humaines</p>
                                                <div class="flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>Approuvé par Miss Seri
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                        Prêt à finaliser
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-500">Étudiant: Jean Dupont • Encadrant: Dr. Dubois</p>
                                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                                <span>Approuvé le 18/05/2025</span>
                                                <span class="mx-2">•</span>
                                                <span class="text-green-600">4 validations</span>
                                                <span class="mx-2">•</span>
                                                <span class="text-red-600">0 rejets</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="viewReport(2)" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>Consulter
                                        </button>
                                        <button onclick="viewEvaluations(2)" class="px-3 py-1 text-sm bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 transition-colors">
                                            <i class="fas fa-users mr-1"></i>Votes
                                        </button>
                                        <button onclick="makeFinalDecision(2)" class="px-3 py-1 text-sm bg-green-100 text-green-700 rounded-md hover:bg-green-200 transition-colors">
                                            <i class="fas fa-gavel mr-1"></i>Finaliser
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Report 3 -->
                            <div class="px-6 py-4 evaluation-card hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center flex-1">
                                        <div class="flex-shrink-0 bg-green-100 p-3 rounded-lg">
                                            <i class="fas fa-file-alt text-green-600"></i>
                                        </div>
                                        <div class="ml-4 flex-1">
                                            <div class="flex items-center justify-between">
                                                <p class="text-sm font-medium text-gray-900">Application Mobile de Commerce Électronique</p>
                                                <div class="flex items-center space-x-2">
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                                        <i class="fas fa-check-circle mr-1"></i>Approuvé par Miss Seri
                                                    </span>
                                                    <span class="px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">
                                                        Rejeté par commission
                                                    </span>
                                                </div>
                                            </div>
                                            <p class="text-sm text-gray-500">Étudiant: Sophie Martin • Encadrant: Dr. Bernard</p>
                                            <div class="mt-2 flex items-center text-xs text-gray-500">
                                                <span>Approuvé le 15/05/2025</span>
                                                <span class="mx-2">•</span>
                                                <span>Votes: 4/4 membres</span>
                                                <span class="mx-2">•</span>
                                                <span class="text-green-600">2 validations</span>
                                                <span class="mx-2">•</span>
                                                <span class="text-red-600">2 rejets</span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <button onclick="viewReport(3)" class="px-3 py-1 text-sm bg-blue-100 text-blue-700 rounded-md hover:bg-blue-200 transition-colors">
                                            <i class="fas fa-eye mr-1"></i>Consulter
                                        </button>
                                        <button onclick="viewEvaluations(3)" class="px-3 py-1 text-sm bg-purple-100 text-purple-700 rounded-md hover:bg-purple-200 transition-colors">
                                            <i class="fas fa-users mr-1"></i>Votes
                                        </button>
                                        <button onclick="makeFinalDecision(3)" class="px-3 py-1 text-sm bg-red-100 text-red-700 rounded-md hover:bg-red-200 transition-colors">
                                            <i class="fas fa-gavel mr-1"></i>Finaliser
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal d'évaluation -->
    <div id="evaluationModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity modal-overlay" onclick="closeEvaluationModal()"></div>
            
            <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-star text-yellow-600 mr-2"></i>
                        Évaluation du rapport
                    </h3>
                    <button onclick="closeEvaluationModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Informations du rapport -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800 mb-2">Informations du rapport</h4>
                            <div class="space-y-2 text-sm text-gray-600">
                                <p><strong>Titre:</strong> Intelligence Artificielle dans le Diagnostic Médical</p>
                                <p><strong>Étudiant:</strong> Marie Lambert</p>
                                <p><strong>Encadrant:</strong> Dr. Martin</p>
                                <p><strong>Date d'approbation:</strong> 20/05/2025</p>
                            </div>
                        </div>

                        <!-- Évaluations des autres membres -->
                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800 mb-3">Évaluations des autres membres</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-2 bg-white rounded">
                                    <span class="text-sm font-medium">Dr. Koné</span>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                        <i class="fas fa-check mr-1"></i>Validé
                                    </span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white rounded">
                                    <span class="text-sm font-medium">Pr. Assan</span>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">
                                        <i class="fas fa-check mr-1"></i>Validé
                                    </span>
                                </div>
                                <div class="flex items-center justify-between p-2 bg-white rounded">
                                    <span class="text-sm font-medium">Dr. Bamba</span>
                                    <span class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded-full">
                                        <i class="fas fa-clock mr-1"></i>En attente
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Formulaire d'évaluation -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Votre commentaire / Avis
                            </label>
                            <textarea id="evaluationComment" rows="6" class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="Veuillez donner votre avis détaillé sur ce rapport..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Votre décision
                            </label>
                            <div class="space-y-2">
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-green-50 cursor-pointer">
                                    <input type="radio" name="decision" value="valider" class="text-green-600 focus:ring-green-500">
                                    <span class="ml-3 flex items-center text-green-700">
                                        <i class="fas fa-check-circle mr-2"></i>
                                        Valider le rapport
                                    </span>
                                </label>
                                <label class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-red-50 cursor-pointer">
                                    <input type="radio" name="decision" value="rejeter" class="text-red-600 focus:ring-red-500">
                                    <span class="ml-3 flex items-center text-red-700">
                                        <i class="fas fa-times-circle mr-2"></i>
                                        Rejeter le rapport
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-4">
                            <button onclick="closeEvaluationModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Annuler
                            </button>
                            <button onclick="submitEvaluation()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md hover:bg-blue-700">
                                <i class="fas fa-paper-plane mr-1"></i>
                                Soumettre l'évaluation
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de décision finale -->
    <div id="finalDecisionModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity modal-overlay" onclick="closeFinalDecisionModal()"></div>
            
            <div class="inline-block w-full max-w-5xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-gavel text-purple-600 mr-2"></i>
                        Décision finale - Blockchain et Sécurité des Données
                    </h3>
                    <button onclick="closeFinalDecisionModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Résumé des évaluations -->
                    <div class="space-y-4">
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800 mb-3">Résumé des évaluations</h4>
                            <div class="space-y-3">
                                <div class="flex items-center justify-between p-3 bg-white rounded border-l-4 border-green-500">
                                    <div>
                                        <p class="font-medium text-sm">Dr. Kouassi</p>
                                        <p class="text-xs text-gray-600">Très bon travail, méthodologie solide</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Validé</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-white rounded border-l-4 border-green-500">
                                    <div>
                                        <p class="font-medium text-sm">Dr. Koné</p>
                                        <p class="text-xs text-gray-600">Innovation intéressante, bien documenté</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Validé</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-white rounded border-l-4 border-green-500">
                                    <div>
                                        <p class="font-medium text-sm">Pr. Assan</p>
                                        <p class="text-xs text-gray-600">Contribution significative au domaine</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-green-100 text-green-800 rounded-full">Validé</span>
                                </div>
                                <div class="flex items-center justify-between p-3 bg-white rounded border-l-4 border-red-500">
                                    <div>
                                        <p class="font-medium text-sm">Dr. Bamba</p>
                                        <p class="text-xs text-gray-600">Quelques lacunes dans l'analyse</p>
                                    </div>
                                    <span class="px-2 py-1 text-xs bg-red-100 text-red-800 rounded-full">Rejeté</span>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg">
                            <h4 class="font-semibold text-gray-800 mb-2">Statistiques</h4>
                            <div class="grid grid-cols-2 gap-4 text-center">
                                <div class="bg-white p-3 rounded">
                                    <p class="text-2xl font-bold text-green-600">3</p>
                                    <p class="text-sm text-gray-600">Validations</p>
                                </div>
                                <div class="bg-white p-3 rounded">
                                    <p class="text-2xl font-bold text-red-600">1</p>
                                    <p class="text-sm text-gray-600">Rejet</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Décision finale -->
                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Commentaire de la décision finale
                            </label>
                            <textarea id="finalComment" rows="4" class="w-full p-3 border border-gray-300 rounded-md focus:ring-2 focus:ring-purple-500 focus:border-purple-500" placeholder="Motivez votre décision finale en tenant compte des évaluations..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-3">
                                Décision finale de la commission
                            </label>
                            <div class="space-y-3">
                                <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:bg-green-50 hover:border-green-300 cursor-pointer transition-all">
                                    <input type="radio" name="finalDecision" value="accepter" class="text-green-600 focus:ring-green-500">
                                    <span class="ml-3 flex items-center text-green-700">
                                        <i class="fas fa-check-circle mr-2 text-lg"></i>
                                        <div>
                                            <p class="font-medium">Accepter définitivement</p>
                                            <p class="text-sm text-gray-600">Le rapport est accepté malgré les réserves</p>
                                        </div>
                                    </span>
                                </label>
                                <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:bg-red-50 hover:border-red-300 cursor-pointer transition-all">
                                    <input type="radio" name="finalDecision" value="rejeter" class="text-red-600 focus:ring-red-500">
                                    <span class="ml-3 flex items-center text-red-700">
                                        <i class="fas fa-times-circle mr-2 text-lg"></i>
                                        <div>
                                            <p class="font-medium">Rejeter définitivement</p>
                                            <p class="text-sm text-gray-600">Le rapport est rejeté en raison des lacunes</p>
                                        </div>
                                    </span>
                                </label>
                                <label class="flex items-center p-4 border-2 border-gray-200 rounded-lg hover:bg-yellow-50 hover:border-yellow-300 cursor-pointer transition-all">
                                    <input type="radio" name="finalDecision" value="revision" class="text-yellow-600 focus:ring-yellow-500">
                                    <span class="ml-3 flex items-center text-yellow-700">
                                        <i class="fas fa-edit mr-2 text-lg"></i>
                                        <div>
                                            <p class="font-medium">Demander une révision</p>
                                            <p class="text-sm text-gray-600">L'étudiant doit corriger les points soulevés</p>
                                        </div>
                                    </span>
                                </label>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 pt-6 border-t">
                            <button onclick="closeFinalDecisionModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                                Annuler
                            </button>
                            <button onclick="submitFinalDecision()" class="px-6 py-2 text-sm font-medium text-white bg-purple-600 border border-transparent rounded-md hover:bg-purple-700">
                                <i class="fas fa-gavel mr-1"></i>
                                Prendre la décision finale
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de consultation des évaluations -->
    <div id="evaluationsModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity modal-overlay" onclick="closeEvaluationsModal()"></div>
            
            <div class="inline-block w-full max-w-4xl p-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-medium text-gray-900">
                        <i class="fas fa-comments text-blue-600 mr-2"></i>
                        Évaluations détaillées - Blockchain et Sécurité des Données
                    </h3>
                    <button onclick="closeEvaluationsModal()" class="text-gray-400 hover:text-gray-600">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="space-y-6">
                    <!-- Évaluation 1 -->
                    <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <img class="w-8 h-8 rounded-full mr-3" src="https://randomuser.me/api/portraits/men/32.jpg" alt="Dr. Kouassi">
                                <div>
                                    <p class="font-medium text-gray-900">Dr. Kouassi</p>
                                    <p class="text-sm text-gray-600">Évalué le 21/05/2025</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full font-medium">
                                <i class="fas fa-check mr-1"></i>Validé
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded border-l-4 border-green-500">
                            <p class="text-sm text-gray-700">
                                "Excellent travail sur l'implémentation de la blockchain pour la sécurité des données. La méthodologie est rigoureuse et les résultats sont probants. L'étudiant démontre une bonne compréhension des enjeux de sécurité. Je recommande vivement la validation de ce rapport."
                            </p>
                        </div>
                    </div>

                    <!-- Évaluation 2 -->
                    <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <img class="w-8 h-8 rounded-full mr-3" src="https://randomuser.me/api/portraits/men/45.jpg" alt="Dr. Koné">
                                <div>
                                    <p class="font-medium text-gray-900">Dr. Koné</p>
                                    <p class="text-sm text-gray-600">Évalué le 22/05/2025</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full font-medium">
                                <i class="fas fa-check mr-1"></i>Validé
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded border-l-4 border-green-500">
                            <p class="text-sm text-gray-700">
                                "Approche innovante et bien documentée. L'étudiant a su intégrer les concepts théoriques avec une application pratique pertinente. Quelques améliorations mineures pourraient être apportées à la présentation, mais le fond est solide. Validation recommandée."
                            </p>
                        </div>
                    </div>

                    <!-- Évaluation 3 -->
                    <div class="border border-green-200 rounded-lg p-4 bg-green-50">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <img class="w-8 h-8 rounded-full mr-3" src="https://randomuser.me/api/portraits/women/68.jpg" alt="Pr. Assan">
                                <div>
                                    <p class="font-medium text-gray-900">Pr. Assan</p>
                                    <p class="text-sm text-gray-600">Évalué le 22/05/2025</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-sm bg-green-100 text-green-800 rounded-full font-medium">
                                <i class="fas fa-check mr-1"></i>Validé
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded border-l-4 border-green-500">
                            <p class="text-sm text-gray-700">
                                "Ce travail représente une contribution significative au domaine de la sécurité informatique. L'analyse comparative des différentes approches blockchain est particulièrement appréciable. L'étudiant montre une maturité scientifique remarquable."
                            </p>
                        </div>
                    </div>

                    <!-- Évaluation 4 -->
                    <div class="border border-red-200 rounded-lg p-4 bg-red-50">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center">
                                <img class="w-8 h-8 rounded-full mr-3" src="https://randomuser.me/api/portraits/men/78.jpg" alt="Dr. Bamba">
                                <div>
                                    <p class="font-medium text-gray-900">Dr. Bamba</p>
                                    <p class="text-sm text-gray-600">Évalué le 23/05/2025</p>
                                </div>
                            </div>
                            <span class="px-3 py-1 text-sm bg-red-100 text-red-800 rounded-full font-medium">
                                <i class="fas fa-times mr-1"></i>Rejeté
                            </span>
                        </div>
                        <div class="bg-white p-3 rounded border-l-4 border-red-500">
                            <p class="text-sm text-gray-700">
                                "Bien que le sujet soit intéressant, je note quelques lacunes dans l'analyse de sécurité. Les tests de performance ne sont pas assez approfondis et certaines vulnérabilités potentielles ne sont pas suffisamment abordées. Une révision serait souhaitable avant validation."
                            </p>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-6 border-t">
                    <button onclick="closeEvaluationsModal()" class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                        Fermer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentReportId = null;

        // Fonctions d'ouverture/fermeture des modales
        function openEvaluationModal(reportId) {
            currentReportId = reportId;
            document.getElementById('evaluationModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeEvaluationModal() {
            document.getElementById('evaluationModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentReportId = null;
        }

        function makeFinalDecision(reportId) {
            currentReportId = reportId;
            document.getElementById('finalDecisionModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeFinalDecisionModal() {
            document.getElementById('finalDecisionModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentReportId = null;
        }

        function viewEvaluations(reportId) {
            currentReportId = reportId;
            document.getElementById('evaluationsModal').classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeEvaluationsModal() {
            document.getElementById('evaluationsModal').classList.add('hidden');
            document.body.style.overflow = 'auto';
            currentReportId = null;
        }

        // Fonction pour consulter un rapport
        function viewReport(reportId) {
            // Simulation d'ouverture du rapport
            alert(`Ouverture du rapport ${reportId} en mode consultation...`);
        }

        // Fonction pour soumettre une évaluation
        function submitEvaluation() {
            const comment = document.getElementById('evaluationComment').value;
            const decision = document.querySelector('input[name="decision"]:checked');
            
            if (!comment.trim()) {
                alert('Veuillez saisir un commentaire avant de soumettre votre évaluation.');
                return;
            }
            
            if (!decision) {
                alert('Veuillez sélectionner une décision (Valider ou Rejeter).');
                return;
            }

            // Créer le FormData pour l'envoi AJAX
            const formData = new FormData();
            formData.append('action', 'traiter_decision');
            formData.append('id_rapport', currentReportId);
            formData.append('decision', decision.value);
            formData.append('commentaire', comment);

            // Envoyer la requête AJAX
            fetch('?page=evaluations_dossiers_soutenance', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Votre évaluation a été enregistrée avec succès!');
                    
                    // Fermer la modale et réinitialiser le formulaire
                    closeEvaluationModal();
                    document.getElementById('evaluationComment').value = '';
                    document.querySelector('input[name="decision"]:checked').checked = false;
                    
                    // Recharger la page pour mettre à jour l'affichage
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    alert('Erreur: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                alert('Erreur lors de l\'envoi de l\'évaluation');
            });
        }

        // Fonction pour soumettre la décision finale
        function submitFinalDecision() {
            const comment = document.getElementById('finalComment').value;
            
            if (!comment.trim()) {
                alert('Veuillez saisir un commentaire pour motiver votre décision finale.');
                return;
            }

            if (confirm('Êtes-vous sûr de vouloir finaliser la décision de la commission?\nCette action est irréversible.')) {
                // Créer le FormData pour l'envoi AJAX
                const formData = new FormData();
                formData.append('action', 'finaliser_decision');
                formData.append('id_rapport', currentReportId);
                formData.append('commentaire', comment);

                // Envoyer la requête AJAX
                fetch('?page=evaluations_dossiers_soutenance', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Décision finale enregistrée avec succès!');
                        
                        closeFinalDecisionModal();
                        document.getElementById('finalComment').value = '';
                        
                        // Recharger la page pour mettre à jour l'affichage
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    } else {
                        alert('Erreur: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Erreur:', error);
                    alert('Erreur lors de la finalisation de la décision');
                });
            }
        }

        // Gestion du menu mobile
        document.addEventListener('DOMContentLoaded', function() {
            const mobileMenuButton = document.getElementById('mobileMenuButton');
            const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0 > .flex.flex-col.w-64');

            if (mobileMenuButton && sidebar) {
                mobileMenuButton.addEventListener('click', function() {
                    sidebar.classList.toggle('hidden');
                    sidebar.classList.toggle('absolute');
                    sidebar.classList.toggle('z-20');
                });
            }

            // Fermeture des modales avec Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeEvaluationModal();
                    closeFinalDecisionModal();
                    closeEvaluationsModal();
                }
            });
        });

        // Animations au chargement
        window.addEventListener('load', function() {
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
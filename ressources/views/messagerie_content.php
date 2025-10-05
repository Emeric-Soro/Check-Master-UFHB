<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messagerie Étudiante</title>
    <script>
    tailwind.config = {
        theme: {
            extend: {
                colors: {
                    primary: {
                        50: '#f0fdf4',
                        100: '#dcfce7',
                        200: '#bbf7d0',
                        300: '#86efac',
                        400: '#4ade80',
                        500: '#22c55e',
                        600: '#16a34a',
                        700: '#15803d',
                        800: '#166534',
                        900: '#14532d',
                    },
                    secondary: {
                        50: '#eff6ff',
                        100: '#dbeafe',
                        200: '#bfdbfe',
                        300: '#93c5fd',
                        400: '#60a5fa',
                        500: '#3b82f6',
                        600: '#2563eb',
                        700: '#1d4ed8',
                        800: '#1e40af',
                        900: '#1e3a8a',
                    },
                    accent: {
                        400: '#f59e0b',
                        500: '#f97316',
                        600: '#ea580c',
                    }
                }
            }
        }
    }
    </script>
    <style>
    .message-unread {
        border-left: 4px solid #3b82f6;
        background-color: #f8fafc;
    }

    .message-read {
        opacity: 0.9;
    }
    </style>
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="flex flex-col h-screen">
        <!-- Header -->
        <header class="bg-green-600 text-white shadow-md">
            <div class="container mx-auto px-4 py-4 flex justify-between items-center">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-envelope text-2xl"></i>
                    <h1 class="text-xl font-bold">Messagerie Universitaire</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="bg-green-500 px-3 py-1 rounded-full text-sm">Jean Dupont</span>
                    <button class="bg-white text-green-600 p-2 rounded-full hover:bg-green-50 transition">
                        <i class="fas fa-cog"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <div class="flex flex-1 overflow-hidden">
            <!-- Sidebar -->
            <div class="w-64 bg-white border-r border-gray-200 flex flex-col">
                <!-- Composer Button -->
                <button id="composeBtn"
                    class="mx-4 my-4 bg-green-600 hover:bg-green-700 text-white py-3 px-4 rounded-lg font-medium flex items-center justify-center transition">
                    <i class="fas fa-plus mr-2"></i> Nouveau message
                </button>

                <!-- Folders -->
                <nav class="flex-1 overflow-y-auto">
                    <ul>
                        <li>
                            <a href="#"
                                class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100 border-l-4 border-green-500 bg-green-50">
                                <i class="fas fa-inbox mr-3 text-green-500"></i>
                                <span>Boîte de réception</span>
                                <span class="ml-auto bg-green-500 text-white text-xs px-2 py-1 rounded-full">12</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-paper-plane mr-3 text-blue-500"></i>
                                <span>Envoyés</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-trash mr-3 text-gray-500"></i>
                                <span>Corbeille</span>
                            </a>
                        </li>
                        <li>
                            <a href="#" class="flex items-center px-4 py-3 text-gray-700 hover:bg-gray-100">
                                <i class="fas fa-star mr-3 text-amber-400"></i>
                                <span>Favoris</span>
                            </a>
                        </li>
                    </ul>

                    <!-- Labels -->
                    <div class="px-4 py-2 mt-4">
                        <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Étiquettes</h3>
                        <ul class="mt-2">
                            <li class="mb-1">
                                <a href="#"
                                    class="flex items-center px-2 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    <span class="w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                    <span>Université</span>
                                </a>
                            </li>
                            <li class="mb-1">
                                <a href="#"
                                    class="flex items-center px-2 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    <span class="w-3 h-3 bg-blue-500 rounded-full mr-2"></span>
                                    <span>Professeurs</span>
                                </a>
                            </li>
                            <li class="mb-1">
                                <a href="#"
                                    class="flex items-center px-2 py-1 text-sm text-gray-700 hover:bg-gray-100 rounded">
                                    <span class="w-3 h-3 bg-purple-500 rounded-full mr-2"></span>
                                    <span>Cours</span>
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>
            </div>

            <!-- Message List -->
            <div class="flex-1 flex flex-col bg-white border-r border-gray-200 w-96 overflow-hidden">
                <!-- Search Bar -->
                <div class="border-b border-gray-200 px-4 py-3">
                    <div class="relative">
                        <input type="text" placeholder="Rechercher des messages..."
                            class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                        <i class="fas fa-search absolute left-3 top-3 text-gray-400"></i>
                    </div>
                </div>

                <!-- Message List -->
                <div class="flex-1 overflow-y-auto">
                    <!-- Message Item -->
                    <div class="message-unread border-b border-gray-200 px-4 py-3 hover:bg-gray-50 cursor-pointer ">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-1">
                                <div
                                    class="bg-blue-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
                                    <span>JD</span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">Prof. Martin</p>
                                    <time class="text-xs text-gray-500">15:30</time>
                                </div>
                                <p class="text-sm text-gray-900 font-semibold truncate">Projet d'algorithme</p>
                                <p class="text-sm text-gray-500 truncate">Votre rendu de projet a été reçu, merci. Nous
                                    ferons un retour la semaine prochaine...</p>
                                <div class="mt-1 flex space-x-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                                        Cours
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Item -->
                    <div class="message-unread border-b border-gray-200 px-4 py-3 hover:bg-gray-50 cursor-pointer ">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-1">
                                <div
                                    class="bg-green-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
                                    <span>SC</span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">Secrétariat</p>
                                    <time class="text-xs text-gray-500">Hier</time>
                                </div>
                                <p class="text-sm text-gray-900 font-semibold truncate">Convocation commission</p>
                                <p class="text-sm text-gray-500 truncate">Vous êtes convoqué(e) devant la commission
                                    pédagogique le 15 novembre à 14h...</p>
                                <div class="mt-1 flex space-x-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800">
                                        Important
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Message Item (Read) -->
                    <div class="message-read border-b border-gray-200 px-4 py-3 hover:bg-gray-50 cursor-pointer ">
                        <div class="flex items-start">
                            <div class="flex-shrink-0 pt-1">
                                <div
                                    class="bg-amber-500 text-white rounded-full w-8 h-8 flex items-center justify-center">
                                    <span>AL</span>
                                </div>
                            </div>
                            <div class="ml-3 flex-1 min-w-0">
                                <div class="flex justify-between">
                                    <p class="text-sm font-medium text-gray-900 truncate">Alexandre Leroy</p>
                                    <time class="text-xs text-gray-500">Lundi</time>
                                </div>
                                <p class="text-sm text-gray-900 font-semibold truncate">Réunion projet</p>
                                <p class="text-sm text-gray-500 truncate">Salut Jean, tu es disponible demain pour
                                    avancer sur le projet de base de données...</p>
                                <div class="mt-1 flex space-x-2">
                                    <span
                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-purple-100 text-purple-800">
                                        Groupe
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- More messages... -->
                </div>
            </div>

            <!-- Message View -->
            <div class="flex-1 flex flex-col bg-white overflow-hidden">
                <!-- Message Toolbar -->
                <div class="border-b border-gray-200 px-4 py-3 flex items-center justify-between bg-gray-50">
                    <div class="flex space-x-2">
                        <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition">
                            <i class="fas fa-reply"></i>
                        </button>
                        <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition">
                            <i class="fas fa-reply-all"></i>
                        </button>
                        <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition">
                            <i class="fas fa-share"></i>
                        </button>
                        <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition">
                            <i class="fas fa-trash"></i>
                        </button>
                        <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition">
                            <i class="fas fa-archive"></i>
                        </button>
                    </div>
                    <div class="flex space-x-2">
                        <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition">
                            <i class="fas fa-tag"></i>
                        </button>
                        <button class="p-2 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-full transition">
                            <i class="fas fa-ellipsis-v"></i>
                        </button>
                    </div>
                </div>

                <!-- Message Content -->
                <div class="flex-1 overflow-y-auto p-6">
                    <div class="max-w-4xl mx-auto">
                        <!-- Message Header -->
                        <div class="border-b border-gray-200 pb-4">
                            <h2 class="text-2xl font-bold text-gray-900 mb-4">Projet d'algorithme - Rendus attendus</h2>
                            <div class="flex items-start justify-between">
                                <div class="flex items-center">
                                    <div
                                        class="bg-blue-500 text-white rounded-full w-10 h-10 flex items-center justify-center mr-3">
                                        <span>PM</span>
                                    </div>
                                    <div>
                                        <p class="font-medium text-gray-900">Prof. Martin</p>
                                        <p class="text-sm text-gray-500">martin@universite.fr</p>
                                    </div>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <time datetime="2023-11-10">10 Nov. 2023, 15:30</time>
                                    <button class="ml-2 text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-ellipsis-h"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Message Body -->
                        <div class="py-6 text-gray-800">
                            <p class="mb-4">Bonjour Jean,</p>
                            <p class="mb-4">Je vous informe que votre rendu de projet pour le cours d'algorithmique
                                avancée a bien été reçu. La qualité générale est satisfaisante, mais j'ai quelques
                                remarques :</p>

                            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-4">
                                <p class="font-medium text-blue-800 mb-2">Points à améliorer :</p>
                                <ul class="list-disc pl-5 text-blue-700">
                                    <li class="mb-1">Optimisation de la fonction de tri (chapitre 4)</li>
                                    <li class="mb-1">Documentation des méthodes complexes</li>
                                    <li>Tests unitaires plus complets</li>
                                </ul>
                            </div>

                            <p class="mb-4">Nous en discuterons plus en détail lors de notre prochaine séance. En
                                attendant, vous pouvez consulter les ressources complémentaires sur la plateforme.</p>
                            <p class="mb-4">Cordialement,</p>
                            <p>Prof. Martin</p>
                        </div>

                        <!-- Attachments -->
                        <div class="border-t border-gray-200 pt-4 mb-6">
                            <h3 class="text-sm font-medium text-gray-700 mb-3">Pièces jointes (2)</h3>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                    <div class="bg-blue-100 p-3 rounded-lg mr-3">
                                        <i class="fas fa-file-pdf text-blue-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 truncate">Correction_projet.pdf</p>
                                        <p class="text-xs text-gray-500">2.4 MB</p>
                                    </div>
                                    <button class="ml-auto text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                                <div class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50">
                                    <div class="bg-green-100 p-3 rounded-lg mr-3">
                                        <i class="fas fa-file-code text-green-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-900 truncate">Exemples_algo.zip</p>
                                        <p class="text-xs text-gray-500">1.1 MB</p>
                                    </div>
                                    <button class="ml-auto text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Reply Area -->
                        <div class="border-t border-gray-200 pt-6">
                            <div class="mb-4">
                                <button class="text-blue-600 hover:text-blue-800 font-medium mr-4">
                                    <i class="fas fa-reply mr-1"></i> Répondre
                                </button>
                                <button class="text-blue-600 hover:text-blue-800 font-medium">
                                    <i class="fas fa-reply-all mr-1"></i> Répondre à tous
                                </button>
                            </div>
                            <div class="border border-gray-300 rounded-lg overflow-hidden">
                                <div class="bg-gray-50 px-4 py-2 border-b border-gray-300">
                                    <p class="text-sm text-gray-600">Réponse à Prof. Martin</p>
                                </div>
                                <textarea class="w-full px-4 py-3 focus:outline-none" rows="5"
                                    placeholder="Écrivez votre réponse ici..."></textarea>
                                <div
                                    class="bg-gray-50 px-4 py-2 border-t border-gray-300 flex justify-between items-center">
                                    <div class="flex space-x-2">
                                        <button class="text-gray-500 hover:text-gray-700 p-1">
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <button class="text-gray-500 hover:text-gray-700 p-1">
                                            <i class="fas fa-image"></i>
                                        </button>
                                    </div>
                                    <button
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                                        Envoyer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compose Modal -->
    <div id="composeModal" class="fixed inset-0 bg-opacity-50 flex items-center justify-center hidden z-50">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl ">
            <div class="border-b border-gray-200 px-4 py-3 flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Nouveau message</h3>
                <button id="closeCompose" class="text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="p-4">
                <div class="space-y-4">
                    <div class="flex items-center border-b border-gray-300 py-2">
                        <span class="text-gray-500 w-20">À :</span>
                        <input type="text" class="flex-1 focus:outline-none"
                            placeholder="Saisissez une ou plusieurs adresses">
                    </div>
                    <div class="flex items-center border-b border-gray-300 py-2">
                        <span class="text-gray-500 w-20">Cc :</span>
                        <input type="text" class="flex-1 focus:outline-none">
                    </div>
                    <div class="flex items-center border-b border-gray-300 py-2">
                        <span class="text-gray-500 w-20">Objet :</span>
                        <input type="text" class="flex-1 focus:outline-none">
                    </div>
                    <textarea
                        class="w-full border border-gray-300 rounded-lg p-3 focus:outline-none focus:ring-2 focus:ring-green-500"
                        rows="10" placeholder="Écrivez votre message ici..."></textarea>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 flex justify-between items-center border-t border-gray-200">
                <div class="flex space-x-2">
                    <button class="text-gray-500 hover:text-gray-700 p-2">
                        <i class="fas fa-paperclip"></i>
                    </button>
                    <button class="text-gray-500 hover:text-gray-700 p-2">
                        <i class="fas fa-image"></i>
                    </button>
                </div>
                <div class="flex space-x-2">
                    <button class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-100">
                        Annuler
                    </button>
                    <button class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg font-medium">
                        Envoyer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Gestion de la modale de composition
        const composeBtn = document.getElementById('composeBtn');
        const composeModal = document.getElementById('composeModal');
        const closeCompose = document.getElementById('closeCompose');

        composeBtn.addEventListener('click', () => {
            composeModal.classList.remove('hidden');
        });

        closeCompose.addEventListener('click', () => {
            composeModal.classList.add('hidden');
        });

        // Marquer les messages comme lus
        const messageItems = document.querySelectorAll('.message-unread');
        messageItems.forEach(item => {
            item.addEventListener('click', function() {
                this.classList.remove('message-unread');
                this.classList.add('message-read');
                this.style.borderLeft = 'none';
                this.style.backgroundColor = '';
            });
        });

        // Animation des messages
        const messages = document.querySelectorAll('.animate-fade-in');
        messages.forEach((message, index) => {
            message.style.animationDelay = `${index * 0.1}s`;
        });
    });
    </script>
</body>

</html>
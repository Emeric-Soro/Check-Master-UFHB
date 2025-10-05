<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendrier des Soutenances | Comité de Validation</title>
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
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .calendar-day:hover {
        background-color: #f0fdf4;
        transform: scale(1.03);
    }

    .calendar-day.today {
        border: 2px solid #10b981;
    }

    .event-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    </style>
</head>

<body class="font-sans antialiased bg-gray-50">
    <div class="flex h-screen overflow-hidden">


        <!-- Main content area -->
        <div class="flex-1 p-4 md:p-6 overflow-y-auto bg-gray-50">
            <div class="max-w-7xl mx-auto">
                <!-- Calendar Controls -->
                <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
                    <div class="flex items-center space-x-4 mb-4 md:mb-0">
                        <button
                            class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <i class="fas fa-chevron-left mr-2"></i> Mois précédent
                        </button>
                        <h2 class="text-xl font-bold text-gray-800">Juin 2023</h2>
                        <button
                            class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Mois suivant <i class="fas fa-chevron-right ml-2"></i>
                        </button>
                    </div>
                    <div class="flex space-x-2">
                        <button
                            class="px-4 py-2 bg-green-600 text-white rounded-md shadow-sm text-sm font-medium hover:bg-green-700">
                            <i class="fas fa-plus mr-2"></i> Nouvelle soutenance
                        </button>
                        <div class="relative">
                            <button
                                class="px-4 py-2 bg-white border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-filter mr-2"></i> Filtres
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="bg-white rounded-lg shadow overflow-hidden fade-in">
                    <!-- Weekday Headers -->
                    <div class="grid grid-cols-7 gap-px bg-gray-200">
                        <div
                            class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Dimanche
                        </div>
                        <div
                            class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Lundi
                        </div>
                        <div
                            class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mardi
                        </div>
                        <div
                            class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Mercredi
                        </div>
                        <div
                            class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Jeudi
                        </div>
                        <div
                            class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Vendredi
                        </div>
                        <div
                            class="bg-gray-100 py-2 text-center text-xs font-medium text-gray-500 uppercase tracking-wider">
                            Samedi
                        </div>
                    </div>

                    <!-- Calendar Grid -->
                    <div class="grid grid-cols-7 gap-px bg-gray-200">
                        <!-- Empty days (May) -->
                        <div class="bg-gray-50 h-24"></div>
                        <div class="bg-gray-50 h-24"></div>
                        <div class="bg-gray-50 h-24"></div>
                        <div class="bg-gray-50 h-24"></div>

                        <!-- June Days -->
                        <!-- Week 1 -->
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">1</div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <!-- Event -->
                                <div class="bg-blue-50 rounded p-1 truncate">
                                    <span class="font-medium">10h00</span> Soutenance IA
                                </div>
                            </div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">2</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">3</div>
                        </div>

                        <!-- Week 2 -->
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">4</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">5</div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <div class="bg-purple-50 rounded p-1 truncate">
                                    <span class="font-medium">14h30</span> Réunion comité
                                </div>
                            </div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">6</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">7</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">8</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">9</div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <div class="bg-green-50 rounded p-1 truncate">
                                    <span class="font-medium">09h00</span> Soutenance Bio
                                </div>
                            </div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">10</div>
                        </div>

                        <!-- Week 3 -->
                        <div class="bg-white h-24 p-1 calendar-day today">
                            <div class="text-right text-sm p-1">
                                <span
                                    class="bg-green-100 text-green-800 rounded-full w-6 h-6 inline-flex items-center justify-center">11</span>
                            </div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <div class="bg-red-50 rounded p-1 truncate">
                                    <span class="font-medium">11h00</span> Soutenance Chimie
                                </div>
                                <div class="bg-yellow-50 rounded p-1 truncate">
                                    <span class="font-medium">15h00</span> Soutenance Physique
                                </div>
                            </div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">12</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">13</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">14</div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <div class="bg-blue-50 rounded p-1 truncate">
                                    <span class="font-medium">16h00</span> Soutenance Maths
                                </div>
                            </div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">15</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">16</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">17</div>
                        </div>

                        <!-- Week 4 -->
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">18</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">19</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">20</div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <div class="bg-purple-50 rounded p-1 truncate">
                                    <span class="font-medium">10h00</span> Réunion comité
                                </div>
                            </div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">21</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">22</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">23</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">24</div>
                        </div>

                        <!-- Week 5 -->
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">25</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">26</div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <div class="bg-green-50 rounded p-1 truncate">
                                    <span class="font-medium">14h00</span> Soutenance Info
                                </div>
                            </div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">27</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">28</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">29</div>
                        </div>
                        <div class="bg-white h-24 p-1 calendar-day">
                            <div class="text-right text-sm p-1">30</div>
                            <div class="overflow-y-auto h-16 text-xs space-y-1">
                                <div class="bg-yellow-50 rounded p-1 truncate">
                                    <span class="font-medium">09h30</span> Soutenance Eco
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Upcoming Events -->
                <div class="mt-8 fade-in" style="animation-delay: 0.2s;">
                    <h2 class="text-lg font-semibold text-gray-800 mb-4">
                        <i class="fas fa-list-ul text-green-600 mr-2"></i>
                        Prochaines soutenances
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                        <!-- Event 1 -->
                        <div
                            class="bg-white rounded-lg shadow overflow-hidden event-card transition duration-150 ease-in-out">
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 bg-green-100 p-3 rounded-full">
                                        <i class="fas fa-graduation-cap text-green-600"></i>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-medium text-gray-900">Soutenance de Thèse</h3>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                Confirmée
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">Jean Dupont</p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <span>15 juin 2023 • 14h00</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="far fa-building mr-1"></i>
                                            <span>Salle B204</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between">
                                    <button class="text-sm text-green-600 hover:text-green-800 font-medium">
                                        <i class="fas fa-eye mr-1"></i> Détails
                                    </button>
                                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="fas fa-calendar-plus mr-1"></i> Ajouter à mon agenda
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Event 2 -->
                        <div
                            class="bg-white rounded-lg shadow overflow-hidden event-card transition duration-150 ease-in-out">
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 bg-blue-100 p-3 rounded-full">
                                        <i class="fas fa-graduation-cap text-blue-600"></i>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-medium text-gray-900">Soutenance de Master</h3>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                En attente
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">Marie Lambert</p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <span>16 juin 2023 • 10h30</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="far fa-building mr-1"></i>
                                            <span>Salle A102</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between">
                                    <button class="text-sm text-green-600 hover:text-green-800 font-medium">
                                        <i class="fas fa-eye mr-1"></i> Détails
                                    </button>
                                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="fas fa-calendar-plus mr-1"></i> Ajouter à mon agenda
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Event 3 -->
                        <div
                            class="bg-white rounded-lg shadow overflow-hidden event-card transition duration-150 ease-in-out">
                            <div class="p-4">
                                <div class="flex items-start">
                                    <div class="flex-shrink-0 bg-purple-100 p-3 rounded-full">
                                        <i class="fas fa-graduation-cap text-purple-600"></i>
                                    </div>
                                    <div class="ml-4 flex-1">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-medium text-gray-900">Soutenance de Doctorat</h3>
                                            <span
                                                class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                                Prioritaire
                                            </span>
                                        </div>
                                        <p class="text-sm text-gray-500 mt-1">Thomas Moreau</p>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="far fa-calendar-alt mr-1"></i>
                                            <span>20 juin 2023 • 09h00</span>
                                        </div>
                                        <div class="mt-2 flex items-center text-sm text-gray-500">
                                            <i class="far fa-building mr-1"></i>
                                            <span>Amphithéâtre Est</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-4 flex justify-between">
                                    <button class="text-sm text-green-600 hover:text-green-800 font-medium">
                                        <i class="fas fa-eye mr-1"></i> Détails
                                    </button>
                                    <button class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                        <i class="fas fa-calendar-plus mr-1"></i> Ajouter à mon agenda
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile menu toggle
        const mobileMenuButton = document.getElementById('mobileMenuButton');
        const sidebar = document.querySelector('.hidden.md\\:flex.md\\:flex-shrink-0 > .flex.flex-col.w-64');

        if (mobileMenuButton && sidebar) {
            mobileMenuButton.addEventListener('click', function() {
                sidebar.classList.toggle('hidden');
                sidebar.classList.toggle('absolute');
                sidebar.classList.toggle('z-20');
            });
        }

        // Highlight today's date
        const today = new Date();
        const todayElement = document.querySelector('.calendar-day.today');
        if (todayElement) {
            todayElement.scrollIntoView({
                behavior: 'smooth',
                block: 'nearest'
            });
        }
    });
    </script>
</body>

</html>
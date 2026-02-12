<?php
$stage_info = isset($GLOBALS['stage_info']) ? $GLOBALS['stage_info'] : [];
$entreprises = isset($GLOBALS['entreprises']) ? $GLOBALS['entreprises'] : [];
?>
<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rédaction de rapport</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --blue: #0F4C75;
            --blue-light: #3282B8;
            --green: #10b981;
            --muted: #64748B
        }

        .text-blue-500 {
            color: var(--blue) !important
        }

        .bg-blue-500 {
            background-color: var(--blue) !important
        }

        .bg-green-500 {
            background-color: var(--green) !important
        }

        .card-btn {
            background: linear-gradient(135deg, var(--blue), var(--blue-light)) !important
        }

        .card {
            background: #ffffff
        }

        .card-icon svg {
            color: var(--blue)
        }

        .bg-red-100 {
            background-color: rgba(15, 76, 117, 0.08)
        }

        .bg-yellow-100 {
            background-color: rgba(16, 185, 129, 0.08)
        }

        .rounded-lg {
            border-radius: 0.5rem
        }

        .shadow-lg {
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1)
        }

        .transition-shadow {
            transition: box-shadow .3s ease
        }

        .text-text-dark {
            color: #0f1720
        }

        .text-text-light {
            color: var(--muted)
        }

        .floating-shape {
            opacity: .07;
            position: absolute;
            z-index: -10
        }

        /* Autocomplete custom styles */
        .autocomplete-wrapper {
            position: relative;
        }

        .autocomplete-suggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            margin-top: 4px;
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            max-height: 240px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
        }

        .autocomplete-suggestions.active {
            display: block;
            animation: slideDown 0.2s ease-out;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .autocomplete-suggestion {
            padding: 12px 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all 0.15s ease;
            border-bottom: 1px solid #f3f4f6;
        }

        .autocomplete-suggestion:last-child {
            border-bottom: none;
        }

        .autocomplete-suggestion:hover,
        .autocomplete-suggestion.selected {
            background: linear-gradient(135deg, rgba(15, 76, 117, 0.08), rgba(50, 130, 184, 0.08));
        }

        .autocomplete-suggestion .icon {
            width: 36px;
            height: 36px;
            border-radius: 0.375rem;
            background: linear-gradient(135deg, #0F4C75, #3282B8);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .autocomplete-suggestion .icon svg {
            width: 20px;
            height: 20px;
            color: white;
        }

        .autocomplete-suggestion .text {
            flex: 1;
            font-size: 14px;
            color: #1f2937;
            font-weight: 500;
        }

        .autocomplete-no-results {
            padding: 16px;
            text-align: center;
            color: #9ca3af;
            font-size: 14px;
        }

        .autocomplete-add-new {
            background: #f9fafb;
            border-top: 2px solid #e5e7eb;
        }

        .autocomplete-add-new .icon {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .autocomplete-add-new .text {
            color: #10b981;
        }

        /* Scrollbar personnalisé */
        .autocomplete-suggestions::-webkit-scrollbar {
            width: 6px;
        }

        .autocomplete-suggestions::-webkit-scrollbar-track {
            background: #f3f4f6;
            border-radius: 0.5rem;
        }

        .autocomplete-suggestions::-webkit-scrollbar-thumb {
            background: #d1d5db;
            border-radius: 0.5rem;
        }

        .autocomplete-suggestions::-webkit-scrollbar-thumb:hover {
            background: #9ca3af;
        }
    </style>
</head>

<body class="min-h-screen" style="background: linear-gradient(135deg, #DFF2FF 0%, #C8E8FF 100%);">
    <div class="floating-shape shape-1"></div>
    <div class="floating-shape shape-2"></div>

    <div class="container max-w-6xl mx-auto px-4 py-4 md:px-4 md:py-3">
        <?php if (isset($_SESSION['success'])): ?>
            <div class="mb-3 p-3 bg-green-100 border border-green-400 text-green-700 rounded text-sm">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="mb-3 p-3 bg-red-100 border border-red-400 text-blue-700 rounded text-sm">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>

        <div id="warningMessage" class="mb-4 p-4 bg-yellow-100 border border-yellow-400 text-blue-700 rounded hidden">
            Veuillez d'abord remplir les informations de stage pour accéder aux autres fonctionnalités.
        </div>

        <div class="header text-center mb-6">
            <h1 class="text-3xl font-bold text-text-dark mb-3 md:text-2xl text-green-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 inline-block mr-2 mb-1" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Candidature à la Soutenance
            </h1>
            <p class="text-base text-text-light max-w-3xl mx-auto leading-relaxed">
                <strong>Bienvenue sur votre espace de candidature.</strong><br>
                Pour pouvoir rédiger votre rapport de stage, veuillez d'abord renseigner les informations ci-dessous
                concernant votre stage en entreprise. Une fois validées, vous serez automatiquement redirigé vers
                l'éditeur de rapport.
            </p>
        </div>

        <div class="bg-white rounded-lg p-6 max-w-5xl mx-auto shadow-xl">
            <div class="flex items-center justify-center mb-4">
                <div class="flex items-center">
                    <span
                        class="flex items-center justify-center w-8 h-8 bg-blue-500 text-white rounded-full font-bold mr-3">1</span>
                    <h3 class="text-xl font-bold text-gray-800">Remplissez vos informations de stage</h3>
                </div>
            </div>
            <p class="text-center text-sm text-gray-600 mb-6">Ces informations seront utilisées pour constituer votre
                dossier de soutenance</p>
            <form id="stageInfoForm" class="space-y-4" method="POST"
                action="?page=candidature_soutenance&action=info_stage">
                <div class="grid grid-cols-3 gap-4">
                    <div class="col-span-3">
                        <label for="entreprise" class="block text-sm font-medium text-gray-700 mb-1">
                            Entreprise
                            <span class="text-xs text-gray-500 font-normal ml-2">(Choisissez ou tapez pour
                                ajouter)</span>
                        </label>
                        <div class="autocomplete-wrapper">
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                        viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                                    </svg>
                                </div>
                                <input type="text" name="entreprise" id="entreprise" required autocomplete="off"
                                    value="<?php echo isset($stage_info['nom_entreprise']) ? htmlspecialchars($stage_info['nom_entreprise']) : ''; ?>"
                                    class="pl-10 block w-full py-2 outline-green-500 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                    placeholder="Tapez le nom de l'entreprise...">
                            </div>
                            <div class="autocomplete-suggestions" id="suggestions-list"></div>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            <svg class="h-3 w-3 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                                    clip-rule="evenodd" />
                            </svg>
                            Si votre entreprise n'est pas dans la liste, tapez son nom et elle sera ajoutée
                            automatiquement
                        </p>
                    </div>

                    <div class="col-span-1">
                        <label for="date_debut" class="block text-sm font-medium text-gray-700 mb-1">Date de
                            début</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="date" name="date_debut" id="date_debut" required
                                max="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo isset($stage_info['date_debut_stage']) ? htmlspecialchars($stage_info['date_debut_stage']) : ''; ?>"
                                class="pl-10 block w-full py-2 outline-green-500 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                        </div>
                    </div>

                    <div class="col-span-1">
                        <label for="date_fin" class="block text-sm font-medium text-gray-700 mb-1">Date de fin</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="date" name="date_fin" id="date_fin" required max="<?php echo date('Y-m-d'); ?>"
                                value="<?php echo isset($stage_info['date_fin_stage']) ? htmlspecialchars($stage_info['date_fin_stage']) : ''; ?>"
                                class="pl-10 block w-full py-2 outline-green-500 rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                        </div>
                        <p id="date-error" class="text-xs text-red-600 mt-1 hidden">
                            <svg class="h-3 w-3 inline-block" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                                    clip-rule="evenodd" />
                            </svg>
                            La période de stage doit être d'au minimum 6 mois
                        </p>
                    </div>

                    <div class="col-span-1">
                        <label for="sujet" class="block text-sm font-medium text-gray-700 mb-1">Sujet du stage</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <input type="text" name="sujet" required
                                value="<?php echo isset($stage_info['sujet_stage']) ? htmlspecialchars($stage_info['sujet_stage']) : ''; ?>"
                                class="pl-10 py-2 outline-green-500 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                placeholder="Ex: Développement d'une application web">
                        </div>
                    </div>

                    <div class="col-span-1">
                        <label for="encadrant" class="block text-sm font-medium text-gray-700 mb-1">Nom de
                            l'encadrant</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            </div>
                            <input type="text" name="encadrant" required
                                value="<?php echo isset($stage_info['encadrant_entreprise']) ? htmlspecialchars($stage_info['encadrant_entreprise']) : ''; ?>"
                                class="pl-10 py-2 outline-green-500 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                placeholder="Nom complet">
                        </div>
                    </div>

                    <div class="col-span-1">
                        <label for="email_encadrant" class="block text-sm font-medium text-gray-700 mb-1">Email de
                            l'encadrant</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <input type="email" name="email_encadrant" required
                                value="<?php echo isset($stage_info['email_encadrant']) ? htmlspecialchars($stage_info['email_encadrant']) : ''; ?>"
                                class="pl-10 py-2 outline-green-500 block w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                placeholder="email@entreprise.com">
                        </div>
                    </div>

                    <div class="col-span-1">
                        <label for="telephone_encadrant" class="block text-sm font-medium text-gray-700 mb-1">Téléphone
                            de l'encadrant</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none"
                                    viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                </svg>
                            </div>
                            <input type="tel" name="telephone_encadrant" required
                                value="<?php echo isset($stage_info['telephone_encadrant']) ? htmlspecialchars($stage_info['telephone_encadrant']) : ''; ?>"
                                class="pl-10 block py-2 outline-green-500 w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                placeholder="+225 07 07 07 07 07">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" name="btn_enregistrer" value="1"
                        class="px-6 py-2.5 bg-green-500 text-white rounded-lg hover:bg-green-600 font-medium transition-colors duration-200 flex items-center">
                        <svg class="w-6 h-6 mr-2" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                        Rédiger mon rapport
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Liste des entreprises depuis PHP
        const entreprises = <?php echo json_encode(array_map(function ($e) {
            return $e->lib_entreprise;
        }, $entreprises)); ?>;

        document.addEventListener('DOMContentLoaded', function () {
            const input = document.getElementById('entreprise');
            const suggestionsList = document.getElementById('suggestions-list');
            let selectedIndex = -1;

            // Fonction pour afficher les suggestions
            function showSuggestions(value) {
                const filteredEntreprises = entreprises.filter(e =>
                    e.toLowerCase().includes(value.toLowerCase())
                );

                suggestionsList.innerHTML = '';

                if (value.trim() === '') {
                    suggestionsList.classList.remove('active');
                    return;
                }

                if (filteredEntreprises.length === 0) {
                    // Aucune entreprise trouvée - afficher option d'ajout
                    suggestionsList.innerHTML = `
                        <div class="autocomplete-suggestion autocomplete-add-new" data-value="${value}">
                            <div class="icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                            </div>
                            <div class="text">
                                <strong>Ajouter "${value}"</strong>
                                <div style="font-size: 12px; color: #6b7280; font-weight: normal; margin-top: 2px;">Nouvelle entreprise</div>
                            </div>
                        </div>
                    `;
                } else {
                    // Afficher les suggestions trouvées
                    filteredEntreprises.forEach((entreprise, index) => {
                        const div = document.createElement('div');
                        div.className = 'autocomplete-suggestion';
                        div.setAttribute('data-value', entreprise);
                        div.innerHTML = `
                            <div class="icon">
                                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                                </svg>
                            </div>
                            <div class="text">${entreprise}</div>
                        `;
                        suggestionsList.appendChild(div);
                    });
                }

                suggestionsList.classList.add('active');
                selectedIndex = -1;

                // Ajouter les écouteurs de clic
                suggestionsList.querySelectorAll('.autocomplete-suggestion').forEach(item => {
                    item.addEventListener('click', function () {
                        input.value = this.getAttribute('data-value');
                        suggestionsList.classList.remove('active');
                    });
                });
            }

            // Événement input
            input.addEventListener('input', function () {
                showSuggestions(this.value);
            });

            // Événement focus
            input.addEventListener('focus', function () {
                if (this.value.trim()) {
                    showSuggestions(this.value);
                }
            });

            // Navigation au clavier
            input.addEventListener('keydown', function (e) {
                const suggestions = suggestionsList.querySelectorAll('.autocomplete-suggestion');

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, suggestions.length - 1);
                    updateSelection(suggestions);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    updateSelection(suggestions);
                } else if (e.key === 'Enter' && selectedIndex >= 0) {
                    e.preventDefault();
                    suggestions[selectedIndex].click();
                } else if (e.key === 'Escape') {
                    suggestionsList.classList.remove('active');
                }
            });

            function updateSelection(suggestions) {
                suggestions.forEach((item, index) => {
                    if (index === selectedIndex) {
                        item.classList.add('selected');
                        item.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
                    } else {
                        item.classList.remove('selected');
                    }
                });
            }

            // Fermer les suggestions en cliquant à l'extérieur
            document.addEventListener('click', function (e) {
                if (!input.contains(e.target) && !suggestionsList.contains(e.target)) {
                    suggestionsList.classList.remove('active');
                }
            });

            // Validation de la durée du stage (minimum 6 mois)
            const dateDebut = document.getElementById('date_debut');
            const dateFin = document.getElementById('date_fin');
            const dateError = document.getElementById('date-error');
            const form = document.getElementById('stageInfoForm');

            function validateStageDuration() {
                if (!dateDebut.value || !dateFin.value) {
                    dateError.classList.add('hidden');
                    return true;
                }

                const debut = new Date(dateDebut.value);
                const fin = new Date(dateFin.value);
                const aujourdhui = new Date();
                aujourdhui.setHours(0, 0, 0, 0);

                // Vérifier que les dates ne sont pas dans le futur
                if (debut > aujourdhui) {
                    dateError.innerHTML = `
                        <svg class="h-3 w-3 inline-block" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        La date de début ne peut pas être dans le futur
                    `;
                    dateError.classList.remove('hidden');
                    return false;
                }

                if (fin > aujourdhui) {
                    dateError.innerHTML = `
                        <svg class="h-3 w-3 inline-block" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        La date de fin ne peut pas être dans le futur
                    `;
                    dateError.classList.remove('hidden');
                    return false;
                }

                // Vérifier que la date de fin est après la date de début
                if (fin <= debut) {
                    dateError.innerHTML = `
                        <svg class="h-3 w-3 inline-block" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        La date de fin doit être après la date de début
                    `;
                    dateError.classList.remove('hidden');
                    return false;
                }

                // Calculer la différence en mois
                const diffTime = Math.abs(fin - debut);
                const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
                const diffMonths = diffDays / 30.44; // Moyenne de jours par mois

                if (diffMonths < 6) {
                    const monthsText = Math.floor(diffMonths);
                    const weeksText = Math.floor((diffMonths - monthsText) * 4.33);
                    dateError.innerHTML = `
                        <svg class="h-3 w-3 inline-block" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        La période de stage doit être d'au minimum 6 mois (actuellement: ${monthsText} mois et ${weeksText} semaines)
                    `;
                    dateError.classList.remove('hidden');
                    return false;
                }

                dateError.classList.add('hidden');
                return true;
            }

            // Valider à chaque changement de date
            dateDebut.addEventListener('change', validateStageDuration);
            dateFin.addEventListener('change', validateStageDuration);

            // Valider avant la soumission du formulaire
            form.addEventListener('submit', function (e) {
                if (!validateStageDuration()) {
                    e.preventDefault();
                    dateFin.focus();
                    dateError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            });

            // Messages d'alerte auto-disparition
            const messages = document.querySelectorAll('.mb-4:not(#warningMessage)');
            messages.forEach(function (message) {
                setTimeout(function () {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease';
                    setTimeout(function () {
                        message.remove();
                    }, 500);
                }, 5000);
            });
        });
    </script>
</body>

</html>
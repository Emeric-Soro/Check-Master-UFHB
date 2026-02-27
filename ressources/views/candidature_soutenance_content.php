<?php
$stage_info = is_array($GLOBALS['stage_info'] ?? null) ? $GLOBALS['stage_info'] : [];
$entreprises = is_array($GLOBALS['entreprises'] ?? null) ? $GLOBALS['entreprises'] : [];

$successMessage = (string) ($_SESSION['success'] ?? '');
$errorMessage = (string) ($_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);

$entrepriseValue = (string) ($stage_info['nom_entreprise'] ?? '');
$dateDebutValue = (string) ($stage_info['date_debut_stage'] ?? '');
$dateFinValue = (string) ($stage_info['date_fin_stage'] ?? '');
$sujetValue = (string) ($stage_info['sujet_stage'] ?? '');
$encadrantValue = (string) ($stage_info['encadrant_entreprise'] ?? '');
$emailEncadrantValue = (string) ($stage_info['email_encadrant'] ?? '');
$telephoneEncadrantValue = (string) ($stage_info['telephone_encadrant'] ?? '');
?>

<div class="cm-etu-screen">
    <section class="cm-etu-panel">
        <header class="cm-etu-panel__header">
            <div>
                <h2 class="cm-etu-panel__title"><i class="fas fa-briefcase" aria-hidden="true"></i> Candidature &
                    Informations de Stage</h2>
                <p class="cm-etu-panel__subtitle">Renseignez vos informations de stage avant la rédaction du rapport.
                </p>
            </div>
            <span class="cm-etu-step-badge"><strong>1</strong> Remplissez vos informations de stage</span>
        </header>

        <?php if ($successMessage !== ''): ?>
            <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $successMessage]); ?>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $errorMessage]); ?>
        <?php endif; ?>

        <form id="stageInfoForm" method="POST" action="?page=candidature_soutenance&action=info_stage"
              class="cm-etu-form" novalidate>
            <div class="cm-etu-grid cm-etu-grid--2">
                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="entreprise">Entreprise <span
                                class="cm-required-star">*</span></label>
                    <p class="cm-etu-help">Choisissez ou tapez pour ajouter</p>
                    <div class="cm-etu-autocomplete">
                        <input type="text" id="entreprise" name="entreprise" class="cm-etu-input" autocomplete="off"
                               required value="<?= htmlspecialchars($entrepriseValue, ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Ex: Orange Côte d'Ivoire">
                        <div id="entrepriseSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                    </div>
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="encadrant">Nom du maître de stage <span
                                class="cm-required-star">*</span></label>
                    <p class="cm-etu-help">Sélectionnez d'abord une entreprise, puis choisissez ou ajoutez</p>
                    <div class="cm-etu-autocomplete">
                        <input type="text" id="encadrant" name="encadrant" class="cm-etu-input" autocomplete="off"
                               required value="<?= htmlspecialchars($encadrantValue, ENT_QUOTES, 'UTF-8') ?>"
                               placeholder="Ex: Koné Seydou">
                        <div id="encadrantSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                    </div>
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="date_debut">Date de début <span
                                class="cm-required-star">*</span></label>
                    <input type="date" id="date_debut" name="date_debut" class="cm-etu-input" required
                           max="<?= date('Y-m-d') ?>"
                           value="<?= htmlspecialchars($dateDebutValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="date_fin">Date de fin <span
                                class="cm-required-star">*</span></label>
                    <input type="date" id="date_fin" name="date_fin" class="cm-etu-input" required
                           max="<?= date('Y-m-d') ?>" value="<?= htmlspecialchars($dateFinValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="sujet">Thème du rapport <span
                                class="cm-required-star">*</span></label>
                    <input type="text" id="sujet" name="sujet" class="cm-etu-input" required maxlength="150"
                           value="<?= htmlspecialchars($sujetValue, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="Ex: Mise en place d'une API REST sécurisée">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="email_encadrant">Email Maître de Stage <span
                                class="cm-required-star">*</span></label>
                    <input type="email" id="email_encadrant" name="email_encadrant" class="cm-etu-input" required
                           value="<?= htmlspecialchars($emailEncadrantValue, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="email@entreprise.ci">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="telephone_encadrant">Téléphone Maître de Stage <span
                                class="cm-required-star">*</span></label>
                    <input type="tel" id="telephone_encadrant" name="telephone_encadrant" class="cm-etu-input" required
                           value="<?= htmlspecialchars($telephoneEncadrantValue, ENT_QUOTES, 'UTF-8') ?>"
                           placeholder="+225 07 00 00 00 00">
                </div>
            </div>

            <p id="stageDateError" class="cm-etu-error" aria-live="assertive"></p>

            <div class="cm-etu-actions">
                <button type="submit" name="btn_enregistrer" value="1" class="cm-btn is-success">
                    <i class="fas fa-pen" aria-hidden="true"></i>
                    <span>Rédiger mon rapport</span>
                </button>
            </div>
        </form>
    </section>
</div>

<script>
    (function () {
        const entreprisesData = <?= json_encode(array_map(static function ($entreprise) {
            $nomLong = (string) ($entreprise->lib_long_entreprise ?? '');
            $nomCourt = (string) ($entreprise->lib_court_en ?? '');
            $nomAffiche = $nomCourt ? "$nomLong ($nomCourt)" : $nomLong;
            return [
                    'id' => (int) ($entreprise->id_entreprise ?? 0),
                    'nom' => $nomAffiche,
                    'nom_long' => $nomLong,
                    'nom_court' => $nomCourt
            ];
        }, $entreprises), JSON_UNESCAPED_UNICODE) ?>;

        const entreprises = entreprisesData.map(e => e.nom);

        const maitresDeStage = <?= json_encode(array_map(static function ($maitre) {
            $nomLong = (string) ($maitre->lib_long_entreprise ?? '');
            $nomCourt = (string) ($maitre->lib_court_en ?? '');
            $nomEntreprise = $nomCourt ? "$nomLong ($nomCourt)" : $nomLong;
            return [
                    'nom_complet' => trim(($maitre->Nom ?? '') . ' ' . ($maitre->prenom ?? '')),
                    'email' => (string) ($maitre->email ?? ''),
                    'telephone' => (string) ($maitre->telephone ?? ''),
                    'id_entreprise' => (int) ($maitre->id_entreprise ?? 0),
                    'entreprise' => $nomEntreprise
            ];
        }, $maitres_de_stage), JSON_UNESCAPED_UNICODE) ?>;

        let selectedEntrepriseId = null;

        const inputEntreprise = document.getElementById('entreprise');
        const suggestions = document.getElementById('entrepriseSuggestions');
        const dateDebut = document.getElementById('date_debut');
        const dateFin = document.getElementById('date_fin');
        const dateError = document.getElementById('stageDateError');
        const form = document.getElementById('stageInfoForm');

        if (!inputEntreprise || !suggestions || !dateDebut || !dateFin || !dateError || !form) {
            return;
        }

        let currentIndex = -1;

        // Initialiser selectedEntrepriseId si une entreprise est déjà sélectionnée (mode édition)
        if (inputEntreprise.value.trim() !== '') {
            const entrepriseExistante = entreprisesData.find(e => e.nom === inputEntreprise.value.trim());
            if (entrepriseExistante) {
                selectedEntrepriseId = entrepriseExistante.id;
            }
        }

        function hideSuggestions() {
            suggestions.classList.remove('is-open');
            suggestions.innerHTML = '';
            currentIndex = -1;
        }

        function createItem(label, isAddNew, entrepriseData) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'cm-etu-autocomplete__item' + (isAddNew ? ' is-add' : '');
            item.dataset.value = label;
            item.innerHTML = isAddNew
                ? '<i class="fas fa-plus" aria-hidden="true"></i> Ajouter "' + label.replace(/"/g, '&quot;') + '"'
                : '<i class="fas fa-building" aria-hidden="true"></i> ' + label.replace(/</g, '&lt;');
            item.addEventListener('click', function () {
                inputEntreprise.value = label;
                if (entrepriseData) {
                    selectedEntrepriseId = entrepriseData.id;
                } else {
                    selectedEntrepriseId = null;
                }
                // Réinitialiser le champ maître de stage quand on change d'entreprise
                inputEncadrant.value = '';
                inputEmailEncadrant.value = '';
                inputTelephoneEncadrant.value = '';
                hideSuggestions();
            });
            return item;
        }

        function renderSuggestions(query) {
            const value = String(query || '').trim();
            if (value === '') {
                hideSuggestions();
                return;
            }

            const matches = entreprisesData.filter(function (entreprise) {
                return String(entreprise.nom).toLowerCase().includes(value.toLowerCase());
            });

            suggestions.innerHTML = '';

            if (matches.length === 0) {
                suggestions.appendChild(createItem(value, true, null));
            } else {
                matches.slice(0, 8).forEach(function (entrepriseData) {
                    suggestions.appendChild(createItem(entrepriseData.nom, false, entrepriseData));
                });
            }

            suggestions.classList.add('is-open');
            currentIndex = -1;
        }

        function updateKeyboardSelection() {
            const items = suggestions.querySelectorAll('.cm-etu-autocomplete__item');
            items.forEach(function (item, idx) {
                item.classList.toggle('is-active', idx === currentIndex);
            });
        }

        inputEntreprise.addEventListener('input', function () {
            renderSuggestions(inputEntreprise.value);
        });

        inputEntreprise.addEventListener('focus', function () {
            if (inputEntreprise.value.trim() !== '') {
                renderSuggestions(inputEntreprise.value);
            }
        });

        inputEntreprise.addEventListener('keydown', function (event) {
            const items = suggestions.querySelectorAll('.cm-etu-autocomplete__item');
            if (!items.length) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                currentIndex = Math.min(currentIndex + 1, items.length - 1);
                updateKeyboardSelection();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                currentIndex = Math.max(currentIndex - 1, 0);
                updateKeyboardSelection();
            } else if (event.key === 'Enter' && currentIndex >= 0) {
                event.preventDefault();
                items[currentIndex].click();
            } else if (event.key === 'Escape') {
                hideSuggestions();
            }
        });

        document.addEventListener('click', function (event) {
            if (!suggestions.contains(event.target) && event.target !== inputEntreprise) {
                hideSuggestions();
            }
        });

        function validateDates() {
            dateError.textContent = '';
            if (!dateDebut.value || !dateFin.value) {
                return true;
            }

            const debut = new Date(dateDebut.value + 'T00:00:00');
            const fin = new Date(dateFin.value + 'T00:00:00');
            const now = new Date();
            now.setHours(0, 0, 0, 0);

            if (Number.isNaN(debut.getTime()) || Number.isNaN(fin.getTime())) {
                dateError.textContent = 'Veuillez saisir des dates valides.';
                return false;
            }

            if (debut > now) {
                dateError.textContent = 'La date de début ne peut pas être dans le futur.';
                return false;
            }

            if (fin > now) {
                dateError.textContent = 'La date de fin ne peut pas être dans le futur.';
                return false;
            }

            if (fin <= debut) {
                dateError.textContent = 'La date de fin doit être après la date de début.';
                return false;
            }

            const days = Math.ceil((fin - debut) / (1000 * 60 * 60 * 24));
            const months = days / 30.44;
            if (months < 6) {
                dateError.textContent = 'La période de stage doit être d\'au minimum 6 mois.';
                return false;
            }

            return true;
        }

        dateDebut.addEventListener('change', validateDates);
        dateFin.addEventListener('change', validateDates);

        form.addEventListener('submit', function (event) {
            if (!validateDates()) {
                event.preventDefault();
                dateError.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });

        // === Autocomplete pour Maître de Stage ===
        const inputEncadrant = document.getElementById('encadrant');
        const suggestionsEncadrant = document.getElementById('encadrantSuggestions');
        const inputEmailEncadrant = document.getElementById('email_encadrant');
        const inputTelephoneEncadrant = document.getElementById('telephone_encadrant');

        if (!inputEncadrant || !suggestionsEncadrant) {
            return;
        }

        let currentIndexEncadrant = -1;

        function hideSuggestionsEncadrant() {
            suggestionsEncadrant.classList.remove('is-open');
            suggestionsEncadrant.innerHTML = '';
            currentIndexEncadrant = -1;
        }

        function createItemEncadrant(maitre, isAddNew) {
            const item = document.createElement('button');
            item.type = 'button';
            item.className = 'cm-etu-autocomplete__item' + (isAddNew ? ' is-add' : '');

            if (isAddNew) {
                item.dataset.value = maitre;
                item.innerHTML = '<i class="fas fa-plus" aria-hidden="true"></i> Ajouter "' + maitre.replace(/"/g, '&quot;') + '"';
                item.addEventListener('click', function () {
                    inputEncadrant.value = maitre;
                    inputEmailEncadrant.value = '';
                    inputTelephoneEncadrant.value = '';
                    hideSuggestionsEncadrant();
                });
            } else {
                item.dataset.value = maitre.nom_complet;
                // Afficher l'entreprise seulement si aucune entreprise n'est sélectionnée
                const entrepriseInfo = selectedEntrepriseId === null && maitre.entreprise
                    ? '<br><small style="margin-left: 24px; color: #0066cc;"><i class="fas fa-building"></i> ' + maitre.entreprise + '</small>'
                    : '';
                item.innerHTML = '<i class="fas fa-user" aria-hidden="true"></i> ' + maitre.nom_complet.replace(/</g, '&lt;') +
                    '<br><small style="margin-left: 24px; color: #666;">' +
                    (maitre.email || 'Pas d\'email') + ' | ' +
                    (maitre.telephone || 'Pas de tél.') + '</small>' + entrepriseInfo;
                item.addEventListener('click', function () {
                    inputEncadrant.value = maitre.nom_complet;
                    inputEmailEncadrant.value = maitre.email || '';
                    inputTelephoneEncadrant.value = maitre.telephone || '';
                    hideSuggestionsEncadrant();
                });
            }

            return item;
        }

        function renderSuggestionsEncadrant(query) {
            const value = String(query || '').trim();
            if (value === '') {
                hideSuggestionsEncadrant();
                return;
            }

            // Filtrer d'abord par entreprise si une entreprise est sélectionnée
            let maitresFiltres = maitresDeStage;
            if (selectedEntrepriseId !== null) {
                maitresFiltres = maitresDeStage.filter(function (maitre) {
                    return maitre.id_entreprise === selectedEntrepriseId;
                });
            }

            // Ensuite filtrer par le nom
            const matches = maitresFiltres.filter(function (maitre) {
                return String(maitre.nom_complet).toLowerCase().includes(value.toLowerCase());
            });

            suggestionsEncadrant.innerHTML = '';

            if (matches.length === 0) {
                if (selectedEntrepriseId === null) {
                    suggestionsEncadrant.innerHTML = '<div style="padding: 12px; color: #666; font-size: 14px;"><i class="fas fa-info-circle"></i> Veuillez d\'abord sélectionner une entreprise</div>';
                } else {
                    suggestionsEncadrant.appendChild(createItemEncadrant(value, true));
                }
            } else {
                matches.slice(0, 8).forEach(function (maitre) {
                    suggestionsEncadrant.appendChild(createItemEncadrant(maitre, false));
                });
            }

            suggestionsEncadrant.classList.add('is-open');
            currentIndexEncadrant = -1;
        }

        function updateKeyboardSelectionEncadrant() {
            const items = suggestionsEncadrant.querySelectorAll('.cm-etu-autocomplete__item');
            items.forEach(function (item, idx) {
                item.classList.toggle('is-active', idx === currentIndexEncadrant);
            });
        }

        inputEncadrant.addEventListener('input', function () {
            renderSuggestionsEncadrant(inputEncadrant.value);
        });

        inputEncadrant.addEventListener('focus', function () {
            if (inputEncadrant.value.trim() !== '') {
                renderSuggestionsEncadrant(inputEncadrant.value);
            }
        });

        inputEncadrant.addEventListener('keydown', function (event) {
            const items = suggestionsEncadrant.querySelectorAll('.cm-etu-autocomplete__item');
            if (!items.length) {
                return;
            }

            if (event.key === 'ArrowDown') {
                event.preventDefault();
                currentIndexEncadrant = Math.min(currentIndexEncadrant + 1, items.length - 1);
                updateKeyboardSelectionEncadrant();
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                currentIndexEncadrant = Math.max(currentIndexEncadrant - 1, 0);
                updateKeyboardSelectionEncadrant();
            } else if (event.key === 'Enter' && currentIndexEncadrant >= 0) {
                event.preventDefault();
                items[currentIndexEncadrant].click();
            } else if (event.key === 'Escape') {
                hideSuggestionsEncadrant();
            }
        });

        document.addEventListener('click', function (event) {
            if (!suggestionsEncadrant.contains(event.target) && event.target !== inputEncadrant) {
                hideSuggestionsEncadrant();
            }
        });
    })();
</script>
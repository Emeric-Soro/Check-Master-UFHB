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
                <h2 class="cm-etu-panel__title"><i class="fas fa-briefcase" aria-hidden="true"></i> Candidature & Informations de Stage</h2>
                <p class="cm-etu-panel__subtitle">Renseignez vos informations de stage avant la rédaction du rapport.</p>
            </div>
            <span class="cm-etu-step-badge"><strong>1</strong> Remplissez vos informations de stage</span>
        </header>

        <?php if ($successMessage !== ''): ?>
            <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $successMessage]); ?>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $errorMessage]); ?>
        <?php endif; ?>

        <form id="stageInfoForm" method="POST" action="?page=candidature_soutenance&action=info_stage" class="cm-etu-form" novalidate>
            <div class="cm-etu-grid cm-etu-grid--2">
                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="entreprise">Entreprise <span class="cm-required-star">*</span></label>
                    <p class="cm-etu-help">Choisissez ou tapez pour ajouter</p>
                    <div class="cm-etu-autocomplete">
                        <input
                            type="text"
                            id="entreprise"
                            name="entreprise"
                            class="cm-etu-input"
                            autocomplete="off"
                            required
                            value="<?= htmlspecialchars($entrepriseValue, ENT_QUOTES, 'UTF-8') ?>"
                            placeholder="Ex: Orange Côte d'Ivoire">
                        <div id="entrepriseSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                    </div>
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="encadrant">Nom du maître de stage <span class="cm-required-star">*</span></label>
                    <input
                        type="text"
                        id="encadrant"
                        name="encadrant"
                        class="cm-etu-input"
                        required
                        value="<?= htmlspecialchars($encadrantValue, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ex: M. Koné Seydou">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="date_debut">Date de début <span class="cm-required-star">*</span></label>
                    <input
                        type="date"
                        id="date_debut"
                        name="date_debut"
                        class="cm-etu-input"
                        required
                        max="<?= date('Y-m-d') ?>"
                        value="<?= htmlspecialchars($dateDebutValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="date_fin">Date de fin <span class="cm-required-star">*</span></label>
                    <input
                        type="date"
                        id="date_fin"
                        name="date_fin"
                        class="cm-etu-input"
                        required
                        max="<?= date('Y-m-d') ?>"
                        value="<?= htmlspecialchars($dateFinValue, ENT_QUOTES, 'UTF-8') ?>">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="sujet">Thème du rapport <span class="cm-required-star">*</span></label>
                    <input
                        type="text"
                        id="sujet"
                        name="sujet"
                        class="cm-etu-input"
                        required
                        maxlength="150"
                        value="<?= htmlspecialchars($sujetValue, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Ex: Mise en place d'une API REST sécurisée">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="email_encadrant">Email Maître de Stage <span class="cm-required-star">*</span></label>
                    <input
                        type="email"
                        id="email_encadrant"
                        name="email_encadrant"
                        class="cm-etu-input"
                        required
                        value="<?= htmlspecialchars($emailEncadrantValue, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="email@entreprise.ci">
                </div>

                <div class="cm-etu-field">
                    <label class="cm-etu-label" for="telephone_encadrant">Téléphone Maître de Stage <span class="cm-required-star">*</span></label>
                    <input
                        type="tel"
                        id="telephone_encadrant"
                        name="telephone_encadrant"
                        class="cm-etu-input"
                        required
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
    const entreprises = <?= json_encode(array_map(static function ($entreprise) {
        return (string) ($entreprise->lib_entreprise ?? '');
    }, $entreprises), JSON_UNESCAPED_UNICODE) ?>;

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

    function hideSuggestions() {
        suggestions.classList.remove('is-open');
        suggestions.innerHTML = '';
        currentIndex = -1;
    }

    function createItem(label, isAddNew) {
        const item = document.createElement('button');
        item.type = 'button';
        item.className = 'cm-etu-autocomplete__item' + (isAddNew ? ' is-add' : '');
        item.dataset.value = label;
        item.innerHTML = isAddNew
            ? '<i class="fas fa-plus" aria-hidden="true"></i> Ajouter "' + label.replace(/"/g, '&quot;') + '"'
            : '<i class="fas fa-building" aria-hidden="true"></i> ' + label.replace(/</g, '&lt;');
        item.addEventListener('click', function () {
            inputEntreprise.value = label;
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

        const matches = entreprises.filter(function (nom) {
            return String(nom).toLowerCase().includes(value.toLowerCase());
        });

        suggestions.innerHTML = '';

        if (matches.length === 0) {
            suggestions.appendChild(createItem(value, true));
        } else {
            matches.slice(0, 8).forEach(function (nom) {
                suggestions.appendChild(createItem(nom, false));
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
})();
</script>

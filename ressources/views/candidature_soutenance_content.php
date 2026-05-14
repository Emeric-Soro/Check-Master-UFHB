<?php
$stage_info = is_array($GLOBALS['stage_info'] ?? null) ? $GLOBALS['stage_info'] : [];
$entreprises = is_array($GLOBALS['entreprises'] ?? null) ? $GLOBALS['entreprises'] : [];
$successMessage = (string) ($_SESSION['success'] ?? '');
$errorMessage = (string) ($_SESSION['error'] ?? '');
unset($_SESSION['success'], $_SESSION['error']);

$candidature_active = $GLOBALS['candidature_active'] ?? null;
$progression = $GLOBALS['progression'] ?? ['candidature' => false, 'stage' => false, 'rapport' => false];

$entrepriseValue = (string) ($stage_info['nom_entreprise'] ?? '');
$dateDebutValue = (string) ($stage_info['date_debut_stage'] ?? '');
$dateFinValue = (string) ($stage_info['date_fin_stage'] ?? '');
$sujetValue = (string) ($stage_info['sujet_stage'] ?? '');
$encadrantNom = (string) ($stage_info['encadrant_nom'] ?? '');
$encadrantPrenom = (string) ($stage_info['encadrant_prenom'] ?? '');
$encadrantValue = trim($encadrantNom . ' ' . $encadrantPrenom);
$emailEncadrantValue = (string) ($stage_info['encadrant_email'] ?? '');
$telephoneEncadrantValue = (string) ($stage_info['encadrant_telephone'] ?? '');
?>
<style>
    .cm-etu-panel {
        background: transparent !important;
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }
    
    .cm-etu-field {
        margin-bottom: 20px;
    }
    
    .cm-etu-label {
        display: block !important;
        font-weight: 600 !important;
        color: #333 !important;
        margin-bottom: 8px !important;
        font-size: 0.85rem !important;
    }
    
    .cm-etu-input {
        background: #fff !important;
        border: 1px solid #e2e8f0 !important;
        border-radius: 4px !important;
        padding: 8px 12px !important;
        font-size: 0.9rem !important;
        width: 100%;
        transition: border-color 0.2s;
        color: #333 !important;
    }
    
    .cm-etu-input:focus {
        border-color: #3182ce !important;
        outline: none !important;
        box-shadow: 0 0 0 1px #3182ce !important;
    }
    
    .cm-required-star {
        color: #e53e3e !important;
        margin-left: 2px;
    }

    .cm-btn {
        padding: 8px 16px !important;
        border-radius: 4px !important;
        font-weight: 600 !important;
        font-size: 0.9rem !important;
        cursor: pointer;
        transition: all 0.2s;
        border: 1px solid transparent;
    }

    .cm-btn.is-primary {
        background-color: #3182ce !important;
        color: #fff !important;
        border-color: #3182ce !important;
    }

    .cm-btn.is-primary:hover {
        background-color: #2b6cb0 !important;
    }

    .cm-btn.is-outline {
        background-color: #fff !important;
        color: #4a5568 !important;
        border-color: #e2e8f0 !important;
    }

    .cm-btn.is-outline:hover {
        background-color: #f7fafc !important;
    }

    .cm-btn.is-ghost {
        background-color: #edf2f7 !important;
        color: #4a5568 !important;
        border-color: #edf2f7 !important;
    }

    .cm-btn.is-ghost:hover {
        background-color: #e2e8f0 !important;
    }

    .cm-form-section-title {
        font-size: 1rem;
        font-weight: 700;
        color: #2d3748;
        margin: 30px 0 15px 0;
        padding-bottom: 8px;
        border-bottom: 1px solid #e2e8f0;
    }

    /* Autocomplete list alignment */
    .cm-etu-autocomplete__list {
        border-radius: 4px !important;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #e2e8f0 !important;
    }
</style>
<div class="cm-etu-screen">
    <div style="padding: 20px;">

        <!-- MESSAGES D'ALERTE -->
        <?php if ($successMessage !== ''): ?>
            <div style="margin-bottom: 20px;">
                <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $successMessage]); ?>
            </div>
        <?php endif; ?>
        <?php if ($errorMessage !== ''): ?>
            <div style="margin-bottom: 20px;">
                <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $errorMessage]); ?>
            </div>
        <?php endif; ?>

        <!-- CONTENEUR PRINCIPAL -->
        <div class="cm-etu-panel">
            
            <form id="stageInfoForm" method="POST" action="?page=candidature_soutenance&action=info_stage" novalidate>
                
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; margin-bottom: 30px;">
                    
                    <div class="cm-etu-field">
                        <label class="cm-etu-label" for="entreprise">Entreprise d'accueil <span class="cm-required-star">*</span></label>
                        <div class="cm-etu-autocomplete">
                            <input type="text" id="entreprise" name="entreprise" class="cm-etu-input" autocomplete="off" required maxlength="50"
                                value="<?= htmlspecialchars($entrepriseValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher ou saisir..." style="padding: 12px 15px !important; font-size: 0.95rem !important;">
                            <div id="entrepriseSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                        </div>
                    </div>
                    
                    <div class="cm-etu-field">
                        <label class="cm-etu-label" for="encadrant">Maître de stage <span class="cm-required-star">*</span></label>
                        <div class="cm-etu-autocomplete">
                            <input type="text" id="encadrant" name="encadrant" class="cm-etu-input" autocomplete="off" required maxlength="35"
                                value="<?= htmlspecialchars($encadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom et Prénoms" style="padding: 12px 15px !important; font-size: 0.95rem !important;">
                            <div id="encadrantSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                        </div>
                    </div>

                    <div class="cm-etu-field">
                        <label class="cm-etu-label" for="email_encadrant">E-mail <span class="cm-required-star">*</span></label>
                        <input type="email" id="email_encadrant" name="email_encadrant" class="cm-etu-input" required maxlength="50" 
                            value="<?= htmlspecialchars($emailEncadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="exemple@domaine.com" style="padding: 12px 15px !important; font-size: 0.95rem !important;">
                    </div>
                    
                    <div class="cm-etu-field">
                        <label class="cm-etu-label" for="telephone_encadrant">Téléphone <span class="cm-required-star">*</span></label>
                        <input type="tel" id="telephone_encadrant" name="telephone_encadrant" class="cm-etu-input" required 
                            value="<?= htmlspecialchars($telephoneEncadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: 0700000000" style="padding: 12px 15px !important; font-size: 0.95rem !important;">
                    </div>

                    <div class="cm-etu-field">
                        <label class="cm-etu-label" for="date_debut">Date Début <span class="cm-required-star">*</span></label>
                        <input type="date" id="date_debut" name="date_debut" class="cm-etu-input" required max="<?= date('Y-m-d') ?>"
                            value="<?= htmlspecialchars($dateDebutValue, ENT_QUOTES, 'UTF-8') ?>" style="padding: 11px 15px !important; font-size: 0.95rem !important;">
                    </div>
                    
                    <div class="cm-etu-field">
                        <label class="cm-etu-label" for="date_fin">Date Fin <span class="cm-required-star">*</span></label>
                        <input type="date" id="date_fin" name="date_fin" class="cm-etu-input" required max="<?= date('Y-m-d') ?>" 
                            value="<?= htmlspecialchars($dateFinValue, ENT_QUOTES, 'UTF-8') ?>" style="padding: 11px 15px !important; font-size: 0.95rem !important;">
                    </div>

                    <div class="cm-etu-field" style="grid-column: span 2;">
                        <label class="cm-etu-label" for="sujet">Thème de stage <span class="cm-required-star">*</span></label>
                        <textarea id="sujet" name="sujet" class="cm-etu-input" required
                            placeholder="Saisissez le titre exact de votre rapport" style="padding: 12px 15px !important; font-size: 0.95rem !important; resize: vertical; min-height: 80px;" rows="3"><?= htmlspecialchars($sujetValue, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>

                <p id="stageDateError" style="margin-top: -10px; margin-bottom: 20px; color: #e53e3e; font-size: 0.85rem; font-weight: 600;"></p>
                
                <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 20px; margin-top: 20px;">
                    <button type="button" class="cm-btn is-outline" onclick="window.history.back();">Annuler</button>
                    
                    <div style="display: flex; gap: 10px;">
                        <?php if (canEdit()): ?>
                            <button type="reset" class="cm-btn is-ghost">Réinitialiser</button>
                            <button type="submit" name="btn_enregistrer" value="1" class="cm-btn is-primary">Enregistrer</button>
                        <?php else: ?>
                            <div style="color: #718096; font-size: 0.85rem; font-style: italic; background: #edf2f7; padding: 8px 16px; border-radius: 4px;">
                                Informations en lecture seule (candidature validée).
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- SECTION DÉPÔT -->
            <?php if ($progression['stage']): ?>
                <div style="margin-top: 40px; padding: 20px; background: #f0fdf4; border-radius: 4px; border: 1px solid #dcfce7; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <i class="fas fa-check-circle" style="color: #16a34a; font-size: 1.2rem;"></i>
                        <span style="font-weight: 600; color: #166534; font-size: 0.95rem;">Informations de stage validées.</span>
                    </div>
                    <a href="?page=gestion_rapports&action=creer_rapport" class="cm-btn is-primary">
                        Déposer mon rapport
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
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
        }, $maitres_de_stage ?? []), JSON_UNESCAPED_UNICODE) ?>;
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
                dateError.innerHTML = '<i class="fas fa-exclamation-triangle"></i> La date de début ne peut pas être dans le futur.';
                return false;
            }
            if (fin > now) {
                dateError.innerHTML = '<i class="fas fa-exclamation-triangle"></i> La date de fin ne peut pas être dans le futur.';
                return false;
            }
            if (fin <= debut) {
                dateError.innerHTML = '<i class="fas fa-exclamation-triangle"></i> La date de fin doit être après la date de début.';
                return false;
            }
            const days = Math.ceil((fin - debut) / (1000 * 60 * 60 * 24));
            const months = days / 30.44;
            if (months < 6) {
                dateError.innerHTML = '<i class="fas fa-exclamation-triangle"></i> La période de stage doit être d\'au minimum 6 mois.';
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
            let maitresFiltres = maitresDeStage;
            if (selectedEntrepriseId !== null) {
                maitresFiltres = maitresDeStage.filter(function (maitre) {
                    return maitre.id_entreprise === selectedEntrepriseId;
                });
            }
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
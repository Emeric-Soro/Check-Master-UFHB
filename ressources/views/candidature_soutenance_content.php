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
<div class="cm-etu-screen">
    <div style="max-width: 800px; margin: 0 auto; padding-top: 10px;">

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

        <!-- CONTENEUR PRINCIPAL UNIQUE -->
        <div class="cm-etu-panel" style="border: 1px solid #eaeaea; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.03); background: #fff; overflow: hidden;">
            
            <?php if ($progression['candidature'] && $candidature_active): ?>
                <!-- EN-TÊTE : STATUT DE LA CANDIDATURE (Discret, visible uniquement si déjà soumise) -->
                <div style="display: flex; align-items: center; justify-content: space-between; padding: 20px 30px; background: #fafafa; border-bottom: 1px solid #f0f0f0;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-check-circle" style="color: #28a745; font-size: 1.2rem;"></i>
                        <div>
                            <span style="display: block; font-weight: 600; color: #333;">Candidature soumise</span>
                            <span style="font-size: 0.85rem; color: #777;">Le <?= htmlspecialchars($candidature_active['date_candidature'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    </div>
                    <div>
                        <?php 
                        $statut = $candidature_active['statut_candidature'] ?? 'En cours';
                        $badgeType = in_array(strtolower($statut), ['validée', 'acceptée']) ? 'success' : (strtolower($statut) === 'rejetée' ? 'danger' : 'info');
                        cm_component('ui/badge', ['text' => $statut, 'type' => $badgeType]); 
                        ?>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (!$progression['candidature']): ?>
                <!-- SI AUCUNE CANDIDATURE : Message d'information sur le processus unifié -->
                <div style="padding: 20px 30px; background: #f0f7ff; border-bottom: 1px solid #d0e3f7;">
                    <div style="display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-info-circle" style="color: #3273DC; font-size: 1.1rem; margin-top: 2px;"></i>
                        <div>
                            <span style="font-weight: 600; color: #333; display: block; margin-bottom: 4px;">Processus de candidature</span>
                            <span style="color: #555; font-size: 0.9rem; line-height: 1.5;">Remplissez les informations de stage ci-dessous, puis déposez votre rapport. Votre candidature sera soumise automatiquement lors du dépôt du rapport.</span>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SECTION 1 : INFORMATIONS DE STAGE (Toujours visible) -->
            <div style="padding: 30px;">
                <div style="margin-bottom: 25px;">
                    <h2 style="font-size: 1.35rem; color: #222; font-weight: 600; margin-bottom: 8px;">Informations de stage</h2>
                    <p style="color: #666; font-size: 0.95rem;">Renseignez les détails de la structure d'accueil et de votre thème. Ces informations figureront sur votre rapport.</p>
                </div>

                <form id="stageInfoForm" method="POST" action="?page=candidature_soutenance&action=info_stage" class="cm-etu-form" novalidate>
                    <div class="cm-etu-grid cm-etu-grid--2" style="gap: 20px;">
                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="entreprise">Entreprise d'accueil <span class="cm-required-star">*</span></label>
                            <div class="cm-etu-autocomplete">
                                <input type="text" id="entreprise" name="entreprise" class="cm-etu-input" autocomplete="off" required maxlength="50"
                                    value="<?= htmlspecialchars($entrepriseValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Rechercher ou saisir...">
                                <div id="entrepriseSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                            </div>
                        </div>
                        
                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="encadrant">Maître de stage <span class="cm-required-star">*</span></label>
                            <div class="cm-etu-autocomplete">
                                <input type="text" id="encadrant" name="encadrant" class="cm-etu-input" autocomplete="off" required maxlength="35"
                                    value="<?= htmlspecialchars($encadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Nom et Prénoms">
                                <div id="encadrantSuggestions" class="cm-etu-autocomplete__list" aria-live="polite"></div>
                            </div>
                        </div>

                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="date_debut">Date de début <span class="cm-required-star">*</span></label>
                            <input type="date" id="date_debut" name="date_debut" class="cm-etu-input" required max="<?= date('Y-m-d') ?>"
                                value="<?= htmlspecialchars($dateDebutValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        
                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="date_fin">Date de fin <span class="cm-required-star">*</span></label>
                            <input type="date" id="date_fin" name="date_fin" class="cm-etu-input" required max="<?= date('Y-m-d') ?>" 
                                value="<?= htmlspecialchars($dateFinValue, ENT_QUOTES, 'UTF-8') ?>">
                        </div>

                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="email_encadrant">Email du maître de stage <span class="cm-required-star">*</span></label>
                            <input type="email" id="email_encadrant" name="email_encadrant" class="cm-etu-input" required maxlength="50" 
                                value="<?= htmlspecialchars($emailEncadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="exemple@entreprise.com">
                        </div>
                        
                        <div class="cm-etu-field">
                            <label class="cm-etu-label" for="telephone_encadrant">Téléphone <span class="cm-required-star">*</span></label>
                            <input type="tel" id="telephone_encadrant" name="telephone_encadrant" class="cm-etu-input" required 
                                value="<?= htmlspecialchars($telephoneEncadrantValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ex: 0700000000">
                        </div>

                        <div class="cm-etu-field" style="grid-column: 1/-1;">
                            <label class="cm-etu-label" for="sujet">Thème de stage <span class="cm-required-star">*</span></label>
                            <input type="text" id="sujet" name="sujet" class="cm-etu-input" required maxlength="150"
                                value="<?= htmlspecialchars($sujetValue, ENT_QUOTES, 'UTF-8') ?>" placeholder="Saisissez le titre exact de votre rapport">
                        </div>
                    </div>

                    <p id="stageDateError" class="cm-etu-error" aria-live="assertive" style="margin-top: 10px; color: #dc3545; font-size: 0.9rem; font-weight: 500;"></p>
                    
                    <div style="margin-top: 25px; text-align: right;">
                        <?php if (canEdit()): ?>
                            <button type="submit" name="btn_enregistrer" value="1" class="cm-btn is-primary" style="padding: 10px 24px; border-radius: 6px;">
                                Enregistrer les informations
                            </button>
                        <?php else: ?>
                            <span style="color: #666; font-size: 0.9rem; font-style: italic;">Informations en lecture seule.</span>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- SECTION 2 : DÉPÔT DU RAPPORT (Visible si le stage est renseigné) -->
            <?php if ($progression['stage']): ?>
                <div style="padding: 25px 30px; background: #fdfdfd; border-top: 1px solid #f0f0f0; display: flex; align-items: center; justify-content: space-between;">
                    <div>
                        <h3 style="font-size: 1.1rem; color: #333; margin-bottom: 4px; font-weight: 600;">Dépôt du rapport</h3>
                        <p style="color: #666; font-size: 0.9rem; margin: 0;">Vos informations de stage sont complètes. Déposez votre rapport pour soumettre votre candidature.</p>
                    </div>
                    <a href="?page=gestion_rapports&action=creer_rapport" class="cm-btn is-primary is-outline" style="background: white;">
                        <i class="fas fa-file-upload" style="margin-right: 6px;"></i> Déposer mon rapport
                    </a>
                </div>
            <?php elseif (!$progression['stage']): ?>
                <div style="padding: 20px 30px; background: #f9f9f9; border-top: 1px solid #f0f0f0; opacity: 0.6;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <i class="fas fa-lock" style="color: #999; font-size: 0.9rem;"></i>
                        <div>
                            <span style="font-weight: 600; color: #777; font-size: 0.95rem;">Dépôt du rapport</span>
                            <span style="color: #999; font-size: 0.85rem; display: block;">Complétez d'abord les informations de stage pour débloquer le dépôt.</span>
                        </div>
                    </div>
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
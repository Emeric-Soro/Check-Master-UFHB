<?php
/**
 * Wizard principal du Parcours Étudiant Complet.
 * Affiche la barre de progression et les onglets.
 */

$wizardData = $GLOBALS['cycle_etudiant_wizard_data'] ?? null;
if (!$wizardData) {
    return;
}

$numEtu = $wizardData['num_etu'];
$anneeAcad = $wizardData['annee_acad'];
$progression = $wizardData['progression'];
$statuts = $progression['statuts'] ?? [];
$pct = $progression['progression_pct'] ?? 0;
$nomComplet = trim(($progression['nom_etu'] ?? '') . ' ' . ($progression['prenom_etu'] ?? ''));

// Configuration des 13 statuts pour l'affichage
$statusConfig = [
    'statut_inscription' => ['label' => 'Inscription', 'icon' => 'fa-file-invoice-dollar'],
    'statut_stage' => ['label' => 'Stage', 'icon' => 'fa-building'],
    'statut_candidature' => ['label' => 'Candidature', 'icon' => 'fa-paper-plane'],
    'statut_rapport' => ['label' => 'Rapport', 'icon' => 'fa-file-alt'],
    'statut_evaluations' => ['label' => 'Évaluations', 'icon' => 'fa-clipboard-check'],
    'statut_validation_finale' => ['label' => 'Validation', 'icon' => 'fa-check-circle'],
    'statut_pv_commission' => ['label' => 'PV Commission', 'icon' => 'fa-file-signature'],
    'statut_soutenance' => ['label' => 'Soutenance', 'icon' => 'fa-calendar-check'],
    'statut_jury' => ['label' => 'Jury', 'icon' => 'fa-users'],
    'statut_notes_soutenance' => ['label' => 'Notes sout.', 'icon' => 'fa-star'],
    'statut_notes_m1m2' => ['label' => 'Notes M1/M2', 'icon' => 'fa-graduation-cap'],
    'statut_pv_final' => ['label' => 'PV Final', 'icon' => 'fa-award'],
];

function cycleStatusColor(string $value): string {
    return match ($value) {
        'complet', 'valide', 'validee', 'redige', 'programme', 'genere', 'renseigne', 'present' => 'is-success',
        'en_evaluation', 'en_attente', 'depose', 'incomplet', 'partiel' => 'is-warning',
        'rejete' => 'is-danger',
        default => 'is-light',
    };
}

// Onglets du wizard
$tabs = [
    ['id' => 'identite', 'label' => 'Identité', 'icon' => 'fa-user'],
    ['id' => 'inscription', 'label' => 'Inscription', 'icon' => 'fa-file-invoice-dollar'],
    ['id' => 'stage', 'label' => 'Stage', 'icon' => 'fa-building'],
    ['id' => 'candidature', 'label' => 'Candidature', 'icon' => 'fa-paper-plane'],
    ['id' => 'rapport', 'label' => 'Rapport', 'icon' => 'fa-file-alt'],
    ['id' => 'pv_commission', 'label' => 'PV Commission', 'icon' => 'fa-file-signature'],
    ['id' => 'planning', 'label' => 'Planning', 'icon' => 'fa-calendar-alt'],
    ['id' => 'jury', 'label' => 'Jury', 'icon' => 'fa-users'],
    ['id' => 'notes', 'label' => 'Notes', 'icon' => 'fa-star'],
    ['id' => 'pv_final', 'label' => 'PV Final', 'icon' => 'fa-award'],
];
?>

<style>
.cycle-wizard { max-width: 1200px; margin: 0 auto; }
.cycle-header { display: flex; align-items: center; justify-content: space-between; padding: 16px 24px; background: var(--cm-card-bg); border-radius: 12px; margin-bottom: 16px; box-shadow: var(--cm-shadow-sm); }
.cycle-header__info { display: flex; align-items: center; gap: 16px; }
.cycle-header__name { font-size: 1.15rem; font-weight: 600; color: var(--cm-text); }
.cycle-header__matricule { color: var(--cm-text-muted); font-size: 0.85rem; }
.cycle-header__pct { font-size: 1.5rem; font-weight: 700; color: var(--cm-primary); }

.cycle-progress { display: flex; gap: 6px; flex-wrap: wrap; padding: 16px 24px; background: var(--cm-card-bg); border-radius: 12px; margin-bottom: 16px; box-shadow: var(--cm-shadow-sm); }
.cycle-progress__step { display: flex; align-items: center; gap: 4px; padding: 6px 10px; border-radius: 6px; font-size: 0.75rem; font-weight: 500; cursor: default; transition: transform 0.15s; }
.cycle-progress__step:hover { transform: scale(1.05); }
.cycle-progress__step i { font-size: 0.7rem; }

.cycle-tabs { display: flex; gap: 4px; flex-wrap: wrap; padding: 12px 24px; background: var(--cm-card-bg); border-radius: 12px; margin-bottom: 16px; box-shadow: var(--cm-shadow-sm); }
.cycle-tab { padding: 8px 16px; border-radius: 8px; font-size: 0.85rem; font-weight: 500; color: var(--cm-text-muted); text-decoration: none; cursor: pointer; transition: all 0.15s; display: flex; align-items: center; gap: 6px; }
.cycle-tab:hover { background: var(--cm-hover-bg); color: var(--cm-text); }
.cycle-tab.is-active { background: var(--cm-primary); color: #fff; }

.cycle-content { background: var(--cm-card-bg); border-radius: 12px; min-height: 400px; box-shadow: var(--cm-shadow-sm); overflow: hidden; }
.cycle-content__header { padding: 16px 24px; border-bottom: 1px solid var(--cm-border-color); font-weight: 600; color: var(--cm-text); display: flex; align-items: center; gap: 8px; }
.cycle-content__body { padding: 24px; }
.cycle-content__loading { text-align: center; padding: 60px; color: var(--cm-text-muted); }
.cycle-content__loading i { font-size: 2rem; margin-bottom: 12px; display: block; }
</style>

<div class="cycle-wizard">
    <!-- En-tête étudiant -->
    <div class="cycle-header">
        <div class="cycle-header__info">
            <a href="?page=cycle_etudiant" class="cm-btn is-light is-sm" title="Retour à la liste">
                <i class="fas fa-arrow-left"></i>
            </a>
            <div>
                <div class="cycle-header__name"><?= htmlspecialchars($nomComplet) ?></div>
                <div class="cycle-header__matricule"><?= htmlspecialchars($numEtu) ?> — <?= htmlspecialchars($progression['promotion_etu'] ?? '') ?></div>
            </div>
        </div>
        <div class="cycle-header__pct"><?= $pct ?>%</div>
    </div>

    <!-- Barre de progression 13 statuts -->
    <div class="cycle-progress">
        <?php foreach ($statusConfig as $key => $config):
            $value = $statuts[$key] ?? 'absent';
            $colorClass = cycleStatusColor($value);
        ?>
            <div class="cycle-progress__step cm-badge <?= $colorClass ?>" title="<?= $config['label'] ?> : <?= $value ?>">
                <i class="fas <?= $config['icon'] ?>"></i>
                <?= $config['label'] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Onglets -->
    <div class="cycle-tabs">
        <?php foreach ($tabs as $tab): ?>
            <div class="cycle-tab" data-module="<?= $tab['id'] ?>">
                <i class="fas <?= $tab['icon'] ?>"></i>
                <?= $tab['label'] ?>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Contenu de l'onglet -->
    <div class="cycle-content">
        <div class="cycle-content__header" id="cycleModuleTitle">
            <i class="fas fa-user"></i> Identité
        </div>
        <div class="cycle-content__body" id="cycleModuleBody">
            <div class="cycle-content__loading">
                <i class="fas fa-spinner fa-spin"></i>
                Chargement...
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    var numEtu = <?= json_encode($numEtu) ?>;
    var anneeAcad = <?= json_encode($anneeAcad) ?>;
    var baseUrl = '?page=cycle_etudiant&action=module&id=' + encodeURIComponent(numEtu) + '&module=';
    var saveUrl = '?page=cycle_etudiant&action=save';
    var tabs = document.querySelectorAll('.cycle-tab');
    var titleEl = document.getElementById('cycleModuleTitle');
    var bodyEl = document.getElementById('cycleModuleBody');
    var currentModule = 'identite';

    function loadModule(moduleId) {
        currentModule = moduleId;
        tabs.forEach(function(t) {
            t.classList.toggle('is-active', t.dataset.module === moduleId);
        });

        // Trouver l'icône de l'onglet
        var activeTab = document.querySelector('.cycle-tab[data-module="' + moduleId + '"]');
        var icon = activeTab ? activeTab.querySelector('i').className : 'fas fa-circle';
        titleEl.innerHTML = '<i class="' + icon + '"></i> ' + (activeTab ? activeTab.textContent.trim() : moduleId);

        bodyEl.innerHTML = '<div class="cycle-content__loading"><i class="fas fa-spinner fa-spin"></i> Chargement...</div>';

        fetch(baseUrl + encodeURIComponent(moduleId), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success && data.data) {
                renderModule(moduleId, data.data);
            } else {
                bodyEl.innerHTML = '<div class="cm-empty-state"><i class="fas fa-exclamation-circle cm-empty-state__icon"></i><p class="cm-empty-state__title">Erreur</p><p class="cm-empty-state__message">' + escapeHtml(data.message || 'Impossible de charger les données') + '</p></div>';
            }
        })
        .catch(function(err) {
            bodyEl.innerHTML = '<div class="cm-empty-state"><i class="fas fa-exclamation-circle cm-empty-state__icon"></i><p class="cm-empty-state__title">Erreur réseau</p><p class="cm-empty-state__message">' + escapeHtml(err.message) + '</p></div>';
        });
    }

    function renderModule(moduleId, data) {
        if (data.error) {
            bodyEl.innerHTML = '<div class="cm-empty-state"><i class="fas fa-info-circle cm-empty-state__icon"></i><p class="cm-empty-state__title">Information</p><p class="cm-empty-state__message">' + escapeHtml(data.error) + '</p></div>';
            return;
        }

        // Rendu générique basé sur les données reçues
        if (moduleId === 'identite') {
            renderIdentite(data);
        } else if (moduleId === 'inscription') {
            renderInscription(data);
        } else if (moduleId === 'stage') {
            renderStage(data);
        } else if (moduleId === 'candidature') {
            renderCandidature(data);
        } else if (moduleId === 'rapport') {
            renderRapport(data);
        } else if (moduleId === 'pv_commission') {
            renderCompteRendu(data);
        } else if (moduleId === 'planning' || moduleId === 'jury') {
            renderSoutenance(data);
        } else if (moduleId === 'notes') {
            renderNotes(data);
        } else if (moduleId === 'pv_final') {
            renderPvFinal(data);
        } else {
            bodyEl.innerHTML = '<pre style="font-size: 0.8rem; white-space: pre-wrap;">' + escapeHtml(JSON.stringify(data, null, 2)) + '</pre>';
        }
    }

    function saveModule(moduleId, formData) {
        formData.append('module', moduleId);
        formData.append('num_etu', numEtu);
        formData.append('id_annee_acad', String(anneeAcad));

        var saveBtn = bodyEl.querySelector('.cm-btn-save');
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement...';
        }

        fetch(saveUrl, {
            method: 'POST',
            body: formData,
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            }
        })
        .then(function(r) { return r.json(); })
        .then(function(resp) {
            if (resp.success) {
                setAlert('success', resp.message || 'Enregistre.');
                loadModule(moduleId);
            } else {
                setAlert('error', resp.message || 'Erreur.');
                if (saveBtn) {
                    saveBtn.disabled = false;
                    saveBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
                }
            }
        })
        .catch(function() {
            setAlert('error', 'Erreur reseau.');
            if (saveBtn) {
                saveBtn.disabled = false;
                saveBtn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
            }
        });
    }

    function setAlert(type, message) {
        var existing = bodyEl.querySelector('.cycle-alert');
        if (existing) existing.remove();
        var alert = document.createElement('div');
        alert.className = 'cycle-alert cm-alert-box is-' + type;
        alert.style.marginBottom = '12px';
        alert.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + escapeHtml(message);
        bodyEl.insertBefore(alert, bodyEl.firstChild);
    }

    function attachFormSubmit(moduleId) {
        var form = bodyEl.querySelector('.cycle-edit-form');
        if (!form) return;
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            var formData = new FormData(form);
            saveModule(moduleId, formData);
        });
    }

    function editField(name, label, value, required, type) {
        type = type || 'text';
        var star = required ? ' <span class="cm-required-star">*</span>' : '';
        if (type === 'textarea') {
            return '<div style="margin-bottom: 12px;"><label style="display:block;font-size:0.8rem;color:var(--cm-text-muted);margin-bottom:4px;">' + escapeHtml(label) + star + '</label><textarea name="' + escapeHtml(name) + '" class="cm-form-control" rows="3">' + escapeHtml(value) + '</textarea></div>';
        }
        return '<div style="margin-bottom: 12px;"><label style="display:block;font-size:0.8rem;color:var(--cm-text-muted);margin-bottom:4px;">' + escapeHtml(label) + star + '</label><input type="' + escapeHtml(type) + '" name="' + escapeHtml(name) + '" class="cm-form-control" value="' + escapeHtml(value) + '"' + (required ? ' required' : '') + ' step="any"></div>';
    }

    function renderIdentite(data) {
        var etu = data.etudiant || {};
        var dossier = data.dossier || [];
        var html = '<form class="cycle-edit-form cm-grid-2" style="gap: 16px; padding: 24px;">';
        html += '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 16px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-user"></i> Informations personnelles</h4>';
        html += editField('nom_etu', 'Nom', etu.nom_etu || '', true);
        html += editField('prenom_etu', 'Prenom', etu.prenom_etu || '', true);
        html += '<div style="margin-bottom: 12px;"><label style="display:block;font-size:0.8rem;color:var(--cm-text-muted);margin-bottom:4px;">Genre</label><select name="id_genre" class="cm-form-control cm-field-sm"><option value="M"' + (etu.id_genre === 'M' ? ' selected' : '') + '>Masculin</option><option value="F"' + (etu.id_genre === 'F' ? ' selected' : '') + '>Feminin</option></select></div>';
        html += '<div style="margin-bottom: 12px;"><span style="font-size:0.8rem;color:var(--cm-text-muted);display:block;">Matricule</span><span style="font-weight:500;">' + escapeHtml(etu.num_carte_etud || '') + '</span></div>';
        html += '</div>';
        html += '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 16px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-graduation-cap"></i> Dossier academique</h4>';
        if (dossier.length > 0) {
            dossier.forEach(function(d) {
                html += field('Annee', d.id_annee_acad || '');
                html += field('Promotion', d.promotion_etu || '');
            });
        } else {
            html += '<p style="color: var(--cm-text-muted);">Aucun dossier</p>';
        }
        html += '</div>';
        html += '<div style="grid-column: span 2; text-align: right;">';
        html += '<button type="submit" class="cm-btn is-primary is-sm cm-btn-save"><i class="fas fa-save"></i> Enregistrer</button>';
        html += '</div></form>';
        bodyEl.innerHTML = html;
        attachFormSubmit('identite');
    }

    function renderInscription(data) {
        var html = '<div style="padding: 24px;">';
        if (Array.isArray(data) && data.length > 0) {
            html += '<div class="cm-table-wrapper" style="margin-bottom: 20px;"><table class="cm-data-table"><thead><tr>';
            html += '<th>Annee</th><th>Date</th><th>Montant</th><th>Mode</th></tr></thead><tbody>';
            data.forEach(function(r) {
                html += '<tr><td>' + escapeHtml(r.id_annee_acad || '') + '</td><td>' + escapeHtml(r.date_inscription || '') + '</td><td>' + escapeHtml(String(r.montant_versement || '')) + '</td><td>' + escapeHtml(r.mode_paiement || '') + '</td></tr>';
            });
            html += '</tbody></table></div>';
        } else {
            html += '<p style="color: var(--cm-text-muted); margin-bottom: 20px;">Aucune inscription trouvee.</p>';
        }
        html += '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 16px;"><i class="fas fa-plus-circle"></i> Ajouter un versement</h4>';
        html += '<form class="cycle-edit-form cm-grid-3" style="gap: 12px;">';
        html += editField('montant_versement', 'Montant (FCFA)', '', true, 'number');
        html += '<div><label style="display:block;font-size:0.8rem;color:var(--cm-text-muted);margin-bottom:4px;">Mode <span class="cm-required-star">*</span></label><select name="mode_paiement" class="cm-form-control" required><option value="">--</option><option value="ES">Especes</option><option value="CH">Cheque</option><option value="OM">Orange Money</option><option value="MN">Mobile Money</option><option value="VR">Virement</option></select></div>';
        html += '<button type="submit" class="cm-btn is-primary is-sm cm-btn-save" style="align-self: flex-end;"><i class="fas fa-plus"></i> Ajouter</button>';
        html += '</form></div></div>';
        bodyEl.innerHTML = html;
        attachFormSubmit('inscription');
    }

    function renderStage(data) {
        var info = data || {};
        var html = '<form class="cycle-edit-form" style="padding: 24px; max-width: 600px;">';
        html += '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 16px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-building"></i> Stage</h4>';
        html += editField('entreprise', 'Entreprise', info.entreprise || info.entreprise_stage || '', false);
        html += editField('maitre_stage', 'Maitre de stage', info.maitre_stage || '', false);
        html += editField('date_debut', 'Date debut', info.date_debut || info.date_debut_stage || '', false, 'date');
        html += editField('date_fin', 'Date fin', info.date_fin || info.date_fin_stage || '', false, 'date');
        html += editField('theme_stage', 'Theme', info.theme_stage || info.sujet_stage || '', false, 'textarea');
        html += '</div>';
        html += '<div style="text-align: right; margin-top: 12px;">';
        html += '<button type="submit" class="cm-btn is-primary is-sm cm-btn-save"><i class="fas fa-save"></i> Enregistrer</button>';
        html += '</div></form>';
        bodyEl.innerHTML = html;
        attachFormSubmit('stage');
    }

    function renderCandidature(data) {
        var info = data || {};
        var statut = info.statut || '';
        var html = '<form class="cycle-edit-form" style="padding: 24px; max-width: 400px;">';
        html += '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 16px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-paper-plane"></i> Candidature</h4>';
        html += '<div style="margin-bottom: 12px;"><label style="display:block;font-size:0.8rem;color:var(--cm-text-muted);margin-bottom:4px;">Statut</label><select name="statut_candidature" class="cm-form-control"><option value="En attente"' + (statut === 'En attente' ? ' selected' : '') + '>En attente</option><option value="Validee"' + (statut === 'Validee' || statut === 'Validée' ? ' selected' : '') + '>Validee</option><option value="Rejetee"' + (statut === 'Rejetee' || statut === 'Rejetée' ? ' selected' : '') + '>Rejetee</option></select></div>';
        if (info.date_candidature) html += field('Date candidature', info.date_candidature);
        html += '</div>';
        html += '<div style="text-align: right; margin-top: 12px;">';
        html += '<button type="submit" class="cm-btn is-primary is-sm cm-btn-save"><i class="fas fa-save"></i> Enregistrer</button>';
        html += '</div></form>';
        bodyEl.innerHTML = html;
        attachFormSubmit('candidature');
    }

    function renderRapport(data) {
        if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
            bodyEl.innerHTML = emptyState('Aucun rapport');
            return;
        }
        var html = '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 12px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-file-alt" style="margin-right: 6px;"></i> Rapport</h4>';
        if (data.theme) html += field('Thème', data.theme);
        if (data.statut) html += field('Statut', data.statut);
        if (data.date_depot) html += field('Date dépôt', data.date_depot);
        html += '</div>';
        bodyEl.innerHTML = html;
    }

    function renderCompteRendu(data) {
        if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
            bodyEl.innerHTML = emptyState('Aucun compte rendu');
            return;
        }
        var html = '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 12px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-file-signature" style="margin-right: 6px;"></i> PV Commission</h4>';
        if (data.nom_CR) html += field('Nom', data.nom_CR);
        if (data.date_CR) html += field('Date', data.date_CR);
        html += '</div>';
        bodyEl.innerHTML = html;
    }

    function renderSoutenance(data) {
        if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
            bodyEl.innerHTML = emptyState('Aucune soutenance programmée');
            return;
        }
        var html = '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 12px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-calendar-check" style="margin-right: 6px;"></i> Soutenance</h4>';
        if (data.date_soutenance) html += field('Date', data.date_soutenance);
        if (data.heure) html += field('Heure', data.heure);
        if (data.salle) html += field('Salle', data.salle);
        if (data.jury && Array.isArray(data.jury)) {
            html += '<h5 style="margin: 12px 0 8px; font-size: 0.85rem;">Jury</h5>';
            data.jury.forEach(function(j) {
                html += field(j.qualite || j.role || '', j.nom || '');
            });
        }
        html += '</div>';
        bodyEl.innerHTML = html;
    }

    function renderNotes(data) {
        var info = data || {};
        var html = '<form class="cycle-edit-form" style="padding: 24px;">';
        html += '<div class="cm-grid-2" style="gap: 16px;">';
        html += '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 16px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-star"></i> Notes M1/M2</h4>';
        html += editField('moyenne_M1', 'Moyenne M1', info.moyenne_M1 || info.moyenne_m1 || '', false, 'number');
        html += editField('moyenne_M2', 'Moyenne M2', info.moyenne_M2 || info.moyenne_m2 || '', false, 'number');
        html += '</div>';
        html += '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 16px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-file-alt"></i> Soutenance</h4>';
        if (info.notes_soutenance && Array.isArray(info.notes_soutenance)) {
            info.notes_soutenance.forEach(function(n) {
                html += field(n.critere || '', String(n.note || ''));
            });
        } else {
            html += '<p style="color: var(--cm-text-muted);">Notes soutenance: ' + (info.note_totale ? info.note_totale : 'Non saisies') + '</p>';
        }
        html += '</div></div>';
        html += '<div style="text-align: right; margin-top: 12px;">';
        html += '<button type="submit" class="cm-btn is-primary is-sm cm-btn-save"><i class="fas fa-save"></i> Enregistrer les notes</button>';
        html += '</div></form>';
        bodyEl.innerHTML = html;
        attachFormSubmit('notes');
    }

    function renderPvFinal(data) {
        if (!data || (typeof data === 'object' && Object.keys(data).length === 0)) {
            bodyEl.innerHTML = emptyState('PV final non généré');
            return;
        }
        var html = '<div class="cm-card" style="padding: 16px;">';
        html += '<h4 style="margin: 0 0 12px; font-size: 0.95rem; color: var(--cm-primary);"><i class="fas fa-award" style="margin-right: 6px;"></i> PV Final</h4>';
        if (data.mention) html += field('Mention', data.mention);
        if (data.moyenne_generale) html += field('Moyenne générale', String(data.moyenne_generale));
        if (data.date_generation) html += field('Date génération', data.date_generation);
        html += '</div>';
        bodyEl.innerHTML = html;
    }

    function field(label, value) {
        return '<div style="margin-bottom: 8px;"><span style="font-size: 0.8rem; color: var(--cm-text-muted); display: block;">' + escapeHtml(label) + '</span><span style="font-weight: 500;">' + escapeHtml(value || '—') + '</span></div>';
    }

    function emptyState(message) {
        return '<div class="cm-empty-state"><i class="fas fa-info-circle cm-empty-state__icon"></i><p class="cm-empty-state__message">' + escapeHtml(message) + '</p></div>';
    }

    function escapeHtml(str) {
        if (str === null || str === undefined) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(String(str)));
        return div.innerHTML;
    }

    // Attacher les événements de clic sur les onglets
    tabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            loadModule(tab.dataset.module);
        });
    });

    // Charger le premier onglet
    loadModule('identite');
})();
</script>

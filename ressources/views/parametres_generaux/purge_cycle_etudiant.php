<?php
/**
 * Vue : Gestion ciblée du cycle étudiant
 * 
 * Onglets : Purger / Reconstituer
 * URL : ?page=parametres_generaux&action=purge_cycle_etudiant
 */

// CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['csrf_token'];

// Onglet actif
$activeTab = isset($_GET['tab']) && $_GET['tab'] === 'reconstituer' ? 'reconstituer' : 'purger';
$baseUrl = '?page=parametres_generaux&action=purge_cycle_etudiant';
?>

<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <div class="cm-crud-wrapper">

        <!-- ════════════════════════════════════════════════════════
             EN-TÊTE + RECHERCHE ÉTUDIANT
             ════════════════════════════════════════════════════════ -->
        <div class="cm-card" style="margin-bottom:1.5rem;">
            <div class="cm-card__header">
                <h2 class="cm-card__title"><i class="fas fa-user-graduate"></i> Gestion ciblée du cycle étudiant</h2>
            </div>
            <div class="cm-card__body">
                <div class="cm-grid-2" style="align-items:end;">
                    <div class="cm-form-group">
                        <label class="cm-form-label" for="search-etu">Rechercher un étudiant</label>
                        <div class="cm-etu-autocomplete" style="position:relative;">
                            <input type="text" id="search-etu" class="cm-form-control"
                                   placeholder="Matricule, identifiant MESRS, nom ou prénom…"
                                   autocomplete="off">
                            <div id="search-results" class="cm-etu-autocomplete__list" style="display:none;"></div>
                        </div>
                    </div>
                    <div class="cm-form-group">
                        <button type="button" id="btn-search" class="cm-btn is-primary is-sm">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════
             IDENTITÉ ÉTUDIANT SÉLECTIONNÉ
             ════════════════════════════════════════════════════════ -->
        <div id="student-identity" class="cm-card" style="margin-bottom:1.5rem; display:none;">
            <div class="cm-card__header">
                <h3 class="cm-card__title"><i class="fas fa-id-card"></i> Étudiant sélectionné</h3>
            </div>
            <div class="cm-card__body">
                <div class="cm-grid-2">
                    <div><strong>Matricule :</strong> <span id="id-num-carte"></span></div>
                    <div><strong>Ident. MESRS :</strong> <span id="id-num-ident"></span></div>
                    <div><strong>Nom :</strong> <span id="id-nom"></span></div>
                    <div><strong>Prénom :</strong> <span id="id-prenom"></span></div>
                    <div><strong>Email :</strong> <span id="id-email"></span></div>
                    <div><strong>Promotion :</strong> <span id="id-promotion"></span></div>
                </div>
            </div>
        </div>

        <!-- ════════════════════════════════════════════════════════
             ONGLETS PURGER / RECONSTITUER
             ════════════════════════════════════════════════════════ -->
        <div id="main-tabs" style="display:none;">
            <div class="cm-tab-links" role="tablist" style="margin-bottom:1.5rem;">
                <a href="<?= $baseUrl ?>&tab=purger"
                   class="cm-btn <?= $activeTab === 'purger' ? 'is-danger' : 'is-light' ?>"
                   data-tab="purger">
                    <i class="fas fa-trash-alt"></i><span>Purger le cycle</span>
                </a>
                <a href="<?= $baseUrl ?>&tab=reconstituer"
                   class="cm-btn <?= $activeTab === 'reconstituer' ? 'is-success' : 'is-light' ?>"
                   data-tab="reconstituer">
                    <i class="fas fa-plus-circle"></i><span>Reconstituer le cycle</span>
                </a>
            </div>

            <!-- ──────────────────────────────────────────────────
                 ONGLET PURGER
                 ────────────────────────────────────────────────── -->
            <div id="tab-purger" class="cm-tab-content" style="<?= $activeTab !== 'purger' ? 'display:none;' : '' ?>">

                <!-- Avertissement -->
                <div class="cm-card" style="margin-bottom:1.5rem; border-left:4px solid #e74c3c;">
                    <div class="cm-card__body" style="background:#fdf2f2;">
                        <p><i class="fas fa-exclamation-triangle" style="color:#e74c3c;"></i>
                        <strong>Attention :</strong> La purge supprime définitivement toutes les données du cycle de l'étudiant
                        (toutes années confondues). La fiche étudiant est conservée. Cette action est irréversible.</p>
                    </div>
                </div>

                <!-- Bouton inventaire -->
                <div style="margin-bottom:1.5rem;">
                    <button type="button" id="btn-inventory" class="cm-btn is-primary is-sm" disabled>
                        <i class="fas fa-list"></i> Charger l'inventaire du cycle
                    </button>
                </div>

                <!-- Zone inventaire -->
                <div id="inventory-zone" style="display:none;">

                    <!-- Compteurs -->
                    <div class="cm-card" style="margin-bottom:1.5rem;">
                        <div class="cm-card__header">
                            <h3 class="cm-card__title"><i class="fas fa-chart-bar"></i> Inventaire du cycle</h3>
                        </div>
                        <div class="cm-card__body">
                            <table class="cm-data-table" id="counters-table">
                                <thead>
                                    <tr>
                                        <th class="cm-data-table__th">Table</th>
                                        <th class="cm-data-table__th" style="text-align:right;">Lignes</th>
                                    </tr>
                                </thead>
                                <tbody id="counters-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Documents classifiés -->
                    <div class="cm-card" style="margin-bottom:1.5rem;">
                        <div class="cm-card__header">
                            <h3 class="cm-card__title"><i class="fas fa-file-alt"></i> Documents</h3>
                        </div>
                        <div class="cm-card__body">
                            <div id="warnings-zone" style="margin-bottom:1rem;"></div>
                            <table class="cm-data-table" id="docs-table">
                                <thead>
                                    <tr>
                                        <th class="cm-data-table__th">Type</th>
                                        <th class="cm-data-table__th">Référence</th>
                                        <th class="cm-data-table__th">Entité</th>
                                        <th class="cm-data-table__th">Classification</th>
                                        <th class="cm-data-table__th">Action</th>
                                        <th class="cm-data-table__th">Raison</th>
                                    </tr>
                                </thead>
                                <tbody id="docs-body"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Bouton simulation -->
                    <div style="margin-bottom:1.5rem;">
                        <button type="button" id="btn-dryrun" class="cm-btn is-warning is-sm">
                            <i class="fas fa-play"></i> Simuler la purge (dry-run)
                        </button>
                    </div>
                </div>

                <!-- Zone dry-run -->
                <div id="dryrun-zone" style="display:none;">
                    <div class="cm-card" style="margin-bottom:1.5rem; border-left:4px solid #f39c12;">
                        <div class="cm-card__header" style="background:#fef9e7;">
                            <h3 class="cm-card__title"><i class="fas fa-clipboard-check"></i> Résultat de la simulation</h3>
                        </div>
                        <div class="cm-card__body">
                            <div id="dryrun-summary"></div>
                        </div>
                    </div>

                    <!-- Zone confirmation -->
                    <div class="cm-card" style="margin-bottom:1.5rem; border-left:4px solid #e74c3c;">
                        <div class="cm-card__header" style="background:#fdf2f2;">
                            <h3 class="cm-card__title"><i class="fas fa-lock"></i> Confirmation de purge</h3>
                        </div>
                        <div class="cm-card__body">
                            <div class="cm-form-group">
                                <label class="cm-form-label">
                                    <input type="checkbox" id="confirm-irreversible">
                                    Je comprends que cette action est <strong>irréversible</strong>
                                </label>
                            </div>
                            <div class="cm-form-group">
                                <label class="cm-form-label" for="confirm-matricule">
                                    Saisissez le matricule exact pour confirmer :
                                </label>
                                <input type="text" id="confirm-matricule" class="cm-form-control" placeholder="Matricule exact">
                            </div>
                            <div class="cm-form-group">
                                <button type="button" id="btn-purge" class="cm-btn is-danger is-sm" disabled>
                                    <i class="fas fa-trash-alt"></i> Exécuter la purge
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Zone résultat -->
                <div id="purge-result-zone" style="display:none;"></div>
            </div>

            <!-- ──────────────────────────────────────────────────
                 ONGLET RECONSTITUER
                 ────────────────────────────────────────────────── -->
            <div id="tab-reconstituer" class="cm-tab-content" style="<?= $activeTab !== 'reconstituer' ? 'display:none;' : '' ?>">

                <!-- Bouton charger brouillon -->
                <div style="margin-bottom:1.5rem;">
                    <button type="button" id="btn-rebuild-draft" class="cm-btn is-success is-sm" disabled>
                        <i class="fas fa-magic"></i> Charger le brouillon de reconstruction
                    </button>
                </div>

                <!-- Wizard de reconstruction -->
                <div id="rebuild-wizard" style="display:none;">
                    <form id="rebuild-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

                        <!-- Sections activables -->
                        <?php
                        $sections = [
                            'inscription' => ['label' => 'Inscription', 'icon' => 'fa-clipboard-list', 'checked' => true],
                            'stage' => ['label' => 'Stage', 'icon' => 'fa-building', 'checked' => true],
                            'candidature' => ['label' => 'Candidature', 'icon' => 'fa-paper-plane', 'checked' => true],
                            'rapport' => ['label' => 'Rapport', 'icon' => 'fa-file-alt', 'checked' => true],
                            'memoire' => ['label' => 'Mémoire', 'icon' => 'fa-book', 'checked' => true],
                            'validation' => ['label' => 'Validation commission', 'icon' => 'fa-check-double', 'checked' => true],
                            'soutenance' => ['label' => 'Soutenance et jury', 'icon' => 'fa-user-graduate', 'checked' => true],
                            'compte_rendu' => ['label' => 'CR / PV commission', 'icon' => 'fa-file-signature', 'checked' => true],
                            'planning' => ['label' => 'Planning', 'icon' => 'fa-calendar-alt', 'checked' => true],
                            'pv_final' => ['label' => 'PV final', 'icon' => 'fa-award', 'checked' => true],
                        ];
                        foreach ($sections as $key => $sec): ?>
                        <div class="cm-card" style="margin-bottom:1rem;">
                            <div class="cm-card__header" style="cursor:pointer;" data-toggle-section="<?= $key ?>">
                                <label style="cursor:pointer;">
                                    <input type="checkbox" name="<?= $key ?>[enabled]" value="1"
                                           <?= $sec['checked'] ? 'checked' : '' ?>
                                           onclick="event.stopPropagation()">
                                    <i class="fas <?= $sec['icon'] ?>"></i>
                                    <strong><?= htmlspecialchars($sec['label']) ?></strong>
                                </label>
                                <i class="fas fa-chevron-down" style="float:right;"></i>
                            </div>
                            <div class="cm-card__body section-body" id="section-<?= $key ?>" style="display:none;">
                                <p class="cm-text-muted" style="font-size:0.85rem;">
                                    Chargement des options…
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>

                        <!-- Récapitulatif -->
                        <div class="cm-card" style="margin-bottom:1.5rem; border-left:4px solid #27ae60;">
                            <div class="cm-card__header" style="background:#eafaf1;">
                                <h3 class="cm-card__title"><i class="fas fa-clipboard-check"></i> Récapitulatif</h3>
                            </div>
                            <div class="cm-card__body">
                                <div id="rebuild-summary">
                                    <p class="cm-text-muted">Sélectionnez les sections et remplissez les champs, puis cliquez sur "Reconstituer".</p>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="cm-btn is-success is-sm">
                            <i class="fas fa-play"></i> Reconstituer le cycle
                        </button>
                    </form>
                </div>

                <!-- Zone résultat reconstruction -->
                <div id="rebuild-result-zone" style="display:none;"></div>
            </div>
        </div>

        <!-- Zone messages globaux -->
        <div id="global-notice"></div>

    </div>
</section>

<!-- ══════════════════════════════════════════════════════════════
     JAVASCRIPT
     ══════════════════════════════════════════════════════════════ -->
<script>
(function() {
    'use strict';

    // ── Variables globales ────────────────────────────────────
    var baseUrl = <?= json_encode($baseUrl) ?>;
    var selectedStudent = null;
    var currentInventory = null;
    var currentDryRun = null;
    var currentDraft = null;
    var debounceTimer = null;

    // ── Éléments DOM ──────────────────────────────────────────
    var searchInput = document.getElementById('search-etu');
    var searchResults = document.getElementById('search-results');
    var btnSearch = document.getElementById('btn-search');
    var studentIdentity = document.getElementById('student-identity');
    var mainTabs = document.getElementById('main-tabs');
    var btnInventory = document.getElementById('btn-inventory');
    var inventoryZone = document.getElementById('inventory-zone');
    var btnDryRun = document.getElementById('btn-dryrun');
    var dryrunZone = document.getElementById('dryrun-zone');
    var btnPurge = document.getElementById('btn-purge');
    var btnRebuildDraft = document.getElementById('btn-rebuild-draft');
    var rebuildWizard = document.getElementById('rebuild-wizard');
    var rebuildForm = document.getElementById('rebuild-form');
    var confirmCheckbox = document.getElementById('confirm-irreversible');
    var confirmMatricule = document.getElementById('confirm-matricule');
    var globalNotice = document.getElementById('global-notice');

    // ── Recherche étudiant ────────────────────────────────────
    searchInput.addEventListener('input', function() {
        clearTimeout(debounceTimer);
        var q = searchInput.value.trim();
        if (q.length < 2) { searchResults.style.display = 'none'; return; }
        debounceTimer = setTimeout(function() {
            fetch(baseUrl + '&ajax=search&q=' + encodeURIComponent(q), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (!res.success || !res.data || res.data.length === 0) {
                    searchResults.innerHTML = '<div class="cm-etu-autocomplete__item" style="color:#999;">Aucun résultat</div>';
                    searchResults.style.display = 'block';
                    return;
                }
                searchResults.innerHTML = '';
                res.data.forEach(function(item) {
                    var btn = document.createElement('button');
                    btn.type = 'button';
                    btn.className = 'cm-etu-autocomplete__item';
                    btn.textContent = item.num_carte_etud + ' — ' + item.nom_etu + ' ' + item.prenom_etu;
                    btn.addEventListener('click', function() { selectStudent(item); });
                    searchResults.appendChild(btn);
                });
                searchResults.style.display = 'block';
            })
            .catch(function() {
                searchResults.innerHTML = '<div class="cm-etu-autocomplete__item" style="color:#e74c3c;">Erreur de recherche</div>';
                searchResults.style.display = 'block';
            });
        }, 300);
    });

    btnSearch.addEventListener('click', function() {
        var q = searchInput.value.trim();
        if (q.length >= 2) {
            searchInput.dispatchEvent(new Event('input'));
        }
    });

    // Fermer les suggestions au clic extérieur
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.cm-etu-autocomplete')) {
            searchResults.style.display = 'none';
        }
    });

    // ── Sélection étudiant ────────────────────────────────────
    function selectStudent(student) {
        selectedStudent = student;
        searchResults.style.display = 'none';
        searchInput.value = student.num_carte_etud + ' — ' + student.nom_etu + ' ' + student.prenom_etu;

        // Afficher identité
        document.getElementById('id-num-carte').textContent = student.num_carte_etud || '—';
        document.getElementById('id-num-ident').textContent = student.num_ident_etud || '—';
        document.getElementById('id-nom').textContent = student.nom_etu || '—';
        document.getElementById('id-prenom').textContent = student.prenom_etu || '—';
        document.getElementById('id-email').textContent = student.email_etu || '—';
        document.getElementById('id-promotion').textContent = student.promotion_etu || '—';
        studentIdentity.style.display = '';
        mainTabs.style.display = '';

        // Activer les boutons
        btnInventory.disabled = false;
        btnRebuildDraft.disabled = false;

        // Reset des zones
        inventoryZone.style.display = 'none';
        dryrunZone.style.display = 'none';
        document.getElementById('purge-result-zone').style.display = 'none';
        rebuildWizard.style.display = 'none';
        document.getElementById('rebuild-result-zone').style.display = 'none';
        currentInventory = null;
        currentDryRun = null;
    }

    // ── Inventaire ────────────────────────────────────────────
    btnInventory.addEventListener('click', function() {
        if (!selectedStudent) return;
        btnInventory.disabled = true;
        btnInventory.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement…';

        fetch(baseUrl + '&ajax=inventory&num_etu=' + encodeURIComponent(selectedStudent.num_carte_etud), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btnInventory.disabled = false;
            btnInventory.innerHTML = '<i class="fas fa-list"></i> Charger l\'inventaire du cycle';
            if (!res.success) {
                CM.alert.show(globalNotice, 'danger', res.message || 'Erreur lors du chargement.');
                return;
            }
            currentInventory = res;
            renderInventory(res);
        })
        .catch(function() {
            btnInventory.disabled = false;
            btnInventory.innerHTML = '<i class="fas fa-list"></i> Charger l\'inventaire du cycle';
            CM.alert.show(globalNotice, 'danger', 'Erreur réseau.');
        });
    });

    function renderInventory(inv) {
        // Compteurs
        var tbody = document.getElementById('counters-body');
        tbody.innerHTML = '';
        var labels = {
            inscriptions: 'Inscriptions', notes: 'Notes', stage: 'Stage',
            candidatures: 'Candidatures', resume_candidature: 'Résumés candidature',
            rapports: 'Rapports', deposer: 'Dépôts', affecter: 'Affectations',
            valider: 'Validations', evaluations_rapports: 'Évaluations rapports',
            memoires: 'Mémoires', evaluations_memoires: 'Évaluations mémoires',
            soutenances: 'Soutenances', enseignant_jury: 'Jury',
            evaluer: 'Évaluations soutenance', evaluation_soutenance_meta: 'Meta évaluations',
            comptes_rendus_directs: 'CR directs', comptes_rendus_lies: 'CR liés',
            reclamations: 'Réclamations',
            documents_exclusifs: 'Documents exclusifs',
            documents_partages: 'Documents partagés',
            documents_orphelins_apres_detachement: 'Documents orphelins (après détachement)'
        };
        Object.keys(inv.counts).forEach(function(key) {
            var tr = document.createElement('tr');
            tr.className = 'cm-data-table__row';
            tr.innerHTML = '<td class="cm-data-table__td">' + (labels[key] || key) + '</td>' +
                           '<td class="cm-data-table__td" style="text-align:right;">' + inv.counts[key] + '</td>';
            tbody.appendChild(tr);
        });

        // Documents
        var docsBody = document.getElementById('docs-body');
        docsBody.innerHTML = '';
        if (inv.documents && inv.documents.length > 0) {
            inv.documents.forEach(function(doc) {
                var badgeClass = doc.classification === 'exclusif' ? 'is-danger' :
                                 doc.classification === 'partage' ? 'is-info' : 'is-warning';
                var action = doc.classification === 'exclusif' ? 'Supprimer' :
                             doc.classification === 'partage' ? 'Conserver' : 'Supprimer (après détachement)';
                var tr = document.createElement('tr');
                tr.className = 'cm-data-table__row';
                tr.innerHTML = '<td class="cm-data-table__td">' + escHtml(doc.type_document) + '</td>' +
                    '<td class="cm-data-table__td">' + escHtml(doc.reference || '—') + '</td>' +
                    '<td class="cm-data-table__td">' + escHtml((doc.entite_type || '') + ':' + (doc.entite_id || '')) + '</td>' +
                    '<td class="cm-data-table__td"><span class="cm-badge ' + badgeClass + '">' + escHtml(doc.classification) + '</span></td>' +
                    '<td class="cm-data-table__td">' + escHtml(action) + '</td>' +
                    '<td class="cm-data-table__td">' + escHtml(doc.reason || '') + '</td>';
                docsBody.appendChild(tr);
            });
        } else {
            docsBody.innerHTML = '<tr><td class="cm-data-table__td" colspan="6" style="text-align:center;color:#999;">Aucun document trouvé</td></tr>';
        }

        // Avertissements
        var warningsZone = document.getElementById('warnings-zone');
        warningsZone.innerHTML = '';
        if (inv.warnings && inv.warnings.length > 0) {
            inv.warnings.forEach(function(w) {
                warningsZone.innerHTML += '<div class="cm-badge is-warning" style="display:block;margin-bottom:0.5rem;padding:0.5rem;"><i class="fas fa-exclamation-triangle"></i> ' + escHtml(w) + '</div>';
            });
        }

        inventoryZone.style.display = '';
        dryrunZone.style.display = 'none';
    }

    // ── Dry-run ───────────────────────────────────────────────
    btnDryRun.addEventListener('click', function() {
        if (!selectedStudent) return;
        btnDryRun.disabled = true;
        btnDryRun.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Simulation en cours…';

        var formData = new FormData();
        formData.append('num_etu', selectedStudent.num_carte_etud);
        formData.append('csrf_token', <?= json_encode($csrfToken) ?>);

        fetch(baseUrl + '&op=dry_run', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btnDryRun.disabled = false;
            btnDryRun.innerHTML = '<i class="fas fa-play"></i> Simuler la purge (dry-run)';
            if (!res.success) {
                CM.alert.show(globalNotice, 'danger', res.message || 'Erreur lors de la simulation.');
                return;
            }
            currentDryRun = res;
            renderDryRun(res);
        })
        .catch(function() {
            btnDryRun.disabled = false;
            btnDryRun.innerHTML = '<i class="fas fa-play"></i> Simuler la purge (dry-run)';
            CM.alert.show(globalNotice, 'danger', 'Erreur réseau.');
        });
    });

    function renderDryRun(dry) {
        var plan = dry.delete_plan;
        var html = '<h4>Suppressions par table</h4><table class="cm-data-table"><thead><tr>' +
            '<th class="cm-data-table__th">Table</th><th class="cm-data-table__th" style="text-align:right;">Lignes supprimées</th>' +
            '</tr></thead><tbody>';
        var totalRows = 0;
        Object.keys(plan.tables).forEach(function(key) {
            if (plan.tables[key] > 0) {
                html += '<tr><td class="cm-data-table__td">' + escHtml(key) + '</td>' +
                    '<td class="cm-data-table__td" style="text-align:right;">' + plan.tables[key] + '</td></tr>';
                totalRows += plan.tables[key];
            }
        });
        html += '<tr style="font-weight:bold;"><td class="cm-data-table__td">TOTAL</td>' +
            '<td class="cm-data-table__td" style="text-align:right;">' + totalRows + '</td></tr>';
        html += '</tbody></table>';

        // Détachements
        html += '<h4 style="margin-top:1.5rem;">Détachements (lignes conservées)</h4><table class="cm-data-table"><thead><tr>' +
            '<th class="cm-data-table__th">Table</th><th class="cm-data-table__th" style="text-align:right;">Lignes détachées</th>' +
            '</tr></thead><tbody>';
        Object.keys(plan.detachments).forEach(function(key) {
            if (plan.detachments[key] > 0) {
                html += '<tr><td class="cm-data-table__td">' + escHtml(key) + '</td>' +
                    '<td class="cm-data-table__td" style="text-align:right;">' + plan.detachments[key] + '</td></tr>';
            }
        });
        html += '</tbody></table>';

        // Documents
        html += '<h4 style="margin-top:1.5rem;">Documents</h4>';
        html += '<p><span class="cm-badge is-danger">Supprimés</span> ' + plan.documents.delete.length +
                ' &nbsp; <span class="cm-badge is-info">Conservés (partagés)</span> ' + plan.documents.keep_shared.length +
                ' &nbsp; <span class="cm-badge is-warning">Supprimés après détachement</span> ' + plan.documents.delete_after_detach.length + '</p>';

        document.getElementById('dryrun-summary').innerHTML = html;
        dryrunZone.style.display = '';
    }

    // ── Validation purge ──────────────────────────────────────
    confirmCheckbox.addEventListener('change', updatePurgeButton);
    confirmMatricule.addEventListener('input', updatePurgeButton);

    function updatePurgeButton() {
        var ready = confirmCheckbox.checked &&
                    selectedStudent &&
                    confirmMatricule.value.trim() === selectedStudent.num_carte_etud;
        btnPurge.disabled = !ready;
    }

    // ── Exécution purge ───────────────────────────────────────
    btnPurge.addEventListener('click', function() {
        if (!selectedStudent || !currentDryRun) return;

        CM.confirm({
            title: 'Confirmer la purge',
            message: 'Êtes-vous absolument sûr de vouloir purger le cycle de ' +
                     selectedStudent.nom_etu + ' ' + selectedStudent.prenom_etu +
                     ' (' + selectedStudent.num_carte_etud + ') ? Cette action est irréversible.',
            type: 'danger',
            confirmText: 'Oui, purger'
        }).then(function(confirmed) {
            if (!confirmed) return;
            executePurge();
        });
    });

    function executePurge() {
        btnPurge.disabled = true;
        btnPurge.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Purge en cours…';

        var formData = new FormData();
        formData.append('num_etu', selectedStudent.num_carte_etud);
        formData.append('confirm_irreversible', '1');
        formData.append('confirm_matricule', confirmMatricule.value.trim());
        formData.append('csrf_token', <?= json_encode($csrfToken) ?>);

        fetch(baseUrl + '&op=purge', {
            method: 'POST',
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btnPurge.disabled = false;
            btnPurge.innerHTML = '<i class="fas fa-trash-alt"></i> Exécuter la purge';

            var resultZone = document.getElementById('purge-result-zone');
            if (res.success) {
                var d = res.details || {};
                var html = '<div class="cm-card" style="border-left:4px solid #27ae60;margin-bottom:1.5rem;">' +
                    '<div class="cm-card__header" style="background:#eafaf1;"><h3 class="cm-card__title"><i class="fas fa-check-circle" style="color:#27ae60;"></i> Purge terminée</h3></div>' +
                    '<div class="cm-card__body"><p>' + escHtml(res.message) + '</p>' +
                    '<p><strong>Étudiant conservé :</strong> ' + escHtml(res.student.nom_etu) + ' ' + escHtml(res.student.prenom_etu) + ' (' + escHtml(res.student.num_carte_etud) + ')</p>';

                if (d.tables) {
                    html += '<h4>Lignes supprimées par table</h4><ul>';
                    Object.keys(d.tables).forEach(function(k) {
                        if (d.tables[k] > 0) html += '<li>' + escHtml(k) + ' : ' + d.tables[k] + '</li>';
                    });
                    html += '</ul>';
                }
                if (d.documents) {
                    html += '<p><strong>Documents supprimés :</strong> ' + (d.documents.deleted || 0) +
                            ' &nbsp; <strong>Documents conservés (partagés) :</strong> ' + (d.documents.kept_shared || 0) + '</p>';
                }
                html += '</div></div>';
                resultZone.innerHTML = html;
                CM.alert.show(globalNotice, 'success', 'Purge du cycle terminée avec succès.');

                // Désactiver les zones
                inventoryZone.style.display = 'none';
                dryrunZone.style.display = 'none';
            } else {
                CM.alert.show(globalNotice, 'danger', res.message || 'Erreur lors de la purge.');
            }
            resultZone.style.display = '';
        })
        .catch(function() {
            btnPurge.disabled = false;
            btnPurge.innerHTML = '<i class="fas fa-trash-alt"></i> Exécuter la purge';
            CM.alert.show(globalNotice, 'danger', 'Erreur réseau.');
        });
    }

    // ── Brouillon reconstruction ──────────────────────────────
    btnRebuildDraft.addEventListener('click', function() {
        if (!selectedStudent) return;
        btnRebuildDraft.disabled = true;
        btnRebuildDraft.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Chargement…';

        fetch(baseUrl + '&ajax=rebuild_draft&num_etu=' + encodeURIComponent(selectedStudent.num_carte_etud), {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(function(r) { return r.json(); })
        .then(function(res) {
            btnRebuildDraft.disabled = false;
            btnRebuildDraft.innerHTML = '<i class="fas fa-magic"></i> Charger le brouillon de reconstruction';
            if (!res.success) {
                CM.alert.show(globalNotice, 'danger', res.message || 'Erreur.');
                return;
            }
            currentDraft = res;
            renderRebuildWizard(res);
        })
        .catch(function() {
            btnRebuildDraft.disabled = false;
            btnRebuildDraft.innerHTML = '<i class="fas fa-magic"></i> Charger le brouillon de reconstruction';
            CM.alert.show(globalNotice, 'danger', 'Erreur réseau.');
        });
    });

    function renderRebuildWizard(draft) {
        var ref = draft.referentiels;
        var def = draft.defaults;
        var lastIns = def.last_inscription || {};

        // Inscription
        setSectionContent('inscription', buildFormFields('inscription', [
            { name: 'id_annee_acad', label: 'Année académique', type: 'select', options: ref.annees_academiques.map(function(a) { return { value: a.id_annee_acad, label: a.id_annee_acad + ' (' + a.date_deb + ' → ' + a.date_fin + ')' }; }), value: def.id_annee_acad },
            { name: 'id_niv_etude', label: 'Niveau', type: 'text', placeholder: 'Ex: M2', value: lastIns.id_niv_etude || '' },
            { name: 'num_versement', label: 'Numéro versement', type: 'text', placeholder: '1', value: '1' },
            { name: 'montant_verser', label: 'Montant payé', type: 'number', value: lastIns.montant_verser || '' },
            { name: 'date_inscription', label: 'Date inscription', type: 'date', value: lastIns.date_inscription || '' },
            { name: 'document_fiche', label: 'Fiche inscription', type: 'file' },
            { name: 'document_recu', label: 'Reçu', type: 'file' },
        ]));

        // Stage
        setSectionContent('stage', buildFormFields('stage', [
            { name: 'id_entreprise', label: 'Entreprise', type: 'select', options: ref.entreprises.map(function(e) { return { value: e.id_entreprise, label: e.lib_long_entreprise }; }), value: '' },
            { name: 'id_maitre_stage', label: 'Maître de stage', type: 'select', options: ref.maitres_stage.map(function(m) { return { value: m.id_maitre_stage, label: m.Nom + ' ' + m.prenom }; }), value: '' },
            { name: 'sujet_stage', label: 'Sujet', type: 'text', placeholder: 'Sujet du stage' },
            { name: 'date_debut', label: 'Date début', type: 'date' },
            { name: 'date_fin', label: 'Date fin', type: 'date' },
        ]));

        // Candidature
        setSectionContent('candidature', buildFormFields('candidature', [
            { name: 'date_candidature', label: 'Date candidature', type: 'date', value: '' },
            { name: 'statut', label: 'Statut', type: 'select', options: [{ value: 'Validée', label: 'Validée' }, { value: 'En attente', label: 'En attente' }], value: 'Validée' },
            { name: 'resume', label: 'Résumé', type: 'textarea', placeholder: 'Résumé de la candidature' },
        ]));

        // Rapport
        setSectionContent('rapport', buildFormFields('rapport', [
            { name: 'theme', label: 'Thème du rapport', type: 'text', placeholder: 'Thème du rapport' },
            { name: 'id_annee_acad', label: 'Année académique', type: 'select', options: ref.annees_academiques.map(function(a) { return { value: a.id_annee_acad, label: a.id_annee_acad }; }), value: def.id_annee_acad },
            { name: 'date_soumission', label: 'Date soumission', type: 'date' },
            { name: 'depose', label: 'Marquer comme déposé', type: 'checkbox', checked: true },
            { name: 'document_rapport', label: 'Document rapport', type: 'file' },
        ]));

        // Mémoire
        setSectionContent('memoire', '<p class="cm-text-muted" style="font-size:0.85rem;">Le document mémoire sera uploadé lors de la reconstruction.</p>' +
            buildFormFields('memoire', [
                { name: 'theme', label: 'Thème mémoire', type: 'text', placeholder: 'Thème du mémoire' },
                { name: 'document_memoire', label: 'Document mémoire', type: 'file' },
            ]));

        // Validation
        setSectionContent('validation', '<p class="cm-text-muted" style="font-size:0.85rem;">Les affectations et évaluations seront créées avec les données fournies.</p>' +
            buildFormFields('validation', [
                { name: 'valider_date', label: 'Date validation', type: 'date' },
                { name: 'valider_decision', label: 'Décision', type: 'select', options: [{ value: 'Validé', label: 'Validé' }, { value: 'Validé avec réserves', label: 'Avec réserves' }], value: 'Validé' },
            ]));

        // Soutenance
        setSectionContent('soutenance', buildFormFields('soutenance', [
            { name: 'date_soutenance', label: 'Date soutenance', type: 'date' },
            { name: 'heure_debut', label: 'Heure début', type: 'time', value: '09:00' },
            { name: 'heure_fin', label: 'Heure fin', type: 'time', value: '10:00' },
            { name: 'theme_soutenance', label: 'Thème soutenance', type: 'text', placeholder: 'Thème soutenance' },
            { name: 'id_annee_acad', label: 'Année académique', type: 'select', options: ref.annees_academiques.map(function(a) { return { value: a.id_annee_acad, label: a.id_annee_acad }; }), value: def.id_annee_acad },
            { name: 'id_domaine', label: 'Domaine', type: 'select', options: ref.domaines.map(function(d) { return { value: d.id_domaine, label: d.lib_domaine }; }), value: '' },
            { name: 'id_salle', label: 'Salle', type: 'select', options: ref.salles.map(function(s) { return { value: s.id_salle, label: s.lib_salle }; }), value: '' },
            { name: 'id_session', label: 'Session', type: 'select', options: ref.sessions.map(function(s) { return { value: s.id_session, label: s.lib_session }; }), value: '' },
        ]));

        // CR
        var crModeOptions = '<div class="cm-form-group"><label class="cm-form-label">Mode</label>' +
            '<select name="compte_rendu[mode]" class="cm-form-control" id="cr-mode">' +
            '<option value="nouveau">Créer un nouveau CR</option>' +
            '<option value="existant">Rattacher à un CR existant</option></select></div>';
        var crExistingSelect = '<div class="cm-form-group" id="cr-existing-group" style="display:none;">' +
            '<label class="cm-form-label">CR existant</label>' +
            '<select name="compte_rendu[id_CR_existant]" class="cm-form-control">' +
            ref.comptes_rendus_existants.map(function(cr) { return '<option value="' + cr.id_CR + '">' + escHtml(cr.nom_CR) + ' (' + cr.date_CR + ')</option>'; }).join('') +
            '</select></div>';
        var crNewFields = '<div id="cr-new-fields">' + buildFormFields('compte_rendu', [
            { name: 'nom_CR', label: 'Nom du CR', type: 'text', placeholder: 'CR Soutenance ...' },
            { name: 'date_CR', label: 'Date CR', type: 'date' },
            { name: 'document_cr', label: 'Document CR', type: 'file' },
            { name: 'document_pv', label: 'Document PV commission', type: 'file' },
        ]) + '</div>';
        setSectionContent('compte_rendu', crModeOptions + crExistingSelect + crNewFields);

        // Planning
        var planModeOptions = '<div class="cm-form-group"><label class="cm-form-label">Mode</label>' +
            '<select name="planning[mode]" class="cm-form-control" id="plan-mode">' +
            '<option value="nouveau">Créer un nouveau planning</option>' +
            '<option value="existant">Rattacher à un planning existant</option></select></div>';
        var planExistingSelect = '<div class="cm-form-group" id="plan-existing-group" style="display:none;">' +
            '<label class="cm-form-label">Planning existant</label>' +
            '<select name="planning[id_document_planning]" class="cm-form-control">' +
            ref.plannings_existants.map(function(p) { return '<option value="' + p.id_document + '">' + escHtml(p.nom_fichier) + ' (' + p.date_creation + ')</option>'; }).join('') +
            '</select></div>';
        var planNewFields = '<div id="plan-new-fields">' + buildFormFields('planning', [
            { name: 'nom_planning', label: 'Nom du planning', type: 'text', placeholder: 'Planning.pdf' },
            { name: 'document_planning', label: 'Document planning', type: 'file' },
        ]) + '</div>';
        setSectionContent('planning', planModeOptions + planExistingSelect + planNewFields);

        // PV final
        setSectionContent('pv_final', '<p class="cm-text-muted" style="font-size:0.85rem;">Le document PV final sera lié à la soutenance.</p>' +
            buildFormFields('pv_final', [
                { name: 'nom_pv', label: 'Nom du PV', type: 'text', placeholder: 'PV_Final.pdf' },
                { name: 'document_pv_final', label: 'Document PV final', type: 'file' },
            ]));

        rebuildWizard.style.display = '';

        // Événements toggle sections
        document.querySelectorAll('[data-toggle-section]').forEach(function(header) {
            header.addEventListener('click', function() {
                var key = header.getAttribute('data-toggle-section');
                var body = document.getElementById('section-' + key);
                if (body) body.style.display = body.style.display === 'none' ? '' : 'none';
            });
        });

        // Événement mode CR
        var crMode = document.getElementById('cr-mode');
        if (crMode) {
            crMode.addEventListener('change', function() {
                document.getElementById('cr-existing-group').style.display = crMode.value === 'existant' ? '' : 'none';
                document.getElementById('cr-new-fields').style.display = crMode.value === 'nouveau' ? '' : 'none';
            });
        }

        // Événement mode Planning
        var planMode = document.getElementById('plan-mode');
        if (planMode) {
            planMode.addEventListener('change', function() {
                document.getElementById('plan-existing-group').style.display = planMode.value === 'existant' ? '' : 'none';
                var newFields = document.getElementById('plan-new-fields');
                if (newFields) newFields.style.display = planMode.value === 'nouveau' ? '' : 'none';
            });
        }
    }

    function setSectionContent(key, html) {
        var body = document.getElementById('section-' + key);
        if (body) body.innerHTML = html;
    }

    function buildFormFields(section, fields) {
        var html = '<div class="cm-grid-2">';
        fields.forEach(function(f) {
            var name = section + '[' + f.name + ']';
            html += '<div class="cm-form-group">';
            html += '<label class="cm-form-label">' + escHtml(f.label) + '</label>';
            if (f.type === 'select') {
                html += '<select name="' + name + '" class="cm-form-control">';
                html += '<option value="">-- Sélectionner --</option>';
                (f.options || []).forEach(function(opt) {
                    var selected = f.value && String(f.value) === String(opt.value) ? ' selected' : '';
                    html += '<option value="' + escHtml(opt.value) + '"' + selected + '>' + escHtml(opt.label) + '</option>';
                });
                html += '</select>';
            } else if (f.type === 'textarea') {
                html += '<textarea name="' + name + '" class="cm-form-control" placeholder="' + escHtml(f.placeholder || '') + '">' + escHtml(f.value || '') + '</textarea>';
            } else if (f.type === 'checkbox') {
                html += '<input type="checkbox" name="' + name + '" value="1"' + (f.checked ? ' checked' : '') + '>';
            } else if (f.type === 'file') {
                html += '<input type="file" name="' + name + '" class="cm-form-control" accept=".pdf,.doc,.docx,.html,.htm,.png,.jpg,.jpeg">';
            } else {
                html += '<input type="' + f.type + '" name="' + name + '" class="cm-form-control" placeholder="' + escHtml(f.placeholder || '') + '" value="' + escHtml(f.value || '') + '">';
            }
            html += '</div>';
        });
        html += '</div>';
        return html;
    }

    // ── Soumission reconstruction ─────────────────────────────
    if (rebuildForm) {
        rebuildForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (!selectedStudent) return;

            var formData = new FormData(rebuildForm);
            formData.append('num_etu', selectedStudent.num_carte_etud);

            CM.confirm({
                title: 'Confirmer la reconstruction',
                message: 'Voulez-vous reconstruire le cycle de ' + selectedStudent.nom_etu + ' ' + selectedStudent.prenom_etu + ' ?',
                type: 'success',
                confirmText: 'Oui, reconstituer'
            }).then(function(confirmed) {
                if (!confirmed) return;

                fetch(baseUrl + '&op=rebuild', {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(function(r) { return r.json(); })
                .then(function(res) {
                    var resultZone = document.getElementById('rebuild-result-zone');
                    if (res.success) {
                        var d = res.details || {};
                        var html = '<div class="cm-card" style="border-left:4px solid #27ae60;margin-top:1.5rem;">' +
                            '<div class="cm-card__header" style="background:#eafaf1;"><h3 class="cm-card__title"><i class="fas fa-check-circle" style="color:#27ae60;"></i> Reconstruction terminée</h3></div>' +
                            '<div class="cm-card__body"><p>' + escHtml(res.message) + '</p>';
                        if (d.created) {
                            html += '<h4>Lignes créées</h4><ul>';
                            Object.keys(d.created).forEach(function(k) {
                                if (d.created[k] > 0) html += '<li>' + escHtml(k) + ' : ' + d.created[k] + '</li>';
                            });
                            html += '</ul>';
                        }
                        if (d.documents && d.documents.length > 0) {
                            html += '<p><strong>Documents créés :</strong> ' + d.documents.length + '</p>';
                        }
                        html += '</div></div>';
                        resultZone.innerHTML = html;
                        CM.alert.show(globalNotice, 'success', 'Reconstruction terminée avec succès.');
                    } else {
                        CM.alert.show(globalNotice, 'danger', res.message || 'Erreur lors de la reconstruction.');
                    }
                    resultZone.style.display = '';
                })
                .catch(function() {
                    CM.alert.show(globalNotice, 'danger', 'Erreur réseau.');
                });
            });
        });
    }

    // ── Utilitaires ───────────────────────────────────────────
    function escHtml(str) {
        if (str === null || str === undefined) return '';
        var div = document.createElement('div');
        div.textContent = String(str);
        return div.innerHTML;
    }

})();
</script>

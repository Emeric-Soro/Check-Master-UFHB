/**
 * CheckMaster UFRMI - Showcase Interactions
 * Simulates ALL system interactions for the component showcase demo.
 * No backend needed - pure client-side simulation.
 */
(function (window, document) {
    'use strict';

    const SC = {};
    window.ShowcaseCtrl = SC;

    /* ===================== SAMPLE DATA ===================== */
    SC.students = [
        { mat: 'E001', nom: 'KOUAME', prenom: 'Aya', email: 'aya@mail.ci', filiere: 'Informatique', genre: 'F', statut: 'Actif', montant: '350 000' },
        { mat: 'E002', nom: 'TOURE', prenom: 'Ibrahim', email: 'ib@mail.ci', filiere: 'Informatique', genre: 'M', statut: 'Actif', montant: '200 000' },
        { mat: 'E003', nom: 'KOFFI', prenom: 'Marie', email: 'mk@mail.ci', filiere: 'Mathématiques', genre: 'F', statut: 'Inactif', montant: '0' },
        { mat: 'E004', nom: 'DIALLO', prenom: 'Ousmane', email: 'od@mail.ci', filiere: 'Physique', genre: 'M', statut: 'Actif', montant: '350 000' },
        { mat: 'E005', nom: 'BAMBA', prenom: 'Fatou', email: 'fb@mail.ci', filiere: 'Informatique', genre: 'F', statut: 'Actif', montant: '150 000' },
        { mat: 'E006', nom: 'SYLLA', prenom: 'Moussa', email: 'ms@mail.ci', filiere: 'Mathématiques', genre: 'M', statut: 'Actif', montant: '350 000' },
        { mat: 'E007', nom: 'COULIBALY', prenom: 'Aminata', email: 'ac@mail.ci', filiere: 'Physique', genre: 'F', statut: 'Inactif', montant: '50 000' },
        { mat: 'E008', nom: 'TRAORE', prenom: 'Seydou', email: 'st@mail.ci', filiere: 'Informatique', genre: 'M', statut: 'Actif', montant: '350 000' }
    ];

    /* ===================== TOAST SYSTEM ===================== */
    SC.showToast = function (msg, type, duration = 3500) {
        let container = document.getElementById('sc-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'sc-toast-container';
            container.className = 'cm-toast-container';
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = `cm-toast is-${type || 'info'}`;
        toast.innerHTML = `<span class="cm-toast-message">${msg}</span><button class="cm-toast-close">&times;</button>`;
        container.appendChild(toast);

        const dismiss = () => {
            toast.classList.add('is-leaving');
            setTimeout(() => { if (toast.parentNode) toast.remove(); }, 300);
        };

        let t = setTimeout(dismiss, duration);
        toast.querySelector('.cm-toast-close').addEventListener('click', () => { clearTimeout(t); dismiss(); });
        toast.addEventListener('mouseenter', () => { clearTimeout(t); });
        toast.addEventListener('mouseleave', () => { t = setTimeout(dismiss, duration); });
    };

    /* ===================== CRUD SIMULATION ===================== */
    SC.crudMode = 'create'; // 'create' or 'edit'
    SC.editingIndex = -1;

    SC.initCrud = function () {
        const wrapper = document.getElementById('sc-crud-demo');
        if (!wrapper) return;

        const btnNew = wrapper.querySelector('[data-action="new"]');
        const btnSubmit = wrapper.querySelector('[data-action="submit"]');
        const btnCancel = wrapper.querySelector('[data-action="cancel"]');
        const titleEl = wrapper.querySelector('.cm-pole-superieur-title h2');
        const tbody = wrapper.querySelector('tbody');
        const countEl = wrapper.querySelector('.sc-count');
        const searchInput = wrapper.querySelector('.sc-search');

        const getFields = () => ({
            mat: wrapper.querySelector('[name="mat"]'),
            nom: wrapper.querySelector('[name="nom"]'),
            prenom: wrapper.querySelector('[name="prenom"]'),
            email: wrapper.querySelector('[name="email"]'),
            filiere: wrapper.querySelector('[name="filiere"]'),
            genre: wrapper.querySelector('[name="genre"]')
        });

        const clearForm = () => {
            const f = getFields();
            for (let k in f) if (f[k]) f[k].value = '';
            SC.crudMode = 'create';
            SC.editingIndex = -1;
            if (titleEl) titleEl.textContent = 'Nouvel Étudiant';
            if (btnSubmit) {
                btnSubmit.textContent = '✓ Valider';
                btnSubmit.classList.remove('is-success');
                btnSubmit.classList.add('is-primary');
            }
            if (btnCancel) btnCancel.style.display = 'none';
        };

        const renderTable = (data) => {
            if (!tbody) return;
            tbody.innerHTML = '';
            const badgeClass = { 'Actif': 'is-success', 'Inactif': 'is-warning', 'Soldé': 'is-success', 'Partiel': 'is-warning', 'Impayé': 'is-danger' };
            data.forEach((s, i) => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${s.mat}</td>
                    <td>${s.nom} ${s.prenom}</td>
                    <td>${s.email}</td>
                    <td>${s.filiere}</td>
                    <td><span class="cm-badge ${badgeClass[s.statut] || 'is-info'}">${s.statut}</span></td>
                    <td>
                        <div class="cm-row-actions">
                            <button class="button is-info is-small is-outlined" data-action="view" data-idx="${i}">Voir</button>
                            <button class="button is-primary is-small is-outlined" data-action="edit" data-idx="${i}">Modifier</button>
                            <button class="button is-light is-small" data-action="delete" data-idx="${i}">Archiver</button>
                        </div>
                    </td>
                `;
                tbody.appendChild(tr);
            });
            if (countEl) countEl.textContent = data.length;
        };

        const fillForm = (s) => {
            const f = getFields();
            if (f.mat) f.mat.value = s.mat;
            if (f.nom) f.nom.value = s.nom;
            if (f.prenom) f.prenom.value = s.prenom;
            if (f.email) f.email.value = s.email;
            if (f.filiere) f.filiere.value = s.filiere;
            if (f.genre) f.genre.value = s.genre;
        };

        // Events
        if (btnNew) btnNew.addEventListener('click', () => { clearForm(); SC.showToast('Formulaire réinitialisé', 'info'); });
        if (btnCancel) btnCancel.addEventListener('click', () => { clearForm(); SC.showToast('Modification annulée', 'info'); });

        if (btnSubmit) btnSubmit.addEventListener('click', () => {
            const f = getFields();
            const nom = f.nom ? f.nom.value.trim() : '';
            const prenom = f.prenom ? f.prenom.value.trim() : '';
            if (!nom || !prenom) {
                SC.showToast('Veuillez remplir les champs obligatoires', 'error');
                if (f.nom && !nom) { f.nom.classList.add('is-danger'); f.nom.closest('.cm-field').classList.add('has-error'); }
                if (f.prenom && !prenom) { f.prenom.classList.add('is-danger'); f.prenom.closest('.cm-field').classList.add('has-error'); }
                return;
            }
            // Clear errors
            wrapper.querySelectorAll('.is-danger').forEach(el => { el.classList.remove('is-danger'); });
            wrapper.querySelectorAll('.has-error').forEach(el => { el.classList.remove('has-error'); });

            if (SC.crudMode === 'edit' && SC.editingIndex >= 0) {
                SC.students[SC.editingIndex] = {
                    ...SC.students[SC.editingIndex],
                    nom, prenom,
                    email: f.email ? f.email.value : '',
                    filiere: f.filiere ? f.filiere.value : 'Informatique',
                    genre: f.genre ? f.genre.value : 'M'
                };
                SC.showToast(`Étudiant ${nom} ${prenom} modifié avec succès`, 'success');
            } else {
                const newMat = 'E' + String(SC.students.length + 1).padStart(3, '0');
                SC.students.push({
                    mat: newMat, nom, prenom,
                    email: f.email ? f.email.value : '',
                    filiere: f.filiere ? f.filiere.value : 'Informatique',
                    genre: f.genre ? f.genre.value : 'M',
                    statut: 'Actif', montant: '350 000'
                });
                SC.showToast(`Étudiant ${nom} ${prenom} (${newMat}) créé avec succès`, 'success');
            }
            renderTable(SC.students);
            clearForm();
        });

        // Delegation for table actions
        if (tbody) tbody.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-action]');
            if (!btn) return;
            const idx = parseInt(btn.dataset.idx, 10);
            const action = btn.dataset.action;
            const s = SC.students[idx];
            if (!s) return;

            if (action === 'edit') {
                SC.crudMode = 'edit';
                SC.editingIndex = idx;
                fillForm(s);
                if (titleEl) titleEl.textContent = `Modifier: ${s.nom} ${s.prenom}`;
                if (btnSubmit) {
                    btnSubmit.textContent = '✓ Appliquer les modifications';
                    btnSubmit.classList.remove('is-primary');
                    btnSubmit.classList.add('is-success');
                }
                if (btnCancel) btnCancel.style.display = '';
                wrapper.querySelector('.cm-pole-superieur').scrollIntoView({ behavior: 'smooth', block: 'start' });
                SC.showToast('Données chargées dans le formulaire — Mode édition', 'info');
            } else if (action === 'view') {
                SC.openSidePanel(s);
            } else if (action === 'delete') {
                SC.openConfirmModal(idx);
            }
        });

        // Search
        if (searchInput) searchInput.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            const filtered = SC.students.filter(s =>
                (s.mat + s.nom + s.prenom + s.email + s.filiere).toLowerCase().includes(q)
            );
            renderTable(filtered);
        });

        renderTable(SC.students);
        clearForm();
    };

    /* ===================== SIDE PANEL ===================== */
    SC.openSidePanel = function (student) {
        const panel = document.getElementById('sc-side-panel');
        const overlay = document.getElementById('sc-panel-overlay');
        const body = panel ? panel.querySelector('.cm-side-panel-body') : null;
        if (!panel || !body) return;

        body.innerHTML = `
            <dl class="cm-detail-card">
                <dt>Matricule</dt><dd>${student.mat}</dd>
                <dt>Nom complet</dt><dd>${student.nom} ${student.prenom}</dd>
                <dt>Email</dt><dd>${student.email}</dd>
                <dt>Filière</dt><dd>${student.filiere}</dd>
                <dt>Genre</dt><dd>${student.genre === 'F' ? 'Féminin' : 'Masculin'}</dd>
                <dt>Statut</dt><dd><span class="cm-badge ${student.statut === 'Actif' ? 'is-success' : 'is-warning'}">${student.statut}</span></dd>
                <dt>Montant</dt><dd>${student.montant} FCFA</dd>
            </dl>
        `;
        panel.classList.add('is-active');
        if (overlay) overlay.classList.add('is-active');
    };

    SC.closeSidePanel = function () {
        const panel = document.getElementById('sc-side-panel');
        const overlay = document.getElementById('sc-panel-overlay');
        if (panel) panel.classList.remove('is-active');
        if (overlay) overlay.classList.remove('is-active');
    };

    /* ===================== CONFIRM MODAL ===================== */
    SC.pendingDeleteIdx = -1;
    SC.openConfirmModal = function (idx) {
        SC.pendingDeleteIdx = idx;
        const s = SC.students[idx];
        const modal = document.getElementById('sc-confirm-modal');
        if (!modal) return;
        const bodyEl = modal.querySelector('.cm-modal-body');
        if (bodyEl && s) {
            bodyEl.innerHTML = `Voulez-vous vraiment archiver l'étudiant <strong>${s.nom} ${s.prenom} (${s.mat})</strong> ?<br>Cette action est irréversible.`;
        }
        modal.style.display = 'flex';
        modal.classList.add('is-active');
    };

    SC.closeConfirmModal = function () {
        const modal = document.getElementById('sc-confirm-modal');
        if (modal) {
            modal.classList.remove('is-active');
            modal.style.display = 'none';
        }
        SC.pendingDeleteIdx = -1;
    };

    SC.confirmDelete = function () {
        if (SC.pendingDeleteIdx >= 0 && SC.pendingDeleteIdx < SC.students.length) {
            const s = SC.students[SC.pendingDeleteIdx];
            SC.students.splice(SC.pendingDeleteIdx, 1);
            SC.showToast(`Étudiant ${s.nom} ${s.prenom} archivé`, 'success');
            SC.initCrud(); // re-render
        }
        SC.closeConfirmModal();
    };

    /* ===================== SELECT SEARCH (auto-fill) ===================== */
    SC.initSelectSearch = function () {
        const wrapper = document.getElementById('sc-select-search-demo');
        if (!wrapper) return;
        const input = wrapper.querySelector('input');
        const dropdown = wrapper.querySelector('.cm-select-search-dropdown');
        const fillFields = wrapper.closest('.showcase-section');
        if (!input || !dropdown) return;

        input.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            dropdown.innerHTML = '';
            if (q.length < 1) {
                dropdown.classList.remove('is-active');
                return;
            }
            const matches = SC.students.filter(s => (s.nom + ' ' + s.prenom + ' ' + s.mat).toLowerCase().includes(q));
            if (matches.length === 0) {
                dropdown.innerHTML = '<div class="cm-select-search-no-results">Aucun résultat</div>';
            } else {
                matches.forEach(s => {
                    const div = document.createElement('div');
                    div.className = 'cm-select-search-option';
                    div.textContent = `${s.nom} ${s.prenom} - ${s.mat} - ${s.filiere}`;
                    div.dataset.fill = JSON.stringify(s);
                    dropdown.appendChild(div);
                });
            }
            dropdown.classList.add('is-active');
        });

        dropdown.addEventListener('click', (e) => {
            const opt = e.target.closest('.cm-select-search-option');
            if (!opt) return;
            try {
                const data = JSON.parse(opt.dataset.fill);
                input.value = `${data.nom} ${data.prenom}`;
                // Auto-fill demo fields
                const nameF = fillFields ? fillFields.querySelector('[data-fill="nom"]') : null;
                const emailF = fillFields ? fillFields.querySelector('[data-fill="email"]') : null;
                const matF = fillFields ? fillFields.querySelector('[data-fill="mat"]') : null;
                if (nameF) { nameF.value = `${data.nom} ${data.prenom}`; nameF.closest('.cm-field').classList.add('has-success'); }
                if (emailF) { emailF.value = data.email; emailF.closest('.cm-field').classList.add('has-success'); }
                if (matF) { matF.value = data.mat; matF.closest('.cm-field').classList.add('has-success'); }
                SC.showToast(`Champs auto-remplis pour ${data.nom} ${data.prenom}`, 'success');
            } catch (ex) { console.error(ex); }
            dropdown.classList.remove('is-active');
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('#sc-select-search-demo')) dropdown.classList.remove('is-active');
        });
    };

    /* ===================== TABS ===================== */
    SC.initTabs = function () {
        document.querySelectorAll('.sc-tabs-demo').forEach(container => {
            const tabs = container.querySelectorAll('.tabs li');
            const contents = container.querySelectorAll('.cm-tab-content');
            tabs.forEach((tab, i) => {
                tab.addEventListener('click', (e) => {
                    e.preventDefault();
                    tabs.forEach(t => { t.classList.remove('is-active'); });
                    contents.forEach(c => { c.classList.remove('is-active'); c.style.display = 'none'; });
                    tab.classList.add('is-active');
                    if (contents[i]) { contents[i].classList.add('is-active'); contents[i].style.display = ''; }
                });
            });
        });
    };

    /* ===================== PAGINATION ===================== */
    SC.initPagination = function () {
        document.querySelectorAll('.sc-pagination-demo').forEach(container => {
            const links = container.querySelectorAll('.pagination-link');
            const prev = container.querySelector('.pagination-previous');
            const next = container.querySelector('.pagination-next');
            links.forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    links.forEach(l => { l.classList.remove('is-current'); });
                    link.classList.add('is-current');
                    SC.showToast(`Page ${link.textContent} sélectionnée`, 'info');
                    if (prev) prev.toggleAttribute('disabled', link.textContent === '1');
                    if (next) next.toggleAttribute('disabled', link.textContent === '10');
                });
            });
            if (prev) prev.addEventListener('click', function (e) { e.preventDefault(); if (!this.hasAttribute('disabled')) SC.showToast('Page précédente', 'info'); });
            if (next) next.addEventListener('click', function (e) { e.preventDefault(); if (!this.hasAttribute('disabled')) SC.showToast('Page suivante', 'info'); });
        });
    };

    /* ===================== SIDEBAR DEMO ===================== */
    SC.initSidebarDemo = function () {
        const sidebar = document.querySelector('#sc-sidebar-demo .cm-sidebar');
        if (!sidebar) return;
        sidebar.addEventListener('click', (e) => {
            const title = e.target.closest('.cm-menu-section-title');
            if (title) {
                title.parentElement.classList.toggle('is-collapsed');
                return;
            }
            const parent = e.target.closest('.cm-menu-parent > a');
            if (parent) {
                parent.parentElement.classList.toggle('is-collapsed');
                return;
            }
            const link = e.target.closest('.cm-menu-item a');
            if (link) {
                e.preventDefault();
                sidebar.querySelectorAll('.cm-menu-item a').forEach(a => { a.classList.remove('is-active'); });
                link.classList.add('is-active');
                SC.showToast(`Navigation: ${link.textContent.trim()}`, 'info');
            }
        });
    };

    /* ===================== SORT TABLE DEMO ===================== */
    SC.initSortDemo = function () {
        document.querySelectorAll('.sc-sort-demo th.cm-sortable').forEach(th => {
            th.addEventListener('click', () => {
                const header = th.querySelector('.cm-column-header');
                if (!header) return;
                const wasAsc = header.classList.contains('is-sorted-asc');
                document.querySelectorAll('.sc-sort-demo .cm-column-header').forEach(h => { h.classList.remove('is-sorted-asc', 'is-sorted-desc'); });
                header.classList.add(wasAsc ? 'is-sorted-desc' : 'is-sorted-asc');
                SC.showToast(`Tri par ${th.textContent.trim()} ${wasAsc ? ' ↓' : ' ↑'}`, 'info');
            });
        });
    };

    /* ===================== TIMELINE EXPAND ===================== */
    SC.initTimeline = function () {
        document.querySelectorAll('.cm-timeline-step').forEach(step => {
            step.addEventListener('click', () => {
                const wasExpanded = step.classList.contains('is-expanded');
                step.closest('.cm-timeline').querySelectorAll('.cm-timeline-step').forEach(s => { s.classList.remove('is-expanded'); });
                if (!wasExpanded) step.classList.add('is-expanded');
            });
        });
    };

    /* ===================== FORM VALIDATION DEMO ===================== */
    SC.initFormValidation = function () {
        const form = document.getElementById('sc-validation-form');
        if (!form) return;
        form.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="validate-demo"]')) {
                let valid = true;
                form.querySelectorAll('[data-rules]').forEach(input => {
                    const field = input.closest('.cm-field');
                    const rules = input.dataset.rules.split('|');
                    const val = input.value.trim();
                    field.classList.remove('has-error', 'has-success');
                    input.classList.remove('is-danger');
                    const existingHelp = field.querySelector('.help.is-danger');
                    if (existingHelp) { existingHelp.remove(); }

                    rules.forEach(rule => {
                        if (rule === 'required' && !val) {
                            field.classList.add('has-error');
                            input.classList.add('is-danger');
                            const h = document.createElement('p');
                            h.className = 'help is-danger';
                            h.textContent = 'Champ requis';
                            input.parentElement.appendChild(h);
                            valid = false;
                        }
                        if (rule === 'email' && val && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val)) {
                            field.classList.add('has-error');
                            input.classList.add('is-danger');
                            const h2 = document.createElement('p');
                            h2.className = 'help is-danger';
                            h2.textContent = 'Email invalide';
                            input.parentElement.appendChild(h2);
                            valid = false;
                        }
                    });
                    if (!field.classList.contains('has-error') && val) { field.classList.add('has-success'); }
                });
                if (valid) SC.showToast('Formulaire valide ! Prêt à soumettre.', 'success');
                else SC.showToast('Erreurs de validation détectées', 'error');
            }
        });
    };

    /* ===================== EDITOR TOOLBAR DEMO ===================== */
    SC.initEditorDemo = function () {
        document.querySelectorAll('.cm-rich-editor-toolbar').forEach(toolbar => {
            toolbar.addEventListener('click', (e) => {
                const btn = e.target.closest('button');
                if (!btn) return;
                btn.classList.toggle('is-active');
            });
        });
    };

    /* ===================== EXPORT/PRINT BUTTONS ===================== */
    SC.initExportButtons = function () {
        document.addEventListener('click', (e) => {
            const btn = e.target.closest('[data-demo-action]');
            if (!btn) return;
            const action = btn.dataset.demoAction;
            if (action === 'export') SC.showToast('📥 Export CSV lancé — fichier en cours de génération...', 'success');
            else if (action === 'import') SC.showToast('📤 Fenêtre d\'import ouverte', 'info');
            else if (action === 'print') SC.showToast('🖨 Impression en cours...', 'info');
        });
    };

    /* ===================== ALERT DISMISS ===================== */
    SC.initAlerts = function () {
        document.addEventListener('click', (e) => {
            const del = e.target.closest('.cm-alert .delete, .notification .delete');
            if (!del) return;
            const notif = del.closest('.notification, .cm-alert');
            if (notif) {
                notif.style.opacity = '0';
                notif.style.transform = 'translateX(30px)';
                notif.style.transition = 'all 300ms ease';
                setTimeout(() => { notif.style.display = 'none'; }, 300);
            }
        });
    };

    /* ===================== BUTTON INTERACTS ===================== */
    SC.toggleLoading = function (btn) {
        if (!btn) return;
        btn.classList.add('is-loading');
        SC.showToast('Chargement simulé...', 'info', 2000);
        setTimeout(() => {
            btn.classList.remove('is-loading');
            SC.showToast('Action terminée', 'success');
        }, 2000);
    };

    SC.simulateRipple = function (e) {
        const btn = e.currentTarget;
        const ripple = document.createElement('span');
        ripple.className = 'cm-ripple-effect';
        const rect = btn.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        ripple.style.width = ripple.style.height = `${size}px`;
        ripple.style.left = `${x}px`;
        ripple.style.top = `${y}px`;
        btn.appendChild(ripple);
        setTimeout(() => { ripple.remove(); }, 600);
    };

    /* ===================== GLOBAL INIT ===================== */
    document.addEventListener('DOMContentLoaded', () => {
        SC.initCrud();
        SC.initSelectSearch();
        SC.initTabs();
        SC.initPagination();
        SC.initSidebarDemo();
        SC.initSortDemo();
        SC.initTimeline();
        SC.initFormValidation();
        SC.initEditorDemo();
        SC.initExportButtons();
        SC.initAlerts();

        // Side panel close events
        document.addEventListener('click', (e) => {
            if (e.target.closest('#sc-panel-overlay') || e.target.closest('.cm-side-panel-close')) SC.closeSidePanel();
            if (e.target.closest('#sc-modal-cancel') || e.target.closest('#sc-modal-overlay-bg')) SC.closeConfirmModal();
            if (e.target.closest('#sc-modal-confirm')) SC.confirmDelete();
        });
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') { SC.closeSidePanel(); SC.closeConfirmModal(); }
        });

        // Smooth nav scroll & Active link highlight & Maintain scroll
        const navLinks = document.querySelectorAll('.showcase-nav a');
        navLinks.forEach(a => {
            a.addEventListener('click', function (e) {
                const href = a.getAttribute('href');
                if (href && href.startsWith('#')) {
                    e.preventDefault();
                    const target = document.querySelector(href);
                    if (target) {
                        // Maintain sidebar scroll position
                        const navEl = a.closest('.showcase-nav');
                        const sidebarScroll = navEl.scrollTop;
                        
                        target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                        
                        // Restore sidebar scroll position (browser might jump)
                        setTimeout(() => {
                            navEl.scrollTop = sidebarScroll;
                        }, 0);

                        // Highlight active nav
                        navLinks.forEach(l => { l.classList.remove('is-active-nav'); });
                        a.classList.add('is-active-nav');
                        
                        // Update URL without jump
                        history.pushState(null, null, href);
                    }
                }
            });
        });

        // Initialize scroll position for sidebar
        const currentHash = window.location.hash;
        if (currentHash) {
            const activeLink = document.querySelector(`.showcase-nav a[href="${currentHash}"]`);
            if (activeLink) activeLink.classList.add('is-active-nav');
        }
    });

})(window, document);


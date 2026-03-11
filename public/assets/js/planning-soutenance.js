/**
 * Module Planning Soutenance
 * Intègre PlanningGeneratorService via les endpoints du ProgrammationSoutenanceController.
 */
const PlanningSoutenance = {
    config: {},
    currentData: null,
    selectedDate: null,

    init(config) {
        this.config = config || {};
        this.bindEvents();
        this.loadPreview();
    },

    bindEvents() {
        document.getElementById('btnPreviewPlanning')
            ?.addEventListener('click', () => this.loadPreview());

        document.getElementById('btnGeneratePlanningPdf')
            ?.addEventListener('click', () => this.generatePdf());
    },

    getFilters() {
        return {
            session_id: document.getElementById('planningSession')?.value ?? '',
            date_from: document.getElementById('planningDateFrom')?.value ?? '',
            date_to: document.getElementById('planningDateTo')?.value ?? '',
        };
    },

    // ─── Prévisualisation ───────────────────────────────────────────────────

    async loadPreview() {
        const f = this.getFilters();
        const qs = new URLSearchParams({
            page: 'programmation_soutenance',
            action: 'getPlanningPreview',
            session_id: f.session_id,
            date_from: f.date_from,
            date_to: f.date_to,
        });

        const calContainer = document.getElementById('planningCalendar');
        if (calContainer) {
            calContainer.innerHTML = '<div class="cm-empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div>';
        }

        try {
            const res = await fetch('layout.php?' + qs);
            const json = await res.json();
            if (json.success) {
                this.currentData = json.data;
                this.renderCalendar();
            } else {
                this.showError(calContainer, json.error ?? 'Erreur de chargement');
            }
        } catch (e) {
            this.showError(calContainer, 'Erreur réseau : ' + e.message);
        }
    },

    renderCalendar() {
        const container = document.getElementById('planningCalendar');
        if (!container || !this.currentData) return;

        const dates = this.currentData.dates ?? {};

        if (Object.keys(dates).length === 0) {
            container.innerHTML = '<div class="cm-empty-state"><i class="fas fa-calendar-times"></i><p>Aucune soutenance dans cette période</p></div>';
            return;
        }

        let html = '';
        Object.entries(dates).forEach(([date, info]) => {
            const cls = info.conflits > 0 ? 'is-warning' : 'is-success';
            const conflictIcon = info.conflits > 0
                ? `<i class="fas fa-exclamation-triangle" title="${info.conflits} conflit(s)"></i>`
                : '<i class="fas fa-check-circle"></i>';
            html += `
                <div class="cm-calendar-day ${cls}" data-date="${date}" role="button" tabindex="0">
                    <span class="cm-calendar-date">${this.formatDateShort(date)}</span>
                    <span class="cm-calendar-count">${info.count} soutenance(s)</span>
                    ${conflictIcon}
                </div>
            `;
        });
        container.innerHTML = html;

        // Summary
        const totalEl = document.getElementById('planningTotalCount');
        if (totalEl) {
            totalEl.textContent = this.currentData.total_soutenances ?? 0;
        }

        container.querySelectorAll('.cm-calendar-day').forEach(el => {
            el.addEventListener('click', () => this.selectDate(el.dataset.date));
            el.addEventListener('keydown', e => { if (e.key === 'Enter') this.selectDate(el.dataset.date); });
        });
    },

    // ─── Détails d'une journée ──────────────────────────────────────────────

    async selectDate(date) {
        this.selectedDate = date;
        const label = document.getElementById('selectedDateLabel');
        if (label) label.textContent = '— ' + this.formatDateLong(date);

        // Highlight active day
        document.querySelectorAll('.cm-calendar-day').forEach(el => el.classList.remove('is-active'));
        const activeEl = document.querySelector(`.cm-calendar-day[data-date="${date}"]`);
        if (activeEl) activeEl.classList.add('is-active');

        const detailContainer = document.getElementById('planningDayDetails');
        if (detailContainer) {
            detailContainer.innerHTML = '<div class="cm-empty-state"><i class="fas fa-spinner fa-spin"></i><p>Chargement…</p></div>';
        }

        try {
            const res = await fetch(`layout.php?page=programmation_soutenance&action=getDayDetails&date=${date}`);
            const json = await res.json();
            if (json.success) this.renderDayDetails(json.data);
            else this.showError(detailContainer, json.error);
        } catch (e) {
            this.showError(detailContainer, 'Erreur réseau');
        }
    },

    renderDayDetails(data) {
        const container = document.getElementById('planningDayDetails');
        if (!container) return;

        let html = '';

        // Summary bar
        html += `<div class="cm-planning-day-summary">
            <span><strong>${(data.soutenances || []).length}</strong> soutenance(s)</span>
            <span>Salles : ${(data.salles_utilisees || []).join(', ') || 'N/A'}</span>
        </div>`;

        // Conflict alerts
        if (data.conflicts?.length > 0) {
            html += '<div class="cm-planning-conflict-alert"><strong><i class="fas fa-exclamation-triangle"></i> Conflits :</strong><ul>';
            data.conflicts.forEach(c => {
                const detail = c.type === 'Salle en double'
                    ? `${c.lib_salle || '?'} à ${(c.heure_soutenance || '').substring(0, 5)}`
                    : `${c.nom_jury || '?'} à ${(c.heure_soutenance || '').substring(0, 5)}`;
                html += `<li>${c.type} — ${detail}</li>`;
            });
            html += '</ul></div>';
        }

        // Soutenances table
        if (data.soutenances?.length > 0) {
            html += `<div class="cm-table-wrapper cm-mt-3"><table class="cm-data-table cm-data-table--sm">
                <thead><tr>
                    <th class="cm-data-table__th">Heure</th>
                    <th class="cm-data-table__th">Étudiant</th>
                    <th class="cm-data-table__th">Salle</th>
                    <th class="cm-data-table__th">Jury</th>
                </tr></thead><tbody>`;
            data.soutenances.forEach(s => {
                const heure = (s.heure_debut ?? '').substring(0, 5);
                const nom = `${s.prenom_etudiant ?? ''} ${s.nom_etudiant ?? ''}`.trim();
                const salle = s.lib_salle ?? 'N/A';
                const jury = s.jury ?? '—';
                html += `<tr class="cm-data-table__row">
                    <td class="cm-data-table__td"><strong>${this.escHtml(heure)}</strong></td>
                    <td class="cm-data-table__td">${this.escHtml(nom)}</td>
                    <td class="cm-data-table__td">${this.escHtml(salle)}</td>
                    <td class="cm-data-table__td cm-text-sm">${this.escHtml(jury)}</td>
                </tr>`;
            });
            html += '</tbody></table></div>';
        } else {
            html += '<div class="cm-empty-state cm-mt-3"><p>Aucune soutenance ce jour</p></div>';
        }

        // Day-specific PDF button
        html += `<div class="cm-mt-3" style="text-align:right">
            <button type="button" class="cm-btn is-outline" onclick="PlanningSoutenance.generatePdfForDate('${data.date}')">
                <i class="fas fa-file-pdf"></i> PDF de ce jour
            </button>
        </div>`;

        container.innerHTML = html;
    },

    // ─── Génération PDF ─────────────────────────────────────────────────────

    async generatePdf(dateFrom, dateTo) {
        const f = this.getFilters();
        const body = {
            session_id: f.session_id || null,
            date_from: dateFrom ?? (f.date_from || null),
            date_to: dateTo ?? (f.date_to || null),
        };

        this.showLoadingModal('Génération du planning PDF en cours…');

        try {
            const res = await fetch('layout.php?page=programmation_soutenance&action=generatePlanningPdf', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(body),
            });
            const json = await res.json();
            this.hideLoadingModal();

            if (json.success) {
                this.showSuccessModal(json.reference, json.download_url);
            } else {
                alert('Erreur : ' + (json.error ?? 'Inconnue'));
            }
        } catch (e) {
            this.hideLoadingModal();
            alert('Erreur réseau lors de la génération.');
        }
    },

    generatePdfForDate(date) {
        this.generatePdf(date, date);
    },

    // ─── Modals ─────────────────────────────────────────────────────────────

    showLoadingModal(message) {
        const overlay = document.createElement('div');
        overlay.id = 'planningLoadingOverlay';
        overlay.className = 'cm-planning-modal-overlay';
        overlay.innerHTML = `
            <div class="cm-planning-modal cm-text-center">
                <i class="fas fa-spinner fa-spin fa-2x" style="margin-bottom:12px;color:var(--cm-primary,#3273DC)"></i>
                <p>${message}</p>
            </div>`;
        document.body.appendChild(overlay);
    },

    hideLoadingModal() {
        document.getElementById('planningLoadingOverlay')?.remove();
    },

    showSuccessModal(reference, downloadUrl) {
        const overlay = document.createElement('div');
        overlay.id = 'planningSuccessOverlay';
        overlay.className = 'cm-planning-modal-overlay';
        overlay.innerHTML = `
            <div class="cm-planning-modal" style="max-width:460px">
                <h3 style="margin:0 0 8px"><i class="fas fa-check-circle" style="color:var(--cm-success,#48c774)"></i> Planning généré</h3>
                <p>Référence : <strong>${this.escHtml(reference)}</strong></p>
                <div style="margin-top:16px;display:flex;gap:8px;justify-content:flex-end">
                    <a href="${downloadUrl}" target="_blank" class="cm-btn is-primary" download>
                        <i class="fas fa-download"></i> Télécharger
                    </a>
                    <button type="button" class="cm-btn is-light" onclick="document.getElementById('planningSuccessOverlay').remove()">
                        Fermer
                    </button>
                </div>
            </div>`;
        document.body.appendChild(overlay);
    },

    // ─── Utilitaires ────────────────────────────────────────────────────────

    showError(container, msg) {
        if (container) {
            container.innerHTML = '<div class="cm-alert is-danger"><div class="cm-alert__content"><span class="cm-alert__message"><i class="fas fa-times-circle"></i> '
                + this.escHtml(msg ?? 'Erreur') + '</span></div></div>';
        }
    },

    escHtml(str) {
        return String(str ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    },

    formatDateShort(dateStr) {
        return new Date(dateStr).toLocaleDateString('fr-FR', { day: 'numeric', month: 'short' });
    },

    formatDateLong(dateStr) {
        return new Date(dateStr).toLocaleDateString('fr-FR',
            { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
    },
};

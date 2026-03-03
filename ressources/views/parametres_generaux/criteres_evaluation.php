<?php
$pageSlug = (string) ($_GET['page'] ?? 'parametres_specifiques');
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <div class="cm-crud-wrapper">
        <?php
        ob_start();
        ?>
        <form id="cmCritereForm" class="cm-form" autocomplete="off">
            <div class="cm-grid-2">
                <?php cm_component('form/input-text', [
                    'name' => 'lib_critere',
                    'id' => 'cmCritereLibelle',
                    'label' => 'Libelle critere',
                    'required' => true,
                    'placeholder' => 'Ex: Qualite de la presentation',
                ]); ?>
                <div class="cm-form-group">
                    <label class="cm-form-label">Barèmes par année</label>
                    <div id="cmBaremesRows"></div>
                    <button type="button" class="cm-btn is-light is-sm" id="cmAddBaremeRow">
                        <i class="fas fa-plus" aria-hidden="true"></i>
                        <span>Ajouter une année</span>
                    </button>
                </div>
            </div>
            <?php
            cm_component('crud/form-actions', [
                'actions' => [
                    [
                        'tag' => 'button',
                        'type' => 'button',
                        'label' => 'Réinitialiser',
                        'icon' => 'fa-rotate-left',
                        'class' => 'cm-btn is-light',
                        'attrs' => ['id' => 'cmResetCritereForm'],
                    ],
                    [
                        'tag' => 'button',
                        'type' => 'submit',
                        'label' => 'Enregistrer',
                        'icon' => 'fa-save',
                        'class' => 'cm-btn is-success',
                        'attrs' => ['id' => 'cmSubmitCritere'],
                    ],
                ],
            ]);
            ?>
        </form>
        <?php
        cm_component('crud/form-pole', [
            'title' => '',
            'icon' => 'fa-list-ol',
            'content' => (string) ob_get_clean(),
        ]);
        ?>

        <?php
        ob_start();
        ?>
        <label class="cm-toolbar__control">
            <span>Annee:</span>
            <select id="cmCritFilterYear" class="cm-form-control cm-toolbar__select">
                <option value="">Toutes</option>
            </select>
        </label>
        <?php
        $leftHtml = (string) ob_get_clean();

        ob_start();
        ?>
        <div class="cm-toolbar__search-wrap">
            <input type="search" class="cm-form-control" id="cmCritSearch" placeholder="Rechercher un critere...">
        </div>
        <?php
        $centerHtml = (string) ob_get_clean();

        ob_start();
        ?>
        <div class="cm-toolbar__actions">
            <button type="button" class="cm-btn is-info is-sm" id="cmCritPrint">
                <i class="fas fa-print" aria-hidden="true"></i>
                <span>Imprimer</span>
            </button>
            <button type="button" class="cm-btn is-info is-sm" id="cmCritExport">
                <i class="fas fa-file-export" aria-hidden="true"></i>
                <span>Exporter</span>
            </button>
        </div>
        <?php
        cm_component('crud/toolbar', [
            'left_html' => $leftHtml,
            'center_html' => $centerHtml,
            'right_html' => (string) ob_get_clean(),
        ]);
        ?>

        <div class="cm-pole-inferieur">
            <div id="cmCritereNotice"></div>
            <div class="cm-table-wrapper">
                <table class="cm-data-table" id="cmCritereTable">
                    <thead>
                        <tr>
                            <th class="cm-data-table__th">Critere</th>
                            <th class="cm-data-table__th">Baremes</th>
                            <th class="cm-data-table__th is-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="cmCritereTableBody">
                        <tr>
                            <td colspan="3" class="cm-data-table__td is-center">Chargement...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>

<template id="cmBaremeRowTpl">
    <div class="cm-grid-2 cm-bareme-row">
        <select class="cm-form-control js-bareme-year">
            <option value="">-- Sélectionner année --</option>
        </select>
        <div class="cm-bareme-row__value-wrap">
            <input type="number" min="0" max="20" class="cm-form-control js-bareme-value" placeholder="Bareme">
            <button type="button" class="cm-btn-action is-delete js-remove-bareme" aria-label="Supprimer">
                <i class="fas fa-trash" aria-hidden="true"></i>
            </button>
        </div>
    </div>
</template>

<script>
(function () {
    const routeBase = '?page=criteres_evaluation&action=';
    const yearsFilter = document.getElementById('cmCritFilterYear');
    const searchInput = document.getElementById('cmCritSearch');
    const tableBody = document.getElementById('cmCritereTableBody');
    const noticeBox = document.getElementById('cmCritereNotice');
    const form = document.getElementById('cmCritereForm');
    const baremesRows = document.getElementById('cmBaremesRows');
    const addRowBtn = document.getElementById('cmAddBaremeRow');
    const resetBtn = document.getElementById('cmResetCritereForm');
    const libelleInput = document.getElementById('cmCritereLibelle');

    let annees = [];
    let criteres = [];
    let editingId = null;

    const esc = function (v) {
        return String(v || '').replace(/[&<>"']/g, function (s) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[s];
        });
    };

    function showNotice(type, message) {
        const cls = type === 'success' ? 'success' : 'danger';
        noticeBox.innerHTML = '<div class="cm-alert is-' + cls + '">' + esc(message) + '</div>';
        setTimeout(function () {
            noticeBox.innerHTML = '';
        }, 4500);
    }

    function makeYearOptions(selected) {
        let html = '<option value="">-- Sélectionner année --</option>';
        annees.forEach(function (annee) {
            const value = String(annee.id || '');
            const isSel = String(selected || '') === value ? ' selected' : '';
            html += '<option value="' + esc(value) + '"' + isSel + '>' + esc(annee.lib || value) + '</option>';
        });
        return html;
    }

    function addBaremeRow(yearValue, baremeValue) {
        const tpl = document.getElementById('cmBaremeRowTpl');
        if (!tpl) {
            return;
        }
        const node = tpl.content.firstElementChild.cloneNode(true);
        const yearSelect = node.querySelector('.js-bareme-year');
        const valueInput = node.querySelector('.js-bareme-value');
        const removeBtn = node.querySelector('.js-remove-bareme');
        yearSelect.innerHTML = makeYearOptions(yearValue || '');
        valueInput.value = baremeValue || '';
        removeBtn.addEventListener('click', function () {
            node.remove();
            if (baremesRows.children.length === 0) {
                addBaremeRow('', '');
            }
        });
        baremesRows.appendChild(node);
    }

    function resetForm() {
        editingId = null;
        libelleInput.value = '';
        baremesRows.innerHTML = '';
        addBaremeRow('', '');
        const submit = document.getElementById('cmSubmitCritere');
        if (submit) {
            submit.querySelector('span').textContent = 'Enregistrer';
        }
    }

    function collectBaremes() {
        const out = [];
        baremesRows.querySelectorAll('.cm-bareme-row').forEach(function (row) {
            const y = row.querySelector('.js-bareme-year').value;
            const b = row.querySelector('.js-bareme-value').value;
            if (y !== '' && b !== '') {
                out.push({ annee_id: y, bareme: Number(b) });
            }
        });
        return out;
    }

    function filterData() {
        const q = (searchInput.value || '').toLowerCase().trim();
        const year = yearsFilter.value || '';
        return criteres.filter(function (row) {
            const hitsSearch = q === '' || String(row.libelle || '').toLowerCase().includes(q);
            const hitsYear = year === '' || (row.baremes || []).some(function (b) {
                return String(b.annee_id || '') === year;
            });
            return hitsSearch && hitsYear;
        });
    }

    function renderTable() {
        const data = filterData();
        if (data.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="3" class="cm-data-table__td is-center">Aucun critere trouve.</td></tr>';
            return;
        }

        tableBody.innerHTML = data.map(function (row) {
            const baremes = (row.baremes || []).map(function (b) {
                return '<span class="cm-badge is-info cm-bareme-badge">' + esc((b.annee_lib || b.annee_id) + ': ' + b.bareme) + '</span>';
            }).join(' ');
            return '' +
                '<tr class="cm-data-table__row">' +
                    '<td class="cm-data-table__td">' + esc(row.libelle) + '</td>' +
                    '<td class="cm-data-table__td">' + (baremes || '<span class="cm-text-muted">Aucun bareme</span>') + '</td>' +
                    '<td class="cm-data-table__td is-center">' +
                        '<button type="button" class="cm-btn-action is-edit js-edit" data-id="' + esc(row.id) + '" aria-label="Modifier"><i class="fas fa-pen"></i></button>' +
                        '<button type="button" class="cm-btn-action is-delete js-delete" data-id="' + esc(row.id) + '" aria-label="Supprimer"><i class="fas fa-trash"></i></button>' +
                    '</td>' +
                '</tr>';
        }).join('');
    }

    function loadYears() {
        return fetch(routeBase + 'getAnnees')
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    throw new Error(res.message || 'Erreur chargement années');
                }
                annees = Array.isArray(res.data) ? res.data : [];
                yearsFilter.innerHTML = '<option value="">Toutes</option>' + annees.map(function (a) {
                    return '<option value="' + esc(a.id) + '">' + esc(a.lib) + '</option>';
                }).join('');
            });
    }

    function loadCriteres() {
        return fetch(routeBase + 'getCriteres')
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (!res.success) {
                    throw new Error(res.message || 'Erreur chargement criteres');
                }
                criteres = Array.isArray(res.data) ? res.data : [];
                renderTable();
            });
    }

    function submitCritere(payload) {
        const action = editingId ? 'updateCritere' : 'createCritere';
        return fetch(routeBase + action, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        }).then(function (r) { return r.json(); });
    }

    function deleteCritere(id) {
        return fetch(routeBase + 'deleteCritere', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: id })
        }).then(function (r) { return r.json(); });
    }

    form.addEventListener('submit', function (event) {
        event.preventDefault();
        const libelle = (libelleInput.value || '').trim();
        const baremes = collectBaremes();
        if (libelle === '') {
            showNotice('danger', 'Le libelle est obligatoire.');
            return;
        }
        if (baremes.length === 0) {
            showNotice('danger', 'Au moins un bareme est obligatoire.');
            return;
        }

        const payload = { libelle: libelle, baremes: baremes };
        if (editingId) {
            payload.id = editingId;
        }

        submitCritere(payload).then(function (res) {
            if (!res.success) {
                throw new Error(res.message || 'Erreur enregistrement');
            }
            showNotice('success', res.message || 'Enregistrement reussi');
            resetForm();
            return loadCriteres();
        }).catch(function (err) {
            showNotice('danger', err.message || 'Erreur serveur');
        });
    });

    if (addRowBtn) {
        addRowBtn.addEventListener('click', function () {
            addBaremeRow('', '');
        });
    }
    if (resetBtn) {
        resetBtn.addEventListener('click', function () {
            resetForm();
        });
    }

    yearsFilter.addEventListener('change', renderTable);
    searchInput.addEventListener('input', renderTable);

    tableBody.addEventListener('click', function (event) {
        const editBtn = event.target.closest('.js-edit');
        if (editBtn) {
            const id = Number(editBtn.getAttribute('data-id') || '0');
            const row = criteres.find(function (c) { return Number(c.id) === id; });
            if (!row) {
                return;
            }
            editingId = id;
            libelleInput.value = row.libelle || '';
            baremesRows.innerHTML = '';
            (row.baremes || []).forEach(function (b) {
                addBaremeRow(String(b.annee_id || ''), String(b.bareme || ''));
            });
            if (baremesRows.children.length === 0) {
                addBaremeRow('', '');
            }
            const submit = document.getElementById('cmSubmitCritere');
            if (submit) {
                submit.querySelector('span').textContent = 'Mettre a jour';
            }
            return;
        }

        const deleteBtn = event.target.closest('.js-delete');
        if (deleteBtn) {
            const id = Number(deleteBtn.getAttribute('data-id') || '0');
            if (!id) {
                return;
            }
            if (!window.confirm('Supprimer ce critere ?')) {
                return;
            }
            deleteCritere(id).then(function (res) {
                if (!res.success) {
                    throw new Error(res.message || 'Erreur suppression');
                }
                showNotice('success', res.message || 'Suppression reussie');
                if (editingId === id) {
                    resetForm();
                }
                return loadCriteres();
            }).catch(function (err) {
                showNotice('danger', err.message || 'Erreur serveur');
            });
        }
    });

    document.getElementById('cmCritPrint').addEventListener('click', function () {
        const table = document.getElementById('cmCritereTable');
        const w = window.open('', '_blank');
        if (!w || !table) {
            return;
        }
        w.document.write('<html><head><title>Impression</title><style>body{font-family:Arial;padding:16px}table{width:100%;border-collapse:collapse}th,td{border:1px solid #d1d5db;padding:8px}th{background:#f3f4f6}</style></head><body>');
        w.document.write(table.outerHTML);
        w.document.write('</body></html>');
        w.document.close();
        w.print();
    });

    document.getElementById('cmCritExport').addEventListener('click', function () {
        const rows = [['Critere', 'Année', 'Bareme']];
        filterData().forEach(function (c) {
            (c.baremes || []).forEach(function (b) {
                rows.push([c.libelle || '', b.annee_lib || b.annee_id || '', String(b.bareme || '')]);
            });
        });
        const csv = rows.map(function (line) {
            return line.map(function (cell) {
                return '"' + String(cell).replace(/"/g, '""') + '"';
            }).join(';');
        }).join('\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'criteres_evaluation.csv';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    });

    Promise.all([loadYears(), loadCriteres()]).then(function () {
        resetForm();
    }).catch(function (err) {
        showNotice('danger', err.message || 'Erreur de chargement');
        resetForm();
    });
})();
</script>

<?php
require_once __DIR__ . '/../../../app/utils/permissions_helper.php';

$listeEtudiants = $GLOBALS['listeEtudiants'] ?? [];
$editEtudiant = $GLOBALS['etudiant_a_modifier'] ?? null;
$currentPage = (int) ($GLOBALS['currentPage'] ?? 1);
$itemsPerPage = (int) ($GLOBALS['itemsPerPage'] ?? 25);
$totalItems = (int) ($GLOBALS['totalItems'] ?? 0);
$totalPages = (int) ($GLOBALS['totalPages'] ?? 1);
$startIndex = (int) ($GLOBALS['startIndex'] ?? 0);
$endIndex = (int) ($GLOBALS['endIndex'] ?? 0);
$searchTerm = (string) ($GLOBALS['searchTerm'] ?? '');
$listeNiveaux = $GLOBALS['listeNiveaux'] ?? [];
$listeAnneesAcad = $GLOBALS['listeAnneesAcad'] ?? [];
$allowedPerPage = [10, 25, 50, 100];

$baseQuery = $_GET;
unset($baseQuery['crud_action'], $baseQuery['num_etu']);
$baseQuery['page'] = 'gestion_etudiants';
$baseQuery['action'] = 'ajouter_des_etudiants';
$buildUrl = static function (array $extra = []) use ($baseQuery): string {
    return '?' . http_build_query(array_merge($baseQuery, $extra));
};
?>

<div class="mx-auto max-w-7xl px-4 py-6" style="background-color:#DFF2FF;">
    <?php if (!empty($GLOBALS['messageSuccess'])): ?>
        <div class="mb-4 rounded-lg border border-green-300 bg-green-50 px-4 py-3 text-green-700"><?= htmlspecialchars($GLOBALS['messageSuccess']) ?></div>
    <?php endif; ?>
    <?php if (!empty($GLOBALS['messageErreur'])): ?>
        <div class="mb-4 rounded-lg border border-red-300 bg-red-50 px-4 py-3 text-red-700"><?= htmlspecialchars($GLOBALS['messageErreur']) ?></div>
    <?php endif; ?>

    <section class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" style="min-height:40vh;">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4">
            <h2 class="text-xl font-bold text-gray-800">Mise à jour Étudiant</h2>
            <p class="text-sm text-gray-600">CRUD_ETUDIANT</p>
        </div>

        <form id="crudEtudiantForm" method="post" action="<?= htmlspecialchars($buildUrl()) ?>" class="p-6">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\CheckMaster\Core\Csrf::token()) ?>">
            <input type="hidden" name="crud_action" id="crud_action" value="<?= $editEtudiant ? 'update' : 'create' ?>">
            <input type="hidden" name="num_etu" id="num_etu" value="<?= htmlspecialchars($editEtudiant->num_carte_etud ?? '') ?>">

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Matricule</label><input id="matricule_display" type="text" readonly value="<?= htmlspecialchars($editEtudiant->num_carte_etud ?? 'Auto-généré') ?>" class="w-full rounded-md border border-gray-300 bg-gray-100 px-3 py-2 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Niveau</label><select id="id_niveau" name="id_niveau" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"><option value="">Sélectionner</option><?php foreach ($listeNiveaux as $niveau): ?><option value="<?= (int) $niveau->id_niv_etude ?>" <?= ($editEtudiant && (int) $editEtudiant->id_niveau === (int) $niveau->id_niv_etude) ? 'selected' : '' ?>><?= htmlspecialchars($niveau->lib_niv_etude) ?></option><?php endforeach; ?></select></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Promotion</label><input id="promotion_etu" name="promotion_etu" type="text" value="<?= htmlspecialchars($editEtudiant->promotion_etu ?? '') ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Année académique</label><select id="id_annee_acad" name="id_annee_acad" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"><option value="">Sélectionner</option><?php foreach ($listeAnneesAcad as $annee): ?><option value="<?= (int) $annee->id_annee_acad ?>" <?= ($editEtudiant && (int) $editEtudiant->id_annee_acad === (int) $annee->id_annee_acad) ? 'selected' : '' ?>><?= htmlspecialchars(date('Y', strtotime($annee->date_deb)) . '-' . date('Y', strtotime($annee->date_fin))) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-4">
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Identifiant MESRS</label><input id="identifiant_mesrs" name="identifiant_mesrs" type="text" value="<?= htmlspecialchars($editEtudiant->identifiant_mesrs ?? '') ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Nom *</label><input id="nom_etu" name="nom_etu" type="text" required value="<?= htmlspecialchars($editEtudiant->nom_etu ?? '') ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Prénom *</label><input id="prenom_etu" name="prenom_etu" type="text" required value="<?= htmlspecialchars($editEtudiant->prenom_etu ?? '') ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Date de naissance *</label><input id="date_naiss_etu" name="date_naiss_etu" type="date" required value="<?= htmlspecialchars($editEtudiant->date_naiss_etu ?? '') ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
            </div>
            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                <div><label class="mb-1 block text-sm font-medium text-gray-700">Genre *</label><select id="genre_etu" name="genre_etu" required class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"><option value="">Sélectionner</option><option value="1" <?= ($editEtudiant && (int) $editEtudiant->genre_etu === 1) ? 'selected' : '' ?>>Masculin</option><option value="2" <?= ($editEtudiant && (int) $editEtudiant->genre_etu === 2) ? 'selected' : '' ?>>Féminin</option><option value="3" <?= ($editEtudiant && (int) $editEtudiant->genre_etu === 3) ? 'selected' : '' ?>>Neutre</option></select></div>
                <div class="md:col-span-2"><label class="mb-1 block text-sm font-medium text-gray-700">Email *</label><input id="email_etu" name="email_etu" type="email" required value="<?= htmlspecialchars($editEtudiant->email_etu ?? '') ?>" class="w-full rounded-md border border-gray-300 px-3 py-2 text-sm"></div>
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <button type="button" id="cancelEditBtn" class="rounded-md bg-gray-500 px-5 py-2 text-sm font-medium text-white hover:bg-gray-600">Annuler</button>
                <?php if (canCreate() || canEdit()): ?>
                    <button type="submit" id="submitBtn" class="rounded-md px-5 py-2 text-sm font-medium text-white <?= $editEtudiant ? 'bg-blue-500 hover:bg-blue-600' : 'bg-emerald-500 hover:bg-emerald-600' ?>">
                        <?= $editEtudiant ? 'Appliquer les modifications' : "Valider l'enregistrement" ?>
                    </button>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="mt-6 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm" style="min-height:60vh;">
        <div class="border-b border-gray-200 bg-gray-50 px-6 py-4"><h3 class="text-lg font-semibold text-gray-800">Liste des étudiants</h3></div>
        <div class="flex flex-col gap-3 px-6 py-4 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex flex-wrap items-center gap-3">
                <label class="text-sm text-gray-700">Lignes
                    <select id="perPageSelect" class="ml-2 rounded-md border border-gray-300 px-3 py-2 text-sm"><?php foreach ($allowedPerPage as $size): ?><option value="<?= $size ?>" <?= $itemsPerPage === $size ? 'selected' : '' ?>><?= $size ?></option><?php endforeach; ?></select>
                </label>
                <div class="relative w-72 max-w-full">
                    <input type="text" id="searchInput" value="<?= htmlspecialchars($searchTerm) ?>" placeholder="Rechercher..." class="w-full rounded-md border border-gray-300 py-2 pl-10 pr-3 text-sm">
                    <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400"><i class="fas fa-search"></i></span>
                </div>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="<?= htmlspecialchars($buildUrl(['crud_action' => 'export_csv'])) ?>" class="rounded-md bg-emerald-600 px-3 py-2 text-sm text-white hover:bg-emerald-700"><i class="fas fa-file-export mr-1"></i>Exporter</a>
                <button type="button" id="importTriggerBtn" class="rounded-md bg-orange-500 px-3 py-2 text-sm text-white hover:bg-orange-600"><i class="fas fa-file-import mr-1"></i>Importer</button>
                <a target="_blank" href="<?= htmlspecialchars($buildUrl(['crud_action' => 'print_pdf'])) ?>" class="rounded-md bg-blue-600 px-3 py-2 text-sm text-white hover:bg-blue-700"><i class="fas fa-print mr-1"></i>Imprimer</a>
            </div>
        </div>
        <div class="px-6 pb-2 text-xs text-gray-500">Import CSV avec mapping automatique des en-têtes usuels.</div>

        <form id="importForm" class="hidden" method="post" enctype="multipart/form-data" action="<?= htmlspecialchars($buildUrl(['p' => 1])) ?>">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\CheckMaster\Core\Csrf::token()) ?>">
            <input type="hidden" name="crud_action" value="import_csv">
            <input type="file" id="importFileInput" name="import_file" accept=".csv,text/csv">
        </form>

        <div class="overflow-x-auto px-6 pb-4">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-100">
                    <tr><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Matricule</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Nom</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Prénom</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Promotion</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Email</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Téléphone</th><th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Genre</th><th class="px-4 py-3 text-center text-xs font-semibold uppercase text-gray-600">Actions</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php if (empty($listeEtudiants)): ?>
                        <tr><td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">Aucun étudiant actif trouvé.</td></tr>
                    <?php else: ?>
                        <?php foreach ($listeEtudiants as $etudiant): ?>
                            <?php
                            $payload = [
                                'num_etu' => $etudiant->num_carte_etud ?? '',
                                'nom_etu' => $etudiant->nom_etu ?? '',
                                'prenom_etu' => $etudiant->prenom_etu ?? '',
                                'date_naiss_etu' => $etudiant->date_naiss_etu ?? '',
                                'genre_etu' => (string) ($etudiant->genre_etu ?? ''),
                                'email_etu' => $etudiant->email_etu ?? '',
                                'promotion_etu' => $etudiant->promotion_etu ?? '',
                                'id_niveau' => $etudiant->id_niveau ?? '',
                                'id_annee_acad' => $etudiant->id_annee_acad ?? '',
                                'identifiant_mesrs' => $etudiant->identifiant_mesrs ?? '',
                                'telephone_etu' => $etudiant->telephone_etu ?? '',
                                'lib_niv_etude' => $etudiant->lib_niv_etude ?? '',
                                'annee' => (!empty($etudiant->date_deb) && !empty($etudiant->date_fin)) ? date('Y', strtotime($etudiant->date_deb)) . '-' . date('Y', strtotime($etudiant->date_fin)) : '',
                                'libelle_genre' => $etudiant->libelle_genre ?? ''
                            ];
                            ?>
                            <tr class="hover:bg-blue-50/40">
                                <td class="px-4 py-3 text-sm font-medium text-gray-800"><?= htmlspecialchars($etudiant->num_carte_etud ?? '') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($etudiant->nom_etu ?? '') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($etudiant->prenom_etu ?? '') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($etudiant->promotion_etu ?? '') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($etudiant->email_etu ?? '') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($etudiant->telephone_etu ?? '-') ?></td>
                                <td class="px-4 py-3 text-sm text-gray-700"><?= htmlspecialchars($etudiant->libelle_genre ?? $etudiant->genre_etu ?? '') ?></td>
                                <td class="px-4 py-3 text-center text-sm">
                                    <div class="inline-flex items-center gap-2">
                                        <button type="button" class="detail-btn rounded-md bg-indigo-500 px-2 py-1 text-xs text-white hover:bg-indigo-600" data-student='<?= htmlspecialchars(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'><i class="fas fa-eye"></i></button>
                                        <?php if (canEdit()): ?><button type="button" class="edit-btn rounded-md bg-blue-500 px-2 py-1 text-xs text-white hover:bg-blue-600" data-student='<?= htmlspecialchars(json_encode($payload, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP), ENT_QUOTES, 'UTF-8') ?>'><i class="fas fa-pen"></i></button><?php endif; ?>
                                        <?php if (canDelete()): ?><button type="button" class="archive-btn rounded-md bg-red-500 px-2 py-1 text-xs text-white hover:bg-red-600" data-num-etu="<?= htmlspecialchars($etudiant->num_carte_etud ?? '') ?>" data-label="<?= htmlspecialchars(trim(($etudiant->nom_etu ?? '') . ' ' . ($etudiant->prenom_etu ?? ''))) ?>"><i class="fas fa-archive"></i></button><?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="flex flex-col items-start justify-between gap-3 border-t border-gray-200 px-6 py-4 text-sm text-gray-600 md:flex-row md:items-center">
            <div><?= $totalItems > 0 ? 'Affichage de ' . ($startIndex + 1) . ' à ' . $endIndex . ' sur ' . $totalItems . ' étudiants.' : 'Aucun résultat.' ?></div>
            <?php if ($totalPages > 1): ?>
                <nav class="inline-flex overflow-hidden rounded-md border border-gray-300">
                    <a href="<?= htmlspecialchars($currentPage > 1 ? $buildUrl(['p' => $currentPage - 1]) : '#') ?>" class="px-3 py-2 <?= $currentPage <= 1 ? 'pointer-events-none text-gray-300' : 'hover:bg-gray-100 text-gray-700' ?>"><i class="fas fa-chevron-left"></i></a>
                    <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?><a href="<?= htmlspecialchars($buildUrl(['p' => $i])) ?>" class="px-3 py-2 <?= $i === $currentPage ? 'bg-blue-100 text-blue-700 font-semibold' : 'text-gray-700 hover:bg-gray-100' ?>"><?= $i ?></a><?php endfor; ?>
                    <a href="<?= htmlspecialchars($currentPage < $totalPages ? $buildUrl(['p' => $currentPage + 1]) : '#') ?>" class="px-3 py-2 <?= $currentPage >= $totalPages ? 'pointer-events-none text-gray-300' : 'hover:bg-gray-100 text-gray-700' ?>"><i class="fas fa-chevron-right"></i></a>
                </nav>
            <?php endif; ?>
        </div>
    </section>
</div>

<div id="detailOverlay" class="fixed inset-0 z-40 hidden bg-black/30"></div>
<aside id="detailPanel" class="fixed right-0 top-0 z-50 h-full w-full translate-x-full overflow-y-auto bg-white shadow-2xl transition-transform duration-300 sm:max-w-[80%] lg:max-w-[42%] xl:max-w-[34%]">
    <div class="sticky top-0 border-b border-gray-200 bg-white px-6 py-4"><div class="flex items-center justify-between"><h4 class="text-lg font-semibold text-gray-800">Détail étudiant</h4><button id="closeDetailPanelBtn" type="button" class="text-gray-500 hover:text-gray-700"><i class="fas fa-times text-lg"></i></button></div></div>
    <div class="space-y-4 px-6 py-5 text-sm" id="detailPanelBody"></div>
</aside>

<div id="archiveModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/40 px-4">
    <div class="w-full max-w-md rounded-lg bg-white p-6 shadow-xl">
        <h5 class="mb-2 text-lg font-semibold text-gray-800">Confirmer l’archivage</h5>
        <p class="mb-4 text-sm text-gray-600">L’étudiant <span id="archiveStudentLabel" class="font-semibold text-gray-800"></span> sera archivé (actif = 0).</p>
        <form method="post" action="<?= htmlspecialchars($buildUrl()) ?>" id="archiveForm" class="flex justify-end gap-3">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(\CheckMaster\Core\Csrf::token()) ?>">
            <input type="hidden" name="crud_action" value="archive">
            <input type="hidden" name="num_etu" id="archiveNumEtu">
            <button type="button" id="cancelArchiveBtn" class="rounded-md bg-gray-500 px-4 py-2 text-sm text-white hover:bg-gray-600">Annuler</button>
            <button type="submit" class="rounded-md bg-red-600 px-4 py-2 text-sm text-white hover:bg-red-700">Archiver</button>
        </form>
    </div>
</div>

<script>
(() => {
    const searchInput = document.getElementById('searchInput');
    const perPageSelect = document.getElementById('perPageSelect');
    const importTriggerBtn = document.getElementById('importTriggerBtn');
    const importFileInput = document.getElementById('importFileInput');
    const importForm = document.getElementById('importForm');
    const submitBtn = document.getElementById('submitBtn');
    const cancelEditBtn = document.getElementById('cancelEditBtn');
    const crudActionInput = document.getElementById('crud_action');
    const numEtuInput = document.getElementById('num_etu');
    const matriculeDisplayInput = document.getElementById('matricule_display');
    const detailPanel = document.getElementById('detailPanel');
    const detailOverlay = document.getElementById('detailOverlay');
    const detailPanelBody = document.getElementById('detailPanelBody');
    const closeDetailPanelBtn = document.getElementById('closeDetailPanelBtn');
    const archiveModal = document.getElementById('archiveModal');
    const archiveNumEtu = document.getElementById('archiveNumEtu');
    const archiveStudentLabel = document.getElementById('archiveStudentLabel');
    const cancelArchiveBtn = document.getElementById('cancelArchiveBtn');

    const fields = {
        id_niveau: document.getElementById('id_niveau'),
        promotion_etu: document.getElementById('promotion_etu'),
        id_annee_acad: document.getElementById('id_annee_acad'),
        identifiant_mesrs: document.getElementById('identifiant_mesrs'),
        nom_etu: document.getElementById('nom_etu'),
        prenom_etu: document.getElementById('prenom_etu'),
        date_naiss_etu: document.getElementById('date_naiss_etu'),
        genre_etu: document.getElementById('genre_etu'),
        email_etu: document.getElementById('email_etu')
    };

    const baseParams = () => { const p = new URLSearchParams(window.location.search); p.set('page', 'gestion_etudiants'); p.set('action', 'ajouter_des_etudiants'); p.delete('crud_action'); p.delete('num_etu'); return p; };
    const goList = (search, perPage, page) => { const p = baseParams(); p.set('search', search || ''); p.set('per_page', perPage || '25'); p.set('p', page || '1'); window.location.search = p.toString(); };

    let searchDebounce = null;
    if (searchInput) searchInput.addEventListener('input', () => { clearTimeout(searchDebounce); searchDebounce = setTimeout(() => goList(searchInput.value.trim(), perPageSelect ? perPageSelect.value : '25', '1'), 300); });
    if (perPageSelect) perPageSelect.addEventListener('change', () => goList(searchInput ? searchInput.value.trim() : '', perPageSelect.value, '1'));
    if (importTriggerBtn && importFileInput) { importTriggerBtn.addEventListener('click', () => importFileInput.click()); importFileInput.addEventListener('change', () => { if (importFileInput.files.length > 0) importForm.submit(); }); }

    function setCreateMode() {
        document.getElementById('crudEtudiantForm').reset();
        crudActionInput.value = 'create';
        numEtuInput.value = '';
        matriculeDisplayInput.value = 'Auto-généré';
        if (submitBtn) { submitBtn.textContent = "Valider l'enregistrement"; submitBtn.classList.remove('bg-blue-500', 'hover:bg-blue-600'); submitBtn.classList.add('bg-emerald-500', 'hover:bg-emerald-600'); }
    }

    function setEditMode(student) {
        crudActionInput.value = 'update';
        numEtuInput.value = student.num_etu || '';
        matriculeDisplayInput.value = student.num_etu || '';
        fields.id_niveau.value = student.id_niveau || '';
        fields.promotion_etu.value = student.promotion_etu || '';
        fields.id_annee_acad.value = student.id_annee_acad || '';
        fields.identifiant_mesrs.value = student.identifiant_mesrs || '';
        fields.nom_etu.value = student.nom_etu || '';
        fields.prenom_etu.value = student.prenom_etu || '';
        fields.date_naiss_etu.value = student.date_naiss_etu || '';
        fields.genre_etu.value = student.genre_etu || '';
        fields.email_etu.value = student.email_etu || '';
        if (submitBtn) { submitBtn.textContent = 'Appliquer les modifications'; submitBtn.classList.remove('bg-emerald-500', 'hover:bg-emerald-600'); submitBtn.classList.add('bg-blue-500', 'hover:bg-blue-600'); }
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    if (cancelEditBtn) cancelEditBtn.addEventListener('click', setCreateMode);
    document.querySelectorAll('.edit-btn').forEach((btn) => btn.addEventListener('click', () => setEditMode(JSON.parse(btn.getAttribute('data-student') || '{}'))));

    function openDetail(student) {
        detailPanelBody.innerHTML = `<div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
            <div><span class="font-semibold">Matricule:</span><div>${student.num_etu || '-'}</div></div>
            <div><span class="font-semibold">Identifiant MESRS:</span><div>${student.identifiant_mesrs || '-'}</div></div>
            <div><span class="font-semibold">Nom:</span><div>${student.nom_etu || '-'}</div></div>
            <div><span class="font-semibold">Prénom:</span><div>${student.prenom_etu || '-'}</div></div>
            <div><span class="font-semibold">Naissance:</span><div>${student.date_naiss_etu || '-'}</div></div>
            <div><span class="font-semibold">Genre:</span><div>${student.libelle_genre || student.genre_etu || '-'}</div></div>
            <div class="sm:col-span-2"><span class="font-semibold">Email:</span><div>${student.email_etu || '-'}</div></div>
            <div><span class="font-semibold">Téléphone:</span><div>${student.telephone_etu || '-'}</div></div>
            <div><span class="font-semibold">Promotion:</span><div>${student.promotion_etu || '-'}</div></div>
            <div><span class="font-semibold">Niveau:</span><div>${student.lib_niv_etude || '-'}</div></div>
            <div><span class="font-semibold">Année académique:</span><div>${student.annee || '-'}</div></div>
        </div>`;
        detailOverlay.classList.remove('hidden');
        detailPanel.classList.remove('translate-x-full');
    }
    function closeDetail() { detailPanel.classList.add('translate-x-full'); detailOverlay.classList.add('hidden'); }
    document.querySelectorAll('.detail-btn').forEach((btn) => btn.addEventListener('click', () => openDetail(JSON.parse(btn.getAttribute('data-student') || '{}'))));
    if (closeDetailPanelBtn) closeDetailPanelBtn.addEventListener('click', closeDetail);
    if (detailOverlay) detailOverlay.addEventListener('click', closeDetail);

    function openArchive(numEtu, label) { archiveNumEtu.value = numEtu; archiveStudentLabel.textContent = label || numEtu; archiveModal.classList.remove('hidden'); archiveModal.classList.add('flex'); }
    function closeArchive() { archiveModal.classList.remove('flex'); archiveModal.classList.add('hidden'); }
    document.querySelectorAll('.archive-btn').forEach((btn) => btn.addEventListener('click', () => openArchive(btn.getAttribute('data-num-etu') || '', btn.getAttribute('data-label') || '')));
    if (cancelArchiveBtn) cancelArchiveBtn.addEventListener('click', closeArchive);
    if (archiveModal) archiveModal.addEventListener('click', (e) => { if (e.target === archiveModal) closeArchive(); });

    const presetEdit = <?= json_encode($editEtudiant, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    if (presetEdit && presetEdit.num_carte_etud) {
        setEditMode({
            num_etu: presetEdit.num_carte_etud,
            id_niveau: presetEdit.id_niveau || '',
            promotion_etu: presetEdit.promotion_etu || '',
            id_annee_acad: presetEdit.id_annee_acad || '',
            identifiant_mesrs: presetEdit.identifiant_mesrs || '',
            nom_etu: presetEdit.nom_etu || '',
            prenom_etu: presetEdit.prenom_etu || '',
            date_naiss_etu: presetEdit.date_naiss_etu || '',
            genre_etu: String(presetEdit.genre_etu || ''),
            email_etu: presetEdit.email_etu || ''
        });
    }
})();
</script>

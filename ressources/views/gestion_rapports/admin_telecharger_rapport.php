<?php
$etudiantsSansRapport = $GLOBALS['etudiantsSansRapport'] ?? [];
$rapportsAdmin = $GLOBALS['rapportsAdmin'] ?? [];
$typesAutorises = $GLOBALS['typesAutorises'] ?? 'pdf,doc,docx';
$tailleMax = $GLOBALS['tailleMax'] ?? 20971520;

$messageSuccess = $_SESSION['success'] ?? null;
$messageError = $_SESSION['error'] ?? null;
unset($_SESSION['success'], $_SESSION['error']);

$searchTerm = isset($_GET['search']) ? trim((string) $_GET['search']) : '';
$dateSysteme = date('Y-m-d\TH:i');
$basePage = (isset($_GET['page']) && $_GET['page'] === 'telecharger_rapport')
    ? 'telecharger_rapport'
    : 'gestion_rapports';
$csrfTokenValue = class_exists('\CheckMaster\Core\Csrf')
    ? \CheckMaster\Core\Csrf::token()
    : '';

$formatText = static function ($value, $fallback = '-') {
    $value = trim((string) $value);
    return $value !== '' ? $value : $fallback;
};

$formatPromotion = static function ($value) use ($formatText) {
    $value = \FormattingUtils::formatPromotion((string) $value);
    return $formatText($value);
};

$formatDate = static function ($value, $fallback = '-') {
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }
    return \FormattingUtils::formatDate($value, 'd/m/Y');
};

$formatDateTime = static function ($value, $fallback = '-') {
    $value = trim((string) $value);
    if ($value === '') {
        return $fallback;
    }
    return \FormattingUtils::formatDateTime($value, 'd/m/Y H:i');
};

$formatStagePeriod = static function ($start, $end) use ($formatDate) {
    $start = trim((string) $start);
    $end = trim((string) $end);

    if ($start === '' && $end === '') {
        return '-';
    }
    if ($start !== '' && $end !== '') {
        return $formatDate($start) . ' au ' . $formatDate($end);
    }
    if ($start !== '') {
        return 'Depuis le ' . $formatDate($start);
    }
    return 'Jusqu au ' . $formatDate($end);
};

$formatFileSize = static function ($bytes) {
    $bytes = (int) $bytes;
    if ($bytes <= 0) {
        return '-';
    }
    if ($bytes >= 1024 * 1024) {
        return number_format($bytes / (1024 * 1024), 2, ',', ' ') . ' Mo';
    }
    return number_format($bytes / 1024, 1, ',', ' ') . ' Ko';
};

$getBadge = static function ($statut) {
    $statut = strtolower(trim((string) $statut));
    if ($statut === 'valider') {
        return ['class' => 'is-success', 'label' => 'Valide'];
    }
    if ($statut === 'rejeter') {
        return ['class' => 'is-danger', 'label' => 'Rejete'];
    }
    if ($statut === 'en_cours' || $statut === 'en_attente') {
        return ['class' => 'is-info', 'label' => 'En attente'];
    }
    return ['class' => 'is-light', 'label' => 'Brouillon'];
};
?>

<style>
    .cm-rapport-admin-alert {
        margin-bottom: 0.75rem;
        padding: 0.85rem 1rem;
        border-radius: 0.75rem;
        border: 1px solid transparent;
        font-weight: 600;
    }

    .cm-rapport-admin-alert.is-success {
        background: #eaf8ef;
        border-color: #bfe7ca;
        color: #1f6a3d;
    }

    .cm-rapport-admin-alert.is-danger {
        background: #fdeeee;
        border-color: #f5c6c6;
        color: #a03232;
    }

    .cm-rapport-admin-form-note {
        margin: 0;
        color: #6d7c8b;
        line-height: 1.6;
    }

    .cm-rapport-admin-form-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.85rem 1rem;
        align-items: start;
    }

    .cm-rapport-admin-form-grid .cm-form-group {
        max-width: none !important;
        width: 100% !important;
    }

    .cm-rapport-admin-form-grid .cm-form-control,
    .cm-rapport-admin-form-grid input:not([type="checkbox"]):not([type="radio"]):not([type="hidden"]),
    .cm-rapport-admin-form-grid select,
    .cm-rapport-admin-form-grid textarea {
        width: 100% !important;
        max-width: none !important;
    }

    .cm-rapport-admin-span-2 {
        grid-column: 1 / -1;
    }

    .cm-rapport-admin-info {
        display: none;
        margin-top: 0.35rem;
        padding: 0.95rem;
        border: 1px solid var(--cm-border-color, #d8e2eb);
        border-radius: 0.9rem;
        background: var(--cm-app-bg, linear-gradient(180deg, #dff2ff 0%, #d6ecff 52%, #cfe6fb 100%));
    }

    .cm-rapport-admin-info__title {
        margin: 0 0 0.8rem 0;
        font-size: 1rem;
        color: #223046;
    }

    .cm-rapport-admin-info__grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.75rem;
    }

    .cm-rapport-admin-info__item {
        padding: 0.7rem 0.8rem;
        border: 1px solid #e6edf4;
        border-radius: 0.75rem;
        background: var(--cm-content-bg, rgba(237, 246, 255, 0.84));
    }

    .cm-rapport-admin-info__label {
        display: block;
        margin-bottom: 0.25rem;
        font-size: 0.74rem;
        text-transform: uppercase;
        letter-spacing: 0.04em;
        color: #7b8794;
    }

    .cm-rapport-admin-info__value {
        color: #223046;
        font-weight: 600;
        word-break: break-word;
    }

    .cm-rapport-admin-help {
        display: block;
        margin-top: 0.3rem;
        color: #758292;
        font-size: 0.82rem;
        line-height: 1.5;
    }

    .cm-rapport-admin-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
        align-items: center;
    }

    .cm-rapport-admin-table-head {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 1rem;
        flex-wrap: wrap;
        margin-bottom: 0.85rem;
    }

    .cm-rapport-admin-table-head h2 {
        margin: 0 0 0.3rem 0;
        font-size: 1.12rem;
    }

    .cm-rapport-admin-table-head p {
        margin: 0;
        color: #5d6b7a;
    }

    .cm-rapport-admin-filter {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
        align-items: end;
    }

    .cm-rapport-admin-filter .cm-form-group {
        max-width: none !important;
        width: 100% !important;
        min-width: 240px;
        flex: 1 1 280px;
    }

    .cm-rapport-admin-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.3rem 0.7rem;
        border-radius: 999px;
        font-size: 0.78rem;
        font-weight: 700;
        white-space: nowrap;
    }

    .cm-rapport-admin-badge.is-success {
        background: #e9f8ef;
        color: #167c41;
    }

    .cm-rapport-admin-badge.is-danger {
        background: #fdecec;
        color: #b42318;
    }

    .cm-rapport-admin-badge.is-info {
        background: #eaf3ff;
        color: #175cd3;
    }

    .cm-rapport-admin-badge.is-light {
        background: #eef2f6;
        color: #516173;
    }

    .cm-rapport-admin-empty {
        padding: 1.4rem;
        text-align: center;
        color: #708090;
    }

    #dateOperationModal {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(0, 0, 0, 0.45);
        z-index: 9999;
        align-items: center;
        justify-content: center;
    }

    #dateOperationModal .cm-rapport-admin-modal {
        width: min(92vw, 440px);
        background: #fff;
        border-radius: 0.9rem;
        padding: 1.25rem;
        box-shadow: 0 16px 38px rgba(0, 0, 0, 0.18);
    }

    #dateOperationModal .cm-rapport-admin-modal h3 {
        margin: 0 0 0.85rem 0;
        font-size: 1.05rem;
    }

    @media (max-width: 900px) {
        .cm-rapport-admin-form-grid {
            grid-template-columns: 1fr;
        }

        .cm-rapport-admin-span-2 {
            grid-column: auto;
        }
    }
</style>

<section class="cm-prd3-crud-screen cm-prd6-admin-screen cm-screen-scrollable cm-rapport-admin-shell">
    <?php if ($messageSuccess): ?>
        <div class="cm-rapport-admin-alert is-success"><?= htmlspecialchars((string) $messageSuccess, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <?php if ($messageError): ?>
        <div class="cm-rapport-admin-alert is-danger"><?= htmlspecialchars((string) $messageError, ENT_QUOTES, 'UTF-8') ?></div>
    <?php endif; ?>

    <div class="cm-crud-wrapper">
        <div class="cm-pole-superieur">
            <form method="POST" action="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>" enctype="multipart/form-data">
                <input type="hidden" name="action" value="admin_upload_rapport">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenValue, ENT_QUOTES, 'UTF-8') ?>">

                <div class="cm-rapport-admin-form-grid">
                    <div class="cm-form-group cm-rapport-admin-span-2">
                        <label class="cm-form-label" for="num_etu_select">Etudiant <span class="cm-required-star">*</span></label>
                        <select id="num_etu_select" name="num_etu" class="cm-form-control" required>
                            <option value="">-- Selectionnez un etudiant --</option>
                            <?php foreach ($etudiantsSansRapport as $e): ?>
                                <?php
                                $nomComplet = trim((string) (($e->nom_etu ?? '') . ' ' . ($e->prenom_etu ?? '')));
                                $matricule = (string) ($e->num_carte_etud ?? $e->num_ident_etud ?? '');
                                $identifiant = (string) ($e->num_ident_etud ?? '');
                                ?>
                                <option
                                    value="<?= htmlspecialchars($matricule, ENT_QUOTES, 'UTF-8') ?>"
                                    data-nom="<?= htmlspecialchars($nomComplet, ENT_QUOTES, 'UTF-8') ?>"
                                    data-num-carte="<?= htmlspecialchars((string) ($e->num_carte_etud ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-num-ident="<?= htmlspecialchars($identifiant, ENT_QUOTES, 'UTF-8') ?>"
                                    data-email="<?= htmlspecialchars((string) ($e->email_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-promotion="<?= htmlspecialchars($formatPromotion($e->promotion_etu ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-annee="<?= htmlspecialchars((string) ($e->id_annee_acad ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-candidature="<?= htmlspecialchars((string) ($e->statut_candidature ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-entreprise="<?= htmlspecialchars((string) ($e->entreprise_stage ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-sujet="<?= htmlspecialchars((string) ($e->sujet_stage ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-maitre="<?= htmlspecialchars((string) ($e->maitre_stage_nom ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                    data-periode="<?= htmlspecialchars($formatStagePeriod($e->date_debut_stage ?? null, $e->date_fin_stage ?? null), ENT_QUOTES, 'UTF-8') ?>"
                                    data-nb-rapports="<?= htmlspecialchars((string) ($e->nb_rapports ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($nomComplet . ' (' . $matricule . ')', ENT_QUOTES, 'UTF-8') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="cm-rapport-admin-help">Le rapport importe sera lie au dossier selectionne.</span>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="admin_theme_rapport">Theme du rapport</label>
                        <input type="text" id="admin_theme_rapport" name="theme_rapport" class="cm-form-control"
                            placeholder="Ex : Conception d'une application de suivi">
                        <span class="cm-rapport-admin-help">Vous pouvez reprendre le sujet de stage si besoin.</span>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="admin_rapport_fichier">Fichier du rapport <span class="cm-required-star">*</span></label>
                        <input type="file" id="admin_rapport_fichier" name="rapport_fichier" class="cm-form-control"
                            accept=".pdf,.doc,.docx,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document"
                            required>
                        <span class="cm-rapport-admin-help">
                            Formats autorises : <?= htmlspecialchars($typesAutorises, ENT_QUOTES, 'UTF-8') ?>.
                            Taille max : <?= htmlspecialchars($formatFileSize($tailleMax), ENT_QUOTES, 'UTF-8') ?>.
                        </span>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label">Date systeme</label>
                        <input type="datetime-local" class="cm-form-control"
                            value="<?= htmlspecialchars($dateSysteme, ENT_QUOTES, 'UTF-8') ?>" disabled>
                    </div>

                    <div class="cm-form-group">
                        <label class="cm-form-label" for="date_operation">Date d'operation</label>
                        <input type="datetime-local" id="date_operation" name="date_operation" class="cm-form-control"
                            value="<?= htmlspecialchars($dateSysteme, ENT_QUOTES, 'UTF-8') ?>">
                    </div>

                    <div class="cm-rapport-admin-span-2">
                        <div id="etudiant_info" class="cm-rapport-admin-info">
                            <h3 id="info_nom_header" class="cm-rapport-admin-info__title">Dossier selectionne</h3>
                            <div class="cm-rapport-admin-info__grid">
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Matricule</span><span id="info_matricule" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Identifiant</span><span id="info_identifiant" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Promotion</span><span id="info_promotion" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Annee academique</span><span id="info_annee" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Email</span><span id="info_email" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Candidature</span><span id="info_candidature" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Entreprise</span><span id="info_entreprise" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Maitre de stage</span><span id="info_maitre_stage" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Periode de stage</span><span id="info_stage_periode" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Sujet de stage</span><span id="info_sujet_stage" class="cm-rapport-admin-info__value">-</span></div>
                                <div class="cm-rapport-admin-info__item"><span class="cm-rapport-admin-info__label">Rapports existants</span><span id="info_nb_rapports" class="cm-rapport-admin-info__value">-</span></div>
                            </div>
                        </div>
                    </div>

                    <div class="cm-rapport-admin-span-2">
                        <div class="cm-form-buttons">
                            <button type="submit" class="cm-btn is-primary">
                                <i class="fas fa-upload" aria-hidden="true"></i>
                                <span id="admin_import_label">Importer le rapport</span>
                            </button>
                            <span id="admin_import_hint" class="cm-rapport-admin-help">Choisissez d'abord un etudiant.</span>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <div class="cm-barre-intermediaire">
            <div class="cm-rapport-admin-table-head">
                <div>
                    <h2>Tableau recapitulatif</h2>
                    <p>Historique des rapports deja importes par l'administration.</p>
                </div>
                <div class="cm-rapport-admin-actions">
                    <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=admin_telecharger_rapport<?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?>"
                        class="cm-btn is-light is-sm" onclick="window.print(); return false;">
                        <i class="fas fa-print" aria-hidden="true"></i>
                        <span>Imprimer</span>
                    </a>
                    <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=export_rapports_csv<?= $searchTerm !== '' ? '&search=' . urlencode($searchTerm) : '' ?>"
                        class="cm-btn is-light is-sm">
                        <i class="fas fa-file-csv" aria-hidden="true"></i>
                        <span>Export CSV</span>
                    </a>
                </div>
            </div>

            <form method="GET" action="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>" class="cm-rapport-admin-filter">
                <input type="hidden" name="page" value="<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="action" value="admin_telecharger_rapport">

                <div class="cm-form-group">
                    <label class="cm-form-label" for="search_rapport_admin">Recherche</label>
                    <input type="text" id="search_rapport_admin" name="search" class="cm-form-control"
                        value="<?= htmlspecialchars($searchTerm, ENT_QUOTES, 'UTF-8') ?>"
                        placeholder="Nom, prenom, theme, rapport...">
                </div>

                <div class="cm-form-buttons">
                    <button type="submit" class="cm-btn is-primary is-sm">
                        <i class="fas fa-search" aria-hidden="true"></i>
                        <span>Filtrer</span>
                    </button>
                    <?php if ($searchTerm !== ''): ?>
                        <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=admin_telecharger_rapport"
                            class="cm-btn is-light is-sm">
                            <i class="fas fa-times" aria-hidden="true"></i>
                            <span>Reinitialiser</span>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="cm-pole-inferieur">
            <div class="cm-table-wrapper">
                <?php if (!empty($rapportsAdmin)): ?>
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th>Etudiant</th>
                                <th>Promotion</th>
                                <th>Entreprise</th>
                                <th>Rapport</th>
                                <th>Date operation</th>
                                <th>Fichier</th>
                                <th>Statut</th>
                                <th class="is-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($rapportsAdmin as $r): ?>
                                <?php $badge = $getBadge($r->statut_rapport ?? ''); ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars(trim((string) (($r->nom_etu ?? '') . ' ' . ($r->prenom_etu ?? ''))), ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="cm-text-muted"><?= htmlspecialchars($formatText($r->num_carte_etud ?? $r->num_etu ?? null), ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($formatPromotion($r->promotion_etu ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($formatText($r->entreprise_stage ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($formatText($r->nom_rapport ?? null, 'Rapport #' . (int) ($r->id_rapport ?? 0)), ENT_QUOTES, 'UTF-8') ?></strong><br>
                                        <small class="cm-text-muted"><?= htmlspecialchars($formatText($r->theme_rapport ?? null), ENT_QUOTES, 'UTF-8') ?></small>
                                    </td>
                                    <td><?= htmlspecialchars($formatDateTime($r->date_operation ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars($formatFileSize($r->taille_fichier ?? 0), ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><span class="cm-rapport-admin-badge <?= htmlspecialchars($badge['class'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($badge['label'], ENT_QUOTES, 'UTF-8') ?></span></td>
                                    <td class="is-right">
                                        <div class="cm-rapport-admin-actions" style="justify-content: flex-end;">
                                            <?php if (!empty($r->chemin_fichier)): ?>
                                                <a href="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>&action=download_fichier_rapport&id=<?= (int) ($r->id_rapport ?? 0) ?>"
                                                    class="cm-btn is-light is-sm" title="Telecharger">
                                                    <i class="fas fa-download" aria-hidden="true"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="cm-btn is-light is-sm" title="Modifier la date"
                                                onclick="openDateModal(<?= (int) ($r->id_rapport ?? 0) ?>, '<?= htmlspecialchars((string) ($r->date_operation ?? ''), ENT_QUOTES, 'UTF-8') ?>')">
                                                <i class="fas fa-calendar-alt" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="cm-rapport-admin-empty">Aucun rapport a afficher pour le moment.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<div id="dateOperationModal">
    <div class="cm-rapport-admin-modal">
        <h3>Modifier la date d'operation</h3>
        <form method="POST" action="?page=<?= htmlspecialchars($basePage, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="action" value="update_date_operation">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfTokenValue, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_rapport" id="modal_id_rapport" value="">

            <div class="cm-form-group">
                <label class="cm-form-label" for="modal_date_operation">Nouvelle date</label>
                <input type="datetime-local" id="modal_date_operation" name="date_operation" class="cm-form-control" required>
            </div>

            <div class="cm-form-buttons">
                <button type="button" class="cm-btn is-light" onclick="closeDateModal()">Annuler</button>
                <button type="submit" class="cm-btn is-primary">Enregistrer</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openDateModal(idRapport, dateActuelle) {
        document.getElementById('modal_id_rapport').value = idRapport;
        var input = document.getElementById('modal_date_operation');
        var value = (dateActuelle || '').trim();
        input.value = value !== '' ? value.replace(' ', 'T').slice(0, 16) : '';
        document.getElementById('dateOperationModal').style.display = 'flex';
    }

    function closeDateModal() {
        document.getElementById('dateOperationModal').style.display = 'none';
    }

    document.addEventListener('click', function (event) {
        var modal = document.getElementById('dateOperationModal');
        if (modal.style.display === 'flex' && event.target === modal) {
            closeDateModal();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        var select = document.getElementById('num_etu_select');
        var infoBox = document.getElementById('etudiant_info');
        var importLabel = document.getElementById('admin_import_label');
        var importHint = document.getElementById('admin_import_hint');
        var themeField = document.getElementById('admin_theme_rapport');

        function setText(id, value) {
            var node = document.getElementById(id);
            if (!node) {
                return;
            }
            node.textContent = value && value.trim() !== '' ? value : '-';
        }

        function updateStudentInfo() {
            if (!select) {
                return;
            }

            var option = select.options[select.selectedIndex];
            if (!option || !option.value) {
                infoBox.style.display = 'none';
                importLabel.textContent = 'Importer le rapport';
                importHint.textContent = 'Choisissez d abord un etudiant.';
                return;
            }

            var nom = option.getAttribute('data-nom') || 'Etudiant selectionne';
            document.getElementById('info_nom_header').textContent = nom;
            setText('info_matricule', option.getAttribute('data-num-carte'));
            setText('info_identifiant', option.getAttribute('data-num-ident'));
            setText('info_promotion', option.getAttribute('data-promotion'));
            setText('info_annee', option.getAttribute('data-annee'));
            setText('info_email', option.getAttribute('data-email'));
            setText('info_candidature', option.getAttribute('data-candidature'));
            setText('info_entreprise', option.getAttribute('data-entreprise'));
            setText('info_maitre_stage', option.getAttribute('data-maitre'));
            setText('info_stage_periode', option.getAttribute('data-periode'));
            setText('info_sujet_stage', option.getAttribute('data-sujet'));
            setText('info_nb_rapports', option.getAttribute('data-nb-rapports'));

            if (themeField && themeField.value.trim() === '') {
                themeField.value = option.getAttribute('data-sujet') || '';
            }

            infoBox.style.display = 'block';
            importLabel.textContent = 'Importer le rapport pour ' + nom;
            importHint.textContent = 'Le fichier sera rattache au dossier de ' + nom + '.';
        }

        if (select) {
            select.addEventListener('change', updateStudentInfo);
            updateStudentInfo();
        }
    });
</script>

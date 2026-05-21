<?php
/**
 * Fiche Financière Année — Vue d'ensemble financière par année académique
 */
$idAnnee = $GLOBALS['idAnnee'] ?? null;
$listeAnnees = $GLOBALS['listeAnnees'] ?? [];
$recap = $GLOBALS['recap'] ?? [];
$etudiants = $GLOBALS['etudiants'] ?? [];

$niveaux = $recap['niveaux'] ?? [];
$totalAttendu = (float) ($recap['total_attendu'] ?? 0);
$totalVerse = (float) ($recap['total_verse'] ?? 0);
$tauxRecouvrement = (float) ($recap['taux_recouvrement'] ?? 0);
$nbTotalInscrits = (int) ($recap['nb_total_inscrits'] ?? 0);
$echeancesRetard = $recap['echeances_retard'] ?? [];
$echeancesAVenir = $recap['echeances_a_venir'] ?? [];

$anneeLabel = '';
$anneeActiveId = null;
foreach ($listeAnnees as $aa) {
    if ((int) ($aa['id_annee_acad'] ?? 0) === $idAnnee) {
        $anneeLabel = $aa['label'] ?? '';
        $anneeActiveId = (int) ($aa['id_annee_acad'] ?? 0);
        break;
    }
}

$isHubContext = (string) ($_GET['page'] ?? '') === 'suivi_scolarite';
$baseUrl = $isHubContext
    ? '?page=suivi_scolarite&tab=fiche_financiere_annee'
    : '?page=fiche_financiere_annee';
$messageSuccess = $_SESSION['success_message'] ?? '';
$messageErreur = $_SESSION['error_message'] ?? '';
unset($_SESSION['success_message'], $_SESSION['error_message']);
?>
<section class="cm-prd3-crud-screen cm-prd6-admin-screen">
    <?php if ($messageSuccess !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'success', 'message' => $messageSuccess]); ?>
    <?php endif; ?>
    <?php if ($messageErreur !== ''): ?>
        <?php cm_component('ui/alert-box', ['type' => 'danger', 'message' => $messageErreur]); ?>
    <?php endif; ?>

    <!-- En-tête avec sélecteur d'année -->
    <div class="cm-card cm-mb-4">
        <div class="cm-card-body cm-flex-between" style="display:flex;align-items:center;justify-content:space-between;padding:1rem;">
            <div>
                <h2 class="cm-text-lg cm-font-bold">
                    <i class="fas fa-coins cm-mr-2"></i>Fiche Financière
                </h2>
                <p class="cm-text-sm cm-text-gray-500">
                    Récapitulatif des inscriptions et paiements
                    <?php if ($anneeLabel !== ''): ?>
                        — <strong><?= htmlspecialchars($anneeLabel) ?></strong>
                    <?php endif; ?>
                </p>
            </div>
            <div>
                <form method="GET" action="<?= htmlspecialchars($baseUrl) ?>" class="cm-inline-form" style="display:inline-flex;align-items:center;gap:0.5rem;">
                    <?php if ($isHubContext): ?>
                        <input type="hidden" name="page" value="suivi_scolarite">
                        <input type="hidden" name="tab" value="fiche_financiere_annee">
                    <?php endif; ?>
                    <label for="ficheAnneeSelect" class="cm-text-sm cm-font-medium">Année :</label>
                    <select name="id_annee_acad" id="ficheAnneeSelect"
                            class="cm-field is-md"
                            onchange="this.form.submit()"
                            style="min-width:160px;">
                        <?php foreach ($listeAnnees as $aa): ?>
                            <?php $aaId = (int) ($aa['id_annee_acad'] ?? 0); ?>
                            <option value="<?= $aaId ?>" <?= $aaId === $idAnnee ? 'selected' : '' ?>>
                                <?= htmlspecialchars($aa['label'] ?? '') ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <noscript><button type="submit" class="cm-btn is-primary is-sm">OK</button></noscript>
                </form>
            </div>
        </div>
    </div>

    <!-- Ligne 1 : 4 stat-widgets -->
    <div class="cm-grid-4 cm-gap-4 cm-mb-4">
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($totalAttendu, 0, ',', ' ') . ' FCFA',
            'label' => 'Total attendu',
            'icon' => 'fa-calculator',
            'color' => 'primary',
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($totalVerse, 0, ',', ' ') . ' FCFA',
            'label' => 'Total versé',
            'icon' => 'fa-money-bill-wave',
            'color' => 'success',
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($tauxRecouvrement, 1, ',', ' ') . ' %',
            'label' => 'Taux de recouvrement',
            'icon' => 'fa-percent',
            'color' => $tauxRecouvrement >= 80 ? 'success' : ($tauxRecouvrement >= 50 ? 'warning' : 'danger'),
        ]); ?>
        <?php cm_component('dashboard/stat-widget', [
            'value' => number_format($nbTotalInscrits, 0, ',', ' '),
            'label' => 'Nombre d\'inscrits',
            'icon' => 'fa-user-graduate',
            'color' => 'info',
        ]); ?>
    </div>

    <!-- Ligne 2 : Tableau récapitulatif par niveau (clickable) -->
    <div class="cm-card cm-mb-4">
        <div class="cm-card-header">
            <h3 class="cm-card-title"><i class="fas fa-layer-group cm-mr-2"></i>Récapitulatif par niveau</h3>
        </div>
        <div class="cm-card-body">
            <?php if (empty($niveaux)): ?>
                <?php cm_component('ui/empty-state', [
                    'title' => 'Aucune donnée',
                    'message' => 'Aucune inscription trouvée pour cette année académique.',
                    'icon' => 'fa-inbox',
                ]); ?>
            <?php else: ?>
                <div class="cm-table-wrapper">
                    <table class="cm-data-table">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th is-left">Niveau</th>
                                <th class="cm-data-table__th is-right">Frais inscription</th>
                                <th class="cm-data-table__th is-right">Nb inscrits</th>
                                <th class="cm-data-table__th is-right">Total attendu</th>
                                <th class="cm-data-table__th is-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($niveaux as $niv): ?>
                            <?php
                                $nivId = (string) ($niv['id_niv_etude'] ?? '');
                                $nivLabel = htmlspecialchars($niv['niveau_label'] ?? '');
                                $frais = (float) ($niv['frais_inscription'] ?? 0);
                                $nb = (int) ($niv['nb_inscrits'] ?? 0);
                                $attendu = (float) ($niv['total_attendu'] ?? 0);
                            ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="<?= htmlspecialchars($baseUrl . '&id_annee_acad=' . $idAnnee . '&niveau=' . urlencode($nivId)) ?>"
                                data-link-type="scroll">
                                <td class="cm-data-table__td is-left"><strong><?= $nivLabel ?></strong></td>
                                <td class="cm-data-table__td is-right"><?= number_format($frais, 0, ',', ' ') ?> FCFA</td>
                                <td class="cm-data-table__td is-right"><?= number_format($nb) ?></td>
                                <td class="cm-data-table__td is-right"><?= number_format($attendu, 0, ',', ' ') ?> FCFA</td>
                                <td class="cm-data-table__td is-center">
                                    <a href="<?= htmlspecialchars($baseUrl . '&id_annee_acad=' . $idAnnee . '&niveau=' . urlencode($nivId)) ?>"
                                       class="cm-btn is-primary is-xs" data-cm-ajax-link="true">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Ligne 3 : Échéances (clickable) -->
    <div class="cm-grid-2 cm-gap-4 cm-mb-4">
        <!-- Échéances en retard -->
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title cm-text-danger">
                    <i class="fas fa-exclamation-triangle cm-mr-2"></i>Échéances en retard
                    <?php if (!empty($echeancesRetard)): ?>
                        <span class="cm-badge is-danger"><?= count($echeancesRetard) ?></span>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="cm-card-body" style="max-height:300px;overflow-y:auto;">
                <?php if (empty($echeancesRetard)): ?>
                    <p class="cm-text-sm cm-text-gray-500">Aucune échéance en retard.</p>
                <?php else: ?>
                    <table class="cm-data-table cm-data-table--compact">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th is-left">Étudiant</th>
                                <th class="cm-data-table__th is-right">Montant</th>
                                <th class="cm-data-table__th is-center">Échéance</th>
                                <th class="cm-data-table__th is-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($echeancesRetard as $ech): ?>
                            <?php
                                $nomEtudiant = htmlspecialchars(trim(($ech['prenom_etu'] ?? '') . ' ' . ($ech['nom_etu'] ?? '')));
                                $montant = (float) ($ech['montant'] ?? 0);
                                $dateEch = htmlspecialchars($ech['date_echeance'] ?? '');
                                $statut = htmlspecialchars($ech['statut_echeance'] ?? '');
                            ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="<?= htmlspecialchars($baseUrl . '&id_annee_acad=' . $idAnnee . '&num_etu=' . urlencode($ech['num_carte_etud'] ?? '')) ?>"
                                data-link-type="scroll">
                                <td class="cm-data-table__td is-left"><?= $nomEtudiant ?: htmlspecialchars($ech['num_carte_etud'] ?? '') ?></td>
                                <td class="cm-data-table__td is-right"><?= number_format($montant, 0, ',', ' ') ?> FCFA</td>
                                <td class="cm-data-table__td is-center"><?= date('d/m/Y', strtotime($dateEch)) ?></td>
                                <td class="cm-data-table__td is-center">
                                    <?php cm_component('ui/badge', ['text' => $statut, 'type' => 'danger']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>

        <!-- Échéances à venir -->
        <div class="cm-card">
            <div class="cm-card-header">
                <h3 class="cm-card-title cm-text-warning">
                    <i class="fas fa-calendar-clock cm-mr-2"></i>Échéances à venir (30 jours)
                    <?php if (!empty($echeancesAVenir)): ?>
                        <span class="cm-badge is-warning"><?= count($echeancesAVenir) ?></span>
                    <?php endif; ?>
                </h3>
            </div>
            <div class="cm-card-body" style="max-height:300px;overflow-y:auto;">
                <?php if (empty($echeancesAVenir)): ?>
                    <p class="cm-text-sm cm-text-gray-500">Aucune échéance à venir.</p>
                <?php else: ?>
                    <table class="cm-data-table cm-data-table--compact">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th is-left">Étudiant</th>
                                <th class="cm-data-table__th is-right">Montant</th>
                                <th class="cm-data-table__th is-center">Échéance</th>
                                <th class="cm-data-table__th is-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($echeancesAVenir as $ech): ?>
                            <?php
                                $nomEtudiant = htmlspecialchars(trim(($ech['prenom_etu'] ?? '') . ' ' . ($ech['nom_etu'] ?? '')));
                                $montant = (float) ($ech['montant'] ?? 0);
                                $dateEch = htmlspecialchars($ech['date_echeance'] ?? '');
                            ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="<?= htmlspecialchars($baseUrl . '&id_annee_acad=' . $idAnnee . '&num_etu=' . urlencode($ech['num_carte_etud'] ?? '')) ?>"
                                data-link-type="scroll">
                                <td class="cm-data-table__td is-left"><?= $nomEtudiant ?: htmlspecialchars($ech['num_carte_etud'] ?? '') ?></td>
                                <td class="cm-data-table__td is-right"><?= number_format($montant, 0, ',', ' ') ?> FCFA</td>
                                <td class="cm-data-table__td is-center"><?= date('d/m/Y', strtotime($dateEch)) ?></td>
                                <td class="cm-data-table__td is-center">
                                    <?php cm_component('ui/badge', ['text' => 'En attente', 'type' => 'warning']); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Ligne 4 : Détail des étudiants inscrits (clickable) -->
    <div class="cm-card">
        <div class="cm-card-header">
            <h3 class="cm-card-title"><i class="fas fa-users cm-mr-2"></i>Détail par étudiant</h3>
        </div>
        <div class="cm-card-body">
            <?php if (empty($etudiants)): ?>
                <?php cm_component('ui/empty-state', [
                    'title' => 'Aucun étudiant',
                    'message' => 'Aucun étudiant inscrit pour cette année académique.',
                    'icon' => 'fa-users',
                ]); ?>
            <?php else: ?>
                <div class="cm-table-wrapper">
                    <table class="cm-data-table" id="ficheFinanciereEtudiantsTable">
                        <thead>
                            <tr>
                                <th class="cm-data-table__th is-left">Matricule</th>
                                <th class="cm-data-table__th is-left">Nom & Prénom</th>
                                <th class="cm-data-table__th is-left">Niveau</th>
                                <th class="cm-data-table__th is-right">Scolarité</th>
                                <th class="cm-data-table__th is-right">Versé</th>
                                <th class="cm-data-table__th is-right">Solde</th>
                                <th class="cm-data-table__th is-center">Statut</th>
                                <th class="cm-data-table__th is-center">Versements</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($etudiants as $etu): ?>
                            <?php
                                $numEtu = htmlspecialchars($etu['num_carte_etud'] ?? '');
                                $nom = htmlspecialchars(trim(($etu['prenom'] ?? '') . ' ' . ($etu['nom'] ?? '')));
                                $niveau = htmlspecialchars($etu['niveau'] ?? '');
                                $montantSco = (float) ($etu['montant_scolarite'] ?? 0);
                                $montantPaye = (float) ($etu['montant_paye'] ?? 0);
                                $solde = (float) ($etu['solde'] ?? 0);
                                $nbVersements = (int) ($etu['nb_versements'] ?? 0);
                                $statut = $solde <= 0 ? 'Soldé' : ($montantPaye > 0 ? 'Partiel' : 'Impayé');
                                $statutType = $solde <= 0 ? 'success' : ($montantPaye > 0 ? 'warning' : 'danger');
                                $detailUrl = htmlspecialchars($baseUrl . '&id_annee_acad=' . $idAnnee . '&num_etu=' . urlencode($numEtu));
                            ?>
                            <tr class="cm-data-table__row cm-clickable-row"
                                data-href="<?= $detailUrl ?>"
                                data-link-type="dialog">
                                <td class="cm-data-table__td is-left"><strong><?= $numEtu ?></strong></td>
                                <td class="cm-data-table__td is-left"><?= $nom ?: 'N/A' ?></td>
                                <td class="cm-data-table__td is-left"><?= $niveau ?></td>
                                <td class="cm-data-table__td is-right"><?= number_format($montantSco, 0, ',', ' ') ?> FCFA</td>
                                <td class="cm-data-table__td is-right"><?= number_format($montantPaye, 0, ',', ' ') ?> FCFA</td>
                                <td class="cm-data-table__td is-right"><strong><?= number_format($solde, 0, ',', ' ') ?> FCFA</strong></td>
                                <td class="cm-data-table__td is-center">
                                    <?php cm_component('ui/badge', ['text' => $statut, 'type' => $statutType]); ?>
                                </td>
                                <td class="cm-data-table__td is-center"><?= $nbVersements ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="cm-card cm-mt-4" id="cmFicheFinanciereDetailCard">
        <div class="cm-card-header" style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
            <h3 class="cm-card-title">
                <i class="fas fa-id-card cm-mr-2"></i>Détail étudiant
            </h3>
            <div class="cm-text-sm cm-text-gray-500" id="cmFicheFinanciereDetailHint">
                Cliquez sur une ligne pour afficher le détail ici.
            </div>
        </div>
        <div class="cm-card-body" id="cmFicheFinanciereDetailBody">
            <div class="cm-empty-state">
                <i class="fas fa-hand-pointer"></i>
                <div class="cm-empty-state__content">
                    <h4>Sélectionner un étudiant</h4>
                    <p>Le détail des versements et des échéances s'affichera ici sans ouvrir de fenêtre modale.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script: détail étudiant inline -->
    <script>
    (function() {
        const idAnnee = <?= json_encode($idAnnee) ?>;
        const detailCard = document.getElementById('cmFicheFinanciereDetailCard');
        const detailBody = document.getElementById('cmFicheFinanciereDetailBody');
        const detailHint = document.getElementById('cmFicheFinanciereDetailHint');

        document.addEventListener('cm:row-click', function(e) {
            var href = e.detail ? e.detail.href : '';
            if (href.indexOf('num_etu=') === -1) return;

            // Extraire le num_etu du href
            var params = new URLSearchParams(href.split('?')[1] || '');
            var numEtu = params.get('num_etu');
            if (!numEtu) return;

            loadDetailEtudiant(numEtu);
        });

        var currentParams = new URLSearchParams(window.location.search);
        var initialNumEtu = currentParams.get('num_etu');
        if (initialNumEtu) {
            loadDetailEtudiant(initialNumEtu);
        }

        function loadDetailEtudiant(numEtu) {
            syncDetailUrl(numEtu);

            if (detailHint) {
                detailHint.textContent = 'Chargement du détail...';
            }

            if (detailBody) {
                detailBody.innerHTML = '<div class="cm-text-center cm-p-4 cm-text-gray-500"><i class="fas fa-spinner fa-spin cm-mr-2"></i>Chargement...</div>';
            }

            fetch('layout.php?page=' + encodeURIComponent(<?= json_encode($isHubContext ? 'suivi_scolarite' : 'fiche_financiere_annee') ?>) +
                <?= $isHubContext ? " '&tab=fiche_financiere_annee'" : " ''" ?> +
                '&action=detail_etudiant&num_etu=' + encodeURIComponent(numEtu) + '&id_annee_acad=' + idAnnee, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.success || !data.data) {
                    afficherDetailErreur('Étudiant non trouvé.');
                    return;
                }
                afficherDetailEtudiant(data.data);
            })
            .catch(function() {
                afficherDetailErreur('Erreur lors du chargement des données.');
            });
        }

        function afficherDetailEtudiant(data) {
            var etu = data.etudiant || {};
            var versements = data.versements || [];
            var echeances = data.echeances || [];

            var rows = versements.map(function(v, i) {
                var dateV = v.date_versement ? new Date(v.date_versement).toLocaleDateString('fr-FR') : '-';
                var mt = Number(v.montant_verser || 0).toLocaleString('fr-FR');
                var sd = Number(v.solde || 0).toLocaleString('fr-FR');
                return '<tr>' +
                    '<td class="cm-data-table__td is-center">' + htmlEscape(String(i + 1)) + '</td>' +
                    '<td class="cm-data-table__td is-center">' + htmlEscape(dateV) + '</td>' +
                    '<td class="cm-data-table__td is-right">' + htmlEscape(mt + ' FCFA') + '</td>' +
                    '<td class="cm-data-table__td is-left">' + htmlEscape(v.methode_paiement || '-') + '</td>' +
                    '<td class="cm-data-table__td is-right">' + htmlEscape(sd + ' FCFA') + '</td>' +
                    '</tr>';
            }).join('');

            var echeanceRows = echeances.map(function(e) {
                var dateE = e.date_echeance ? new Date(e.date_echeance).toLocaleDateString('fr-FR') : '-';
                var mt = Number(e.montant || 0).toLocaleString('fr-FR');
                var st = e.statut_echeance || 'En attente';
                var stClass = st === 'Payée' ? 'is-success' : (st === 'En retard' ? 'is-danger' : 'is-warning');
                return '<tr>' +
                    '<td class="cm-data-table__td is-center">' + htmlEscape(dateE) + '</td>' +
                    '<td class="cm-data-table__td is-right">' + htmlEscape(mt + ' FCFA') + '</td>' +
                    '<td class="cm-data-table__td is-center"><span class="cm-badge ' + stClass + '">' + htmlEscape(st) + '</span></td>' +
                    '</tr>';
            }).join('');

            if (detailHint) {
                detailHint.textContent = 'Détail chargé pour ' + String(etu.nom_etu || '') + ' ' + String(etu.prenom_etu || '') + ' (' + String(etu.num_carte_etud || '') + ')';
            }

            if (detailBody) {
                detailBody.innerHTML =
                    '<div class="cm-grid-3 cm-gap-4 cm-mb-4">' +
                    '<div class="cm-stat-card is-info"><div class="cm-stat-card__content"><div class="cm-stat-card__value">' + Number(data.montant_scolarite || 0).toLocaleString('fr-FR') + ' FCFA</div><div class="cm-stat-card__label">Scolarité</div></div></div>' +
                    '<div class="cm-stat-card is-success"><div class="cm-stat-card__content"><div class="cm-stat-card__value">' + Number(data.montant_paye || 0).toLocaleString('fr-FR') + ' FCFA</div><div class="cm-stat-card__label">Versé</div></div></div>' +
                    '<div class="cm-stat-card ' + (Number(data.solde || 0) <= 0 ? 'is-success' : 'is-danger') + '"><div class="cm-stat-card__content"><div class="cm-stat-card__value">' + Number(data.solde || 0).toLocaleString('fr-FR') + ' FCFA</div><div class="cm-stat-card__label">Solde</div></div></div>' +
                    '</div>' +
                    '<div class="cm-grid-2 cm-gap-4">' +
                    '<div>' +
                    '<h4 class="cm-font-bold cm-mb-2"><i class="fas fa-receipt cm-mr-1"></i> Versements</h4>' +
                    '<div class="cm-table-wrapper" style="max-height:250px;overflow-y:auto;">' +
                    '<table class="cm-data-table cm-data-table--compact">' +
                    '<thead><tr><th class="cm-data-table__th is-center">#</th><th class="cm-data-table__th is-center">Date</th><th class="cm-data-table__th is-right">Montant</th><th class="cm-data-table__th is-left">Mode</th><th class="cm-data-table__th is-right">Solde</th></tr></thead>' +
                    '<tbody>' + (rows || '<tr><td colspan="5" class="cm-data-table__td is-center">Aucun versement</td></tr>') + '</tbody>' +
                    '</table></div>' +
                    '</div>' +
                    '<div>' +
                    '<h4 class="cm-font-bold cm-mb-2"><i class="fas fa-calendar cm-mr-1"></i> Échéances</h4>' +
                    '<div class="cm-table-wrapper" style="max-height:250px;overflow-y:auto;">' +
                    '<table class="cm-data-table cm-data-table--compact">' +
                    '<thead><tr><th class="cm-data-table__th is-center">Date</th><th class="cm-data-table__th is-right">Montant</th><th class="cm-data-table__th is-center">Statut</th></tr></thead>' +
                    '<tbody>' + (echeanceRows || '<tr><td colspan="3" class="cm-data-table__td is-center">Aucune échéance</td></tr>') + '</tbody>' +
                    '</table></div>' +
                    '</div>' +
                    '</div>';
            }
            if (detailCard) {
                detailCard.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }

        function syncDetailUrl(numEtu) {
            if (!numEtu || !window.history || typeof window.history.replaceState !== 'function') {
                return;
            }

            var url = new URL(window.location.href);
            url.searchParams.set('num_etu', numEtu);
            if (idAnnee) {
                url.searchParams.set('id_annee_acad', String(idAnnee));
            }
            window.history.replaceState({}, '', url.toString());
        }

        function afficherDetailErreur(message) {
            if (detailHint) {
                detailHint.textContent = message;
            }
            if (detailBody) {
                detailBody.innerHTML = '<div class="cm-alert cm-alert-danger">' + htmlEscape(message) + '</div>';
            }
        }

        function htmlEscape(str) {
            if (!str) return '';
            return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }
    })();
    </script>
</section>

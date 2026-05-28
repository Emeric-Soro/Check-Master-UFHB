<?php
/**
 * Vue wizard pour un étudiant spécifique (page=cycle_etudiant&action=show)
 */

$numEtu = (string) ($GLOBALS['cycle_num_etu'] ?? '');
$anneeAcad = (int) ($GLOBALS['cycle_annee_acad'] ?? 0);
$progression = $GLOBALS['cycle_progression'] ?? null;
$error = $GLOBALS['cycle_error'] ?? '';

if ($error !== '') {
    echo '<div class="cm-prd3-screen"><div class="cm-alert-box is-danger"><i class="fas fa-exclamation-triangle"></i> ' . htmlspecialchars($error) . '</div></div>';
    return;
}

if (!$progression) {
    echo '<div class="cm-prd3-screen"><div class="cm-alert-box is-warning"><i class="fas fa-info-circle"></i> Aucune donnée trouvée pour cet étudiant.</div></div>';
    return;
}

$GLOBALS['cycle_etudiant_wizard_data'] = [
    'num_etu' => $numEtu,
    'annee_acad' => $anneeAcad,
    'progression' => $progression,
];

include __DIR__ . '/partials/_wizard.php';

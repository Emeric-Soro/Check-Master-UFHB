<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/app/utils/EmailService.php';

$testEmail = 'aurevoirytb23@gmail.com';
$emailService = new EmailService();
$templates = require __DIR__ . '/app/config/email_templates.php';
$results = [];

// Helper to send and log
function sendTest($emailService, $template, $to, $data) {
    global $results;
    try {
        $ok = $emailService->sendTemplate($template, $to, $data);
        $results[] = [
            'template' => $template,
            'status' => $ok ? 'OK' : 'ECHEC',
            'error' => $ok ? '' : 'sendTemplate a retourne false',
        ];
    } catch (\Throwable $e) {
        $results[] = [
            'template' => $template,
            'status' => 'ERREUR',
            'error' => $e->getMessage(),
        ];
    }
}

$now = date('d/m/Y H:i');
$date = date('Y-m-d');

// === EXISTING TEMPLATES ===
sendTest($emailService, 'USER_WELCOME', $testEmail, [
    'nom' => 'Jean Dupont',
    'login' => 'jean.dupont@email.com',
    'password_row' => '<tr><td class="info-label">Mot de passe :</td><td class="info-value"><div class="copyable-container"><code class="copy-value">TempPass123!</code><span class="copy-btn" data-copy="TempPass123!">Copier</span></div></td></tr>',
    'reset_password_section' => '<p style="margin-top: 15px;"><a href="https://checkmaster.ufhb.edu.ci/reset?token=abc123" style="color: #0f172a;">Reinitialiser mon mot de passe</a></p>',
    'login_url' => 'https://checkmaster.ufhb.edu.ci',
]);

sendTest($emailService, 'CANDIDATURE_RESULT', $testEmail, [
    'nom' => 'Jean Dupont',
    'decision' => 'Validee',
    'status_icon' => '&#x1F389;',
    'status_text_color' => '#10b981',
    'status_bg_color' => '#f0fdf4',
    'status_border_color' => '#bbf7d0',
    'details_html' => '
        <div class="box" style="border-left: 4px solid #10b981;">
            <h4 style="margin-top: 0;">Scolarite</h4>
            <p><strong>Validation :</strong> <span style="background-color: #10b981; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">VALIDE</span></p>
            <p>Statut : A jour</p>
        </div>
        <div class="box" style="border-left: 4px solid #10b981;">
            <h4 style="margin-top: 0;">Stage</h4>
            <p><strong>Validation :</strong> <span style="background-color: #10b981; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px;">VALIDE</span></p>
            <p>Entreprise : SGABS</p>
        </div>
    ',
    'action_message' => '<div class="box text-center" style="background-color: #f0fdf4; border: 1px dashed #10b981;"><p style="margin: 0; color: #065f46;"><strong>Felicitations !</strong> Votre candidature a ete validee.</p></div>',
]);

sendTest($emailService, 'PASSWORD_RESET', $testEmail, [
    'reset_link' => 'https://checkmaster.ufhb.edu.ci/reset?token=abc123def456',
]);

sendTest($emailService, 'REPORT_NOTIFICATION', $testEmail, [
    'nom' => 'Jean Dupont',
    'nom_rapport' => 'Automatisation des tests SWIFT',
    'nom_CR' => 'CR Soutenance Janvier 2026',
    'date_CR' => $now,
]);

sendTest($emailService, 'COMMISSION_NOTIFICATION', $testEmail, [
    'nom' => 'Membre Commission',
    'nom_CR' => 'CR Soutenance Janvier 2026',
    'nbRapports' => 5,
    'id_CR' => 42,
]);

sendTest($emailService, 'RESPONSABLE_NOTIFICATION', $testEmail, [
    'nom' => 'Responsable Filiere',
    'nom_CR' => 'CR Soutenance Janvier 2026',
    'nbRapports' => 5,
    'id_CR' => 42,
]);

// === NEW TEMPLATES ===
sendTest($emailService, 'CANDIDATURE_SOUMISE_ETUDIANT', $testEmail, [
    'nom' => 'Jean Dupont',
    'id_candidature' => 53,
    'date_candidature' => $now,
]);

sendTest($emailService, 'CANDIDATURE_SOUMISE_ADMIN', $testEmail, [
    'nom' => 'Jean Dupont',
    'num_etu' => 'CI0123456789',
    'date_candidature' => $now,
    'id_candidature' => 53,
    'admin_url' => 'https://checkmaster.ufhb.edu.ci/?page=gestion_dossiers_candidatures',
]);

sendTest($emailService, 'DEPOT_RAPPORT', $testEmail, [
    'nom_enseignant' => 'Prof. Kouassi',
    'nom_etudiant' => 'Jean Dupont',
    'nom_rapport' => 'Rapport de stage SGABS',
    'theme_rapport' => 'Automatisation des tests SWIFT',
    'date_depot' => $now,
    'role' => 'Encadrant',
]);

sendTest($emailService, 'EVALUATION_RAPPORT_VALIDE', $testEmail, [
    'nom' => 'Jean Dupont',
    'nom_rapport' => 'Rapport de stage SGABS',
    'commentaires' => '<p style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 8px;">Travail de qualite, rapport bien structure.</p>',
]);

sendTest($emailService, 'EVALUATION_RAPPORT_REJETE', $testEmail, [
    'nom' => 'Jean Dupont',
    'nom_rapport' => 'Rapport de stage SGABS',
    'commentaires' => '<p style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 8px;">Quelques parties a revoir : la methodologie et les conclusions.</p>',
]);

sendTest($emailService, 'AFFECTATION_ENCADRANT', $testEmail, [
    'nom_enseignant' => 'Prof. Kouassi',
    'nom_etudiant' => 'Jean Dupont',
    'theme' => 'Automatisation des tests SWIFT',
    'entreprise' => 'SGABS',
]);

sendTest($emailService, 'AFFECTATION_DIRECTEUR', $testEmail, [
    'nom_enseignant' => 'Prof. Kouassi',
    'nom_etudiant' => 'Jean Dupont',
    'theme' => 'Automatisation des tests SWIFT',
    'entreprise' => 'SGABS',
]);

sendTest($emailService, 'AJOUT_JURY', $testEmail, [
    'nom_enseignant' => 'Prof. Kouassi',
    'role' => 'Examinateur',
    'nom_etudiant' => 'Jean Dupont',
    'theme' => 'Automatisation des tests SWIFT',
    'date_soutenance' => '2026-06-15',
    'heure_soutenance' => '09:00',
    'salle' => 'Salle A102',
]);

sendTest($emailService, 'RETRAIT_JURY', $testEmail, [
    'nom_enseignant' => 'Prof. Kouassi',
    'role' => 'President',
    'nom_etudiant' => 'Jean Dupont',
    'theme' => 'Automatisation des tests SWIFT',
]);

sendTest($emailService, 'SOUTENANCE_PROGRAMMEE', $testEmail, [
    'nom' => 'Jean Dupont',
    'nom_etudiant' => 'Jean Dupont',
    'theme' => 'Automatisation des tests SWIFT',
    'date_soutenance' => '2026-06-15',
    'heure_soutenance' => '09:00',
    'salle' => 'Salle A102',
    'composition_jury' => '',
]);

sendTest($emailService, 'SOUTENANCE_MODIFIEE', $testEmail, [
    'nom' => 'Jean Dupont',
    'nom_etudiant' => 'Jean Dupont',
    'date_soutenance' => '2026-06-20',
    'heure_soutenance' => '10:00',
    'salle' => 'Salle B203',
]);

sendTest($emailService, 'SOUTENANCE_ANNULEE', $testEmail, [
    'nom' => 'Jean Dupont',
    'nom_etudiant' => 'Jean Dupont',
    'theme' => 'Automatisation des tests SWIFT',
    'date_soutenance' => '2026-06-15',
]);

sendTest($emailService, 'DECISION_JURY_ETUDIANT', $testEmail, [
    'nom' => 'Jean Dupont',
    'theme' => 'Automatisation des tests SWIFT',
    'decision' => 'ADMIS(E)',
    'note' => '15.5',
    'mention' => 'Bien',
    'bg_color' => '#f0fdf4',
    'border_color' => '#bbf7d0',
    'text_color' => '#10b981',
]);

sendTest($emailService, 'DECISION_JURY_ENCADRANT', $testEmail, [
    'nom' => 'Prof. Kouassi',
    'nom_etudiant' => 'Jean Dupont',
    'decision' => 'ADMIS(E)',
    'note' => '15.5',
    'mention' => 'Bien',
]);

sendTest($emailService, 'BULLETIN_NOTES', $testEmail, [
    'nom' => 'Jean Dupont',
    'semestre' => 'S2',
    'moyenne' => '14.50/20',
    'credits' => '30/30 credits valides',
]);

sendTest($emailService, 'INSCRIPTION_CONFIRMATION', $testEmail, [
    'nom' => 'Jean Dupont',
    'annee_academique' => '2025-2026',
    'niveau' => 'Master 2',
    'montant_total' => '1 025 000',
    'montant_verse' => '500 000',
    'solde' => '525 000',
]);

sendTest($emailService, 'RECU_PAIEMENT', $testEmail, [
    'nom' => 'Jean Dupont',
    'montant' => '525 000',
    'date_paiement' => $now,
    'mode_paiement' => 'Especes',
    'solde' => '0',
]);

sendTest($emailService, 'INSCRIPTION_VALIDEE', $testEmail, [
    'nom' => 'Jean Dupont',
    'annee_academique' => '2025-2026',
]);

sendTest($emailService, 'RELANCE_IMPAYE', $testEmail, [
    'nom' => 'Jean Dupont',
    'annee_academique' => '2025-2026',
    'montant_total' => '1 025 000',
    'montant_verse' => '500 000',
    'solde' => '525 000',
]);

sendTest($emailService, 'RECLAMATION_SOUMISE_ADMIN', $testEmail, [
    'nom_etudiant' => 'Jean Dupont',
    'objet' => 'Probleme de notes',
    'type' => 'Academique',
    'id' => 42,
    'admin_url' => 'https://checkmaster.ufhb.edu.ci/?page=gestion_reclamations_scolarite',
]);

sendTest($emailService, 'RECLAMATION_STATUT', $testEmail, [
    'nom' => 'Jean Dupont',
    'id' => 42,
    'objet' => 'Probleme de notes',
    'statut' => 'En cours',
    'statut_couleur' => '#3b82f6',
    'commentaire' => '<p style="background: #f1f5f9; padding: 12px; border-radius: 6px; margin-top: 8px;">Votre dossier est en cours de traitement.</p>',
]);

sendTest($emailService, 'RECLAMATION_REPONSE', $testEmail, [
    'nom' => 'Jean Dupont',
    'id' => 42,
    'objet' => 'Probleme de notes',
    'reponse' => 'Nous avons verifie vos notes. Il n\'y a pas d\'erreur. Cordialement, la scolarite.',
]);

sendTest($emailService, 'MDP_CHANGE', $testEmail, [
    'nom' => 'Jean Dupont',
    'date_changement' => $now,
]);

sendTest($emailService, 'EMAIL_MODIFIE', $testEmail, [
    'nom' => 'Jean Dupont',
    'ancien_email' => 'ancien.email@email.com',
    'nouvel_email' => 'nouveau.email@email.com',
    'date_modification' => $now,
]);

sendTest($emailService, 'COMPTE_VERROUILLE', $testEmail, [
    'nom' => 'Jean Dupont',
    'login' => 'jean.dupont',
    'date_verrouillage' => $now,
    'duree' => 10,
]);

sendTest($emailService, 'CR_MODIFIE', $testEmail, [
    'nom' => 'Jean Dupont',
    'nom_rapport' => 'Automatisation des tests SWIFT',
    'nom_CR' => 'CR Soutenance Janvier 2026',
    'date_maj' => $now,
]);

// === OUTPUT ===
$success = count(array_filter($results, fn($r) => $r['status'] === 'OK'));
$fail = count($results) - $success;

?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Test des 32 emails - Check Master</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 30px; background: #f4f7f9; }
        h1 { color: #0f172a; }
        .summary { font-size: 18px; margin: 20px 0; padding: 20px; border-radius: 10px; }
        .summary.ok { background: #f0fdf4; border: 1px solid #bbf7d0; }
        .summary.fail { background: #fef2f2; border: 1px solid #fecaca; }
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; }
        th { background: #0f172a; color: white; padding: 12px 16px; text-align: left; }
        td { padding: 10px 16px; border-bottom: 1px solid #e2e8f0; }
        .OK { color: #10b981; font-weight: bold; }
        .ECHEC, .ERREUR { color: #ef4444; font-weight: bold; }
        .dest { color: #64748b; font-size: 13px; }
    </style>
</head>
<body>
    <h1>Test des 32 emails transactionnels</h1>
    <p>Destinataire : <strong><?= htmlspecialchars($testEmail) ?></strong></p>
    <div class="summary <?= $fail > 0 ? 'fail' : 'ok' ?>">
        <strong><?= $success ?>/<?= count($results) ?></strong> emails envoyes avec succes.
        <?php if ($fail > 0): ?>
            <span style="color: #ef4444;"><?= $fail ?> echec(s)</span>
        <?php else: ?>
            <span style="color: #10b981;">Tous les emails ont ete envoyes !</span>
        <?php endif; ?>
    </div>
    <table>
        <tr><th>#</th><th>Template</th><th>Statut</th><th>Erreur</th></tr>
        <?php foreach ($results as $i => $r): ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><strong><?= htmlspecialchars($r['template']) ?></strong></td>
            <td class="<?= $r['status'] ?>"><?= $r['status'] ?></td>
            <td><?= htmlspecialchars($r['error']) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
</body>
</html>

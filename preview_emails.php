<?php
/**
 * Visual Preview System for Check Master Transactional Emails
 * Permits real-time rendering of all 32 templates in the browser with mock data.
 */

// Load email templates config
$templatesConfig = require __DIR__ . '/app/config/email_templates.php';
$templates = $templatesConfig['TEMPLATES'];
$layout = $templatesConfig['LAYOUT'];

// Selected template key
$selectedKey = $_GET['template'] ?? 'USER_WELCOME';
if (!isset($templates[$selectedKey])) {
    $selectedKey = key($templates);
}

// Define comprehensive mock data for all templates
$mockData = [
    'USER_WELCOME' => [
        'nom' => 'Dr. Emeric Soro',
        'login' => 'emeric.soro@ufhb.edu.ci',
        'password_row' => '<tr><td class="info-label">Mot de passe provisoire :</td><td class="info-value"><div class="copyable-container"><code class="copy-value">k9$XmP2wL</code><span class="copy-btn" data-copy="k9$XmP2wL">Copier</span></div></td></tr>',
        'reset_password_section' => '<p style="margin-top: 16px;">Par sécurité, vous devrez réinitialiser ce mot de passe lors de votre première connexion.</p>',
        'login_url' => '#'
    ],
    'CANDIDATURE_RESULT' => [
        'nom' => 'Emeric Soro',
        'decision' => 'VALIDÉE',
        'status_icon' => '',
        'status_bg_color' => '#f0fdf4',
        'status_border_color' => '#bbf7d0',
        'status_text_color' => '#15803d',
        'details_html' => '<table class="info-table"><tr><td class="info-label">Scolarité :</td><td class="info-value">Dossier académique complet et validé</td></tr><tr><td class="info-label">Frais d\'inscription :</td><td class="info-value">Totalité des frais acquittée (Solde : 0 FCFA)</td></tr><tr><td class="info-label">Rapport déposé :</td><td class="info-value">« Conception d\'un système de surveillance IoT »</td></tr></table>',
        'action_message' => '<p>Félicitations, votre dossier de soutenance est accepté. Vous recevrez prochainement votre date de passage devant le jury.</p>'
    ],
    'PASSWORD_RESET' => [
        'reset_link' => '#'
    ],
    'REPORT_NOTIFICATION' => [
        'nom' => 'Emeric Soro',
        'nom_rapport' => 'Système de gestion d\'évaluations académiques',
        'nom_CR' => 'Commission de Validation Master 2',
        'date_CR' => '20 mai 2026'
    ],
    'COMMISSION_NOTIFICATION' => [
        'nom_CR' => 'Commission de Validation Master 2',
        'nbRapports' => '12',
        'id_CR' => 'COM-2026-089'
    ],
    'RESPONSABLE_NOTIFICATION' => [
        'nom_CR' => 'Commission de Validation Master 2',
        'nbRapports' => '12',
        'id_CR' => 'COM-2026-089'
    ],
    'CANDIDATURE_SOUMISE_ETUDIANT' => [
        'nom' => 'Emeric Soro',
        'id_candidature' => 'CAND-7892',
        'date_candidature' => '20 mai 2026 à 20:42'
    ],
    'CANDIDATURE_SOUMISE_ADMIN' => [
        'nom' => 'Emeric Soro',
        'num_etu' => '2026-ETU-904',
        'date_candidature' => '20 mai 2026',
        'id_candidature' => '7892',
        'admin_url' => '#'
    ],
    'DEPOT_RAPPORT' => [
        'nom_enseignant' => 'Prof. Adama Coulibaly',
        'nom_etudiant' => 'Emeric Soro',
        'nom_rapport' => 'Conception d\'un système de surveillance IoT',
        'theme_rapport' => 'Objets Connectés et Télésurveillance Académique',
        'date_depot' => '20 mai 2026',
        'role' => 'Directeur de Mémoire'
    ],
    'EVALUATION_RAPPORT_VALIDE' => [
        'nom' => 'Emeric Soro',
        'nom_rapport' => 'Conception d\'un système de surveillance IoT',
        'commentaires' => '<div class="cm-alert cm-alert-success"><h4 class="cm-alert-title text-success">Commentaires de la commission :</h4><p>Excellent travail de recherche. La méthodologie est robuste et l\'implémentation pratique démontre une maîtrise complète du sujet.</p></div>'
    ],
    'EVALUATION_RAPPORT_REJETE' => [
        'nom' => 'Emeric Soro',
        'nom_rapport' => 'Conception d\'un système de surveillance IoT',
        'commentaires' => '<div class="cm-alert cm-alert-danger"><h4 class="cm-alert-title text-danger">Corrections requises :</h4><p>La section consacrée à l\'analyse de sécurité réseau est trop succincte. Veuillez approfondir l\'étude des protocoles de chiffrement et soumettre à nouveau sous 15 jours.</p></div>'
    ],
    'AFFECTATION_ENCADRANT' => [
        'nom_enseignant' => 'Prof. Adama Coulibaly',
        'nom_etudiant' => 'Emeric Soro',
        'theme' => 'Conception d\'un système de surveillance IoT',
        'entreprise' => 'Innovations CI'
    ],
    'AFFECTATION_DIRECTEUR' => [
        'nom_enseignant' => 'Prof. Adama Coulibaly',
        'nom_etudiant' => 'Emeric Soro',
        'theme' => 'Conception d\'un système de surveillance IoT',
        'entreprise' => 'Innovations CI'
    ],
    'AJOUT_JURY' => [
        'nom_enseignant' => 'Prof. Adama Coulibaly',
        'role' => 'Rapporteur externe',
        'nom_etudiant' => 'Emeric Soro',
        'theme' => 'Conception d\'un système de surveillance IoT',
        'date_soutenance' => '12 juin 2026',
        'heure_soutenance' => '09:00',
        'salle' => 'Amphithéâtre 4'
    ],
    'RETRAIT_JURY' => [
        'nom_enseignant' => 'Prof. Adama Coulibaly',
        'nom_etudiant' => 'Emeric Soro',
        'theme' => 'Conception d\'un système de surveillance IoT',
        'role' => 'Rapporteur externe'
    ],
    'SOUTENANCE_PROGRAMMEE' => [
        'nom' => 'Emeric Soro',
        'nom_etudiant' => 'Emeric Soro',
        'theme' => 'Conception d\'un système de surveillance IoT',
        'date_soutenance' => '12 juin 2026',
        'heure_soutenance' => '09:00',
        'salle' => 'Amphithéâtre 4',
        'composition_jury' => '<h4 style="margin-top: 20px; color: #0f172a; font-size: 15px; font-weight: 600;">Composition du Jury :</h4><table class="info-table"><tr><td class="info-label">Président :</td><td class="info-value">Prof. Koffi N\'guessan</td></tr><tr><td class="info-label">Directeur :</td><td class="info-value">Prof. Adama Coulibaly</td></tr><tr><td class="info-label">Rapporteur :</td><td class="info-value">Dr. Kouamé Yao</td></tr></table>'
    ],
    'SOUTENANCE_MODIFIEE' => [
        'nom' => 'Emeric Soro',
        'nom_etudiant' => 'Emeric Soro',
        'date_soutenance' => '18 juin 2026',
        'heure_soutenance' => '14:30',
        'salle' => 'Salle de Visioconférence B'
    ],
    'SOUTENANCE_ANNULEE' => [
        'nom' => 'Emeric Soro',
        'nom_etudiant' => 'Emeric Soro',
        'theme' => 'Conception d\'un système de surveillance IoT',
        'date_soutenance' => '12 juin 2026'
    ],
    'DECISION_JURY_ETUDIANT' => [
        'nom' => 'Emeric Soro',
        'bg_color' => '#f0fdf4',
        'border_color' => '#bbf7d0',
        'text_color' => '#15803d',
        'decision' => 'ADMIS (Très Bien)',
        'theme' => 'Conception d\'un système de surveillance IoT',
        'note' => '17.5',
        'mention' => 'Très Bien'
    ],
    'DECISION_JURY_ENCADRANT' => [
        'nom' => 'Prof. Adama Coulibaly',
        'nom_etudiant' => 'Emeric Soro',
        'decision' => 'ADMIS',
        'note' => '17.5',
        'mention' => 'Très Bien'
    ],
    'BULLETIN_NOTES' => [
        'nom' => 'Emeric Soro',
        'semestre' => 'Semestre 4 (M2)',
        'moyenne' => '16.42/20',
        'credits' => '30/30'
    ],
    'INSCRIPTION_CONFIRMATION' => [
        'nom' => 'Emeric Soro',
        'annee_academique' => '2025-2026',
        'niveau' => 'Master 2 Recherche (M2R)',
        'montant_total' => '600 000',
        'montant_verse' => '300 000',
        'solde' => '300 000'
    ],
    'RECU_PAIEMENT' => [
        'nom' => 'Emeric Soro',
        'montant' => '300 000',
        'date_paiement' => '20 mai 2026',
        'mode_paiement' => 'Mobile Money (Orange Money)',
        'solde' => '0'
    ],
    'INSCRIPTION_VALIDEE' => [
        'nom' => 'Emeric Soro',
        'annee_academique' => '2025-2026'
    ],
    'RELANCE_IMPAYE' => [
        'nom' => 'Emeric Soro',
        'annee_academique' => '2025-2026',
        'montant_total' => '600 000',
        'montant_verse' => '300 000',
        'solde' => '300 000'
    ],
    'RECLAMATION_SOUMISE_ADMIN' => [
        'nom_etudiant' => 'Emeric Soro',
        'objet' => 'Contestation de note - Systèmes Répartis',
        'type' => 'Pédagogique',
        'id' => '409',
        'admin_url' => '#'
    ],
    'RECLAMATION_STATUT' => [
        'nom' => 'Emeric Soro',
        'id' => '409',
        'objet' => 'Contestation de note - Systèmes Répartis',
        'statut' => 'EN COURS DE TRAITEMENT',
        'statut_couleur' => '#a16207',
        'commentaire' => '<p>Votre dossier a été transmis au responsable de la matière pour examen approfondi des copies d\'examen.</p>'
    ],
    'RECLAMATION_REPONSE' => [
        'nom' => 'Emeric Soro',
        'id' => '409',
        'objet' => 'Contestation de note - Systèmes Répartis',
        'reponse' => 'Après vérification avec l\'enseignant responsable, une erreur de transcription de 2 points a été constatée. Votre note finale a été corrigée de 11/20 à 13/20. Votre bulletin a été réédité.'
    ],
    'MDP_CHANGE' => [
        'nom' => 'Emeric Soro',
        'date_changement' => '20 mai 2026 à 20:42'
    ],
    'EMAIL_MODIFIE' => [
        'nom' => 'Emeric Soro',
        'ancien_email' => 'e.soro@ufhb.edu.ci',
        'nouvel_email' => 'emeric.soro@gmail.com',
        'date_modification' => '20 mai 2026'
    ],
    'COMPTE_VERROUILLE' => [
        'nom' => 'Emeric Soro',
        'login' => 'emeric.soro',
        'date_verrouillage' => '20 mai 2026 à 20:42',
        'duree' => '15'
    ],
    'CR_MODIFIE' => [
        'nom' => 'Emeric Soro',
        'nom_CR' => 'Commission de Validation Master 2',
        'nom_rapport' => 'Conception d\'un système de surveillance IoT',
        'date_maj' => '20 mai 2026'
    ]
];

// Compile selected email
$template = $templates[$selectedKey];
$subject = $template['subject'];
$body = $template['body'];
$currentMock = $mockData[$selectedKey] ?? [];

foreach ($currentMock as $k => $v) {
    $subject = str_replace('{{' . $k . '}}', (string) $v, $subject);
    $body = str_replace('{{' . $k . '}}', (string) $v, $body);
}

// Compile inside Layout
$html = str_replace(['{{body}}', '{{subject}}'], [$body, $subject], $layout);
$html = str_replace('{{logo_src}}', 'public/image/logo_simple_cm.png', $html); // Local path fallback

// If raw iframe request, render compiled email directly
if (isset($_GET['raw'])) {
    echo $html;
    exit;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizer - Check Master Email Templates</title>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@600;700&family=Fira+Code&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background-color: #090d16;
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        header {
            background-color: #0e1424;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 16px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 10;
        }
        .logo-section h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 700;
            background: linear-gradient(135deg, #38bdf8, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .logo-section span {
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.15em;
            color: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.15);
            padding: 2px 8px;
            border-radius: 4px;
        }
        .controls {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        label {
            font-size: 13px;
            color: rgba(255, 255, 255, 0.6);
            font-weight: 500;
        }
        select {
            background-color: #1a2238;
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
            padding: 8px 16px;
            border-radius: 8px;
            font-family: inherit;
            font-size: 14px;
            font-weight: 500;
            outline: none;
            cursor: pointer;
            transition: border-color 0.2s;
            max-width: 320px;
        }
        select:focus {
            border-color: #38bdf8;
        }
        .main-container {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        .sidebar {
            width: 320px;
            background-color: #0c1122;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
            overflow-y: auto;
            padding: 24px;
        }
        .sidebar-section {
            margin-bottom: 24px;
        }
        .sidebar-section h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            color: rgba(255, 255, 255, 0.4);
            margin-bottom: 12px;
        }
        .template-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }
        .template-item a {
            display: block;
            color: #94a3b8;
            text-decoration: none;
            font-size: 13px;
            padding: 8px 12px;
            border-radius: 6px;
            transition: all 0.2s;
            font-weight: 500;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .template-item a:hover {
            background-color: rgba(255, 255, 255, 0.04);
            color: #ffffff;
        }
        .template-item.active a {
            background-color: rgba(56, 189, 248, 0.15);
            color: #38bdf8;
            font-weight: 600;
            border-left: 3px solid #38bdf8;
            border-top-left-radius: 0;
            border-bottom-left-radius: 0;
        }
        .viewport-pane {
            flex: 1;
            display: flex;
            flex-direction: column;
            background-color: #070a13;
            overflow: hidden;
        }
        .viewport-header {
            background-color: #0a0e1a;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 12px 24px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .email-subject {
            font-size: 14px;
            color: #ffffff;
            font-weight: 600;
        }
        .email-subject span {
            color: rgba(255, 255, 255, 0.4);
            font-weight: normal;
            margin-right: 8px;
        }
        .device-selector {
            display: flex;
            gap: 4px;
            background-color: #121829;
            padding: 4px;
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .device-btn {
            background: none;
            border: none;
            color: rgba(255, 255, 255, 0.4);
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .device-btn:hover {
            color: #ffffff;
        }
        .device-btn.active {
            background-color: #1a2238;
            color: #38bdf8;
            box-shadow: 0 1px 3px rgba(0,0,0,0.2);
        }
        .iframe-container {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 30px;
            overflow-y: auto;
            position: relative;
        }
        iframe {
            background-color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            height: 100%;
        }
        /* Device Sizes */
        iframe.desktop { width: 100%; max-width: 800px; }
        iframe.tablet { width: 640px; height: 800px; }
        iframe.mobile { width: 375px; height: 660px; }
    </style>
</head>
<body>
    <header>
        <div class="logo-section">
            <h1>Check Master <span>Email Lab</span></h1>
        </div>
        <div class="controls">
            <label for="template-select">Sélection Rapide :</label>
            <select id="template-select" onchange="window.location.href='?template=' + this.value">
                <?php foreach (array_keys($templates) as $key): ?>
                    <option value="<?= $key ?>" <?= $key === $selectedKey ? 'selected' : '' ?>><?= $key ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </header>

    <div class="main-container">
        <aside class="sidebar">
            <div class="sidebar-section">
                <h3>Toutes les notifications (32)</h3>
                <ul class="template-list">
                    <?php foreach (array_keys($templates) as $key): ?>
                        <li class="template-item <?= $key === $selectedKey ? 'active' : '' ?>">
                            <a href="?template=<?= $key ?>" title="<?= htmlspecialchars($templates[$key]['subject']) ?>">
                                <?= $key ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </aside>

        <main class="viewport-pane">
            <div class="viewport-header">
                <div class="email-subject">
                    <span>Sujet :</span> <?= htmlspecialchars($subject) ?>
                </div>
                <div class="device-selector">
                    <button class="device-btn active" onclick="setDevice('desktop', this)">Desktop</button>
                    <button class="device-btn" onclick="setDevice('tablet', this)">Tablette</button>
                    <button class="device-btn" onclick="setDevice('mobile', this)">Mobile</button>
                </div>
            </div>

            <div class="iframe-container">
                <iframe id="preview-frame" class="desktop" src="?template=<?= $selectedKey ?>&raw=1"></iframe>
            </div>
        </main>
    </div>

    <script>
        function setDevice(device, btn) {
            const frame = document.getElementById('preview-frame');
            frame.className = device;
            
            // Toggle active buttons
            document.querySelectorAll('.device-btn').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        }

        // Listen to iframe loads to wire up the interactive copy functionality
        const frame = document.getElementById('preview-frame');
        frame.addEventListener('load', () => {
            const iframeDoc = frame.contentDocument || frame.contentWindow.document;
            const copyBtns = iframeDoc.querySelectorAll('.copy-btn');
            
            copyBtns.forEach(btn => {
                // Ensure pointer style and interactivity
                btn.style.cursor = 'pointer';
                btn.addEventListener('click', (e) => {
                    const text = btn.getAttribute('data-copy');
                    if (!text) return;
                    
                    navigator.clipboard.writeText(text).then(() => {
                        const originalText = btn.textContent;
                        btn.textContent = 'Copié !';
                        btn.classList.add('copied');
                        
                        setTimeout(() => {
                            btn.textContent = originalText;
                            btn.classList.remove('copied');
                        }, 2000);
                    }).catch(err => {
                        console.error('Failed to copy: ', err);
                    });
                });
            });
        });
    </script>
</body>
</html>

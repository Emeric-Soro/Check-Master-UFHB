<?php

return [
    'LAYOUT' => '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Check Master</title>
    <style>
        @import url(\'https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Outfit:wght@600;700&display=swap\');

        body, .email-body {
            font-family: "Plus Jakarta Sans", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f8fafc;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f8fafc;
            padding: 48px 16px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 4px 30px -4px rgba(15, 23, 42, 0.04);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #ffffff;
            padding: 40px 48px 24px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .header img {
            max-height: 48px;
            display: block;
            margin: 0 auto;
        }
        .content {
            padding: 32px 48px 48px;
            font-size: 15px;
        }
        .content p {
            margin: 0 0 18px 0;
            line-height: 1.6;
        }
        .footer {
            background-color: #f8fafc;
            padding: 32px 48px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 0 0 8px;
            font-size: 12px;
            color: #64748b;
            line-height: 1.5;
        }
        .button {
            display: inline-block;
            padding: 12px 28px;
            background-color: #0f172a;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            margin: 24px 0;
            text-align: center;
            letter-spacing: 0.01em;
            transition: background-color 0.2s;
        }
        .greeting { 
            font-family: "Outfit", sans-serif;
            font-size: 18px; 
            color: #0f172a; 
            font-weight: 600; 
            margin-bottom: 24px; 
            letter-spacing: -0.01em;
        }
        .info-table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 16px 0; 
        }
        .info-label { 
            color: #64748b; 
            font-weight: 500; 
            font-size: 13px; 
            text-transform: uppercase;
            letter-spacing: 0.05em;
            padding: 12px 0; 
            width: 150px; 
            vertical-align: top; 
            border-bottom: 1px solid #f1f5f9;
        }
        .info-value { 
            font-weight: 600; 
            color: #0f172a; 
            padding: 12px 0; 
            font-size: 14px;
            vertical-align: top; 
            border-bottom: 1px solid #f1f5f9;
        }
        .code-block {
            background-color: #f8fafc;
            padding: 8px 12px;
            border-radius: 6px;
            font-family: Consolas, Monaco, monospace;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }
        .copy-hint {
            font-size: 11px;
            color: #94a3b8;
            margin-left: 8px;
            font-weight: normal;
            font-family: "Plus Jakarta Sans", -apple-system, sans-serif;
            display: inline-block;
            vertical-align: middle;
        }
        .copyable-container {
            display: inline-flex;
            align-items: center;
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 12px;
            margin: 4px 0;
            vertical-align: middle;
        }
        .copy-value {
            font-family: Consolas, Monaco, monospace;
            color: #0f172a;
            font-size: 13px;
            font-weight: 600;
            user-select: all;
            -webkit-user-select: all;
            -moz-user-select: all;
            -ms-user-select: all;
            border: none;
            background: none;
            padding: 0;
            margin: 0;
        }
        .copy-btn {
            font-family: "Plus Jakarta Sans", -apple-system, sans-serif;
            font-size: 10px;
            font-weight: 700;
            color: #475569;
            background-color: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 3px 8px;
            margin-left: 8px;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            display: inline-block;
            transition: all 0.2s ease;
            user-select: none;
            -webkit-user-select: none;
        }
        .copy-btn:hover {
            background-color: #f1f5f9;
            color: #0f172a;
            border-color: #cbd5e1;
        }
        .text-center {
            text-align: center;
        }
        
        /* Alert components styled for a high-end experience without emojis */
        .cm-alert {
            padding: 20px 24px;
            border-radius: 12px;
            margin: 24px 0;
            display: block;
            border: 1px solid;
        }
        .cm-alert-success { 
            background-color: #f0fdf4; 
            border-color: #bbf7d0; 
            color: #15803d;
        }
        .cm-alert-danger { 
            background-color: #fef2f2; 
            border-color: #fecaca; 
            color: #b91c1c;
        }
        .cm-alert-warning { 
            background-color: #fffbeb; 
            border-color: #fef08a; 
            color: #a16207;
        }
        .cm-alert-info { 
            background-color: #f8fafc; 
            border-color: #e2e8f0; 
            color: #334155;
        }
        
        .cm-alert-title { 
            font-family: "Outfit", sans-serif;
            font-weight: 700; 
            margin: 0 0 8px 0; 
            font-size: 13px; 
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }
        .text-success { color: #15803d; }
        .text-danger { color: #b91c1c; }
        .text-warning { color: #a16207; }
        .text-info { color: #334155; }
        
        .cm-alert p { margin: 0; font-size: 14px; line-height: 1.5; }
        .cm-alert p + p { margin-top: 8px; }
        
        @media only screen and (max-width: 600px) {
            .wrapper {
                padding: 16px 8px;
            }
            .content {
                padding: 24px 20px 32px;
            }
            .header {
                padding: 24px 20px 16px;
            }
            .footer {
                padding: 24px 20px;
            }
        }
    </style>
</head>
<body class="email-body">
    <div style="display: none; max-height: 0px; overflow: hidden;">
        Notification Check Master
    </div>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="{{logo_src}}" alt="Check Master">
            </div>
            <div class="content">
                {{body}}
            </div>
            <div class="footer">
                <p>Ce message a été envoyé automatiquement par le système, merci de ne pas y répondre.</p>
                <p style="margin-top: 10px;">&copy; ' . date('Y') . ' Check Master. Tous droits réservés.</p>
            </div>
        </div>
    </div>
</body>
</html>
    ',

    'TEMPLATES' => [

        'USER_WELCOME' => [
            'subject' => 'Bienvenue sur Check Master',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre compte utilisateur a été créé avec succès sur la plateforme de gestion académique <strong>Check Master</strong>.</p>
                <p>Vous pouvez dès à présent vous connecter en utilisant les identifiants sécurisés générés ci-dessous :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Identifiants de connexion</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Identifiant :</td>
                            <td class="info-value">
                                <div class="copyable-container">
                                    <code class="copy-value">{{login}}</code>
                                    <span class="copy-btn" data-copy="{{login}}">Copier</span>
                                </div>
                            </td>
                        </tr>
                        {{password_row}}
                    </table>
                    {{reset_password_section}}
                </div>
                
                <p class="text-center" style="margin-top: 30px;">
                    <a href="{{login_url}}" class="button">Accéder à la plateforme</a>
                </p>
            '
        ],

        'CANDIDATURE_RESULT' => [
            'subject' => 'Résultat de votre candidature à la soutenance',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>L\'évaluation de votre dossier de candidature à la soutenance de fin de cycle a été finalisée.</p>
                
                <div class="cm-alert" style="background-color: {{status_bg_color}}; border: 1px solid {{status_border_color}}; color: {{status_text_color}};">
                    <h3 style="margin: 0; text-align: center; font-size: 16px; font-weight: 700; font-family: \'Outfit\', sans-serif; letter-spacing: 0.02em;">DÉCISION FINALE : {{decision}}</h3>
                </div>
                
                <h3 style="margin-top: 32px; margin-bottom: 16px; font-family: \'Outfit\', sans-serif; font-size: 16px; font-weight: 700; color: #0f172a; border-bottom: 1px solid #e2e8f0; padding-bottom: 8px;">Résumé détaillé de l\'évaluation</h3>
                {{details_html}}
                
                {{action_message}}
            '
        ],

        'PASSWORD_RESET' => [
            'subject' => 'Réinitialisation de votre mot de passe',
            'body' => '
                <p class="greeting">Bonjour,</p>
                <p>Une demande de réinitialisation de mot de passe a été initiée pour votre compte Check Master.</p>
                <p>Pour configurer un nouveau mot de passe, veuillez cliquer sur le bouton de réinitialisation ci-dessous :</p>
                
                <p class="text-center" style="margin: 30px 0;">
                    <a href="{{reset_link}}" class="button">Réinitialiser le mot de passe</a>
                </p>
                
                <div class="cm-alert cm-alert-warning">
                    <h4 class="cm-alert-title text-warning">Sécurité et Expiration</h4>
                    <p>Par mesure de sécurité, ce lien de réinitialisation expirera dans <strong>1 heure</strong>.</p>
                    <p style="font-size: 13px; color: #a16207; margin-top: 6px;">Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet e-mail en toute sécurité. Votre mot de passe actuel restera inchangé.</p>
                </div>
            '
        ],

        'REPORT_NOTIFICATION' => [
            'subject' => 'Notification de compte rendu de soutenance',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre rapport intitulé <strong>« {{nom_rapport}} »</strong> a été officiellement inclus dans le compte rendu de session <strong>« {{nom_CR}} »</strong> établi le {{date_CR}}.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Pièce jointe officielle</h4>
                    <p style="margin: 0;">Le compte rendu complet et officiel de la séance d\'évaluation a été généré et est disponible en pièce jointe de ce courriel au format PDF.</p>
                </div>
                
                <p style="margin-top: 24px;">Nous vous prions d\'agréer nos salutations distinguées.</p>
                <p><em>L\'équipe pédagogique de coordination</em></p>
            '
        ],

        'COMMISSION_NOTIFICATION' => [
            'subject' => 'Compte rendu de commission disponible : {{nom_CR}}',
            'body' => '
                <p class="greeting">Bonjour,</p>
                <p>Le compte rendu de commission de soutenance <strong>{{nom_CR}}</strong> a été formellement validé et enregistré dans le système.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Informations de synthèse</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Dossiers concernés :</td>
                            <td class="info-value">{{nbRapports}} rapport(s) d\'étudiant</td>
                        </tr>
                        <tr>
                            <td class="info-label">Référence interne :</td>
                            <td class="info-value"><code class="code-block">#{{id_CR}}</code></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous recevez cette notification automatique en tant que <strong>membre de la commission de validation</strong>.</p>
                <p>Le procès-verbal complet de la commission est joint à ce courriel au format PDF pour votre archivage.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>L\'administration académique Check Master</p>
            '
        ],

        'RESPONSABLE_NOTIFICATION' => [
            'subject' => 'Compte rendu disponible : {{nom_CR}}',
            'body' => '
                <p class="greeting">Bonjour,</p>
                <p>Le compte rendu académique officiel <strong>{{nom_CR}}</strong> a été rédigé et est à présent disponible pour consultation.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Synthèse de session</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Dossiers concernés :</td>
                            <td class="info-value">{{nbRapports}} rapport(s) d\'étudiant</td>
                        </tr>
                        <tr>
                            <td class="info-label">Référence de session :</td>
                            <td class="info-value"><code class="code-block">#{{id_CR}}</code></td>
                        </tr>
                    </table>
                </div>
                
                <p>Cette notification automatique vous est adressée en votre qualité de <strong>responsable de filière ou de parcours</strong>.</p>
                <p>Le document PDF consolidant l\'ensemble des résultats est attaché à cet email.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>L\'administration Check Master</p>
            '
        ],

        'CANDIDATURE_SOUMISE_ETUDIANT' => [
            'subject' => 'Confirmation de votre candidature à la soutenance',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre dossier de candidature à la soutenance de fin de cycle a bien été réceptionné par nos services.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">Accusé de réception</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Référence :</td>
                            <td class="info-value"><code class="code-block">#{{id_candidature}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Enregistrement :</td>
                            <td class="info-value">{{date_candidature}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Statut initial :</td>
                            <td class="info-value" style="color: #a16207; font-weight: bold;">En attente d\'instruction</td>
                        </tr>
                    </table>
                </div>
                
                <p>Le service scolarité va procéder à l\'instruction administrative de vos pièces justificatives. Une notification vous sera transmise automatiquement dès qu\'une décision sera prise.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le secrétariat académique</p>
            '
        ],

        'CANDIDATURE_SOUMISE_ADMIN' => [
            'subject' => 'Nouvelle candidature à la soutenance à traiter',
            'body' => '
                <p class="greeting">Bonjour,</p>
                <p>Une nouvelle demande d\'inscription à la soutenance vient d\'être soumise par un étudiant et requiert votre instruction.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Dossier à instruire</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value"><strong>{{nom}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">N° Étudiant :</td>
                            <td class="info-value"><code class="code-block">{{num_etu}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date de soumission :</td>
                            <td class="info-value">{{date_candidature}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Réf. Candidature :</td>
                            <td class="info-value">#{{id_candidature}}</td>
                        </tr>
                    </table>
                </div>
                
                <p class="text-center" style="margin-top: 30px;">
                    <a href="{{admin_url}}" class="button">Instruire la candidature</a>
                </p>
            '
        ],

        'DEPOT_RAPPORT' => [
            'subject' => 'Dépôt de rapport - {{nom_rapport}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>L\'étudiant <strong>{{nom_etudiant}}</strong> a procédé au dépôt numérique de son rapport de stage de fin de cycle.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Détails du dépôt</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Rapport :</td>
                            <td class="info-value">« {{nom_rapport}} »</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thématique :</td>
                            <td class="info-value">{{theme_rapport}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date de dépôt :</td>
                            <td class="info-value">{{date_depot}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Votre rôle :</td>
                            <td class="info-value"><span style="background-color: #e2e8f0; padding: 4px 8px; border-radius: 4px; font-size: 12px; font-weight: bold; color: #1e293b;">{{role}}</span></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous pouvez à présent vous connecter à la plateforme pour accéder au manuscrit complet et préparer votre rapport d\'évaluation en vue de la soutenance.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>L\'administration Check Master</p>
            '
        ],

        'EVALUATION_RAPPORT_VALIDE' => [
            'subject' => 'Rapport validé - {{nom_rapport}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous avons le plaisir de vous informer que la commission a validé sur le plan académique votre rapport de stage <strong>« {{nom_rapport}} »</strong>.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">Avis de la Commission : VALIDÉ</h4>
                    <table class="info-table" style="margin-bottom: 12px;">
                        <tr>
                            <td class="info-label" style="border: none;">Rapport :</td>
                            <td class="info-value" style="border: none;">« {{nom_rapport}} »</td>
                        </tr>
                    </table>
                    {{commentaires}}
                </div>
                
                <p>Cette validation vous permet de poursuivre le processus d\'inscription et de planification finale de votre date de passage en soutenance.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>La commission d\'évaluation académique</p>
            '
        ],

        'EVALUATION_RAPPORT_REJETE' => [
            'subject' => 'Rapport à corriger - {{nom_rapport}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Après examen attentif de votre rapport <strong>« {{nom_rapport}} »</strong>, la commission a estimé que des corrections substantielles sont requises avant de pouvoir autoriser votre soutenance.</p>
                
                <div class="cm-alert cm-alert-danger">
                    <h4 class="cm-alert-title text-danger">Avis de la Commission : À CORRIGER</h4>
                    <table class="info-table" style="margin-bottom: 12px;">
                        <tr>
                            <td class="info-label" style="border: none;">Rapport :</td>
                            <td class="info-value" style="border: none;">« {{nom_rapport}} »</td>
                        </tr>
                    </table>
                    {{commentaires}}
                </div>
                
                <p>Nous vous invitons à prendre attentivement connaissance des observations de la commission, à effectuer les révisions requises, et à re-déposer la version corrigée de votre document sur la plateforme dans les plus brefs délais.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>La commission d\'évaluation académique</p>
            '
        ],

        'AFFECTATION_ENCADRANT' => [
            'subject' => 'Attribution en tant qu\'encadrant pédagogique',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Vous avez été officiellement désigné en tant qu\'<strong>encadrant pédagogique</strong> pour le suivi du travail de l\'étudiant mentionné ci-dessous :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Fiche d\'affectation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Sujet d\'étude :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Structure d\'accueil :</td>
                            <td class="info-value">{{entreprise}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>En tant qu\'encadrant, vous assurerez l\'accompagnement académique et méthodologique de l\'étudiant tout au long de sa période d\'immersion professionnelle.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>La direction des enseignements</p>
            '
        ],

        'AFFECTATION_DIRECTEUR' => [
            'subject' => 'Attribution en tant que directeur de mémoire',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Vous avez été formellement affecté en qualité de <strong>directeur de mémoire</strong> pour superviser les travaux de recherche de l\'étudiant suivant :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Fiche académique d\'affectation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Thématique :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Organisme :</td>
                            <td class="info-value">{{entreprise}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Votre mission consistera à guider l\'étudiant dans sa démarche scientifique, de la construction de la problématique jusqu\'à la rédaction du manuscrit final destiné à la soutenance.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>La direction des affaires académiques</p>
            '
        ],

        'AJOUT_JURY' => [
            'subject' => 'Assignation au jury de soutenance',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Vous avez été désigné par la direction pour siéger en qualité de <strong>{{role}}</strong> dans le jury de soutenance ci-après :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Détails de la session</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Candidat :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème soutenu :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date :</td>
                            <td class="info-value">{{date_soutenance}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Heure de passage :</td>
                            <td class="info-value"><strong>{{heure_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Lieu / Salle :</td>
                            <td class="info-value">{{salle}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Le manuscrit et les pièces constitutives du dossier sont accessibles en ligne sur votre portail enseignant. Nous vous remercions pour votre précieuse collaboration scientifique.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le secrétariat général des examens</p>
            '
        ],

        'RETRAIT_JURY' => [
            'subject' => 'Retrait du jury de soutenance',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Suite à une réorganisation du calendrier de soutenance, nous vous informons que votre affectation en tant que membre de jury a été modifiée pour le candidat ci-dessous :</p>
                
                <div class="cm-alert cm-alert-warning">
                    <h4 class="cm-alert-title text-warning">Avis de modification</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème initial :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Rôle initial :</td>
                            <td class="info-value"><del>{{role}}</del></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous êtes dispensé de présence pour cette séance. Nous tenons à vous remercier pour votre disponibilité réactive et nous vous prions de nous excuser pour ce changement de programmation.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>L\'administration académique</p>
            '
        ],

        'SOUTENANCE_PROGRAMMEE' => [
            'subject' => 'Soutenance programmée - {{date_soutenance}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le calendrier officiel des examens a été arrêté. Nous vous confirmons que votre séance de soutenance est officiellement planifiée selon les modalités suivantes :</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">Programmation validée</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Candidat :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème validé :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date d\'évaluation :</td>
                            <td class="info-value"><strong>{{date_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Heure de passage :</td>
                            <td class="info-value"><strong>{{heure_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Salle assignée :</td>
                            <td class="info-value">{{salle}}</td>
                        </tr>
                    </table>
                </div>
                
                {{composition_jury}}
                
                <p style="margin-top: 24px;">Nous vous conseillons de vous présenter 30 minutes avant l\'heure indiquée muni de vos supports de présentation.</p>
                <p>Nous vous souhaitons un franc succès pour cette épreuve majeure de votre parcours.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>La direction de la scolarité</p>
            '
        ],

        'SOUTENANCE_MODIFIEE' => [
            'subject' => 'Soutenance modifiée - {{nom_etudiant}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous vous informons qu\'une modification de calendrier ou de logistique a été appliquée à la programmation de la soutenance concernant l\'étudiant <strong>{{nom_etudiant}}</strong>.</p>
                
                <div class="cm-alert cm-alert-warning">
                    <h4 class="cm-alert-title text-warning">Avis de modification de planning</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Candidat :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvelle Date :</td>
                            <td class="info-value"><strong>{{date_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvel Horaire :</td>
                            <td class="info-value"><strong>{{heure_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvelle Salle :</td>
                            <td class="info-value">{{salle}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Nous vous prions de bien vouloir mettre à jour vos agendas en fonction de ces nouveaux paramètres officiels.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le secrétariat académique</p>
            '
        ],

        'SOUTENANCE_ANNULEE' => [
            'subject' => 'Soutenance annulée - {{nom_etudiant}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous vous notifions que la soutenance initialement planifiée pour le candidat <strong>{{nom_etudiant}}</strong> a dû être annulée.</p>
                
                <div class="cm-alert cm-alert-danger">
                    <h4 class="cm-alert-title text-danger">Avis d\'annulation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Candidat :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème concerné :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date annulée :</td>
                            <td class="info-value"><del>{{date_soutenance}}</del></td>
                        </tr>
                    </table>
                </div>
                
                <p>Une nouvelle date de soutenance fera l\'objet d\'une étude et vous sera notifiée ultérieurement par nos services dès confirmation officielle.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service d\'organisation des examens</p>
            '
        ],

        'DECISION_JURY_ETUDIANT' => [
            'subject' => 'Résultat de votre soutenance',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le jury d\'évaluation a consigné sa décision finale à l\'issue de votre prestation de soutenance.</p>
                
                <div class="cm-alert" style="background-color: {{bg_color}}; border: 1px solid {{border_color}}; color: {{text_color}};">
                    <h3 style="margin: 0; text-align: center; font-size: 16px; font-weight: 700; font-family: \'Outfit\', sans-serif; letter-spacing: 0.02em;">DÉCISION DU JURY : {{decision}}</h3>
                </div>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Détails des résultats</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Thématique soutenue :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Note obtenue :</td>
                            <td class="info-value"><strong>{{note}} / 20</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Mention :</td>
                            <td class="info-value"><strong>{{mention}}</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vos documents officiels (relevé de notes de soutenance et extrait de procès-verbal) sont dès à présent téléchargeables sur votre espace personnel Check Master.</p>
                <p>Toutes nos félicitations pour l\'accomplissement de ce travail académique de fin d\'études.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le secrétariat des délibérations</p>
            '
        ],

        'DECISION_JURY_ENCADRANT' => [
            'subject' => 'Résultat de soutenance - {{nom_etudiant}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Les délibérations officielles de la soutenance de l\'étudiant <strong>{{nom_etudiant}}</strong> ont été enregistrées dans le système Check Master.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Résultats d\'évaluation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Candidat :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Décision du jury :</td>
                            <td class="info-value"><strong>{{decision}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Note attribuée :</td>
                            <td class="info-value"><strong>{{note}} / 20</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Mention :</td>
                            <td class="info-value"><strong>{{mention}}</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Cette notification automatique vous est adressée en votre qualité de <strong>membre ou d\'encadrant référent</strong> du candidat.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>L\'administration académique</p>
            '
        ],

        'BULLETIN_NOTES' => [
            'subject' => 'Notes disponibles - {{semestre}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le service scolarité a finalisé la saisie et le calcul des résultats pour le <strong>{{semestre}}</strong>.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">Bulletin de notes disponible</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Session :</td>
                            <td class="info-value">{{semestre}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Moyenne générale :</td>
                            <td class="info-value"><strong>{{moyenne}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Crédits capitalisés :</td>
                            <td class="info-value">{{credits}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous pouvez vous connecter à votre portail étudiant pour examiner le détail de vos notes par matière et télécharger votre relevé de notes au format numérique signé.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service de la scolarité centrale</p>
            '
        ],

        'INSCRIPTION_CONFIRMATION' => [
            'subject' => 'Confirmation d\'inscription - {{annee_academique}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre dossier d\'inscription administrative pour l\'année universitaire <strong>{{annee_academique}}</strong> a été validé sur la plateforme.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Situation administrative et financière</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Niveau d\'études :</td>
                            <td class="info-value"><strong>{{niveau}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Frais d\'inscription :</td>
                            <td class="info-value"><strong>{{montant_total}} FCFA</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Montant versé :</td>
                            <td class="info-value">{{montant_verse}} FCFA</td>
                        </tr>
                        <tr>
                            <td class="info-label">Solde restant dû :</td>
                            <td class="info-value" style="color: #b91c1c;"><strong>{{solde}} FCFA</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Nous vous rappelons que la scolarité doit être intégralement soldée selon le calendrier financier convenu pour maintenir vos accès aux examens de fin d\'année.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service de la scolarité</p>
            '
        ],

        'RECU_PAIEMENT' => [
            'subject' => 'Reçu de paiement - {{montant}} FCFA',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous vous confirmons le bon traitement comptable et la réception de votre versement financier de scolarité.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">Reçu de paiement</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Montant crédité :</td>
                            <td class="info-value" style="color: #15803d;"><strong>{{montant}} FCFA</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date d\'opération :</td>
                            <td class="info-value">{{date_paiement}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Mode de règlement :</td>
                            <td class="info-value">{{mode_paiement}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Solde restant :</td>
                            <td class="info-value"><strong>{{solde}} FCFA</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Votre reçu de paiement officiel au format PDF signé numériquement est disponible en téléchargement sur votre portail.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service de l\'agence comptable</p>
            '
        ],

        'INSCRIPTION_VALIDEE' => [
            'subject' => 'Inscription validée - {{annee_academique}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous avons le plaisir de vous informer que votre dossier d\'inscription universitaire pour l\'année <strong>{{annee_academique}}</strong> est désormais définitif et pleinement validé.</p>
                
                <div class="cm-alert cm-alert-success" style="text-align: center;">
                    <h4 class="cm-alert-title text-success" style="margin: 0 0 4px 0;">Frais de scolarité acquittés</h4>
                    <p style="margin: 4px 0 0; font-size: 15px;">Solde : <strong>0 FCFA</strong></p>
                </div>
                
                <p>Votre quitus de scolarité a été généré et tous les services et outils du portail Check Master vous sont pleinement ouverts pour l\'année en cours.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service de la scolarité universitaire</p>
            '
        ],

        'RELANCE_IMPAYE' => [
            'subject' => 'Rappel : solde d\'inscription en attente',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Après vérification comptable de votre dossier d\'inscription pour l\'année académique <strong>{{annee_academique}}</strong>, il s\'avère qu\'un solde demeure en attente de régularisation.</p>
                
                <div class="cm-alert cm-alert-warning">
                    <h4 class="cm-alert-title text-warning">Rappel de versement</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Frais exigibles :</td>
                            <td class="info-value">{{montant_total}} FCFA</td>
                        </tr>
                        <tr>
                            <td class="info-label">Déjà réglé :</td>
                            <td class="info-value">{{montant_verse}} FCFA</td>
                        </tr>
                        <tr>
                            <td class="info-label">Solde impayé :</td>
                            <td class="info-value" style="color: #b91c1c;"><strong>{{solde}} FCFA</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Nous vous demandons de bien vouloir procéder à la régularisation de ce montant sous un délai de 8 jours afin d\'éviter la suspension temporaire de vos droits d\'accès aux services pédagogiques.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service de la comptabilité universitaire</p>
            '
        ],

        'RECLAMATION_SOUMISE_ADMIN' => [
            'subject' => 'Nouvelle réclamation - {{objet}}',
            'body' => '
                <p class="greeting">Bonjour,</p>
                <p>Un dossier de réclamation vient d\'être soumis par un étudiant sur le portail et requiert votre instruction.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Dossier de réclamation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value"><strong>{{nom_etudiant}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Objet :</td>
                            <td class="info-value">{{objet}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Catégorie :</td>
                            <td class="info-value">{{type}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Référence :</td>
                            <td class="info-value"><code class="code-block">REC-{{id}}</code></td>
                        </tr>
                    </table>
                </div>
                
                <p class="text-center" style="margin-top: 30px;">
                    <a href="{{admin_url}}" class="button">Consulter et Traiter le ticket</a>
                </p>
            '
        ],

        'RECLAMATION_STATUT' => [
            'subject' => 'Suivi de votre réclamation - {{statut}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous vous informons de la mise à jour de l\'instruction concernant votre ticket de réclamation.</p>
                
                <div class="cm-alert" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid {{statut_couleur}};">
                    <table class="info-table" style="margin: 0;">
                        <tr>
                            <td class="info-label" style="border: none; padding: 6px 0;">Référence ticket :</td>
                            <td class="info-value" style="border: none; padding: 6px 0;"><code class="code-block">REC-{{id}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label" style="border: none; padding: 6px 0;">Sujet traité :</td>
                            <td class="info-value" style="border: none; padding: 6px 0;">{{objet}}</td>
                        </tr>
                        <tr>
                            <td class="info-label" style="border: none; padding: 6px 0;">Statut actuel :</td>
                            <td class="info-value" style="border: none; padding: 6px 0; color: {{statut_couleur}}; font-weight: 700; text-transform: uppercase;">{{statut}}</td>
                        </tr>
                    </table>
                </div>
                
                {{commentaire}}
                
                <p style="margin-top: 24px;">Le fil de discussion complet et le suivi en temps réel de votre réclamation restent accessibles sur votre espace Check Master.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service de la scolarité</p>
            '
        ],

        'RECLAMATION_REPONSE' => [
            'subject' => 'Réponse à votre réclamation - {{objet}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Une réponse définitive a été apportée à votre dossier de réclamation par le secrétariat d\'études.</p>
                
                <div class="cm-alert cm-alert-info">
                    <table class="info-table" style="margin: 0 0 16px 0;">
                        <tr>
                            <td class="info-label" style="border: none; padding: 6px 0;">Référence :</td>
                            <td class="info-value" style="border: none; padding: 6px 0;"><code class="code-block">REC-{{id}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label" style="border: none; padding: 6px 0;">Objet du ticket :</td>
                            <td class="info-value" style="border: none; padding: 6px 0;">{{objet}}</td>
                        </tr>
                    </table>
                    
                    <h4 class="cm-alert-title text-info" style="margin-top: 16px; margin-bottom: 8px;">Décision administrative :</h4>
                    <div style="background-color: #ffffff; padding: 18px; border-radius: 8px; border: 1px solid #e2e8f0; color: #334155; font-size: 14px; line-height: 1.6;">
                        {{reponse}}
                    </div>
                </div>
                
                <p>Si vous avez des questions complémentaires concernant cette résolution, n\'hésitez pas à nous recontacter via le fil de discussion en ligne.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>Le service de la scolarité</p>
            '
        ],

        'MDP_CHANGE' => [
            'subject' => 'Confirmation de changement de mot de passe',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>La sécurité de votre compte a été mise à jour. Nous vous confirmons que votre mot de passe a été modifié avec succès.</p>
                
                <div class="cm-alert cm-alert-success" style="text-align: center;">
                    <h4 class="cm-alert-title text-success" style="margin: 0 0 4px 0;">Sécurité mise à jour</h4>
                    <p style="margin: 4px 0 0; font-size: 13px; color: #475569;">Changement effectué le : {{date_changement}}</p>
                </div>
                
                <div class="cm-alert cm-alert-warning" style="margin-top: 32px;">
                    <h4 class="cm-alert-title text-warning">Alerte de sécurité</h4>
                    <p style="margin: 0; font-size: 13px; color: #a16207;">Si vous n\'êtes pas à l\'origine de cette modification, votre compte a peut-être fait l\'objet d\'une compromission. Veuillez contacter d\'extrême urgence l\'administrateur système de la scolarité pour geler vos accès.</p>
                </div>
                
                <p style="margin-top: 24px;">Cordialement,<br>La direction des systèmes d\'information</p>
            '
        ],

        'EMAIL_MODIFIE' => [
            'subject' => 'Votre adresse email a été modifiée',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous vous informons qu\'une modification de votre adresse e-mail de contact a été enregistrée sur votre espace personnel Check Master.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Détails des modifications</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Ancien email :</td>
                            <td class="info-value">{{ancien_email}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvel email :</td>
                            <td class="info-value"><strong>{{nouvel_email}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date :</td>
                            <td class="info-value">{{date_modification}}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="cm-alert cm-alert-warning" style="margin-top: 32px;">
                    <h4 class="cm-alert-title text-warning">Alerte de sécurité</h4>
                    <p style="margin: 0; font-size: 13px; color: #a16207;">Si vous n\'avez pas expressément demandé ce changement, veuillez immédiatement prendre contact avec le support informatique pour sécuriser vos accès administratifs.</p>
                </div>
                
                <p style="margin-top: 24px;">Cordialement,<br>La direction des systèmes d\'information</p>
            '
        ],

        'COMPTE_VERROUILLE' => [
            'subject' => 'Alerte de sécurité - Compte verrouillé',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Par mesure de sécurité préventive, l\'accès à votre compte Check Master a été temporairement suspendu suite à des tentatives de connexion infructueuses successives.</p>
                
                <div class="cm-alert cm-alert-danger">
                    <h4 class="cm-alert-title text-danger">Accès suspendu</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Identifiant visé :</td>
                            <td class="info-value"><code class="code-block">{{login}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Heure d\'alerte :</td>
                            <td class="info-value">{{date_verrouillage}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Suspension :</td>
                            <td class="info-value"><strong>{{duree}} minutes</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>À l\'issue de la durée de suspension indiquée, vous pourrez tenter une nouvelle authentification. En cas d\'oubli de vos paramètres, nous vous invitons à utiliser la procédure sécurisée de récupération de mot de passe.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>La direction de la sécurité Check Master</p>
            '
        ],

        'CR_MODIFIE' => [
            'subject' => 'Compte rendu mis à jour - {{nom_CR}}',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le procès-verbal académique ou compte rendu officiel <strong>{{nom_CR}}</strong> auquel est lié votre dossier a fait l\'objet d\'une mise à jour corrective.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">Mise à jour du document</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Rapport concerné :</td>
                            <td class="info-value">« {{nom_rapport}} »</td>
                        </tr>
                        <tr>
                            <td class="info-label">Mise à jour :</td>
                            <td class="info-value">{{date_maj}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>La nouvelle version consolidée et visée de ce compte rendu est annexée à ce courriel au format PDF pour votre information.</p>
                
                <p style="margin-top: 24px;">Cordialement,<br>La commission académique des délibérations</p>
            '
        ],
    ]
];

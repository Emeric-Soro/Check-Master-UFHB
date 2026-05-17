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
        body, .email-body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f1f5f9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f1f5f9;
            padding: 40px 10px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #ffffff;
            padding: 30px 40px 20px;
            text-align: center;
            border-bottom: 1px solid #f1f5f9;
        }
        .header img {
            max-height: 50px;
            display: block;
            margin: 0 auto;
        }
        .content {
            padding: 30px 40px 40px;
            font-size: 15px;
        }
        .content p {
            margin: 0 0 16px 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 24px 40px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        .footer p {
            margin: 0 0 8px;
            font-size: 12px;
            color: #64748b;
        }
        .button {
            display: inline-block;
            padding: 12px 28px;
            background-color: #1a5276;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            font-size: 15px;
            margin: 20px 0;
            text-align: center;
            transition: background-color 0.2s;
        }
        .greeting { font-size: 18px; color: #1a5276; font-weight: 600; margin-bottom: 24px; }
        .info-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .info-label { color: #64748b; font-weight: 500; font-size: 14px; padding: 8px 0; width: 140px; vertical-align: top; }
        .info-value { font-weight: 600; color: #0f172a; padding: 8px 0; vertical-align: top; }
        .code-block {
            background-color: #f1f5f9;
            padding: 6px 12px;
            border-radius: 6px;
            font-family: Consolas, Monaco, monospace;
            color: #1a5276;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid #cbd5e1;
            display: inline-block;
        }
        .copy-hint {
            font-size: 12px;
            color: #94a3b8;
            margin-left: 8px;
            font-weight: normal;
            font-family: "Inter", -apple-system, sans-serif;
            display: inline-block;
            vertical-align: middle;
        }
        
        /* Alert components mimicking UI */
        .cm-alert {
            padding: 16px 20px;
            border-radius: 8px;
            margin: 24px 0;
            display: block;
        }
        .cm-alert-success { background-color: #ecfdf5; border: 1px solid #a7f3d0; border-left: 4px solid #10b981; }
        .cm-alert-danger { background-color: #fef2f2; border: 1px solid #fecaca; border-left: 4px solid #ef4444; }
        .cm-alert-warning { background-color: #fffbeb; border: 1px solid #fde68a; border-left: 4px solid #f59e0b; }
        .cm-alert-info { background-color: #eff6ff; border: 1px solid #bfdbfe; border-left: 4px solid #3b82f6; }
        
        .cm-alert-title { font-weight: 600; margin: 0 0 8px 0; font-size: 15px; }
        .text-success { color: #047857; }
        .text-danger { color: #b91c1c; }
        .text-warning { color: #b45309; }
        .text-info { color: #1d4ed8; }
        
        .cm-alert p { margin: 0; }
        .cm-alert p + p { margin-top: 8px; }
    </style>
</head>
<body class="email-body">
    <div style="display: none; max-height: 0px; overflow: hidden;">
        Notification Check Master
    </div>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <img src="{{logo_src}}" alt="Check Master Logo">
            </div>
            <div class="content">
                {{body}}
            </div>
            <div class="footer">
                <p>Ce message a été envoyé automatiquement, merci de ne pas y répondre.</p>
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
                <p>Votre compte a été créé sur la plateforme <strong>Check Master</strong>.</p>
                <p>Vous pouvez dès à présent vous connecter avec les informations ci-dessous :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Vos identifiants de connexion</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Identifiant :</td>
                            <td class="info-value">
                                <code class="code-block">{{login}}</code>
                                <span class="copy-hint">(Double-cliquez pour sélectionner)</span>
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
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>L\'évaluation de votre candidature à la soutenance est terminée.</p>
                
                <div class="cm-alert" style="background-color: {{status_bg_color}}; border: 1px solid {{status_border_color}}; border-left: 4px solid {{status_text_color}};">
                    <h3 style="margin: 0; color: {{status_text_color}}; text-align: center;">{{status_icon}} Décision finale : {{decision}}</h3>
                </div>
                
                <h3 style="margin-top: 30px; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; color: #1a5276;">Résumé détaillé de l\'évaluation</h3>
                {{details_html}}
                
                {{action_message}}
            '
        ],

        'PASSWORD_RESET' => [
            'subject' => 'Réinitialisation de votre mot de passe',
            'body' => '
                <p>Bonjour,</p>
                <p>Pour réinitialiser votre mot de passe, cliquez sur le bouton ci-dessous :</p>
                
                <p class="text-center" style="margin: 30px 0;">
                    <a href="{{reset_link}}" class="button">Réinitialiser mon mot de passe</a>
                </p>
                
                <div class="cm-alert cm-alert-warning">
                    <p class="text-warning">⚠️ Ce lien expirera dans <strong>1 heure</strong>.</p>
                    <p style="font-size: 13px; color: #b45309; margin-top: 4px;">Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet email en toute sécurité.</p>
                </div>
            '
        ],

        'REPORT_NOTIFICATION' => [
            'subject' => 'Notification de compte rendu de soutenance',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre rapport (<strong>« {{nom_rapport}} »</strong>) a été inclus dans le compte rendu <strong>« {{nom_CR}} »</strong> le {{date_CR}}.</p>
                
                <div class="cm-alert cm-alert-info">
                    <p class="text-info" style="margin: 0; font-size: 15px; font-weight: 500;">
                        📄 Vous trouverez en pièce jointe le compte rendu complet de la séance d\'évaluation au format PDF.
                    </p>
                </div>
                
                <p>Cordialement,<br>L\'équipe pédagogique</p>
            '
        ],

        'COMMISSION_NOTIFICATION' => [
            'subject' => 'Compte rendu de commission disponible : {{nom_CR}}',
            'body' => '
                <p>Bonjour,</p>
                <p>Le compte rendu <strong>{{nom_CR}}</strong> a été enregistré et est prêt à être consulté.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Informations du compte rendu</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Rapports concernés :</td>
                            <td class="info-value">{{nbRapports}} rapport(s)</td>
                        </tr>
                        <tr>
                            <td class="info-label">Référence interne :</td>
                            <td class="info-value"><code class="code-block">#{{id_CR}}</code></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous recevez ce message en tant que <strong>membre de la commission</strong>.</p>
                <p>Le PDF est joint à cet email.</p>
                
                <p>Cordialement,<br>L\'administration CheckMaster</p>
            '
        ],

        'RESPONSABLE_NOTIFICATION' => [
            'subject' => 'Compte rendu disponible : {{nom_CR}}',
            'body' => '
                <p>Bonjour,</p>
                <p>Le compte rendu <strong>{{nom_CR}}</strong> a été enregistré et est prêt à être consulté.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Informations du compte rendu</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Rapports concernés :</td>
                            <td class="info-value">{{nbRapports}} rapport(s)</td>
                        </tr>
                        <tr>
                            <td class="info-label">Référence interne :</td>
                            <td class="info-value"><code class="code-block">#{{id_CR}}</code></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous recevez ce message en tant que <strong>responsable concerné</strong>.</p>
                <p>Le PDF est joint à cet email.</p>
                
                <p>Cordialement,<br>L\'administration CheckMaster</p>
            '
        ],

        'CANDIDATURE_SOUMISE_ETUDIANT' => [
            'subject' => 'Confirmation de votre candidature à la soutenance',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre candidature à la soutenance a bien été soumise.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">✅ Détails de la candidature</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Référence :</td>
                            <td class="info-value"><code class="code-block">#{{id_candidature}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date de soumission :</td>
                            <td class="info-value">{{date_candidature}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Statut :</td>
                            <td class="info-value" style="color: #d97706;">En attente de traitement</td>
                        </tr>
                    </table>
                </div>
                
                <p>Un administrateur va examiner votre dossier. Vous recevrez un email dès que la décision sera prise.</p>
                <p>Cordialement,<br>L\'équipe Check Master</p>
            '
        ],

        'CANDIDATURE_SOUMISE_ADMIN' => [
            'subject' => 'Nouvelle candidature à la soutenance à traiter',
            'body' => '
                <p>Bonjour,</p>
                <p>Une nouvelle candidature à la soutenance a été soumise et attend votre traitement.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Informations de l\'étudiant</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Numéro étudiant :</td>
                            <td class="info-value"><code class="code-block">{{num_etu}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date de soumission :</td>
                            <td class="info-value">{{date_candidature}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Référence :</td>
                            <td class="info-value">#{{id_candidature}}</td>
                        </tr>
                    </table>
                </div>
                
                <p class="text-center" style="margin-top: 30px;">
                    <a href="{{admin_url}}" class="button">Traiter la candidature</a>
                </p>
            '
        ],

        'DEPOT_RAPPORT' => [
            'subject' => 'Dépôt de rapport - {{nom_rapport}}',
            'body' => '
                <p>Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>L\'étudiant <strong>{{nom_etudiant}}</strong> a déposé son rapport de stage.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">📄 Détails du document</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Rapport :</td>
                            <td class="info-value">{{nom_rapport}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme_rapport}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date de dépôt :</td>
                            <td class="info-value">{{date_depot}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Votre rôle :</td>
                            <td class="info-value">{{role}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous pouvez dès à présent consulter le rapport et préparer votre évaluation sur la plateforme.</p>
                <p>Cordialement,<br>Check Master</p>
            '
        ],

        'EVALUATION_RAPPORT_VALIDE' => [
            'subject' => 'Rapport validé - {{nom_rapport}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre rapport <strong>{{nom_rapport}}</strong> a été validé par la commission.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">✅ Décision : VALIDÉ</h4>
                    <table class="info-table" style="margin-bottom: 12px;">
                        <tr>
                            <td class="info-label">Rapport :</td>
                            <td class="info-value">{{nom_rapport}}</td>
                        </tr>
                    </table>
                    {{commentaires}}
                </div>
                
                <p>Vous pouvez maintenant procéder aux prochaines étapes de votre soutenance.</p>
                <p>Cordialement,<br>La commission de validation</p>
            '
        ],

        'EVALUATION_RAPPORT_REJETE' => [
            'subject' => 'Rapport à corriger - {{nom_rapport}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre rapport <strong>{{nom_rapport}}</strong> nécessite des corrections avant validation.</p>
                
                <div class="cm-alert cm-alert-danger">
                    <h4 class="cm-alert-title text-danger">❌ Décision : À CORRIGER</h4>
                    <table class="info-table" style="margin-bottom: 12px;">
                        <tr>
                            <td class="info-label">Rapport :</td>
                            <td class="info-value">{{nom_rapport}}</td>
                        </tr>
                    </table>
                    {{commentaires}}
                </div>
                
                <p>Veuillez apporter les corrections nécessaires et soumettre une nouvelle version sur la plateforme.</p>
                <p>Cordialement,<br>La commission de validation</p>
            '
        ],

        'AFFECTATION_ENCADRANT' => [
            'subject' => 'Attribution en tant qu\'encadrant pédagogique',
            'body' => '
                <p>Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Vous avez été attribué en tant qu\'<strong>encadrant pédagogique</strong> pour l\'étudiant suivant :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Informations de l\'étudiant</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Entreprise :</td>
                            <td class="info-value">{{entreprise}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous serez responsable du suivi pédagogique de cet étudiant tout au long de son stage.</p>
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'AFFECTATION_DIRECTEUR' => [
            'subject' => 'Attribution en tant que directeur de mémoire',
            'body' => '
                <p>Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Vous avez été attribué en tant que <strong>directeur de mémoire</strong> pour l\'étudiant suivant :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Informations de l\'étudiant</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Entreprise :</td>
                            <td class="info-value">{{entreprise}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous superviserez les travaux de recherche et la rédaction du mémoire de cet étudiant.</p>
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'AJOUT_JURY' => [
            'subject' => 'Assignation au jury de soutenance',
            'body' => '
                <p>Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Vous avez été désigné comme <strong>{{role}}</strong> pour la soutenance suivante :</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">📅 Détails de la soutenance</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date :</td>
                            <td class="info-value"><strong>{{date_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Heure :</td>
                            <td class="info-value"><strong>{{heure_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Salle :</td>
                            <td class="info-value">{{salle}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Merci de bien vouloir prendre connaissance du dossier sur la plateforme et vous tenir disponible à la date indiquée.</p>
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'RETRAIT_JURY' => [
            'subject' => 'Retrait du jury de soutenance',
            'body' => '
                <p>Bonjour <strong>{{nom_enseignant}}</strong>,</p>
                <p>Vous avez été retiré du jury de soutenance pour l\'étudiant suivant :</p>
                
                <div class="cm-alert cm-alert-warning">
                    <h4 class="cm-alert-title text-warning">⚠️ Changement de programmation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Votre ancien rôle :</td>
                            <td class="info-value">{{role}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Nous vous remercions pour votre disponibilité et vous excusons pour ce changement.</p>
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'SOUTENANCE_PROGRAMMEE' => [
            'subject' => 'Soutenance programmée - {{date_soutenance}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre soutenance a été programmée. Voici les détails :</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">📅 Programmation confirmée</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date :</td>
                            <td class="info-value"><strong>{{date_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Heure :</td>
                            <td class="info-value"><strong>{{heure_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Salle :</td>
                            <td class="info-value">{{salle}}</td>
                        </tr>
                    </table>
                </div>
                
                {{composition_jury}}
                
                <p>Merci de vous préparer en conséquence.</p>
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'SOUTENANCE_MODIFIEE' => [
            'subject' => 'Soutenance modifiée - {{nom_etudiant}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Les informations de la soutenance ont été modifiées. Voici les nouveaux détails :</p>
                
                <div class="cm-alert cm-alert-warning">
                    <h4 class="cm-alert-title text-warning">⚠️ Modification de la programmation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvelle date :</td>
                            <td class="info-value"><strong>{{date_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvelle heure :</td>
                            <td class="info-value"><strong>{{heure_soutenance}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvelle salle :</td>
                            <td class="info-value">{{salle}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Nous vous prions de bien vouloir prendre note de ces modifications.</p>
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'SOUTENANCE_ANNULEE' => [
            'subject' => 'Soutenance annulée - {{nom_etudiant}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>La soutenance prévue pour l\'étudiant <strong>{{nom_etudiant}}</strong> a été annulée.</p>
                
                <div class="cm-alert cm-alert-danger">
                    <h4 class="cm-alert-title text-danger">❌ Annulation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date initiale :</td>
                            <td class="info-value"><del>{{date_soutenance}}</del></td>
                        </tr>
                    </table>
                </div>
                
                <p>Une nouvelle programmation sera communiquée ultérieurement.</p>
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'DECISION_JURY_ETUDIANT' => [
            'subject' => 'Résultat de votre soutenance',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le jury a rendu sa décision concernant votre soutenance.</p>
                
                <div class="cm-alert" style="background-color: {{bg_color}}; border: 1px solid {{border_color}}; border-left: 4px solid {{text_color}};">
                    <h3 style="margin: 0; color: {{text_color}}; text-align: center;">Décision : {{decision}}</h3>
                </div>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">📊 Détails de l\'évaluation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Thème :</td>
                            <td class="info-value">{{theme}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Note :</td>
                            <td class="info-value"><strong>{{note}} / 20</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Mention :</td>
                            <td class="info-value"><strong>{{mention}}</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous pouvez consulter votre bulletin et le PV dans votre espace personnel.</p>
                <p>Cordialement,<br>Le jury de soutenance</p>
            '
        ],

        'DECISION_JURY_ENCADRANT' => [
            'subject' => 'Résultat de soutenance - {{nom_etudiant}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le résultat de la soutenance de l\'étudiant <strong>{{nom_etudiant}}</strong> est disponible.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">📊 Détails de l\'évaluation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Décision :</td>
                            <td class="info-value"><strong>{{decision}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Note :</td>
                            <td class="info-value"><strong>{{note}} / 20</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Mention :</td>
                            <td class="info-value"><strong>{{mention}}</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Cordialement,<br>L\'administration</p>
            '
        ],

        'BULLETIN_NOTES' => [
            'subject' => 'Notes disponibles - {{semestre}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Les notes pour le semestre <strong>{{semestre}}</strong> sont désormais disponibles.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">✅ Bulletin édité</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Semestre :</td>
                            <td class="info-value">{{semestre}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Moyenne :</td>
                            <td class="info-value"><strong>{{moyenne}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Crédits validés :</td>
                            <td class="info-value">{{credits}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>Connectez-vous à votre espace étudiant pour consulter le détail de vos notes et télécharger le bulletin.</p>
                <p>Cordialement,<br>La scolarité</p>
            '
        ],

        'INSCRIPTION_CONFIRMATION' => [
            'subject' => 'Confirmation d\'inscription - {{annee_academique}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre inscription pour l\'année académique <strong>{{annee_academique}}</strong> a bien été enregistrée.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Détails de la facturation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Niveau :</td>
                            <td class="info-value">{{niveau}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Montant total :</td>
                            <td class="info-value"><strong>{{montant_total}} FCFA</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Premier versement :</td>
                            <td class="info-value">{{montant_verse}} FCFA</td>
                        </tr>
                        <tr>
                            <td class="info-label">Solde restant :</td>
                            <td class="info-value text-danger"><strong>{{solde}} FCFA</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Merci de bien vouloir régulariser votre solde dans les délais impartis.</p>
                <p>Cordialement,<br>La scolarité</p>
            '
        ],

        'RECU_PAIEMENT' => [
            'subject' => 'Reçu de paiement - {{montant}} FCFA',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous vous confirmons la réception de votre paiement.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h4 class="cm-alert-title text-success">💳 Reçu de paiement</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Montant reçu :</td>
                            <td class="info-value text-success"><strong>{{montant}} FCFA</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date :</td>
                            <td class="info-value">{{date_paiement}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Mode de paiement :</td>
                            <td class="info-value">{{mode_paiement}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Solde restant :</td>
                            <td class="info-value"><strong>{{solde}} FCFA</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Cordialement,<br>La scolarité</p>
            '
        ],

        'INSCRIPTION_VALIDEE' => [
            'subject' => 'Inscription validée - {{annee_academique}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre inscription pour l\'année académique <strong>{{annee_academique}}</strong> est désormais validée.</p>
                
                <div class="cm-alert cm-alert-success">
                    <h3 style="margin: 0; text-align: center; color: #047857;">✅ Totalité des frais acquittés</h3>
                    <p style="margin: 8px 0 0; text-align: center; font-size: 16px;">Solde : <strong>0 FCFA</strong></p>
                </div>
                
                <p>Vous pouvez dès à présent accéder à l\'ensemble des services de la plateforme.</p>
                <p>Cordialement,<br>La scolarité</p>
            '
        ],

        'RELANCE_IMPAYE' => [
            'subject' => 'Rappel : solde d\'inscription en attente',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Nous vous rappelons qu\'il reste un solde impayé sur votre inscription pour l\'année académique <strong>{{annee_academique}}</strong>.</p>
                
                <div class="cm-alert cm-alert-warning">
                    <h4 class="cm-alert-title text-warning">⚠️ Solde restant dû</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Montant total :</td>
                            <td class="info-value">{{montant_total}} FCFA</td>
                        </tr>
                        <tr>
                            <td class="info-label">Total versé :</td>
                            <td class="info-value">{{montant_verse}} FCFA</td>
                        </tr>
                        <tr>
                            <td class="info-label">Solde impayé :</td>
                            <td class="info-value text-danger"><strong>{{solde}} FCFA</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Merci de bien vouloir procéder au règlement dans les meilleurs délais.</p>
                <p>Cordialement,<br>La scolarité</p>
            '
        ],

        'RECLAMATION_SOUMISE_ADMIN' => [
            'subject' => 'Nouvelle réclamation - {{objet}}',
            'body' => '
                <p>Bonjour,</p>
                <p>Une nouvelle réclamation a été soumise par un étudiant.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">🎫 Ticket de réclamation</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Étudiant :</td>
                            <td class="info-value">{{nom_etudiant}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Objet :</td>
                            <td class="info-value">{{objet}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Type :</td>
                            <td class="info-value">{{type}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Référence :</td>
                            <td class="info-value"><code class="code-block">REC-{{id}}</code></td>
                        </tr>
                    </table>
                </div>
                
                <p class="text-center" style="margin-top: 30px;">
                    <a href="{{admin_url}}" class="button">Traiter la réclamation</a>
                </p>
            '
        ],

        'RECLAMATION_STATUT' => [
            'subject' => 'Suivi de votre réclamation - {{statut}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le statut de votre réclamation a été mis à jour.</p>
                
                <div class="cm-alert" style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-left: 4px solid {{statut_couleur}};">
                    <table class="info-table" style="margin: 0;">
                        <tr>
                            <td class="info-label">Référence :</td>
                            <td class="info-value"><code class="code-block">REC-{{id}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Objet :</td>
                            <td class="info-value">{{objet}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouveau statut :</td>
                            <td class="info-value" style="color: {{statut_couleur}}; font-weight: bold;">{{statut}}</td>
                        </tr>
                    </table>
                </div>
                
                {{commentaire}}
                
                <p>Connectez-vous à votre espace pour suivre l\'évolution de votre dossier.</p>
                <p>Cordialement,<br>La scolarité</p>
            '
        ],

        'RECLAMATION_REPONSE' => [
            'subject' => 'Réponse à votre réclamation - {{objet}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Une réponse a été apportée à votre réclamation.</p>
                
                <div class="cm-alert cm-alert-info">
                    <table class="info-table" style="margin: 0 0 16px 0;">
                        <tr>
                            <td class="info-label" style="padding-top: 0;">Référence :</td>
                            <td class="info-value" style="padding-top: 0;"><code class="code-block">REC-{{id}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label" style="padding-bottom: 0;">Objet :</td>
                            <td class="info-value" style="padding-bottom: 0;">{{objet}}</td>
                        </tr>
                    </table>
                    
                    <h4 class="cm-alert-title text-info" style="margin-top: 8px;">Réponse de l\'administration :</h4>
                    <div style="background: #ffffff; padding: 16px; border-radius: 6px; border: 1px solid #bfdbfe; margin-top: 8px; color: #1e293b;">
                        {{reponse}}
                    </div>
                </div>
                
                <p>Si vous avez besoin d\'informations complémentaires, n\'hésitez pas à nous contacter.</p>
                <p>Cordialement,<br>La scolarité</p>
            '
        ],

        'MDP_CHANGE' => [
            'subject' => 'Confirmation de changement de mot de passe',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre mot de passe a été modifié avec succès.</p>
                
                <div class="cm-alert cm-alert-success text-center">
                    <h3 class="text-success" style="margin: 0;">✅ Mot de passe mis à jour</h3>
                    <p style="margin: 8px 0 0; font-size: 13px; color: #64748b;">Date de l\'opération : {{date_changement}}</p>
                </div>
                
                <div class="cm-alert cm-alert-warning" style="margin-top: 30px;">
                    <p class="text-warning">⚠️ <strong>Alerte de sécurité</strong></p>
                    <p style="font-size: 13px; margin-top: 4px;">Si vous n\'êtes pas à l\'origine de cette modification, contactez immédiatement l\'administration.</p>
                </div>
                
                <p>Cordialement,<br>L\'équipe Check Master</p>
            '
        ],

        'EMAIL_MODIFIE' => [
            'subject' => 'Votre adresse email a été modifiée',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre adresse email de contact a été modifiée sur votre espace.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Détails de la modification</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Ancienne adresse :</td>
                            <td class="info-value">{{ancien_email}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Nouvelle adresse :</td>
                            <td class="info-value"><strong>{{nouvel_email}}</strong></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date :</td>
                            <td class="info-value">{{date_modification}}</td>
                        </tr>
                    </table>
                </div>
                
                <div class="cm-alert cm-alert-warning" style="margin-top: 30px;">
                    <p class="text-warning">⚠️ <strong>Alerte de sécurité</strong></p>
                    <p style="font-size: 13px; margin-top: 4px;">Si vous n\'êtes pas à l\'origine de cette modification, contactez immédiatement l\'administration.</p>
                </div>
                
                <p>Cordialement,<br>L\'équipe Check Master</p>
            '
        ],

        'COMPTE_VERROUILLE' => [
            'subject' => 'Alerte de sécurité - Compte verrouillé',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre compte a été temporairement verrouillé en raison de trop nombreuses tentatives de connexion échouées.</p>
                
                <div class="cm-alert cm-alert-danger">
                    <h4 class="cm-alert-title text-danger">❌ Compte bloqué</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Identifiant :</td>
                            <td class="info-value"><code class="code-block">{{login}}</code></td>
                        </tr>
                        <tr>
                            <td class="info-label">Date et heure :</td>
                            <td class="info-value">{{date_verrouillage}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Durée du blocage :</td>
                            <td class="info-value"><strong>{{duree}} minutes</strong></td>
                        </tr>
                    </table>
                </div>
                
                <p>Vous pourrez réessayer de vous connecter après le délai indiqué. Si vous avez oublié votre mot de passe, utilisez la fonction "Mot de passe oublié" sur la page de connexion.</p>
                <p>Cordialement,<br>L\'équipe de sécurité Check Master</p>
            '
        ],

        'CR_MODIFIE' => [
            'subject' => 'Compte rendu mis à jour - {{nom_CR}}',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>Le compte rendu de soutenance <strong>{{nom_CR}}</strong> a été modifié.</p>
                
                <div class="cm-alert cm-alert-info">
                    <h4 class="cm-alert-title text-info">ℹ️ Mise à jour de document</h4>
                    <table class="info-table">
                        <tr>
                            <td class="info-label">Rapport concerné :</td>
                            <td class="info-value">{{nom_rapport}}</td>
                        </tr>
                        <tr>
                            <td class="info-label">Date de mise à jour :</td>
                            <td class="info-value">{{date_maj}}</td>
                        </tr>
                    </table>
                </div>
                
                <p>La version mise à jour du compte rendu au format PDF est jointe à cet email et reste disponible dans votre espace personnel.</p>
                <p>Cordialement,<br>La commission d\'évaluation</p>
            '
        ],
    ]
];

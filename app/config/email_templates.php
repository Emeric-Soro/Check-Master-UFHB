<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Global Email Layout
    |--------------------------------------------------------------------------
    | This layout wraps all email bodies.
    | Use {{body}} as the placeholder for the template content.
    |
    */
    'LAYOUT' => '
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{subject}}</title>
    <style>
        /* Styles en ligne privilégiés, mais le style bloc aide certains clients */
        body {
            font-family: "Inter", -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            line-height: 1.6;
            color: #334155;
            background-color: #f4f7f9;
            margin: 0;
            padding: 0;
            -webkit-font-smoothing: antialiased;
        }
        .wrapper {
            width: 100%;
            background-color: #f4f7f9;
            padding: 40px 10px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.05);
            overflow: hidden;
            border: 1px solid #e2e8f0;
        }
        .header {
            background-color: #ffffff;
            padding: 35px 40px 25px;
            text-align: center;
        }
        .header img {
            max-height: 55px;
            display: block;
            margin: 0 auto;
        }
        .header h1 {
            color: #1e293b;
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .content {
            padding: 10px 40px 40px;
            font-size: 15px;
        }
        .content p {
            margin: 0 0 16px 0;
        }
        .footer {
            background-color: #f8fafc;
            padding: 25px 40px;
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
            padding: 14px 32px;
            background-color: #0f172a; /* Bleu foncé CM */
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 15px;
            margin: 25px 0;
            text-align: center;
            box-shadow: 0 4px 6px -1px rgba(15, 23, 42, 0.2);
            transition: all 0.2s;
        }
        .button:hover {
            background-color: #1e293b;
            box-shadow: 0 6px 8px -1px rgba(15, 23, 42, 0.3);
        }
        .box {
            background-color: #f8fafc;
            padding: 24px;
            border-radius: 10px;
            margin: 25px 0;
            border: 1px solid #e2e8f0;
        }
        .password-box {
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, Courier, monospace;
            font-size: 18px;
            background-color: #e2e8f0;
            padding: 8px 16px;
            border-radius: 6px;
            display: inline-block;
            letter-spacing: 2px;
            color: #0f172a;
            font-weight: bold;
            border: 1px dashed #cbd5e1;
        }
        /* Utilitaires */
        .text-center { text-align: center; }
        .greeting { font-size: 18px; color: #0f172a; font-weight: 600; margin-bottom: 20px; }
        .divider { height: 1px; background-color: #e2e8f0; margin: 25px 0; }
    </style>
</head>
<body>
    <div class="wrapper">
        <div class="container">
            <div class="header">
                <!-- Logo intégré via CID (Embedded Image) pour garantie daffichage -->
                <img src="{{logo_src}}" alt="Check Master Logo">
                <h1 style="display: none;">{{subject}}</h1>
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

    /*
    |--------------------------------------------------------------------------
    | Email Templates
    |--------------------------------------------------------------------------
    */
    'TEMPLATES' => [

        'USER_WELCOME' => [
            'subject' => 'Bienvenue sur Check Master',
            'body' => '
                <p class="greeting">Bonjour <strong>{{nom}}</strong>,</p>
                <p>Votre compte a ete cree sur la plateforme <strong>Check Master</strong>.</p>
                <p>Vous pouvez des a present vous connecter avec les informations ci-dessous :</p>

                <div class="box">
                    <table style="width: 100%; border-collapse: collapse;">
                        <tr>
                            <td style="padding-bottom: 10px; color: #64748b; width: 180px;">Identifiant :</td>
                            <td style="padding-bottom: 10px;"><strong>{{login}}</strong></td>
                        </tr>
                        {{password_row}}
                    </table>

                    {{reset_password_section}}
                </div>

                <p class="text-center">
                    <a href="{{login_url}}" class="button">Acceder a la plateforme</a>
                </p>

                <p style="font-size: 14px; color: #64748b;">
                    Si vous ne parvenez pas a cliquer sur le bouton, copiez ce lien dans votre navigateur :
                    <br>
                    <a href="{{login_url}}" style="color: #0f172a; word-break: break-all;">{{login_url}}</a>
                </p>
            '
        ],

        'CANDIDATURE_RESULT' => [
            'subject' => 'Résultat de votre candidature à la soutenance',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                <p>L\'évaluation de votre candidature à la soutenance est terminée.</p>
                
                <div class="box text-center" style="background-color: {{status_bg_color}}; border: 1px solid {{status_border_color}};">
                    <h2 style="margin: 0; color: {{status_text_color}};">{{status_icon}} Décision finale : {{decision}}</h2>
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
                
                <p class="text-center">
                    <a href="{{reset_link}}" class="button">Réinitialiser mon mot de passe</a>
                </p>
                
                <p style="font-size: 14px; color: #64748b; margin-top: 30px;">
                    Ce lien expirera dans <strong>1 heure</strong>. Si vous n\'êtes pas à l\'origine de cette demande, vous pouvez ignorer cet email en toute sécurité.
                </p>
            '
        ],

        'REPORT_NOTIFICATION' => [
            'subject' => 'Notification de compte rendu de soutenance',
            'body' => '
                <p>Bonjour <strong>{{nom}}</strong>,</p>
                
                <p>Votre rapport (<strong>« {{nom_rapport}} »</strong>) a été inclus dans le compte rendu <strong>« {{nom_CR}} »</strong> le {{date_CR}}.</p>
                
                <div class="box text-center">
                    <p style="margin: 0; font-size: 16px;">
                        📄 Vous trouverez en pièce jointe le compte rendu complet de la séance d\'évaluation au format PDF.
                    </p>
                </div>
                
                <p>Cordialement,<br>L\'équipe pédagogique</p>
            '
        ]
    ]
];

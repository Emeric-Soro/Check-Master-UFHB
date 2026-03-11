<?php
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    private $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer()
    {
        try {
            // Charger la configuration SMTP
            $config = require __DIR__ . '/../config/email.php';

            // Configuration du serveur SMTP
            $this->mailer->isSMTP();
            $this->mailer->Host = $config['smtp']['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $config['smtp']['username'];
            $this->mailer->Password = $config['smtp']['password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $config['smtp']['port'];
            $this->mailer->CharSet = 'UTF-8';

            // Configuration de l'expéditeur
            $this->mailer->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);

        } catch (Exception $e) {
            // Ne pas logger de secrets
            error_log("Erreur de configuration PHPMailer.");
        }
    }

    public function sendEmail($to, $subject, $message, $isHTML = false)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;

            if ($isHTML) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $message;
                $this->mailer->AltBody = strip_tags($message);
            } else {
                $this->mailer->Body = $message;
            }

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email.");
            return false;
        }
    }

    public function sendEmailWithAttachment($to, $subject, $message, $attachmentPath, $attachmentName = null, $isHTML = false)
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;

            if ($isHTML) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $message;
                $this->mailer->AltBody = strip_tags($message);
            } else {
                $this->mailer->Body = $message;
            }

            // Ajouter la pièce jointe
            if (file_exists($attachmentPath)) {
                $this->mailer->addAttachment($attachmentPath, $attachmentName);
            } else {
                error_log("Fichier pièce jointe non trouvé: " . $attachmentPath);
            }

            return $this->mailer->send();
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email avec pièce jointe.");
            return false;
        }
    }

    public function sendTemplate($templateKey, $to, $data = [], $attachments = [])
    {
        $templatesConfig = require __DIR__ . '/../config/email_templates.php';

        if (!isset($templatesConfig['TEMPLATES'][$templateKey])) {
            error_log("Template email non trouvé: " . $templateKey);
            return false;
        }

        $template = $templatesConfig['TEMPLATES'][$templateKey];
        $layout = $templatesConfig['LAYOUT'];

        // Injecter les variables dans le sujet
        $subject = $template['subject'];
        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', $value, $subject);
        }

        // Injecter les variables dans le corps
        $body = $template['body'];
        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', $value, $body);
        }

        // Insérer le corps dans le layout (et le sujet pour la balise <title>)
        $htmlMessage = str_replace(['{{body}}', '{{subject}}'], [$body, $subject], $layout);

        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $this->mailer->isHTML(true);

            // Gestion du logo incrusté (CID) pour affichage garanti
            $logoPath = __DIR__ . '/../../public/image/logo_simple_cm.png';
            if (file_exists($logoPath)) {
                $this->mailer->addEmbeddedImage($logoPath, 'logo_cm');
                $htmlMessage = str_replace('{{logo_src}}', 'cid:logo_cm', $htmlMessage);
            } else {
                // Fallback basique
                $htmlMessage = str_replace('{{logo_src}}', '', $htmlMessage);
            }

            $this->mailer->Body = $htmlMessage;
            
            // Un peu de formatage pour la version texte
            $altBody = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</tr>'], "\n", $htmlMessage);
            $altBody = strip_tags($altBody);
            $this->mailer->AltBody = trim($altBody);

            // Déterminer s'il y a des pièces jointes
            if (!empty($attachments)) {
                $attachment = is_array($attachments) && isset($attachments['path']) ? $attachments : $attachments[0];
                if (file_exists($attachment['path'])) {
                    $this->mailer->addAttachment($attachment['path'], $attachment['name'] ?? null);
                } else {
                    error_log("Fichier pièce jointe non trouvé: " . $attachment['path']);
                }
            }

            return $this->mailer->send();
        } catch (\Exception $e) {
            error_log("Erreur d'envoi d'email template: " . $e->getMessage());
            return false;
        }
    }
    public function sendResultEmail($studentEmail, $studentName, $resume, $decision)
    {
        $statusColor = ($decision === 'Validée') ? '#10b981' : '#ef4444';
        $statusIcon = ($decision === 'Validée') ? '🎉' : '❌';
        $statusBgColor = ($decision === 'Validée') ? '#f0fdf4' : '#fef2f2';
        $statusBorderColor = ($decision === 'Validée') ? '#bbf7d0' : '#fecaca';
        
        $details_html = '';
        foreach ($resume as $etape => $data) {
            $etapeName = ucfirst($etape);
            $validation = $data['validation'];
            $badgeColor = ($validation === 'validé') ? '#10b981' : '#ef4444';

            $details_html .= "
                <div class='box' style='border-left: 4px solid {$badgeColor};'>
                    <h4 style='margin-top: 0;'>{$etapeName}</h4>
                    <p><strong>Validation :</strong> <span style='background-color: {$badgeColor}; color: white; padding: 3px 8px; border-radius: 4px; font-size: 12px; font-weight: bold;'>" . strtoupper($validation) . "</span></p>
            ";

            if ($etape === 'scolarite') {
                $details_html .= "<p style='margin-bottom: 5px;'><strong>Statut :</strong> {$data['statut']}</p>";
                $details_html .= "<p style='margin-bottom: 5px;'><strong>Montant total :</strong> {$data['montant_total']}</p>";
                $details_html .= "<p style='margin-bottom: 0;'><strong>Montant payé :</strong> {$data['montant_paye']}</p>";
            } elseif ($etape === 'stage') {
                $details_html .= "<p style='margin-bottom: 5px;'><strong>Entreprise :</strong> {$data['entreprise']}</p>";
                $details_html .= "<p style='margin-bottom: 5px;'><strong>Sujet :</strong> {$data['sujet']}</p>";
                $details_html .= "<p style='margin-bottom: 0;'><strong>Période :</strong> {$data['periode']}</p>";
            } elseif ($etape === 'semestre') {
                $details_html .= "<p style='margin-bottom: 5px;'><strong>Semestre :</strong> {$data['semestre']}</p>";
                $details_html .= "<p style='margin-bottom: 5px;'><strong>Moyenne :</strong> {$data['moyenne']}</p>";
                $details_html .= "<p style='margin-bottom: 0;'><strong>Unités validées :</strong> {$data['unites']}</p>";
            }
            $details_html .= "</div>";
        }

        $action_message = '';
        if ($decision === 'Validée') {
            $action_message = '
                <div class="box text-center" style="background-color: #f0fdf4; border: 1px dashed #10b981;">
                    <p style="margin: 0; color: #065f46;"><strong>Félicitations !</strong> Votre candidature a été validée. Vous pouvez maintenant procéder à la rédaction de votre rapport.</p>
                </div>
            ';
        } else {
            $action_message = '
                <div class="box text-center" style="background-color: #fef2f2; border: 1px dashed #ef4444;">
                    <p style="margin-bottom: 10px; color: #991b1b;"><strong>Votre candidature a été rejetée.</strong></p>
                    <p style="margin: 0; font-size: 14px;">Veuillez corriger les problèmes identifiés et soumettre une nouvelle candidature.</p>
                </div>
            ';
        }

        $templateData = [
            'nom' => htmlspecialchars($studentName),
            'decision' => htmlspecialchars($decision),
            'status_icon' => $statusIcon,
            'status_text_color' => $statusColor,
            'status_bg_color' => $statusBgColor,
            'status_border_color' => $statusBorderColor,
            'details_html' => $details_html,
            'action_message' => $action_message
        ];

        return $this->sendTemplate('CANDIDATURE_RESULT', $studentEmail, $templateData);
    }
}
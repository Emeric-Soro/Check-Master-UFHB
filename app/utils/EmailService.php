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

    private function configureMailer(): void
    {
        try {
            $config = require __DIR__ . '/../config/email.php';

            $this->mailer->isSMTP();
            $this->mailer->Host = $config['smtp']['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $config['smtp']['username'];
            $this->mailer->Password = $config['smtp']['password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $config['smtp']['port'];
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);

        } catch (Exception $e) {
            error_log('[EmailService] configureMailer failed.');
        }
    }

    public function sendEmail($to, $subject, $message, $isHTML = false): bool
    {
        return $this->doSend($to, $subject, function() use ($message, $isHTML) {
            if ($isHTML) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $message;
                $this->mailer->AltBody = strip_tags($message);
            } else {
                $this->mailer->Body = $message;
            }
        });
    }

    public function sendEmailWithAttachment($to, $subject, $message, $attachmentPath, $attachmentName = null, $isHTML = false): bool
    {
        return $this->doSend($to, $subject, function() use ($message, $isHTML, $attachmentPath, $attachmentName) {
            if ($isHTML) {
                $this->mailer->isHTML(true);
                $this->mailer->Body = $message;
                $this->mailer->AltBody = strip_tags($message);
            } else {
                $this->mailer->Body = $message;
            }
            if (file_exists($attachmentPath)) {
                $this->mailer->addAttachment($attachmentPath, $attachmentName);
            }
        });
    }

    public function sendTemplate(string $templateKey, string $to, array $data = [], $attachments = []): bool
    {
        $templatesConfig = require __DIR__ . '/../config/email_templates.php';

        if (!isset($templatesConfig['TEMPLATES'][$templateKey])) {
            error_log('[EmailService] Template not found: ' . $templateKey);
            return false;
        }

        $template = $templatesConfig['TEMPLATES'][$templateKey];
        $layout = $templatesConfig['LAYOUT'];

        // Interpoler le sujet
        $subject = $template['subject'];
        foreach ($data as $key => $value) {
            $subject = str_replace('{{' . $key . '}}', (string) $value, $subject);
        }

        // Interpoler le corps
        $body = $template['body'];
        foreach ($data as $key => $value) {
            $body = str_replace('{{' . $key . '}}', (string) $value, $body);
        }

        // Layout + logo incrusté
        $htmlMessage = str_replace(['{{body}}', '{{subject}}'], [$body, $subject], $layout);

        $logoPath = __DIR__ . '/../../public/image/logo_simple_cm.png';
        $hasEmbeddedLogo = file_exists($logoPath);
        $htmlMessage = str_replace('{{logo_src}}', $hasEmbeddedLogo ? 'cid:logo_cm' : '', $htmlMessage);

        return $this->doSend($to, $subject, function() use ($htmlMessage, $attachments, $hasEmbeddedLogo, $logoPath, $subject) {
            $this->mailer->isHTML(true);
            $this->mailer->Body = $htmlMessage;

            // Correction AltBody : On s'assure qu'il commence bien par le sujet et on enlève les styles.
            // On strip les tags de htmlMessage direct, mais les styles css risquent de rester dans le texte.
            // Une meilleure approche est d'utiliser le body interpolé pur, sans le layout.
            // Cependant, on n'a pas accès au $body ici directement dans la closure si on ne le passe pas.
            // On peut au moins s'assurer que le altBody est propre.
            $altBody = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', "", $htmlMessage);
            $altBody = str_ireplace(['<br>', '<br/>', '<br />', '</p>', '</tr>'], "\n", $altBody);
            $altBody = strip_tags($altBody);
            
            // Pour être sûr que le AltBody commence par quelque chose de propre et non "Check Master .email-body {..."
            $this->mailer->AltBody = $subject . "\n\n" . trim($altBody);

            if ($hasEmbeddedLogo) {
                $this->mailer->addEmbeddedImage($logoPath, 'logo_cm');
            }

            if (!empty($attachments)) {
                $attachment = is_array($attachments) && isset($attachments['path']) ? $attachments : $attachments[0];
                if (file_exists($attachment['path'])) {
                    $this->mailer->addAttachment($attachment['path'], $attachment['name'] ?? null);
                }
            }
        });
    }

    private function doSend(string $to, string $subject, callable $sendBlock): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            $this->mailer->addAddress($to);
            $this->mailer->Subject = $subject;
            $sendBlock();
            return $this->mailer->send();
        } catch (Exception $e) {
            error_log('[EmailService] send failed: ' . $e->getMessage());
            return false;
        }
    }

    public function getMailer(): PHPMailer
    {
        return $this->mailer;
    }
}

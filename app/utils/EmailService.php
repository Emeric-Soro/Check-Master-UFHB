<?php
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\PHPMailer;

class EmailService
{
    private $mailer;
    private array $config = [];

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);
        $this->configureMailer();
    }

    private function configureMailer(): void
    {
        try {
            $config = require __DIR__ . '/../config/email.php';
            $this->config = is_array($config) ? $config : [];

            $this->mailer->isSMTP();
            $this->mailer->Host = $config['smtp']['host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $config['smtp']['username'];
            $this->mailer->Password = $config['smtp']['password'];
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $config['smtp']['port'];
            // Une panne réseau SMTP ne doit jamais monopoliser la requête PHP.
            $smtpTimeout = (int) ($config['smtp']['timeout'] ?? 15);
            $this->mailer->Timeout = max(5, min($smtpTimeout, 60));
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->setFrom($config['smtp']['from_email'], $config['smtp']['from_name']);

        } catch (Exception $e) {
            error_log('[EmailService] configureMailer failed.');
        }
    }

    private function getRedirectEmail(): ?string
    {
        $delivery = is_array($this->config['delivery'] ?? null) ? $this->config['delivery'] : [];
        $enabled = (bool) ($delivery['redirect_all'] ?? false);
        $redirectTo = trim((string) ($delivery['redirect_to'] ?? ''));

        if (!$enabled || $redirectTo === '' || filter_var($redirectTo, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $redirectTo;
    }

    private function getRedirectSubject(string $subject, string $originalTo): string
    {
        $delivery = is_array($this->config['delivery'] ?? null) ? $this->config['delivery'] : [];
        $prefix = (string) ($delivery['subject_prefix'] ?? '[TEST] ');
        $originalTo = preg_replace('/[\r\n]+/', ' ', $originalTo) ?: $originalTo;
        return $prefix . $subject . ' [destinataire reel: ' . $originalTo . ']';
    }

    private function appendRedirectNotice(string $originalTo): void
    {
        $noticeText = "Mode test Check Master: email redirige. Destinataire reel: {$originalTo}.";
        $noticeHtml = '<div style="margin:0 0 16px;padding:10px 12px;border:1px solid #f59e0b;background:#fffbeb;color:#92400e;font-family:Arial,sans-serif;font-size:13px;">'
            . htmlspecialchars($noticeText, ENT_QUOTES, 'UTF-8')
            . '</div>';

        if (stripos((string) $this->mailer->ContentType, 'text/html') !== false) {
            $this->mailer->Body = $noticeHtml . $this->mailer->Body;
        } else {
            $this->mailer->Body = $noticeText . "\n\n" . $this->mailer->Body;
        }

        $this->mailer->AltBody = trim($noticeText . "\n\n" . (string) $this->mailer->AltBody);
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
            $originalTo = trim($to);
            $redirectTo = $this->getRedirectEmail();
            $recipient = $redirectTo ?? $originalTo;

            $this->mailer->clearAddresses();
            $this->mailer->clearCCs();
            $this->mailer->clearBCCs();
            $this->mailer->clearAttachments();
            $this->mailer->isHTML(false);
            if (method_exists($this->mailer, 'clearCustomHeaders')) {
                $this->mailer->clearCustomHeaders();
            }

            $this->mailer->addAddress($recipient);
            $this->mailer->Subject = $redirectTo !== null ? $this->getRedirectSubject($subject, $originalTo) : $subject;
            $sendBlock();
            if ($redirectTo !== null) {
                $this->appendRedirectNotice($originalTo);
                $headerOriginalTo = preg_replace('/[\r\n]+/', ' ', $originalTo) ?: $originalTo;
                $this->mailer->addCustomHeader('X-CheckMaster-Original-To', $headerOriginalTo);
            }
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

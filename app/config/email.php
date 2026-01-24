<?php
// Configuration SMTP pour PHPMailer
// Sécurité: le mot de passe ne doit pas être en dur dans le repo.

require_once __DIR__ . '/database.php';

$cfg = [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'checkmaster.ci@gmail.com',
        'password' => '', // à fournir via table app_settings (clé: smtp_password)
        'from_email' => 'checkmaster.ci@gmail.com',
        'from_name' => 'Check Master',
    ],
];

// Override DB-only si la table existe
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'smtp_%'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $k = $row['setting_key'] ?? '';
        $v = $row['setting_value'] ?? '';
        if (!is_string($k) || !is_string($v)) {
            continue;
        }
        switch ($k) {
            case 'smtp_host':
                $cfg['smtp']['host'] = $v;
                break;
            case 'smtp_port':
                $cfg['smtp']['port'] = (int) $v;
                break;
            case 'smtp_username':
                $cfg['smtp']['username'] = $v;
                break;
            case 'smtp_password':
                $cfg['smtp']['password'] = $v;
                break;
            case 'smtp_from_email':
                $cfg['smtp']['from_email'] = $v;
                break;
            case 'smtp_from_name':
                $cfg['smtp']['from_name'] = $v;
                break;
        }
    }
} catch (Throwable $e) {
    // table absente ou DB indispo: fallback sur config statique (sans mot de passe)
}

return $cfg;
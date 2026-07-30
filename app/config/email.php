<?php

/**
 * Configuration SMTP pour l'envoi d'emails.
 * Les valeurs sont lues depuis AppConfig (fichier .env).
 * Override possible via la table app_settings en base de données.
 */

$cfg = [
    'smtp' => \CheckMaster\Core\AppConfig::smtpConfig(),
    'delivery' => \CheckMaster\Core\AppConfig::emailDelivery(),
];

// Override depuis la table app_settings si elle existe
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM app_settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'email_%'");
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $mapping = [
        'smtp_host'          => ['smtp', 'host'],
        'smtp_port'          => ['smtp', 'port', 'int'],
        'smtp_username'      => ['smtp', 'username'],
        'smtp_password'      => ['smtp', 'password'],
        'smtp_from_email'    => ['smtp', 'from_email'],
        'smtp_from_name'     => ['smtp', 'from_name'],
        'email_redirect_all' => ['delivery', 'redirect_all', 'bool'],
        'email_redirect_to'  => ['delivery', 'redirect_to'],
        'email_subject_prefix' => ['delivery', 'subject_prefix'],
    ];

    foreach ($rows as $row) {
        $k = (string) ($row['setting_key'] ?? '');
        $v = (string) ($row['setting_value'] ?? '');

        if (isset($mapping[$k])) {
            $section = $mapping[$k][0];
            $key = $mapping[$k][1];
            $type = $mapping[$k][2] ?? 'string';

            $cfg[$section][$key] = match ($type) {
                'int'  => (int) $v,
                'bool' => filter_var($v, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $cfg[$section][$key],
                default => $v,
            };
        }
    }
} catch (\Throwable $e) {
    // Table absente ou DB indisponible : fallback sur config .env
}

return $cfg;

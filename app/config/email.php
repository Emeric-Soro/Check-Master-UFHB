<?php
// Configuration SMTP pour PHPMailer
// Use environment variables for sensitive data
$smtp_password = getenv('SMTP_PASSWORD');
if ($smtp_password === false) {
    // In production, throw an error if password is not set
    if (getenv('APP_ENV') === 'production') {
        throw new Exception('SMTP_PASSWORD environment variable must be set in production');
    }
    $smtp_password = 'nyuy mywe kghx mdek'; // Development fallback
}

return [
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
        'port' => getenv('SMTP_PORT') ?: 587,
        'encryption' => 'tls',
        'username' => getenv('SMTP_USERNAME') ?: 'checkmaster.ci@gmail.com',
        'password' => $smtp_password,
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'checkmaster.ci@gmail.com',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Check Master'
    ]
]; 
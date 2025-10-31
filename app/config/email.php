<?php
// Configuration SMTP pour PHPMailer
// Use environment variables for sensitive data
return [
    'smtp' => [
        'host' => getenv('SMTP_HOST') ?: 'smtp.gmail.com',
        'port' => getenv('SMTP_PORT') ?: 587,
        'encryption' => 'tls',
        'username' => getenv('SMTP_USERNAME') ?: 'checkmaster.ci@gmail.com',
        'password' => getenv('SMTP_PASSWORD') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'checkmaster.ci@gmail.com',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'Check Master'
    ]
]; 
<?php
// Configuration SMTP pour PHPMailer (sans Docker)
return [
    'smtp' => [
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'encryption' => 'tls',
        'username' => 'checkmaster.ci@gmail.com',
        'password' => 'nyuy mywe kghx mdek',
        'from_email' => 'checkmaster.ci@gmail.com',
        'from_name' => 'Check Master'
    ]
]; 
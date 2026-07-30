<?php

/**
 * Configuration centralisée de l'application.
 * Toutes les valeurs sont définies ici — AUCUN fichier .env.
 * Pour modifier une valeur, éditez directement ce fichier.
 */
return [

    // ─── Base de données ─────────────────────────────────
    'db' => [
        'host'     => 'localhost',
        'name'     => 'ufrmi1802974_2q2mpf',
        'user'     => 'root',
        'pass'     => '',
        'charset'  => 'utf8',
        'timezone' => '+00:00',
    ],

    // ─── Application ─────────────────────────────────────
    'app' => [
        'url'       => '',
        'base_path' => '/',
        'name'      => 'CheckMaster',
        'env'       => 'production',
        'debug'     => false,
    ],

    // ─── SMTP ────────────────────────────────────────────
    'smtp' => [
        'host'       => 'smtp.gmail.com',
        'port'       => 587,
        'encryption' => 'tls',
        'username'   => 'checkmaster.ci@gmail.com',
        'password'   => '',
        'from_email' => 'checkmaster.ci@gmail.com',
        'from_name'  => 'Check Master',
    ],

    // ─── Email Delivery ──────────────────────────────────
    'email' => [
        'redirect_all'   => true,
        'redirect_to'    => 'checkmaster.ci@gmail.com',
        'subject_prefix' => '',
    ],

    // ─── Session ─────────────────────────────────────────
    'session' => [
        'lifetime' => 0,
        'samesite' => 'Lax',
    ],

    // ─── Sécurité ────────────────────────────────────────
    'security' => [
        'rate_limit_max'    => 5,
        'rate_limit_window' => 900,
        'rate_limit_block'  => 900,
    ],
];

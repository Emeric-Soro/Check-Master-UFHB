<?php
// Site vitrine (UFHB) sous /public/site/
ob_start();
require __DIR__ . '/../../index.php';
$html = (string) ob_get_clean();

$replacements = [
    // assets (public/image/* -> ../image/*)
    'href="public/' => 'href="../',
    "href='public/" => "href='../",
    'src="public/' => 'src="../',
    "src='public/" => "src='../",

    // navigation
    'href="public/index.php"' => 'href="index.php"',
    "href='public/index.php'" => "href='index.php'",
    'href="public/index.php?_path=/login"' => 'href="../app/index.php?_path=/login"',
    "href='public/index.php?_path=/login'" => "href='../app/index.php?_path=/login'",
];

echo strtr($html, $replacements);


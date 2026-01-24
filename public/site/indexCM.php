<?php
// Site vitrine (CheckMaster) sous /public/site/
ob_start();
require __DIR__ . '/../indexCM.php';
$html = (string) ob_get_clean();

$replacements = [
    // assets (image/* -> ../image/*)
    'href="image/' => 'href="../image/',
    "href='image/" => "href='../image/",
    'src="image/' => 'src="../image/',
    "src='image/" => "src='../image/",

    // login vers application
    'href="index.php?_path=/login"' => 'href="../app/index.php?_path=/login"',
    "href='index.php?_path=/login'" => "href='../app/index.php?_path=/login'",
];

echo strtr($html, $replacements);


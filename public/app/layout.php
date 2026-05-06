<?php
/**
 * Passerelle "application" sous /public/app/
 *
 * Objectif: garder le layout legacy COMPLET (public/layout.php) sans le casser,
 * tout en servant la page depuis /public/app/layout.php avec des chemins d'assets corrects.
 */

// IMPORTANT: pour les exports (PDF/XLSX), ne pas bufferiser/réécrire la sortie,
// sinon le binaire est corrompu.
if (!empty($_GET['_export'])) {
    require_once __DIR__ . '/../layout.php';
    exit;
}

ob_start();
require_once __DIR__ . '/../layout.php';
$html = (string) ob_get_clean();

// Réécriture des chemins d'assets pour fonctionner sous /public/app/
$replacements = [
    'href="css/' => 'href="../css/',
    "href='css/" => "href='../css/",
    'href="assets/' => 'href="../assets/',
    "href='assets/" => "href='../assets/",
    'href="image/' => 'href="../image/',
    "href='image/" => "href='../image/",
    'src="image/' => 'src="../image/',
    "src='image/" => "src='../image/",
    'src="assets/' => 'src="../assets/',
    "src='assets/" => "src='../assets/",
    'href="./images/' => 'href="../images/',
    "href='./images/" => "href='../images/",
    'src="./images/' => 'src="../images/',
    "src='./images/" => "src='../images/",
    'href="images/' => 'href="../images/',
    "href='images/" => "href='../images/",
    'src="images/' => 'src="../images/',
    "src='images/" => "src='../images/",
    'src="./js/' => 'src="../js/',
    "src='./js/" => "src='../js/",
    'src="js/' => 'src="../js/',
    "src='js/" => "src='../js/",
];

echo strtr($html, $replacements);

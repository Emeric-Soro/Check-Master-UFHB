<?php
/**
 * Passerelle "application" sous /public/app/
 *
 * Objectif: garder le layout legacy COMPLET (public/layout.php) sans le casser,
 * tout en servant la page depuis /public/app/layout.php avec des chemins d'assets corrects.
 */

ob_start();
require __DIR__ . '/../layout.php';
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

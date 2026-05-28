<?php
/**
 * P2.10 — Portfolio Enseignant
 * Alias vers P1.2 (Fiche Enseignante)
 *
 * Simple redirection vers la page fiche_enseignante.
 */
$redirectUrl = '?page=fiche_enseignante';
header('Location: ' . $redirectUrl);
exit;

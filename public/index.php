<?php
// Passerelle: l'application est désormais sous /public/app/
header('Location: app/index.php' . (isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : ''));
exit;

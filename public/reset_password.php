<?php
// Passerelle: reset password déplacé vers /public/app/
$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== '' ? ('?' . $_SERVER['QUERY_STRING']) : '';
header('Location: app/reset_password.php' . $qs);
exit;


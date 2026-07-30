<?php
// Endpoint legacy: délègue vers le nouveau Router.
$_GET['_path'] = $_GET['_path'] ?? '/logout';
require __DIR__ . '/index.php';
exit;

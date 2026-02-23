<?php
$token = '';
if (class_exists('\CheckMaster\Core\Csrf')) {
    $token = (string) \CheckMaster\Core\Csrf::token();
}
if ($token === '') {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        @session_start();
    }
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    $token = (string) $_SESSION['csrf_token'];
}
?>
<input type="hidden" name="csrf_token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">

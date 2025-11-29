<?php
session_start();
require_once __DIR__.'/../app/config/database.php';
require_once __DIR__.'/../app/controllers/AuthController.php';

$authController = new AuthController(Database::getConnection());
$authController->logout();
// The logout method now handles redirection to /login

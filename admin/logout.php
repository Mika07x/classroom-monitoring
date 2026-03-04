<?php
require_once __DIR__ . '/../config/SessionManager.php';

SessionManager::startAdminSession();
SessionManager::logout();
header('Location: ../login.php');
exit;
?>
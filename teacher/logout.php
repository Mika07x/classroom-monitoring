<?php
require_once __DIR__ . '/../config/SessionManager.php';

SessionManager::startTeacherSession();
SessionManager::logout();
header('Location: ../login.php');
exit;
?>
<?php
// Quick local utility to set the admin password (for local dev only).
// Usage (once): http://localhost/Teacher%20Faculty%20Management%20website/tools/set_admin_password.php
// Or provide password: http://localhost/.../tools/set_admin_password.php?pw=admin123

// Only allow from localhost for safety
$allowed_ips = ['127.0.0.1', '::1', 'localhost'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, $allowed_ips)) {
    http_response_code(403);
    echo "Forbidden: this script can only be run from the local machine.";
    exit;
}

require_once __DIR__ . '/../config/Database.php';

$db = new Database();
$conn = $db->connect();

$password = $_GET['pw'] ?? 'admin123';
$hashed = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
if (!$stmt) {
    echo 'Prepare failed: ' . $conn->error;
    exit;
}
$username = 'admin';
$stmt->bind_param('ss', $hashed, $username);
if ($stmt->execute()) {
    echo "Admin password updated successfully.\n";
    echo "New password: " . htmlspecialchars($password) . "\n";
    echo "Stored hash: " . htmlspecialchars($hashed) . "\n";
    echo "Please delete this file after use for security.";
} else {
    echo 'Update failed: ' . $stmt->error;
}

$stmt->close();
$conn->close();

?>
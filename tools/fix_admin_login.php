<?php
// Diagnostic and fix tool for admin login - LOCAL ONLY
// Usage: http://localhost/Teacher%20Faculty%20Management%20website/tools/fix_admin_login.php

// Only allow from localhost
$allowed_ips = ['127.0.0.1', '::1'];
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($ip, $allowed_ips)) {
    http_response_code(403);
    echo "Forbidden: this script can only be run from localhost.";
    exit;
}

require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/User.php';

$db = new Database();
$conn = $db->connect();

echo "<h1>Admin Login Diagnostic & Fix</h1>";

// Check if admin user exists
$stmt = $conn->prepare("SELECT id, username, password FROM users WHERE username = ?");
$username = 'admin';
$stmt->bind_param('s', $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<p style='color:red;'><strong>Error:</strong> No admin user found in database!</p>";
} else {
    $admin = $result->fetch_assoc();
    echo "<p><strong>Admin user found:</strong></p>";
    echo "<ul>";
    echo "<li>ID: " . $admin['id'] . "</li>";
    echo "<li>Username: " . htmlspecialchars($admin['username']) . "</li>";
    echo "<li>Stored hash: " . htmlspecialchars(substr($admin['password'], 0, 20)) . "...</li>";
    echo "</ul>";

    // Test if demo password works
    echo "<h3>Testing Password Verification</h3>";
    echo "<p>Testing password: <code>admin123</code></p>";
    if (password_verify('admin123', $admin['password'])) {
        echo "<p style='color:green;'><strong>✓ Password verification PASSED - Login should work!</strong></p>";
    } else {
        echo "<p style='color:red;'><strong>✗ Password verification FAILED - Password mismatch</strong></p>";
        echo "<p>The stored hash does not match 'admin123'.</p>";
    }
}

// Form to set new password
echo "<hr>";
echo "<h3>Set New Password</h3>";
echo "<form method='POST'>";
echo "<div style='margin-bottom:10px;'>";
echo "  <label>New Password:</label><br>";
echo "  <input type='text' name='new_password' value='admin123' style='padding:8px;width:300px;'>";
echo "</div>";
echo "<button type='submit' name='set_password' style='padding:8px 16px; background:#667eea; color:white; border:none; cursor:pointer; border-radius:4px;'>Set Password</button>";
echo "</form>";

// Handle password setting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_password'])) {
    $new_password = $_POST['new_password'] ?? 'admin123';
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);

    $update = $conn->prepare("UPDATE users SET password = ? WHERE username = ?");
    $update->bind_param('ss', $hashed, $username);

    if ($update->execute()) {
        echo "<p style='color:green;'><strong>✓ Password updated successfully!</strong></p>";
        echo "<p>New password: <code>" . htmlspecialchars($new_password) . "</code></p>";
        echo "<p>Hash: <code>" . htmlspecialchars($hashed) . "</code></p>";

        // Test immediately
        echo "<h3>Testing New Password</h3>";
        if (password_verify($new_password, $hashed)) {
            echo "<p style='color:green;'><strong>✓ Verification test PASSED</strong></p>";
            echo "<p><strong>You can now login with:</strong></p>";
            echo "<ul>";
            echo "<li>Username: <code>admin</code></li>";
            echo "<li>Password: <code>" . htmlspecialchars($new_password) . "</code></li>";
            echo "</ul>";
        }
    } else {
        echo "<p style='color:red;'><strong>Error updating password: " . $update->error . "</strong></p>";
    }
}

echo "<hr>";
echo "<p style='color:#666; font-size:12px;'><strong>⚠️ Security:</strong> Delete this file after use (tools/fix_admin_login.php)</p>";

$conn->close();

?>
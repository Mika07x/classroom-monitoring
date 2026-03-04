<?php
// Add profile_image column to users table if it doesn't exist
// Usage: http://localhost/Teacher%20Faculty%20Management%20website/tools/add_profile_image_column.php

require_once __DIR__ . '/../config/Database.php';

$db = new Database();
$conn = $db->connect();

echo "<h2>Database Migration: Add profile_image column</h2>";

// Check if column already exists
$result = $conn->query("SHOW COLUMNS FROM users LIKE 'profile_image'");

if ($result && $result->num_rows > 0) {
    echo "<p style='color:green;'><strong>✓ Column 'profile_image' already exists.</strong></p>";
} else {
    echo "<p>Adding 'profile_image' column to users table...</p>";

    $alter = "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) DEFAULT NULL AFTER password";

    if ($conn->query($alter) === TRUE) {
        echo "<p style='color:green;'><strong>✓ Column added successfully!</strong></p>";
    } else {
        echo "<p style='color:red;'><strong>✗ Error: " . $conn->error . "</strong></p>";
    }
}

// Create uploads directory if doesn't exist
$upload_dir = __DIR__ . '/../assets/uploads';
if (!is_dir($upload_dir)) {
    if (mkdir($upload_dir, 0755, true)) {
        echo "<p style='color:green;'><strong>✓ Uploads directory created.</strong></p>";
    } else {
        echo "<p style='color:orange;'><strong>⚠ Could not create uploads directory.</strong></p>";
    }
} else {
    echo "<p style='color:green;'><strong>✓ Uploads directory exists.</strong></p>";
}

$conn->close();

echo "<hr>";
echo "<p style='color:#666; font-size:12px;'><strong>⚠️ Security:</strong> Delete this file after use (tools/add_profile_image_column.php)</p>";

?>
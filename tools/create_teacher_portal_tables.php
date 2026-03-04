<?php
// Run this once from localhost to create required tables for teacher portal
// Usage: http://localhost/Teacher%20Faculty%20Management%20website/tools/create_teacher_portal_tables.php

require_once __DIR__ . '/../config/Database.php';
$db = new Database();
$conn = $db->connect();

$queries = [
    // Room reservations
    "CREATE TABLE IF NOT EXISTS room_reservations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        teacher_id INT NOT NULL,
        classroom_id INT NOT NULL,
        reservation_date DATE NOT NULL,
        time_slot_id INT NOT NULL,
        status ENUM('pending','approved','rejected','cancelled') DEFAULT 'pending',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
        FOREIGN KEY (classroom_id) REFERENCES classrooms(id) ON DELETE RESTRICT,
        FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE RESTRICT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

    // Teacher availability
    "CREATE TABLE IF NOT EXISTS teacher_availability (
        id INT PRIMARY KEY AUTO_INCREMENT,
        teacher_id INT NOT NULL,
        day_of_week ENUM('Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday') NOT NULL,
        time_slot_id INT NOT NULL,
        status ENUM('available','unavailable') DEFAULT 'available',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
        FOREIGN KEY (time_slot_id) REFERENCES time_slots(id) ON DELETE RESTRICT,
        UNIQUE KEY unique_availability (teacher_id, day_of_week, time_slot_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
];

foreach ($queries as $q) {
    if ($conn->query($q) === TRUE) {
        echo "Query executed successfully.<br>";
    } else {
        echo "Error executing query: " . $conn->error . "<br>";
    }
}

echo "Done. Please delete this file after use for security.";

$conn->close();

?>
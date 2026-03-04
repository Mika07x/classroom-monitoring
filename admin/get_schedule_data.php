<?php
require_once __DIR__ . '/../config/Database.php';

header('Content-Type: application/json');

if (!isset($_GET['schedule_id'])) {
    echo json_encode(['success' => false, 'error' => 'Schedule ID not provided']);
    exit;
}

$schedule_id = (int) $_GET['schedule_id'];

try {
    $db = new Database();
    $conn = $db->connect();

    $query = "SELECT s.*, sub.subject_name, c.room_number, c.room_name, ts.slot_name, ts.start_time, ts.end_time
              FROM schedules s
              JOIN subjects sub ON s.subject_id = sub.id
              JOIN classrooms c ON s.classroom_id = c.id
              JOIN time_slots ts ON s.time_slot_id = ts.id
              WHERE s.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $scheduleData = $result->fetch_assoc();
        echo json_encode([
            'success' => true,
            'subject_id' => $scheduleData['subject_id'],
            'classroom_id' => $scheduleData['classroom_id'],
            'day_of_week' => $scheduleData['day_of_week'],
            'time_slot_id' => $scheduleData['time_slot_id'],
            'status' => $scheduleData['status'],
            'subject_name' => $scheduleData['subject_name'],
            'room_number' => $scheduleData['room_number'],
            'room_name' => $scheduleData['room_name'],
            'slot_name' => $scheduleData['slot_name'],
            'start_time' => $scheduleData['start_time'],
            'end_time' => $scheduleData['end_time']
        ]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Schedule not found']);
    }

    $stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Database error: ' . $e->getMessage()]);
}
?>
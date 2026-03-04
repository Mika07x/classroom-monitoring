<?php
/**
 * SHARED SYSTEM FUNCTIONS - Used across admin, teacher, and student modules
 * This ensures consistent business logic and prevents code duplication
 */

/**
 * Get current teacher location based on schedule and reservations
 * REUSED BY: admin/teachers.php, teacher/dashboard.php, student/teachers.php
 * 
 * @param mysqli $conn Database connection
 * @param int $teacher_id Teacher ID
 * @return string Current location or '-' if not found
 */
function getCurrentTeacherLocation($conn, $teacher_id)
{
    $current_day = date('l'); // Monday, Tuesday, etc.
    $current_time = date('H:i:s');

    // Check scheduled classes first - SAME LOGIC as admin module
    $scheduleQuery = "SELECT c.room_name, c.room_number, ts.start_time, ts.end_time, s.day_of_week 
                     FROM schedules s 
                     JOIN classrooms c ON s.classroom_id = c.id 
                     JOIN time_slots ts ON s.time_slot_id = ts.id 
                     WHERE s.teacher_id = ? AND s.day_of_week = ? AND s.status = 'active'";

    $stmt = $conn->prepare($scheduleQuery);
    $stmt->bind_param('is', $teacher_id, $current_day);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Convert times to comparable format
        $start_time = strtotime($row['start_time']);
        $end_time = strtotime($row['end_time']);
        $current_timestamp = strtotime($current_time);

        // Check if current time is within this slot (with 5 minute buffer)
        if ($current_timestamp >= ($start_time - 300) && $current_timestamp <= ($end_time + 300)) {
            return $row['room_name'] . ' (' . $row['room_number'] . ')';
        }
    }

    // Check room reservations if no scheduled class - SAME LOGIC as admin module
    $reservationQuery = "SELECT c.room_name, c.room_number, ts.start_time, ts.end_time 
                        FROM room_reservations rr 
                        JOIN classrooms c ON rr.classroom_id = c.id 
                        JOIN time_slots ts ON rr.time_slot_id = ts.id
                        WHERE rr.teacher_id = ? AND rr.status = 'approved' AND rr.reservation_date = CURDATE()";

    $stmt = $conn->prepare($reservationQuery);
    $stmt->bind_param('i', $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $start_time = strtotime($row['start_time']);
        $end_time = strtotime($row['end_time']);
        $current_timestamp = strtotime($current_time);

        if ($current_timestamp >= ($start_time - 300) && $current_timestamp <= ($end_time + 300)) {
            return $row['room_name'] . ' (' . $row['room_number'] . ')';
        }
    }

    return '-'; // Not currently assigned anywhere
}

/**
 * Get teacher's schedule for a specific day
 * REUSED BY: Multiple modules for schedule display
 * 
 * @param mysqli $conn Database connection
 * @param int $teacher_id Teacher ID
 * @param string $day Day of week (Monday, Tuesday, etc.)
 * @return mysqli_result Schedule result set
 */
function getTeacherScheduleByDay($conn, $teacher_id, $day = null)
{
    if ($day === null) {
        $day = date('l');
    }

    $query = "SELECT s.*, c.room_name, c.room_number, ts.slot_name, ts.start_time, ts.end_time, 
                     sub.subject_name, sub.subject_code
              FROM schedules s 
              JOIN classrooms c ON s.classroom_id = c.id 
              JOIN time_slots ts ON s.time_slot_id = ts.id
              JOIN subjects sub ON s.subject_id = sub.id
              WHERE s.teacher_id = ? AND s.day_of_week = ? AND s.status = 'active'
              ORDER BY ts.start_time";

    $stmt = $conn->prepare($query);
    $stmt->bind_param('is', $teacher_id, $day);
    $stmt->execute();
    return $stmt->get_result();
}

/**
 * Format time for display - consistent across all modules
 * REUSED BY: All modules for time display formatting
 * 
 * @param string $time Time in H:i:s format
 * @return string Formatted time
 */
function formatTimeForDisplay($time)
{
    return date('g:i A', strtotime($time));
}

/**
 * Get status badge class - consistent styling across modules
 * REUSED BY: All modules for status display
 * 
 * @param string $status Status value
 * @return string Bootstrap badge class
 */
function getStatusBadgeClass($status)
{
    switch (strtolower($status)) {
        case 'active':
            return 'badge-success';
        case 'inactive':
            return 'badge-danger';
        case 'on_leave':
            return 'badge-warning';
        default:
            return 'badge-secondary';
    }
}

/**
 * Validate academic year format
 * REUSED BY: All modules handling academic year input
 * 
 * @param string $year Academic year
 * @return bool True if valid format
 */
function isValidAcademicYear($year)
{
    return preg_match('/^\d{4}(-\d{4})?$/', $year);
}

/**
 * Get user role display name
 * REUSED BY: All modules for role display
 * 
 * @param string $role Role code
 * @return string Display name
 */
function getRoleDisplayName($role)
{
    $roles = [
        'admin' => 'Administrator',
        'teacher' => 'Teacher',
        'student' => 'Student'
    ];
    return $roles[$role] ?? ucfirst($role);
}
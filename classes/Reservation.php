<?php

class Reservation
{
    private $conn;
    private $table = 'room_reservations';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Check if a room is available on a given date/time slot (no approved reservation and no schedule clash)
    public function isRoomAvailable($classroom_id, $reservation_date, $time_slot_id)
    {
        // Check approved or pending reservations
        $query = "SELECT COUNT(*) as cnt FROM room_reservations WHERE classroom_id = ? AND reservation_date = ? AND time_slot_id = ? AND status IN ('approved','pending')";
        $stmt = $this->conn->prepare($query);
        if (!$stmt)
            return false;
        $stmt->bind_param('isi', $classroom_id, $reservation_date, $time_slot_id);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        if ($res && $res['cnt'] > 0) {
            return false;
        }

        // Check schedules (classrooms already assigned in schedules table for that day/time)
        $dayOfWeek = date('l', strtotime($reservation_date));
        $query2 = "SELECT COUNT(*) as cnt FROM schedules s WHERE s.classroom_id = ? AND s.day_of_week = ? AND s.time_slot_id = ? AND s.status = 'active'";
        $stmt2 = $this->conn->prepare($query2);
        if (!$stmt2)
            return false;
        $stmt2->bind_param('isi', $classroom_id, $dayOfWeek, $time_slot_id);
        $stmt2->execute();
        $res2 = $stmt2->get_result()->fetch_assoc();
        if ($res2 && $res2['cnt'] > 0) {
            return false;
        }

        return true;
    }

    // Create a reservation (initially pending)
    public function createReservation($teacher_id, $classroom_id, $reservation_date, $time_slot_id, $notes = '')
    {
        // Validate availability
        if (!$this->isRoomAvailable($classroom_id, $reservation_date, $time_slot_id)) {
            return ['success' => false, 'message' => 'Room is not available for the selected date/time.'];
        }

        $query = "INSERT INTO " . $this->table . " (teacher_id, classroom_id, reservation_date, time_slot_id, notes) VALUES (?, ?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        if (!$stmt)
            return ['success' => false, 'message' => 'Database error: ' . $this->conn->error];
        $stmt->bind_param('iisis', $teacher_id, $classroom_id, $reservation_date, $time_slot_id, $notes);
        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Reservation request created and is pending approval.'];
        }
        return ['success' => false, 'message' => 'Failed to create reservation: ' . $stmt->error];
    }

    // Get reservations for a teacher
    public function getReservationsByTeacher($teacher_id)
    {
        $query = "SELECT rr.*, c.room_number, c.room_name, ts.slot_name FROM " . $this->table . " rr
                  LEFT JOIN classrooms c ON rr.classroom_id = c.id
                  LEFT JOIN time_slots ts ON rr.time_slot_id = ts.id
                  WHERE rr.teacher_id = ? ORDER BY rr.reservation_date DESC, ts.start_time";
        $stmt = $this->conn->prepare($query);
        if (!$stmt)
            return false;
        $stmt->bind_param('i', $teacher_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get all reservations (for admin)
    public function getAllReservations($status = null)
    {
        $query = "SELECT rr.*, c.room_number, c.room_name, ts.slot_name, t.id as teacher_id, t.first_name, t.last_name, u.username, u.email
                  FROM " . $this->table . " rr
                  LEFT JOIN classrooms c ON rr.classroom_id = c.id
                  LEFT JOIN time_slots ts ON rr.time_slot_id = ts.id
                  LEFT JOIN teachers t ON rr.teacher_id = t.id
                  LEFT JOIN users u ON t.user_id = u.id";
        if ($status) {
            $query .= " WHERE rr.status = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param('s', $status);
            $stmt->execute();
            return $stmt->get_result();
        } else {
            $query .= " ORDER BY rr.reservation_date DESC, ts.start_time";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            return $stmt->get_result();
        }
    }

    // Approve a reservation (admin)
    public function approveReservation($id)
    {
        // Fetch reservation
        $stmt = $this->conn->prepare("SELECT classroom_id, reservation_date, time_slot_id FROM " . $this->table . " WHERE id = ?");
        if (!$stmt)
            return ['success' => false, 'message' => 'Reservation not found'];
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res->num_rows === 0)
            return ['success' => false, 'message' => 'Reservation not found'];
        $row = $res->fetch_assoc();

        // Check if already has an approved reservation
        $q = "SELECT COUNT(*) as cnt FROM " . $this->table . " WHERE classroom_id = ? AND reservation_date = ? AND time_slot_id = ? AND status = 'approved'";
        $s = $this->conn->prepare($q);
        $s->bind_param('isi', $row['classroom_id'], $row['reservation_date'], $row['time_slot_id']);
        $s->execute();
        $r = $s->get_result()->fetch_assoc();
        if ($r && $r['cnt'] > 0) {
            return ['success' => false, 'message' => 'Room already approved for that date/time'];
        }

        // Check schedule conflict
        $dayOfWeek = date('l', strtotime($row['reservation_date']));
        $q2 = "SELECT COUNT(*) as cnt FROM schedules WHERE classroom_id = ? AND day_of_week = ? AND time_slot_id = ? AND status = 'active'";
        $s2 = $this->conn->prepare($q2);
        $s2->bind_param('isi', $row['classroom_id'], $dayOfWeek, $row['time_slot_id']);
        $s2->execute();
        $r2 = $s2->get_result()->fetch_assoc();
        if ($r2 && $r2['cnt'] > 0) {
            return ['success' => false, 'message' => 'There is an existing schedule in that room at the selected time'];
        }

        // Update status to approved
        $u = $this->conn->prepare("UPDATE " . $this->table . " SET status = 'approved', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        $u->bind_param('i', $id);
        if ($u->execute()) {
            return ['success' => true, 'message' => 'Reservation approved'];
        }
        return ['success' => false, 'message' => 'Failed to approve reservation'];
    }

    // Reject a reservation (admin)
    public function rejectReservation($id, $reason = '')
    {
        $u = $this->conn->prepare("UPDATE " . $this->table . " SET status = 'rejected', notes = CONCAT(IFNULL(notes,''), '\n[Admin reason]: ', ?) , updated_at = CURRENT_TIMESTAMP WHERE id = ?");
        if (!$u)
            return ['success' => false, 'message' => 'Database error'];
        $u->bind_param('si', $reason, $id);
        if ($u->execute()) {
            return ['success' => true, 'message' => 'Reservation rejected'];
        }
        return ['success' => false, 'message' => 'Failed to reject reservation'];
    }

}

?>
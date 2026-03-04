<?php

class Schedule
{
    private $conn;
    private $table = 'schedules';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all schedules
    public function getAll()
    {
        $query = "SELECT s.*, t.first_name, t.last_name, sub.subject_name, 
                         c.room_number, ts.slot_name, ts.start_time, ts.end_time 
                  FROM " . $this->table . " s 
                  JOIN teachers t ON s.teacher_id = t.id 
                  JOIN subjects sub ON s.subject_id = sub.id 
                  JOIN classrooms c ON s.classroom_id = c.id 
                  JOIN time_slots ts ON s.time_slot_id = ts.id 
                  ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
                           ts.start_time";

        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get schedule by ID
    public function getById($id)
    {
        $query = "SELECT s.*, t.first_name, t.last_name, sub.subject_name, 
                         c.room_number, ts.slot_name, ts.start_time, ts.end_time 
                  FROM " . $this->table . " s 
                  JOIN teachers t ON s.teacher_id = t.id 
                  JOIN subjects sub ON s.subject_id = sub.id 
                  JOIN classrooms c ON s.classroom_id = c.id 
                  JOIN time_slots ts ON s.time_slot_id = ts.id 
                  WHERE s.id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Add schedule
    public function add($teacher_id, $subject_id, $classroom_id, $day_of_week, $time_slot_id, $semester, $academic_year)
    {
        // Check for conflicts
        if ($this->hasConflict($teacher_id, $classroom_id, $day_of_week, $time_slot_id)) {
            return ['status' => false, 'message' => 'Schedule conflict detected'];
        }

        $status = 'active';
        $query = "INSERT INTO " . $this->table . " 
                  (teacher_id, subject_id, classroom_id, day_of_week, time_slot_id, semester, academic_year, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iiisssss", $teacher_id, $subject_id, $classroom_id, $day_of_week, $time_slot_id, $semester, $academic_year, $status);

        if ($stmt->execute()) {
            return ['status' => true, 'message' => 'Schedule added successfully'];
        }
        return ['status' => false, 'message' => 'Error adding schedule'];
    }

    // Update schedule
    public function update($id, $teacher_id, $subject_id, $classroom_id, $day_of_week, $time_slot_id, $status)
    {
        // Check for conflicts (excluding current schedule)
        if ($this->hasConflict($teacher_id, $classroom_id, $day_of_week, $time_slot_id, $id)) {
            return ['status' => false, 'message' => 'Schedule conflict detected'];
        }

        $query = "UPDATE " . $this->table . " SET 
                  teacher_id = ?, subject_id = ?, classroom_id = ?, day_of_week = ?, time_slot_id = ?, status = ? 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iiisssi", $teacher_id, $subject_id, $classroom_id, $day_of_week, $time_slot_id, $status, $id);

        if ($stmt->execute()) {
            return ['status' => true, 'message' => 'Schedule updated successfully'];
        }
        return ['status' => false, 'message' => 'Error updating schedule'];
    }

    // Delete schedule
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Check for schedule conflict
    private function hasConflict($teacher_id, $classroom_id, $day_of_week, $time_slot_id, $exclude_id = null)
    {
        $query = "SELECT COUNT(*) as conflict_count FROM " . $this->table . " 
                  WHERE (teacher_id = ? OR classroom_id = ?) 
                  AND day_of_week = ? 
                  AND time_slot_id = ? 
                  AND status = 'active'";

        if ($exclude_id) {
            $query .= " AND id != ?";
        }

        $stmt = $this->conn->prepare($query);

        if ($exclude_id) {
            $stmt->bind_param("iiisi", $teacher_id, $classroom_id, $day_of_week, $time_slot_id, $exclude_id);
        } else {
            $stmt->bind_param("iiis", $teacher_id, $classroom_id, $day_of_week, $time_slot_id);
        }

        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        return $result['conflict_count'] > 0;
    }

    // Get timetable for a day
    public function getTimetableByDay($day, $academic_year = null)
    {
        $query = "SELECT s.*, t.first_name, t.last_name, sub.subject_name, 
                         c.room_number, c.room_name, ts.slot_name, ts.start_time, ts.end_time 
                  FROM " . $this->table . " s 
                  JOIN teachers t ON s.teacher_id = t.id 
                  JOIN subjects sub ON s.subject_id = sub.id 
                  JOIN classrooms c ON s.classroom_id = c.id 
                  JOIN time_slots ts ON s.time_slot_id = ts.id 
                  WHERE s.day_of_week = ? AND s.status = 'active'";

        if ($academic_year) {
            $query .= " AND s.academic_year = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ss", $day, $academic_year);
        } else {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $day);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    // Get current timetable (week view)
    public function getCurrentTimetable($academic_year = null)
    {
        $query = "SELECT s.*, t.first_name, t.last_name, sub.subject_name, 
                         c.room_number, c.room_name, ts.slot_name, ts.start_time, ts.end_time,
                         FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday') as day_order
                  FROM " . $this->table . " s 
                  JOIN teachers t ON s.teacher_id = t.id 
                  JOIN subjects sub ON s.subject_id = sub.id 
                  JOIN classrooms c ON s.classroom_id = c.id 
                  JOIN time_slots ts ON s.time_slot_id = ts.id 
                  WHERE s.status = 'active'";

        if ($academic_year) {
            $query .= " AND s.academic_year = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $academic_year);
        } else {
            $stmt = $this->conn->prepare($query);
        }

        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
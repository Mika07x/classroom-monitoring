<?php

class Teacher
{
    private $conn;
    private $table = 'teachers';

    public $id;
    public $user_id;
    public $first_name;
    public $last_name;
    public $email;
    public $phone;
    public $department;
    public $qualification;
    public $hire_date;
    public $bio;
    public $profile_image;
    public $status;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all teachers
    public function getAll($status = null)
    {
        $query = "SELECT t.*, u.username FROM " . $this->table . " t 
                  LEFT JOIN users u ON t.user_id = u.id";

        if ($status) {
            $query .= " WHERE t.status = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $this->conn->prepare($query);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    // Get teacher by ID
    public function getById($id)
    {
        $query = "SELECT t.*, u.username FROM " . $this->table . " t 
                  LEFT JOIN users u ON t.user_id = u.id 
                  WHERE t.id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Add teacher
    public function add($user_id, $first_name, $last_name, $email, $phone, $department, $qualification, $hire_date, $bio)
    {
        $query = "INSERT INTO " . $this->table . " 
                  (user_id, first_name, last_name, email, phone, department, qualification, hire_date, bio, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issssssss", $user_id, $first_name, $last_name, $email, $phone, $department, $qualification, $hire_date, $bio);

        return $stmt->execute();
    }

    // Update teacher
    public function update($id, $first_name, $last_name, $email, $phone, $department, $qualification, $hire_date, $bio, $status)
    {
        $query = "UPDATE " . $this->table . " SET 
                  first_name = ?, last_name = ?, email = ?, phone = ?, department = ?, 
                  qualification = ?, hire_date = ?, bio = ?, status = ? 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssssssssi", $first_name, $last_name, $email, $phone, $department, $qualification, $hire_date, $bio, $status, $id);

        return $stmt->execute();
    }

    // Delete teacher
    public function delete($id)
    {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        return $stmt->execute();
    }

    // Search teachers
    public function search($keyword)
    {
        $query = "SELECT t.*, u.username FROM " . $this->table . " t 
                  LEFT JOIN users u ON t.user_id = u.id 
                  WHERE t.first_name LIKE ? OR t.last_name LIKE ? OR t.email LIKE ? OR t.department LIKE ? 
                  AND t.status = 'active'";

        $searchTerm = "%$keyword%";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssss", $searchTerm, $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();

        return $stmt->get_result();
    }

    // Get teachers by department
    public function getByDepartment($department)
    {
        $query = "SELECT t.*, u.username FROM " . $this->table . " t 
                  LEFT JOIN users u ON t.user_id = u.id 
                  WHERE t.department = ? AND t.status = 'active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $department);
        $stmt->execute();

        return $stmt->get_result();
    }

    // Get current assignment for teacher (based on current time)
    public function getCurrentAssignment($teacher_id)
    {
        $query = "SELECT s.*, ts.slot_name, ts.start_time, ts.end_time, 
                         sub.subject_name, c.room_number, c.room_name 
                  FROM schedules s 
                  JOIN time_slots ts ON s.time_slot_id = ts.id 
                  JOIN subjects sub ON s.subject_id = sub.id 
                  JOIN classrooms c ON s.classroom_id = c.id 
                  WHERE s.teacher_id = ? 
                  AND s.day_of_week = DAYNAME(NOW()) 
                  AND TIME(NOW()) BETWEEN ts.start_time AND ts.end_time 
                  AND s.status = 'active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();

        return $stmt->get_result()->fetch_assoc();
    }

    // Get all schedules for teacher
    public function getSchedules($teacher_id)
    {
        $query = "SELECT s.*, ts.slot_name, ts.start_time, ts.end_time, 
                         sub.subject_name, sub.subject_code, c.room_number, c.room_name 
                  FROM schedules s 
                  JOIN time_slots ts ON s.time_slot_id = ts.id 
                  JOIN subjects sub ON s.subject_id = sub.id 
                  JOIN classrooms c ON s.classroom_id = c.id 
                  WHERE s.teacher_id = ? 
                  ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), 
                           ts.start_time";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();

        return $stmt->get_result();
    }
}
?>
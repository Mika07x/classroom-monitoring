<?php

class Subject
{
    private $conn;
    private $table = 'subjects';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all subjects
    public function getAll($status = null)
    {
        $query = "SELECT * FROM " . $this->table;

        if ($status) {
            $query .= " WHERE status = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("s", $status);
        } else {
            $stmt = $this->conn->prepare($query);
        }

        $stmt->execute();
        return $stmt->get_result();
    }

    // Get subject by ID
    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Add subject
    public function add($subject_code, $subject_name, $department, $description, $credits)
    {
        $status = 'active';
        $query = "INSERT INTO " . $this->table . " 
                  (subject_code, subject_name, department, description, credits, status) 
                  VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssis", $subject_code, $subject_name, $department, $description, $credits, $status);

        // Check if insert was successful
        if ($stmt->execute()) {
            return ['status' => true, 'message' => 'Subject added successfully'];
        }
        return ['status' => false, 'message' => 'Error adding subject'];
    }

    // Update subject
    public function update($id, $subject_code, $subject_name, $department, $description, $credits, $status)
    {
        $query = "UPDATE " . $this->table . " SET 
                  subject_code = ?, subject_name = ?, department = ?, description = ?, credits = ?, status = ? 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ssssisi", $subject_code, $subject_name, $department, $description, $credits, $status, $id);

        return $stmt->execute();
    }

    // Delete subject
    public function delete($id)
    {
        try {
            $query = "DELETE FROM " . $this->table . " WHERE id = ?";
            $stmt = $this->conn->prepare($query);

            if (!$stmt) {
                return false;
            }

            $stmt->bind_param("i", $id);
            $result = $stmt->execute();

            if (!$result) {
                error_log("Delete error: " . $stmt->error);
                return false;
            }

            return $stmt->affected_rows > 0;
        } catch (Exception $e) {
            error_log("Delete exception: " . $e->getMessage());
            return false;
        }
    }

    // Get subjects by department
    public function getByDepartment($department)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE department = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $department);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get subjects assigned to a teacher
    public function getTeacherSubjects($teacher_id)
    {
        $query = "SELECT sub.* FROM " . $this->table . " sub 
                  JOIN teacher_subjects ts ON sub.id = ts.subject_id 
                  WHERE ts.teacher_id = ? AND sub.status = 'active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $teacher_id);
        $stmt->execute();
        return $stmt->get_result();
    }
}
?>
<?php

class Classroom
{
    private $conn;
    private $table = 'classrooms';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Get all classrooms
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

    // Get classroom by ID
    public function getById($id)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Add classroom
    public function add($room_number, $room_name, $building, $capacity, $room_type, $equipment = null, $floor = null)
    {
        $status = 'active';
        $query = "INSERT INTO " . $this->table . " 
                  (room_number, room_name, building, capacity, room_type, equipment, floor, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssissis", $room_number, $room_name, $building, $capacity, $room_type, $equipment, $floor, $status);

        if ($stmt->execute()) {
            return ['status' => true, 'message' => 'Classroom added successfully'];
        }
        return ['status' => false, 'message' => 'Error adding classroom'];
    }

    // Update classroom
    public function update($id, $room_number, $room_name, $building, $capacity, $room_type, $equipment, $floor, $status)
    {
        $query = "UPDATE " . $this->table . " SET 
                  room_number = ?, room_name = ?, building = ?, capacity = ?, room_type = ?, equipment = ?, floor = ?, status = ? 
                  WHERE id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssisissi", $room_number, $room_name, $building, $capacity, $room_type, $equipment, $floor, $status, $id);

        return $stmt->execute();
    }

    // Delete classroom
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

    // Get classrooms by building
    public function getByBuilding($building)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE building = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $building);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get classrooms by type
    public function getByType($room_type)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE room_type = ? AND status = 'active'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $room_type);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Search classrooms
    public function search($keyword)
    {
        $query = "SELECT * FROM " . $this->table . " WHERE room_number LIKE ? OR room_name LIKE ? OR building LIKE ?";
        $searchTerm = "%$keyword%";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sss", $searchTerm, $searchTerm, $searchTerm);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get available classrooms for a time slot
    public function getAvailableForTimeSlot($day_of_week, $time_slot_id)
    {
        $query = "SELECT c.* FROM " . $this->table . " c 
                  WHERE c.id NOT IN (
                      SELECT classroom_id FROM schedules 
                      WHERE day_of_week = ? AND time_slot_id = ? AND status = 'active'
                  ) 
                  AND c.status = 'active'";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("si", $day_of_week, $time_slot_id);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getSchedule($id)
    {
        $query = "SELECT * FROM schedules WHERE classroom_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result();   // <-- returns mysqli_result object
    }
}
?>
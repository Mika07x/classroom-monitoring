<?php
$page_title = 'Professor Schedules';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Teacher.php';
require_once __DIR__ . '/../classes/Subject.php';
require_once __DIR__ . '/../classes/Classroom.php';

$db = new Database();
$conn = $db->connect();

$teacherObj = new Teacher($conn);
$subjectObj = new Subject($conn);
$classroomObj = new Classroom($conn);

$action = $_GET['action'] ?? '';
$teacher_id = $_GET['teacher_id'] ?? null;
$schedule_id = $_GET['schedule_id'] ?? null;
$message = '';
$error = '';

// Handle schedule update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $subject_id = $_POST['subject_id'];
    $classroom_id = $_POST['classroom_id'];
    $day_of_week = $_POST['day_of_week'];
    $time_slot_id = $_POST['time_slot_id'];
    $status = $_POST['status'] ?? 'active';

    // Update the schedule
    $updateQuery = "UPDATE schedules SET subject_id = ?, classroom_id = ?, day_of_week = ?, time_slot_id = ?, status = ? WHERE id = ?";
    $updateStmt = $conn->prepare($updateQuery);
    $updateStmt->bind_param('iisssi', $subject_id, $classroom_id, $day_of_week, $time_slot_id, $status, $schedule_id);

    if ($updateStmt->execute()) {
        $message = 'Schedule updated successfully!';
    } else {
        $error = 'Error updating schedule.';
    }
    $updateStmt->close();

    // Redirect to prevent form resubmission
    if ($message) {
        header('Location: schedules.php?action=view_schedule&teacher_id=' . $teacher_id . '&msg=' . urlencode($message));
    } else {
        header('Location: schedules.php?action=view_schedule&teacher_id=' . $teacher_id . '&error=' . urlencode($error));
    }
    exit;
}

// Handle schedule deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = $_POST['schedule_id'];
    $teacher_id = $_POST['teacher_id'] ?? $_GET['teacher_id'];

    // Get schedule details for confirmation message
    $scheduleQuery = "SELECT s.*, sub.subject_name, ts.slot_name, ts.start_time 
                     FROM schedules s 
                     JOIN subjects sub ON s.subject_id = sub.id 
                     JOIN time_slots ts ON s.time_slot_id = ts.id 
                     WHERE s.id = ?";
    $scheduleStmt = $conn->prepare($scheduleQuery);
    $scheduleStmt->bind_param('i', $schedule_id);
    $scheduleStmt->execute();
    $scheduleResult = $scheduleStmt->get_result();
    $scheduleData = $scheduleResult->fetch_assoc();

    if ($scheduleData) {
        // Delete the schedule
        $deleteQuery = "DELETE FROM schedules WHERE id = ?";
        $deleteStmt = $conn->prepare($deleteQuery);
        $deleteStmt->bind_param('i', $schedule_id);

        if ($deleteStmt->execute()) {
            $message = 'Schedule deleted successfully! (' . $scheduleData['subject_name'] . ' on ' . $scheduleData['day_of_week'] . ' at ' . $scheduleData['slot_name'] . ')';
        } else {
            $error = 'Error deleting schedule.';
        }
        $deleteStmt->close();
    } else {
        $error = 'Schedule not found.';
    }
    $scheduleStmt->close();

    // Redirect to prevent form resubmission
    if ($message) {
        header('Location: schedules.php?action=view_schedule&teacher_id=' . $teacher_id . '&msg=' . urlencode($message));
    } else {
        header('Location: schedules.php?action=view_schedule&teacher_id=' . $teacher_id . '&error=' . urlencode($error));
    }
    exit;
}

// Handle adding new schedule
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_schedule'])) {
    $teacher_id = $_POST['teacher_id'];
    $subject_id = $_POST['subject_id'];
    $classroom_id = $_POST['classroom_id'];
    $day_of_week = $_POST['day_of_week'];
    $time_slot_id = $_POST['time_slot_id'];
    $status = $_POST['status'] ?? 'active';

    // Check for existing schedule conflict
    $conflictQuery = "SELECT COUNT(*) as count FROM schedules WHERE teacher_id = ? AND day_of_week = ? AND time_slot_id = ? AND status = 'active'";
    $conflictStmt = $conn->prepare($conflictQuery);
    $conflictStmt->bind_param('isi', $teacher_id, $day_of_week, $time_slot_id);
    $conflictStmt->execute();
    $conflictResult = $conflictStmt->get_result();
    $conflictRow = $conflictResult->fetch_assoc();

    if ($conflictRow['count'] > 0) {
        $error = 'Schedule conflict: Teacher already has a schedule for this day and time slot.';
    } else {
        // Check teacher availability for this time slot
        $availabilityQuery = "SELECT status FROM teacher_availability WHERE teacher_id = ? AND day_of_week = ? AND time_slot_id = ?";
        $availabilityStmt = $conn->prepare($availabilityQuery);
        $availabilityStmt->bind_param('isi', $teacher_id, $day_of_week, $time_slot_id);
        $availabilityStmt->execute();
        $availabilityResult = $availabilityStmt->get_result();
        $availability = $availabilityResult->fetch_assoc();

        // Block scheduling if teacher marked themselves unavailable
        if ($availability && strtolower($availability['status']) === 'unavailable') {
            $error = 'Cannot add schedule: Teacher has marked themselves as UNAVAILABLE for ' . $day_of_week . ' at this time slot. Please check the teacher\'s availability settings.';
        } else {
            // Check for classroom conflicts (any teacher using the room at this time)
            $roomConflictQuery = "SELECT sc.id, t.first_name, t.last_name, s.subject_name, c.room_number, c.building
                                  FROM schedules sc
                                  JOIN teachers t ON sc.teacher_id = t.id
                                  JOIN subjects s ON sc.subject_id = s.id
                                  JOIN classrooms c ON sc.classroom_id = c.id
                                  WHERE sc.classroom_id = ? AND sc.day_of_week = ? AND sc.time_slot_id = ? 
                                  AND sc.status = 'active' AND sc.teacher_id != ?
                                  LIMIT 1";
            $roomConflictStmt = $conn->prepare($roomConflictQuery);
            $roomConflictStmt->bind_param('isii', $classroom_id, $day_of_week, $time_slot_id, $teacher_id);
            $roomConflictStmt->execute();
            $roomConflictResult = $roomConflictStmt->get_result();
            $roomConflictRow = $roomConflictResult->fetch_assoc();

            if ($roomConflictRow) {
                $error = 'Room conflict: ' . $roomConflictRow['room_number'] . ' (' . $roomConflictRow['building'] . ') is already booked on ' . $day_of_week . ' at this time by ' . $roomConflictRow['first_name'] . ' ' . $roomConflictRow['last_name'] . ' for ' . $roomConflictRow['subject_name'] . '.';
            } else {
                // Get time slot details for better success message
                $timeSlotQuery = "SELECT slot_name, start_time, end_time,time_interval FROM time_slots WHERE id = ?";
                $timeSlotStmt = $conn->prepare($timeSlotQuery);
                $timeSlotStmt->bind_param('i', $time_slot_id);
                $timeSlotStmt->execute();
                $timeSlotResult = $timeSlotStmt->get_result();
                $timeSlotData = $timeSlotResult->fetch_assoc();

                // Insert the new schedule
                $insertQuery = "INSERT INTO schedules (teacher_id, subject_id, classroom_id, day_of_week, time_slot_id, status) VALUES (?, ?, ?, ?, ?, ?)";
                $insertStmt = $conn->prepare($insertQuery);
                $insertStmt->bind_param('iiisis', $teacher_id, $subject_id, $classroom_id, $day_of_week, $time_slot_id, $status);

                if ($insertStmt->execute()) {
                    $timeSlotInfo = $timeSlotData ? $timeSlotData['slot_name'] : 'selected time slot';
                    $message = 'Schedule added successfully for ' . $day_of_week . ' (' . $timeSlotInfo . ')!';
                } else {
                    $error = 'Error adding schedule to database.';
                }
                $insertStmt->close();
                $timeSlotStmt->close();
            }
            $roomConflictStmt->close();
        }
        $availabilityStmt->close();
    }
    $conflictStmt->close();

    // Redirect to prevent form resubmission
    if ($message) {
        header('Location: schedules.php?action=view_schedule&teacher_id=' . $teacher_id . '&msg=' . urlencode($message));
    } else {
        header('Location: schedules.php?action=view_schedule&teacher_id=' . $teacher_id . '&error=' . urlencode($error));
    }
    exit;
}

// Check for success message from redirect
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

// Check for error message from redirect
if (isset($_GET['error'])) {
    $error = $_GET['error'];
}

// Get teacher data for schedule view
$viewTeacher = null;
if ($action === 'view_schedule' && $teacher_id) {
    $viewTeacher = $teacherObj->getById($teacher_id);
}

// Get schedule data for editing
$editScheduleData = null;
if ($action === 'view_schedule' && $schedule_id) {
    $editQuery = "SELECT s.*, sub.subject_name, c.room_number, c.room_name, ts.slot_name, ts.start_time, ts.end_time
                  FROM schedules s
                  JOIN subjects sub ON s.subject_id = sub.id
                  JOIN classrooms c ON s.classroom_id = c.id
                  JOIN time_slots ts ON s.time_slot_id = ts.id
                  WHERE s.id = ?";
    $editStmt = $conn->prepare($editQuery);
    $editStmt->bind_param('i', $schedule_id);
    $editStmt->execute();
    $editResult = $editStmt->get_result();
    $editScheduleData = $editResult->fetch_assoc();
    $editStmt->close();
}

require_once 'header.php'; ?>

<div class="main-content" style="margin-left: 280px; margin-top: 70px; padding: 30px; min-height: calc(100vh - 70px);">
    <div class="page-header">
        <div class="flex-between">
            <div>
                <?php if ($action === 'view_schedule' && $viewTeacher): ?>
                    <h1>
                        <a href="schedules.php" class="btn btn-outline-secondary btn-sm me-2">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                        <?php echo htmlspecialchars($viewTeacher['first_name'] . ' ' . $viewTeacher['last_name']); ?>'s
                        Schedule
                    </h1>
                    <p>Weekly teaching schedule overview</p>
                <?php else: ?>
                    <h1>Professor Schedules</h1>
                    <p>View teaching schedules for all professors</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'view_schedule' && $viewTeacher): ?>
        <!-- Teacher Schedule View -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><?php echo htmlspecialchars($viewTeacher['first_name'] . ' ' . $viewTeacher['last_name']); ?> - Weekly
                    Schedule</h5>
                <button type="button" class="btn btn-primary btn-sm" onclick="openAddScheduleModal()">
                    <i class="fas fa-plus"></i> Add Schedule
                </button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <?php
                    // Get all time slots
                    $timeSlotsQuery = "SELECT * FROM time_slots WHERE status = 'active' ORDER BY start_time";
                    $timeSlotsResult = $conn->query($timeSlotsQuery);
                    $timeSlots = [];
                    while ($slot = $timeSlotsResult->fetch_assoc()) {
                        $timeSlots[] = $slot;
                    }

                    // Get teacher's schedules organized by day and time
                    $scheduleData = [];
                    $schedules = $teacherObj->getSchedules($teacher_id);
                    while ($schedule = $schedules->fetch_assoc()) {
                        $day = $schedule['day_of_week'];
                        $timeSlotId = $schedule['time_slot_id'];
                        $scheduleData[$day][$timeSlotId] = $schedule;
                    }

                    $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    ?>

                    <table class="table table-bordered schedule-grid">
                        <thead class="table-light">
                            <tr>
                                <th class="time-column">Time</th>
                                <?php foreach ($days as $day): ?>
                                    <th class="text-center"><?php echo $day; ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($timeSlots as $timeSlot): ?>
                                <tr>
                                    <td class="time-slot">
                                        <div class="text-center">
                                            <small
                                                class="text-muted"><?php echo date('g:i A', strtotime($timeSlot['start_time'])); ?></small>
                                        </div>
                                        <strong><?php echo date('g:i A', strtotime($timeSlot['time_interval'])); ?></strong><br>
                                        <small
                                            class="text-muted"><?php echo date('g:i A', strtotime($timeSlot['end_time'])); ?></small>
                    </div>
                    </td>
                    <?php foreach ($days as $day): ?>
                        <td class="schedule-cell">
                            <?php
                            if (isset($scheduleData[$day][$timeSlot['id']])) {
                                $class = $scheduleData[$day][$timeSlot['id']];
                                ?>
                                <div class="class-block editable-block" data-schedule-id="<?php echo $class['id']; ?>"
                                    title="Click to edit this schedule" style="cursor: pointer;">
                                    <div class="subject-code">
                                        <strong><?php echo htmlspecialchars($class['subject_code'] ?? 'N/A'); ?></strong>
                                    </div>
                                    <div class="subject-name"><?php echo htmlspecialchars($class['subject_name']); ?>
                                    </div>
                                    <div class="room-info">
                                        <small><i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($class['room_number']); ?></small>
                                    </div>
                                    <div class="edit-indicator">
                                        <i class="fas fa-edit"></i>
                                    </div>
                                </div>
                                <?php
                            }
                            ?>
                        </td>
                    <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                </tbody>
                </table>

                <?php if (empty($scheduleData)): ?>
                    <div class="text-center text-muted mt-3">
                        <p>No schedules assigned to this teacher</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal fade" id="editScheduleModal" tabindex="-1" aria-labelledby="editScheduleModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editScheduleModalLabel">Edit Schedule</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_schedule_id" name="schedule_id" value="">
                        <input type="hidden" name="edit_schedule" value="1">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_subject_id" class="form-label">Subject *</label>
                                    <select class="form-select" id="edit_subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        <?php
                                        $subjectsResult = $subjectObj->getAll('active');
                                        while ($subject = $subjectsResult->fetch_assoc()) {
                                            echo "<option value='{$subject['id']}'>{$subject['subject_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_classroom_id" class="form-label">Classroom *</label>
                                    <select class="form-select" id="edit_classroom_id" name="classroom_id" required>
                                        <option value="">Select Classroom</option>
                                        <?php
                                        $classroomsResult = $classroomObj->getAll('active');
                                        while ($classroom = $classroomsResult->fetch_assoc()) {
                                            echo "<option value='{$classroom['id']}'>{$classroom['room_number']} - {$classroom['room_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_day_of_week" class="form-label">Day of Week *</label>
                                    <select class="form-select" id="edit_day_of_week" name="day_of_week" required>
                                        <option value="">Select Day</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="edit_time_slot_id" class="form-label">Time Slot *</label>
                                    <select class="form-select" id="edit_time_slot_id" name="time_slot_id" required>
                                        <option value="">Select Time Slot</option>
                                        <?php
                                        $slotsResult = $conn->query("SELECT * FROM time_slots WHERE status = 'active' ORDER BY start_time");
                                        while ($slot = $slotsResult->fetch_assoc()) {
                                            echo "<option value='{$slot['id']}'>{$slot['slot_name']} ({$slot['start_time']} - {$slot['end_time']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="edit_status" class="form-label">Status</label>
                            <select class="form-select" id="edit_status" name="status">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer d-flex justify-content-between">
                        <button type="button" class="btn btn-danger" onclick="confirmDeleteSchedule()">
                            <i class="fas fa-trash"></i> Delete Schedule
                        </button>
                        <div>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Schedule
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Hidden Delete Form -->
                <form id="deleteScheduleForm" method="POST" action="" style="display: none;">
                    <input type="hidden" id="delete_schedule_id" name="schedule_id" value="">
                    <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">
                    <input type="hidden" name="delete_schedule" value="1">
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- Search and Filter Section -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-search"></i> Search & Filter Professor</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-6">
                    <label for="searchInput" class="form-label">Search Professor</label>
                    <div class="position-relative">
                        <input type="text" id="searchInput" class="form-control"
                            placeholder="Type to search by name, email, or department..." autocomplete="off">
                        <div id="searchSuggestions" class="search-suggestions"></div>
                        <i class="fas fa-search search-icon"></i>
                    </div>
                </div>
                <div class="col-md-3">
                    <label for="departmentFilter" class="form-label">Department Filter</label>
                    <select id="departmentFilter" class="form-select">
                        <option value="">All Departments</option>
                        <option value="Computer Science">Computer Science</option>
                        <option value="Mathematics">Mathematics</option>
                        <option value="English">English</option>
                        <option value="Physics">Physics</option>
                        <option value="Chemistry">Chemistry</option>
                        <option value="Biology">Biology</option>
                        <option value="History">History</option>
                        <option value="Business">Business</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="statusFilter" class="form-label">Status Filter</label>
                    <select id="statusFilter" class="form-select">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-6">
                    <label for="sortBy" class="form-label">Sort By</label>
                    <select id="sortBy" class="form-select">
                        <option value="name_asc">Name A-Z</option>
                        <option value="name_desc">Name Z-A</option>
                        <option value="department_asc">Department A-Z</option>
                        <option value="email_asc">Email A-Z</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <div class="d-flex align-items-end h-100">
                        <button id="clearSearch" class="btn btn-outline-secondary me-3">
                            <i class="fas fa-times"></i> Clear Filters
                        </button>
                        <span id="resultCount" class="text-muted"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Teachers List -->
    <div class="card">
        <div class="card-header">
            <h5>All Professor</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="teachersTable" class="table table-hover">
                    <thead>
                        <tr>
                            <th>Professor Name</th>
                            <th>Email</th>
                            <th>Department</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="teachersTableBody">
                        <?php
                        $result = $teacherObj->getAll('active');

                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                ?>
                                <tr data-name="<?= strtolower(htmlspecialchars($row['first_name'] . ' ' . $row['last_name'])) ?>"
                                    data-email="<?= strtolower(htmlspecialchars($row['email'])) ?>"
                                    data-department="<?= strtolower(htmlspecialchars($row['department'] ?? '')) ?>"
                                    data-status="<?= htmlspecialchars($row['status']) ?>">
                                    <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['email']); ?></td>
                                    <td><?php echo htmlspecialchars($row['department'] ?? 'Not specified'); ?></td>
                                    <td>
                                        <span
                                            class="badge badge-<?php echo ($row['status'] === 'active') ? 'success' : 'warning'; ?>">
                                            <?php echo htmlspecialchars($row['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="schedules.php?action=view_schedule&teacher_id=<?php echo $row['id']; ?>"
                                            class="btn btn-sm btn-primary">
                                            <i class="fas fa-calendar-alt"></i> View Schedule
                                        </a>
                                    </td>
                                </tr>
                                <?php
                            }
                        } else {
                            ?>
                            <tr id="noTeachersRow">
                                <td colspan="5" class="text-center text-muted">No teachers found</td>
                            </tr>
                            <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

<?php endif; ?>

</div>

<style>
    /* Schedule grid styles */
    .schedule-grid {
        font-size: 0.9rem;
    }

    .schedule-grid th {
        background-color: #e8f5e8 !important;
        text-align: center;
        font-weight: bold;
        border: 1px solid #28a745;
        padding: 12px 8px;
    }

    .time-column {
        width: 120px;
        min-width: 120px;
    }

    .time-slot {
        background-color: #e8f5e8;
        border: 1px solid #28a745;
        font-size: 0.85rem;
        padding: 20px 8px;
        vertical-align: middle;
        text-align: center;
    }

    .schedule-cell {
        width: 140px;
        height: 80px;
        vertical-align: middle;
        border: 1px solid #28a745;
        padding: 4px;
        position: relative;
    }

    .class-block {
        background-color: #d4edda;
        border: 1px solid #28a745;
        border-radius: 4px;
        padding: 6px;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        position: relative;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .editable-block:hover {
        background-color: #c3e6cb;
        border-color: #1e7e34;
        transform: scale(1.02);
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .edit-indicator {
        position: absolute;
        top: 2px;
        right: 2px;
        opacity: 0;
        transition: opacity 0.3s ease;
        color: #155724;
        font-size: 0.7rem;
    }

    .editable-block:hover .edit-indicator {
        opacity: 1;
    }

    .editable-block::before {
        content: '';
        position: absolute;
        top: -2px;
        left: -2px;
        right: -2px;
        bottom: -2px;
        border: 2px solid transparent;
        border-radius: 6px;
        transition: border-color 0.3s ease;
    }

    .editable-block:hover::before {
        border-color: #007bff;
    }

    .subject-code {
        font-size: 0.85rem;
        font-weight: bold;
        line-height: 1.2;
        margin-bottom: 2px;
        color: #155724;
    }

    .subject-name {
        font-size: 0.75rem;
        line-height: 1.1;
        margin-bottom: 2px;
        color: #155724;
        word-wrap: break-word;
    }

    .room-info {
        font-size: 0.7rem;
        color: #6c757d;
        margin-top: auto;
    }

    .schedule-cell:empty {
        background-color: #f8f9fa;
    }

    /* Responsive design */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
            padding: 20px !important;
        }

        .schedule-grid {
            font-size: 0.75rem;
        }

        .schedule-cell {
            width: 100px;
            height: 60px;
        }

        .subject-code {
            font-size: 0.7rem;
        }

        .subject-name {
            font-size: 0.65rem;
        }

        .room-info {
            font-size: 0.6rem;
        }
    }

    /* Search Suggestions Styles */
    .position-relative {
        position: relative;
    }

    .search-suggestions {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ddd;
        border-top: none;
        border-radius: 0 0 4px 4px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 1000;
        display: none;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .suggestion-item {
        padding: 12px;
        cursor: pointer;
        border-bottom: 1px solid #eee;
        transition: background-color 0.2s;
    }

    .suggestion-item:hover,
    .suggestion-item.active {
        background-color: #f8f9fa;
    }

    .suggestion-item:last-child {
        border-bottom: none;
    }

    .suggestion-name {
        font-weight: 600;
        color: #333;
    }

    .suggestion-details {
        font-size: 0.9em;
        color: #666;
        margin-top: 2px;
    }

    .search-icon {
        position: absolute;
        right: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        pointer-events: none;
    }

    .highlight {
        background-color: #fff3cd;
        transition: background-color 0.3s ease;
    }

    /* Filter animations */
    .fade-out {
        opacity: 0;
        transform: scale(0.95);
        transition: all 0.2s ease-out;
    }

    .fade-in {
        opacity: 1;
        transform: scale(1);
        transition: all 0.2s ease-in;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Only initialize search functionality when NOT viewing a schedule
        <?php if ($action !== 'view_schedule'): ?>

            const searchInput = document.getElementById('searchInput');
            const searchSuggestions = document.getElementById('searchSuggestions');
            const departmentFilter = document.getElementById('departmentFilter');
            const statusFilter = document.getElementById('statusFilter');
            const sortBy = document.getElementById('sortBy');
            const clearSearch = document.getElementById('clearSearch');
            const resultCount = document.getElementById('resultCount');
            const tableBody = document.getElementById('teachersTableBody');
            const allRows = Array.from(tableBody.querySelectorAll('tr:not(#noTeachersRow)'));

            let currentSuggestionIndex = -1;
            let filteredTeachers = allRows;

            // Update result count
            function updateResultCount() {
                const visibleRows = allRows.filter(row => row.style.display !== 'none');
                const count = visibleRows.length;
                resultCount.textContent = `Showing ${count} of ${allRows.length} professors`;
            }

            // Get teacher suggestions based on search term
            function getTeacherSuggestions(searchTerm) {
                if (searchTerm.length < 2) return [];

                const suggestions = [];
                const searchTermLower = searchTerm.toLowerCase();

                allRows.forEach(row => {
                    const name = row.dataset.name;
                    const email = row.dataset.email;
                    const department = row.dataset.department;

                    if (name.includes(searchTermLower) || email.includes(searchTermLower) || department.includes(searchTermLower)) {
                        const nameDisplay = row.children[0].textContent.trim();
                        const emailDisplay = row.children[1].textContent.trim();
                        const departmentDisplay = row.children[2].textContent.trim();

                        suggestions.push({
                            name: nameDisplay,
                            email: emailDisplay,
                            department: departmentDisplay,
                            fullMatch: name === searchTermLower || email === searchTermLower
                        });
                    }
                });

                // Sort by relevance (exact matches first, then partial matches)
                suggestions.sort((a, b) => {
                    if (a.fullMatch && !b.fullMatch) return -1;
                    if (!a.fullMatch && b.fullMatch) return 1;
                    return 0;
                });

                return suggestions.slice(0, 5); // Limit to 5 suggestions
            }

            // Display suggestions
            function showSuggestions(suggestions) {
                if (suggestions.length === 0) {
                    searchSuggestions.style.display = 'none';
                    return;
                }

                const html = suggestions.map((suggestion, index) => `
            <div class="suggestion-item ${index === currentSuggestionIndex ? 'active' : ''}" data-name="${suggestion.name}">
                <div class="suggestion-name">${suggestion.name}</div>
                <div class="suggestion-details">${suggestion.email} • ${suggestion.department}</div>
            </div>
        `).join('');

                searchSuggestions.innerHTML = html;
                searchSuggestions.style.display = 'block';

                // Add click handlers to suggestions
                searchSuggestions.querySelectorAll('.suggestion-item').forEach(item => {
                    item.addEventListener('click', function () {
                        searchInput.value = this.dataset.name;
                        searchSuggestions.style.display = 'none';
                        filterTeachers();
                    });
                });
            }

            // Filter teachers based on all criteria
            function filterTeachers() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                const departmentValue = departmentFilter.value.toLowerCase();
                const statusValue = statusFilter.value;
                const sortValue = sortBy.value;

                // Reset all rows
                allRows.forEach(row => {
                    row.style.display = '';
                    row.classList.remove('highlight');
                });

                // Apply filters
                const visibleRows = allRows.filter(row => {
                    const name = row.dataset.name;
                    const email = row.dataset.email;
                    const department = row.dataset.department;
                    const status = row.dataset.status;

                    // Search filter
                    const matchesSearch = !searchTerm ||
                        name.includes(searchTerm) ||
                        email.includes(searchTerm) ||
                        department.includes(searchTerm);

                    // Department filter
                    const matchesDepartment = !departmentValue || department.includes(departmentValue);

                    // Status filter
                    const matchesStatus = !statusValue || status === statusValue;

                    const isVisible = matchesSearch && matchesDepartment && matchesStatus;

                    if (!isVisible) {
                        row.style.display = 'none';
                    } else if (searchTerm) {
                        // Highlight matching row
                        row.classList.add('highlight');
                        setTimeout(() => row.classList.remove('highlight'), 2000);
                    }

                    return isVisible;
                });

                // Sort visible rows
                const sortedRows = [...visibleRows];
                switch (sortValue) {
                    case 'name_asc':
                        sortedRows.sort((a, b) => a.dataset.name.localeCompare(b.dataset.name));
                        break;
                    case 'name_desc':
                        sortedRows.sort((a, b) => b.dataset.name.localeCompare(a.dataset.name));
                        break;
                    case 'department_asc':
                        sortedRows.sort((a, b) => a.dataset.department.localeCompare(b.dataset.department));
                        break;
                    case 'email_asc':
                        sortedRows.sort((a, b) => a.dataset.email.localeCompare(b.dataset.email));
                        break;
                }

                // Reorder table rows
                sortedRows.forEach((row, index) => {
                    tableBody.appendChild(row);
                });

                // Show no results message if needed
                const noResultsRow = document.getElementById('noTeachersRow');
                if (visibleRows.length === 0 && allRows.length > 0) {
                    if (!noResultsRow) {
                        const newNoResultsRow = document.createElement('tr');
                        newNoResultsRow.id = 'noTeachersRow';
                        newNoResultsRow.innerHTML = '<td colspan="5" class="text-center">No teachers match your search criteria.</td>';
                        tableBody.appendChild(newNoResultsRow);
                    } else {
                        noResultsRow.style.display = '';
                    }
                } else if (noResultsRow) {
                    noResultsRow.style.display = 'none';
                }

                updateResultCount();
            }

            // Search input event handlers
            if (searchInput) {
                searchInput.addEventListener('input', function () {
                    const searchTerm = this.value.trim();

                    if (searchTerm.length >= 2) {
                        const suggestions = getTeacherSuggestions(searchTerm);
                        showSuggestions(suggestions);
                    } else {
                        searchSuggestions.style.display = 'none';
                    }

                    filterTeachers();
                });

                // Keyboard navigation for suggestions
                searchInput.addEventListener('keydown', function (e) {
                    const suggestions = searchSuggestions.querySelectorAll('.suggestion-item');

                    if (e.key === 'ArrowDown') {
                        e.preventDefault();
                        currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
                        updateSuggestionSelection();
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
                        updateSuggestionSelection();
                    } else if (e.key === 'Enter' && currentSuggestionIndex >= 0) {
                        e.preventDefault();
                        suggestions[currentSuggestionIndex].click();
                    } else if (e.key === 'Escape') {
                        searchSuggestions.style.display = 'none';
                        currentSuggestionIndex = -1;
                    }
                });
            }

            function updateSuggestionSelection() {
                const suggestions = searchSuggestions.querySelectorAll('.suggestion-item');
                suggestions.forEach((item, index) => {
                    item.classList.toggle('active', index === currentSuggestionIndex);
                });
            }

            // Hide suggestions when clicking outside
            document.addEventListener('click', function (e) {
                if (searchInput && !searchInput.contains(e.target) && !searchSuggestions.contains(e.target)) {
                    searchSuggestions.style.display = 'none';
                    currentSuggestionIndex = -1;
                }
            });

            // Filter change handlers
            if (departmentFilter) departmentFilter.addEventListener('change', filterTeachers);
            if (statusFilter) statusFilter.addEventListener('change', filterTeachers);
            if (sortBy) sortBy.addEventListener('change', filterTeachers);

            // Clear filters
            if (clearSearch) {
                clearSearch.addEventListener('click', function () {
                    searchInput.value = '';
                    departmentFilter.value = '';
                    statusFilter.value = '';
                    sortBy.value = 'name_asc';
                    searchSuggestions.style.display = 'none';
                    currentSuggestionIndex = -1;
                    filterTeachers();
                    searchInput.focus();
                });
            }

            // Initialize
            updateResultCount();

        <?php endif; ?>
    });

    // Initialize edit functionality when DOM is loaded
    document.addEventListener('DOMContentLoaded', function () {
        // Only initialize edit functionality when viewing a schedule
        <?php if ($action === 'view_schedule'): ?>
            initEditSchedule();
        <?php endif; ?>
    });

    // Edit schedule functionality
    function initEditSchedule() {
        console.log('Initializing edit schedule functionality');

        // Add click handlers to all editable blocks
        const editableBlocks = document.querySelectorAll('.editable-block');
        console.log('Found', editableBlocks.length, 'editable blocks');

        editableBlocks.forEach(block => {
            block.addEventListener('click', function (e) {
                e.preventDefault();
                const scheduleId = this.getAttribute('data-schedule-id');
                console.log('Clicked schedule ID:', scheduleId);

                if (scheduleId) {
                    openEditModal(scheduleId);
                }
            });
        });
    }

    function openEditModal(scheduleId) {
        console.log('Opening modal for schedule ID:', scheduleId);

        // Set the schedule ID in the hidden input
        document.getElementById('edit_schedule_id').value = scheduleId;

        // Show loading state
        document.getElementById('editScheduleModalLabel').textContent = 'Loading...';

        // Fetch current schedule data via AJAX
        fetch('get_schedule_data.php?schedule_id=' + scheduleId)
            .then(response => {
                console.log(response);
                console.log('Response status:', response.status);
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.json();
            })
            .then(data => {
                console.log('Schedule data received:', data);
                if (data.success) {
                    // Populate the form fields
                    document.getElementById('edit_subject_id').value = data.subject_id;
                    document.getElementById('edit_classroom_id').value = data.classroom_id;
                    document.getElementById('edit_day_of_week').value = data.day_of_week;
                    document.getElementById('edit_time_slot_id').value = data.time_slot_id;
                    document.getElementById('edit_status').value = data.status;

                    // Update modal title
                    document.getElementById('editScheduleModalLabel').textContent =
                        `Edit Schedule - ${data.subject_name} (${data.day_of_week} ${data.slot_name})`;

                    // Show the modal using different methods
                    const modal = document.getElementById('editScheduleModal');

                    // Try different Bootstrap modal methods
                    if (typeof $ !== 'undefined' && $.fn.modal) {
                        // jQuery Bootstrap
                        $('#editScheduleModal').modal('show');
                    } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
                        // Bootstrap 5
                        const bsModal = new bootstrap.Modal(modal);
                        bsModal.show();
                    } else if (modal.setAttribute) {
                        // Fallback: manually show modal
                        modal.style.display = 'block';
                        modal.classList.add('show');
                        modal.setAttribute('aria-hidden', 'false');
                        modal.setAttribute('aria-modal', 'true');

                        // Add backdrop
                        const backdrop = document.createElement('div');
                        backdrop.className = 'modal-backdrop fade show';
                        backdrop.id = 'modal-backdrop';
                        document.body.appendChild(backdrop);
                        document.body.classList.add('modal-open');

                        // Add close handlers
                        const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
                        closeButtons.forEach(btn => {
                            btn.addEventListener('click', closeModal);
                        });

                        // Close on backdrop click
                        backdrop.addEventListener('click', closeModal);
                    }
                } else {
                    console.error('Error loading schedule data:', data.error);
                    alert('Error loading schedule data: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Fetch error:', error);
                alert('Error loading schedule data. Please check the console for details.');
            });
    }

    function closeModal() {
        const modal = document.getElementById('editScheduleModal');
        const backdrop = document.getElementById('modal-backdrop');

        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#editScheduleModal').modal('hide');
        } else {
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');

            if (backdrop) {
                backdrop.remove();
            }
            document.body.classList.remove('modal-open');
        }
    }

    function openAddScheduleModal() {
        console.log('Opening Add Schedule modal');
        const modal = document.getElementById('addScheduleModal');

        if (typeof $ !== 'undefined' && $.fn.modal) {
            // jQuery Bootstrap
            $('#addScheduleModal').modal('show');
        } else if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            // Bootstrap 5
            const bsModal = new bootstrap.Modal(modal);
            bsModal.show();
        } else {
            // Fallback: manually show modal
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            modal.setAttribute('aria-modal', 'true');

            // Add backdrop
            const backdrop = document.createElement('div');
            backdrop.className = 'modal-backdrop fade show';
            backdrop.id = 'add-modal-backdrop';
            document.body.appendChild(backdrop);
            document.body.classList.add('modal-open');

            // Add close handlers
            const closeButtons = modal.querySelectorAll('[data-bs-dismiss="modal"], .btn-close');
            closeButtons.forEach(btn => {
                btn.addEventListener('click', function () {
                    closeAddScheduleModal();
                });
            });

            // Close on backdrop click
            backdrop.addEventListener('click', function () {
                closeAddScheduleModal();
            });
        }
    }

    function closeAddScheduleModal() {
        const modal = document.getElementById('addScheduleModal');
        const backdrop = document.getElementById('add-modal-backdrop');

        if (typeof $ !== 'undefined' && $.fn.modal) {
            $('#addScheduleModal').modal('hide');
        } else {
            modal.style.display = 'none';
            modal.classList.remove('show');
            modal.setAttribute('aria-hidden', 'true');
            modal.removeAttribute('aria-modal');

            if (backdrop) {
                backdrop.remove();
            }
            document.body.classList.remove('modal-open');
        }
    }

    function confirmDeleteSchedule() {
        const scheduleId = document.getElementById('edit_schedule_id').value;
        const modalTitle = document.getElementById('editScheduleModalLabel').textContent;

        if (confirm('Are you sure you want to delete this schedule?\n\n' + modalTitle + '\n\nThis action cannot be undone.')) {
            // Set the schedule ID in the delete form
            document.getElementById('delete_schedule_id').value = scheduleId;

            // Submit the delete form
            document.getElementById('deleteScheduleForm').submit();
        }
    }
</script>

<!-- Add Schedule Modal -->
<?php if ($action === 'view_schedule' && $viewTeacher): ?>
    <div class="modal fade" id="addScheduleModal" tabindex="-1" aria-labelledby="addScheduleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addScheduleModalLabel">Add New Schedule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="teacher_id" value="<?php echo $teacher_id; ?>">

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="add_subject_id" class="form-label">Subject *</label>
                                    <select class="form-select" id="add_subject_id" name="subject_id" required>
                                        <option value="">Select Subject</option>
                                        <?php
                                        if (isset($teacher_id)) {
                                            // Get all active subjects (not just teacher's specializations)
                                            // This allows more flexibility in schedule assignment
                                            $subjectsQuery = "SELECT id, subject_name, subject_code 
                                                         FROM subjects 
                                                         WHERE status = 'active'
                                                         ORDER BY subject_name";
                                            $subjectsResult = $conn->query($subjectsQuery);

                                            if ($subjectsResult && $subjectsResult->num_rows > 0) {
                                                while ($subject = $subjectsResult->fetch_assoc()) {
                                                    echo "<option value='{$subject['id']}'>{$subject['subject_name']} ({$subject['subject_code']})</option>";
                                                }
                                            } else {
                                                echo "<option value='' disabled>No subjects available</option>";
                                            }
                                        } else {
                                            echo "<option value='' disabled>Teacher ID not found</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="add_classroom_id" class="form-label">Classroom *</label>
                                    <select class="form-select" id="add_classroom_id" name="classroom_id" required>
                                        <option value="">Select Classroom</option>
                                        <?php
                                        $classroomsResult = $conn->query("SELECT * FROM classrooms WHERE status = 'active' ORDER BY room_number");
                                        while ($classroom = $classroomsResult->fetch_assoc()) {
                                            echo "<option value='{$classroom['id']}'>{$classroom['room_number']} ({$classroom['building']} - {$classroom['floor']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="add_day_of_week" class="form-label">Day of Week *</label>
                                    <select class="form-select" id="add_day_of_week" name="day_of_week" required>
                                        <option value="">Select Day</option>
                                        <option value="Monday">Monday</option>
                                        <option value="Tuesday">Tuesday</option>
                                        <option value="Wednesday">Wednesday</option>
                                        <option value="Thursday">Thursday</option>
                                        <option value="Friday">Friday</option>
                                        <option value="Saturday">Saturday</option>
                                        <option value="Sunday">Sunday</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label for="add_time_slot_id" class="form-label">Time Slot *</label>
                                    <select class="form-select" id="add_time_slot_id" name="time_slot_id" required>
                                        <option value="">Select Time Slot</option>
                                        <?php
                                        $slotsResult = $conn->query("SELECT * FROM time_slots WHERE status = 'active' ORDER BY start_time");
                                        while ($slot = $slotsResult->fetch_assoc()) {
                                            echo "<option value='{$slot['id']}'>{$slot['slot_name']} ({$slot['start_time']} - {$slot['end_time']})</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="add_status" class="form-label">Status</label>
                            <select class="form-select" id="add_status" name="status">
                                <option value="active" selected>Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>

                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            <strong>Note:</strong> This will check teacher availability and prevent scheduling conflicts.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_schedule" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Add Schedule
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/footer.php'; ?>
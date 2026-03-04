<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher schedule modules
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\SessionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Schedule.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\includes\functions.php';

SessionManager::requireStudent();

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

// REUSE EXISTING SCHEDULE CLASS - same as admin module
$scheduleObj = new Schedule($conn);

$page_title = 'My Schedule';
require_once __DIR__ . '/header.php';

$user_id = SessionManager::getUserId();

// Create student_enrollments table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS student_enrollments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_user_id INT NOT NULL,
    subject_id INT NOT NULL,
    academic_year VARCHAR(10) NOT NULL,
    semester VARCHAR(10) DEFAULT '1',
    status ENUM('enrolled', 'dropped') DEFAULT 'enrolled',
    enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_student_subject (student_user_id, subject_id),
    INDEX idx_status (status),
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
)";
$conn->query($createTable);

// Show only schedules for subjects the student is enrolled in
$query = "SELECT s.day_of_week, ts.slot_name, sub.subject_name, sub.subject_code, 
                 c.room_number, c.room_name, ts.start_time, ts.end_time,
                 CONCAT(t.first_name, ' ', t.last_name) as teacher_name
          FROM schedules s
          JOIN time_slots ts ON s.time_slot_id = ts.id
          JOIN subjects sub ON s.subject_id = sub.id
          JOIN classrooms c ON s.classroom_id = c.id
          JOIN teachers t ON s.teacher_id = t.id
          JOIN student_enrollments se ON s.subject_id = se.subject_id
          WHERE s.status = 'active' AND se.student_user_id = ? AND se.status = 'enrolled'
          ORDER BY FIELD(s.day_of_week, 'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time";

$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$res = $stmt->get_result();

?>

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <h4><i class="fas fa-calendar-alt"></i> My Weekly Schedule</h4>
            <p class="text-muted">Schedule for subjects you are currently enrolled in</p>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header bg-success text-white">
            <h5 class="mb-0"><i class="fas fa-calendar-week"></i> Class Schedule</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th><i class="fas fa-calendar-day"></i> Day</th>
                            <th><i class="fas fa-clock"></i> Time Slot</th>
                            <th><i class="fas fa-book"></i> Subject</th>
                            <th><i class="fas fa-user-tie"></i> Professor</th>
                            <th><i class="fas fa-map-marker-alt"></i> Room</th>
                            <th><i class="fas fa-stopwatch"></i> Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res && $res->num_rows > 0): ?>
                            <?php while ($row = $res->fetch_assoc()): ?>
                                <tr>
                                    <td>
                                        <strong
                                            class="text-success"><?php echo htmlspecialchars($row['day_of_week']); ?></strong>
                                    </td>
                                    <td>
                                        <span
                                            class="badge bg-success text-white"><?php echo htmlspecialchars($row['slot_name']); ?></span>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['subject_code']); ?></strong><br>
                                        <small class="text-muted"><?php echo htmlspecialchars($row['subject_name']); ?></small>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['teacher_name']); ?></td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['room_number']); ?></strong>
                                        <br><small class="text-muted"><?php echo htmlspecialchars($row['room_name']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark">
                                            <?php echo formatTimeForDisplay($row['start_time']) . ' - ' . formatTimeForDisplay($row['end_time']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5">
                                    <div>
                                        <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                        <h5 class="text-muted">No schedule available</h5>
                                        <p class="text-muted">You haven't enrolled in any subjects yet or no schedules are
                                            set for your enrolled subjects.</p>
                                        <a href="subjects.php" class="btn btn-success">
                                            <i class="fas fa-plus"></i> Enroll in Subjects
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php if ($res && $res->num_rows > 0): ?>
            <div class="card-footer text-muted">
                <small>
                    <i class="fas fa-info-circle"></i>
                    Showing <?php echo $res->num_rows; ?> scheduled classes for your enrolled subjects.
                    Times are displayed in 24-hour format.
                </small>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../admin/footer.php'; ?>
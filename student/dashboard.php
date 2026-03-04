<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher dashboards
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\SessionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Teacher.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Subject.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Classroom.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Schedule.php';

SessionManager::startStudentSession();
if (!SessionManager::isLoggedIn() || !SessionManager::isStudent()) {
    header('Location: ../student.php');
    exit;
}

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

// REUSE EXISTING CLASS INSTANCES - same pattern as admin dashboard
$teacherObj = new Teacher($conn);
$subjectObj = new Subject($conn);
$classroomObj = new Classroom($conn);
$scheduleObj = new Schedule($conn);

// REUSE EXISTING SHARED FUNCTIONS
require_once $_SERVER['DOCUMENT_ROOT'] . '\includes\functions.php';

// Get student data and statistics
$student_id = SessionManager::getUserId();

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

// Get enrolled subjects count
$enrolledQuery = "SELECT COUNT(*) as enrolled_count FROM student_enrollments WHERE student_user_id = ? AND status = 'enrolled'";
$stmt = $conn->prepare($enrolledQuery);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$enrolledResult = $stmt->get_result();
$enrolledCount = $enrolledResult->fetch_assoc()['enrolled_count'] ?? 0;

// Get total subjects available
$totalSubjectsQuery = "SELECT COUNT(*) as total_subjects FROM subjects";
$totalSubjectsResult = $conn->query($totalSubjectsQuery);
$totalSubjects = $totalSubjectsResult->fetch_assoc()['total_subjects'] ?? 0;

// Get unread announcements count (if announcements table exists)
$unreadCount = 0;
$checkAnnouncements = $conn->query("SHOW TABLES LIKE 'announcements'");
if ($checkAnnouncements->num_rows > 0) {
    // Create announcement_reads table if it doesn't exist
    $createAnnouncementReads = "CREATE TABLE IF NOT EXISTS announcement_reads (
        id INT PRIMARY KEY AUTO_INCREMENT,
        announcement_id INT NOT NULL,
        student_user_id INT NOT NULL,
        read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
        FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_read (announcement_id, student_user_id)
    )";
    $conn->query($createAnnouncementReads);

    $unreadQuery = "SELECT COUNT(*) as unread_count FROM announcements a 
                    JOIN student_enrollments se ON a.subject_id = se.subject_id 
                    LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.student_user_id = ?
                    WHERE se.student_user_id = ? AND se.status = 'enrolled' AND ar.id IS NULL";
    $stmt = $conn->prepare($unreadQuery);
    $stmt->bind_param("ii", $student_id, $student_id);
    $stmt->execute();
    $unreadResult = $stmt->get_result();
    $unreadCount = $unreadResult->fetch_assoc()['unread_count'] ?? 0;
}

// Get recent announcements (last 3) if table exists
$recentAnnouncements = null;
if ($checkAnnouncements->num_rows > 0) {
    // Check if announcements table has data before querying
    $checkDataQuery = "SELECT COUNT(*) as count FROM announcements";
    $checkDataResult = $conn->query($checkDataQuery);
    $announcementCount = 0;
    if ($checkDataResult) {
        $announcementCount = $checkDataResult->fetch_assoc()['count'] ?? 0;
    }

    if ($announcementCount > 0) {
        $recentAnnouncementsQuery = "SELECT a.id, a.title, a.message as content, a.priority, a.created_at, 
                                    sub.subject_name as subject_name, teach.first_name, teach.last_name 
                                    FROM announcements a 
                                    JOIN subjects sub ON a.subject_id = sub.id 
                                    JOIN teachers teach ON a.teacher_id = teach.id
                                    JOIN student_enrollments se ON a.subject_id = se.subject_id 
                                    WHERE se.student_user_id = ? AND se.status = 'enrolled'
                                    ORDER BY a.created_at DESC LIMIT 3";
        $announcementStmt = $conn->prepare($recentAnnouncementsQuery);
        if ($announcementStmt) {
            $announcementStmt->bind_param("i", $student_id);
            $announcementStmt->execute();
            $recentAnnouncements = $announcementStmt->get_result();
        }
    }
}

// Get today's schedule - simplified and error-free
$todaySchedule = null;
$today = date('Y-m-d');

// Create a simple mock result first
$todaySchedule = (object) ['num_rows' => 0];

// Only query schedule if student has enrollments and tables exist
if ($enrolledCount > 0) {
    try {
        $scheduleQuery = "SELECT sch.id, sch.date, sch.notes,
                         subj.subject_name, subj.subject_code, 
                         classroom.room_name as classroom_name, classroom.building as classroom_location,
                         teacher.first_name, teacher.last_name, 
                         slot.start_time, slot.end_time
                         FROM schedules sch
                         JOIN subjects subj ON sch.subject_id = subj.id 
                         JOIN classrooms classroom ON sch.classroom_id = classroom.id 
                         JOIN teachers teacher ON sch.teacher_id = teacher.id 
                         JOIN time_slots slot ON sch.time_slot_id = slot.id
                         JOIN student_enrollments enroll ON sch.subject_id = enroll.subject_id
                         WHERE enroll.student_user_id = ? AND enroll.status = 'enrolled' AND sch.date = ?
                         ORDER BY slot.start_time";

        $scheduleStmt = $conn->prepare($scheduleQuery);
        if ($scheduleStmt) {
            $scheduleStmt->bind_param("is", $student_id, $today);
            $scheduleStmt->execute();
            $scheduleResult = $scheduleStmt->get_result();
            if ($scheduleResult) {
                $todaySchedule = $scheduleResult;
            }
        }
    } catch (Exception $e) {
        // Keep the mock empty result if there's any error
        $todaySchedule = (object) ['num_rows' => 0];
    }
}

$page_title = 'Student Dashboard';
require_once __DIR__ . '/header.php';

?>

<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-home"></i> Welcome, <?php echo htmlspecialchars(SessionManager::getUsername()); ?>!
            </h2>
            <p class="text-muted">Student Dashboard — Your academic journey at a glance</p>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $enrolledCount; ?></h3>
                            <p class="mb-0">Enrolled Subjects</p>
                        </div>
                        <i class="fas fa-book-open fa-2x opacity-75"></i>
                    </div>
                </div>
                <div class="card-footer bg-success border-0">
                    <small>out of <?php echo $totalSubjects; ?> total subjects</small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $todaySchedule->num_rows; ?></h3>
                            <p class="mb-0">Classes Today</p>
                        </div>
                        <i class="fas fa-calendar-check fa-2x opacity-75"></i>
                    </div>
                </div>
                <div class="card-footer bg-success border-0">
                    <small><?php echo date('F j, Y'); ?></small>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-success text-white h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h3 class="mb-0"><?php echo $unreadCount; ?></h3>
                            <p class="mb-0">New Announcements</p>
                        </div>
                        <i class="fas fa-bullhorn fa-2x opacity-75"></i>
                    </div>
                </div>
                <div class="card-footer bg-success border-0">
                    <small><?php echo $unreadCount > 0 ? 'Check them out!' : 'All caught up!'; ?></small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Today's Schedule -->
        <div class="col-lg-8">
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-calendar-day"></i> Today's Schedule</h5>
                    <a href="schedule.php" class="btn btn-sm btn-light">View Full Schedule</a>
                </div>
                <div class="card-body">
                    <?php if ($todaySchedule->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Professor</th>
                                        <th>Location</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($class = $todaySchedule->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo date('H:i', strtotime($class['start_time'])); ?> -
                                                    <?php echo date('H:i', strtotime($class['end_time'])); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($class['subject_code']); ?></strong><br>
                                                <small
                                                    class="text-muted"><?php echo htmlspecialchars($class['subject_name']); ?></small>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($class['first_name'] . ' ' . $class['last_name']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-map-marker-alt text-success"></i>
                                                <?php echo htmlspecialchars($class['classroom_name']); ?>
                                                <?php if ($class['classroom_location']): ?>
                                                    <br><small
                                                        class="text-muted"><?php echo htmlspecialchars($class['classroom_location']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-4">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No classes scheduled for today</h5>
                            <p class="text-muted">Enjoy your free time!</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="col-lg-4">
            <div class="card mb-4">
                <div class="card-header bg-success text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bullhorn"></i> Recent Announcements</h5>
                    <a href="announcements.php" class="btn btn-sm btn-light">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recentAnnouncements && $recentAnnouncements->num_rows > 0): ?>
                        <?php while ($announcement = $recentAnnouncements->fetch_assoc()): ?>
                            <div class="announcement-item border-bottom pb-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start">
                                    <span
                                        class="badge bg-<?php echo $announcement['priority'] === 'urgent' ? 'danger' : ($announcement['priority'] === 'important' ? 'warning' : 'info'); ?> mb-1">
                                        <?php echo ucfirst($announcement['priority']); ?>
                                    </span>
                                    <small
                                        class="text-muted"><?php echo date('M j', strtotime($announcement['created_at'])); ?></small>
                                </div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                <p class="mb-1 small">
                                    <?php echo htmlspecialchars(substr($announcement['content'], 0, 100)); ?>
                                    <?php echo strlen($announcement['content']) > 100 ? '...' : ''; ?>
                                </p>
                                <small class="text-muted">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($announcement['first_name'] . ' ' . $announcement['last_name']); ?>
                                    • <i class="fas fa-book"></i> <?php echo htmlspecialchars($announcement['subject_name']); ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="text-center py-3">
                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                            <p class="text-muted mb-0">No announcements yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row">
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-calendar-alt fa-3x text-success mb-3"></i>
                    <h5>My Schedule</h5>
                    <p class="text-muted">View your personal timetable and upcoming classes.</p>
                    <a href="schedule.php" class="btn btn-success">Open Schedule</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-chalkboard-user fa-3x text-success mb-3"></i>
                    <h5>Professor Directory</h5>
                    <p class="text-muted">Search and view professor profiles, subjects, and current location.</p>
                    <a href="teachers.php" class="btn btn-success">Browse Professors</a>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card mb-3 h-100">
                <div class="card-body text-center">
                    <i class="fas fa-book-open fa-3x text-success mb-3"></i>
                    <h5>Subjects & Classes</h5>
                    <p class="text-muted">Enroll in new subjects and manage your class enrollment.</p>
                    <a href="subjects.php" class="btn btn-success">Manage Subjects</a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../admin/footer.php'; ?>
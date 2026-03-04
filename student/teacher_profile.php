<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher modules
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\SessionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Teacher.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\includes\functions.php';

SessionManager::requireStudent();

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

$page_title = 'Teacher Profile';
require_once __DIR__ . '/header.php';

$id = $_GET['id'] ?? null;
if (!$id) {
    echo '<div class="container"><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Teacher not specified.</div></div>';
    require_once __DIR__ . '/../admin/footer.php';
    exit;
}

// REUSE existing Teacher class for consistency
$teacherObj = new Teacher($conn);
$teacher = $teacherObj->getById($id);

if (!$teacher) {
    echo '<div class="container"><div class="alert alert-danger"><i class="fas fa-exclamation-triangle"></i> Teacher not found.</div></div>';
    require_once __DIR__ . '/../admin/footer.php';
    exit;
}

// Get teacher's profile image from users table
$profileImageQuery = $conn->prepare('SELECT u.profile_image FROM users u JOIN teachers t ON u.id = t.user_id WHERE t.id = ?');
$profileImageQuery->bind_param('i', $id);
$profileImageQuery->execute();
$profileResult = $profileImageQuery->get_result();
$profileData = $profileResult->fetch_assoc();
$teacher['profile_image'] = $profileData['profile_image'] ?? null;

// Get subjects taught using existing pattern
$subQ = $conn->prepare('SELECT sub.subject_name, sub.subject_code FROM teacher_subjects ts JOIN subjects sub ON ts.subject_id = sub.id WHERE ts.teacher_id = ? AND ts.status = "active"');
$subQ->bind_param('i', $id);
$subQ->execute();
$subjects = $subQ->get_result();

// Get teacher's today schedule
$todaySchedule = getTeacherScheduleByDay($conn, $id);

?>

<div class="container-fluid">
    <!-- Back Button -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="teachers.php" class="btn btn-outline-primary">
                <i class="fas fa-arrow-left"></i> Back to Teacher Directory
            </a>
        </div>
    </div>

    <!-- Teacher Profile Card -->
    <div class="row">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body text-center">
                    <!-- Profile Image -->
                    <div class="mb-3">
                        <?php if (!empty($teacher['profile_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/Teacher Faculty Management website/assets/uploads/' . $teacher['profile_image'])): ?>
                            <img src="../assets/uploads/<?php echo htmlspecialchars($teacher['profile_image']); ?>"
                                class="rounded-circle border" width="120" height="120" alt="Profile"
                                style="object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-primary text-white rounded-circle mx-auto d-flex align-items-center justify-content-center"
                                style="width: 120px; height: 120px; font-size: 36px; font-weight: bold;">
                                <?php echo strtoupper(substr($teacher['first_name'], 0, 1) . substr($teacher['last_name'], 0, 1)); ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Name and Title -->
                    <h4 class="card-title mb-1">
                        <?php echo htmlspecialchars($teacher['first_name'] . ' ' . $teacher['last_name']); ?></h4>
                    <p class="text-muted mb-2">Teacher</p>
                    <span class="badge <?php echo getStatusBadgeClass($teacher['status']); ?>">
                        <?php echo ucfirst($teacher['status']); ?>
                    </span>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <!-- Contact Information -->
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-address-card"></i> Contact Information</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Email:</label>
                                <p class="mb-0">
                                    <a href="mailto:<?php echo htmlspecialchars($teacher['email']); ?>"
                                        class="text-decoration-none">
                                        <i class="fas fa-envelope"></i>
                                        <?php echo htmlspecialchars($teacher['email']); ?>
                                    </a>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Phone:</label>
                                <p class="mb-0">
                                    <i class="fas fa-phone"></i>
                                    <?php echo htmlspecialchars($teacher['phone'] ?? 'Not provided'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Department:</label>
                                <p class="mb-0">
                                    <i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($teacher['department'] ?? 'Not specified'); ?>
                                </p>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="fw-bold text-muted">Current Location:</label>
                                <p class="mb-0">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <?php
                                    $location = getCurrentTeacherLocation($conn, $id);
                                    if ($location !== '-'): ?>
                                        <span class="badge bg-success"><?php echo htmlspecialchars($location); ?></span>
                                    <?php else: ?>
                                        <span class="text-muted">Not currently in class</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Subjects Taught -->
            <div class="card shadow-sm mb-3">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-book"></i> Subjects Taught</h5>
                </div>
                <div class="card-body">
                    <?php if ($subjects->num_rows > 0): ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php while ($s = $subjects->fetch_assoc()): ?>
                                <span class="badge bg-primary fs-6 px-3 py-2">
                                    <?php echo htmlspecialchars($s['subject_code']) . ' - ' . htmlspecialchars($s['subject_name']); ?>
                                </span>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No subjects assigned</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="card shadow-sm">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="fas fa-calendar-day"></i> Today's Schedule
                        (<?php echo date('l, M d'); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if ($todaySchedule->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Time</th>
                                        <th>Subject</th>
                                        <th>Room</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($schedule = $todaySchedule->fetch_assoc()): ?>
                                        <tr>
                                            <td>
                                                <small class="fw-bold">
                                                    <?php echo formatTimeForDisplay($schedule['start_time']) . ' - ' . formatTimeForDisplay($schedule['end_time']); ?>
                                                </small>
                                            </td>
                                            <td><?php echo htmlspecialchars($schedule['subject_name']); ?></td>
                                            <td>
                                                <span class="badge bg-info text-dark">
                                                    <?php echo htmlspecialchars($schedule['room_name'] . ' (' . $schedule['room_number'] . ')'); ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No classes scheduled for today</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../admin/footer.php'; ?>
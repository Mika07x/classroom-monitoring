<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher modules
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\SessionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\Subject.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\includes\functions.php';

SessionManager::requireStudent();

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

// REUSE EXISTING SUBJECT CLASS - same as admin module
$subjectObj = new Subject($conn);

$page_title = 'Subjects & Enrollment';

$message = '';
$error = '';

// Create student_enrollments table if it doesn't exist
$createTable = "CREATE TABLE IF NOT EXISTS student_enrollments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    student_user_id INT NOT NULL,
    subject_id INT NOT NULL,
    enrollment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    status ENUM('enrolled', 'dropped') DEFAULT 'enrolled',
    academic_year VARCHAR(20),
    semester VARCHAR(20),
    FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE,
    UNIQUE KEY unique_enrollment (student_user_id, subject_id, academic_year)
)";
$conn->query($createTable);

$student_user_id = SessionManager::getUserId();
$current_academic_year = date('Y');

// Handle enrollment/unenrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject_id = $_POST['subject_id'] ?? null;
    $action = $_POST['action'] ?? null;

    if ($subject_id && $action) {
        if ($action === 'enroll') {
            // Check if already enrolled
            $checkStmt = $conn->prepare("SELECT id FROM student_enrollments WHERE student_user_id = ? AND subject_id = ? AND status = 'enrolled'");
            $checkStmt->bind_param('ii', $student_user_id, $subject_id);
            $checkStmt->execute();

            if ($checkStmt->get_result()->num_rows === 0) {
                $enrollStmt = $conn->prepare("INSERT INTO student_enrollments (student_user_id, subject_id, academic_year, semester) VALUES (?, ?, ?, '1')");
                $enrollStmt->bind_param('iis', $student_user_id, $subject_id, $current_academic_year);

                if ($enrollStmt->execute()) {
                    $message = 'Successfully enrolled in subject!';
                } else {
                    $error = 'Error enrolling in subject. Please try again.';
                }
                $enrollStmt->close();
            } else {
                $error = 'You are already enrolled in this subject.';
            }
            $checkStmt->close();

        } elseif ($action === 'unenroll') {
            $unenrollStmt = $conn->prepare("UPDATE student_enrollments SET status = 'dropped' WHERE student_user_id = ? AND subject_id = ?");
            $unenrollStmt->bind_param('ii', $student_user_id, $subject_id);

            if ($unenrollStmt->execute()) {
                $message = 'Successfully unenrolled from subject!';
            } else {
                $error = 'Error unenrolling from subject. Please try again.';
            }
            $unenrollStmt->close();
        }
    }
}

require_once __DIR__ . '/header.php';

// Get all active subjects with teacher information
$subjectsQuery = "SELECT s.*, 
    GROUP_CONCAT(CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ') as teachers,
    GROUP_CONCAT(t.id SEPARATOR ',') as teacher_ids,
    se.id as enrollment_id,
    se.status as enrollment_status
FROM subjects s 
LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id AND ts.status = 'active'
LEFT JOIN teachers t ON ts.teacher_id = t.id AND t.status = 'active'
LEFT JOIN student_enrollments se ON s.id = se.subject_id AND se.student_user_id = ? AND se.status = 'enrolled'
WHERE s.status = 'active'
GROUP BY s.id
ORDER BY s.subject_name";

$stmt = $conn->prepare($subjectsQuery);
$stmt->bind_param('i', $student_user_id);
$stmt->execute();
$subjects = $stmt->get_result();

?>

<div class="container-fluid">
    <!-- Back Button and Title -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="dashboard.php" class="btn btn-outline-success mb-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h3><i class="fas fa-book"></i> Available Subjects</h3>
            <p class="text-muted">Browse and enroll in subjects offered by our teachers</p>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Subjects Grid -->
    <div class="row">
        <?php if ($subjects->num_rows > 0): ?>
            <?php while ($subject = $subjects->fetch_assoc()): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-graduation-cap"></i>
                                <?php echo htmlspecialchars($subject['subject_code']); ?>
                            </h5>
                        </div>
                        <div class="card-body d-flex flex-column">
                            <h6 class="card-subtitle mb-2"><?php echo htmlspecialchars($subject['subject_name']); ?></h6>

                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-building"></i>
                                    <?php echo htmlspecialchars($subject['department'] ?? 'General'); ?>
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-credit-card"></i>
                                    <?php echo htmlspecialchars($subject['credits']); ?> Credits
                                </small>
                            </div>

                            <?php if (!empty($subject['description'])): ?>
                                <p class="card-text small text-muted">
                                    <?php echo htmlspecialchars(substr($subject['description'], 0, 100)) . (strlen($subject['description']) > 100 ? '...' : ''); ?>
                                </p>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="fw-bold text-muted small">Assigned Professor:</label>
                                <?php if (!empty($subject['teachers'])): ?>
                                    <div class="mt-1">
                                        <?php
                                        $teacherNames = explode(', ', $subject['teachers']);
                                        $teacherIds = explode(',', $subject['teacher_ids']);
                                        foreach ($teacherNames as $index => $teacherName):
                                            if (!empty(trim($teacherName))):
                                                ?>
                                                <a href="teacher_profile.php?id=<?php echo trim($teacherIds[$index]); ?>"
                                                    class="badge bg-success text-white text-decoration-none me-1">
                                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars(trim($teacherName)); ?>
                                                </a>
                                            <?php
                                            endif;
                                        endforeach;
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <small class="text-muted">No professor assigned yet</small>
                                <?php endif; ?>
                            </div>

                            <div class="mt-auto">
                                <?php if (!empty($subject['enrollment_id'])): ?>
                                    <!-- Already enrolled -->
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-success fs-6 px-3 py-2">
                                            <i class="fas fa-check-circle"></i> Enrolled
                                        </span>
                                        <form method="post" class="d-inline"
                                            onsubmit="return confirm('Are you sure you want to unenroll from this subject?');">
                                            <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                            <input type="hidden" name="action" value="unenroll">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-times"></i> Unenroll
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <!-- Not enrolled -->
                                    <form method="post" class="d-inline">
                                        <input type="hidden" name="subject_id" value="<?php echo $subject['id']; ?>">
                                        <input type="hidden" name="action" value="enroll">
                                        <button type="submit" class="btn btn-success w-100">
                                            <i class="fas fa-plus-circle"></i> Enroll Now
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div class="col-12">
                <div class="alert alert-info text-center">
                    <i class="fas fa-info-circle fa-2x mb-3"></i>
                    <h5>No Subjects Available</h5>
                    <p>There are currently no subjects available for enrollment. Please check back later.</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- My Enrollments Section -->
    <div class="row mt-5">
        <div class="col-12">
            <h4><i class="fas fa-bookmark"></i> My Current Enrollments</h4>
            <?php
            // Get student's current enrollments
            $enrollmentsQuery = "SELECT s.subject_code, s.subject_name, s.credits, s.department,
                GROUP_CONCAT(CONCAT(t.first_name, ' ', t.last_name) SEPARATOR ', ') as teachers,
                se.enrollment_date
                FROM student_enrollments se
                JOIN subjects s ON se.subject_id = s.id
                LEFT JOIN teacher_subjects ts ON s.id = ts.subject_id AND ts.status = 'active'
                LEFT JOIN teachers t ON ts.teacher_id = t.id AND t.status = 'active'
                WHERE se.student_user_id = ? AND se.status = 'enrolled'
                GROUP BY s.id
                ORDER BY se.enrollment_date DESC";

            $enrollStmt = $conn->prepare($enrollmentsQuery);
            $enrollStmt->bind_param('i', $student_user_id);
            $enrollStmt->execute();
            $enrollments = $enrollStmt->get_result();
            ?>

            <?php if ($enrollments->num_rows > 0): ?>
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> Enrolled Subjects</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Subject Code</th>
                                        <th>Subject Name</th>
                                        <th>Department</th>
                                        <th>Credits</th>
                                        <th>Professor</th>
                                        <th>Enrolled Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($enrollment = $enrollments->fetch_assoc()): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($enrollment['subject_code']); ?></strong>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['subject_name']); ?></td>
                                            <td><?php echo htmlspecialchars($enrollment['department'] ?? '-'); ?></td>
                                            <td><span
                                                    class="badge bg-success text-white"><?php echo $enrollment['credits']; ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars($enrollment['teachers'] ?? 'No teacher assigned'); ?>
                                            </td>
                                            <td><small
                                                    class="text-muted"><?php echo date('M d, Y', strtotime($enrollment['enrollment_date'])); ?></small>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-circle"></i> You are not currently enrolled in any subjects.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../admin/footer.php'; ?>
<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher modules
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Teacher.php';
require_once __DIR__ . '/../classes/Subject.php';
require_once __DIR__ . '/../includes/functions.php';

SessionManager::requireTeacher();

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

$message = '';
$error = '';

// Get teacher ID from user
$user_id = SessionManager::getUserId();
$teacherQuery = $conn->prepare("SELECT id FROM teachers WHERE user_id = ?");
$teacherQuery->bind_param('i', $user_id);
$teacherQuery->execute();
$teacherResult = $teacherQuery->get_result();

if ($teacherResult->num_rows === 0) {
    $error = 'Teacher profile not found.';
    $teacher_id = null;
} else {
    $teacher_id = $teacherResult->fetch_assoc()['id'];
}

// Create announcements table if it doesn't exist
$createTableQuery = "CREATE TABLE IF NOT EXISTS announcements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    teacher_id INT NOT NULL,
    subject_id INT NULL,
    target_audience ENUM('all_students', 'subject_students') DEFAULT 'subject_students',
    priority ENUM('low', 'normal', 'high', 'urgent') DEFAULT 'normal',
    status ENUM('draft', 'published', 'archived') DEFAULT 'published',
    publish_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (teacher_id) REFERENCES teachers(id) ON DELETE CASCADE,
    FOREIGN KEY (subject_id) REFERENCES subjects(id) ON DELETE CASCADE
)";
$conn->query($createTableQuery);

// Handle announcement submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $teacher_id) {
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $subject_id = !empty($_POST['subject_id']) ? $_POST['subject_id'] : NULL;
    $target_audience = $_POST['target_audience'] ?? 'subject_students';
    $priority = $_POST['priority'] ?? 'normal';

    if (!empty($title) && !empty($message)) {
        $stmt = $conn->prepare("INSERT INTO announcements (title, message, teacher_id, subject_id, target_audience, priority) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssiiss', $title, $message, $teacher_id, $subject_id, $target_audience, $priority);

        if ($stmt->execute()) {
            $message = 'Announcement sent successfully!';
        } else {
            $error = 'Error sending announcement. Please try again.';
        }
        $stmt->close();
    } else {
        $error = 'Please fill in all required fields.';
    }
}

// Get teacher's subjects
$subjectsQuery = "SELECT s.id, s.subject_name, s.subject_code 
                  FROM teacher_subjects ts 
                  JOIN subjects s ON ts.subject_id = s.id 
                  WHERE ts.teacher_id = ? AND ts.status = 'active'
                  ORDER BY s.subject_name";
$subjectsStmt = $conn->prepare($subjectsQuery);
$subjectsStmt->bind_param('i', $teacher_id);
$subjectsStmt->execute();
$teacherSubjects = $subjectsStmt->get_result();

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Send Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-graduation-cap"></i> CMS Professor Portal
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <span class="nav-link">Welcome,
                            <?php echo htmlspecialchars(SessionManager::getUsername()); ?></span>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content"
        style="margin-left: 280px; margin-top: 70px; padding: 30px; min-height: calc(100vh - 70px);">

        <div class="container-fluid">
            <!-- Back Button and Title -->
            <div class="row mb-3">
                <div class="col-12">
                    <a href="dashboard.php" class="btn btn-outline-primary mb-2">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                    <h3><i class="fas fa-bullhorn"></i> Send Announcements</h3>
                    <p class="text-muted">Send announcements to students enrolled in your subjects</p>
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

            <?php if ($teacher_id): ?>
                <div class="row">
                    <div class="col-lg-8">
                        <!-- Announcement Form -->
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h5 class="card-title mb-0"><i class="fas fa-edit"></i> Create New Announcement</h5>
                            </div>
                            <div class="card-body">
                                <form method="post">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Announcement Title *</label>
                                        <input type="text" class="form-control" id="title" name="title" required
                                            maxlength="255">
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="target_audience" class="form-label">Target Audience *</label>
                                            <select class="form-select" id="target_audience" name="target_audience"
                                                required>
                                                <option value="subject_students">Students of Selected Subject</option>
                                                <option value="all_students">All Students</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="priority" class="form-label">Priority</label>
                                            <select class="form-select" id="priority" name="priority">
                                                <option value="low">Low</option>
                                                <option value="normal" selected>Normal</option>
                                                <option value="high">High</option>
                                                <option value="urgent">Urgent</option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="mb-3" id="subject-selection">
                                        <label for="subject_id" class="form-label">Select Subject *</label>
                                        <select class="form-select" id="subject_id" name="subject_id">
                                            <option value="">Select a subject...</option>
                                            <?php while ($subject = $teacherSubjects->fetch_assoc()): ?>
                                                <option value="<?php echo $subject['id']; ?>">
                                                    <?php echo htmlspecialchars($subject['subject_code'] . ' - ' . $subject['subject_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>

                                    <div class="mb-3">
                                        <label for="message" class="form-label">Announcement Message *</label>
                                        <textarea class="form-control" id="message" name="message" rows="6" required
                                            placeholder="Type your announcement message here..."></textarea>
                                        <div class="form-text">Write a clear and detailed message for students.</div>
                                    </div>

                                    <div class="d-flex justify-content-between">
                                        <button type="button" class="btn btn-outline-secondary"
                                            onclick="document.querySelector('form').reset()">
                                            <i class="fas fa-undo"></i> Clear Form
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane"></i> Send Announcement
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <!-- Guidelines Card -->
                        <div class="card shadow-sm mb-3">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="fas fa-info-circle"></i> Guidelines</h6>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-light small mb-0">
                                    <h6>Priority Levels:</h6>
                                    <ul class="mb-2">
                                        <li><strong>Low:</strong> General information</li>
                                        <li><strong>Normal:</strong> Regular updates</li>
                                        <li><strong>High:</strong> Important notices</li>
                                        <li><strong>Urgent:</strong> Time-sensitive alerts</li>
                                    </ul>

                                    <h6>Best Practices:</h6>
                                    <ul class="mb-0">
                                        <li>Use clear, descriptive titles</li>
                                        <li>Keep messages concise but informative</li>
                                        <li>Choose appropriate priority levels</li>
                                        <li>Proofread before sending</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Announcements -->
                        <div class="card shadow-sm">
                            <div class="card-header">
                                <h6 class="card-title mb-0"><i class="fas fa-history"></i> Recent Announcements</h6>
                            </div>
                            <div class="card-body">
                                <?php
                                $recentQuery = "SELECT a.title, a.created_at, a.priority, s.subject_code 
                                   FROM announcements a 
                                   LEFT JOIN subjects s ON a.subject_id = s.id 
                                   WHERE a.teacher_id = ? 
                                   ORDER BY a.created_at DESC 
                                   LIMIT 5";
                                $recentStmt = $conn->prepare($recentQuery);
                                $recentStmt->bind_param('i', $teacher_id);
                                $recentStmt->execute();
                                $recent = $recentStmt->get_result();
                                ?>

                                <?php if ($recent->num_rows > 0): ?>
                                    <div class="list-group list-group-flush">
                                        <?php while ($announcement = $recent->fetch_assoc()): ?>
                                            <div class="list-group-item border-0 px-0 py-2">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <h6 class="mb-1 small">
                                                            <?php echo htmlspecialchars($announcement['title']); ?></h6>
                                                        <small class="text-muted">
                                                            <?php if ($announcement['subject_code']): ?>
                                                                <?php echo htmlspecialchars($announcement['subject_code']); ?>
                                                            <?php else: ?>
                                                                All Students
                                                            <?php endif; ?>
                                                            • <?php echo date('M d', strtotime($announcement['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                    <span class="badge bg-<?php
                                                    echo match ($announcement['priority']) {
                                                        'urgent' => 'danger',
                                                        'high' => 'warning',
                                                        'normal' => 'primary',
                                                        'low' => 'secondary'
                                                    };
                                                    ?> small">
                                                        <?php echo ucfirst($announcement['priority']); ?>
                                                    </span>
                                                </div>
                                            </div>
                                        <?php endwhile; ?>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small mb-0">No announcements sent yet.</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const targetAudience = document.getElementById('target_audience');
                const subjectSelection = document.getElementById('subject-selection');
                const subjectSelect = document.getElementById('subject_id');

                function toggleSubjectSelection() {
                    if (targetAudience.value === 'all_students') {
                        subjectSelection.style.display = 'none';
                        subjectSelect.required = false;
                        subjectSelect.value = '';
                    } else {
                        subjectSelection.style.display = 'block';
                        subjectSelect.required = true;
                    }
                }

                targetAudience.addEventListener('change', toggleSubjectSelection);
                toggleSubjectSelection(); // Initialize on page load
            });
        </script>

        <?php require_once __DIR__ . '/../admin/footer.php'; ?>
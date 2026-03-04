<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher modules
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\SessionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\includes\functions.php';

SessionManager::requireStudent();

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

$page_title = 'Announcements';

$student_user_id = SessionManager::getUserId();

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

// Create announcement_reads table if it doesn't exist
$createReadsTableQuery = "CREATE TABLE IF NOT EXISTS announcement_reads (
    id INT PRIMARY KEY AUTO_INCREMENT,
    announcement_id INT NOT NULL,
    student_user_id INT NOT NULL,
    read_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (announcement_id) REFERENCES announcements(id) ON DELETE CASCADE,
    FOREIGN KEY (student_user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_read (announcement_id, student_user_id)
)";
$conn->query($createReadsTableQuery);

// Handle mark as read
if (isset($_POST['mark_read'])) {
    $announcement_id = $_POST['announcement_id'];
    $markReadStmt = $conn->prepare("INSERT IGNORE INTO announcement_reads (announcement_id, student_user_id) VALUES (?, ?)");
    $markReadStmt->bind_param('ii', $announcement_id, $student_user_id);
    $markReadStmt->execute();
    $markReadStmt->close();
}

require_once __DIR__ . '/header.php';

// Get announcements for this student
$announcementsQuery = "SELECT DISTINCT a.*, 
    CONCAT(t.first_name, ' ', t.last_name) as teacher_name,
    s.subject_code, s.subject_name,
    ar.read_at as read_status
FROM announcements a
JOIN teachers t ON a.teacher_id = t.id
LEFT JOIN subjects s ON a.subject_id = s.id
LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id AND ar.student_user_id = ?
WHERE a.status = 'published' 
AND a.publish_date <= NOW()
AND (
    a.target_audience = 'all_students'
    OR (
        a.target_audience = 'subject_students' 
        AND a.subject_id IN (
            SELECT subject_id 
            FROM student_enrollments 
            WHERE student_user_id = ? AND status = 'enrolled'
        )
    )
)
ORDER BY 
    CASE a.priority 
        WHEN 'urgent' THEN 1
        WHEN 'high' THEN 2  
        WHEN 'normal' THEN 3
        WHEN 'low' THEN 4
    END,
    ar.read_at IS NULL DESC,
    a.publish_date DESC";

$stmt = $conn->prepare($announcementsQuery);
$stmt->bind_param('ii', $student_user_id, $student_user_id);
$stmt->execute();
$announcements = $stmt->get_result();

// Count unread announcements
$unreadQuery = "SELECT COUNT(*) as unread_count
FROM announcements a
WHERE a.status = 'published' 
AND a.publish_date <= NOW()
AND a.id NOT IN (
    SELECT announcement_id 
    FROM announcement_reads 
    WHERE student_user_id = ?
)
AND (
    a.target_audience = 'all_students'
    OR (
        a.target_audience = 'subject_students' 
        AND a.subject_id IN (
            SELECT subject_id 
            FROM student_enrollments 
            WHERE student_user_id = ? AND status = 'enrolled'
        )
    )
)";

$unreadStmt = $conn->prepare($unreadQuery);
$unreadStmt->bind_param('ii', $student_user_id, $student_user_id);
$unreadStmt->execute();
$unreadCount = $unreadStmt->get_result()->fetch_assoc()['unread_count'];

?>

<div class="container-fluid">
    <!-- Back Button and Title -->
    <div class="row mb-3">
        <div class="col-12">
            <a href="dashboard.php" class="btn btn-outline-success mb-2">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            <h3>
                <i class="fas fa-bullhorn"></i> Announcements
                <?php if ($unreadCount > 0): ?>
                    <span class="badge bg-danger ms-2"><?php echo $unreadCount; ?> unread</span>
                <?php endif; ?>
            </h3>
            <p class="text-muted">Important updates and notices from your professors</p>
        </div>
    </div>

    <!-- Filter Buttons -->
    <div class="row mb-3">
        <div class="col-12">
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-success active" data-filter="all">
                    <i class="fas fa-list"></i> All Announcements
                </button>
                <button type="button" class="btn btn-outline-warning" data-filter="unread">
                    <i class="fas fa-eye-slash"></i> Unread (<?php echo $unreadCount; ?>)
                </button>
                <button type="button" class="btn btn-outline-success" data-filter="read">
                    <i class="fas fa-check"></i> Read
                </button>
            </div>
        </div>
    </div>

    <!-- Announcements List -->
    <div class="row">
        <div class="col-12">
            <?php if ($announcements->num_rows > 0): ?>
                <?php while ($announcement = $announcements->fetch_assoc()): ?>
                    <div class="card mb-3 shadow-sm announcement-card <?php echo $announcement['read_status'] ? 'read' : 'unread'; ?>"
                        data-read-status="<?php echo $announcement['read_status'] ? 'read' : 'unread'; ?>">

                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <!-- Priority Badge -->
                                <span class="badge me-2 <?php
                                echo match ($announcement['priority']) {
                                    'urgent' => 'bg-danger',
                                    'high' => 'bg-warning text-dark',
                                    'normal' => 'bg-success',
                                    'low' => 'bg-secondary'
                                };
                                ?>">
                                    <?php echo ucfirst($announcement['priority']); ?>
                                </span>

                                <!-- Read Status -->
                                <?php if (!$announcement['read_status']): ?>
                                    <span class="badge bg-success me-2">NEW</span>
                                <?php endif; ?>

                                <!-- Title -->
                                <h5 class="card-title mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            </div>

                            <!-- Mark as Read Button -->
                            <?php if (!$announcement['read_status']): ?>
                                <form method="post" class="d-inline">
                                    <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                    <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success"
                                        title="Mark as read">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-success"
                                    title="Read on <?php echo date('M d, Y g:i A', strtotime($announcement['read_status'])); ?>">
                                    <i class="fas fa-check-circle"></i>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="card-body">
                            <!-- Teacher and Subject Info -->
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-user"></i> From:
                                    <strong><?php echo htmlspecialchars($announcement['teacher_name']); ?></strong>

                                    <?php if ($announcement['subject_code']): ?>
                                        <span class="ms-3">
                                            <i class="fas fa-book"></i> Subject:
                                            <span class="badge bg-light text-dark">
                                                <?php echo htmlspecialchars($announcement['subject_code'] . ' - ' . $announcement['subject_name']); ?>
                                            </span>
                                        </span>
                                    <?php else: ?>
                                        <span class="ms-3">
                                            <i class="fas fa-globe"></i>
                                            <span class="badge bg-light text-dark">General Announcement</span>
                                        </span>
                                    <?php endif; ?>

                                    <span class="ms-3">
                                        <i class="fas fa-clock"></i>
                                        <?php echo date('M d, Y g:i A', strtotime($announcement['publish_date'])); ?>
                                    </span>
                                </small>
                            </div>

                            <!-- Message -->
                            <div class="announcement-message">
                                <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Announcements</h5>
                        <p class="text-muted">You don't have any announcements at the moment. Check back later for updates
                            from your professors.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<style>
    .announcement-card.unread {
        border-left: 4px solid #1b5e20;
    }

    .announcement-card.read {
        opacity: 0.8;
    }

    .announcement-message {
        line-height: 1.6;
        font-size: 1rem;
    }

    .btn-group .btn.active {
        background-color: #1b5e20;
        border-color: #1b5e20;
        color: white;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const filterButtons = document.querySelectorAll('[data-filter]');
        const announcementCards = document.querySelectorAll('.announcement-card');

        filterButtons.forEach(button => {
            button.addEventListener('click', function () {
                // Remove active class from all buttons
                filterButtons.forEach(btn => btn.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');

                const filter = this.getAttribute('data-filter');

                announcementCards.forEach(card => {
                    const readStatus = card.getAttribute('data-read-status');

                    if (filter === 'all') {
                        card.style.display = 'block';
                    } else if (filter === 'unread' && readStatus === 'unread') {
                        card.style.display = 'block';
                    } else if (filter === 'read' && readStatus === 'read') {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        });

        // Auto-refresh page when marking as read to update UI
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function () {
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            });
        });
    });
</script>

<?php require_once __DIR__ . '/../admin/footer.php'; ?>
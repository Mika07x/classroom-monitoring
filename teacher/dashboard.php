<?php
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Teacher.php';

SessionManager::startTeacherSession();
if (!SessionManager::isLoggedIn() || !SessionManager::isTeacher()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

$userTeacherId = null;
$query = "SELECT id FROM teachers WHERE user_id = ?";
$stmt = $conn->prepare($query);
$user_id = SessionManager::getUserId();
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $userTeacherId = $row['id'];
}

$teacherObj = new Teacher($conn);
$currentAssignment = $userTeacherId ? $teacherObj->getCurrentAssignment($userTeacherId) : null;

// Dashboard counts and lists
$scheduleCount = 0;
$subjectCount = 0;
$roomCount = 0;
$todayClasses = [];
$upcomingClasses = [];

if ($userTeacherId) {
    // Total active schedules
    $q1 = "SELECT COUNT(*) as cnt FROM schedules WHERE teacher_id = ? AND status = 'active'";
    $st1 = $conn->prepare($q1);
    if ($st1) {
        $st1->bind_param('i', $userTeacherId);
        $st1->execute();
        $r1 = $st1->get_result()->fetch_assoc();
        $scheduleCount = intval($r1['cnt'] ?? 0);
    }

    // Assigned subjects
    $q2 = "SELECT COUNT(DISTINCT subject_id) as cnt FROM teacher_subjects WHERE teacher_id = ? AND status = 'active'";
    $st2 = $conn->prepare($q2);
    if ($st2) {
        $st2->bind_param('i', $userTeacherId);
        $st2->execute();
        $r2 = $st2->get_result()->fetch_assoc();
        $subjectCount = intval($r2['cnt'] ?? 0);
    }

    // Assigned rooms (distinct)
    $q3 = "SELECT COUNT(DISTINCT classroom_id) as cnt FROM schedules WHERE teacher_id = ? AND status = 'active'";
    $st3 = $conn->prepare($q3);
    if ($st3) {
        $st3->bind_param('i', $userTeacherId);
        $st3->execute();
        $r3 = $st3->get_result()->fetch_assoc();
        $roomCount = intval($r3['cnt'] ?? 0);
    }

    // Today's classes
    $today = date('l');
    $q4 = "SELECT s.day_of_week, ts.slot_name, ts.start_time, ts.end_time, sub.subject_name, c.room_number, c.room_name
           FROM schedules s
           JOIN time_slots ts ON s.time_slot_id = ts.id
           JOIN subjects sub ON s.subject_id = sub.id
           JOIN classrooms c ON s.classroom_id = c.id
           WHERE s.teacher_id = ? AND s.day_of_week = ? AND s.status = 'active'
           ORDER BY ts.start_time";
    $st4 = $conn->prepare($q4);
    if ($st4) {
        $st4->bind_param('is', $userTeacherId, $today);
        $st4->execute();
        $res4 = $st4->get_result();
        while ($row4 = $res4->fetch_assoc()) {
            $todayClasses[] = $row4;
        }
    }

    // Upcoming classes (this week) - order by weekday
    $q5 = "SELECT s.day_of_week, ts.slot_name, ts.start_time, ts.end_time, sub.subject_name, c.room_number, c.room_name
           FROM schedules s
           JOIN time_slots ts ON s.time_slot_id = ts.id
           JOIN subjects sub ON s.subject_id = sub.id
           JOIN classrooms c ON s.classroom_id = c.id
           WHERE s.teacher_id = ? AND s.status = 'active'
           ORDER BY FIELD(s.day_of_week,'Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'), ts.start_time
           LIMIT 10";
    $st5 = $conn->prepare($q5);
    if ($st5) {
        $st5->bind_param('i', $userTeacherId);
        $st5->execute();
        $res5 = $st5->get_result();
        while ($row5 = $res5->fetch_assoc()) {
            $upcomingClasses[] = $row5;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Professor Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
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
    <div class="main-content">
        <div class="page-header">
            <h1>Professor Dashboard</h1>
            <p>Welcome to your teaching portal</p>
        </div>
        <div class="container-fluid">

            <!-- Current Assignment Alert -->
            <?php if ($currentAssignment): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <h4 class="alert-heading">Current Class Assignment</h4>
                    <p class="mb-0">
                        <strong>Subject:</strong> <?php echo htmlspecialchars($currentAssignment['subject_name']); ?><br>
                        <strong>Room:</strong>
                        <?php echo htmlspecialchars($currentAssignment['room_number'] . ' - ' . $currentAssignment['room_name']); ?><br>
                        <strong>Time:</strong>
                        <?php echo htmlspecialchars($currentAssignment['start_time'] . ' - ' . $currentAssignment['end_time']); ?>
                    </p>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Quick Stats -->
            <div class="row mb-4">
                <div class="col-md-6 col-lg-4">
                    <div class="stat-card">
                        <div class="stat-icon">📅</div>
                        <div class="stat-number" id="scheduleCount"><?php echo htmlspecialchars($scheduleCount); ?>
                        </div>
                        <div class="stat-label">Total Classes This Week</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="stat-card">
                        <div class="stat-icon">📚</div>
                        <div class="stat-number" id="subjectCount"><?php echo htmlspecialchars($subjectCount); ?></div>
                        <div class="stat-label">Assigned Subjects</div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-4">
                    <div class="stat-card">
                        <div class="stat-icon">🏛️</div>
                        <div class="stat-number" id="roomCount"><?php echo htmlspecialchars($roomCount); ?></div>
                        <div class="stat-label">Assigned Rooms</div>
                    </div>
                </div>
            </div>

            <!-- Quick Links -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Quick Links</h5>
                        </div>
                        <div class="card-body">
                            <a href="schedule.php" class="btn btn-primary btn-sm mb-2 w-100">
                                <i class="fas fa-calendar-alt"></i> View Full Schedule
                            </a>
                            <a href="current.php" class="btn btn-primary btn-sm mb-2 w-100">
                                <i class="fas fa-info-circle"></i> Current Assignment Details
                            </a>
                            <a href="profile_edit.php" class="btn btn-primary btn-sm mb-2 w-100">
                                <i class="fas fa-user-edit"></i> Edit My Profile
                            </a>
                            <a href="timetable.php" class="btn btn-primary btn-sm mb-2 w-100">
                                <i class="fas fa-table"></i> Weekly Timetable
                            </a>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5>Today's Classes</h5>
                        </div>
                        <div class="card-body">
                            <div id="todayClasses">
                                <?php if (!empty($todayClasses)): ?>
                                    <ul class="list-group">
                                        <?php foreach ($todayClasses as $tc): ?>
                                            <li class="list-group-item">
                                                <strong><?php echo htmlspecialchars($tc['slot_name']); ?></strong>
                                                &nbsp;<?php echo htmlspecialchars(substr($tc['start_time'], 0, 5) . ' - ' . substr($tc['end_time'], 0, 5)); ?>
                                                <br>
                                                <?php echo htmlspecialchars($tc['subject_name']); ?> —
                                                <small><?php echo htmlspecialchars($tc['room_number'] . ' ' . $tc['room_name']); ?></small>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php else: ?>
                                    <div class="text-muted">No classes scheduled for today.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Classes -->
            <div class="card">
                <div class="card-header">
                    <h5>Upcoming Classes</h5>
                </div>
                <div class="card-body">
                    <div id="upcomingClasses">
                        <?php if (!empty($upcomingClasses)): ?>
                            <ul class="list-group">
                                <?php foreach ($upcomingClasses as $uc): ?>
                                    <li class="list-group-item">
                                        <strong><?php echo htmlspecialchars($uc['day_of_week']); ?></strong>
                                        &nbsp;<?php echo htmlspecialchars($uc['slot_name'] . ' (' . substr($uc['start_time'], 0, 5) . ')'); ?>
                                        <br>
                                        <?php echo htmlspecialchars($uc['subject_name']); ?> —
                                        <small><?php echo htmlspecialchars($uc['room_number'] . ' ' . $uc['room_name']); ?></small>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-muted">No upcoming classes scheduled.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
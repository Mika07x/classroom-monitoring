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

// Get current teacher ID
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Current Assignment</title>
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
                        <a class="nav-link" href="dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="schedule.php">My Schedule</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="current.php">Current Assignment</a>
                    </li>
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
        <div class="container-fluid">
            <div class="col-12">
                <div class="page-header">
                    <h1>Current Class Assignment</h1>
                    <p>Real-time information about your current class</p>
                </div>

                <?php if ($currentAssignment): ?>
                    <!-- Current Assignment Details -->
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-check-circle"></i> You are Currently in Class!</h4>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon">📚</div>
                                <div class="stat-number">
                                    <?php echo htmlspecialchars(substr($currentAssignment['subject_name'], 0, 20)); ?>
                                </div>
                                <div class="stat-label">Subject</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon">🏛️</div>
                                <div class="stat-number"><?php echo htmlspecialchars($currentAssignment['room_number']); ?>
                                </div>
                                <div class="stat-label">Room Number</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card">
                                <div class="stat-icon">⏰</div>
                                <div class="stat-number"><?php echo date('H:i'); ?></div>
                                <div class="stat-label">Current Time</div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Class Details</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Subject:</strong></td>
                                            <td><?php echo htmlspecialchars($currentAssignment['subject_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Room Number:</strong></td>
                                            <td><?php echo htmlspecialchars($currentAssignment['room_number']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Room Name:</strong></td>
                                            <td><?php echo htmlspecialchars($currentAssignment['room_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Time Slot:</strong></td>
                                            <td><?php echo htmlspecialchars($currentAssignment['slot_name']); ?></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5>Time Information</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Start Time:</strong></td>
                                            <td><?php echo htmlspecialchars($currentAssignment['start_time']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>End Time:</strong></td>
                                            <td><?php echo htmlspecialchars($currentAssignment['end_time']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Day:</strong></td>
                                            <td><?php echo htmlspecialchars($currentAssignment['day_of_week']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td><span class="badge badge-success">IN PROGRESS</span></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                <?php else: ?>
                    <!-- No Current Assignment -->
                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                        <h4 class="alert-heading"><i class="fas fa-info-circle"></i> No Current Assignment</h4>
                        <p>You do not have a class scheduled at this time. Check your full schedule for upcoming classes.
                        </p>
                        <a href="schedule.php" class="btn btn-sm btn-primary">View Full Schedule</a>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h5>Next Scheduled Class</h5>
                        </div>
                        <div class="card-body">
                            <?php
                            if ($userTeacherId) {
                                $query = "SELECT s.*, ts.slot_name, ts.start_time, ts.end_time, 
                                             sub.subject_name, c.room_number, c.room_name 
                                      FROM schedules s 
                                      JOIN time_slots ts ON s.time_slot_id = ts.id 
                                      JOIN subjects sub ON s.subject_id = sub.id 
                                      JOIN classrooms c ON s.classroom_id = c.id 
                                      WHERE s.teacher_id = ? AND s.status = 'active'
                                      AND (s.day_of_week = DAYNAME(DATE_ADD(NOW(), INTERVAL 1 DAY)) 
                                           OR (s.day_of_week = DAYNAME(NOW()) AND TIME(ts.end_time) > TIME(NOW())))
                                      ORDER BY FIELD(s.day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), ts.start_time
                                      LIMIT 1";
                                $stmt = $conn->prepare($query);
                                $stmt->bind_param("i", $userTeacherId);
                                $stmt->execute();
                                $result = $stmt->get_result();

                                if ($result->num_rows > 0) {
                                    $next = $result->fetch_assoc();
                                    ?>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Subject:</strong></td>
                                            <td><?php echo htmlspecialchars($next['subject_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Room:</strong></td>
                                            <td><?php echo htmlspecialchars($next['room_number'] . ' - ' . $next['room_name']); ?>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Day:</strong></td>
                                            <td><?php echo htmlspecialchars($next['day_of_week']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Time:</strong></td>
                                            <td><?php echo htmlspecialchars($next['start_time'] . ' - ' . $next['end_time']); ?>
                                            </td>
                                        </tr>
                                    </table>
                                    <?php
                                } else {
                                    echo '<p class="text-muted">No upcoming classes scheduled</p>';
                                }
                            }
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh current assignment every 30 seconds
        setTimeout(function () {
            location.reload();
        }, 30000);
    </script>
</body>

</html>
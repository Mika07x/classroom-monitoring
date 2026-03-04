<?php
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';

SessionManager::startTeacherSession();
if (!SessionManager::isLoggedIn() || !SessionManager::isTeacher()) {
    header('Location: ../login.php');
    exit;
}

$db = new Database();
$conn = $db->connect();

// Get teacher id
$userTeacherId = null;
$q = "SELECT id FROM teachers WHERE user_id = ?";
$stmt = $conn->prepare($q);
$user_id = SessionManager::getUserId();
$stmt->bind_param('i', $user_id);
$stmt->execute();
$res = $stmt->get_result();
if ($res->num_rows > 0) {
    $row = $res->fetch_assoc();
    $userTeacherId = $row['id'];
}

$message = '';

// Handle availability form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_availability'])) {
    $day = $_POST['day_of_week'];
    $time_slot_id = intval($_POST['time_slot_id']);
    $status = $_POST['status'];

    // Upsert logic
    $query = "INSERT INTO teacher_availability (teacher_id, day_of_week, time_slot_id, status) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = CURRENT_TIMESTAMP";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('isis', $userTeacherId, $day, $time_slot_id, $status);
    if ($stmt->execute()) {
        $message = 'Availability updated.';
    } else {
        $message = 'Failed to update availability: ' . $stmt->error;
    }
}

// Load time slots and existing availability
$timeSlots = $conn->query("SELECT id, slot_name, start_time, end_time FROM time_slots ORDER BY start_time");
$availability = [];
if ($userTeacherId) {
    $stmt2 = $conn->prepare("SELECT day_of_week, time_slot_id, status FROM teacher_availability WHERE teacher_id = ?");
    $stmt2->bind_param('i', $userTeacherId);
    $stmt2->execute();
    $res2 = $stmt2->get_result();
    while ($row2 = $res2->fetch_assoc()) {
        $availability[$row2['day_of_week']][$row2['time_slot_id']] = $row2['status'];
    }
}

$days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Availability</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php require_once __DIR__ . '/../includes/sidebar.php'; ?>
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
    <div class="main-content" style="margin-top: 90px !important; padding-top: 20px !important;">
        <div class="container mt-4">
            <h3>Set Availability</h3>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div><?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <form method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label">Day of Week</label>
                            <select name="day_of_week" class="form-select" required>
                                <?php foreach ($days as $d): ?>
                                    <option value="<?php echo $d; ?>"><?php echo $d; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Time Slot</label>
                            <select name="time_slot_id" class="form-select" required>
                                <?php while ($slot = $timeSlots->fetch_assoc()): ?>
                                    <option value="<?php echo $slot['id']; ?>">
                                        <?php echo htmlspecialchars($slot['slot_name'] . ' - ' . substr($slot['start_time'], 0, 5) . ' to ' . substr($slot['end_time'], 0, 5)); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <option value="available">Available</option>
                                <option value="unavailable">Unavailable</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <button type="submit" name="set_availability" class="btn btn-primary">Save
                                Availability</button>
                        </div>
                    </form>
                </div>
            </div>

            <h4>Your Availability</h4>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Day / Slot</th>
                                    <?php
                                    // reload slots for header
                                    $slotsHeader = $conn->query("SELECT id, slot_name FROM time_slots ORDER BY start_time");
                                    $slotIds = [];
                                    while ($s = $slotsHeader->fetch_assoc()) {
                                        $slotIds[] = $s['id'];
                                        echo '<th>' . htmlspecialchars($s['slot_name']) . '</th>';
                                    }
                                    ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($days as $d): ?>
                                    <tr>
                                        <td><strong><?php echo $d; ?></strong></td>
                                        <?php foreach ($slotIds as $sid): ?>
                                            <?php $st = $availability[$d][$sid] ?? 'available'; ?>
                                            <td
                                                class="text-center <?php echo ($st === 'available') ? 'table-success' : 'table-warning'; ?>">
                                                <?php echo htmlspecialchars(strtoupper($st)); ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
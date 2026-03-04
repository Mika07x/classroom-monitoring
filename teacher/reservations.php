<?php
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Reservation.php';

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

$resObj = new Reservation($conn);
$message = '';

// Handle reservation form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reserve'])) {
    $classroom_id = intval($_POST['classroom_id']);
    $reservation_date = $_POST['reservation_date'];
    $time_slot_id = intval($_POST['time_slot_id']);
    $notes = $_POST['notes'] ?? '';

    $result = $resObj->createReservation($userTeacherId, $classroom_id, $reservation_date, $time_slot_id, $notes);
    $message = $result['message'];
}

// Load classrooms and time slots
$rooms = $conn->query("SELECT id, room_number, room_name, capacity, room_type FROM classrooms WHERE status = 'active'");
$timeSlots = $conn->query("SELECT id, slot_name, start_time, end_time FROM time_slots WHERE status = 'active' ORDER BY start_time");

// Fetch teacher reservations
$myReservations = $resObj->getReservationsByTeacher($userTeacherId);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Room Reservations</title>
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
            <h3>Room Reservation</h3>
            <?php if ($message): ?>
                <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-body">
                    <for m method="POST" class="row g-3">
                        <div class="col-md-4">
                            <label for="reservation_date" class="form-label">Date</label>

                            <input type="date" id="reservation_date" name="reservation_date" class="form-control"
                                required>
                        </div>
                        <div class="col-md-4">
                            <label for="time_slot_id" class="form-label">Time Slot</label>
                            <select id="time_slot_id" name="time_slot_id" class="form-select" required>
                                <option value="">Select slot</option>
                                <?php while ($slot = $timeSlots->fetch_assoc()): ?>
                                    <option value="<?php echo $slot['id']; ?>">
                                        <?php echo htmlspecialchars($slot['slot_name'] . ' - ' . substr($slot['start_time'], 0, 5) . ' to ' . substr($slot['end_time'], 0, 5)); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="classroom_id" class="form-label">Classroom</label>
                            <select id="classroom_id" name="classroom_id" class="form-select" required>
                                <option value="">Select room</option>
                                <?php while ($r = $rooms->fetch_assoc()): ?>
                                    <option value="<?php echo $r['id']; ?>">
                                        <?php echo htmlspecialchars($r['room_number'] . ' - ' . $r['room_name'] . ' (' . $r['room_type'] . ')'); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="col-12">
                            <label for="notes" class="form-label">Notes (optional)</label>
                            <textarea id="notes" name="notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="col-12">
                            <button type="submit" name="reserve" class="btn btn-primary">Request Reservation</button>
                        </div>
                        </form>
                </div>
            </div>

            <h4>My Reservations</h4>
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Slot</th>
                                    <th>Room</th>
                                    <th>Notes</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($myReservations && $myReservations->num_rows > 0): ?>
                                    <?php while ($resv = $myReservations->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($resv['reservation_date']); ?></td>
                                            <td><?php echo htmlspecialchars($resv['slot_name']); ?></td>
                                            <td><?php echo htmlspecialchars($resv['room_number'] . ' - ' . $resv['room_name']); ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($resv['notes']); ?></td>
                                            <td><?php echo htmlspecialchars(strtoupper($resv['status'])); ?></td>
                                            <td><?php echo htmlspecialchars($resv['created_at']); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-muted">No reservations found</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
<?php
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Reservation.php';

SessionManager::requireAdmin();

$db = new Database();
$conn = $db->connect();
$resObj = new Reservation($conn);

$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['reservation_id'])) {
    $action = $_POST['action'];
    $reservation_id = intval($_POST['reservation_id']);
    if ($action === 'approve') {
        $result = $resObj->approveReservation($reservation_id);
        $message = $result['message'];
    } elseif ($action === 'reject') {
        $reason = $_POST['reason'] ?? '';
        $result = $resObj->rejectReservation($reservation_id, $reason);
        $message = $result['message'];
    }
}

// Load reservations (pending first)
$pending = $resObj->getAllReservations('pending');
$all = $resObj->getAllReservations();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Reservations</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/style.css" rel="stylesheet">
</head>

<body>
    <?php include 'header.php'; ?>

    <div class="main-content">
        <div class="page-header">
            <h2>Room Reservations</h2>
        </div>
        <?php if ($message): ?>
            <div class="alert alert-info"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <h4>Pending Requests</h4>
        <div class="card mb-4">
            <div class="card-body">
                <?php if ($pending && $pending->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Professor</th>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Slot</th>
                                    <th>Notes</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($r = $pending->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name'] . ' (' . $r['username'] . ')'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($r['room_number'] . ' - ' . $r['room_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['reservation_date']); ?></td>
                                        <td><?php echo htmlspecialchars($r['slot_name']); ?></td>
                                        <td><?php echo htmlspecialchars($r['notes']); ?></td>
                                        <td>
                                            <form method="POST" style="display:inline-block">
                                                <input type="hidden" name="reservation_id" value="<?php echo $r['id']; ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <button type="submit" class="btn btn-success btn-sm">Approve</button>
                                            </form>
                                            <button class="btn btn-danger btn-sm" data-bs-toggle="modal"
                                                data-bs-target="#rejectModal<?php echo $r['id']; ?>">Reject</button>

                                            <!-- Reject Modal -->
                                            <div class="modal fade" id="rejectModal<?php echo $r['id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Reject Reservation
                                                                    #<?php echo $r['id']; ?></h5>
                                                                <button type="button" class="btn-close"
                                                                    data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Reason (optional)</label>
                                                                    <textarea name="reason" class="form-control"></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <input type="hidden" name="reservation_id"
                                                                    value="<?php echo $r['id']; ?>">
                                                                <input type="hidden" name="action" value="reject">
                                                                <button type="button" class="btn btn-secondary"
                                                                    data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Reject</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No pending reservations.</p>
                <?php endif; ?>
            </div>
        </div>

        <h4>All Reservations</h4>
        <div class="card">
            <div class="card-body">
                <?php if ($all && $all->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Professor</th>
                                    <th>Room</th>
                                    <th>Date</th>
                                    <th>Slot</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($a = $all->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $a['id']; ?></td>
                                        <td><?php echo htmlspecialchars($a['first_name'] . ' ' . $a['last_name'] . ' (' . $a['username'] . ')'); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($a['room_number'] . ' - ' . $a['room_name']); ?></td>
                                        <td><?php echo htmlspecialchars($a['reservation_date']); ?></td>
                                        <td><?php echo htmlspecialchars($a['slot_name']); ?></td>
                                        <td><?php echo htmlspecialchars(strtoupper($a['status'])); ?></td>
                                        <td><?php echo htmlspecialchars($a['notes']); ?></td>
                                        <td><?php echo htmlspecialchars($a['created_at']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-muted">No reservations found.</p>
                <?php endif; ?>
            </div>
        </div>

    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
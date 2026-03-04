<?php
$page_title = 'Time Slots Management';
require_once __DIR__ . '/../config/Database.php';

$db = new Database();
$conn = $db->connect();

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $slot_name = $_POST['slot_name'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';

        if (!empty($slot_name) && !empty($start_time) && !empty($end_time)) {
            $query = "INSERT INTO time_slots (slot_name, start_time, end_time, status) VALUES (?, ?, ?, 'active')";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sss", $slot_name, $start_time, $end_time);

            if ($stmt->execute()) {
                $message = 'Time slot added successfully!';
                header('Refresh: 2; url=time-slots.php');
            } else {
                $error = 'Error adding time slot.';
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    } elseif ($action === 'edit' && $id) {
        $slot_name = $_POST['slot_name'] ?? '';
        $start_time = $_POST['start_time'] ?? '';
        $end_time = $_POST['end_time'] ?? '';
        $status = $_POST['status'] ?? 'active';

        if (!empty($slot_name) && !empty($start_time) && !empty($end_time)) {
            $query = "UPDATE time_slots SET slot_name = ?, start_time = ?, end_time = ?, status = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssssi", $slot_name, $start_time, $end_time, $status, $id);

            if ($stmt->execute()) {
                $message = 'Time slot updated successfully!';
                header('Refresh: 2; url=time-slots.php');
            } else {
                $error = 'Error updating time slot.';
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    } elseif ($action === 'delete' && $id) {
        $query = "DELETE FROM time_slots WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            $message = 'Time slot deleted successfully!';
            header('Refresh: 2; url=time-slots.php');
        } else {
            $error = 'Error deleting time slot.';
        }
    }
}

// Get time slot data for edit form
$editSlot = null;
if ($action === 'edit' && $id) {
    $query = "SELECT * FROM time_slots WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $editSlot = $stmt->get_result()->fetch_assoc();
}
?>

<?php require_once 'header.php'; ?>

<div class="main-content" style="margin-left: 280px; margin-top: 70px; padding: 30px; min-height: calc(100vh - 70px);">
    <div class="page-header">
        <div class="flex-between">
            <div>
                <h1>Time Slots Management</h1>
                <p>Configure class time slots</p>
            </div>
            <a href="time-slots.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Time Slot
            </a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($action === 'add' || $action === 'edit'): ?>
        <!-- Add/Edit Form -->
        <div class="card">
            <div class="card-header">
                <h5><?php echo ($action === 'add') ? 'Add New Time Slot' : 'Edit Time Slot'; ?></h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="form-group mb-3">
                        <label for="slot_name" class="form-label">Slot Name *</label>
                        <input type="text" class="form-control" id="slot_name" name="slot_name"
                            placeholder="e.g., Slot 1, Morning Session"
                            value="<?php echo $editSlot ? htmlspecialchars($editSlot['slot_name']) : ''; ?>" required>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="start_time" class="form-label">Start Time *</label>
                                <input type="time" class="form-control" id="start_time" name="start_time"
                                    value="<?php echo $editSlot ? htmlspecialchars($editSlot['start_time']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="end_time" class="form-label">End Time *</label>
                                <input type="time" class="form-control" id="end_time" name="end_time"
                                    value="<?php echo $editSlot ? htmlspecialchars($editSlot['end_time']) : ''; ?>"
                                    required>
                            </div>
                        </div>
                    </div>

                    <?php if ($action === 'edit'): ?>
                        <div class="form-group mb-3">
                            <label for="status" class="form-label">Status</label>
                            <select class="form-select" id="status" name="status">
                                <option value="active" <?php echo ($editSlot['status'] === 'active') ? 'selected' : ''; ?>>Active
                                </option>
                                <option value="inactive" <?php echo ($editSlot['status'] === 'inactive') ? 'selected' : ''; ?>>
                                    Inactive</option>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i>
                            <?php echo ($action === 'add') ? 'Add Time Slot' : 'Update Time Slot'; ?>
                        </button>
                        <a href="time-slots.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    <?php else: ?>
        <!-- Time Slots Table -->
        <div class="card">
            <div class="card-header">
                <h5>Time Slots List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Slot Name</th>
                                <th>Start Time</th>
                                <th>End Time</th>
                                <th>Duration</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $query = "SELECT * FROM time_slots ORDER BY start_time";
                            $result = $conn->query($query);

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    $start = new DateTime($row['start_time']);
                                    $end = new DateTime($row['end_time']);
                                    $duration = $start->diff($end)->format('%H:%I');
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($row['slot_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($row['start_time']); ?></td>
                                        <td><?php echo htmlspecialchars($row['end_time']); ?></td>
                                        <td><?php echo $duration; ?> hours</td>
                                        <td>
                                            <span
                                                class="badge badge-<?php echo ($row['status'] === 'active') ? 'success' : 'danger'; ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="time-slots.php?action=edit&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i> Edit
                                            </a>
                                            <a href="time-slots.php?action=delete&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No time slots found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    <?php endif; ?>

</div>

<style>
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
            padding: 20px !important;
        }
    }
</style>

<?php require_once 'footer.php'; ?>
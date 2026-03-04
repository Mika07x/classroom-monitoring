<?php
$page_title = 'Professor Management';
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Teacher.php';
require_once __DIR__ . '/../classes/User.php';

SessionManager::requireAdmin();

$db = new Database();
$conn = $db->connect();

$teacherObj = new Teacher($conn);
$userObj = new User($conn);

$action = $_GET['action'] ?? '';
$id = $_GET['id'] ?? null;
$message = '';
$error = '';

# Handle AJAX search requests
if (isset($_POST['ajax_search'])) {
    $search = $_POST['search'] ?? '';
    $status = $_POST['status'] ?? '';

    if ($search) {
        $result = $teacherObj->search($search);
    } else {
        $result = $teacherObj->getAll($status ?: null);
    }

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $location = getCurrentTeacherLocation($conn, $row['id']);
            ?>
            <tr>
                <td><?php echo $row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong></td>
                <td><?php echo htmlspecialchars($row['email']); ?></td>
                <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['department'] ?? '-'); ?></td>
                <td><?php echo htmlspecialchars($row['qualification'] ?? '-'); ?></td>
                <td>
                    <?php
                    if ($location !== '-') {
                        echo '<span class="badge badge-info">' . htmlspecialchars($location) . '</span>';
                    } else {
                        // Show current schedule info for debugging
                        $debugQuery = "SELECT c.room_name, c.room_number, ts.start_time, ts.end_time, s.day_of_week 
                                     FROM schedules s 
                                     JOIN classrooms c ON s.classroom_id = c.id 
                                     JOIN time_slots ts ON s.time_slot_id = ts.id 
                                     WHERE s.teacher_id = ? AND s.status = 'active'";
                        $debugStmt = $conn->prepare($debugQuery);
                        $debugStmt->bind_param('i', $row['id']);
                        $debugStmt->execute();
                        $debugResult = $debugStmt->get_result();

                        if ($debugResult->num_rows > 0) {
                            $debugRow = $debugResult->fetch_assoc();
                            echo '<span class="text-muted">-</span><br>';
                            echo '<small class="text-warning">Next: ' . htmlspecialchars($debugRow['room_name']) . ' (' . $debugRow['day_of_week'] . ' ' . $debugRow['start_time'] . ')</small>';
                        } else {
                            echo '<span class="text-muted">No schedule</span>';
                        }
                    }
                    ?>
                </td>
                <td>
                    <span
                        class="badge badge-<?php echo ($row['status'] === 'active') ? 'success' : ($row['status'] === 'inactive' ? 'danger' : 'warning'); ?>">
                        <?php echo htmlspecialchars($row['status']); ?>
                    </span>
                </td>
                <td class="action-buttons">
                    <a href="teachers.php?action=view&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-eye"></i> View
                    </a>
                    <a href="teachers.php?action=delete&id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger"
                        onclick="return confirm('Are you sure?');">
                        <i class="fas fa-trash"></i> Delete
                    </a>
                </td>
            </tr>
            <?php
        }
    } else {
        echo '<tr><td colspan="9" class="text-center text-muted">No professor found</td></tr>';
    }
    exit; // Stop execution for AJAX requests
}

// Function to get current teacher location
function getCurrentTeacherLocation($conn, $teacher_id)
{
    $current_day = date('l'); // Monday, Tuesday, etc.
    $current_time = date('H:i:s');

    // Check scheduled classes first
    $scheduleQuery = "SELECT c.room_name, c.room_number, ts.start_time, ts.end_time, s.day_of_week 
                     FROM schedules s 
                     JOIN classrooms c ON s.classroom_id = c.id 
                     JOIN time_slots ts ON s.time_slot_id = ts.id 
                     WHERE s.teacher_id = ? AND s.day_of_week = ? AND s.status = 'active'";

    $stmt = $conn->prepare($scheduleQuery);
    $stmt->bind_param('is', $teacher_id, $current_day);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        // Convert times to comparable format
        $start_time = strtotime($row['start_time']);
        $end_time = strtotime($row['end_time']);
        $current_timestamp = strtotime($current_time);

        // Check if current time is within this slot (with 5 minute buffer)
        if ($current_timestamp >= ($start_time - 300) && $current_timestamp <= ($end_time + 300)) {
            return $row['room_name'] . ' (' . $row['room_number'] . ')';
        }
    }

    // Check room reservations if no scheduled class
    $reservationQuery = "SELECT c.room_name, c.room_number, ts.start_time, ts.end_time 
                        FROM room_reservations rr 
                        JOIN classrooms c ON rr.classroom_id = c.id 
                        JOIN time_slots ts ON rr.time_slot_id = ts.id
                        WHERE rr.teacher_id = ? AND rr.status = 'approved' AND rr.reservation_date = CURDATE()";

    $stmt = $conn->prepare($reservationQuery);
    $stmt->bind_param('i', $teacher_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $start_time = strtotime($row['start_time']);
        $end_time = strtotime($row['end_time']);
        $current_timestamp = strtotime($current_time);

        if ($current_timestamp >= ($start_time - 300) && $current_timestamp <= ($end_time + 300)) {
            return $row['room_name'] . ' (' . $row['room_number'] . ')';
        }
    }

    return '-';
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add') {
        $username = $_POST['username'] ?? '';
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $department = $_POST['department'] ?? '';
        $qualification = $_POST['qualification'] ?? '';
        $hire_date = $_POST['hire_date'] ?? '';
        $bio = $_POST['bio'] ?? '';

        if (!empty($username) && !empty($email) && !empty($password) && !empty($first_name) && !empty($last_name)) {
            // Create user first
            if ($userObj->register($username, $email, $password, 'teacher')) {
                // Get the newly created user ID
                $userResult = $conn->query("SELECT id FROM users WHERE username = '$username'");
                $userData = $userResult->fetch_assoc();
                $user_id = $userData['id'];

                // Add teacher record
                if ($teacherObj->add($user_id, $first_name, $last_name, $email, $phone, $department, $qualification, $hire_date, $bio)) {
                    $message = 'Teacher added successfully!';
                    header('Refresh: 2; url=teachers.php');
                } else {
                    $error = 'Error adding teacher record.';
                }
            } else {
                $error = 'Error creating user account.';
            }
        } else {
            $error = 'Please fill in all required fields.';
        }
    } elseif ($action === 'delete' && $id) {
        if ($teacherObj->delete($id)) {
            $message = 'Teacher deleted successfully!';
            header('Refresh: 2; url=teachers.php');
        } else {
            $error = 'Error deleting teacher.';
        }
    }
}

// Get teacher data for view
$viewTeacher = null;
if ($action === 'view' && $id) {
    $viewTeacher = $teacherObj->getById($id);
}
?>

<?php require_once 'header.php'; ?>

<style>
    .action-buttons {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
</style>

<div class="main-content">
    <div class="page-header">
        <div class="flex-between">
            <div>
                <h1>Professor Management</h1>
                <p>Manage professor records and information</p>
            </div>
            <a href="teachers.php?action=add" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Professor
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

    <?php if ($action === 'add'): ?>
        <!-- Add Form -->
        <div class="card">
            <div class="card-header">
                <h5>Add New Professor</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone" name="phone">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="password" class="form-label">Password *</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="department" class="form-label">Department</label>
                                <input type="text" class="form-control" id="department" name="department">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="qualification" class="form-label">Qualification</label>
                                <input type="text" class="form-control" id="qualification" name="qualification">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group mb-3">
                                <label for="hire_date" class="form-label">Hire Date</label>
                                <input type="date" class="form-control" id="hire_date" name="hire_date">
                            </div>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label for="bio" class="form-label">Bio</label>
                        <textarea class="form-control" id="bio" name="bio" rows="4"></textarea>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Add Professor
                        </button>
                        <a href="teachers.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($action === 'view' && $viewTeacher): ?>
        <!-- View Teacher Details -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5>Professor Details</h5>
                <a href="teachers.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Back to List
                </a>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">First Name</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($viewTeacher['first_name']); ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Last Name</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($viewTeacher['last_name']); ?></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($viewTeacher['email']); ?></p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Phone</label>
                            <p class="form-control-plaintext"><?php echo htmlspecialchars($viewTeacher['phone'] ?? '-'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Department</label>
                            <p class="form-control-plaintext">
                                <?php echo htmlspecialchars($viewTeacher['department'] ?? '-'); ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Qualification</label>
                            <p class="form-control-plaintext">
                                <?php echo htmlspecialchars($viewTeacher['qualification'] ?? '-'); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Hire Date</label>
                            <p class="form-control-plaintext">
                                <?php echo $viewTeacher['hire_date'] ? date('F j, Y', strtotime($viewTeacher['hire_date'])) : '-'; ?>
                            </p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Status</label>
                            <p class="form-control-plaintext">
                                <span
                                    class="badge badge-<?php echo ($viewTeacher['status'] === 'active') ? 'success' : ($viewTeacher['status'] === 'inactive' ? 'danger' : 'warning'); ?>">
                                    <?php echo ucfirst(htmlspecialchars($viewTeacher['status'])); ?>
                                </span>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Bio</label>
                    <p class="form-control-plaintext">
                        <?php echo $viewTeacher['bio'] ? nl2br(htmlspecialchars($viewTeacher['bio'])) : '-'; ?>
                    </p>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Registration Date</label>
                    <p class="form-control-plaintext">
                        <?php echo date('F j, Y g:i A', strtotime($viewTeacher['created_at'])); ?>
                    </p>
                </div>

                <div class="d-flex gap-2">
                    <a href="teachers.php?action=delete&id=<?php echo $viewTeacher['id']; ?>" class="btn btn-danger"
                        onclick="return confirm('Are you sure you want to delete this professor? This action cannot be undone.');">
                        <i class="fas fa-trash"></i> Delete Professor
                    </a>
                    <a href="teachers.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to List
                    </a>
                </div>
            </div>
        </div>

    <?php else: ?>
        <!-- Search and Filter -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" class="row g-3" id="searchForm">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="search" id="searchInput"
                            placeholder="Search by name, email, or department..."
                            value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>" autocomplete="off">
                        <div id="searchSuggestions" class="position-absolute bg-white border rounded shadow-sm"
                            style="display: none; z-index: 1000; max-height: 300px; overflow-y: auto; width: 100%;"></div>
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" name="status" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="active" <?php echo ($_GET['status'] ?? '') === 'active' ? 'selected' : ''; ?>>
                                Active</option>
                            <option value="inactive" <?php echo ($_GET['status'] ?? '') === 'inactive' ? 'selected' : ''; ?>>
                                Inactive</option>
                            <option value="on_leave" <?php echo ($_GET['status'] ?? '') === 'on_leave' ? 'selected' : ''; ?>>
                                On Leave</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="submit" class="btn btn-primary w-100">Search</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Teachers Table -->
        <div class="card">
            <div class="card-header">
                <h5>Professors List</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover" id="teachersTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Department</th>
                                <th>Qualification</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="teachersTableBody">
                            <?php
                            $search = $_GET['search'] ?? '';
                            $status = $_GET['status'] ?? '';

                            if ($search) {
                                $result = $teacherObj->search($search);
                            } else {
                                $result = $teacherObj->getAll($status ?: null);
                            }

                            if ($result->num_rows > 0) {
                                while ($row = $result->fetch_assoc()) {
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['phone'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['department'] ?? '-'); ?></td>
                                        <td><?php echo htmlspecialchars($row['qualification'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $location = getCurrentTeacherLocation($conn, $row['id']);
                                            if ($location !== '-') {
                                                echo '<span class="badge badge-info">' . htmlspecialchars($location) . '</span>';
                                            } else {
                                                // Show current schedule info for debugging
                                                $debugQuery = "SELECT c.room_name, c.room_number, ts.start_time, ts.end_time, s.day_of_week 
                                                         FROM schedules s 
                                                         JOIN classrooms c ON s.classroom_id = c.id 
                                                         JOIN time_slots ts ON s.time_slot_id = ts.id 
                                                         WHERE s.teacher_id = ? AND s.status = 'active'";
                                                $debugStmt = $conn->prepare($debugQuery);
                                                $debugStmt->bind_param('i', $row['id']);
                                                $debugStmt->execute();
                                                $debugResult = $debugStmt->get_result();

                                                if ($debugResult->num_rows > 0) {
                                                    $debugRow = $debugResult->fetch_assoc();
                                                    echo '<span class="text-muted">-</span><br>';
                                                    echo '<small class="text-warning">Next: ' . htmlspecialchars($debugRow['room_name']) . ' (' . $debugRow['day_of_week'] . ' ' . $debugRow['start_time'] . ')</small>';
                                                } else {
                                                    echo '<span class="text-muted">No schedule</span>';
                                                }
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <span
                                                class="badge badge-<?php echo ($row['status'] === 'active') ? 'success' : ($row['status'] === 'inactive' ? 'danger' : 'warning'); ?>">
                                                <?php echo htmlspecialchars($row['status']); ?>
                                            </span>
                                        </td>
                                        <td class="action-buttons">
                                            <a href="teachers.php?action=view&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <a href="teachers.php?action=delete&id=<?php echo $row['id']; ?>"
                                                class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="9" class="text-center text-muted">No professor found</td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const searchInput = document.getElementById('searchInput');
                const statusFilter = document.getElementById('statusFilter');
                const tableBody = document.getElementById('teachersTableBody');
                let searchTimeout;

                function performSearch() {
                    const searchTerm = searchInput.value.trim();
                    const status = statusFilter.value;

                    // Create FormData for AJAX request
                    const formData = new FormData();
                    formData.append('ajax_search', '1');
                    formData.append('search', searchTerm);
                    formData.append('status', status);

                    fetch('teachers.php', {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.text())
                        .then(data => {
                            tableBody.innerHTML = data;
                        })
                        .catch(error => {
                            console.error('Search error:', error);
                        });
                }

                // Real-time search on keyup with debounce
                searchInput.addEventListener('input', function () {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(performSearch, 300); // 300ms delay
                });

                // Search on status filter change
                statusFilter.addEventListener('change', function () {
                    performSearch();
                });
            });
        </script>

    <?php endif; ?>

</div>

<?php require_once 'footer.php'; ?>
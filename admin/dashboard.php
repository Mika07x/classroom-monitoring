<?php
$page_title = 'Admin Dashboard';
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';
require_once __DIR__ . '/../classes/Teacher.php';
require_once __DIR__ . '/../classes/Subject.php';
require_once __DIR__ . '/../classes/Classroom.php';
require_once __DIR__ . '/../classes/Schedule.php';

SessionManager::requireAdmin();

$db = new Database();
$conn = $db->connect();

$teacherObj = new Teacher($conn);
$subjectObj = new Subject($conn);
$classroomObj = new Classroom($conn);
$scheduleObj = new Schedule($conn);

// Get statistics
$teachersResult = $teacherObj->getAll('active');
$totalTeachers = $teachersResult->num_rows;

$subjectsResult = $subjectObj->getAll('active');
$totalSubjects = $subjectsResult->num_rows;

$classroomsResult = $classroomObj->getAll('active');
$totalClassrooms = $classroomsResult->num_rows;

$schedulesResult = $scheduleObj->getAll();
$totalSchedules = $schedulesResult->num_rows;
?>

<?php require_once 'header.php'; ?>

<div class="main-content">
    <div class="page-header">
        <h1>Dashboard</h1>
        <p>Welcome to Classroom Monitoring System</p>
    </div>

    <!-- Statistics Cards -->
    <div class="row">
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-chalkboard-teacher text-success"></i></div>
                <div class="stat-number"><?php echo $totalTeachers; ?></div>
                <div class="stat-label">Active Professors</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-book text-success"></i></div>
                <div class="stat-number"><?php echo $totalSubjects; ?></div>
                <div class="stat-label">Subjects</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-school text-success"></i></div>
                <div class="stat-number"><?php echo $totalClassrooms; ?></div>
                <div class="stat-label">Classrooms</div>
            </div>
        </div>
        <div class="col-md-6 col-lg-3">
            <div class="stat-card">
                <div class="stat-icon"><i class="fas fa-calendar-alt text-success"></i></div>
                <div class="stat-number"><?php echo $totalSchedules; ?></div>
                <div class="stat-label">Schedules</div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mt-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Quick Actions</h5>
                </div>
                <div class="card-body">
                    <a href="teachers.php?action=add" class="btn btn-primary btn-sm mb-2">
                        <i class="fas fa-plus"></i> Add New Professor
                    </a>
                    <a href="subjects.php?action=add" class="btn btn-primary btn-sm mb-2">
                        <i class="fas fa-plus"></i> Add New Subject
                    </a>
                    <a href="classrooms.php?action=add" class="btn btn-primary btn-sm mb-2">
                        <i class="fas fa-plus"></i> Add New Classroom
                    </a>
                    <a href="schedules.php?action=add" class="btn btn-primary btn-sm mb-2">
                        <i class="fas fa-plus"></i> Create Schedule
                    </a>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5>Current System Status</h5>
                </div>
                <div class="card-body">
                    <p><strong>System Status:</strong> <span class="badge badge-success">Active</span></p>
                    <p><strong>Current User:</strong> <?php echo htmlspecialchars(SessionManager::getUsername()); ?></p>
                    <p><strong>Login Time:</strong>
                        <?php echo isset($_SESSION['login_time']) ? date('Y-m-d H:i:s', $_SESSION['login_time']) : 'N/A'; ?>
                    </p>
                    <p><strong>Last Updated:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Teachers -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <div class="flex-between">
                        <h5>Recent Professors</h5>
                        <a href="teachers.php" class="btn btn-sm btn-primary">View All</a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Department</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $result = $teacherObj->getAll('active');
                                $count = 0;
                                while ($row = $result->fetch_assoc() and $count < 5) {
                                    $count++;
                                    ?>
                                    <tr>
                                        <td><?php echo $row['id']; ?></td>
                                        <td><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($row['email']); ?></td>
                                        <td><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></td>
                                        <td>
                                            <span
                                                class="badge badge-success"><?php echo htmlspecialchars($row['status']); ?></span>
                                        </td>
                                    </tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
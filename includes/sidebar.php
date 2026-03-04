<?php
// Sidebar component - displays role-based navigation
// Include at the top of pages: require_once __DIR__ . '/../includes/sidebar.php';

// Ensure user is logged in
if (!SessionManager::isLoggedIn()) {
    header('Location: ../login.php');
    exit;
}

// Get user role and profile
$user_role = SessionManager::getRole();
$user_name = SessionManager::getUsername();
$user_email = SessionManager::getUserEmail();
$user_id = SessionManager::getUserId();

// Get profile image from database if exists
$profile_image = 'https://via.placeholder.com/80?text=' . urlencode($user_name[0]);
$db = new Database();
$conn = $db->connect();
$stmt = $conn->prepare("SELECT profile_image FROM users WHERE id = ?");
if ($stmt) {
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if ($row['profile_image'] && file_exists(__DIR__ . '/../assets/uploads/' . $row['profile_image'])) {
            $profile_image = '../assets/uploads/' . $row['profile_image'];
        }
    }
}

// Determine active page
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <!-- Profile Section -->
    <div class="sidebar-profile">
        <img src="<?php echo htmlspecialchars($profile_image); ?>" alt="Profile" class="profile-avatar">
        <div class="profile-info">
            <h5 class="profile-name"><?php echo htmlspecialchars($user_name); ?></h5>
            <p class="profile-role">
                <span class="badge badge-role-<?php echo strtolower($user_role); ?>">
                    <?php echo htmlspecialchars(ucfirst($user_role)); ?>
                </span>
            </p>
            <small class="profile-email"><?php echo htmlspecialchars($user_email); ?></small>
        </div>
        <?php if ($user_role === 'admin'): ?>
            <a href="profile.php" class="btn-edit-profile" title="Edit Profile">
                <i class="fas fa-edit"></i>
            </a>
        <?php endif; ?>
    </div>

    <!-- Navigation Menu -->
    <nav class="sidebar-nav">
        <style>
            <!-- Navigation Menu 
            -->
        <nav class="sidebar-nav">
            <style>
                .navbar {
                    position: fixed !important;
                    top: 0 !important;
                    left: 0 !important;
                    right: 0 !important;
                    width: 100% !important;
                    height: 70px !important;
                    background: #0d3d1a !important;
                    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1) !important;
                    padding: 1rem 2rem !important;
                    z-index: 1050 !important;
                    margin: 0 !important;
                    border: none !important;
                    border-radius: 0 !important;
                    display: flex !important;
                    align-items: center !important;
                }

                .sidebar {
                    position: fixed !important;
                    left: 0 !important;
                    top: 0 !important;
                    width: 280px !important;
                    height: 100vh !important;
                    padding-top: 70px !important;
                    background: #e8f5e9 !important;
                    box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1) !important;
                    display: flex !important;
                    flex-direction: column !important;
                    z-index: 999 !important;
                    overflow-y: auto !important;
                    overflow-x: hidden !important;
                    box-sizing: border-box !important;
                }

                .sidebar-nav .nav-link {
                    color: #000000 !important;
                }

                .sidebar-nav .nav-link span {
                    color: #000000 !important;
                }

                .sidebar-nav .nav-link:hover {
                    background-color: #c8e6c9 !important;
                    color: #1b5e20 !important;
                }

                .sidebar-nav .nav-link.active {
                    background-color: #c8e6c9 !important;
                    color: #1b5e20 !important;
                }
            </style>
                <?php if ($user_role === 'admin'): ?>
                <!-- Admin Menu -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php"
                            class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-chart-line"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="teachers.php"
                            class="nav-link <?php echo ($current_page === 'teachers.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-chalkboard-user"></i>
                            <span>Professors</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="students.php"
                            class="nav-link <?php echo ($current_page === 'students.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-user-graduate"></i>
                            <span>Students</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="classrooms.php"
                            class="nav-link <?php echo ($current_page === 'classrooms.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-door-open"></i>
                            <span>Classrooms</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="schedules.php"
                            class="nav-link <?php echo ($current_page === 'schedules.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-calendar-alt"></i>
                            <span>Schedules</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="time-slots.php"
                            class="nav-link <?php echo ($current_page === 'time-slots.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-hourglass-start"></i>
                            <span>Time Slots</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reservations.php"
                            class="nav-link <?php echo ($current_page === 'reservations.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <span>Reservations</span>
                        </a>
                    </li>
                </ul>
                <?php elseif ($user_role === 'teacher'): ?>
                <!-- Teacher Menu -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="dashboard.php"
                            class="nav-link <?php echo ($current_page === 'dashboard.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="profile.php"
                            class="nav-link <?php echo ($current_page === 'profile.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-user-circle"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="schedule.php"
                            class="nav-link <?php echo ($current_page === 'schedule.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-calendar-alt"></i>
                            <span>My Schedule</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="reservations.php"
                            class="nav-link <?php echo ($current_page === 'reservations.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-door-open"></i>
                            <span>Room Reservation</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="availability.php"
                            class="nav-link <?php echo ($current_page === 'availability.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-clock"></i>
                            <span>Availability</span>
                        </a>
                </ul>
                <?php endif; ?>

            <?php if ($user_role === 'student'): ?>
                <!-- Student Menu -->
                <ul class="nav-menu">
                    <li class="nav-item">
                        <a href="../student/dashboard.php"
                            class="nav-link <?php echo ($current_dir === 'student' && $current_page === 'dashboard.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-home"></i>
                            <span>Classroom Schedule</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a href="../student/profile.php"
                            class="nav-link <?php echo ($current_dir === 'student' && $current_page === 'profile.php') ? 'active' : ''; ?>"
                            style="color: #000000 !important;">
                            <i class="fas fa-user-circle"></i>
                            <span>My Profile</span>
                        </a>
                    </li>
                </ul>
                <?php endif; ?>
        </nav>

        <!-- Sidebar Footer -->
        <div class="sidebar-footer">
            <a href="../login.php" class="btn-logout" onclick="return confirm('Are you sure you want to logout?');">
                <i class="fas fa-sign-out-alt"></i>
                <span>Logout</span>
            </a>
        </div>
</aside>

<!-- Sidebar Toggle Button (for mobile) -->
<button class="sidebar-toggle" id="sidebarToggle" title="Toggle Sidebar">
    <i class="fas fa-bars"></i>
</button>

<!-- Overlay (shown on mobile when sidebar is open) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>
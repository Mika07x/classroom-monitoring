<?php
require_once __DIR__ . '/../config/SessionManager.php';
require_once __DIR__ . '/../config/Database.php';

SessionManager::startTeacherSession();
SessionManager::requireLogin();
if (!SessionManager::isTeacher()) {
    header('Location: ../unauthorized.php');
    exit;
}

$db = new Database();
$conn = $db->connect();
$user_id = SessionManager::getUserId();
$message = '';
$error = '';

// Fetch current user info
$stmt = $conn->prepare("SELECT username, email, profile_image FROM users WHERE id = ?");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_result = $stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch teacher info
$stmt2 = $conn->prepare("SELECT id, first_name, last_name, phone, department FROM teachers WHERE user_id = ?");
$stmt2->bind_param('i', $user_id);
$stmt2->execute();
$teacher_result = $stmt2->get_result();
$teacher = $teacher_result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $first_name = $_POST['first_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $department = $_POST['department'] ?? '';
    $password = $_POST['password'] ?? '';

    // Validate
    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = 'First name, last name, and email are required.';
    } else {
        // Handle file upload
        $profile_image = $user['profile_image'];
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
            $file = $_FILES['profile_image'];
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, $allowed)) {
                $error = 'Only JPG, PNG, and GIF files are allowed.';
            } else {
                // Create uploads directory if doesn't exist
                $upload_dir = __DIR__ . '/../assets/uploads';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $new_name = 'profile_' . $user_id . '_' . time() . '.' . $ext;
                $upload_path = $upload_dir . '/' . $new_name;

                if (move_uploaded_file($file['tmp_name'], $upload_path)) {
                    // Delete old image if exists
                    if ($user['profile_image'] && file_exists($upload_dir . '/' . $user['profile_image'])) {
                        unlink($upload_dir . '/' . $user['profile_image']);
                    }
                    $profile_image = $new_name;
                } else {
                    $error = 'Failed to upload image.';
                }
            }
        }

        if (empty($error)) {
            // Update user info
            $update_user = $conn->prepare("UPDATE users SET email = ?, profile_image = ? WHERE id = ?");
            $update_user->bind_param('ssi', $email, $profile_image, $user_id);

            // Update password if provided
            if (!empty($password)) {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $update_user = $conn->prepare("UPDATE users SET email = ?, profile_image = ?, password = ? WHERE id = ?");
                $update_user->bind_param('sssi', $email, $profile_image, $hashed, $user_id);
            }

            // Update teacher info
            $update_teacher = $conn->prepare("UPDATE teachers SET first_name = ?, last_name = ?, phone = ?, department = ? WHERE user_id = ?");
            $update_teacher->bind_param('ssssi', $first_name, $last_name, $phone, $department, $user_id);

            if ($update_user->execute() && $update_teacher->execute()) {
                $message = 'Profile updated successfully!';
                // Refresh user data
                $stmt = $conn->prepare("SELECT username, email, profile_image FROM users WHERE id = ?");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $user_result = $stmt->get_result();
                $user = $user_result->fetch_assoc();

                // Refresh teacher data
                $stmt2 = $conn->prepare("SELECT id, first_name, last_name, phone, department FROM teachers WHERE user_id = ?");
                $stmt2->bind_param('i', $user_id);
                $stmt2->execute();
                $teacher_result = $stmt2->get_result();
                $teacher = $teacher_result->fetch_assoc();
            } else {
                $error = 'Failed to update profile.';
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - TFMS</title>
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
                    <li class="nav-item"><a class="nav-link" href="dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="profile_edit.php">Edit Profile</a></li>
                    <li class="nav-item"><a class="nav-link" href="schedule.php">My Schedule</a></li>
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
            <h2>Edit My Profile</h2>
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>
            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="card">
                            <div class="card-body">
                                <!-- Profile Image -->
                                <div class="mb-3">
                                    <label class="form-label">Profile Image</label>
                                    <div class="mb-2">
                                        <img src="<?php echo htmlspecialchars('../assets/uploads/' . ($user['profile_image'] ?: 'default.png')); ?>"
                                            alt="Profile"
                                            style="width:100px; height:100px; border-radius:50%; object-fit:cover;">
                                    </div>
                                    <input type="file" name="profile_image" class="form-control" accept="image/*">
                                    <small class="text-muted">Accepted formats: JPG, PNG, GIF (Max 5MB)</small>
                                </div>

                                <!-- Personal Info -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">First Name</label>
                                        <input type="text" name="first_name" class="form-control"
                                            value="<?php echo htmlspecialchars($teacher['first_name'] ?? ''); ?>"
                                            required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Last Name</label>
                                        <input type="text" name="last_name" class="form-control"
                                            value="<?php echo htmlspecialchars($teacher['last_name'] ?? ''); ?>"
                                            required>
                                    </div>
                                </div>

                                <!-- Contact Info -->
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email</label>
                                        <input type="email" name="email" class="form-control"
                                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Phone</label>
                                        <input type="tel" name="phone" class="form-control"
                                            value="<?php echo htmlspecialchars($teacher['phone'] ?? ''); ?>">
                                    </div>
                                </div>

                                <!-- Department -->
                                <div class="mb-3">
                                    <label class="form-label">Department</label>
                                    <input type="text" name="department" class="form-control"
                                        value="<?php echo htmlspecialchars($teacher['department'] ?? ''); ?>">
                                </div>

                                <!-- Username (read-only) -->
                                <div class="mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control"
                                        value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                </div>

                                <!-- Password -->
                                <div class="mb-3">
                                    <label class="form-label">New Password (leave blank to keep current)</label>
                                    <input type="password" name="password" class="form-control">
                                </div>

                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
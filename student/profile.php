<?php
// REUSE EXISTING SYSTEM PATTERNS - consistent with admin/teacher profile modules
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\SessionManager.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\config\Database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\classes\User.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '\includes\functions.php';

SessionManager::requireStudent();

// REUSE EXISTING DATABASE CONNECTION PATTERN
$db = new Database();
$conn = $db->connect();

// REUSE EXISTING USER CLASS - same as admin module
$userObj = new User($conn);

$page_title = 'My Profile';

$user_id = SessionManager::getUserId();
$success_message = '';
$error_message = '';

// Handle profile update form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Handle file upload
    $profile_image = null;
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
        $max_size = 5 * 1024 * 1024; // 5MB

        if (!in_array($_FILES['profile_image']['type'], $allowed_types)) {
            $error_message = 'Please upload a valid image file (JPEG, PNG, or GIF).';
        } elseif ($_FILES['profile_image']['size'] > $max_size) {
            $error_message = 'Image file size must be less than 5MB.';
        } else {
            $upload_dir = $_SERVER['DOCUMENT_ROOT'] . '/Teacher Faculty Management website/assets/uploads/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_extension = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
            $profile_image = 'profile_' . $user_id . '_' . time() . '.' . $file_extension;
            $upload_path = $upload_dir . $profile_image;

            if (!move_uploaded_file($_FILES['profile_image']['tmp_name'], $upload_path)) {
                $error_message = 'Failed to upload image. Please try again.';
                $profile_image = null;
            }
        }
    }

    // Validation
    if (empty($error_message)) {
        if (empty($username) || empty($email)) {
            $error_message = 'Username and email are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif (!empty($new_password) && $new_password !== $confirm_password) {
            $error_message = 'New password and confirmation do not match.';
        } elseif (!empty($new_password) && strlen($new_password) < 6) {
            $error_message = 'New password must be at least 6 characters long.';
        } else {
            // Check if current password is correct (if changing password)
            if (!empty($new_password)) {
                $stmt = $conn->prepare('SELECT password FROM users WHERE id = ?');
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_data = $result->fetch_assoc();

                if (!password_verify($current_password, $user_data['password'])) {
                    $error_message = 'Current password is incorrect.';
                }
            }

            if (empty($error_message)) {
                // Check if username/email already exists for other users
                $stmt = $conn->prepare('SELECT id FROM users WHERE (username = ? OR email = ?) AND id != ?');
                $stmt->bind_param('ssi', $username, $email, $user_id);
                $stmt->execute();
                $result = $stmt->get_result();

                if ($result->num_rows > 0) {
                    $error_message = 'Username or email already exists.';
                } else {
                    // Update user profile
                    if (!empty($new_password) && $profile_image) {
                        // Update with new password and image
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE users SET username = ?, email = ?, password = ?, profile_image = ?, updated_at = NOW() WHERE id = ?');
                        $stmt->bind_param('ssssi', $username, $email, $hashed_password, $profile_image, $user_id);
                    } elseif (!empty($new_password)) {
                        // Update with new password only
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare('UPDATE users SET username = ?, email = ?, password = ?, updated_at = NOW() WHERE id = ?');
                        $stmt->bind_param('sssi', $username, $email, $hashed_password, $user_id);
                    } elseif ($profile_image) {
                        // Update with new image only
                        $stmt = $conn->prepare('UPDATE users SET username = ?, email = ?, profile_image = ?, updated_at = NOW() WHERE id = ?');
                        $stmt->bind_param('sssi', $username, $email, $profile_image, $user_id);
                    } else {
                        // Update without changing password or image
                        $stmt = $conn->prepare('UPDATE users SET username = ?, email = ?, updated_at = NOW() WHERE id = ?');
                        $stmt->bind_param('ssi', $username, $email, $user_id);
                    }

                    if ($stmt->execute()) {
                        // Update session with new username
                        $_SESSION['username'] = $username;
                        $_SESSION['email'] = $email;
                        $success_message = 'Profile updated successfully!';
                    } else {
                        $error_message = 'Failed to update profile. Please try again.';
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/header.php';

// Use consistent query pattern with other modules
$stmt = $conn->prepare('SELECT username, email, status, created_at, profile_image FROM users WHERE id = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

// Get profile image path
$profile_image_url = 'https://via.placeholder.com/120?text=' . urlencode(substr($user['username'], 0, 1));
if (!empty($user['profile_image']) && file_exists($_SERVER['DOCUMENT_ROOT'] . '/Teacher Faculty Management website/assets/uploads/' . $user['profile_image'])) {
    $profile_image_url = '../assets/uploads/' . $user['profile_image'];
}

?>

<div class="container-fluid">
    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body text-center">
                    <img src="<?php echo htmlspecialchars($profile_image_url); ?>" class="rounded-circle mb-3"
                        width="120" height="120" style="object-fit: cover;">
                    <h5><?php echo htmlspecialchars($user['username']); ?></h5>
                    <p class="text-muted"><?php echo htmlspecialchars($user['email']); ?></p>
                    <span class="badge bg-success text-white">
                        <?php echo getRoleDisplayName('student'); ?>
                    </span>
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-user"></i> Profile Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Username:</strong>
                            <p><?php echo htmlspecialchars($user['username']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Email:</strong>
                            <p><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Status:</strong>
                            <p><span class="badge bg-success text-white">
                                    <?php echo ucfirst($user['status']); ?>
                                </span></p>
                        </div>
                        <div class="col-md-6">
                            <strong>Member Since:</strong>
                            <p><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Profile Update Form - Centered and Full Width -->
    <div class="row justify-content-center mt-4">
        <div class="col-lg-10 col-xl-8">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-edit"></i> Update Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <input type="hidden" name="update_profile" value="1">

                        <!-- Profile Image Upload -->
                        <div class="row mb-4">
                            <div class="col-12 text-center">
                                <div class="mb-3">
                                    <label class="form-label"><strong><i class="fas fa-camera"></i> Profile
                                            Picture</strong></label>
                                    <div class="d-flex flex-column align-items-center">
                                        <img id="imagePreview" src="<?php echo htmlspecialchars($profile_image_url); ?>"
                                            class="rounded-circle mb-3" width="100" height="100"
                                            style="object-fit: cover;">
                                        <div class="mb-2">
                                            <input type="file" class="form-control" id="profile_image"
                                                name="profile_image" accept="image/*" style="display: none;">
                                            <button type="button" class="btn btn-outline-success btn-sm"
                                                onclick="document.getElementById('profile_image').click();">
                                                <i class="fas fa-upload"></i> Choose New Picture
                                            </button>
                                        </div>
                                        <small class="text-muted">Maximum file size: 5MB. Supported formats: JPEG, PNG,
                                            GIF</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="username" class="form-label">Username <span
                                            class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="username" name="username"
                                        value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email Address <span
                                            class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email"
                                        value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                </div>
                            </div>
                        </div>

                        <hr>
                        <h6 class="mb-3"><i class="fas fa-lock"></i> Change Password (Optional)</h6>

                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="current_password"
                                        name="current_password">
                                    <small class="text-muted">Required only if changing password</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="new_password" name="new_password"
                                        minlength="6">
                                    <small class="text-muted">Minimum 6 characters</small>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm New Password</label>
                                    <input type="password" class="form-control" id="confirm_password"
                                        name="confirm_password" minlength="6">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <button type="submit" class="btn btn-success btn-lg">
                                <i class="fas fa-save"></i> Update Profile
                            </button>
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i> All fields marked with * are required
                            </small>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../admin/footer.php'; ?>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        const currentPassword = document.getElementById('current_password');
        const form = document.getElementById('profileForm');
        const profileImageInput = document.getElementById('profile_image');
        const imagePreview = document.getElementById('imagePreview');

        // Profile image preview
        profileImageInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                // Check file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert('File size must be less than 5MB');
                    this.value = '';
                    return;
                }

                // Check file type
                if (!file.type.match('image.*')) {
                    alert('Please select a valid image file');
                    this.value = '';
                    return;
                }

                const reader = new FileReader();
                reader.onload = function (e) {
                    imagePreview.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        });

        // Password validation
        function validatePasswords() {
            if (newPassword.value && newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }

        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);

        // Require current password when setting new password
        newPassword.addEventListener('input', function () {
            if (this.value) {
                currentPassword.required = true;
            } else {
                currentPassword.required = false;
                confirmPassword.value = '';
            }
        });

        // Form submission validation
        form.addEventListener('submit', function (e) {
            if (newPassword.value && !currentPassword.value) {
                e.preventDefault();
                alert('Please enter your current password to change your password.');
                currentPassword.focus();
                return false;
            }

            if (newPassword.value && newPassword.value !== confirmPassword.value) {
                e.preventDefault();
                alert('New password and confirmation do not match.');
                confirmPassword.focus();
                return false;
            }
        });
    });
</script>
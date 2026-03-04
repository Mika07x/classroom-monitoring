<?php
require_once __DIR__ . '/config/SessionManager.php';
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/classes/Teacher.php';
require_once __DIR__ . '/classes/User.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password.';
    } else {
        $db = new Database();
        $connection = $db->connect();

        $user = new User($connection);
        if ($user->login($username, $password)) {
            if ($user->role === 'admin') {
                SessionManager::startAdminSession();
            } elseif ($user->role === 'student') {
                SessionManager::startStudentSession();
            } else {
                SessionManager::startTeacherSession();
            }

            SessionManager::login($user->id, $user->username, $user->email, $user->role);

            if ($user->role === 'admin') {
                header('Location: admin/dashboard.php');
            } elseif ($user->role === 'student') {
                header('Location: student/dashboard.php');
            } else {
                header('Location: teacher/dashboard.php');
            }
            exit;
        } else {
            $error = 'Invalid username or password.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        body {
            background: #1b5e20;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(46, 125, 50, 0.06);
            overflow: hidden;
            width: 100%;
            max-width: 420px;
        }

        .login-header {
            background: #2e7d32;
            color: white;
            padding: 40px 20px;
            text-align: center;
        }

        .login-header h1 {
            margin: 0;
            font-size: 28px;
            font-weight: 700;
        }

        .header-icon {
            color: #ffffff;
            margin-right: 10px;
            font-size: 34px;
            vertical-align: middle;
        }

        .login-header p {
            margin: 8px 0 0 0;
            font-size: 14px;
            opacity: 0.95;
        }

        .login-body {
            padding: 40px 30px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-control {
            border: 1px solid #e0e0e0;
            padding: 12px 15px;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.18s ease-in-out;
        }

        .form-control:focus {
            border-color: #2e7d32;
            box-shadow: 0 0 0 0.2rem rgba(46, 125, 50, 0.08);
        }

        .btn-login {
            background: #2e7d32;
            border: none;
            padding: 12px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            transition: all 0.18s ease-in-out;
            width: 100%;
            color: white;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            background: #256b2a;
            box-shadow: 0 5px 20px rgba(46, 125, 50, 0.12);
        }

        .alert {
            border-radius: 8px;
            margin-bottom: 20px;
            border: none;
        }

        .login-footer {
            text-align: center;
            padding: 0 30px 30px;
            font-size: 13px;
            color: #666;
        }

        .demo-credentials {
            background: #f8fff9;
            border-left: 4px solid #81c784;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            font-size: 13px;
        }

        .demo-credentials strong {
            color: #2e7d32;
        }

        /* Modal styles match the login theme */
        .modal-content {
            border-radius: 15px;
        }

        .modal-header {
            background: #2e7d32;
            color: white;
            border-bottom: none;
        }

        .modal-header .btn-close {
            filter: invert(1);
        }

        .modal-body .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            font-size: 14px;
        }

        .modal-body .btn {
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-header">
            <h1><i class="fas fa-graduation-cap header-icon"></i> CMS</h1>
            <p>Classroom Monitoring System</p>
        </div>

        <div class="login-body">

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo htmlspecialchars($error); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation">
                <div class="form-group">
                    <label for="username" class="form-label">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                        placeholder="Enter your username" required>
                </div>

                <!-- <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input 
                        type="password" 
                        class="form-control" 
                        id="password" 
                        name="password" 
                        placeholder="Enter your password"
                        required>
                </div> -->

                <div class="mb-3 form-group">
                    <label for="password" class="form-label">Password</label>
                    <div class="input-group">
                        <input type="password" name="password" id="password" class="form-control"
                            placeholder="Enter password">
                        <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="d-flex justify-content-end mb-3">
                    <a href="#" data-bs-toggle="modal" data-bs-target="#forgotPasswordModal"
                        class="text-decoration-none" style="font-size: 13px; color:#2e7d32;">
                        Forgot Password?
                    </a>
                </div>

                <button type="submit" class="btn btn-primary btn-login">Sign In</button>
            </form>

            <div class="demo-credentials">
                <strong>Demo Credentials:</strong><br>
                <strong>Admin:</strong><br>
                Username: <code>admin</code><br>
                Password: <code>admin123</code>
            </div>
        </div>
    </div>

    <!-- In login.php, replace the modal with this simpler version -->
    <div class="modal fade" id="forgotPasswordModal" tabindex="-1" aria-labelledby="forgotPasswordModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="forgotPasswordModalLabel"><i class="fas fa-key me-2"></i>Forgot Password
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <p class="mb-3">Enter your email address to receive a password reset link.</p>
                    <form action="forgot-password.php" method="POST">
                        <div class="mb-3">
                            <input type="email" class="form-control" name="email" placeholder="Enter your email"
                                required>
                        </div>
                        <button type="submit" class="btn btn-success w-100">Send Reset Link</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Remove all the JavaScript at the bottom of the file -->

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const togglePassword = document.querySelector('#togglePassword');
        const password = document.querySelector('#password');
        const eyeIcon = document.querySelector('#eyeIcon');

        togglePassword.addEventListener('click', function () {
            const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
            password.setAttribute('type', type);

            eyeIcon.classList.toggle('bi-eye');
            eyeIcon.classList.toggle('bi-eye-slash');
        });
        document.getElementById('forgotPasswordForm').addEventListener('submit', function (e) {
            e.preventDefault();
            const email = document.getElementById('forgotEmail').value;
            const messageDiv = document.getElementById('forgotPasswordMessage');

            if (!email) {
                messageDiv.textContent = 'Please enter your email.';
                messageDiv.style.color = 'red';
                return;
            }

            fetch('forgot-password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'email=' + encodeURIComponent(email)
            })
                .then(response => response.text())
                .then(data => {
                    messageDiv.innerHTML = data;
                    messageDiv.style.color = 'green';
                })
                .catch(err => {
                    messageDiv.textContent = 'An error occurred. Try again.';
                    messageDiv.style.color = 'red';
                });
        });
    </script>
</body>

</html>
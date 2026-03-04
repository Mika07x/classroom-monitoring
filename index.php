<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Classroom Monitoring System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: #1b5e20;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .hero-container {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(46, 125, 50, 0.06);
            padding: 60px 40px;
            text-align: center;
            max-width: 600px;
        }

        .hero-icon {
            font-size: 80px;
            color: #2e7d32;
            margin-bottom: 20px;
        }

        h1 {
            color: #1b5e20;
            font-weight: 700;
            margin-bottom: 15px;
            font-size: 42px;
        }

        .subtitle {
            color: #2e7d32;
            font-size: 18px;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .features {
            text-align: left;
            margin: 40px 0;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .feature {
            padding: 15px;
            background: #f8fff9;
            border-radius: 10px;
            border-left: 4px solid #81c784;
        }

        .feature-icon {
            font-size: 24px;
            color: #4caf50;
            margin-right: 10px;
        }

        .feature-text {
            font-size: 14px;
            color: #2e7d32;
        }

        .cta-buttons {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        .btn-login {
            background: #2e7d32;
            border: none;
            padding: 12px 35px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            color: white;
            text-decoration: none;
            transition: all 0.18s ease-in-out;
        }

        .btn-login:hover {
            transform: translateY(-3px);
            background: #256b2a;
            box-shadow: 0 10px 20px rgba(46, 125, 50, 0.12);
            color: white;
        }

        .btn-docs {
            background: #ffffff;
            border: 2px solid #2e7d32;
            padding: 10px 35px;
            font-size: 16px;
            font-weight: 600;
            border-radius: 8px;
            color: #2e7d32;
            text-decoration: none;
            transition: all 0.18s ease-in-out;
        }

        .btn-docs:hover {
            background: #e8f5e9;
            color: #2e7d32;
        }

        .footer-text {
            margin-top: 40px;
            color: #6b8e6b;
            font-size: 13px;
        }
    </style>
</head>

<body>
    <div class="hero-container">
        <div class="hero-icon">
            <i class="fas fa-graduation-cap"></i>
        </div>
        <h1>CMS</h1>
        <p class="subtitle">Classroom Monitoring System</p>
        <p style="color: #555; margin-bottom: 25px;">
            A comprehensive solution for managing teachers, subjects, classrooms, and teaching schedules in educational
            institutions.
        </p>

        <div class="features">
            <div class="feature">
                <span class="feature-icon"><i class="fas fa-users-cog"></i></span>
                <span class="feature-text">Professor Management</span>
            </div>
            <div class="feature">
                <span class="feature-icon"><i class="fas fa-book"></i></span>
                <span class="feature-text">Subject Management</span>
            </div>
            <div class="feature">
                <span class="feature-icon"><i class="fas fa-door-open"></i></span>
                <span class="feature-text">Classroom Management</span>
            </div>
            <div class="feature">
                <span class="feature-icon"><i class="fas fa-calendar-alt"></i></span>
                <span class="feature-text">Schedule Management</span>
            </div>
            <div class="feature">
                <span class="feature-icon"><i class="fas fa-tasks"></i></span>
                <span class="feature-text">Assignment Tracking</span>
            </div>
            <div class="feature">
                <span class="feature-icon"><i class="fas fa-lock"></i></span>
                <span class="feature-text">Secure Access</span>
            </div>
        </div>

        <div class="cta-buttons">
            <a href="login.php" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </a>
        </div>

        <div class="footer-text">
            <p><strong>Demo Credentials:</strong> admin / admin123</p>
            <p>Version 1.0.0 | Classroom Management System</p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
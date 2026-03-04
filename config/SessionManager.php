<?php

class SessionManager
{
    // Separate session names for admin, teacher, and student
    const ADMIN_SESSION = 'TFMS_ADMIN_SESSION';
    const TEACHER_SESSION = 'TFMS_TEACHER_SESSION';
    const STUDENT_SESSION = 'TFMS_STUDENT_SESSION';

    public static function startAdminSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::ADMIN_SESSION);
            session_start();
        } elseif (session_name() !== self::ADMIN_SESSION) {
            session_write_close();
            session_name(self::ADMIN_SESSION);
            session_start();
        }
    }

    public static function startTeacherSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::TEACHER_SESSION);
            session_start();
        } elseif (session_name() !== self::TEACHER_SESSION) {
            session_write_close();
            session_name(self::TEACHER_SESSION);
            session_start();
        }
    }

    public static function startStudentSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_name(self::STUDENT_SESSION);
            session_start();
        } elseif (session_name() !== self::STUDENT_SESSION) {
            session_write_close();
            session_name(self::STUDENT_SESSION);
            session_start();
        }
    }

    public static function startSession()
    {
        // Start based on context
        if (strpos($_SERVER['PHP_SELF'], '/admin/') !== false) {
            self::startAdminSession();
        } elseif (strpos($_SERVER['PHP_SELF'], '/student/') !== false) {
            self::startStudentSession();
        } else {
            self::startTeacherSession();
        }
    }

    public static function login($user_id, $username, $email, $role)
    {
        // Start appropriate session based on role
        if ($role === 'admin') {
            self::startAdminSession();
        } elseif ($role === 'student') {
            self::startStudentSession();
        } else {
            self::startTeacherSession();
        }

        $_SESSION['user_id'] = $user_id;
        $_SESSION['username'] = $username;
        $_SESSION['email'] = $email;
        $_SESSION['role'] = $role;
        $_SESSION['login_time'] = time();
    }

    public static function logout()
    {
        // Get current session name before destroying
        $current_name = session_name();

        // Destroy current session
        session_destroy();

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                $current_name,
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }
    }

    public static function isLoggedIn()
    {
        self::ensureCorrectSession();
        return isset($_SESSION['user_id']);
    }

    public static function getUserId()
    {
        self::ensureCorrectSession();
        return $_SESSION['user_id'] ?? null;
    }

    public static function getUsername()
    {
        self::ensureCorrectSession();
        return $_SESSION['username'] ?? null;
    }

    public static function getUserEmail()
    {
        self::ensureCorrectSession();
        return $_SESSION['email'] ?? null;
    }

    public static function getRole()
    {
        self::ensureCorrectSession();
        return $_SESSION['role'] ?? null;
    }

    public static function isAdmin()
    {
        self::ensureCorrectSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }

    public static function isTeacher()
    {
        self::ensureCorrectSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'teacher';
    }

    public static function isStudent()
    {
        self::ensureCorrectSession();
        return isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'student';
    }

    public static function requireStudent()
    {
        // First, ensure the correct session based on context
        self::ensureCorrectSession();

        // Check if user is actually a student in their own session
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'student') {
            // Redirect to login if not student or unauthorized access
            header('Location: ../login.php');
            exit;
        }
    }

    public static function requireLogin()
    {
        self::ensureCorrectSession();
        if (!isset($_SESSION['user_id'])) {
            header('Location: /Teacher Faculty Management website/login.php');
            exit;
        }
    }

    public static function requireAdmin()
    {
        // First, ensure the correct session based on context
        self::ensureCorrectSession();

        // Check if user is actually an admin in their own session
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
            // Redirect to unauthorized page if teacher tries to access admin area
            header('Location: /Teacher Faculty Management website/unauthorized.php');
            exit;
        }
    }

    public static function requireTeacher()
    {
        self::ensureCorrectSession();
        if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'teacher') {
            header('Location: ../login.php');
            exit;
        }
    }

    // Helper method to ensure we're working with the correct session based on context
    private static function ensureCorrectSession()
    {
        // Determine context from the current page path
        $current_path = $_SERVER['PHP_SELF'] ?? '';

        // If in admin context, start admin session
        if (strpos($current_path, '/admin/') !== false) {
            self::startAdminSession();
        }
        // If in teacher context, start teacher session
        elseif (strpos($current_path, '/teacher/') !== false) {
            self::startTeacherSession();
        }
        // If in student context, start student session
        elseif (strpos($current_path, '/student/') !== false) {
            self::startStudentSession();
        }
        // For root pages like login.php, check current session or default to teacher
        else {
            if (session_status() === PHP_SESSION_NONE) {
                self::startTeacherSession();
            }
        }
    }
}
?>
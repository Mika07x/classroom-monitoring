<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';
require_once 'config/Database.php';

// Check if it's an AJAX request
$isAjax = isset($_SERVER['HTTP_X_REQUESTED_WITH']) && 
          strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email)) {
        $message = "Please enter your email.";
        if ($isAjax) {
            echo "<p style='color:red;'>$message</p>";
        } else {
            // Handle normal form submission
            header("Location: login.php?error=" . urlencode($message));
        }
        exit;
    }

    $db = new Database();
    $conn = $db->connect();

    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        $stmt->bind_result($userId);
        $stmt->fetch();

        $token = bin2hex(random_bytes(50));
        $expiry = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $update = $conn->prepare("UPDATE users SET reset_token=?, reset_token_expiry=? WHERE email=?");
        $update->bind_param("sss", $token, $expiry, $email);
        $update->execute();

        $mail = new PHPMailer(true);
        try {
            // Your existing mail configuration
            $mail->SMTPDebug = 0;
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'jhob.isaac@gmail.com';
            $mail->Password = 'ctvpmeyjudnpghdo';
            $mail->SMTPSecure = 'ssl';
            $mail->Port = 465;

            $mail->setFrom('jhob.isaac@gmail.com', 'CMS Support');
            $mail->addAddress($email);

                $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email from PHPMailer.';

            $mail->isHTML(true);
            $mail->Subject = 'CMS Password Reset';
            $resetLink = "http://localhost/Teacher-Faculty-Management-website/reset-password.php?token=$token";
            $mail->Body = "
                <p>Hi,</p>
                <p>We received a request to reset your password.</p>
                <p>Click the link below to reset your password (valid for 1 hour):</p>
                <p><a href='$resetLink'>$resetLink</a></p>
                <p>If you did not request this, please ignore this email.</p>
                <br>
                <p>CMS Team</p>
            ";

            $mail->send();
            $message = "If this email exists, a reset link has been sent. Check your inbox or spam folder.";
            
            if ($isAjax) {
                echo "<p style='color:green;'>$message</p>";
            } else {
                header("Location: login.php?success=" . urlencode($message));
            }
        } catch (Exception $e) {
            $message = "Mailer Error: {$mail->ErrorInfo}";
            if ($isAjax) {
                echo "<p style='color:red;'>$message</p>";
            } else {
                header("Location: login.php?error=" . urlencode($message));
            }
        }
    } else {
        $message = "If this email exists, a reset link has been sent. Check your inbox or spam folder.";
        if ($isAjax) {
            echo "<p style='color:green;'>$message</p>";
        } else {
            header("Location: login.php?success=" . urlencode($message));
        }
    }
} else {
    $message = "Invalid request.";
    if ($isAjax) {
        echo "<p style='color:red;'>$message</p>";
    } else {
        header("Location: login.php?error=" . urlencode($message));
    }
}
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php';

$mail = new PHPMailer(true);
try {
    $mail->SMTPDebug = 2;
    $mail->Debugoutput = 'html';

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'jhob.isaac@gmail.com';      // Gmail login
    $mail->Password = 'ctvpmeyjudnpghdo'; // App Password, no spaces
    $mail->SMTPSecure = 'ssl';
    $mail->Port = 465;

    $mail->setFrom('jhob.isaac@gmail.com', 'Test');
    $mail->addAddress('jhob.isaac@gmail.com');

    $mail->isHTML(true);
    $mail->Subject = 'Test Email';
    $mail->Body = 'This is a test email from PHPMailer.';

    $mail->send();
    echo "Email sent!";
} catch (Exception $e) {
    echo "Mailer Error: {$mail->ErrorInfo}";
}
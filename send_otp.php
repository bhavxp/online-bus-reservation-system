
<?php
session_start();

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$config = require __DIR__ . '/config/email_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verify_email.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Please enter a valid email address.');
}

// Check if email is already registered
$stmt = $conn->prepare(
    "SELECT id FROM customer_users WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    exit('This email is already registered. Please log in.');
}
$stmt->close();

// Check resend limit: 60 seconds
$stmt = $conn->prepare(
    "SELECT last_sent_at FROM email_otps WHERE email = ?"
);
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$previous = $result->fetch_assoc();
$stmt->close();

if ($previous) {
    $lastSent = strtotime($previous['last_sent_at']);

    if (time() - $lastSent < 60) {
        exit('Please wait 60 seconds before requesting another OTP.');
    }
}

// Generate a 6-digit OTP
$otp = (string) random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);

// Save OTP hash; OTP expires in 1 minutes
$stmt = $conn->prepare(
    "INSERT INTO email_otps
        (email, otp_hash, expires_at, attempts, last_sent_at, verified_at)
VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 MINUTE), 0, NOW(), NULL)
     ON DUPLICATE KEY UPDATE
        otp_hash = VALUES(otp_hash),
       expires_at = DATE_ADD(NOW(), INTERVAL 1 MINUTE),
        attempts = 0,
        last_sent_at = NOW(),
        verified_at = NULL"
);

$stmt->bind_param("ss", $email, $otpHash);

if (!$stmt->execute()) {
    exit('Could not save OTP. Please try again.');
}
$stmt->close();

// Send OTP using existing SMTP configuration
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_username'];
    $mail->Password   = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port       = $config['smtp_port'];

    $mail->CharSet = $config['charset'] ?? 'UTF-8';
    $mail->setFrom(
        $config['from_email'],
        $config['from_name']
    );

    $mail->addAddress($email);
    $mail->isHTML(true);
    $mail->Subject = 'OBRS Email Verification OTP';

    $mail->Body = "
        <h2>OBRS Email Verification</h2>
        <p>Your email verification OTP is:</p>
        <h1 style='letter-spacing:5px;'>$otp</h1>
        <p>This OTP expires in 1 minutes.</p>
        <p>If you did not request this code, ignore this email.</p>
    ";

    $mail->AltBody =
        "Your OBRS email verification OTP is $otp. It expires in 1 minutes.";

    $mail->send();

    $_SESSION['otp_email'] = $email;

    header('Location: verify_email.php');
    exit;

} catch (Exception $e) {
    // Do not leave an active OTP when email delivery fails
    $stmt = $conn->prepare(
        "DELETE FROM email_otps WHERE email = ?"
    );
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->close();

    error_log('OBRS OTP email error: ' . $mail->ErrorInfo);
    exit('Email could not be sent. Please check SMTP settings and try again.');
}
?>

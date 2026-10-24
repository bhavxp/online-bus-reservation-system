
<?php
session_start();

require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;

function otpSendRedirect(
    string $message,
    string $type = 'error',
    bool $showOtp = false
): never {
    $_SESSION['otp_flash_message'] = $message;
    $_SESSION['otp_flash_type'] = $type;

    if ($showOtp) {
        $_SESSION['otp_form_once'] = true;
    } else {
        unset($_SESSION['otp_form_once']);
    }

    header('Location: verify_email.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: verify_email.php');
    exit;
}

$email = trim($_POST['email'] ?? '');

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
    otpSendRedirect('Please enter a valid email address.');
}

// Check whether the email is already registered.
$stmt = $conn->prepare(
    'SELECT id FROM customer_users WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$alreadyRegistered = $stmt->get_result()->num_rows > 0;
$stmt->close();

if ($alreadyRegistered) {
    otpSendRedirect('This email is already registered. Please log in.');
}

// Enforce 60-second resend cooldown.
$stmt = $conn->prepare(
    'SELECT last_sent_at FROM email_otps WHERE email = ? LIMIT 1'
);
$stmt->bind_param('s', $email);
$stmt->execute();
$previous = $stmt->get_result()->fetch_assoc();
$stmt->close();

if ($previous && !empty($previous['last_sent_at'])) {
    $lastSent = strtotime($previous['last_sent_at']);

    if ($lastSent !== false && (time() - $lastSent) < 60) {
        $remaining = max(1, 60 - (time() - $lastSent));
        $_SESSION['otp_email'] = $email;

        otpSendRedirect(
            "Please wait {$remaining} seconds before requesting another OTP.",
            'error',
            true
        );
    }
}

$config = require __DIR__ . '/config/email_config.php';

$otp = (string) random_int(100000, 999999);
$otpHash = password_hash($otp, PASSWORD_DEFAULT);

// OTP expires after 5 minutes.
$stmt = $conn->prepare(
    "INSERT INTO email_otps
        (email, otp_hash, expires_at, attempts, last_sent_at, verified_at)
     VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 5 MINUTE), 0, NOW(), NULL)
     ON DUPLICATE KEY UPDATE
        otp_hash = VALUES(otp_hash),
        expires_at = DATE_ADD(NOW(), INTERVAL 5 MINUTE),
        attempts = 0,
        last_sent_at = NOW(),
        verified_at = NULL"
);

$stmt->bind_param('ss', $email, $otpHash);

if (!$stmt->execute()) {
    $stmt->close();
    otpSendRedirect('Could not save OTP. Please try again.');
}

$stmt->close();

$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = $config['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $config['smtp_username'];
    $mail->Password = $config['smtp_password'];
    $mail->SMTPSecure = $config['smtp_secure'];
    $mail->Port = (int) $config['smtp_port'];

    // Limit SMTP waiting time.
    $mail->Timeout = 10;
    $mail->Timelimit = 15;
    $mail->SMTPDebug = 0;

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
        <h1 style='letter-spacing:5px;'>{$otp}</h1>
        <p>This OTP expires in 5 minutes.</p>
        <p>If you did not request this code, ignore this email.</p>
    ";

    $mail->AltBody =
        "Your OBRS email verification OTP is {$otp}. It expires in 5 minutes.";

    $mail->send();

    $_SESSION['otp_email'] = $email;
    $_SESSION['otp_form_once'] = true;
    $_SESSION['otp_flash_message'] = 'OTP sent. Check your inbox and spam folder.';
    $_SESSION['otp_flash_type'] = 'success';

    header('Location: verify_email.php');
    exit;

} catch (\Throwable $e) {
    // Invalidate the OTP if sending fails.
    $stmt = $conn->prepare(
        'DELETE FROM email_otps WHERE email = ?'
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();

    error_log(
        'OBRS OTP email error: ' .
        ($mail->ErrorInfo ?: $e->getMessage())
    );

    otpSendRedirect(
        'Email could not be sent. Please try again later or check SMTP configuration.'
    );
}
?>

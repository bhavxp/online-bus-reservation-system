
<?php
session_start();
require_once __DIR__ . '/config/db_connect.php';

$message = $_SESSION['otp_flash_message'] ?? '';
$messageType = $_SESSION['otp_flash_type'] ?? 'info';
unset($_SESSION['otp_flash_message'], $_SESSION['otp_flash_type']);

$email = $_SESSION['otp_email'] ?? '';
$showOtpForm = !empty($_SESSION['otp_form_once']);
unset($_SESSION['otp_form_once']);

// Refresh ke baad email form dikhane ke liye OTP form one-time hai.
if (isset($_GET['change_email'])) {
    unset($_SESSION['otp_email'], $_SESSION['otp_form_once']);
    header('Location: verify_email.php');
    exit;
}

function otpRedirect(string $message, string $type = 'error', bool $showOtp = false): never
{
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

if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'verify') {

    $email = $_SESSION['otp_email'] ?? '';
    $otp = trim($_POST['otp'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        otpRedirect('Please enter your email and request a new OTP.');
    }

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        otpRedirect('Enter the valid 6-digit OTP.', 'error', true);
    }

    $stmt = $conn->prepare(
        "SELECT otp_hash, expires_at, attempts, verified_at
         FROM email_otps WHERE email = ? LIMIT 1"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) {
        otpRedirect('OTP not found. Please request a new OTP.');
    }

    if (!empty($row['verified_at'])) {
        $_SESSION['verified_registration_email'] = $email;
        unset($_SESSION['otp_email'], $_SESSION['otp_form_once']);
        header('Location: register.php');
        exit;
    }

    if ((int)$row['attempts'] >= 5) {
        otpRedirect('Too many attempts. Request a new OTP.');
    }

    if (strtotime($row['expires_at']) < time()) {
        otpRedirect('OTP expired. Please request a new OTP.');
    }

    if (password_verify($otp, $row['otp_hash'])) {
        $stmt = $conn->prepare(
            "UPDATE email_otps SET verified_at = NOW()
             WHERE email = ? AND verified_at IS NULL"
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->close();

        $_SESSION['verified_registration_email'] = $email;
        unset($_SESSION['otp_email'], $_SESSION['otp_form_once']);

        header('Location: register.php');
        exit;
    }

    $stmt = $conn->prepare(
        "UPDATE email_otps SET attempts = attempts + 1
         WHERE email = ?"
    );
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $stmt->close();

    otpRedirect('Incorrect OTP. Please try again.', 'error', true);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>OBRS Email Verification</title>

<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    font-family: Arial, sans-serif;
    color: #fff;
    background: linear-gradient(135deg, #101827, #172554, #312e81);
}

.card {
    width: 100%;
    max-width: 430px;
    padding: 32px;
    border: 1px solid rgba(255,255,255,.2);
    border-radius: 18px;
    background: rgba(255,255,255,.08);
    box-shadow: 0 15px 40px rgba(0,0,0,.25);
}

h2 { margin-top: 0; text-align: center; }
p { color: #d1d5db; line-height: 1.5; }
label { display: block; margin: 16px 0 8px; }

input {
    width: 100%;
    padding: 13px;
    border: 1px solid #64748b;
    border-radius: 8px;
    background: #fff;
    color: #111827;
    font-size: 16px;
}

button {
    width: 100%;
    margin-top: 18px;
    padding: 13px;
    border: 0;
    border-radius: 8px;
    color: white;
    background: #4f46e5;
    font-size: 16px;
    font-weight: bold;
    cursor: pointer;
}

button:hover { background: #4338ca; }

.message {
    padding: 12px;
    margin-bottom: 15px;
    border-radius: 8px;
    background: rgba(255,255,255,.12);
}

.message.error { border-left: 4px solid #f87171; }
.message.success { border-left: 4px solid #4ade80; }
a { color: #c7d2fe; }
.center { text-align: center; margin-top: 18px; }
</style>
</head>

<body>
<div class="card">
    <h2>Verify Your Email</h2>
    <p class="center">OBRS — Online Bus Reservation System</p>

    <?php if ($message !== ''): ?>
        <div class="message <?= htmlspecialchars($messageType, ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!$showOtpForm || !filter_var($email, FILTER_VALIDATE_EMAIL)): ?>

        <p>Enter your email address. We will send a 6-digit verification code.</p>

        <form method="POST" action="send_otp.php">
            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                autocomplete="email"
                maxlength="254"
                required
            >
            <button type="submit">Send OTP</button>
        </form>

    <?php else: ?>

        <p>
            Enter the 6-digit OTP sent to
            <strong><?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?></strong>.
            The code expires in 5 minutes.
        </p>

        <form method="POST" action="verify_email.php">
            <input type="hidden" name="action" value="verify">

            <label for="otp">6-Digit OTP</label>
            <input
                type="text"
                id="otp"
                name="otp"
                inputmode="numeric"
                pattern="[0-9]{6}"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="Enter OTP"
                required
            >

            <button type="submit">Verify OTP</button>
        </form>

        <form method="POST" action="send_otp.php">
            <input
                type="hidden"
                name="email"
                value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
            >
            <button type="submit">Resend OTP</button>
        </form>

        <p class="center">
            <a href="verify_email.php?change_email=1">Use a different email</a>
        </p>

    <?php endif; ?>

    <div class="center">
        <a href="user_login.php">Back to Login</a>
    </div>
</div>
</body>
</html>


<?php
session_start();
require_once __DIR__ . '/config/db_connect.php';

$message = '';
$messageType = '';

$email = $_SESSION['otp_email'] ?? '';
$showOtpForm = !empty($email);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Step A: Send OTP
    if ($action === 'send') {
        $email = trim($_POST['email'] ?? '');

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = 'Please enter a valid email address.';
            $messageType = 'error';
        } else {
            $_SESSION['otp_email'] = $email;

            // send_otp.php expects POST with email
            $_SESSION['otp_send_requested'] = true;

            // Submit email to OTP sender
            echo '<form id="otpForm" method="POST" action="send_otp.php">';
            echo '<input type="hidden" name="email" value="' .
                htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '">';
            echo '</form>';
            echo '<script>document.getElementById("otpForm").submit();</script>';
            exit;
        }
    }

    // Step B: Verify OTP
    if ($action === 'verify') {
        $email = $_SESSION['otp_email'] ?? '';
        $otp = trim($_POST['otp'] ?? '');
        $showOtpForm = true;

        if (!preg_match('/^[0-9]{6}$/', $otp)) {
            $message = 'Enter the valid 6-digit OTP.';
            $messageType = 'error';
        } else {
            $stmt = $conn->prepare(
                "SELECT otp_hash, expires_at, attempts, verified_at
                 FROM email_otps WHERE email = ?"
            );
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if (!$row) {
                $message = 'OTP not found. Please request a new OTP.';
                $messageType = 'error';
            } elseif (!empty($row['verified_at'])) {
                $_SESSION['verified_registration_email'] = $email;
                header('Location: register.php');
                exit;
            } elseif ((int)$row['attempts'] >= 5) {
                $message = 'Too many attempts. Request a new OTP.';
                $messageType = 'error';
            } elseif (strtotime($row['expires_at']) < time()) {
                $message = 'OTP expired. Please request a new OTP.';
                $messageType = 'error';
            } elseif (password_verify($otp, $row['otp_hash'])) {
                $stmt = $conn->prepare(
                    "UPDATE email_otps SET verified_at = NOW()
                     WHERE email = ?"
                );
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();

                $_SESSION['verified_registration_email'] = $email;

                header('Location: register.php');
                exit;
            } else {
                $stmt = $conn->prepare(
                    "UPDATE email_otps SET attempts = attempts + 1
                     WHERE email = ?"
                );
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $stmt->close();

                $message = 'Incorrect OTP. Please try again.';
                $messageType = 'error';
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

label {
    display: block;
    margin: 16px 0 8px;
}

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

a { color: #c7d2fe; }
.center { text-align: center; margin-top: 18px; }
</style>
</head>

<body>
<div class="card">
    <h2>Verify Your Email</h2>
    <p class="center">OBRS — Online Bus Reservation System</p>

    <?php if ($message !== ''): ?>
        <div class="message">
            <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <?php if (!$showOtpForm): ?>
        <p>Enter your email address. We will send a 6-digit verification code.</p>

        <form method="POST" action="verify_email.php">
            <input type="hidden" name="action" value="send">

            <label for="email">Email Address</label>
            <input
                type="email"
                id="email"
                name="email"
                placeholder="you@example.com"
                required
            >

            <button type="submit">Send OTP</button>
        </form>

    <?php else: ?>
        <p>Enter the 6-digit OTP sent to
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
                placeholder="Enter OTP"
                required
            >

            <button type="submit">Verify OTP</button>
        </form>

        <form method="POST" action="verify_email.php">
            <input type="hidden" name="action" value="send">
            <input type="hidden"
                   name="email"
                   value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>">
            <button type="submit">Resend OTP</button>
        </form>
    <?php endif; ?>

    <div class="center">
        <a href="user_login.php">Back to Login</a>
    </div>
</div>
</body>
</html>

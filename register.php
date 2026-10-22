<?php

session_start();


if (empty($_SESSION['verified_registration_email'])) {
    header('Location: verify_email.php');
    exit;
}


require_once 'config/db_connect.php';

$message = '';
$message_type = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $verifiedEmail = $_SESSION['verified_registration_email'];

if (strcasecmp($email, $verifiedEmail) !== 0) {
    $message = 'Please use the email address verified by OTP.';
    $message_type = 'error';
}
    $mobile = trim($_POST['mobile'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    /* =========================
       VALIDATION
    ========================= */

    if (
        $full_name === '' ||
        $email === '' ||
        $mobile === '' ||
        $username === '' ||
        $password === '' ||
        $confirm_password === ''
    ) {
        $message = 'Please fill all fields.';
        $message_type = 'danger';

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid email address.';
        $message_type = 'danger';

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $message = 'Mobile number must contain exactly 10 digits.';
        $message_type = 'danger';

    } elseif (!preg_match('/^[A-Za-z0-9_]{3,50}$/', $username)) {
        $message = 'Username must be 3-50 characters and contain only letters, numbers and underscore.';
        $message_type = 'danger';

    } elseif (strlen($password) < 8) {
        $message = 'Password must be at least 8 characters.';
        $message_type = 'danger';

    } elseif ($password !== $confirm_password) {
        $message = 'Passwords do not match.';
        $message_type = 'danger';

    } else {

        /* =========================
           CHECK EMAIL / USERNAME
        ========================= */

        $checkStmt = $conn->prepare("
            SELECT id
            FROM customer_users
            WHERE email = ? OR username = ?
            LIMIT 1
        ");

        if (!$checkStmt) {

            error_log(
                'Registration prepare error: ' .
                $conn->error
            );

            $message = 'Something went wrong. Please try again.';
            $message_type = 'danger';

        } else {

            $checkStmt->bind_param(
                "ss",
                $email,
                $username
            );

            $checkStmt->execute();

            $checkStmt->store_result();

            if ($checkStmt->num_rows > 0) {

                $message =
                    'Email or username already exists. Please use another one.';

                $message_type = 'warning';

                $checkStmt->close();

            } else {

                $checkStmt->close();

                /* =========================
                   PASSWORD HASH
                ========================= */

                $hashed_password = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                /* =========================
                   INSERT CUSTOMER
                ========================= */

                $insertStmt = $conn->prepare("
                    INSERT INTO customer_users
                    (
                        full_name,
                        email,
                        mobile,
                        username,
                        password
                    )
                    VALUES
                    (
                        ?,
                        ?,
                        ?,
                        ?,
                        ?
                    )
                ");

                if (!$insertStmt) {

                    error_log(
                        'Registration insert prepare error: ' .
                        $conn->error
                    );

                    $message =
                        'Unable to create account. Please try again.';

                    $message_type = 'danger';

                } else {

                    $insertStmt->bind_param(
                        "sssss",
                        $full_name,
                        $email,
                        $mobile,
                        $username,
                        $hashed_password
                    );

                    if ($insertStmt->execute()) {

    $insertStmt->close();

    $verifiedEmail = $_SESSION['verified_registration_email'];

    $deleteStmt = $conn->prepare(
        "DELETE FROM email_otps WHERE email = ?"
    );

    $deleteStmt->bind_param("s", $verifiedEmail);
    $deleteStmt->execute();
    $deleteStmt->close();

    unset($_SESSION['verified_registration_email']);
    unset($_SESSION['otp_email']);

    header('Location: user_login.php?registered=1');
    exit;

}

                     else {

                        error_log(
                            'Registration execute error: ' .
                            $insertStmt->error
                        );

                        $message =
                            'Unable to create account. Please try again.';

                        $message_type = 'danger';

                        $insertStmt->close();
                    }
                }
            }
        }
    }
}

?>

<!DOCTYPE html>
<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <meta
        name="robots"
        content="noindex, nofollow, noarchive"
    >

    <title>Create Account | OBRS</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            font-family:
                Arial,
                Helvetica,
                sans-serif;

            background:
                radial-gradient(
                    circle at top left,
                    #243b55,
                    #141e30 45%,
                    #080d17 100%
                );

            color: #ffffff;

            display: flex;
            align-items: center;
            justify-content: center;

            padding: 25px;
        }

        .register-container {
            width: 100%;
            max-width: 520px;
        }

        .register-card {

            background:
                rgba(255, 255, 255, 0.08);

            border:
                1px solid
                rgba(255, 255, 255, 0.15);

            backdrop-filter: blur(18px);

            border-radius: 24px;

            padding: 35px;

            box-shadow:
                0 25px 70px
                rgba(0, 0, 0, 0.45);
        }

        .logo {

            width: 58px;
            height: 58px;

            margin: 0 auto 18px;

            border-radius: 18px;

            display: flex;
            align-items: center;
            justify-content: center;

            font-size: 28px;

            background:
                linear-gradient(
                    135deg,
                    #00c6ff,
                    #0072ff
                );

            box-shadow:
                0 10px 30px
                rgba(0, 114, 255, 0.35);
        }

        h1 {

            text-align: center;

            margin: 0;

            font-size: 30px;
        }

        .subtitle {

            text-align: center;

            color: #b8c4d6;

            margin:
                10px 0 28px;

            font-size: 14px;
        }

        .form-group {
            margin-bottom: 17px;
        }

        label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            color: #dbe5f2;

            font-weight: 600;
        }

        input {

            width: 100%;

            padding: 13px 14px;

            border-radius: 12px;

            border:
                1px solid
                rgba(255,255,255,0.14);

            background:
                rgba(255,255,255,0.07);

            color: white;

            outline: none;

            font-size: 15px;

            transition: 0.2s;
        }

        input::placeholder {
            color: #8795aa;
        }

        input:focus {

            border-color: #00c6ff;

            background:
                rgba(255,255,255,0.1);

            box-shadow:
                0 0 0 3px
                rgba(0,198,255,0.12);
        }

        .password-wrapper {
            position: relative;
        }

        .password-wrapper input {
            padding-right: 50px;
        }

        .toggle-password {

            position: absolute;

            right: 12px;
            top: 50%;

            transform:
                translateY(-50%);

            border: none;

            background: none;

            color: #aebbd0;

            cursor: pointer;

            font-size: 18px;
        }

        .message {

            padding: 12px 14px;

            border-radius: 12px;

            margin-bottom: 20px;

            font-size: 14px;

            line-height: 1.4;
        }

        .message.danger {

            background:
                rgba(220, 53, 69, 0.15);

            border:
                1px solid
                rgba(220, 53, 69, 0.35);

            color: #ffb8bf;
        }

        .message.warning {

            background:
                rgba(255, 193, 7, 0.12);

            border:
                1px solid
                rgba(255, 193, 7, 0.3);

            color: #ffe69c;
        }

        .register-btn {

            width: 100%;

            border: none;

            padding: 14px;

            border-radius: 13px;

            background:
                linear-gradient(
                    135deg,
                    #00c6ff,
                    #0072ff
                );

            color: white;

            font-size: 16px;

            font-weight: 700;

            cursor: pointer;

            transition: 0.2s;

            margin-top: 5px;
        }

        .register-btn:hover {

            transform: translateY(-2px);

            box-shadow:
                0 12px 28px
                rgba(0, 114, 255, 0.35);
        }

        .login-link {

            text-align: center;

            margin-top: 22px;

            color: #aebbd0;

            font-size: 14px;
        }

        .login-link a {

            color: #00c6ff;

            text-decoration: none;

            font-weight: 700;
        }

        .login-link a:hover {
            text-decoration: underline;
        }

        .back-link {

            display: block;

            text-align: center;

            margin-top: 15px;

            color: #8997ab;

            text-decoration: none;

            font-size: 13px;
        }

        .back-link:hover {
            color: white;
        }

        @media (max-width: 600px) {

            body {
                padding: 15px;
            }

            .register-card {
                padding: 25px 20px;
            }

            h1 {
                font-size: 26px;
            }
        }

    </style>

</head>

<body>

<div class="register-container">

    <div class="register-card">

        <div class="logo">
            🚌
        </div>

        <h1>
            Create Account
        </h1>

        <p class="subtitle">
            Join OBRS and manage your bus bookings easily.
        </p>

        <?php if ($message !== ''): ?>

            <div
                class="message <?php echo htmlspecialchars(
                    $message_type,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>"
            >
                <?php echo htmlspecialchars(
                    $message,
                    ENT_QUOTES,
                    'UTF-8'
                ); ?>
            </div>

        <?php endif; ?>

        <form
            method="POST"
            action=""
            autocomplete="on"
        >

            <div class="form-group">

                <label for="full_name">
                    Full Name
                </label>

                <input
                    type="text"
                    id="full_name"
                    name="full_name"
                    placeholder="Enter your full name"
                    maxlength="100"
                    required
                    value="<?php echo htmlspecialchars(
                        $_POST['full_name'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

            </div>

            <div class="form-group">

                <label for="email">
                    Email Address
                </label>

                <input
                    type="email"
                    id="email"
                    name="email"
                    placeholder="Enter your email"
                    maxlength="150"
                    required
                    value="<?php echo htmlspecialchars(
                        $_POST['email'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

            </div>

            <div class="form-group">

                <label for="mobile">
                    Mobile Number
                </label>

                <input
                    type="tel"
                    id="mobile"
                    name="mobile"
                    placeholder="Enter 10-digit mobile number"
                    maxlength="10"
                    pattern="[0-9]{10}"
                    inputmode="numeric"
                    required
                    value="<?php echo htmlspecialchars(
                        $_POST['mobile'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

            </div>

            <div class="form-group">

                <label for="username">
                    Username
                </label>

                <input
                    type="text"
                    id="username"
                    name="username"
                    placeholder="Create a username"
                    maxlength="50"
                    required
                    value="<?php echo htmlspecialchars(
                        $_POST['username'] ?? '',
                        ENT_QUOTES,
                        'UTF-8'
                    ); ?>"
                >

            </div>

            <div class="form-group">

                <label for="password">
                    Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="password"
                        name="password"
                        placeholder="Minimum 8 characters"
                        minlength="8"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="password"
                    >
                        👁
                    </button>

                </div>

            </div>

            <div class="form-group">

                <label for="confirm_password">
                    Confirm Password
                </label>

                <div class="password-wrapper">

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Re-enter your password"
                        minlength="8"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        data-target="confirm_password"
                    >
                        👁
                    </button>

                </div>

            </div>

            <button
                type="submit"
                class="register-btn"
            >
                Create Account
            </button>

        </form>

        <div class="login-link">

            Already have an account?

            <a href="user_login.php">
                Login →
            </a>

        </div>

        <a
            href="index.php"
            class="back-link"
        >
            ← Back to OBRS
        </a>

    </div>

</div>

<script>

document
    .querySelectorAll('.toggle-password')
    .forEach(function(button) {

        button.addEventListener(
            'click',
            function() {

                const targetId =
                    this.getAttribute('data-target');

                const input =
                    document.getElementById(targetId);

                if (!input) {
                    return;
                }

                if (input.type === 'password') {

                    input.type = 'text';

                    this.textContent = '🙈';

                } else {

                    input.type = 'password';

                    this.textContent = '👁';
                }
            }
        );
    });

</script>

</body>

</html>
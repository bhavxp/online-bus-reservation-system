<?php

session_start();

require_once 'config/db_connect.php';

$message = '';
$message_type = '';

/* =========================
   REGISTERED MESSAGE
========================= */

if (isset($_GET['registered']) && $_GET['registered'] === '1') {
    $message = 'Account created successfully. Please login.';
    $message_type = 'success';
}

/* =========================
   LOGIN
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $login = trim($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($login === '' || $password === '') {

        $message = 'Please enter username/email and password.';
        $message_type = 'danger';

    } else {

        $stmt = $conn->prepare("
            SELECT
                id,
                full_name,
                email,
                mobile,
                username,
                password,
                status
            FROM customer_users
            WHERE username = ? OR email = ?
            LIMIT 1
        ");

        if (!$stmt) {

            error_log(
                'User login prepare error: ' .
                $conn->error
            );

            $message =
                'Something went wrong. Please try again.';

            $message_type = 'danger';

        } else {

            $stmt->bind_param(
                "ss",
                $login,
                $login
            );

            if (!$stmt->execute()) {

                error_log(
                    'User login execute error: ' .
                    $stmt->error
                );

                $message =
                    'Something went wrong. Please try again.';

                $message_type = 'danger';

                $stmt->close();

            } else {

                $result = $stmt->get_result();

                if ($result->num_rows !== 1) {

                    $message =
                        'Invalid username/email or password.';

                    $message_type = 'danger';

                    $stmt->close();

                } else {

                    $user = $result->fetch_assoc();

                    $stmt->close();

                    /* =========================
                       STATUS CHECK
                    ========================= */

                    if ($user['status'] !== 'Active') {

                        $message =
                            'Your account is currently blocked. Please contact support.';

                        $message_type = 'danger';

                    }

                    /* =========================
                       PASSWORD CHECK
                    ========================= */

                    elseif (
                        !password_verify(
                            $password,
                            $user['password']
                        )
                    ) {

                        $message =
                            'Invalid username/email or password.';

                        $message_type = 'danger';

                    } else {

                        /* =========================
                           SECURE SESSION
                        ========================= */

                        session_regenerate_id(true);

                        $_SESSION['user_id'] =
                            (int) $user['id'];

                        $_SESSION['user_name'] =
                            $user['full_name'];

                        $_SESSION['user_email'] =
                            $user['email'];

                        $_SESSION['user_mobile'] =
                            $user['mobile'];

                        $_SESSION['user_username'] =
                            $user['username'];

                        $_SESSION['user_logged_in'] =
                            true;
$redirect = $_GET['redirect'] ?? 'user_dashboard.php';

// Allow only local page names; prevent external redirects.
$allowedRedirects = [
    'user_dashboard.php',
    'help_support.php'
];

if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = 'user_dashboard.php';
}

header('Location: ' . $redirect);
exit;

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

    <title>Login | OBRS</title>

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

            color: white;

            display: flex;

            align-items: center;

            justify-content: center;

            padding: 20px;
        }

        .login-container {

            width: 100%;

            max-width: 440px;
        }

        .login-card {

            background:
                rgba(255,255,255,0.08);

            border:
                1px solid
                rgba(255,255,255,0.15);

            backdrop-filter:
                blur(18px);

            border-radius: 24px;

            padding: 35px;

            box-shadow:
                0 25px 70px
                rgba(0,0,0,0.45);
        }

        .logo {

            width: 60px;

            height: 60px;

            margin:
                0 auto 18px;

            border-radius: 18px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 29px;

            background:
                linear-gradient(
                    135deg,
                    #00c6ff,
                    #0072ff
                );

            box-shadow:
                0 10px 30px
                rgba(0,114,255,0.35);
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

        .message {

            padding: 12px 14px;

            border-radius: 12px;

            margin-bottom: 20px;

            font-size: 14px;

            line-height: 1.4;
        }

        .message.danger {

            background:
                rgba(220,53,69,0.15);

            border:
                1px solid
                rgba(220,53,69,0.35);

            color: #ffb8bf;
        }

        .message.success {

            background:
                rgba(25,135,84,0.15);

            border:
                1px solid
                rgba(25,135,84,0.35);

            color: #a8f0c8;
        }

        .form-group {

            margin-bottom: 18px;
        }

        label {

            display: block;

            margin-bottom: 7px;

            font-size: 14px;

            font-weight: 600;

            color: #dbe5f2;
        }

        input {

            width: 100%;

            padding: 14px;

            border-radius: 12px;

            border:
                1px solid
                rgba(255,255,255,0.14);

            background:
                rgba(255,255,255,0.07);

            color: white;

            outline: none;

            font-size: 15px;
        }

        input::placeholder {
            color: #8795aa;
        }

        input:focus {

            border-color: #00c6ff;

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

        .login-btn {

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

            margin-top: 4px;
        }

        .login-btn:hover {

            transform:
                translateY(-2px);

            box-shadow:
                0 12px 28px
                rgba(0,114,255,0.35);
        }

        .register-link {

            text-align: center;

            margin-top: 22px;

            color: #aebbd0;

            font-size: 14px;
        }

        .register-link a {

            color: #00c6ff;

            text-decoration: none;

            font-weight: 700;
        }

        .register-link a:hover {
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

            .login-card {
                padding: 28px 20px;
            }

            h1 {
                font-size: 26px;
            }
        }

    </style>

</head>

<body>

<div class="login-container">

    <div class="login-card">

        <div class="logo">
            🚌
        </div>

        <h1>
            Welcome Back
        </h1>

        <p class="subtitle">
            Login to your bus reservation account
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

                <label for="login">
                    Username or Email
                </label>

                <input
                    type="text"
                    id="login"
                    name="login"
                    placeholder="Enter username or email"
                    autocomplete="username"
                    required
                    value="<?php echo htmlspecialchars(
                        $_POST['login'] ?? '',
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
                        placeholder="Enter your password"
                        autocomplete="current-password"
                        required
                    >

                    <button
                        type="button"
                        class="toggle-password"
                        id="togglePassword"
                    >
                        👁
                    </button>

                </div>

            </div>

            <button
                type="submit"
                class="login-btn"
            >
                Login
            </button>

        </form>

        <div class="register-link">

            Don't have an account?

            <a href="register.php">
                Create Account
            </a>

        </div>

        <a
            href="index.php"
            class="back-link"
        >
            ← Back to Website
        </a>

    </div>

</div>

<script>

const togglePassword =
    document.getElementById('togglePassword');

const passwordInput =
    document.getElementById('password');

if (togglePassword && passwordInput) {

    togglePassword.addEventListener(
        'click',
        function() {

            if (passwordInput.type === 'password') {

                passwordInput.type = 'text';

                togglePassword.textContent = '🙈';

            } else {

                passwordInput.type = 'password';

                togglePassword.textContent = '👁';
            }
        }
    );
}

</script>

</body>

</html>
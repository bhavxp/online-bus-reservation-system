<?php

session_start();

require_once 'config/db_connect.php';

/* =========================
   AUTH CHECK
========================= */

if (
    !isset($_SESSION['user_logged_in']) ||
    $_SESSION['user_logged_in'] !== true ||
    !isset($_SESSION['user_id'])
) {
    header('Location: user_login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

$message = '';
$message_type = '';

/* =========================
   UPDATE PROFILE
========================= */

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $full_name = trim($_POST['full_name'] ?? '');
    $mobile = trim($_POST['mobile'] ?? '');

    if ($full_name === '' || $mobile === '') {

        $message = 'Please fill all required fields.';
        $message_type = 'danger';

    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {

        $message = 'Mobile number must contain exactly 10 digits.';
        $message_type = 'danger';

    } else {

        $stmt = $conn->prepare("
            UPDATE customer_users
            SET
                full_name = ?,
                mobile = ?
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            error_log(
                'Profile update prepare error: ' .
                $conn->error
            );

            $message = 'Unable to update profile.';
            $message_type = 'danger';

        } else {

            $stmt->bind_param(
                "ssi",
                $full_name,
                $mobile,
                $user_id
            );

            if ($stmt->execute()) {

                $_SESSION['user_name'] = $full_name;
                $_SESSION['user_mobile'] = $mobile;

                $message = 'Profile updated successfully.';
                $message_type = 'success';

            } else {

                error_log(
                    'Profile update error: ' .
                    $stmt->error
                );

                $message = 'Unable to update profile.';
                $message_type = 'danger';
            }

            $stmt->close();
        }
    }
}

/* =========================
   CHANGE PASSWORD
========================= */

if (
    $_SERVER['REQUEST_METHOD'] === 'POST' &&
    isset($_POST['change_password'])
) {

    $current_password =
        $_POST['current_password'] ?? '';

    $new_password =
        $_POST['new_password'] ?? '';

    $confirm_password =
        $_POST['confirm_password'] ?? '';

    if (
        $current_password === '' ||
        $new_password === '' ||
        $confirm_password === ''
    ) {

        $message =
            'Please fill all password fields.';

        $message_type = 'danger';

    } elseif (strlen($new_password) < 8) {

        $message =
            'New password must be at least 8 characters.';

        $message_type = 'danger';

    } elseif ($new_password !== $confirm_password) {

        $message =
            'New passwords do not match.';

        $message_type = 'danger';

    } else {

        $stmt = $conn->prepare("
            SELECT password
            FROM customer_users
            WHERE id = ?
            LIMIT 1
        ");

        if (!$stmt) {

            error_log(
                'Password profile prepare error: ' .
                $conn->error
            );

            $message =
                'Unable to change password.';

            $message_type = 'danger';

        } else {

            $stmt->bind_param(
                "i",
                $user_id
            );

            $stmt->execute();

            $result = $stmt->get_result();

            if ($result->num_rows !== 1) {

                $message =
                    'Unable to load account.';

                $message_type = 'danger';

                $stmt->close();

            } else {

                $account = $result->fetch_assoc();

                $stmt->close();

                if (
                    !password_verify(
                        $current_password,
                        $account['password']
                    )
                ) {

                    $message =
                        'Current password is incorrect.';

                    $message_type = 'danger';

                } elseif (
                    password_verify(
                        $new_password,
                        $account['password']
                    )
                ) {

                    $message =
                        'New password must be different from the current password.';

                    $message_type = 'danger';

                } else {

                    $new_hash =
                        password_hash(
                            $new_password,
                            PASSWORD_DEFAULT
                        );

                    $updatePassword =
                        $conn->prepare("
                            UPDATE customer_users
                            SET password = ?
                            WHERE id = ?
                            LIMIT 1
                        ");

                    if (!$updatePassword) {

                        error_log(
                            'Password update prepare error: ' .
                            $conn->error
                        );

                        $message =
                            'Unable to change password.';

                        $message_type = 'danger';

                    } else {

                        $updatePassword->bind_param(
                            "si",
                            $new_hash,
                            $user_id
                        );

                        if (
                            $updatePassword->execute()
                        ) {

                            session_regenerate_id(true);

                            $message =
                                'Password changed successfully.';

                            $message_type = 'success';

                        } else {

                            error_log(
                                'Password update error: ' .
                                $updatePassword->error
                            );

                            $message =
                                'Unable to change password.';

                            $message_type = 'danger';
                        }

                        $updatePassword->close();
                    }
                }
            }
        }
    }
}

/* =========================
   LOAD CUSTOMER PROFILE
========================= */

$stmt = $conn->prepare("
    SELECT
        id,
        full_name,
        email,
        mobile,
        username,
        status,
        created_at
    FROM customer_users
    WHERE id = ?
    LIMIT 1
");

if (!$stmt) {

    error_log(
        'Profile load prepare error: ' .
        $conn->error
    );

    exit('Unable to load profile.');

}

$stmt->bind_param(
    "i",
    $user_id
);

if (!$stmt->execute()) {

    error_log(
        'Profile load execute error: ' .
        $stmt->error
    );

    $stmt->close();

    exit('Unable to load profile.');
}

$result = $stmt->get_result();

if ($result->num_rows !== 1) {

    $stmt->close();

    session_unset();
    session_destroy();

    header('Location: user_login.php');
    exit;
}

$user = $result->fetch_assoc();

$stmt->close();

/* =========================
   STATUS CHECK
========================= */

if ($user['status'] !== 'Active') {

    session_unset();
    session_destroy();

    exit(
        'Your account is currently blocked. Please contact support.'
    );
}

/* =========================
   SAFE OUTPUT
========================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
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

    <title>My Profile | OBRS</title>

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

            color: #fff;

            background:
                radial-gradient(
                    circle at top left,
                    #243b55,
                    #141e30 45%,
                    #080d17 100%
                );

            padding: 30px 15px;
        }

        .container {

            width: 100%;

            max-width: 850px;

            margin: auto;
        }

        .topbar {

            display: flex;

            justify-content:
                space-between;

            align-items: center;

            margin-bottom: 25px;
        }

        .topbar a {

            color: #aebbd0;

            text-decoration: none;

            font-size: 14px;
        }

        .topbar a:hover {
            color: #fff;
        }

        h1 {

            margin: 0;

            font-size: 30px;
        }

        .card {

            background:
                rgba(255,255,255,0.07);

            border:
                1px solid
                rgba(255,255,255,0.12);

            border-radius: 22px;

            padding: 28px;

            margin-bottom: 22px;

            backdrop-filter:
                blur(16px);
        }

        .profile-header {

            display: flex;

            align-items: center;

            gap: 20px;

            margin-bottom: 25px;
        }

        .avatar {

            width: 75px;

            height: 75px;

            border-radius: 20px;

            display: flex;

            align-items: center;

            justify-content: center;

            font-size: 32px;

            background:
                linear-gradient(
                    135deg,
                    #00c6ff,
                    #0072ff
                );
        }

        .profile-header h2 {

            margin: 0 0 7px;

            font-size: 22px;
        }

        .profile-header p {

            margin: 0;

            color: #aebbd0;

            font-size: 14px;
        }

        .message {

            padding: 12px 14px;

            border-radius: 12px;

            margin-bottom: 20px;

            font-size: 14px;
        }

        .success {

            background:
                rgba(25,135,84,0.15);

            border:
                1px solid
                rgba(25,135,84,0.35);

            color: #9df0c0;
        }

        .danger {

            background:
                rgba(220,53,69,0.15);

            border:
                1px solid
                rgba(220,53,69,0.35);

            color: #ffb8bf;
        }

        .grid {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 18px;
        }

        .form-group {

            margin-bottom: 17px;
        }

        label {

            display: block;

            margin-bottom: 7px;

            color: #dbe5f2;

            font-size: 14px;

            font-weight: 600;
        }

        input {

            width: 100%;

            padding: 13px;

            border-radius: 12px;

            border:
                1px solid
                rgba(255,255,255,0.14);

            background:
                rgba(255,255,255,0.07);

            color: #fff;

            outline: none;

            font-size: 15px;
        }

        input:focus {

            border-color: #00c6ff;

            box-shadow:
                0 0 0 3px
                rgba(0,198,255,0.12);
        }

        input[readonly] {

            opacity: 0.65;

            cursor: not-allowed;
        }

        button {

            border: none;

            padding: 13px 20px;

            border-radius: 12px;

            background:
                linear-gradient(
                    135deg,
                    #00c6ff,
                    #0072ff
                );

            color: white;

            font-weight: 700;

            cursor: pointer;
        }

        button:hover {

            transform:
                translateY(-1px);

            box-shadow:
                0 10px 25px
                rgba(0,114,255,0.3);
        }

        .section-title {

            margin:
                0 0 20px;

            font-size: 20px;
        }

        .account-info {

            display: grid;

            grid-template-columns:
                1fr 1fr;

            gap: 15px;
        }

        .info-box {

            padding: 15px;

            border-radius: 13px;

            background:
                rgba(255,255,255,0.05);
        }

        .info-label {

            color: #8997ab;

            font-size: 12px;

            margin-bottom: 5px;
        }

        .info-value {

            font-size: 15px;

            word-break: break-word;
        }

        @media (max-width: 650px) {

            .grid,
            .account-info {

                grid-template-columns:
                    1fr;
            }

            .profile-header {

                align-items: flex-start;
            }

            .card {

                padding: 22px;
            }

            .topbar {

                align-items: flex-start;

                gap: 15px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="topbar">

        <a href="user_dashboard.php">
            ← Dashboard
        </a>

        <a href="user_logout.php">
            Logout
        </a>

    </div>

    <div class="card">

        <div class="profile-header">

            <div class="avatar">
                👤
            </div>

            <div>

                <h2>
                    <?php echo e($user['full_name']); ?>
                </h2>

                <p>
                    @<?php echo e($user['username']); ?>
                </p>

            </div>

        </div>

        <?php if ($message !== ''): ?>

            <div
                class="message <?php echo e($message_type); ?>"
            >
                <?php echo e($message); ?>
            </div>

        <?php endif; ?>

        <h3 class="section-title">
            Account Information
        </h3>

        <div class="account-info">

            <div class="info-box">

                <div class="info-label">
                    Email
                </div>

                <div class="info-value">
                    <?php echo e($user['email']); ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    Username
                </div>

                <div class="info-value">
                    <?php echo e($user['username']); ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    Account Status
                </div>

                <div class="info-value">
                    <?php echo e($user['status']); ?>
                </div>

            </div>

            <div class="info-box">

                <div class="info-label">
                    Member Since
                </div>

                <div class="info-value">
                    <?php echo e($user['created_at']); ?>
                </div>

            </div>

        </div>

    </div>

    <div class="card">

        <h3 class="section-title">
            Update Profile
        </h3>

        <form method="POST">

            <div class="grid">

                <div class="form-group">

                    <label for="full_name">
                        Full Name
                    </label>

                    <input
                        type="text"
                        id="full_name"
                        name="full_name"
                        maxlength="100"
                        required
                        value="<?php echo e(
                            $user['full_name']
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
                        maxlength="10"
                        pattern="[0-9]{10}"
                        inputmode="numeric"
                        required
                        value="<?php echo e(
                            $user['mobile']
                        ); ?>"
                    >

                </div>

            </div>

            <button type="submit">
                Save Changes
            </button>

        </form>

    </div>

    <div class="card">

        <h3 class="section-title">
            Change Password
        </h3>

        <form method="POST">

            <input
                type="hidden"
                name="change_password"
                value="1"
            >

            <div class="form-group">

                <label for="current_password">
                    Current Password
                </label>

                <input
                    type="password"
                    id="current_password"
                    name="current_password"
                    required
                >

            </div>

            <div class="grid">

                <div class="form-group">

                    <label for="new_password">
                        New Password
                    </label>

                    <input
                        type="password"
                        id="new_password"
                        name="new_password"
                        minlength="8"
                        required
                    >

                </div>

                <div class="form-group">

                    <label for="confirm_password">
                        Confirm New Password
                    </label>

                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        minlength="8"
                        required
                    >

                </div>

            </div>

            <button type="submit">
                Change Password
            </button>

        </form>

    </div>

</div>

</body>

</html>
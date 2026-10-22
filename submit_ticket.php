
<?php
session_start();

if (
    empty($_SESSION['user_logged_in']) ||
    empty($_SESSION['user_id'])
) {
    header('Location: user_login.php?redirect=help_support.php');
    exit;
}

require_once __DIR__ . '/config/db_connect.php';

// Generate CSRF token
if (empty($_SESSION['support_csrf'])) {
    $_SESSION['support_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$ticketId = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['support_csrf'], $token)
    ) {
        $error = 'Invalid request. Please go back and try again.';
    } else {
        $userId = (int) $_SESSION['user_id'];
        $subject = trim($_POST['subject'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $message = trim($_POST['message'] ?? '');

        $allowedCategories = [
            'Booking',
            'Payment',
            'Cancellation & Refund',
            'Login & OTP',
            'Other'
        ];

        if (
            $subject === '' ||
            strlen($subject) > 150 ||
            $message === '' ||
            !in_array($category, $allowedCategories, true)
        ) {
            $error = 'Please enter a valid subject, category and message.';
        } elseif (strlen($message) > 5000) {
            $error = 'Your message must be within 5000 characters.';
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO support_tickets
                    (user_id, subject, category, message)
                 VALUES (?, ?, ?, ?)"
            );

            if ($stmt) {
                $stmt->bind_param(
                    "isss",
                    $userId,
                    $subject,
                    $category,
                    $message
                );

                if ($stmt->execute()) {
                    $ticketId = (int) $conn->insert_id;
                    $_SESSION['support_csrf'] =
                        bin2hex(random_bytes(32));
                } else {
                    $error = 'Could not submit your complaint. Please try again.';
                    error_log('Support ticket insert failed: ' . $stmt->error);
                }

                $stmt->close();
            } else {
                $error = 'Something went wrong. Please try again later.';
                error_log('Support ticket prepare failed: ' . $conn->error);
            }
        }
    }
} else {
    header('Location: help_support.php#contact');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Support Ticket | OBRS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">
    <style>
        body {
            background: #f4f7fb;
            font-family: Arial, sans-serif;
        }
        .result-card {
            max-width: 600px;
            margin: 70px auto;
            padding: 32px;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 30px rgba(0,0,0,.07);
        }
    </style>
</head>
<body>
<div class="container">
    <div class="result-card text-center">
        <?php if ($ticketId > 0): ?>
            <div class="display-3">✅</div>
            <h1 class="h3 mt-3">Complaint Submitted!</h1>
            <p class="text-secondary">
                Your support request has been saved successfully.
            </p>

            <div class="alert alert-success">
                Your ticket number:
                <strong>#<?= $ticketId ?></strong>
            </div>

            <p>Please save your ticket number for reference.</p>

            <a href="help_support.php"
               class="btn btn-primary">Back to Help & Support</a>
        <?php else: ?>
            <div class="display-4">⚠️</div>
            <h1 class="h4 mt-3">Unable to Submit</h1>
            <p class="text-danger">
                <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            </p>
            <a href="help_support.php#contact"
               class="btn btn-primary">Try Again</a>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

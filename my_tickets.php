
<?php
session_start();

if (
    empty($_SESSION['user_logged_in']) ||
    empty($_SESSION['user_id'])
) {
    header('Location: user_login.php?redirect=my_tickets.php');
    exit;
}

require_once __DIR__ . '/config/db_connect.php';

$userId = (int) $_SESSION['user_id'];
$userName = $_SESSION['user_name'] ?? 'Customer';
$tickets = [];
$error = '';

$stmt = $conn->prepare(
    "SELECT id, subject, category, message, status,
            admin_reply, created_at, updated_at
     FROM support_tickets
     WHERE user_id = ?
     ORDER BY created_at DESC"
);

if ($stmt) {
    $stmt->bind_param("i", $userId);

    if ($stmt->execute()) {
        $stmt->bind_result(
            $id,
            $subject,
            $category,
            $message,
            $status,
            $adminReply,
            $createdAt,
            $updatedAt
        );

        while ($stmt->fetch()) {
            $tickets[] = [
                'id' => $id,
                'subject' => $subject,
                'category' => $category,
                'message' => $message,
                'status' => $status,
                'admin_reply' => $adminReply,
                'created_at' => $createdAt,
                'updated_at' => $updatedAt
            ];
        }
    } else {
        $error = 'Unable to load your complaints. Please try again.';
        error_log('Ticket list query failed: ' . $stmt->error);
    }

    $stmt->close();
} else {
    $error = 'Something went wrong. Please try again later.';
    error_log('Ticket list prepare failed: ' . $conn->error);
}

function e($value) {
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Complaints | OBRS</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f4f7fb;
            font-family: Arial, sans-serif;
            color: #1f2937;
        }

        .page-header {
            background: linear-gradient(135deg, #123c69, #2563eb);
            color: white;
            padding: 35px 20px;
            border-radius: 18px;
            margin: 25px 0;
        }

        .ticket-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 16px;
            padding: 22px;
            margin-bottom: 18px;
            box-shadow: 0 5px 18px rgba(0,0,0,.04);
        }

        .ticket-message {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .status-open {
            background: #fff3cd;
            color: #856404;
        }

        .status-progress {
            background: #cfe2ff;
            color: #084298;
        }

        .status-resolved {
            background: #d1e7dd;
            color: #0f5132;
        }

        .ticket-meta {
            color: #6b7280;
            font-size: 14px;
        }
    </style>
</head>
<body>

<div class="container py-3">

    <div class="page-header">
        <h1 class="h3">My Complaints</h1>
        <p class="mb-0">
            Hello <?= e($userName) ?>! Track your support requests here.
        </p>
    </div>

    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="help_support.php" class="btn btn-primary">
            Help &amp; Support
        </a>

        <a href="user_dashboard.php" class="btn btn-outline-secondary">
            Dashboard
        </a>
    </div>

    <?php if ($error !== ''): ?>

        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>

    <?php elseif (empty($tickets)): ?>

        <div class="bg-white rounded-4 p-5 text-center shadow-sm">
            <div class="display-4">📩</div>
            <h2 class="h5 mt-3">No complaints yet</h2>
            <p class="text-secondary">
                You haven't submitted any support tickets.
            </p>

            <a href="help_support.php#contact" class="btn btn-primary">
                Submit a Complaint
            </a>
        </div>

    <?php else: ?>

        <p class="text-secondary">
            Total complaints: <strong><?= count($tickets) ?></strong>
        </p>

        <?php foreach ($tickets as $ticket): ?>

            <?php
            $statusClass = 'status-open';

            if ($ticket['status'] === 'In Progress') {
                $statusClass = 'status-progress';
            } elseif ($ticket['status'] === 'Resolved') {
                $statusClass = 'status-resolved';
            }
            ?>

            <div class="ticket-card">

                <div class="d-flex flex-wrap justify-content-between
                            align-items-start gap-2">

                    <div>
                        <h2 class="h5 mb-1">
                            <?= e($ticket['subject']) ?>
                        </h2>

                        <div class="ticket-meta">
                            Ticket #<?= (int) $ticket['id'] ?>
                            &bull;
                            <?= e($ticket['category']) ?>
                        </div>
                    </div>

                    <span class="badge rounded-pill <?= e($statusClass) ?> p-2">
                        <?= e($ticket['status']) ?>
                    </span>

                </div>

                <hr>

                <h3 class="h6">Your Message</h3>

                <p class="ticket-message">
                    <?= e($ticket['message']) ?>
                </p>

                <div class="ticket-meta">
                    Submitted:
                    <?= e($ticket['created_at']) ?>
                </div>

                <?php if (!empty($ticket['admin_reply'])): ?>

                    <div class="alert alert-info mt-3 mb-0">
                        <h3 class="h6">Support Team Reply</h3>

                        <p class="ticket-message mb-0">
                            <?= e($ticket['admin_reply']) ?>
                        </p>
                    </div>

                <?php else: ?>

                    <p class="text-secondary small mt-3 mb-0">
                        No reply has been added yet.
                    </p>

                <?php endif; ?>

            </div>

        <?php endforeach; ?>

    <?php endif; ?>

</div>

</body>
</html>

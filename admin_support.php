
<?php
require_once __DIR__ . '/config/admin_auth.php';
require_once __DIR__ . '/config/db_connect.php';
require_once __DIR__ . '/send_support_ticket_email.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Escape HTML output safely.
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

// Create CSRF token.
if (empty($_SESSION['admin_support_csrf'])) {
    $_SESSION['admin_support_csrf'] = bin2hex(random_bytes(32));
}

$error = '';
$flash = $_SESSION['admin_support_flash'] ?? '';
unset($_SESSION['admin_support_flash']);

// Process ticket update.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $token = $_POST['csrf_token'] ?? '';

    if (
        !is_string($token) ||
        !hash_equals($_SESSION['admin_support_csrf'], $token)
    ) {
        $error = 'Security validation failed. Please refresh the page.';
    } else {

        $ticketId = filter_var(
            $_POST['ticket_id'] ?? null,
            FILTER_VALIDATE_INT
        );

        $status = $_POST['status'] ?? '';
        $reply = trim($_POST['admin_reply'] ?? '');

        $allowedStatuses = ['Open', 'In Progress', 'Resolved'];

        if (!$ticketId || $ticketId < 1) {
            $error = 'Invalid support ticket ID.';
        } elseif (!in_array($status, $allowedStatuses, true)) {
            $error = 'Please select a valid ticket status.';
        } elseif (strlen($reply) > 5000) {
            $error = 'Reply must not exceed 5000 characters.';
        } else {

            $stmt = $conn->prepare(
                "UPDATE support_tickets
                 SET status = ?, admin_reply = ?
                 WHERE id = ?"
            );

            if (!$stmt) {
                error_log('OBRS support update prepare failed: ' . $conn->error);
                $error = 'Unable to prepare the ticket update.';
            } else {

                $stmt->bind_param('ssi', $status, $reply, $ticketId);

                if ($stmt->execute()) {

                    $stmt->close();

                    // Find the customer connected to this ticket.
                    $customerStmt = $conn->prepare(
                        "SELECT cu.full_name, cu.email, st.subject
                         FROM support_tickets AS st
                         INNER JOIN customer_users AS cu
                             ON cu.id = st.user_id
                         WHERE st.id = ?
                         LIMIT 1"
                    );

                    if ($customerStmt) {
                        $customerStmt->bind_param('i', $ticketId);

                        if ($customerStmt->execute()) {

                            $customerStmt->bind_result(
                                $customerName,
                                $customerEmail,
                                $ticketSubject
                            );

                            if ($customerStmt->fetch()) {

                                $customerStmt->close();

                                // Email failure must not undo the ticket update.
                                $emailSent = send_support_ticket_email(
                                    (string) $customerEmail,
                                    (string) $customerName,
                                    (int) $ticketId,
                                    (string) $ticketSubject,
                                    (string) $status,
                                    (string) $reply
                                );

                                if (!$emailSent) {
                                    error_log(
                                        'OBRS: Support notification email failed for ticket #' .
                                        $ticketId
                                    );
                                }

                            } else {
                                $customerStmt->close();

                                error_log(
                                    'OBRS: Customer not found for ticket #' .
                                    $ticketId
                                );
                            }

                        } else {
                            error_log(
                                'OBRS: Customer lookup failed: ' .
                                $customerStmt->error
                            );

                            $customerStmt->close();
                        }

                    } else {
                        error_log(
                            'OBRS: Customer lookup prepare failed: ' .
                            $conn->error
                        );
                    }

                    $_SESSION['admin_support_flash'] =
                        'Ticket #' . $ticketId . ' updated successfully.';

                    // Rotate CSRF token after processing.
                    $_SESSION['admin_support_csrf'] =
                        bin2hex(random_bytes(32));

                    header('Location: admin_support.php');
                    exit;

                } else {
                    error_log(
                        'OBRS support update failed: ' . $stmt->error
                    );

                    $error = 'Unable to update the ticket. Please try again.';
                    $stmt->close();
                }
            }
        }
    }
}

// Load all support tickets.
$tickets = [];

$result = $conn->query(
    "SELECT id, user_id, subject, category, message,
            status, admin_reply, created_at, updated_at
     FROM support_tickets
     ORDER BY created_at DESC"
);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }

    $result->free();
} else {
    error_log('OBRS support ticket list failed: ' . $conn->error);
    $error = 'Unable to load support tickets. Check the database table.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Support Tickets | OBRS Admin</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <style>
        body {
            background: #f4f6fb;
            font-family: Arial, sans-serif;
            color: #1f2937;
        }

        .page-header {
            background: #172554;
            color: white;
            padding: 24px 0;
            margin-bottom: 28px;
        }

        .ticket-card {
            border: 0;
            border-radius: 14px;
            box-shadow: 0 4px 18px rgba(0, 0, 0, 0.06);
            margin-bottom: 22px;
            overflow: hidden;
        }

        .ticket-card .card-header {
            background: white;
            border-bottom: 1px solid #e5e7eb;
            padding: 18px;
        }

        .ticket-card .card-body {
            padding: 20px;
        }

        .ticket-message {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
            background: #f8fafc;
            padding: 15px;
            border-radius: 8px;
        }

        .admin-reply {
            white-space: pre-wrap;
            overflow-wrap: anywhere;
        }

        .form-label {
            font-weight: 600;
        }

        .ticket-meta {
            color: #64748b;
            font-size: 13px;
        }
    </style>
</head>

<body>

<header class="page-header">
    <div class="container">
        <h1 class="h3 mb-1">OBRS Support Tickets</h1>
        <p class="mb-0">Manage customer complaints and replies.</p>
    </div>
</header>

<main class="container pb-5">

    <?php if ($flash !== ''): ?>
        <div class="alert alert-success" role="status">
            <?= e($flash) ?>
        </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
        <div class="alert alert-danger" role="alert">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="h5 mb-0">All Tickets</h2>
        <span class="badge text-bg-primary">
            <?= count($tickets) ?> ticket(s)
        </span>
    </div>

    <?php if (empty($tickets)): ?>

        <div class="alert alert-info">
            No support tickets found.
        </div>

    <?php else: ?>

        <?php foreach ($tickets as $ticket): ?>

            <section class="card ticket-card">

                <div class="card-header">
                    <div class="d-flex flex-wrap justify-content-between gap-2">
                        <div>
                            <h3 class="h5 mb-1">
                                #<?= e($ticket['id']) ?> —
                                <?= e($ticket['subject']) ?>
                            </h3>

                            <div class="ticket-meta">
                                Customer ID: <?= e($ticket['user_id']) ?>
                                &nbsp;|&nbsp;
                                Category: <?= e($ticket['category']) ?>
                            </div>
                        </div>

                        <?php
                        $badgeClass = 'text-bg-secondary';

                        if ($ticket['status'] === 'Open') {
                            $badgeClass = 'text-bg-danger';
                        } elseif ($ticket['status'] === 'In Progress') {
                            $badgeClass = 'text-bg-warning';
                        } elseif ($ticket['status'] === 'Resolved') {
                            $badgeClass = 'text-bg-success';
                        }
                        ?>

                        <span class="badge <?= e($badgeClass) ?> align-self-start">
                            <?= e($ticket['status']) ?>
                        </span>
                    </div>

                    <div class="ticket-meta mt-2">
                        Created: <?= e($ticket['created_at']) ?>
                        <?php if (!empty($ticket['updated_at'])): ?>
                            &nbsp;|&nbsp;
                            Updated: <?= e($ticket['updated_at']) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="card-body">

                    <h4 class="h6">Customer Message</h4>

                    <div class="ticket-message mb-4"><?= e($ticket['message']) ?></div>

                    <?php if (!empty($ticket['admin_reply'])): ?>
                        <h4 class="h6">Previous Admin Reply</h4>

                        <div class="admin-reply mb-4">
                            <?= e($ticket['admin_reply']) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" action="admin_support.php">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?= e($_SESSION['admin_support_csrf']) ?>"
                        >

                        <input
                            type="hidden"
                            name="ticket_id"
                            value="<?= e($ticket['id']) ?>"
                        >

                        <div class="row g-3">

                            <div class="col-md-4">
                                <label
                                    class="form-label"
                                    for="status-<?= e($ticket['id']) ?>"
                                >
                                    Update Status
                                </label>

                                <select
                                    class="form-select"
                                    id="status-<?= e($ticket['id']) ?>"
                                    name="status"
                                    required
                                >
                                    <?php
                                    $statuses = [
                                        'Open',
                                        'In Progress',
                                        'Resolved'
                                    ];
                                    ?>

                                    <?php foreach ($statuses as $option): ?>
                                        <option
                                            value="<?= e($option) ?>"
                                            <?= $ticket['status'] === $option ? 'selected' : '' ?>
                                        >
                                            <?= e($option) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-8">
                                <label
                                    class="form-label"
                                    for="reply-<?= e($ticket['id']) ?>"
                                >
                                    Admin Reply
                                </label>

                                <textarea
                                    class="form-control"
                                    id="reply-<?= e($ticket['id']) ?>"
                                    name="admin_reply"
                                    rows="3"
                                    maxlength="5000"
                                    placeholder="Write your reply to the customer..."
                                ><?= e($ticket['admin_reply'] ?? '') ?></textarea>
                            </div>

                            <div class="col-12">
                                <button
                                    type="submit"
                                    class="btn btn-primary"
                                >
                                    Save Update &amp; Notify Customer
                                </button>
                            </div>

                        </div>
                    </form>

                </div>
            </section>

        <?php endforeach; ?>

    <?php endif; ?>

</main>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>

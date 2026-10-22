<?php
session_start();

require_once 'config/db_connect.php';

/* =========================================================
   AUTH CHECK
   ========================================================= */
if (
    empty($_SESSION['user_logged_in']) ||
    empty($_SESSION['user_id'])
) {
    header('Location: user_login.php');
    exit;
}

$user_id = (int) $_SESSION['user_id'];

/* =========================================================
   HELPER FUNCTIONS
   ========================================================= */
function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}

function money($amount)
{
    return '₹' . number_format(
        (float) $amount,
        2
    );
}

function formatDateTime($dateTime)
{
    if (empty($dateTime)) {
        return '—';
    }

    $time = strtotime($dateTime);

    if ($time === false) {
        return '—';
    }

    return date('d M Y, h:i A', $time);
}

/* =========================================================
   VERIFY CUSTOMER ACCOUNT
   ========================================================= */
$userStmt = $conn->prepare("
    SELECT
        id,
        full_name,
        email,
        mobile,
        username,
        status
    FROM customer_users
    WHERE id = ?
    LIMIT 1
");

if (!$userStmt) {
    die('Unable to load user account.');
}

$userStmt->bind_param(
    "i",
    $user_id
);

$userStmt->execute();

$userResult = $userStmt->get_result();

$user = $userResult->fetch_assoc();

$userStmt->close();

if (!$user) {
    session_unset();
    session_destroy();

    header('Location: user_login.php');
    exit;
}

if ($user['status'] !== 'Active') {
    session_unset();
    session_destroy();

    header('Location: user_login.php');
    exit;
}

/* =========================================================
   GET BOOKINGS
   ========================================================= */
$bookings = [];

$bookingStmt = $conn->prepare("
    SELECT
        b.id AS booking_id,
        b.reference_no,
        b.customer_name,
        b.customer_phone,
        b.customer_email,
        b.seats,
        b.ticket_price,
        b.total_amount,
        b.payment_status,
        b.booking_date,

        s.departure_time,
        s.eta_time,

        bus.bus_name,
        bus.bus_number,

        origin.location_name AS origin,
        destination.location_name AS destination,

        p.payment_method,
        p.transaction_id,
        p.paid_at

    FROM booked b

    INNER JOIN schedule s
        ON b.schedule_id = s.id

    INNER JOIN bus
        ON s.bus_id = bus.id

    INNER JOIN location AS origin
        ON s.origin_id = origin.id

    INNER JOIN location AS destination
        ON s.destination_id = destination.id

    LEFT JOIN payments p
        ON p.booking_id = b.id

    WHERE b.user_id = ?

    ORDER BY b.id DESC
");

if (!$bookingStmt) {
    die('Unable to load bookings.');
}

$bookingStmt->bind_param(
    "i",
    $user_id
);

$bookingStmt->execute();

$bookingResult = $bookingStmt->get_result();

while ($row = $bookingResult->fetch_assoc()) {
    $bookings[] = $row;
}

$bookingStmt->close();

/* =========================================================
   STATISTICS
   ========================================================= */
$totalBookings = count($bookings);

$paidBookings = 0;
$totalPaidAmount = 0;

foreach ($bookings as $booking) {

    if (
        strtolower(
            trim((string) $booking['payment_status'])
        ) === 'paid'
    ) {
        $paidBookings++;

        $totalPaidAmount +=
            (float) $booking['total_amount'];
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

    <title>My Bookings | OBRS</title>

    <style>

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family:
                Inter,
                system-ui,
                -apple-system,
                BlinkMacSystemFont,
                "Segoe UI",
                sans-serif;

            background:
                radial-gradient(
                    circle at top left,
                    rgba(99, 102, 241, 0.16),
                    transparent 32%
                ),
                radial-gradient(
                    circle at top right,
                    rgba(14, 165, 233, 0.12),
                    transparent 30%
                ),
                #070b14;

            color: #f8fafc;
            min-height: 100vh;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .navbar {
            position: sticky;
            top: 0;
            z-index: 100;

            border-bottom:
                1px solid rgba(255,255,255,0.08);

            background:
                rgba(7,11,20,0.84);

            backdrop-filter: blur(18px);
        }

        .nav-inner {
            max-width: 1180px;
            margin: auto;

            min-height: 72px;

            padding: 0 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 20px;
        }

        .brand {
            font-size: 23px;
            font-weight: 900;
            letter-spacing: -0.5px;
        }

        .brand span {
            color: #818cf8;
        }

        .nav-links {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links a {
            padding: 10px 14px;

            border-radius: 10px;

            color: #94a3b8;

            font-size: 14px;
            font-weight: 700;

            transition: 0.2s;
        }

        .nav-links a:hover {
            color: #fff;
            background: rgba(255,255,255,0.06);
        }

        .nav-links .active {
            color: #fff;
            background: rgba(99,102,241,0.14);
        }

        .logout {
            color: #fca5a5 !important;
        }

        .container {
            max-width: 1180px;
            margin: auto;

            padding:
                45px 20px 70px;
        }

        .hero {
            margin-bottom: 30px;
        }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 8px;

            padding: 7px 12px;

            border:
                1px solid rgba(129,140,248,0.22);

            background:
                rgba(99,102,241,0.09);

            border-radius: 999px;

            color: #a5b4fc;

            font-size: 12px;
            font-weight: 800;

            margin-bottom: 15px;
        }

        h1 {
            margin: 0;

            font-size:
                clamp(32px, 5vw, 48px);

            line-height: 1.05;

            letter-spacing: -1.5px;
        }

        .subtitle {
            margin:
                12px 0 0;

            color: #94a3b8;

            font-size: 16px;
        }

        /* =====================================================
           STATS
           ===================================================== */

        .stats {
            display: grid;

            grid-template-columns:
                repeat(3, 1fr);

            gap: 16px;

            margin-bottom: 30px;
        }

        .stat-card {
            padding: 22px;

            border:
                1px solid rgba(255,255,255,0.08);

            background:
                linear-gradient(
                    145deg,
                    rgba(255,255,255,0.065),
                    rgba(255,255,255,0.025)
                );

            border-radius: 18px;

            box-shadow:
                0 18px 50px rgba(0,0,0,0.16);
        }

        .stat-label {
            color: #94a3b8;

            font-size: 13px;
            font-weight: 700;

            margin-bottom: 9px;
        }

        .stat-value {
            font-size: 29px;
            font-weight: 900;
        }

        /* =====================================================
           BOOKINGS
           ===================================================== */

        .section-title {
            margin:
                0 0 15px;

            font-size: 20px;
            font-weight: 900;
        }

        .booking-list {
            display: grid;
            gap: 18px;
        }

        .booking-card {
            border:
                1px solid rgba(255,255,255,0.09);

            background:
                rgba(15,23,42,0.72);

            border-radius: 20px;

            overflow: hidden;

            box-shadow:
                0 20px 55px rgba(0,0,0,0.18);
        }

        .booking-top {
            padding: 18px 20px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 15px;

            border-bottom:
                1px solid rgba(255,255,255,0.07);
        }

        .reference {
            color: #a5b4fc;

            font-size: 14px;
            font-weight: 900;

            word-break: break-word;
        }

        .booking-date {
            color: #64748b;

            font-size: 12px;
            margin-top: 4px;
        }

        .status {
            display: inline-flex;
            align-items: center;

            padding: 7px 11px;

            border-radius: 999px;

            font-size: 11px;
            font-weight: 900;
        }

        .status.paid {
            color: #86efac;
            background: rgba(34,197,94,0.12);
            border: 1px solid rgba(34,197,94,0.18);
        }

        .status.unpaid {
            color: #fcd34d;
            background: rgba(245,158,11,0.12);
            border: 1px solid rgba(245,158,11,0.18);
        }

        .status.failed {
            color: #fca5a5;
            background: rgba(239,68,68,0.12);
            border: 1px solid rgba(239,68,68,0.18);
        }

        .booking-body {
            padding: 22px 20px;
        }

        .route {
            display: grid;

            grid-template-columns:
                1fr auto 1fr;

            align-items: center;

            gap: 18px;

            margin-bottom: 22px;
        }

        .location {
            min-width: 0;
        }

        .location:last-child {
            text-align: right;
        }

        .location-label {
            color: #64748b;

            font-size: 11px;
            font-weight: 800;

            text-transform: uppercase;

            letter-spacing: 0.8px;

            margin-bottom: 5px;
        }

        .location-name {
            font-size: 19px;
            font-weight: 900;

            overflow-wrap: anywhere;
        }

        .arrow {
            width: 40px;
            height: 40px;

            border-radius: 50%;

            display: grid;
            place-items: center;

            background:
                rgba(99,102,241,0.12);

            border:
                1px solid rgba(129,140,248,0.18);

            color: #a5b4fc;

            font-size: 18px;
        }

        .details {
            display: grid;

            grid-template-columns:
                repeat(4, 1fr);

            gap: 12px;
        }

        .detail {
            padding: 13px;

            border-radius: 12px;

            background:
                rgba(255,255,255,0.035);
        }

        .detail-label {
            color: #64748b;

            font-size: 10px;
            font-weight: 800;

            text-transform: uppercase;

            margin-bottom: 5px;
        }

        .detail-value {
            color: #e2e8f0;

            font-size: 13px;
            font-weight: 800;

            overflow-wrap: anywhere;
        }

        .booking-bottom {
            padding: 16px 20px;

            display: flex;
            justify-content: space-between;
            align-items: center;

            gap: 15px;

            border-top:
                1px solid rgba(255,255,255,0.07);
        }

        .amount-label {
            color: #64748b;
            font-size: 11px;
        }

        .amount {
            margin-top: 3px;

            font-size: 21px;
            font-weight: 900;
        }

        .ticket-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            padding: 11px 16px;

            border-radius: 11px;

            color: white;

            background:
                linear-gradient(
                    135deg,
                    #6366f1,
                    #4f46e5
                );

            font-size: 13px;
            font-weight: 900;

            transition: 0.2s;

            box-shadow:
                0 10px 25px rgba(79,70,229,0.22);
        }

        .ticket-btn:hover {
            transform: translateY(-1px);

            box-shadow:
                0 14px 30px rgba(79,70,229,0.3);
        }

        /* =====================================================
           EMPTY
           ===================================================== */

        .empty {
            padding: 55px 20px;

            text-align: center;

            border:
                1px solid rgba(255,255,255,0.08);

            background:
                rgba(255,255,255,0.025);

            border-radius: 20px;
        }

        .empty-icon {
            font-size: 42px;
            margin-bottom: 12px;
        }

        .empty h2 {
            margin: 0 0 8px;

            font-size: 22px;
        }

        .empty p {
            margin: 0 0 20px;

            color: #64748b;
        }

        .find-btn {
            display: inline-flex;

            padding: 12px 18px;

            border-radius: 11px;

            background:
                linear-gradient(
                    135deg,
                    #6366f1,
                    #4f46e5
                );

            font-weight: 900;
            font-size: 13px;
        }

        footer {
            max-width: 1180px;

            margin:
                0 auto;

            padding:
                20px;

            color: #475569;

            text-align: center;

            font-size: 12px;
        }

        /* =====================================================
           RESPONSIVE
           ===================================================== */

        @media (max-width: 800px) {

            .nav-inner {
                min-height: auto;

                padding-top: 14px;
                padding-bottom: 14px;

                flex-direction: column;
                align-items: stretch;
            }

            .brand {
                text-align: center;
            }

            .nav-links {
                justify-content: center;
                flex-wrap: wrap;
            }

            .stats {
                grid-template-columns: 1fr;
            }

            .details {
                grid-template-columns:
                    repeat(2, 1fr);
            }
        }

        @media (max-width: 560px) {

            .container {
                padding:
                    30px 14px 50px;
            }

            .nav-links a {
                padding: 8px 10px;
                font-size: 12px;
            }

            .booking-top {
                align-items: flex-start;

                flex-direction: column;
            }

            .route {
                grid-template-columns: 1fr;

                gap: 10px;
            }

            .location,
            .location:last-child {
                text-align: left;
            }

            .arrow {
                width: 34px;
                height: 34px;
            }

            .details {
                grid-template-columns: 1fr;
            }

            .booking-bottom {
                align-items: stretch;

                flex-direction: column;
            }

            .ticket-btn {
                width: 100%;
            }
        }

    </style>

</head>

<body>

<nav class="navbar">

    <div class="nav-inner">

        <a
            href="user_dashboard.php"
            class="brand"
        >
            OBRS<span>.</span>
        </a>

        <div class="nav-links">

            <a href="user_dashboard.php">
                Dashboard
            </a>

            <a href="schedule.php">
                Book Bus
            </a>

            <a
                href="user_bookings.php"
                class="active"
            >
                My Bookings
            </a>

            <a
                href="user_logout.php"
                class="logout"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<main class="container">

    <section class="hero">

        <div class="eyebrow">
            🎫 BOOKING CENTER
        </div>

        <h1>
            My Bookings
        </h1>

        <p class="subtitle">
            View and manage your bus reservations.
        </p>

    </section>


    <!-- =====================================================
         STATS
         ===================================================== -->

    <section class="stats">

        <div class="stat-card">

            <div class="stat-label">
                Total Bookings
            </div>

            <div class="stat-value">
                <?= $totalBookings ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Paid Bookings
            </div>

            <div class="stat-value">
                <?= $paidBookings ?>
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-label">
                Total Paid Amount
            </div>

            <div class="stat-value">
                <?= e(money($totalPaidAmount)) ?>
            </div>

        </div>

    </section>


    <!-- =====================================================
         BOOKINGS
         ===================================================== -->

    <?php if ($totalBookings > 0): ?>

        <h2 class="section-title">
            Your Reservations
        </h2>

        <section class="booking-list">

            <?php foreach ($bookings as $booking): ?>

                <?php

                $paymentStatus =
                    trim(
                        (string)
                        $booking['payment_status']
                    );

                $paymentClass =
                    strtolower($paymentStatus);

                ?>

                <article class="booking-card">

                    <div class="booking-top">

                        <div>

                            <div class="reference">

                                <?= e(
                                    $booking['reference_no']
                                ) ?>

                            </div>

                            <div class="booking-date">

                                Booked on
                                <?= e(
                                    formatDateTime(
                                        $booking['booking_date']
                                    )
                                ) ?>

                            </div>

                        </div>


                        <div>

                            <span
                                class="status <?= e($paymentClass) ?>"
                            >

                                <?= e(
                                    $paymentStatus ?: 'Unknown'
                                ) ?>

                            </span>

                        </div>

                    </div>


                    <div class="booking-body">

                        <div class="route">

                            <div class="location">

                                <div class="location-label">
                                    From
                                </div>

                                <div class="location-name">

                                    <?= e(
                                        $booking['origin']
                                    ) ?>

                                </div>

                            </div>


                            <div class="arrow">
                                →
                            </div>


                            <div class="location">

                                <div class="location-label">
                                    To
                                </div>

                                <div class="location-name">

                                    <?= e(
                                        $booking['destination']
                                    ) ?>

                                </div>

                            </div>

                        </div>


                        <div class="details">

                            <div class="detail">

                                <div class="detail-label">
                                    Bus
                                </div>

                                <div class="detail-value">

                                    <?= e(
                                        $booking['bus_name']
                                    ) ?>

                                    <?php if (
                                        !empty(
                                            $booking['bus_number']
                                        )
                                    ): ?>

                                        <br>

                                        <?= e(
                                            $booking['bus_number']
                                        ) ?>

                                    <?php endif; ?>

                                </div>

                            </div>


                            <div class="detail">

                                <div class="detail-label">
                                    Seats
                                </div>

                                <div class="detail-value">

                                    <?= e(
                                        $booking['seats']
                                    ) ?>

                                </div>

                            </div>


                            <div class="detail">

                                <div class="detail-label">
                                    Departure
                                </div>

                                <div class="detail-value">

                                    <?= e(
                                        formatDateTime(
                                            $booking[
                                                'departure_time'
                                            ]
                                        )
                                    ) ?>

                                </div>

                            </div>


                            <div class="detail">

                                <div class="detail-label">
                                    Arrival
                                </div>

                                <div class="detail-value">

                                    <?= e(
                                        formatDateTime(
                                            $booking[
                                                'eta_time'
                                            ]
                                        )
                                    ) ?>

                                </div>

                            </div>

                        </div>

                    </div>


                    <div class="booking-bottom">

                        <div>

                            <div class="amount-label">
                                Total Amount
                            </div>

                            <div class="amount">

                                <?= e(
                                    money(
                                        $booking[
                                            'total_amount'
                                        ]
                                    )
                                ) ?>

                            </div>

                        </div>


                        <a
                            href="ticket.php?reference=<?= urlencode(
                                $booking['reference_no']
                            ) ?>"
                            class="ticket-btn"
                        >
                            🎫 View Ticket
                        </a>

                    </div>

                </article>

            <?php endforeach; ?>

        </section>

    <?php else: ?>

        <section class="empty">

            <div class="empty-icon">
                🎫
            </div>

            <h2>
                No bookings yet
            </h2>

            <p>
                You haven't booked any bus tickets yet.
            </p>

            <a
                href="schedule.php"
                class="find-btn"
            >
                Find a Bus
            </a>

        </section>

    <?php endif; ?>

</main>


<footer>

    © <?= date('Y') ?> OBRS ·
    Online Bus Reservation System

</footer>

</body>

</html>
<?php

session_start();

require_once 'config/db_connect.php';

/* =========================================================
   USER AUTHENTICATION
========================================================= */

if (
    !isset($_SESSION['user_logged_in']) ||
    $_SESSION['user_logged_in'] !== true ||
    !isset($_SESSION['user_id'])
) {
    header("Location: user_login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];


/* =========================================================
   SAFE OUTPUT
========================================================= */

function e($value)
{
    return htmlspecialchars(
        (string) $value,
        ENT_QUOTES,
        'UTF-8'
    );
}


/* =========================================================
   LOAD USER
========================================================= */

$userStmt = $conn->prepare("
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

if (!$userStmt) {
    exit("Unable to load user account.");
}

$userStmt->bind_param("i", $user_id);
$userStmt->execute();

$userResult = $userStmt->get_result();

if ($userResult->num_rows !== 1) {
    $userStmt->close();

    session_unset();
    session_destroy();

    header("Location: user_login.php");
    exit;
}

$user = $userResult->fetch_assoc();

$userStmt->close();


/* =========================================================
   ACCOUNT STATUS
========================================================= */

if ($user['status'] !== 'Active') {

    session_unset();
    session_destroy();

    header("Location: user_login.php?blocked=1");
    exit;
}


/* =========================================================
   DASHBOARD STATISTICS
========================================================= */

$totalBookings = 0;
$paidBookings = 0;
$pendingBookings = 0;
$totalPaidAmount = 0;
$totalBookingAmount = 0;


/* Total bookings */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total
    FROM booked
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $totalBookings = (int) $row['total'];
}

$stmt->close();


/* Paid bookings + paid amount */

$stmt = $conn->prepare("
    SELECT
        COUNT(*) AS total_paid,
        COALESCE(SUM(total_amount), 0) AS paid_amount
    FROM booked
    WHERE user_id = ?
    AND payment_status = 'Paid'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {

    $paidBookings = (int) $row['total_paid'];

    $totalPaidAmount =
        (float) $row['paid_amount'];
}

$stmt->close();


/* Pending bookings */

$stmt = $conn->prepare("
    SELECT COUNT(*) AS total_pending
    FROM booked
    WHERE user_id = ?
    AND payment_status != 'Paid'
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $pendingBookings =
        (int) $row['total_pending'];
}

$stmt->close();


/* Total booking amount */

$stmt = $conn->prepare("
    SELECT
        COALESCE(SUM(total_amount), 0) AS total_amount
    FROM booked
    WHERE user_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $totalBookingAmount =
        (float) $row['total_amount'];
}

$stmt->close();


/* =========================================================
   LATEST BOOKING
========================================================= */

$latestBooking = null;

$stmt = $conn->prepare("
    SELECT
        b.id,
        b.reference_no,
        b.customer_name,
        b.customer_phone,
        b.seats,
        b.total_amount,
        b.payment_status,
        b.booking_date,

        bus.bus_name,
        bus.bus_number,

        origin.location_name AS origin_name,
        destination.location_name AS destination_name,

        s.departure_time,
        s.eta_time

    FROM booked b

    INNER JOIN schedule s
        ON b.schedule_id = s.id

    INNER JOIN bus
        ON s.bus_id = bus.id

    INNER JOIN location AS origin
        ON s.origin_id = origin.id

    INNER JOIN location AS destination
        ON s.destination_id = destination.id

    WHERE b.user_id = ?

    ORDER BY b.booking_date DESC

    LIMIT 1
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $latestBooking = $result->fetch_assoc();
}

$stmt->close();


/* =========================================================
   RECENT BOOKINGS
========================================================= */

$recentBookings = [];

$stmt = $conn->prepare("
    SELECT
        b.id,
        b.reference_no,
        b.seats,
        b.total_amount,
        b.payment_status,
        b.booking_date,

        bus.bus_name,

        origin.location_name AS origin_name,
        destination.location_name AS destination_name,

        s.departure_time

    FROM booked b

    INNER JOIN schedule s
        ON b.schedule_id = s.id

    INNER JOIN bus
        ON s.bus_id = bus.id

    INNER JOIN location AS origin
        ON s.origin_id = origin.id

    INNER JOIN location AS destination
        ON s.destination_id = destination.id

    WHERE b.user_id = ?

    ORDER BY b.booking_date DESC

    LIMIT 5
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $recentBookings[] = $row;
}

$stmt->close();


/* =========================================================
   HELPER FUNCTIONS
========================================================= */

function money($amount)
{
    return '₹' . number_format(
        (float) $amount,
        2
    );
}


function formatDateTime($date)
{
    if (!$date) {
        return '-';
    }

    $time = strtotime($date);

    if (!$time) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        $time
    );
}


function paymentBadge($status)
{
    if ($status === 'Paid') {

        return '
            <span class="status-badge paid">
                ✓ Paid
            </span>
        ';
    }

    return '
        <span class="status-badge pending">
            ● Pending
        </span>
    ';
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

<title>
    My Dashboard - OBRS
</title>


<style>

* {
    box-sizing: border-box;
}

body {

    margin: 0;

    font-family:
        Inter,
        -apple-system,
        BlinkMacSystemFont,
        "Segoe UI",
        sans-serif;

    background:
        linear-gradient(
            135deg,
            #07111f,
            #0b1728 45%,
            #101c31
        );

    color: #ffffff;

    min-height: 100vh;
}


/* =========================================================
   NAVBAR
========================================================= */

.navbar {

    position: sticky;

    top: 0;

    z-index: 100;

    backdrop-filter: blur(18px);

    background:
        rgba(7, 17, 31, 0.82);

    border-bottom:
        1px solid
        rgba(255,255,255,0.08);
}

.navbar-inner {

    max-width: 1250px;

    margin: auto;

    padding:
        16px 22px;

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 20px;
}

.logo {

    color: #ffffff;

    text-decoration: none;

    font-size: 20px;

    font-weight: 800;

    letter-spacing: -0.5px;
}

.logo span {

    color: #61dafb;
}

.nav-actions {

    display: flex;

    align-items: center;

    gap: 10px;

    flex-wrap: wrap;
}

.nav-btn {

    text-decoration: none;

    color: #dce8f7;

    padding:
        9px 14px;

    border-radius: 10px;

    font-size: 14px;

    font-weight: 600;

    transition: 0.2s;
}

.nav-btn:hover {

    background:
        rgba(255,255,255,0.08);

    color: #ffffff;
}

.logout-btn {

    color: #ff9d9d;

    border:
        1px solid
        rgba(255,100,100,0.25);
}


/* =========================================================
   MAIN
========================================================= */

.container {

    width: min(
        1250px,
        calc(100% - 32px)
    );

    margin: auto;

    padding:
        42px 0 60px;
}


/* =========================================================
   WELCOME
========================================================= */

.welcome {

    display: flex;

    justify-content: space-between;

    align-items: flex-end;

    gap: 25px;

    margin-bottom: 30px;
}

.welcome h1 {

    margin: 0 0 8px;

    font-size:
        clamp(28px, 4vw, 42px);

    letter-spacing: -1.5px;
}

.welcome h1 span {

    color: #61dafb;
}

.welcome p {

    margin: 0;

    color: #9fb0c5;

    font-size: 15px;
}

.book-main-btn {

    display: inline-flex;

    align-items: center;

    justify-content: center;

    gap: 9px;

    text-decoration: none;

    background:
        linear-gradient(
            135deg,
            #61dafb,
            #4facfe
        );

    color: #06101d;

    padding:
        13px 21px;

    border-radius: 13px;

    font-weight: 800;

    box-shadow:
        0 10px 30px
        rgba(79,172,254,0.20);

    transition:
        transform 0.2s,
        box-shadow 0.2s;
}

.book-main-btn:hover {

    color: #06101d;

    transform:
        translateY(-2px);

    box-shadow:
        0 14px 35px
        rgba(79,172,254,0.30);
}


/* =========================================================
   STAT CARDS
========================================================= */

.stats-grid {

    display: grid;

    grid-template-columns:
        repeat(4, 1fr);

    gap: 17px;

    margin-bottom: 25px;
}

.stat-card {

    padding: 22px;

    border-radius: 18px;

    background:
        rgba(255,255,255,0.055);

    border:
        1px solid
        rgba(255,255,255,0.09);

    backdrop-filter:
        blur(15px);

    transition:
        transform 0.2s,
        border-color 0.2s;
}

.stat-card:hover {

    transform:
        translateY(-3px);

    border-color:
        rgba(97,218,251,0.28);
}

.stat-icon {

    width: 42px;

    height: 42px;

    border-radius: 12px;

    display: flex;

    align-items: center;

    justify-content: center;

    font-size: 19px;

    background:
        rgba(97,218,251,0.10);

    margin-bottom: 17px;
}

.stat-title {

    color: #91a4ba;

    font-size: 13px;

    margin-bottom: 7px;
}

.stat-value {

    font-size: 28px;

    font-weight: 800;

    letter-spacing: -1px;
}

.stat-sub {

    margin-top: 5px;

    color: #73869d;

    font-size: 12px;
}


/* =========================================================
   CONTENT GRID
========================================================= */

.content-grid {

    display: grid;

    grid-template-columns:
        minmax(0, 1.4fr)
        minmax(300px, 0.6fr);

    gap: 20px;

    margin-bottom: 20px;
}

.panel {

    background:
        rgba(255,255,255,0.055);

    border:
        1px solid
        rgba(255,255,255,0.09);

    border-radius: 20px;

    overflow: hidden;

    backdrop-filter:
        blur(15px);
}

.panel-header {

    padding:
        20px 22px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.07);

    display: flex;

    align-items: center;

    justify-content: space-between;

    gap: 15px;
}

.panel-title {

    font-size: 17px;

    font-weight: 800;
}

.panel-link {

    color: #61dafb;

    text-decoration: none;

    font-size: 13px;

    font-weight: 700;
}

.panel-body {

    padding: 22px;
}


/* =========================================================
   LATEST BOOKING
========================================================= */

.latest-route {

    display: flex;

    align-items: center;

    gap: 15px;

    flex-wrap: wrap;

    margin-bottom: 22px;
}

.city {

    font-size: 21px;

    font-weight: 800;
}

.route-arrow {

    color: #61dafb;

    font-size: 21px;
}

.booking-reference {

    color: #8da2ba;

    font-size: 13px;

    margin-bottom: 18px;
}

.booking-reference strong {

    color: #ffffff;
}

.details-grid {

    display: grid;

    grid-template-columns:
        repeat(2, 1fr);

    gap: 13px;
}

.detail-box {

    padding: 14px;

    border-radius: 13px;

    background:
        rgba(255,255,255,0.045);
}

.detail-label {

    color: #8296ad;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 0.6px;

    margin-bottom: 5px;
}

.detail-value {

    font-size: 14px;

    font-weight: 700;
}


/* =========================================================
   EMPTY
========================================================= */

.empty-state {

    text-align: center;

    padding: 35px 20px;

    color: #91a4ba;
}

.empty-icon {

    font-size: 42px;

    margin-bottom: 12px;
}

.empty-state h3 {

    margin:
        0 0 8px;

    color: #ffffff;

    font-size: 19px;
}

.empty-state p {

    margin:
        0 0 20px;

    font-size: 13px;
}


/* =========================================================
   PROFILE
========================================================= */

.profile-name {

    font-size: 22px;

    font-weight: 800;

    margin-bottom: 5px;
}

.profile-username {

    color: #61dafb;

    font-size: 13px;

    margin-bottom: 22px;
}

.profile-item {

    display: flex;

    justify-content: space-between;

    gap: 15px;

    padding:
        13px 0;

    border-bottom:
        1px solid
        rgba(255,255,255,0.06);

    font-size: 13px;
}

.profile-item:last-child {

    border-bottom: 0;
}

.profile-label {

    color: #8194aa;
}

.profile-value {

    color: #ffffff;

    text-align: right;

    word-break: break-word;
}

.profile-actions {

    display: grid;

    gap: 9px;

    margin-top: 18px;
}

.profile-btn {

    display: block;

    text-align: center;

    text-decoration: none;

    padding: 11px;

    border-radius: 11px;

    color: #dce8f7;

    background:
        rgba(255,255,255,0.06);

    border:
        1px solid
        rgba(255,255,255,0.07);

    font-size: 13px;

    font-weight: 700;
}

.profile-btn:hover {

    color: #ffffff;

    background:
        rgba(255,255,255,0.10);
}


/* =========================================================
   RECENT TABLE
========================================================= */

.table-wrap {

    overflow-x: auto;
}

.booking-table {

    width: 100%;

    border-collapse: collapse;

    min-width: 720px;
}

.booking-table th {

    color: #8194aa;

    font-size: 11px;

    text-transform: uppercase;

    letter-spacing: 0.6px;

    text-align: left;

    padding:
        13px 12px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.08);
}

.booking-table td {

    padding:
        15px 12px;

    border-bottom:
        1px solid
        rgba(255,255,255,0.055);

    font-size: 13px;

    color: #dbe5f0;
}

.booking-table tr:last-child td {

    border-bottom: 0;
}

.reference {

    color: #61dafb;

    font-weight: 800;
}

.route-small {

    font-weight: 700;
}

.muted {

    color: #8194aa;
}

.status-badge {

    display: inline-flex;

    padding:
        5px 9px;

    border-radius: 999px;

    font-size: 11px;

    font-weight: 800;
}

.status-badge.paid {

    color: #8ff0c0;

    background:
        rgba(50,205,130,0.11);
}

.status-badge.pending {

    color: #ffd48a;

    background:
        rgba(255,184,77,0.11);
}

.ticket-btn {

    color: #61dafb;

    text-decoration: none;

    font-weight: 700;

    font-size: 12px;
}


/* =========================================================
   RESPONSIVE
========================================================= */

@media (max-width: 1000px) {

    .stats-grid {

        grid-template-columns:
            repeat(2, 1fr);
    }

    .content-grid {

        grid-template-columns:
            1fr;
    }
}

@media (max-width: 700px) {

    .navbar-inner {

        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .nav-actions {

        width: 100%;
    }

    .welcome {

        align-items:
            flex-start;

        flex-direction:
            column;
    }

    .book-main-btn {

        width: 100%;
    }

    .stats-grid {

        grid-template-columns:
            1fr 1fr;

        gap: 10px;
    }

    .stat-card {

        padding: 17px;
    }

    .stat-value {

        font-size: 23px;
    }

    .container {

        width:
            min(
                100% - 22px,
                1250px
            );

        padding-top: 27px;
    }
}

@media (max-width: 450px) {

    .stats-grid {

        grid-template-columns:
            1fr;
    }

    .details-grid {

        grid-template-columns:
            1fr;
    }

    .panel-body {

        padding: 17px;
    }

    .welcome h1 {

        font-size: 30px;
    }
}

.website-btn {
    color: #fff !important;

    background:
        linear-gradient(
            135deg,
            #6366f1,
            #4f46e5
        );

    border-radius: 10px;

    box-shadow:
        0 8px 20px rgba(79,70,229,0.20);
}

.website-btn:hover {
    transform: translateY(-1px);
}


.brand {
    display: inline-flex;
    align-items: center;
    font-family: Arial, sans-serif;
    font-size: 30px;
    font-weight: 900;
    letter-spacing: 2px;
    color: #f8fafc;
    text-decoration: none;
    transition: all 0.3s ease;
    text-shadow: 0 2px 12px rgba(139, 92, 246, 0.15);
}

.brand span {
    display: inline-block;
    font-size: 40px;
    line-height: 0.7;
    background: linear-gradient(135deg, #a78bfa, #6366f1);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    transition: transform 0.3s ease;
}

.brand:hover {
    color: #c4b5fd;
    text-decoration: none;
    transform: translateY(-2px);
}

.brand:hover span {
    transform: scale(1.3);
}


</style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================= -->

<nav class="navbar">

    <div class="navbar-inner">

        <a
    href="index.php"
    class="brand"
    title="View Website"
>
    OBRS<span>.</span>
</a>


        <div class="nav-actions">

            <a
                href="user_dashboard.php"
                class="nav-btn"
            >
                Dashboard
            </a>

            <a
                href="user_bookings.php"
                class="nav-btn"
            >
                My Bookings
            </a>

            <a
                href="schedule.php"
                class="nav-btn"
            >
                Book a Bus
            </a>

            <a
                href="user_profile.php"
                class="nav-btn"
            >
                Profile
            </a>
<a
    href="index.php"
    class="nav-btn website-btn"
>
    🌐 View Website
</a>
            <a
                href="user_logout.php"
                class="nav-btn logout-btn"
            >
                Logout
            </a>

        </div>

    </div>

</nav>


<!-- =========================================================
     MAIN
========================================================= -->

<main class="container">


    <!-- WELCOME -->

    <section class="welcome">

        <div>

            <h1>
                Welcome,
                <span>
                    <?php echo e($user['full_name']); ?>
                </span>
                👋
            </h1>

            <p>
                Manage your bus bookings, tickets and account
                from one place.
            </p>

        </div>


        <a
            href="schedule.php"
            class="book-main-btn"
        >
            🚌 Book a Bus
        </a>

    </section>


    <!-- =====================================================
         STATS
    ====================================================== -->

    <section class="stats-grid">


        <div class="stat-card">

            <div class="stat-icon">
                🎫
            </div>

            <div class="stat-title">
                Total Bookings
            </div>

            <div class="stat-value">
                <?php echo $totalBookings; ?>
            </div>

            <div class="stat-sub">
                All your bookings
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ✓
            </div>

            <div class="stat-title">
                Paid Bookings
            </div>

            <div class="stat-value">
                <?php echo $paidBookings; ?>
            </div>

            <div class="stat-sub">
                Successfully paid
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ⏳
            </div>

            <div class="stat-title">
                Pending
            </div>

            <div class="stat-value">
                <?php echo $pendingBookings; ?>
            </div>

            <div class="stat-sub">
                Payment pending
            </div>

        </div>


        <div class="stat-card">

            <div class="stat-icon">
                ₹
            </div>

            <div class="stat-title">
                Paid Amount
            </div>

            <div class="stat-value">
                <?php echo money($totalPaidAmount); ?>
            </div>

            <div class="stat-sub">
                Total paid amount
            </div>

        </div>

    </section>


    <!-- =====================================================
         MAIN CONTENT
    ====================================================== -->

    <section class="content-grid">


        <!-- LATEST BOOKING -->

        <div class="panel">

            <div class="panel-header">

                <div class="panel-title">
                    🎟️ Latest Booking
                </div>

                <a
                    href="user_bookings.php"
                    class="panel-link"
                >
                    View All →
                </a>

            </div>


            <div class="panel-body">

                <?php if ($latestBooking): ?>

                    <div class="booking-reference">

                        Booking Reference:

                        <strong>
                            <?php
                            echo e(
                                $latestBooking['reference_no']
                            );
                            ?>
                        </strong>

                    </div>


                    <div class="latest-route">

                        <div class="city">

                            <?php
                            echo e(
                                $latestBooking['origin_name']
                            );
                            ?>

                        </div>

                        <div class="route-arrow">
                            →
                        </div>

                        <div class="city">

                            <?php
                            echo e(
                                $latestBooking['destination_name']
                            );
                            ?>

                        </div>

                    </div>


                    <div class="details-grid">


                        <div class="detail-box">

                            <div class="detail-label">
                                Bus
                            </div>

                            <div class="detail-value">

                                <?php
                                echo e(
                                    $latestBooking['bus_name']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-box">

                            <div class="detail-label">
                                Seats
                            </div>

                            <div class="detail-value">

                                <?php
                                echo e(
                                    $latestBooking['seats']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-box">

                            <div class="detail-label">
                                Departure
                            </div>

                            <div class="detail-value">

                                <?php
                                echo formatDateTime(
                                    $latestBooking['departure_time']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-box">

                            <div class="detail-label">
                                Amount
                            </div>

                            <div class="detail-value">

                                <?php
                                echo money(
                                    $latestBooking['total_amount']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-box">

                            <div class="detail-label">
                                Payment
                            </div>

                            <div class="detail-value">

                                <?php
                                echo paymentBadge(
                                    $latestBooking['payment_status']
                                );
                                ?>

                            </div>

                        </div>


                        <div class="detail-box">

                            <div class="detail-label">
                                Booking Date
                            </div>

                            <div class="detail-value">

                                <?php
                                echo formatDateTime(
                                    $latestBooking['booking_date']
                                );
                                ?>

                            </div>

                        </div>


                    </div>


                    <div style="margin-top:20px;">

                        <a
                            href="ticket.php?reference=<?php
                                echo urlencode(
                                    $latestBooking['reference_no']
                                );
                            ?>"
                            class="book-main-btn"
                            style="display:inline-flex;"
                        >
                            🎫 View Ticket
                        </a>

                    </div>


                <?php else: ?>


                    <div class="empty-state">

                        <div class="empty-icon">
                            🚌
                        </div>

                        <h3>
                            No bookings yet
                        </h3>

                        <p>
                            Your first journey starts here.
                        </p>

                        <a
                            href="schedule.php"
                            class="book-main-btn"
                        >
                            Find a Bus
                        </a>

                    </div>


                <?php endif; ?>

            </div>

        </div>


        <!-- PROFILE -->

        <div class="panel">

            <div class="panel-header">

                <div class="panel-title">
                    👤 My Account
                </div>

            </div>


            <div class="panel-body">

                <div class="profile-name">

                    <?php
                    echo e(
                        $user['full_name']
                    );
                    ?>

                </div>

                <div class="profile-username">

                    @<?php
                    echo e(
                        $user['username']
                    );
                    ?>

                </div>


                <div class="profile-item">

                    <span class="profile-label">
                        Email
                    </span>

                    <span class="profile-value">
                        <?php
                        echo e(
                            $user['email']
                        );
                        ?>
                    </span>

                </div>


                <div class="profile-item">

                    <span class="profile-label">
                        Mobile
                    </span>

                    <span class="profile-value">
                        <?php
                        echo e(
                            $user['mobile']
                        );
                        ?>
                    </span>

                </div>


                <div class="profile-item">

                    <span class="profile-label">
                        Account
                    </span>

                    <span class="profile-value">
                        Active ✓
                    </span>

                </div>


                <div class="profile-actions">

                    <a
                        href="user_profile.php"
                        class="profile-btn"
                    >
                        ⚙️ Manage Profile
                    </a>

                    <a
                        href="user_bookings.php"
                        class="profile-btn"
                    >
                        🎫 View Booking History
                    </a>

                </div>

            </div>

        </div>

    </section>


    <!-- =====================================================
         RECENT BOOKINGS
    ====================================================== -->

    <section class="panel">

        <div class="panel-header">

            <div class="panel-title">
                🧾 Recent Bookings
            </div>

            <a
                href="user_bookings.php"
                class="panel-link"
            >
                View All →
            </a>

        </div>


        <div class="panel-body">


            <?php if (count($recentBookings) > 0): ?>


                <div class="table-wrap">

                    <table class="booking-table">

                        <thead>

                            <tr>

                                <th>
                                    Reference
                                </th>

                                <th>
                                    Route
                                </th>

                                <th>
                                    Bus
                                </th>

                                <th>
                                    Seats
                                </th>

                                <th>
                                    Amount
                                </th>

                                <th>
                                    Status
                                </th>

                                <th>
                                    Ticket
                                </th>

                            </tr>

                        </thead>


                        <tbody>


                        <?php foreach (
                            $recentBookings
                            as $booking
                        ): ?>

                            <tr>

                                <td>

                                    <span class="reference">

                                        <?php
                                        echo e(
                                            $booking['reference_no']
                                        );
                                        ?>

                                    </span>

                                </td>


                                <td>

                                    <div class="route-small">

                                        <?php
                                        echo e(
                                            $booking['origin_name']
                                        );
                                        ?>

                                        →

                                        <?php
                                        echo e(
                                            $booking['destination_name']
                                        );
                                        ?>

                                    </div>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $booking['bus_name']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo e(
                                        $booking['seats']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo money(
                                        $booking['total_amount']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <?php
                                    echo paymentBadge(
                                        $booking['payment_status']
                                    );
                                    ?>

                                </td>


                                <td>

                                    <a
                                        href="ticket.php?reference=<?php
                                            echo urlencode(
                                                $booking['reference_no']
                                            );
                                        ?>"
                                        class="ticket-btn"
                                    >
                                        View
                                    </a>

                                </td>

                            </tr>


                        <?php endforeach; ?>


                        </tbody>

                    </table>

                </div>


            <?php else: ?>


                <div class="empty-state">

                    <div class="empty-icon">
                        📋
                    </div>

                    <h3>
                        Your booking history is empty
                    </h3>

                    <p>
                        Book your first bus and it will appear here.
                    </p>

                    <a
                        href="schedule.php"
                        class="book-main-btn"
                    >
                        🚌 Explore Buses
                    </a>

                </div>


            <?php endif; ?>


        </div>

    </section>


</main>


</body>

</html>
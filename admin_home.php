
<?php

// ============================================================
// ADMIN AUTHENTICATION
// ============================================================

require_once 'config/admin_auth.php';


// ============================================================
// DATABASE CONNECTION
// ============================================================

require_once 'config/db_connect.php';


// ============================================================
// ADMIN ID
// ============================================================

$admin_id = (int) ($_SESSION['admin_id'] ?? 0);

if ($admin_id <= 0) {

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");

    exit;
}


// ============================================================
// DASHBOARD MAIN STATISTICS
// ============================================================

$dashboardStmt = $conn->prepare("
    SELECT

        (
            SELECT COUNT(*)
            FROM bus
        ) AS total_buses,

        (
            SELECT COUNT(*)
            FROM schedule
        ) AS total_schedules,

        (
            SELECT COUNT(*)
            FROM booked
        ) AS total_bookings,

        (
            SELECT COALESCE(
                SUM(total_amount),
                0
            )
            FROM booked
            WHERE payment_status = 'Paid'
        ) AS total_revenue,

        (
            SELECT COUNT(*)
            FROM booked
            WHERE DATE(booking_date) = CURDATE()
        ) AS today_bookings,

        (
            SELECT COALESCE(
                SUM(seats),
                0
            )
            FROM booked
        ) AS total_passengers,

        (
            SELECT COUNT(*)
            FROM booked
            WHERE payment_status != 'Paid'
        ) AS pending_payments,

        (
            SELECT COALESCE(
                SUM(total_amount),
                0
            )
            FROM booked
            WHERE payment_status != 'Paid'
        ) AS pending_amount

");


if (!$dashboardStmt) {

    http_response_code(500);

    exit("Unable to load dashboard data.");

}


if (!$dashboardStmt->execute()) {

    $dashboardStmt->close();

    http_response_code(500);

    exit("Unable to load dashboard data.");

}


$dashboardResult =
    $dashboardStmt->get_result();


if (!$dashboardResult) {

    $dashboardStmt->close();

    http_response_code(500);

    exit("Unable to read dashboard data.");

}


$dashboardData =
    $dashboardResult->fetch_assoc();


$dashboardStmt->close();


// ============================================================
// SAFE DASHBOARD VALUES
// ============================================================

$totalBuses =
    (int) ($dashboardData['total_buses'] ?? 0);

$totalSchedules =
    (int) ($dashboardData['total_schedules'] ?? 0);

$totalBookings =
    (int) ($dashboardData['total_bookings'] ?? 0);

$totalRevenue =
    (float) ($dashboardData['total_revenue'] ?? 0);

$todayBookings =
    (int) ($dashboardData['today_bookings'] ?? 0);

$totalPassengers =
    (int) ($dashboardData['total_passengers'] ?? 0);

$pendingPayments =
    (int) ($dashboardData['pending_payments'] ?? 0);

$pendingAmount =
    (float) ($dashboardData['pending_amount'] ?? 0);


// ============================================================
// PAYMENT SUMMARY
// ============================================================

$paymentStmt = $conn->prepare("
    SELECT
        SUM(
            CASE
                WHEN payment_status = 'Paid'
                THEN 1
                ELSE 0
            END
        ) AS paid_count,

        SUM(
            CASE
                WHEN payment_status != 'Paid'
                THEN 1
                ELSE 0
            END
        ) AS pending_count

    FROM booked
");


$paidCount = 0;
$pendingCount = 0;


if ($paymentStmt) {

    if ($paymentStmt->execute()) {

        $paymentResult =
            $paymentStmt->get_result();

        if ($paymentResult) {

            $paymentData =
                $paymentResult->fetch_assoc();

            $paidCount =
                (int) ($paymentData['paid_count'] ?? 0);

            $pendingCount =
                (int) ($paymentData['pending_count'] ?? 0);
        }
    }

    $paymentStmt->close();
}


// ============================================================
// TOP ROUTES
// ============================================================

$routeStmt = $conn->prepare("
    SELECT

        CONCAT(
            origin.location_name,
            ' → ',
            destination.location_name
        ) AS route_name,

        COUNT(booked.id) AS booking_count,

        COALESCE(
            SUM(booked.seats),
            0
        ) AS passenger_count,

        COALESCE(
            SUM(
                CASE
                    WHEN booked.payment_status = 'Paid'
                    THEN booked.total_amount
                    ELSE 0
                END
            ),
            0
        ) AS route_revenue

    FROM booked

    INNER JOIN schedule
        ON booked.schedule_id = schedule.id

    INNER JOIN location AS origin
        ON schedule.origin_id = origin.id

    INNER JOIN location AS destination
        ON schedule.destination_id = destination.id

    GROUP BY
        schedule.origin_id,
        schedule.destination_id,
        origin.location_name,
        destination.location_name

    ORDER BY
        booking_count DESC

    LIMIT 5
");


$routeData = [];


if ($routeStmt) {

    if ($routeStmt->execute()) {

        $routeResult =
            $routeStmt->get_result();

        if ($routeResult) {

            while (
                $routeRow =
                $routeResult->fetch_assoc()
            ) {

                $routeData[] =
                    $routeRow;
            }
        }
    }

    $routeStmt->close();
}


// ============================================================
// RECENT BOOKINGS
// ============================================================

$recentStmt = $conn->prepare("
    SELECT

        booked.id,

        booked.reference_no,

        booked.customer_name,

        booked.seats,

        booked.total_amount,

        booked.payment_status,

        booked.booking_date,

        bus.bus_number,

        CONCAT(
            origin.location_name,
            ' → ',
            destination.location_name
        ) AS route_name

    FROM booked

    INNER JOIN schedule
        ON booked.schedule_id = schedule.id

    INNER JOIN bus
        ON schedule.bus_id = bus.id

    INNER JOIN location AS origin
        ON schedule.origin_id = origin.id

    INNER JOIN location AS destination
        ON schedule.destination_id = destination.id

    ORDER BY
        booked.id DESC

    LIMIT 8
");


$recentBookings = [];


if ($recentStmt) {

    if ($recentStmt->execute()) {

        $recentResult =
            $recentStmt->get_result();

        if ($recentResult) {

            while (
                $recentRow =
                $recentResult->fetch_assoc()
            ) {

                $recentBookings[] =
                    $recentRow;
            }
        }
    }

    $recentStmt->close();
}


// ============================================================
// HELPER FUNCTIONS
// ============================================================

function safeOutput($value)
{
    return htmlspecialchars(
        (string) ($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}


function formatMoney($value)
{
    return number_format(
        (float) $value,
        2
    );
}


function formatDateTime($value)
{
    if (
        empty($value) ||
        $value === '0000-00-00 00:00:00'
    ) {
        return '-';
    }

    $timestamp =
        strtotime($value);

    if ($timestamp === false) {
        return '-';
    }

    return date(
        'd M Y, h:i A',
        $timestamp
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
        content="noindex,nofollow"
    >

    <title>
        Admin Dashboard - OBRS
    </title>


    <!-- Bootstrap -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {

            background:
                #f5f7fb;

            color:
                #212529;

        }


        .sidebar {

            min-height:
                calc(100vh - 56px);

            background:
                #212529;

        }


        .sidebar a {

            color:
                #ddd;

            text-decoration:
                none;

            display:
                block;

            padding:
                12px 20px;

            transition:
                0.2s;

        }


        .sidebar a:hover {

            background:
                #343a40;

            color:
                white;

        }


        .dashboard-card {

            border:
                none;

            border-radius:
                16px;

            transition:
                0.25s;

        }


        .dashboard-card:hover {

            transform:
                translateY(-3px);

        }


        .dashboard-icon {

            font-size:
                34px;

        }


        .analytics-card {

            border:
                none;

            border-radius:
                16px;

        }


        .section-title {

            font-weight:
                700;

        }


        .stat-number {

            font-size:
                28px;

        }


        .progress {

            height:
                8px;

            border-radius:
                20px;

        }


        .route-row {

            padding:
                14px 0;

            border-bottom:
                1px solid #eee;

        }


        .route-row:last-child {

            border-bottom:
                none;

        }


        .recent-table th {

            white-space:
                nowrap;

            font-size:
                13px;

        }


        .recent-table td {

            vertical-align:
                middle;

            font-size:
                14px;

        }


        .status-badge {

            font-size:
                12px;

            padding:
                6px 10px;

            border-radius:
                20px;

        }


        .dashboard-header {

            background:
                linear-gradient(
                    135deg,
                    #212529,
                    #343a40
                );

            color:
                white;

            border-radius:
                18px;

            padding:
                25px;

        }


        @media (max-width: 767px) {

            .sidebar {

                min-height:
                    auto;

            }

            .dashboard-header {

                padding:
                    20px;

            }

            .stat-number {

                font-size:
                    24px;

            }

        }

    </style>

</head>


<body>


<!-- ============================================================
     NAVBAR
============================================================= -->

<nav class="navbar navbar-dark bg-dark">

    <div class="container-fluid">

        <a
            class="navbar-brand fw-bold"
            href="admin_home.php"
        >

            🚍 OBRS Admin

        </a>


        <div class="text-white">

            Welcome,

            <strong>

                <?php

                echo safeOutput(
                    $_SESSION['admin_name'] ?? 'Admin'
                );

                ?>

            </strong>


            <a
                href="logout.php"
                class="btn btn-danger btn-sm ms-3"
            >

                Logout

            </a>

        </div>

    </div>

</nav>


<!-- ============================================================
     MAIN LAYOUT
============================================================= -->

<div class="container-fluid">

    <div class="row">
        <!-- ====================================================
             SIDEBAR
        ===================================================== -->

        <div class="col-md-3 col-lg-2 p-0 sidebar">

            <div class="py-3">

                <div class="text-white px-3 mb-2">

                    ADMIN MENU

                </div>


                <a href="admin_home.php">

                    🏠 Dashboard

                </a>


                <a href="booked.php">

                    🎫 Reservations

                </a>


                <a href="bus.php">

                    🚌 Manage Buses

                </a>


                <a href="location.php">

                    📍 Manage Locations

                </a>


                <a href="manage_schedule.php">

                    📅 Manage Schedules

                </a>
                    <a href="admin_support.php">
                         🎫 Support Tickets
                    </a>
                <a href="index.php">

                    🌐 View Website

                </a>

            </div>

        </div>
        <!-- ====================================================
             DASHBOARD CONTENT
        ===================================================== -->

        <div class="col-md-9 col-lg-10">
            <div class="container-fluid py-4">


                <!-- ==================================================
                     HEADER
                =================================================== -->

                <div class="dashboard-header mb-4">

                    <div
                        class="d-flex justify-content-between align-items-center flex-wrap gap-3"
                    >

                        <div>

                            <h2 class="fw-bold mb-1">

                                Admin Dashboard

                            </h2>

                            <p class="mb-0 text-white-50">

                                Monitor bookings, passengers,
                                revenue and payment activity.

                            </p>

                        </div>


                        <div>

                            <span class="badge bg-light text-dark p-2">

                                📊 Live Dashboard

                            </span>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     STAT CARDS
                =================================================== -->

                <div class="row g-4">


                    <!-- TOTAL BUSES -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Total Buses

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            <?php
                                            echo $totalBuses;
                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        🚌

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- TOTAL SCHEDULES -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Total Schedules

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            <?php
                                            echo $totalSchedules;
                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        📅

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- TOTAL BOOKINGS -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Total Bookings

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            <?php
                                            echo $totalBookings;
                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        🎫

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PAID REVENUE -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Paid Revenue

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            ₹<?php

                                            echo formatMoney(
                                                $totalRevenue
                                            );

                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        💰

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- TODAY BOOKINGS -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Today's Bookings

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            <?php
                                            echo $todayBookings;
                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        📌

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PASSENGERS -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Total Passengers

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            <?php
                                            echo $totalPassengers;
                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        👥

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PENDING PAYMENTS -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Pending Payments

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            <?php
                                            echo $pendingPayments;
                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        ⏳

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- PENDING AMOUNT -->

                    <div class="col-md-6 col-xl-3">

                        <div
                            class="card dashboard-card shadow-sm"
                        >

                            <div class="card-body">

                                <div
                                    class="d-flex justify-content-between"
                                >

                                    <div>

                                        <h6 class="text-muted">

                                            Pending Amount

                                        </h6>

                                        <h2
                                            class="fw-bold stat-number"
                                        >

                                            ₹<?php

                                            echo formatMoney(
                                                $pendingAmount
                                            );

                                            ?>

                                        </h2>

                                    </div>

                                    <div
                                        class="dashboard-icon"
                                    >

                                        💳

                                    </div>

                                </div>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     ANALYTICS SECTION
                =================================================== -->

                <div class="row g-4 mt-2">


                    <!-- PAYMENT OVERVIEW -->

                    <div class="col-lg-5">

                        <div
                            class="card analytics-card shadow-sm h-100"
                        >

                            <div class="card-body p-4">

                                <h4 class="section-title mb-4">

                                    💳 Payment Overview

                                </h4>


                                <div class="mb-4">

                                    <div
                                        class="d-flex justify-content-between mb-2"
                                    >

                                        <span>
                                            Paid Bookings
                                        </span>

                                        <strong>

                                            <?php
                                            echo $paidCount;
                                            ?>

                                        </strong>

                                    </div>


                                    <div class="progress">

                                        <div
                                            class="progress-bar bg-success"
                                            role="progressbar"
                                            style="width: <?php

                                                $paymentTotal =
                                                    $paidCount +
                                                    $pendingCount;

                                                echo $paymentTotal > 0
                                                    ? ($paidCount / $paymentTotal) * 100
                                                    : 0;

                                            ?>%;"
                                        ></div>

                                    </div>

                                </div>


                                <div class="mb-4">

                                    <div
                                        class="d-flex justify-content-between mb-2"
                                    >

                                        <span>
                                            Pending Bookings
                                        </span>

                                        <strong>

                                            <?php
                                            echo $pendingCount;
                                            ?>

                                        </strong>

                                    </div>


                                    <div class="progress">

                                        <div
                                            class="progress-bar bg-warning"
                                            role="progressbar"
                                            style="width: <?php

                                                echo $paymentTotal > 0
                                                    ? ($pendingCount / $paymentTotal) * 100
                                                    : 0;

                                            ?>%;"
                                        ></div>

                                    </div>

                                </div>


                                <div
                                    class="alert alert-light border mb-0"
                                >

                                    <strong>
                                        Paid Revenue:
                                    </strong>

                                    ₹<?php
                                    echo formatMoney(
                                        $totalRevenue
                                    );
                                    ?>

                                </div>

                            </div>

                        </div>

                    </div>


                    <!-- TOP ROUTES -->

                    <div class="col-lg-7">

                        <div
                            class="card analytics-card shadow-sm h-100"
                        >

                            <div class="card-body p-4">

                                <h4 class="section-title mb-3">

                                    🛣️ Top Routes

                                </h4>


                                <?php if (empty($routeData)): ?>

                                    <div
                                        class="text-center text-muted py-4"
                                    >

                                        No route booking data available.

                                    </div>

                                <?php else: ?>

                                    <?php foreach (
                                        $routeData
                                        as $route
                                    ): ?>

                                        <div
                                            class="route-row"
                                        >

                                            <div
                                                class="d-flex justify-content-between align-items-center gap-3"
                                            >

                                                <div>

                                                    <div
                                                        class="fw-bold"
                                                    >

                                                        <?php

                                                        echo safeOutput(
                                                            $route['route_name']
                                                        );

                                                        ?>

                                                    </div>

                                                    <small
                                                        class="text-muted"
                                                    >

                                                        <?php

                                                        echo (int)
                                                            $route['passenger_count'];

                                                        ?>

                                                        passengers

                                                        ·

                                                        ₹<?php

                                                        echo formatMoney(
                                                            $route['route_revenue']
                                                        );

                                                        ?>

                                                        paid

                                                    </small>

                                                </div>


                                                <span
                                                    class="badge bg-primary"
                                                >

                                                    <?php

                                                    echo (int)
                                                        $route['booking_count'];

                                                    ?>

                                                    bookings

                                                </span>

                                            </div>

                                        </div>

                                    <?php endforeach; ?>

                                <?php endif; ?>

                            </div>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     RECENT BOOKINGS
                =================================================== -->

                <div
                    class="card analytics-card shadow-sm mt-4"
                >

                    <div class="card-body p-4">

                        <div
                            class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"
                        >

                            <h4 class="section-title mb-0">

                                🎫 Recent Bookings

                            </h4>


                            <a
                                href="booked.php"
                                class="btn btn-sm btn-primary"
                            >

                                View All

                            </a>

                        </div>


                        <div class="table-responsive">

                            <table
                                class="table table-hover recent-table mb-0"
                            >

                                <thead>

                                    <tr>

                                        <th>
                                            Reference
                                        </th>

                                        <th>
                                            Passenger
                                        </th>

                                        <th>
                                            Route
                                        </th>

                                        <th>
                                            Seats
                                        </th>

                                        <th>
                                            Amount
                                        </th>

                                        <th>
                                            Payment
                                        </th>

                                        <th>
                                            Date
                                        </th>

                                    </tr>

                                </thead>


                                <tbody>

                                    <?php if (
                                        empty($recentBookings)
                                    ): ?>

                                        <tr>

                                            <td
                                                colspan="7"
                                                class="text-center text-muted py-4"
                                            >

                                                No bookings found.

                                            </td>

                                        </tr>

                                    <?php else: ?>


                                        <?php foreach (
                                            $recentBookings
                                            as $booking
                                        ): ?>

                                            <tr>

                                                <td>

                                                    <span
                                                        class="fw-bold"
                                                    >

                                                        <?php

                                                        echo safeOutput(
                                                            $booking['reference_no']
                                                        );

                                                        ?>

                                                    </span>

                                                </td>


                                                <td>

                                                    <?php

                                                    echo safeOutput(
                                                        $booking['customer_name']
                                                    );

                                                    ?>

                                                </td>


                                                <td>

                                                    <?php

                                                    echo safeOutput(
                                                        $booking['route_name']
                                                    );

                                                    ?>

                                                    <br>

                                                    <small
                                                        class="text-muted"
                                                    >

                                                        Bus:

                                                        <?php

                                                        echo safeOutput(
                                                            $booking['bus_number']
                                                        );

                                                        ?>

                                                    </small>

                                                </td>


                                                <td>

                                                    <?php

                                                    echo (int)
                                                        $booking['seats'];

                                                    ?>

                                                </td>


                                                <td>

                                                    ₹<?php

                                                    echo formatMoney(
                                                        $booking['total_amount']
                                                    );

                                                    ?>

                                                </td>


                                                <td>

                                                    <?php

                                                    if (
                                                        $booking['payment_status']
                                                        === 'Paid'
                                                    ) {

                                                        echo '<span class="badge bg-success status-badge">Paid</span>';

                                                    } else {

                                                        echo '<span class="badge bg-warning text-dark status-badge">Pending</span>';

                                                    }

                                                    ?>

                                                </td>


                                                <td>

                                                    <small>

                                                        <?php

                                                        echo formatDateTime(
                                                            $booking['booking_date']
                                                        );

                                                        ?>

                                                    </small>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>


                                    <?php endif; ?>

                                </tbody>

                            </table>

                        </div>

                    </div>

                </div>


                <!-- ==================================================
                     QUICK ACTIONS
                =================================================== -->

                <div
                    class="card shadow-sm border-0 mt-4"
                >

                    <div class="card-body p-4">

                        <h4 class="fw-bold mb-4">

                            Quick Actions

                        </h4>


                        <div class="row g-3">


                            <div class="col-md-4">

                                <a
                                    href="booked.php"
                                    class="btn btn-primary w-100 p-3"
                                >

                                    🎫

                                    <br>

                                    Manage Reservations

                                </a>

                            </div>


                            <div class="col-md-4">

                                <a
                                    href="bus.php"
                                    class="btn btn-success w-100 p-3"
                                >

                                    🚌

                                    <br>

                                    Manage Buses

                                </a>

                            </div>


                            <div class="col-md-4">

                                <a
                                    href="manage_schedule.php"
                                    class="btn btn-warning w-100 p-3"
                                >

                                    📅

                                    <br>

                                    Manage Schedules

                                </a>

                            </div>


                        </div>

                    </div>

                </div>


            </div>

        </div>

    </div>

</div>


</body>

</html>

<?php


if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$isEmbedded = defined('OBRS_EMBED_SCHEDULE') && OBRS_EMBED_SCHEDULE;

require_once 'config/db_connect.php';

if (
    !$isEmbedded &&
    (
        empty($_SESSION['user_logged_in']) ||
        empty($_SESSION['user_id'])
    )
) {
    header('Location: user_login.php');
    exit;
}

$userId = (int)($_SESSION['user_id'] ?? 0);

$userStmt = $conn->prepare("
    SELECT full_name, email, mobile
    FROM customer_users
    WHERE id = ?
      AND status = 'Active'
    LIMIT 1
");

if (!$userStmt) {
    exit('Unable to load your account.');
}

$userStmt->bind_param("i", $userId);
$userStmt->execute();

$userResult = $userStmt->get_result();
$customer = $userResult->fetch_assoc();

$userStmt->close();


if (!$customer) {
    if ($isEmbedded) {
        echo '
        <style>
            .login-prompt {
                max-width: 520px;
                margin: 35px auto;
                padding: 32px 24px;
                text-align: center;
                color: #f8fafc;
                background: linear-gradient(145deg, #171b2e, #101322);
                border: 1px solid rgba(139, 92, 246, 0.35);
                border-radius: 22px;
                box-shadow: 0 15px 40px rgba(0, 0, 0, 0.25);
                font-family: Arial, sans-serif;
            }

            .login-prompt .login-icon {
                font-size: 42px;
                margin-bottom: 12px;
            }

            .login-prompt h3 {
                margin: 0 0 12px;
                font-size: 25px;
                font-weight: 800;
                color: #ffffff;
            }

            .login-prompt p {
                margin: 0 0 24px;
                color: #b8bfd3;
                font-size: 15px;
                line-height: 1.7;
            }

            .login-prompt .login-btn {
                display: inline-block;
                padding: 13px 25px;
                border-radius: 12px;
                color: #ffffff;
                background: linear-gradient(90deg, #7c3aed, #4f46e5);
                text-decoration: none;
                font-size: 15px;
                font-weight: 700;
                transition: transform 0.2s ease,
                            box-shadow 0.2s ease;
            }

            .login-prompt .login-btn:hover {
                color: #ffffff;
                transform: translateY(-3px);
                box-shadow: 0 8px 24px rgba(124, 58, 237, 0.4);
            }

            @media (max-width: 480px) {
                .login-prompt {
                    margin: 20px 12px;
                    padding: 26px 18px;
                }

                .login-prompt h3 {
                    font-size: 22px;
                }
            }
        </style>

        <div class="login-prompt">
            <div class="login-icon">🚌</div>
            <h3>Your Next Trip Starts Here!</h3>
            <p>
                No login, no booking 😅<br>
                Sign in to grab your seat and make your next ride happen.
            </p>
            <a class="login-btn" href="user_login.php">
                Login Now &nbsp; →
            </a>
        </div>
        ';
        return;
    }

    header("Location: user_login.php");
    exit;
}

$sql = "
    SELECT
        s.id,
        b.bus_name,
        b.bus_number,
        b.total_seats,
        o.location_name AS origin,
        d.location_name AS destination,
        s.departure_time,
        s.eta_time,
        s.available_seats,
        s.ticket_price
    FROM schedule s
    INNER JOIN bus b ON s.bus_id = b.id
    INNER JOIN location o ON s.origin_id = o.id
    INNER JOIN location d ON s.destination_id = d.id
    ORDER BY s.departure_time ASC
";

$result = $conn->query($sql);

function h($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function scheduleDate($value): string
{
    if (!$value || strtotime($value) === false) {
        return 'Not available';
    }

    return date('d M Y, h:i A', strtotime($value));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Choose Your Bus | OBRS</title>

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
        rel="stylesheet"
    >

    <style>
        :root {
            --bg: #080d1b;
            --panel: #111a2e;
            --panel2: #17233b;
            --text: #f4f7ff;
            --muted: #9ba9c5;
            --accent: #7c5cff;
            --cyan: #39d9ff;
            --border: rgba(255,255,255,.10);
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            min-height: 100vh;
            color: var(--text);
            background:
                radial-gradient(
                    circle at 10% 0%,
                    rgba(124,92,255,.20),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 95% 15%,
                    rgba(57,217,255,.10),
                    transparent 30%
                ),
                var(--bg);
            font-family: Inter, "Segoe UI", Arial, sans-serif;
        }

        a {
            text-decoration: none;
        }

        .topbar {
            position: sticky;
            top: 0;
            z-index: 100;
            background: rgba(8,13,27,.88);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid var(--border);
        }

        .nav-inner {
            max-width: 1200px;
            margin: auto;
            padding: 15px 22px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 15px;
        }

        .brand {
            color: white;
            font-weight: 800;
            font-size: 21px;
            letter-spacing: -.5px;
        }

        .brand i {
            color: var(--cyan);
            margin-right: 8px;
        }

        .nav-links {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 9px;
        }

        .nav-links a {
            color: #c7d1e7;
            padding: 9px 12px;
            border-radius: 10px;
            font-size: 13px;
        }

        .nav-links a:hover,
        .nav-links a.active {
            color: white;
            background: rgba(124,92,255,.20);
        }

        .page {
            width: min(1200px, 100%);
            margin: auto;
            padding: 38px 22px 60px;
        }

        .hero {
            padding: 34px;
            border: 1px solid var(--border);
            border-radius: 25px;
            background:
                linear-gradient(
                    120deg,
                    rgba(124,92,255,.20),
                    rgba(57,217,255,.06)
                ),
                var(--panel);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .hero::after {
            content: "✦";
            position: absolute;
            right: 45px;
            top: 5px;
            font-size: 130px;
            color: rgba(255,255,255,.05);
        }

        .eyebrow {
            color: var(--cyan);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 2px;
            text-transform: uppercase;
        }

        .hero h1 {
            font-weight: 850;
            letter-spacing: -1.5px;
            font-size: clamp(30px, 5vw, 46px);
            margin: 12px 0;
        }

        .hero p {
            color: var(--muted);
            max-width: 600px;
            margin-bottom: 0;
            line-height: 1.7;
        }

        .section-heading {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin: 28px 0 18px;
        }

        .section-heading h2 {
            font-size: 23px;
            font-weight: 800;
            margin: 0;
        }

        .schedule-count {
            color: var(--muted);
            font-size: 13px;
        }

        .schedule-card {
            height: 100%;
            padding: 23px;
            border: 1px solid var(--border);
            border-radius: 21px;
            background: linear-gradient(
                145deg,
                rgba(23,35,59,.98),
                rgba(14,22,39,.98)
            );
            transition: transform .2s, border-color .2s;
        }

        .schedule-card:hover {
            transform: translateY(-4px);
            border-color: rgba(124,92,255,.65);
        }

        .bus-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
            margin-bottom: 22px;
        }

        .bus-icon {
            width: 48px;
            height: 48px;
            display: grid;
            place-items: center;
            border-radius: 15px;
            background: rgba(124,92,255,.17);
            color: #b8aaff;
            font-size: 23px;
        }

        .bus-name {
            font-weight: 800;
            font-size: 17px;
            margin-bottom: 4px;
        }

        .bus-number {
            color: var(--muted);
            font-size: 12px;
        }

        .seat-pill {
            font-size: 11px;
            font-weight: 700;
            border-radius: 30px;
            padding: 7px 10px;
            color: #74f0bd;
            background: rgba(35,205,135,.12);
            white-space: nowrap;
        }

        .seat-pill.full {
            color: #ff929e;
            background: rgba(255,75,95,.12);
        }

        .route {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 12px;
            padding: 18px 0;
            border-top: 1px solid var(--border);
            border-bottom: 1px solid var(--border);
        }

        .route-point:last-child {
            text-align: right;
        }

        .route-city {
            font-weight: 800;
            font-size: 15px;
            overflow-wrap: anywhere;
        }

        .route-label {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 5px;
        }

        .route-line {
            color: var(--cyan);
            font-size: 19px;
        }

        .time-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 14px;
            padding: 20px 0;
        }

        .time-label {
            color: var(--muted);
            font-size: 11px;
            margin-bottom: 6px;
        }

        .time-value {
            font-size: 13px;
            font-weight: 700;
            line-height: 1.5;
        }

        .card-bottom {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
        }

        .price-label {
            color: var(--muted);
            font-size: 11px;
        }

        .price {
            font-size: 24px;
            font-weight: 850;
        }

        .reserve-btn {
            border: 0;
            border-radius: 12px;
            color: white;
            font-weight: 800;
            font-size: 13px;
            padding: 12px 15px;
            background: linear-gradient(
                100deg,
                var(--accent),
                #5c8cff
            );
            box-shadow: 0 8px 22px rgba(124,92,255,.22);
        }

        .reserve-btn:hover {
            color: white;
            filter: brightness(1.12);
        }

        .empty-state {
            text-align: center;
            padding: 55px 20px;
            border: 1px dashed var(--border);
            border-radius: 20px;
            color: var(--muted);
        }

        .modal-content {
            color: #172033;
            border: 0;
            border-radius: 22px;
            overflow: hidden;
        }

        .modal-header {
            padding: 20px 23px;
            color: white;
            border: 0;
            background: linear-gradient(110deg, #5b45d6, #347ce9);
        }

        .modal-body {
            padding: 23px;
        }

        .journey-box {
            background: #f3f6ff;
            border: 1px solid #e3e9fa;
            border-radius: 15px;
            padding: 16px;
            margin-bottom: 20px;
        }

        .journey-box .small-label {
            color: #69748a;
            font-size: 12px;
        }

        .seat-legend {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
            font-size: 12px;
            color: #566176;
        }

        .legend-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .legend-square {
            width: 15px;
            height: 15px;
            border-radius: 4px;
            border: 1px solid #cbd3e1;
        }

        .legend-available {
            background: white;
        }

        .legend-selected {
            background: #6554e8;
        }

        .legend-booked {
            background: #e65b6b;
        }

        .seat-panel {
            border-radius: 17px;
            background: #f5f7fc;
            border: 1px solid #e4e9f3;
            padding: 18px 10px;
            margin-bottom: 18px;
        }

        .driver {
            text-align: right;
            max-width: 320px;
            margin: 0 auto 18px;
            padding-bottom: 12px;
            border-bottom: 1px dashed #c6cede;
            color: #667085;
            font-size: 12px;
        }

        .seat-grid {
            display: grid;
            grid-template-columns: repeat(2, 44px) 25px repeat(2, 44px);
            gap: 9px;
            justify-content: center;
        }

        .seat-btn {
            width: 44px;
            height: 42px;
            border: 1px solid #cbd3e1;
            border-radius: 9px;
            background: white;
            color: #253047;
            font-size: 12px;
            font-weight: 800;
        }

        .seat-btn:hover:not(:disabled) {
            border-color: #6554e8;
        }

        .seat-btn.selected {
            background: #6554e8;
            color: white;
            border-color: #6554e8;
        }

        .seat-btn.booked {
            background: #e65b6b;
            color: white;
            border-color: #e65b6b;
            cursor: not-allowed;
        }

        .seat-aisle {
            width: 25px;
        }

        .selected-summary {
            border: 1px solid #e4e9f3;
            border-radius: 13px;
            padding: 13px;
            background: #fbfcff;
            margin-bottom: 20px;
        }

        .selected-badges {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 8px;
        }

        .selected-badge {
            background: #e9e6ff;
            color: #5141c5;
            padding: 5px 9px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .total-box {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-radius: 13px;
            background: #f0f3fb;
            margin: 18px 0;
        }

        .confirm-btn {
            border: 0;
            background: linear-gradient(100deg, #5b45d6, #347ce9);
            color: white;
            font-weight: 800;
            border-radius: 12px;
            padding: 13px;
        }

        .confirm-btn:disabled {
            opacity: .55;
        }

        .success-panel {
            text-align: center;
            padding: 25px 10px;
        }

        .success-icon {
            font-size: 50px;
            color: #159b69;
        }

        .reference {
            background: #f1f4fb;
            border-radius: 10px;
            padding: 12px;
            font-weight: 800;
            letter-spacing: 1px;
            overflow-wrap: anywhere;
        }

        @media (max-width: 600px) {
            .nav-inner {
                align-items: flex-start;
                flex-direction: column;
            }

            .page {
                padding: 22px 14px 40px;
            }

            .hero {
                padding: 25px 21px;
            }

            .schedule-card {
                padding: 19px;
            }

            .modal-body {
                padding: 16px;
            }

            .seat-grid {
                grid-template-columns: repeat(2, 39px) 20px repeat(2, 39px);
                gap: 7px;
            }

            .seat-btn {
                width: 39px;
                height: 39px;
            }

            .seat-aisle {
                width: 20px;
            }
        }
    </style>
</head>

<body>

<nav class="topbar">
    <div class="nav-inner">
        <a class="brand" href="user_dashboard.php">
            <i class="bi bi-bus-front-fill"></i>OBRS
        </a>

        <div class="nav-links">
            <a href="user_dashboard.php">
                <i class="bi bi-grid me-1"></i>Dashboard
            </a>
            <a href="user_bookings.php">
                <i class="bi bi-ticket-perforated me-1"></i>My Bookings
            </a>
            <a href="schedule.php" class="active">
                <i class="bi bi-search me-1"></i>Book a Bus
            </a>
            <a href="user_profile.php">
                <i class="bi bi-person me-1"></i>Profile
            </a>
            <a href="user_logout.php">
                <i class="bi bi-box-arrow-right me-1"></i>Logout
            </a>
        </div>
    </div>
</nav>

<main class="page">

    <section class="hero">
        <div class="eyebrow">Your journey starts here</div>
        <h1>Find your next <span style="color:#8c7aff">ride.</span></h1>
        <p>
            Hi <?= h($customer['full_name']) ?>! Choose your route,
            select your favourite seats, and reserve your bus in a few clicks.
        </p>
    </section>

    <div class="section-heading">
        <h2><i class="bi bi-compass me-2" style="color:#39d9ff"></i>Available Buses</h2>
        <span class="schedule-count">
            <?= $result ? (int)$result->num_rows : 0 ?> schedules
        </span>
    </div>

    <?php if (!$result): ?>

        <div class="empty-state">
            <i class="bi bi-exclamation-circle fs-1"></i>
            <h4 class="mt-3">Unable to load schedules</h4>
            <p>Please refresh the page or try again later.</p>
        </div>

    <?php elseif ($result->num_rows === 0): ?>

        <div class="empty-state">
            <i class="bi bi-bus-front fs-1"></i>
            <h4 class="mt-3">No buses available right now</h4>
            <p>Please check again later.</p>
        </div>

    <?php else: ?>

        <div class="row g-4">

        <?php while ($row = $result->fetch_assoc()): ?>

            <?php
                $available = (int)$row['available_seats'];
                $price = (float)$row['ticket_price'];
            ?>

            <div class="col-12 col-md-6 col-xl-4">
                <article class="schedule-card">

                    <div class="bus-top">
                        <div class="d-flex gap-3">
                            <div class="bus-icon">
                                <i class="bi bi-bus-front-fill"></i>
                            </div>

                            <div>
                                <div class="bus-name">
                                    <?= h($row['bus_name']) ?>
                                </div>
                                <div class="bus-number">
                                    <?= h($row['bus_number']) ?>
                                </div>
                            </div>
                        </div>

                        <span class="seat-pill <?= $available <= 0 ? 'full' : '' ?>">
                            <?= $available > 0
                                ? $available . ' seats left'
                                : 'Fully booked' ?>
                        </span>
                    </div>

                    <div class="route">
                        <div class="route-point">
                            <div class="route-label">FROM</div>
                            <div class="route-city"><?= h($row['origin']) ?></div>
                        </div>

                        <div class="route-line">
                            <i class="bi bi-arrow-right"></i>
                        </div>

                        <div class="route-point">
                            <div class="route-label">TO</div>
                            <div class="route-city"><?= h($row['destination']) ?></div>
                        </div>
                    </div>

                    <div class="time-grid">
                        <div>
                            <div class="time-label">
                                <i class="bi bi-calendar-event me-1"></i>Departure
                            </div>
                            <div class="time-value">
                                <?= h(scheduleDate($row['departure_time'])) ?>
                            </div>
                        </div>

                        <div>
                            <div class="time-label">
                                <i class="bi bi-flag me-1"></i>Arrival
                            </div>
                            <div class="time-value">
                                <?= h(scheduleDate($row['eta_time'])) ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-bottom">
                        <div>
                            <div class="price-label">TICKET PRICE</div>
                            <div class="price">
                                ₹<?= number_format($price, 2) ?>
                            </div>
                        </div>

                        <?php if ($available > 0 && $price > 0): ?>
                            <button
                                type="button"
                                class="reserve-btn"
                                data-schedule-id="<?= (int)$row['id'] ?>"
                                data-bus-name="<?= h($row['bus_name']) ?>"
                                data-route="<?= h($row['origin'] . ' → ' . $row['destination']) ?>"
                                data-price="<?= h($price) ?>"
                                data-seats="<?= $available ?>"
                            >
                                <i class="bi bi-ticket-perforated me-1"></i>
                                Reserve Now
                            </button>
                        <?php else: ?>
                            <button class="btn btn-secondary" disabled>
                                Unavailable
                            </button>
                        <?php endif; ?>
                    </div>

                </article>
            </div>

        <?php endwhile; ?>

        </div>
    <?php endif; ?>

</main>

<!-- BOOKING MODAL -->
<div class="modal fade" id="reservationModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header">
                <div>
                    <h5 class="modal-title fw-bold">
                        <i class="bi bi-ticket-perforated me-2"></i>Complete Your Booking
                    </h5>
                    <small class="opacity-75">Choose seats and confirm your details</small>
                </div>

                <button
                    type="button"
                    class="btn-close btn-close-white"
                    data-bs-dismiss="modal"
                    aria-label="Close"
                ></button>
            </div>

            <div class="modal-body">

                <form id="bookingForm">

                    <input type="hidden" name="schedule_id" id="schedule_id">
                    <input type="hidden" name="selected_seats" id="selected_seats">
                    <input type="hidden" name="seats" id="seats" value="0">
                    <input type="hidden" id="ticketPriceHidden">

                    <div class="journey-box">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <div class="small-label">BUS</div>
                                <strong id="displayBusName">-</strong>
                            </div>

                            <div class="col-sm-6">
                                <div class="small-label">ROUTE</div>
                                <strong id="displayRoute">-</strong>
                            </div>

                            <div class="col-sm-6">
                                <div class="small-label">PRICE PER SEAT</div>
                                <strong id="displayPrice">₹0.00</strong>
                            </div>

                            <div class="col-sm-6">
                                <div class="small-label">AVAILABLE SEATS</div>
                                <strong id="displaySeats">0</strong>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold mb-3">Select your seats</h6>

                    <div class="seat-legend">
                        <span class="legend-item">
                            <span class="legend-square legend-available"></span>
                            Available
                        </span>
                        <span class="legend-item">
                            <span class="legend-square legend-selected"></span>
                            Selected
                        </span>
                        <span class="legend-item">
                            <span class="legend-square legend-booked"></span>
                            Booked
                        </span>
                    </div>

                    <div class="seat-panel">
                        <div class="driver">
                            <i class="bi bi-steering-wheel me-1"></i>Driver
                        </div>

                        <div id="seatMap" class="seat-grid">
                            <div style="grid-column:1/-1;text-align:center">
                                Loading seats...
                            </div>
                        </div>
                    </div>

                    <div class="selected-summary">
                        <div class="d-flex justify-content-between">
                            <strong>Selected Seats</strong>
                            <span class="badge text-bg-primary" id="selectedSeatCount">0</span>
                        </div>

                        <div id="selectedSeatList" class="selected-badges">
                            <span class="text-secondary">No seats selected</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="customer_name" class="form-label fw-semibold">
                            Full Name
                        </label>
                        <input
                            type="text"
                            class="form-control"
                            id="customer_name"
                            name="customer_name"
                            maxlength="150"
                            autocomplete="name"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="customer_phone" class="form-label fw-semibold">
                            Mobile Number
                        </label>
                        <input
                            type="tel"
                            class="form-control"
                            id="customer_phone"
                            name="customer_phone"
                            maxlength="10"
                            minlength="10"
                            inputmode="numeric"
                            autocomplete="tel"
                            required
                        >
                    </div>

                    <div class="mb-3">
                        <label for="customer_email" class="form-label fw-semibold">
                            Email Address
                        </label>
                        <input
                            type="email"
                            class="form-control"
                            id="customer_email"
                            name="customer_email"
                            maxlength="150"
                            autocomplete="email"
                            required
                        >
                    </div>

                    <div class="total-box">
                        <span class="fw-semibold">Total Amount</span>
                        <strong class="fs-5" id="totalAmount">₹0.00</strong>
                    </div>

                    <button
                        type="submit"
                        class="confirm-btn w-100"
                        id="bookingSubmitBtn"
                        disabled
                    >
                        <i class="bi bi-lock-fill me-1"></i>
                        Confirm Reservation
                    </button>

                    <div id="bookingError" class="alert alert-danger mt-3 d-none"></div>
                </form>

                <div id="bookingSuccess" class="success-panel d-none">
                    <div class="success-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>

                    <h4 class="fw-bold mt-2">Booking Successful!</h4>
                    <p class="text-secondary" id="successMessage">
                        Your reservation has been created.
                    </p>

                    <div class="text-secondary small mb-2">
                        Booking Reference
                    </div>

                    <div class="reference" id="referenceNumber">-</div>

                    <p class="mt-3 mb-1" id="successSeats"></p>
                    <h5 id="successTotal" class="fw-bold"></h5>

                    <div class="d-flex flex-wrap justify-content-center gap-2 mt-4">
                        <a href="#" id="ticketLink" class="btn btn-primary">
                            <i class="bi bi-ticket-detailed me-1"></i>View Ticket
                        </a>

                        <button type="button" class="btn btn-outline-secondary" id="doneBtn">
                            Done
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modalElement = document.getElementById('reservationModal');
    const bookingModal = bootstrap.Modal.getOrCreateInstance(modalElement);

    const bookingForm = document.getElementById('bookingForm');
    const bookingSuccess = document.getElementById('bookingSuccess');
    const bookingError = document.getElementById('bookingError');
    const seatMap = document.getElementById('seatMap');
    const submitButton = document.getElementById('bookingSubmitBtn');

    let selectedSeats = [];
    let currentScheduleId = '';
    let currentPrice = 0;
    let requestInProgress = false;

    const customer = {
        name: <?= json_encode($customer['full_name'] ?? '') ?>,
        email: <?= json_encode($customer['email'] ?? '') ?>,
        mobile: <?= json_encode($customer['mobile'] ?? '') ?>
    };

    function escapeHtml(value) {
        return String(value ?? '').replace(/[&<>"']/g, function (char) {
            return {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char];
        });
    }

    function showError(message) {
        bookingError.textContent = message;
        bookingError.classList.remove('d-none');
    }

    function clearError() {
        bookingError.textContent = '';
        bookingError.classList.add('d-none');
    }

    function updateSeatSummary() {
        const count = selectedSeats.length;
        const total = count * currentPrice;

        document.getElementById('selected_seats').value =
            JSON.stringify(selectedSeats);

        document.getElementById('seats').value = count;
        document.getElementById('selectedSeatCount').textContent = count;
        document.getElementById('totalAmount').textContent =
            '₹' + total.toFixed(2);

        document.getElementById('selectedSeatList').innerHTML =
            count
                ? selectedSeats.map(function (number) {
                    return '<span class="selected-badge">Seat ' +
                        number + '</span>';
                }).join('')
                : '<span class="text-secondary">No seats selected</span>';

        submitButton.disabled = count === 0 || requestInProgress;
    }

    function renderSeats(seats) {
        if (!Array.isArray(seats) || seats.length === 0) {
            seatMap.innerHTML =
                '<div class="text-danger" style="grid-column:1/-1;text-align:center">' +
                'No seats found for this bus.</div>';
            return;
        }

        seatMap.innerHTML = '';

        seats.forEach(function (seat) {
            const number = Number.parseInt(seat.seat_number, 10);
            const status = String(seat.status || '').toLowerCase();

            if (!Number.isInteger(number) || number < 1) {
                return;
            }

            if ((number - 1) % 4 === 2) {
                const aisle = document.createElement('div');
                aisle.className = 'seat-aisle';
                seatMap.appendChild(aisle);
            }

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = number;
            button.className = 'seat-btn';

            if (status === 'booked') {
                button.classList.add('booked');
                button.disabled = true;
                button.title = 'Seat ' + number + ' is booked';
            } else {
                button.title = 'Select seat ' + number;
                button.addEventListener('click', function () {
                    const index = selectedSeats.indexOf(number);

                    if (index === -1) {
                        selectedSeats.push(number);
                        button.classList.add('selected');
                    } else {
                        selectedSeats.splice(index, 1);
                        button.classList.remove('selected');
                    }

                    selectedSeats.sort(function (a, b) {
                        return a - b;
                    });

                    updateSeatSummary();
                });
            }

            seatMap.appendChild(button);
        });

        updateSeatSummary();
    }

    async function loadSeats(scheduleId) {
        seatMap.innerHTML =
            '<div style="grid-column:1/-1;text-align:center;color:#667085">' +
            '<span class="spinner-border spinner-border-sm me-2"></span>' +
            'Loading seats...</div>';

        try {
            const url = 'get_seats.php?schedule_id=' +
                encodeURIComponent(scheduleId);

            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json'
                },
                cache: 'no-store'
            });

            const data = await response.json();

            if (!response.ok || !data || data.status !== 'success') {
                throw new Error(
                    data && data.message
                        ? data.message
                        : 'Unable to load seats.'
                );
            }

            renderSeats(data.seats || []);
        } catch (error) {
            seatMap.innerHTML =
                '<div class="text-danger" style="grid-column:1/-1;text-align:center">' +
                escapeHtml(error.message || 'Unable to load seats. Please try again.') +
                '</div>';
        }
    }

    document.querySelectorAll('.reserve-btn').forEach(function (button) {
        button.addEventListener('click', function () {
            currentScheduleId = this.dataset.scheduleId || '';
            currentPrice = Number.parseFloat(this.dataset.price) || 0;

            if (!currentScheduleId || currentPrice <= 0) {
                alert('Invalid schedule selected.');
                return;
            }

            selectedSeats = [];
            clearError();

            bookingForm.reset();
            bookingForm.classList.remove('d-none');
            bookingSuccess.classList.add('d-none');

            document.getElementById('schedule_id').value = currentScheduleId;
            document.getElementById('ticketPriceHidden').value = currentPrice;

            document.getElementById('displayBusName').textContent =
                this.dataset.busName || '-';

            document.getElementById('displayRoute').textContent =
                this.dataset.route || '-';

            document.getElementById('displayPrice').textContent =
                '₹' + currentPrice.toFixed(2);

            document.getElementById('displaySeats').textContent =
                this.dataset.seats || '0';

            document.getElementById('customer_name').value = customer.name;
            document.getElementById('customer_phone').value = customer.mobile;
            document.getElementById('customer_email').value = customer.email;

            updateSeatSummary();
            bookingModal.show();
            loadSeats(currentScheduleId);
        });
    });

    document.getElementById('customer_phone').addEventListener('input', function () {
        this.value = this.value.replace(/\D/g, '').slice(0, 10);
    });

    bookingForm.addEventListener('submit', async function (event) {
        event.preventDefault();
        clearError();

        if (requestInProgress) {
            return;
        }

        if (selectedSeats.length === 0) {
            showError('Please select at least one seat.');
            return;
        }

        const phone = document.getElementById('customer_phone').value.trim();

        if (!/^[0-9]{10}$/.test(phone)) {
            showError('Please enter a valid 10-digit mobile number.');
            return;
        }

        requestInProgress = true;
        submitButton.disabled = true;
        submitButton.innerHTML =
            '<span class="spinner-border spinner-border-sm me-2"></span>Processing...';

        try {
            const formData = new FormData(bookingForm);

            const response = await fetch('reserve_action.php', {
                method: 'POST',
                body: new URLSearchParams(formData),
                headers: {
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (!response.ok || !data ||
                !(data.success === true || data.status === 'success')) {
                throw new Error(
                    data && data.message
                        ? data.message
                        : 'Booking failed. Please try again.'
                );
            }

            bookingForm.classList.add('d-none');
            bookingSuccess.classList.remove('d-none');

            document.getElementById('referenceNumber').textContent =
                data.reference_no || '-';

            document.getElementById('successMessage').textContent =
                data.message || 'Your booking was successful.';

            const bookedSeats = Array.isArray(data.seats)
                ? data.seats
                : selectedSeats;

            document.getElementById('successSeats').textContent =
                'Seats: ' + bookedSeats.join(', ');

            document.getElementById('successTotal').textContent =
                'Total: ₹' + Number(data.total_amount || 0).toFixed(2);

            const ticketLink = document.getElementById('ticketLink');

            if (data.ticket_url) {
                ticketLink.href = data.ticket_url;
            } else {
                ticketLink.href =
                    'ticket.php?reference=' +
                    encodeURIComponent(data.reference_no || '');
            }

        } catch (error) {
            showError(error.message || 'Something went wrong. Please try again.');
            await loadSeats(currentScheduleId);
        } finally {
            requestInProgress = false;
            submitButton.disabled = selectedSeats.length === 0;
            submitButton.innerHTML =
                '<i class="bi bi-lock-fill me-1"></i> Confirm Reservation';
        }
    });

    document.getElementById('doneBtn').addEventListener('click', function () {
        bookingModal.hide();
        window.location.href = 'user_dashboard.php';
    });

    modalElement.addEventListener('hidden.bs.modal', function () {
        if (bookingSuccess.classList.contains('d-none') === false) {
            window.location.href = 'user_dashboard.php';
        }
    });
});
</script>

</body>
</html>
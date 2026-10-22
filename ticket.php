<?php

session_start();

require_once 'config/db_connect.php';

/*
|--------------------------------------------------------------------------
| LOGIN CHECK
|--------------------------------------------------------------------------
*/

if (
    empty($_SESSION['user_logged_in']) ||
    empty($_SESSION['user_id'])
) {
    header('Location: user_login.php');
    exit;
}

/*
|--------------------------------------------------------------------------
| GET BOOKING REFERENCE
|--------------------------------------------------------------------------
*/

$reference = trim(
    $_GET['reference'] ?? ''
);

if ($reference === '') {
    exit('Invalid booking reference.');
}

if (strlen($reference) > 100) {
    exit('Invalid booking reference.');
}

/*
|--------------------------------------------------------------------------
| FETCH BOOKING
|--------------------------------------------------------------------------
*/

$sql = "
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
        p.payment_status AS payment_record_status,
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

    WHERE b.reference_no = ?

    LIMIT 1
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    exit('Unable to load ticket.');
}

$stmt->bind_param(
    "s",
    $reference
);

if (!$stmt->execute()) {
    $stmt->close();
    exit('Unable to load ticket.');
}

$result = $stmt->get_result();

$ticket = $result->fetch_assoc();

$stmt->close();

/*
|--------------------------------------------------------------------------
| CHECK TICKET
|--------------------------------------------------------------------------
*/

if (!$ticket) {
    exit('Ticket not found. Please check your booking reference.');
}

/*
|--------------------------------------------------------------------------
| SECURITY
|--------------------------------------------------------------------------
*/

function e($value): string
{
    return htmlspecialchars(
        (string)($value ?? ''),
        ENT_QUOTES,
        'UTF-8'
    );
}

function money($value): string
{
    return '₹' . number_format(
        (float)$value,
        2
    );
}

function dateTimeFormat($value): string
{
    if (
        empty($value) ||
        strtotime($value) === false
    ) {
        return 'Not available';
    }

    return date(
        'd M Y, h:i A',
        strtotime($value)
    );
}

/*
|--------------------------------------------------------------------------
| MASK PHONE
|--------------------------------------------------------------------------
*/

$phone = (string)($ticket['customer_phone'] ?? '');

if (strlen($phone) >= 10) {

    $displayPhone =
        substr($phone, 0, 2) .
        '******' .
        substr($phone, -2);

} else {

    $displayPhone = $phone;
}

/*
|--------------------------------------------------------------------------
| SEAT DISPLAY
|--------------------------------------------------------------------------
*/

$seatText = $ticket['seats'] . ' Seat';

if ((int)$ticket['seats'] > 1) {
    $seatText .= 's';
}

/*
|--------------------------------------------------------------------------
| PAYMENT STATUS
|--------------------------------------------------------------------------
*/

$paymentStatus =
    $ticket['payment_status']
        ?: 'Pending';

$paymentClass =
    strtolower($paymentStatus) === 'paid'
        ? 'paid'
        : 'pending';

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
        content="noindex,nofollow,noarchive"
    >

    <title>
        Ticket <?= e($ticket['reference_no']) ?> | OBRS
    </title>

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
            --bg: #070b17;
            --panel: #11192c;
            --panel2: #17223a;
            --text: #f5f7ff;
            --muted: #9da9c0;
            --accent: #745cff;
            --cyan: #35d8ff;
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
                    rgba(116,92,255,.20),
                    transparent 35%
                ),
                radial-gradient(
                    circle at 90% 10%,
                    rgba(53,216,255,.10),
                    transparent 30%
                ),
                var(--bg);

            font-family:
                Inter,
                "Segoe UI",
                Arial,
                sans-serif;
        }

        .topbar {
            border-bottom: 1px solid var(--border);

            background:
                rgba(7,11,23,.88);

            backdrop-filter: blur(15px);
        }

        .nav-inner {
            width: min(1100px, 100%);
            margin: auto;

            padding:
                15px 20px;

            display: flex;
            align-items: center;
            justify-content: space-between;

            gap: 15px;
        }

        .brand {
            color: white;
            text-decoration: none;

            font-weight: 850;
            font-size: 21px;
        }

        .brand i {
            color: var(--cyan);
            margin-right: 7px;
        }

        .nav-link-custom {
            color: #cbd4e7;
            text-decoration: none;

            padding:
                9px 13px;

            border-radius: 10px;

            font-size: 13px;
        }

        .nav-link-custom:hover {
            color: white;
            background: rgba(116,92,255,.18);
        }

        .page {
            width: min(900px, 100%);

            margin: auto;

            padding:
                35px 18px 60px;
        }

        .ticket-header {
            text-align: center;
            margin-bottom: 25px;
        }

        .ticket-header .icon {
            width: 65px;
            height: 65px;

            margin: auto;

            display: grid;
            place-items: center;

            border-radius: 20px;

            background:
                rgba(116,92,255,.17);

            color: #a89aff;

            font-size: 30px;
        }

        .ticket-header h1 {
            margin:
                16px 0 7px;

            font-weight: 850;

            letter-spacing: -.7px;
        }

        .ticket-header p {
            color: var(--muted);
            margin: 0;
        }

        .ticket {
            overflow: hidden;

            border:
                1px solid var(--border);

            border-radius: 25px;

            background:
                linear-gradient(
                    145deg,
                    rgba(23,34,58,.98),
                    rgba(12,19,34,.98)
                );

            box-shadow:
                0 25px 70px
                rgba(0,0,0,.28);
        }

        .ticket-top {
            padding: 24px;

            background:
                linear-gradient(
                    110deg,
                    #5d46d7,
                    #327de9
                );
        }

        .ticket-top-row {
            display: flex;

            align-items: center;
            justify-content: space-between;

            gap: 15px;
        }

        .ticket-label {
            font-size: 11px;

            opacity: .75;

            letter-spacing: 1.5px;

            text-transform: uppercase;
        }

        .reference {
            font-size: 23px;

            font-weight: 850;

            letter-spacing: 1px;

            overflow-wrap: anywhere;
        }

        .ticket-icon {
            font-size: 42px;
        }

        .route-section {
            padding: 28px 25px;

            border-bottom:
                1px dashed
                rgba(255,255,255,.15);
        }

        .route {
            display: grid;

            grid-template-columns:
                1fr
                auto
                1fr;

            align-items: center;

            gap: 20px;
        }

        .route-side:last-child {
            text-align: right;
        }

        .route-label {
            color: var(--muted);

            font-size: 11px;

            letter-spacing: 1px;

            margin-bottom: 6px;
        }

        .route-city {
            font-size: 21px;

            font-weight: 850;
        }

        .route-arrow {
            width: 48px;
            height: 48px;

            display: grid;
            place-items: center;

            border-radius: 50%;

            color: var(--cyan);

            background:
                rgba(53,216,255,.10);

            font-size: 20px;
        }

        .info-section {
            padding: 25px;
        }

        .info-grid {
            display: grid;

            grid-template-columns:
                repeat(2, 1fr);

            gap: 13px;
        }

        .info-card {
            padding: 15px;

            border:
                1px solid
                rgba(255,255,255,.07);

            border-radius: 14px;

            background:
                rgba(255,255,255,.035);
        }

        .info-label {
            color: var(--muted);

            font-size: 11px;

            margin-bottom: 7px;

            text-transform: uppercase;

            letter-spacing: .6px;
        }

        .info-value {
            font-size: 14px;

            font-weight: 750;

            overflow-wrap: anywhere;
        }

        .payment-row {
            display: flex;

            justify-content: space-between;

            align-items: center;

            gap: 15px;

            margin-top: 20px;

            padding: 17px;

            border-radius: 15px;

            background:
                rgba(255,255,255,.045);
        }

        .payment-badge {
            padding:
                7px 13px;

            border-radius: 30px;

            font-size: 12px;

            font-weight: 800;
        }

        .payment-badge.paid {
            color: #71edb9;

            background:
                rgba(44,210,135,.12);
        }

        .payment-badge.pending {
            color: #ffd37a;

            background:
                rgba(255,184,77,.12);
        }

        .total-section {
            padding: 22px 25px;

            border-top:
                1px dashed
                rgba(255,255,255,.15);

            background:
                rgba(116,92,255,.08);

            display: flex;

            align-items: center;

            justify-content: space-between;

            gap: 15px;
        }

        .total-label {
            color: var(--muted);

            font-size: 12px;
        }

        .total-value {
            font-size: 27px;

            font-weight: 900;
        }

        .actions {
            display: flex;

            justify-content: center;

            flex-wrap: wrap;

            gap: 10px;

            margin-top: 22px;
        }

        .action-btn {
            border-radius: 12px;

            padding:
                12px 17px;

            font-size: 13px;

            font-weight: 800;
        }

        .ticket-note {
            text-align: center;

            color: var(--muted);

            font-size: 12px;

            line-height: 1.6;

            margin-top: 20px;
        }

        @media print {

            body {
                background: white !important;
                color: black !important;
            }

            .topbar,
            .actions,
            .ticket-header p,
            .ticket-note {
                display: none !important;
            }

            .page {
                width: 100%;
                padding: 0;
            }

            .ticket {
                color: black !important;
                border: 1px solid #ccc;
                box-shadow: none;
                background: white !important;
            }

            .route-section,
            .info-section,
            .total-section {
                background: white !important;
            }

            .info-card {
                color: black !important;
                border-color: #ddd;
                background: white !important;
            }

            .info-label,
            .route-label,
            .total-label {
                color: #666 !important;
            }

            .ticket-top {
                background: #eeeeee !important;
                color: black !important;
            }

            .route-arrow {
                color: black !important;
                background: #eee !important;
            }
        }

        @media (max-width: 600px) {

            .nav-inner {
                flex-direction: column;
                align-items: flex-start;
            }

            .route {
                grid-template-columns:
                    1fr;

                text-align: center;
            }

            .route-side:last-child {
                text-align: center;
            }

            .route-arrow {
                margin: auto;
            }

            .info-grid {
                grid-template-columns: 1fr;
            }

            .ticket-top-row,
            .total-section,
            .payment-row {
                align-items: flex-start;
                flex-direction: column;
            }

        }

    </style>

</head>

<body>

<nav class="topbar">

    <div class="nav-inner">

        <a
            href="user_dashboard.php"
            class="brand"
        >
            <i class="bi bi-bus-front-fill"></i>
            OBRS
        </a>

        <div>

            <a
                href="user_dashboard.php"
                class="nav-link-custom"
            >
                <i class="bi bi-grid me-1"></i>
                Dashboard
            </a>

            <a
                href="user_bookings.php"
                class="nav-link-custom"
            >
                <i class="bi bi-ticket-perforated me-1"></i>
                My Bookings
            </a>

        </div>

    </div>

</nav>


<main class="page">

    <div class="ticket-header">

        <div class="icon">

            <i class="bi bi-ticket-detailed-fill"></i>

        </div>

        <h1>Your Bus Ticket</h1>

        <p>
            Booking confirmation for your journey
        </p>

    </div>


    <div class="ticket">

        <!-- TICKET HEADER -->

        <div class="ticket-top">

            <div class="ticket-top-row">

                <div>

                    <div class="ticket-label">
                        Booking Reference
                    </div>

                    <div class="reference">
                        <?= e($ticket['reference_no']) ?>
                    </div>

                </div>

                <div class="ticket-icon">
                    <i class="bi bi-bus-front-fill"></i>
                </div>

            </div>

        </div>


        <!-- ROUTE -->

        <div class="route-section">

            <div class="route">

                <div class="route-side">

                    <div class="route-label">
                        FROM
                    </div>

                    <div class="route-city">
                        <?= e($ticket['origin']) ?>
                    </div>

                </div>


                <div class="route-arrow">

                    <i class="bi bi-arrow-right"></i>

                </div>


                <div class="route-side">

                    <div class="route-label">
                        TO
                    </div>

                    <div class="route-city">
                        <?= e($ticket['destination']) ?>
                    </div>

                </div>

            </div>

        </div>


        <!-- INFORMATION -->

        <div class="info-section">

            <div class="info-grid">

                <div class="info-card">

                    <div class="info-label">
                        Passenger
                    </div>

                    <div class="info-value">
                        <?= e($ticket['customer_name']) ?>
                    </div>

                </div>


                <div class="info-card">

                    <div class="info-label">
                        Mobile
                    </div>

                    <div class="info-value">
                        <?= e($displayPhone) ?>
                    </div>

                </div>


                <div class="info-card">

                    <div class="info-label">
                        Bus
                    </div>

                    <div class="info-value">
                        <?= e($ticket['bus_name']) ?>
                    </div>

                </div>


                <div class="info-card">

                    <div class="info-label">
                        Bus Number
                    </div>

                    <div class="info-value">
                        <?= e($ticket['bus_number']) ?>
                    </div>

                </div>


                <div class="info-card">

                    <div class="info-label">
                        Departure
                    </div>

                    <div class="info-value">
                        <?= e(
                            dateTimeFormat(
                                $ticket['departure_time']
                            )
                        ) ?>
                    </div>

                </div>


                <div class="info-card">

                    <div class="info-label">
                        Arrival / ETA
                    </div>

                    <div class="info-value">
                        <?= e(
                            dateTimeFormat(
                                $ticket['eta_time']
                            )
                        ) ?>
                    </div>

                </div>


                <div class="info-card">

                    <div class="info-label">
                        Seats
                    </div>

                    <div class="info-value">
                        <?= e($seatText) ?>
                    </div>

                </div>


                <div class="info-card">

                    <div class="info-label">
                        Ticket Price
                    </div>

                    <div class="info-value">
                        <?= money(
                            $ticket['ticket_price']
                        ) ?>
                    </div>

                </div>

            </div>


            <!-- PAYMENT -->

            <div class="payment-row">

                <div>

                    <div class="info-label">
                        Payment Status
                    </div>

                    <div class="info-value">

                        <?= e($paymentStatus) ?>

                    </div>

                </div>


                <span
                    class="payment-badge <?= e($paymentClass) ?>"
                >
                    <?= e($paymentStatus) ?>
                </span>

            </div>

            <?php if (!empty($ticket['transaction_id'])): ?>

                <div class="payment-row">

                    <div>

                        <div class="info-label">
                            Transaction ID
                        </div>

                        <div class="info-value">
                            <?= e(
                                $ticket['transaction_id']
                            ) ?>
                        </div>

                    </div>

                </div>

            <?php endif; ?>

        </div>


        <!-- TOTAL -->

        <div class="total-section">

            <div>

                <div class="total-label">
                    TOTAL AMOUNT
                </div>

                <div class="total-value">
                    <?= money(
                        $ticket['total_amount']
                    ) ?>
                </div>

            </div>

            <div class="text-end">

                <div class="total-label">
                    BOOKING DATE
                </div>

                <strong>
                    <?= e(
                        dateTimeFormat(
                            $ticket['booking_date']
                        )
                    ) ?>
                </strong>

            </div>

        </div>

    </div>


    <!-- ACTIONS -->

    <div class="actions">

        <button
            type="button"
            class="btn btn-primary action-btn"
            onclick="window.print()"
        >
            <i class="bi bi-printer me-1"></i>
            Print / Save PDF
        </button>


        <a
            href="user_bookings.php"
            class="btn btn-outline-light action-btn"
        >
            <i class="bi bi-ticket-perforated me-1"></i>
            My Bookings
        </a>


        <a
            href="user_dashboard.php"
            class="btn btn-outline-light action-btn"
        >
            <i class="bi bi-arrow-left me-1"></i>
            Dashboard
        </a>

    </div>


    <div class="ticket-note">

        <i class="bi bi-shield-check me-1"></i>

        Keep your booking reference private.
        You may use this ticket for your journey.

    </div>

</main>

</body>

</html>
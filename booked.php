
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

$admin_id =
    (int) ($_SESSION['admin_id'] ?? 0);

if ($admin_id <= 0) {

    $_SESSION = [];

    session_destroy();

    header("Location: login.php");

    exit;
}


// ============================================================
// CSRF PROTECTION
// ============================================================

require_once 'config/csrf.php';


// ============================================================
// HANDLE PAYMENT UPDATE REQUEST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_valid_csrf();

    $booking_id =
        (int) ($_POST['booking_id'] ?? 0);

    $payment_status =
        $_POST['payment_status'] ?? '';

    $transaction_id =
        trim($_POST['transaction_id'] ?? '');


    // ========================================================
    // VALIDATE BOOKING ID
    // ========================================================

    if ($booking_id <= 0) {

        http_response_code(400);

        exit(
            'Invalid booking ID.'
        );
    }


    // ========================================================
    // VALIDATE PAYMENT STATUS
    // ========================================================

    if (
        !in_array(
            $payment_status,
            ['Paid', 'Unpaid'],
            true
        )
    ) {

        http_response_code(400);

        exit(
            'Invalid payment status.'
        );
    }


    // ========================================================
    // VALIDATE TRANSACTION ID
    // ========================================================

    if (strlen($transaction_id) > 150) {

        http_response_code(400);

        exit(
            'Invalid transaction ID.'
        );
    }


    // ========================================================
    // START TRANSACTION
    // ========================================================

    $conn->begin_transaction();


    try {

        // ====================================================
        // UPDATE BOOKED TABLE
        // ====================================================

        $stmt = $conn->prepare("
            UPDATE booked
            SET payment_status = ?
            WHERE id = ?
        ");

        if (!$stmt) {

            throw new Exception(
                'Unable to prepare booking update.'
            );
        }


        $stmt->bind_param(
            "si",
            $payment_status,
            $booking_id
        );


        if (!$stmt->execute()) {

            $stmt->close();

            throw new Exception(
                'Unable to update booking payment status.'
            );
        }


        $stmt->close();


        // ====================================================
        // PAID PAYMENT
        // ====================================================

        if ($payment_status === 'Paid') {

            // Transaction ID required

            if ($transaction_id === '') {

                throw new Exception(
                    'Transaction ID is required for Paid status.'
                );
            }


            // =================================================
            // VERIFY PAYMENT RECORD
            // =================================================

            $amountStmt = $conn->prepare("
                SELECT
                    booked.total_amount,
                    payments.amount
                FROM booked
                INNER JOIN payments
                    ON booked.id = payments.booking_id
                WHERE booked.id = ?
                LIMIT 1
            ");

            if (!$amountStmt) {

                throw new Exception(
                    'Unable to prepare payment verification.'
                );
            }


            $amountStmt->bind_param(
                "i",
                $booking_id
            );


            if (!$amountStmt->execute()) {

                $amountStmt->close();

                throw new Exception(
                    'Unable to verify payment amount.'
                );
            }


            $amountResult =
                $amountStmt->get_result();


            $amountRow =
                $amountResult->fetch_assoc();


            $amountStmt->close();


            // =================================================
            // PAYMENT RECORD NOT FOUND
            // =================================================

            if (!$amountRow) {

                throw new Exception(
                    'Payment record not found.'
                );
            }


            // =================================================
            // COMPARE AMOUNTS
            // =================================================

            $bookingAmount =
                (float) $amountRow['total_amount'];

            $paymentAmount =
                (float) $amountRow['amount'];


            if (
                abs(
                    $bookingAmount -
                    $paymentAmount
                ) > 0.01
            ) {

                throw new Exception(
                    'Payment amount does not match booking amount.'
                );
            }


            // =================================================
            // UPDATE PAYMENT AS PAID
            // =================================================

            $payment_db_status = 'Paid';


            $paymentStmt = $conn->prepare("
                UPDATE payments
                SET
                    payment_status = ?,
                    transaction_id = ?,
                    paid_at = NOW()
                WHERE booking_id = ?
            ");


            if (!$paymentStmt) {

                throw new Exception(
                    'Unable to prepare payment update.'
                );
            }


            $paymentStmt->bind_param(
                "ssi",
                $payment_db_status,
                $transaction_id,
                $booking_id
            );


        } else {

            // =================================================
            // UPDATE PAYMENT AS PENDING
            // =================================================

            $payment_db_status = 'Pending';


            $paymentStmt = $conn->prepare("
                UPDATE payments
                SET
                    payment_status = ?,
                    transaction_id = NULL,
                    paid_at = NULL
                WHERE booking_id = ?
            ");


            if (!$paymentStmt) {

                throw new Exception(
                    'Unable to prepare payment update.'
                );
            }


            $paymentStmt->bind_param(
                "si",
                $payment_db_status,
                $booking_id
            );
        }


        // ====================================================
        // EXECUTE PAYMENT UPDATE
        // ====================================================

        if (!$paymentStmt->execute()) {

            $paymentStmt->close();

            throw new Exception(
                'Unable to update payment record.'
            );
        }


        $paymentStmt->close();


        // ====================================================
        // COMMIT
        // ====================================================

        $conn->commit();


        // ====================================================
        // REDIRECT
        // ====================================================

        header(
            "Location: booked.php?updated=1"
        );

        exit;


    } catch (Throwable $e) {

        // ====================================================
        // ROLLBACK
        // ====================================================

        $conn->rollback();


        // Temporary debugging
        error_log(
            'OBRS Payment Update Error: ' .
            $e->getMessage()
        );


        http_response_code(500);


        exit(
            'PAYMENT ERROR: ' .
            $e->getMessage()
        );
    }
}


// ============================================================
// FETCH ALL BOOKINGS
// ============================================================


// ============================================================
// FETCH ALL BOOKINGS
// ============================================================

$sql = "

    SELECT

        booked.id,

        booked.reference_no,

        booked.customer_name,

        booked.customer_phone,

        booked.seats,

        booked.ticket_price,

        booked.total_amount,

        booked.payment_status,

        payments.payment_method,

        payments.transaction_id,

        payments.paid_at,

        booked.booking_date,

        bus.bus_name,

        bus.bus_number,

        origin.location_name AS origin,

        destination.location_name AS destination,

        schedule.departure_time,

        schedule.eta_time

    FROM booked

    LEFT JOIN payments
        ON booked.id = payments.booking_id

    INNER JOIN schedule
        ON booked.schedule_id = schedule.id

    INNER JOIN bus
        ON schedule.bus_id = bus.id

    INNER JOIN location AS origin
        ON schedule.origin_id = origin.id

    INNER JOIN location AS destination
        ON schedule.destination_id = destination.id

    ORDER BY booked.booking_date DESC

";


$result =
    $conn->query($sql);


// ============================================================
// FILTER DATA
// ============================================================

$busOptions = [];

$routeOptions = [];


if ($result) {

    while ($filterRow = $result->fetch_assoc()) {

        $busKey =
            $filterRow['bus_name']
            . ' | '
            . $filterRow['bus_number'];

        $routeKey =
            $filterRow['origin']
            . ' → '
            . $filterRow['destination'];


        $busOptions[$busKey] =
            $busKey;


        $routeOptions[$routeKey] =
            $routeKey;
    }

    // Reset result pointer
    $result->data_seek(0);
}


ksort($busOptions);

ksort($routeOptions);

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
        Reservations - OBRS Admin
    </title>


    <!-- =====================================================
         BOOTSTRAP
    ====================================================== -->

    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <!-- =====================================================
         DATATABLES
    ====================================================== -->

    <link
        rel="stylesheet"
        href="https://cdn.datatables.net/2.1.8/css/dataTables.bootstrap5.css"
    >


    <!-- =====================================================
         CUSTOM CSS
    ====================================================== -->

    <style>

        body {

            background:
                #f5f7fb;

        }


        .navbar-brand {

            letter-spacing:
                0.3px;

        }


        .card {

            border-radius:
                14px;

        }


        .table th {

            white-space:
                nowrap;

        }


        .table td {

            vertical-align:
                middle;

        }


        .filter-card {

            border:
                none;

            border-radius:
                16px;

            background:
                #ffffff;

        }


        .filter-label {

            font-size:
                13px;

            font-weight:
                600;

            margin-bottom:
                6px;

        }


        .filter-control {

            min-height:
                42px;

        }


        .filter-title {

            font-weight:
                700;

        }


        .result-count {

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


        .table-wrapper {

            overflow-x:
                auto;

        }


        @media (max-width: 767px) {

            .page-header {

                align-items:
                    flex-start !important;

                gap:
                    15px;

                flex-direction:
                    column;

            }

        }

    </style>

</head>


<body>


<!-- =========================================================
     NAVBAR
========================================================== -->

<nav class="navbar navbar-dark bg-dark">

    <div class="container-fluid">


        <a
            href="admin_home.php"
            class="navbar-brand fw-bold"
        >

            🚍 OBRS Admin

        </a>


        <div class="text-white">

            <?php

            echo htmlspecialchars(
                $_SESSION['admin_name'] ?? 'Admin',
                ENT_QUOTES,
                'UTF-8'
            );

            ?>


            <a
                href="logout.php"
                class="btn btn-danger btn-sm ms-3"
            >

                Logout

            </a>

        </div>

    </div>

</nav>


<!-- =========================================================
     MAIN CONTENT
========================================================== -->

<div class="container-fluid py-4">


    <!-- =====================================================
         PAGE HEADER
    ====================================================== -->

    <div
        class="d-flex justify-content-between page-header mb-4"
    >

        <div>

            <h2 class="fw-bold mb-1">

                🎫 Reservations

            </h2>


            <p class="text-muted mb-0">

                Search, filter and manage all customer
                bus reservations.

            </p>

        </div>


        <a
            href="admin_home.php"
            class="btn btn-secondary"
        >

            ← Dashboard

        </a>

    </div>


    <!-- =====================================================
         SUCCESS MESSAGE
    ====================================================== -->

    <?php if (isset($_GET['updated'])): ?>

        <div class="alert alert-success">

            ✅ Payment status updated successfully.

        </div>

    <?php endif; ?>


    <!-- =====================================================
         ADVANCED FILTER PANEL
    ====================================================== -->

    <div
        class="card filter-card shadow-sm mb-4"
    >

        <div class="card-body p-4">


            <div
                class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3"
            >

                <h5 class="filter-title mb-0">

                    🔎 Advanced Filters

                </h5>


                <span
                    class="text-muted result-count"
                    id="filterResultCount"
                >

                    Showing all reservations

                </span>

            </div>


            <div class="row g-3">


                <!-- SEARCH -->

                <div class="col-lg-4 col-md-6">

                    <label
                        for="globalSearch"
                        class="filter-label"
                    >

                        Search

                    </label>


                    <input
                        type="text"
                        id="globalSearch"
                        class="form-control filter-control"
                        placeholder="Reference, customer, phone..."
                        autocomplete="off"
                    >

                </div>


                <!-- BUS -->

                <div class="col-lg-2 col-md-6">

                    <label
                        for="busFilter"
                        class="filter-label"
                    >

                        Bus

                    </label>


                    <select
                        id="busFilter"
                        class="form-select filter-control"
                    >

                        <option value="">
                            All Buses
                        </option>


                        <?php foreach (
                            $busOptions
                            as $bus
                        ): ?>

                            <option
                                value="<?php
                                    echo htmlspecialchars(
                                        $bus,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $bus,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- ROUTE -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="routeFilter"
                        class="filter-label"
                    >

                        Route

                    </label>


                    <select
                        id="routeFilter"
                        class="form-select filter-control"
                    >

                        <option value="">
                            All Routes
                        </option>


                        <?php foreach (
                            $routeOptions
                            as $route
                        ): ?>

                            <option
                                value="<?php
                                    echo htmlspecialchars(
                                        $route,
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $route,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </option>

                        <?php endforeach; ?>

                    </select>

                </div>


                <!-- PAYMENT -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="paymentFilter"
                        class="filter-label"
                    >

                        Payment Status

                    </label>


                    <select
                        id="paymentFilter"
                        class="form-select filter-control"
                    >

                        <option value="">
                            All Payments
                        </option>


                        <option value="Paid">
                            Paid
                        </option>


                        <option value="Unpaid">
                            Unpaid
                        </option>

                    </select>

                </div>


                <!-- DATE -->

                <div class="col-lg-3 col-md-6">

                    <label
                        for="dateFilter"
                        class="filter-label"
                    >

                        Booking Date

                    </label>


                    <input
                        type="date"
                        id="dateFilter"
                        class="form-control filter-control"
                    >

                </div>


                <!-- RESET -->

                <div class="col-lg-3 col-md-6 d-flex align-items-end">

                    <button
                        type="button"
                        id="resetFilters"
                        class="btn btn-outline-secondary w-100 filter-control"
                    >

                        🔄 Reset Filters

                    </button>

                </div>

            </div>

        </div>

    </div>


    <!-- =====================================================
         BOOKING TABLE
    ====================================================== -->

    <div class="card shadow-sm border-0">

        <div class="card-body">

            <div class="table-wrapper">

                <table
                    id="bookingTable"
                    class="table table-striped table-hover align-middle"
                    style="width:100%"
                >


                    <thead class="table-dark">

                        <tr>

                            <th>
                                Reference
                            </th>


                            <th>
                                Customer
                            </th>


                            <th>
                                Bus
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
                                Booking Date
                            </th>


                            <th>
                                Payment
                            </th>


                            <th>
                                Method
                            </th>


                            <th>
                                Transaction ID
                            </th>


                            <th>
                                Paid At
                            </th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        $result &&
                        $result->num_rows > 0
                    ):

                        while (
                            $row =
                            $result->fetch_assoc()
                        ):

                    ?>

                        <tr
                            data-booking-date="<?php

                                echo htmlspecialchars(
                                    date(
                                        'Y-m-d',
                                        strtotime(
                                            $row['booking_date']
                                        )
                                    ),
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                            ?>"
                        >


                            <!-- =================================================
                                 REFERENCE
                            ================================================== -->

                            <td>

                                <strong class="text-primary">

                                    <?php

                                    echo htmlspecialchars(
                                        $row['reference_no'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </strong>

                            </td>


                            <!-- =================================================
                                 CUSTOMER
                            ================================================== -->

                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['customer_name'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </strong>


                                <br>


                                <small class="text-muted">

                                    <?php

                                    echo htmlspecialchars(
                                        $row['customer_phone'],
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </small>

                            </td>


                            <!-- =================================================
                                 BUS
                            ================================================== -->

                            <td>

                                <?php

                                $busDisplay =
                                    $row['bus_name']
                                    . ' | '
                                    . $row['bus_number'];


                                echo htmlspecialchars(
                                    $busDisplay,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </td>


                            <!-- =================================================
                                 ROUTE
                            ================================================== -->

                            <td>

                                <?php

                                $routeDisplay =
                                    $row['origin']
                                    . ' → '
                                    . $row['destination'];


                                echo htmlspecialchars(
                                    $routeDisplay,
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </td>


                            <!-- =================================================
                                 SEATS
                            ================================================== -->

                            <td>

                                <span class="badge bg-primary">

                                    <?php

                                    echo (int) $row['seats'];

                                    ?>

                                </span>

                            </td>


                            <!-- =================================================
                                 AMOUNT
                            ================================================== -->

                            <td>

                                <strong>

                                    ₹<?php

                                    echo number_format(
                                        (float) $row['total_amount'],
                                        2
                                    );

                                    ?>

                                </strong>

                            </td>


                            <!-- =================================================
                                 BOOKING DATE
                            ================================================== -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $row['booking_date']
                                    )
                                ) {

                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $row['booking_date']
                                        )
                                    );

                                } else {

                                    echo '-';

                                }

                                ?>

                            </td>


                            <!-- =================================================
                                 PAYMENT STATUS
                            ================================================== -->

                            <td>

                                <form
                                    method="POST"
                                    action=""
                                    class="payment-form"
                                >


                                    <!-- CSRF TOKEN -->

                                    <input
                                        type="hidden"
                                        name="csrf_token"
                                        value="<?php

                                        echo htmlspecialchars(
                                            csrf_token(),
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                        ?>"
                                    >


                                    <!-- BOOKING ID -->

                                    <input
                                        type="hidden"
                                        name="booking_id"
                                        value="<?php

                                        echo (int) $row['id'];

                                        ?>"
                                    >


                                    <!-- PAYMENT STATUS -->

                                    <select
                                        name="payment_status"
                                        class="form-select form-select-sm"
                                        onchange="handlePaymentStatus(this)"
                                    >

                                        <option
                                            value="Unpaid"
                                            <?php

                                            if (
                                                $row['payment_status']
                                                === 'Unpaid'
                                            ) {

                                                echo 'selected';

                                            }

                                            ?>
                                        >

                                            Unpaid

                                        </option>


                                        <option
                                            value="Paid"
                                            <?php

                                            if (
                                                $row['payment_status']
                                                === 'Paid'
                                            ) {

                                                echo 'selected';

                                            }

                                            ?>
                                        >

                                            Paid

                                        </option>

                                    </select>


                                    <!-- TRANSACTION ID -->

                                    <input
                                        type="text"
                                        name="transaction_id"
                                        class="form-control form-control-sm mt-2 transaction-input"
                                        placeholder="Transaction ID"
                                        maxlength="150"
                                        value="<?php

                                        echo htmlspecialchars(
                                            $row['transaction_id'] ?? '',
                                            ENT_QUOTES,
                                            'UTF-8'
                                        );

                                        ?>"
                                        style="<?php

                                        echo (
                                            $row['payment_status']
                                            === 'Paid'
                                            ? ''
                                            : 'display:none;'
                                        );

                                        ?>"
                                    >


                                    <!-- UPDATE BUTTON -->

                                    <button
                                        type="submit"
                                        class="btn btn-sm btn-primary mt-2 transaction-submit"
                                        style="<?php

                                        echo (
                                            $row['payment_status']
                                            === 'Paid'
                                            ? ''
                                            : 'display:none;'
                                        );

                                        ?>"
                                    >

                                        Update Payment

                                    </button>

                                </form>

                            </td>


                            <!-- =================================================
                                 PAYMENT METHOD
                            ================================================== -->

                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row['payment_method'] ?? '-',
                                    ENT_QUOTES,
                                    'UTF-8'
                                );

                                ?>

                            </td>


                            <!-- =================================================
                                 TRANSACTION ID
                            ================================================== -->

                            <td>

                                <small>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['transaction_id'] ?? '-',
                                        ENT_QUOTES,
                                        'UTF-8'
                                    );

                                    ?>

                                </small>

                            </td>


                            <!-- =================================================
                                 PAID AT
                            ================================================== -->

                            <td>

                                <?php

                                if (
                                    !empty(
                                        $row['paid_at']
                                    )
                                ) {

                                    echo date(
                                        'd M Y, h:i A',
                                        strtotime(
                                            $row['paid_at']
                                        )
                                    );

                                } else {

                                    echo '-';

                                }

                                ?>

                            </td>


                        </tr>


                    <?php

                        endwhile;

                    endif;

                    ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</div>


<!-- =========================================================
     JQUERY
========================================================== -->

<script
    src="https://code.jquery.com/jquery-3.7.1.min.js"
></script>


<!-- =========================================================
     DATATABLES
========================================================== -->

<script
    src="https://cdn.datatables.net/2.1.8/js/dataTables.js"
></script>


<script
    src="https://cdn.datatables.net/2.1.8/js/dataTables.bootstrap5.js"
></script>


<!-- =========================================================
     JAVASCRIPT
========================================================== -->

<script>

$(document).ready(function () {


    // ========================================================
    // DATATABLE INITIALIZATION
    // ========================================================

    const table =
        $('#bookingTable').DataTable({

            pageLength: 10,

            ordering: true,

            searching: true,

            paging: true,

            info: true,

            lengthMenu: [
                [10, 25, 50, 100],
                [10, 25, 50, 100]
            ],

            language: {

                search:
                    "Quick Search:",

                emptyTable:
                    "No reservations found.",

                zeroRecords:
                    "No matching reservations found."

            }

        });


    // ========================================================
    // CUSTOM GLOBAL SEARCH
    // ========================================================

    $('#globalSearch').on(
        'keyup',
        function () {

            table
                .search(this.value)
                .draw();

            updateFilterCount();

        }
    );


    // ========================================================
    // BUS FILTER
    // ========================================================

    $('#busFilter').on(
        'change',
        function () {

            const value =
                this.value;

            table
                .column(2)
                .search(
                    value
                        ? '^' +
                          escapeRegex(value) +
                          '$'
                        : '',
                    true,
                    false
                )
                .draw();

            updateFilterCount();

        }
    );


    // ========================================================
    // ROUTE FILTER
    // ========================================================

    $('#routeFilter').on(
        'change',
        function () {

            const value =
                this.value;

            table
                .column(3)
                .search(
                    value
                        ? '^' +
                          escapeRegex(value) +
                          '$'
                        : '',
                    true,
                    false
                )
                .draw();

            updateFilterCount();

        }
    );


    // ========================================================
    // PAYMENT FILTER
    // ========================================================

    $('#paymentFilter').on(
        'change',
        function () {

            const value =
                this.value;

            table
                .column(7)
                .search(
                    value
                        ? value
                        : ''
                )
                .draw();

            updateFilterCount();

        }
    );


    // ========================================================
    // DATE FILTER
    // ========================================================

    $.fn.dataTable.ext.search.push(
        function (
            settings,
            data,
            dataIndex
        ) {

            if (
                settings.nTable.id !==
                'bookingTable'
            ) {

                return true;

            }


            const selectedDate =
                $('#dateFilter').val();


            if (!selectedDate) {

                return true;

            }


            const row =
                table
                    .row(dataIndex)
                    .node();


            if (!row) {

                return false;

            }


            const bookingDate =
                row.getAttribute(
                    'data-booking-date'
                );


            return (
                bookingDate ===
                selectedDate
            );

        }
    );


    $('#dateFilter').on(
        'change',
        function () {

            table.draw();

            updateFilterCount();

        }
    );


    // ========================================================
    // RESET FILTERS
    // ========================================================

    $('#resetFilters').on(
        'click',
        function () {

            $('#globalSearch').val('');

            $('#busFilter').val('');

            $('#routeFilter').val('');

            $('#paymentFilter').val('');

            $('#dateFilter').val('');


            table
                .search('')
                .columns()
                .search('')
                .draw();


            updateFilterCount();

        }
    );


    // ========================================================
    // UPDATE FILTER RESULT COUNT
    // ========================================================

    function updateFilterCount() {

        const info =
            table.page.info();


        const total =
            info.recordsDisplay;


        const all =
            info.recordsTotal;


        if (total === all) {

            $('#filterResultCount').text(
                'Showing all ' +
                all +
                ' reservations'
            );

        } else {

            $('#filterResultCount').text(
                'Showing ' +
                total +
                ' of ' +
                all +
                ' reservations'
            );

        }

    }


    // ========================================================
    // REGEX ESCAPE
    // ========================================================

    function escapeRegex(value) {

        return value.replace(
            /[.*+?^${}()|[\]\\]/g,
            '\\$&'
        );

    }


    // ========================================================
    // INITIAL COUNT
    // ========================================================

    updateFilterCount();

});


// ============================================================
// PAYMENT STATUS HANDLER
// ============================================================

function handlePaymentStatus(select) {

    const form =
        select.closest(
            '.payment-form'
        );


    if (!form) {

        return;

    }


    const transactionInput =
        form.querySelector(
            '.transaction-input'
        );


    const submitButton =
        form.querySelector(
            '.transaction-submit'
        );


    if (
        !transactionInput ||
        !submitButton
    ) {

        return;

    }


    if (
        select.value ===
        'Paid'
    ) {

        transactionInput.style.display =
            'block';

        submitButton.style.display =
            'block';

        transactionInput.focus();

    } else {

        transactionInput.value =
            '';

        transactionInput.style.display =
            'none';

        submitButton.style.display =
            'none';


        form.submit();

    }

}

</script>


</body>

</html>

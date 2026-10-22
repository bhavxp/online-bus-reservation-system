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
    (int)$_SESSION['admin_id'];


$message = "";

$message_type = "success";


// ============================================================
// ADD SCHEDULE
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'add'
) {

    $bus_id =
        (int)($_POST['bus_id'] ?? 0);

    $origin_id =
        (int)($_POST['origin_id'] ?? 0);

    $destination_id =
        (int)($_POST['destination_id'] ?? 0);

    $departure_time =
        $_POST['departure_time'] ?? '';

    $eta_time =
        $_POST['eta_time'] ?? '';

    $available_seats =
        (int)($_POST['available_seats'] ?? 0);

    $ticket_price =
        (float)($_POST['ticket_price'] ?? 0);


  // --------------------------------------------------------
// VALIDATION
// --------------------------------------------------------

$departureDate = DateTime::createFromFormat(
    'Y-m-d\TH:i',
    $departure_time
);

$etaDate = DateTime::createFromFormat(
    'Y-m-d\TH:i',
    $eta_time
);

if (
    $bus_id <= 0
    ||
    $origin_id <= 0
    ||
    $destination_id <= 0
    ||
    $departure_time === ''
    ||
    $eta_time === ''
    ||
    $available_seats <= 0
    ||
    $available_seats > 200
    ||
    $ticket_price <= 0
    ||
    $ticket_price > 100000
) {

    $message =
        "Please enter all schedule details correctly.";

    $message_type = "danger";

}

elseif (
    !$departureDate
    ||
    !$etaDate
    ||
    $departureDate->format('Y-m-d\TH:i') !== $departure_time
    ||
    $etaDate->format('Y-m-d\TH:i') !== $eta_time
) {

    $message =
        "Please enter valid departure and ETA times.";

    $message_type = "danger";

}

elseif ($origin_id === $destination_id) {

    $message =
        "Origin and destination cannot be the same.";

    $message_type = "danger";

}

elseif ($etaDate <= $departureDate) {

    $message =
        "ETA must be later than departure time.";

    $message_type = "danger";

}

else {

    // Continue with existing bus capacity check...


        // ----------------------------------------------------
        // GET BUS TOTAL SEATS
        // ----------------------------------------------------

        $busStmt = $conn->prepare("
            SELECT total_seats
            FROM bus
            WHERE id = ?
            LIMIT 1
        ");

        $busStmt->bind_param(
            "i",
            $bus_id
        );

        $busStmt->execute();

        $busResult =
            $busStmt->get_result();


        if ($busResult->num_rows === 0) {

            $message =
                "Selected bus does not exist.";

            $message_type = "danger";

        }

        else {

            $busData =
                $busResult->fetch_assoc();


            $total_bus_seats =
                (int)$busData['total_seats'];


            if (
                $available_seats
                >
                $total_bus_seats
            ) {

                $message =
                    "Available seats cannot exceed "
                    . $total_bus_seats
                    . ".";

                $message_type = "danger";

            }

            else {


                // ------------------------------------------------
                // INSERT
                // ------------------------------------------------

                $stmt = $conn->prepare("

                    INSERT INTO schedule

                    (
                        bus_id,
                        origin_id,
                        destination_id,
                        departure_time,
                        eta_time,
                        available_seats,
                        ticket_price
                    )

                    VALUES (?, ?, ?, ?, ?, ?, ?)

                ");


                $stmt->bind_param(

                    "iiissid",

                    $bus_id,

                    $origin_id,

                    $destination_id,

                    $departure_time,

                    $eta_time,

                    $available_seats,

                    $ticket_price

                );


                if ($stmt->execute()) {

                    $message =
                        "Schedule added successfully.";

                }

                else {

                    $message =
                        "Unable to add schedule.";

                    $message_type = "danger";

                }


                $stmt->close();

            }

        }

        $busStmt->close();

    }

}


// ============================================================
// UPDATE SCHEDULE
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'update'
) {

    $schedule_id =
        (int)($_POST['schedule_id'] ?? 0);

    $bus_id =
        (int)($_POST['bus_id'] ?? 0);

    $origin_id =
        (int)($_POST['origin_id'] ?? 0);

    $destination_id =
        (int)($_POST['destination_id'] ?? 0);

    $departure_time =
        $_POST['departure_time'] ?? '';

    $eta_time =
        $_POST['eta_time'] ?? '';

    $available_seats =
        (int)($_POST['available_seats'] ?? 0);

    $ticket_price =
        (float)($_POST['ticket_price'] ?? 0);


    // --------------------------------------------------------
    // DATE VALIDATION
    // --------------------------------------------------------

    $departureDate = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $departure_time
    );

    $etaDate = DateTime::createFromFormat(
        'Y-m-d\TH:i',
        $eta_time
    );


    // --------------------------------------------------------
    // BASIC VALIDATION
    // --------------------------------------------------------

    if (
        $schedule_id <= 0
        ||
        $bus_id <= 0
        ||
        $origin_id <= 0
        ||
        $destination_id <= 0
        ||
        $departure_time === ''
        ||
        $eta_time === ''
        ||
        $available_seats <= 0
        ||
        $available_seats > 200
        ||
        $ticket_price <= 0
        ||
        $ticket_price > 100000
    ) {

        $message =
            "Please enter all schedule details correctly.";

        $message_type = "danger";

    }


    // --------------------------------------------------------
    // DATE FORMAT VALIDATION
    // --------------------------------------------------------

    elseif (
        !$departureDate
        ||
        !$etaDate
        ||
        $departureDate->format('Y-m-d\TH:i')
        !==
        $departure_time
        ||
        $etaDate->format('Y-m-d\TH:i')
        !==
        $eta_time
    ) {

        $message =
            "Please enter valid departure and ETA times.";

        $message_type = "danger";

    }


    // --------------------------------------------------------
    // SAME LOCATION CHECK
    // --------------------------------------------------------

    elseif ($origin_id === $destination_id) {

        $message =
            "Origin and destination cannot be the same.";

        $message_type = "danger";

    }


    // --------------------------------------------------------
    // ETA CHECK
    // --------------------------------------------------------

    elseif ($etaDate <= $departureDate) {

        $message =
            "ETA must be later than departure time.";

        $message_type = "danger";

    }


    else {

        // ----------------------------------------------------
        // VERIFY SCHEDULE EXISTS
        // ----------------------------------------------------

        $scheduleCheckStmt = $conn->prepare("
            SELECT id
            FROM schedule
            WHERE id = ?
            LIMIT 1
        ");

        $scheduleCheckStmt->bind_param(
            "i",
            $schedule_id
        );

        $scheduleCheckStmt->execute();

        $scheduleCheckResult =
            $scheduleCheckStmt->get_result();


        if ($scheduleCheckResult->num_rows === 0) {

            $message =
                "Schedule does not exist.";

            $message_type = "danger";

        }

        else {

            // ------------------------------------------------
            // VERIFY LOCATIONS EXIST
            // ------------------------------------------------

            $locationStmt = $conn->prepare("
                SELECT id
                FROM location
                WHERE id IN (?, ?)
            ");

            $locationStmt->bind_param(
                "ii",
                $origin_id,
                $destination_id
            );

            $locationStmt->execute();

            $locationResult =
                $locationStmt->get_result();


            if ($locationResult->num_rows !== 2) {

                $message =
                    "Selected origin or destination does not exist.";

                $message_type = "danger";

            }

            else {

                // --------------------------------------------
                // GET BUS CAPACITY
                // --------------------------------------------

                $busStmt = $conn->prepare("
                    SELECT total_seats
                    FROM bus
                    WHERE id = ?
                    LIMIT 1
                ");

                $busStmt->bind_param(
                    "i",
                    $bus_id
                );

                $busStmt->execute();

                $busResult =
                    $busStmt->get_result();


                if ($busResult->num_rows === 0) {

                    $message =
                        "Selected bus does not exist.";

                    $message_type = "danger";

                }

                else {

                    $busData =
                        $busResult->fetch_assoc();

                    $total_bus_seats =
                        (int)$busData['total_seats'];


                    // ----------------------------------------
                    // SEAT CAPACITY CHECK
                    // ----------------------------------------

                    if (
                        $available_seats
                        >
                        $total_bus_seats
                    ) {

                        $message =
                            "Available seats cannot exceed "
                            . $total_bus_seats
                            . ".";

                        $message_type = "danger";

                    }

                    else {

                        // ------------------------------------
                        // UPDATE SCHEDULE
                        // ------------------------------------

                        $stmt = $conn->prepare("
                            UPDATE schedule
                            SET
                                bus_id = ?,
                                origin_id = ?,
                                destination_id = ?,
                                departure_time = ?,
                                eta_time = ?,
                                available_seats = ?,
                                ticket_price = ?
                            WHERE id = ?
                        ");

                        $stmt->bind_param(
                            "iiissidi",
                            $bus_id,
                            $origin_id,
                            $destination_id,
                            $departure_time,
                            $eta_time,
                            $available_seats,
                            $ticket_price,
                            $schedule_id
                        );


                        if ($stmt->execute()) {

                            $message =
                                "Schedule updated successfully.";

                            $message_type = "success";

                        }

                        else {

                            $message =
                                "Unable to update schedule.";

                            $message_type = "danger";

                        }


                        $stmt->close();

                    }

                }


                $busStmt->close();

            }


            $locationStmt->close();

        }


        $scheduleCheckStmt->close();

    }

}

// ============================================================
// DELETE SCHEDULE
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'delete'
) {

    $schedule_id =
        (int)($_POST['schedule_id'] ?? 0);


    // --------------------------------------------------------
    // BASIC ID VALIDATION
    // --------------------------------------------------------

    if ($schedule_id <= 0) {

        $message =
            "Invalid schedule.";

        $message_type = "danger";

    }

    else {

        // ----------------------------------------------------
        // CHECK SCHEDULE EXISTS
        // ----------------------------------------------------

        $checkStmt = $conn->prepare("
            SELECT id
            FROM schedule
            WHERE id = ?
            LIMIT 1
        ");

        $checkStmt->bind_param(
            "i",
            $schedule_id
        );

        $checkStmt->execute();

        $checkResult =
            $checkStmt->get_result();


        if ($checkResult->num_rows === 0) {

            $message =
                "Schedule does not exist.";

            $message_type = "danger";

        }

        else {

            // ------------------------------------------------
            // CHECK EXISTING BOOKINGS
            // ------------------------------------------------

            $bookingStmt = $conn->prepare("
                SELECT COUNT(*) AS total_bookings
                FROM booked
                WHERE schedule_id = ?
            ");

            $bookingStmt->bind_param(
                "i",
                $schedule_id
            );

            $bookingStmt->execute();

            $bookingResult =
                $bookingStmt->get_result();

            $bookingData =
                $bookingResult->fetch_assoc();

            $total_bookings =
                (int)$bookingData['total_bookings'];


            if ($total_bookings > 0) {

                $message =
                    "This schedule has existing bookings and cannot be deleted.";

                $message_type = "danger";

            }

            else {

                // --------------------------------------------
                // DELETE SCHEDULE
                // --------------------------------------------

                $deleteStmt = $conn->prepare("
                    DELETE FROM schedule
                    WHERE id = ?
                ");

                $deleteStmt->bind_param(
                    "i",
                    $schedule_id
                );


                if ($deleteStmt->execute()) {

                    if ($deleteStmt->affected_rows === 1) {

                        $message =
                            "Schedule deleted successfully.";

                        $message_type = "success";

                    }

                    else {

                        $message =
                            "Schedule could not be deleted.";

                        $message_type = "danger";

                    }

                }

                else {

                    $message =
                        "Unable to delete schedule.";

                    $message_type = "danger";

                }


                $deleteStmt->close();

            }


            $bookingStmt->close();

        }


        $checkStmt->close();

    }

}
// ============================================================
// FETCH BUSES
// ============================================================

$busList = $conn->query("
    SELECT id, bus_number, bus_name, total_seats
    FROM bus
    ORDER BY bus_name ASC
");


// ============================================================
// FETCH LOCATIONS
// ============================================================

$locationList = $conn->query("
    SELECT id, location_name
    FROM location
    ORDER BY location_name ASC
");


// ============================================================
// FETCH SCHEDULES
// ============================================================

$scheduleList = $conn->query("

    SELECT

        schedule.id,

        schedule.bus_id,

        schedule.origin_id,

        schedule.destination_id,

        schedule.departure_time,

        schedule.eta_time,

        schedule.available_seats,

        schedule.ticket_price,

        bus.bus_name,

        bus.bus_number,

        origin.location_name AS origin_name,

        destination.location_name AS destination_name

    FROM schedule

    INNER JOIN bus
        ON schedule.bus_id = bus.id

    INNER JOIN location AS origin
        ON schedule.origin_id = origin.id

    INNER JOIN location AS destination
        ON schedule.destination_id = destination.id

    ORDER BY schedule.departure_time DESC

");

?>

<!DOCTYPE html>

<html lang="en">

<head>

    <meta charset="UTF-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1.0"
    >

    <title>Manage Schedules - OBRS</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >


    <style>

        body {
            background: #f5f7fb;
        }

        .form-label {
            font-weight: 600;
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
                $_SESSION['admin_name']
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



<div class="container-fluid py-4">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                📅 Manage Schedules
            </h2>

            <p class="text-muted mb-0">

                Create and manage bus schedules.

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
         MESSAGE
    ====================================================== -->

    <?php if ($message !== ""): ?>

        <div
            class="alert alert-<?php echo $message_type; ?>"
        >

            <?php
            echo htmlspecialchars($message);
            ?>

        </div>

    <?php endif; ?>



    <!-- =====================================================
         ADD SCHEDULE
    ====================================================== -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-primary text-white">

            <strong>
                Add New Schedule
            </strong>

        </div>


        <div class="card-body">

            <form
                method="POST"
                class="row g-3"
            >

                <input
                    type="hidden"
                    name="action"
                    value="add"
                >


                <!-- BUS -->

                <div class="col-md-4">

                    <label class="form-label">
                        Bus
                    </label>

                    <select
                        name="bus_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Bus
                        </option>


                        <?php

                        if (
                            $busList
                            &&
                            $busList->num_rows > 0
                        ):

                            while (
                                $bus =
                                $busList->fetch_assoc()
                            ):

                        ?>

                            <option
                                value="<?php
                                    echo $bus['id'];
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $bus['bus_name']
                                );

                                ?>

                                -
                                <?php

                                echo htmlspecialchars(
                                    $bus['bus_number']
                                );

                                ?>

                            </option>

                        <?php

                            endwhile;

                        endif;

                        ?>

                    </select>

                </div>



                <!-- ORIGIN -->

                <div class="col-md-4">

                    <label class="form-label">
                        Origin
                    </label>

                    <select
                        name="origin_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Origin
                        </option>


                        <?php

                        if (
                            $locationList
                            &&
                            $locationList->num_rows > 0
                        ):

                            // Save locations for edit modal

                            $locations = [];


                            while (
                                $location =
                                $locationList->fetch_assoc()
                            ):

                                $locations[] =
                                    $location;

                        ?>

                            <option
                                value="<?php
                                    echo $location['id'];
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $location['location_name']
                                );

                                ?>

                            </option>

                        <?php

                            endwhile;

                        endif;

                        ?>

                    </select>

                </div>



                <!-- DESTINATION -->

                <div class="col-md-4">

                    <label class="form-label">
                        Destination
                    </label>

                    <select
                        name="destination_id"
                        class="form-select"
                        required
                    >

                        <option value="">
                            Select Destination
                        </option>


                        <?php

                        if (
                            isset($locations)
                        ):

                            foreach (
                                $locations
                                as $location
                            ):

                        ?>

                            <option
                                value="<?php
                                    echo $location['id'];
                                ?>"
                            >

                                <?php

                                echo htmlspecialchars(
                                    $location['location_name']
                                );

                                ?>

                            </option>

                        <?php

                            endforeach;

                        endif;

                        ?>

                    </select>

                </div>



                <!-- DEPARTURE -->

                <div class="col-md-3">

                    <label class="form-label">
                        Departure
                    </label>

                    <input
                        type="datetime-local"
                        name="departure_time"
                        class="form-control"
                        required
                    >

                </div>



                <!-- ETA -->

                <div class="col-md-3">

                    <label class="form-label">
                        ETA
                    </label>

                    <input
                        type="datetime-local"
                        name="eta_time"
                        class="form-control"
                        required
                    >

                </div>



                <!-- AVAILABLE SEATS -->

                <div class="col-md-3">

                    <label class="form-label">
                        Available Seats
                    </label>

                    <input
                        type="number"
                        name="available_seats"
                        class="form-control"
                        min="1"
                        max="200"
                        required
                    >

                </div>



                <!-- PRICE -->

                <div class="col-md-3">

                    <label class="form-label">
                        Ticket Price
                    </label>

                    <input
                        type="number"
                        name="ticket_price"
                        class="form-control"
                        min="0"
                        step="0.01"
                        required
                    >

                </div>



                <!-- BUTTON -->

                <div class="col-12">

                    <button
                        type="submit"
                        class="btn btn-success"
                    >

                        + Add Schedule

                    </button>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         SCHEDULE LIST
    ====================================================== -->

    <div class="card shadow-sm border-0">

        <div class="card-header">

            <strong>
                Schedule List
            </strong>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover align-middle">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>Bus</th>

                            <th>Route</th>

                            <th>Departure</th>

                            <th>ETA</th>

                            <th>Seats</th>

                            <th>Price</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        $scheduleList
                        &&
                        $scheduleList->num_rows > 0
                    ):

                        while (
                            $schedule =
                            $scheduleList->fetch_assoc()
                        ):

                    ?>

                        <tr>


                            <td>

                                <?php
                                echo $schedule['id'];
                                ?>

                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $schedule['bus_name']
                                    );

                                    ?>

                                </strong>

                                <br>

                                <small class="text-muted">

                                    <?php

                                    echo htmlspecialchars(
                                        $schedule['bus_number']
                                    );

                                    ?>

                                </small>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $schedule['origin_name']
                                );

                                ?>

                                →

                                <?php

                                echo htmlspecialchars(
                                    $schedule['destination_name']
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $schedule['departure_time']
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <?php

                                echo date(
                                    'd M Y, h:i A',
                                    strtotime(
                                        $schedule['eta_time']
                                    )
                                );

                                ?>

                            </td>


                            <td>

                                <span class="badge bg-success">

                                    <?php

                                    echo $schedule[
                                        'available_seats'
                                    ];

                                    ?>

                                </span>

                            </td>


                            <td>

                                <strong>

                                    ₹<?php

                                    echo number_format(
                                        $schedule[
                                            'ticket_price'
                                        ],
                                        2
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editScheduleModal"
                                    onclick="editSchedule(this)"
                                    data-id="<?php echo $schedule['id']; ?>"
                                    data-bus="<?php echo $schedule['bus_id']; ?>"
                                    data-origin="<?php echo $schedule['origin_id']; ?>"
                                    data-destination="<?php echo $schedule['destination_id']; ?>"
                                    data-departure="<?php echo date('Y-m-d\TH:i', strtotime($schedule['departure_time'])); ?>"
                                    data-eta="<?php echo date('Y-m-d\TH:i', strtotime($schedule['eta_time'])); ?>"
                                    data-seats="<?php echo $schedule['available_seats']; ?>"
                                    data-price="<?php echo $schedule['ticket_price']; ?>"
                                >

                                    Edit

                                </button>


                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this schedule?');"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="schedule_id"
                                        value="<?php
                                            echo $schedule['id'];
                                        ?>"
                                    >

                                    <button
                                        type="submit"
                                        class="btn btn-danger btn-sm"
                                    >

                                        Delete

                                    </button>

                                </form>

                            </td>


                        </tr>

                    <?php

                        endwhile;

                    else:

                    ?>

                        <tr>

                            <td
                                colspan="8"
                                class="text-center"
                            >

                                No schedules found.

                            </td>

                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>



<!-- =========================================================
     EDIT SCHEDULE MODAL
========================================================== -->

<div
    class="modal fade"
    id="editScheduleModal"
    tabindex="-1"
>

    <div class="modal-dialog modal-lg">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Edit Schedule
                </h5>

                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="modal"
                ></button>

            </div>


            <form method="POST">


                <div class="modal-body">

                    <input
                        type="hidden"
                        name="action"
                        value="update"
                    >


                    <input
                        type="hidden"
                        name="schedule_id"
                        id="edit_schedule_id"
                    >


                    <div class="row g-3">


                        <!-- BUS -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Bus
                            </label>

                            <select
                                name="bus_id"
                                id="edit_bus_id"
                                class="form-select"
                                required
                            >

                                <?php

                                $editBuses =
                                    $conn->query("

                                        SELECT
                                            id,
                                            bus_name,
                                            bus_number

                                        FROM bus

                                        ORDER BY bus_name

                                    ");

                                while (
                                    $bus =
                                    $editBuses->fetch_assoc()
                                ):

                                ?>

                                    <option
                                        value="<?php
                                            echo $bus['id'];
                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $bus['bus_name']
                                        );

                                        ?>

                                        -
                                        <?php

                                        echo htmlspecialchars(
                                            $bus['bus_number']
                                        );

                                        ?>

                                    </option>

                                <?php endwhile; ?>

                            </select>

                        </div>



                        <!-- ORIGIN -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Origin
                            </label>

                            <select
                                name="origin_id"
                                id="edit_origin_id"
                                class="form-select"
                                required
                            >

                                <?php

                                foreach (
                                    $locations as $location
                                ):

                                ?>

                                    <option
                                        value="<?php
                                            echo $location['id'];
                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $location[
                                                'location_name'
                                            ]
                                        );

                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>



                        <!-- DESTINATION -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Destination
                            </label>

                            <select
                                name="destination_id"
                                id="edit_destination_id"
                                class="form-select"
                                required
                            >

                                <?php

                                foreach (
                                    $locations as $location
                                ):

                                ?>

                                    <option
                                        value="<?php
                                            echo $location['id'];
                                        ?>"
                                    >

                                        <?php

                                        echo htmlspecialchars(
                                            $location[
                                                'location_name'
                                            ]
                                        );

                                        ?>

                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>



                        <!-- DEPARTURE -->

                        <div class="col-md-6">

                            <label class="form-label">
                                Departure
                            </label>

                            <input
                                type="datetime-local"
                                name="departure_time"
                                id="edit_departure_time"
                                class="form-control"
                                required
                            >

                        </div>



                        <!-- ETA -->

                        <div class="col-md-6">

                            <label class="form-label">
                                ETA
                            </label>

                            <input
                                type="datetime-local"
                                name="eta_time"
                                id="edit_eta_time"
                                class="form-control"
                                required
                            >

                        </div>



                        <!-- SEATS -->

                        <div class="col-md-3">

                            <label class="form-label">
                                Available Seats
                            </label>

                            <input
                                type="number"
                                name="available_seats"
                                id="edit_available_seats"
                                class="form-control"
                                min="1"
                                max="200"
                                required
                            >

                        </div>



                        <!-- PRICE -->

                        <div class="col-md-3">

                            <label class="form-label">
                                Ticket Price
                            </label>

                            <input
                                type="number"
                                name="ticket_price"
                                id="edit_ticket_price"
                                class="form-control"
                                min="0"
                                step="0.01"
                                required
                            >

                        </div>


                    </div>

                </div>


                <div class="modal-footer">

                    <button
                        type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal"
                    >

                        Cancel

                    </button>


                    <button
                        type="submit"
                        class="btn btn-primary"
                    >

                        Update Schedule

                    </button>

                </div>


            </form>

        </div>

    </div>

</div>



<!-- =========================================================
     BOOTSTRAP JS
========================================================== -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>



<script>

function editSchedule(button) {


    document.getElementById(
        'edit_schedule_id'
    ).value =
        button.getAttribute('data-id');


    document.getElementById(
        'edit_bus_id'
    ).value =
        button.getAttribute('data-bus');


    document.getElementById(
        'edit_origin_id'
    ).value =
        button.getAttribute('data-origin');


    document.getElementById(
        'edit_destination_id'
    ).value =
        button.getAttribute('data-destination');


    document.getElementById(
        'edit_departure_time'
    ).value =
        button.getAttribute('data-departure');


    document.getElementById(
        'edit_eta_time'
    ).value =
        button.getAttribute('data-eta');


    document.getElementById(
        'edit_available_seats'
    ).value =
        button.getAttribute('data-seats');


    document.getElementById(
        'edit_ticket_price'
    ).value =
        button.getAttribute('data-price');

}

</script>


</body>

</html>
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


// ============================================================
// MESSAGE VARIABLES
// ============================================================

$message = "";
$message_type = "success";


// ============================================================
// ADD BUS
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'add'
) {

    $bus_number =
        trim($_POST['bus_number'] ?? '');

    $bus_name =
        trim($_POST['bus_name'] ?? '');

    $total_seats =
        (int)($_POST['total_seats'] ?? 0);


   if (
    $bus_number === ''
    ||
    $bus_name === ''
    ||
    $total_seats < 1
    ||
    $total_seats > 200
    ||
    strlen($bus_number) > 50
    ||
    strlen($bus_name) > 150
) {

        $message =
            "Please enter valid bus details.";

        $message_type = "danger";

    }

    else {

        // Check duplicate bus number

        $checkStmt = $conn->prepare("
            SELECT id
            FROM bus
            WHERE bus_number = ?
            LIMIT 1
        ");

        $checkStmt->bind_param(
            "s",
            $bus_number
        );

        $checkStmt->execute();

        $checkResult =
            $checkStmt->get_result();


        if ($checkResult->num_rows > 0) {

            $message =
                "This bus number already exists.";

            $message_type = "danger";

        }

        else {

            $stmt = $conn->prepare("
                INSERT INTO bus
                (
                    bus_number,
                    bus_name,
                    total_seats
                )
                VALUES (?, ?, ?)
            ");

            $stmt->bind_param(
                "ssi",
                $bus_number,
                $bus_name,
                $total_seats
            );


            if ($stmt->execute()) {

                $message =
                    "Bus added successfully.";

            }

            else {

                $message =
                    "Unable to add bus.";

                $message_type = "danger";

            }

            $stmt->close();

        }

        $checkStmt->close();

    }

}


// ============================================================
// UPDATE BUS
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'update'
) {

    $bus_id =
        (int)($_POST['bus_id'] ?? 0);

    $bus_number =
        trim($_POST['bus_number'] ?? '');

    $bus_name =
        trim($_POST['bus_name'] ?? '');

    $total_seats =
        (int)($_POST['total_seats'] ?? 0);


   if (
    $bus_id <= 0
    ||
    $bus_number === ''
    ||
    $bus_name === ''
    ||
    $total_seats < 1
    ||
    $total_seats > 200
    ||
    strlen($bus_number) > 50
    ||
    strlen($bus_name) > 150
) {

        $message =
            "Please enter valid bus details.";

        $message_type = "danger";

    }

    else {

        $checkStmt = $conn->prepare("
            SELECT id
            FROM bus
            WHERE bus_number = ?
            AND id != ?
            LIMIT 1
        ");

        $checkStmt->bind_param(
            "si",
            $bus_number,
            $bus_id
        );

        $checkStmt->execute();

        $checkResult =
            $checkStmt->get_result();


        if ($checkResult->num_rows > 0) {

            $message =
                "Another bus already uses this bus number.";

            $message_type = "danger";

        }

        else {

            $stmt = $conn->prepare("
                UPDATE bus
                SET
                    bus_number = ?,
                    bus_name = ?,
                    total_seats = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "ssii",
                $bus_number,
                $bus_name,
                $total_seats,
                $bus_id
            );


            if ($stmt->execute()) {

                $message =
                    "Bus updated successfully.";

            }

            else {

                $message =
                    "Unable to update bus.";

                $message_type = "danger";

            }

            $stmt->close();

        }

        $checkStmt->close();

    }

}


// ============================================================
// DELETE BUS
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'delete'
) {

    $bus_id =
        (int)($_POST['bus_id'] ?? 0);


    if ($bus_id <= 0) {

        $message =
            "Invalid bus.";

        $message_type = "danger";

    }

    else {

        $stmt = $conn->prepare("
            DELETE FROM bus
            WHERE id = ?
        ");

        $stmt->bind_param(
            "i",
            $bus_id
        );


        if ($stmt->execute()) {

            $message =
                "Bus deleted successfully.";

        }

        else {

            $message =
                "Unable to delete bus.";

            $message_type = "danger";

        }

        $stmt->close();

    }

}


// ============================================================
// FETCH BUSES
// ============================================================

$result = $conn->query("
    SELECT *
    FROM bus
    ORDER BY id DESC
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

    <title>Manage Buses - OBRS</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


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



<div class="container py-4">


    <!-- =====================================================
         HEADER
    ====================================================== -->

    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                🚌 Manage Buses
            </h2>

            <p class="text-muted mb-0">
                Add, edit and delete buses.
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
         ADD BUS
    ====================================================== -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-primary text-white">

            <strong>
                Add New Bus
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


                <div class="col-md-4">

                    <label class="form-label">
                        Bus Number
                    </label>

                    <input
                        type="text"
                        name="bus_number"
                        class="form-control"
                        placeholder="Example: MH-19-AB-1234"
                        maxlength="50"
                        required
                    >

                </div>


                <div class="col-md-4">

                    <label class="form-label">
                        Bus Name
                    </label>

                    <input
                        type="text"
                        name="bus_name"
                        class="form-control"
                        placeholder="Example: Maharashtra Express"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="col-md-2">

                    <label class="form-label">
                        Total Seats
                    </label>

                    <input
                        type="number"
                        name="total_seats"
                        class="form-control"
                        min="1"
                        max="200"
                        value="40"
                        required
                    >

                </div>


                <div class="col-md-2 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                    >

                        + Add Bus

                    </button>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         BUS LIST
    ====================================================== -->

    <div class="card shadow-sm border-0">

        <div class="card-header">

            <strong>
                Bus List
            </strong>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>Bus Number</th>

                            <th>Bus Name</th>

                            <th>Total Seats</th>

                            <th>Action</th>

                        </tr>

                    </thead>


                    <tbody>

                    <?php

                    if (
                        $result
                        &&
                        $result->num_rows > 0
                    ):

                        while (
                            $row =
                            $result->fetch_assoc()
                        ):

                    ?>

                        <tr>

                            <td>
                                <?php
                                echo $row['id'];
                                ?>
                            </td>


                            <td>

                                <strong>

                                    <?php

                                    echo htmlspecialchars(
                                        $row['bus_number']
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>

                                <?php

                                echo htmlspecialchars(
                                    $row['bus_name']
                                );

                                ?>

                            </td>


                            <td>

                                <?php
                                echo $row['total_seats'];
                                ?>

                            </td>


                            <td>


                                <!-- EDIT -->

                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editBusModal"
                                    data-id="<?php echo $row['id']; ?>"
                                    data-number="<?php echo htmlspecialchars($row['bus_number'], ENT_QUOTES); ?>"
                                    data-name="<?php echo htmlspecialchars($row['bus_name'], ENT_QUOTES); ?>"
                                    data-seats="<?php echo $row['total_seats']; ?>"
                                    onclick="editBus(this)"
                                >

                                    Edit

                                </button>



                                <!-- DELETE -->

                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this bus?');"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="bus_id"
                                        value="<?php
                                            echo $row['id'];
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
                                colspan="5"
                                class="text-center"
                            >

                                No buses found.

                            </td>

                        </tr>

                    <?php

                    endif;

                    ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>


</div>



<!-- =========================================================
     EDIT BUS MODAL
========================================================== -->

<div
    class="modal fade"
    id="editBusModal"
    tabindex="-1"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Edit Bus
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
                        name="bus_id"
                        id="edit_bus_id"
                    >


                    <div class="mb-3">

                        <label class="form-label">
                            Bus Number
                        </label>

                        <input
                            type="text"
                            name="bus_number"
                            id="edit_bus_number"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Bus Name
                        </label>

                        <input
                            type="text"
                            name="bus_name"
                            id="edit_bus_name"
                            class="form-control"
                            required
                        >

                    </div>


                    <div class="mb-3">

                        <label class="form-label">
                            Total Seats
                        </label>

                        <input
                            type="number"
                            name="total_seats"
                            id="edit_total_seats"
                            class="form-control"
                            min="1"
                            max="200"
                            required
                        >

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

                        Update Bus

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<!-- Bootstrap JS -->

<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

function editBus(button) {

    document.getElementById('edit_bus_id').value =
        button.getAttribute('data-id');

    document.getElementById('edit_bus_number').value =
        button.getAttribute('data-number');

    document.getElementById('edit_bus_name').value =
        button.getAttribute('data-name');

    document.getElementById('edit_total_seats').value =
        button.getAttribute('data-seats');

}

</script>


</body>

</html>
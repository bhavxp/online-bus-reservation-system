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
// ADD LOCATION
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'add'
) {

    $location_name =
        trim($_POST['location_name'] ?? '');


    if (
    $location_name === ''
    ||
    strlen($location_name) > 150
) {

        $message =
            "Please enter location name.";

        $message_type = "danger";

    }

    else {

        $checkStmt = $conn->prepare("
            SELECT id
            FROM location
            WHERE location_name = ?
            LIMIT 1
        ");

        $checkStmt->bind_param(
            "s",
            $location_name
        );

        $checkStmt->execute();

        $checkResult =
            $checkStmt->get_result();


        if ($checkResult->num_rows > 0) {

            $message =
                "This location already exists.";

            $message_type = "danger";

        }

        else {

            $stmt = $conn->prepare("
                INSERT INTO location
                (location_name)
                VALUES (?)
            ");

            $stmt->bind_param(
                "s",
                $location_name
            );


            if ($stmt->execute()) {

                $message =
                    "Location added successfully.";

            }

            else {

                $message =
                    "Unable to add location.";

                $message_type = "danger";

            }

            $stmt->close();

        }

        $checkStmt->close();

    }

}


// ============================================================
// UPDATE LOCATION
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'update'
) {

    $location_id =
        (int)($_POST['location_id'] ?? 0);

    $location_name =
        trim($_POST['location_name'] ?? '');


    if (
    $location_id <= 0
    ||
    $location_name === ''
    ||
    strlen($location_name) > 150
) {

        $message =
            "Invalid location details.";

        $message_type = "danger";

    }

    else {

        $checkStmt = $conn->prepare("
            SELECT id
            FROM location
            WHERE location_name = ?
            AND id != ?
            LIMIT 1
        ");

        $checkStmt->bind_param(
            "si",
            $location_name,
            $location_id
        );

        $checkStmt->execute();

        $checkResult =
            $checkStmt->get_result();


        if ($checkResult->num_rows > 0) {

            $message =
                "Another location already uses this name.";

            $message_type = "danger";

        }

        else {

            $stmt = $conn->prepare("
                UPDATE location
                SET location_name = ?
                WHERE id = ?
            ");

            $stmt->bind_param(
                "si",
                $location_name,
                $location_id
            );


            if ($stmt->execute()) {

                $message =
                    "Location updated successfully.";

            }

            else {

                $message =
                    "Unable to update location.";

                $message_type = "danger";

            }

            $stmt->close();

        }

        $checkStmt->close();

    }

}


// ============================================================
// DELETE LOCATION
// ============================================================

if (
    $_SERVER['REQUEST_METHOD'] === 'POST'
    &&
    isset($_POST['action'])
    &&
    $_POST['action'] === 'delete'
) {

    $location_id =
        (int)($_POST['location_id'] ?? 0);


    if ($location_id <= 0) {

        $message =
            "Invalid location.";

        $message_type = "danger";

    }

    else {

        $stmt = $conn->prepare("
            DELETE FROM location
            WHERE id = ?
        ");

        $stmt->bind_param(
            "i",
            $location_id
        );


        if ($stmt->execute()) {

            $message =
                "Location deleted successfully.";

        }

        else {

            $message =
                "Unable to delete location.";

            $message_type = "danger";

        }

        $stmt->close();

    }

}


// ============================================================
// FETCH LOCATIONS
// ============================================================

$result = $conn->query("
    SELECT *
    FROM location
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

    <title>Manage Locations - OBRS</title>


    <link
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
        rel="stylesheet"
    >

</head>


<body class="bg-light">


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


    <div class="d-flex justify-content-between align-items-center mb-4">

        <div>

            <h2 class="fw-bold">
                📍 Manage Locations
            </h2>

            <p class="text-muted">
                Add, edit and delete route locations.
            </p>

        </div>


        <a
            href="admin_home.php"
            class="btn btn-secondary"
        >

            ← Dashboard

        </a>

    </div>



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
         ADD LOCATION
    ====================================================== -->

    <div class="card shadow-sm border-0 mb-4">

        <div class="card-header bg-primary text-white">

            <strong>
                Add New Location
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


                <div class="col-md-9">

                    <label class="form-label">
                        Location Name
                    </label>

                    <input
                        type="text"
                        name="location_name"
                        class="form-control"
                        placeholder="Example: Pune"
                        maxlength="150"
                        required
                    >

                </div>


                <div class="col-md-3 d-flex align-items-end">

                    <button
                        type="submit"
                        class="btn btn-success w-100"
                    >

                        + Add Location

                    </button>

                </div>

            </form>

        </div>

    </div>



    <!-- =====================================================
         LOCATION LIST
    ====================================================== -->

    <div class="card shadow-sm border-0">

        <div class="card-header">

            <strong>
                Location List
            </strong>

        </div>


        <div class="card-body">

            <div class="table-responsive">

                <table class="table table-striped table-hover">

                    <thead class="table-dark">

                        <tr>

                            <th>ID</th>

                            <th>Location Name</th>

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
                                        $row['location_name']
                                    );

                                    ?>

                                </strong>

                            </td>


                            <td>


                                <button
                                    type="button"
                                    class="btn btn-warning btn-sm"
                                    data-bs-toggle="modal"
                                    data-bs-target="#editLocationModal"
                                    onclick="editLocation(
                                        '<?php echo $row['id']; ?>',
                                        '<?php echo htmlspecialchars($row['location_name'], ENT_QUOTES); ?>'
                                    )"
                                >

                                    Edit

                                </button>



                                <form
                                    method="POST"
                                    class="d-inline"
                                    onsubmit="return confirm('Are you sure you want to delete this location?');"
                                >

                                    <input
                                        type="hidden"
                                        name="action"
                                        value="delete"
                                    >

                                    <input
                                        type="hidden"
                                        name="location_id"
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
                                colspan="3"
                                class="text-center"
                            >

                                No locations found.

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
     EDIT LOCATION MODAL
========================================================== -->

<div
    class="modal fade"
    id="editLocationModal"
    tabindex="-1"
>

    <div class="modal-dialog">

        <div class="modal-content">


            <div class="modal-header">

                <h5 class="modal-title">
                    Edit Location
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
                        name="location_id"
                        id="edit_location_id"
                    >


                    <div class="mb-3">

                        <label class="form-label">
                            Location Name
                        </label>

                        <input
                            type="text"
                            name="location_name"
                            id="edit_location_name"
                            class="form-control"
                            maxlength="150"
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

                        Update Location

                    </button>

                </div>

            </form>

        </div>

    </div>

</div>



<script
    src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js">
</script>


<script>

function editLocation(id, name) {

    document.getElementById(
        'edit_location_id'
    ).value = id;


    document.getElementById(
        'edit_location_name'
    ).value = name;

}

</script>


</body>

</html>
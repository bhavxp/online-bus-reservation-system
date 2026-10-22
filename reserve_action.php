<?php

// ============================================================
// ONLINE BUS RESERVATION SYSTEM
// FINAL BOOKING PROCESSOR
// ============================================================

header('Content-Type: application/json');

session_start();

require_once 'config/db_connect.php';


// ============================================================
// HELPER
// ============================================================

function json_error($message)
{
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => $message
    ]);
    exit;
}


// ============================================================
// ONLY POST
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    json_error(
        'Invalid request method.'
    );
}


// ============================================================
// USER LOGIN CHECK
// ============================================================

if (
    empty($_SESSION['user_logged_in']) ||
    empty($_SESSION['user_id'])
) {

    json_error(
        'Please login before making a reservation.'
    );
}


$user_id = (int) $_SESSION['user_id'];


// ============================================================
// FORM DATA
// ============================================================

$schedule_id = isset($_POST['schedule_id'])
    ? (int) $_POST['schedule_id']
    : 0;

$customer_name = isset($_POST['customer_name'])
    ? trim($_POST['customer_name'])
    : '';

$customer_phone = isset($_POST['customer_phone'])
    ? trim($_POST['customer_phone'])
    : '';

$customer_email = isset($_POST['customer_email'])
    ? trim($_POST['customer_email'])
    : '';

$seats = isset($_POST['seats'])
    ? (int) $_POST['seats']
    : 0;

$selected_seats_raw = isset($_POST['selected_seats'])
    ? $_POST['selected_seats']
    : '';


// ============================================================
// BASIC VALIDATION
// ============================================================

if ($schedule_id <= 0) {

    json_error(
        'Invalid bus schedule.'
    );
}


if ($customer_name === '') {

    json_error(
        'Please enter customer name.'
    );
}


if (strlen($customer_name) > 150) {

    json_error(
        'Customer name is too long.'
    );
}


if ($customer_phone === '') {

    json_error(
        'Please enter mobile number.'
    );
}


if (!preg_match('/^[0-9]{10}$/', $customer_phone)) {

    json_error(
        'Please enter a valid 10 digit mobile number.'
    );
}


if (
    $customer_email === '' ||
    !filter_var(
        $customer_email,
        FILTER_VALIDATE_EMAIL
    )
) {

    json_error(
        'Please enter a valid email address.'
    );
}


if ($seats <= 0) {

    json_error(
        'Please select at least one seat.'
    );
}


// ============================================================
// PARSE SELECTED SEATS
// ============================================================

$selected_seats = [];


if ($selected_seats_raw !== '') {

    $decoded =
        json_decode(
            $selected_seats_raw,
            true
        );


    if (is_array($decoded)) {

        foreach ($decoded as $seat) {

            $seat_number = (int) $seat;


            if ($seat_number > 0) {

                $selected_seats[] =
                    $seat_number;
            }
        }
    }
}


$selected_seats =
    array_values(
        array_unique(
            $selected_seats
        )
    );


sort($selected_seats);


// ============================================================
// SEAT COUNT MUST MATCH
// ============================================================

if (
    count($selected_seats) !==
    $seats
) {

    json_error(
        'Selected seat count does not match booking.'
    );
}


// ============================================================
// GET CUSTOMER ACCOUNT
// ============================================================

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

    json_error(
        'Unable to load customer account.'
    );
}


$userStmt->bind_param(
    "i",
    $user_id
);


if (!$userStmt->execute()) {

    $userStmt->close();

    json_error(
        'Unable to verify customer account.'
    );
}


$userResult =
    $userStmt->get_result();


if (
    !$userResult ||
    $userResult->num_rows === 0
) {

    $userStmt->close();

    json_error(
        'Customer account not found.'
    );
}


$user =
    $userResult->fetch_assoc();


$userStmt->close();


if (
    $user['status'] !== 'Active'
) {

    json_error(
        'Your account is currently blocked.'
    );
}


// ============================================================
// GET SCHEDULE + LOCK ROW
// ============================================================

$conn->begin_transaction();


try {


    $scheduleStmt = $conn->prepare("
        SELECT
            s.id,
            s.available_seats,
            s.ticket_price,
            b.total_seats
        FROM schedule s
        INNER JOIN bus b
            ON s.bus_id = b.id
        WHERE s.id = ?
        FOR UPDATE
    ");


    if (!$scheduleStmt) {

        throw new Exception(
            'Unable to prepare schedule query.'
        );
    }


    $scheduleStmt->bind_param(
        "i",
        $schedule_id
    );


    if (!$scheduleStmt->execute()) {

        throw new Exception(
            'Unable to load bus schedule.'
        );
    }


    $scheduleResult =
        $scheduleStmt->get_result();


    if (
        !$scheduleResult ||
        $scheduleResult->num_rows === 0
    ) {

        $scheduleStmt->close();

        throw new Exception(
            'Bus schedule not found.'
        );
    }


    $schedule =
        $scheduleResult->fetch_assoc();


    $scheduleStmt->close();


    $available_seats =
        (int) $schedule['available_seats'];


    $ticket_price =
        (float) $schedule['ticket_price'];


    $total_seats =
        (int) $schedule['total_seats'];


    // ========================================================
    // CHECK SEAT COUNT
    // ========================================================

    if (
        $seats > $available_seats
    ) {

        throw new Exception(
            'Only ' .
            $available_seats .
            ' seats are available.'
        );
    }


    // ========================================================
    // VALIDATE PHYSICAL SEAT NUMBERS
    // ========================================================

    foreach (
        $selected_seats
        as $seat_number
    ) {

        if (
            $seat_number < 1 ||
            $seat_number > $total_seats
        ) {

            throw new Exception(
                'Invalid seat number: ' .
                $seat_number
            );
        }
    }


    // ========================================================
    // CHECK ALREADY BOOKED SEATS
    // ========================================================

    $seatCheckStmt = $conn->prepare("
        SELECT seat_number
        FROM reservation_seats
        WHERE schedule_id = ?
        AND seat_number IN (
            " .
            implode(
                ',',
                array_fill(
                    0,
                    count($selected_seats),
                    '?'
                )
            )
            . "
        )
        FOR UPDATE
    ");


    if (!$seatCheckStmt) {

        throw new Exception(
            'Unable to check seat availability.'
        );
    }


    $types =
        'i' .
        str_repeat(
            'i',
            count($selected_seats)
        );


    $params =
        array_merge(
            [$schedule_id],
            $selected_seats
        );


    $bindParams = [];


    $bindParams[] =
        $types;


    foreach ($params as $key => $value) {

        $bindParams[] =
            &$params[$key];
    }


    call_user_func_array(
        [$seatCheckStmt, 'bind_param'],
        $bindParams
    );


    if (!$seatCheckStmt->execute()) {

        $error =
            $seatCheckStmt->error;

        $seatCheckStmt->close();

        throw new Exception(
            'Unable to verify selected seats. ' .
            $error
        );
    }


    $bookedSeatResult =
        $seatCheckStmt->get_result();


    $alreadyBooked = [];


    while (
        $seatRow =
        $bookedSeatResult->fetch_assoc()
    ) {

        $alreadyBooked[] =
            (int) $seatRow['seat_number'];
    }


    $seatCheckStmt->close();


    if (
        count($alreadyBooked) > 0
    ) {

        throw new Exception(
            'Seat ' .
            implode(
                ', ',
                $alreadyBooked
            ) .
            ' is already booked. Please select another seat.'
        );
    }


    // ========================================================
    // TOTAL AMOUNT
    // ========================================================

    $total_amount =
        $seats *
        $ticket_price;


    // ========================================================
    // UNIQUE BOOKING REFERENCE
    // ========================================================

    do {

        $reference_no =
            'OBRS' .
            date('YmdHis') .
            rand(
                100,
                999
            );


        $refStmt =
            $conn->prepare("
                SELECT id
                FROM booked
                WHERE reference_no = ?
                LIMIT 1
            ");


        if (!$refStmt) {

            throw new Exception(
                'Unable to generate booking reference.'
            );
        }


        $refStmt->bind_param(
            "s",
            $reference_no
        );


        $refStmt->execute();


        $refResult =
            $refStmt->get_result();


        $referenceExists =
            $refResult->num_rows > 0;


        $refStmt->close();


    } while (
        $referenceExists
    );


    // ========================================================
    // INSERT BOOKING
    // ========================================================

    $insertStmt = $conn->prepare("
        INSERT INTO booked
        (
            user_id,
            reference_no,
            schedule_id,
            customer_name,
            customer_phone,
            customer_email,
            seats,
            ticket_price,
            total_amount,
            payment_status
        )
        VALUES
        (
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            ?,
            'Unpaid'
        )
    ");


    if (!$insertStmt) {

        throw new Exception(
            'Unable to prepare booking.'
        );
    }


    $insertStmt->bind_param(
        "isisssidd",
        $user_id,
        $reference_no,
        $schedule_id,
        $customer_name,
        $customer_phone,
        $customer_email,
        $seats,
        $ticket_price,
        $total_amount
    );


    if (!$insertStmt->execute()) {

        $error =
            $insertStmt->error;

        $insertStmt->close();

        throw new Exception(
            'Unable to save booking. ' .
            $error
        );
    }


    $booking_id =
        $conn->insert_id;


    $insertStmt->close();


    // ========================================================
    // INSERT PHYSICAL SEATS
    // ========================================================

    $seatInsertStmt =
        $conn->prepare("
            INSERT INTO reservation_seats
            (
                booking_id,
                schedule_id,
                seat_number
            )
            VALUES
            (
                ?,
                ?,
                ?
            )
        ");


    if (!$seatInsertStmt) {

        throw new Exception(
            'Unable to prepare seat reservation.'
        );
    }


    foreach (
        $selected_seats
        as $seat_number
    ) {

        $seatInsertStmt->bind_param(
            "iii",
            $booking_id,
            $schedule_id,
            $seat_number
        );


        if (
            !$seatInsertStmt->execute()
        ) {

            $error =
                $seatInsertStmt->error;

            $seatInsertStmt->close();

            throw new Exception(
                'Unable to reserve seat ' .
                $seat_number .
                '. ' .
                $error
            );
        }
    }


    $seatInsertStmt->close();


    // ========================================================
    // REDUCE AVAILABLE SEATS
    // ========================================================

    $updateStmt = $conn->prepare("
        UPDATE schedule
        SET available_seats =
            available_seats - ?
        WHERE id = ?
        AND available_seats >= ?
    ");


    if (!$updateStmt) {

        throw new Exception(
            'Unable to update available seats.'
        );
    }


    $updateStmt->bind_param(
        "iii",
        $seats,
        $schedule_id,
        $seats
    );


    if (!$updateStmt->execute()) {

        $error =
            $updateStmt->error;

        $updateStmt->close();

        throw new Exception(
            'Unable to update available seats. ' .
            $error
        );
    }


    if (
        $updateStmt->affected_rows !== 1
    ) {

        $updateStmt->close();

        throw new Exception(
            'Seats are no longer available.'
        );
    }


    $updateStmt->close();


    // ========================================================
    // CREATE PAYMENT RECORD
    // ========================================================

    $payment_reference =
        $reference_no;

    $payment_method =
        'Online';

    $payment_status =
        'Pending';


    $paymentStmt =
        $conn->prepare("
            INSERT INTO payments
            (
                booking_id,
                payment_reference,
                amount,
                payment_method,
                payment_status
            )
            VALUES
            (
                ?,
                ?,
                ?,
                ?,
                ?
            )
        ");


    if (!$paymentStmt) {

        throw new Exception(
            'Unable to prepare payment record.'
        );
    }


    $paymentStmt->bind_param(
        "isdss",
        $booking_id,
        $payment_reference,
        $total_amount,
        $payment_method,
        $payment_status
    );


    if (!$paymentStmt->execute()) {

        $error =
            $paymentStmt->error;

        $paymentStmt->close();

        throw new Exception(
            'Unable to create payment record. ' .
            $error
        );
    }


    $paymentStmt->close();


    // ========================================================
    // COMMIT
    // ========================================================

    if (!$conn->commit()) {

        throw new Exception(
            'Unable to complete booking transaction.'
        );
    }

$conn->commit();

/* =========================
   SEND BOOKING EMAIL
   ========================= */

$email_sent = false;

try {

    require_once 'send_booking_email.php';

    $email_sent = send_booking_confirmation_email(
        $customer_email,
        $customer_name,
        $reference_no,
        $selected_seats,
        $total_amount,
        'http://localhost/obrs/ticket.php?reference=' .
        urlencode($reference_no)
    );

    if (!$email_sent) {
        error_log(
            'OBRS: Booking email failed for reference ' .
            $reference_no
        );
    }

} catch (Throwable $emailError) {

    error_log(
        'OBRS EMAIL ERROR: ' .
        $emailError->getMessage()
    );
}
    // ========================================================
    // SUCCESS
    // ========================================================

    echo json_encode([
        'status' => 'success',
        'success' => true,
        'message' =>
            'Your bus reservation has been confirmed successfully.',
        'reference_no' =>
            $reference_no,
        'booking_id' =>
            $booking_id,
        'seats' =>
            $selected_seats,
        'seat_count' =>
            $seats,
        'total_amount' =>
            $total_amount,
        'ticket_url' =>
            'ticket.php?reference=' .
            urlencode($reference_no)
    ]);


} catch (Throwable $e) {


    // ========================================================
    // ROLLBACK
    // ========================================================

    $conn->rollback();


    // Log exact technical error
    error_log(
        'OBRS BOOKING ERROR: ' .
        $e->getMessage()
    );


    // During testing show exact reason
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' =>
            $e->getMessage()
    ]);
}


$conn->close();

?>
<?php

// ============================================================
// ONLINE BUS RESERVATION SYSTEM
// GET SEAT MAP
// ============================================================

header('Content-Type: application/json');

require_once 'config/db_connect.php';


// ============================================================
// ONLY GET REQUEST ALLOWED
// ============================================================

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {

    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);

    exit;
}


// ============================================================
// GET SCHEDULE ID
// ============================================================

$schedule_id = isset($_GET['schedule_id'])
    ? (int) $_GET['schedule_id']
    : 0;


if ($schedule_id <= 0) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid schedule.'
    ]);

    exit;
}


// ============================================================
// GET BUS + SCHEDULE INFORMATION
// ============================================================

$stmt = $conn->prepare("
    SELECT
        schedule.id AS schedule_id,
        schedule.available_seats,
        bus.id AS bus_id,
        bus.total_seats,
        bus.bus_name,
        bus.bus_number
    FROM schedule

    INNER JOIN bus
        ON schedule.bus_id = bus.id

    WHERE schedule.id = ?

    LIMIT 1
");


if (!$stmt) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Database error.'
    ]);

    exit;
}


$stmt->bind_param(
    "i",
    $schedule_id
);

$stmt->execute();

$result = $stmt->get_result();


if ($result->num_rows === 0) {

    $stmt->close();

    echo json_encode([
        'status' => 'error',
        'message' => 'Schedule not found.'
    ]);

    exit;
}


$schedule = $result->fetch_assoc();

$stmt->close();


$total_seats = (int) $schedule['total_seats'];
$available_seats = (int) $schedule['available_seats'];


// ============================================================
// GET ACTUAL MAPPED BOOKED SEATS
// ============================================================

$stmt = $conn->prepare("
    SELECT seat_number
    FROM reservation_seats
    WHERE schedule_id = ?
    ORDER BY seat_number ASC
");


if (!$stmt) {

    echo json_encode([
        'status' => 'error',
        'message' => 'Unable to load booked seats.'
    ]);

    exit;
}


$stmt->bind_param(
    "i",
    $schedule_id
);

$stmt->execute();

$result = $stmt->get_result();


$mapped_booked_seats = [];

while ($row = $result->fetch_assoc()) {

    $mapped_booked_seats[] =
        (int) $row['seat_number'];
}


$stmt->close();


// ============================================================
// LEGACY BOOKINGS
// ============================================================
//
// Old bookings in your database contain only the number of
// seats, not their actual seat numbers.
//
// Example:
// schedule total = 40
// available = 36
//
// That means 4 seats were already booked.
//
// We cannot know which physical seats were used.
//
// Therefore we temporarily mark unused seat numbers as
// legacy booked seats so that the old bookings remain respected.
// ============================================================

$mapped_count = count($mapped_booked_seats);

$legacy_booked_count =
    $total_seats
    - $available_seats
    - $mapped_count;


if ($legacy_booked_count < 0) {

    $legacy_booked_count = 0;
}


// ============================================================
// BUILD BOOKED SEAT LIST
// ============================================================

$booked_seats = $mapped_booked_seats;


// ============================================================
// ADD LEGACY BOOKED SEATS
// ============================================================

if ($legacy_booked_count > 0) {

    for ($seat = 1; $seat <= $total_seats; $seat++) {

        if (
            in_array(
                $seat,
                $booked_seats,
                true
            )
        ) {
            continue;
        }

        $booked_seats[] = $seat;

        if (
            count($booked_seats)
            >= ($mapped_count + $legacy_booked_count)
        ) {
            break;
        }
    }
}


// ============================================================
// SORT BOOKED SEATS
// ============================================================

sort($booked_seats);


// ============================================================
// BUILD COMPLETE SEAT MAP
// ============================================================

$seats = [];

for ($seat = 1; $seat <= $total_seats; $seat++) {

    $is_booked = in_array(
        $seat,
        $booked_seats,
        true
    );

    $seats[] = [
        'seat_number' => $seat,
        'status' => $is_booked
            ? 'booked'
            : 'available'
    ];
}


// ============================================================
// RESPONSE
// ============================================================

echo json_encode([

    'status' => 'success',

    'schedule_id' => $schedule_id,

    'bus_name' =>
        $schedule['bus_name'],

    'bus_number' =>
        $schedule['bus_number'],

    'total_seats' =>
        $total_seats,

    'available_seats' =>
        $available_seats,

    'booked_seats' =>
        $booked_seats,

    'seats' =>
        $seats

]);

?>
<?php
/**
 * get_booked_seats.php
 * AJAX endpoint — returns JSON array of already-booked seat IDs
 * for a given movie + screen + date + time combination.
 *
 * Called by: book_ticket.php (fetch API)
 * Returns:   { "booked": ["A1","B3","C5", ...] }
 */

session_start();

// Only allow logged-in users
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

include '../config.php';

header('Content-Type: application/json');

// ── Validate inputs ──────────────────────────────────────────────
$movie_id = isset($_GET['movie_id']) ? (int)$_GET['movie_id']        : 0;
$screen   = isset($_GET['screen'])   ? trim($_GET['screen'])          : '';
$date     = isset($_GET['date'])     ? trim($_GET['date'])            : '';
$time     = isset($_GET['time'])     ? trim($_GET['time'])            : '';

if (!$movie_id || !$screen || !$date || !$time) {
    echo json_encode(['booked' => []]);
    exit();
}

// Validate date format  (YYYY-MM-DD)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    echo json_encode(['booked' => []]);
    exit();
}

// ── Query bookings table ─────────────────────────────────────────
// seat_numbers column stores comma-separated seat IDs e.g. "A1,B3,C5"
// We only look at confirmed / paid bookings (payment_status = 'completed')
// so that abandoned sessions don't block seats.
$stmt = $conn->prepare(
    "SELECT seat_numbers
     FROM   bookings
     WHERE  movie_id       = ?
       AND  screen         = ?
       AND  show_date      = ?
       AND  show_time      = ?
       AND  payment_status = 'completed'"
);
$stmt->bind_param("isss", $movie_id, $screen, $date, $time);
$stmt->execute();
$result = $stmt->get_result();

// ── Flatten all seat_numbers into a single array ─────────────────
$booked = [];
while ($row = $result->fetch_assoc()) {
    $seats = array_map('trim', explode(',', $row['seat_numbers']));
    foreach ($seats as $seat) {
        if ($seat !== '') {
            $booked[] = $seat;
        }
    }
}

$stmt->close();

// ── Return unique, sorted list ───────────────────────────────────
$booked = array_values(array_unique($booked));

echo json_encode([
    'booked'     => $booked,
    'movie_id'   => $movie_id,
    'screen'     => $screen,
    'date'       => $date,
    'time'       => $time,
    'count'      => count($booked),
]);

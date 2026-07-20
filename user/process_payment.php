<?php
/**
 * process_payment.php
 * Handles the booking form submission from book_ticket.php.
 * Demo payment flow — no real gateway needed.
 * On success → redirects back to book_ticket.php with ?success=1&booking_id=X&seats=...
 * On failure → redirects back with ?error=1&msg=...
 */

session_start();

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'user') {
    header('Location: ../auth/login.php');
    exit();
}

include '../config.php';

// ── Only accept POST ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.php');
    exit();
}

// ── Collect & Sanitise Inputs ─────────────────────────────────────
$user_id     = (int)$_SESSION['user_id'];
$movie_id    = isset($_POST['movie_id'])     ? (int)$_POST['movie_id']                          : 0;
$name        = isset($_POST['name'])         ? trim(htmlspecialchars($_POST['name']))            : '';
$phone       = isset($_POST['phone'])        ? preg_replace('/\D/', '', $_POST['phone'])         : '';
$email       = isset($_POST['email'])        ? trim(filter_var($_POST['email'], FILTER_SANITIZE_EMAIL)) : '';
$screen      = isset($_POST['screen'])       ? trim($_POST['screen'])                            : '';
$show_date   = isset($_POST['show_date'])    ? trim($_POST['show_date'])                         : '';
$show_time   = isset($_POST['show_time'])    ? trim($_POST['show_time'])                         : '';
$seat_numbers = isset($_POST['seat_numbers']) ? trim($_POST['seat_numbers'])                     : '';
$seats       = isset($_POST['seats'])        ? (int)$_POST['seats']                             : 0;
$amount      = isset($_POST['amount'])       ? (int)$_POST['amount']                            : 0;

// ── Server-side Validation ────────────────────────────────────────
$errors = [];

if (!$movie_id)                              $errors[] = 'Invalid movie.';
if (empty($name))                            $errors[] = 'Name is required.';
if (strlen($phone) < 10)                     $errors[] = 'Valid phone number required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email required.';
if (empty($screen))                          $errors[] = 'Screen selection required.';
if (empty($show_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $show_date))
                                             $errors[] = 'Valid show date required.';
if (empty($show_time))                       $errors[] = 'Show time required.';
if (empty($seat_numbers) || $seats < 1)      $errors[] = 'Please select at least one seat.';
if ($seats > 10)                             $errors[] = 'Cannot book more than 10 seats at once.';

// Re-verify amount server-side (don't trust client-sent amount)
$base_price   = 299;
$gst_rate     = 0.18;
$expected_amt = ($seats * $base_price) + (int)round(($seats * $base_price) * $gst_rate);

if ($amount !== $expected_amt) {
    $amount = $expected_amt; // silently correct tampered amount
}

if (!empty($errors)) {
    $msg = urlencode(implode(' ', $errors));
    header("Location: book_ticket.php?movie_id={$movie_id}&error=1&msg={$msg}");
    exit();
}

// ── Check for duplicate / race-condition bookings ─────────────────
// Re-query booked seats to prevent double-booking
$stmt = $conn->prepare(
    "SELECT seat_numbers FROM bookings
     WHERE  movie_id = ? AND screen = ? AND show_date = ? AND show_time = ?
       AND  payment_status = 'completed'"
);
$stmt->bind_param("isss", $movie_id, $screen, $show_date, $show_time);
$stmt->execute();
$res = $stmt->get_result();
$stmt->close();

$already_booked = [];
while ($row = $res->fetch_assoc()) {
    $s = array_map('trim', explode(',', $row['seat_numbers']));
    $already_booked = array_merge($already_booked, $s);
}
$already_booked = array_unique($already_booked);

$requested_seats = array_map('trim', explode(',', $seat_numbers));
$conflicts       = array_intersect($requested_seats, $already_booked);

if (!empty($conflicts)) {
    $conflicted = implode(', ', $conflicts);
    $msg = urlencode("Seats {$conflicted} were just booked by someone else. Please choose different seats.");
    header("Location: book_ticket.php?movie_id={$movie_id}&error=1&msg={$msg}");
    exit();
}

// ── Demo Payment Processing ───────────────────────────────────────
// In a real app you would call Stripe / Razorpay / PayPal here.
// For now we simulate a successful payment.
$payment_intent_id = 'DEMO_' . strtoupper(bin2hex(random_bytes(8)));
$payment_status    = 'completed';

// ── Save Booking to Database ──────────────────────────────────────
$stmt = $conn->prepare(
    "INSERT INTO bookings
        (user_id, movie_id, name, screen, seat_numbers, seats,
         show_date, show_time, amount, payment_intent_id, payment_status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param(
    "iissssissss",
    $user_id,
    $movie_id,
    $name,
    $screen,
    $seat_numbers,
    $seats,
    $show_date,
    $show_time,
    $amount,
    $payment_intent_id,
    $payment_status
);

if ($stmt->execute()) {
    $booking_id   = $conn->insert_id;
    $seats_encoded = urlencode($seat_numbers);
    header("Location: book_ticket.php?movie_id={$movie_id}&success=1&booking_id={$booking_id}&seats={$seats_encoded}");
    exit();
} else {
    $msg = urlencode('Database error. Please try again.');
    header("Location: book_ticket.php?movie_id={$movie_id}&error=1&msg={$msg}");
    exit();
}

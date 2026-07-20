<?php
// Copy this file to config.php and fill in your values

$host = 'localhost';
$user = 'root';       // your MySQL username
$pass = '';           // your MySQL password
$db   = 'movie_system';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stripe keys (optional - for real payment integration)
// Get free test keys from: https://dashboard.stripe.com/test/apikeys
define('STRIPE_PUBLIC_KEY', 'pk_test_YOUR_KEY_HERE');
define('STRIPE_SECRET_KEY', 'sk_test_YOUR_KEY_HERE');
define('CURRENCY', 'INR');
?>

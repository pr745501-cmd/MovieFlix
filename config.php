<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db = 'movie_system';

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Stripe Configuration - TEST KEYS
// Get free keys from: https://stripe.com/docs/development/quickstart
define('STRIPE_PUBLIC_KEY', 'pk_test_51NvXXXXXXXXXXXXXXXXXXXX'); // Replace with your Stripe Public Key
define('STRIPE_SECRET_KEY', 'sk_test_51NvXXXXXXXXXXXXXXXXXXXX'); // Replace with your Stripe Secret Key
define('CURRENCY', 'INR');
?>
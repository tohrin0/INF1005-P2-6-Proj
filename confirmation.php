<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? null;

if ($booking_id) {
    $booking = getBookingDetails($booking_id, $user_id);
} else {
    header('Location: index.php');
    exit();
}

// Remove this duplicate function - it's already in functions.php
// function getBookingDetails($booking_id, $user_id) {
//     global $db;
//     $stmt = $db->prepare("SELECT * FROM bookings WHERE id = ? AND user_id = ?");
//     $stmt->execute([$booking_id, $user_id]);
//     return $stmt->fetch(PDO::FETCH_ASSOC);
// }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Booking Confirmation</title>
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>
    
    <div class="confirmation">
        <h1>Booking Confirmation</h1>
        <?php if ($booking): ?>
            <p>Thank you for your booking, <?php echo htmlspecialchars($booking['customer_name']); ?>!</p>
            <p>Your booking ID is: <strong><?php echo htmlspecialchars($booking['id']); ?></strong></p>
            <p>Flight: <?php echo htmlspecialchars($booking['flight_number']); ?></p>
            <p>Date: <?php echo htmlspecialchars($booking['flight_date']); ?></p>
            <p>Departure: <?php echo htmlspecialchars($booking['departure']); ?></p>
            <p>Arrival: <?php echo htmlspecialchars($booking['arrival']); ?></p>
            <p>Status: <?php echo htmlspecialchars($booking['status']); ?></p>
        <?php else: ?>
            <p>Booking not found.</p>
        <?php endif; ?>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
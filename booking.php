<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$flights = [];
$booking_success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $flight_id = $_POST['flight_id'];
    $passenger_details = $_POST['passenger_details'];

    $booking = new Booking();
    $booking_success = $booking->createBooking($user_id, $flight_id, $passenger_details);

    if ($booking_success) {
        header('Location: confirmation.php');
        exit;
    }
}

$apiClient = new ApiClient();
$flights = $apiClient->getAvailableFlights();

include 'templates/header.php';
?>

<div class="container">
    <h1>Flight Booking</h1>
    <?php if ($booking_success): ?>
        <div class="alert alert-success">Booking successful!</div>
    <?php endif; ?>
    
    <form action="booking.php" method="POST">
        <label for="flight">Select Flight:</label>
        <select name="flight_id" id="flight" required>
            <?php foreach ($flights as $flight): ?>
                <option value="<?= $flight['id'] ?>"><?= $flight['flight_number'] ?> - <?= $flight['destination'] ?> - <?= $flight['departure_time'] ?></option>
            <?php endforeach; ?>
        </select>

        <h2>Passenger Details</h2>
        <div id="passenger-details">
            <label for="name">Name:</label>
            <input type="text" name="passenger_details[name]" required>
            <label for="email">Email:</label>
            <input type="email" name="passenger_details[email]" required>
            <label for="phone">Phone:</label>
            <input type="tel" name="passenger_details[phone]" required>
        </div>

        <button type="submit">Book Flight</button>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
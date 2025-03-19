<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Passenger.php';
require_once 'classes/Booking.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get booking ID from URL
$bookingId = $_GET['booking_id'] ?? null;
if (!$bookingId) {
    header('Location: my-bookings.php');
    exit();
}

// Initialize classes
$bookingObj = new Booking();
$passengerObj = new Passenger();

// Verify booking belongs to user
$booking = $bookingObj->getBookingById($bookingId, $userId);
if (!$booking) {
    header('Location: my-bookings.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_passenger'])) {
    $passengerData = [
        'title' => $_POST['title'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'date_of_birth' => $_POST['date_of_birth'],
        'nationality' => $_POST['nationality'],
        'passport_number' => $_POST['passport_number'],
        'passport_expiry' => $_POST['passport_expiry'],
        'special_requirements' => $_POST['special_requirements'] ?? null
    ];

    if ($passengerObj->addPassenger($bookingId, $passengerData)) {
        $success = "Passenger details saved successfully.";
    } else {
        $error = "Failed to save passenger details.";
    }
}

// Get existing passengers
$passengers = $passengerObj->getPassengersByBooking($bookingId);
$remainingPassengers = $booking['passengers'] - count($passengers);

include 'templates/header.php';
?>

<div class="container">
    <h1>Manage Passengers</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <div class="booking-info">
        <h2>Booking Details</h2>
        <p>Booking Reference: <?php echo htmlspecialchars($bookingId); ?></p>
        <p>Total Passengers: <?php echo htmlspecialchars($booking['passengers']); ?></p>
        <p>Remaining Passengers to Add: <?php echo htmlspecialchars($remainingPassengers); ?></p>
    </div>

    <?php if ($remainingPassengers > 0): ?>
    <div class="passenger-form">
        <h2>Add Passenger Details</h2>
        <form method="POST" action="">
            <div class="form-group">
                <label for="title">Title</label>
                <select name="title" id="title" required>
                    <option value="Mr">Mr</option>
                    <option value="Mrs">Mrs</option>
                    <option value="Ms">Ms</option>
                    <option value="Dr">Dr</option>
                </select>
            </div>

            <div class="form-group">
                <label for="first_name">First Name</label>
                <input type="text" id="first_name" name="first_name" required>
            </div>

            <div class="form-group">
                <label for="last_name">Last Name</label>
                <input type="text" id="last_name" name="last_name" required>
            </div>

            <div class="form-group">
                <label for="date_of_birth">Date of Birth</label>
                <input type="date" id="date_of_birth" name="date_of_birth" required>
            </div>

            <div class="form-group">
                <label for="nationality">Nationality</label>
                <input type="text" id="nationality" name="nationality" required>
            </div>

            <div class="form-group">
                <label for="passport_number">Passport Number</label>
                <input type="text" id="passport_number" name="passport_number" required>
            </div>

            <div class="form-group">
                <label for="passport_expiry">Passport Expiry Date</label>
                <input type="date" id="passport_expiry" name="passport_expiry" required>
            </div>

            <div class="form-group">
                <label for="special_requirements">Special Requirements</label>
                <textarea id="special_requirements" name="special_requirements"></textarea>
            </div>

            <button type="submit" name="save_passenger" class="btn-primary">Save Passenger</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if (!empty($passengers)): ?>
    <div class="existing-passengers">
        <h2>Added Passengers</h2>
        <div class="passengers-grid">
            <?php foreach ($passengers as $passenger): ?>
            <div class="passenger-card">
                <h3><?php echo htmlspecialchars($passenger['title'] . ' ' . $passenger['first_name'] . ' ' . $passenger['last_name']); ?></h3>
                <p><strong>Passport:</strong> <?php echo htmlspecialchars($passenger['passport_number']); ?></p>
                <p><strong>Nationality:</strong> <?php echo htmlspecialchars($passenger['nationality']); ?></p>
                <p><strong>Date of Birth:</strong> <?php echo htmlspecialchars($passenger['date_of_birth']); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
    .container {
        max-width: 800px;
        margin: 0 auto;
        padding: 20px;
    }

    .booking-info {
        background-color: #f8f9fa;
        padding: 20px;
        border-radius: 8px;
        margin-bottom: 30px;
    }

    .passenger-form {
        background-color: #fff;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }

    .form-group {
        margin-bottom: 20px;
    }

    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }

    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }

    .passengers-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }

    .passenger-card {
        background-color: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .btn-primary {
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
    }

    .btn-primary:hover {
        background-color: #45a049;
    }
</style>

<?php include 'templates/footer.php'; ?>
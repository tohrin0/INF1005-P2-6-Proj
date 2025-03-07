<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Booking.php';

session_start();

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$bookings = Booking::getAllBookings();

include 'includes/header.php';
?>

<div class="container">
    <h1>Manage Bookings</h1>
    <table class="table">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>User</th>
                <th>Flight</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($bookings as $booking): ?>
                <tr>
                    <td><?php echo htmlspecialchars($booking['id']); ?></td>
                    <td><?php echo htmlspecialchars($booking['user_id']); ?></td>
                    <td><?php echo htmlspecialchars($booking['flight_id']); ?></td>
                    <td><?php echo htmlspecialchars($booking['date']); ?></td>
                    <td><?php echo htmlspecialchars($booking['status']); ?></td>
                    <td>
                        <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-warning">Edit</a>
                        <a href="delete-booking.php?id=<?php echo $booking['id']; ?>" class="btn btn-danger">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
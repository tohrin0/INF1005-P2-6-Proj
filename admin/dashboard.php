<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if the user is logged in and has admin privileges
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Fetch key metrics for the dashboard
$totalUsers = getTotalUsers();
$totalBookings = getTotalBookings();
$totalFlights = getTotalFlights();

include 'includes/header.php';
?>

<div class="dashboard">
    <h1>Admin Dashboard</h1>
    <div class="metrics">
        <div class="metric">
            <h2>Total Users</h2>
            <p><?php echo $totalUsers; ?></p>
        </div>
        <div class="metric">
            <h2>Total Bookings</h2>
            <p><?php echo $totalBookings; ?></p>
        </div>
        <div class="metric">
            <h2>Total Flights</h2>
            <p><?php echo $totalFlights; ?></p>
        </div>
    </div>
    <div class="links">
        <h3>Quick Links</h3>
        <ul>
            <li><a href="bookings.php">Manage Bookings</a></li>
            <li><a href="users.php">Manage Users</a></li>
            <li><a href="flights.php">Manage Flights</a></li>
            <li><a href="settings.php">Site Settings</a></li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<?php
// Clear any potential PHP cache
clearstatcache();

require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../inc/api.php';

// Add this line to verify if functions are loaded
if (!function_exists('addFlight')) {
    die("Functions are not properly loaded. Check your functions.php file.");
}

session_start();
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$flights = getAllFlights(); // Function to retrieve all flights from the database

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_flight'])) {
        // Code to add a new flight
        $flightData = [
            'flight_number' => $_POST['flight_number'],
            'departure' => $_POST['departure'],
            'arrival' => $_POST['arrival'],
            'date' => $_POST['date'],
            'time' => $_POST['time'],
            'price' => $_POST['price'],
        ];
        addFlight($flightData); // Function to add flight to the database
        header('Location: flights.php');
        exit();
    }

    if (isset($_POST['delete_flight'])) {
        // Code to delete a flight
        $flightId = $_POST['flight_id'];
        deleteFlight($flightId); // Function to delete flight from the database
        header('Location: flights.php');
        exit();
    }
}

include 'includes/header.php';
?>

<div class="container">
    <h1>Manage Flights</h1>
    <form method="POST" action="">
        <h2>Add Flight</h2>
        <input type="text" name="flight_number" placeholder="Flight Number" required>
        <input type="text" name="departure" placeholder="Departure" required>
        <input type="text" name="arrival" placeholder="Arrival" required>
        <input type="date" name="date" required>
        <input type="time" name="time" required>
        <input type="number" name="price" placeholder="Price" required>
        <button type="submit" name="add_flight">Add Flight</button>
    </form>

    <h2>Existing Flights</h2>
    <table>
        <thead>
            <tr>
                <th>Flight Number</th>
                <th>Departure</th>
                <th>Arrival</th>
                <th>Date</th>
                <th>Time</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($flights as $flight): ?>
                <tr>
                    <td><?php echo htmlspecialchars($flight['flight_number']); ?></td>
                    <td><?php echo htmlspecialchars($flight['departure']); ?></td>
                    <td><?php echo htmlspecialchars($flight['arrival']); ?></td>
                    <td><?php echo htmlspecialchars($flight['date']); ?></td>
                    <td><?php echo htmlspecialchars($flight['time']); ?></td>
                    <td><?php echo htmlspecialchars($flight['price']); ?></td>
                    <td>
                        <form method="POST" action="">
                            <input type="hidden" name="flight_id" value="<?php echo $flight['id']; ?>">
                            <button type="submit" name="delete_flight">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'includes/footer.php'; ?>
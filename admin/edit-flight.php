<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Flight.php';

// Check if the user is logged in and has admin privileges
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get flight ID from URL parameter
$flightId = $_GET['id'] ?? null;

if (!$flightId) {
    header('Location: flights.php');
    exit();
}

$error = '';
$success = '';

// Get flight details
try {
    $flightObj = new Flight(null, null, null);
    $flight = $flightObj->findById($flightId);

    if (!$flight) {
        $_SESSION['admin_error'] = "Flight not found.";
        header('Location: flights.php');
        exit();
    }

    // Handle form submission for updating flight
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_flight'])) {
        // Gather flight data
        $flightData = [
            'flight_number' => trim($_POST['flight_number']),
            'airline' => trim($_POST['airline']),
            'departure' => trim($_POST['departure']),
            'arrival' => trim($_POST['arrival']),
            'date' => trim($_POST['date']),
            'time' => trim($_POST['time']),
            'duration' => intval($_POST['duration']),
            'price' => floatval($_POST['price']),
            'available_seats' => intval($_POST['available_seats']),
            'status' => trim($_POST['status'])
        ];

        // Validate the data
        $validationErrors = validateFlightData($flightData);

        if (empty($validationErrors)) {
            // Update the flight
            $result = $flightObj->update($flightId, $flightData);

            if ($result) {
                $success = "Flight updated successfully.";

                // Refresh flight data
                $flight = $flightObj->findById($flightId);
            } else {
                $error = "Failed to update flight.";
            }
        } else {
            $error = "Validation errors: " . implode(", ", $validationErrors);
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

include 'includes/header.php';

// Helper function to validate flight data
function validateFlightData($data)
{
    $errors = [];

    if (empty($data['flight_number'])) {
        $errors[] = "Flight number is required";
    }

    if (empty($data['departure'])) {
        $errors[] = "Departure location is required";
    }

    if (empty($data['arrival'])) {
        $errors[] = "Arrival location is required";
    }

    if (empty($data['date'])) {
        $errors[] = "Date is required";
    } else if (!preg_match("/^\d{4}-\d{2}-\d{2}$/", $data['date'])) {
        $errors[] = "Date must be in YYYY-MM-DD format";
    }

    if (empty($data['time'])) {
        $errors[] = "Time is required";
    }

    if ($data['price'] <= 0) {
        $errors[] = "Price must be greater than zero";
    }

    if ($data['available_seats'] < 0) {
        $errors[] = "Available seats cannot be negative";
    }

    return $errors;
}
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Edit Flight</h1>
            <p class="text-gray-600">Update flight details and status</p>
        </div>
        <a href="flights.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors flex items-center">
            &larr; Back to Flights
        </a>
    </div>

    <?php if ($error): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <form method="POST" action="" class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="flight_number" class="block text-sm font-medium text-gray-700 mb-1">Flight Number</label>
                        <input type="text" id="flight_number" name="flight_number" value="<?= htmlspecialchars($flight['flight_number'] ?? '') ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="airline" class="block text-sm font-medium text-gray-700 mb-1">Airline</label>
                        <input type="text" id="airline" name="airline" value="<?= htmlspecialchars($flight['airline'] ?? '') ?>"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="departure" class="block text-sm font-medium text-gray-700 mb-1">Departure</label>
                        <input type="text" id="departure" name="departure" value="<?= htmlspecialchars($flight['departure'] ?? '') ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="arrival" class="block text-sm font-medium text-gray-700 mb-1">Arrival</label>
                        <input type="text" id="arrival" name="arrival" value="<?= htmlspecialchars($flight['arrival'] ?? '') ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                        <input type="date" id="date" name="date" value="<?= htmlspecialchars($flight['date'] ?? '') ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="time" class="block text-sm font-medium text-gray-700 mb-1">Time</label>
                        <input type="time" id="time" name="time" value="<?= htmlspecialchars($flight['time'] ?? '') ?>" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="duration" class="block text-sm font-medium text-gray-700 mb-1">Duration (minutes)</label>
                        <input type="number" id="duration" name="duration" value="<?= htmlspecialchars($flight['duration'] ?? '0') ?>" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-1">Price ($)</label>
                        <input type="number" id="price" name="price" value="<?= htmlspecialchars($flight['price'] ?? '0.00') ?>" min="0" step="0.01" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="available_seats" class="block text-sm font-medium text-gray-700 mb-1">Available Seats</label>
                        <input type="number" id="available_seats" name="available_seats" value="<?= htmlspecialchars($flight['available_seats'] ?? '100') ?>" min="0"
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="scheduled" <?= ($flight['status'] ?? '') === 'scheduled' ? 'selected' : '' ?>>Scheduled</option>
                            <option value="delayed" <?= ($flight['status'] ?? '') === 'delayed' ? 'selected' : '' ?>>Delayed</option>
                            <option value="canceled" <?= ($flight['status'] ?? '') === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                            <option value="completed" <?= ($flight['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="submit" name="update_flight" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                        Update Flight
                    </button>
                </div>
            </form>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200 h-fit">
            <h2 class="text-lg font-semibold text-gray-800 mb-4">Flight Overview</h2>

            <div class="space-y-4">
                <div>
                    <h3 class="text-sm font-medium text-gray-500">Flight Number</h3>
                    <p class="font-semibold"><?= htmlspecialchars($flight['flight_number'] ?? 'N/A') ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Route</h3>
                    <div class="flex items-center mt-1">
                        <span class="font-semibold"><?= htmlspecialchars($flight['departure'] ?? 'N/A') ?></span>
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mx-2 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3" />
                        </svg>
                        <span class="font-semibold"><?= htmlspecialchars($flight['arrival'] ?? 'N/A') ?></span>
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Date & Time</h3>
                    <p class="font-semibold">
                        <?= isset($flight['date']) ? date('F j, Y', strtotime($flight['date'])) : 'N/A' ?> at
                        <?= isset($flight['time']) ? date('H:i', strtotime($flight['time'])) : 'N/A' ?>
                    </p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Status</h3>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                        <?php if (($flight['status'] ?? '') === 'scheduled'): ?>
                            bg-green-100 text-green-800
                        <?php elseif (($flight['status'] ?? '') === 'delayed'): ?>
                            bg-yellow-100 text-yellow-800
                        <?php elseif (($flight['status'] ?? '') === 'canceled'): ?>
                            bg-red-100 text-red-800
                        <?php elseif (($flight['status'] ?? '') === 'completed'): ?>
                            bg-blue-100 text-blue-800
                        <?php else: ?>
                            bg-gray-100 text-gray-800
                        <?php endif; ?>">
                        <?= ucfirst(htmlspecialchars($flight['status'] ?? 'N/A')) ?>
                    </span>
                </div>

                <div class="pt-3 border-t border-gray-200">
                    <h3 class="text-sm font-medium text-gray-500">Price</h3>
                    <p class="text-lg font-bold text-blue-600">$<?= number_format(($flight['price'] ?? 0), 2) ?></p>
                </div>

                <div>
                    <h3 class="text-sm font-medium text-gray-500">Available Seats</h3>
                    <p class="font-semibold"><?= htmlspecialchars($flight['available_seats'] ?? '0') ?> seats</p>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-gray-200">
                <h3 class="text-sm font-medium text-gray-500 mb-2">Quick Actions</h3>
                <div class="space-y-2">
                    <!-- Delete Flight Form -->
                    <form method="POST" action="flights.php" onsubmit="return confirm('Are you sure you want to delete this flight? This action cannot be undone.');">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="flight_id" value="<?= htmlspecialchars($flightId) ?>">
                        <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            Delete Flight
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings Associated with this Flight -->
    <div class="mt-8 bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Associated Bookings</h2>

        <?php
        // Get bookings for this flight
        $bookings = fetchAll("SELECT * FROM bookings WHERE flight_id = ? ORDER BY booking_date DESC", [$flightId]);

        if (!empty($bookings)):
        ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Booking ID
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Customer
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Date
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Status
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Total Price
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?= htmlspecialchars($booking['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($booking['customer_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($booking['booking_date'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php if ($booking['status'] === 'confirmed'): ?>
                                            bg-green-100 text-green-800
                                        <?php elseif ($booking['status'] === 'pending'): ?>
                                            bg-yellow-100 text-yellow-800
                                        <?php elseif ($booking['status'] === 'canceled'): ?>
                                            bg-red-100 text-red-800
                                        <?php else: ?>
                                            bg-gray-100 text-gray-800
                                        <?php endif; ?>">
                                        <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    $<?= number_format($booking['total_price'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <a href="view-booking.php?id=<?= $booking['id'] ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                    <a href="edit-booking.php?id=<?= $booking['id'] ?>" class="text-indigo-600 hover:text-indigo-900">Edit</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-4 text-gray-500 italic">
                No bookings have been made for this flight yet.
            </div>
        <?php endif; ?>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
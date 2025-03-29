<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Flight.php';
require_once '../inc/session.php';

verifyAdminSession();

// Get flight ID from URL parameter
$flightId = $_GET['id'] ?? null;

if (!$flightId) {
    header('Location: flights.php');
    exit();
}

$error = '';
$success = '';

try {
    // Get flight data using direct query for reliability
    $stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
    $stmt->execute([$flightId]);
    $flight = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$flight) {
        $_SESSION['admin_error'] = "Flight not found.";
        header('Location: flights.php');
        exit();
    }
    
    // Initialize Flight class for operations
    $flightObj = new Flight();
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_flight'])) {
            // Handle flight deletion
            if ($flightObj->delete($flightId)) {
                $_SESSION['admin_message'] = "Flight deleted successfully.";
                header('Location: flights.php');
                exit();
            } else {
                $error = "Failed to delete flight. It may have associated bookings.";
            }
        } else {
            // Handle flight update
            $flightData = [
                'flight_number' => $_POST['flight_number'],
                'airline' => $_POST['airline'],
                'departure' => $_POST['departure'],
                'arrival' => $_POST['arrival'],
                'date' => $_POST['date'],
                'time' => $_POST['time'],
                'duration' => $_POST['duration'],
                'price' => $_POST['price'],
                'available_seats' => $_POST['available_seats'],
                'status' => $_POST['status'],
                'departure_gate' => $_POST['departure_gate'] ?? null,
                'arrival_gate' => $_POST['arrival_gate'] ?? null,
                'departure_terminal' => $_POST['departure_terminal'] ?? null,
                'arrival_terminal' => $_POST['arrival_terminal'] ?? null,
                'flight_api' => $_POST['flight_api'] ?? null
            ];
            
            // Call update method
            if ($flightObj->update($flightId, $flightData)) {
                $success = "Flight updated successfully.";
                
                // Refresh flight data
                $stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
                $stmt->execute([$flightId]);
                $flight = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Failed to update flight.";
            }
        }
    }
} catch (Exception $e) {
    $error = "Error: " . $e->getMessage();
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Edit Flight</h1>
            <p class="text-gray-600">Update flight information</p>
        </div>
        <div>
            <a href="flights.php" class="admin-btn admin-btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Flights
            </a>
        </div>
    </div>

    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2">
            <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
                <h2 class="text-xl font-semibold text-gray-800 mb-4">Flight Details</h2>
                
                <form method="POST" action="">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="flight_number" class="block text-sm font-medium text-gray-700 mb-1">Flight Number</label>
                            <input type="text" id="flight_number" name="flight_number" value="<?= htmlspecialchars($flight['flight_number'] ?? '') ?>" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="airline" class="block text-sm font-medium text-gray-700 mb-1">Airline</label>
                            <input type="text" id="airline" name="airline" value="<?= htmlspecialchars($flight['airline'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500" required>
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
                            <input type="number" id="price" name="price" value="<?= htmlspecialchars($flight['price'] ?? '0') ?>" step="0.01" min="0" required
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
                                <option value="cancelled" <?= ($flight['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                <option value="completed" <?= ($flight['status'] ?? '') === 'completed' ? 'selected' : '' ?>>Completed</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="flight_api" class="block text-sm font-medium text-gray-700 mb-1">API Reference ID (Optional)</label>
                            <input type="text" id="flight_api" name="flight_api" value="<?= htmlspecialchars($flight['flight_api'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <h3 class="text-lg font-medium text-gray-800 mb-3">Additional Details</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="departure_terminal" class="block text-sm font-medium text-gray-700 mb-1">Departure Terminal</label>
                            <input type="text" id="departure_terminal" name="departure_terminal" value="<?= htmlspecialchars($flight['departure_terminal'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="departure_gate" class="block text-sm font-medium text-gray-700 mb-1">Departure Gate</label>
                            <input type="text" id="departure_gate" name="departure_gate" value="<?= htmlspecialchars($flight['departure_gate'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="arrival_terminal" class="block text-sm font-medium text-gray-700 mb-1">Arrival Terminal</label>
                            <input type="text" id="arrival_terminal" name="arrival_terminal" value="<?= htmlspecialchars($flight['arrival_terminal'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>

                        <div>
                            <label for="arrival_gate" class="block text-sm font-medium text-gray-700 mb-1">Arrival Gate</label>
                            <input type="text" id="arrival_gate" name="arrival_gate" value="<?= htmlspecialchars($flight['arrival_gate'] ?? '') ?>"
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                        </div>
                    </div>

                    <div class="flex justify-between">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Update Flight
                        </button>
                        <button type="submit" name="delete_flight" onclick="return confirm('Are you sure you want to delete this flight? This action cannot be undone and will also remove any associated bookings.')" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center">
                            <i class="fas fa-trash mr-1"></i>
                            Delete Flight
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4">Flight Summary</h2>
            <div class="space-y-4">
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Flight Number:</span>
                    <span class="font-semibold"><?= htmlspecialchars($flight['flight_number'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Route:</span>
                    <span><?= htmlspecialchars($flight['departure'] ?? 'N/A') ?> → <?= htmlspecialchars($flight['arrival'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Schedule:</span>
                    <span><?= isset($flight['date']) ? date('M j, Y', strtotime($flight['date'])) : 'N/A' ?> at <?= htmlspecialchars($flight['time'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Airline:</span>
                    <span><?= htmlspecialchars($flight['airline'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Status:</span>
                    <span class="px-2 py-1 text-xs rounded-full
                        <?php if (($flight['status'] ?? '') === 'scheduled'): ?>
                            bg-green-100 text-green-800
                        <?php elseif (($flight['status'] ?? '') === 'delayed'): ?>
                            bg-yellow-100 text-yellow-800
                        <?php elseif (($flight['status'] ?? '') === 'cancelled'): ?>
                            bg-red-100 text-red-800
                        <?php else: ?>
                            bg-gray-100 text-gray-800
                        <?php endif; ?>">
                        <?= ucfirst(htmlspecialchars($flight['status'] ?? 'N/A')) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Bookings Associated with this Flight -->
    <div class="mt-8 bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h2 class="text-lg font-semibold text-gray-800 mb-4">Associated Bookings</h2>

        <?php
        // Get bookings for this flight
        $stmt = $pdo->prepare("SELECT * FROM bookings WHERE flight_id = ? ORDER BY booking_date DESC");
        $stmt->execute([$flightId]);
        $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                        <?php 
                        // Get customer info for each booking
                        foreach ($bookings as $booking): 
                            $userStmt = $pdo->prepare("SELECT username, email FROM users WHERE id = ?");
                            $userStmt->execute([$booking['user_id']]);
                            $user = $userStmt->fetch(PDO::FETCH_ASSOC);
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    #<?= htmlspecialchars($booking['id']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php if ($user): ?>
                                        <?= htmlspecialchars($user['username']) ?><br>
                                        <span class="text-xs text-gray-400"><?= htmlspecialchars($user['email']) ?></span>
                                    <?php else: ?>
                                        Unknown User
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($booking['booking_date'] ?? $booking['created_at'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php if ($booking['status'] === 'confirmed'): ?>
                                            bg-green-100 text-green-800
                                        <?php elseif ($booking['status'] === 'pending'): ?>
                                            bg-yellow-100 text-yellow-800
                                        <?php elseif ($booking['status'] === 'canceled' || $booking['status'] === 'cancelled'): ?>
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
                                    <div class="flex justify-end space-x-2">
                                        <a href="view-booking.php?id=<?= $booking['id'] ?>" class="admin-action-view" title="View Booking">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-booking.php?id=<?= $booking['id'] ?>" class="admin-action-edit" title="Edit Booking">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </div>
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
<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Booking.php';
require_once '../classes/Flight.php';

// Check if the user is logged in and has admin privileges
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get booking ID from URL parameter
$bookingId = $_GET['id'] ?? null;

if (!$bookingId) {
    header('Location: bookings.php');
    exit();
}

$error = '';
$success = '';

// Get booking details
try {
    $bookingObj = new Booking();
    $booking = $bookingObj->getBookingById($bookingId);
    
    if (!$booking) {
        $_SESSION['admin_error'] = "Booking not found.";
        header('Location: bookings.php');
        exit();
    }
    
    // Handle form submission for updating booking
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate the form data
        if (empty($_POST['customer_name']) || empty($_POST['customer_email']) || empty($_POST['customer_phone'])) {
            $error = "All fields are required";
        } else if (!filter_var($_POST['customer_email'], FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email address";
        } else {
            // Update booking details
            $updateData = [
                'customer_name' => $_POST['customer_name'],
                'customer_email' => $_POST['customer_email'],
                'customer_phone' => $_POST['customer_phone'],
                'passengers' => $_POST['passengers'],
                'status' => $_POST['status'],
                'total_price' => $_POST['total_price']
            ];
            
            try {
                global $pdo;
                $stmt = $pdo->prepare("
                    UPDATE bookings 
                    SET customer_name = ?, customer_email = ?, customer_phone = ?, 
                        passengers = ?, status = ?, total_price = ? 
                    WHERE id = ?
                ");
                $stmt->execute([
                    $updateData['customer_name'],
                    $updateData['customer_email'],
                    $updateData['customer_phone'],
                    $updateData['passengers'],
                    $updateData['status'],
                    $updateData['total_price'],
                    $bookingId
                ]);
                
                $success = "Booking updated successfully";
                
                // Get the updated booking data
                $booking = $bookingObj->getBookingById($bookingId);
            } catch (Exception $e) {
                $error = "Error updating booking: " . $e->getMessage();
            }
        }
    }
    
    // Get flight details
    $flightId = $booking['flight_id'];
    $flightObj = new Flight(null, null, null);
    $flight = $flightObj->findById($flightId);
    
} catch (Exception $e) {
    $_SESSION['admin_error'] = "Error retrieving booking details: " . $e->getMessage();
    header('Location: bookings.php');
    exit();
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Edit Booking</h1>
            <p class="text-gray-600">Update booking information</p>
        </div>
        <div class="flex space-x-2">
            <a href="bookings.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors flex items-center">
                &larr; Back to Bookings
            </a>
            <a href="view-booking.php?id=<?= htmlspecialchars($bookingId) ?>" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-eye mr-1"></i> View Full Details
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
    
    <form method="POST" action="" class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Customer Info Section -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Customer Information</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 mb-1">Customer Name</label>
                        <input type="text" id="customer_name" name="customer_name" 
                               value="<?= htmlspecialchars($booking['customer_name']) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="customer_email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="customer_email" name="customer_email" 
                               value="<?= htmlspecialchars($booking['customer_email']) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="text" id="customer_phone" name="customer_phone" 
                               value="<?= htmlspecialchars($booking['customer_phone']) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
            </div>
            
            <!-- Booking Details Section -->
            <div>
                <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Booking Details</h2>
                
                <div class="space-y-4">
                    <div>
                        <label for="booking_id" class="block text-sm font-medium text-gray-700 mb-1">Booking ID</label>
                        <input type="text" id="booking_id" value="<?= htmlspecialchars($booking['id']) ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" disabled>
                    </div>
                    
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select id="status" name="status" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="pending" <?= $booking['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="canceled" <?= $booking['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                            <option value="completed" <?= $booking['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="passengers" class="block text-sm font-medium text-gray-700 mb-1">Number of Passengers</label>
                        <input type="number" id="passengers" name="passengers" 
                               value="<?= htmlspecialchars($booking['passengers']) ?>" min="1" max="10"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="total_price" class="block text-sm font-medium text-gray-700 mb-1">Total Price ($)</label>
                        <input type="number" id="total_price" name="total_price" step="0.01"
                               value="<?= htmlspecialchars($booking['total_price']) ?>" min="0"
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Flight Information (Read-only) -->
        <div class="mt-8">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Flight Information</h2>
            
            <?php if ($flight): ?>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Flight Number</label>
                    <input type="text" value="<?= htmlspecialchars($flight['flight_number']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" disabled>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Route</label>
                    <input type="text" value="<?= htmlspecialchars($flight['departure']) ?> → <?= htmlspecialchars($flight['arrival']) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" disabled>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Date & Time</label>
                    <input type="text" value="<?= date('M j, Y', strtotime($flight['date'])) ?> at <?= date('H:i', strtotime($flight['time'])) ?>" 
                           class="w-full px-3 py-2 border border-gray-300 rounded-md bg-gray-50" disabled>
                </div>
            </div>
            <div class="mt-4">
                <a href="edit-flight.php?id=<?= htmlspecialchars($flight['id']) ?>" class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                    Edit Flight Details →
                </a>
            </div>
            <?php else: ?>
            <div class="py-4 text-gray-500 italic">
                Flight information not available or has been deleted.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Submit Buttons -->
        <div class="mt-8 flex justify-end space-x-3">
            <a href="bookings.php" class="px-6 py-2 border border-gray-300 text-gray-700 rounded-md hover:bg-gray-50 transition-colors">
                Cancel
            </a>
            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                Update Booking
            </button>
        </div>
    </form>
</div>

<?php include 'includes/footer.php'; ?>
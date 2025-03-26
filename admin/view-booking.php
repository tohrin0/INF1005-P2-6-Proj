<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Booking.php';
require_once '../classes/Flight.php';
require_once '../classes/Payment.php';
require_once '../classes/Passenger.php';

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

// Get booking details
try {
    $bookingObj = new Booking();
    $booking = $bookingObj->getBookingById($bookingId);
    
    if (!$booking) {
        $_SESSION['admin_error'] = "Booking not found.";
        header('Location: bookings.php');
        exit();
    }
    
    // Get flight details
    $flightId = $booking['flight_id'];
    $flightObj = new Flight(null, null, null);
    $flight = $flightObj->findById($flightId);
    
    // Get passenger details
    $passengerObj = new Passenger();
    $passengers = $passengerObj->getPassengersByBooking($bookingId);
    
    // Get payment information
    $paymentObj = new Payment(0, 'USD', 'credit_card');
    $payment = $paymentObj->getPaymentByBookingId($bookingId);
    
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
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Booking Details</h1>
            <p class="text-gray-600">View detailed information about this booking</p>
        </div>
        <div class="flex space-x-2">
            <a href="bookings.php" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-md hover:bg-gray-200 transition-colors flex items-center">
                <i class="fas fa-arrow-left mr-1"></i> Back to Bookings
            </a>
            <a href="edit-booking.php?id=<?= htmlspecialchars($bookingId) ?>" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
                <i class="fas fa-edit mr-1"></i> Edit Booking
            </a>
        </div>
    </div>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Booking Summary Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Booking Summary</h2>
            
            <div class="space-y-3">
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Booking ID:</span>
                    <span class="font-bold">#<?= htmlspecialchars($booking['id']) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Date:</span>
                    <span><?= date('F j, Y', strtotime($booking['booking_date'])) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Status:</span>
                    <span class="px-3 py-1 rounded-full text-xs font-medium
                        <?php if ($booking['status'] === 'pending'): ?>
                            bg-yellow-100 text-yellow-800
                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                            bg-green-100 text-green-800
                        <?php elseif ($booking['status'] === 'canceled'): ?>
                            bg-red-100 text-red-800
                        <?php else: ?>
                            bg-gray-100 text-gray-800
                        <?php endif; ?>">
                        <?= ucfirst(htmlspecialchars($booking['status'])) ?>
                    </span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Total Price:</span>
                    <span class="font-bold text-blue-600">$<?= number_format($booking['total_price'], 2) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-gray-600 font-medium">Passengers:</span>
                    <span><?= htmlspecialchars($booking['passengers']) ?></span>
                </div>
            </div>
            
            <!-- Status Update Form -->
            <div class="mt-6 pt-4 border-t border-gray-200">
                <h3 class="text-md font-medium text-gray-800 mb-3">Update Status</h3>
                <form method="POST" action="bookings.php">
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
                    <div class="flex space-x-2">
                        <select name="new_status" class="flex-grow px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
                            <option value="pending" <?= $booking['status'] === 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="confirmed" <?= $booking['status'] === 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
                            <option value="canceled" <?= $booking['status'] === 'canceled' ? 'selected' : '' ?>>Canceled</option>
                            <option value="completed" <?= $booking['status'] === 'completed' ? 'selected' : '' ?>>Completed</option>
                        </select>
                        <button type="submit" class="px-3 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Update
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Flight Details Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Flight Details</h2>
            
            <?php if ($flight): ?>
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Flight Number:</span>
                    <span class="font-semibold"><?= htmlspecialchars($flight['flight_number']) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Airline:</span>
                    <span><?= htmlspecialchars($flight['airline'] ?? 'N/A') ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Route:</span>
                    <span><?= htmlspecialchars($flight['departure']) ?> → <?= htmlspecialchars($flight['arrival']) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Date & Time:</span>
                    <span>
                        <?= date('M j, Y', strtotime($flight['date'])) ?> at 
                        <?= date('H:i', strtotime($flight['time'])) ?>
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Price:</span>
                    <span>$<?= number_format($flight['price'], 2) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Available Seats:</span>
                    <span><?= htmlspecialchars($flight['available_seats'] ?? 'N/A') ?></span>
                </div>
            </div>
            <?php else: ?>
            <div class="py-4 text-gray-500 italic">
                Flight information not available or has been deleted.
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Customer Details Card -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <h2 class="text-xl font-semibold text-gray-800 mb-4 pb-2 border-b border-gray-200">Customer Information</h2>
            
            <div class="space-y-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Name:</span>
                    <span><?= htmlspecialchars($booking['customer_name']) ?></span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Email:</span>
                    <a href="mailto:<?= htmlspecialchars($booking['customer_email']) ?>" class="text-blue-600 hover:underline">
                        <?= htmlspecialchars($booking['customer_email']) ?>
                    </a>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-gray-600 font-medium">Phone:</span>
                    <span><?= htmlspecialchars($booking['customer_phone']) ?></span>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Passenger Details Section -->
    <div class="mt-8 bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Passenger Details</h2>
        
        <?php if (!empty($passengers)): ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">DOB</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Nationality</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Passport</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Exp. Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Special Req.</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($passengers as $index => $passenger): ?>
                            <tr class="<?= $index % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?>">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?= htmlspecialchars($passenger['title']) ?> 
                                    <?= htmlspecialchars($passenger['first_name']) ?> 
                                    <?= htmlspecialchars($passenger['last_name']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($passenger['date_of_birth'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($passenger['nationality']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($passenger['passport_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= date('M j, Y', strtotime($passenger['passport_expiry'])) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($passenger['special_requirements'] ?? 'None') ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="py-4 text-gray-500 italic">
                No passenger information has been added to this booking yet.
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Payment Information Section -->
    <div class="mt-8 bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Payment Information</h2>
        
        <?php if ($payment): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Payment Status:</span>
                            <span class="px-3 py-1 rounded-full text-xs font-medium
                                <?php if ($payment['status'] === 'completed'): ?>
                                    bg-green-100 text-green-800
                                <?php elseif ($payment['status'] === 'pending'): ?>
                                    bg-yellow-100 text-yellow-800
                                <?php elseif ($payment['status'] === 'failed'): ?>
                                    bg-red-100 text-red-800
                                <?php else: ?>
                                    bg-gray-100 text-gray-800
                                <?php endif; ?>">
                                <?= ucfirst(htmlspecialchars($payment['status'])) ?>
                            </span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Amount:</span>
                            <span class="font-bold">$<?= number_format($payment['amount'], 2) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Payment Method:</span>
                            <span><?= ucfirst(htmlspecialchars($payment['payment_method'])) ?></span>
                        </div>
                    </div>
                </div>
                <div>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Transaction ID:</span>
                            <span class="font-mono text-sm"><?= htmlspecialchars($payment['transaction_id']) ?></span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-600 font-medium">Payment Date:</span>
                            <span><?= date('F j, Y H:i', strtotime($payment['payment_date'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="py-4 text-gray-500 italic">
                No payment information found for this booking.
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Actions Footer -->
    <div class="mt-8 flex justify-end space-x-4">
        <form method="POST" action="bookings.php" onsubmit="return confirm('Are you sure you want to delete this booking? This action cannot be undone.');">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="booking_id" value="<?= htmlspecialchars($booking['id']) ?>">
            <button type="submit" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 transition-colors flex items-center">
                <i class="fas fa-trash mr-1"></i> Delete Booking
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
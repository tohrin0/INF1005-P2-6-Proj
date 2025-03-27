<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Booking.php';
require_once '../inc/session.php';

verifyAdminSession();
// Get booking status filter from query string
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['booking_id'])) {
        $bookingId = $_POST['booking_id'];
        $bookingObj = new Booking();
        
        if ($_POST['action'] === 'update_status') {
            $newStatus = $_POST['new_status'] ?? 'pending';
            if ($bookingObj->updateBookingStatus($bookingId, $newStatus)) {
                $_SESSION['admin_message'] = "Booking status updated to " . ucfirst($newStatus);
            } else {
                $_SESSION['admin_error'] = "Failed to update booking status";
            }
        } elseif ($_POST['action'] === 'delete') {
            // Implement delete functionality
            try {
                $pdo->beginTransaction();
                
                // Delete passengers for this booking
                $stmt = $pdo->prepare("DELETE FROM passengers WHERE booking_id = ?");
                $stmt->execute([$bookingId]);
                
                // Delete payments for this booking
                $stmt = $pdo->prepare("DELETE FROM payments WHERE booking_id = ?");
                $stmt->execute([$bookingId]);
                
                // Delete the booking
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                $stmt->execute([$bookingId]);
                
                $pdo->commit();
                $_SESSION['admin_message'] = "Booking deleted successfully";
            } catch (Exception $e) {
                $pdo->rollBack();
                $_SESSION['admin_error'] = "Error deleting booking: " . $e->getMessage();
            }
        }
        
        header('Location: bookings.php');
        exit();
    }
}

// Get bookings based on filter
$bookings = ($statusFilter !== 'all') 
    ? Booking::getBookingsByStatus($statusFilter) 
    : Booking::getAllBookings();

// Get statistics
$stats = [
    'total' => count(Booking::getAllBookings()),
    'pending' => count(Booking::getBookingsByStatus('pending')),
    'confirmed' => count(Booking::getBookingsByStatus('confirmed')),
    'canceled' => count(Booking::getBookingsByStatus('canceled')),
];

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Manage Bookings</h1>
        <p class="text-gray-600">View and manage customer bookings</p>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($_SESSION['admin_message']); ?></p>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?php echo htmlspecialchars($_SESSION['admin_error']); ?></p>
        </div>
        <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>
    
    <!-- Booking Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <!-- Total Bookings -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-blue-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-wider">Total Bookings</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                </div>
                <div class="text-blue-500 h-10 w-10 flex items-center justify-center rounded-full bg-blue-100">
                    <i class="fas fa-ticket-alt"></i>
                </div>
            </div>
        </div>
        
        <!-- Pending Bookings -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-yellow-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-wider">Pending</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['pending']; ?></p>
                </div>
                <div class="text-yellow-500 h-10 w-10 flex items-center justify-center rounded-full bg-yellow-100">
                    <i class="fas fa-clock"></i>
                </div>
            </div>
        </div>
        
        <!-- Confirmed Bookings -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-green-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-wider">Confirmed</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['confirmed']; ?></p>
                </div>
                <div class="text-green-500 h-10 w-10 flex items-center justify-center rounded-full bg-green-100">
                    <i class="fas fa-check"></i>
                </div>
            </div>
        </div>
        
        <!-- Canceled Bookings -->
        <div class="bg-white rounded-lg shadow-sm p-4 border-l-4 border-red-500">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-500 uppercase tracking-wider">Canceled</p>
                    <p class="text-2xl font-bold text-gray-800"><?php echo $stats['canceled']; ?></p>
                </div>
                <div class="text-red-500 h-10 w-10 flex items-center justify-center rounded-full bg-red-100">
                    <i class="fas fa-times"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Booking Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Filter by status:</span>
            <a href="?status=all" class="px-3 py-1.5 rounded-full text-sm <?php echo $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                All
            </a>
            <a href="?status=pending" class="px-3 py-1.5 rounded-full text-sm <?php echo $statusFilter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'; ?> transition">
                Pending
            </a>
            <a href="?status=confirmed" class="px-3 py-1.5 rounded-full text-sm <?php echo $statusFilter === 'confirmed' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?> transition">
                Confirmed
            </a>
            <a href="?status=canceled" class="px-3 py-1.5 rounded-full text-sm <?php echo $statusFilter === 'canceled' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> transition">
                Canceled
            </a>
            <a href="?status=completed" class="px-3 py-1.5 rounded-full text-sm <?php echo $statusFilter === 'completed' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-800 hover:bg-purple-200'; ?> transition">
                Completed
            </a>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Bookings List</h2>
            <input type="text" id="bookingSearch" placeholder="Search bookings..." 
                class="px-3 py-1.5 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Booking ID</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flight Details</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody id="bookingTableBody" class="bg-white divide-y divide-gray-200">
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No bookings found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    #<?php echo htmlspecialchars($booking['id']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo isset($booking['flight_number']) ? htmlspecialchars($booking['flight_number']) : 'N/A'; ?>
                                    </div>
                                    <div class="text-sm text-gray-500">
                                        <?php if (isset($booking['departure']) && isset($booking['arrival'])): ?>
                                            <?php echo htmlspecialchars($booking['departure']); ?> → <?php echo htmlspecialchars($booking['arrival']); ?>
                                        <?php else: ?>
                                            Route info not available
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            bg-yellow-100 text-yellow-800
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            bg-green-100 text-green-800
                                        <?php elseif ($booking['status'] === 'canceled'): ?>
                                            bg-red-100 text-red-800
                                        <?php elseif ($booking['status'] === 'completed'): ?>
                                            bg-blue-100 text-blue-800
                                        <?php else: ?>
                                            bg-gray-100 text-gray-800
                                        <?php endif; ?>">
                                        <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    $<?php echo number_format($booking['total_price'], 2); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="text-indigo-600 hover:text-indigo-900" title="View Booking">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900" title="Edit Booking">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDeleteBooking(<?php echo $booking['id']; ?>)" class="text-red-600 hover:text-red-900" title="Delete Booking">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Hidden form for delete action -->
    <form id="deleteBookingForm" method="POST" style="display: none;">
        <input type="hidden" name="action" value="delete">
        <input type="hidden" name="booking_id" id="deleteBookingId">
    </form>
</div>

<script>
    function confirmDeleteBooking(bookingId) {
        if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
            document.getElementById('deleteBookingId').value = bookingId;
            document.getElementById('deleteBookingForm').submit();
        }
    }
</script>

<?php include 'includes/footer.php'; ?>
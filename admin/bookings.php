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
            // Add delete booking functionality
            // This would require more careful handling as it involves multiple tables
            $_SESSION['admin_error'] = "Delete functionality requires careful implementation due to database relationships";
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
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h2 class="text-lg font-semibold text-gray-800">Booking List</h2>
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
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200" id="bookingTableBody">
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-sm text-gray-500">No bookings found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $index => $booking): ?>
                            <tr class="<?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?>">
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
                                        <?php 
                                        if (isset($booking['departure']) && isset($booking['arrival'])) {
                                            echo htmlspecialchars($booking['departure'] . ' → ' . $booking['arrival']);
                                        } else {
                                            echo 'Flight info not available';
                                        }
                                        ?>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo isset($booking['booking_date']) ? date('M j, Y', strtotime($booking['booking_date'])) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    $<?php echo isset($booking['total_price']) ? number_format($booking['total_price'], 2) : 'N/A'; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($booking['status'] === 'pending'): ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            Pending
                                        </span>
                                    <?php elseif ($booking['status'] === 'confirmed'): ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                            Confirmed
                                        </span>
                                    <?php elseif ($booking['status'] === 'canceled'): ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                            Canceled
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M10 12a2 2 0 100-4 2 2 0 000 4z" />
                                                <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" />
                                            </svg>
                                        </a>
                                        <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="text-blue-600 hover:text-blue-900">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                            </svg>
                                        </a>
                                        <form method="POST" action="bookings.php">
                                            <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="text-red-600 hover:text-red-900">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                                    <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// Search functionality for the bookings table
document.getElementById('bookingSearch').addEventListener('keyup', function() {
    const searchTerm = this.value.toLowerCase();
    const tableRows = document.getElementById('bookingTableBody').getElementsByTagName('tr');
    
    Array.from(tableRows).forEach(row => {
        const rowText = row.textContent.toLowerCase();
        if (rowText.includes(searchTerm)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});

// Delete confirmation
function confirmDelete(bookingId) {
    if (confirm('Are you sure you want to delete this booking? This action cannot be undone.')) {
        window.location.href = 'delete-booking.php?id=' + bookingId;
    }
}
</script>

<?php include 'includes/footer.php'; ?>
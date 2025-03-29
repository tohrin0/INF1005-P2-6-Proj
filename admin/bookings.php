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
    <div class="admin-page-header">
        <h1>Manage Bookings</h1>
        <p>View and manage customer bookings</p>
    </div>
    
    <?php if (isset($_SESSION['admin_message'])): ?>
        <div class="admin-alert admin-alert-success">
            <p><?php echo htmlspecialchars($_SESSION['admin_message']); ?></p>
        </div>
        <?php unset($_SESSION['admin_message']); ?>
    <?php endif; ?>
    
    <?php if (isset($_SESSION['admin_error'])): ?>
        <div class="admin-alert admin-alert-danger">
            <p><?php echo htmlspecialchars($_SESSION['admin_error']); ?></p>
        </div>
        <?php unset($_SESSION['admin_error']); ?>
    <?php endif; ?>
    
    <!-- Booking Stats Cards -->
    <div class="admin-grid-4 mb-6">
        <!-- Total Bookings -->
        <div class="admin-metric-card admin-metric-primary">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Total Bookings</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-primary">
                <i class="fas fa-ticket-alt"></i>
            </div>
        </div>
        
        <!-- Pending Bookings -->
        <div class="admin-metric-card admin-metric-warning">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Pending</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['pending']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        
        <!-- Confirmed Bookings -->
        <div class="admin-metric-card admin-metric-success">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Confirmed</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['confirmed']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-success">
                <i class="fas fa-check"></i>
            </div>
        </div>
        
        <!-- Canceled Bookings -->
        <div class="admin-metric-card admin-metric-danger">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Canceled</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['canceled']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-danger">
                <i class="fas fa-times"></i>
            </div>
        </div>
    </div>
    
    <!-- Bookings Table -->
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h2>Bookings List</h2>
            <div class="header-actions flex flex-wrap gap-2">
                <a href="?status=all" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                    All
                </a>
                <a href="?status=pending" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'pending' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'; ?> transition">
                    Pending
                </a>
                <a href="?status=confirmed" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'confirmed' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?> transition">
                    Confirmed
                </a>
                <a href="?status=canceled" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'canceled' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> transition">
                    Canceled
                </a>
                <a href="?status=completed" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'completed' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-800 hover:bg-purple-200'; ?> transition">
                    Completed
                </a>
                
                <input type="text" id="bookingSearch" placeholder="Search bookings..." 
                    class="px-3 py-1 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 ml-auto">
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Booking ID</th>
                        <th scope="col">Customer</th>
                        <th scope="col">Flight Details</th>
                        <th scope="col">Date</th>
                        <th scope="col">Status</th>
                        <th scope="col">Price</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="bookingTableBody">
                    <?php if (empty($bookings)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-gray-500">No bookings found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $booking): ?>
                            <tr>
                                <td>
                                    <div class="font-medium">#<?php echo htmlspecialchars($booking['id']); ?></div>
                                </td>
                                <td>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($booking['customer_name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($booking['customer_email']); ?></div>
                                </td>
                                <td>
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
                                <td>
                                    <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?>
                                </td>
                                <td>
                                    <span class="status-badge 
                                        <?php if ($booking['status'] === 'pending'): ?>
                                            status-badge-warning
                                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                                            status-badge-success
                                        <?php elseif ($booking['status'] === 'canceled'): ?>
                                            status-badge-danger
                                        <?php elseif ($booking['status'] === 'completed'): ?>
                                            status-badge-info
                                        <?php else: ?>
                                            status-badge-default
                                        <?php endif; ?>">
                                        <?php echo ucfirst(htmlspecialchars($booking['status'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="font-medium">$<?php echo number_format($booking['total_price'], 2); ?></div>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end space-x-2">
                                        <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="admin-action-view" title="View Booking">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="admin-action-edit" title="Edit Booking">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDeleteBooking(<?php echo $booking['id']; ?>)" class="admin-action-delete" title="Delete Booking">
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
    
    // Simple search functionality
    document.getElementById('bookingSearch').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const rows = document.querySelectorAll('#bookingTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
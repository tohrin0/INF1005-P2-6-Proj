<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Flight.php';
require_once '../inc/session.php';

verifyAdminSession();

// Get flight status filter from query string
$statusFilter = isset($_GET['status']) ? $_GET['status'] : 'all';

// Handle flight actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_flight']) && isset($_POST['flight_id'])) {
        $flightId = $_POST['flight_id'];
        
        if (Flight::delete($flightId)) {
            $_SESSION['admin_message'] = "Flight deleted successfully.";
        } else {
            $_SESSION['admin_error'] = "Failed to delete flight. It may have associated bookings.";
        }
        
        header('Location: flights.php');
        exit();
    }
    
    if (isset($_POST['add_flight'])) {
        // Code to add a new flight
        $flightData = [
            'flight_number' => $_POST['flight_number'],
            'departure' => $_POST['departure'],
            'arrival' => $_POST['arrival'],
            'date' => $_POST['date'],
            'time' => $_POST['time'],
            'duration' => $_POST['duration'],
            'price' => $_POST['price'],
            'available_seats' => $_POST['available_seats'],
            'airline' => $_POST['airline'],
            'status' => $_POST['status'] ?? 'scheduled',
            'flight_api' => $_POST['flight_api'] ?? null
        ];
        
        $flight = new Flight();
        $flight->setFromArray($flightData);
        
        if ($flight->save()) {
            $_SESSION['admin_message'] = "Flight added successfully.";
        } else {
            $_SESSION['admin_error'] = "Failed to add flight.";
        }
        
        header('Location: flights.php');
        exit();
    }
}

// Get flights based on filter
$flights = ($statusFilter !== 'all') 
    ? Flight::getFlightsByStatus($statusFilter) 
    : getAllFlights();

// Get statistics
$stats = [
    'total' => count(getAllFlights()),
    'scheduled' => count(Flight::getFlightsByStatus('scheduled')),
    'delayed' => count(Flight::getFlightsByStatus('delayed')),
    'cancelled' => count(Flight::getFlightsByStatus('cancelled')),
    'completed' => count(Flight::getFlightsByStatus('completed')),
];

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="admin-page-header">
        <h1>Manage Flights</h1>
        <p>Add, edit, and delete flights in the system</p>
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
    
    <!-- Flight Stats Cards -->
    <div class="admin-grid-4 mb-6">
        <!-- Total Flights -->
        <div class="admin-metric-card admin-metric-primary">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Total Flights</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-primary">
                <i class="fas fa-plane"></i>
            </div>
        </div>
        
        <!-- Scheduled Flights -->
        <div class="admin-metric-card admin-metric-success">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Scheduled</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['scheduled']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-success">
                <i class="fas fa-calendar-check"></i>
            </div>
        </div>
        
        <!-- Delayed Flights -->
        <div class="admin-metric-card admin-metric-warning">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Delayed</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['delayed']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-warning">
                <i class="fas fa-clock"></i>
            </div>
        </div>
        
        <!-- Cancelled Flights -->
        <div class="admin-metric-card admin-metric-danger">
            <div>
                <p class="text-sm text-gray-500 uppercase tracking-wider">Cancelled</p>
                <p class="text-2xl font-bold text-gray-800"><?php echo $stats['cancelled']; ?></p>
            </div>
            <div class="admin-metric-icon admin-metric-icon-danger">
                <i class="fas fa-ban"></i>
            </div>
        </div>
    </div>
    
    <!-- Flights Table -->
    <div class="admin-content-card">
        <div class="admin-card-header">
            <h2>Flights List</h2>
            <div class="header-actions flex flex-wrap gap-2">
                <a href="?status=all" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?> transition">
                    All
                </a>
                <a href="?status=scheduled" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'scheduled' ? 'bg-green-600 text-white' : 'bg-green-100 text-green-800 hover:bg-green-200'; ?> transition">
                    Scheduled
                </a>
                <a href="?status=delayed" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'delayed' ? 'bg-yellow-600 text-white' : 'bg-yellow-100 text-yellow-800 hover:bg-yellow-200'; ?> transition">
                    Delayed
                </a>
                <a href="?status=cancelled" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'cancelled' ? 'bg-red-600 text-white' : 'bg-red-100 text-red-800 hover:bg-red-200'; ?> transition">
                    Cancelled
                </a>
                <a href="?status=completed" class="px-3 py-1 rounded-full text-sm <?php echo $statusFilter === 'completed' ? 'bg-purple-600 text-white' : 'bg-purple-100 text-purple-800 hover:bg-purple-200'; ?> transition">
                    Completed
                </a>
                
                <input type="text" id="flightSearch" placeholder="Search flights..." 
                    class="px-3 py-1 text-sm border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 ml-auto">
                    
                <button id="showAddFlightModal" class="px-3 py-1.5 rounded-md text-sm bg-green-600 text-white hover:bg-green-700 transition">
                    <i class="fas fa-plus mr-1"></i> Add Flight
                </button>
            </div>
        </div>
        
        <div class="overflow-x-auto">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th scope="col">Flight Number</th>
                        <th scope="col">Route</th>
                        <th scope="col">Date & Time</th>
                        <th scope="col">Airline</th>
                        <th scope="col">Price</th>
                        <th scope="col">Status</th>
                        <th scope="col" class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="flightTableBody">
                    <?php if (empty($flights)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-gray-500">No flights found matching your criteria.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($flights as $flight): ?>
                            <tr>
                                <td>
                                    <div class="font-medium"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                                </td>
                                <td>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($flight['departure']); ?> → <?php echo htmlspecialchars($flight['arrival']); ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-sm"><?php echo date('M j, Y', strtotime($flight['date'])); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($flight['time'])); ?></div>
                                </td>
                                <td>
                                    <?php echo htmlspecialchars($flight['airline']); ?>
                                </td>
                                <td>
                                    <div class="font-medium">$<?php echo number_format($flight['price'], 2); ?></div>
                                </td>
                                <td>
                                    <span class="status-badge 
                                        <?php if ($flight['status'] === 'scheduled'): ?>
                                            status-badge-success
                                        <?php elseif ($flight['status'] === 'delayed'): ?>
                                            status-badge-warning
                                        <?php elseif ($flight['status'] === 'cancelled'): ?>
                                            status-badge-danger
                                        <?php elseif ($flight['status'] === 'completed'): ?>
                                            status-badge-info
                                        <?php else: ?>
                                            status-badge-default
                                        <?php endif; ?>">
                                        <?php echo ucfirst(htmlspecialchars($flight['status'])); ?>
                                    </span>
                                </td>
                                <td class="text-right">
                                    <div class="flex justify-end space-x-2">
                                        <a href="edit-flight.php?id=<?php echo $flight['id']; ?>" class="admin-action-edit" title="Edit Flight">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDeleteFlight(<?php echo $flight['id']; ?>, '<?php echo htmlspecialchars($flight['flight_number']); ?>')" class="admin-action-delete" title="Delete Flight">
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
    <form id="deleteFlightForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_flight" value="1">
        <input type="hidden" name="flight_id" id="deleteFlightId">
    </form>
</div>

<!-- Add Flight Modal -->
<div id="addFlightModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
    <div class="fixed inset-0 bg-black opacity-50"></div>
    <div class="bg-white rounded-lg shadow-xl max-w-3xl w-full z-10 p-6 max-h-screen overflow-y-auto relative">
        <button id="closeModal" class="absolute top-4 right-4 text-gray-500 hover:text-gray-700">
            <i class="fas fa-times"></i>
        </button>
        
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Add New Flight</h2>
        
        <form action="" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="space-y-2">
                <label for="flight_number" class="block text-sm font-medium text-gray-700">Flight Number</label>
                <input type="text" name="flight_number" id="flight_number" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="airline" class="block text-sm font-medium text-gray-700">Airline</label>
                <input type="text" name="airline" id="airline" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="departure" class="block text-sm font-medium text-gray-700">Departure</label>
                <input type="text" name="departure" id="departure" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="arrival" class="block text-sm font-medium text-gray-700">Arrival</label>
                <input type="text" name="arrival" id="arrival" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="date" class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" name="date" id="date" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="time" class="block text-sm font-medium text-gray-700">Time</label>
                <input type="time" name="time" id="time" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="duration" class="block text-sm font-medium text-gray-700">Duration (minutes)</label>
                <input type="number" name="duration" id="duration" min="1" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="price" class="block text-sm font-medium text-gray-700">Price ($)</label>
                <input type="number" name="price" id="price" step="0.01" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="available_seats" class="block text-sm font-medium text-gray-700">Available Seats</label>
                <input type="number" name="available_seats" id="available_seats" value="100" min="0"
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="space-y-2">
                <label for="status" class="block text-sm font-medium text-gray-700">Status</label>
                <select name="status" id="status" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="scheduled">Scheduled</option>
                    <option value="delayed">Delayed</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            
            <div class="space-y-2">
                <label for="flight_api" class="block text-sm font-medium text-gray-700">API Reference ID (Optional)</label>
                <input type="text" name="flight_api" id="flight_api" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="md:col-span-2 mt-4">
                <button type="submit" name="add_flight" class="w-full inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    Add Flight
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Modal functionality
    const modal = document.getElementById('addFlightModal');
    const showModalBtn = document.getElementById('showAddFlightModal');
    const closeModalBtn = document.getElementById('closeModal');
    
    showModalBtn.addEventListener('click', () => {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden'; // Prevent scrolling when modal is open
    });
    
    closeModalBtn.addEventListener('click', () => {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto'; // Re-enable scrolling
    });
    
    // Close modal if clicked outside the content
    modal.addEventListener('click', (e) => {
        if (e.target === modal) {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }
    });
    
    // Function to confirm flight deletion
    function confirmDeleteFlight(flightId, flightNumber) {
        if (confirm(`Are you sure you want to delete flight ${flightNumber}? This action cannot be undone.`)) {
            document.getElementById('deleteFlightId').value = flightId;
            document.getElementById('deleteFlightForm').submit();
        }
    }
    
    // Simple search functionality
    document.getElementById('flightSearch').addEventListener('keyup', function() {
        const searchText = this.value.toLowerCase();
        const rows = document.querySelectorAll('#flightTableBody tr');
        
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchText) ? '' : 'none';
        });
    });
</script>

<?php include 'includes/footer.php'; ?>
<?php
// Clear any potential PHP cache
clearstatcache();

require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Flight.php';
require_once '../inc/session.php';

verifyAdminSession();

$flights = getAllFlights(); // Function to retrieve all flights from the database

$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';

unset($_SESSION['success']);
unset($_SESSION['error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
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
            $_SESSION['success'] = "Flight added successfully.";
        } else {
            $_SESSION['error'] = "Failed to add flight.";
        }
        
        header('Location: flights.php');
        exit();
    }
    
    if (isset($_POST['delete_flight']) && isset($_POST['flight_id'])) {
        $flightId = $_POST['flight_id'];
        
        if (Flight::delete($flightId)) {
            $_SESSION['success'] = "Flight deleted successfully.";
        } else {
            $_SESSION['error'] = "Failed to delete flight.";
        }
        
        header('Location: flights.php');
        exit();
    }
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Manage Flights</h1>
            <p class="text-gray-600">Add, edit, and delete flights in the system</p>
        </div>
        <button id="showAddFlightModal" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors flex items-center">
            <i class="fas fa-plus mr-2"></i> Add New Flight
        </button>
    </div>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-50 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
    
    <!-- Flights Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Flight Number</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Airline</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php if (empty($flights)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-4 text-center text-gray-500">No flights found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($flights as $flight): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 whitespace-nowrap font-medium text-gray-900">
                                    <?= htmlspecialchars($flight['flight_number']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= htmlspecialchars($flight['departure']) ?> → <?= htmlspecialchars($flight['arrival']) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?= date('M j, Y', strtotime($flight['date'])) ?></div>
                                    <div class="text-sm text-gray-500"><?= date('g:i A', strtotime($flight['time'])) ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?= htmlspecialchars($flight['airline']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    $<?= number_format($flight['price'], 2) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full
                                        <?php if ($flight['status'] === 'scheduled'): ?>
                                            bg-green-100 text-green-800
                                        <?php elseif ($flight['status'] === 'delayed'): ?>
                                            bg-yellow-100 text-yellow-800
                                        <?php elseif ($flight['status'] === 'cancelled'): ?>
                                            bg-red-100 text-red-800
                                        <?php else: ?>
                                            bg-gray-100 text-gray-800
                                        <?php endif; ?>">
                                        <?= ucfirst(htmlspecialchars($flight['status'])) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="edit-flight.php?id=<?= $flight['id'] ?>" class="text-blue-600 hover:text-blue-900" title="Edit Flight">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button onclick="confirmDeleteFlight(<?= $flight['id'] ?>, '<?= htmlspecialchars($flight['flight_number']) ?>')" class="text-red-600 hover:text-red-900" title="Delete Flight">
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

<!-- Hidden form for flight deletion -->
<form id="deleteFlightForm" method="POST" style="display: none;">
    <input type="hidden" name="delete_flight" value="1">
    <input type="hidden" name="flight_id" id="deleteFlightId">
</form>

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
</script>

<?php include 'includes/footer.php'; ?>
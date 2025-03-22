<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Passenger.php';
require_once 'classes/Booking.php';
require_once 'classes/BookingManager.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';

// Get booking ID from URL
$bookingId = $_GET['booking_id'] ?? null;
if (!$bookingId) {
    header('Location: my-bookings.php');
    exit();
}

// Initialize classes
$bookingObj = new Booking();
$passengerObj = new Passenger();
$bookingManager = new BookingManager();

// Verify booking belongs to user
$booking = $bookingObj->getBookingById($bookingId, $userId);
if (!$booking) {
    header('Location: my-bookings.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_passenger'])) {
    $passengerData = [
        'title' => $_POST['title'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'date_of_birth' => $_POST['date_of_birth'],
        'nationality' => $_POST['nationality'],
        'passport_number' => $_POST['passport_number'],
        'passport_expiry' => $_POST['passport_expiry'],
        'special_requirements' => $_POST['special_requirements'] ?? null
    ];

    if ($passengerObj->addPassenger($bookingId, $passengerData)) {
        $success = "Passenger details saved successfully.";
    } else {
        $error = "Failed to save passenger details.";
    }
}

// Handle passenger edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_passenger'])) {
    $passengerId = $_POST['passenger_id'];
    $passengerData = [
        'title' => $_POST['title'],
        'first_name' => $_POST['first_name'],
        'last_name' => $_POST['last_name'],
        'date_of_birth' => $_POST['date_of_birth'],
        'nationality' => $_POST['nationality'],
        'passport_number' => $_POST['passport_number'],
        'passport_expiry' => $_POST['passport_expiry'],
        'special_requirements' => $_POST['special_requirements'] ?? null
    ];

    if ($passengerObj->updatePassenger($passengerId, $passengerData)) {
        $success = "Passenger details updated successfully.";
    } else {
        $error = "Failed to update passenger details.";
    }
}

// Get existing passengers
$passengers = $passengerObj->getPassengersByBooking($bookingId);
$remainingPassengers = $booking['passengers'] - count($passengers);

include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-8 max-w-4xl">
    <h1 class="text-3xl font-bold text-gray-800 mb-6">Manage Passengers</h1>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Booking Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <div>
                <p class="text-gray-600">Booking Reference:</p>
                <p class="font-medium">#<?= htmlspecialchars($bookingId) ?></p>
            </div>
            <div>
                <p class="text-gray-600">Flight:</p>
                <p class="font-medium"><?= htmlspecialchars($booking['flight_number'] ?? 'N/A') ?></p>
            </div>
            <div>
                <p class="text-gray-600">Total Passengers:</p>
                <p class="font-medium"><?= htmlspecialchars($booking['passengers']) ?></p>
            </div>
            <div>
                <p class="text-gray-600">Remaining Passengers to Add:</p>
                <p class="font-medium"><?= htmlspecialchars($remainingPassengers) ?></p>
            </div>
        </div>
        
        <?php if ($remainingPassengers > 0): ?>
            <div class="bg-blue-50 border border-blue-100 text-blue-700 px-4 py-3 rounded-md mb-6">
                <p class="flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Please add details for <?= $remainingPassengers ?> more passenger(s) below.
                </p>
            </div>
            
            <form method="POST" action="" class="border border-gray-200 rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Add Passenger</h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                        <select name="title" id="title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="Mr">Mr</option>
                            <option value="Mrs">Mrs</option>
                            <option value="Ms">Ms</option>
                            <option value="Dr">Dr</option>
                        </select>
                    </div>
                    
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" id="first_name" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" id="last_name" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="nationality" class="block text-sm font-medium text-gray-700 mb-1">Nationality *</label>
                        <input type="text" id="nationality" name="nationality" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="passport_number" class="block text-sm font-medium text-gray-700 mb-1">Passport Number *</label>
                        <input type="text" id="passport_number" name="passport_number" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    
                    <div>
                        <label for="passport_expiry" class="block text-sm font-medium text-gray-700 mb-1">Passport Expiry Date *</label>
                        <input type="date" id="passport_expiry" name="passport_expiry" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                
                <div class="mt-4">
                    <label for="special_requirements" class="block text-sm font-medium text-gray-700 mb-1">Special Requirements</label>
                    <textarea id="special_requirements" name="special_requirements" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
                
                <div class="mt-6 flex justify-end">
                    <button type="submit" name="save_passenger" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                        Save Passenger
                    </button>
                </div>
            </form>
        <?php endif; ?>
    </div>
    
    <?php if (!empty($passengers)): ?>
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold text-gray-800 mb-4">Saved Passengers</h2>
        
        <div class="space-y-4">
            <?php foreach ($passengers as $passenger): ?>
            <div class="border border-gray-200 rounded-lg p-4">
                <div class="flex justify-between items-center mb-2">
                    <h3 class="font-medium">
                        <?= htmlspecialchars($passenger['title']) ?> 
                        <?= htmlspecialchars($passenger['first_name']) ?> 
                        <?= htmlspecialchars($passenger['last_name']) ?>
                    </h3>
                    <a href="#" class="text-blue-600 hover:text-blue-800 text-sm font-medium edit-passenger" 
                       data-passenger-id="<?= $passenger['id'] ?>"
                       data-title="<?= htmlspecialchars($passenger['title']) ?>"
                       data-first-name="<?= htmlspecialchars($passenger['first_name']) ?>"
                       data-last-name="<?= htmlspecialchars($passenger['last_name']) ?>"
                       data-dob="<?= htmlspecialchars($passenger['date_of_birth']) ?>"
                       data-nationality="<?= htmlspecialchars($passenger['nationality']) ?>"
                       data-passport="<?= htmlspecialchars($passenger['passport_number']) ?>"
                       data-expiry="<?= htmlspecialchars($passenger['passport_expiry']) ?>"
                       data-requirements="<?= htmlspecialchars($passenger['special_requirements'] ?? '') ?>">
                        Edit Details
                    </a>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-2 text-sm">
                    <p><strong>Passport:</strong> <?= htmlspecialchars($passenger['passport_number']) ?></p>
                    <p><strong>Expires:</strong> <?= date('F j, Y', strtotime($passenger['passport_expiry'])) ?></p>
                    <p><strong>Nationality:</strong> <?= htmlspecialchars($passenger['nationality']) ?></p>
                    <p><strong>Date of Birth:</strong> <?= date('F j, Y', strtotime($passenger['date_of_birth'])) ?></p>
                    <?php if (!empty($passenger['special_requirements'])): ?>
                    <p class="col-span-2"><strong>Special Requirements:</strong> <?= htmlspecialchars($passenger['special_requirements']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="mt-6">
        <a href="my-bookings.php" class="text-blue-600 hover:text-blue-800 flex items-center">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Back to My Bookings
        </a>
    </div>
</div>

<!-- Edit Passenger Modal -->
<div id="editPassengerModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white rounded-lg p-6 max-w-md w-full max-h-screen overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-semibold">Edit Passenger</h3>
            <button id="closeModal" class="text-gray-500 hover:text-gray-700">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        
        <form method="POST" action="" id="editPassengerForm">
            <input type="hidden" name="passenger_id" id="edit_passenger_id">
            
            <div class="grid grid-cols-1 gap-4">
                <div>
                    <label for="edit_title" class="block text-sm font-medium text-gray-700 mb-1">Title *</label>
                    <select name="title" id="edit_title" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="Mr">Mr</option>
                        <option value="Mrs">Mrs</option>
                        <option value="Ms">Ms</option>
                        <option value="Dr">Dr</option>
                    </select>
                </div>
                
                <div>
                    <label for="edit_first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                    <input type="text" id="edit_first_name" name="first_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="edit_last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                    <input type="text" id="edit_last_name" name="last_name" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="edit_date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth *</label>
                    <input type="date" id="edit_date_of_birth" name="date_of_birth" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="edit_nationality" class="block text-sm font-medium text-gray-700 mb-1">Nationality *</label>
                    <input type="text" id="edit_nationality" name="nationality" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="edit_passport_number" class="block text-sm font-medium text-gray-700 mb-1">Passport Number *</label>
                    <input type="text" id="edit_passport_number" name="passport_number" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="edit_passport_expiry" class="block text-sm font-medium text-gray-700 mb-1">Passport Expiry Date *</label>
                    <input type="date" id="edit_passport_expiry" name="passport_expiry" required class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>
                
                <div>
                    <label for="edit_special_requirements" class="block text-sm font-medium text-gray-700 mb-1">Special Requirements</label>
                    <textarea id="edit_special_requirements" name="special_requirements" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                </div>
            </div>
            
            <div class="mt-6 flex justify-end">
                <button type="submit" name="edit_passenger" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                    Update Passenger
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Edit passenger modal functionality
    const editLinks = document.querySelectorAll('.edit-passenger');
    const modal = document.getElementById('editPassengerModal');
    const closeModal = document.getElementById('closeModal');
    
    editLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Get passenger data from data attributes
            document.getElementById('edit_passenger_id').value = this.dataset.passengerId;
            document.getElementById('edit_title').value = this.dataset.title;
            document.getElementById('edit_first_name').value = this.dataset.firstName;
            document.getElementById('edit_last_name').value = this.dataset.lastName;
            document.getElementById('edit_date_of_birth').value = this.dataset.dob;
            document.getElementById('edit_nationality').value = this.dataset.nationality;
            document.getElementById('edit_passport_number').value = this.dataset.passport;
            document.getElementById('edit_passport_expiry').value = this.dataset.expiry;
            document.getElementById('edit_special_requirements').value = this.dataset.requirements;
            
            // Show modal
            modal.classList.remove('hidden');
        });
    });
    
    closeModal.addEventListener('click', function() {
        modal.classList.add('hidden');
    });
    
    // Close modal when clicking outside
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            modal.classList.add('hidden');
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>
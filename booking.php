<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';
require_once 'classes/Booking.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$success = '';
$flights = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    if (empty($_POST['flight_id']) || empty($_POST['name']) || empty($_POST['email']) || empty($_POST['phone'])) {
        $error = "Please fill in all required fields";
    } else {
        // Create booking
        try {
            $bookingObj = new Booking();
            $passengerDetails = [
                'name' => $_POST['name'],
                'email' => $_POST['email'],
                'phone' => $_POST['phone'],
                'passengers' => $_POST['passengers'] ?? 1
            ];
            
            $bookingId = $bookingObj->createBooking($userId, $_POST['flight_id'], $passengerDetails);
            
            if ($bookingId) {
                $success = "Booking successful! Your booking ID is #" . $bookingId;
                // Optionally redirect to a confirmation page
                // header("Location: confirmation.php?booking_id=" . $bookingId);
                // exit;
            } else {
                $error = "Failed to create booking. Please try again.";
            }
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

// Always load available flights
try {
    // Get flights with available seats
    $stmt = $pdo->query("SELECT id, flight_number, departure, arrival, date, time, price, available_seats 
                         FROM flights 
                         WHERE available_seats > 0 
                         ORDER BY date, time");
    $flights = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($flights)) {
        $error = "No flights available at this time.";
    }
} catch (Exception $e) {
    error_log("Error loading available flights: " . $e->getMessage());
    $error = "Error loading available flights.";
}

// Add formatDuration function if it doesn't exist
if (!function_exists('formatDuration')) {
    function formatDuration($durationMinutes) {
        if (!is_numeric($durationMinutes)) {
            return "N/A";
        }
        
        $hours = floor($durationMinutes / 60);
        $minutes = $durationMinutes % 60;
        
        if ($hours > 0 && $minutes > 0) {
            return $hours . "h " . $minutes . "m";
        } elseif ($hours > 0) {
            return $hours . "h";
        } else {
            return $minutes . "m";
        }
    }
}

include 'templates/header.php';
?>

<div class="container">
    <h1>Book Your Flight</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($flights)): ?>
        <div class="booking-form-container">
            <h2>Flight Booking</h2>
            <form id="booking-form" method="POST" action="booking.php">
                <div class="form-group">
                    <label for="flight_id">Select a Flight:</label>
                    <select id="flight_id" name="flight_id" class="form-control" required>
                        <option value="">-- Select Flight --</option>
                        <?php foreach ($flights as $flight): ?>
                            <option value="<?php echo htmlspecialchars($flight['id']); ?>">
                                <?php echo htmlspecialchars($flight['flight_number']); ?> 
                                (<?php echo htmlspecialchars($flight['departure']); ?> - <?php echo htmlspecialchars($flight['arrival']); ?>)
                                - <?php echo htmlspecialchars(date('M j, Y', strtotime($flight['date']))); ?> 
                                at <?php echo htmlspecialchars(date('H:i', strtotime($flight['time']))); ?>
                                - $<?php echo htmlspecialchars($flight['price']); ?>
                                - <?php echo htmlspecialchars($flight['available_seats']); ?> seats available
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="passengers">Number of Passengers:</label>
                    <select id="passengers" name="passengers" class="form-control" required>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                
                <h3>Passenger Information</h3>
                
                <div class="form-group">
                    <label for="name">Full Name:</label>
                    <input type="text" id="name" name="name" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <input type="submit" class="btn-primary" value="Book Flight">
                </div>
            </form>
        </div>
    <?php endif; ?>
</div>

<style>
    .container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
    }
    
    h1 {
        color: #333;
        margin-bottom: 30px;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    .booking-form-container {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 25px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-top: 30px;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 8px;
        font-weight: 600;
    }
    
    .form-group input,
    .form-group select,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    
    .btn-primary {
        background-color: #4CAF50;
        color: white;
        padding: 12px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
    }
    
    .btn-primary:hover {
        background-color: #45a049;
    }
    
    h2, h3 {
        margin-bottom: 20px;
        color: #333;
    }
    
    h3 {
        margin-top: 30px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }
    
    @media (max-width: 768px) {
        .container {
            padding: 10px;
        }
        
        .booking-form-container {
            padding: 15px;
        }
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Add Font Awesome if not already included
        if (!document.querySelector('link[href*="font-awesome"]')) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
            document.head.appendChild(link);
        }
        
        const form = document.getElementById('booking-form');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                // Basic form validation
                const name = document.getElementById('name').value.trim();
                const email = document.getElementById('email').value.trim();
                const phone = document.getElementById('phone').value.trim();
                const flightId = document.getElementById('flight_id').value;
                
                if (!flightId || !name || !email || !phone) {
                    e.preventDefault();
                    alert('Please fill in all required fields');
                    return false;
                }
                
                // Email validation
                const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailPattern.test(email)) {
                    e.preventDefault();
                    alert('Please enter a valid email address');
                    return false;
                }
                
                // Basic phone validation
                const phonePattern = /^[0-9\+\-\(\)\s]{7,20}$/;
                if (!phonePattern.test(phone)) {
                    e.preventDefault();
                    alert('Please enter a valid phone number');
                    return false;
                }
            });
        }
    });
</script>

<?php include 'templates/footer.php'; ?>
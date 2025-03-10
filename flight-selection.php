<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$userId = $_SESSION['user_id'];
$error = '';
$flights = [];

// If direct POST from search.php, store the selected flight and redirect to passenger details
if (isset($_POST['select_flight']) && !empty($_POST['flight_id'])) {
    $_SESSION['selected_flight_id'] = $_POST['flight_id'];
    $_SESSION['selected_flight_price'] = $_POST['price'];
    header('Location: passenger-details.php');
    exit;
}

// If form is submitted from this page, store the flight ID in session and redirect to booking details
if (isset($_POST['continue_to_details'])) {
    if (!empty($_POST['flight_id'])) {
        $_SESSION['selected_flight_id'] = $_POST['flight_id'];
        $_SESSION['selected_flight_price'] = $_POST['price'];
        header('Location: passenger-details.php');
        exit;
    } else {
        $error = 'Please select a flight to continue.';
    }
}

// Retrieve flights data from API
try {
    $apiClient = new ApiClient();
    $flights = $apiClient->getAvailableFlights();
    
    if (empty($flights)) {
        $error = "No flights available at this time.";
    }
} catch (Exception $e) {
    $error = "Error loading flights from API: " . $e->getMessage();
    error_log("Error loading flights from API: " . $e->getMessage());
}

include 'templates/header.php';
?>

<div class="container">
    <h1>Select Your Flight</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    
    <?php if (!empty($flights)): ?>
    <div class="flight-selection-container">
        <h2>Available Flights</h2>
        <form id="flight-selection-form" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
            <div class="flights-grid">
                <?php foreach ($flights as $index => $flight): ?>
                <div class="flight-card">
                    <div class="flight-header">
                        <span class="flight-number"><?php echo htmlspecialchars($flight['flight_number']); ?></span>
                        <span class="flight-airline"><?php echo htmlspecialchars($flight['airline']); ?></span>
                    </div>
                    <div class="flight-details">
                        <div class="flight-route">
                            <div class="departure">
                                <div class="city"><?php echo htmlspecialchars($flight['departure']); ?></div>
                                <div class="time"><?php echo htmlspecialchars($flight['departure_time']); ?></div>
                            </div>
                            <div class="route-info">
                                <div class="duration"><?php echo isset($flight['duration']) ? formatDuration($flight['duration']) : ''; ?></div>
                                <div class="line"></div>
                                <div class="plane-icon"><i class="fas fa-plane"></i></div>
                            </div>
                            <div class="arrival">
                                <div class="city"><?php echo htmlspecialchars($flight['arrival']); ?></div>
                                <div class="time"><?php echo htmlspecialchars($flight['arrival_time']); ?></div>
                            </div>
                        </div>
                    </div>
                    <div class="flight-price">
                        $<?php echo htmlspecialchars($flight['price']); ?>
                    </div>
                    <div class="flight-select">
                        <input type="radio" id="flight-<?php echo $index; ?>" name="flight_id" value="<?php echo htmlspecialchars($flight['id']); ?>" 
                            data-price="<?php echo htmlspecialchars($flight['price']); ?>">
                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($flight['price']); ?>">
                        <label for="flight-<?php echo $index; ?>" class="select-button">Select</label>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="form-actions">
                <button type="submit" name="continue_to_details" class="btn-primary">Continue to Passenger Details</button>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="no-flights">
        <p>No flights are available. Please try again later.</p>
        <a href="search.php" class="btn-primary">Search for Flights</a>
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
    
    .flight-selection-container {
        margin-top: 30px;
    }
    
    .flights-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .flight-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        padding: 20px;
        position: relative;
    }
    
    .flight-card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .flight-header {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #eee;
    }
    
    .flight-number {
        font-weight: bold;
        color: #333;
    }
    
    .flight-airline {
        color: #666;
    }
    
    .flight-details {
        margin-bottom: 20px;
    }
    
    .flight-route {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .route-info {
        flex: 1;
        text-align: center;
        position: relative;
        padding: 0 15px;
    }
    
    .line {
        height: 2px;
        background-color: #ddd;
        margin: 15px 0;
    }
    
    .plane-icon {
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        background-color: white;
        padding: 5px;
        border-radius: 50%;
        color: #3498db;
    }
    
    .departure, .arrival {
        width: 80px;
    }
    
    .city {
        font-weight: 600;
    }
    
    .time {
        color: #666;
        font-size: 0.9em;
        margin-top: 5px;
    }
    
    .duration {
        font-size: 0.8em;
        color: #777;
    }
    
    .flight-price {
        font-size: 1.5em;
        font-weight: bold;
        color: #4CAF50;
        text-align: center;
        margin: 15px 0;
    }
    
    .flight-select {
        text-align: center;
    }
    
    .flight-select input[type="radio"] {
        display: none;
    }
    
    .select-button {
        display: inline-block;
        padding: 8px 15px;
        background-color: #f1f1f1;
        color: #333;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    .flight-select input[type="radio"]:checked + .select-button {
        background-color: #4CAF50;
        color: white;
    }
    
    .form-actions {
        text-align: center;
        margin-top: 30px;
    }
    
    .btn-primary {
        background-color: #4CAF50;
        color: white;
        padding: 12px 25px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    
    .btn-primary:hover {
        background-color: #45a049;
    }
    
    .no-flights {
        text-align: center;
        padding: 50px 0;
    }
    
    @media (max-width: 768px) {
        .flights-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const flightCards = document.querySelectorAll('.flight-card');
    
    flightCards.forEach(card => {
        const radio = card.querySelector('input[type="radio"]');
        
        card.addEventListener('click', function() {
            // Clear previous selections
            document.querySelectorAll('input[name="flight_id"]').forEach(input => {
                input.checked = false;
            });
            
            // Select this flight
            radio.checked = true;
            
            // Update visual cue
            document.querySelectorAll('.flight-card').forEach(c => {
                c.classList.remove('selected');
            });
            card.classList.add('selected');
        });
    });
    
    // Add font awesome
    const link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css';
    document.head.appendChild(link);
});
</script>

<?php include 'templates/footer.php'; ?>
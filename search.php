<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';

$flights = [];
$error = '';
$searchPerformed = false;

// Fix the REQUEST_METHOD error by checking if it exists first
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $departure = $_POST['departure'] ?? '';
    $arrival = $_POST['arrival'] ?? '';
    $date = $_POST['date'] ?? '';
    $searchPerformed = true;

    if (empty($departure) || empty($arrival) || empty($date)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $apiClient = new ApiClient();
            $flights = $apiClient->searchFlights($departure, $arrival, $date);
            
            if (empty($flights)) {
                $error = 'No flights found for your search criteria.';
            }
        } catch (Exception $e) {
            $error = 'An error occurred while searching for flights: ' . $e->getMessage();
            error_log($e->getMessage());
        }
    }
} else {
    // If no search performed, load all available flights
    try {
        $apiClient = new ApiClient();
        $flights = $apiClient->getAvailableFlights();
        
        if (empty($flights)) {
            $error = 'No available flights found. Please try a different search.';
        }
    } catch (Exception $e) {
        $error = 'An error occurred while loading flights: ' . $e->getMessage();
        error_log($e->getMessage());
    }
}

// Common airports list for autocomplete
$commonAirports = [
    ['name' => 'Atlanta International Airport', 'code' => 'ATL'],
    ['name' => 'Beijing Capital International Airport', 'code' => 'PEK'],
    ['name' => 'Dubai International Airport', 'code' => 'DXB'],
    ['name' => 'Los Angeles International Airport', 'code' => 'LAX'],
    ['name' => 'Tokyo Haneda Airport', 'code' => 'HND'],
    ['name' => 'Chicago O\'Hare International Airport', 'code' => 'ORD'],
    ['name' => 'London Heathrow Airport', 'code' => 'LHR'],
    ['name' => 'Shanghai Pudong International Airport', 'code' => 'PVG'],
    ['name' => 'Paris Charles de Gaulle Airport', 'code' => 'CDG'],
    ['name' => 'Amsterdam Airport Schiphol', 'code' => 'AMS'],
    ['name' => 'Dallas/Fort Worth International Airport', 'code' => 'DFW'],
    ['name' => 'Hong Kong International Airport', 'code' => 'HKG'],
    ['name' => 'Frankfurt Airport', 'code' => 'FRA'],
    ['name' => 'Denver International Airport', 'code' => 'DEN'],
    ['name' => 'Seoul Incheon International Airport', 'code' => 'ICN'],
    ['name' => 'Singapore Changi Airport', 'code' => 'SIN'],
    ['name' => 'New York JFK International Airport', 'code' => 'JFK'],
    ['name' => 'San Francisco International Airport', 'code' => 'SFO'],
    ['name' => 'Sydney Airport', 'code' => 'SYD'],
    ['name' => 'Miami International Airport', 'code' => 'MIA'],
    ['name' => 'Toronto Pearson International Airport', 'code' => 'YYZ'],
    ['name' => 'Barcelona–El Prat Airport', 'code' => 'BCN'],
    ['name' => 'Orlando International Airport', 'code' => 'MCO'],
    ['name' => 'New Delhi Indira Gandhi International Airport', 'code' => 'DEL'],
    ['name' => 'Mumbai Chhatrapati Shivaji Maharaj International Airport', 'code' => 'BOM'],
    ['name' => 'Istanbul Airport', 'code' => 'IST'],
    ['name' => 'Mexico City International Airport', 'code' => 'MEX'],
    ['name' => 'Munich Airport', 'code' => 'MUC'],
    ['name' => 'Moscow Sheremetyevo International Airport', 'code' => 'SVO'],
    ['name' => 'São Paulo–Guarulhos International Airport', 'code' => 'GRU']
];

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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="assets/css/main.css">
    <link rel="stylesheet" href="assets/css/responsive.css">
    <title>Flight Search</title>
    <!-- Add jQuery UI CSS -->
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <style>
        .search-form {
            background-color: #f8f9fa;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        
        button[type="submit"] {
            background-color: #4CAF50;
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        
        .error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .no-results {
            background-color: #fff3cd;
            color: #856404;
            padding: 20px;
            border-radius: 4px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .flight-results-title {
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .flights-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .flight-card {
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: all 0.3s ease;
            border: 1px solid #eaeaea;
        }
        
        .flight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .flight-header {
            padding: 15px;
            background-color: #f8f9fa;
            display: flex;
            justify-content: space-between;
            border-bottom: 1px solid #eaeaea;
        }
        
        .flight-number {
            font-weight: bold;
            color: #2c3e50;
        }
        
        .flight-airline {
            color: #7f8c8d;
        }
        
        .flight-details {
            padding: 15px;
        }
        
        .route-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            text-align: center;
        }
        
        .departure, .arrival {
            flex: 1;
        }
        
        .time {
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .airport {
            color: #7f8c8d;
        }
        
        .duration-display {
            background-color: #e8f4fd;
            color: #0078d4;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
        }
        
        .flight-meta {
            background-color: #f8f9fa;
            padding: 15px;
            border-top: 1px solid #eaeaea;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .price {
            font-size: 1.2rem;
            font-weight: bold;
            color: #27ae60;
        }
        
        .seats {
            color: #7f8c8d;
            font-size: 0.9rem;
        }
        
        .book-btn {
            background-color: #4CAF50;
            color: white;
            padding: 8px 15px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background-color 0.3s;
        }
        
        .book-btn:hover {
            background-color: #45a049;
            color: white;
        }
        
        .sold-out {
            background-color: #f8d7da;
            color: #721c24;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 0.9rem;
        }
        
        /* Separator between departure and arrival */
        .route-separator {
            display: flex;
            align-items: center;
            margin: 0 15px;
        }
        
        .route-line {
            flex-grow: 1;
            height: 2px;
            background: #ddd;
            position: relative;
        }
        
        .route-icon {
            margin: 0 10px;
            color: #7f8c8d;
            font-size: 1.2rem;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .flights-grid {
                grid-template-columns: 1fr;
            }
            
            .form-row {
                flex-direction: column;
                gap: 10px;
            }
            
            .form-group {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="search-form">
            <form method="POST" action="search.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="departure">From</label>
                        <input type="text" id="departure" name="departure" placeholder="City or Airport" value="<?php echo htmlspecialchars($departure ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="arrival">To</label>
                        <input type="text" id="arrival" name="arrival" placeholder="City or Airport" value="<?php echo htmlspecialchars($arrival ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="date">Date</label>
                        <input type="date" id="date" name="date" value="<?php echo htmlspecialchars($date ?? ''); ?>" required>
                    </div>
                </div>
                
                <button type="submit">Search Flights</button>
            </form>
        </div>

        <?php if (!empty($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php elseif (empty($flights)): ?>
            <div class="no-results">No flights found. Please try different search criteria.</div>
        <?php else: ?>
            <h2 class="flight-results-title">
                <?php if ($searchPerformed): ?>
                    Search Results
                <?php else: ?>
                    Available Flights
                <?php endif; ?>
            </h2>
            <div class="flights-grid">
                <?php foreach ($flights as $flight): ?>
                    <div class="flight-card">
                        <div class="flight-header">
                            <div class="flight-number"><?php echo htmlspecialchars($flight['flight_number']); ?></div>
                            <div class="flight-airline"><?php echo htmlspecialchars($flight['airline']); ?></div>
                            <?php if (isset($flight['duration'])): ?>
                                <div class="duration-display"><?php echo formatDuration($flight['duration']); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="flight-details">
                            <div class="route-info">
                                <div class="departure">
                                    <div class="time"><?php echo htmlspecialchars(date('H:i', strtotime($flight['time']))); ?></div>
                                    <div class="airport"><?php echo htmlspecialchars($flight['departure']); ?></div>
                                </div>
                                
                                <div class="route-separator">
                                    <div class="route-line"></div>
                                    <div class="route-icon"><i class="fas fa-plane"></i></div>
                                    <div class="route-line"></div>
                                </div>
                                
                                <div class="arrival">
                                    <?php 
                                    $arrivalTime = strtotime($flight['time']) + ($flight['duration'] * 60);
                                    ?>
                                    <div class="time"><?php echo date('H:i', $arrivalTime); ?></div>
                                    <div class="airport"><?php echo htmlspecialchars($flight['arrival']); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="flight-meta">
                            <div class="price">$<?php echo htmlspecialchars(number_format($flight['price'], 2)); ?></div>
                            <?php if (isset($flight['available_seats']) && $flight['available_seats'] > 0): ?>
                                <div class="seats"><?php echo htmlspecialchars($flight['available_seats']); ?> seats left</div>
                                <!-- Instead of passing flight ID, link to booking.php -->
                                <a href="booking.php" class="book-btn">Book Now</a>
                            <?php else: ?>
                                <div class="sold-out">Sold Out</div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Add jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    
    <script>
    // Autocomplete for departure and arrival fields
    $(function() {
        var airports = <?php echo json_encode($commonAirports); ?>;
        var airportNames = airports.map(function(airport) {
            return airport.name + " (" + airport.code + ")";
        });
        
        $("#departure, #arrival").autocomplete({
            source: airportNames,
            minLength: 2
        });
        
        // Add plane icon to route
        $(".route-info").append('<i class="fas fa-plane" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);"></i>');
    });
    </script>
    
    <!-- Add Font Awesome -->
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</body>
</html>

<?php include 'templates/footer.php'; ?>
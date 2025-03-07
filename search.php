<?php

require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';

session_start();

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
            $error = 'No flights available at this time.';
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
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .form-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
            gap: 10px;
        }
        
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
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
            padding: 12px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        button[type="submit"]:hover {
            background-color: #45a049;
        }
        
        .error {
            color: #f44336;
            margin-bottom: 15px;
        }
        
        .flights-grid {
            margin-top: 30px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
        }
        
        .flight-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        
        .flight-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }
        
        .flight-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }
        
        .flight-number {
            font-weight: bold;
            font-size: 1.2em;
        }
        
        .flight-airline {
            color: #555;
        }
        
        /* Adjust the duration display for header positioning */
        .duration-display {
            color: #555;
            font-size: 0.9em;
            padding: 0 10px;
            text-align: center;
            border-left: 1px solid #eee;
            border-right: 1px solid #eee;
        }
        
        .flight-details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        
        .route-info {
            flex: 1;
        }
        
        .time {
            font-weight: bold;
            font-size: 1.1em;
        }
        
        .airport {
            margin-top: 5px;
            color: #555;
        }
        
        .flight-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid #eee;
        }
        
        .price {
            font-weight: bold;
            font-size: 1.2em;
            color: #4CAF50;
        }
        
        .seats {
            color: #555;
        }
        
        .book-btn {
            display: block;
            width: 100%;
            background-color: #2196F3;
            color: white;
            text-align: center;
            padding: 10px;
            border: none;
            border-radius: 4px;
            margin-top: 10px;
            cursor: pointer;
            text-decoration: none;
        }
        
        .book-btn:hover {
            background-color: #0b7dda;
        }
        
        .no-results {
            text-align: center;
            padding: 20px;
            margin-top: 20px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .duration-display {
            color: #555;
            font-size: 0.9em;
            margin-top: 5px;
            text-align: center;
        }
        
        /* Custom autocomplete styles */
        .ui-autocomplete {
            max-height: 300px;
            overflow-y: auto;
            overflow-x: hidden;
            z-index: 9999 !important;
        }
        
        .ui-menu-item {
            padding: 5px 10px;
            font-size: 14px;
        }
        
        .ui-menu-item .ui-menu-item-wrapper.ui-state-active {
            background: #f0f0f0 !important;
            border: none !important;
            color: #333 !important;
        }
        
        /* Input selection toggle */
        .input-toggle {
            display: flex;
            justify-content: center;
            margin-bottom: 15px;
        }
        
        .toggle-btn {
            padding: 8px 15px;
            margin: 0 5px;
            border: 1px solid #ddd;
            background-color: #f8f9fa;
            border-radius: 4px;
            cursor: pointer;
        }
        
        .toggle-btn.active {
            background-color: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .airport-select {
            width: 100%;
        }

        .flight-results-title {
            margin-top: 30px;
            margin-bottom: 15px;
            text-align: center;
            font-size: 1.5em;
            color: #333;
        }
    </style>
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container">
        <h1>Search Flights</h1>
        <div class="search-form">
            <div class="input-toggle">
                <button type="button" class="toggle-btn active" data-input-type="text">Enter City/Airport Name</button>
                <button type="button" class="toggle-btn" data-input-type="select">Select from List</button>
            </div>
            
            <form method="POST" action="search.php">
                <div class="form-row">
                    <div class="form-group">
                        <label for="departure">Departure</label>
                        <input type="text" id="departure" name="departure" class="airport-input" placeholder="Departure city or airport" value="<?php echo htmlspecialchars($departure ?? ''); ?>" required>
                        <select id="departure_select" name="departure" class="airport-select" style="display:none;">
                            <?php foreach ($commonAirports as $airport): ?>
                                <option value="<?php echo htmlspecialchars($airport['code']); ?>"><?php echo htmlspecialchars($airport['name']); ?> (<?php echo htmlspecialchars($airport['code']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="arrival">Arrival</label>
                        <input type="text" id="arrival" name="arrival" class="airport-input" placeholder="Arrival city or airport" value="<?php echo htmlspecialchars($arrival ?? ''); ?>" required>
                        <select id="arrival_select" name="arrival" class="airport-select" style="display:none;">
                            <?php foreach ($commonAirports as $airport): ?>
                                <option value="<?php echo htmlspecialchars($airport['code']); ?>"><?php echo htmlspecialchars($airport['name']); ?> (<?php echo htmlspecialchars($airport['code']); ?>)</option>
                            <?php endforeach; ?>
                        </select>
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
                            <div class="duration-display">
                                <?php 
                                    $duration = isset($flight['duration']) ? $flight['duration'] : 0;
                                    $hours = floor($duration / 60);
                                    $minutes = $duration % 60;
                                    echo $hours . 'h ' . $minutes . 'm';
                                ?>
                            </div>
                            <div class="flight-airline"><?php echo htmlspecialchars($flight['airline'] ?? 'Airline'); ?></div>
                        </div>
                        
                        <div class="flight-details">
                            <div class="route-info">
                                <div class="time"><?php echo htmlspecialchars($flight['departure_time'] ?? ''); ?></div>
                                <div class="airport"><?php echo htmlspecialchars($flight['departure']); ?></div>
                            </div>
                            
                            <div class="route-info">
                                <div class="time"><?php echo htmlspecialchars($flight['arrival_time'] ?? ''); ?></div>
                                <div class="airport"><?php echo htmlspecialchars($flight['arrival']); ?></div>
                            </div>
                        </div>
                        
                        <div class="flight-meta">
                            <div class="price"><?php echo htmlspecialchars('$' . number_format($flight['price'], 2)); ?></div>
                            <div class="seats"><?php echo htmlspecialchars($flight['available_seats'] ?? ''); ?> seats left</div>
                        </div>
                        
                        <a href="booking.php?flight_id=<?php echo htmlspecialchars($flight['id']); ?>" class="book-btn">Book Now</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <?php include 'templates/footer.php'; ?>
    
    <!-- Add jQuery and jQuery UI -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>
    
    <script>
        // Set minimum date to today
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('date');
            if (dateInput) {
                const today = new Date().toISOString().split('T')[0];
                dateInput.setAttribute('min', today);
                
                // Initialize with today's date if no date is set
                if (!dateInput.value) {
                    dateInput.value = today;
                }
            }
        });
        
        // Initialize airport autocomplete and toggle functionality
        $(document).ready(function() {
            // Prepare data for autocomplete
            const airports = <?php echo json_encode($commonAirports); ?>;
            const airportNames = airports.map(airport => airport.name + " (" + airport.code + ")");
            
            // Initialize autocomplete for departure and arrival inputs
            $(".airport-input").autocomplete({
                source: airportNames,
                minLength: 2,
                select: function(event, ui) {
                    // Extract the IATA code from the selection
                    const match = ui.item.value.match(/\(([^)]+)\)/);
                    if (match && match[1]) {
                        // For API use, we'll store both name and code
                        $(this).attr('data-code', match[1]);
                    }
                }
            });
            
            // Toggle between text input and select dropdown
            $(".toggle-btn").click(function() {
                const inputType = $(this).data("input-type");
                $(".toggle-btn").removeClass("active");
                $(this).addClass("active");
                
                if (inputType === "text") {
                    $(".airport-input").show();
                    $(".airport-select").hide();
                } else {
                    $(".airport-input").hide();
                    $(".airport-select").show();
                }
            });
        });
    </script>
</body>
</html>
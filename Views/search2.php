<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'inc/api.php';
require_once 'classes/ApiClient.php';

$flights = [];
$error = '';
$searchPerformed = false;
$pagination = null;

// Get search parameters
$from = $_GET['from'] ?? ($_POST['from'] ?? '');
$to = $_GET['to'] ?? ($_POST['to'] ?? '');
$departDate = $_GET['departDate'] ?? ($_POST['departDate'] ?? '');
$returnDate = $_GET['returnDate'] ?? ($_POST['returnDate'] ?? '');
$cabinClass = $_GET['cabinClass'] ?? ($_POST['cabinClass'] ?? 'economy');
$adults = $_GET['adults'] ?? ($_POST['adults'] ?? 1);
$children = $_GET['children'] ?? ($_POST['children'] ?? 0);
$infants = $_GET['infants'] ?? ($_POST['infants'] ?? 0);
$tripType = $_GET['tripType'] ?? ($_POST['tripType'] ?? 'oneway');
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * 100; // 100 is the max limit per page for the free plan

// Check if a search was performed
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' || !empty($from) && !empty($to) && !empty($departDate)) {
    $searchPerformed = true;

    // Try API search first
    try {
        $apiClient = new ApiClient();
        $result = $apiClient->searchFlightsEnhanced($from, $to, $departDate, $offset);
        $flights = $result['flights'];
        $pagination = $result['pagination'];
        
        if (empty($flights)) {
            $error = "No flights found matching your criteria. Please try different search parameters.";
        }
    } catch (Exception $e) {
        error_log("API search error: " . $e->getMessage());
        // API failed, fall back to database
        try {
            $flights = searchFlightsFromDatabase($from, $to, $departDate);
            if (empty($flights)) {
                $error = "No flights found. Please try different search parameters.";
            }
        } catch (Exception $dbEx) {
            $error = "Search error: " . $dbEx->getMessage();
        }
    }
} else {
    // Always load flights on page load (even if no search performed)
    try {
        $apiClient = new ApiClient();
        $result = $apiClient->getAvailableFlights();
        
        if (is_array($result) && isset($result['flights'])) {
            $flights = $result['flights'];
            $pagination = $result['pagination'] ?? null;
        } else {
            $flights = $result; // In case it directly returns an array of flights
        }
        
        if (empty($flights)) {
            $flights = getFeaturedFlightsFromDatabase();
            if (empty($flights)) {
                $error = "Unable to load featured flights. Please try searching for specific routes.";
            }
        }
    } catch (Exception $e) {
        error_log("Error fetching featured flights: " . $e->getMessage());
        $flights = getFeaturedFlightsFromDatabase();
    }
}

// Database search fallback function
function searchFlightsFromDatabase($from, $to, $date) {
    global $pdo;
    
    try {
        // Search for flights in the local database
        $stmt = $pdo->prepare(
            "SELECT * FROM flights 
             WHERE departure LIKE ? AND arrival LIKE ? AND date = ?
             ORDER BY time ASC LIMIT 100"
        );
        
        // Use wildcards for partial matching on departure/arrival
        $stmt->execute(["%$from%", "%$to%", date('Y-m-d', strtotime($date))]);
        $dbFlights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format database flights to match API format
        $formattedFlights = [];
        foreach ($dbFlights as $flight) {
            // Convert database fields to match API format
            $formattedFlights[] = [
                'id' => $flight['id'],
                'flight_number' => $flight['flight_number'],
                'airline' => $flight['airline'] ?? 'Unknown Airline',
                'departureAirport' => $flight['departure'],
                'departureTime' => $flight['time'],
                'arrivalAirport' => $flight['arrival'],
                'arrivalTime' => calculateArrivalTime($flight['time'], $flight['duration_minutes']),
                'duration' => floor($flight['duration_minutes'] / 60) . 'h ' . ($flight['duration_minutes'] % 60) . 'm',
                'price' => $flight['price'],
                'stops' => $flight['stops'] ?? 0,
                'source' => 'database'
            ];
        }
        return $formattedFlights;
    } catch (Exception $e) {
        error_log("Error searching flights from database: " . $e->getMessage());
        throw $e;
    }
}

function getFeaturedFlightsFromDatabase() {
    global $pdo;
    
    try {
        // Get upcoming flights from database
        $stmt = $pdo->query(
            "SELECT * FROM flights 
             WHERE date >= CURDATE() 
             ORDER BY featured DESC, date ASC 
             LIMIT 100"
        );
        $dbFlights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the flights 
        $formattedFlights = [];
        foreach ($dbFlights as $flight) {
            $formattedFlights[] = [
                'id' => $flight['id'],
                'flight_number' => $flight['flight_number'],
                'airline' => $flight['airline'] ?? 'Unknown Airline',
                'departureAirport' => $flight['departure'],
                'departureTime' => $flight['time'],
                'arrivalAirport' => $flight['arrival'],
                'arrivalTime' => calculateArrivalTime($flight['time'], $flight['duration_minutes']),
                'duration' => floor($flight['duration_minutes'] / 60) . 'h ' . ($flight['duration_minutes'] % 60) . 'm',
                'price' => $flight['price'],
                'stops' => $flight['stops'] ?? 0,
                'source' => 'database'
            ];
        }
        
        return $formattedFlights;
    } catch (Exception $e) {
        error_log("Error getting featured flights: " . $e->getMessage());
        throw $e;
    }
}

// Helper functions for formatting
function calculateArrivalTime($departureTime, $durationMinutes) {
    $departure = new DateTime($departureTime);
    $departure->add(new DateInterval('PT' . $durationMinutes . 'M'));
    return $departure->format('H:i');
}

/**
 * Get a list of major airports worldwide sorted alphabetically
 * @return array Array of airports with name and IATA code
 */
function getAirports() {
    return [
        ['name' => 'Amsterdam Airport Schiphol', 'code' => 'AMS'],
        ['name' => 'Athens International Airport', 'code' => 'ATH'],
        ['name' => 'Auckland Airport', 'code' => 'AKL'],
        ['name' => 'Bangkok Suvarnabhumi Airport', 'code' => 'BKK'],
        ['name' => 'Barcelona–El Prat Airport', 'code' => 'BCN'],
        ['name' => 'Beijing Capital International Airport', 'code' => 'PEK'],
        ['name' => 'Berlin Brandenburg Airport', 'code' => 'BER'],
        ['name' => 'Boston Logan International Airport', 'code' =>'BOS'],
        ['name' => 'Cairo International Airport', 'code' => 'CAI'],
        ['name' => 'Cancún International Airport', 'code' => 'CUN'],
        ['name' => 'Cape Town International Airport', 'code' => 'CPT'],
        ['name' => 'Charles de Gaulle Airport (Paris)', 'code' => 'CDG'],
        ['name' => 'Changi Airport (Singapore)', 'code' => 'SIN'],
        ['name' => 'Chicago O\'Hare International Airport', 'code' => 'ORD'],
        ['name' => 'Copenhagen Airport', 'code' => 'CPH'],
        ['name' => 'Dallas/Fort Worth International Airport', 'code' => 'DFW'],
        ['name' => 'Denver International Airport', 'code' => 'DEN'],
        ['name' => 'Dubai International Airport', 'code' => 'DXB'],
        ['name' => 'Dublin Airport', 'code' => 'DUB'],
        ['name' => 'Frankfurt Airport', 'code' => 'FRA'],
        ['name' => 'Geneva Airport', 'code' => 'GVA'],
        ['name' => 'Hamad International Airport (Doha)', 'code' => 'DOH'],
        ['name' => 'Haneda Airport (Tokyo)', 'code' => 'HND'],
        ['name' => 'Hartsfield–Jackson Atlanta International Airport', 'code' => 'ATL'],
        ['name' => 'Helsinki Airport', 'code' => 'HEL'],
        ['name' => 'Hong Kong International Airport', 'code' => 'HKG'],
        ['name' => 'Incheon International Airport (Seoul)', 'code' => 'ICN'],
        ['name' => 'Istanbul Airport', 'code' => 'IST'],
        ['name' => 'John F. Kennedy International Airport (New York)', 'code' => 'JFK'],
        ['name' => 'Kuala Lumpur International Airport', 'code' => 'KUL'],
        ['name' => 'Las Vegas Harry Reid International Airport', 'code' => 'LAS'],
    ];
}

// Function to render flight card
function renderFlightCard($flight) {
    $flightId = $flight['id'] ?? '';
    $airline = $flight['airline'] ?? 'Unknown Airline';
    $flightNumber = $flight['flight_number'] ?? 'N/A';
    $departureAirport = $flight['departureAirport'] ?? 'N/A';
    $departureTime = $flight['departureTime'] ?? '00:00';
    $arrivalAirport = $flight['arrivalAirport'] ?? 'N/A';
    $arrivalTime = $flight['arrivalTime'] ?? '00:00';
    $duration = $flight['duration'] ?? 'N/A';
    $stops = $flight['stops'] ?? 0;
    $price = $flight['price'] ?? 0;
    
    $stopsText = $stops === 0 ? 'Non-stop' : ($stops === 1 ? '1 Stop' : $stops . ' Stops');
    $stopsClass = $stops === 0 ? 'bg-green-100 text-green-800' : ($stops === 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
    
    return <<<HTML
    <div class="flight-card bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-all">
        <div class="flex justify-between items-center mb-3 pb-3 border-b border-gray-100">
            <div class="flex items-center">
                <div class="w-8 h-8 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center mr-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5-1.429A1 1 0 009 15.571V11.43a1 1 0 01.725-.962l5-1.429a1 1 0 001.17-1.409l-7-14z"></path>
                    </svg>
                </div>
                <div>
                    <span class="text-sm font-medium text-gray-900">{$airline}</span>
                    <span class="text-xs text-gray-500 ml-2">{$flightNumber}</span>
                </div>
            </div>
            <span class="px-2 py-1 text-xs font-medium rounded-full {$stopsClass}">{$stopsText}</span>
        </div>
        
        <div class="flex justify-between mb-4">
            <div class="text-center">
                <div class="text-xl font-bold">{$departureTime}</div>
                <div class="text-sm text-gray-500">{$departureAirport}</div>
            </div>
            
            <div class="flex-1 flex flex-col items-center justify-center px-4">
                <div class="w-full h-[1px] bg-gray-300 relative">
                    <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-blue-500 rounded-full"></div>
                </div>
                <span class="text-xs text-gray-500 mt-1">{$duration}</span>
            </div>
            
            <div class="text-center">
                <div class="text-xl font-bold">{$arrivalTime}</div>
                <div class="text-sm text-gray-500">{$arrivalAirport}</div>
            </div>
        </div>
        
        <div class="flex justify-between items-center">
            <div class="text-lg font-bold text-green-600">\${$price}</div>
            <form action="passenger-details.php" method="POST">
                <input type="hidden" name="flight_id" value="{$flightId}">
                <input type="hidden" name="price" value="{$price}">
                <button type="submit" name="select_flight" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition-colors">
                    Select Flight
                </button>
            </form>
        </div>
    </div>
    HTML;
}

// Include header
include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <!-- Search Form Section -->
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-xl p-6 shadow-lg mb-8">
        <h2 class="text-2xl font-bold text-white mb-6">Find Your Flight</h2>
        
        <form action="search2.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <!-- From Airport -->
            <div>
                <label for="from" class="block text-sm font-medium text-white mb-1">From</label>
                <select id="from" name="from" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                    <option value="">Select departure airport</option>
                    <?php foreach (getAirports() as $airport): ?>
                        <option value="<?= $airport['code'] ?>" <?= $from === $airport['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($airport['name']) ?> (<?= htmlspecialchars($airport['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- To Airport -->
            <div>
                <label for="to" class="block text-sm font-medium text-white mb-1">To</label>
                <select id="to" name="to" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                    <option value="">Select arrival airport</option>
                    <?php foreach (getAirports() as $airport): ?>
                        <option value="<?= $airport['code'] ?>" <?= $to === $airport['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($airport['name']) ?> (<?= htmlspecialchars($airport['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <!-- Departure Date -->
            <div>
                <label for="departDate" class="block text-sm font-medium text-white mb-1">Departure Date</label>
                <input type="date" id="departDate" name="departDate" value="<?= htmlspecialchars($departDate) ?>" min="<?= date('Y-m-d') ?>" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
            </div>
            
            <!-- Cabin Class -->
            <div>
                <label for="cabinClass" class="block text-sm font-medium text-white mb-1">Cabin Class</label>
                <select id="cabinClass" name="cabinClass" class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                    <option value="economy" <?= $cabinClass === 'economy' ? 'selected' : '' ?>>Economy</option>
                    <option value="premium" <?= $cabinClass === 'premium' ? 'selected' : '' ?>>Premium Economy</option>
                    <option value="business" <?= $cabinClass === 'business' ? 'selected' : '' ?>>Business</option>
                    <option value="first" <?= $cabinClass === 'first' ? 'selected' : '' ?>>First Class</option>
                </select>
            </div>
            
            <!-- Search Button (spans full width on mobile, right-aligned on desktop) -->
            <div class="col-span-1 md:col-span-2 lg:col-span-4 flex justify-end mt-2">
                <button type="submit" class="bg-white hover:bg-gray-100 text-blue-700 font-semibold py-2 px-6 rounded-lg shadow transition-colors flex items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    Search Flights
                </button>
            </div>
        </form>
    </div>
    
    <!-- Results Section -->
    <div class="mb-8">
        <?php if ($searchPerformed): ?>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Search Results</h2>
            <p class="text-gray-600 mb-6">Showing flights from <?= htmlspecialchars($from) ?> to <?= htmlspecialchars($to) ?></p>
        <?php else: ?>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Featured Flights</h2>
            <p class="text-gray-600 mb-6">Explore our best available flight options</p>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($flights)): ?>
            <!-- Flight Results Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php foreach ($flights as $flight): ?>
                    <?= renderFlightCard($flight) ?>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <?php if ($pagination && isset($pagination['total_pages']) && $pagination['total_pages'] > 1): ?>
                <div class="flex justify-center mt-8">
                    <div class="flex space-x-1">
                        <?php if ($page > 1): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                               class="px-4 py-2 bg-white text-blue-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                Previous
                            </a>
                        <?php endif; ?>
                        
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($pagination['total_pages'], $page + 2);
                        
                        if ($startPage > 1) {
                            echo '<span class="px-4 py-2">...</span>';
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                               class="px-4 py-2 <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 border border-gray-300 hover:bg-gray-50' ?> rounded-md">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <?php if ($endPage < $pagination['total_pages']): ?>
                            <span class="px-4 py-2">...</span>
                        <?php endif; ?>
                        
                        <?php if ($page < $pagination['total_pages']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                               class="px-4 py-2 bg-white text-blue-600 border border-gray-300 rounded-md hover:bg-gray-50">
                                Next
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="bg-gray-50 rounded-xl p-8 text-center">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-gray-400 mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <h3 class="text-xl font-semibold text-gray-700 mb-2">No Flights Found</h3>
                <p class="text-gray-500">Try adjusting your search criteria or check back later</p>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Travel Tips Section -->
    <div class="bg-blue-50 rounded-xl p-6 border border-blue-100">
        <h3 class="text-xl font-bold text-blue-800 mb-4">Travel Tips</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="text-blue-600 text-xl mb-2">✓</div>
                <h4 class="font-semibold mb-2">Book Early</h4>
                <p class="text-sm text-gray-600">Booking flights 1-3 months in advance often results in the best prices.</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="text-blue-600 text-xl mb-2">✓</div>
                <h4 class="font-semibold mb-2">Be Flexible</h4>
                <p class="text-sm text-gray-600">Flights on Tuesdays, Wednesdays, and Saturdays are typically cheaper.</p>
            </div>
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="text-blue-600 text-xl mb-2">✓</div>
                <h4 class="font-semibold mb-2">Check Documents</h4>
                <p class="text-sm text-gray-600">Ensure your passport is valid for at least 6 months beyond your travel dates.</p>
            </div>
        </div>
    </div>
</div>

<style>
/* Remove the diagonal card rotation effects to accommodate more cards */
.flight-card {
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.flight-card:hover {
    transform: translateY(-2px);
}

/* Additional styles for compact cards */
.flight-card .text-xs {
    font-size: 0.7rem;
}

.flight-card .text-sm {
    font-size: 0.8rem;
}

.flight-card .text-base {
    font-size: 0.9rem;
}

/* Improve dropdown readability on mobile */
@media (max-width: 640px) {
    select {
        font-size: 16px; /* Prevents iOS zoom on focus */
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add search functionality to dropdowns
    const airportSelects = document.querySelectorAll('#from, #to');
    
    airportSelects.forEach(select => {
        select.addEventListener('input', function() {
            const filter = this.value.toUpperCase();
            const options = this.querySelectorAll('option');
            
            for (let i = 0; i < options.length; i++) {
                const txtValue = options[i].textContent || options[i].innerText;
                if (txtValue.toUpperCase().indexOf(filter) > -1) {
                    options[i].style.display = "";
                } else {
                    options[i].style.display = "none";
                }
            }
        });
    });
    
    // Prevent form submission when pressing Enter in selects
    airportSelects.forEach(select => {
        select.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
            }
        });
    });
});
</script>

<?php include 'templates/footer.php'; ?>
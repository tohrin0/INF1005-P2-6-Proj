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
            // If API fails or returns no results, fall back to database
            $flights = searchFlightsFromDatabase($from, $to, $departDate);
            
            if (empty($flights)) {
                $error = 'No flights found for your search criteria.';
            }
        }
    } catch (Exception $e) {
        error_log("API search error: " . $e->getMessage());
        // API failed, fall back to database
        try {
            $flights = searchFlightsFromDatabase($from, $to, $departDate);
            
            if (empty($flights)) {
                $error = 'No flights found for your search criteria.';
            }
        } catch (Exception $dbEx) {
            $error = 'An error occurred while searching for flights.';
            error_log($dbEx->getMessage());
        }
    }
} else {
    // If no search performed, load featured flights
    try {
        $apiClient = new ApiClient();
        $result = $apiClient->getAvailableFlights();
        if (is_array($result) && isset($result['flights'])) {
            $flights = $result['flights'];
            $pagination = $result['pagination'];
        } else {
            $flights = $result;
        }
        
        if (empty($flights)) {
            // Fall back to database for featured flights
            $flights = getFeaturedFlightsFromDatabase();
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
             WHERE departure LIKE ? 
             AND arrival LIKE ? 
             AND date = ? 
             AND available_seats > 0
             ORDER BY time"
        );
        
        // Use wildcards for partial matching on departure/arrival
        $stmt->execute(["%$from%", "%$to%", date('Y-m-d', strtotime($date))]);
        $dbFlights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format database flights to match API format
        $formattedFlights = [];
        foreach ($dbFlights as $flight) {
            // Extract airport codes if available, otherwise use first 3 letters
            $departureCode = substr($flight['departure'], 0, 3);
            $arrivalCode = substr($flight['arrival'], 0, 3);
            
            $formattedFlights[] = [
                'airline' => $flight['airline'] ?? 'Local Airline',
                'flightNumber' => $flight['flight_number'],
                'departureTime' => $flight['time'],
                'departureAirport' => $departureCode,
                'departureTerminal' => $flight['departure_terminal'] ?? '',
                'departureGate' => $flight['departure_gate'] ?? '',
                'arrivalTime' => calculateArrivalTime($flight['time'], $flight['duration']),
                'arrivalAirport' => $arrivalCode,
                'arrivalTerminal' => $flight['arrival_terminal'] ?? '',
                'arrivalGate' => $flight['arrival_gate'] ?? '',
                'duration' => formatDuration($flight['duration']),
                'durationMinutes' => $flight['duration'],
                'status' => $flight['status'] ?? 'scheduled',
                'stops' => 0, // Assuming direct flights in database
                'aircraft' => $flight['aircraft_type'] ?? '',
                'price' => (float)$flight['price'],
                'date' => $flight['date'],
                'source' => 'database',
                'flight_id' => $flight['id']
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
             AND available_seats > 0 
             ORDER BY date, time 
             LIMIT 10"
        );
        $dbFlights = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Format the flights 
        $formattedFlights = [];
        foreach ($dbFlights as $flight) {
            $departureCode = substr($flight['departure'], 0, 3);
            $arrivalCode = substr($flight['arrival'], 0, 3);
            
            $formattedFlights[] = [
                'airline' => $flight['airline'] ?? 'Featured Airline',
                'flightNumber' => $flight['flight_number'],
                'departureTime' => $flight['time'],
                'departureAirport' => $departureCode,
                'departureTerminal' => $flight['departure_terminal'] ?? '',
                'departureGate' => $flight['departure_gate'] ?? '',
                'arrivalTime' => calculateArrivalTime($flight['time'], $flight['duration']),
                'arrivalAirport' => $arrivalCode,
                'arrivalTerminal' => $flight['arrival_terminal'] ?? '',
                'arrivalGate' => $flight['arrival_gate'] ?? '',
                'duration' => formatDuration($flight['duration']),
                'durationMinutes' => $flight['duration'],
                'status' => $flight['status'] ?? 'scheduled',
                'stops' => 0,
                'aircraft' => $flight['aircraft_type'] ?? '',
                'price' => (float)$flight['price'],
                'date' => $flight['date'],
                'source' => 'database',
                'flight_id' => $flight['id']
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



// Include header
include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-4">
    <!-- Search Form Section -->
    <div class="bg-gradient-to-r from-blue-700 to-indigo-800 rounded-xl p-4 shadow-lg mb-6">
        <h1 class="text-white text-2xl font-bold mb-4">Find Your Flight</h1>
        
        <form method="POST" action="search2.php" class="space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-3">
                <div>
                    <label for="from" class="block text-white font-medium mb-1 text-sm">From</label>
                    <input type="text" id="from" name="from" placeholder="City or Airport" 
                           value="<?php echo htmlspecialchars($from); ?>"
                           class="w-full px-3 py-2 rounded-md text-sm border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                
                <div>
                    <label for="to" class="block text-white font-medium mb-1 text-sm">To</label>
                    <input type="text" id="to" name="to" placeholder="City or Airport" 
                           value="<?php echo htmlspecialchars($to); ?>"
                           class="w-full px-3 py-2 rounded-md text-sm border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>
                
                <div>
                    <label for="departDate" class="block text-white font-medium mb-1 text-sm">Departure Date</label>
                    <input type="date" id="departDate" name="departDate" 
                           value="<?php echo htmlspecialchars($departDate); ?>"
                           class="w-full px-3 py-2 rounded-md text-sm border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                </div>

                <div>
                    <label for="cabinClass" class="block text-white font-medium mb-1 text-sm">Cabin Class</label>
                    <select id="cabinClass" name="cabinClass" class="w-full px-3 py-2 rounded-md text-sm border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
                        <option value="economy" <?php echo $cabinClass === 'economy' ? 'selected' : ''; ?>>Economy</option>
                        <option value="premium" <?php echo $cabinClass === 'premium' ? 'selected' : ''; ?>>Premium Economy</option>
                        <option value="business" <?php echo $cabinClass === 'business' ? 'selected' : ''; ?>>Business</option>
                        <option value="first" <?php echo $cabinClass === 'first' ? 'selected' : ''; ?>>First Class</option>
                    </select>
                </div>

                <div class="self-end">
                    <button type="submit" class="bg-white text-blue-700 w-full font-bold py-2 px-4 rounded-md hover:bg-blue-50 focus:outline-none focus:ring-2 focus:ring-white focus:ring-offset-2 focus:ring-offset-blue-700 transition-colors">
                        <i class="fas fa-search mr-2"></i> Search Flights
                    </button>
                </div>
            </div>
        </form>
    </div>
    
    <!-- Results Section -->
    <?php if ($searchPerformed): ?>
        <div class="mb-6">
            <div class="flex flex-wrap items-center justify-between mb-4">
                <h2 class="text-xl font-bold"><?php 
                    echo !empty($flights) ? 
                        'Found ' . count($flights) . ' of ' . ($pagination['total'] ?? count($flights)) . ' ' . (($pagination['total'] ?? count($flights)) == 1 ? 'flight' : 'flights') : 
                        'Search Results'; 
                ?></h2>
                
                <?php if (!empty($flights)): ?>
                <div class="text-sm text-gray-500">
                    Page <?= $page ?> of <?= ceil(($pagination['total'] ?? count($flights)) / ($pagination['limit'] ?? 100)) ?>
                </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p><?php echo htmlspecialchars($error); ?></p>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($flights)): ?>
                <div class="flight-results grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3">
                    <?php foreach ($flights as $index => $flight): ?>
                        <div class="flight-card bg-white rounded-md shadow-sm overflow-hidden border border-gray-200 hover:shadow-md transition-shadow">
                            <div class="p-2">
                                <!-- Airline & Flight Info Header -->
                                <div class="flex items-center justify-between mb-1">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center mr-1">
                                            <span class="text-xs font-bold"><?php echo htmlspecialchars($flight['airline'][0] ?? 'A'); ?></span>
                                        </div>
                                        <div>
                                            <div class="font-medium text-xs"><?php echo htmlspecialchars($flight['airline']); ?></div>
                                            <div class="text-xs text-gray-500"><?php echo htmlspecialchars($flight['flightNumber']); ?></div>
                                        </div>
                                    </div>
                                    <div class="px-1 py-0.5 rounded text-xs font-medium <?php echo $flight['source'] === 'database' ? 'bg-gray-200 text-gray-700' : 'bg-blue-100 text-blue-700'; ?>">
                                        <?php echo $flight['source'] === 'database' ? 'DB' : 'API'; ?>
                                    </div>
                                </div>
                                
                                <!-- Flight Route -->
                                <div class="flex items-center justify-between mb-2">
                                    <div class="text-center">
                                        <div class="text-sm font-bold"><?php echo htmlspecialchars($flight['departureTime']); ?></div>
                                        <div class="text-xs"><?php echo htmlspecialchars($flight['departureAirport']); ?></div>
                                    </div>
                                    
                                    <div class="flex flex-col items-center flex-1 mx-1">
                                        <div class="text-xs text-gray-500"><?php echo htmlspecialchars($flight['duration']); ?></div>
                                        <div class="relative w-full h-px bg-gray-300 my-1">
                                            <div class="absolute top-1/2 left-0 w-1 h-1 bg-blue-600 rounded-full transform -translate-y-1/2"></div>
                                            <div class="absolute top-1/2 right-0 w-1 h-1 bg-blue-600 rounded-full transform -translate-y-1/2"></div>
                                            <?php if ($flight['stops'] > 0): ?>
                                                <div class="absolute top-1/2 left-1/2 w-1 h-1 bg-yellow-500 rounded-full transform -translate-x-1/2 -translate-y-1/2"></div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-xs <?php echo $flight['stops'] === 0 ? 'text-green-600' : 'text-yellow-600'; ?> font-medium">
                                            <?php 
                                            echo $flight['stops'] === 0 ? 'Direct' : 
                                                ($flight['stops'] === 1 ? '1 Stop' : $flight['stops'] . ' Stops'); 
                                            ?>
                                        </div>
                                    </div>
                                    
                                    <div class="text-center">
                                        <div class="text-sm font-bold"><?php echo htmlspecialchars($flight['arrivalTime']); ?></div>
                                        <div class="text-xs"><?php echo htmlspecialchars($flight['arrivalAirport']); ?></div>
                                    </div>
                                </div>
                                
                                <!-- Price & Booking -->
                                <div class="flex items-center justify-between border-t border-gray-200 pt-1 mt-1">
                                    <div class="text-base font-bold text-blue-600">$<?php echo number_format($flight['price'], 2); ?></div>
                                    <form action="passenger-details.php" method="POST">
                                        <input type="hidden" name="select_flight" value="1">
                                        <input type="hidden" name="flight_id" value="<?php echo htmlspecialchars($flight['flight_id'] ?? ''); ?>">
                                        <input type="hidden" name="price" value="<?php echo htmlspecialchars($flight['price']); ?>">
                                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium py-1 px-2 rounded transition-colors">
                                            Book Now
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination && isset($pagination['total']) && $pagination['total'] > $pagination['limit']): ?>
                    <div class="mt-4 flex justify-center">
                        <div class="inline-flex rounded-md shadow">
                            <?php
                            $totalPages = ceil($pagination['total'] / $pagination['limit']);
                            $maxVisiblePages = 5; // Show maximum 5 page numbers at once
                            
                            // Calculate the range of page numbers to display
                            $startPage = max(1, min($page - floor($maxVisiblePages / 2), $totalPages - $maxVisiblePages + 1));
                            $endPage = min($totalPages, $startPage + $maxVisiblePages - 1);
                            
                            // Previous page button
                            if ($page > 1): 
                            ?>
                                <a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&departDate=<?= urlencode($departDate) ?>&page=<?= ($page - 1) ?>&cabinClass=<?= urlencode($cabinClass) ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Previous</span>
                                    &laquo;
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($startPage > 1): ?>
                                <a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&departDate=<?= urlencode($departDate) ?>&page=1&cabinClass=<?= urlencode($cabinClass) ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    1
                                </a>
                                <?php if ($startPage > 2): ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                        ...
                                    </span>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                <a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&departDate=<?= urlencode($departDate) ?>&page=<?= $i ?>&cabinClass=<?= urlencode($cabinClass) ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?= $i === $page ? 'bg-blue-50 text-blue-600' : 'bg-white text-gray-700 hover:bg-gray-50' ?> text-sm font-medium">
                                    <?= $i ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($endPage < $totalPages): ?>
                                <?php if ($endPage < $totalPages - 1): ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700">
                                        ...
                                    </span>
                                <?php endif; ?>
                                <a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&departDate=<?= urlencode($departDate) ?>&page=<?= $totalPages ?>&cabinClass=<?= urlencode($cabinClass) ?>" 
                                   class="relative inline-flex items-center px-4 py-2 border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                    <?= $totalPages ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?from=<?= urlencode($from) ?>&to=<?= urlencode($to) ?>&departDate=<?= urlencode($departDate) ?>&page=<?= ($page + 1) ?>&cabinClass=<?= urlencode($cabinClass) ?>" 
                                   class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-500 hover:bg-gray-50">
                                    <span class="sr-only">Next</span>
                                    &raquo;
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Pagination Info -->
                    <div class="mt-2 text-center text-sm text-gray-500">
                        Showing <?= (($page - 1) * $pagination['limit']) + 1 ?> - <?= min($page * $pagination['limit'], $pagination['total']) ?> of <?= $pagination['total'] ?> flights
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-md text-center">
                    No flights found matching your criteria. Please try different search parameters.
                </div>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="text-center py-10">
            <div class="text-2xl font-bold mb-4">Search for Available Flights</div>
            <p class="text-gray-500 max-w-xl mx-auto">
                Enter your departure and destination cities along with your preferred travel date to find available flights.
            </p>
        </div>
    <?php endif; ?>
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

/* Make pagination more compact for mobile */
@media (max-width: 640px) {
    .flight-results {
        grid-template-columns: repeat(1, minmax(0, 1fr));
    }
}
</style>

<?php include 'templates/footer.php'; ?>
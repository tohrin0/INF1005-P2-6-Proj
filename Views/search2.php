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
$offset = ($page - 1) * 10; // 10 items per page

// Sorting options
$sortBy = $_GET['sortBy'] ?? 'price';
$sortOrder = $_GET['sortOrder'] ?? 'asc';

// Filter options
$minPrice = $_GET['minPrice'] ?? '';
$maxPrice = $_GET['maxPrice'] ?? '';
$selectedAirlines = $_GET['airlines'] ?? [];
$selectedDepartureTimes = $_GET['departureTimes'] ?? [];
$selectedArrivalTimes = $_GET['arrivalTimes'] ?? [];

// Load flights - either from search or default flights
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' || !empty($from) && !empty($to) && !empty($departDate)) {
    $searchPerformed = true;

    // Try API search first
    try {
        $apiClient = new ApiClient();
        $result = $apiClient->searchFlightsEnhanced($from, $to, $departDate, $offset);
        $flights = $result['flights'];
        $pagination = $result['pagination'] ?? ['total_pages' => 5, 'current_page' => $page];
        
        if (empty($flights)) {
            $error = "No flights found matching your criteria. Please try different search parameters.";
            // Try to get flights from database as fallback
            $flights = searchFlightsFromDatabase($from, $to, $departDate);
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
            $pagination = $result['pagination'] ?? ['total_pages' => 5, 'current_page' => 1];
        } else {
            $flights = $result; // In case it directly returns an array of flights
        }
        
        if (empty($flights)) {
            $flights = getFeaturedFlightsFromDatabase();
        }
    } catch (Exception $e) {
        error_log("Error fetching featured flights: " . $e->getMessage());
        $flights = getFeaturedFlightsFromDatabase();
    }
}

// Extract dynamic filter options from available flights
$availableAirlines = extractUniqueAirlines($flights);
$availableDepartureTimeRanges = extractDepartureTimeRanges($flights);
$availableArrivalTimeRanges = extractArrivalTimeRanges($flights);
$priceRange = extractPriceRange($flights);

// Apply filters if selected
if (!empty($flights) && (!empty($minPrice) || !empty($maxPrice) || 
                          !empty($selectedAirlines) || !empty($selectedDepartureTimes) || 
                          !empty($selectedArrivalTimes))) {
    $flights = applyFilters($flights, $minPrice, $maxPrice, $selectedAirlines, 
                           $selectedDepartureTimes, $selectedArrivalTimes);
}

// Apply sorting
if (!empty($flights)) {
    $flights = applySorting($flights, $sortBy, $sortOrder);
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
        return [];
    }
}

// Extract unique airlines from flights
function extractUniqueAirlines($flights) {
    $airlines = [];
    
    foreach ($flights as $flight) {
        $airline = $flight['airline'] ?? 'Unknown Airline';
        if (!in_array($airline, $airlines) && !empty($airline)) {
            $airlines[] = $airline;
        }
    }
    
    sort($airlines); // Sort alphabetically
    return $airlines;
}

// Extract departure time ranges from flights
function extractDepartureTimeRanges($flights) {
    $timeRanges = [
        'Morning (6AM - 12PM)' => 0,
        'Afternoon (12PM - 6PM)' => 0,
        'Evening (6PM - 12AM)' => 0,
        'Night (12AM - 6AM)' => 0
    ];
    
    foreach ($flights as $flight) {
        $time = $flight['departureTime'] ?? '00:00';
        $hour = (int)substr($time, 0, 2);
        
        if ($hour >= 6 && $hour < 12) {
            $timeRanges['Morning (6AM - 12PM)']++;
        } else if ($hour >= 12 && $hour < 18) {
            $timeRanges['Afternoon (12PM - 6PM)']++;
        } else if ($hour >= 18) {
            $timeRanges['Evening (6PM - 12AM)']++;
        } else {
            $timeRanges['Night (12AM - 6AM)']++;
        }
    }
    
    // Only return time ranges that have flights
    return array_filter($timeRanges, function($count) {
        return $count > 0;
    });
}

// Extract arrival time ranges from flights
function extractArrivalTimeRanges($flights) {
    $timeRanges = [
        'Morning (6AM - 12PM)' => 0,
        'Afternoon (12PM - 6PM)' => 0,
        'Evening (6PM - 12AM)' => 0,
        'Night (12AM - 6AM)' => 0
    ];
    
    foreach ($flights as $flight) {
        $time = $flight['arrivalTime'] ?? '00:00';
        $hour = (int)substr($time, 0, 2);
        
        if ($hour >= 6 && $hour < 12) {
            $timeRanges['Morning (6AM - 12PM)']++;
        } else if ($hour >= 12 && $hour < 18) {
            $timeRanges['Afternoon (12PM - 6PM)']++;
        } else if ($hour >= 18) {
            $timeRanges['Evening (6PM - 12AM)']++;
        } else {
            $timeRanges['Night (12AM - 6AM)']++;
        }
    }
    
    // Only return time ranges that have flights
    return array_filter($timeRanges, function($count) {
        return $count > 0;
    });
}

// Extract price range from flights
function extractPriceRange($flights) {
    if (empty($flights)) {
        return ['min' => 0, 'max' => 1000];
    }
    
    $prices = array_map(function($flight) {
        return floatval($flight['price'] ?? 0);
    }, $flights);
    
    return [
        'min' => min($prices),
        'max' => max($prices)
    ];
}

// Apply filters to flight results
function applyFilters($flights, $minPrice, $maxPrice, $selectedAirlines, $selectedDepartureTimes, $selectedArrivalTimes) {
    $filtered = array_filter($flights, function($flight) use ($minPrice, $maxPrice, $selectedAirlines, $selectedDepartureTimes, $selectedArrivalTimes) {
        // Price filter
        if (!empty($minPrice) && floatval($flight['price']) < floatval($minPrice)) {
            return false;
        }
        
        if (!empty($maxPrice) && floatval($flight['price']) > floatval($maxPrice)) {
            return false;
        }
        
        // Airline filter
        if (!empty($selectedAirlines) && !in_array($flight['airline'] ?? 'Unknown Airline', $selectedAirlines)) {
            return false;
        }
        
        // Departure time filter
        if (!empty($selectedDepartureTimes)) {
            $departureHour = (int)substr($flight['departureTime'] ?? '00:00', 0, 2);
            $departureTimeCategory = '';
            
            if ($departureHour >= 6 && $departureHour < 12) {
                $departureTimeCategory = 'Morning (6AM - 12PM)';
            } else if ($departureHour >= 12 && $departureHour < 18) {
                $departureTimeCategory = 'Afternoon (12PM - 6PM)';
            } else if ($departureHour >= 18) {
                $departureTimeCategory = 'Evening (6PM - 12AM)';
            } else {
                $departureTimeCategory = 'Night (12AM - 6AM)';
            }
            
            if (!in_array($departureTimeCategory, $selectedDepartureTimes)) {
                return false;
            }
        }
        
        // Arrival time filter
        if (!empty($selectedArrivalTimes)) {
            $arrivalHour = (int)substr($flight['arrivalTime'] ?? '00:00', 0, 2);
            $arrivalTimeCategory = '';
            
            if ($arrivalHour >= 6 && $arrivalHour < 12) {
                $arrivalTimeCategory = 'Morning (6AM - 12PM)';
            } else if ($arrivalHour >= 12 && $arrivalHour < 18) {
                $arrivalTimeCategory = 'Afternoon (12PM - 6PM)';
            } else if ($arrivalHour >= 18) {
                $arrivalTimeCategory = 'Evening (6PM - 12AM)';
            } else {
                $arrivalTimeCategory = 'Night (12AM - 6AM)';
            }
            
            if (!in_array($arrivalTimeCategory, $selectedArrivalTimes)) {
                return false;
            }
        }
        
        return true;
    });
    
    return array_values($filtered); // Reset array keys
}

// Apply sorting to flight results
function applySorting($flights, $sortBy, $sortOrder) {
    $sortField = $sortBy;
    $direction = $sortOrder === 'asc' ? 1 : -1;
    
    if ($sortBy === 'price-desc') {
        $sortField = 'price';
        $direction = -1;
    }
    
    usort($flights, function($a, $b) use ($sortField, $direction) {
        switch ($sortField) {
            case 'price':
                return $direction * (floatval($a['price'] ?? 0) <=> floatval($b['price'] ?? 0));
            case 'duration':
                $aDuration = convertDurationToMinutes($a['duration'] ?? '0h 0m');
                $bDuration = convertDurationToMinutes($b['duration'] ?? '0h 0m');
                return $direction * ($aDuration <=> $bDuration);
            case 'departure':
                $aTime = convertTimeToMinutes($a['departureTime'] ?? '00:00');
                $bTime = convertTimeToMinutes($b['departureTime'] ?? '00:00');
                return $direction * ($aTime <=> $bTime);
            case 'arrival':
                $aTime = convertTimeToMinutes($a['arrivalTime'] ?? '00:00');
                $bTime = convertTimeToMinutes($b['arrivalTime'] ?? '00:00');
                return $direction * ($aTime <=> $bTime);
            default:
                return 0;
        }
    });
    
    return $flights;
}

function convertDurationToMinutes($duration) {
    preg_match('/(\d+)h\s*(\d*)m?/', $duration, $matches);
    $hours = isset($matches[1]) ? (int)$matches[1] : 0;
    $minutes = isset($matches[2]) ? (int)$matches[2] : 0;
    return $hours * 60 + $minutes;
}

function convertTimeToMinutes($time) {
    $parts = explode(':', $time);
    return (int)$parts[0] * 60 + (isset($parts[1]) ? (int)$parts[1] : 0);
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
        ['name' => 'Boston Logan International Airport', 'code' => 'BOS'],
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
    // Fix field name to match the API/database format
    $flightNumber = $flight['flight_number'] ?? ($flight['flightNumber'] ?? 'N/A');
    // Support both camelCase and snake_case formats for these fields
    $departureAirport = $flight['departureAirport'] ?? $flight['departure_airport'] ?? $flight['departure'] ?? 'N/A';
    $departureTime = $flight['departureTime'] ?? $flight['departure_time'] ?? $flight['time'] ?? '00:00';
    $arrivalAirport = $flight['arrivalAirport'] ?? $flight['arrival_airport'] ?? $flight['arrival'] ?? 'N/A';
    $arrivalTime = $flight['arrivalTime'] ?? $flight['arrival_time'] ?? '00:00';
    $duration = $flight['duration'] ?? 'N/A';
    $stops = $flight['stops'] ?? 0;
    $price = $flight['price'] ?? 0;
    
    $stopsText = $stops === 0 ? 'Non-stop' : ($stops === 1 ? '1 Stop' : $stops . ' Stops');
    $stopsClass = $stops === 0 ? 'bg-green-100 text-green-800' : ($stops === 1 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800');
    
    // Get first letter of airline name for the airline logo placeholder
    $airlineInitial = substr($airline, 0, 1);
    if (empty($airlineInitial)) $airlineInitial = 'U';
    
    return <<<HTML
    <div class="bg-white rounded-lg shadow-md p-5 mb-4 hover:shadow-lg transition-all">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
            <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4 md:mb-0">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-blue-100 text-blue-700 rounded-full flex items-center justify-center">
                        <span class="font-bold text-sm">{$airlineInitial}</span>
                    </div>
                </div>
                
                <div>
                    <div class="text-gray-900 font-medium">{$airline}</div>
                    <div class="text-xs text-gray-500">Flight {$flightNumber}</div>
                </div>
                
                <div class="flex items-center gap-3 mt-2 md:mt-0 md:ml-4">
                    <div class="text-center">
                        <div class="text-lg font-bold">{$departureTime}</div>
                        <div class="text-sm font-medium">{$departureAirport}</div>
                    </div>
                    
                    <div class="flex flex-col items-center px-2">
                        <div class="text-xs text-gray-500">{$duration}</div>
                        <div class="w-20 md:w-32 h-px bg-gray-300 my-1 relative">
                            <div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-gray-500 rounded-full"></div>
                        </div>
                        <div class="text-xs {$stopsClass} px-2 py-0.5 rounded-full">{$stopsText}</div>
                    </div>
                    
                    <div class="text-center">
                        <div class="text-lg font-bold">{$arrivalTime}</div>
                        <div class="text-sm font-medium">{$arrivalAirport}</div>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col items-end">
                <div class="text-2xl font-bold text-blue-600">\${$price}</div>
                <div class="text-sm text-gray-500 mb-2">per person</div>
                <form action="passenger-details.php" method="POST">
                    <input type="hidden" name="select_flight" value="1">
                    <input type="hidden" name="flight_id" value="{$flightId}">
                    <input type="hidden" name="price" value="{$price}">
                    <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg text-sm font-medium transition-colors">
                        Select
                    </button>
                </form>
            </div>
        </div>
    </div>
    HTML;
}

// Include header
include 'templates/header.php';
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Flight Search Results</h1>

    <!-- Search Form -->
    <div class="bg-white rounded-lg shadow-md mb-8 p-6">
        <form action="search2.php" method="POST" class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="from" class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <select id="from" name="from" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="">Select departure airport</option>
                    <?php foreach (getAirports() as $airport): ?>
                        <option value="<?= $airport['code'] ?>" <?= $from === $airport['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($airport['name']) ?> (<?= htmlspecialchars($airport['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="to" class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <select id="to" name="to" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="">Select arrival airport</option>
                    <?php foreach (getAirports() as $airport): ?>
                        <option value="<?= $airport['code'] ?>" <?= $to === $airport['code'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($airport['name']) ?> (<?= htmlspecialchars($airport['code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div>
                <label for="departDate" class="block text-sm font-medium text-gray-700 mb-1">Departure Date</label>
                <input type="date" id="departDate" name="departDate" value="<?= htmlspecialchars($departDate) ?>" min="<?= date('Y-m-d') ?>" class="w-full p-2 border border-gray-300 rounded-md">
            </div>
            
            <div>
                <label for="returnDate" class="block text-sm font-medium text-gray-700 mb-1">Return Date (Optional)</label>
                <input type="date" id="returnDate" name="returnDate" value="<?= htmlspecialchars($returnDate) ?>" min="<?= date('Y-m-d') ?>" class="w-full p-2 border border-gray-300 rounded-md">
            </div>
            
            <div>
                <label for="cabinClass" class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
                <select id="cabinClass" name="cabinClass" class="w-full p-2 border border-gray-300 rounded-md">
                    <option value="economy" <?= $cabinClass === 'economy' ? 'selected' : '' ?>>Economy</option>
                    <option value="premium" <?= $cabinClass === 'premium' ? 'selected' : '' ?>>Premium Economy</option>
                    <option value="business" <?= $cabinClass === 'business' ? 'selected' : '' ?>>Business</option>
                    <option value="first" <?= $cabinClass === 'first' ? 'selected' : '' ?>>First Class</option>
                </select>
            </div>
            
            <div class="flex items-end">
                <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md w-full flex justify-center items-center">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                    </svg>
                    Search Flights
                </button>
            </div>
        </form>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Filters Sidebar -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-lg shadow-md sticky top-4">
                <div class="p-6">
                    <h2 class="text-xl font-bold mb-4">Filter Results</h2>

                    <form method="GET" action="" class="space-y-6">
                        <!-- Preserve search parameters -->
                        <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                        <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
                        <input type="hidden" name="departDate" value="<?= htmlspecialchars($departDate) ?>">
                        <input type="hidden" name="returnDate" value="<?= htmlspecialchars($returnDate) ?>">
                        <input type="hidden" name="cabinClass" value="<?= htmlspecialchars($cabinClass) ?>">
                        <input type="hidden" name="adults" value="<?= htmlspecialchars($adults) ?>">
                        <input type="hidden" name="children" value="<?= htmlspecialchars($children) ?>">
                        <input type="hidden" name="infants" value="<?= htmlspecialchars($infants) ?>">
                        <input type="hidden" name="tripType" value="<?= htmlspecialchars($tripType) ?>">
                        <input type="hidden" name="sortBy" value="<?= htmlspecialchars($sortBy) ?>">
                        <input type="hidden" name="sortOrder" value="<?= htmlspecialchars($sortOrder) ?>">

                        <!-- Price Range -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Price Range</label>
                            <div class="flex items-center gap-2">
                                <input type="number" name="minPrice" placeholder="Min" value="<?= htmlspecialchars($minPrice) ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md">
                                <span>-</span>
                                <input type="number" name="maxPrice" placeholder="Max" value="<?= htmlspecialchars($maxPrice) ?>"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md">
                            </div>
                            <?php if (!empty($priceRange)): ?>
                                <div class="mt-1 text-xs text-gray-500">
                                    Range: $<?= number_format($priceRange['min']) ?> - $<?= number_format($priceRange['max']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Airlines - Dynamically generated from available flights -->
                        <?php if (!empty($availableAirlines)): ?>
                        <div>
                            <label class="text-sm font-medium mb-2 block">Airlines</label>
                            <div class="space-y-2 max-h-48 overflow-y-auto">
                                <?php foreach ($availableAirlines as $airline): ?>
                                    <div class="flex items-start">
                                        <input type="checkbox" id="airline-<?= md5($airline) ?>" name="airlines[]"
                                            value="<?= htmlspecialchars($airline) ?>"
                                            <?= in_array($airline, (array)$selectedAirlines) ? 'checked' : '' ?>
                                            class="mt-1 mr-2">
                                        <label for="airline-<?= md5($airline) ?>" class="text-sm text-left">
                                            <?= htmlspecialchars($airline) ?>
                                            <span class="text-xs text-gray-500">(<?= count(array_filter($flights, function($f) use ($airline) { return $f['airline'] == $airline; })) ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Departure Time - Dynamically generated -->
                        <?php if (!empty($availableDepartureTimeRanges)): ?>
                        <div>
                            <label class="text-sm font-medium mb-2 block">Departure Time</label>
                            <div class="space-y-2">
                                <?php foreach (array_keys($availableDepartureTimeRanges) as $timeRange): ?>
                                    <div class="flex items-start">
                                        <input type="checkbox" id="departure-<?= md5($timeRange) ?>" name="departureTimes[]"
                                            value="<?= htmlspecialchars($timeRange) ?>"
                                            <?= in_array($timeRange, (array)$selectedDepartureTimes) ? 'checked' : '' ?>
                                            class="mt-1 mr-2">
                                        <label for="departure-<?= md5($timeRange) ?>" class="text-sm text-left">
                                            <?= htmlspecialchars($timeRange) ?> 
                                            <span class="text-xs text-gray-500">(<?= $availableDepartureTimeRanges[$timeRange] ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Arrival Time - Dynamically generated -->
                        <?php if (!empty($availableArrivalTimeRanges)): ?>
                        <div>
                            <label class="text-sm font-medium mb-2 block">Arrival Time</label>
                            <div class="space-y-2">
                                <?php foreach (array_keys($availableArrivalTimeRanges) as $timeRange): ?>
                                    <div class="flex items-start">
                                        <input type="checkbox" id="arrival-<?= md5($timeRange) ?>" name="arrivalTimes[]"
                                            value="<?= htmlspecialchars($timeRange) ?>"
                                            <?= in_array($timeRange, (array)$selectedArrivalTimes) ? 'checked' : '' ?>
                                            class="mt-1 mr-2">
                                        <label for="arrival-<?= md5($timeRange) ?>" class="text-sm text-left">
                                            <?= htmlspecialchars($timeRange) ?>
                                            <span class="text-xs text-gray-500">(<?= $availableArrivalTimeRanges[$timeRange] ?>)</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>

                        <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded-md transition-colors flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                            </svg>
                            Apply Filters
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Results Column -->
        <div class="lg:col-span-3">
            <!-- Search Summary and Sort Options -->
            <div class="bg-white rounded-lg shadow-md mb-6">
                <div class="p-4">
                    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div class="flex items-center">
                            <div class="font-medium"><?= htmlspecialchars($from) ?></div>
                            <span class="mx-2">→</span>
                            <div class="font-medium"><?= htmlspecialchars($to) ?></div>
                            <span class="mx-2 text-sm text-gray-500"><?= htmlspecialchars($departDate) ?><?= $returnDate ? ' - ' . htmlspecialchars($returnDate) : '' ?></span>
                        </div>

                        <div class="flex items-center gap-2">
                            <form method="GET" action="" id="sortForm" class="flex items-center gap-2">
                                <!-- Preserve search and filter parameters -->
                                <input type="hidden" name="from" value="<?= htmlspecialchars($from) ?>">
                                <input type="hidden" name="to" value="<?= htmlspecialchars($to) ?>">
                                <input type="hidden" name="departDate" value="<?= htmlspecialchars($departDate) ?>">
                                <input type="hidden" name="returnDate" value="<?= htmlspecialchars($returnDate) ?>">
                                <input type="hidden" name="cabinClass" value="<?= htmlspecialchars($cabinClass) ?>">
                                <input type="hidden" name="adults" value="<?= htmlspecialchars($adults) ?>">
                                <input type="hidden" name="children" value="<?= htmlspecialchars($children) ?>">
                                <input type="hidden" name="infants" value="<?= htmlspecialchars($infants) ?>">
                                <input type="hidden" name="tripType" value="<?= htmlspecialchars($tripType) ?>">
                                
                                <?php foreach ((array)$selectedAirlines as $airline): ?>
                                    <input type="hidden" name="airlines[]" value="<?= htmlspecialchars($airline) ?>">
                                <?php endforeach; ?>
                                
                                <?php foreach ((array)$selectedDepartureTimes as $time): ?>
                                    <input type="hidden" name="departureTimes[]" value="<?= htmlspecialchars($time) ?>">
                                <?php endforeach; ?>
                                
                                <?php foreach ((array)$selectedArrivalTimes as $time): ?>
                                    <input type="hidden" name="arrivalTimes[]" value="<?= htmlspecialchars($time) ?>">
                                <?php endforeach; ?>
                                
                                <?php if (!empty($minPrice)): ?>
                                    <input type="hidden" name="minPrice" value="<?= htmlspecialchars($minPrice) ?>">
                                <?php endif; ?>
                                
                                <?php if (!empty($maxPrice)): ?>
                                    <input type="hidden" name="maxPrice" value="<?= htmlspecialchars($maxPrice) ?>">
                                <?php endif; ?>

                                <!-- Sort options -->
                                <select name="sortBy" onchange="document.getElementById('sortForm').submit()"
                                    class="px-3 py-2 border border-gray-300 rounded-md w-[180px]">
                                    <option value="price" <?= $sortBy === 'price' && $sortOrder === 'asc' ? 'selected' : '' ?>>Price: Low to High</option>
                                    <option value="price-desc" <?= $sortBy === 'price' && $sortOrder === 'desc' ? 'selected' : '' ?>>Price: High to Low</option>
                                    <option value="duration" <?= $sortBy === 'duration' ? 'selected' : '' ?>>Duration: Shortest</option>
                                    <option value="departure" <?= $sortBy === 'departure' ? 'selected' : '' ?>>Departure: Earliest</option>
                                    <option value="arrival" <?= $sortBy === 'arrival' ? 'selected' : '' ?>>Arrival: Earliest</option>
                                </select>

                                <button type="submit" name="sortOrder" value="<?= $sortOrder === 'asc' ? 'desc' : 'asc' ?>"
                                    class="p-2 border border-gray-300 rounded-md hover:bg-gray-100">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <polyline points="16 3 21 3 21 8"></polyline>
                                        <line x1="4" y1="20" x2="21" y2="3"></line>
                                        <polyline points="21 16 21 21 16 21"></polyline>
                                        <line x1="15" y1="15" x2="21" y2="21"></line>
                                        <line x1="4" y1="4" x2="9" y2="9"></line>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Flight Results -->
            <?php if ($error): ?>
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($flights) && empty($error)): ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-md mb-6">
                    No flights found. Please try different search criteria.
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <?php
                if (!empty($flights)) {
                    foreach ($flights as $flight) {
                        echo renderFlightCard($flight);
                    }
                }
                ?>
            </div>

            <!-- Pagination -->
            <?php if (!empty($flights) && isset($pagination) && isset($pagination['total_pages']) && $pagination['total_pages'] > 1): ?>
            <div class="flex justify-center mt-8">
                <div class="flex space-x-2">
                    <?php if ($page > 1): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" 
                           class="px-4 py-2 bg-white text-blue-600 border border-gray-300 rounded-md hover:bg-gray-50">
                            Previous
                        </a>
                    <?php endif; ?>

                    <?php 
                    $startPage = max(1, min($page - 2, $pagination['total_pages'] - 4));
                    $endPage = min($startPage + 4, $pagination['total_pages']);
                    
                    for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
                           class="<?= $i === (int)$page ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-gray-50' ?> px-4 py-2 border border-gray-300 rounded-md">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $pagination['total_pages']): ?>
                        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" 
                           class="px-4 py-2 bg-white text-blue-600 border border-gray-300 rounded-md hover:bg-gray-50">
                            Next
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Date inputs handling
    const departDateInput = document.getElementById('departDate');
    const returnDateInput = document.getElementById('returnDate');
    
    departDateInput.addEventListener('change', function() {
        // Set minimum return date to be the departure date
        returnDateInput.min = this.value;
        
        // If return date is earlier than departure date, update it
        if (returnDateInput.value && returnDateInput.value < this.value) {
            returnDateInput.value = this.value;
        }
    });
    
    // Form submission validation
    const searchForm = document.querySelector('form[action="search2.php"]');
    searchForm.addEventListener('submit', function(e) {
        const from = document.getElementById('from').value;
        const to = document.getElementById('to').value;
        const departDate = document.getElementById('departDate').value;
        
        if (!from || !to || !departDate) {
            e.preventDefault();
            alert('Please fill in departure airport, arrival airport, and departure date.');
        }
    });
});
</script>

<?php include 'templates/footer.php'; ?>
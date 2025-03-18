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

// Get search parameters
$from = $_GET['from'] ?? ($_POST['from'] ?? '');
$to = $_GET['to'] ?? ($_POST['to'] ?? '');
$departDate = $_GET['departDate'] ?? ($_POST['departDate'] ?? '');
$returnDate = $_GET['returnDate'] ?? ($_POST['returnDate'] ?? '');
$cabinClass = $_GET['cabinClass'] ?? ($_POST['cabinClass'] ?? 'economy');
$adults = $_GET['adults'] ?? ($_POST['adults'] ?? 1);
$children = $_GET['children'] ?? ($_POST['children'] ?? 0);
$infants = $_GET['infants'] ?? ($_POST['infants'] ?? 0);
$tripType = $_GET['tripType'] ?? ($_POST['tripType'] ?? 'roundtrip');

// Sorting options
$sortBy = $_GET['sortBy'] ?? 'price';
$sortOrder = $_GET['sortOrder'] ?? 'asc';

// Filter options
$minPrice = $_GET['minPrice'] ?? '';
$maxPrice = $_GET['maxPrice'] ?? '';
$selectedAirlines = $_GET['airlines'] ?? [];
$selectedDepartureTimes = $_GET['departureTimes'] ?? [];
$selectedStops = $_GET['stops'] ?? [];

// Fix the REQUEST_METHOD error by checking if it exists first
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST' || !empty($from) && !empty($to) && !empty($departDate)) {
    $searchPerformed = true;

    if (empty($from) || empty($to) || empty($departDate)) {
        $error = 'Please fill in all fields.';
    } else {
        try {
            $apiClient = new ApiClient();
            $flights = $apiClient->searchFlights($from, $to, $departDate);

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

// Sample airlines for filter
$airlines = [
    "SkyWings Airlines",
    "Pacific Air",
    "TransAtlantic",
    "Coastal Airways",
    "Global Express"
];

// Sample departure times for filter
$departureTimes = [
    "Morning (6AM - 12PM)",
    "Afternoon (12PM - 6PM)",
    "Evening (6PM - 12AM)",
    "Night (12AM - 6AM)"
];

// Sample stops for filter
$stops = [
    "Non-stop",
    "1 Stop",
    "2+ Stops"
];

// Function to render flight search result
function renderFlightSearchResult($flight)
{
    $airline = $flight['airline'] ?? 'Unknown Airline';
    $flightNumber = $flight['flightNumber'] ?? 'N/A';
    $departureTime = $flight['departureTime'] ?? '00:00';
    $departureAirport = $flight['departureAirport'] ?? 'N/A';
    $arrivalTime = $flight['arrivalTime'] ?? '00:00';
    $arrivalAirport = $flight['arrivalAirport'] ?? 'N/A';
    $duration = $flight['duration'] ?? 'N/A';
    $stops = $flight['stops'] ?? 0;
    $stopAirport = $flight['stopAirport'] ?? '';
    $price = $flight['price'] ?? 0;

    $stopsText = $stops === 0 ? 'Non-stop' : ($stops === 1 ? '1 Stop' : $stops . ' Stops');
    $stopsClass = $stops === 0 ? 'text-green-600' : ($stops === 1 ? 'text-yellow-600' : 'text-red-600');

    $stopDot = $stops > 0 ? '<div class="absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-2 h-2 bg-gray-400 rounded-full"></div>' : '';
    $stopAirportText = $stops > 0 && $stopAirport ? '<div class="text-xs text-gray-400">' . $stopAirport . '</div>' : '';

    return <<<HTML
    <div class="bg-white rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
        <div class="flex flex-col md:flex-row md:items-center justify-between">
            <div class="flex flex-col md:flex-row md:items-center gap-4 mb-4 md:mb-0">
                <div class="flex-shrink-0">
                    <div class="w-12 h-12 bg-gray-100 rounded-full flex items-center justify-center">
                        <span class="font-bold text-sm">{$airline[0]}</span>
                    </div>
                </div>
                
                <div>
                    <div class="text-sm text-gray-500">{$airline}</div>
                    <div class="text-xs text-gray-400">Flight {$flightNumber}</div>
                </div>
                
                <div class="flex items-center gap-3">
                    <div class="text-center">
                        <div class="text-lg font-bold">{$departureTime}</div>
                        <div class="text-sm font-medium">{$departureAirport}</div>
                    </div>
                    
                    <div class="flex flex-col items-center">
                        <div class="text-xs text-gray-500">{$duration}</div>
                        <div class="w-20 md:w-32 h-px bg-gray-300 my-1 relative">
                            {$stopDot}
                        </div>
                        <div class="text-xs {$stopsClass}">{$stopsText}</div>
                        {$stopAirportText}
                    </div>
                    
                    <div class="text-center">
                        <div class="text-lg font-bold">{$arrivalTime}</div>
                        <div class="text-sm font-medium">{$arrivalAirport}</div>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col items-end">
                <div class="text-2xl font-bold text-blue-600">\${$price}</div>
                <div class="text-sm text-gray-500">per person</div>
                <a href="booking.php?flight={$flightNumber}" class="mt-2 inline-flex items-center justify-center rounded-md bg-blue-600 px-4 py-2 text-sm font-medium text-white shadow hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Select
                </a>
            </div>
        </div>
    </div>
    HTML;
}

include 'templates/header.php';
?>

<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-6">Flight Search Results</h1>

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
                        </div>

                        <!-- Airlines -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Airlines</label>
                            <div class="space-y-2">
                                <?php foreach ($airlines as $airline): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="airline-<?= htmlspecialchars($airline) ?>" name="airlines[]"
                                            value="<?= htmlspecialchars($airline) ?>"
                                            <?= in_array($airline, (array)$selectedAirlines) ? 'checked' : '' ?>
                                            class="mr-2">
                                        <label for="airline-<?= htmlspecialchars($airline) ?>" class="text-sm">
                                            <?= htmlspecialchars($airline) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Departure Time -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Departure Time</label>
                            <div class="space-y-2">
                                <?php foreach ($departureTimes as $time): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="time-<?= htmlspecialchars($time) ?>" name="departureTimes[]"
                                            value="<?= htmlspecialchars($time) ?>"
                                            <?= in_array($time, (array)$selectedDepartureTimes) ? 'checked' : '' ?>
                                            class="mr-2">
                                        <label for="time-<?= htmlspecialchars($time) ?>" class="text-sm">
                                            <?= htmlspecialchars($time) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Stops -->
                        <div>
                            <label class="text-sm font-medium mb-2 block">Stops</label>
                            <div class="space-y-2">
                                <?php foreach ($stops as $stop): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="stop-<?= htmlspecialchars($stop) ?>" name="stops[]"
                                            value="<?= htmlspecialchars($stop) ?>"
                                            <?= in_array($stop, (array)$selectedStops) ? 'checked' : '' ?>
                                            class="mr-2">
                                        <label for="stop-<?= htmlspecialchars($stop) ?>" class="text-sm">
                                            <?= htmlspecialchars($stop) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

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
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-4">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (empty($flights) && empty($error)): ?>
                <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-3 rounded-md mb-4">
                    No flights found. Please try different search criteria.
                </div>
            <?php endif; ?>

            <div class="space-y-4">
                <?php
                // If no real flights data, use sample data for demonstration
                if (empty($flights) && empty($error)) {
                    $flights = [
                        [
                            'airline' => 'SkyWings Airlines',
                            'flightNumber' => 'SW1234',
                            'departureTime' => '08:30',
                            'departureAirport' => 'JFK',
                            'arrivalTime' => '20:15',
                            'arrivalAirport' => 'LHR',
                            'duration' => '7h 45m',
                            'stops' => 0,
                            'price' => 349
                        ],
                        [
                            'airline' => 'TransAtlantic',
                            'flightNumber' => 'TA5678',
                            'departureTime' => '10:45',
                            'departureAirport' => 'JFK',
                            'arrivalTime' => '22:30',
                            'arrivalAirport' => 'LHR',
                            'duration' => '7h 45m',
                            'stops' => 0,
                            'price' => 379
                        ],
                        [
                            'airline' => 'Global Express',
                            'flightNumber' => 'GE9012',
                            'departureTime' => '14:20',
                            'departureAirport' => 'JFK',
                            'arrivalTime' => '02:15',
                            'arrivalAirport' => 'LHR',
                            'duration' => '7h 55m',
                            'stops' => 0,
                            'price' => 329
                        ],
                        [
                            'airline' => 'Pacific Air',
                            'flightNumber' => 'PA3456',
                            'departureTime' => '16:30',
                            'departureAirport' => 'JFK',
                            'arrivalTime' => '09:45',
                            'arrivalAirport' => 'LHR',
                            'duration' => '8h 15m',
                            'stops' => 1,
                            'stopAirport' => 'BOS',
                            'price' => 299
                        ],
                        [
                            'airline' => 'Coastal Airways',
                            'flightNumber' => 'CA7890',
                            'departureTime' => '19:15',
                            'departureAirport' => 'JFK',
                            'arrivalTime' => '12:30',
                            'arrivalAirport' => 'LHR',
                            'duration' => '8h 15m',
                            'stops' => 1,
                            'stopAirport' => 'DUB',
                            'price' => 319
                        ]
                    ];
                }

                foreach ($flights as $flight) {
                    echo renderFlightSearchResult($flight);
                }
                ?>
            </div>

            <!-- Pagination -->
            <div class="flex justify-center mt-8">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                    <a href="?page=<?= $i ?>&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>&departDate=<?= htmlspecialchars($departDate) ?>&returnDate=<?= htmlspecialchars($returnDate) ?>&cabinClass=<?= htmlspecialchars($cabinClass) ?>&adults=<?= htmlspecialchars($adults) ?>&children=<?= htmlspecialchars($children) ?>&infants=<?= htmlspecialchars($infants) ?>&tripType=<?= htmlspecialchars($tripType) ?>&sortBy=<?= htmlspecialchars($sortBy) ?>&sortOrder=<?= htmlspecialchars($sortOrder) ?>"
                        class="mx-1 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-100 <?= ($_GET['page'] ?? 1) == $i ? 'bg-blue-600 text-white hover:bg-blue-700' : '' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>
                <a href="?page=<?= min(($_GET['page'] ?? 1) + 1, 5) ?>&from=<?= htmlspecialchars($from) ?>&to=<?= htmlspecialchars($to) ?>&departDate=<?= htmlspecialchars($departDate) ?>&returnDate=<?= htmlspecialchars($returnDate) ?>&cabinClass=<?= htmlspecialchars($cabinClass) ?>&adults=<?= htmlspecialchars($adults) ?>&children=<?= htmlspecialchars($children) ?>&infants=<?= htmlspecialchars($infants) ?>&tripType=<?= htmlspecialchars($tripType) ?>&sortBy=<?= htmlspecialchars($sortBy) ?>&sortOrder=<?= htmlspecialchars($sortOrder) ?>"
                    class="mx-1 px-3 py-2 border border-gray-300 rounded-md hover:bg-gray-100">
                    Next
                </a>
            </div>
        </div>
    </div>
</main>

<?php include 'templates/footer.php'; ?>
<?php
// Function to render the arrow SVG
function renderArrow($direction = "down")
{
    $rotateClass = $direction === "up" ? "rotate-180" : "";
    return '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" 
        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" 
        class="' . $rotateClass . ' text-gray-500">
        <polyline points="6 9 12 15 18 9"></polyline>
    </svg>';
}

// Sample location suggestions
$locationSuggestions = [
    "New York (JFK)",
    "New York (LGA)",
    "New York (EWR)",
    "Los Angeles (LAX)",
    "Chicago (ORD)",
    "London (LHR)",
    "Paris (CDG)",
    "Tokyo (NRT)",
    "Sydney (SYD)",
    "Dubai (DXB)",
];

// Initialize variables from form submission or set defaults
$tripType = $_POST['tripType'] ?? 'roundtrip';
$from = $_POST['from'] ?? '';
$to = $_POST['to'] ?? '';
$departDate = $_POST['departDate'] ?? '';
$returnDate = $_POST['returnDate'] ?? '';
$cabinClass = $_POST['cabinClass'] ?? 'economy';
$adults = $_POST['adults'] ?? 1;
$children = $_POST['children'] ?? 0;
$infants = $_POST['infants'] ?? 0;

// Filter suggestions based on input
$filteredFromSuggestions = array_filter($locationSuggestions, function ($loc) use ($from, $to) {
    return $from ? (stripos($loc, $from) !== false && $loc !== $to) : false; // Only show if user has typed something
});

$filteredToSuggestions = array_filter($locationSuggestions, function ($loc) use ($to, $from) {
    return $to ? (stripos($loc, $to) !== false && $loc !== $from) : false; // Only show if user has typed something
});

// Handle form submission
$formSubmitted = isset($_POST['search_flights']);
if ($formSubmitted) {
    // Process the form submission
    // You would typically redirect to a search results page or perform a search query
    $searchData = [
        'tripType' => $tripType,
        'from' => $from,
        'to' => $to,
        'departDate' => $departDate,
        'returnDate' => $returnDate,
        'cabinClass' => $cabinClass,
        'passengers' => [
            'adults' => $adults,
            'children' => $children,
            'infants' => $infants
        ]
    ];

    // Redirect to search results page
    header("Location: search-results.php?" . http_build_query($searchData));
    exit;
}
?>

<div class="bg-white rounded-xl shadow-lg p-6">
    <!-- Trip Type Selection -->
    <div class="flex space-x-4 mb-6">
        <button type="button"
            onclick="setTripType('roundtrip')"
            class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $tripType === 'roundtrip' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            Round Trip
        </button>
        <button type="button"
            onclick="setTripType('oneway')"
            class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $tripType === 'oneway' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            One Way
        </button>
        <button type="button"
            onclick="setTripType('multicity')"
            class="px-4 py-2 rounded-md text-sm font-medium transition-colors <?= $tripType === 'multicity' ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
            Multi-City
        </button>
    </div>

    <form method="POST" action="" id="flightSearchForm">
        <input type="hidden" id="tripType" name="tripType" value="<?= htmlspecialchars($tripType) ?>">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- From Location -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">From</label>
                <div class="relative">
                    <input
                        type="text"
                        name="from"
                        id="fromInput"
                        placeholder="City or Airport"
                        value="<?= htmlspecialchars($from) ?>"
                        class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        autocomplete="off"
                        onfocus="showSuggestions('from')"
                        onblur="setTimeout(function() { hideSuggestions('from'); }, 200)"
                        oninput="filterSuggestions('from')">
                    <div id="fromSuggestions" class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-200 max-h-60 overflow-auto hidden">
                        <?php foreach ($locationSuggestions as $suggestion): ?>
                            <div class="p-2 hover:bg-gray-100 cursor-pointer suggestion-item"
                                onclick="selectSuggestion('from', '<?= htmlspecialchars($suggestion) ?>')">
                                <?= htmlspecialchars($suggestion) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- To Location -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">To</label>
                <div class="relative">
                    <input
                        type="text"
                        name="to"
                        id="toInput"
                        placeholder="City or Airport"
                        value="<?= htmlspecialchars($to) ?>"
                        class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        autocomplete="off"
                        onfocus="showSuggestions('to')"
                        onblur="setTimeout(function() { hideSuggestions('to'); }, 200)"
                        oninput="filterSuggestions('to')">
                    <div id="toSuggestions" class="absolute z-10 w-full mt-1 bg-white shadow-lg rounded-md border border-gray-200 max-h-60 overflow-auto hidden">
                        <?php foreach ($locationSuggestions as $suggestion): ?>
                            <div class="p-2 hover:bg-gray-100 cursor-pointer suggestion-item"
                                onclick="selectSuggestion('to', '<?= htmlspecialchars($suggestion) ?>')">
                                <?= htmlspecialchars($suggestion) ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <!-- Departure Date -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Departure Date</label>
                <input
                    type="date"
                    name="departDate"
                    value="<?= htmlspecialchars($departDate) ?>"
                    min="<?= date('Y-m-d') ?>"
                    class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>

            <!-- Return Date (only for roundtrip) -->
            <div id="returnDateContainer" <?= $tripType !== 'roundtrip' ? 'class="hidden"' : '' ?>>
                <label class="block text-sm font-medium text-gray-700 mb-1">Return Date</label>
                <input
                    type="date"
                    name="returnDate"
                    value="<?= htmlspecialchars($returnDate) ?>"
                    min="<?= $departDate ?: date('Y-m-d') ?>"
                    class="w-full p-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
            <!-- Passenger Selector -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Passengers</label>
                <div class="grid grid-cols-3 gap-2">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Adults</label>
                        <div class="flex items-center border border-gray-300 rounded-md">
                            <button type="button" onclick="decrementPassenger('adults')" class="px-2 py-1 text-gray-500">-</button>
                            <input type="number" id="adults" name="adults" value="<?= $adults ?>" min="1" max="9" class="w-full text-center border-0 focus:ring-0" readonly>
                            <button type="button" onclick="incrementPassenger('adults')" class="px-2 py-1 text-gray-500">+</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Children</label>
                        <div class="flex items-center border border-gray-300 rounded-md">
                            <button type="button" onclick="decrementPassenger('children')" class="px-2 py-1 text-gray-500">-</button>
                            <input type="number" id="children" name="children" value="<?= $children ?>" min="0" max="9" class="w-full text-center border-0 focus:ring-0" readonly>
                            <button type="button" onclick="incrementPassenger('children')" class="px-2 py-1 text-gray-500">+</button>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Infants</label>
                        <div class="flex items-center border border-gray-300 rounded-md">
                            <button type="button" onclick="decrementPassenger('infants')" class="px-2 py-1 text-gray-500">-</button>
                            <input type="number" id="infants" name="infants" value="<?= $infants ?>" min="0" max="9" class="w-full text-center border-0 focus:ring-0" readonly>
                            <button type="button" onclick="incrementPassenger('infants')" class="px-2 py-1 text-gray-500">+</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Cabin Class Selector -->
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Cabin Class</label>
                <div class="grid grid-cols-2 gap-2">
                    <?php
                    $options = [
                        ['value' => 'economy', 'label' => 'Economy'],
                        ['value' => 'premium', 'label' => 'Premium Economy'],
                        ['value' => 'business', 'label' => 'Business'],
                        ['value' => 'first', 'label' => 'First Class']
                    ];

                    foreach ($options as $option):
                    ?>
                        <button
                            type="button"
                            onclick="setCabinClass('<?= $option['value'] ?>')"
                            class="py-2 px-3 rounded-md text-sm font-medium transition-colors <?= $cabinClass === $option['value'] ? 'bg-blue-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200' ?>">
                            <?= htmlspecialchars($option['label']) ?>
                        </button>
                    <?php endforeach; ?>
                    <input type="hidden" id="cabinClass" name="cabinClass" value="<?= htmlspecialchars($cabinClass) ?>">
                </div>
            </div>
        </div>

        <button
            type="submit"
            name="search_flights"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 px-6 rounded-md transition-colors flex items-center justify-center">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-2">
                <circle cx="11" cy="11" r="8"></circle>
                <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
            </svg>
            Search Flights
        </button>
    </form>
</div>

<script>
    // JavaScript functions for passenger count manipulation
    function incrementPassenger(type) {
        const input = document.getElementById(type);
        const currentValue = parseInt(input.value);
        const max = parseInt(input.getAttribute('max'));

        if (currentValue < max) {
            input.value = currentValue + 1;
        }
    }

    function decrementPassenger(type) {
        const input = document.getElementById(type);
        const currentValue = parseInt(input.value);
        const min = parseInt(input.getAttribute('min'));

        if (currentValue > min) {
            input.value = currentValue - 1;
        }
    }

    // Trip type selection
    function setTripType(type) {
        document.getElementById('tripType').value = type;

        // Show/hide return date based on trip type
        if (type === 'roundtrip') {
            document.getElementById('returnDateContainer').classList.remove('hidden');
        } else {
            document.getElementById('returnDateContainer').classList.add('hidden');
        }

        // Update button styles
        const buttons = document.querySelectorAll('[onclick^="setTripType"]');
        buttons.forEach(button => {
            if (button.getAttribute('onclick') === `setTripType('${type}')`) {
                button.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                button.classList.add('bg-blue-600', 'text-white');
            } else {
                button.classList.remove('bg-blue-600', 'text-white');
                button.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
            }
        });
    }

    // Cabin class selection
    function setCabinClass(classType) {
        document.getElementById('cabinClass').value = classType;

        // Update button styles
        const buttons = document.querySelectorAll('[onclick^="setCabinClass"]');
        buttons.forEach(button => {
            if (button.getAttribute('onclick') === `setCabinClass('${classType}')`) {
                button.classList.remove('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
                button.classList.add('bg-blue-600', 'text-white');
            } else {
                button.classList.remove('bg-blue-600', 'text-white');
                button.classList.add('bg-gray-100', 'text-gray-700', 'hover:bg-gray-200');
            }
        });
    }

    // Location suggestions
    function showSuggestions(field) {
        document.getElementById(`${field}Suggestions`).classList.remove('hidden');
    }

    function hideSuggestions(field) {
        document.getElementById(`${field}Suggestions`).classList.add('hidden');
    }

    function selectSuggestion(field, value) {
        document.getElementById(`${field}Input`).value = value;
        hideSuggestions(field);
    }

    function filterSuggestions(field) {
        const input = document.getElementById(`${field}Input`);
        const filter = input.value.toUpperCase();
        const suggestionContainer = document.getElementById(`${field}Suggestions`);
        const suggestions = suggestionContainer.getElementsByClassName('suggestion-item');

        let hasVisibleSuggestions = false;

        for (let i = 0; i < suggestions.length; i++) {
            const txtValue = suggestions[i].textContent || suggestions[i].innerText;
            if (txtValue.toUpperCase().indexOf(filter) > -1) {
                suggestions[i].style.display = "";
                hasVisibleSuggestions = true;
            } else {
                suggestions[i].style.display = "none";
            }
        }

        // Show/hide the suggestions container based on if there are visible suggestions
        if (filter && hasVisibleSuggestions) {
            suggestionContainer.classList.remove('hidden');
        } else {
            suggestionContainer.classList.add('hidden');
        }
    }

    // Initialize the form
    document.addEventListener('DOMContentLoaded', function() {
        // Set initial state for return date container
        if ('<?= $tripType ?>' !== 'roundtrip') {
            document.getElementById('returnDateContainer').classList.add('hidden');
        }
    });
</script>
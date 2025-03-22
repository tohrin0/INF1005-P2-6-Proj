<?php

class ApiClient
{
    private $apiUrl;
    private $apiKeys;
    private $currentKeyIndex = 0;

    public function __construct($apiUrl = null)
    {
        $this->apiUrl = $apiUrl ?? FLIGHT_API_URL;
        $this->apiKeys = json_decode(FLIGHT_API_KEYS, true);
        
        if (empty($this->apiKeys)) {
            throw new Exception("No API keys configured");
        }
    }

    public function searchFlights($departure, $arrival, $date)
    {
        // Prepare parameters for AviationStack API
        $params = [
            'limit' => 20
        ];
        
        // Format date to YYYY-MM-DD for the API
        if (!empty($date)) {
            $params['flight_date'] = date('Y-m-d', strtotime($date));
        }
        
        // Extract IATA code if present in the format "Airport Name (CODE)"
        $pattern = '/\(([A-Z]{3})\)$/';
        
        // Add departure filter
        if (!empty($departure)) {
            if (preg_match($pattern, $departure, $matches)) {
                // If input contains IATA code in parentheses, extract it
                $params['dep_iata'] = $matches[1];
            } else {
                // Otherwise, use first 3 characters (or the full city name)
                // AviationStack will handle the matching
                $params['dep_iata'] = strtoupper(substr($departure, 0, 3));
            }
        }
        
        // Add arrival filter
        if (!empty($arrival)) {
            if (preg_match($pattern, $arrival, $matches)) {
                $params['arr_iata'] = $matches[1];
            } else {
                $params['arr_iata'] = strtoupper(substr($arrival, 0, 3));
            }
        }

        // Build API URL (without access_key - we'll add it in makeRequest)
        $url = $this->apiUrl . '/flights?' . http_build_query($params);
        error_log("API Request URL for flight search (without key): " . $url);

        try {
            $response = $this->makeRequest($url);
            
            if (empty($response['data']) || !isset($response['data'])) {
                error_log("Empty API response or no flight data");
                // Return dummy data that matches the search criteria
                return $this->getDummyFlightData($departure, $arrival, $date);
            }
            
            return $this->formatFlightResults($response);
        } catch (Exception $e) {
            error_log("Error searching flights: " . $e->getMessage());
            return $this->getDummyFlightData($departure, $arrival, $date);
        }
    }

    public function getFlightSchedules($params = [])
    {
        $url = $this->apiUrl . '/flights?' . http_build_query($params);
        
        $response = $this->makeRequest($url);
        return $this->formatFlightResults($response);
    }

    public function getAvailableFlights($offset = 0)
    {
        $params = [
            'limit' => 100,  // Maximum allowed by the free plan
            'offset' => $offset
        ];

        $url = $this->apiUrl . '/flights?' . http_build_query($params);
        error_log("API Request URL for available flights (without key): " . $url);

        try {
            $response = $this->makeRequest($url);
            return [
                'flights' => $this->formatFlightResults($response),
                'pagination' => $response['pagination'] ?? null
            ];
        } catch (Exception $e) {
            error_log("Error in getAvailableFlights: " . $e->getMessage());
            return ['flights' => [], 'pagination' => null];
        }
    }

    private function makeRequest($url)
    {
        // Try each API key until one works or we run out of keys
        $attempts = 0;
        $maxAttempts = count($this->apiKeys);
        $lastError = null;
        
        while ($attempts < $maxAttempts) {
            try {
                // Add the current API key to the URL
                $currentKey = $this->apiKeys[$this->currentKeyIndex];
                $fullUrl = $url . (parse_url($url, PHP_URL_QUERY) ? '&' : '?') . 'access_key=' . $currentKey;
                
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, $fullUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                
                $response = curl_exec($ch);
                
                if (curl_errno($ch)) {
                    throw new Exception('cURL error: ' . curl_error($ch));
                }
                
                curl_close($ch);
                
                $result = json_decode($response, true);
                
                // Check for API-specific errors
                if (!$result) {
                    throw new Exception("Failed to decode API response");
                }
                
                if (isset($result['error'])) {
                    $errorInfo = $result['error']['info'] ?? 'Unknown API error';
                    
                    // If usage limit is exceeded, try the next key
                    if (strpos($errorInfo, 'usage limit') !== false || 
                        strpos($errorInfo, 'rate limit') !== false) {
                        throw new Exception("API key limit reached: " . $errorInfo);
                    }
                    
                    // For other errors, just return the error
                    error_log('API response error: ' . $errorInfo);
                    return ['data' => []];
                }
                
                // If we got here, the request was successful
                return $result;
                
            } catch (Exception $e) {
                $lastError = $e->getMessage();
                error_log("API key {$this->currentKeyIndex} failed: " . $lastError);
                
                // Try the next API key
                $this->currentKeyIndex = ($this->currentKeyIndex + 1) % $maxAttempts;
                $attempts++;
                
                // If we've tried all keys and are back to the first one, break
                if ($attempts >= $maxAttempts) {
                    error_log("All API keys failed. Last error: " . $lastError);
                    break;
                }
            }
        }
        
        // If we've exhausted all API keys, return an empty result
        error_log("All API keys exhausted. Last error: " . $lastError);
        return ['data' => []];
    }

    private function formatFlightResults($response)
    {
        if (!isset($response['data']) || empty($response['data'])) {
            error_log("No flight data in API response");
            return $this->getDummyFlightData();
        }

        $formattedFlights = [];
        foreach ($response['data'] as $flight) {
            // Extract all the necessary information from the API response
            $departureAirport = $flight['departure']['airport'] ?? 'Unknown';
            $departureCity = $this->extractCityFromAirport($departureAirport);
            
            $arrivalAirport = $flight['arrival']['airport'] ?? 'Unknown';
            $arrivalCity = $this->extractCityFromAirport($arrivalAirport);
            
            // Format departure and arrival times
            $departureTime = isset($flight['departure']['scheduled']) ? 
                            date('H:i', strtotime($flight['departure']['scheduled'])) : 
                            date('H:i');
                            
            $arrivalTime = isset($flight['arrival']['scheduled']) ? 
                          date('H:i', strtotime($flight['arrival']['scheduled'])) : 
                          date('H:i', strtotime('+2 hours'));
            
            // Calculate duration in minutes
            $duration = 0;
            if (isset($flight['departure']['scheduled']) && isset($flight['arrival']['scheduled'])) {
                $depTime = strtotime($flight['departure']['scheduled']);
                $arrTime = strtotime($flight['arrival']['scheduled']);
                if ($depTime && $arrTime) {
                    $duration = round(($arrTime - $depTime) / 60);
                }
            }
            
            $flightDate = isset($flight['flight_date']) ? 
                         $flight['flight_date'] : 
                         (isset($flight['departure']['scheduled']) ? 
                         date('Y-m-d', strtotime($flight['departure']['scheduled'])) : 
                         date('Y-m-d'));
            
            // Generate a unique ID for this flight
            $id = isset($flight['flight']['iata']) ? 
                  md5($flight['flight']['iata'] . $flightDate) : 
                  uniqid('flight_');
            
            $formattedFlight = [
                'id' => $id,
                'flight_api' => $id, // Store the API's flight ID
                'flight_number' => $flight['flight']['iata'] ?? $flight['flight']['icao'] ?? 'Unknown',
                'airline' => $flight['airline']['name'] ?? ($flight['airline']['iata'] ?? 'Unknown'),
                'departure' => $departureCity,
                'departure_airport' => $flight['departure']['iata'] ?? substr($departureAirport, 0, 3),
                'departure_time' => $departureTime,
                'departure_terminal' => $flight['departure']['terminal'] ?? null,
                'departure_gate' => $flight['departure']['gate'] ?? null,
                'departure_delay' => $flight['departure']['delay'] ?? 0,
                'arrival' => $arrivalCity,
                'arrival_airport' => $flight['arrival']['iata'] ?? substr($arrivalAirport, 0, 3),
                'arrival_time' => $arrivalTime,
                'arrival_terminal' => $flight['arrival']['terminal'] ?? null,
                'arrival_gate' => $flight['arrival']['gate'] ?? null,
                'arrival_delay' => $flight['arrival']['delay'] ?? 0,
                'status' => $flight['flight_status'] ?? 'scheduled',
                'date' => $flightDate,
                'time' => $departureTime,
                'duration' => $duration > 0 ? $duration : rand(45, 360),
                'price' => rand(99, 999), // Random price as AviationStack doesn't provide pricing
                'available_seats' => rand(10, 150), // Random seats as AviationStack doesn't provide seating
                'aircraft_registration' => $flight['aircraft']['registration'] ?? null,
                'aircraft_type' => $flight['aircraft']['iata'] ?? null
            ];

            $formattedFlights[] = $formattedFlight;
        }

        // If no flights were found or properly formatted, return dummy data
        if (empty($formattedFlights)) {
            return $this->getDummyFlightData();
        }

        return $formattedFlights;
    }

    // Extract city name from airport name
    private function extractCityFromAirport($airportName)
    {
        // Common airport name patterns
        $patterns = [
            '/(.+)\s+International(\s+Airport)?/i',
            '/(.+)\s+Airport/i',
            '/(.+)\s+Regional(\s+Airport)?/i',
            '/(.+)\s+Municipal(\s+Airport)?/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $airportName, $matches)) {
                return $matches[1];
            }
        }
        
        // If we can't extract a city name, return the airport name
        return $airportName;
    }

    private function getDummyFlightData($departure = null, $arrival = null, $date = null)
    {
        // Generate some fake flights for testing or when API is unavailable
        $flights = [];
        $airlines = ['American Airlines', 'Delta Air Lines', 'United Airlines', 'Southwest', 'JetBlue'];
        
        // Set default date if not provided
        $searchDate = $date ? date('Y-m-d', strtotime($date)) : date('Y-m-d');
        
        // Cities with airport codes
        $cities = [
            'New York' => 'NYC',
            'Los Angeles' => 'LAX',
            'Chicago' => 'ORD',
            'Miami' => 'MIA',
            'Dallas' => 'DFW',
            'San Francisco' => 'SFO',
            'Seattle' => 'SEA',
            'Denver' => 'DEN',
            'Boston' => 'BOS',
            'Las Vegas' => 'LAS',
            'Orlando' => 'MCO',
            'Phoenix' => 'PHX'
        ];
        
        // Extract city and code info for departure and arrival
        // Extract codes from city names if provided in "City (CODE)" format
        $pattern = '/\(([A-Z]{3})\)$/';
        
        if (!empty($departure) && preg_match($pattern, $departure, $matches)) {
            $departureCode = $matches[1];
            // Find the city name for this code
            $departureCity = array_search($departureCode, $cities) ?: $departure;
        } elseif (!empty($departure) && array_key_exists($departure, $cities)) {
            $departureCity = $departure;
            $departureCode = $cities[$departure];
        } elseif (!empty($departure) && in_array(strtoupper($departure), $cities)) {
            $departureCity = array_search(strtoupper($departure), $cities);
            $departureCode = strtoupper($departure);
        } else {
            // If no match, just use what was provided
            $departureCity = $departure ?: array_rand($cities);
            $departureCode = $cities[$departureCity] ?? substr($departureCity, 0, 3);
        }
        
        if (!empty($arrival) && preg_match($pattern, $arrival, $matches)) {
            $arrivalCode = $matches[1];
            // Find the city name for this code
            $arrivalCity = array_search($arrivalCode, $cities) ?: $arrival;
        } elseif (!empty($arrival) && array_key_exists($arrival, $cities)) {
            $arrivalCity = $arrival;
            $arrivalCode = $cities[$arrival];
        } elseif (!empty($arrival) && in_array(strtoupper($arrival), $cities)) {
            $arrivalCity = array_search(strtoupper($arrival), $cities);
            $arrivalCode = strtoupper($arrival);
        } else {
            // If no match, just use what was provided
            $arrivalCity = $arrival ?: array_rand(array_diff_key($cities, [$departureCity => '']));
            $arrivalCode = $cities[$arrivalCity] ?? substr($arrivalCity, 0, 3);
        }

        for ($i = 0; $i < 5; $i++) {
            // Generate random flight times
            $depTime = strtotime($searchDate . ' +' . rand(0, 23) . ' hours +' . rand(0, 59) . ' minutes');
            $flightDuration = rand(45, 360); // Flight duration in minutes
            $arrTime = $depTime + ($flightDuration * 60);
            
            // Generate unique flight ID for API
            $flightApiId = 'api_flight_' . uniqid();

            $flights[] = [
                'id' => 'FL' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                'flight_api' => $flightApiId, // Add flight_api ID
                'flight_number' => 'FL' . str_pad($i + 100, 4, '0', STR_PAD_LEFT),
                'airline' => $airlines[array_rand($airlines)],
                'departure' => $departureCity,
                'departure_airport' => $departureCode,
                'departure_time' => date('H:i', $depTime),
                'arrival' => $arrivalCity,
                'arrival_airport' => $arrivalCode,
                'arrival_time' => date('H:i', $arrTime),
                'status' => 'scheduled',
                'date' => $searchDate,
                'time' => date('H:i', $depTime),
                'duration' => $flightDuration,
                'price' => rand(89, 999),
                'available_seats' => rand(5, 120)
            ];
        }

        return $flights;
    }

    public function getFlightById($flightId)
    {
        // For API IDs or database IDs, search through available flights
        $flights = $this->getAvailableFlights();
        
        foreach ($flights as $flight) {
            if ($flight['id'] == $flightId || $flight['flight_api'] == $flightId) {
                // If we find the flight by ID, try to enhance it with real-time data
                try {
                    if (isset($flight['flight_number'])) {
                        $params = [
                            'flight_iata' => $flight['flight_number'],
                            'flight_date' => $flight['date'] ?? date('Y-m-d')
                        ];
                        
                        $realTimeData = $this->getFlightStatus($params);
                        
                        if (!empty($realTimeData)) {
                            // Enhance with real-time data (status, gates, terminals, etc.)
                            $rtf = $realTimeData[0];
                            
                            $flight['status'] = $rtf['flight_status'] ?? $flight['status'] ?? 'scheduled';
                            $flight['departure_terminal'] = $rtf['departure']['terminal'] ?? $flight['departure_terminal'] ?? null;
                            $flight['departure_gate'] = $rtf['departure']['gate'] ?? $flight['departure_gate'] ?? null;
                            $flight['arrival_terminal'] = $rtf['arrival']['terminal'] ?? $flight['arrival_terminal'] ?? null;
                            $flight['arrival_gate'] = $rtf['arrival']['gate'] ?? $flight['arrival_gate'] ?? null;
                            $flight['aircraft_registration'] = $rtf['aircraft']['registration'] ?? null;
                            $flight['aircraft_type'] = $rtf['aircraft']['iata'] ?? null;
                        }
                    }
                } catch (Exception $e) {
                    // Just log the error but continue with the basic flight info
                    error_log("Error enhancing flight with real-time data: " . $e->getMessage());
                }
                
                return $flight;
            }
        }
        
        // Return null if not found
        return null;
    }

    public function getFlightStatus($params = [])
    {
        // Build API URL (without access_key - we'll add it in makeRequest)
        $url = $this->apiUrl . '/flights?' . http_build_query($params);
        error_log("API Request URL for flight status (without key): " . $url);

        try {
            $response = $this->makeRequest($url);
            
            if (empty($response['data'])) {
                error_log("No flight status data returned from API");
                return [];
            }
            
            return $response['data'];
        } catch (Exception $e) {
            error_log("Error getting flight status: " . $e->getMessage());
            return [];
        }
    }

    // Add this new method to the ApiClient class

    /**
     * Enhanced flight search that extracts more information from the API
     * 
     * @param string $departure Departure city or airport
     * @param string $arrival Arrival city or airport
     * @param string $date Date in YYYY-MM-DD format
     * @return array Formatted flight results
     */
    public function searchFlightsEnhanced($from, $to, $date, $offset = 0)
    {
        $params = [
            'dep_iata' => $from,
            'arr_iata' => $to,
            'flight_date' => $date,
            'limit' => 100,
            'offset' => $offset // This needs to be passed correctly
        ];
        
        // Build API URL (without access_key - we'll add it in makeRequest)
        $url = $this->apiUrl . '/flights?' . http_build_query($params);
        error_log("API Request with offset: " . $offset . ", params: " . json_encode($params));
        
        try {
            $response = $this->makeRequest($url);
            return [
                'flights' => $this->formatEnhancedFlightResults($response),
                'pagination' => $response['pagination'] ?? null
            ];
        } catch (Exception $e) {
            error_log("Error in searchFlightsEnhanced: " . $e->getMessage());
            return ['flights' => [], 'pagination' => null];
        }
    }

    /**
     * Format enhanced flight results from API
     * 
     * @param array $response API response
     * @return array Formatted flight data
     */
    private function formatEnhancedFlightResults($response)
    {
        if (!isset($response['data']) || empty($response['data'])) {
            error_log("No flight data in API response for enhanced format");
            return [];
        }

        $formattedFlights = [];
        foreach ($response['data'] as $flight) {
            // Generate a unique ID for this flight
            $flightDate = isset($flight['flight_date']) ? 
                         $flight['flight_date'] : 
                         (isset($flight['departure']['scheduled']) ? 
                         date('Y-m-d', strtotime($flight['departure']['scheduled'])) : 
                         date('Y-m-d'));
                         
            $id = isset($flight['flight']['iata']) ? 
                  md5($flight['flight']['iata'] . $flightDate) : 
                  uniqid('flight_');
                  
            // Extract departure info
            $departureTime = isset($flight['departure']['scheduled']) ? 
                date('H:i', strtotime($flight['departure']['scheduled'])) : 
                date('H:i');
                
            $departureAirport = isset($flight['departure']['iata']) ? 
                $flight['departure']['iata'] : 
                (isset($flight['departure']['airport']) ? substr($flight['departure']['airport'], 0, 3) : 'N/A');
                
            $departureTerminal = $flight['departure']['terminal'] ?? null;
            $departureGate = $flight['departure']['gate'] ?? null;
            $departureDelay = $flight['departure']['delay'] ?? 0;
            
            // Extract arrival info
            $arrivalTime = isset($flight['arrival']['scheduled']) ? 
                date('H:i', strtotime($flight['arrival']['scheduled'])) : 
                date('H:i', strtotime('+2 hours'));
                
            $arrivalAirport = isset($flight['arrival']['iata']) ? 
                $flight['arrival']['iata'] : 
                (isset($flight['arrival']['airport']) ? substr($flight['arrival']['airport'], 0, 3) : 'N/A');
                
            $arrivalTerminal = $flight['arrival']['terminal'] ?? null;
            $arrivalGate = $flight['arrival']['gate'] ?? null;
            $arrivalDelay = $flight['arrival']['delay'] ?? 0;
            
            // Calculate duration in minutes
            $durationMinutes = 0;
            if (isset($flight['departure']['scheduled']) && isset($flight['arrival']['scheduled'])) {
                $depTime = strtotime($flight['departure']['scheduled']);
                $arrTime = strtotime($flight['arrival']['scheduled']);
                if ($depTime && $arrTime) {
                    $durationMinutes = round(($arrTime - $depTime) / 60);
                }
            } else {
                // If we can't calculate, provide a reasonable default
                $durationMinutes = rand(90, 240);
            }
            
            // Format a nice duration string (e.g., "2h 15m")
            $durationFormatted = $this->formatDuration($durationMinutes);
            
            // Extract aircraft info
            $aircraft = isset($flight['aircraft']) ? 
                       ($flight['aircraft']['iata'] ?? $flight['aircraft']['icao'] ?? null) : 
                       null;

            // Generate a random price since API doesn't provide it
            $basePrice = rand(89, 299);
            $price = $basePrice + ($durationMinutes / 10); // Longer flights cost more
            
            // Assume direct flight unless indicated otherwise
            $stops = 0;
            
            $formattedFlights[] = [
                'airline' => $flight['airline']['name'] ?? ($flight['airline']['iata'] ?? 'Unknown Airline'),
                'flight_number' => $flight['flight']['iata'] ?? $flight['flight']['icao'] ?? 'N/A',
                'departureTime' => $departureTime,
                'departureAirport' => $departureAirport,
                'departureTerminal' => $departureTerminal,
                'departureGate' => $departureGate,
                'departureDelay' => $departureDelay,
                'arrivalTime' => $arrivalTime,
                'arrivalAirport' => $arrivalAirport,
                'arrivalTerminal' => $arrivalTerminal,
                'arrivalGate' => $arrivalGate,
                'arrivalDelay' => $arrivalDelay,
                'duration' => $durationFormatted,
                'durationMinutes' => $durationMinutes,
                'stops' => $stops,
                'aircraft' => $aircraft,
                'status' => $flight['flight_status'] ?? 'scheduled',
                'price' => $price,
                'date' => $flightDate,
                'source' => 'api',
                'flight_id' => $id
            ];
        }
        
        return $formattedFlights;
    }

    /**
     * Format duration minutes into human-readable string
     * 
     * @param int $minutes Duration in minutes
     * @return string Formatted duration (e.g., "2h 15m")
     */
    private function formatDuration($minutes) 
    {
        $hours = floor($minutes / 60);
        $mins = $minutes % 60;
        return $hours . 'h ' . str_pad($mins, 2, '0', STR_PAD_LEFT) . 'm';
    }
}

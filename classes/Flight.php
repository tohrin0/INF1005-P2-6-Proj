<?php

class Flight {
    private $flightNumber;
    private $departure;
    private $arrival;
    private $duration;
    private $price;
    private $date;
    private $time;
    private $availableSeats;
    private $airline;
    private $status;
    private $departureTerminal;
    private $departureGate;
    private $arrivalTerminal;
    private $arrivalGate;
    private $flightApi; // New property for API flight ID
    private $db;

    public function __construct($flightNumber, $departure, $arrival, $duration = 0, $price = 0) {
        global $pdo;
        $this->db = $pdo;
        $this->flightNumber = $flightNumber;
        $this->departure = $departure;
        $this->arrival = $arrival;
        $this->duration = $duration;
        $this->price = $price;
        $this->availableSeats = 100; // Default value
        $this->status = 'scheduled'; // Default value
        $this->flightApi = null; // Default value
    }

    // Getters
    public function getFlightNumber() {
        return $this->flightNumber;
    }

    public function getDeparture() {
        return $this->departure;
    }

    public function getArrival() {
        return $this->arrival;
    }

    public function getDuration() {
        return $this->duration;
    }

    public function getPrice() {
        return $this->price;
    }

    public function getDate() {
        return $this->date;
    }

    public function getTime() {
        return $this->time;
    }

    public function getAvailableSeats() {
        return $this->availableSeats;
    }

    public function getAirline() {
        return $this->airline;
    }

    public function getStatus() {
        return $this->status;
    }

    // Add getter for flight_api
    public function getFlightApi() {
        return $this->flightApi;
    }

    // Setters
    public function setFlightNumber($flightNumber) {
        $this->flightNumber = $flightNumber;
    }

    public function setDeparture($departure) {
        $this->departure = $departure;
    }

    public function setArrival($arrival) {
        $this->arrival = $arrival;
    }

    public function setDuration($duration) {
        $this->duration = $duration;
    }

    public function setPrice($price) {
        $this->price = $price;
    }

    public function setDate($date) {
        $this->date = $date;
    }

    public function setTime($time) {
        $this->time = $time;
    }

    public function setAvailableSeats($availableSeats) {
        $this->availableSeats = $availableSeats;
    }

    public function setAirline($airline) {
        $this->airline = $airline;
    }

    public function setStatus($status) {
        $this->status = $status;
    }

    public function setDepartureTerminal($terminal) {
        $this->departureTerminal = $terminal;
    }

    public function setDepartureGate($gate) {
        $this->departureGate = $gate;
    }

    public function setArrivalTerminal($terminal) {
        $this->arrivalTerminal = $terminal;
    }

    public function setArrivalGate($gate) {
        $this->arrivalGate = $gate;
    }

    // Add setter for flight_api
    public function setFlightApi($flightApi) {
        $this->flightApi = $flightApi;
    }

    /**
     * Set properties from an array of flight data
     * 
     * @param array $flightData Array containing flight details
     * @return Flight Returns the current Flight instance
     */
    public function setFromArray($flightData) {
        if (isset($flightData['date'])) $this->setDate($flightData['date']);
        if (isset($flightData['time'])) $this->setTime($flightData['time']);
        if (isset($flightData['available_seats'])) $this->setAvailableSeats($flightData['available_seats']);
        if (isset($flightData['airline'])) $this->setAirline($flightData['airline']);
        if (isset($flightData['status'])) $this->setStatus($flightData['status']);
        if (isset($flightData['departure_terminal'])) $this->setDepartureTerminal($flightData['departure_terminal']);
        if (isset($flightData['departure_gate'])) $this->setDepartureGate($flightData['departure_gate']);
        if (isset($flightData['arrival_terminal'])) $this->setArrivalTerminal($flightData['arrival_terminal']);
        if (isset($flightData['arrival_gate'])) $this->setArrivalGate($flightData['arrival_gate']);
        if (isset($flightData['flight_api'])) $this->setFlightApi($flightData['flight_api']); // Add flight_api
        
        return $this;
    }

    /**
     * Convert Flight object to array
     * 
     * @return array Flight data as associative array
     */
    public function toArray() {
        return [
            'flight_number' => $this->flightNumber,
            'flight_api' => $this->flightApi, // Add flight_api
            'departure' => $this->departure,
            'arrival' => $this->arrival,
            'duration' => $this->duration,
            'price' => $this->price,
            'date' => $this->date,
            'time' => $this->time,
            'available_seats' => $this->availableSeats,
            'airline' => $this->airline,
            'status' => $this->status,
            'departure_terminal' => $this->departureTerminal,
            'departure_gate' => $this->departureGate,
            'arrival_terminal' => $this->arrivalTerminal,
            'arrival_gate' => $this->arrivalGate
        ];
    }

    /**
     * Find flight by flight number and date
     * 
     * @param string $flightNumber The flight number to search for
     * @param string $date The flight date in YYYY-MM-DD format
     * @return Flight|null Returns Flight object if found, null otherwise
     */
    public static function findByFlightNumberAndDate($flightNumber, $date) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM flights WHERE flight_number = ? AND date = ?");
            $stmt->execute([$flightNumber, $date]);
            $flightData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$flightData) {
                return null;
            }
            
            $flight = new Flight(
                $flightData['flight_number'],
                $flightData['departure'],
                $flightData['arrival'],
                $flightData['duration'],
                $flightData['price']
            );
            
            $flight->setFromArray($flightData);
            return $flight;
        } catch (PDOException $e) {
            error_log("Error finding flight: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Find flight by ID
     * 
     * @param int $id Flight ID
     * @return Flight|null Returns Flight object if found, null otherwise
     */
    public static function findById($id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM flights WHERE id = ?");
            $stmt->execute([$id]);
            $flightData = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$flightData) {
                return null;
            }
            
            $flight = new Flight(
                $flightData['flight_number'],
                $flightData['departure'],
                $flightData['arrival'],
                $flightData['duration'],
                $flightData['price']
            );
            
            $flight->setFromArray($flightData);
            return $flight;
        } catch (PDOException $e) {
            error_log("Error finding flight by ID: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get all flights from database
     * 
     * @return array Array of Flight objects
     */
    public static function getAll() {
        global $pdo;
        $flights = [];
        
        try {
            $stmt = $pdo->query("SELECT * FROM flights ORDER BY date, time");
            $flightRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($flightRecords as $record) {
                $flight = new Flight(
                    $record['flight_number'],
                    $record['departure'],
                    $record['arrival'],
                    $record['duration'],
                    $record['price']
                );
                $flight->setFromArray($record);
                $flights[] = $flight;
            }
            
            return $flights;
        } catch (PDOException $e) {
            error_log("Error getting all flights: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Search flights by departure, arrival and date
     * 
     * @param string $departure Departure location
     * @param string $arrival Arrival location
     * @param string $date Flight date
     * @return array Array of matching Flight objects
     */
    public static function search($departure, $arrival, $date) {
        global $pdo;
        $flights = [];
        
        try {
            $query = "SELECT * FROM flights 
                     WHERE 1=1";
            $params = [];
            
            if (!empty($departure)) {
                $query .= " AND departure LIKE ?";
                $params[] = "%$departure%";
            }
            
            if (!empty($arrival)) {
                $query .= " AND arrival LIKE ?";
                $params[] = "%$arrival%";
            }
            
            if (!empty($date)) {
                $query .= " AND date = ?";
                $params[] = date('Y-m-d', strtotime($date));
            }
            
            $query .= " ORDER BY date, time";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $flightRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($flightRecords as $record) {
                $flight = new Flight(
                    $record['flight_number'],
                    $record['departure'],
                    $record['arrival'],
                    $record['duration'],
                    $record['price']
                );
                $flight->setFromArray($record);
                $flights[] = $flight;
            }
            
            return $flights;
        } catch (PDOException $e) {
            error_log("Error searching flights: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Save flight to database (insert or update)
     * 
     * @return int|bool Returns inserted ID or true on success, false on failure
     */
    public function save() {
        try {
            // Check if flight already exists
            $stmt = $this->db->prepare("SELECT id FROM flights WHERE flight_number = ? AND date = ?");
            $stmt->execute([$this->flightNumber, $this->date]);
            $existingFlight = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingFlight) {
                // Update existing flight
                $stmt = $this->db->prepare(
                    "UPDATE flights SET 
                    departure = ?,
                    arrival = ?,
                    time = ?,
                    duration = ?,
                    price = ?,
                    available_seats = ?,
                    airline = ?,
                    departure_gate = ?,
                    arrival_gate = ?,
                    departure_terminal = ?,
                    arrival_terminal = ?,
                    status = ?,
                    flight_api = ?
                    WHERE id = ?"
                );
                
                $result = $stmt->execute([
                    $this->departure,
                    $this->arrival,
                    $this->time,
                    $this->duration,
                    $this->price,
                    $this->availableSeats,
                    $this->airline,
                    $this->departureGate,
                    $this->arrivalGate,
                    $this->departureTerminal,
                    $this->arrivalTerminal,
                    $this->status,
                    $this->flightApi ?? null, // Add flight_api
                    $existingFlight['id']
                ]);
                
                return $result ? $existingFlight['id'] : false;
            } else {
                // Insert new flight
                $stmt = $this->db->prepare(
                    "INSERT INTO flights (
                        flight_number, flight_api, departure, arrival, date, time, duration, price, 
                        available_seats, airline, departure_gate, arrival_gate, 
                        departure_terminal, arrival_terminal, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                $result = $stmt->execute([
                    $this->flightNumber,
                    $this->flightApi ?? null, // Add flight_api
                    $this->departure,
                    $this->arrival,
                    $this->date,
                    $this->time,
                    $this->duration,
                    $this->price,
                    $this->availableSeats,
                    $this->airline,
                    $this->departureGate,
                    $this->arrivalGate,
                    $this->departureTerminal,
                    $this->arrivalTerminal,
                    $this->status
                ]);
                
                return $result ? $this->db->lastInsertId() : false;
            }
        } catch (PDOException $e) {
            error_log("Error saving flight: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update available seats for a flight
     * 
     * @param int $bookedSeats Number of seats to reduce from available
     * @return bool True on success, false on failure
     */
    public function updateSeats($bookedSeats = 1) {
        try {
            $stmt = $this->db->prepare(
                "UPDATE flights SET available_seats = available_seats - ? 
                 WHERE flight_number = ? AND date = ? AND available_seats >= ?"
            );
            
            $result = $stmt->execute([
                $bookedSeats,
                $this->flightNumber,
                $this->date,
                $bookedSeats
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->availableSeats -= $bookedSeats;
                return true;
            }
            
            return false;
        } catch (PDOException $e) {
            error_log("Error updating seats: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete flight from database
     * 
     * @param int $id Flight ID
     * @return bool True on success, false on failure
     */
    public static function delete($id) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("DELETE FROM flights WHERE id = ?");
            return $stmt->execute([$id]);
        } catch (PDOException $e) {
            error_log("Error deleting flight: " . $e->getMessage());
            return false;
        }
    }
}
<?php
// Debug script to check database structure
require_once 'inc/config.php';
require_once 'inc/db.php';

echo "<h1>Checking Database Tables</h1>";

try {
    // Check bookings table structure
    $result = $pdo->query("DESCRIBE bookings");
    echo "<h2>Bookings Table Structure:</h2>";
    echo "<pre>";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    echo "</pre>";
    
    // Test insert with hardcoded values
    echo "<h2>Testing Insert:</h2>";
    $testInsert = $pdo->prepare("
        INSERT INTO bookings 
        (user_id, flight_id, status, customer_name, customer_email, customer_phone, passengers, total_price)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $testResult = $testInsert->execute([
        $_SESSION['user_id'] ?? 1, // User ID
        'test_flight_123',  // Flight ID
        'test',             // Status
        'Test User',        // Name
        'test@example.com', // Email
        '123456789',        // Phone
        1,                  // Passengers
        100.00              // Total price
    ]);
    
    if ($testResult) {
        echo "<p>Test insert successful. ID: " . $pdo->lastInsertId() . "</p>";
    } else {
        echo "<p>Test insert failed:</p>";
        echo "<pre>";
        print_r($testInsert->errorInfo());
        echo "</pre>";
    }
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
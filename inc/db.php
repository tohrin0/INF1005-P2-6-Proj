<?php
// Database connection settings
$host = 'localhost'; 
$dbname = 'flight_booking'; 
$username = 'root'; 
$password = '';

try {
    // Create PDO connection
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    
    // Set error mode to exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Remove this debug message
    // error_log("Database connection established successfully");
} catch (PDOException $e) {
    // Log connection error but don't display it to users
    error_log("Database connection failed: " . $e->getMessage());
    // Don't show error details to users, just a friendly message
    die("We're experiencing technical difficulties. Please try again later.");
}

// Function to execute a query
function executeQuery($query, $params = []) {
    global $pdo;
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    return $stmt;
}

// Function to fetch all results
function fetchAll($query, $params = []) {
    return executeQuery($query, $params)->fetchAll();
}

// Function to fetch a single result
function fetchOne($query, $params = []) {
    return executeQuery($query, $params)->fetch();
}
?>
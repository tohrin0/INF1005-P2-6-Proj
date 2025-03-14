<?php
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Simple database query to check connection
    $stmt = $pdo->query("SELECT 1");
    echo "<p style='color:green'>Database connection successful!</p>";
} catch (Exception $e) {
    echo "<p style='color:red'>Database connection failed: " . $e->getMessage() . "</p>";
}

// Print current session data
echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

exit;

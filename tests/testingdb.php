<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Include the database connection
require_once 'inc/db.php';

echo "<h1>Database Connection Test</h1>";

try {
    // Test the connection
    if ($pdo) {
        echo "<div style='color:green;'>✅ Database connection successful!</div>";
        
        // Get database name from connection
        $stmt = $pdo->query("SELECT DATABASE()");
        $dbName = $stmt->fetchColumn();
        
        echo "<h2>Current database: " . htmlspecialchars($dbName) . "</h2>";
        
        // Get all tables without using DB_NAME
        $tables = array();
        $stmt = $pdo->query("SHOW TABLES");
        
        echo "<h2>Tables in database:</h2>";
        
        if ($stmt->rowCount() > 0) {
            echo "<ul>";
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                echo "<li>" . htmlspecialchars($row[0]) . "</li>";
                
                // Show table structure
                $tableInfo = $pdo->query("DESCRIBE `" . $row[0] . "`");
                echo "<ul>";
                while ($column = $tableInfo->fetch(PDO::FETCH_ASSOC)) {
                    echo "<li>" . htmlspecialchars($column['Field']) . " - " . 
                         htmlspecialchars($column['Type']) . " " . 
                         ($column['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . " " .
                         ($column['Key'] === 'PRI' ? '[PRIMARY KEY]' : '') . "</li>";
                }
                echo "</ul>";
            }
            echo "</ul>";
        } else {
            echo "<p>No tables found in the database.</p>";
        }
        
        // Show environment info
        echo "<h2>Environment Information:</h2>";
        echo "<ul>";
        echo "<li>PHP Version: " . phpversion() . "</li>";
        echo "<li>Server: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "</li>";
        echo "<li>HTTP Method Detection: " . (isset($_SERVER['REQUEST_METHOD']) ? 'Working' : 'Not working') . "</li>";
        
        // Display more information about the database connection
        echo "<li>PDO Driver: " . $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) . "</li>";
        echo "<li>Server Version: " . $pdo->getAttribute(PDO::ATTR_SERVER_VERSION) . "</li>";
        echo "</ul>";
        
    } else {
        echo "<div style='color:red;'>❌ Database connection failed!</div>";
    }
} catch (PDOException $e) {
    echo "<div style='color:red;'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>
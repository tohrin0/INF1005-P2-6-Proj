<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check if the user is logged in and has admin privileges
if (!isLoggedIn() || !isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$error = '';
$success = '';

// Handle form submission for updating settings
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle General Settings
    if (isset($_POST['update_general_settings'])) {
        $siteName = $_POST['site_name'] ?? '';
        $siteEmail = $_POST['site_email'] ?? '';
        $sitePhone = $_POST['site_phone'] ?? '';

        // Validate inputs
        if (empty($siteName) || empty($siteEmail) || empty($sitePhone)) {
            $error = "All fields are required for general settings.";
        } else {
            // Update settings in the database
            $stmt = $pdo->prepare("UPDATE settings SET site_name = ?, site_email = ?, site_phone = ?");
            if ($stmt->execute([$siteName, $siteEmail, $sitePhone])) {
                $success = "General settings updated successfully.";
            } else {
                $error = "Failed to update general settings.";
            }
        }
    }
    
    // Handle API Keys update
    else if (isset($_POST['update_api_keys'])) {
        $apiKeys = [];
        $apiKeysValid = true;
        
        // Get all API keys from the form
        for ($i = 0; $i < 10; $i++) {
            $keyName = 'api_key_' . $i;
            $apiKey = trim($_POST[$keyName] ?? '');
            
            // Validate API key (basic check - non-empty and minimum length)
            if (!empty($apiKey)) {
                if (strlen($apiKey) < 10) {
                    $error = "API key #" . ($i + 1) . " is too short. Keys should be at least 10 characters.";
                    $apiKeysValid = false;
                    break;
                }
                $apiKeys[] = $apiKey;
            }
        }
        
        // Ensure at least one API key is provided
        if (empty($apiKeys)) {
            $error = "At least one API key is required.";
            $apiKeysValid = false;
        }
        
        // If all keys are valid, update the config file
        if ($apiKeysValid) {
            try {
                // Create a backup of the current config.php file
                $configFile = '../inc/config.php';
                $backupFile = '../inc/config.backup.' . date('Y-m-d-His') . '.php';
                
                if (file_exists($configFile)) {
                    copy($configFile, $backupFile);
                }
                
                // Read the current config file
                $configContent = file_get_contents($configFile);
                
                // Find and replace the API keys section
                $pattern = "/define\('FLIGHT_API_KEYS', json_encode\(\[(.*?)\]\)\);/s";
                $apiKeysString = "'" . implode("',\n    '", $apiKeys) . "'";
                $replacement = "define('FLIGHT_API_KEYS', json_encode([\n    $apiKeysString\n]));";
                
                $newConfigContent = preg_replace($pattern, $replacement, $configContent);
                
                // Also update the FLIGHT_API_KEY constant for backward compatibility
                $pattern2 = "/define\('FLIGHT_API_KEY', json_decode\(FLIGHT_API_KEYS, true\)\[\d+\]\);/";
                $replacement2 = "define('FLIGHT_API_KEY', json_decode(FLIGHT_API_KEYS, true)[0]);";
                $newConfigContent = preg_replace($pattern2, $replacement2, $newConfigContent);
                
                // Write the updated content back to the config file
                if (file_put_contents($configFile, $newConfigContent) !== false) {
                    $success = "API keys updated successfully. The previous configuration has been backed up.";
                    
                    // Update the API key status table in the database
                    try {
                        // Check if we have a table to track API key status
                        $tableExists = $pdo->query("SHOW TABLES LIKE 'api_keys'")->rowCount() > 0;
                        
                        // Create table if it doesn't exist
                        if (!$tableExists) {
                            $pdo->exec("CREATE TABLE api_keys (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                key_index INT NOT NULL,
                                api_key VARCHAR(255) NOT NULL,
                                is_working BOOLEAN DEFAULT TRUE,
                                last_error TEXT,
                                last_used TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                request_count INT DEFAULT 0
                            )");
                        }
                        
                        // Reset the table
                        $pdo->exec("TRUNCATE TABLE api_keys");
                        
                        // Insert new keys
                        $stmt = $pdo->prepare("INSERT INTO api_keys (key_index, api_key, is_working, request_count) VALUES (?, ?, 1, 0)");
                        foreach ($apiKeys as $index => $key) {
                            $stmt->execute([$index, $key]);
                        }
                    } catch (Exception $e) {
                        // Just log the error but don't display to user since the file update worked
                        error_log("Error updating API keys table: " . $e->getMessage());
                    }
                } else {
                    $error = "Failed to update API keys. Check file permissions of the config.php file.";
                }
            } catch (Exception $e) {
                $error = "An error occurred while updating API keys: " . $e->getMessage();
            }
        }
    }
}

// Fetch current settings from the database
$stmt = $pdo->query("SELECT * FROM settings LIMIT 1");
$currentSettings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current API keys from the config file
$currentApiKeys = json_decode(FLIGHT_API_KEYS, true) ?? [];

// Pad array to always have 10 elements (for form fields)
while (count($currentApiKeys) < 10) {
    $currentApiKeys[] = '';
}

// Limit to 10 keys if there are somehow more
$currentApiKeys = array_slice($currentApiKeys, 0, 10);

// Include header
include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Site Settings</h1>
            <p class="text-gray-600">Manage system settings and configurations</p>
        </div>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($error) ?></p>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
            <p><?= htmlspecialchars($success) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="bg-white rounded-lg shadow-md p-6 mb-8">
        <h2 class="text-xl font-semibold mb-4 pb-2 border-b">General Settings</h2>
        
        <form action="" method="POST">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <div>
                    <label for="site_name" class="block mb-2 text-sm font-medium text-gray-700">Site Name</label>
                    <input type="text" name="site_name" id="site_name" value="<?= htmlspecialchars($currentSettings['site_name'] ?? 'Flight Booking Website') ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                
                <div>
                    <label for="site_email" class="block mb-2 text-sm font-medium text-gray-700">Site Email</label>
                    <input type="email" name="site_email" id="site_email" value="<?= htmlspecialchars($currentSettings['site_email'] ?? 'info@flightbooking.com') ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md" required>
                </div>
                
                <div>
                    <label for="site_phone" class="block mb-2 text-sm font-medium text-gray-700">Site Phone</label>
                    <input type="text" name="site_phone" id="site_phone" value="<?= htmlspecialchars($currentSettings['site_phone'] ?? '+1234567890') ?>" 
                           class="w-full p-2 border border-gray-300 rounded-md" required>
                </div>
            </div>
            
            <div>
                <button type="submit" name="update_general_settings" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Update General Settings
                </button>
            </div>
        </form>
    </div>
    
    <div class="bg-white rounded-lg shadow-md p-6">
        <h2 class="text-xl font-semibold mb-4 pb-2 border-b">API Settings</h2>
        
        <form action="" method="POST">
            <div class="mb-6">
                <p class="text-gray-700 mb-4">
                    Configure your AviationStack API keys below. The system will try each key in order until a working one is found.
                    At least one valid API key is required for flight search functionality to work properly.
                </p>
                
                <div class="space-y-4">
                    <?php foreach ($currentApiKeys as $index => $apiKey): ?>
                        <div>
                            <label for="api_key_<?= $index ?>" class="block mb-1 text-sm font-medium text-gray-700">
                                API Key #<?= $index + 1 ?>
                            </label>
                            <input type="text" name="api_key_<?= $index ?>" id="api_key_<?= $index ?>" 
                                   value="<?= htmlspecialchars($apiKey) ?>" 
                                   class="w-full p-2 border border-gray-300 rounded-md"
                                   <?= $index === 0 ? 'required' : '' ?>>
                            <?php if ($index === 0): ?>
                                <p class="mt-1 text-sm text-gray-500">Primary key (required)</p>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <svg class="h-5 w-5 text-yellow-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-yellow-700">
                            <strong>Warning:</strong> Changing API keys will update the config.php file directly. A backup of the current configuration will be created automatically.
                        </p>
                    </div>
                </div>
            </div>
            
            <div>
                <button type="submit" name="update_api_keys" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                    Update API Keys
                </button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
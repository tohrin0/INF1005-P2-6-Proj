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

<div class="container mx-auto px-4 py-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">System Settings</h1>
        <p class="text-gray-600">Configure your application's core settings and API integrations</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="mb-6 p-4 rounded-lg bg-red-50 border-l-4 border-red-500 text-red-700 animate-fade-in" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-red-100 text-red-500">
                        !
                    </span>
                </div>
                <div class="ml-3">
                    <p class="font-medium"><?= htmlspecialchars($error) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="mb-6 p-4 rounded-lg bg-green-50 border-l-4 border-green-500 text-green-700 animate-fade-in" role="alert">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <span class="inline-flex items-center justify-center h-8 w-8 rounded-full bg-green-100 text-green-500">
                        ✓
                    </span>
                </div>
                <div class="ml-3">
                    <p class="font-medium"><?= htmlspecialchars($success) ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Side Navigation -->
        <div class="lg:col-span-1">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 sticky top-6">
                <div class="p-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-800">Settings Menu</h2>
                </div>
                <nav class="p-2">
                    <ul class="space-y-1">
                        <li>
                            <a href="#general-settings" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                <span class="inline-block mr-3 text-lg">⚙️</span>
                                <span>General Settings</span>
                            </a>
                        </li>
                        <li>
                            <a href="#api-settings" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                <span class="inline-block mr-3 text-lg">🔑</span>
                                <span>API Configuration</span>
                            </a>
                        </li>
                        <li>
                            <a href="maintenance.php" class="flex items-center px-4 py-3 text-gray-700 rounded-lg hover:bg-blue-50 hover:text-blue-700 transition-colors">
                                <span class="inline-block mr-3 text-lg">🔧</span>
                                <span>System Maintenance</span>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="lg:col-span-2 space-y-8">
            <!-- General Settings -->
            <div id="general-settings" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-blue-50 to-indigo-50 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <span class="inline-block mr-3 text-xl">⚙️</span>
                        General Settings
                    </h2>
                    <p class="text-gray-600 mt-1">Basic configuration for your website</p>
                </div>
                
                <div class="p-6">
                    <form action="" method="POST">
                        <div class="space-y-6 mb-8">
                            <div>
                                <label for="site_name" class="block mb-2 font-medium text-gray-700">Site Name</label>
                                <input type="text" name="site_name" id="site_name" value="<?= htmlspecialchars($currentSettings['site_name'] ?? 'Flight Booking Website') ?>" 
                                    class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <p class="mt-1 text-sm text-gray-500">This appears in the browser title and various places throughout the site.</p>
                            </div>
                            
                            <div>
                                <label for="site_email" class="block mb-2 font-medium text-gray-700">Contact Email</label>
                                <input type="email" name="site_email" id="site_email" value="<?= htmlspecialchars($currentSettings['site_email'] ?? 'info@flightbooking.com') ?>" 
                                    class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <p class="mt-1 text-sm text-gray-500">Primary contact email displayed to users.</p>
                            </div>
                            
                            <div>
                                <label for="site_phone" class="block mb-2 font-medium text-gray-700">Contact Phone</label>
                                <input type="text" name="site_phone" id="site_phone" value="<?= htmlspecialchars($currentSettings['site_phone'] ?? '+1234567890') ?>" 
                                    class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent" required>
                                <p class="mt-1 text-sm text-gray-500">Support phone number displayed to users.</p>
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit" name="update_general_settings" class="px-6 py-3 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 focus:outline-none transition-colors">
                                Update General Settings
                            </button>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- API Settings -->
            <div id="api-settings" class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="p-6 bg-gradient-to-r from-indigo-50 to-purple-50 border-b border-gray-100">
                    <h2 class="text-xl font-semibold text-gray-800 flex items-center">
                        <span class="inline-block mr-3 text-xl">🔑</span>
                        API Configuration
                    </h2>
                    <p class="text-gray-600 mt-1">Manage integration with external flight data services</p>
                </div>
                
                <div class="p-6">
                    <form action="" method="POST">
                        <div class="mb-6">
                            <div class="p-4 bg-blue-50 rounded-lg mb-6">
                                <p class="text-blue-800">
                                    Configure your AviationStack API keys below. The system will try each key in order until a working one is found.
                                    At least one valid API key is required for flight search functionality.
                                </p>
                            </div>
                            
                            <div class="space-y-4">
                                <?php foreach ($currentApiKeys as $index => $apiKey): ?>
                                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-100">
                                        <label for="api_key_<?= $index ?>" class="block mb-2 font-medium text-gray-700 flex justify-between">
                                            <span>API Key #<?= $index + 1 ?></span>
                                            <?php if ($index === 0): ?>
                                                <span class="text-sm bg-blue-100 text-blue-800 px-2 py-1 rounded">Primary</span>
                                            <?php endif; ?>
                                        </label>
                                        <input type="text" name="api_key_<?= $index ?>" id="api_key_<?= $index ?>" 
                                            value="<?= htmlspecialchars($apiKey) ?>" 
                                            class="w-full p-3 border border-gray-300 rounded-lg shadow-sm focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                                            <?= $index === 0 ? 'required' : '' ?>>
                                        <?php if ($index === 0): ?>
                                            <p class="mt-1 text-sm text-gray-500">This is the primary key and is required.</p>
                                        <?php else: ?>
                                            <p class="mt-1 text-sm text-gray-500">Backup key used if previous keys fail or exceed limits.</p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="p-4 bg-yellow-50 border border-yellow-100 rounded-lg mb-6">
                            <div class="flex">
                                <div class="ml-3">
                                    <h3 class="text-sm font-medium text-yellow-800">Important Note</h3>
                                    <p class="text-sm text-yellow-700 mt-1">
                                        Changing API keys will update the config.php file directly. A backup of the current configuration will be created automatically.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <button type="submit" name="update_api_keys" class="px-6 py-3 bg-indigo-600 text-white font-medium rounded-lg hover:bg-indigo-700 focus:ring-4 focus:ring-indigo-300 focus:outline-none transition-colors">
                                Update API Keys
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
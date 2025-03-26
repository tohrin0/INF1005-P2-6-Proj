<?php
session_start();
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';

// Check admin access
if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

$message = '';
$messageType = '';

// Handle cron job execution
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['run_cron'])) {
        $cronJob = $_POST['cron_job'];
        
        try {
            switch ($cronJob) {
                case 'delete_accounts':
                    // Include the cron script but capture its output
                    ob_start();
                    include_once 'cron/delete-accounts.php';
                    $result = ob_get_clean();
                    
                    $message = "Delete accounts job completed: " . $result;
                    $messageType = "success";
                    break;
                    
                // Add more cron jobs here as needed
                    
                default:
                    $message = "Unknown cron job.";
                    $messageType = "error";
            }
        } catch (Exception $e) {
            $message = "Error executing cron job: " . $e->getMessage();
            $messageType = "error";
        }
    }
}

// Log last run times (you could store these in the database instead)
$lastRunTimes = [
    'delete_accounts' => file_exists('cron/logs/delete_accounts.log') ? 
        date('Y-m-d H:i:s', filemtime('cron/logs/delete_accounts.log')) : 'Never run'
];

// Create logs directory if not exists
if (!file_exists('cron/logs')) {
    mkdir('cron/logs', 0755, true);
}

include 'includes/header.php';
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6 flex justify-between items-center">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 mb-2">Cron Jobs Manager</h1>
            <p class="text-gray-600">Manually run scheduled tasks</p>
        </div>
    </div>
    
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded-lg shadow-sm border <?php echo $messageType === 'error' ? 'bg-red-50 border-red-200 text-red-700' : 'bg-green-50 border-green-200 text-green-700'; ?>">
            <p><?= htmlspecialchars($message) ?></p>
        </div>
    <?php endif; ?>
    
    <div class="grid grid-cols-1 gap-6">
        <!-- Delete Accounts Job -->
        <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
            <div class="flex justify-between items-start mb-4">
                <div>
                    <h2 class="text-xl font-semibold text-gray-800">Delete Inactive Accounts</h2>
                    <p class="text-gray-600 mt-1">Permanently delete user accounts that have been scheduled for deletion more than 24 hours ago.</p>
                </div>
                <div class="px-3 py-1 bg-blue-100 text-blue-800 text-xs font-medium rounded-md">
                    System
                </div>
            </div>
            
            <div class="mb-4 p-4 bg-gray-50 rounded-md">
                <div class="flex items-center mb-2">
                    <span class="text-gray-600 font-medium mr-2">Last run:</span>
                    <span class="text-gray-800"><?= $lastRunTimes['delete_accounts'] ?></span>
                </div>
                <div class="flex items-center">
                    <span class="text-gray-600 font-medium mr-2">Normal schedule:</span>
                    <span class="text-gray-800">Every hour</span>
                </div>
            </div>
            
            <form method="POST" action="">
                <input type="hidden" name="cron_job" value="delete_accounts">
                <button type="submit" name="run_cron" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors inline-flex items-center">
                    <span class="mr-2">Run Now</span>
                    <i class="fas fa-play-circle"></i>
                </button>
            </form>
        </div>
        
        <!-- You can add more cron job cards here following the same pattern -->
    </div>
</div>

<?php include 'includes/footer.php'; ?>
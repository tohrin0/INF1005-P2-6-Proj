<?php
// This script should be run by a cron job every hour or so
require_once __DIR__ . '/../../inc/config.php';
require_once __DIR__ . '/../../inc/db.php';
require_once __DIR__ . '/../../inc/functions.php';

// Find accounts scheduled for deletion over 24 hours ago
$stmt = $pdo->prepare("
    SELECT id, username, email 
    FROM users 
    WHERE deletion_requested IS NOT NULL 
    AND deletion_requested < DATE_SUB(NOW(), INTERVAL 24 HOUR)
");
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$deletedCount = 0;

foreach ($users as $user) {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Log the deletion
        error_log("Deleting user account: ID " . $user['id'] . ", Username: " . $user['username'] . ", Email: " . $user['email']);
        
        // Delete related records
        // First, get all bookings for this user
        $bookingStmt = $pdo->prepare("SELECT id FROM bookings WHERE user_id = ?");
        $bookingStmt->execute([$user['id']]);
        $bookings = $bookingStmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($bookings as $booking) {
            // Delete passengers
            $stmt = $pdo->prepare("DELETE FROM passengers WHERE booking_id = ?");
            $stmt->execute([$booking['id']]);
            
            // Delete payments
            $stmt = $pdo->prepare("DELETE FROM payments WHERE booking_id = ?");
            $stmt->execute([$booking['id']]);
        }
        
        // Delete bookings
        $stmt = $pdo->prepare("DELETE FROM bookings WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        // Delete password history
        $stmt = $pdo->prepare("DELETE FROM password_history WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        // Finally, delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $result = $stmt->execute([$user['id']]);
        
        if ($result) {
            $pdo->commit();
            $deletedCount++;
        } else {
            $pdo->rollBack();
            error_log("Failed to delete user: " . $user['id']);
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log("Error deleting user " . $user['id'] . ": " . $e->getMessage());
    }
}

echo "Deleted $deletedCount accounts\n";
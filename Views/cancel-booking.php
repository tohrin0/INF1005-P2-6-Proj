<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();
require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/db.php';
require_once __DIR__ . '/../inc/functions.php';
require_once __DIR__ . '/../inc/auth.php';
require_once __DIR__ . '/../classes/Booking.php';

// Ensure user is logged in
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'You must be logged in to cancel a booking.']);
    exit;
}

// Check CSRF token
if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid token. Please refresh the page and try again.']);
    exit;
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get booking ID from request
$bookingId = $_POST['booking_id'] ?? null;
$userId = $_SESSION['user_id'];

if (!$bookingId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Booking ID is required.']);
    exit;
}

try {
    // Create booking object
    $bookingObj = new Booking();
    
    // Get the booking to verify it belongs to the user
    $booking = $bookingObj->getBookingById($bookingId, $userId);
    
    if (!$booking) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Booking not found or does not belong to you.']);
        exit;
    }
    
    // Only allow cancellation of pending bookings
    if ($booking['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Only pending bookings can be cancelled.']);
        exit;
    }
    
    // Update booking status to cancelled - this will trigger the email notification
    if ($bookingObj->updateBookingStatus($bookingId, 'canceled')) {
        echo json_encode(['success' => true, 'message' => 'Booking has been cancelled successfully. A confirmation email has been sent.']);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to cancel booking. Please try again.']);
    }
    
} catch (Exception $e) {
    error_log("Cancel booking error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
?>
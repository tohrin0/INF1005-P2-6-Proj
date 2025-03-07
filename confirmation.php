<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? null;
$booking = null;

if (!$booking_id) {
    header('Location: index.php');
    exit();
}

try {
    // Get booking details
    $stmt = $pdo->prepare(
        "SELECT b.*, f.flight_number, f.departure, f.arrival, f.date as flight_date, f.time 
         FROM bookings b
         JOIN flights f ON b.flight_id = f.id
         WHERE b.id = ? AND b.user_id = ?"
    );
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception("Booking not found or you don't have permission to view it.");
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

include 'templates/header.php';
?>

<div class="container">
    <div class="confirmation-page">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <div class="mt-3">
                <a href="search.php" class="btn-primary">Back to Search</a>
            </div>
        <?php else: ?>
            <div class="confirmation-card">
                <div class="confirmation-header">
                    <h1>Booking Confirmed!</h1>
                    <div class="booking-id">
                        <strong>Booking ID:</strong> <?php echo htmlspecialchars($booking['id']); ?>
                    </div>
                    <div class="booking-status <?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                        <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                    </div>
                </div>
                
                <div class="customer-info">
                    <h2>Customer Information</h2>
                    <p><strong>Name:</strong> <?php echo htmlspecialchars($booking['customer_name']); ?></p>
                    <p><strong>Email:</strong> <?php echo htmlspecialchars($booking['customer_email']); ?></p>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($booking['customer_phone']); ?></p>
                    <p><strong>Number of Passengers:</strong> <?php echo htmlspecialchars($booking['passengers']); ?></p>
                </div>
                
                <div class="flight-info">
                    <h2>Flight Information</h2>
                    <div class="flight-detail-row">
                        <div class="flight-detail">
                            <span class="label">Flight Number:</span>
                            <span class="value"><?php echo htmlspecialchars($booking['flight_number']); ?></span>
                        </div>
                        <div class="flight-detail">
                            <span class="label">Date:</span>
                            <span class="value"><?php echo htmlspecialchars(date('F j, Y', strtotime($booking['flight_date']))); ?></span>
                        </div>
                    </div>
                    
                    <div class="flight-detail-row">
                        <div class="flight-detail">
                            <span class="label">Departure:</span>
                            <span class="value"><?php echo htmlspecialchars($booking['departure']); ?></span>
                        </div>
                        <div class="flight-detail">
                            <span class="label">Time:</span>
                            <span class="value"><?php echo htmlspecialchars(date('g:i A', strtotime($booking['time']))); ?></span>
                        </div>
                    </div>
                    
                    <div class="flight-detail-row">
                        <div class="flight-detail">
                            <span class="label">Arrival:</span>
                            <span class="value"><?php echo htmlspecialchars($booking['arrival']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="payment-info">
                    <h2>Payment Information</h2>
                    <div class="total-price">
                        <span class="label">Total Price:</span>
                        <span class="value price">$<?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?></span>
                    </div>
                </div>
                
                <div class="confirmation-actions">
                    <a href="index.php" class="btn btn-primary">Return to Home</a>
                    <a href="account.php" class="btn btn-secondary">View All Bookings</a>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
    .confirmation-page {
        padding: 30px 0;
    }
    
    .confirmation-card {
        background-color: #ffffff;
        border-radius: 8px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        padding: 30px;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .confirmation-header {
        text-align: center;
        padding-bottom: 25px;
        border-bottom: 1px solid #eee;
        position: relative;
    }
    
    .confirmation-header h1 {
        color: #28a745;
        margin-bottom: 15px;
    }
    
    .booking-id {
        font-size: 1.1em;
        margin-bottom: 5px;
    }
    
    .booking-status {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 20px;
        font-weight: bold;
        font-size: 0.9em;
        margin-top: 10px;
    }
    
    .booking-status.confirmed {
        background-color: #d4edda;
        color: #155724;
    }
    
    .booking-status.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .booking-status.canceled {
        background-color: #f8d7da;
        color: #721c24;
    }
    
    .customer-info, .flight-info, .payment-info {
        padding: 25px 0;
        border-bottom: 1px solid #eee;
    }
    
    .customer-info h2, .flight-info h2, .payment-info h2 {
        color: #333;
        font-size: 1.5em;
        margin-bottom: 15px;
    }
    
    .customer-info p {
        margin-bottom: 10px;
        line-height: 1.6;
    }
    
    .flight-detail-row {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 15px;
    }
    
    .flight-detail {
        flex: 1;
        min-width: 200px;
        margin-bottom: 10px;
    }
    
    .flight-detail .label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
        color: #555;
    }
    
    .flight-detail .value {
        font-size: 1.1em;
    }
    
    .total-price {
        text-align: right;
        padding: 10px 0;
    }
    
    .total-price .label {
        font-weight: bold;
        font-size: 1.2em;
        margin-right: 15px;
    }
    
    .total-price .price {
        font-size: 1.5em;
        color: #28a745;
        font-weight: bold;
    }
    
    .confirmation-actions {
        padding-top: 25px;
        display: flex;
        justify-content: center;
        gap: 20px;
    }
    
    .btn {
        padding: 12px 25px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
    }
    
    .btn-primary {
        background-color: #4CAF50;
        color: white;
    }
    
    .btn-primary:hover {
        background-color: #45a049;
    }
    
    .btn-secondary {
        background-color: #f8f9fa;
        color: #212529;
        border: 1px solid #dee2e6;
    }
    
    .btn-secondary:hover {
        background-color: #e2e6ea;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .mt-3 {
        margin-top: 15px;
    }
    
    @media (max-width: 768px) {
        .confirmation-card {
            padding: 20px;
        }
        
        .flight-detail-row {
            flex-direction: column;
        }
        
        .flight-detail {
            min-width: 100%;
        }
        
        .confirmation-actions {
            flex-direction: column;
            gap: 10px;
        }
        
        .btn {
            display: block;
            text-align: center;
        }
    }
</style>

<?php include 'templates/footer.php'; ?>
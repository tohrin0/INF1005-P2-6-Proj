<?php
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/ApiClient.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$booking_id = $_GET['booking_id'] ?? null;
$booking = null;
$flightStatus = null;

if (!$booking_id) {
    header('Location: index.php');
    exit();
}

try {
    // Get booking details - Fix the missing FROM clause in the SQL query
    $stmt = $pdo->prepare(
        "SELECT b.*, f.flight_number, f.departure, f.arrival, f.date as flight_date, f.time 
         FROM bookings b
         LEFT JOIN flights f ON b.flight_id = f.id
         WHERE b.id = ? AND b.user_id = ?"
    );
    $stmt->execute([$booking_id, $user_id]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        throw new Exception("Booking not found or you don't have permission to view it.");
    }
    
    // Get real-time flight data from AviationStack API
    try {
        $apiClient = new ApiClient();
        if (isset($booking['flight_number']) && !empty($booking['flight_number'])) {
            $params = [
                'flight_iata' => $booking['flight_number'],
                'flight_date' => $booking['flight_date']
            ];
            
            $realTimeFlight = $apiClient->getFlightStatus($params);
            
            if (!empty($realTimeFlight)) {
                $flightStatus = $realTimeFlight[0]; // Get first match
            }
        }
    } catch (Exception $e) {
        // Just log the error but continue
        error_log("Error fetching real-time flight data for confirmation page: " . $e->getMessage());
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
                    
                    <?php if ($flightStatus): ?>
                    <div class="real-time-info">
                        <h3>Real-Time Flight Status</h3>
                        
                        <div class="flight-detail-row">
                            <div class="flight-detail">
                                <span class="label">Current Status:</span>
                                <span class="value status-badge <?php echo htmlspecialchars(strtolower($flightStatus['flight_status'])); ?>">
                                    <?php echo htmlspecialchars(ucfirst($flightStatus['flight_status'])); ?>
                                </span>
                            </div>
                            <?php if (isset($flightStatus['airline']['name'])): ?>
                            <div class="flight-detail">
                                <span class="label">Airline:</span>
                                <span class="value"><?php echo htmlspecialchars($flightStatus['airline']['name']); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <?php if (isset($flightStatus['departure']) && 
                            (!empty($flightStatus['departure']['terminal']) || !empty($flightStatus['departure']['gate']))): ?>
                        <div class="flight-detail-row">
                            <div class="flight-detail">
                                <span class="label">Departure Details:</span>
                                <span class="value">
                                    <?php 
                                    $details = [];
                                    if (!empty($flightStatus['departure']['terminal'])) 
                                        $details[] = "Terminal: " . htmlspecialchars($flightStatus['departure']['terminal']);
                                    if (!empty($flightStatus['departure']['gate'])) 
                                        $details[] = "Gate: " . htmlspecialchars($flightStatus['departure']['gate']);
                                    echo implode(' | ', $details);
                                    ?>
                                </span>
                            </div>
                            <?php if (!empty($flightStatus['departure']['delay']) && $flightStatus['departure']['delay'] > 0): ?>
                            <div class="flight-detail">
                                <span class="label">Departure Delay:</span>
                                <span class="value delay"><?php echo htmlspecialchars($flightStatus['departure']['delay']); ?> minutes</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($flightStatus['arrival']) && 
                            (!empty($flightStatus['arrival']['terminal']) || !empty($flightStatus['arrival']['gate']))): ?>
                        <div class="flight-detail-row">
                            <div class="flight-detail">
                                <span class="label">Arrival Details:</span>
                                <span class="value">
                                    <?php 
                                    $details = [];
                                    if (!empty($flightStatus['arrival']['terminal'])) 
                                        $details[] = "Terminal: " . htmlspecialchars($flightStatus['arrival']['terminal']);
                                    if (!empty($flightStatus['arrival']['gate'])) 
                                        $details[] = "Gate: " . htmlspecialchars($flightStatus['arrival']['gate']);
                                    if (!empty($flightStatus['arrival']['baggage'])) 
                                        $details[] = "Baggage: " . htmlspecialchars($flightStatus['arrival']['baggage']);
                                    echo implode(' | ', $details);
                                    ?>
                                </span>
                            </div>
                            <?php if (!empty($flightStatus['arrival']['delay']) && $flightStatus['arrival']['delay'] > 0): ?>
                            <div class="flight-detail">
                                <span class="label">Arrival Delay:</span>
                                <span class="value delay"><?php echo htmlspecialchars($flightStatus['arrival']['delay']); ?> minutes</span>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (isset($flightStatus['aircraft']) && !empty($flightStatus['aircraft']['iata'])): ?>
                        <div class="flight-detail-row">
                            <div class="flight-detail">
                                <span class="label">Aircraft:</span>
                                <span class="value"><?php echo htmlspecialchars($flightStatus['aircraft']['iata']); ?>
                                <?php if (!empty($flightStatus['aircraft']['registration'])): ?>
                                    (<?php echo htmlspecialchars($flightStatus['aircraft']['registration']); ?>)
                                <?php endif; ?>
                                </span>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
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
                    <a href="my-bookings.php" class="btn btn-secondary">View All Bookings</a>
                    <?php if ($booking['status'] === 'pending'): ?>
                        <form method="POST" action="payment.php">
                            <input type="hidden" name="booking_id" value="<?php echo htmlspecialchars($booking['id']); ?>">
                            <input type="hidden" name="price" value="<?php echo htmlspecialchars($booking['total_price']); ?>">
                            <button type="submit" name="pay_booking" class="btn btn-accent">Pay Now</button>
                        </form>
                    <?php endif; ?>
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
        position: relative;
        overflow: hidden;
    }
    
    .confirmation-card::before {
        content: '';
        position: absolute;
        top: 0;
        right: 0;
        width: 150px;
        height: 150px;
        background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.1) 50%, transparent 50%, transparent 100%);
        z-index: 0;
        border-radius: 0 0 0 150px;
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
        font-size: 2rem;
        font-weight: 700;
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
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .booking-status.confirmed {
        background-color: #d4edda;
        color: #155724;
    }
    
    .booking-status.pending {
        background-color: #fff3cd;
        color: #856404;
    }
    
    .booking-status.cancelled {
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
        position: relative;
        padding-bottom: 8px;
    }
    
    .customer-info h2::after, .flight-info h2::after, .payment-info h2::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: #4CAF50;
    }
    
    .customer-info p {
        margin-bottom: 10px;
        line-height: 1.6;
        display: flex;
        justify-content: space-between;
    }
    
    .flight-detail-row {
        display: flex;
        flex-wrap: wrap;
        margin-bottom: 15px;
        gap: 20px;
    }
    
    .flight-detail {
        flex: 1;
        min-width: 200px;
        margin-bottom: 10px;
        background: #f9f9f9;
        padding: 12px 15px;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
    }
    
    .flight-detail .label {
        font-weight: bold;
        display: block;
        margin-bottom: 5px;
        color: #555;
        font-size: 0.85em;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .flight-detail .value {
        font-size: 1.1em;
        color: #333;
    }
    
    .total-price {
        text-align: right;
        padding: 10px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: #f9f9f9;
        padding: 15px 20px;
        border-radius: 6px;
    }
    
    .total-price .label {
        font-weight: bold;
        font-size: 1.2em;
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
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 12px 25px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 600;
        font-size: 16px;
        transition: all 0.3s ease;
        display: inline-block;
    }
    
    .btn-primary {
        background-color: #4CAF50;
        color: white;
        border: none;
    }
    
    .btn-primary:hover {
        background-color: #45a049;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .btn-secondary {
        background-color: #f8f9fa;
        color: #212529;
        border: 1px solid #dee2e6;
    }
    
    .btn-secondary:hover {
        background-color: #e2e6ea;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.05);
    }
    
    .btn-accent {
        background-color: #007bff;
        color: white;
        border: none;
        cursor: pointer;
    }
    
    .btn-accent:hover {
        background-color: #0069d9;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
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
    
    .real-time-info {
        background-color: #f8f9fa;
        border-radius: 8px;
        padding: 20px;
        margin-top: 25px;
        border-left: 4px solid #007bff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .real-time-info h3 {
        color: #007bff;
        margin-bottom: 20px;
        font-size: 1.2em;
        display: flex;
        align-items: center;
    }
    
    .real-time-info h3::before {
        content: "●";
        color: #007bff;
        animation: pulse 2s infinite;
        margin-right: 10px;
        font-size: 0.8em;
    }
    
    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.3; }
        100% { opacity: 1; }
    }
    
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-weight: bold;
    }
    
    .status-badge.scheduled {
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }
    
    .status-badge.active {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .status-badge.landed {
        background-color: rgba(108, 117, 125, 0.1);
        color: #6c757d;
    }
    
    .status-badge.cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .status-badge.diverted {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .value.delay {
        color: #dc3545;
        font-weight: bold;
    }
    
    @media (max-width: 768px) {
        .confirmation-card {
            padding: 20px 15px;
        }
        
        .flight-detail-row {
            flex-direction: column;
            gap: 10px;
        }
        
        .flight-detail {
            min-width: 100%;
        }
        
        .confirmation-actions {
            flex-direction: column;
            gap: 10px;
            align-items: stretch;
        }
        
        .btn {
            display: block;
            text-align: center;
            margin-bottom: 10px;
        }
    }
</style>

<?php include 'templates/footer.php'; ?>
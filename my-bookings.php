<?php
session_start();
require_once 'inc/config.php';
require_once 'inc/db.php';
require_once 'inc/functions.php';
require_once 'inc/auth.php';
require_once 'classes/Booking.php';
require_once 'classes/ApiClient.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    header('Location: login.php?redirect=my-bookings.php');
    exit();
}

$userId = $_SESSION['user_id'];
$bookings = [];
$error = '';

try {
    // Get all bookings for the current user with flight details from database
    $stmt = $pdo->prepare(
        "SELECT b.*, f.flight_number, f.departure, f.arrival, f.date, f.time,
        (SELECT COUNT(*) FROM passengers p WHERE p.booking_id = b.id) as registered_passengers 
        FROM bookings b 
        JOIN flights f ON b.flight_id = f.id 
        WHERE b.user_id = ?"
    );
    $stmt->execute([$userId]);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Enhance with real-time flight data from AviationStack
    $apiClient = new ApiClient();

    foreach ($bookings as &$booking) {
        if (isset($booking['flight_number']) && !empty($booking['flight_number'])) {
            try {
                // Get real-time flight data
                $params = [
                    'flight_iata' => $booking['flight_number'],
                    'flight_date' => $booking['date']
                ];

                $realTimeFlight = $apiClient->getFlightStatus($params);

                if (!empty($realTimeFlight)) {
                    $rtf = $realTimeFlight[0]; // Get first match

                    // Add real-time data to the booking
                    $booking['real_time_status'] = $rtf['flight_status'] ?? 'scheduled';
                    $booking['departure_terminal'] = $rtf['departure']['terminal'] ?? null;
                    $booking['departure_gate'] = $rtf['departure']['gate'] ?? null;
                    $booking['departure_delay'] = $rtf['departure']['delay'] ?? 0;
                    $booking['arrival_terminal'] = $rtf['arrival']['terminal'] ?? null;
                    $booking['arrival_gate'] = $rtf['arrival']['gate'] ?? null;
                    $booking['arrival_delay'] = $rtf['arrival']['delay'] ?? 0;
                    $booking['aircraft_registration'] = $rtf['aircraft']['registration'] ?? null;
                    $booking['aircraft_type'] = $rtf['aircraft']['iata'] ?? null;
                    $booking['airline_name'] = $rtf['airline']['name'] ?? null;
                }
            } catch (Exception $e) {
                // Just log the error but continue
                error_log("Error fetching real-time flight data: " . $e->getMessage());
            }
        }
    }
} catch (Exception $e) {
    $error = "Error retrieving bookings: " . $e->getMessage();
    error_log($error);
}

include 'templates/header.php';
?>

<div class="container">
    <h1>My Bookings</h1>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <?php if (empty($bookings)): ?>
        <div class="no-bookings">
            <p>You don't have any bookings yet.</p>
            <a href="search.php" class="btn-primary">Search Flights</a>
        </div>
    <?php else: ?>
        <div class="bookings-list">
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card <?php echo strtolower($booking['status']); ?>">
                    <div class="booking-header">
                        <div class="booking-id">
                            <span>Booking ID:</span> #<?php echo htmlspecialchars($booking['id']); ?>
                        </div>
                        <div class="booking-status <?php echo strtolower($booking['status']); ?>">
                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>

                            <?php if (isset($booking['real_time_status'])): ?>
                                <span class="real-time-badge">
                                    Flight: <?php echo htmlspecialchars(ucfirst($booking['real_time_status'])); ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="booking-details">
                        <div class="flight-info">
                            <h3>Flight Details</h3>
                            <div class="detail-row">
                                <span class="label">Flight Number:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['flight_number'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (isset($booking['airline_name'])): ?>
                                <div class="detail-row">
                                    <span class="label">Airline:</span>
                                    <span class="value"><?php echo htmlspecialchars($booking['airline_name']); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="label">From:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['departure'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (isset($booking['departure_terminal']) || isset($booking['departure_gate'])): ?>
                                <div class="detail-row">
                                    <span class="label">Departure Details:</span>
                                    <span class="value">
                                        <?php
                                        $details = [];
                                        if (!empty($booking['departure_terminal'])) $details[] = "Terminal: " . htmlspecialchars($booking['departure_terminal']);
                                        if (!empty($booking['departure_gate'])) $details[] = "Gate: " . htmlspecialchars($booking['departure_gate']);
                                        echo implode(' | ', $details);
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($booking['departure_delay']) && $booking['departure_delay'] > 0): ?>
                                <div class="detail-row delay">
                                    <span class="label">Departure Delay:</span>
                                    <span class="value"><?php echo htmlspecialchars($booking['departure_delay']); ?> minutes</span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="label">To:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['arrival'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (isset($booking['arrival_terminal']) || isset($booking['arrival_gate'])): ?>
                                <div class="detail-row">
                                    <span class="label">Arrival Details:</span>
                                    <span class="value">
                                        <?php
                                        $details = [];
                                        if (!empty($booking['arrival_terminal'])) $details[] = "Terminal: " . htmlspecialchars($booking['arrival_terminal']);
                                        if (!empty($booking['arrival_gate'])) $details[] = "Gate: " . htmlspecialchars($booking['arrival_gate']);
                                        echo implode(' | ', $details);
                                        ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                            <?php if (isset($booking['arrival_delay']) && $booking['arrival_delay'] > 0): ?>
                                <div class="detail-row delay">
                                    <span class="label">Arrival Delay:</span>
                                    <span class="value"><?php echo htmlspecialchars($booking['arrival_delay']); ?> minutes</span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="label">Date:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['date'] ?? 'N/A'); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Time:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['time'] ?? 'N/A'); ?></span>
                            </div>
                            <?php if (isset($booking['aircraft_type'])): ?>
                                <div class="detail-row">
                                    <span class="label">Aircraft:</span>
                                    <span class="value"><?php echo htmlspecialchars($booking['aircraft_type'] .
                                                            (!empty($booking['aircraft_registration']) ? ' (' . $booking['aircraft_registration'] . ')' : '')); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="detail-row">
                                <span class="label">Registered Passengers:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['registered_passengers']); ?></span>
                            </div>
                        </div>

                        <div class="passenger-info">
                            <h3>Passenger Information</h3>
                            <div class="detail-row">
                                <span class="label">Name:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['customer_name']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Email:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['customer_email']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Phone:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['customer_phone']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Passengers:</span>
                                <span class="value"><?php echo htmlspecialchars($booking['passengers']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="label">Total Price:</span>
                                <span class="value price">$<?php echo htmlspecialchars(number_format($booking['total_price'], 2)); ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="booking-actions">
                        <?php if ($booking['status'] === 'pending'): ?>
                            <form class="pay-form" method="POST" action="payment.php">
                                <input type="hidden" name="booking_id" value="<?php echo $booking['id']; ?>">
                                <input type="hidden" name="price" value="<?php echo $booking['total_price']; ?>">
                                <button type="submit" name="pay_booking" class="btn-primary">Pay Now</button>
                            </form>
                        <?php endif; ?>

                        <?php
                        // Calculate remaining passengers to be registered
                        $registeredPassengers = $booking['registered_passengers'] ?? 0;
                        $remainingPassengers = $booking['passengers'] - $registeredPassengers;
                        ?>

                        <a href="manage-passengers.php?booking_id=<?php echo $booking['id']; ?>" class="btn-secondary">
                            Edit Passenger(s)
                            <?php if ($remainingPassengers > 0): ?>
                                <span class="badge alert-warning">
                                    <?php echo $remainingPassengers; ?> passenger(s) remaining
                                </span>
                            <?php endif; ?>
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<style>
    .container {
        max-width: 1000px;
        margin: 0 auto;
        padding: 20px;
    }

    h1 {
        margin-bottom: 30px;
        color: #333;
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

    .no-bookings {
        text-align: center;
        padding: 40px;
        background-color: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .no-bookings p {
        font-size: 1.2em;
        margin-bottom: 20px;
        color: #6c757d;
    }

    .booking-card {
        background-color: #fff;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin-bottom: 30px;
        overflow: hidden;
        border-left: 5px solid #6c757d;
    }

    .booking-card.confirmed {
        border-left-color: #28a745;
    }

    .booking-card.pending {
        border-left-color: #ffc107;
    }

    .booking-card.cancelled {
        border-left-color: #dc3545;
    }

    .booking-header {
        background-color: #f8f9fa;
        padding: 15px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #e9ecef;
    }

    .booking-id {
        font-weight: 600;
        color: #495057;
    }

    .booking-id span {
        color: #6c757d;
        font-weight: normal;
    }

    .booking-status {
        padding: 5px 10px;
        border-radius: 15px;
        font-size: 0.85em;
        font-weight: 600;
        text-transform: uppercase;
    }

    .booking-status.confirmed {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }

    .booking-status.pending {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }

    .booking-status.cancelled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }

    .real-time-badge {
        display: inline-block;
        margin-left: 10px;
        padding: 2px 8px;
        background-color: rgba(0, 123, 255, 0.1);
        color: #007bff;
        border-radius: 12px;
        font-size: 0.8em;
    }

    .booking-details {
        padding: 20px;
        display: flex;
        flex-wrap: wrap;
    }

    .flight-info,
    .passenger-info {
        flex: 1;
        min-width: 250px;
        padding: 0 15px;
    }

    .flight-info h3,
    .passenger-info h3 {
        color: #343a40;
        font-size: 1.2em;
        margin-bottom: 15px;
        padding-bottom: 5px;
        border-bottom: 1px solid #e9ecef;
    }

    .detail-row {
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
    }

    .detail-row.delay {
        color: #dc3545;
    }

    .label {
        color: #6c757d;
        font-weight: 500;
    }

    .value {
        font-weight: 500;
        color: #343a40;
    }

    .value.price {
        color: #28a745;
        font-size: 1.1em;
    }

    .booking-actions {
        padding: 15px 20px;
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
        display: flex;
        justify-content: flex-end;
        gap: 15px;
    }

    .btn-primary,
    .btn-secondary {
        padding: 8px 16px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: 500;
        display: inline-block;
    }

    .btn-primary {
        background-color: #4CAF50;
        color: white;
        border: none;
        cursor: pointer;
    }

    .btn-primary:hover {
        background-color: #45a049;
    }

    .btn-secondary {
        background-color: #f8f9fa;
        color: #343a40;
        border: 1px solid #dee2e6;
    }

    .btn-secondary:hover {
        background-color: #e2e6ea;
    }

    .pay-form {
        display: inline-block;
    }

    @media (max-width: 768px) {
        .booking-header {
            flex-direction: column;
            align-items: flex-start;
        }

        .booking-status {
            margin-top: 10px;
        }

        .booking-details {
            flex-direction: column;
        }

        .flight-info,
        .passenger-info {
            padding: 0;
            margin-bottom: 20px;
        }
    }
    .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 0.8em;
        margin-left: 8px;
    }

    .alert-warning {
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
    }

    .btn-secondary {
        background-color: #6c757d;
        color: white;
        padding: 8px 16px;
        border: none;
        border-radius: 4px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        margin: 5px;
    }

    .btn-secondary:hover {
        background-color: #5a6268;
        text-decoration: none;
        color: white;
    }

    .booking-actions {
        display: flex;
        gap: 10px;
        align-items: center;
        justify-content: flex-end;
        padding: 15px 20px;
        background-color: #f8f9fa;
        border-top: 1px solid #e9ecef;
    }
</style>

<?php include 'templates/footer.php'; ?>
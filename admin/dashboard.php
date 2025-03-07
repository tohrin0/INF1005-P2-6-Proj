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

// Fetch key metrics for the dashboard
$totalUsers = getTotalUsers();
$totalBookings = getTotalBookings();
$totalFlights = getTotalFlights();

// Get recent activities (last 5 bookings)
try {
    $stmt = $pdo->query("SELECT b.id, b.booking_date, u.username, f.flight_number, b.status 
                        FROM bookings b 
                        JOIN users u ON b.user_id = u.id 
                        JOIN flights f ON b.flight_id = f.id 
                        ORDER BY b.booking_date DESC LIMIT 5");
    $recentBookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $recentBookings = [];
    error_log("Error fetching recent bookings: " . $e->getMessage());
}

include 'includes/header.php';
?>

<div class="admin-dashboard">
    <div class="admin-header">
        <h1><i class="fa fa-tachometer-alt"></i> Admin Dashboard</h1>
        <p class="last-login">Last login: <?php echo date("F j, Y, g:i a"); ?></p>
    </div>

    <div class="metrics-container">
        <div class="metric-card users">
            <div class="metric-icon">
                <i class="fa fa-users"></i>
            </div>
            <div class="metric-content">
                <h2><?php echo $totalUsers; ?></h2>
                <p>Total Users</p>
            </div>
            <div class="metric-action">
                <a href="users.php" class="view-all">View All</a>
            </div>
        </div>
        
        <div class="metric-card bookings">
            <div class="metric-icon">
                <i class="fa fa-ticket-alt"></i>
            </div>
            <div class="metric-content">
                <h2><?php echo $totalBookings; ?></h2>
                <p>Total Bookings</p>
            </div>
            <div class="metric-action">
                <a href="bookings.php" class="view-all">View All</a>
            </div>
        </div>
        
        <div class="metric-card flights">
            <div class="metric-icon">
                <i class="fa fa-plane"></i>
            </div>
            <div class="metric-content">
                <h2><?php echo $totalFlights; ?></h2>
                <p>Total Flights</p>
            </div>
            <div class="metric-action">
                <a href="flights.php" class="view-all">View All</a>
            </div>
        </div>
    </div>

    <div class="admin-content-row">
        <div class="admin-panel">
            <div class="panel-header">
                <h3><i class="fa fa-history"></i> Recent Bookings</h3>
            </div>
            <div class="panel-body">
                <?php if (empty($recentBookings)): ?>
                    <p class="no-data">No recent bookings found.</p>
                <?php else: ?>
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Booking ID</th>
                                <th>Date</th>
                                <th>User</th>
                                <th>Flight</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentBookings as $booking): ?>
                                <tr>
                                    <td>#<?php echo htmlspecialchars($booking['id']); ?></td>
                                    <td><?php echo htmlspecialchars(date('M j, Y', strtotime($booking['booking_date']))); ?></td>
                                    <td><?php echo htmlspecialchars($booking['username']); ?></td>
                                    <td><?php echo htmlspecialchars($booking['flight_number']); ?></td>
                                    <td>
                                        <span class="status-badge <?php echo strtolower(htmlspecialchars($booking['status'])); ?>">
                                            <?php echo htmlspecialchars(ucfirst($booking['status'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="edit-booking.php?id=<?php echo $booking['id']; ?>" class="action-btn edit">
                                            <i class="fa fa-edit"></i>
                                        </a>
                                        <a href="view-booking.php?id=<?php echo $booking['id']; ?>" class="action-btn view">
                                            <i class="fa fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
            <div class="panel-footer">
                <a href="bookings.php" class="view-all-link">View All Bookings</a>
            </div>
        </div>
        
        <div class="admin-panel">
            <div class="panel-header">
                <h3><i class="fa fa-link"></i> Quick Links</h3>
            </div>
            <div class="panel-body quick-links">
                <a href="bookings.php" class="quick-link">
                    <i class="fa fa-ticket-alt"></i>
                    <span>Manage Bookings</span>
                </a>
                <a href="users.php" class="quick-link">
                    <i class="fa fa-users"></i>
                    <span>Manage Users</span>
                </a>
                <a href="flights.php" class="quick-link">
                    <i class="fa fa-plane"></i>
                    <span>Manage Flights</span>
                </a>
                <a href="settings.php" class="quick-link">
                    <i class="fa fa-cog"></i>
                    <span>Site Settings</span>
                </a>
                <a href="../index.php" class="quick-link">
                    <i class="fa fa-home"></i>
                    <span>View Website</span>
                </a>
                <a href="reports.php" class="quick-link">
                    <i class="fa fa-chart-bar"></i>
                    <span>Reports</span>
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    /* Additional Dashboard Styling */
    .admin-dashboard {
        padding: 20px;
    }
    
    .admin-header {
        margin-bottom: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    
    .admin-header h1 {
        margin: 0;
        color: #343a40;
        display: flex;
        align-items: center;
        font-size: 24px;
    }
    
    .admin-header h1 i {
        margin-right: 10px;
        color: #007bff;
    }
    
    .last-login {
        color: #6c757d;
        font-size: 14px;
    }
    
    .metrics-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .metric-card {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        padding: 20px;
        display: flex;
        align-items: center;
        transition: transform 0.3s ease;
    }
    
    .metric-card:hover {
        transform: translateY(-5px);
    }
    
    .metric-card.users {
        border-left: 4px solid #007bff;
    }
    
    .metric-card.bookings {
        border-left: 4px solid #28a745;
    }
    
    .metric-card.flights {
        border-left: 4px solid #fd7e14;
    }
    
    .metric-icon {
        width: 60px;
        height: 60px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 15px;
        font-size: 24px;
    }
    
    .metric-card.users .metric-icon {
        background: rgba(0, 123, 255, 0.1);
        color: #007bff;
    }
    
    .metric-card.bookings .metric-icon {
        background: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .metric-card.flights .metric-icon {
        background: rgba(253, 126, 20, 0.1);
        color: #fd7e14;
    }
    
    .metric-content {
        flex: 1;
    }
    
    .metric-content h2 {
        margin: 0;
        font-size: 28px;
    }
    
    .metric-content p {
        margin: 5px 0 0;
        color: #6c757d;
    }
    
    .metric-action {
        text-align: right;
    }
    
    .view-all {
        display: inline-block;
        padding: 5px 10px;
        background-color: #f8f9fa;
        color: #343a40;
        border-radius: 4px;
        text-decoration: none;
        font-size: 12px;
        transition: background-color 0.3s;
    }
    
    .view-all:hover {
        background-color: #e9ecef;
    }
    
    .admin-content-row {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }
    
    .admin-panel {
        background: white;
        border-radius: 8px;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        margin-bottom: 20px;
        overflow: hidden;
    }
    
    .panel-header {
        padding: 15px 20px;
        background: #f8f9fa;
        border-bottom: 1px solid #e9ecef;
    }
    
    .panel-header h3 {
        margin: 0;
        color: #343a40;
        font-size: 18px;
        display: flex;
        align-items: center;
    }
    
    .panel-header h3 i {
        margin-right: 10px;
        color: #007bff;
    }
    
    .panel-body {
        padding: 20px;
    }
    
    .panel-footer {
        padding: 10px 20px;
        background: #f8f9fa;
        border-top: 1px solid #e9ecef;
        text-align: center;
    }
    
    .view-all-link {
        color: #007bff;
        text-decoration: none;
        font-weight: 500;
    }
    
    .view-all-link:hover {
        text-decoration: underline;
    }
    
    .admin-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .admin-table th,
    .admin-table td {
        padding: 12px 15px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }
    
    .admin-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #343a40;
    }
    
    .status-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 500;
    }
    
    .status-badge.confirmed {
        background-color: rgba(40, 167, 69, 0.1);
        color: #28a745;
    }
    
    .status-badge.pending {
        background-color: rgba(255, 193, 7, 0.1);
        color: #ffc107;
    }
    
    .status-badge.canceled {
        background-color: rgba(220, 53, 69, 0.1);
        color: #dc3545;
    }
    
    .action-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 28px;
        height: 28px;
        border-radius: 4px;
        color: white;
        margin-right: 5px;
        transition: opacity 0.3s;
    }
    
    .action-btn:hover {
        opacity: 0.8;
    }
    
    .action-btn.edit {
        background-color: #ffc107;
    }
    
    .action-btn.view {
        background-color: #17a2b8;
    }
    
    .action-btn.delete {
        background-color: #dc3545;
    }
    
    .no-data {
        color: #6c757d;
        text-align: center;
        padding: 20px 0;
    }
    
    .quick-links {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
    }
    
    .quick-link {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 15px;
        background-color: #f8f9fa;
        border-radius: 8px;
        text-decoration: none;
        color: #343a40;
        transition: all 0.3s ease;
    }
    
    .quick-link:hover {
        background-color: #007bff;
        color: white;
        transform: translateY(-3px);
    }
    
    .quick-link i {
        font-size: 24px;
        margin-bottom: 8px;
    }
    
    /* Responsive adjustments */
    @media (max-width: 992px) {
        .admin-content-row {
            grid-template-columns: 1fr;
        }
    }
    
    @media (max-width: 768px) {
        .metrics-container {
            grid-template-columns: 1fr;
        }
        
        .admin-header {
            flex-direction: column;
            align-items: flex-start;
        }
        
        .last-login {
            margin-top: 10px;
        }
    }
</style>

<?php include 'includes/footer.php'; ?>
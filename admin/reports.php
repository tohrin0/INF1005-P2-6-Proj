<?php
require_once '../inc/config.php';
require_once '../inc/db.php';
require_once '../inc/functions.php';
require_once '../inc/auth.php';
require_once '../classes/Booking.php';
require_once '../classes/User.php';

session_start();

if (!isAdmin()) {
    header('Location: ../login.php');
    exit();
}

// Get date range for filtering
$startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Initialize Booking class
$bookingObj = new Booking();
$userObj = new User($pdo);

// Fetch report data
try {
    // Revenue by date
    $stmt = $pdo->prepare(
        "SELECT DATE(p.payment_date) as date, SUM(p.amount) as revenue 
         FROM payments p 
         JOIN bookings b ON p.booking_id = b.id 
         WHERE p.status = 'completed' 
         AND DATE(p.payment_date) BETWEEN ? AND ?
         GROUP BY DATE(p.payment_date)
         ORDER BY date"
    );
    $stmt->execute([$startDate, $endDate]);
    $revenueByDate = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Total revenue in the period
    $stmt = $pdo->prepare(
        "SELECT SUM(p.amount) as total_revenue 
         FROM payments p 
         WHERE p.status = 'completed' 
         AND DATE(p.payment_date) BETWEEN ? AND ?"
    );
    $stmt->execute([$startDate, $endDate]);
    $totalRevenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?? 0;

    // Bookings by status
    $stmt = $pdo->prepare(
        "SELECT status, COUNT(*) as count 
         FROM bookings 
         WHERE DATE(booking_date) BETWEEN ? AND ? 
         GROUP BY status"
    );
    $stmt->execute([$startDate, $endDate]);
    $bookingsByStatus = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Popular routes
    $stmt = $pdo->prepare(
        "SELECT f.departure, f.arrival, COUNT(*) as bookings, 
         SUM(b.total_price) as revenue
         FROM bookings b 
         JOIN flights f ON b.flight_id = f.id 
         WHERE DATE(b.booking_date) BETWEEN ? AND ? 
         GROUP BY f.departure, f.arrival 
         ORDER BY bookings DESC 
         LIMIT 5"
    );
    $stmt->execute([$startDate, $endDate]);
    $popularRoutes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // New user registrations
    $stmt = $pdo->prepare(
        "SELECT DATE(created_at) as date, COUNT(*) as registrations 
         FROM users 
         WHERE DATE(created_at) BETWEEN ? AND ? 
         GROUP BY DATE(created_at)
         ORDER BY date"
    );
    $stmt->execute([$startDate, $endDate]);
    $userRegistrations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Monthly revenue comparison
    $stmt = $pdo->prepare(
        "SELECT 
            MONTH(p.payment_date) as month, 
            YEAR(p.payment_date) as year,
            SUM(p.amount) as revenue
         FROM payments p 
         WHERE p.status = 'completed' 
         AND DATE(p.payment_date) >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
         GROUP BY YEAR(p.payment_date), MONTH(p.payment_date)
         ORDER BY year, month"
    );
    $stmt->execute();
    $monthlyRevenue = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Error generating reports: " . $e->getMessage());
    $error = "There was an error generating the reports. Please try again later.";
}

include 'includes/header.php';

// Function to convert data for charts
function prepareChartData($data, $dateKey = 'date', $valueKey = 'revenue') {
    $labels = [];
    $values = [];
    
    foreach ($data as $item) {
        $labels[] = isset($item[$dateKey]) ? ($dateKey === 'month' ? date('M', mktime(0, 0, 0, $item[$dateKey], 1)) : $item[$dateKey]) : '';
        $values[] = $item[$valueKey] ?? 0;
    }
    
    return [
        'labels' => json_encode($labels),
        'values' => json_encode($values)
    ];
}

// Prepare data for charts
$revenueChartData = prepareChartData($revenueByDate);
$registrationsChartData = prepareChartData($userRegistrations, 'date', 'registrations');
$monthlyRevenueChartData = prepareChartData($monthlyRevenue, 'month');

// Calculate total bookings
$totalBookings = array_reduce($bookingsByStatus, function($carry, $item) {
    return $carry + ($item['count'] ?? 0);
}, 0);

// Calculate conversion rate (confirmed bookings / total bookings)
$confirmedBookings = 0;
foreach ($bookingsByStatus as $status) {
    if ($status['status'] === 'confirmed') {
        $confirmedBookings = $status['count'];
        break;
    }
}
$conversionRate = $totalBookings > 0 ? round(($confirmedBookings / $totalBookings) * 100, 1) : 0;
?>

<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800 mb-2">Analytics & Reports</h1>
        <p class="text-gray-600">Analyze booking trends, revenue, and user metrics</p>
    </div>
    
    <?php if (isset($error)): ?>
        <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>
    
    <!-- Date Range Filter -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4 mb-6">
        <form method="GET" class="flex flex-wrap items-center gap-4">
            <div>
                <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
                <input type="date" id="start_date" name="start_date" value="<?= htmlspecialchars($startDate) ?>"
                    class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div>
                <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
                <input type="date" id="end_date" name="end_date" value="<?= htmlspecialchars($endDate) ?>"
                    class="w-full sm:w-auto px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
            </div>
            
            <div class="self-end pb-1">
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-colors">
                    Apply Filter
                </button>
            </div>
            
            <div class="self-end pb-1 ml-auto">
                <button type="button" onclick="exportReportToPDF()" class="px-4 py-2 bg-gray-800 text-white rounded-md hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export Report
                </button>
            </div>
        </form>
    </div>
    
    <!-- Key Metrics -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <!-- Revenue Icon -->
                <div class="flex-shrink-0 bg-blue-100 rounded-md p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-blue-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0-2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                    <p class="text-2xl font-semibold text-gray-900">$<?= number_format($totalRevenue, 2) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <!-- Total Bookings Icon -->
                <div class="flex-shrink-0 bg-green-100 rounded-md p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Total Bookings</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= number_format($totalBookings) ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <!-- New Users Icon -->
                <div class="flex-shrink-0 bg-purple-100 rounded-md p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-purple-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">New Users</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= count($userRegistrations) ? array_sum(array_column($userRegistrations, 'registrations')) : 0 ?></p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-4">
            <div class="flex items-center">
                <!-- Conversion Rate Icon -->
                <div class="flex-shrink-0 bg-yellow-100 rounded-md p-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-yellow-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
                <div class="ml-4">
                    <p class="text-sm font-medium text-gray-500">Conversion Rate</p>
                    <p class="text-2xl font-semibold text-gray-900"><?= $conversionRate ?>%</p>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Revenue Chart & User Registration -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Revenue Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Revenue Trend</h2>
            </div>
            <div class="p-6">
                <canvas id="revenueChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- User Registration Chart -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">New User Registrations</h2>
            </div>
            <div class="p-6">
                <canvas id="userRegistrationChart" height="250"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Bookings by Status & Popular Routes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- Bookings by Status -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Bookings by Status</h2>
            </div>
            <div class="p-6">
                <canvas id="bookingStatusChart" height="250"></canvas>
            </div>
        </div>
        
        <!-- Popular Routes -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-800">Most Popular Routes</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Route</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if (empty($popularRoutes)): ?>
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-center text-sm text-gray-500">No route data available for the selected period.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($popularRoutes as $route): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?= htmlspecialchars($route['departure']) ?> → <?= htmlspecialchars($route['arrival']) ?>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?= number_format($route['bookings']) ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right">
                                        <div class="text-sm text-gray-900">$<?= number_format($route['revenue'], 2) ?></div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Monthly Revenue Comparison -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-semibold text-gray-800">Monthly Revenue Comparison</h2>
        </div>
        <div class="p-6">
            <canvas id="monthlyRevenueChart" height="300"></canvas>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>

<script>
// Set up Chart.js with the data
document.addEventListener('DOMContentLoaded', function() {
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart').getContext('2d');
    const revenueChart = new Chart(revenueCtx, {
        type: 'line',
        data: {
            labels: <?= $revenueChartData['labels'] ?>,
            datasets: [{
                label: 'Revenue',
                data: <?= $revenueChartData['values'] ?>,
                fill: false,
                borderColor: 'rgb(59, 130, 246)',
                tension: 0.1,
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y;
                        }
                    }
                }
            }
        }
    });
    
    // User Registration Chart
    const userCtx = document.getElementById('userRegistrationChart').getContext('2d');
    const userChart = new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: <?= $registrationsChartData['labels'] ?>,
            datasets: [{
                label: 'New Users',
                data: <?= $registrationsChartData['values'] ?>,
                backgroundColor: 'rgba(139, 92, 246, 0.2)',
                borderColor: 'rgb(139, 92, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Booking Status Chart
    const statusCtx = document.getElementById('bookingStatusChart').getContext('2d');
    const statusLabels = [];
    const statusData = [];
    const statusColors = [];
    
    <?php foreach ($bookingsByStatus as $status): ?>
        statusLabels.push('<?= ucfirst($status['status']) ?>');
        statusData.push(<?= $status['count'] ?>);
        
        <?php if ($status['status'] === 'confirmed'): ?>
            statusColors.push('rgba(16, 185, 129, 0.8)');
        <?php elseif ($status['status'] === 'pending'): ?>
            statusColors.push('rgba(245, 158, 11, 0.8)');
        <?php elseif ($status['status'] === 'canceled'): ?>
            statusColors.push('rgba(239, 68, 68, 0.8)');
        <?php else: ?>
            statusColors.push('rgba(107, 114, 128, 0.8)');
        <?php endif; ?>
    <?php endforeach; ?>
    
    const statusChart = new Chart(statusCtx, {
        type: 'doughnut',
        data: {
            labels: statusLabels,
            datasets: [{
                data: statusData,
                backgroundColor: statusColors,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        }
    });
    
    // Monthly Revenue Comparison
    const monthlyCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
    const monthlyChart = new Chart(monthlyCtx, {
        type: 'bar',
        data: {
            labels: <?= $monthlyRevenueChartData['labels'] ?>,
            datasets: [{
                label: 'Monthly Revenue',
                data: <?= $monthlyRevenueChartData['values'] ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgb(59, 130, 246)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return '$' + value;
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return '$' + context.parsed.y;
                        }
                    }
                }
            }
        }
    });
});

// Export report to PDF
function exportReportToPDF() {
    const { jsPDF } = window.jspdf;
    
    // Create new PDF
    const doc = new jsPDF('p', 'mm', 'a4');
    doc.setFont('helvetica');
    doc.setFontSize(16);
    
    // Add title and period
    doc.text('Flight Booking Analytics Report', 20, 20);
    doc.setFontSize(12);
    doc.text(`Period: ${document.getElementById('start_date').value} to ${document.getElementById('end_date').value}`, 20, 30);
    doc.setFontSize(10);
    doc.text(`Generated on: ${new Date().toLocaleDateString()}`, 20, 35);
    
    // Capture charts as images
    const charts = ['revenueChart', 'userRegistrationChart', 'bookingStatusChart', 'monthlyRevenueChart'];
    let yPosition = 45;
    
    // Promise.all for all chart captures
    Promise.all(charts.map(chartId => {
        const canvas = document.getElementById(chartId);
        return Promise.resolve({
            id: chartId,
            dataUrl: canvas.toDataURL('image/png')
        });
    })).then(results => {
        // Add each chart to the PDF
        results.forEach((result, index) => {
            if (index > 0) {
                // Add new page for each chart after the first one
                doc.addPage();
                yPosition = 20;
            }
            
            // Add chart title
            let title = '';
            switch(result.id) {
                case 'revenueChart':
                    title = 'Revenue Trend';
                    break;
                case 'userRegistrationChart':
                    title = 'New User Registrations';
                    break;
                case 'bookingStatusChart':
                    title = 'Bookings by Status';
                    break;
                case 'monthlyRevenueChart':
                    title = 'Monthly Revenue Comparison';
                    break;
            }
            
            doc.setFontSize(14);
            doc.text(title, 20, yPosition);
            yPosition += 10;
            
            // Add the chart image
            doc.addImage(result.dataUrl, 'PNG', 20, yPosition, 170, 100);
        });
        
        // Save the PDF
        doc.save(`flight-booking-report-${document.getElementById('start_date').value}-to-${document.getElementById('end_date').value}.pdf`);
    });
}
</script>

<?php include 'includes/footer.php'; ?>
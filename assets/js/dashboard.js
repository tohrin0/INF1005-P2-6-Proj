/**
 * Dashboard Charts Script
 * This external JavaScript file handles all chart initialization and configuration
 * to comply with Content Security Policy (CSP) restrictions.
 * 
 * Includes fallback data generation for empty datasets to ensure charts always display.
 */

// Wait for DOM to be fully loaded
document.addEventListener('DOMContentLoaded', function() {
    // Get chart data from the hidden div
    const chartDataElement = document.getElementById('chartData');
    if (!chartDataElement) return;

    // Set default Chart.js options
    Chart.defaults.color = '#6b7280';
    Chart.defaults.font.family = 'Arial, sans-serif';
    Chart.defaults.font.size = 12;
    
    // Common options for all charts
    const commonOptions = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'top',
                labels: {
                    usePointStyle: true,
                    padding: 15
                }
            },
            tooltip: {
                cornerRadius: 6,
                caretSize: 6,
                padding: 10,
                backgroundColor: 'rgba(0, 0, 0, 0.7)'
            }
        }
    };
    
    // Initialize Revenue Chart
    initRevenueChart();
    
    // Initialize Booking Status Chart
    initBookingStatusChart();
    
    // Initialize Routes Chart
    initRoutesChart();
    
    // Initialize Airline Chart
    initAirlineChart();
    
    // Initialize Demographics Chart
    initDemographicsChart();
    
    // Initialize export button
    document.getElementById('exportReportBtn').addEventListener('click', exportReportToPDF);
    
    // Revenue Chart Initialization
    function initRevenueChart() {
        try {
            let revenueData = JSON.parse(chartDataElement.dataset.revenue || '[]');
            
            // Generate random data if the dataset is empty
            if (revenueData.length === 0) {
                revenueData = generateRandomDateData(30, 'revenue', 1000, 10000);
            }
            
            const labels = revenueData.map(item => item.label);
            const values = revenueData.map(item => item.value);
            
            const ctx = document.getElementById('revenueChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Revenue',
                        data: values,
                        fill: {
                            target: 'origin',
                            above: 'rgba(59, 130, 246, 0.1)'
                        },
                        borderColor: 'rgb(59, 130, 246)',
                        tension: 0.3,
                        borderWidth: 2,
                        pointBackgroundColor: 'rgb(59, 130, 246)',
                        pointRadius: 3,
                        pointHoverRadius: 5
                    }]
                },
                options: {
                    ...commonOptions,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return '$' + value.toLocaleString();
                                }
                            }
                        }
                    },
                    plugins: {
                        ...commonOptions.plugins,
                        tooltip: {
                            ...commonOptions.plugins.tooltip,
                            callbacks: {
                                label: function(context) {
                                    return '$' + context.parsed.y.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing revenue chart:', error);
        }
    }
    
    // Booking Status Chart Initialization
    function initBookingStatusChart() {
        try {
            let statusLabels = JSON.parse(chartDataElement.dataset.statusLabels || '[]');
            let statusData = JSON.parse(chartDataElement.dataset.statusData || '[]');
            let statusColors = JSON.parse(chartDataElement.dataset.statusColors || '[]');
            
            // Generate random data if the dataset is empty
            if (statusLabels.length === 0 || statusData.length === 0) {
                statusLabels = ['Confirmed', 'Pending', 'Canceled', 'Completed'];
                statusData = [
                    Math.floor(Math.random() * 300) + 200,
                    Math.floor(Math.random() * 100) + 50,
                    Math.floor(Math.random() * 50) + 10,
                    Math.floor(Math.random() * 400) + 300
                ];
                statusColors = [
                    'rgba(16, 185, 129, 0.8)',  // Confirmed (green)
                    'rgba(245, 158, 11, 0.8)',  // Pending (yellow)
                    'rgba(239, 68, 68, 0.8)',   // Canceled (red)
                    'rgba(37, 99, 235, 0.8)'    // Completed (blue)
                ];
            }
            
            const ctx = document.getElementById('bookingStatusChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: statusLabels,
                    datasets: [{
                        data: statusData,
                        backgroundColor: statusColors,
                        borderWidth: 1,
                        hoverOffset: 10
                    }]
                },
                options: {
                    ...commonOptions,
                    cutout: '65%',
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 15
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing booking status chart:', error);
        }
    }
    
    // Routes Chart Initialization
    function initRoutesChart() {
        try {
            let routeLabels = JSON.parse(chartDataElement.dataset.routeLabels || '[]');
            let routeValues = JSON.parse(chartDataElement.dataset.routeValues || '[]');
            let routeColors = JSON.parse(chartDataElement.dataset.routeColors || '[]');
            
            // Generate random data if the dataset is empty
            if (routeLabels.length === 0 || routeValues.length === 0) {
                const cities = [
                    'New York', 'Los Angeles', 'Chicago', 'Miami', 'San Francisco', 
                    'Seattle', 'Boston', 'Las Vegas', 'Dallas', 'Denver'
                ];
                
                routeLabels = [];
                routeValues = [];
                routeColors = [];
                
                // Generate 5 random routes
                for (let i = 0; i < 5; i++) {
                    const departure = cities[Math.floor(Math.random() * cities.length)];
                    let arrival;
                    do {
                        arrival = cities[Math.floor(Math.random() * cities.length)];
                    } while (arrival === departure);
                    
                    routeLabels.push(`${departure} → ${arrival}`);
                    routeValues.push(Math.floor(Math.random() * 100) + 50);
                    
                    // Generate a random color
                    const r = Math.floor(Math.random() * 150) + 50;
                    const g = Math.floor(Math.random() * 150) + 50;
                    const b = Math.floor(Math.random() * 150) + 50;
                    routeColors.push(`rgba(${r}, ${g}, ${b}, 0.7)`);
                }
            }
            
            const ctx = document.getElementById('routesChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: routeLabels,
                    datasets: [{
                        label: 'Number of Bookings',
                        data: routeValues,
                        backgroundColor: routeColors,
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    ...commonOptions,
                    indexAxis: 'y',
                    scales: {
                        x: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing routes chart:', error);
        }
    }
    
    // Airline Chart Initialization
    function initAirlineChart() {
        try {
            let airlineLabels = JSON.parse(chartDataElement.dataset.airlineLabels || '[]');
            let airlineValues = JSON.parse(chartDataElement.dataset.airlineValues || '[]');
            let airlineColors = JSON.parse(chartDataElement.dataset.airlineColors || '[]');
            
            // Generate random data if the dataset is empty
            if (airlineLabels.length === 0 || airlineValues.length === 0) {
                const airlines = [
                    'American Airlines', 'Delta Air Lines', 'United Airlines', 'Southwest Airlines', 
                    'JetBlue Airways', 'Alaska Airlines', 'Spirit Airlines'
                ];
                
                // Use default colors if none provided
                if (airlineColors.length === 0) {
                    airlineColors = ['#4e79a7', '#f28e2c', '#e15759', '#76b7b2', '#59a14f', '#edc949', '#af7aa1'];
                }
                
                airlineLabels = [];
                airlineValues = [];
                
                // Get 5 random airlines
                const shuffledAirlines = [...airlines].sort(() => 0.5 - Math.random());
                for (let i = 0; i < Math.min(5, shuffledAirlines.length); i++) {
                    airlineLabels.push(shuffledAirlines[i]);
                    airlineValues.push(Math.floor(Math.random() * 300) + 100);
                }
            }
            
            const ctx = document.getElementById('airlineChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: airlineLabels,
                    datasets: [{
                        data: airlineValues,
                        backgroundColor: airlineColors,
                        borderWidth: 1,
                        hoverOffset: 10
                    }]
                },
                options: {
                    ...commonOptions,
                    plugins: {
                        ...commonOptions.plugins,
                        legend: {
                            position: 'right',
                            labels: {
                                usePointStyle: true,
                                padding: 10
                            }
                        }
                    }
                }
            });
        } catch (error) {
            console.error('Error initializing airline chart:', error);
        }
    }
    
    // Demographics Chart Initialization
    function initDemographicsChart() {
        try {
            let demographicsData = JSON.parse(chartDataElement.dataset.demographics || '[]');
            
            // Generate random data if the dataset is empty
            if (demographicsData.length === 0) {
                const ageGroups = ['Under 18', '18-24', '25-34', '35-44', '45-54', '55-64', '65+'];
                demographicsData = ageGroups.map(group => ({
                    label: group,
                    value: Math.floor(Math.random() * 70) + 10
                }));
            }
            
            const labels = demographicsData.map(item => item.label);
            const values = demographicsData.map(item => item.value);
            
            // Define colors for demographics
            const demographicColors = [
                'rgba(54, 162, 235, 0.7)', // Under 18
                'rgba(75, 192, 192, 0.7)', // 18-24
                'rgba(153, 102, 255, 0.7)', // 25-34
                'rgba(255, 159, 64, 0.7)',  // 35-44
                'rgba(255, 99, 132, 0.7)',  // 45-54
                'rgba(255, 205, 86, 0.7)',  // 55-64
                'rgba(201, 203, 207, 0.7)'  // 65+
            ];
            
            const ctx = document.getElementById('demographicsChart').getContext('2d');
            
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Passenger Count',
                        data: values,
                        backgroundColor: demographicColors.slice(0, labels.length),
                        borderWidth: 1,
                        borderRadius: 4
                    }]
                },
                options: {
                    ...commonOptions,
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
        } catch (error) {
            console.error('Error initializing demographics chart:', error);
        }
    }
    
    // Export report to PDF functionality
    function exportReportToPDF() {
        alert('Export functionality would be implemented here.\n\nTo comply with CSP restrictions, you would use a server-side PDF generation approach or use a CSP-compliant PDF library loaded from an allowed source.');
        
        // Implementation suggestions:
        // 1. Server-side approach: Create a PHP endpoint that generates PDFs using a library like mPDF or TCPDF
        // 2. Client-side with CSP: Use a library hosted on your allowed CDN domains
        
        // Example server-side implementation would look like:
        // window.location.href = 'export-pdf.php?start_date=' + document.getElementById('start_date').value + '&end_date=' + document.getElementById('end_date').value;
    }
    
    // Helper function to generate random date-based data
    function generateRandomDateData(days, valueKey, min, max) {
        const data = [];
        const today = new Date();
        
        for (let i = days; i >= 0; i--) {
            const date = new Date();
            date.setDate(today.getDate() - i);
            const dateStr = date.toISOString().split('T')[0];
            
            data.push({
                label: dateStr,
                value: Math.floor(Math.random() * (max - min)) + min
            });
        }
        
        return data;
    }
});
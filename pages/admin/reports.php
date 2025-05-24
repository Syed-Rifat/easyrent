<?php
// Session check and authentication
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    header("Location: ../auth/login.php");
    exit();
}

// Include database connection
require_once "../../database/config.php";

// Get admin information
$admin_id = $_SESSION['user_id'];
$admin_name = $_SESSION['full_name'];

// Get date ranges for filtering
$current_month = date('Y-m');
$previous_month = date('Y-m', strtotime('-1 month'));
$current_year = date('Y');

// Get monthly booking stats
$monthly_booking_query = "SELECT 
                            DATE_FORMAT(created_at, '%Y-%m') as month,
                            COUNT(*) as booking_count,
                            SUM(total_amount) as total_revenue
                          FROM bookings
                          WHERE status IN ('confirmed', 'completed')
                          GROUP BY DATE_FORMAT(created_at, '%Y-%m')
                          ORDER BY month DESC
                          LIMIT 6";
$monthly_bookings = $conn->query($monthly_booking_query);

// Get property type distribution
$property_types_query = "SELECT 
                           property_type,
                           COUNT(*) as count
                         FROM properties
                         GROUP BY property_type";
$property_types = $conn->query($property_types_query);

// Get user type distribution
$user_types_query = "SELECT 
                       user_type,
                       COUNT(*) as count
                     FROM users
                     GROUP BY user_type";
$user_types = $conn->query($user_types_query);

// Get top locations
$top_locations_query = "SELECT 
                          location,
                          COUNT(*) as property_count
                        FROM properties
                        GROUP BY location
                        ORDER BY property_count DESC
                        LIMIT 5";
$top_locations = $conn->query($top_locations_query);
?>

<?php include_once "../includes/header.php"; ?>

<!-- Add print-specific styles -->
<style>
    @media print {
        body {
            background: white;
            color: black;
        }
        
        .container-fluid {
            padding: 0;
            margin: 0;
        }
        
        .card {
            border: 1px solid #ddd;
            break-inside: avoid;
            page-break-inside: avoid;
        }
        
        .card-header {
            background-color: #f8f9fa !important;
            color: black !important;
            border-bottom: 1px solid #ddd;
        }
        
        .table {
            border-collapse: collapse;
            width: 100%;
        }
        
        .table th, .table td {
            border: 1px solid #ddd;
            padding: 8px;
        }
        
        .table th {
            background-color: #f8f9fa;
        }
        
        .btn, .sidebar {
            display: none !important;
        }
        
        .main-content {
            width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .chart-container {
            width: 100% !important;
            height: 400px !important;
        }
        
        .print-header {
            display: block !important;
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        
        .print-footer {
            display: block !important;
            text-align: center;
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #ddd;
            font-size: 12px;
        }
        
        .no-print {
            display: none !important;
        }
    }
    
    /* Screen styles */
    .print-header, .print-footer {
        display: none;
    }
    
    .chart-container {
        position: relative;
        height: 300px;
        margin-bottom: 20px;
    }
    
    /* Header Colors */
    .revenue-header {
        background-color: #e3f2fd !important;
        color: #0d6efd !important;
    }
    
    .property-header {
        background-color: #e8f5e9 !important;
        color: #198754 !important;
    }
    
    .location-header {
        background-color: #fff3e0 !important;
        color: #fd7e14 !important;
    }
    
    .user-header {
        background-color: #f3e5f5 !important;
        color: #6f42c1 !important;
    }
</style>

<!-- Print Header -->
<div class="print-header">
    <h1>EasyRent Management Report</h1>
    <p>Generated on: <?php echo date('F j, Y'); ?></p>
</div>

<!-- Main Content -->
<div class="container-fluid my-5">
    <div class="row">
        <?php include_once "includes/sidebar.php"; ?>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Reports & Analytics</h2>
                <div>
                    <button class="btn btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i> Print Report
                    </button>
                </div>
            </div>
            
            <!-- Revenue Reports -->
            <div class="card mb-4">
                <div class="card-header revenue-header">
                    <h5 class="mb-0">Booking Revenue</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Bookings</th>
                                    <th>Revenue</th>
                                    <th>Trend</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $previous_revenue = 0;
                                if ($monthly_bookings->num_rows > 0): 
                                    while ($month = $monthly_bookings->fetch_assoc()): 
                                        $month_name = date('F Y', strtotime($month['month'] . '-01'));
                                        $trend_icon = '';
                                        $trend_class = '';
                                        
                                        if ($previous_revenue > 0) {
                                            if ($month['total_revenue'] > $previous_revenue) {
                                                $trend_icon = '<i class="fas fa-arrow-up"></i>';
                                                $trend_class = 'text-success';
                                            } else if ($month['total_revenue'] < $previous_revenue) {
                                                $trend_icon = '<i class="fas fa-arrow-down"></i>';
                                                $trend_class = 'text-danger';
                                            } else {
                                                $trend_icon = '<i class="fas fa-equals"></i>';
                                                $trend_class = 'text-muted';
                                            }
                                        }
                                        
                                        $previous_revenue = $month['total_revenue'];
                                ?>
                                    <tr>
                                        <td><?php echo $month_name; ?></td>
                                        <td><?php echo $month['booking_count']; ?></td>
                                        <td>৳<?php echo number_format($month['total_revenue']); ?></td>
                                        <td class="<?php echo $trend_class; ?>"><?php echo $trend_icon; ?></td>
                                    </tr>
                                <?php endwhile; else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No booking data available</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Statistics Cards -->
            <div class="row mb-4">
                <!-- Property Types Distribution -->
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header property-header">
                            <h5 class="mb-0">Property Types</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="propertyTypesChart"></canvas>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Property Type</th>
                                            <th>Count</th>
                                            <th>Distribution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($property_types && $property_types->num_rows > 0): 
                                            $total_properties = 0;
                                            $property_type_data = array();
                                            
                                            // First pass to get total
                                            while ($type = $property_types->fetch_assoc()) {
                                                $total_properties += $type['count'];
                                                $property_type_data[] = $type;
                                            }
                                            
                                            // Second pass to display with percentages
                                            foreach ($property_type_data as $type):
                                                $percentage = ($type['count'] / $total_properties) * 100;
                                        ?>
                                            <tr>
                                                <td><?php echo ucfirst(htmlspecialchars($type['property_type'])); ?></td>
                                                <td><?php echo $type['count']; ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-primary" role="progressbar" 
                                                             style="width: <?php echo $percentage; ?>%" 
                                                             aria-valuenow="<?php echo $percentage; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                            <?php echo round($percentage); ?>%
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No property data available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Top Locations -->
                <div class="col-md-6 mb-3">
                    <div class="card h-100">
                        <div class="card-header location-header">
                            <h5 class="mb-0">Top Locations</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="locationsChart"></canvas>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Location</th>
                                            <th>Property Count</th>
                                            <th>Distribution</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if ($top_locations && $top_locations->num_rows > 0): 
                                            $max_count = 0;
                                            $location_data = array();
                                            
                                            // First pass to get max count
                                            while ($location = $top_locations->fetch_assoc()) {
                                                if ($location['property_count'] > $max_count) {
                                                    $max_count = $location['property_count'];
                                                }
                                                $location_data[] = $location;
                                            }
                                            
                                            // Second pass to display
                                            foreach ($location_data as $location):
                                                $percentage = ($location['property_count'] / $max_count) * 100;
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($location['location']); ?></td>
                                                <td><?php echo $location['property_count']; ?></td>
                                                <td>
                                                    <div class="progress">
                                                        <div class="progress-bar bg-success" role="progressbar" 
                                                             style="width: <?php echo $percentage; ?>%" 
                                                             aria-valuenow="<?php echo $percentage; ?>" 
                                                             aria-valuemin="0" 
                                                             aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; else: ?>
                                            <tr>
                                                <td colspan="3" class="text-center">No location data available</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- User Distribution -->
            <div class="card mb-4">
                <div class="card-header user-header">
                    <h5 class="mb-0">User Distribution</h5>
                </div>
                <div class="card-body">
                    <?php if ($user_types && $user_types->num_rows > 0): 
                        $total_users = 0;
                        $user_type_data = array();
                        $chart_labels = array();
                        $chart_data = array();
                        
                        // First pass to get total and prepare chart data
                        while ($type = $user_types->fetch_assoc()) {
                            $total_users += $type['count'];
                            $user_type_data[] = $type;
                            
                            // Prepare chart data
                            $chart_labels[] = ucfirst($type['user_type']) . 's';
                            $chart_data[] = $type['count'];
                        }
                    ?>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="chart-container">
                                <canvas id="userDistributionChart"></canvas>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>User Type</th>
                                            <th>Count</th>
                                            <th>Percentage</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                        <?php foreach ($user_type_data as $type): 
                            $percentage = ($type['count'] / $total_users) * 100;
                                        ?>
                                        <tr>
                                            <td><?php echo ucfirst($type['user_type']); ?>s</td>
                                            <td><?php echo $type['count']; ?></td>
                                            <td><?php echo round($percentage, 1); ?>%</td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-info">No user data available</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Print Footer -->
<div class="print-footer">
    <p>© <?php echo date('Y'); ?> EasyRent. All rights reserved.</p>
    <p>Page <span class="page-number"></span></p>
</div>

<!-- Add Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // User Distribution Chart
    const userCtx = document.getElementById('userDistributionChart').getContext('2d');
    new Chart(userCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [{
                data: <?php echo json_encode($chart_data); ?>,
                backgroundColor: ['#6f42c1', '#0d6efd', '#198754'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });

    // Property Types Chart
    const propertyCtx = document.getElementById('propertyTypesChart').getContext('2d');
    new Chart(propertyCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode(array_map(function($type) { return ucfirst($type['property_type']); }, $property_type_data)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_column($property_type_data, 'count')); ?>,
                backgroundColor: ['#198754', '#0d6efd', '#fd7e14', '#6f42c1', '#0dcaf0'],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                }
            }
        }
    });

    // Locations Chart
    const locationCtx = document.getElementById('locationsChart').getContext('2d');
    new Chart(locationCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_column($location_data, 'location')); ?>,
            datasets: [{
                label: 'Property Count',
                data: <?php echo json_encode(array_column($location_data, 'property_count')); ?>,
                backgroundColor: '#fd7e14',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
});

// Add page numbers for print
window.onbeforeprint = function() {
    const pageNumbers = document.querySelectorAll('.page-number');
    let page = 1;
    pageNumbers.forEach(el => {
        el.textContent = page++;
    });
};
</script>

<?php include_once "../includes/footer.php"; ?>